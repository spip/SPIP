<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_ADMIN")) return;
define("_INC_ADMIN", "1");


//
// Afficher un bouton admin
//

function bouton_admin($titre, $lien) {
	return "<li><a href='$lien' class='spip-admin-boutons'>$titre</a></li>\n";
}


function boutons_admin_debug () {
	if ($GLOBALS['bouton_admin_debug']
	AND ($GLOBALS['auteur_session']['statut'] == '0minirezo')) {
		include_ecrire('inc_filtres.php3');

		$link = $GLOBALS['clean_link'];
		if ($link->getvar('var_afficher_debug') != 'page') {
			$link->addvar('var_afficher_debug', 'page');
			$ret .= bouton_admin(_L('Debug cache'), $link->getUrl());
		}

		$link = $GLOBALS['clean_link'];
		if ($link->getvar('var_afficher_debug') != 'skel') {
			$link->addvar('var_afficher_debug', 'skel');
			$link->addvar('recalcul', 'oui');
			$ret .= bouton_admin(_L('Debug skel'), $link->getUrl());
		}

		$link = $GLOBALS['clean_link'];
		if ($link->getvar('var_afficher_debug') != '') {
			$link->delvar('var_afficher_debug');
			$link->addvar('recalcul', 'oui');
			$ret .= bouton_admin(_T('icone_retour'), $link->getUrl());
		}
	}
	
	return $ret;
}

function afficher_boutons_admin($pop) {
	global $id_article, $id_breve, $id_rubrique, $id_mot, $id_auteur;
	include_ecrire("inc_filtres.php3");
	include_ecrire("inc_lang.php3");

	// regler les boutons dans la langue de l'admin (sinon tant pis)
	if ($login = addslashes(ereg_replace('^@','',$GLOBALS['spip_admin']))) {
		$q = spip_query("SELECT lang FROM spip_auteurs WHERE login='$login'");
		$row = spip_fetch_array($q);
		$lang = $row['lang'];
	}
	lang_select($lang);

	// Feuilles de style admin : d'abord la CSS officielle, puis la perso,
	// puis celle du squelette (.spip-admin, cf. impression.css)
	$ret .= "<link rel='stylesheet' href='spip_admin.css' type='text/css' />\n";
	if (@file_exists('spip_admin_perso.css')) echo "\t<link rel='stylesheet' href='spip_admin_perso.css' type='text/css' />\n";
	$ret .= '<div class="spip-admin-float">
	<div class="spip-admin-bloc" dir="'.lang_dir($lang,'ltr','rtl').'">
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

	// Bouton Recalculer
	$link = $GLOBALS['clean_link'];
	$link->addVar('recalcul', 'oui');
	$lien =  $link->getUrl();
	$ret .= bouton_admin(_T('admin_recalculer').$pop, $lien);

	// Bouton statistiques
	if (lire_meta("activer_statistiques") != "non" AND $id_article
	AND ($GLOBALS['auteur_session']['statut'] == '0minirezo')) {
		if (spip_fetch_array(spip_query("SELECT id_article FROM spip_articles WHERE id_article =".intval($id_article)))) {
			include_local ("inc-stats.php3");
			$ret .= bouton_admin(_T('stats_visites_et_popularite',
			afficher_raccourci_stats($id_article)),
			"./ecrire/statistiques_visites.php3?id_article=$id_article");
		}
	}

	// Boutons debug
	$ret .= boutons_admin_debug();

	$ret .= "</ul></div></div></div>";

	lang_dselect();

	return $ret;
}

function calcul_admin_page($cached, $texte) {
	$a = '<'.'?php echo afficher_boutons_admin("'. ($cached ? ' *' : '').'"); ?'.'>';

	// La constante doit etre definie a l'identique dans inc-form-squel
	// balise #FORMULAIRE_ADMIN ? sinon ajouter en fin de page
	if (!(strpos($texte, '<!-- @@formulaire_admin@@45609871@@ -->') === false))
		$texte = str_replace('<!-- @@formulaire_admin@@45609871@@ -->', $a, $texte);
	else if (eregi('</(body|html)>', $texte, $regs))
		$texte = str_replace($regs[0], $a.$regs[0], $texte);
	else
		$texte .= $a;

	return $texte;
}


function page_debug($type,$texte,$fichier) {
	@header('Content-Type: text/html; charset='.lire_meta('charset'));
	echo "<html><head><title>Debug $type : $fichier</title>
	<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
	<link rel='stylesheet' href='spip_admin.css' type='text/css' />\n";
	if (@file_exists('spip_admin_perso.css')) echo "\t<link rel='stylesheet' href='spip_admin_perso.css' type='text/css' />\n";
	echo "</head><body>\n";
	echo "<code>$fichier</code>\n";
	echo '<div class="spip-admin-bloc">
	<div class="spip-admin">';
	echo boutons_admin_debug();
	echo "</ul></div><hr />\n";
	highlight_string($texte);
}


?>
