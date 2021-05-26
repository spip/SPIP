<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion des mises à jour de bdd de SPIP
 * 
 * Mises à jour en 3.0
 *
 * @package SPIP\Core\SQL\Upgrade
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


$GLOBALS['maj'][16428] = array(
	array('maj_liens', 'auteur'), // creer la table liens
	array('maj_liens', 'auteur', 'article'),
	array('sql_drop_table', "spip_auteurs_articles"),
	array('maj_liens', 'auteur', 'rubrique'),
	array('sql_drop_table', "spip_auteurs_rubriques"),
	array('maj_liens', 'auteur', 'message'),
	array('sql_drop_table', "spip_auteurs_messages"),
);

/**
 * Mise à jour des tables de liens
 *
 * Crée la table de lien au nouveau format (spip_xx_liens) ou insère
 * les données d'ancien format dans la nouveau format.
 *
 * Par exemple pour réunir en une seule table les liens de documents,
 * spip_documents_articles et spip_documents_forum
 *
 * Supprime la table au vieux format une fois les données transférées.
 *
 * @uses creer_ou_upgrader_table()
 * @uses maj_liens_insertq_multi_check()
 *
 * @param string $pivot
 *     Nom de la table pivot, tel que `auteur`
 * @param string $l
 *     Vide : crée la table de lien pivot.
 *     Sinon, nom de la table à lier, tel que `article`, et dans ce cas là,
 *     remplit spip_auteurs_liens à partir de spip_auteurs_articles.
 */
function maj_liens($pivot, $l = '') {

	@define('_LOG_FILTRE_GRAVITE', 8);

	$exceptions_pluriel = array('forum' => 'forum', 'syndic' => 'syndic');

	$pivot = preg_replace(',[^\w],', '', $pivot); // securite
	$pivots = (isset($exceptions_pluriel[$pivot]) ? $exceptions_pluriel[$pivot] : $pivot . "s");
	$liens = "spip_" . $pivots . "_liens";
	$id_pivot = "id_" . $pivot;
	// Creer spip_auteurs_liens
	global $tables_auxiliaires;
	if (!$l) {
		include_spip('base/auxiliaires');
		include_spip('base/create');
		creer_ou_upgrader_table($liens, $tables_auxiliaires[$liens], false);
	} else {
		// Preparer
		$l = preg_replace(',[^\w],', '', $l); // securite
		$primary = "id_$l";
		$objet = ($l == 'syndic' ? 'site' : $l);
		$ls = (isset($exceptions_pluriel[$l]) ? $exceptions_pluriel[$l] : $l . "s");
		$ancienne_table = 'spip_' . $pivots . '_' . $ls;
		$pool = 400;

		$trouver_table = charger_fonction('trouver_table', 'base');
		if (!$desc = $trouver_table($ancienne_table)) {
			return;
		}

		// securite pour ne pas perdre de donnees
		if (!$trouver_table($liens)) {
			return;
		}

		$champs = $desc['field'];
		if (isset($champs['maj'])) {
			unset($champs['maj']);
		}
		if (isset($champs[$primary])) {
			unset($champs[$primary]);
		}

		$champs = array_keys($champs);
		// ne garder que les champs qui existent sur la table destination
		if ($desc_cible = $trouver_table($liens)) {
			$champs = array_intersect($champs, array_keys($desc_cible['field']));
		}

		$champs[] = "$primary as id_objet";
		$champs[] = "'$objet' as objet";
		$champs = implode(', ', $champs);

		// Recopier les donnees
		$sub_pool = 100;
		while ($ids = array_map('reset', sql_allfetsel("$primary", $ancienne_table, '', '', '', "0,$sub_pool"))) {
			$insert = array();
			foreach ($ids as $id) {
				$n = sql_countsel($liens, "objet='$objet' AND id_objet=" . intval($id));
				while ($t = sql_allfetsel($champs, $ancienne_table, "$primary=" . intval($id), '', $id_pivot, "$n,$pool")) {
					$n += count($t);
					// empiler en s'assurant a minima de l'unicite
					while ($r = array_shift($t)) {
						$insert[$r[$id_pivot] . ':' . $r['id_objet']] = $r;
					}
					if (count($insert) >= $sub_pool) {
						maj_liens_insertq_multi_check($liens, $insert, $tables_auxiliaires[$liens]);
						$insert = array();
					}
					// si timeout, sortir, la relance nous ramenera dans cette fonction
					// et on verifiera/repartira de la
					if (time() >= _TIME_OUT) {
						return;
					}
				}
				if (time() >= _TIME_OUT) {
					return;
				}
			}
			if (count($insert)) {
				maj_liens_insertq_multi_check($liens, $insert, $tables_auxiliaires[$liens]);
			}
			sql_delete($ancienne_table, sql_in($primary, $ids));
		}
	}
}

