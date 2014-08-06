<?php

/**
 *
 * @author blumbeet - web.studio
 * @author Thomas Blum
 * @author http://www.blumbeet.com
 * @author mail[at]blumbeet[dot]com
 *
 */

$myself = 'url_control';
$addon  = $REX['ADDON'][$myself]['addon'];

$oid  = rex_request('oid', 'int');
$func = rex_request('func', 'string');
$echo = '';

rex_register_extension('REX_FORM_SAVED', 'url_generate::generatePathFile');

if (!function_exists('url_generate_column_article')) {

    function url_generate_column_article($params) {
        
        global $I18N;
        $list = $params['list'];

        $return = '';

        $a = OOArticle::getArticleById($list->getValue("article_id"), $list->getValue("clang"));
        if ($a instanceof OOArticle) {
            $return = $a->getName();
            $return .= ' [';
            $return .= '<a href="index.php?article_id=' . $list->getValue('article_id') . '&amp;clang=' . $list->getValue('clang') . '">Backend</a>';
            $return .= ' | ';
            $return .= '<a href="' . rex_getUrl($list->getValue('article_id'), $list->getValue('clang')) . '">Frontend</a>';
            $return .= ']';

            $tree = $a->getParentTree();

            $levels = array();
            foreach ($tree as $object) {
                $levels[] = $object->getName();
            }

            $return .= '<div class="url-control-path"><small><b>Pfad: </b>' . implode(' : ', $levels) . '</small></div>';

        }

        return $return;

    }

}

if (!function_exists('url_generate_column_data')) {

    function url_generate_column_data($params) {
        
        global $I18N;
        $list = $params['list'];

        $return = '';

        $params = unserialize($list->getValue('table_parameters'));

        $table = $list->getValue('table');

        $return .= '<dl class="url-control-dl">';
        $return .= '<dt>' . $I18N->msg('b_table') . ': </dt><dd><code>' . $table . '</code></dd>';
        $return .= '<dt>' . $I18N->msg('b_url') . ': </dt><dd><code>' . $params[ $table ][ $table . '_name'] . '</code></dd>';
        $return .= '<dt>' . $I18N->msg('b_id') . ': </dt><dd><code>' . $params[ $table ][ $table . '_id'] . '</code></dd>';
        

        $field      = $params[ $table ][ $table . '_restriction_field'];
        $operator   = $params[ $table ][ $table . '_restriction_operator'];
        $value      = $params[ $table ][ $table . '_restriction_value'];

        if ($field != '') {

            $return .= '<dt>' . $I18N->msg('b_url_control_generate_restriction') . ': </dt><dd><code>' . $field . $operator . $value . '</code></dd>';

        }

        $return .= '</dl>';

        return $return;

    }

}

if ($func == '') {

    $query = '  SELECT      `id`,
                            `article_id`,
                            `clang`,
                            `url`,
                            `table`,
                            `table_parameters`
                FROM        ' . $REX['TABLE_PREFIX'] . 'url_control_generate';

    $list = rex_list::factory($query, 30, 'url_control_generate');
//    $list->debug = true;
    $list->setNoRowsMessage($I18N->msg('b_no_results'));
    $list->setCaption($I18N->msg('b_tables'));
    $list->addTableAttribute('summary', $I18N->msg('b_tables'));

    $list->addTableColumnGroup(array(40, '*', 200, 153));

    $header = '<a class="rex-i-element rex-i-generic-add" href="' . $list->getUrl(array('func' => 'add')) . '"><span class="rex-i-element-text">' . $I18N->msg('b_add_entry', $I18N->msg('b_table')) . '</span></a>';
    $list->addColumn($header, '###id###', 0, array('<th class="rex-icon">###VALUE###</th>', '<td class="rex-small">###VALUE###</td>'));

    $list->removeColumn('id');
    $list->removeColumn('clang');
    $list->removeColumn('url');
    $list->removeColumn('table');
    $list->removeColumn('table_parameters');

    $list->setColumnLabel('article_id', $I18N->msg('b_article'));
    $list->setColumnFormat('article_id', 'custom', 'url_generate_column_article');

    $list->addColumn('data', '');
    $list->setColumnLabel('data', $I18N->msg('b_url_control_data'));
    $list->setColumnFormat('data', 'custom', 'url_generate_column_data');

    $list->addColumn($I18N->msg('b_function'), $I18N->msg('b_edit'));
    $list->setColumnParams($I18N->msg('b_function'), array('func' => 'edit', 'oid' => '###id###'));

    $echo = $list->get();

}


