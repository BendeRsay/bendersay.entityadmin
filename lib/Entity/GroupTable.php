<?php

namespace Bendersay\Entityadmin\Entity;

use Bendersay\Entityadmin\DataManagerInterface;

/**
 * Своя сущность для работы с b_group.
 * Используем для отражения NAME в админке
 *
 * @see \Bitrix\Main\UserTable
 */
class GroupTable extends \Bitrix\Main\GroupTable implements DataManagerInterface
{
    /**
     * @inheritdoc
     */
    public static function getTitle(): string
    {
        return 'Группы пользователей';
    }

    /** @inheritdoc */
    public static function getEntityReferenceShowField(): string
    {
        return 'NAME';
    }
}
