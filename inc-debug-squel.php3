<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_DEBUG_SKEL")) return;
define("_INC_DEBUG_SKEL", "1");


function erreur_requete_boucle($query, $id_boucle) {
	$retour .= "<tt><br><br><blink>&lt;BOUCLE".$id_boucle."&gt;</blink><br>\n".
		"<b>Erreur dans la requ&ecirc;te envoy&eacute;e &agrave; MySQL :</b><br>\n".
		htmlspecialchars($query)."<br>\n<font color=\'red\'><b>&gt; ".
		spip_sql_error()."</b></font><br>\n".
		"<blink>&lt;/BOUCLE".$id_boucle."&gt;</blink></tt>\n";
	$retour .= "<" ."?php
		if (\$GLOBALS['spip_admin']) {
		include_ecrire ('inc_lang.php3');
		utiliser_langue_visiteur();
		include_ecrire('inc_presentation.php3');
		echo aide('erreur_mysql');
	} ?".">";
	$retour .= "<br><br>\n"; // debugger les squelettes
	return $retour;
}

?>
