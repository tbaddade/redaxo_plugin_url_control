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
        self::$path_file    = $REX['INCLUDE_PATH'].'/generated/files/url_generate_path_file.php';
        self::$paths        = self::getPaths();
    }


    /**
     * REDAXO Artikel Id setzen
     *
     */
    public static function rewriter_yrewrite()
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


    public static function rewriter_rexseo()
    {
        $params = url_generate::getArticleParams();
        return $params;
    }


    public static function rewriter_rexseo42()
    {
        $params = url_generate::getArticleParams();
        return $params;
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

            foreach ($results as $result) {

                $article_id = $result['article_id'];
                $clang      = $result['clang'];

                $a = OOArticle::getArticleById($article_id, $clang);
                if ($a instanceof OOArticle) {
                    $path = $a->getUrl().'/';
                    $path = ltrim($path, '/');
                    $path = str_replace('.html', '', $path);


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

        $url = self::getUrlPath();

        $paths = self::$paths;
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

        $url = self::getUrlPath();

        $paths = self::$paths;
        foreach ($paths as $table => $article_ids) {

            foreach ($article_ids as $article_id => $clangs) {

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

                    foreach ($clangs as $clang => $ids) {

                        if ($REX['CUR_CLANG'] == $clang) {

                            foreach ($ids as $id => $path) {
                                if ($primary_id == $id) {
                                    return $path;
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

                    foreach ($clangs as $clang => $ids) {

                        if ($REX['CUR_CLANG'] == $clang) {

                            return $ids;

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
}