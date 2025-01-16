<?php

namespace Bendersay\Entityadmin\Helper;

use Bitrix\Main\ORM\Data\DataManager;

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
}
