<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_ADMIN")) return;
define("_INC_ADMIN", "1");


//
// Afficher un bouton admin
//

function bouton_admin($titre, $lien) {
	include_ecrire("inc_filtres.php3");
	$link = new Link($lien);
	$link->delVar('submit');
	echo $link->getForm('GET');
	echo "<INPUT TYPE='submit' NAME='submit' VALUE=\"".attribut_html($titre)."\" CLASS='spip_bouton'>\n";
	echo "</FORM>";
}

function afficher_boutons_admin() {
	global $id_article, $id_breve, $id_rubrique, $id_mot, $id_auteur;

	echo '<div class="spip-admin">';
	if ($id_article) {
		bouton_admin(_T('admin_modifier_article')." ($id_article)", "./ecrire/articles.php3?id_article=$id_article");
	}
	else if ($id_breve) {
		bouton_admin(_T('admin_modifier_breve')." ($id_breve)", "./ecrire/breves_voir.php3?id_breve=$id_breve");
	}
	else if ($id_rubrique) {
		bouton_admin(_T('admin_modifier_rubrique')." ($id_rubrique)", "./ecrire/naviguer.php3?coll=$id_rubrique");
	}
	else if ($id_mot) {
		bouton_admin(_T('admin_modifier_mot')." ($id_mot)", "./ecrire/mots_edit.php3?id_mot=$id_mot");
	}
	else if ($id_auteur) {
		bouton_admin(_T('admin_modifier_auteur')." ($id_auteur)", "./ecrire/auteurs_edit.php3?id_auteur=$id_auteur");
	}
	$link = $GLOBALS['clean_link'];
	$link->addVar('recalcul', 'oui');
	$link->delVar('submit');
	echo $link->getForm('GET');
	if ($GLOBALS['use_cache']) $pop = " *";
	else $pop = "";
	echo "<input type='submit' class='spip_bouton' name='submit' value=\"".attribut_html(_T('admin_recalculer')).$pop."\">";
	echo "</form>\n";

	if (lire_meta("activer_statistiques") != "non" AND $id_article) {
		include_local ("inc-stats.php3");
		afficher_raccourci_stats($id_article);
	}

	echo "</div>";
}


?>
