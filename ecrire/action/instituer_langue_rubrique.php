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
			sql_updateq('spip_rubriques', array('lang'=>$changer_lang, 'langue_choisie'=>'oui'), "id_rubrique=$id_rubrique");
		else {
			if ($id_parent == 0)
				$langue_parent = $GLOBALS['meta']['langue_site'];
			else {
				$langue_parent = sql_getfetsel("lang", "spip_rubriques", "id_rubrique=$id_parent");
			}
			sql_updateq('spip_rubriques', array('lang'=>$langue_parent, 'langue_choisie'=>'non'), "id_rubrique=$id_rubrique");
		}
		include_spip('inc/rubriques');
		calculer_langues_rubriques();

		// invalider les caches marques de cette rubrique
		include_spip('inc/invalideur');
		suivre_invalideur("id='id_rubrique/$id_rubrique'");
	}
}
?>
