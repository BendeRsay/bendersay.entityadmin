<?php

namespace Bendersay\Entityadmin;

use Bendersay\Entityadmin\Helper\EntityHelper;
use Bendersay\Entityadmin\Helper\FieldHelper;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Grid\Options;
use Bitrix\Main\Grid\Panel\Snippet;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Field;
use Bitrix\Main\ORM\Fields\Relations\ManyToMany;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\ScalarField;
use Bitrix\Main\ORM\Query\Result as QueryResult;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\PageNavigation;

/**
 * Менеджер для работы со списком элементов в компоненте main.ui.grid
 *
 * @see https://dev.1c-bitrix.ru/api_d7/bitrix/main/systemcomponents/gridandfilter/mainuigrid/parameters.php
 */
class EntityListManager extends AbstractEntityManager
{
    /** @var int Кол-во страниц по умолчанию */
    public const int DEFAULT_PAGE_SIZE = 20;

    /** @var Options Опции грида */
    protected Options $gridOption;

    /** @var PageNavigation Объект пагинации */
    protected PageNavigation $pageNavigation;

    protected QueryResult $queryResult;

    /**
     * @throws LoaderException
     * @throws SystemException
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->gridOption = new Options($this->getGridId());
        $this->gridOption->setPageSize(self::DEFAULT_PAGE_SIZE);
        $this->gridOption->save();

        $this->pageNavigation = new PageNavigation($this->getGridId());
        $this->pageNavigation->allowAllRecords(true)
            ->setPageSize(
                $this->gridOption->GetNavParams([
                    'nPageSize' => self::DEFAULT_PAGE_SIZE,
                ])['nPageSize']
            )
            ->initFromUri();
    }

    /**
     * Возвращаем id грида
     *
     * @return string {getTableName()}_list
     */
    public function getGridId(): string
    {
        return $this->entityClass::getTableName() . '_list';
    }

    /**
     * Возвращаем id фильтра для грида
     *
     * @return string {getTableName()}_filter_list
     */
    public function getFilterGridId(): string
    {
        return $this->entityClass::getTableName() . '_filter_list';
    }

    /**
     * Возвращаем набор колонок грида
     *
     * @see https://dev.1c-bitrix.ru/api_d7/bitrix/main/systemcomponents/gridandfilter/mainuigrid/columns.php
     *
     * @return array
     *
     * @throws NotSupportedException
     */
    public function getColumnList(): array
    {
        $columnList = [];

        foreach ($this->fieldList as $key => $field) {
            if ($field instanceof Field) {
                $column = [
                    'id' => $field->getName(),
                    'name' => !empty($field->getTitle()) ? $field->getTitle() : $field->getName(),
                    'sort' => $field->getName(),
                    'title' => $field->getName(),
                    'default' => true,
                ];
            }

            if ($field instanceof ArrayField) {
                $reflection = new \ReflectionClass($field);
                $serializationType = $reflection->getProperty('serializationType');

                $column['editable'] = $serializationType->getValue($field) === 'json'; //todo вынести значение
                $columnList[] = $column;
            } elseif ($field instanceof ScalarField) {
                $columnList[] = FieldHelper::preparedColumn($field, $column);
            } elseif ($field instanceof Reference || $field instanceof OneToMany || $field instanceof ManyToMany || $field instanceof ExpressionField) {
                continue;
            } elseif (is_array($field)) {
                $column = [
                    'id' => $key,
                    'name' => $field['title'] ?? $key,
                    'sort' => $key,
                    'title' => $key,
                    'default' => true,
                ];
                $columnList[] = FieldHelper::preparedColumnArray($field, $column);
            } else {
                throw new NotSupportedException('field not supported');
            }
        }

        return $columnList;
    }

    /**
     * Возвращаем строки для грида
     * TODO разбить на методы
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     */
    public function getRowList(): array
    {
        $rowList = [];
        $elementList = $this->getElementList();

        foreach ($elementList as $elemKey => $elem) {
            $rowList[$elemKey]['actions'] = $this->getActionList($elem);

            foreach ($this->fieldList as $fieldKey => $field) {
                if ($field instanceof ArrayField) {
                    try {
                        if (empty($elem[$field->getName()])) {
                            $fieldDataJson = null;
                        } else {
                            $fieldDataJson = $field->encode($elem[$field->getName()]);
                        }
                    } catch (\JsonException|ArgumentException $ex) {
                        $fieldDataJson = null;
                    }

                    $rowList[$elemKey]['data'][$field->getName()] = $fieldDataJson;
                } elseif ($field instanceof ScalarField) {
                    if ($field->isPrimary()) {
                        $rowList[$elemKey]['id'] = $elem[$field->getName()];
                    }
                    $rowList[$elemKey]['data'][$field->getName()] = $elem[$field->getName()];

                    if ($field instanceof BooleanField) {
                        $rowList[$elemKey]['data'][$field->getName()] = $field->booleanizeValue(
                            $elem[$field->getName()]
                        ) ? 'Y' : 'N';
                    }
                    if ($field instanceof DatetimeField && !empty($elem[$field->getName()])) {
                        $rowList[$elemKey]['data'][$field->getName()] = $elem[$field->getName()]->toString();
                    }
                } elseif ($field instanceof Reference || $field instanceof OneToMany || $field instanceof ManyToMany || $field instanceof ExpressionField) {
                    continue;
                } elseif (is_array($field)) {
                    $value = $elem[$fieldKey];
                    if ($field['primary']) {
                        $rowList[$elemKey]['id'] = $value;
                    }
                    $rowList[$elemKey]['data'][$fieldKey] = $value;

                    if ($value instanceof DateTime) {
                        $rowList[$elemKey]['data'][$fieldKey] = $value->toString();
                    }
                } else {
                    throw new NotSupportedException('field not supported');
                }
            }
        }

        return $rowList;
    }

