<?php

namespace Bendersay\Entityadmin\Install;

use Bendersay\Entityadmin\Entity\ModuleToModuleTable;
use Bendersay\Entityadmin\Event\EventInterface;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\EventManager;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\FileNotFoundException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

/**
 * Менеджер событий в модуле при установке
 *
 * @author bender
 */
class ManagerEvent
{
    use ParseTrait;

    /** @var array IGNORE_FILE_NAME Игнорируемые имена файлов в lib/Event */
    private const array IGNORE_FILE_NAME = [
        'AbstractEvent.php',
        'EventInterface.php',
    ];

    protected EventManager $eventManager;

    public function __construct()
    {
        $this->eventManager = EventManager::getInstance();
    }

    /**
     * Регистрация событий модуля
     *
     * @return void
     *
     * @throws SystemException
     * @throws FileNotFoundException
     */
    public function registerEvents(): void
    {
        foreach ($this->getEventList() as $eventInfo) {
            $this->eventManager->registerEventHandler(
                $eventInfo->getModule(),
                $eventInfo->getEventType(),
                Config::MODULE_CODE,
                $eventInfo->getToClass(),
                $eventInfo->getToMethod(),
                $eventInfo->getSort()
            );
        }
    }

    /**
     * Удаляем события модуля
     *
     * @return void
     *
     * @throws SystemException
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws \Exception
     */
    public function unRegisterEvents(): void
    {
        $rows = ModuleToModuleTable::getList([
            'select' => ['ID'],
            'filter' => ['=TO_MODULE_ID' => Config::MODULE_CODE],
        ])->fetchAll();

        foreach ($rows as $row) {
            ModuleToModuleTable::delete($row['ID']);
        }
    }

    /**
     * Получаем список событий модуля
     *
     * @return array
     *
     * @throws FileNotFoundException
     * @throws SystemException
     */
    private function getEventList(): array
    {
        $result = [];

        $dir = new Directory(__DIR__ . '/../Event/');
        $arDir = $dir->getChildren();

        foreach ($arDir as $dirItem) {
            if ($dirItem->isFile() && !in_array($dirItem->getName(), self::IGNORE_FILE_NAME, true)) {
                $eventInterface = Config::MODULE_NAMESPACE . '\Event\\EventInterface';
                $className = $this->parseTokens(token_get_all(file_get_contents($dirItem->getPhysicalPath())));
                $eventClass = Config::MODULE_NAMESPACE . '\Event\\' . $className;
                $eventOb = new $eventClass();

                if (!$eventOb instanceof EventInterface) {
                    throw new SystemException(
                        $eventClass . ' событие должно реализовывать интерфейс ' . $eventInterface
                    );
                }

                $result[] = $eventOb;
            }
        }

        return $result;
    }
}