if ($func == 'add' || $func == 'edit') {

    $legend = $func == 'edit' ? $I18N->msg('b_edit') : $I18N->msg('b_add');

    $form = new rex_form($REX['TABLE_PREFIX'] . 'url_control_generate', $I18N->msg('b_table') . ' ' . $legend, 'id=' . $oid, 'post', false);
    //$form->debug = true;

    if ($func == 'edit') {
        $form->addParam('oid', $oid);
    }

    $field = & $form->addLinkmapField('article_id');
    $field->setLabel($I18N->msg('b_article'));


    if (count($REX['CLANG']) >= 2) {
        $field = & $form->addSelectField('clang');
        $field->setLabel($I18N->msg('b_language'));
        $field->setAttribute('style', 'width: 200px;');
        $select = & $field->getSelect();
        $select->setSize(1);

        foreach ($REX['CLANG'] as $key => $value) {
            $select->addOption($value, $key);
        }

    }


    $field = & $form->addSelectField('table');
    $field->setLabel($I18N->msg('b_table'));
    $field->setAttribute('onchange', 'url_generate_table(this);');
    $field->setAttribute('style', 'width: 200px;');
    $select = & $field->getSelect();
    $select->setSize(1);
    $select->addOption($I18N->msg('b_no_table_selected'), '');

    $fields = array();
    $tables = rex_sql::showTables();
    foreach ($tables as $table) {
        $select->addOption($table, $table);

        $columns = rex_sql::showColumns($table);
        foreach ($columns as $column) {
            $fields[$table][] = $column['name'];
        }
    }

    $table_id = $field->getAttribute('id');


    $fieldContainer = & $form->addContainerField('table_parameters');
    $fieldContainer->setAttribute('style', 'display: none');



    if (count($fields > 0)) {
        foreach ($fields as $table => $columns) {
            $group      = $table;
            $options    = $columns;

            $type       = 'select';
            $name       = $table . '_name';

            $f1 = & $fieldContainer->addGroupedField($group, $type, $name, $value, $attributes = array());
            $f1->setLabel($I18N->msg('b_url'));
            $f1->setAttribute('style', 'width: 200px;');
            $f1->setNotice($I18N->msg('b_url_control_generate_notice_name'));
            $select = & $f1->getSelect();
            $select->setSize(1);
            $select->addOptions($options, true);



            $type       = 'select';
            $name       = $table . '_id';

            $f2 = & $fieldContainer->addGroupedField($group, $type, $name, $value, $attributes = array());
            $f2->setLabel($I18N->msg('b_id'));
            $f2->setAttribute('style', 'width: 200px;');
            $f2->setNotice($I18N->msg('b_url_control_generate_notice_id'));
            $select = & $f2->getSelect();
            $select->setSize(1);
            $select->addOptions($options, true);

            
            $type       = 'select';
            $name       = $table . '_restriction_field';

            $f3 =& $fieldContainer->addGroupedField($group, $type, $name, $value, $attributes = array());
            $f3->setHeader('<div class="url-control-grid3col">');

            $f3->setLabel($I18N->msg('b_url_control_generate_restriction'));
            $f3->setAttribute('style', 'width: 200px;');
            $select =& $f3->getSelect();
            $select->setSize(1);
            $select->addOption('keine Einschränkung', '');
            $select->addOptions($options, true);



            $type       = 'select';
            $name       = $table . '_restriction_operator';

            $f4 =& $fieldContainer->addGroupedField($group, $type, $name, $value, $attributes = array());
            //$f4->setLabel();
            $f4->setAttribute('style', 'width: auto;');
            $select =& $f4->getSelect();
            $select->setSize(1);
            $select->addOptions(url_generate::getRestrictionOperators());


            $type       = 'text';
            $name       = $table . '_restriction_value';
            $value = '';

            $f5 =& $fieldContainer->addGroupedField($group, $type, $name, $value, $attributes = array());
            //$f5->setLabel();
            $f5->setFooter('<div class="rex-form-row"><p><span class="rex-form-notice">' . $I18N->msg('b_url_control_generate_notice_restriction') . '</span></p></div></div>');

        }
    }

    $echo = $form->get();

}

require_once $REX['INCLUDE_PATH'] . '/layout/top.php';
rex_title($addon . ' :: ' . $I18N->msg('b_url_control_generate_title'), $REX['ADDON']['pages'][$addon]);
echo $echo;
require_once $REX['INCLUDE_PATH'] . '/layout/bottom.php';

?>

<style type="text/css">

small {
    font-size: 95%;
}
.url-control-grid3col {
    clear: both;
}
.url-control-path {
    padding-top: 4px;
}
.url-control-dl dt {
    clear: left;
    float: left;
    margin-bottom: 4px;
    font-size: 95%;
    font-weight: 700;
}
.url-control-dl dd {
    margin-left: 45px;
    margin-bottom: 4px;
}
body .rex-form .url-control-grid3col .rex-form-row {
    clear: none;
    float: left;
    width: auto;
    margin-right: 20px;
}
body .rex-form .url-control-grid3col .rex-form-row:last-child {
}
body .rex-form .url-control-grid3col .rex-form-row p {
    float: none;
    width: auto;
}
body .rex-form .url-control-grid3col .rex-form-row .rex-form-text input {
    width: 200px;
}

</style>

<?php
if ($func == 'add' || $func == 'edit') {
?>
    <script type="text/javascript">

        jQuery(document).ready(function($) {

            var $currentShown = null;
            $("#<?php echo $table_id; ?>").change(function() {
                if($currentShown) {
                    $currentShown.hide();
                }

                var $table_id = "#rex-"+ jQuery(this).val();
                $currentShown = $($table_id);
                $currentShown.show();
            }).change();
        });

    </script>
<?php
}
?>

