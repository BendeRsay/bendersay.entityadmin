<?php
/** @global CMain $APPLICATION */

/** @var string $mid */

use Bendersay\Entityadmin\Enum\AccessLevelEnum;
use Bendersay\Entityadmin\Helper\EntityHelper;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

global $USER;

Loader::includeModule($mid);
IncludeModuleLangFile(__FILE__);
$module_id = $mid; //переменная нужна для файла group_rights.php
$modRight = $APPLICATION->GetGroupRight($mid);

if (!$USER->CanDoOperation('catalog_read') && !$USER->CanDoOperation('catalog_settings')) {
    return;
}

$aTabs = [
    [
        'DIV' => 'tab_entity',
        'TAB' => Loc::getMessage('BENDERSAY_ENTITYADMIN_ENTITY_TAB_NAME'),
        'TITLE' => Loc::getMessage('BENDERSAY_ENTITYADMIN_ENTITY_TAB_TITLE'),
    ],
    [
        'DIV' => 'tab_permission',
        'TAB' => Loc::getMessage('MAIN_TAB_RIGHTS'),
        'TITLE' => Loc::getMessage('MAIN_TAB_TITLE_RIGHTS'),
    ],
];

$tabControl = new CAdminTabControl('tabControl', $aTabs);

if (($request = Context::getCurrent()->getRequest())->isPost() && strlen($Update . $Apply)
    && $modRight >= AccessLevelEnum::WRITE->value && check_bitrix_sessid()) {
    $postList = $request->getPostList()->toArray();

    $Update = $Update . $Apply;
    ob_start();
    require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights.php');
    ob_end_clean();

    if (!$Apply && $postList['back_url_settings']) {
        LocalRedirect($postList['back_url_settings']);
    } else {
        LocalRedirect(
            $APPLICATION->GetCurPage() . '?mid=' . urlencode($mid) . '&lang=' . urlencode(
                LANGUAGE_ID
            ) . '&back_url_settings=' . urlencode(
                $postList['back_url_settings']
            ) . '&' . $tabControl->ActiveTabParam()
        );
    }
}

$urlParams = [
    'mid' => $mid,
    'lang' => LANGUAGE_ID,
];
$formActionUrl = $APPLICATION->GetCurPage() . '?' . http_build_query($urlParams);
?>

<form method="post" action="<?= $formActionUrl ?>">
    <?php
    echo bitrix_sessid_post();
    $tabControl->Begin();

    foreach ($aTabs as $tab) {
        $tabControl->BeginNextTab();

        if ($tab['DIV'] === 'tab_permission') {
            // Доступ к модулю
            require_once(Loader::getDocumentRoot() . '/bitrix/modules/main/admin/group_rights.php');

            continue;
        } ?>
        <tr>
            <td class="adm-detail-valign-top">
                <p>
                    <a href="<?= EntityHelper::getListUrl(
                        ['entity' => 'Bendersay\Entityadmin\Entity\EntityNameSpaceTable']
                    ) ?>"><?= Loc::getMessage('BENDERSAY_ENTITYADMIN_ENTITY_GO_SETTING') ?></a>
                </p>
                <p>
                    <a href="<?= EntityHelper::getListUrl(
                        ['entity' => 'Bendersay\Entityadmin\Entity\EntityNameSpaceGroupTable']
                    ) ?>"><?= Loc::getMessage('BENDERSAY_ENTITYADMIN_ENTITY_GO_SETTING_ACCESS') ?></a>
                </p>

            </td>
        </tr>
        <?php

        $tabControl->EndTab();
    }

    $tabControl->Buttons();
    ?>
    <input type="submit"
           name="Update"<?php
    echo ($modRight < AccessLevelEnum::WRITE->value) ? ' disabled' : null ?>
           value="<?php
           echo Loc::getMessage('MAIN_SAVE') ?>"
           class="adm-btn-save">
    <input type="reset"
           name="reset"
           value="<?php
           echo Loc::getMessage('MAIN_RESET') ?>">
    <?php
    echo bitrix_sessid_post();
    $tabControl->End();
    ?>
</form>