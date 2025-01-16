<?php

namespace Bendersay\Entityadmin;

interface DataManagerInterface
{
    /**
     * Возвращает поле сущности для связей и поиска.
     * В списке элементов сущности, в строке поиска фильтра, поиск будет осуществляться по значению этого поля.
     * Значение поля будет отображаться в списке элементов связанных сущностей. Пример: [38] name
     *
     * @return string
     */
    public static function getEntityReferenceShowField(): string;
}
