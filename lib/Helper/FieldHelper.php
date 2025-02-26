<?php

namespace Bendersay\Entityadmin\Helper;

use Bitrix\Main\Grid\Column\Type;
use Bitrix\Main\Grid\Editor\Types;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\DecimalField;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\ExpressionField;
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
     * Дополняем $column:
     *  1. типом колонок
     *  2. возможность редактирования
     *
     * @param ScalarField|ExpressionField $field
     * @param array $column
     *
     * @return array
     */
    public static function preparedColumn(ScalarField|ExpressionField $field, array $column): array
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
}