/**
 * Insère des données dans une table de liaison de façon un peu sécurisée
 *
 * Si une insertion multiple échoue, on réinsère ligne par ligne.
 *
 * @param string $table Table de liaison
 * @param array $couples Tableau de couples de données à insérer
 * @param array $desc Description de la table de liaison
 * @return void
 **/
function maj_liens_insertq_multi_check($table, $couples, $desc = array()) {
	$n_before = sql_countsel($table);
	sql_insertq_multi($table, $couples, $desc);
	$n_after = sql_countsel($table);
	if (($n_after - $n_before) == count($couples)) {
		return;
	}
	// si ecart, on recommence l'insertion ligne par ligne...
	// moins rapide mais secure : seul le couple en doublon echouera, et non toute la serie
	foreach ($couples as $c) {
		sql_insertq($table, $c, $desc);
	}
}

$GLOBALS['maj'][17311] = array(
	array(
		'ecrire_meta',
		"multi_objets",
		implode(',',
			array_diff(
				array(
					(isset($GLOBALS['meta']['multi_rubriques']) and $GLOBALS['meta']['multi_rubriques'] == 'oui')
						? 'spip_rubriques' : '',
					(isset($GLOBALS['meta']['multi_articles']) and $GLOBALS['meta']['multi_articles'] == 'oui')
						? 'spip_articles' : ''
				),
				array('')
			))
	),
	array(
		'ecrire_meta',
		"gerer_trad_objets",
		implode(',',
			array_diff(
				array(
					(isset($GLOBALS['meta']['gerer_trad']) and $GLOBALS['meta']['gerer_trad'] == 'oui')
						? 'spip_articles' : ''
				),
				array('')
			))
	),
);
$GLOBALS['maj'][17555] = array(
	array('sql_alter', "TABLE spip_resultats ADD table_objet varchar(30) DEFAULT '' NOT NULL"),
	array('sql_alter', "TABLE spip_resultats ADD serveur char(16) DEFAULT '' NOT NULL"),
);

$GLOBALS['maj'][17563] = array(
	array('sql_alter', "TABLE spip_articles ADD virtuel VARCHAR(255) DEFAULT '' NOT NULL"),
	array('sql_update', 'spip_articles', array('virtuel' => 'SUBSTRING(chapo,2)', 'chapo' => "''"), "chapo LIKE '=_%'"),
);

$GLOBALS['maj'][17577] = array(
	array('maj_tables', array('spip_jobs', 'spip_jobs_liens')),
);

$GLOBALS['maj'][17743] = array(
	array('sql_update', 'spip_auteurs', array('prefs' => 'bio', 'bio' => "''"), "statut='nouveau' AND bio<>''"),
);

$GLOBALS['maj'][18219] = array(
	array('sql_alter', "TABLE spip_rubriques DROP id_import"),
	array('sql_alter', "TABLE spip_rubriques DROP export"),
);

$GLOBALS['maj'][18310] = array(
	array('sql_alter', "TABLE spip_auteurs_liens CHANGE vu vu VARCHAR(6) DEFAULT 'non' NOT NULL"),
);

$GLOBALS['maj'][18597] = array(
	array('sql_alter', "TABLE spip_rubriques ADD profondeur smallint(5) DEFAULT '0' NOT NULL"),
	array('maj_propager_les_secteurs'),
);

$GLOBALS['maj'][18955] = array(
	array('sql_alter', "TABLE spip_auteurs_liens ADD INDEX id_objet (id_objet)"),
	array('sql_alter', "TABLE spip_auteurs_liens ADD INDEX objet (objet)"),
);

/**
 * Mise à jour pour recalculer les secteurs des rubriques
 *
 * @uses propager_les_secteurs()
 **/
