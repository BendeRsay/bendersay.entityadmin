<?php

use Bendersay\Entityadmin\EntityListManager;
use Bendersay\Entityadmin\Handler\EntityListHandler;
use Bendersay\Entityadmin\Helper\EntityHelper;
use Bitrix\Main\Application;
use Bitrix\Main\Diag\ExceptionHandlerLog;
use Bitrix\Main\Localization\Loc;

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

IncludeModuleLangFile(__FILE__);

try {
    $entityListManager = new EntityListManager();

    $handler = new EntityListHandler($entityListManager);

    if ($handler->isError()) {
        require_once(Application::getDocumentRoot() . '/bitrix/modules/main/include/prolog_admin_after.php');
        CAdminMessage::ShowMessage(
            [
                'MESSAGE' => $handler->getError(),
                'HTML' => true,
                'TYPE' => 'ERROR',
            ]
        );
    }

    $handler->processGet()->processPost()->processFinish();

    global $APPLICATION;
    $APPLICATION->SetTitle(
        Loc::getMessage(
            'BENDERSAY_ENTITYADMIN_LIST_TITLE',
            ['%title%' => EntityHelper::getEntityTitle($entityListManager->getEntityClass())]
        )
    );

    require_once(Application::getDocumentRoot() . '/bitrix/modules/main/include/prolog_admin_after.php');

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
                    'ENABLE_LIVE_SEARCH' => true,
                    'DISABLE_SEARCH' => false,
                ]
            ); ?>
        </div>
        <?php
        if ($entityListManager->getModRight() === 'W') {
            (new CAdminUiContextMenu([
                [
                    'TEXT' => Loc::getMessage('BENDERSAY_ENTITYADMIN_ADD_CONTEXT_ACTION_TEXT'),
                    'LINK' => EntityHelper::getEditUrl(
                        [
                            'entity' => $entityListManager->getEntityClass(),
                            'add' => 'Y',
                        ]
                    ),
                    'TITLE' => Loc::getMessage('BENDERSAY_ENTITYADMIN_ADD_CONTEXT_ACTION_TEXT'),
                    'ICON' => 'btn_new',
                ],
            ]))->Show();
        }
        ?>
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
            'SHOW_ROW_CHECKBOXES' => $entityListManager->showRowCheckbox(),
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
            'ACTION_PANEL' => $entityListManager->getActionPanel(),
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

    require(Application::getDocumentRoot() . '/bitrix/modules/main/include/epilog_admin.php');
} catch (Exception $e) {
    require_once(Application::getDocumentRoot() . '/bitrix/modules/main/include/prolog_admin_after.php');
    Application::getInstance()->getExceptionHandler()->writeToLog($e, ExceptionHandlerLog::CAUGHT_EXCEPTION);
    CAdminMessage::ShowMessage(
        [
            'MESSAGE' => $e->getMessage(),
            'HTML' => true,
            'TYPE' => 'ERROR',
        ]
    );
    require_once(Application::getDocumentRoot() . '/bitrix/modules/main/include/epilog_admin.php');
}
