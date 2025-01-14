<?php

namespace Bendersay\Entityadmin\Helper;

use Bitrix\Main\Grid\Column\Type;
use Bitrix\Main\Grid\Editor\Types;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\DecimalField;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\ScalarField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\UI\Filter\FieldAdapter;

/**
 * Хелпер для работы с полем сущности
 */
class FieldHelper
{
    /** @var string целочисленный тип поля */
    public const string TYPE_INTEGER = 'integer';

    /** @var string дробный тип поля */
    public const string TYPE_FLOAT = 'float';

    /** @var string boolean тип поля */
    public const string TYPE_BOOLEAN = 'boolean';

    /** @var string строковый тип поля */
    public const string TYPE_STRING = 'string';

    /** @var string тип поля дата */
    public const string TYPE_DATE = 'date';

    /** @var string тип поля дата со временем */
    public const string TYPE_DATETIME = 'datetime';

    /** @var string тип поля перечисляемых значений */
    public const string TYPE_ENUM = 'enum';

    /** @var string тип поля текст */
    public const string TYPE_TEXT = 'text';

    /**
     * Дополняем $column типом колонок
     *
     * @param ScalarField $field
     * @param array $column
     *
     * @return array
     */
    public static function preparedColumn(ScalarField $field, array $column): array
    {
        $editable = !($field->isPrimary() || $field->isAutocomplete());

        $columnNew = [
            'type' => Type::TEXT,
            'editable' => false,
        ];

        if ($editable) {
            $columnNew = match (get_class($field)) {
                IntegerField::class => [
                    'type' => Type::INT,
                    'editable' => ['TYPE' => Types::NUMBER],
                ],
                FloatField::class, DecimalField::class => [
                    'type' => Type::FLOAT,
                    'editable' => ['TYPE' => Types::NUMBER],
                ],
                StringField::class => [
                    'type' => Type::TEXT,
                    'editable' => ['TYPE' => Types::TEXT],
                ],
                TextField::class => [
                    'type' => Type::TEXT,
                    'editable' => ['TYPE' => Types::TEXTAREA],
                ],
                DateField::class, DatetimeField::class => [
                    'type' => Type::DATE,
                    'editable' => ['TYPE' => Types::DATE],
                ],
                BooleanField::class => [
                    'type' => Type::CHECKBOX,
                    'editable' => ['TYPE' => Types::CHECKBOX],
                ],
                EnumField::class => [
                    'editable' => false,
                ],
                default => $columnNew,
            };
        }

        return array_merge($column, $columnNew);
    }

    public static function preparedColumnArray(array $field, array $column): array
    {
        $editable = !($field['primary'] || $field['autocomplete'] || isset($field['expression']));

        $columnNew = [
            'type' => Type::TEXT,
            'editable' => false,
        ];

        if ($editable) {
            $columnNew = match ($field['data_type']) {
                self::TYPE_INTEGER => [
                    'type' => Type::INT,
                    'editable' => ['TYPE' => Types::NUMBER],
                ],
                self::TYPE_FLOAT => [
                    'type' => Type::FLOAT,
                    'editable' => ['TYPE' => Types::NUMBER],
                ],
                self::TYPE_STRING => [
                    'type' => Type::TEXT,
                    'editable' => ['TYPE' => Types::TEXT],
                ],
                self::TYPE_TEXT => [
                    'type' => Type::TEXT,
                    'editable' => ['TYPE' => Types::TEXTAREA],
                ],
                self::TYPE_DATE, self::TYPE_DATETIME => [
                    'type' => Type::DATE,
                    'editable' => ['TYPE' => Types::DATE],
                ],
                self::TYPE_BOOLEAN => [
                    'type' => Type::CHECKBOX,
                    'editable' => ['TYPE' => Types::CHECKBOX],
                ],
                self::TYPE_ENUM => [
                    'editable' => false,
                ],
                default => $columnNew,
            };
        }

        return array_merge($column, $columnNew);
    }

    /**
     * Получаем тип фильтра для поля
     *
     * @param ScalarField $field
     *
     * @return false|string
     */
    public static function getUiFilterTypeByObject(ScalarField $field): false|string
    {
        return match (get_class($field)) {
            IntegerField::class, FloatField::class, DecimalField::class => FieldAdapter::NUMBER,
            StringField::class => FieldAdapter::STRING,
            TextField::class => FieldAdapter::TEXTAREA,
            DateField::class, DatetimeField::class => FieldAdapter::DATE,
            BooleanField::class => FieldAdapter::CHECKBOX,
            EnumField::class => FieldAdapter::LIST,
            default => false,
        };
    }

    /**
     * Получаем тип фильтра для поля
     *
     * @param array $field
     *
     * @return false|string
     */
    public static function getUiFilterTypeByArray(array $field): false|string
    {
        return match ($field['data_type']) {
            self::TYPE_INTEGER, self::TYPE_FLOAT => FieldAdapter::NUMBER,
            self::TYPE_STRING => FieldAdapter::STRING,
            self::TYPE_TEXT => FieldAdapter::TEXTAREA,
            self::TYPE_DATE, self::TYPE_DATETIME => FieldAdapter::DATE,
            self::TYPE_BOOLEAN => FieldAdapter::CHECKBOX,
            self::TYPE_ENUM => FieldAdapter::LIST,
            default => false,
        };
    }
}
