<?php

/**
 *
 * @author blumbeet - web.studio
 * @author Thomas Blum
 * @author mail[at]blumbeet[dot]com
 *
 */

class url_generate
{
    static $path_file;
    static $paths;


    public static function init()
    {
        global $REX;
        self::$path_file = $REX['INCLUDE_PATH'].'/generated/files/url_generate_path_file.php';
        self::$paths     = self::getPaths();
    }


    /**
     * REDAXO Artikel Id setzen
     *
     */
    public static function extension_rewriter_yrewrite()
    {
        global $REX;
        $params = url_generate::getArticleParams();
        if ((int)$params['article_id'] > 0) {
            $REX['ARTICLE_ID'] = $params['article_id'];
            $REX['CUR_CLANG']  = $params['clang'];
            return true;
        } else {
            return false;
        }
    }


    public static function extension_rewriter_rexseo()
    {
        $params = url_generate::getArticleParams();
        return $params;
    }


    public static function extension_rewriter_rexseo42()
    {
        $params = url_generate::getArticleParams();
        return $params;
    }

    public static function extension_register_extensions ()
    {
        global $REX;
        // refresh PathFile
        if ($REX['REDAXO']) {
            $extension_points = array(
                'CAT_ADDED',   'CAT_UPDATED',   'CAT_DELETED',
                'ART_ADDED',   'ART_UPDATED',   'ART_DELETED',
                'CLANG_ADDED', 'CLANG_UPDATED', 'CLANG_DELETED',
                'ALL_GENERATED'
            );

            foreach($extension_points as $extension_point) {
                rex_register_extension($extension_point, 'url_generate::generatePathFile');
            }
        }
    }


    /**
     * Erzeugt die Domains
     *
     */
    public static function generatePathFile($params)
    {
        global $REX;

        $query = '  SELECT  `article_id`,
                            `clang`,
                            `table`,
                            `table_parameters`
                    FROM    ' . $REX['TABLE_PREFIX'] . 'url_generate
                    ';
        $sql = rex_sql::factory();
        $sql->setQuery($query);

        $paths = array();
        if ($sql->getRows() >= 1) {
            $results = $sql->getArray();
            $server  = self::getServer(true);
            foreach ($results as $result) {

                $article_id = $result['article_id'];
                $clang      = $result['clang'];

                $a = OOArticle::getArticleById($article_id, $clang);
                if ($a instanceof OOArticle) {

                    $path = $a->getUrl();
                    $path = self::getCleanPath($path);
                    $path = $server . $path;

                    $table          = $result['table'];
                    $table_params   = unserialize($result['table_parameters']);

                    $name = $table_params[$table][$table . '_name'];
                    $id   = $table_params[$table][$table . '_id'];


                    $query = '  SELECT  ' . $name . '   AS name,
                                        ' . $id . '     AS id
                                FROM    ' . $table . '
                                ';
                    $s = rex_sql::factory();
                    $s->setQuery($query);
                    if ($s->getRows() >= 1) {
                        $urls = $s->getArray();
                        foreach ($urls as $url) {
                            $paths[ $table ][ $article_id ][ $clang ][ $url['id'] ] = $path . strtolower(rex_parse_article_name($url['name'])) . '.html';
                        }
                    }

                }
            }
        }

        rex_put_file_contents(self::$path_file, json_encode($paths));
    }




    /**
     * gibt die REDAXO Artikel Params anhand der Url zurück
     *
     */
    public static function getArticleParams()
    {
        global $REX;

        $url    = self::getUrlPath();
        $paths  = self::$paths;

        foreach ($paths as $table => $article_ids) {

            foreach ($article_ids as $article_id => $clangs) {

                foreach ($clangs as $clang => $ids) {

                    if ($REX['CUR_CLANG'] == $clang) {

                        foreach ($ids as $id => $path) {
                            if ($path == $url) {
                                return array('article_id' => $article_id, 'clang' => $clang);
                            }
                        }

                    }
                }
            }
        }
    }



    /**
     * gibt die Id des Datensatzes anhand der Url zurück
     *
     */
    public static function getId()
    {
        global $REX;

        $url    = self::getUrlPath();
        $paths  = self::$paths;

        foreach ($paths as $table => $article_ids) {

            foreach ($article_ids as $article_id => $clangs) {

                if ($article_id == $REX['ARTICLE_ID']) {

                    foreach ($clangs as $clang => $ids) {

                        if ($REX['CUR_CLANG'] == $clang) {

                            foreach ($ids as $id => $path) {
                                if ($path == $url) {
                                    return $id;
                                }
                            }

                        }
                    }

                }
            }
        }
    }



    /**
     * gibt die Url des Datensatzes anhand der Primary Id zurück
     *
     */
    public static function getUrlById($primary_id, $table_name)
    {
        global $REX;

        if ((int)$primary_id < 1) {
            return;
        }


        $paths = self::$paths;
        foreach ($paths as $table => $article_ids) {

            if ($table_name == $table) {

                foreach ($article_ids as $article_id => $clangs) {

                    if ($article_id == $REX['ARTICLE_ID']) {

                        foreach ($clangs as $clang => $ids) {

                            if ($REX['CUR_CLANG'] == $clang) {

                                foreach ($ids as $id => $path) {
                                    if ($primary_id == $id) {
                                        return substr($path, strpos($path, '/'));
                                    }
                                }

                            }
                        }
                    }
                }
            }
        }
    }



    /**
     * gibt die Urls der Tabelle zurück
     *
     */
    public static function getUrlsByTable($table_name)
    {
        global $REX;

        $paths = self::$paths;
        foreach ($paths as $table => $article_ids) {

            if ($table_name == $table) {

                foreach ($article_ids as $article_id => $clangs) {

                    if ($article_id == $REX['ARTICLE_ID']) {

                        foreach ($clangs as $clang => $ids) {

                            if ($REX['CUR_CLANG'] == $clang) {
                                $save = array();
                                foreach ($ids as $id => $path) {
                                    $save[$id] = substr($path, strpos($path, '/'));
                                }
                                return $save;

                            }
                        }
                    }
                }
            }
        }
    }



    /**
     * gibt den Urlpfad zurück
     *
     */
    protected static function getUrlPath()
    {
        $url_path = urldecode($_SERVER['REQUEST_URI']);
        $url_path = ltrim($url_path, '/');
        $url_path = $_SERVER['SERVER_NAME'] . '/' . $url_path;

        // query löschen
        if(($pos = strpos($url_path, '?')) !== false) {
            $url_path = substr($url_path, 0, $pos);
        }

        // fragment löschen
        if(($pos = strpos($url_path, '#')) !== false) {
            $url_path = substr($url_path, 0, $pos);
        }

        return $url_path;
    }



    /**
     * holt die gespeicherten Pfade
     *
     */
    protected static function getPaths()
    {
        if(!file_exists(self::$path_file)) {
            self::generatePathFile(array());
        }
        $content = file_get_contents(self::$path_file);
        return json_decode($content, true);
    }



    /**
     *
     *
     */
    protected static function getCleanPath($path)
    {
        global $REX;
        return trim(str_replace(array(self::getServer(), $REX['SERVER'], '.html'), '', $path), '/') . '/';
    }



    /**
     *
     *
     */
    protected static function getServer($ignore_scheme = false)
    {
        global $REX;
        $server = trim($REX['SERVER'], '/') . '/';
        if (strpos($server, '://') === false) {
            $scheme = 'http';
            if($_SERVER['SERVER_PORT'] == 443) {
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