<?php

namespace Bendersay\Entityadmin\Install;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\Json;

class Dependence
{
    /**
     * Проверка зависимых модулей
     *
     * @return void
     *
     * @throws SystemException
     */
    public function checkRequiredModulesInstalled(): void
    {
        foreach ($this->getRequiredModules() as $moduleId => $version) {
            if (!ModuleManager::isModuleInstalled($moduleId)) {
                throw new SystemException(
                    Loc::getMessage('BENDERSAY_ENTITYADMIN_REQUIRED_MODULE_ERROR', ['#MODULE#' => $moduleId])
                );
            } elseif ($version !== '*' && !CheckVersion(ModuleManager::getVersion($moduleId), $version)) {
                throw new SystemException(
                    Loc::getMessage(
                        'BENDERSAY_ENTITYADMIN_MODULE_VERSION_ERROR',
                        ['#MODULE#' => $moduleId, '#VERSION#' => $version]
                    )
                );
            }
        }
    }

    /**
     * формат: [<moduleId> => <version>, ...]
     *
     * @return mixed
     *
     * @throws ArgumentException
     */
    private function getRequiredModules(): mixed
    {
        return Json::decode(file_get_contents(__DIR__ . '/../../install/dependence.json'));
    }
}
