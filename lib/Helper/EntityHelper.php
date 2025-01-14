<?php

namespace Bendersay\Entityadmin\Helper;

use Bitrix\Main\Annotations\AnnotationReader;
use Bitrix\Main\ORM\Data\DataManager;
use ReflectionClass;
use ReflectionException;

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
     * Получение заголовка таблицы из аннотации.
     * Пример: @Table(title=CRM Продукт)
     *
     * @param string|DataManager $entity
     *
     * @return string
     *
     * @throws ReflectionException
     */
    public static function getTableTitle(DataManager|string $entity): string
    {
        $reflectionClass = new ReflectionClass($entity);
        $annotationReader = new AnnotationReader();

        $method = $reflectionClass->getMethod('getTableName');
        $annotations = $annotationReader->getMethodAnnotations($method);

        return array_key_exists('Table', $annotations) && !empty($annotations['Table']['title'])
            ? $annotations['Table']['title']
            : $entity::getTableName();
    }
}
