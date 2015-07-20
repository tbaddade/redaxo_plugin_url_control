<?php

/**
 *
 * @author blumbeet - web.studio
 * @author Thomas Blum
 * @author mail[at]blumbeet[dot]com
 *
 */

class UrlControl
{
    protected static $rex_server;

    public static function init()
    {
        global $REX;
        
        self::$rex_server = $REX['SERVER'];

        
        if ($REX['MOD_REWRITE'] !== false) {

            $rewriter_addon_name = $REX['ADDON']['url_control']['rewriter']['addon_name'];
            $rewriter_addons     = $REX['ADDON']['url_control']['rewriter']['addons'];

            if (isset($rewriter_addons[$rewriter_addon_name])) {
                rex_register_extension(
                    $rewriter_addons[$rewriter_addon_name]['extension_point'], 
                    'UrlControl::' . $rewriter_addons[$rewriter_addon_name]['extension_function']
                );

                rex_register_extension('ADDONS_INCLUDED', 'UrlControl::registerExtensionsForGeneratePathFile', '', REX_EXTENSION_EARLY);
                rex_register_extension('URL_REWRITE', 'UrlGenerator::rewrite');
            }

        }


        UrlGenerator::init();
    }


    public static function debug($value, $exit = true)
    {
        echo '<pre style="text-align: left">';
        print_r($value);
        echo '</pre>';

        if ($exit) {
            exit();
        }

    }


    public static function registerExtensionsForGeneratePathFile()
    {
        global $REX;
        // refresh PathFile
        if ($REX['REDAXO']) {
            $extension_points = array(
                'CAT_ADDED',   'CAT_UPDATED',   'CAT_DELETED',
                'ART_ADDED',   'ART_UPDATED',   'ART_DELETED',
                'CLANG_ADDED', 'CLANG_UPDATED', 'CLANG_DELETED',
                'ALL_GENERATED',
                'REX_FORM_SAVED', 
                'XFORM_DATA_ADDED', 'XFORM_DATA_UPDATED'
            );

            foreach ($extension_points as $extension_point) {
                rex_register_extension($extension_point, 'UrlGenerator::generatePathFile');
            }
        }
    }


    /**
     * REDAXO Artikel Id setzen
     *
     */
    public static function extension_rewriter_yrewrite()
    {
        //$params = UrlManager::control();
        $params = false;
        if (!$params) {
            $params = UrlGenerator::getArticleParams();
        }

        return $params;
    }


    public static function extension_rewriter_seo42()
    {
        //$params = UrlManager::control();
        $params = false;
        if (!$params) {
            $params = UrlGenerator::getArticleParams();
        }

        return $params;
    }


    public static function extension_rewriter_rexseo()
    {
        $params = UrlManager::control();
        if (!$params) {
            $params = UrlGenerator::getArticleParams();
        }

        return $params;
    }
    
}
