<?php

/**
 *
 * @author blumbeet - web.studio
 * @author Thomas Blum
 * @author mail[at]blumbeet[dot]com
 *
 */

class url_generate extends url_control
{
    static $path_file;
    static $paths;


    public static function init()
    {
        global $REX;

        /**
         *  seit REX 4.5.0 wird GENERATED_PATH gesetzt
         *  
         */
        if (isset($REX['GENERATED_PATH'])) {
            self::$path_file = $REX['GENERATED_PATH'].'/files/url_control_generate_path_file.php';
        } else {
            self::$path_file = $REX['INCLUDE_PATH'] . '/generated/files/url_control_generate_path_file.php';
        }
        self::$paths     = self::getPaths();

    }


    public static function getRestrictionOperators()
    {
        return array(
                     '='            => '=',
                     '>'            => '>',
                     '>='           => '>=',
                     '<'            => '<',
                     '<='           => '<=',
                     '!='           => '!=',
                     'LIKE'         => 'LIKE',
                     'NOT LIKE'     => 'NOT LIKE',
                     'IN (...)'     => 'IN (...)',
                     'NOT IN (...)' => 'NOT IN (...)',
                     'BETWEEN'      => 'BETWEEN',
                     'NOT BETWEEN'  => 'NOT BETWEEN',
                    );
    }


    /**
     * Erzeugt die Domains
     *
     */
    public static function generatePathFile($params)
    {
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

                    $path = parent::getCleanPath($path);

                    $table          = $result['table'];
                    $table_params   = unserialize($result['table_parameters']);

                    $name = $table_params[$table][$table . '_name'];
                    $id   = $table_params[$table][$table . '_id'];
                    
                    $restriction_field    = $table_params[$table][$table . '_restriction_field'];
                    $restriction_operator = $table_params[$table][$table . '_restriction_operator'];
                    $restriction_value    = $table_params[$table][$table . '_restriction_value'];
                    

                    $qyery_where = '';
                    if ($restriction_field != '' && $restriction_value != '' && in_array($restriction_operator, self::getRestrictionOperators())) {

                        switch ($restriction_operator) {

                            case 'IN (...)':
                            case 'NOT IN (...)':

                                $restriction_operator = str_replace(' (...)', '', $restriction_operator);

                                $values = explode(',', $restriction_value);
                                foreach ($values as $key => $value) {
                                    if (! (int)$value > 0) {
                                        unset($values[$key]);
                                    }
                                }
                                $restriction_value = ' (' . implode(',', $values) . ') ';

                                break;

                            case 'BETWEEN':
                            case 'NOT BETWEEN':

                                $values = explode(',', $restriction_value);
                                if (count($values) == 2) {
                                    $restriction_value = $values[0] . ' AND ' . $values[1];
                                }

                                break;

                            default:

                                $restriction_value = '"' . mysql_real_escape_string($restriction_value) . '"';

                                break;


                        }

                        $qyery_where = ' WHERE ' . $restriction_field . ' ' . $restriction_operator . ' ' . $restriction_value . '';

                    }


                    $query = '  SELECT  ' . $name . '   AS name,
                                        ' . $id . '     AS id
                                FROM    ' . $table . '
                                ' . $qyery_where . '
                                ';
                    $s = rex_sql::factory();
                    $s->setQuery($query);
                    if ($s->getRows() >= 1) {
                        $urls = $s->getArray();
                        $save_names = array();
                        foreach ($urls as $url) {
                            
                            if (isset($save_names[ $url['name'] ])) {
                                $url['name'] = $url['name'] . '-' . $url['id'];
                            }


                            $paths[ $table ][ $article_id ][ $clang ][ $url['id'] ] = $path . strtolower(rex_parse_article_name($url['name'])) . '.html';

                            $save_names[ $url['name'] ] = '';
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

        $url    = parent::getUrlForComparisonWithPath();
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
     * gibt die Ids einer Tabelle zurück
     *
     */
    public static function getIds($table_name, $check = false)
    {
        global $REX;

        $paths  = self::$paths;

        foreach ($paths as $table => $article_ids) {

            if ($table_name == $table) {

                $return_ids = array();

                foreach ($article_ids as $article_id => $clangs) {

                    if ($check) {
                        if ($article_id == $REX['ARTICLE_ID']) {
                            foreach ($clangs as $clang => $ids) {
                                if ($REX['CUR_CLANG'] == $clang) {
                                    return $ids;
                                }
                            }
                        }
                    } else {
                        foreach ($clangs as $clang => $ids) {
                            if ($REX['CUR_CLANG'] == $clang) {
                                $return_ids = $return_ids + $ids;
                            }
                        }
                    }
                }
                return $return_ids;
            }
        }

        return false;
    }



    /**
     * gibt die Id des Datensatzes anhand der Url zurück
     *
     */
    public static function getId($table_name)
    {
        $url = parent::getUrlForComparisonWithPath();
        $ids = self::getIds($table_name, true);

        if ($ids) {
            foreach ($ids as $id => $path) {
                if ($path == $url) {
                    return $id;
                }
            }
        }
    }



    /**
     * gibt die Url des Datensatzes anhand der Primary Id zurück
     *
     */
    public static function getUrlById($table_name, $primary_id)
    {
        if ((int) $primary_id < 1) {
            return;
        }


        $ids = self::getIds($table_name);

        if ($ids) {
            foreach ($ids as $id => $path) {
                if ($primary_id == $id) {
                    return parent::getCleanUrl($path);
                }
            }
        }
    }



    /**
     * gibt alle Urls einer Tabelle zurück
     *
     */
    public static function getUrlsByTable($table_name)
    {
        $ids = self::getIds($table_name);

        if ($ids) {
            $save = array();
            foreach ($ids as $id => $path) {
                $save[$id] = parent::getCleanUrl($path);
            }
            return $save;
        }
    }



    /**
     * holt die gespeicherten Pfade
     *
     */
    protected static function getPaths()
    {
        if (!file_exists(self::$path_file)) {
            self::generatePathFile(array());
        }
        $content = file_get_contents(self::$path_file);
        return json_decode($content, true);
    }
}
