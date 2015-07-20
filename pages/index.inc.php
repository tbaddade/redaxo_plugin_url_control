<?php

require $REX['INCLUDE_PATH'] . '/layout/top.php';

$subpage = rex_request('subpage', 'string', 'url_generate');
$_REQUEST['subpage'] = $subpage;


rex_title($I18N->msg('url_control_title') . ' :: ' . $I18N->msg('url_control_' . $subpage . '_title'), $REX['ADDON']['pages'][$myself]);


require dirname(__FILE__) . '/' . $subpage . '.php';


require $REX['INCLUDE_PATH'] . '/layout/bottom.php';
