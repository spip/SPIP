<?php

include ("inc_version.php3");


// Si reinstallation necessaire, message ad hoc
if ($reinstall == 'oui') {
	if (!file_exists("inc_connect.php3")) {
		Header("Location: install.php3");
		exit;
	}

	@copy("inc_connect.php3", "inc_connect_install.php3");

	include_ecrire("inc_presentation.php3");
	install_debut_html(_T('titre_page_upgrade'));
	echo "<p><b>"._T('texte_nouvelle_version_spip_1')."</b><p> ";
	echo _T('texte_nouvelle_version_spip_2');
	echo _T('texte_nouvelle_version_spip_3');
	echo _T('texte_nouvelle_version_spip_4');
	echo _T('texte_nouvelle_version_spip_5');
	echo _T('texte_nouvelle_version_spip_6');
	echo _T('texte_nouvelle_version_spip_7');
	echo "<p>"._T('texte_nouvelle_version_spip_8');
	echo _T('texte_nouvelle_version_spip_9');
	echo _T('texte_nouvelle_version_spip_10');

	$link = new Link();
	echo "<p><div align='right'>";
	echo $link->getForm('GET');
	echo "<input type='submit' name='submit' value=\""._T('bouton_relancer_installation')."\" class='fondl'>";
	echo "</form>\n";

	install_fin_html();
	exit;
}


include_ecrire ("inc_auth.php3");
include_ecrire ("inc_admin.php3");
include_ecrire ("inc_acces.php3");
include_ecrire ("inc_meta.php3");
include_ecrire ("inc_config.php3");
include_ecrire ("inc_texte.php3");
include_ecrire ("inc_filtres.php3");

$upgrade_titre = _T('info_mise_a_niveau_base');

// Commentaire standard upgrade
$commentaire = _T('texte_mise_a_niveau_base_1');

// Erreur downgrade (cas de double installation de fichiers SPIP sur une meme base)
if ($spip_version < (double) lire_meta('version_installee'))
	$commentaire = _T('info_mise_a_niveau_base_2');

// Qu'est-ce que tu fais ici?
if ($spip_version == (double) lire_meta('version_installee')) {
	@header("Location: index.php3");
	exit;
}

debut_admin($upgrade_titre, $commentaire);

include_ecrire ("inc_base.php3");

creer_base();
maj_base();
ecrire_acces();

init_config();

$hash = calculer_action_auteur("purger_cache");
$redirect = rawurlencode("index.php3");

fin_admin($upgrade_titre);

@header ("Location: ../spip_cache.php3?purger_cache=oui&id_auteur=$connect_id_auteur&hash=$hash&redirect=$redirect");

?>
