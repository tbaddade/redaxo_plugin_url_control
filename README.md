**aktuelle Version noch nicht getestet mit**

* rexseo
* rexseo42


redaxo_plugin_url_control
================================================================================

- url_generate: zur URL-Generierung für eigene AddOns (ehemals Frau Schultze)
- url_manager: eigene Urls verwalten, zum Bspl. Urls für Landingpages, Weiterleitungen


url_generate - Beispiel: News AddOn
--------------------------------------------------------------------------------
Normlerweise wird eine News über eine Url wie **/news.html?news_id=1** geholt

Mit dem Plugin ist es möglich Urls wie **/news/news-title.html** zu erzeugen

Der Artikel **/news-title.html** selbst existiert dabei nicht. Es wird alles im Artikel **/news.html** abgehandelt

Um an die tatsächliche Id der einzelnen News zu kommen, wird folgende Methode verwendet:
```
$news_id = url_generate::getId('news_table');
```

Die Url holt man sich mit folgender Methode:
```
$news_url = url_generate::getUrlById('news_table', $news_id);
```

### Beispiel Code

```php
<?php
$id = url_generate::getId('news_table');

if ($id > 0) {

    $datas = rex_sql::factory()->getArray('SELECT * FROM news_table WHERE id = "' . mysql_real_escape_string($id). '"');

    if (count($datas) > 0) {

        $data = current($datas);
		
		echo $data['title'];

	}
	
} else {

    $datas = rex_sql::factory()->getArray('SELECT * FROM news_table');

    if (count($datas) > 0) {
	
    	foreach ($datas as $data) {
	
			echo '<a href="' . url_generate::getUrlById('news_table', $data['id']) . '">' . $data['title'] . '</a>';
			
		}
		
	}
	
}
?>
```


Installation
--------------------------------------------------------------------------------
* Plugin in den plugin-Ordner des Rewriters laden
* Ordner **redaxo_plugin_url_control** in **url_control** umbenennen
* Plugin installieren und aktivieren


unterstützte Rewriter
--------------------------------------------------------------------------------
* [yrewriter](https://github.com/dergel/redaxo4_yrewrite) von dergel (Jan Kristinus)
* [rexseo](https://github.com/gn2netwerk/rexseo) von GN2 Netwerk und jdlx
* [seo42](https://github.com/RexDude/seo42) von RexDude
