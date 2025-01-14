<?php

namespace Bendersay\Entityadmin;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\SystemException;

/**
 * Менеджер для работы со списком элементов в компоненте main.ui.grid для Reference полей
 *
 * @see https://dev.1c-bitrix.ru/api_d7/bitrix/main/systemcomponents/gridandfilter/mainuigrid/parameters.php
 */
class EntityReferenceManager extends EntityListManager
{
    /**
     * Получаем JS для подстановки id в input и закрытия окна
     *
     * @return string
     *
     * @throws ArgumentException
     */
    public function getJsSelectElement(): string
    {
        $fieldId = $this->request->get('field_id');
        if (empty($fieldId)) {
            throw new ArgumentException('field_id is empty');
        }

        return "
            <script>
                function SelectElement(id) {
                    let el;
                    el = window.opener.document.getElementById('" . $fieldId . "');
                    if (el) {
                        el.value = id;
                        if (window.opener.BX) {
                            window.opener.BX.fireEvent(el, 'change');
                        }
                    }
                    window.close();
                }
            </script>
        ";
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
                'text' => Loc::getMessage('BENDERSAY_ENTITYADMIN_CHOOSE_ELEMENT_ACTION_TEXT'),
                'default' => true,
                'onclick' => 'SelectElement(\'' . $elem[$primaryKey] . '\')',
            ],
        ];
    }

}
