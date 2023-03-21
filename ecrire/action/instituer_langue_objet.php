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
 * Action des changements de langue des objets éditoriaux
 *
 * @package SPIP\Core\Edition
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Modifier la langue d'un objet
 *
 * @param string $objet
 * @param int $id
 * @param int $id_rubrique
 * @param string $changer_lang
 * @param string $serveur
 * @return string
 */
function action_instituer_langue_objet_dist($objet, $id, $id_rubrique, $changer_lang, $serveur = '') {
	if ($changer_lang) {
		$table_objet_sql = table_objet_sql($objet);
		$id_table_objet = id_table_objet($objet);
		$trouver_table = charger_fonction('trouver_table', 'base');
		$desc = $trouver_table($table_objet_sql, $serveur);

		$set = [];
		if (isset($desc['field']['langue_choisie'])) {
			$set['langue_choisie'] = 'oui';
		}

		if ($changer_lang != 'herit') {
			$set['lang'] = $changer_lang;
			sql_updateq($table_objet_sql, $set, "$id_table_objet=" . (int) $id, [], $serveur);
			include_spip('inc/rubriques'); // pour calculer_langues_rubriques et calculer_langues_utilisees
			if ($table_objet_sql == 'spip_rubriques') {
				calculer_langues_rubriques();
			}
			$langues = calculer_langues_utilisees($serveur);
			ecrire_meta('langues_utilisees', $langues);
		} else {
			$langue_parent = sql_getfetsel('lang', 'spip_rubriques', 'id_rubrique=' . (int) $id_rubrique);
			if (!$langue_parent) {
				$langue_parent = $GLOBALS['meta']['langue_site'];
			}
			$changer_lang = $langue_parent;
			$set['lang'] = $changer_lang;
			if (isset($set['langue_choisie'])) {
				$set['langue_choisie'] = 'non';
			}
			sql_updateq($table_objet_sql, $set, "$id_table_objet=" . (int) $id, [], $serveur);
			if ($table_objet_sql == 'spip_rubriques') {
				include_spip('inc/rubriques');
				calculer_langues_rubriques();
			}
		}
	}

	return $changer_lang;
}