    /**
     * Возвращаем сортировку
     *
     * @return array
     */
    public function getSort(): array
    {
        $defaultSortField = is_string(array_key_first($this->fieldList))
            ? array_key_first($this->fieldList)
            : $this->fieldList[array_key_first($this->fieldList)]->getName();

        return $this->gridOption->getSorting(
            [
                'sort' => [
                    $defaultSortField => 'asc',
                ],
                'vars' => [
                    'by' => 'by',
                    'order' => 'order',
                ],
            ]
        );
    }

    /**
     * Возвращаем объект пагинации
     *
     * @return PageNavigation
     */
    public function getPageNavigation(): PageNavigation
    {
        return $this->pageNavigation;
    }

    /**
     * Возвращаем Набор действий/элементов для панели групповых действий
     *
     * @return array[]
     */
    public function getActionPanel(): array
    {
        $snippet = new Snippet();

        return [
            'GROUPS' => [
                [
                    'ITEMS' => [
                        $snippet->getEditButton(),
                        $snippet->getRemoveButton(),
                        $snippet->getForAllCheckbox(),
                    ],
                ],
            ],
        ];
    }

    /**
     * Общее кол-во элементов в запросе
     *
     * @throws ObjectPropertyException
     */
    public function getTotalRowsCount(): int
    {
        return $this->queryResult->getCount();
    }

    /**
     * Фильтр по полям
     *
     * @see https://dev.1c-bitrix.ru/api_d7/bitrix/main/systemcomponents/gridandfilter/mainuifilter.php
     *
     * @return array
     *
     * @throws NotSupportedException
     */
    public function getUiFilter(): array
    {
        $uiFilter = [];
        $keyFirst = array_key_first($this->fieldList);

        foreach ($this->fieldList as $key => $field) {
            if ($field instanceof ScalarField) {
                $uiFilterTmp = [
                    'id' => $field->getName(),
                    'name' => !empty($field->getTitle()) ? $field->getTitle() : $field->getName(),
                    'type' => FieldHelper::getUiFilterTypeByObject($field),
                    'default' => $key === $keyFirst,
                ];
                // для BooleanField делаем фильтр цифровым
                if ($field instanceof BooleanField) {
                    $uiFilterTmp['valueType'] = 'numeric';
                }
                $uiFilter[] = $uiFilterTmp;
            } elseif ($field instanceof Reference || $field instanceof OneToMany || $field instanceof ManyToMany || $field instanceof ExpressionField) {
                continue;
            } elseif (is_array($field)) {
                $uiFilter[] = [
                    'id' => $key,
                    'name' => $field['title'] ?? $key,
                    'type' => FieldHelper::getUiFilterTypeByArray($field),
                    'default' => $key === $keyFirst,
                ];
            } else {
                throw new NotSupportedException('field not supported');
            }
        }

        return $uiFilter;
    }

    /**
     * Выбираем элементы сущности
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     */
    protected function getElementList(): array
    {
        $this->queryResult = $this->entityClass::getList([
            'select' => $this->gridOption->getUsedColumns() ?: ['*'],
            'filter' => $this->getFilter(),
            'offset' => $this->pageNavigation->getOffset(),
            'limit' => $this->pageNavigation->getLimit(),
            'order' => $this->getSort()['sort'],
            'count_total' => true,
        ]);

        $this->pageNavigation->setRecordCount($this->queryResult->getCount());

        return $this->queryResult->fetchAll();
    }

    /**
     * Получаем фильтр для выборки
     *
     * TODO: хоть поиск и отключен в параметрах компонента 'DISABLE_SEARCH' => true,
     * но есть возможность написать текст в input и он приходит. Додумать обработку.
     * $searchString = $filterOption->getSearchString();
     *
     * @return array
     *
     * @throws NotSupportedException
     */
    protected function getFilter(): array
    {
        $filterOption = new \Bitrix\Main\UI\Filter\Options($this->getFilterGridId());

        return $filterOption->getFilterLogic($this->getUiFilter());
    }

    /**
     * Меню для каждого элемента
     *
     * @param array $elem
     *
     * @return array
     *
     * @throws ArgumentException
     * @throws SystemException
     */
    protected function getActionList(array $elem): array
    {
        $primaryKey = $this->entityClass::getEntity()->getPrimary();

        return [
            [
                'text' => Loc::getMessage('BENDERSAY_ENTITYADMIN_VIEW_ELEMENT_ACTION_TEXT'),
                'default' => true,
                'onclick' => 'document.location.href="'
                    . EntityHelper::getEditUrl(
                        [
                            'entity' => $this->entityClass,
                            'id' => $elem[$primaryKey],
                        ]
                    )
                    . '"',
            ],
            [
                'text' => Loc::getMessage('BENDERSAY_ENTITYADMIN_DELETE_ELEMENT_ACTION_TEXT'),
                'default' => true,
                'onclick' => 'if(confirm("'
                    . Loc::getMessage('BENDERSAY_ENTITYADMIN_DELETE_ELEMENT_ACTION_CONFIRM')
                    . '")){document.location.href="'
                    . EntityHelper::getListUrl(
                        [
                            'entity' => $this->entityClass,
                            'delete' => 'Y',
                            'id' => $elem[$primaryKey],
                            'sessid' => bitrix_sessid(),
                        ]
                    )
                    . '"}',
            ],
        ];
    }

}