function maj_propager_les_secteurs() {
	include_spip('inc/rubriques');
	propager_les_secteurs();
}

/**
 * Mise à jour des bdd SQLite pour réparer les collation des champs texte
 * pour les passer en NOCASE
 *
 * @uses base_lister_toutes_tables()
 * @uses _sqlite_remplacements_definitions_table()
 **/
function maj_collation_sqlite() {


	include_spip('base/dump');
	$tables = base_lister_toutes_tables();

	// rien a faire si base non sqlite
	if (strncmp($GLOBALS['connexions'][0]['type'], 'sqlite', 6) !== 0) {
		return;
	}

	$trouver_table = charger_fonction('trouver_table', 'base');
	// forcer le vidage de cache
	$trouver_table('');

	// cas particulier spip_auteurs : retablir le collate binary sur le login
	$desc = $trouver_table("spip_auteurs");
	spip_log("spip_auteurs : " . var_export($desc['field'], true), "maj." . _LOG_INFO_IMPORTANTE);
	if (stripos($desc['field']['login'], "BINARY") === false) {
		spip_log("Retablir champ login BINARY sur table spip_auteurs", "maj");
		sql_alter("table spip_auteurs change login login VARCHAR(255) BINARY");
		$trouver_table('');
		$new_desc = $trouver_table("spip_auteurs");
		spip_log("Apres conversion spip_auteurs : " . var_export($new_desc['field'], true), "maj." . _LOG_INFO_IMPORTANTE);
	}

	foreach ($tables as $table) {
		if (time() >= _TIME_OUT) {
			return;
		}
		if ($desc = $trouver_table($table)) {
			$desc_collate = _sqlite_remplacements_definitions_table($desc['field']);
			if ($d = array_diff($desc['field'], $desc_collate)) {
				spip_log("Table $table COLLATE incorrects", "maj");

				// cas particulier spip_urls :
				// supprimer les doublons avant conversion sinon echec (on garde les urls les plus recentes)
				if ($table == 'spip_urls') {
					// par date DESC pour conserver les urls les plus recentes
					$data = sql_allfetsel("*", "spip_urls", '', '', 'date DESC');
					$urls = array();
					foreach ($data as $d) {
						$key = $d['id_parent'] . "::" . strtolower($d['url']);
						if (!isset($urls[$key])) {
							$urls[$key] = true;
						} else {
							spip_log("Suppression doublon dans spip_urls avant conversion : " . serialize($d),
								"maj." . _LOG_INFO_IMPORTANTE);
							sql_delete("spip_urls", "id_parent=" . sql_quote($d['id_parent']) . " AND url=" . sql_quote($d['url']));
						}
					}
				}
				foreach ($desc['field'] as $field => $type) {
					if ($desc['field'][$field] !== $desc_collate[$field]) {
						spip_log("Conversion COLLATE table $table", "maj." . _LOG_INFO_IMPORTANTE);
						sql_alter("table $table change $field $field " . $desc_collate[$field]);
						$trouver_table('');
						$new_desc = $trouver_table($table);
						spip_log("Apres conversion $table : " . var_export($new_desc['field'], true),
							"maj." . _LOG_INFO_IMPORTANTE);
						continue 2; // inutile de continuer pour cette table : un seul alter remet tout a jour en sqlite
					}
				}
			}
		}
	}

	// forcer le vidage de cache
	$trouver_table('');

}


$GLOBALS['maj'][19236] = array(
	array('sql_updateq', 'spip_meta', array('impt' => 'oui'), "nom='version_installee'"), // version base principale
	array('sql_updateq', 'spip_meta', array('impt' => 'oui'), "nom LIKE '%_base_version'"),  // version base plugins
	array('maj_collation_sqlite'),
);

$GLOBALS['maj'][19268] = array(
	array('supprimer_toutes_sessions'),
);

/**
 * Supprime toutes les sessions des auteurs
 *
 * Obligera tous les auteurs à se reconnecter !
 **/
function supprimer_toutes_sessions() {
	spip_log("supprimer sessions auteur");
	if ($dir = opendir(_DIR_SESSIONS)) {
		while (($f = readdir($dir)) !== false) {
			spip_unlink(_DIR_SESSIONS . $f);
			if (time() >= _TIME_OUT) {
				return;
			}
		}
	}
}
