<?php

declare(strict_types=1);

namespace Bendersay\Entityadmin\Enum;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Перечень исключений
 */
enum CodeExceptionEnum
{
    /** Доступ запрещён */
    case ACCESS_DENIED;

    /**
     * Получаем языковую фразу по коду
     *
     * @param string $enumName
     *
     * @return string
     */
    public static function getMessage(string $enumName): string
    {
        return (string)Loc::getMessage('BENDERSAY_ENTITYADMIN_' . $enumName);
    }

    /**
     * Получаем массив значений
     *
     * @return array
     */
    public static function getNameList(): array
    {
        $result = [];

        foreach (self::cases() as $case) {
            $result[] = $case->name;
        }

        return $result;
    }
}
