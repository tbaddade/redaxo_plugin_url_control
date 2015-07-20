<?php

/**
 *
 * @author blumbeet - web.studio
 * @author Thomas Blum
 * @author mail[at]blumbeet[dot]com Thomas Blum
 *
 */

$basedir = __DIR__;
$myself = 'url_control';

// Einstellungen für die Rewriter
$rewriter_addons = array(
    'yrewrite' => array(
        'extension_point'       => 'YREWRITE_PREPARE',
        'extension_function'    => 'extension_rewriter_yrewrite',
        'getFullUrl'            => 'rex_yrewrite::getFullUrlByArticleId',
    ),
    'seo42' => array(
        'extension_point'       => 'SEO42_ARTICLE_ID_NOT_FOUND',
        'extension_function'    => 'extension_rewriter_seo42',
        'getFullUrl'            => 'seo42::getFullUrl', 
    ),
    'rexseo' => array(
        'extension_point'       => 'REXSEO_ARTICLE_ID_NOT_FOUND',
        'extension_function'    => 'extension_rewriter_rexseo',
    ),
);


$rewriter_addon_name = '';

foreach ($rewriter_addons as $addon_name => $addon_settings) {

    if (OOAddon::isActivated($addon_name)) {

        $rewriter_addon_name = $addon_name;
        break;

    }

}


if (!isset($I18N) && !is_object($I18N)) {

    $I18N = rex_create_lang($REX['LANG']);

}


// Sprachdateien anhaengen
if ($REX['REDAXO']) {

    $I18N->appendFile($basedir . '/lang/');

}



$REX['ADDON']['rxid'][$myself]                   = '';
$REX['ADDON']['name'][$myself]                   = $I18N->msg('url_control');
$REX['ADDON']['version'][$myself]                = '2.0';
$REX['ADDON']['author'][$myself]                 = 'blumbeet - web.studio';
$REX['ADDON']['supportpage'][$myself]            = '';
$REX['ADDON']['perm'][$myself]                   = 'url_control[]';
$REX['PERM'][]                                   = 'url_control[]';
$REX['ADDON'][$myself]['rewriter']['addon_name'] = $rewriter_addon_name;
$REX['ADDON'][$myself]['rewriter']['addons']     = $rewriter_addons;



$subpages = array('url_generate', 'url_manager');

if (isset($REX['USER']) && $REX['USER'] && ($REX['USER']->isAdmin() || $REX['USER']->hasPerm('url_control[]'))) {

    foreach ($subpages as $subpage) {
        $be_page = new rex_be_page(
            $I18N->msg('url_control_' . $subpage), 
            array(
                'page'      => $myself,
                'subpage'   => $subpage
            )
        );
        $be_page->setHref('index.php?page=' . $myself . '&subpage=' . $subpage);
        $REX['ADDON']['pages'][$myself][] = $be_page;
    }
    

}


if (! $REX['SETUP']) {
    require_once $basedir . '/lib/UrlControl.php';
    require_once $basedir . '/lib/UrlGenerator.php';
    require_once $basedir . '/lib/UrlManager.php';
    require_once $basedir . '/lib/Url.php';

    UrlControl::init();

    echo Url::to('/test.html', array('key' => 1, 'name' => 'testen'));
    echo '<br>';
    echo Url::previous();
    echo '<br>';
    echo Url::current();
    echo '<br>';
    echo Url::dirname( Url::current() );
    echo '<br>';
    echo Url::filename( Url::current() );

    echo '<br>';
    $url = Url::parse(Url::current());
    echo $url->getDomain();
}
