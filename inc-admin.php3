<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_ADMIN")) return;
define("_INC_ADMIN", "1");


//
// Afficher un bouton admin
//

function bouton_admin($titre, $lien) {
	$link = new Link($lien);
	$link->delVar('submit');
	$ret = $link->getForm('GET');
	$ret .= "<input type='submit' name='submit' value=\"".attribut_html($titre)."\" class='spip_bouton' />\n";
	$ret .= "</form>";
	return $ret;
}


function boutons_admin_debug () {
	if ($GLOBALS['bouton_admin_debug']
	AND ($GLOBALS['auteur_session']['statut'] == '0minirezo')) {
		include_ecrire('inc_filtres.php3');

		$link = $GLOBALS['clean_link'];
		if ($link->getvar('var_afficher_debug') != 'page') {
			$link->addvar('var_afficher_debug', 'page');
			$ret .=  $link->getForm('GET');
			$ret .= "<input type='submit' class='spip_bouton' name='submit' value=\"".attribut_html(_L('Debug cache'))."\" />";
			$ret .= "</form>\n";
		}

		$link = $GLOBALS['clean_link'];
		if ($link->getvar('var_afficher_debug') != 'skel') {
			$link->addvar('var_afficher_debug', 'skel');
			$link->addvar('recalcul', 'oui');
			$ret .=  $link->getForm('GET');
			$ret .= "<input type='submit' class='spip_bouton' name='submit' value=\"".attribut_html(_L('Debug skel'))."\" />";
			$ret .= "</form>\n";
		}

		$link = $GLOBALS['clean_link'];
		if ($link->getvar('var_afficher_debug') != '') {
			$link->delvar('var_afficher_debug');
			$link->addvar('recalcul', 'oui');
			$ret .=  $link->getForm('GET');
			$ret .= "<input type='submit' class='spip_bouton' name='submit' value=\"".attribut_html(_T('icone_retour'))."\" />";
			$ret .= "</form>\n";
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

	// Bouton modifier
	$ret = '<div class="spip-admin" dir="'.lang_dir($lang,'ltr','rtl').'">';
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
	$ret .=  $link->getForm('GET');
	$ret .= "<input type='submit' class='spip_bouton' name='submit' value=\"".attribut_html(_T('admin_recalculer')).$pop."\" />";
	$ret .= "</form>\n";

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

	$ret .= "</div>";

	lang_dselect();

	return $ret;
}

?>
