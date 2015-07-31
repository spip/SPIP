<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Modifier la langue d'un objet
 * @param string $objet
 * @param int $id
 * @param int $id_rubrique
 * @param string $changer_lang
 * @return string
 */
function action_instituer_langue_objet_dist($objet,$id, $id_rubrique, $changer_lang) {
	if ($changer_lang) {
		$table_objet_sql = table_objet_sql($objet);
		$id_table_objet = id_table_objet($objet);

		if ($changer_lang != "herit") {
			sql_updateq($table_objet_sql, array('lang'=>$changer_lang, 'langue_choisie'=>'oui'), "$id_table_objet=".intval($id));
			include_spip('inc/rubriques');
			$langues = calculer_langues_utilisees();
			ecrire_meta('langues_utilisees', $langues);
		}
		else {
			$langue_parent = sql_getfetsel("lang", "spip_rubriques", "id_rubrique=" . intval($id_rubrique));
			if (!$langue_parent)
				$langue_parent = $GLOBALS['meta']['langue_site'];
			sql_updateq($table_objet_sql, array('lang'=>$langue_parent, 'langue_choisie'=>'non'), "$id_table_objet=".intval($id));
			$changer_lang = $langue_parent;
		}
	}
	return $changer_lang;
}
