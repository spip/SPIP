<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_DEBUG_SKEL")) return;
define("_INC_DEBUG_SKEL", "1");
include_ecrire("inc_presentation.php3");

function erreur_requete_boucle($query, $id_boucle, $type) {
	$GLOBALS["delais"]=0;
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
		if ($GLOBALS['spip_admin'])
		$retour .= 
			_T('info_erreur_systeme2').
				"<blink>"._T('info_erreur_systeme', array('errsys'=>$errsys))."</blink>";
	}
	// Requete erronee
	else {
		$retour .= "<tt><blink>&lt;BOUCLE".
		  $id_boucle.
		  "&gt;(".
		  $type .
		  ")</blink><br>\n".
		"<b>"._T('avis_erreur_mysql')."</b><br>\n".
		  htmlspecialchars($query)."<br><font color='red'><b>$erreur</b></font><br>".
		  "<blink>&lt;/BOUCLE".$id_boucle."&gt;</blink></tt>\n";
		if ($GLOBALS['spip_admin']) {
		  include_ecrire('inc_lang.php3');
		  utiliser_langue_visiteur();
		  $retour .= aide('erreur_mysql');
		}
	}
	return "<div style='position: fixed; top: 10px; left: 10px; z-index: 10000'>$retour</div>";
}

function erreur_squelette($message, $fautif, $lieu)
{
  install_debut_html($message);
  if ($fautif)
    echo ' (<FONT color="#FF000">' . entites_html($fautif) . '</FONT>)';
  echo '<br /><FONT color="#FF000">' . $lieu . '</FONT>.'; 
  install_fin_html();
  exit;
}
?>
