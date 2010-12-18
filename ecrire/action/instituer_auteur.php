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

// http://doc.spip.org/@action_instituer_auteur_dist
function action_instituer_auteur_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	list($id_auteur, $statut) = preg_split('/\W/', $arg);
	if (!$statut) $statut = _request('statut'); // cas POST
	if (!$statut) return; // impossible mais sait-on jamais

	$id_auteur = intval($id_auteur);

	include_spip('action/editer_auteur');

	$c = array(
		array('statut'=>$statut,'id_parent'=>intval(_request('id_parent')),'restreintes'=>_request('restreintes'))
	);
	instituer_auteur($id_auteur, $c);

}

?>
