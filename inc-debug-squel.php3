<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_DEBUG_SKEL")) return;
define("_INC_DEBUG_SKEL", "1");


function erreur_requete_boucle($query, $id_boucle) {
	$erreur = spip_sql_error();
	if (ereg('errno:[[:space:]]+([0-9]+)', $erreur, $regs))
		$errno = $regs[1];
	$erreur = htmlspecialchars($erreur);

	// Erreur systeme
	if ($errno > 0) {
		$retour .= "<tt><br><br><blink>Erreur syst&egrave;me</blink><br>\n".
			"<b>Le disque dur est peut-&ecirc;tre plein, ou la base de donn&eacute;es endommag&eacute;e. <br>";
		$retour .= "<" ."?php
		if (\$GLOBALS['spip_admin']) {
			echo \"<font color='red'>Essayez de <a href='ecrire/admin_repair.php3'>r&eacute;parer la base</a>, \"
				.\"ou contactez votre h&eacute;bergeur.</font><br></b>".
				"<blink>Erreur syst&egrave;me</blink></tt>\n\";
		} ?".">";
	}
	// Requete erronee
	else {
		$retour .= "<tt><br><br><blink>&lt;BOUCLE".$id_boucle."&gt;</blink><br>\n".
			"<b>Erreur dans la requ&ecirc;te envoy&eacute;e &agrave; MySQL :</b><br>\n".
			htmlspecialchars($query)."<br><font color='red'><b>$erreur</b></font><br>".
			"<blink>&lt;/BOUCLE".$id_boucle."&gt;</blink></tt>\n";
		$retour .= "<" ."?php
		if (\$GLOBALS['spip_admin']) {
			include_ecrire ('inc_lang.php3');
			utiliser_langue_visiteur();
			include_ecrire('inc_presentation.php3');
			echo aide('erreur_mysql');
		} ?".">";
	}
	$retour .= "<br><br>\n"; // debugger les squelettes
	return $retour;
}

?>
