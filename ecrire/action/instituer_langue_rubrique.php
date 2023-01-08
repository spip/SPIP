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
 * Action des changements de langue des rubriques
 *
 * @package SPIP\Core\Edition
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Modifie la langue d'une rubrique
 **/
function action_instituer_langue_rubrique_dist() {

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();
	$changer_lang = _request('changer_lang');

	[$id_rubrique, $id_parent] = preg_split('/\W/', $arg);

	if (
		$changer_lang
		and $id_rubrique > 0
		and $GLOBALS['meta']['multi_rubriques'] == 'oui'
		and ($GLOBALS['meta']['multi_secteurs'] == 'non' or $id_parent == 0)
	) {
		if ($changer_lang != 'herit') {
			sql_updateq(
				'spip_rubriques',
				['lang' => $changer_lang, 'langue_choisie' => 'oui'],
				"id_rubrique=$id_rubrique"
			);
		} else {
			if ($id_parent == 0) {
				$langue_parent = $GLOBALS['meta']['langue_site'];
			} else {
				$langue_parent = sql_getfetsel('lang', 'spip_rubriques', "id_rubrique=$id_parent");
			}
			sql_updateq(
				'spip_rubriques',
				['lang' => $langue_parent, 'langue_choisie' => 'non'],
				"id_rubrique=$id_rubrique"
			);
		}
		include_spip('inc/rubriques');
		calculer_langues_rubriques();

		// invalider les caches marques de cette rubrique
		include_spip('inc/invalideur');
		suivre_invalideur("id='rubrique/$id_rubrique'");
	}
}
