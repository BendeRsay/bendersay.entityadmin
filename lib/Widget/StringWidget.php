<?php

namespace Bendersay\Entityadmin\Widget;

use Bendersay\Entityadmin\Helper\AdminEditHelper;
use Bendersay\Entityadmin\Helper\AdminListHelper;
use Bendersay\Entityadmin\Helper\AdminSectionListHelper;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Виджет строки с текстом.
 *
 * Доступные опции:
 * <ul>
 * <li> <b>EDIT_LINK</b> - отображать в виде ссылки на редактирование элемента </li>
 * <li> <b>STYLE</b> - inline-стили для input </li>
 * <li> <b>SIZE</b> - значение атрибута size для input </li>
 * <li> <b>TRANSLIT</b> - true, если поле будет транслитерироваться в символьный код</li>
 * <li> <b>MULTIPLE</b> - поддерживается множественный ввод. В таблице требуется наличие поля VALUE</li>
 * </ul>
 */
class StringWidget extends HelperWidget
{
    protected static $defaults = [
        'FILTER' => '%', //Фильтрация по подстроке, а не по точному соответствию.
        'EDIT_IN_LIST' => true,
    ];

    /**
     * Генерирует HTML для поля в списке
     *
     * @param \CAdminListRow $row
     * @param array $data - данные текущей строки
     *
     * @see AdminListHelper::addRowCell();
     *
     */
    public function generateRow(&$row, $data)
    {
        if ($this->getSettings('MULTIPLE')) {
        } else {
            if ($this->getSettings('EDIT_LINK') || $this->getSettings('SECTION_LINK')) {
                $pk = $this->helper->pk();

                if ($this->getSettings('SECTION_LINK')) {
                    $params = $this->helper->isPopup() ? $_GET : [];
                    $params['ID'] = $this->data[$pk];
                    $listHelper = $this->helper->getHelperClass(
                        $this->helper->isPopup() ? AdminSectionListHelper::className() : AdminListHelper::className()
                    );
                    $pageUrl = $listHelper::getUrl($params);
                    $value = '<span class="adm-submenu-item-link-icon adm-list-table-icon iblock-section-icon"></span>';
                } else {
                    $editHelper = $this->helper->getHelperClass(AdminEditHelper::className());
                    $pageUrl = $editHelper::getUrl([
                        'ID' => $this->data[$pk],
                    ]);
                }

                $value .= '<a href="' . $pageUrl . '">' . static::prepareToOutput($this->getValue()) . '</a>';
            } else {
                $value = static::prepareToOutput($this->getValue());
            }

            if ($this->getSettings('EDIT_IN_LIST') and !$this->getSettings('READONLY')) {
                $row->AddInputField($this->getCode(), ['style' => 'width:90%']);
            }

            $row->AddViewField($this->getCode(), $value);
        }
    }

    /**
     * @inheritdoc
     */
    public function showFilterHtml()
    {
        if ($this->getSettings('MULTIPLE')) {
        } else {
            print '<tr>';
            print '<td>' . $this->getSettings('TITLE') . '</td>';

            if ($this->isFilterBetween()) {
                list($from, $to) = $this->getFilterInputName();
                print '<td>
            <div class="adm-filter-box-sizing">
                <span style="display: inline-block; left: 11px; top: 5px; position: relative;">От:</span>
                <div class="adm-input-wrap" style="display: inline-block">
                    <input type="text" class="adm-input" name="' . $from . '" value="' . $$from . '">
                </div>
                <span style="display: inline-block; left: 11px; top: 5px; position: relative;">До:</span>
                <div class="adm-input-wrap" style="display: inline-block">
                    <input type="text" class="adm-input" name="' . $to . '" value="' . $$to . '">
                </div>
            </div>
            </td> ';
            } else {
                print '<td><input type="text" name="' . $this->getFilterInputName(
                ) . '" size="47" value="' . $this->getCurrentFilterValue() . '"></td>';
            }

            print '</tr>';
        }
    }

