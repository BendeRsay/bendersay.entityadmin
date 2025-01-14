<?php

namespace Bendersay\Entityadmin\Widget;

use Bitrix\Main\Type\DateTime as BitrixDateTime;

class DateTimeWidget extends HelperWidget
{
    /**
     * Генерирует HTML для поля в списке
     *
     * @param CAdminListRow $row
     * @param array $data - данные текущей строки
     *
     * @return mixed
     *
     * @see AdminListHelper::addRowCell();
     *
     */
    public function generateRow(&$row, $data)
    {
        if (isset($this->settings['EDIT_IN_LIST']) and $this->settings['EDIT_IN_LIST']) {
            $row->AddCalendarField($this->getCode());
        } else {
            $strDate = '';
            $value = $this->getValue();

            if (!empty($value)) {
                $strDate = $value->format(BitrixDateTime::getFormat());
            }

            $row->AddViewField($this->getCode(), $strDate);
        }
    }

    /**
     * Генерирует HTML для поля фильтрации
     *
     * @return mixed
     *
     * @see AdminListHelper::createFilterForm();
     *
     */
    public function showFilterHtml()
    {
        list($inputNameFrom, $inputNameTo) = $this->getFilterInputName();
        print '<tr>';
        print '<td>' . $this->settings['TITLE'] . '</td>';
        print '<td width="0%" nowrap>' . CalendarPeriod(
            $inputNameFrom,
            $$inputNameFrom,
            $inputNameTo,
            $$inputNameTo,
            'find_form'
        ) . '</td>';
    }

    /**
     * Конвертируем дату в формат Mysql
     *
     * @return boolean
     */
    public function processEditAction()
    {
        $value = $this->getValue();
        if (!empty($value)) {
            $value = new BitrixDateTime($value);
        }

        $this->setValue($value);
    }

    /**
     * Генерирует HTML для редактирования поля
     *
     * @return mixed
     *
     * @see AdminEditHelper::showField();
     *
     */
    protected function getEditHtml(): string
    {
        $value = $this->getValue();
        if (empty($value) && $this->getSettings('REQUIRED') == true) {
            $value = new BitrixDateTime();
        }
        $valueStr = $value instanceof BitrixDateTime ? $value->format(BitrixDateTime::getFormat()) : (string)$value;

        return \CAdminCalendar::CalendarDate($this->getEditInputName(), $valueStr, 10, true);
    }
}
