<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_DEBUG_SQL")) return;
define("_INC_DEBUG_SQL", "1");

// Si le code php produit des erreurs, on les affiche en surimpression
// sauf pour un visiteur non admin (lui ne voit rien de special)
// ajouter &var_mode=debug pour voir les erreurs et en parler sur spip@rezo.net
function affiche_erreurs_page($tableau_des_erreurs) {

	echo "<div id='spip-debug' style='position: absolute; top: 20;",
	" z-index: 1000;'><ul><li>",
	_T('zbug_erreur_squelette'),

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

	// Calmer le jeu avec MySQL (si jamais on est en saturation)
	@touch(_FILE_MYSQL_OUT);	// pour spip_cron
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

		include_ecrire('inc_presentation.php3');
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
		if ($_COOKIE['spip_admin'] OR
		$auteur_session['statut'] == '0minirezo' OR
		    ($GLOBALS['var_mode'] == 'debug')) {
			echo debut_entete('Debug'), '</head><body>';
			die(affiche_erreurs_page($tableau_des_erreurs));
		}
	}
}

//
// Le debugueur v2
//

// appelee a chaque sortie de boucle (inc-compilo) et a chaque requete
// dans ce derniers cas on n'a pas le nom du squelette
// alors on memorise dans une statique et lors de l'autre appel qui suit
// on finalise
function boucle_debug_resultat ($id, $nom, $resultat) {
	global $debug_objets;
	static $requete = array();
	if (!$nom) {$requete[$id] = $resultat; return;}
	if ($requete[$id]) {
		$debug_objets['requete']["$nom$id"] = $requete[$id];
		$requete[$id] = '';
	}
	// ne pas memoriser plus de 3 tours d'une meme boucle
	if (count($debug_objets['resultat']["$nom$id"]) < 3)
		$debug_objets['resultat']["$nom$id"][] = $resultat;
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

// appelee a chaque analyse syntaxique de squelette (inc-parser)
function boucle_debug ($id, $nom, $boucle) {
	global $debug_objets;

	$debug_objets['boucle'][$nom.$id] = $boucle;
}

// l'environnement graphique du debuggueur 
function debug_dumpfile ($texte, $fonc, $type) {

	global $debug_objets, $var_mode_objet, $var_mode_affiche;
	$debug_objets[$type][$fonc . 'tout'] = $texte;
	if (!$debug_objets['sourcefile']) return;
	if ($texte && ($var_mode_objet != $fonc || $var_mode_affiche != $type))
	    return;
	spip_setcookie('spip_debug', 'oui', time()+12*3600);

	$link = new Link;
	$link->delvar('var_mode_affiche');
	$link->delvar('var_mode_objet');
	$link->addvar('var_mode','debug');
	$self = $link->getUrl();

// en cas de squelette inclus,  virer le code de l'incluant:
// - il contient souvent une Div restreignant la largeur a 3 fois rien
// - ca fait 2 headers !
	ob_end_clean();

	echo debut_entete(_T('info_debug')), 
	  "<link rel='stylesheet' href='spip_admin.css' type='text/css' />",
	  "</head>\n<body>",
	  "<div id='spip-debug' style='position: absolute; top: 20; z-index: 1000;'><ul>\n"; 

	foreach ($debug_objets['sourcefile'] as $nom_skel => $sourcefile) {
		echo "<li><b>",$sourcefile,"</b>";
		echo " <a href='",$self, "&var_mode_objet=$nom_skel&var_mode_affiche=resultat'>"._T('zbug_resultat')."</a>";
		echo " <a href='", $self, "&var_mode_objet=$nom_skel&var_mode_affiche=code'>"._T('zbug_code')."</a>";
		echo "<table width='100%'>\n";
		$i = 0;
		$colors = array('#c0c0c0', '#c0cad4');
		if (is_array($debug_objets['pretty']))
		foreach ($debug_objets['pretty'] as $nom => $pretty)
			if (substr($nom, 0, strlen($nom_skel)) == $nom_skel) {
				$i++;
				$aff = "&lt;".$pretty."&gt;";
				if ($var_mode_objet == $nom)
					$aff = "<b>$aff</b>";
				echo "<tr bgcolor='" . $colors[$i%2] . "'><td  align='right'>$i</td><td><a href='",$self,"&var_mode_objet=$nom&var_mode_affiche=boucle' class='debug_link_boucle'>"._T('zbug_boucle')."</a></td><td><a href='",$self, "&var_mode_objet=$nom&var_mode_affiche=resultat' class='debug_link_resultat'>"._T('zbug_resultat')."</a></td><td><a href='", $self, "&var_mode_objet=$nom&var_mode_affiche=code' class='debug_link_code'>"._T('zbug_code')."</a></td><td>$aff</td></tr>";
			}
		echo "</table>\n</li>\n";
	}
	echo "</ul>\n";
	if ($var_mode_objet && ($res = $debug_objets[$var_mode_affiche][$var_mode_objet])) {
	  if ($var_mode_affiche == 'resultat') {
		echo "<div id=\"debug_boucle\"><fieldset><legend>",$debug_objets['pretty'][$var_mode_objet],"</legend>";
		highlight_string($debug_objets['requete'][$var_mode_objet]);
#		echo "<p class='spip-admin-bloc'>les premiers appels &agrave; cette boucle ont donn&eacute;&nbsp;:</p>";
		foreach ($res as $view) 
			if ($res) echo "<br><fieldset>",interdire_scripts($view),"</fieldset>";
		echo "</fieldset></div>";

      } else if ($var_mode_affiche == 'code') {
		echo "<div id=\"debug_boucle\"><fieldset><legend>",$debug_objets['pretty'][$var_mode_objet],"</legend>";
		highlight_string("<"."?php\n".$res."\n?".">");
		echo "</fieldset></div>";
	} else if ($var_mode_affiche == 'boucle') {
		echo "<div id=\"debug_boucle\"><fieldset><legend>",$debug_objets['pretty'][$var_mode_objet],"</legend>";
		highlight_string($res);
		echo "</fieldset></div>";
	  }
    }

	if ($texte) {
	  echo "<div id=\"debug_boucle\"><fieldset><legend>",$GLOBALS['var_mode_affiche'],"</legend>";
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
	echo "\n</div>";
	echo inclure_balise_dynamique(balise_formulaire_admin_dyn('','','','','','',
		$debug_objets));
	echo '</body></html>';
	exit;
}

?>
