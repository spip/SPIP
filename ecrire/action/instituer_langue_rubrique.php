<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@action_instituer_langue_rubrique_dist
function action_instituer_langue_rubrique_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();
	$changer_lang = _request('changer_lang');

	list($id_rubrique, $id_parent) = preg_split('/\W/', $arg);

	if ($changer_lang
	AND $id_rubrique>0
	AND $GLOBALS['meta']['multi_rubriques'] == 'oui'
	AND ($GLOBALS['meta']['multi_secteurs'] == 'non' OR $id_parent == 0)) {
		if ($changer_lang != "herit")
			spip_query("UPDATE spip_rubriques SET lang=" . _q($changer_lang) . ", langue_choisie='oui' WHERE id_rubrique=$id_rubrique");
		else {
			if ($id_parent == 0)
				$langue_parent = $GLOBALS['meta']['langue_site'];
			else {
				$row = spip_abstract_fetch(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_parent"));
				$langue_parent = $row['lang'];
			}
			spip_query("UPDATE spip_rubriques SET lang=" . _q($langue_parent) . ", langue_choisie='non' WHERE id_rubrique=$id_rubrique");
		}
		include_spip('inc/rubriques');
		calculer_rubriques();
		calculer_langues_rubriques();

		// invalider les caches marques de cette rubrique
		include_spip('inc/invalideur');
		suivre_invalideur("id='id_rubrique/$id_rubrique'");
	}
}
?>
