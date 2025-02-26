<?php

namespace Bendersay\Entityadmin\Readonly;

use Bitrix\Main\ORM\Data\DataManager;

/**
 * Объект поля связанной сущности
 */
readonly class FieldReference
{
    /** @var DataManager|string Название сущности */
    public DataManager|string $entity;

    /** @var string Название поля */
    public string $name;

    /** @var string код поля, по которому строится связь */
    public string $foreignKey;

    /** @var array Primary ключ сущности, может быть составной */
    public array $primaryArray;

    /** @var array список элементов в формате 'primaryKey сущности из поля $foreignKey' => 'значение поля $foreignKey' */
    public array $itemList;

    /**
     * @param DataManager|string $entity название сущности
     * @param string $name название поля
     * @param string $foreignKey код поля, по которому строится связь
     * @param array $itemList список элементов в формате 'primaryKey сущности из поля $foreignKey' => 'значение поля $foreignKey'
     * @param array $primaryArray
     */
    public function __construct(
        DataManager|string $entity,
        string $name,
        string $foreignKey,
        array $primaryArray = [],
        array $itemList = [],
    ) {
        $this->entity = $entity;
        $this->name = $name;
        $this->foreignKey = $foreignKey;
        $this->primaryArray = $primaryArray;
        $this->itemList = $itemList;
    }
}
