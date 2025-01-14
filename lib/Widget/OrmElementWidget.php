<?php

namespace Bendersay\Entityadmin\Widget;

use Bendersay\Entityadmin\Helper\AdminBaseHelper;
use Bendersay\Entityadmin\Helper\EntityHelper;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Виджет выбора записей из ORM.
 *
 * Настройки:
 * - `ENTITY` — (string) класс сущности, из которой будет производиться поиск записией. Должен быть
 * наследником `\Bitrix\Main\Entity\DataManager`.
 * - `ADDITIONAL_URL_PARAMS` — (array) дополнительные параметры для URL с попапом выбора записи.
 * - `TEMPLATE` — (string) шаблон отображения виджета, может принимать значения select и radio, по-умолчанию — select.
 * - `INPUT_SIZE` — (int) значение атрибута size для input.
 * - `WINDOW_WIDTH` — (int) значение width для всплывающего окна выбора элемента.
 * - `WINDOW_HEIGHT` — (int) значение height для всплывающего окна выбора элемента.
 * - `TITLE_FIELD_NAME` — (string) название поля, из которого выводить имя элемента.
 *
 * @author Nik Samokhvalov <nik@samokhvalov.info>
 */
class OrmElementWidget extends NumberWidget
{
    protected static $defaults = [
        'FILTER' => '=',
        'INPUT_SIZE' => 5,
        'WINDOW_WIDTH' => 600,
        'WINDOW_HEIGHT' => 500,
        'TITLE_FIELD_NAME' => 'TITLE',
        'TEMPLATE' => 'select',
        'ADDITIONAL_URL_PARAMS' => [],
    ];

    /**
     * @inheritdoc
     */
    public function getEditHtml(): string
    {
        if ($this->getSettings('TEMPLATE') == 'radio') {
            $html = $this->genEditHtmlInputs();
        } else {
            $html = $this->getEditHtmlSelect();
        }

        return $html;
    }

