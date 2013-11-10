<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/msiecompat?lang_cible=ru
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// C
	'choix_explication' => '<p>Эта опция позволяет улучшить совместимость вашего сайта с Internet Explorer. </p>
<ul>
<li><a href=\'http://jquery.khurshid.com/ifixpng.php\'>iFixPng</a> (<b>default setting</b>) добавить прозрачность PNG файлов для MSIE 5 and 6. </li>
<li><a href=\'http://code.google.com/p/ie7-js/\'>IE7.js</a> исправляет PNG прозрачность and добавляет поддержку CSS2 селекторов для MSIE 5 и 6 (<a href=\'http://ie7-js.googlecode.com/svn/test/index.html\'>полный список селекторов для IE7.js и IE8.js</a>).</li>
<li>IE8.js улучшает IE7.js  CSS селекторами с MSIE 5 по 7.</li>
<li>IE7-squish устраняет три ошибки в  MSIE 6 (включая задвоение margin у floating элементов), но может проявляться side effects. </li>
</ul>',
	'choix_ie7' => '<a href=\'http://code.google.com/p/ie7-js/\'>IE7.js</a> исправляет проблему с прозрачностью png файлов и добавляет селекторы CCS2 для ИЕ 5 и ИЕ6  (<a href=\'http://ie7-js.googlecode.com/svn/test/index.html\'> вы можете посмотреть список доступных селекторов</a>).',
	'choix_ie7squish' => 'IE7-squish исправляет три основных бага ИЕ 6 (включая задваивание отступов у floating элементов), но может проявляться side effect.',
	'choix_ie8' => 'IE8.js дополняет IE7.js расширяя возможности использовать селекторы CSS  IE 5 до  IE 7.',
	'choix_ifixpng' => 'По умолчанию, <a href=\'http://jquery.khurshid.com/ifixpng.php\'>iFixPng</a> подключает прозрачность png файлов в броузерах  MSIE 5 и 6.',
	'choix_non' => 'Отключить: не добавлять iepngfix на сайт',
	'choix_titre' => 'Совместимость с Internet Explorer (iepngfix)'
);

?>
