<?php

/**
 *
 * @author blumbeet - web.studio
 * @author Thomas Blum
 * @author mail[at]blumbeet[dot]com
 *
 */

class UrlManager extends UrlControl
{
    public static function control()
    {
        global $REX;
        $myself   = 'url_control';
        $addon    = $REX['ADDON'][$myself]['addon'];
        $rewriter = $REX['ADDON'][$myself]['rewriter'];

        // http://www.domain.de/kategorie/artikel.html
        $url_full = parent::getFullRequestedUrl();

        $parse = parse_url($url_full);
        $url_path  = $parse['path'];

        $sql = rex_sql::factory();
        //$sql->debugsql = true;
        $sql->setQuery('SELECT  *
                        FROM    ' . $REX['TABLE_PREFIX'] . 'url_control_manager
                        WHERE   status = "1"
                            AND (
                                url = "' . mysql_real_escape_string($url_full) . '"
                                OR
                                url = "' . mysql_real_escape_string($url_path) . '"
                            )
                    ');
        if ($sql->getRows() == 1) {
            $method = $sql->getValue('method');
            $params = unserialize($sql->getValue('method_parameters'));
            switch ($method) {

                case 'article':

                    $article_id = (int) $params['article']['article_id'];
                    $clang      = (int) $params['article']['clang'];

                    if ($params['article']['action'] == 'view') {
                        return array(
                            'article_id' => $article_id,
                            'clang'      => $clang,
                        );
                    } elseif ($params['article']['action'] == 'redirect') {

                        $a = OOArticle::getArticleById((int) $params['article']['article_id'], (int) $params['article']['clang']);
                        if ($a instanceof OOArticle) {

                            if (isset($rewriter[$addon]['get_url'])) {
                                $func = $rewriter[$addon]['get_url'];
                                $url = call_user_func($func, $article_id, $clang);
                            } else {
                                $url = $a->getUrl();
                            }
                        }
                        //$url = rex_getUrl((int) $params['article']['article_id'], (int) $params['article']['clang']);
                        self::redirect($a->getUrl(), $params['http_type']['code']);
                    }
                    break;

                case 'target_url':
                    $url = $params['target_url']['url'];
                    self::redirect($url, $params['http_type']['code']);
                    break;
            }
        }
        return false;
    }


    public static function redirect($url, $code)
    {
        global $REX;
        header('Location: ' . trim($url), true, $code);
        header('Content-Type: text/html');
        echo '
<!DOCTYPE html>
<html>
    <head>
        <title>' . $REX['SERVERNAME'] . '</title>
        <meta charset="utf-8" />
    </head>
    <body>
        <p style="display: block; font-size:14px; text-align: left;">This page has moved to <a href="' . trim($url) . '">' . str_replace('&', '&amp;', trim($url)) . '</a>.</p>
    </body>
</html>
';
        exit();
    }
}
