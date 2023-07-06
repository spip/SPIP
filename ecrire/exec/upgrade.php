<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Gestion de l'installation et de la mise à jour de SPIP
 *
 * @package SPIP\Core\Upgrade
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/actions');
include_spip('inc/headers');

/**
 * Fonction d'installation et de mise à jour du core de SPIP
 *
 * @return void
 **/
function exec_upgrade_dist() {

	if (!_FILE_CONNECT) {
		redirige_url_ecrire('install');
	}

	// Si reinstallation necessaire, message ad hoc
	if (_request('reinstall') == 'oui') {
		include_spip('inc/minipres');
		$r = minipres(
			_T('titre_page_upgrade'),
			'<p><b>'
			. _T('texte_nouvelle_version_spip_1')
			. '</b><p> '
			. _T(
				'texte_nouvelle_version_spip_2',
				['connect' => '<tt>' . _FILE_CONNECT . '</tt>']
			)
			. generer_form_ecrire(
				'upgrade',
				"<input type='hidden' name='reinstall' value='non'>",
				'',
				_T('bouton_relancer_installation')
			)
		);
		echo $r;
	} elseif (_request('fin')) {
		include_spip('inc/plugin');
		actualise_plugins_actifs();
		include_spip('inc/headers');
		$res = generer_url_ecrire('admin_plugin', 'var_mode=recalcul');
		echo redirige_formulaire($res);
	} else {
		if (!isset($GLOBALS['meta']['version_installee'])) {
			$GLOBALS['meta']['version_installee'] = 0.0;
		} else {
			$GLOBALS['meta']['version_installee'] =
				(double) str_replace(',', '.', (string) $GLOBALS['meta']['version_installee']);
		}
		# NB: str_replace car, sur club-internet, il semble que version_installe soit
		# enregistree au format '1,812' et non '1.812'

		// Erreur downgrade
		// (cas de double installation de fichiers SPIP sur une meme base)
		if ($GLOBALS['spip_version_base'] < $GLOBALS['meta']['version_installee']) {
			$commentaire = _T('info_mise_a_niveau_base_2');
		} // Commentaire standard upgrade
		else {
			$commentaire = _T('texte_mise_a_niveau_base_1');
		}

		$commentaire .= '<br />[' . $GLOBALS['meta']['version_installee'] . '/' . $GLOBALS['spip_version_base'] . ']';

		$_POST['reinstall'] = 'non'; // pour copy_request dans admin
		include_spip('inc/headers');
		$admin = charger_fonction('admin', 'inc');
		$res = $admin('upgrade', _T('info_mise_a_niveau_base'), $commentaire);
		if ($res) {
			echo $res;
		} else {
			// effacer les alea pour forcer leur relecture
			// si jamais ils ont change pendant l'upgrade
			unset($GLOBALS['meta']['alea_ephemere']);
			unset($GLOBALS['meta']['alea_ephemere_ancien']);
			$res = redirige_action_auteur('purger', 'cache', 'upgrade', 'fin=oui', true);
			echo redirige_formulaire($res);
		}
	}
}
