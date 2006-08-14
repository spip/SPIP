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

function action_instituer_collaboration_dist() {

	include_spip('inc/actions');
	$var_f = charger_fonction('controler_action_auteur', 'inc');
	$var_f();

	$arg = _request('arg');
	$id_auteur = _request('id_auteur');

	if ($arg AND ($id_auteur = intval($id_auteur))) {
		include_spip('inc/drapeau_edition');
		if ($arg == 'tous')
			debloquer_tous($id_auteur);
		else
			debloquer_edition($id_auteur, $arg, 'article');
	}
}
?>
