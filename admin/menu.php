<?php

use Bendersay\Entityadmin\Helper\EntityHelper;
use Bendersay\Entityadmin\Install\Config;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;

global $APPLICATION;

if (!Loader::includeModule(Config::MODULE_CODE)
    || $APPLICATION->GetGroupRight(Config::MODULE_CODE) === 'D') {
    return;
}

Loc::loadMessages(__FILE__);

$entityClassList = unserialize(Option::get(Config::MODULE_CODE, 'entityList'));
$entityList = [];

foreach ($entityClassList as $entityClass) {
    if (EntityHelper::checkEntityExistence($entityClass)) {
        /** @var DataManager $entityClass */
        $fields = $entityClass::getMap();
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
