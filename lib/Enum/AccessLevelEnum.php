<?php

namespace Bendersay\Entityadmin\Enum;

/**
 * Уровень прав доступа к сущности
 */
enum AccessLevelEnum: string
{
    /** Все права. Запись, чтение, удаление */
    case WRITE = 'W';

    /** Только чтение */
    case READ = 'R';

    /** Запрещено */
    case DENIED = 'D';
}
