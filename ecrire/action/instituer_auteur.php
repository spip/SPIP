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

// http://doc.spip.org/@action_instituer_auteur_dist
function action_instituer_auteur_dist() {

	$var_f = charger_fonction('securiser_action', 'inc');
	$var_f();

	$arg = _request('arg');

	if (preg_match(",^(\d+)\W-(\d+)$,", $arg, $r))
		spip_query("DELETE FROM spip_auteurs_rubriques WHERE id_auteur=".$r[1]." AND id_rubrique=" . $r[2]);
	else if (!preg_match(',^(\d+)$,', $arg, $r)) {
		spip_log("action_instituer_auteur_dist: $arg incompris");
	} else {
		$id_auteur = $r[1];
		if ($id_parent = intval(_request('id_parent'))) {
			include_spip('base/abstract_sql');
			spip_abstract_insert('spip_auteurs_rubriques', "(id_auteur,id_rubrique)", "(" .$id_auteur .',' . $id_parent . ')');
		}
		if ($statut = _request('statut')) {
			if (!ereg("^(0minirezo|1comite|5poubelle|6forum)$",$statut))
			  spip_log("action_instituer_auteur_dist: $statut incompris  pour $id_auteur");
			else {
				spip_query("UPDATE spip_auteurs SET statut='".$statut . "' WHERE id_auteur=" . $id_auteur);

				if ($GLOBALS['meta']['activer_moteur'] == 'oui') {
					include_spip("inc/indexation");
					marquer_indexer('spip_auteurs', $id_auteur);
				}
				// Mettre a jour les fichiers .htpasswd et .htpasswd-admin
				include_spip('inc/acces');
				ecrire_acces();
			}
		}
	}
}
?>
