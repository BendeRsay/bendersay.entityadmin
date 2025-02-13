<?php

namespace Bendersay\Entityadmin;

use Bendersay\Entityadmin\Helper\EntityHelper;
use Bendersay\Entityadmin\Widget\CheckboxWidget;
use Bendersay\Entityadmin\Widget\DateTimeWidget;
use Bendersay\Entityadmin\Widget\EnumWidget;
use Bendersay\Entityadmin\Widget\JsonArrayWidget;
use Bendersay\Entityadmin\Widget\NumberWidget;
use Bendersay\Entityadmin\Widget\OrmElementWidget;
use Bendersay\Entityadmin\Widget\StringWidget;
use Bendersay\Entityadmin\Widget\TextAreaWidget;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\ManyToMany;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\ScalarField;
use Bitrix\Main\SystemException;

/**
 * Менеджер для работы с элементом на детальной странице
 */
class EntityEditManager extends AbstractEntityManager
{
    /** @var null|string id элемента с которым работаем, может быть строкой или числом */
    protected ?string $elementId;

    /** @var array данные элемента */
    protected array $elementData = [];

    /** @var bool Действие создание элемента */
    protected bool $actionAdd = false;

    /**
     * @throws LoaderException
     * @throws SystemException
     */
    public function __construct()
    {
        parent::__construct();
        $this->elementId = $this->request->get('id');
        $this->actionAdd = $this->request->get('add') === 'Y';

        if (!$this->actionAdd && empty($this->elementId)) {
            /* @phpstan-ignore-next-line */
            throw new ArgumentOutOfRangeException($this->elementId, 1, PHP_INT_MAX);
        }
        $this->elementData = $this->getElementData();
    }

    /**
     * Список вкладок для страницы
     *
     * @see https://dev.1c-bitrix.ru/api_help/main/general/admin.section/classes/cadmintabcontrol/cadmintabcontrol.php
     *
     * @return array[]
     */
    public function getTabList(): array
    {
        return [
            [
                'DIV' => 'edit',
                'TAB' => Loc::getMessage('BENDERSAY_ENTITYADMIN_ELEMENT_TAB'),
                'ICON' => 'main_user_edit',
                'TITLE' => Loc::getMessage(
                    'BENDERSAY_ENTITYADMIN_ELEMENT_TAB_TITLE' . ($this->actionAdd ? '_ADD' : ''),
                    ['%title%' => $this->tableTitle]
                ),
            ],
        ];
    }

    /**
     * URL куда отправить форму
     *
     * @return string|null
     */
    public function getActionUrl(): ?string
    {
        return $this->request->getRequestUri();
    }

    /**
     * Меню детальной страницы
     *
     * @return array[]
     */
    public function getMenu(): array
    {
        $result[] = [
            'TEXT' => $this->tableTitle,
            'TITLE' => Loc::getMessage('BENDERSAY_ENTITYADMIN_RETURN_TO_LIST_BUTTON_TITLE'),
            'LINK' => EntityHelper::getListUrl([
                'entity' => $this->entityClass,
            ]),
            'ICON' => 'btn_list',
        ];

        if ($this->modRight === 'W') {
            $result[] = [
                'TEXT' => Loc::getMessage('BENDERSAY_ENTITYADMIN_DELETE_BUTTON_TEXT'),
                'TITLE' => Loc::getMessage('BENDERSAY_ENTITYADMIN_DELETE_BUTTON_TITLE'),
                'LINK' => 'javascript: if (confirm("'
                    . Loc::getMessage('BENDERSAY_ENTITYADMIN_DELETE_ELEMENT_ACTION_CONFIRM')
                    . '")) {document.getElementById("delete").value = "Y"; document.getElementById("editform").submit()}',
                'ICON' => 'btn_delete',
            ];
        }

        return $result;
    }

    /**
     * Кнопки внизу формы редактирования
     *
     * @see https://dev.1c-bitrix.ru/api_help/main/general/admin.section/classes/cadmintabcontrol/buttons.php
     *
     * @return array
     */
    public function getTabControlButtonList(): array
    {
        return [
            'disabled' => $this->modRight !== 'W',
            'back_url' => EntityHelper::getListUrl([
                'entity' => $this->entityClass,
            ]),
        ];
    }

