<?php

/**
 *
 * @author blumbeet - web.studio
 * @author Thomas Blum
 * @author mail[at]blumbeet[dot]com
 *
 */

class UrlGenerator extends UrlControl
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

        self::$paths = self::getPaths();

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
                     'FIND_IN_SET'  => 'FIND_IN_SET',
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

        $rewriter_addon_name = $REX['ADDON'][$myself]['rewriter']['addon_name'];
        $rewriter_addons     = $REX['ADDON'][$myself]['rewriter']['addons'];

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

                    $article_url = $a->getUrl();
 
                    if (isset($rewriter_addons[$rewriter_addon_name]['getFullUrl'])) {

                        $func = $rewriter_addons[$rewriter_addon_name]['getFullUrl'];
                        $article_url = call_user_func($func, $article_id, $clang);

                    } else {

                        $url = Url::parse($REX['SERVER']);
                        if ($url->getScheme() == '') {
                            // unvollständige Url in $REX['SERVER']
                            $article_url = 'http://' . rtrim($REX['SERVER'], '/') . '/' . ltrim($article_url, '/');
                        } else {
                            $article_url = rtrim($REX['SERVER'], '/') . '/' . ltrim($article_url, '/');
                        }

                    }

                    $article_url_parsed = Url::parse($article_url);

                    $article_domain = $article_url_parsed->getDomain();
                    $article_path = $article_url_parsed->getPath();
 
                    // html und Slashes am Anfang und Ende aus aktueller getUrl() löschen
                    $article_path = rtrim(str_replace('.html', '', $article_path), '/') . '/';


                    $table          = $result['table'];
                    $table_params   = unserialize($result['table_parameters']);

                    $name   = $table_params[$table][$table . '_name'];
                    $name_2 = $table_params[$table][$table . '_name_2'];
                    $id     = $table_params[$table][$table . '_id'];
                    
                    $restriction_field    = $table_params[$table][$table . '_restriction_field'];
                    $restriction_operator = $table_params[$table][$table . '_restriction_operator'];
                    $restriction_value    = $table_params[$table][$table . '_restriction_value'];
                    

                    $query_where = '';
                    if ($restriction_field != '' && $restriction_value != '' && in_array($restriction_operator, self::getRestrictionOperators())) {

                        switch ($restriction_operator) {

                            case 'FIND_IN_SET':

                                break;

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

                        switch ($restriction_operator) {

                            case 'FIND_IN_SET':

                                $qyery_where = ' WHERE ' . $restriction_operator . ' (' . $restriction_value . ', ' . $restriction_field . ')';

                                break;

                            default:

                                $query_where = ' WHERE ' . $restriction_field . ' ' . $restriction_operator . ' ' . $restriction_value;

                                break;
                        }

                    }

                    $query_select = ($name_2 != '') ? ', ' . $name_2 . ' AS name_2' : '';

                    $query = '  SELECT  ' . $name . '   AS name,
                                        ' . $id . '     AS id
                                        ' . $query_select . '
                                FROM    ' . $table . '
                                ' . $query_where . '
                                ';
                    $s = rex_sql::factory();
                    $s->setQuery($query);
                    if ($s->getRows() >= 1) {
                        $urls = $s->getArray();
                        $save_names = array();
                        foreach ($urls as $url) {

                            if (isset($url['name_2']) && $url['name_2'] != '') {
                                $url['name'] = $url['name'] . ' ' . $url['name_2'];
                            }
                            
                            if (isset($save_names[ $url['name'] ])) {
                                $url['name'] = $url['name'] . '-' . $url['id'];
                            }


                            //$paths[ $table ][ $article_id ][ $clang ][ $url['id'] ] = $path . strtolower(rex_parse_article_name($url['name'])) . '.html';


                            $paths[ $article_domain ][ $article_id ][ $clang ][ $url['id'] ] = $article_path . strtolower(rex_parse_article_name($url['name'])) . '.html';

                            $save_names[ $url['name'] ] = '';
                        }
                    }

                }
            }
        }
        UrlControl::debug($paths, 0);
        rex_put_file_contents(self::$path_file, json_encode($paths));
    }




    /**
     * gibt die REDAXO Artikel Params anhand der Url zurück
     *
     */
    public static function getArticleParams()
    {
        $paths = self::$paths;

        $url = Url::parse( Url::current() );
        $current_domain = $url->getDomain();
        $current_path = $url->getPath();

        foreach ($paths as $domain => $article_ids) {

            if ($current_domain == $domain) {

                foreach ($article_ids as $article_id => $clangs) {

                    foreach ($clangs as $clang => $ids) {

                        foreach ($ids as $id => $path) {

                            if ($current_path == $path) {
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
        $paths = self::$paths;

        $url = Url::parse( Url::current() );
        $current_domain = $url->getDomain();
        $current_path = $url->getPath();

        foreach ($paths as $domain => $article_ids) {

            if ($current_domain == $domain) {

                foreach ($article_ids as $article_id => $clangs) {

                    foreach ($clangs as $clang => $ids) {

                        foreach ($ids as $id => $path) {

                            if ($current_path == $path) {
                                return $id;
                            }

                        }
                    }
                }
            }
        }

        return false;
    }



    /**
     * gibt die Url des Datensatzes anhand der Primary Id zurück
     *
     */
    public static function getUrlById($primary_id, $param_article_id = null, $param_clang = null)
    {
        global $REX;

        if ((int) $primary_id < 1) {
            return;
        }

        if (is_null($param_article_id)) {
            $param_article_id = $REX['ARTICLE_ID'];
        }

        if (is_null($param_clang)) {
            $param_clang = $REX['CUR_CLANG'];
        }

        $paths = self::$paths;

        $url = Url::parse( Url::current() );
        $current_domain = $url->getDomain();

        foreach ($paths as $domain => $article_ids) {

            foreach ($article_ids as $article_id => $clangs) {

                if ($param_article_id == $article_id) {

                    foreach ($clangs as $clang => $ids) {

                        if ($param_clang == $clang) {

                            foreach ($ids as $id => $path) {

                                if ($primary_id == $id) {

                                    if ($current_domain == $domain) {
                                        return $path;
                                    } else {
                                        return $url->getScheme() . '://' . $domain . $path;
                                    }
                                    
                                }

                            }

                        }
                    }

                }
            }
        }

        return false;
    }



    /**
     * gibt alle Urls eines Artikels zurück
     *
     */
    public static function getUrlsByArticleId($param_article_id = null, $param_clang = null)
    {
        global $REX;

        if (is_null($param_article_id)) {
            $param_article_id = $REX['ARTICLE_ID'];
        }

        if (is_null($param_clang)) {
            $param_clang = $REX['CUR_CLANG'];
        }

        $paths = self::$paths;

        $url = Url::parse( Url::current() );
        $current_domain = $url->getDomain();

        $urls = array();
        foreach ($paths as $domain => $article_ids) {

            foreach ($article_ids as $article_id => $clangs) {

                if ($param_article_id == $article_id) {

                    foreach ($clangs as $clang => $ids) {

                        if ($param_clang == $clang) {

                            foreach ($ids as $id => $path) {

                                if ($current_domain == $domain) {
                                    $urls[ $id ] = $path;
                                } else {
                                    $urls[ $id ] = $url->getScheme() . '://' . $domain . $path;
                                }

                            }

                        }
                    }

                }
            }
        }

        return $urls;
    }



    /**
     * gibt die Url eines Datensatzes zurück
     * wurde über rex_getUrl() aufgerufen
     */
    public static function rewrite($params = array())
    {
        global $REX;
        $id = $params['id'];
        $clang = $params['clang'];
        

        $urlparams = isset($params['params']) ? $params['params'] : '';
        $urlparams_parsed = parse_url($urlparams);

        parse_str(html_entity_decode($urlparams_parsed['path']), $urlparamsAsArray);

        if ((isset($urlparamsAsArray['ugid']) && (int)$urlparamsAsArray['ugid'] > 0) || (isset($urlparamsAsArray['ucid']) && (int)$urlparamsAsArray['ucid'] > 0)) {

            $ugid = isset($urlparamsAsArray['ugid']) ? (int)$urlparamsAsArray['ugid'] : (int)$urlparamsAsArray['ucid'];
            unset($urlparamsAsArray['ugid']);
            unset($urlparamsAsArray['ucid']);

            $urlparams = rex_param_string($urlparamsAsArray, '&amp;');
            $urlparams = $urlparams != '' ? '?' . substr($urlparams, 1, strlen($urlparams)) : '';
echo $ugid . ' :: ' . $id . ' :: ' . $clang;
            return self::getUrlById($ugid, $id, $clang) . $urlparams;
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
