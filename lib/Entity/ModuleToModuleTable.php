<?php

namespace Bendersay\Entityadmin\Entity;

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\SystemException;

Loc::loadMessages(__FILE__);

/**
 * Class ModuleToModuleTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TIMESTAMP_X datetime optional
 * <li> SORT int optional default 100
 * <li> FROM_MODULE_ID string(50) mandatory
 * <li> MESSAGE_ID string(255) mandatory
 * <li> TO_MODULE_ID string(50) mandatory
 * <li> TO_PATH string(255) optional
 * <li> TO_CLASS string(255) optional
 * <li> TO_METHOD string(255) optional
 * <li> TO_METHOD_ARG string(255) optional
 * <li> VERSION int optional
 * <li> UNIQUE_ID string(32) mandatory
 * </ul>
 *
 **/
class ModuleToModuleTable extends DataManager
{
    /**
     * Returns entity map definition.
     *
     * @return array
     *
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return [
            'ID' => (new IntegerField(
                'ID',
                []
            ))->configureTitle(Loc::getMessage('BIZONE_BASE_TO_MODULE_ENTITY_ID_FIELD'))
                ->configurePrimary(true)
                ->configureAutocomplete(true),
            'TIMESTAMP_X' => (new DatetimeField(
                'TIMESTAMP_X',
                []
            ))->configureTitle(Loc::getMessage('BIZONE_BASE_TO_MODULE_ENTITY_TIMESTAMP_X_FIELD')),
            'SORT' => (new IntegerField(
                'SORT',
                []
            ))->configureTitle(Loc::getMessage('BIZONE_BASE_TO_MODULE_ENTITY_SORT_FIELD'))
                ->configureDefaultValue(100),
            'FROM_MODULE_ID' => (new StringField(
                'FROM_MODULE_ID',
                [
                    'validation' => [__CLASS__, 'validateFromModuleId'],
                ]
            ))->configureTitle(Loc::getMessage('BIZONE_BASE_TO_MODULE_ENTITY_FROM_MODULE_ID_FIELD'))
                ->configureRequired(true),
            'MESSAGE_ID' => (new StringField(
                'MESSAGE_ID',
                [
                    'validation' => [__CLASS__, 'validateMessageId'],
                ]
            ))->configureTitle(Loc::getMessage('BIZONE_BASE_TO_MODULE_ENTITY_MESSAGE_ID_FIELD'))
                ->configureRequired(true),
            'TO_MODULE_ID' => (new StringField(
                'TO_MODULE_ID',
                [
                    'validation' => [__CLASS__, 'validateToModuleId'],
                ]
            ))->configureTitle(Loc::getMessage('BIZONE_BASE_TO_MODULE_ENTITY_TO_MODULE_ID_FIELD'))
                ->configureRequired(true),
            'TO_PATH' => (new StringField(
                'TO_PATH',
                [
                    'validation' => [__CLASS__, 'validateToPath'],
                ]
            ))->configureTitle(Loc::getMessage('BIZONE_BASE_TO_MODULE_ENTITY_TO_PATH_FIELD')),
            'TO_CLASS' => (new StringField(
                'TO_CLASS',
                [
                    'validation' => [__CLASS__, 'validateToClass'],
                ]
            ))->configureTitle(Loc::getMessage('BIZONE_BASE_TO_MODULE_ENTITY_TO_CLASS_FIELD')),
            'TO_METHOD' => (new StringField(
                'TO_METHOD',
                [
                    'validation' => [__CLASS__, 'validateToMethod'],
                ]
            ))->configureTitle(Loc::getMessage('BIZONE_BASE_TO_MODULE_ENTITY_TO_METHOD_FIELD')),
            'TO_METHOD_ARG' => (new StringField(
                'TO_METHOD_ARG',
                [
                    'validation' => [__CLASS__, 'validateToMethodArg'],
                ]
            ))->configureTitle(Loc::getMessage('BIZONE_BASE_TO_MODULE_ENTITY_TO_METHOD_ARG_FIELD')),
            'VERSION' => (new IntegerField(
                'VERSION',
                []
            ))->configureTitle(Loc::getMessage('BIZONE_BASE_TO_MODULE_ENTITY_VERSION_FIELD')),
            'UNIQUE_ID' => (new StringField(
                'UNIQUE_ID',
                [
                    'validation' => [__CLASS__, 'validateUniqueId'],
                ]
            ))->configureTitle(Loc::getMessage('BIZONE_BASE_TO_MODULE_ENTITY_UNIQUE_ID_FIELD'))
                ->configureRequired(true),
        ];
    }

    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return 'b_module_to_module';
    }

    /**
     * Returns validators for FROM_MODULE_ID field.
     *
     * @return LengthValidator[]
     *
     * @throws ArgumentTypeException
     */
    public static function validateFromModuleId(): array
    {
        return [
            new LengthValidator(null, 50),
        ];
    }

    /**
     * Returns validators for MESSAGE_ID field.
     *
     * @return LengthValidator[]
     *
     * @throws ArgumentTypeException
     */
    public static function validateMessageId(): array
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * Returns validators for TO_CLASS field.
     *
     * @return LengthValidator[]
     *
     * @throws ArgumentTypeException
     */
    public static function validateToClass(): array
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * Returns validators for TO_METHOD field.
     *
     * @return LengthValidator[]
     *
     * @throws ArgumentTypeException
     */
    public static function validateToMethod(): array
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * Returns validators for TO_METHOD_ARG field.
     *
     * @return LengthValidator[]
     *
     * @throws ArgumentTypeException
     */
    public static function validateToMethodArg(): array
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * Returns validators for TO_MODULE_ID field.
     *
     * @return LengthValidator[]
     *
     * @throws ArgumentTypeException
     */
    public static function validateToModuleId(): array
    {
        return [
            new LengthValidator(null, 50),
        ];
    }

    /**
     * Returns validators for TO_PATH field.
     *
     * @return LengthValidator[]
     *
     * @throws ArgumentTypeException
     */
    public static function validateToPath(): array
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * Returns validators for UNIQUE_ID field.
     *
     * @return LengthValidator[]
     *
     * @throws ArgumentTypeException
     */
    public static function validateUniqueId(): array
    {
        return [
            new LengthValidator(null, 32),
        ];
    }
}
