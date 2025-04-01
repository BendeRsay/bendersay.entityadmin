<?php

use Bendersay\Entityadmin\Entity\EntityNameSpaceTable;
use Bendersay\Entityadmin\Enum\AccessLevelEnum;
use Bendersay\Entityadmin\Helper\EntityHelper;
use Bendersay\Entityadmin\Install\Config;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;

global $APPLICATION;

$moduleId = 'bendersay.entityadmin';

if (!Loader::includeModule($moduleId)
    || $APPLICATION->GetGroupRight($moduleId) === AccessLevelEnum::DENIED->value) {
    return;
}

Loc::loadMessages(__FILE__);

$entityClassList = array_column(EntityNameSpaceTable::getList()->fetchAll(), 'namespace');
$entityList = [];

foreach ($entityClassList as $entityClass) {
    if (EntityHelper::checkEntityExistence($entityClass)) {
        /** @var DataManager $entityClass */
        $fields = $entityClass::getMap();
        if (EntityHelper::getGroupRight($entityClass) === AccessLevelEnum::DENIED->value) {
            continue;
        }
        $entityList[] = [
            'title' => EntityHelper::getEntityTitle($entityClass),
            'name' => $entityClass,
        ];
    } else {
        $tag = str_replace('\\', '_', $entityClass);
        CAdminNotify::DeleteByTag($tag);
        CAdminNotify::Add([
            'MESSAGE' => Loc::getMessage('BENDERSAY_ENTITYADMIN_ENTITY_NOT_FOUND', ['%entity%' => $entityClass]),
            'MODULE_ID' => Config::MODULE_CODE,
            'TAG' => $tag,
            'ENABLE_CLOSE' => 'Y',
            'NOTIFY_TYPE' => CAdminNotify::TYPE_ERROR,
        ]);
    }
}

foreach ($entityList as $entity) {
    $items[] = [
        'text' => $entity['title'],
        'url' => EntityHelper::getListUrl([
            'entity' => $entity['name'],
        ]),
        'more_url' => [
            EntityHelper::getEditUrl([
                'entity' => $entity['name'],
            ]),
        ],
    ];
}

if (empty($items)) {
    return [];
}

return [
    [
        'parent_menu' => 'global_menu_content',
        'sort' => 100,
        'icon' => 'scale_menu_icon',
        'page_icon' => 'scale_menu_icon',
        'text' => Loc::getMessage('BENDERSAY_ENTITYADMIN_MENU_SECTION_TEXT'),
        'items_id' => 'menu_references',
        'items' => $items ?? [],
    ],
];