    /**
     * Генерирует HTML с выбором элемента в виде радио инпутов.
     *
     * @return string
     */
    public function genEditHtmlInputs()
    {
        $return = '';

        $elementList = $this->getOrmElementList();

        if (!is_null($elementList)) {
            foreach ($elementList as $key => $element) {
                $return .= InputType(
                    'radio',
                    $this->getEditInputName(),
                    $element['ID'],
                    $this->getValue(),
                    false,
                    $element['ID'] ?: $element['id']
                );
            }
        } else {
            $return = Loc::getMessage('zebrains_AH_ORM_MISSING_ELEMENTS');
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function getMultipleEditHtml()
    {
        /**
         * @var AdminBaseHelper $linkedHelper
         */
        $linkedHelper = $this->getSettings('ENTITY');
        $inputSize = (int)$this->getSettings('INPUT_SIZE');
        $windowWidth = (int)$this->getSettings('WINDOW_WIDTH');
        $windowHeight = (int)$this->getSettings('WINDOW_HEIGHT');

        $name = 'FIELDS';
        $key = $this->getCode();

        $uniqueId = $this->getEditInputHtmlId();

        $entityListData = $this->getOrmElementData();

        $popupUrl = $linkedHelper::getUrl(
            array_merge(
                [
                    'popup' => 'Y',
                    'eltitle' => $this->getSettings('TITLE_FIELD_NAME'),
                    'n' => $name,
                    'k' => '{{field_id}}',
                ],
                $this->getSettings('ADDITIONAL_URL_PARAMS')
            )
        );

        ob_start();
        ?>

        <div id="<?= $uniqueId ?>-field-container" class="<?= $uniqueId ?>"></div>

        <script>
            var multiple = new MultipleWidgetHelper(
                '#<?= $uniqueId ?>-field-container',
                '<input name="<?=$key?>[{{field_id}}][VALUE]"' +
                'id="<?=$name?>[{{field_id}}]"' +
                'value="{{value}}"' +
                'size="<?=$inputSize?>"' +
                'type="text">' +
                '<input type="button"' +
                'value="..."' +
                'onClick="jsUtils.OpenWindow(<?=$popupUrl?>, <?=$windowWidth?>, <?=$windowHeight?>);">' +
                '&nbsp;<span id="sp_<?=md5($name)?>_{{field_id}}" >{{element_title}}</span>'
            );
            <?php
            if (!empty($entityListData)) {
                foreach ($entityListData as $referenceData) {
                    $elementId = $referenceData['ID'];
                    $elementName = $referenceData[$this->getSettings('TITLE_FIELD_NAME')]
                        ? $referenceData[$this->getSettings('TITLE_FIELD_NAME')]
                        : Loc::getMessage('IBLOCK_ELEMENT_NOT_FOUND');

                    ?>
            multiple.addField({
                value: '<?= $elementId ?>',
                field_id: <?= $elementId ?>,
                element_title: '<?= static::prepareToJs($elementName) ?>'
            });
            <?php
                }
            }
        ?>
            multiple.addField();
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * @inheritdoc
     */
    public function getValueReadonly()
    {
        $entityData = $this->getOrmElementData();

        if (!empty($entityData)) {
            $entityName = $entityData[$this->getSettings('TITLE_FIELD_NAME')]
                ? $entityData[$this->getSettings('TITLE_FIELD_NAME')]
                : Loc::getMessage('IBLOCK_ELEMENT_NOT_FOUND');

            return '[' . $entityData['ID'] . ']' . static::prepareToOutput($entityName);
        }

        return '';
    }

    /**
     * @inheritdoc
     */
    public function getMultipleValueReadonly()
    {
        $entityListData = $this->getOrmElementData();

        if (!empty($entityListData)) {
            $multipleData = [];

            foreach ($entityListData as $entityData) {
                $entityName = $entityData[$this->getSettings('TITLE_FIELD_NAME')]
                    ? $entityData[$this->getSettings('TITLE_FIELD_NAME')]
                    : Loc::getMessage('IBLOCK_ELEMENT_NOT_FOUND');

                $multipleData[] = '[' . $entityData['ID'] . ']' . static::prepareToOutput($entityName);
            }

            return implode('<br />', $multipleData);
        }

        return '';
    }

    /**
     * @inheritdoc
     */
    public function generateRow(&$row, $data)
    {
        if ($this->getSettings('MULTIPLE')) {
            $strElement = $this->getMultipleValueReadonly();
        } else {
            $strElement = $this->getValueReadonly();
        }

        $row->AddViewField($this->getCode(), $strElement);
    }

    /**
     * @inheritdoc
     */
    public function showFilterHtml()
    {
        /**
         * @var AdminBaseHelper $linkedHelper
         */
        $linkedHelper = $this->getSettings('ENTITY');

        if ($this->getSettings('MULTIPLE')) {
        } else {
            $inputSize = (int)$this->getSettings('INPUT_SIZE');
            $windowWidth = (int)$this->getSettings('WINDOW_WIDTH');
            $windowHeight = (int)$this->getSettings('WINDOW_HEIGHT');

            $name = 'FIND';
            $key = $this->getCode();

            print '<tr>';
            print '<td>' . $this->getSettings('TITLE') . '</td>';

            $popupUrl = $linkedHelper::getUrl(
                array_merge(
                    [
                        'popup' => 'Y',
                        'eltitle' => $this->getSettings('TITLE_FIELD_NAME'),
                        'n' => $name,
                        'k' => $key,
                    ],
                    $this->getSettings('ADDITIONAL_URL_PARAMS')
                )
            );

            $editStr = '<input name="' . $this->getFilterInputName() . '"
                     id="' . $name . '[' . $key . ']"
                     value="' . $this->getCurrentFilterValue() . '"
                     size="' . $inputSize . '"
                     type="text">'
                . '<input type="button"
                    value="..."
                    onClick="jsUtils.OpenWindow(\'' . $popupUrl . '\', ' . $windowWidth . ', ' . $windowHeight . ');">';

            print '<td>' . $editStr . '</td>';

            print '</tr>';
        }
    }

    /**
     * Генерирует HTML с выбором элемента во вcплывающем окне, шаблон select.
     *
     * @return string
     */
    protected function getEditHtmlSelect()
    {
        /**
         * @var AdminBaseHelper $linkedHelper
         */
        $entity = $this->getSettings('ENTITY');
        $inputSize = (int)$this->getSettings('INPUT_SIZE');
        $windowWidth = (int)$this->getSettings('WINDOW_WIDTH');
        $windowHeight = (int)$this->getSettings('WINDOW_HEIGHT');

        $name = 'FIELDS';
        $key = $this->getCode();

        $entityData = $this->getOrmElementData();

        if (!empty($entityData)) {
            $elementId = $entityData['ID'];
            $elementName = $entityData[$this->getSettings('TITLE_FIELD_NAME')]
                ? $entityData[$this->getSettings('TITLE_FIELD_NAME')]
                : Loc::getMessage('IBLOCK_ELEMENT_NOT_FOUND');
        } else {
            $elementId = '';
        }

        $popupUrl = EntityHelper::getReferenceElementListUrl(
            array_merge(
                [
                    'entity' => $entity,
                    'table' => $entity::getTableName(),
                    'field_id' => $name . '[' . $key . ']',
                ],
                $this->getSettings('ADDITIONAL_URL_PARAMS')
            )
        );

        return '<input name="' . $this->getEditInputName() . '"
                     id="' . $name . '[' . $key . ']"
                     value="' . $elementId . '"
                     size="' . $inputSize . '"
                     type="text">'
            . '<input type="button"
                    value="..." onClick="jsUtils.OpenWindow(\'' . $popupUrl . '\', ' . $windowWidth . ', '
            . $windowHeight . ');">' . '&nbsp;<span id="sp_' . md5($name) . '_' . $key . '" >'
            . static::prepareToOutput($elementName)
            . '</span>';
    }

    /**
     * Получает информацию о записях, к которым осуществлена привязка.
     *
     * @return array
     *
     * @throws \Bitrix\Main\ArgumentException
     */
    protected function getOrmElementData()
    {
        $refInfo = [];
        $valueList = null;
        $linkedModel = $this->getLinkedModel();

        if ($this->getSettings('MULTIPLE')) {
            $entityName = $this->entityName;

            $rsMultEntity = $entityName::getList([
                'select' => ['REFERENCE_' => $this->getCode() . '.*'],
                'filter' => ['=ID' => $this->data['ID']],
            ]);

            while ($multEntity = $rsMultEntity->fetch()) {
                $valueList[$multEntity['REFERENCE_VALUE']] = $multEntity['REFERENCE_VALUE'];
            }
        } else {
            $value = $this->getValue();

            if (!empty($value)) {
                $valueList[$value] = $value;
            }
        }

        if ($valueList) {
            $rsEntity = $linkedModel::getList([
                'filter' => ['ID' => $valueList],
            ]);

            while ($entity = $rsEntity->fetch()) {
                if (in_array($entity['ID'], $valueList)) {
                    unset($valueList[$entity['ID']]);
                }

                if ($this->getSettings('MULTIPLE')) {
                    $refInfo[] = $entity;
                } else {
                    $refInfo = $entity;

                    break;
                }
            }

            foreach ($valueList as $entityId) {
                if ($this->getSettings('MULTIPLE')) {
                    $refInfo[] = ['ID' => $entityId];
                } else {
                    $refInfo = ['ID' => $entityId];

                    break;
                }
            }
        }

        return $refInfo;
    }

    /**
     * Получает информацию о всех активных элементах для их выбора в виджете.
     *
     * @return array
     *
     * @throws \Bitrix\Main\ArgumentException
     */
    protected function getOrmElementList()
    {
        $valueList = null;
        $linkedModel = $this->getLinkedModel();

        $rsEntity = $linkedModel::getList([
            'select' => [
                'ID',
            ],
        ]);

        while ($entity = $rsEntity->fetch()) {
            $valueList[] = $entity;
        }

        return $valueList;
    }

    /**
     * Возвращает связанную модель.
     *
     * @return \Bitrix\Main\ORM\Data\DataManager
     */
    protected function getLinkedModel()
    {
        return $this->getSettings('ENTITY');
    }
}
