<?php

namespace Bendersay\Entityadmin;

use Bendersay\Entityadmin\Helper\EntityHelper;
use Bendersay\Entityadmin\Install\Config;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Context;
use Bitrix\Main\Data\LocalStorage\SessionLocalStorage;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\SystemException;

abstract class AbstractEntityManager
{
    /** @var HttpRequest */
    protected HttpRequest $request;

    /** @var DataManager|string Класс сущности с которой работаем */
    protected DataManager|string $entityClass;

    /** @var array Поля сущности */
    protected array $fieldList;

    /** @var array Поля связей с другими сущностями */
    protected array $fieldReferenceList;

    /** @var string Код primary поля */
    protected string $primaryCode;

    /** @var string Название сущности */
    protected string $tableTitle;

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
        $this->primaryCode = $this->entityClass::getEntity()->getPrimary();
        $this->fieldList = $this->entityClass::getMap();
        $this->tableTitle = EntityHelper::getTableTitle($this->entityClass);
        $this->fieldReferenceList = $this->getFieldReferenceList();
        $this->localSession = Application::getInstance()->getLocalSession(static::class);
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
     * Возвращаем список полей связей с другими сущностями
     *
     * @return array
     *
     * @throws SystemException
     * @throws ArgumentException
     */
    public function getFieldReferenceList(): array
    {
        $result = [];

        foreach ($this->fieldList as $field) {
            if ($field instanceof Reference) {
                $fieldName = $field->getName();
                $foreignKey = array_key_first($field->getElementals());
                $result[$foreignKey] = [
                    'NAME' => $fieldName,
                    'FOREIGN_KEY' => $foreignKey,
                    'ENTITY' => $field->getRefEntity()->getDataClass(),
                ];
            }
        }

        return $result;
    }

}
