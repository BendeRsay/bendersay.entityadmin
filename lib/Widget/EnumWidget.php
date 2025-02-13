<?php

namespace Bendersay\Entityadmin\Widget;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Виджет списка
 */
class EnumWidget extends StringWidget
{
    /**
     * Настройки виджета. Доступные опции:
     * EDIT_LINK - отображать в виде ссылки на редактирование элемента
     * STYLE - inline-стили для input
     * SIZE - значение атрибута <size> для input и <select>
     * TRANSLIT - true, если поле будет транслитерироваться в символьный код
     * MULTIPLE - поддерживается множественный ввод. В таблице требуется наличие поля VALUE
     */
    protected static $defaults = [
        'FILTER' => '%', // Фильтрация по подстроке, а не по точному соответствию.
        'EDIT_IN_LIST' => true,
        'SIZE' => 7,
    ];

    /**
     * @inheritdoc
     */
    protected function getEditHtml(): string
    {
        $style = $this->getSettings('STYLE');
        $size = $this->getSettings('SIZE');

        $link = '';

        if ($this->getSettings('TRANSLIT')) {
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

        // Формируем HTML

        $html = '<select name="' . $this->getEditInputName() . '" size="' . $size . '" style="' . $style . '">';

        foreach ($this->getEnumList() as $enumKey => $enumValue) {
            $selected = '';
            if ($enumValue === $this->getValue()) {
                $selected = 'selected';
            }
            $html .= '<option value="' . $enumKey . '" ' . $selected . ' >' . $enumValue . '</option>';
        }

        return $html . '</select>' . $link;
    }
}
