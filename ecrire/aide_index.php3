<?php

include ("inc_version.php3");

$lastmodified = filemtime("aide_index.php3");
$headers_only = http_last_modified($lastmodified, time() + 24 * 3600);
if ($headers_only) exit;

// Recuperer les infos de langue (preferences auteur), si possible
if (file_exists("inc_connect.php3")) {
	include_ecrire ("inc_auth.php3");
}

include_ecrire("inc_lang.php3");
utiliser_langue_visiteur();
if ($var_lang) changer_langue($var_lang);

echo "<HTML>";
echo "<HEAD>";
echo "<TITLE dir=\"".($spip_lang_rtl ? 'rtl' : 'ltr')."\">"._T('info_aide_en_ligne')."</TITLE>";
echo "</HEAD>";

$frame_menu = "<frame src=\"aide_gauche.php3?aide=$aide&les_rub=$les_rub&var_lang=$spip_lang#$ancre\" name=\"gauche\" scrolling=\"auto\" noresize>\n";
$frame_body = "<frame src=\"aide_droite.php3?aide=$aide&var_lang=$spip_lang\" name=\"droite\" scrolling=\"auto\" noresize>\n";

if ($spip_lang_rtl) {
	echo '<frameset cols="*,160" border="0" frameborder="0" framespacing="0">';
	echo $frame_body.$frame_menu;
}
else {
	echo '<frameset cols="160,*" border="0" frameborder="0" framespacing="0">';
	echo $frame_menu.$frame_body;
}
echo '</frameset>';

echo "</HTML>";

?>

