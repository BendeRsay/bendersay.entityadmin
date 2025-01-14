<?php

use Bendersay\Entityadmin\EntityListManager;
use Bendersay\Entityadmin\EntityReferenceManager;
use Bendersay\Entityadmin\Helper\EntityHelper;
use Bitrix\Main\Application;
use Bitrix\Main\Diag\ExceptionHandlerLog;
use Bitrix\Main\Localization\Loc;

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

IncludeModuleLangFile(__FILE__);

try {
    $entityListManager = new EntityReferenceManager();

    global $APPLICATION;
    $APPLICATION->SetTitle(
        Loc::getMessage(
            'BENDERSAY_ENTITYADMIN_REFERENCE_ELEMENT_LIST_TITLE',
            ['%title%' => EntityHelper::getTableTitle($entityListManager->getEntityClass())]
        )
    );

    require_once(Application::getDocumentRoot() . '/bitrix/modules/main/include/prolog_popup_admin.php');

    ?>
    <div class="adm-toolbar-panel-container">
        <div class="adm-toolbar-panel-flexible-space">
            <?php
            $APPLICATION->IncludeComponent(
                'bitrix:main.ui.filter',
                '',
                [
                    'FILTER_ID' => $entityListManager->getFilterGridId(),
                    'GRID_ID' => $entityListManager->getGridId(),
                    'FILTER' => $entityListManager->getUiFilter(),
                    'ENABLE_LABEL' => true,
                    'DISABLE_SEARCH' => true,
                ]
            ); ?>
        </div>
    </div>

    <?php
    $APPLICATION->IncludeComponent(
        'bitrix:main.ui.grid',
        '',
        [
            'GRID_ID' => $entityListManager->getGridId(),
            'COLUMNS' => $entityListManager->getColumnList(),
            'ROWS' => $entityListManager->getRowList(),
            'TOTAL_ROWS_COUNT' => $entityListManager->getTotalRowsCount(),
            'SHOW_ROW_CHECKBOXES' => false,
            'NAV_OBJECT' => $entityListManager->getPageNavigation(),
            'AJAX_MODE' => 'Y',
            'AJAX_ID' => \CAjax::getComponentID('bitrix:main.ui.grid', '.default', ''),
            'PAGE_SIZES' => [
                ['NAME' => EntityListManager::DEFAULT_PAGE_SIZE, 'VALUE' => EntityListManager::DEFAULT_PAGE_SIZE],
                ['NAME' => 50, 'VALUE' => 50],
                ['NAME' => 100, 'VALUE' => 100],
                ['NAME' => 200, 'VALUE' => 200],
                ['NAME' => 500, 'VALUE' => 500],
            ],
            'AJAX_OPTION_JUMP' => 'N',
            'SHOW_CHECK_ALL_CHECKBOXES' => true,
            'SHOW_ROW_ACTIONS_MENU' => true,
            'SHOW_GRID_SETTINGS_MENU' => true,
            'SHOW_NAVIGATION_PANEL' => true,
            'SHOW_PAGINATION' => true,
            'SHOW_SELECTED_COUNTER' => true,
            'SHOW_TOTAL_COUNTER' => true,
            'SHOW_PAGESIZE' => true,
            'SHOW_ACTION_PANEL' => true,
            'ACTION_PANEL' => false,
            'ALLOW_COLUMNS_SORT' => true,
            'ALLOW_COLUMNS_RESIZE' => true,
            'ALLOW_HORIZONTAL_SCROLL' => true,
            'ALLOW_SORT' => true,
            'SORT' => $entityListManager->getSort()['sort'],
            'SORT_VARS' => $entityListManager->getSort()['vars'],
            'ALLOW_PIN_HEADER' => true,
            'AJAX_OPTION_HISTORY' => 'N',
            'HANDLE_RESPONSE_ERRORS' => true,
        ]
    );

    echo $entityListManager->getJsSelectElement();

    require(Application::getDocumentRoot() . '/bitrix/modules/main/include/epilog_popup_admin.php');
} catch (Exception $e) {
    require_once(Application::getDocumentRoot() . '/bitrix/modules/main/include/prolog_popup_admin.php');
    Application::getInstance()->getExceptionHandler()->writeToLog($e, ExceptionHandlerLog::CAUGHT_EXCEPTION);
    CAdminMessage::ShowMessage(
        [
            'MESSAGE' => $e->getMessage(),
            'HTML' => true,
            'TYPE' => 'ERROR',
        ]
    );
    require_once(Application::getDocumentRoot() . '/bitrix/modules/main/include/epilog_popup_admin.php');
}
