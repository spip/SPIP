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

// http://doc.spip.org/@action_instituer_breve_dist
function action_instituer_breve_dist() {

	include_spip('inc/actions');
	$var_f = charger_fonction('controler_action_auteur', 'inc');
	$var_f();

	$arg = _request('arg');

	list($id_breve, $statut) = preg_split('/\W/', $arg);

	$id_breve = intval($id_breve);
	$result = spip_query("SELECT statut FROM spip_breves WHERE id_breve=$id_breve");

	if ($row = spip_fetch_array($result)) {
		$statut_ancien = $row['statut'];
		}

	if ($statut != $statut_ancien) {
		spip_query("UPDATE spip_breves SET date_heure=NOW(), statut='$statut' WHERE id_breve=$id_breve");

		include_spip('inc/rubriques');
		calculer_rubriques();
	}
}
?>
