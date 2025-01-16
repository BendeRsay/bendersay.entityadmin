<?php

use Bendersay\Entityadmin\EntityEditManager;
use Bendersay\Entityadmin\Handler\EntityEditHandler;
use Bendersay\Entityadmin\Helper\EntityHelper;
use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Diag\ExceptionHandlerLog;
use Bitrix\Main\Localization\Loc;

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

IncludeModuleLangFile(__FILE__);

try {
    $entityEditManager = new EntityEditManager();

    $handler = new EntityEditHandler($entityEditManager);

    if ($handler->isError()) {
        require_once(Application::getDocumentRoot() . '/bitrix/modules/main/include/prolog_admin_after.php');
        foreach ($handler->getError() as $error) {
            CAdminMessage::ShowMessage(
                [
                    'MESSAGE' => $error,
                    'HTML' => true,
                    'TYPE' => 'ERROR',
                ]
            );
        }
    }

    $handler->processGet()->processPost()->processFinish();

    global $APPLICATION;
    $APPLICATION->SetTitle(
        Loc::getMessage(
            'BENDERSAY_ENTITYADMIN_ELEMENT_TAB_TITLE',
            ['%title%' => EntityHelper::getEntityTitle($entityEditManager->getEntityClass())]
        )
    );

    require_once(Application::getDocumentRoot() . '/bitrix/modules/main/include/prolog_admin_after.php');

    $context = new CAdminContextMenu($entityEditManager->getMenu());
    $context->Show();

    $tabControl = new CAdminTabControl('tabControl', $entityEditManager->getTabList());
    ?>

    <form method="POST" action="<?= $entityEditManager->getActionUrl() ?>" enctype="multipart/form-data" name="editform"
          id="editform">
        <?php
        $tabControl->Begin();
    $tabControl->BeginNextTab();

    $entityEditManager->renderFieldList();
    $entityEditManager->clearDataBySession();

    ?>

        <?= bitrix_sessid_post() ?>

        <input type="hidden" name="lang" value="<?= Context::getCurrent()->getLanguage() ?>">
        <input type="hidden" name="delete" id="delete" value="">

        <?php
    $tabControl->Buttons($entityEditManager->getTabControlButtonList());
    $tabControl->End();
    ?>
    </form>

    <?php

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
