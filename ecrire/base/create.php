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
 * Création ou mise à jour des tables
 *
 * @package SPIP\Core\Installation
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/acces');
include_spip('base/objets');
include_spip('base/typedoc');
include_spip('base/abstract_sql');

/**
 * Determiner le flag autoinc pour une table
 * en fonction de si c'est une table principale
 *
 * @param string $table
 * @param array $desc
 * @return bool
 */
function base_determine_autoinc($table, $desc = []) {
	if ($t = lister_tables_principales() and isset($t[$table])) {
		$autoinc = true;
	} elseif ($t = lister_tables_auxiliaires() and isset($t[$table])) {
		$autoinc = false;
	} else {
		// essayer de faire au mieux !
		$autoinc = (isset($desc['key']['PRIMARY KEY'])
			and strpos($desc['key']['PRIMARY KEY'], ',') === false
			and strpos($desc['field'][$desc['key']['PRIMARY KEY']], 'default') === false);
	}

	return $autoinc;
}

/**
 * Créer une table,
 * ou ajouter les champs manquants si elle existe déjà
 *
 * @param string $table
 * @param array $desc
 * @param bool|string $autoinc
 *   'auto' pour detecter automatiquement si le champ doit etre autoinc ou non
 *   en fonction de la table
 * @param bool $upgrade
 * @param string $serveur
 * @return void
 */
function creer_ou_upgrader_table($table, $desc, $autoinc, $upgrade = false, $serveur = '') {
	#spip_log("creer_ou_upgrader_table table=$table autoinc=$autoinc upgrade=$upgrade","dbinstall"._LOG_INFO_IMPORTANTE);
	$sql_desc = $upgrade ? sql_showtable($table, true, $serveur) : false;
	#if (!$sql_desc) $sql_desc = false;
	#spip_log("table=$table sql_desc:$sql_desc","dbinstall"._LOG_INFO_IMPORTANTE);
	if (!$sql_desc) {
		if ($autoinc === 'auto') {
			$autoinc = base_determine_autoinc($table, $desc);
		}
		#spip_log("sql_create $table autoinc=$autoinc","dbinstall"._LOG_INFO_IMPORTANTE);
		if (isset($desc['field']) and isset($desc['key'])) {
			sql_create($table, $desc['field'], $desc['key'], $autoinc, false, $serveur);
		}
		// verifier la bonne installation de la table (php-fpm es-tu la ?)
		$sql_desc = sql_showtable($table, true, $serveur);
		#if (!$sql_desc) $sql_desc = false;
		#spip_log("Resultat table=$table sql_desc:$sql_desc","dbinstall"._LOG_INFO_IMPORTANTE);
		if (!$sql_desc) {
			// on retente avec un sleep ?
			sleep(1);
			sql_create($table, $desc['field'], $desc['key'], $autoinc, false, $serveur);
			$sql_desc = sql_showtable($table, true, $serveur);
			#if (!$sql_desc) $sql_desc = false;
			#spip_log("Resultat table=$table sql_desc:$sql_desc","dbinstall"._LOG_INFO_IMPORTANTE);
			if (!$sql_desc) {
				spip_log("Echec creation table $table", 'maj' . _LOG_CRITIQUE);
			}
		}
	} else {
		#spip_log("sql_alter $table ... (on s'en fiche)","dbinstall"._LOG_INFO_IMPORTANTE);
		// ajouter les champs manquants
		// on ne supprime jamais les champs, car c'est dangereux
		// c'est toujours a faire manuellement
		$last = '';
		if (isset($desc['field'])) {
			foreach ($desc['field'] as $field => $type) {
				if (!isset($sql_desc['field'][$field])) {
					sql_alter("TABLE $table ADD $field $type" . ($last ? " AFTER $last" : ''), $serveur);
				}
				$last = $field;
			}
		}
		if (isset($desc['key'])) {
			foreach ($desc['key'] as $key => $type) {
				// Ne pas oublier les cas des cles non nommees dans la declaration et qui sont retournees
				// par le showtable sous la forme d'un index de tableau "KEY $type" et non "KEY"
				if (!isset($sql_desc['key'][$key]) and !isset($sql_desc['key']["$key $type"])) {
					sql_alter("TABLE $table ADD $key ($type)", $serveur);
				}
				$last = $field;
			}
		}
	}
}

/**
 * Creer ou mettre à jour un ensemble de tables
 * en fonction du flag `$up`
 *
 * @uses creer_ou_upgrader_table()
 *
 * @param array $tables_inc
 *   tables avec autoincrement sur la cle primaire
 * @param  $tables_noinc
 *   tables sans autoincrement sur la cle primaire
 * @param bool|array $up
 *   upgrader (true) ou creer (false)
 *   si un tableau de table est fournie, seules l'intersection de ces tables
 *   et des $tables_inc / $tables_noinc seront traitees
 * @param string $serveur
 *   serveur sql
 * @return void
 */
function alterer_base($tables_inc, $tables_noinc, $up = false, $serveur = '') {
	if ($up === false) {
		$old = false;
		$up = [];
	} else {
		$old = true;
		if (!is_array($up)) {
			$up = [$up];
		}
	}
	foreach ($tables_inc as $k => $v) {
		if (!$old or in_array($k, $up)) {
			creer_ou_upgrader_table($k, $v, true, $old, $serveur);
		}
	}

	foreach ($tables_noinc as $k => $v) {
		if (!$old or in_array($k, $up)) {
			creer_ou_upgrader_table($k, $v, false, $old, $serveur);
		}
	}
}

/**
 * Créer une base de données
 * à partir des tables principales et auxiliaires
 *
 * Lorsque de nouvelles tables ont été déclarées, cette fonction crée les tables manquantes.
 * mais ne crée pas des champs manquant d'une table déjà présente.
 * Pour cela, c’est `maj_tables()` qu’il faut appeler.
 *
 * @api
 * @see  maj_tables()
 * @uses alterer_base()
 *
 * @param string $serveur
 * @return void
 */
function creer_base($serveur = '') {

	// Note: les mises a jour reexecutent ce code pour s'assurer
	// de la conformite de la base
	// pas de panique sur  "already exists" et "duplicate entry" donc.

	alterer_base(
		lister_tables_principales(),
		lister_tables_auxiliaires(),
		false,
		$serveur
	);
}

/**
 * Mettre à jour une liste de tables
 *
 * Fonction facilitatrice utilisée pour les maj de base
 * dans les plugins.
 *
 * Elle permet de créer les champs manquants d'une table déjà présente.
 *
 * @api
 * @see  creer_base()
 * @uses alterer_base()
 *
 * @param array $upgrade_tables
 * @param string $serveur
 * @return void
 */
function maj_tables($upgrade_tables = [], $serveur = '') {
	alterer_base(
		lister_tables_principales(),
		lister_tables_auxiliaires(),
		$upgrade_tables,
		$serveur
	);
}