    /**
     * Рисуем список полей для редактирования
     * TODO разбить на отдельные методы
     *
     * @return void
     *
     * @throws ArgumentException
     * @throws NotSupportedException
     * @throws SystemException
     */
    public function renderFieldList(): void
    {
        $identity = $this->entityClass::getEntity()->getAutoIncrement();

        foreach ($this->fieldList as $field) {
            if ($field instanceof ScalarField && $field->isPrivate()) {
                continue;
            }

            $expressionField = false;
            if ($field instanceof ArrayField) {
                $reflection = new \ReflectionClass($field);
                $serializationType = $reflection->getProperty('serializationType');
                $serializationType->setAccessible(true);

                $primary = $field->isPrimary();
                $autocomplete = $field->isAutocomplete();
                $code = $field->getName();
                $title = !empty($field->getTitle()) ? $field->getTitle() : $field->getName();
                $dataType = $serializationType->getValue($field);
                $required = $field->isRequired();
            } elseif ($field instanceof ScalarField) {
                $primary = $field->isPrimary();
                $autocomplete = $field->isAutocomplete();
                $code = $field->getName();
                $title = !empty($field->getTitle()) ? $field->getTitle() : $field->getName();
                $dataType = array_key_exists($code, $this->fieldReferenceList) ? 'reference' : $field->getDataType();
                $required = $field->isRequired();
            } elseif ($field instanceof ExpressionField) {
                $primary = false;
                $autocomplete = false;
                $code = $field->getName();
                $title = !empty($field->getTitle()) ? $field->getTitle() : $field->getName();
                $dataType = array_key_exists($code, $this->fieldReferenceList) ? 'reference' : $field->getDataType();
                $required = false;
                $expressionField = true;
            } elseif ($field instanceof Reference || $field instanceof OneToMany || $field instanceof ManyToMany) {
                continue;
            } else {
                throw new NotSupportedException('field not supported');
            }

            // делаем редактируемыми primary поля без autoincrement
            if ((
                ($primary && $identity !== null)
                    || $autocomplete || $expressionField
            ) && $this->actionAdd) {
                continue;
            }

            //В таком формате данные надо передавать в виджет
            $value = [
                $code => $this->elementData[$code],
            ];

            switch ($dataType) {
                case 'integer':
                case 'float':
                    $widget = new NumberWidget();

                    break;
                case 'string':
                    $widget = new StringWidget();

                    break;
                case 'text':
                    $widget = new TextAreaWidget();

                    break;
                case 'date':
                case 'datetime':
                    $widget = new DateTimeWidget();

                    break;
                case 'boolean':
                    $widget = new CheckboxWidget();

                    break;
                case 'json':
                    $widget = new JsonArrayWidget();

                    break;
                case 'reference':
                    $widget = new OrmElementWidget(
                        [
                            'ENTITY' => $this->fieldReferenceList[$code]->entity,
                            'INPUT_SIZE' => 5,
                            'WINDOW_WIDTH' => 1000,
                            'WINDOW_HEIGHT' => 800,
                            'TITLE_FIELD_NAME' => $code,
                            'TEMPLATE' => 'select',
                            'ADDITIONAL_URL_PARAMS' => [],
                            'REFERENCE_VALUE' => $this->fieldReferenceList[$field->getName(
                            )]->itemList[$this->elementData[$code]],
                        ]
                    );

                    break;
                case 'enum':
                    $widget = new EnumWidget();
                    $widget->setEnumList($this->getEnumFieldItemList($field));

                    break;
                default:
                    continue 2;
            }

            $showBasicEditField = ($primary && $identity !== null)
                || $autocomplete || $expressionField || $this->modRight !== 'W';

            $widget->setEntityName($this->entityClass);
            $widget->setCode($code);
            $widget->setTitle($title);
            $widget->setData($value);
            $widget->setRequired($required);
            $widget->showBasicEditField($showBasicEditField);
        }
    }

    /**
     * Очищаем данные в сессии
     *
     * @return void
     */
    public function clearDataBySession(): void
    {
        $this->localSession->clear();
    }

    /**
     * Получаем поля для select
     *
     * @return array
     */
    public function getSelect(): array
    {
        $result = [];

        foreach ($this->fieldList as $field) {
            if ($field instanceof ScalarField || $field instanceof ExpressionField) {
                if ($field->isPrivate()) {
                    continue;
                }
                $result[] = $field->getName();
            }
        }

        return $result;
    }

    /**
     * Получаем данные элемента
     *
     * @return array
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected function getElementData(): array
    {
        if ($this->actionAdd) {
            $data = $this->getElementDataBySession();
        } else {
            $data = $this->entityClass::getRow([
                'select' => $this->getSelect(),
                'filter' => [
                    '=' . $this->primaryCode => $this->elementId,
                ],
            ]);
            if (empty($data)) {
                throw new ObjectNotFoundException(Loc::getMessage('BENDERSAY_ENTITYADMIN_ELEMENT_NOT_FOUND'));
            }
        }

        return $data;
    }

    /**
     * Достаем данные из сессии, если есть
     *
     * @return array
     */
    protected function getElementDataBySession(): array
    {
        $result = [];
        $postFieldList = $this->localSession->get('postFieldList');
        if (!empty($postFieldList)) {
            $result = $postFieldList;
            $this->localSession->clear();
        }

        return $result;
    }

}
