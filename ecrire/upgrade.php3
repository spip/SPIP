<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!isset($reinstall)) $reinstall = 'non';
include ("inc.php3");

if (!_FILE_CONNECT) {
	Header("Location: install.php3");
	exit;
 }

include_ecrire ("inc_acces.php3");
include_ecrire ("inc_config.php3");
include_ecrire ("inc_texte.php3");

// Si reinstallation necessaire, message ad hoc
if ($reinstall == 'oui') {

	@copy(_FILE_CONNECT, _FILE_CONNECT_INS);

	$link = new Link();

	install_debut_html(_T('titre_page_upgrade')); echo "<p><b>"._T('texte_nouvelle_version_spip_1')."</b><p> ";
	echo _T('texte_nouvelle_version_spip_2');
	echo "<p><div align='right'>";
	echo $link->getForm('GET');
	echo "<input type='submit' name='submit' value=\""._T('bouton_relancer_installation')."\" class='fondl'>";
	echo "</form>\n";

	install_fin_html();
	exit;
}


// eviter les actions vides pour cause de fichier de langue inaccessible.
$upgrade_titre = _T('info_mise_a_niveau_base') ;
if (!$upgrade_titre) $upgrade_titre = 'info_mise_a_niveau_base';

// Commentaire standard upgrade
$commentaire = _T('texte_mise_a_niveau_base_1');

// Verifier la version
$version_installee = (double) str_replace(',','.',lire_meta('version_installee'));
# NB: str_replace car, sur club-internet, il semble que version_installe soit
# enregistree au format '1,812' et non '1.812'

// Erreur downgrade (cas de double installation de fichiers SPIP sur une meme base)
if ($spip_version < $version_installee)
	$commentaire = _T('info_mise_a_niveau_base_2');


// Qu'est-ce que tu fais ici?
if ($spip_version == $version_installee) {
	@header("Location: index.php3");
	exit;
}

debut_admin($upgrade_titre, $commentaire);

include_ecrire ("inc_base.php3");

creer_base();
$ok = maj_base();

if ($ok) {
	ecrire_acces();
	init_config();

}

fin_admin($upgrade_titre);

if ($ok) {
	$hash = calculer_action_auteur("purger_cache");
	redirige_par_entete("../spip_cache.php3?purger_cache=oui"
		."&id_auteur=$connect_id_auteur&hash=$hash"
		."&redirect=" .  _DIR_RESTREINT_ABS . "index.php3");
}
else {
	echo _T('alerte_maj_impossible', array('version' => $spip_version));
	exit;
}

?>
