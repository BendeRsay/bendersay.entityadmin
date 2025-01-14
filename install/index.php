<?php

use Bendersay\Entityadmin\Install\Config;
use Bendersay\Entityadmin\Install\Dependence;
use Bendersay\Entityadmin\Install\ManagerEvent;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\IO\File;
use Bitrix\Main\IO\FileNotFoundException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

require_once __DIR__ . '/../lib/Install/Config.php';

/**
 * Модуль отображает сущности d7 в админке.
 */
class bendersay_entityadmin extends CModule
{
    public function __construct()
    {
        $this->MODULE_ID = Config::MODULE_CODE;
        $this->setVersionData();

        $this->MODULE_NAME = Loc::getMessage('BENDERSAY_ENTITYADMIN_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('BENDERSAY_ENTITYADMIN_MODULE_DESC');

        $this->PARTNER_NAME = Loc::getMessage('BENDERSAY_ENTITYADMIN_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('BENDERSAY_ENTITYADMIN_PARTNER_URI');

        $this->SHOW_SUPER_ADMIN_GROUP_RIGHTS = 'Y';
        $this->MODULE_GROUP_RIGHTS = 'Y';
    }

    /**
     * Установка модуля
     *
     * @return bool
     *
     * @throws SqlQueryException
     * @throws LoaderException
     */
    public function DoInstall(): bool
    {
        /** @var $APPLICATION CMain */
        global $APPLICATION;

        $connection = Application::getConnection();
        $connection->startTransaction();

        try {
            ModuleManager::registerModule($this->MODULE_ID);
            Loader::includeModule($this->MODULE_ID);

            $dependence = new Dependence();
            $dependence->checkRequiredModulesInstalled();

            $this->InstallFiles();
            $this->InstallDB();

            $connection->commitTransaction();

            return true;
        } catch (Throwable $th) {
            Loader::includeModule($this->MODULE_ID);
            ModuleManager::unRegisterModule($this->MODULE_ID);
            $connection->rollbackTransaction();
            $APPLICATION->ThrowException($th->getTraceAsString());

            return false;
        }
    }

    /**
     * Удаление модуля
     *
     * @return false|void
     *
     * @throws SqlQueryException
     */
    public function DoUninstall()
    {
        /** @var $APPLICATION CMain */
        global $APPLICATION;

        $connection = Application::getConnection();
        $connection->startTransaction();

        try {
            Loader::includeModule($this->MODULE_ID);

            $this->UnInstallFiles();
            $this->UnInstallDB();
            ModuleManager::unRegisterModule($this->MODULE_ID);
            $connection->commitTransaction();
        } catch (Throwable $th) {
            $connection->rollbackTransaction();
            $APPLICATION->ThrowException($th->getMessage());

            return false;
        }
    }

    /**
     * @param array $arParams
     *
     * @return bool
     */
    public function InstallFiles(array $arParams = []): bool
    {
        CopyDirFiles(__DIR__ . '/admin', Application::getDocumentRoot() . '/bitrix/admin');

        return true;
    }

    /**
     * @return bool
     */
    public function UnInstallFiles(): bool
    {
        $result = true;
        $fileList = [
            '/bitrix/admin/bendersay_entityadmin_entity_element_edit.php',
            '/bitrix/admin/bendersay_entityadmin_entity_element_list.php',
            '/bitrix/admin/bendersay_entityadmin_reference_element_list.php',
        ];

        foreach ($fileList as $file) {
            $path = Application::getDocumentRoot() . $file;
            if (File::isFileExists($path)) {
                $result = File::deleteFile($path);
            }
        }

        return $result;
    }

    /**
     * @param array $arParams
     *
     * @return bool
     *
     * @throws SystemException
     * @throws ArgumentException
     * @throws ObjectPropertyException
     */
    public function UnInstallDB(array $arParams = []): bool
    {
        $event = new ManagerEvent();
        $event->unRegisterEvents();

        Option::delete($this->MODULE_ID);

        return true;
    }

    /**
     * @return bool
     *
     * @throws FileNotFoundException
     * @throws SystemException
     */
    public function InstallDB(): bool
    {
        $event = new ManagerEvent();
        $event->registerEvents();

        return true;
    }

    /**
     * Устанавливаем версию модуля из VERSION
     *
     * @return void
     */
    private function setVersionData(): void
    {
        $arModuleVersion = [];
        include(__DIR__ . '/version.php');

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
    }
}
