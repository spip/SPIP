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
 * Gestion d'affichage de page introuvable
 *
 * @package SPIP\Core\Exec
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Un exec d'erreur
 */
function exec_404_dist() {

	$exec = _request('exec');

	$titre = "exec_$exec";
	$navigation = '';
	$extra = '';

	include_spip('inc/presentation'); // alleger les inclusions avec un inc/presentation_mini
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page($titre);

	echo debut_gauche();
	echo pipeline('affiche_gauche', ['args' => ['exec' => '404', 'exec_erreur' => $exec], 'data' => '']);

	echo creer_colonne_droite();
	echo pipeline('affiche_droite', ['args' => ['exec' => '404', 'exec_erreur' => $exec], 'data' => '']);

	echo debut_droite();
	echo "<h1 class='grostitre'>" . _T('fichier_introuvable', ['fichier' => $exec]) . '</h1>';
	echo pipeline('affiche_milieu', ['args' => ['exec' => '404', 'exec_erreur' => $exec], 'data' => '']);

	echo fin_gauche(), fin_page();
}
