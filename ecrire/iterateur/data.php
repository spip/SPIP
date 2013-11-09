<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * creer une boucle sur un iterateur DATA
 * annonce au compilo les "champs" disponibles
 *
 * @param  $b
 * @return
 */
function iterateur_DATA_dist($b) {
	$b->iterateur = 'DATA'; # designe la classe d'iterateur
	$b->show = array(
		'field' => array(
			'cle' => 'STRING',
			'valeur' => 'STRING',
			'*' => 'ALL' // Champ joker *
		)
	);
	$b->select[] = '.valeur';
	return $b;
}

/*
 * Fonctions de transformation donnee => tableau
 */

/**
 * file -> tableau
 *
 * @param  string $u
 * @return array
 */
function inc_file_to_array_dist($u) {
	return preg_split('/\r?\n/', $u);
}

/**
 * plugins -> tableau
 * @return unknown
 */
function inc_plugins_to_array_dist() {
	include_spip('inc/plugin');
	return liste_chemin_plugin_actifs();
}

/**
 * xml -> tableau
 * @param  string $u
 * @return array
 */
function inc_xml_to_array_dist($u) {
	return @ObjectToArray(new SimpleXmlIterator($u));
}

/**
 * yql -> tableau
 * @throws Exception
 * @param  string $u
 * @return array|bool
 */
function inc_yql_to_array_dist($u) {
	define('_YQL_ENDPOINT', 'http://query.yahooapis.com/v1/public/yql?&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys&q=');
	$v = recuperer_page($url = _YQL_ENDPOINT.urlencode($u).'&format=json');
	$w = json_decode($v);
	if (!$w) {
		throw new Exception('YQL: r&#233;ponse vide ou mal form&#233;e');
		return false;
	}
	return (array) $w;
}

/**
 * sql -> tableau
 * @param string $u
 * @return array|bool
 */
function inc_sql_to_array_dist($u) {
	# sortir le connecteur de $u
	preg_match(',^(?:(\w+):)?(.*)$,S', $u, $v);
	$serveur = (string) $v[1];
	$req = trim($v[2]);
	if ($s = sql_query($req, $serveur)) {
		$r = array();
		while ($t = sql_fetch($s))
			$r[] = $t;
		return $r;
	}
	return false;
}

/**
 * json -> tableau
 * @param string $u
 * @return array|bool
 */
function inc_json_to_array_dist($u) {
	if (is_array($json = json_decode($u))
	OR is_object($json))
		return (array) $json;
}

/**
 * csv -> tableau
 * @param string $u
 * @return array|bool
 */
function inc_csv_to_array_dist($u) {
	include_spip('inc/csv');
	list($entete,$csv) = analyse_csv($u);
	array_unshift($csv,$entete);

	include_spip('inc/charsets');
	foreach ($entete as $k => $v) {
		$v = strtolower(preg_replace(',\W+,', '_', translitteration($v)));
		foreach ($csv as &$item)
			$item[$v] = &$item[$k];
	}
	return $csv;
}

/**
 * RSS -> tableau
 * @param string $u
 * @return array|bool
 */
function inc_rss_to_array_dist($u) {
	include_spip('inc/syndic');
	if (is_array($rss = analyser_backend($u)))
		$tableau = $rss;
	return $tableau;
}

/**
 * atom, alias de rss -> tableau
 * @param string $u
 * @return array|bool
 */
function inc_atom_to_array_dist($u) {
	$g = charger_fonction('rss_to_array', 'inc');
	return $g($u);
}

/**
 * glob -> tableau
 * lister des fichiers selon un masque, pour la syntaxe cf php.net/glob
 * @param string $u
 * @return array|bool
 */
function inc_glob_to_array_dist($u) {
	return (array) glob($u,
		GLOB_MARK | GLOB_NOSORT | GLOB_BRACE
	);
}

/**
 * YAML -> tableau
 * @param string $u
 * @return bool|array
 * @throws Exception
 */
function inc_yaml_to_array_dist($u){
	include_spip('inc/yaml-mini');
	if (!function_exists("yaml_decode")){
		throw new Exception('YAML: impossible de trouver la fonction yaml_decode');
		return false;
	}

	return yaml_decode($u);
}


/**
 * pregfiles -> tableau
 * lister des fichiers a partir d'un dossier de base et selon une regexp.
 * pour la syntaxe cf la fonction spip preg_files
 * @param string $dir
 * @param string $regexp
 * @param int $limit
 * @return array|bool
 */
function inc_pregfiles_to_array_dist($dir, $regexp=-1, $limit=10000) {
	return (array) preg_files($dir, $regexp, $limit);
}

/**
 * ls -> tableau
 * ls : lister des fichiers selon un masque glob
 * et renvoyer aussi leurs donnees php.net/stat
 * @param string $u
 * @return array|bool
 */
function inc_ls_to_array_dist($u) {
	$glob = charger_fonction('glob_to_array', 'inc');
	$a = $glob($u);
	foreach ($a as &$v) {
		$b = (array) @stat($v);
		foreach ($b as $k => $ignore)
			if (is_numeric($k)) unset($b[$k]);
		$b['file'] = basename($v);
		$v = array_merge(
			pathinfo($v),
			$b
		);
	}
	return $a;
}

/**
 * Object -> tableau
 * @param Object $object
 * @return array|bool
 */
function ObjectToArray($object){
	$xml_array = array();
	for( $object->rewind(); $object->valid(); $object->next() ) {
		if(array_key_exists($key = $object->key(), $xml_array)){
			$key .= '-'.uniqid();
		}
		$vars = get_object_vars($object->current());
		if (isset($vars['@attributes']))
			foreach($vars['@attributes'] as $k => $v)
			$xml_array[$key][$k] = $v;
		if($object->hasChildren()){
			$xml_array[$key][] = ObjectToArray(
				$object->current());
		}
		else{
			$xml_array[$key][] = strval($object->current());
		}
	}
	return $xml_array;
}
