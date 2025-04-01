<?php

namespace Bendersay\Entityadmin\Entity;

use Bendersay\Entityadmin\DataManagerInterface;
use Bendersay\Entityadmin\Orm\Fields\Validators\EntityNameSpaceValidator;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\EntityError;
use Bitrix\Main\Entity\Event;
use Bitrix\Main\Entity\EventResult;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\ManyToMany;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Fields\Validators\UniqueValidator;
use Bitrix\Main\SystemException;

/**
 * Сущность namespace сущностей
 */
class EntityNameSpaceTable extends DataManager implements DataManagerInterface
{
    /** @inheritdoc */
    public static function getTableName(): string
    {
        return 'bendersay_entityadmin_entity_namespace';
    }

    /**
     * Название сущности
     *
     * @return string
     */
    public static function getTitle(): string
    {
        return 'namespace сущностей';
    }

    /** @inheritdoc */
    public static function getEntityReferenceShowField(): string
    {
        return 'namespace';
    }

    /** @inheritdoc
     * @throws ArgumentException
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return [
            'id' => new IntegerField(
                'id',
                [
                    'primary' => true,
                    'autocomplete' => true,
                    'title' => 'Идентификатор записи',
                ]
            ),
            'namespace' => new StringField(
                'namespace',
                [
                    'title' => 'Классы сущностей',
                    'required' => true,
                    'unique' => true,
                    'validation' => function () {
                        return [
                            new LengthValidator(1, 255),
                            new UniqueValidator(),
                            new EntityNameSpaceValidator(),
                        ];
                    },
                    'save_data_modification' => function () {
                        return [
                            function ($value) {
                                return Entity::normalizeEntityClass($value);
                            },
                        ];
                    },
                ]
            ),

            (new ManyToMany('GROUP', GroupTable::class))
                ->configureTableName(EntityNameSpaceGroupTable::getTableName())
                ->configureLocalPrimary('id', 'namespaceId')
                ->configureLocalReference('namespace')
                ->configureRemotePrimary('ID', 'GROUP_ID')
                ->configureRemoteReference('GROUP'),

        ];
    }

    /**
     * Проверяем, есть ли настройки для этого namespace, перед удалением
     *
     * @param Event $event
     *
     * @return EventResult
     *
     * @throws SystemException
     * @throws \Bitrix\Main\ObjectPropertyException
     */
    public static function onBeforeDelete(Event $event): EventResult
    {
        $result = new EventResult();
        $elemId = $event->getParameter('id')['id'];

        $nameSpaceGroupCount = EntityNameSpaceGroupTable::getCount(['namespaceId' => $elemId]);

        if ($nameSpaceGroupCount === 0) {
            return $result;
        }

        $result->addError(
            new EntityError(
                'Нельзя удалить namespace, который используется в настройках прав доступа.'
            )
        );

        return $result;
    }
}
