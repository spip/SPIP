<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2019                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
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
		$message = _L("Vous n'avez pas le droit d'acc&eacute;der à la page <b>@exec@</b>.", array('exec' => _request('exec')));
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
		echo recuperer_fond('prive/squelettes/navigation/dist', array());
		echo pipeline('affiche_gauche', array('args' => array('exec' => '403', 'exec_erreur' => $exec), 'data' => ''));

		echo creer_colonne_droite('403', true);
		echo pipeline('affiche_droite', array('args' => array('exec' => '403', 'exec_erreur' => $exec), 'data' => ''));

		echo debut_droite('403', true);
		echo pipeline(
			'affiche_milieu',
			array('args' => array('exec' => '403', 'exec_erreur' => $exec), 'data' => $contenu)
		);

		echo fin_gauche(), fin_page();
	}
}
