<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_ADMIN")) return;
define("_INC_ADMIN", "1");


$debug_messages = '';


//
// Afficher un bouton admin
//
function bouton_admin($titre, $lien) {
	return "<li><a href='$lien' class='spip-admin-boutons'>$titre</a></li>\n";
}


function afficher_boutons_admin($pop='', $forcer_debug = false /* cas ou l'eval() plante dans inc-public */) {
	global $id_article, $id_breve, $id_rubrique, $id_mot, $id_auteur;
	global $var_preview;
	include_ecrire("inc_filtres.php3");
	include_ecrire("inc_lang.php3");

	// regler les boutons dans la langue de l'admin (sinon tant pis)
	if ($login = addslashes(ereg_replace('^@','',$GLOBALS['spip_admin']))) {
		$q = spip_query("SELECT lang FROM spip_auteurs WHERE login='$login'");
		$row = spip_fetch_array($q);
		$lang = $row['lang'];
		lang_select($lang);
	}

	$ret = '<div class="spip-admin-bloc" dir="'.lang_dir($lang,'ltr','rtl').'">
	<div class="spip-admin">
	<ul>';

	// Bouton modifier
	if ($id_article) {
		$ret .= bouton_admin(_T('admin_modifier_article')." ($id_article)", "./ecrire/articles.php3?id_article=$id_article");
	}
	else if ($id_breve) {
		$ret .= bouton_admin(_T('admin_modifier_breve')." ($id_breve)", "./ecrire/breves_voir.php3?id_breve=$id_breve");
	}
	else if ($id_rubrique) {
		$ret .= bouton_admin(_T('admin_modifier_rubrique')." ($id_rubrique)", "./ecrire/naviguer.php3?coll=$id_rubrique");
	}
	else if ($id_mot) {
		$ret .= bouton_admin(_T('admin_modifier_mot')." ($id_mot)", "./ecrire/mots_edit.php3?id_mot=$id_mot");
	}
	else if ($id_auteur) {
		$ret .= bouton_admin(_T('admin_modifier_auteur')." ($id_auteur)", "./ecrire/auteurs_edit.php3?id_auteur=$id_auteur");
	}

	// Si on est en preview rien d'autre ne fonctionne
	if (!$var_preview) {

		// Bouton Recalculer
		$link = $GLOBALS['clean_link'];
		$link->addVar('recalcul', 'oui');
		$link->delVar('var_debug');
		$link->delVar('debug_objet');
		$link->delVar('debug_affiche');
		$lien = $link->getUrl();
		$ret .= bouton_admin(_T('admin_recalculer').$pop, $lien);

		// Bouton statistiques
		if (lire_meta("activer_statistiques") != "non" AND $id_article
		AND ($GLOBALS['auteur_session']['statut'] == '0minirezo')) {
			if (spip_fetch_array(spip_query("SELECT id_article
			FROM spip_articles WHERE statut='publie'
			AND id_article =".intval($id_article)))) {
				include_local ("inc-stats.php3");
				$ret .= bouton_admin(_T('stats_visites_et_popularite',
				afficher_raccourci_stats($id_article)),
				"./ecrire/statistiques_visites.php3?id_article=$id_article");
			}
		}

		// Bouton de debug
		if ($forcer_debug
		OR $GLOBALS['bouton_admin_debug']
		OR (!$GLOBALS['var_debug']
		AND $GLOBALS['HTTP_COOKIE_VARS']['spip_debug'])) {
			$link = $GLOBALS['clean_link'];
			if ($GLOBALS['code_activation_debug'])
				$code_activation = $GLOBALS['code_activation_debug'];
			else if ($GLOBALS['auteur_session']['statut'] == '0minirezo')
				$code_activation = 'oui';
			if ($code_activation) {
				$link->addvar('var_debug', $code_activation);
				$ret .= bouton_admin(_L('Debug'), $link->getUrl());
			}
		}

		// Messages de debug
		global $debug_messages;
		$ret .= $debug_messages;
	}

	$ret .= "</ul></div></div>\n";

	lang_dselect();

	return $ret;
}

function calcul_admin_page($cached, $texte) {

	$a = afficher_boutons_admin($cached ? ' *' : '');

	// Inserer la feuille de style selon les normes, dans le <head>
	// Feuilles de style admin : d'abord la CSS officielle, puis la perso,
	// puis celle du squelette (.spip-admin, cf. impression.css)
	$css = "<link rel='stylesheet' href='spip_admin.css' type='text/css' />\n";
	if (@file_exists('spip_admin_perso.css'))
		$css .= "<link rel='stylesheet' href='spip_admin_perso.css' type='text/css' />\n";
	if (eregi('<(/head|body)', $texte, $regs)) {
		$texte = explode($regs[0], $texte, 2);
		$texte = $texte[0].$css.$regs[0].$texte[1];
	} else
		$texte .= $css;

	// Inserer les boutons admin dans la page
	// La constante doit etre definie a l'identique dans inc-form-squel
	// balise #FORMULAIRE_ADMIN ? sinon ajouter en fin de page
	if (!(strpos($texte, '<!-- @@formulaire_admin@@45609871@@ -->') === false))
		$texte = str_replace('<!-- @@formulaire_admin@@45609871@@ -->', $a, $texte);
	else {
		$a = '<div class="spip-admin-float">'.$a."</div>\n";
		if (eregi('</(body|html)>', $texte, $regs)){
			$texte = explode($regs[0], $texte, 2);
			$texte = $texte[0].$a.$regs[0].$texte[1];
		} else
			$texte .= $a;
	}

	return $texte;
}


//
// Leve un drapeau si le squelette donne une page generant de graves erreurs php
//
function spip_error_handler ($errno, $errmsg, $filename, $linenum, $vars) {
	global $tableau_des_erreurs, $page;

	// On ne veut intercepter que les erreurs des $page['texte'], pas
	// celles qui peuvent se trouver dans SPIP: $filename = inc-public + eval
	if (($errno & (E_ERROR | E_WARNING | E_PARSE))
	AND strpos($filename, 'inc-public.php3(')) {
		$tableau = explode("\n", $page['texte']);
		$format = "%0".strlen(count($tableau))."d";
		for($i=max(1,$linenum-3); $i<=min(count($tableau),$linenum+3); $i++) {
			$l = propre("<code>".sprintf($format, $i).'. '.$tableau[$i-1]."</code>");
			if ($i == $linenum) $l = "<b><font color='red'>$l</font></b>";
			$contexte .= "<br />".$l;
		}

		$tableau_des_erreurs[]
		= array($errno, $errmsg, $linenum, $page['squelette'], $contexte);
	}
}

//
// Si le code php produit des erreurs, on peut les afficher
//
function affiche_erreurs_execution_page() {
	global $tableau_des_erreurs, $page_principale, $s;
	echo "<div style='position: absolute; z-index: 1000;
	background-color: pink;'><h2>".
	_L("Erreur lors de l'ex&eacute;cution du squelette")."</h2>";
	echo "<p>"._L("php a rencontr&eacute; les
	erreurs suivantes :")."<code><ul>";
	foreach ($tableau_des_erreurs as $err) {
		$fichier_inclus = ($err[3] <> $page_principale['squelette'])
		? ", fichier inclus $err[3].html" : '';
		echo "<li>ligne $err[2]$fichier_inclus: $err[1]
		($err[0])";
		echo "<small>$err[4]</small>";
		echo "</li>\n";
	}
	if ($s === false)
		echo "<li>Erreur de compilation</li>\n";
	echo "</ul></code></div>";
	$GLOBALS['bouton_admin_debug'] = true;
}

//
// Si une boucle cree des soucis, on peut afficher la requete fautive
// avec son code d'erreur
//
function erreur_requete_boucle($query, $id_boucle, $type) {
	global $auteur_session, $HTTP_COOKIE_VARS, $dir_ecrire;
	include_ecrire("inc_presentation.php3");

	// Drapeau pour interdire d'ecrire les fichiers dans le cache
	define('spip_erreur_fatale', 'requete_boucle');

	// Calmer le jeu avec MySQL (si jamais on est en saturation)
	@touch($dir_ecrire.'data/mysql_out');	// pour spip_cron
	@touch($dir_ecrire.'data/lock');		// lock hebergeur
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

	// Pour un visiteur normal, afficher juste le fait qu'il y a une erreur
	// ajouter &afficher_erreurs=1 pour discuter sur spip@rezo.net
	if (!$HTTP_COOKIE_VARS['spip_admin'] AND !$auteur_session
	AND !$GLOBALS['afficher_erreurs'])
		return "<br />\n<b>"._T('info_erreur_squelette')."</b><br />\n";
	else
		$debug_messages .= "<div style='position: fixed; top: 10px; left: 10px;
		z-index: 10000; background-color: pink;'>$retour</div>";
}


//
// Erreur au parsing des squelettes : afficher le code fautif
//
function erreur_squelette($message, $lieu) {
	global $auteur_session, $debug_messages;
	static $runs;
	
	// Drapeau pour interdire d'ecrire les fichiers dans le cache
	# define('spip_erreur_fatale', 'erreur_squelette');
	# En fait, a partir du moment ou l'erreur est dans le squelette,
	# ca ne change rien et autant cacher quand meme !

	spip_log("Erreur squelette: $message | $lieu ("
	.$GLOBALS['fond'].".html)");
	$GLOBALS['bouton_admin_debug'] = true;

	// Pour un visiteur normal, ne rien afficher, si SPIP peut s'en sortir
	// tant mieux, sinon l'erreur se verra de toutes facons :-(
	// ajouter &var_debug=oui pour discuter sur spip@rezo.net
	if ($HTTP_COOKIE_VARS['spip_admin'] OR $auteur_session
	OR $GLOBALS['var_debug']) {
		$message = "<h2>"._T('info_erreur_squelette')."</h2><p>$message</p>";
		$message .= '<br /><FONT color="#FF000">' . $lieu . '</FONT>'; 

		$debug_messages .= "<div style='position: fixed; top: 10px; left: 10px;
		z-index: 10000; background-color: pink;'>$message</div>";
	}

	// Eviter les boucles infernales
	if (++$runs > 4) die ($debug_messages);
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
function debug_page($no_exit = false) {
	global $flag_ob;
	global $debug_objets, $debug_objet, $debug_affiche;

	if ($flag_ob)
		ob_end_clean();


	@header("Content-Type: text/html; charset=".lire_meta('charset'));
	if (!$GLOBALS['debug_objets']['sourcefile']) return;
	spip_setcookie('spip_debug', 'oui', time()+12*3600);

	$page = "<html><head><title>Debug</title></head>\n";
	$page .= "<body>\n<div id='spip-debug'>";
	echo calcul_admin_page('', $page);

	echo "<ul>\n";
	foreach ($debug_objets['sourcefile'] as $nom_skel => $sourcefile) {
		echo "<li><b>".$sourcefile."</b>";
		$link = $GLOBALS['clean_link'];
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
				$link = $GLOBALS['clean_link'];
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

	echo "\n</div></body>";
	if (!$no_exit) exit;
}

function debug_dumpfile ($texte) {
	global $flag_ob;

	# un peu violent : si on est un fichier inclus,
	# il faut d'abord vider le ob_
	if ($flag_ob)
		ob_end_clean();

	debug_page('no exit');

	if (!$texte) exit;
	$tableau = explode("\n", $texte);
	$format = "%0".strlen(count($tableau))."d";
	$texte = '';
	foreach ($tableau as $ligne)
		$texte .= "\n".sprintf($format, ++$i).'. '.$ligne;
    echo "<div id=\"debug_boucle\"><fieldset><legend>".$GLOBALS['debug_affiche']."</legend>";
	highlight_string($texte);
	echo "</fieldset></div>";

	exit;
}


/*
function verifie_cookie_debug() {
	global $code_activation_debug;

	if ($GLOBALS['HTTP_COOKIE_VARS']['spip_debug']
	!= $code_activation_debug) {
		spip_setcookie('spip_debug', $code_activation_debug, time()+3600);
		include_ecrire('inc_presentation.php3');
		install_debut_html(_L('Bienvenue dans le debuggueur de SPIP'));
		echo "<P>"._L("Cet outil vous permet d'analyser les pages produites par
		SPIP. Il est parfois de lecture difficile, mais il offre en contrepartie
		une grande capacit&eacute; de recherche des erreurs, et une meilleure
		compr&eacute;hension du fonctionnement des boucles et balises du
		syst&egrave;me.</p>");
		echo "<P>"._L("Pour entrer, il vous suffit de recharger cette page,
		apr&egrave;s avoir accept&eacute; un cookie (ce dernier permet
		d'&eacute;carter les moteurs de recherche, et installe un bouton
		d'administration suppl&eacute;mentaire &laquo;debug&raquo; sur
		votre &eacute;cran, pendant une heure).</p>");
		install_fin_html();
		exit;
	} else
		return true;
}
*/

?>