    /**
     * @inheritdoc
     */
    protected function getEditHtml(): string
    {
        $style = $this->getSettings('STYLE');
        $size = $this->getSettings('SIZE');

        $link = '';

        if ($this->getSettings('TRANSLIT')) {
            //TODO: refactor this!
            $uniqId = get_class($this->entityName) . '_' . $this->getCode();
            $nameId = 'name_link_' . $uniqId;
            $linkedFunctionName = 'set_linked_' . get_class($this->entityName) . '_CODE';//FIXME: hardcode here!!!

            if (isset($this->entityName->{$this->entityName->pk()})) {
                $pkVal = $this->entityName->{$this->entityName->pk()};
            } else {
                $pkVal = '_new_';
            }

            $nameId .= $pkVal;
            $linkedFunctionName .= $pkVal;

            $link = '<image id="' . $nameId . '" title="' . Loc::getMessage(
                'IBSEC_E_LINK_TIP'
            ) . '" class="linked" src="/bitrix/themes/.default/icons/iblock/link.gif" onclick="' . $linkedFunctionName . '()" />';
        }

        return '<input type="text"
                       name="' . $this->getEditInputName() . '"
                       value="' . static::prepareToTagAttr($this->getValue()) . '"
                       size="' . $size . '"
                       style="' . $style . '"/>' . $link;
    }

    protected function getMultipleEditHtml()
    {
        $style = $this->getSettings('STYLE');
        $size = $this->getSettings('SIZE');
        $uniqueId = $this->getEditInputHtmlId();

        $rsEntityData = null;

        if (!empty($this->data['ID'])) {
            $entityName = $this->entityName;
            $rsEntityData = $entityName::getList([
                'select' => ['REFERENCE_' => $this->getCode() . '.*'],
                'filter' => ['=ID' => $this->data['ID']],
            ]);
        }

        ob_start();
        ?>

        <div id="<?= $uniqueId ?>-field-container" class="<?= $uniqueId ?>">
        </div>

        <script>
            var multiple = new MultipleWidgetHelper(
                '#<?= $uniqueId ?>-field-container',
                '{{field_original_id}}<input type="text" name="<?= $this->getCode(
                )?>[{{field_id}}][<?=$this->getMultipleField(
                    'VALUE'
                )?>]" style="<?=$style?>" size="<?=$size?>" value="{{value}}">'
            );
            <?php
            if ($rsEntityData) {
                while ($referenceData = $rsEntityData->fetch()) {
                    if (empty($referenceData['REFERENCE_' . $this->getMultipleField('ID')])) {
                        continue;
                    }

                    ?>
            multiple.addField({
                value: '<?= static::prepareToJs($referenceData['REFERENCE_' . $this->getMultipleField('VALUE')]) ?>',
                field_original_id: '<input type="hidden" name="<?= $this->getCode(
                )?>[{{field_id}}][<?= $this->getMultipleField('ID') ?>]"' +
                    ' value="<?= $referenceData['REFERENCE_' . $this->getMultipleField('ID')] ?>">',
                field_id: <?= $referenceData['REFERENCE_' . $this->getMultipleField('ID')] ?>
            });
            <?php
                }
            }
        ?>

            // TODO Добавление созданных полей
            multiple.addField();
        </script>
        <?php
        return ob_get_clean();
    }

    protected function getMultipleValueReadonly()
    {
        $rsEntityData = null;
        if (!empty($this->data['ID'])) {
            $entityName = $this->entityName;
            $rsEntityData = $entityName::getList([
                'select' => ['REFERENCE_' => $this->getCode() . '.*'],
                'filter' => ['=ID' => $this->data['ID']],
            ]);
        }

        $result = '';
        if ($rsEntityData) {
            while ($referenceData = $rsEntityData->fetch()) {
                if (empty($referenceData['REFERENCE_VALUE'])) {
                    continue;
                }

                $result .= '<div class="wrap_text" style="margin-bottom: 5px">'
                    . static::prepareToOutput($referenceData['REFERENCE_VALUE']) . '</div>';
            }
        }

        return $result;
    }
}
