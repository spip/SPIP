<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/actions');
include_spip('inc/headers');

// http://doc.spip.org/@exec_upgrade_dist
function exec_upgrade_dist() {

	global $connect_id_auteur, $spip_version, $reinstall;

	if (!_FILE_CONNECT)
		redirige_par_entete(generer_url_ecrire("install"));

	// Si reinstallation necessaire, message ad hoc
	if ($reinstall == 'oui') {

		@copy(_FILE_CONNECT, _FILE_CONNECT_INS);

		echo install_debut_html(_T('titre_page_upgrade')); 
		echo "<p><b>",_T('texte_nouvelle_version_spip_1'),"</b><p> ",
		  _T('texte_nouvelle_version_spip_2',
		     array('connect' => '<tt>' . _FILE_CONNECT . '</tt>')),
		 "<p><div align='right'>",
		  '<form action="', generer_url_ecrire("upgrade", 'reinstall=non'),
		  '">', "<input type='submit' value=\"",
		_T('bouton_relancer_installation'),
		"\" class='fondl'>",
		"</form>\n";

		echo install_fin_html();
		exit;
	}

	// eviter les actions vides pour cause de fichier de langue inaccessible.
	$upgrade_titre = _T('info_mise_a_niveau_base') ;
	if (!$upgrade_titre) $upgrade_titre = 'info_mise_a_niveau_base';

	// Commentaire standard upgrade
	$commentaire = _T('texte_mise_a_niveau_base_1');

	// Verifier la version
	$version_installee = (double) str_replace(',','.',$GLOBALS['meta']['version_installee']);
# NB: str_replace car, sur club-internet, il semble que version_installe soit
# enregistree au format '1,812' et non '1.812'

	// Erreur downgrade
	// (cas de double installation de fichiers SPIP sur une meme base)
	if ($spip_version < $version_installee)
		$commentaire = _T('info_mise_a_niveau_base_2');

	// Qu'est-ce que tu fais ici?
	if ($spip_version == $version_installee)
		redirige_par_entete('./');

	// On passe a l'upgrade
	include_spip('inc/admin');

	$_POST['reinstall'] = 'non';
	debut_admin("upgrade", $upgrade_titre, $commentaire);

	include_spip('base/create');
	creer_base();
	include_spip('base/upgrade');
	maj_base();

	include_spip('inc/acces');
	include_spip('inc/config');
	ecrire_acces();
	init_config();

	fin_admin($upgrade_titre);

	redirige_par_entete(generer_action_auteur('purger', 'cache', _DIR_RESTREINT_ABS, true));
}

?>
