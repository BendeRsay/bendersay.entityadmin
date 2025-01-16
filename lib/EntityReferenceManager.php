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
        $fieldIdValue = $this->request->get('field_id_span');
        if (empty($fieldId)) {
            throw new ArgumentException('field_id is empty');
        }

        return "
            <script>
                function SelectElement(id, value) {
                    let el;
                    let span;
                    el = window.opener.document.getElementById('" . $fieldId . "');
                    span = window.opener.document.getElementById('" . $fieldIdValue . "');
                    if (el) {
                        el.value = id;
                        if (window.opener.BX) {
                            window.opener.BX.fireEvent(el, 'change');
                        }
                    }
                    if (span) {
                        span.textContent = value;
                        if (window.opener.BX) {
                            window.opener.BX.fireEvent(span, 'change');
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
        $entity = $this->entityClass::getEntity();
        $primaryKey = $entity->getPrimary();
        $refKey = '';
        $dataClass = $entity->getDataClass();
        $reflectionClass = new \ReflectionClass($dataClass);

        if ($reflectionClass->implementsInterface(DataManagerInterface::class)) {
            /** @var $dataClass DataManagerInterface */
            $refKey = $dataClass::getEntityReferenceShowField();
        }

        $value = $elem[$refKey] ?? '';

        return [
            [
                'text' => Loc::getMessage('BENDERSAY_ENTITYADMIN_CHOOSE_ELEMENT_ACTION_TEXT'),
                'default' => true,
                'onclick' => 'SelectElement(\'' . $elem[$primaryKey] . '\', \'' . $value . '\')',
            ],
        ];
    }

}
