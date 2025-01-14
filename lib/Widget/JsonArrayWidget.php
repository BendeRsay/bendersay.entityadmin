<?php

namespace Bendersay\Entityadmin\Widget;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

/**
 * Виджет для полей типа ArrayField
 * Выводит textarea для редактирования массивов, хранящихся в БД как в виде JSON так и в виде сериализованного массива
 * На странице редактирования всегда отображается textarea с данными в json формате, даже если в БД значение
 * хранится в виде сериализованного массива.
 * При сохранении производится проверка синтаксиса JSON, если есть ошибка, элемент не будет сохранён, о чём будет
 * уведомлено в виде сообщения об ошибке
 *
 * Доступные опции:
 * <ul>
 * <li><b>COLS</b> - ширина</li>
 * <li><b>ROWS</b> - высота</li>
 * </ul>
 */
class JsonArrayWidget extends HelperWidget
{
    /**
     * количество отображаемых символов в режиме списка.
     */
    public const LIST_TEXT_SIZE = 150;

    protected static $defaults = [
        'COLS' => 65,
        'ROWS' => 15,
        'EDIT_IN_LIST' => false,
    ];

    /**
     * @inheritdoc
     */
    public function generateRow(&$row, $data)
    {
        $text = $this->getConvertedValue();

        if ($this->getSettings('EDIT_IN_LIST') and !$this->getSettings('READONLY')) {
            $row->AddInputField($this->getCode(), ['style' => 'width:90%']);
        } else {
            if (strlen($text) > self::LIST_TEXT_SIZE && !$this->isExcelView()) {
                $pos = false;
                $pos = $pos === false ? stripos($text, ' ', self::LIST_TEXT_SIZE) : $pos;
                $pos = $pos === false ? stripos($text, "\n", self::LIST_TEXT_SIZE) : $pos;
                $pos = $pos === false ? stripos($text, '</', self::LIST_TEXT_SIZE) : $pos;
                $pos = $pos === false ? 300 : $pos;
                $text = substr($text, 0, $pos) . ' ...';
            }

            $text = static::prepareToOutput($text);

            $row->AddViewField($this->code, $text);
        }
    }

    public function showFilterHtml()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    protected function getEditHtml(): string
    {
        $cols = $this->getSettings('COLS');
        $rows = $this->getSettings('ROWS');

        $editInputHtmlId = ucfirst(str_replace(['-', '_'], '', $this->getEditInputHtmlId()));

        return '
        <textarea 
            id="textarea' . $this->getEditInputHtmlId() . '" 
            cols="' . $cols . '" 
            rows="' . $rows . '" 
            name="' . $this->getEditInputName() . '">
        </textarea>
        <script>
            const jsonValue' . $editInputHtmlId . ' = \'' . $this->getConvertedValue() . '\';
            const textArea' . $editInputHtmlId . ' = document.querySelector("#textarea' . $this->getEditInputHtmlId() . '");
            const text' . $editInputHtmlId . ' = (jsonValue' . $editInputHtmlId . '.length > 0) ? JSON.stringify(JSON.parse(jsonValue' . $editInputHtmlId . '), null, 4) : "";
            (textArea' . $editInputHtmlId . ').innerHTML = (text' . $editInputHtmlId . ');
        </script>
        ';
    }

    private function getConvertedValue(): string
    {
        if (!$this->getValue()) {
            return '';
        }

        try {
            if (!$value = Json::encode($this->getValue(), JSON_UNESCAPED_UNICODE)) {
                throw new \JsonException();
            }

            return $value;
        } catch (\JsonException|ArgumentException $ex) {
            return serialize($this->getValue());
        }
    }
}
