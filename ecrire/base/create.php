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
function base_determine_autoinc($table,$desc=array()){
	if ($t=lister_tables_principales() AND isset($t[$table]))
		$autoinc = true;
	elseif ($t=lister_tables_auxiliaires() AND isset($t[$table]))
		$autoinc = false;
	else {
		// essayer de faire au mieux !
		$autoinc = (isset($desc['key']['PRIMARY KEY'])
						AND strpos($desc['key']['PRIMARY KEY'],',')===false
						AND strpos($desc['field'][$desc['key']['PRIMARY KEY']],'default')===false);
	}
	return $autoinc;
}

/**
 * Creer une table,
 * ou ajouter les champs manquants si elle existe deja
 *
 * http://doc.spip.org/@creer_ou_upgrader_table
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
function creer_ou_upgrader_table($table,$desc,$autoinc,$upgrade=false,$serveur='') {
	$sql_desc = $upgrade ? sql_showtable($table,true,$serveur) : false;
	if (!$sql_desc) {
		if ($autoinc==='auto')
			$autoinc = base_determine_autoinc($table,$desc);
		sql_create($table, $desc['field'], $desc['key'], $autoinc, false, $serveur);
	}
	else {
		// ajouter les champs manquants
		// on ne supprime jamais les champs, car c'est dangereux
		// c'est toujours a faire manuellement
		$last = '';
		foreach($desc['field'] as $field=>$type){
			if (!isset($sql_desc['field'][$field]))
				sql_alter("TABLE $table ADD $field $type".($last?" AFTER $last":""),$serveur);
			$last = $field;
		}
		foreach($desc['key'] as $key=>$type){
			// Ne pas oublier les cas des cles non nommees dans la declaration et qui sont retournees
			// par le showtable sous la forme d'un index de tableau "KEY $type" et non "KEY"
			if (!isset($sql_desc['key'][$key]) AND !isset($sql_desc['key']["$key $type"]))
				sql_alter("TABLE $table ADD $key ($type)",$serveur);
			$last = $field;
		}

	}
}

/**
 * Creer ou mettre a jour un ensemble de tables
 * en fonction du flag $up
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
function alterer_base($tables_inc, $tables_noinc, $up=false, $serveur='')
{
	if ($up === false) {
		$old = false;
		$up = array();
	} else {
		$old = true;
		if (!is_array($up)) $up = array($up);
	}
	foreach($tables_inc as $k => $v)
		if (!$old OR in_array($k, $up))
			creer_ou_upgrader_table($k,$v,true,$old,$serveur);

	foreach($tables_noinc as $k => $v)
		if (!$old OR in_array($k, $up))
			creer_ou_upgrader_table($k,$v,false,$old,$serveur);
}

/**
 * Creer une base de donnee
 * a partir des tables principales et auxiliaires
 *
 * http://doc.spip.org/@creer_base
 *
 * @param string $serveur
 * @return void
 */
function creer_base($serveur='') {

	// Note: les mises a jour reexecutent ce code pour s'assurer
	// de la conformite de la base
	// pas de panique sur  "already exists" et "duplicate entry" donc.

	alterer_base(lister_tables_principales(),
		     lister_tables_auxiliaires(),
		     false,
		     $serveur);
}

/**
 * Mettre a jour une liste de tables,
 * fonction facilitatrice utilisee pour les maj de base
 * dans les plugins
 *
 * @param array $upgrade_tables
 * @param string $serveur
 * @return void
 */
function maj_tables($upgrade_tables=array(),$serveur=''){
	alterer_base(lister_tables_principales(),
		     lister_tables_auxiliaires(),
		     $upgrade_tables,
		     $serveur);
}

?>
