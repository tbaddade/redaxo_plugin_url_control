<?php

/**
 *
 * @author blumbeet - web.studio
 * @author Thomas Blum
 * @author mail[at]blumbeet[dot]com Thomas Blum
 *
 */


$basedir = __DIR__;
$myself = 'url_generate';

$addon = str_replace(DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $myself, '', $basedir);
$addon = ltrim(substr($addon, strrpos($addon, '/')), DIRECTORY_SEPARATOR);
$addon = strtolower($addon);

// Einstellungen für die Rewriter
$rewriter = array(
    'yrewrite' => array(
        'extension_point'       => 'YREWRITE_PREPARE',
        'extension_function'    => 'extension_rewriter_yrewrite',
        'pages'                 => true,
        'subpages'              => false,
    ),
    'rexseo' => array(
        'extension_point'       => 'REXSEO_ARTICLE_ID_NOT_FOUND',
        'extension_function'    => 'extension_rewriter_rexseo',
        'pages'                 => false,
        'subpages'              => true,
    ),
    'rexseo42' => array(
        'extension_point'       => 'REXSEO_ARTICLE_ID_NOT_FOUND',
        'extension_function'    => 'extension_rewriter_rexseo42',
        'pages'                 => false,
        'subpages'              => true,
    )
);



// Sprachdateien anhaengen
if ($REX['REDAXO']) {
    $I18N->appendFile($basedir . '/lang/');
}



$REX['ADDON']['rxid'][$myself]         = '';
//$REX['ADDON']['name'][$myself]         = $I18N->msg('b_url_generate_title');
$REX['ADDON']['version'][$myself]      = '0.0';
$REX['ADDON']['author'][$myself]       = 'blumbeet - web.studio';
$REX['ADDON']['supportpage'][$myself]  = '';
$REX['ADDON']['perm'][$myself]         = 'url_generate[]';
$REX['PERM'][]                         = 'url_generate[]';
$REX['ADDON'][$myself]['addon']        = $addon;


//$REX['ADDON'][$addon]['SUBPAGES'][] = array ('url_generate' , $I18N->msg('b_url_generate'));
if ($REX['USER'] && ($REX['USER']->isAdmin() || $REX['USER']->hasPerm('url_generate[]'))) {

    $be_page = new rex_be_page($I18N->msg('b_url_generate'), array(
            'page'      => $addon,
            'subpage'   => 'url_generate'
        )
    );
    $be_page->setHref('index.php?page=' . $addon . '&subpage=url_generate');


    if ($rewriter[$addon]['pages']) {
        $REX['ADDON']['pages'][$addon][] = $be_page;
    }

    if ($rewriter[$addon]['subpages']) {
        $REX['ADDON'][$addon]['SUBPAGES'][] = array('url_generate', $I18N->msg('b_url_generate'));
    }

}

if (rex_request('page', 'string') == $addon && rex_request('subpage', 'string') == $myself) {
    $REX['ADDON']['navigation'][$addon]['path'] = $REX['INCLUDE_PATH'].'/addons/' . $addon . '/plugins/' . $myself . '/pages/url_generate.php';
}


if ($REX['MOD_REWRITE'] !== false && !$REX['SETUP']) {
    require_once($basedir . '/lib/url_generate.php');
    url_generate::init();

    $extension_point    = $rewriter[$addon]['extension_point'];
    $extension_function = $rewriter[$addon]['extension_function'];

    rex_register_extension($extension_point, 'url_generate::' . $extension_function);

    rex_register_extension('ADDONS_INCLUDED', 'url_generate::extension_register_extensions', '', REX_EXTENSION_EARLY);

}

