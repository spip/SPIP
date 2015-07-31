<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/actions');
include_spip('inc/headers');

// http://doc.spip.org/@exec_upgrade_dist
function exec_upgrade_dist() {

	global $spip_version_base;
	if (!_FILE_CONNECT)
		redirige_url_ecrire("install");

	// Si reinstallation necessaire, message ad hoc
	if (_request('reinstall') == 'oui') {
		include_spip('inc/minipres');
		$r = minipres(_T('titre_page_upgrade'),
				"<p><b>"
				. _T('texte_nouvelle_version_spip_1')
				. "</b><p> "
				. _T('texte_nouvelle_version_spip_2',
				   array('connect' => '<tt>' . _FILE_CONNECT . '</tt>'))
				. generer_form_ecrire('upgrade', "<input type='hidden' name='reinstall' value='non' />",'',	_T('bouton_relancer_installation')));
		echo $r;
	} else {

	if (!isset($GLOBALS['meta']['version_installee']))
		$GLOBALS['meta']['version_installee'] = 0.0;
	else $GLOBALS['meta']['version_installee'] =
	  (double) str_replace(',','.',$GLOBALS['meta']['version_installee']);
# NB: str_replace car, sur club-internet, il semble que version_installe soit
# enregistree au format '1,812' et non '1.812'

	// Erreur downgrade
	// (cas de double installation de fichiers SPIP sur une meme base)
	if ($spip_version_base < $GLOBALS['meta']['version_installee'])
		$commentaire = _T('info_mise_a_niveau_base_2');
	// Commentaire standard upgrade
	else $commentaire = _T('texte_mise_a_niveau_base_1');

	$_POST['reinstall'] = 'non'; // pour copy_request dans admin
	include_spip('inc/headers');
	$admin = charger_fonction('admin', 'inc');
	$res = $admin('upgrade', _T('info_mise_a_niveau_base'), $commentaire);
	if ($res) echo $res;
	else {
		$res = redirige_action_auteur('purger', 'cache', 'accueil', '', true);
		redirige_par_entete($res);
	}
	}
}
?>
