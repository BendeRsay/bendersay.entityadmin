<?php

namespace Bendersay\Entityadmin\Widget;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Виджет с числовыми значениями. Точная копия StringWidget, только работает с числами и не ищет по подстроке.
 */
class NumberWidget extends StringWidget
{
    protected static $defaults = [
        'FILTER' => '=',
        'EDIT_IN_LIST' => true,
    ];

    public function checkFilter($operationType, $value)
    {
        return $this->isNumber($value);
    }

    public function checkRequired()
    {
        if ($this->getSettings('REQUIRED') == true) {
            $value = $this->getValue();

            return !is_null($value);
        }

        return true;
    }

    public function processEditAction()
    {
        if (!$this->checkRequired()) {
            $this->addError('zebrains_AH_REQUIRED_FIELD_ERROR');
        } elseif (!$this->isNumber($this->getValue())) {
            $this->addError('VALUE_IS_NOT_NUMERIC');
        }
    }

    protected function isNumber($value)
    {
        return intval($value) or floatval($value) or doubleval($value) or is_null($value) or empty($value);
    }
}
