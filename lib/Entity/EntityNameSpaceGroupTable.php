<?php

namespace Bendersay\Entityadmin\Entity;

use Bendersay\Entityadmin\Enum\AccessLevelEnum;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\Validators\EnumValidator;
use Bitrix\Main\ORM\Fields\Validators\ForeignValidator;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;

/**
 * Связь namespace сущностей и группы
 */
class EntityNameSpaceGroupTable extends DataManager
{
    /**
     * @inheritdoc
     */
    public static function getTableName(): string
    {
        return 'bendersay_entityadmin_entity_namespace_group';
    }

    /**
     * @inheritdoc
     */
    public static function getTitle(): string
    {
        return 'Связь namespace сущностей и группы';
    }

    /**
     * @inheritdoc
     *
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return [
            'namespaceId' => new IntegerField(
                'namespaceId',
                [
                    'primary' => true,
                    'title' => 'id namespace сущностей',
                    'validation' => function () {
                        return [
                            new ForeignValidator(EntityNameSpaceTable::getEntity()->getField('id')),
                        ];
                    },
                ]
            ),
            'namespace' => (new Reference(
                'namespace',
                EntityNameSpaceTable::class,
                Join::on('this.namespaceId', 'ref.id')
            ))->configureJoinType(Join::TYPE_INNER),

            'GROUP_ID' => new IntegerField(
                'GROUP_ID',
                [
                    'primary' => true,
                    'title' => 'ID группы пользователей',
                    'validation' => function () {
                        return [
                            new ForeignValidator(GroupTable::getEntity()->getField('ID')),
                        ];
                    },
                ]
            ),
            'GROUP' => (new Reference(
                'GROUP',
                GroupTable::class,
                Join::on('this.GROUP_ID', 'ref.ID')
            ))->configureJoinType(Join::TYPE_INNER),

            'accessLevelEnum' => new EnumField(
                'accessLevelEnum',
                [
                    'required' => true,
                    'title' => 'Уровень доступа',
                    'values' => array_column(AccessLevelEnum::cases(), 'value'),
                    'validation' => function () {
                        return [
                            new EnumValidator(),
                        ];
                    },
                ]
            ),
        ];
    }
}
