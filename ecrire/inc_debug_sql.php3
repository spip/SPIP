<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_DEBUG_SQL")) return;
define("_INC_DEBUG_SQL", "1");

// Si le code php produit des erreurs, on les affiche en surimpression
// sauf pour un visiteur non admin (lui ne voit rien de special)
// ajouter &var_debug=oui pour voir les erreurs et en parler sur spip@rezo.net
function affiche_erreurs_page($tableau_des_erreurs) {
	include_ecrire('inc_presentation.php3');

	echo "<div id='spip-debug' style='position: absolute; top: 20;",
	" z-index: 1000;'><ul><li>",
	_L("Erreur(s) dans le squelette"),

## aide locale courte a ecrire, avec lien vers une grosse page de documentation
#		aide('erreur_compilation'),

	"<br /></li>",
	"<ul>";
	foreach ($tableau_des_erreurs as $err) {
		echo "<li>".$err[0],
		", <small>".$err[1]."</small><br />",
		"</li>\n";
	}
	echo "</ul>";
	echo "</ul></div>";
	$GLOBALS['bouton_admin_debug'] = true;
}

//
// Si une boucle cree des soucis, on peut afficher la requete fautive
// avec son code d'erreur
//
function erreur_requete_boucle($query, $id_boucle, $type) {

	include_ecrire("inc_presentation.php3");

	// Calmer le jeu avec MySQL (si jamais on est en saturation)
	@touch(_FILE_MYSQL_OUT);	// pour spip_cron
	@touch(_FILE_LOCK);		// lock hebergeur
	spip_log('Erreur MySQL: on limite les acces quelques minutes');
	$GLOBALS['bouton_admin_debug'] = true;

	$erreur = spip_sql_error();
	$errno = spip_sql_errno();
	if (eregi('err(no|code):?[[:space:]]*([0-9]+)', $erreur, $regs))
		$errsys = $regs[2];
	else if (($errno == 1030 OR $errno <= 1026)
	AND ereg('[^[:alnum:]]([0-9]+)[^[:alnum:]]', $erreur, $regs))
		$errsys = $regs[1];

	// Erreur systeme
	if ($errsys > 0 AND $errsys < 200) {
		$retour .= "<tt><br><br><blink>"
		. _T('info_erreur_systeme', array('errsys'=>$errsys))
		. "</blink><br>\n"
		. _T('info_erreur_systeme2');
		spip_log("Erreur systeme $errsys");
	}
	// Requete erronee
	else {
		$retour .= "<tt><blink>&lt;BOUCLE".$id_boucle."&gt;("
		. $type . ")</blink><br>\n"
		. "<b>"._T('avis_erreur_mysql')."</b><br>\n"
		. htmlspecialchars($query)
		. "<br><font color='red'><b>".htmlspecialchars($erreur)
		. "</b></font><br>"
		. "<blink>&lt;/BOUCLE".$id_boucle."&gt;</blink></tt>\n";

		include_ecrire('inc_lang.php3');
		utiliser_langue_visiteur();
		$retour .= aide('erreur_mysql');
		spip_log("Erreur MySQL BOUCLE$id_boucle (".$GLOBALS['fond'].".html)");
	}

	erreur_squelette($retour);
}


//
// Erreur de syntaxe des squelettes : memoriser le code fautif
//
function erreur_squelette($message='', $lieu='') {
	global $tableau_des_erreurs;
	global $auteur_session;
	static $runs;

	if (is_array($message)) list($message, $lieu) = $message;

	spip_log("Erreur squelette: $message | $lieu ("
		. $GLOBALS['fond'].".html)");
	$GLOBALS['bouton_admin_debug'] = true;
	$tableau_des_erreurs[] = array($message, $lieu);
	// Eviter les boucles infernales
	if (++$runs > 4) {
		if ($HTTP_COOKIE_VARS['spip_admin'] OR
		$auteur_session['statut'] == '0minirezo' OR
		$GLOBALS['var_debug']) {
			die(affiche_erreurs_page($tableau_des_erreurs));
		}
	}
}

//
// Le debugueur v2
//

// appelee a chaque sortie de boucle (inc-compilo)
function boucle_debug_resultat ($nom, $resultat) {
	global $debug_objets;

	// ne pas memoriser plus de 3 tours d'une meme boucle
	if (count($debug_objets['resultats'][$nom]) < 3)
		$debug_objets['resultats'][$nom][] = $resultat;
}

// appelee a chaque compilation de boucle (inc-compilo)
function boucle_debug_compile ($id, $nom, $pretty, $sourcefile, $code) {
	global $debug_objets;

	$debug_objets['code'][$nom.$id] = $code;
	$debug_objets['pretty'][$nom.$id] = $pretty;
}

