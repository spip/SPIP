<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion d'affichage d'accès interdit
 *
 * @package SPIP\Core\Exec
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Un exec d'acces interdit
 *
 * @param string $message
 */
function exec_403_dist($message = '') {

	$exec = _request('exec');

	$titre = "exec_$exec";
	$navigation = '';
	$extra = '';

	if (!$message) {
		$message = _T('avis_acces_interdit_prive', ['exec' => _request('exec')]);
	}

	$contenu = "<h1 class='grostitre'>" . _T('info_acces_interdit') . '</h1>' . $message;

	if (_request('var_zajax')) {
		include_spip('inc/actions');
		ajax_retour($contenu);
	} else {
		include_spip('inc/presentation'); // alleger les inclusions avec un inc/presentation_mini

		$commencer_page = charger_fonction('commencer_page', 'inc');
		echo $commencer_page($titre);

		echo debut_gauche("403_$exec", true);
		echo recuperer_fond('prive/squelettes/navigation/dist', []);
		echo pipeline('affiche_gauche', ['args' => ['exec' => '403', 'exec_erreur' => $exec], 'data' => '']);

		echo creer_colonne_droite('403', true);
		echo pipeline('affiche_droite', ['args' => ['exec' => '403', 'exec_erreur' => $exec], 'data' => '']);

		echo debut_droite('403', true);
		echo pipeline(
			'affiche_milieu',
			['args' => ['exec' => '403', 'exec_erreur' => $exec], 'data' => $contenu]
		);

		echo fin_gauche(), fin_page();
	}
}
