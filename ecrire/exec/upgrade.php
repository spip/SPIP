<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
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

	global $spip_version;

	if (!_FILE_CONNECT)
		redirige_par_entete(generer_url_ecrire("install"));

	// Si reinstallation necessaire, message ad hoc
	if (_request('reinstall') == 'oui') {

		@copy(_FILE_CONNECT, _FILE_CONNECT_INS);

		echo minipres(_T('titre_page_upgrade'),
				"<p><b>"
				. _T('texte_nouvelle_version_spip_1')
				. "</b><p> "
				. _T('texte_nouvelle_version_spip_2',
				   array('connect' => '<tt>' . _FILE_CONNECT . '</tt>'))
				. generer_form_ecrire('upgrade', "<input name='reinstall' value='non' />",'',	_T('bouton_relancer_installation')));
		exit;
	}

	// Verifier la version
	$version_installee = (double) str_replace(',','.',$GLOBALS['meta']['version_installee']);
# NB: str_replace car, sur club-internet, il semble que version_installe soit
# enregistree au format '1,812' et non '1.812'

	// Qu'est-ce que tu fais ici?
	if ($spip_version == $version_installee)
		redirige_par_entete(generer_url_ecrire());

	// Erreur downgrade
	// (cas de double installation de fichiers SPIP sur une meme base)
	if ($spip_version < $version_installee)
		$commentaire = _T('info_mise_a_niveau_base_2');
	// Commentaire standard upgrade
	else $commentaire = _T('texte_mise_a_niveau_base_1');

	$_POST['reinstall'] = 'non'; // pour copy_request dans admin

	$r = generer_action_auteur('purger', 'cache', _DIR_RESTREINT_ABS, true);
	$admin = charger_fonction('admin', 'inc');
	$admin('upgrade', _T('info_mise_a_niveau_base'), $commentaire, $r);
}
?>
