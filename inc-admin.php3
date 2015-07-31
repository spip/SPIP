<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_ADMIN")) return;
define("_INC_ADMIN", "1");

include_ecrire('inc_debug_sql.php3');

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
		$link = new Link;
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
			$link = new Link;
			if ($GLOBALS['code_activation_debug'])
				$code_activation = $GLOBALS['code_activation_debug'];
			else if ($GLOBALS['auteur_session']['statut'] == '0minirezo')
				$code_activation = 'oui';
			if ($code_activation) {
				$link->addvar('var_debug', $code_activation);
				$ret .= bouton_admin(_L('Debug'), $link->getUrl());
			}
		}
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


?>
