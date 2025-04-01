<?php

namespace Bendersay\Entityadmin\Helper;

use Bendersay\Entityadmin\Entity\EntityNameSpaceGroupTable;
use Bendersay\Entityadmin\Entity\EntityNameSpaceTable;
use Bendersay\Entityadmin\Install\Config;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Entity;

/**
 * Хелпер для работы с сущностями
 */
class EntityHelper
{
    /**
     * Метод проверки сущности на возможность отображения
     *
     * @param string $entityClass Класс проверяемой сущности
     *
     * @return bool
     */
    public static function checkEntityExistence(string $entityClass): bool
    {
        if (!class_exists($entityClass)
            || !is_subclass_of($entityClass, DataManager::class)
            || !$entityClass::getMap()
            || !$entityClass::getTableName()
        ) {
            return false;
        }

        return true;
    }

    /**
     * Метод для получения ссылки на список элементов сущности в админке
     *
     * @param array $queryParams
     *
     * @return string
     */
    public static function getListUrl(array $queryParams = []): string
    {
        $queryParams = array_merge($queryParams, ['sessid' => bitrix_sessid()]);

        return '/bitrix/admin/bendersay_entityadmin_entity_element_list.php?lang=' . LANGUAGE_ID
            . '&' . http_build_query($queryParams);
    }

    /**
     * Метод для получения ссылки на редактирование элемента сущности в админке
     *
     * @param array $queryParams
     *
     * @return string
     */
    public static function getEditUrl(array $queryParams = []): string
    {
        $queryParams = array_merge($queryParams, ['sessid' => bitrix_sessid()]);

        return '/bitrix/admin/bendersay_entityadmin_entity_element_edit.php?lang=' . LANGUAGE_ID
            . '&' . http_build_query($queryParams);
    }

    /**
     * Метод для получения ссылки на выбор
     *
     * @param array $queryParams
     *
     * @return string
     */
    public static function getReferenceElementListUrl(array $queryParams = []): string
    {
        $queryParams = array_merge($queryParams, ['sessid' => bitrix_sessid()]);

        return '/bitrix/admin/bendersay_entityadmin_reference_element_list.php?lang=' . LANGUAGE_ID
            . '&' . http_build_query($queryParams);
    }

    /**
     * Получение названия сущности
     *
     * @param string|DataManager $entity
     *
     * @return string
     *
     */
    public static function getEntityTitle(DataManager|string $entity): string
    {
        return $entity::getTitle() ?? $entity::getTableName();
    }

    /**
     * Кодирование первичного ключа элемента сущности для использования в url
     *
     * @param array $elem
     * @param Entity $entity
     *
     * @return string
     */
    public static function encodeUrlPrimaryId(array $elem, Entity $entity): string
    {
        $primary = $entity->getPrimaryArray();
        $data = [];

        if (is_array($primary)) {
            foreach ($primary as $key) {
                $data[$key] = $elem[$key];
            }
        }

        return http_build_query($data);
    }

    /**
     * Получение прав доступа к сущности
     *
     * @param DataManager|string $entityClass
     *
     * @return string
     *
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getGroupRight(DataManager|string $entityClass): string
    {
        $access = \CMain::GetGroupRight(Config::MODULE_CODE);

        $entityNameSpace = EntityNameSpaceTable::getRow([
            'select' => ['id'],
            'filter' => ['=namespace' => $entityClass],
        ]);
        if ($entityNameSpace === null) {
            return $access;
        }

        $accessEntityNameSpaceGroup = EntityNameSpaceGroupTable::getList([
            'select' => ['accessLevelEnum'],
            'filter' => [
                '=namespaceId' => (int)$entityNameSpace['id'],
                '=GROUP_ID' => CurrentUser::get()->getUserGroups(),
            ],
        ])->fetchAll();
        if (empty($accessEntityNameSpaceGroup)) {
            return $access;
        }
        $accessList = array_column($accessEntityNameSpaceGroup, 'accessLevelEnum', 'accessLevelEnum');

        return max($accessList);
    }
}
