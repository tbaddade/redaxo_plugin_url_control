<?php

/**
 *
 * @author blumbeet - web.studio
 * @author Thomas Blum
 * @author mail[at]blumbeet[dot]com
 *
 */

class url_control
{
    protected static $rex_server;

    public static function init()
    {
        global $REX;
        
        self::$rex_server = $REX['SERVER'];

        url_generate::init();
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


    public static function extension_register_extensions()
    {
        global $REX;
        // refresh PathFile
        if ($REX['REDAXO']) {
            $extension_points = array(
                'CAT_ADDED',   'CAT_UPDATED',   'CAT_DELETED',
                'ART_ADDED',   'ART_UPDATED',   'ART_DELETED',
                'CLANG_ADDED', 'CLANG_UPDATED', 'CLANG_DELETED',
                'ALL_GENERATED',
                'XFORM_DATA_ADDED', 'XFORM_DATA_UPDATED'
            );

            foreach ($extension_points as $extension_point) {
                rex_register_extension($extension_point, 'url_generate::generatePathFile');
            }
        }
    }


    /**
     * REDAXO Artikel Id setzen
     *
     */
    public static function extension_rewriter_yrewrite()
    {
        $params = url_manager::control();
        if (!$params) {
            $params = url_generate::getArticleParams();
        }

        return $params;
    }


    public static function extension_rewriter_seo42()
    {
        $params = url_manager::control();
        if (!$params) {
            $params = url_generate::getArticleParams();
        }
        return $params;
    }


    public static function extension_rewriter_rexseo()
    {
        $params = url_manager::control();
        if (!$params) {
            $params = url_generate::getArticleParams();
        }
        return $params;
    }



    /**
     * Deprecated
     * rexseo42 - umbenannt in seo42
     */
    public static function extension_rewriter_rexseo42()
    {
        $params = url_generate::getArticleParams();
        return $params;
    }

    public static function extension_sitemap_seo42($sitemap) {
        global $REX;
        $myself   = 'url_control';
        $addon    = $REX['ADDON'][$myself]['addon'];
        $rewriter = $REX['ADDON'][$myself]['rewriter'];

        $query = '  SELECT  `article_id`,
                            `clang`,
                            `table`,
                            `table_parameters`
                    FROM    ' . $REX['TABLE_PREFIX'] . 'url_control_generate
                    ';
        $sql = rex_sql::factory();
        $sql->setQuery($query);

        $paths = array();
        if ($sql->getRows() >= 1) {
            $results = $sql->getArray();
            foreach ($results as $result) {

                $article_id = $result['article_id'];
                $clang      = $result['clang'];

                $a = OOArticle::getArticleById($article_id, $clang);
                if ($a instanceof OOArticle) {

                    if (isset($rewriter[$addon]['get_url'])) {
                        $func = $rewriter[$addon]['get_url'];
                        $path = call_user_func($func, $article_id, $clang);
                    } else {
                        $path = $a->getUrl();
                    }

                    $table          = $result['table'];
                    $table_params   = unserialize($result['table_parameters']);

                    if(isset($table_params[$table][$table . '_sitemap_settings'])) {
                        $sitemapSetting   = $table_params[$table][$table . '_sitemap_settings'];
                    } else {
                        $sitemapSetting = 'Artikel ohne dynamische Seiten';
                    }
                    switch($sitemapSetting) {
                        case 'Artikel ohne dynamische Seiten':
                            break;
                        case 'Artikel mit dynamische Seiten':
                            $sitemapNode = $sitemap['subject'][$a->getId()][0];
                            $tableUrls = url_generate::getUrlsByTable($table);
                            foreach($tableUrls as $tableUrl) {
                                $sitemapNode['loc'] = $tableUrl;
                                $sitemap['subject'][][] = $sitemapNode;
                            }
                            break;
                        case 'Nur dynamische Seiten':
                            $sitemapNode = $sitemap['subject'][$a->getId()][0];
                            unset($sitemap['subject'][$a->getId()]);
                            $tableUrls = url_generate::getUrlsByTable($table);
                            foreach($tableUrls as $tableUrl) {
                                $sitemapNode['loc'] = $tableUrl;
                                $sitemap['subject'][][] = $sitemapNode;
                            }
                            break;
                    }



                }
            }
        }
        return $sitemap['subject'];
    }



    /**
     * gibt eine saubere Url für Links zurück
     * je nach Server entweder mit http:// oder absolut beginnend mit /
     *
    */
    public static function getCleanUrl($url)
    {
        $server = rtrim(self::getServer(), '/');
        return str_replace($server, '', $url);
    }

    /**
     * gibt den Urlpfad zurück
     *
     */
    public static function getUrlForComparisonWithPath()
    {
        return self::getFullRequestedUrl();
    }


    public static function getFullRequestedUrl()
    {
        $s = empty($_SERVER['HTTPS']) ? '' : ($_SERVER['HTTPS'] == 'on') ? 's' : '';
        $protocol = substr(strtolower($_SERVER['SERVER_PROTOCOL']), 0, strpos(strtolower($_SERVER['SERVER_PROTOCOL']), '/')) . $s;
        $port = ($_SERVER['SERVER_PORT'] == '80') ? '' : (':' . $_SERVER['SERVER_PORT']);

        return $protocol . '://' . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
    }



    /**
     * gibt einen sauberen Pfad zurück
     * der für alle Rewriter gleich erstellt wird
     *
     * @return
     * www.domain.de/kategorie/artikel/
     * domain.de/kategorie/artikel/
     *
     * so nicht:
     * http://www.domain.de/kategorie/artikel/
     * http://domain.de/kategorie/artikel/
     * kategorie/artikel/
     * /kategorie/artikel/
     */
    public static function getCleanPath($path)
    {
        global $REX;

        // html und Slashes am Anfang und Ende aus aktueller getUrl() löschen
        $path = trim(str_replace('.html', '', $path), '/') . '/';
        //$path = str_replace('.html', '', $path) . '/';

        // kein Scheme vorhanden, dann setzen
        if (strpos($path, '://') === false) {

            $server = self::getServer();

            $path = $server . $path;
        }

        // nur Host und Path zurückgeben
        // $parse = parse_url($path);
        // $path  = $parse['host'] . $parse['path'];

        return $path;
    }



    /**
     *
     *
     */
    public static function getServer($ignore_scheme = false)
    {
        global $REX;
        $server = trim($REX['SERVER'], '/') . '/';

        // Speziell für yrewrite.
        if (strpos($server, 'undefined') !== false) {
            $server = self::$rex_server;
        }

        if (strpos($server, '://') === false) {
            $scheme = 'http';
            if ($_SERVER['SERVER_PORT'] == 443) {
                $scheme .= 's';
            }
            $server = $scheme . '://' . $server;
        }

        if ($ignore_scheme) {
            $parse  = parse_url($server);
            $server = $parse['host'] . $parse['path'];
        }

        return $server;
    }
}
