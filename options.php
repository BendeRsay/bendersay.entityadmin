<?php
/** @global CMain $APPLICATION */

/** @var string $mid */

use Bendersay\Entityadmin\Helper\EntityHelper;
use Bendersay\Entityadmin\Install\Config;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Entity;

global $USER;

Loader::includeModule($mid);
IncludeModuleLangFile(__FILE__);
$module_id = $mid; //переменная нужна для файла group_rights.php
$modRight = $APPLICATION->GetGroupRight($mid);
$session = Application::getInstance()->getSession();

if (!$USER->CanDoOperation('catalog_read') && !$USER->CanDoOperation('catalog_settings')) {
    return;
}

if ($moduleErrors = $session->get('bendersay_entityadmin_errors')) {
    foreach ($moduleErrors as $error) {
        CAdminMessage::ShowMessage($error);
    }
    $session->remove('bendersay_entityadmin_errors');
}

$aTabs = [
    [
        'DIV' => 'tab_entity',
        'TAB' => Loc::getMessage('BENDERSAY_ENTITYADMIN_ENTITY_TAB_NAME'),
        'TITLE' => Loc::getMessage('BENDERSAY_ENTITYADMIN_ENTITY_TAB_TITLE'),
        'OPTIONS' => [
            [
                'CODE' => 'entityList',
                'NAME' => Loc::getMessage('BENDERSAY_ENTITYADMIN_ENTITY_CLASSES_OPTION_NAME'),
                'HINT' => Loc::getMessage('BENDERSAY_ENTITYADMIN_ENTITY_CLASSES_OPTION_HINT'),
                'SETTINGS' => [
                    'multiple_text',
                    50,
                    Loc::getMessage('BENDERSAY_ENTITYADMIN_ENTITY_CLASSES_ADD_BUTTON'),
                ],
            ],
        ],
    ],
    [
        'DIV' => 'tab_permission',
        'TAB' => Loc::getMessage('MAIN_TAB_RIGHTS'),
        'TITLE' => Loc::getMessage('MAIN_TAB_TITLE_RIGHTS'),
    ],
];

$tabControl = new CAdminTabControl('tabControl', $aTabs);

if (($request = Context::getCurrent()->getRequest())->isPost() && strlen($Update . $Apply)
    && $modRight >= 'W' && check_bitrix_sessid()) {
    $postList = $request->getPostList()->toArray();
    $moduleErrors = [];

    CAdminNotify::DeleteByModule(Config::MODULE_CODE);

    foreach ($aTabs as $tab) {
        if ($tab['DIV'] === 'tab_permission') {
            continue;
        }

        foreach ($tab['OPTIONS'] as $option) {
            if (array_key_exists($option['CODE'], $postList)) {
                switch ($option['SETTINGS'][0]) {
                    case 'multiple_text':
                        $list = array_diff($postList[$option['CODE']], ['']);
                        if ($option['CODE'] == 'entityList') {
                            foreach ($list as $key => $entityClass) {
                                if (!EntityHelper::checkEntityExistence($entityClass)) {
                                    $moduleErrors[] = 'Ошибка добавления сущности ' . $entityClass;
                                    unset($list[$key]);

                                    continue;
                                }

                                $list[$key] = Entity::normalizeEntityClass($entityClass);
                            }
                        }

                        Option::set($mid, $option['CODE'], serialize($list));

                        break;
                }
            }
        }
    }

    if ($moduleErrors) {
        $session->set('bendersay_entityadmin_errors', $moduleErrors);
        $session->save();
    }

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
    }

    foreach ($tab['OPTIONS'] as $option) { ?>
            <tr>
                <td class="adm-detail-valign-top">
                    <?php
                if ($option['HINT']) { ?>
                        <span id="hint_<?= $option['CODE']; ?>">
                        </span>
                        <script type="text/javascript">
                            BX.hint_replace(BX('hint_<?= $option['CODE']; ?>'), '<?= CUtil::JSEscape(
                                htmlspecialcharsbx($option['HINT'])
                            )?>');
                        </script>
                        <?php
                } ?>
                    <?= $option['NAME']; ?>:
                </td>
                <td>
                    <?php
                switch ($option['SETTINGS'][0]) {
                    case 'multiple_text':
                        include Loader::getDocumentRoot() . '/local/modules/' . $mid . '/options/multiple-text.php';

                        break;
                } ?>
                </td>
            </tr>
            <?php
    }
    $tabControl->EndTab();
}

$tabControl->Buttons();
?>
    <input type="submit"
           name="Update"<?php
echo ($modRight < 'W') ? ' disabled' : null ?>
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