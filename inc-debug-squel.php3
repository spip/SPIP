<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_DEBUG_SKEL")) return;
define("_INC_DEBUG_SKEL", "1");


function erreur_requete_boucle($query, $id_boucle) {
	$erreur = spip_sql_error();
	$errno = spip_sql_errno();
	if (eregi('err(no|code):?[[:space:]]*([0-9]+)', $erreur, $regs))
		$errsys = $regs[2];
	else if (($errno == 1030 OR $errno <= 1026) AND ereg('[^[:alnum:]]([0-9]+)[^[:alnum:]]', $erreur, $regs))
		$errsys = $regs[1];

	$erreur = htmlspecialchars($erreur);

	// Erreur systeme
	if ($errsys > 0 AND $errsys < 200) {
		$retour .= "<tt><br><br><blink>"._T('info_erreur_systeme', array('errsys'=>$errsys))."</blink><br>\n";
		$retour .= "<" ."?php
		if (\$GLOBALS['spip_admin']) {
			echo \""._T('info_erreur_systeme2').
				"<blink>"._T('info_erreur_systeme', array('errsys'=>$errsys))."</blink>\";
		}
		echo \"</tt>\n\";
		?".">";
	}
	// Requete erronee
	else {
		$retour .= "<tt><br><br><blink>&lt;BOUCLE".$id_boucle."&gt;</blink><br>\n".
			"<b>"._T('avis_erreur_mysql')."</b><br>\n".
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
