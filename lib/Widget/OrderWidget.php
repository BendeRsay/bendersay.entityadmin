<?php

namespace Bendersay\Entityadmin\Widget;

use Bitrix\Sale\OrderTable;

/**
 * Виджет для вывода заказа.
 *
 * Доступные опции:
 * <ul>
 * <li> STYLE - inline-стили
 * <li> SIZE - значение атрибута size для input
 * </ul>
 */
class OrderWidget extends NumberWidget
{
    /**
     * @inheritdoc
     */
    public function getEditHtml(): string
    {
        $style = $this->getSettings('STYLE');
        $size = $this->getSettings('SIZE');

        $orderId = $this->getValue();

        $htmlOrder = '';

        if (!empty($orderId) && $orderId != 0) {
            $rsOrder = OrderTable::getById($orderId);
            $order = $rsOrder->fetch();

            $htmlOrder = ' [<a href="sale_order_view.php?ID=' . $order['ID'] . '">' . $order['ID'] . '</a>]'
                . ' Статус оплаты: ' . $order['PAYED'];
        }

        return '<input type="text"
                       name="' . $this->getEditInputName() . '"
                       value="' . static::prepareToTagAttr($this->getValue()) . '"
                       size="' . $size . '"
                       style="' . $style . '"/>' . $htmlOrder;
    }

    /**
     * @inheritdoc
     */
    public function getValueReadonly()
    {
        $orderId = $this->getValue();
        $htmlOrder = '';

        if (!empty($orderId) && $orderId != 0) {
            $rsOrder = OrderTable::getById($orderId);
            $order = $rsOrder->fetch();

            $htmlOrder = ' [<a href="sale_order_view.php?ID=' . $order['ID'] . '">' . $order['ID'] . '</a>]'
                . ' Статус оплаты: ' . $order['PAYED'];
        }

        return $htmlOrder;
    }

    /**
     * @inheritdoc
     */
    public function generateRow(&$row, $data)
    {
        $orderId = $this->getValue();
        $htmlOrder = '';

        if (\Bitrix\Main\Loader::IncludeModule('sale')) {
            if (!empty($orderId) && $orderId != 0) {
                $rsOrder = OrderTable::getById($orderId);
                $order = $rsOrder->fetch();

                $htmlOrder = ' [<a href="sale_order_view.php?lang=ru&ID=' . $order['ID'] . '">' . $order['ID'] . '</a>]'
                    . ' Статус оплаты: ' . $order['PAYED'];
            }
        } else {
            $htmlOrder = ' [<a href="sale_order_view.php?lang=ru&ID=' . $orderId . '">' . $orderId . '</a>]';
        }

        $row->AddViewField($this->getCode(), $htmlOrder);
    }
}
