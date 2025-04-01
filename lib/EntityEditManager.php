<?php

namespace Bendersay\Entityadmin;

use Bendersay\Entityadmin\Enum\AccessLevelEnum;
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
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\DecimalField;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\ManyToMany;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\ScalarField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\SystemException;

/**
 * Менеджер для работы с элементом на детальной странице
 */
class EntityEditManager extends AbstractEntityManager
{
    /** @var array id элемента с которым работаем, массив (составной ключ) */
    protected array $elementPrimary = [];

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
        $id = $this->request->get('id');
        $this->actionAdd = $this->request->get('add') === 'Y';

        if (!$this->actionAdd && empty($id)) {
            /* @phpstan-ignore-next-line */
            throw new ArgumentOutOfRangeException($id, 1, PHP_INT_MAX);
        }
        parse_str($id, $this->elementPrimary);

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

        if ($this->modRight === AccessLevelEnum::WRITE->value && !$this->actionAdd) {
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
            'disabled' => $this->modRight !== AccessLevelEnum::WRITE->value,
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
        foreach ($this->fieldList as $field) {
            if ($field instanceof ScalarField && $field->isPrivate()) {
                continue;
            }

            $expressionField = false;
            $referenceField = false;
            if ($field instanceof ArrayField) {
                $primary = $field->isPrimary();
                $autocomplete = $field->isAutocomplete();
                $code = $field->getName();
                $title = !empty($field->getTitle()) ? $field->getTitle() : $field->getName();
                $required = $field->isRequired();
            } elseif ($field instanceof ScalarField) {
                $primary = $field->isPrimary();
                $autocomplete = $field->isAutocomplete();
                $code = $field->getName();
                $title = !empty($field->getTitle()) ? $field->getTitle() : $field->getName();
                $required = $field->isRequired();
                $referenceField = array_key_exists($code, $this->fieldReferenceList);
            } elseif ($field instanceof ExpressionField) {
                $primary = false;
                $autocomplete = false;
                $code = $field->getName();
                $title = !empty($field->getTitle()) ? $field->getTitle() : $field->getName();
                $required = false;
                $expressionField = true;
            } elseif ($field instanceof Reference || $field instanceof OneToMany || $field instanceof ManyToMany) {
                continue;
            } else {
                throw new NotSupportedException('field not supported');
            }

            // делаем редактируемыми primary поля без autoincrement
            if (($autocomplete || $expressionField) && $this->actionAdd) {
                continue;
            }

            //В таком формате данные надо передавать в виджет
            $value = [
                $code => $this->elementData[$code],
            ];

            if ($referenceField) {
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
            } else {
                switch ($field) {
                    case $field instanceof IntegerField:
                    case $field instanceof FloatField:
                    case $field instanceof DecimalField:
                        $widget = new NumberWidget();

                        break;

                    case $field instanceof TextField:
                        $widget = new TextAreaWidget();

                        break;

                    case $field instanceof StringField:
                    case $field instanceof ExpressionField:
                        $widget = new StringWidget();

                        break;

                    case $field instanceof DateField:
                    case $field instanceof DatetimeField:
                        $widget = new DateTimeWidget();

                        break;

                    case $field instanceof BooleanField:
                        $widget = new CheckboxWidget();

                        break;

                    case $field instanceof ArrayField:
                        $field->configureSerializationJson();
                        $widget = new JsonArrayWidget();

                        break;

                    case $field instanceof EnumField:
                        $widget = new EnumWidget();
                        $widget->setEnumList($this->getEnumFieldItemList($field));

                        break;

                    default:
                        continue 2;
                }
            }

            $showBasicEditField = ($primary && !$this->actionAdd)
                || $autocomplete || $expressionField || $this->modRight !== AccessLevelEnum::WRITE->value;

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
            $filter = [];
            foreach ($this->primaryFieldList as $field) {
                $filter['=' . $field] = $this->elementPrimary[$field];
            }
            $data = $this->entityClass::getRow([
                'select' => $this->getSelectDefault(),
                'filter' => $filter,
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
