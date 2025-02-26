<?php

namespace Bendersay\Entityadmin;

use Bendersay\Entityadmin\Helper\EntityHelper;
use Bendersay\Entityadmin\Install\Config;
use Bendersay\Entityadmin\Readonly\FieldReference;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Context;
use Bitrix\Main\Data\LocalStorage\SessionLocalStorage;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Field;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\ScalarField;
use Bitrix\Main\SystemException;

abstract class AbstractEntityManager
{
    /** @var HttpRequest */
    protected HttpRequest $request;

    /** @var DataManager|string Класс сущности с которой работаем */
    protected DataManager|string $entityClass;

    /** @var Field[] Поля сущности */
    protected array $fieldList;

    /** @var FieldReference[] Поля связей с другими сущностями */
    protected array $fieldReferenceList;

    /** @var array Primary поля */
    protected array $primaryFieldList;

    /** @var string Название сущности */
    protected string $tableTitle;

    /** @var mixed|false|string|null Права пользователя на модуль */
    protected mixed $modRight;

    /**
     * Сессионный кеш
     *
     * @see https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=14018&LESSON_PATH=3913.3435.4816.14028.14018
     *
     * @var SessionLocalStorage
     */
    protected SessionLocalStorage $localSession;

    /**
     * @throws LoaderException
     * @throws SystemException
     * @throws \Exception
     */
    public function __construct()
    {
        Loader::requireModule(Config::MODULE_CODE);
        /* @phpstan-ignore-next-line */
        $this->request = Context::getCurrent()->getRequest();
        $entityClass = $this->request->get('entity');

        if (empty($entityClass) || is_array($entityClass) || !EntityHelper::checkEntityExistence($entityClass)) {
            throw new SystemException(
                Loc::getMessage(
                    'BENDERSAY_ENTITYADMIN_ENTITY_NOT_FOUND',
                    ['%entity%' => $entityClass]
                )
            );
        }

        $this->entityClass = $entityClass;
        $entity = $this->entityClass::getEntity();
        $this->primaryFieldList = $entity->getPrimaryArray();
        $this->fieldList = $entity->getFields();
        $this->tableTitle = EntityHelper::getEntityTitle($this->entityClass);
        $this->fieldReferenceList = $this->getFieldReferenceList();
        $this->localSession = Application::getInstance()->getLocalSession(static::class);
        $this->modRight = \CMain::GetGroupRight(Config::MODULE_CODE);
    }

    /**
     * Возвращаем сущность
     *
     * @return DataManager|string
     */
    public function getEntityClass(): DataManager|string
    {
        return $this->entityClass;
    }

    /**
     * Возвращаем HttpRequest запроса
     *
     * @return HttpRequest
     */
    public function getRequest(): HttpRequest
    {
        return $this->request;
    }

    /**
     * Возвращаем список полей сущности
     *
     * @return array
     */
    public function getFieldList(): array
    {
        return $this->fieldList;
    }

    /**
     * Права пользователя на модуль
     *
     * @return false|mixed|string|null
     */
    public function getModRight(): mixed
    {
        return $this->modRight;
    }

    /**
     * Возвращаем список полей связей с другими сущностями
     *
     * @return FieldReference[]
     *
     * @throws SystemException
     * @throws ArgumentException|\ReflectionException
     */
    public function getFieldReferenceList(): array
    {
        $result = [];

        foreach ($this->fieldList as $field) {
            if ($field instanceof Reference) {
                $fieldName = $field->getName();
                $link = $field->getElementals();
                if ($link === false) {
                    continue;
                }
                $foreignKey = array_key_first($link);

                $result[$foreignKey] = new FieldReference(
                    $field->getRefEntity()->getDataClass(),
                    $fieldName,
                    $foreignKey,
                    $field->getRefEntity()->getPrimaryArray(),
                    $this->getItemList($field),
                );
            }
        }

        return $result;
    }

    /**
     * Возвращаем список значений связанной сущности
     *
     * @param Reference $field
     *
     * @return array
     *
     * @throws SystemException
     * @throws ArgumentException|\ReflectionException
     */
    protected function getItemList(Reference $field): array
    {
        $result = [];
        $entityRef = $field->getRefEntity();
        $dataClassRef = $entityRef->getDataClass();
        $reflectionClass = new \ReflectionClass($dataClassRef);

        if ($reflectionClass->implementsInterface(DataManagerInterface::class)) {
            /** @var DataManagerInterface|DataManager $dataClassRef */
            $keyName = $dataClassRef::getEntityReferenceShowField();
            $keyPrimary = $entityRef->getPrimary();

            $list = $dataClassRef::getList([
                'select' => [
                    $entityRef->getPrimary(),
                    $keyName,
                ],
                'cache' => ['ttl' => 3600],
            ])->fetchAll();

            foreach ($list as $item) {
                $result[$item[$keyPrimary]] = $item[$keyName];
            }
        }

        return $result;
    }

    /**
     * Получаем список значений поля EnumField
     *
     * @param EnumField $field
     *
     * @return array
     *
     * @throws SystemException
     */
    protected function getEnumFieldItemList(EnumField $field): array
    {
        $valueList = $field->getValues();
        $fetchDataModifiers = $field->getFetchDataModifiers();

        if (!empty($fetchDataModifiers)) {
            foreach ($fetchDataModifiers as $callback) {
                $valueList = array_map($callback, $valueList);
            }
        }

        return array_combine($field->getValues(), $valueList);
    }

    /**
     * Получаем поля для select
     *
     * @return array
     */
    protected function getSelectDefault(): array
    {
        $result = [];

        foreach ($this->fieldList as $field) {
            if ($field instanceof ScalarField || $field instanceof ExpressionField) {
                if ($field->isPrivate()) {
                    continue;
                }
                $result[] = $field->getName();
            }
        }

        return $result;
    }

}