// appelee a chaque compilation de squelette (inc-compilo)
function squelette_debug_compile($nom, $sourcefile, $squelette) {
	global $debug_objets;

	$debug_objets['squelettes'][$nom] = $squelette;
	$debug_objets['sourcefile'][$nom] = $sourcefile;
}

// appelee a chaque parsing de squelette (inc-parser)
function boucle_debug ($id, $nom, $boucle) {
	global $debug_objets;

	$debug_objets['boucle'][$nom.$id] = $boucle;
}

// l'environnement graphique du debuggueur 
function debug_dumpfile ($texte) {

	global $debug_objets, $debug_objet, $debug_affiche;
	if (!headers_sent())
	  header("Content-Type: text/html; charset=".lire_meta('charset'));
	if (!$GLOBALS['debug_objets']['sourcefile']) return;
	spip_setcookie('spip_debug', 'oui', time()+12*3600);
        $page = "<html><head><title>Debug</title></head>\n<body>";
        echo calcul_admin_page('', $page),
	  "<div id='spip-debug' style='position: absolute; top: 20; z-index: 1000;'><ul>\n"; 

	foreach ($debug_objets['sourcefile'] as $nom_skel => $sourcefile) {
		echo "<li><b>".$sourcefile."</b>";
		$link = new Link;
		$link->addvar('debug_objet', $nom_skel);
		$link->delvar('debug_affiche');
		echo " <a href='".$link->getUrl()."&debug_affiche=resultat'>resultat</a>";
		echo " <a href='".$link->getUrl()."&debug_affiche=code'>code</a>";
		echo "<ul>\n";

		if (is_array($debug_objets['pretty']))
		foreach ($debug_objets['pretty'] as $nom => $pretty)
			if (substr($nom, 0, strlen($nom_skel)) == $nom_skel) {
				echo "<li>";
				$aff = "&lt;".$pretty."&gt;";
				if ($debug_objet == $nom)
					$aff = "<b>$aff</b>";
				echo $aff;
				$link = new Link;
				$link->addvar('debug_objet', $nom);
				$link->delvar('debug_affiche');
				echo " <a href='".$link->getUrl()."&debug_affiche=boucle' class='debug_link_boucle'>boucle</a>";
				echo " <a href='".$link->getUrl()."&debug_affiche=resultat' class='debug_link_resultat'>resultat</a>";
				echo " <a href='".$link->getUrl()."&debug_affiche=code' class='debug_link_code'>code</a>";
				echo "</li>\n";
			}
		echo "</ul>\n</li>\n";
	}
    echo "</ul>\n";

	if ($debug_objet AND $debug_affiche == 'resultat' AND ($res = $debug_objets['resultats'][$debug_objet])) {
		echo "<div id=\"debug_boucle\"><fieldset><legend>".$debug_objets['pretty'][$debug_objet]."</legend>";
		echo "<p class='spip-admin-bloc'>les premiers appels &agrave; cette boucle ont donn&eacute;&nbsp;:</p>";
		foreach ($res as $view)
			echo "<ul><fieldset>".interdire_scripts($view)."</fieldset></ul>";
		echo "</fieldset></div>";

	} else if ($debug_objet AND $debug_affiche == 'code' AND $res = $debug_objets['code'][$debug_objet]) {
		echo "<div id=\"debug_boucle\"><fieldset><legend>".$debug_objets['pretty'][$debug_objet]."</legend>";
		highlight_string("<"."?php\n".$res."\n?".">");
		echo "</fieldset></div>";
	} else if ($debug_objet AND $debug_affiche == 'boucle' AND $res = $debug_objets['boucle'][$debug_objet]) {
		echo "<div id=\"debug_boucle\"><fieldset><legend>".$debug_objets['pretty'][$debug_objet]."</legend>";
		highlight_string($res);
		echo "</fieldset></div>";
	}

	if ($texte) {
	  echo "<div id=\"debug_boucle\"><fieldset><legend>".$GLOBALS['debug_affiche']."</legend>";
	  ob_start();
	  highlight_string($texte);
	  $s = ob_get_contents();
	  ob_end_clean();
	  if (substr($s,0,6) == '<code>') { $s=substr($s,6); echo '<code>';}
	  $tableau = explode("<br />", $s);
	  $format = "<br />\n<span style='color: black'>%0".
	    strlen(count($tableau)).
	    "d </span>";
	  $format10=str_replace('black','pink',$format);
	  $i=1;
	  foreach ($tableau as $ligne)
	    echo sprintf(($i%10) ? $format :$format10, $i++), $ligne ;


	  echo "</fieldset></div>";
	}
	echo "\n</div></body>";
	if ($texte) exit;
}

?>
