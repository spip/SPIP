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

include_spip('inc/charsets');	# pour le nom de fichier
include_spip('inc/documents');

// Effacer une rubrique
// http://doc.spip.org/@action_supprimer_dist
function action_supprimer_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	preg_match('/^(\w+)\W(\d+)(\W(\w+)\W(\d+))?$/', $arg, $r);
	$var_nom = 'action_supprimer_' . $r[1];
	if (function_exists($var_nom)) {
		$var_nom($r);
	}
	else
		spip_log("action supprimer $arg incompris");
}


// http://doc.spip.org/@action_supprimer_rubrique
function action_supprimer_rubrique($r)
{
	list(,,$id_rubrique) = $r;
	sql_delete("spip_rubriques", "id_rubrique=$id_rubrique");
	// Les admin restreints qui n'administraient que cette rubrique
	// deviennent redacteurs
	// (il y a sans doute moyen de faire ca avec un having)

	$q = sql_select("id_auteur", "spip_auteurs_rubriques", "id_rubrique=$id_rubrique");

	while ($r = sql_fetch($q)) {
		$id_auteur = $r['id_auteur'];
		sql_delete("spip_auteurs_rubriques", "id_rubrique=$id_rubrique AND id_auteur=$id_auteur");
		$n = sql_countsel("spip_auteurs_rubriques", "id_auteur=$id_auteur");
		if (!$n)
			sql_updateq("spip_auteurs", array("statut" => '1comite'), "id_auteur=$id_auteur");
	}
	// menu_rubriques devra recalculer
	effacer_meta("date_calcul_rubriques");

	// Une rubrique supprimable n'avait pas le statut "publie"
	// donc rien de neuf pour la rubrique parente
	include_spip('inc/rubriques');
	calculer_langues_rubriques();

	// invalider les caches marques de cette rubrique
	include_spip('inc/invalideur');
	suivre_invalideur("id='id_rubrique/$id_rubrique'");
}

?>
