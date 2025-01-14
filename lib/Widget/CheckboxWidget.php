<?php

namespace Bendersay\Entityadmin\Widget;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Виджет "галочка"
 */
class CheckboxWidget extends HelperWidget
{
    /**
     * Строковый тип чекбокса (Y/N)
     */
    public const string TYPE_STRING = 'string';
    /**
     * Целочисленный тип чекбокса (1/0)
     */
    public const string TYPE_INT = 'integer';
    /**
     * Булевый тип чекбокса
     */
    public const string TYPE_BOOLEAN = 'boolean';
    /**
     * Значение положительного варианта для строкового чекбокса
     */
    public const string TYPE_STRING_YES = 'Y';
    /**
     * Значение отрицательного варианта для строкового чекбокса
     */
    public const string TYPE_STRING_NO = 'N';
    /**
     * Значение положительного варианта для целочисленного чекбокса
     */
    public const int TYPE_INT_YES = 1;
    /**
     * Значение отрицательного варианта для целочисленного чекбокса
     */
    public const int TYPE_INT_NO = 0;

    protected static $defaults = [
        'EDIT_IN_LIST' => true,
    ];

    /**
     * @inheritdoc
     */
    public function getValueReadonly()
    {
        $code = $this->getCode();
        $value = isset($this->data[$code]) ? $this->data[$code] : null;
        $modeType = $this->getCheckboxType();

        switch ($modeType) {
            case static::TYPE_STRING:
                {
                    $value = $value == 'Y' ? Loc::getMessage('zebrains_AH_CHECKBOX_YES') : Loc::getMessage(
                        'zebrains_AH_CHECKBOX_NO'
                    );

                    break;
                }
            case static::TYPE_INT:
            case static::TYPE_BOOLEAN:
                {
                    $value = $value ? Loc::getMessage('zebrains_AH_CHECKBOX_YES') : Loc::getMessage(
                        'zebrains_AH_CHECKBOX_NO'
                    );

                    break;
                }
        }

        return static::prepareToOutput($value);
    }

    /**
     * Получить тип чекбокса по типу поля.
     *
     * @return mixed
     */
    public function getCheckboxType()
    {
        $fieldType = '';
        $entity = $this->getEntityName();
        $entityMap = $entity::getMap();
        $columnName = $this->getCode();

        if (preg_match('/[0-9]{1}/', $this->getValue(), $matches)) {
            return static::TYPE_INT;
        }

        if (preg_match('/[' . self::TYPE_STRING_YES . '|' . self::TYPE_STRING_NO . ']{1}/', $this->getValue())) {
            return static::TYPE_STRING;
        }

        if (!isset($entityMap[$columnName])) {
            foreach ($entityMap as $field) {
                if ($field->getColumnName() === $columnName) {
                    $fieldType = $field->getDataType();

                    break;
                }
            }
        } else {
            $fieldType = static::TYPE_INT;
        }

        return $fieldType;
    }

    /**
     * @inheritdoc
     */
    protected function getEditHtml(): string
    {
        $html = '';
        $modeType = $this->getCheckboxType();
        switch ($modeType) {
            case static::TYPE_STRING:
                {
                    $checked = $this->getValue() == self::TYPE_STRING_YES ? 'checked' : '';

                    $html = '<input type="hidden" name="' . $this->getEditInputName(
                    ) . '" value="' . self::TYPE_STRING_NO . '" />';
                    $html .= '<input type="checkbox" name="' . $this->getEditInputName(
                    ) . '" value="' . self::TYPE_STRING_YES . '" ' . $checked . ' />';

                    break;
                }
            case static::TYPE_INT:
            case static::TYPE_BOOLEAN:
                {
                    $checked = $this->getValue() == self::TYPE_INT_YES ? 'checked' : '';

                    $html = '<input type="hidden" name="' . $this->getEditInputName(
                    ) . '" value="' . self::TYPE_INT_NO . '" />';
                    $html .= '<input type="checkbox" name="' . $this->getEditInputName(
                    ) . '" value="' . self::TYPE_INT_YES . '" ' . $checked . ' />';

                    break;
                }
        }

        return $html;
    }
}
