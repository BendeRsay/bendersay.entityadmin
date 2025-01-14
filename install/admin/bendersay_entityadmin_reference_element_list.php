<?php

if (!@include_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/bendersay.entityadmin/admin/bendersay_entityadmin_reference_element_list.php'
    && !@include_once $_SERVER['DOCUMENT_ROOT'] . '/local/modules/bendersay.entityadmin/admin/bendersay_entityadmin_reference_element_list.php'
) {
    include $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/404.php';
}
