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


// ON PEUT SUPPRIMER CE FICHIER

// http://doc.spip.org/@action_instituer_breve_dist
function action_instituer_breve_dist() {

	include_spip('inc/actions');
	$var_f = charger_fonction('controler_action_auteur', 'inc');
	$var_f();

	$arg = _request('arg');

	list($id_breve, $statut) = preg_split('/\W/', $arg);

	$id_breve = intval($id_breve);

	include_spip('action/editer_breve');
	revisions_breves($id_breve, false, array('statut' => $statut));
}

?>
