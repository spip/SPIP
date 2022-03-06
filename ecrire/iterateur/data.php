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

use Spip\Core\Boucle;

/**
 * Gestion de l'itérateur DATA
 *
 * @package SPIP\Core\Iterateur\DATA
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

if (!defined('_DATA_SOURCE_MAX_SIZE')) {
	define('_DATA_SOURCE_MAX_SIZE', 2 * 1_048_576);
}


/**
 * Créer une boucle sur un itérateur DATA
 *
 * Annonce au compilateur les "champs" disponibles, c'est à dire
 * 'cle', 'valeur' et '*' (tout nom possible).
 *
 * On ne peut effectivement pas connaître à la compilation la structure
 * des données qui seront obtenues. On indique donc au compilateur que
 * toute balise utilisée dans la boucle est possiblement un champ
 * des données reçues.
 *
 * @param Boucle $b
 *     Description de la boucle
 * @return Boucle
 *     Description de la boucle complétée des champs
 */
function iterateur_DATA_dist($b) {
	$b->iterateur = 'DATA'; # designe la classe d'iterateur
	$b->show = [
		'field' => [
			'cle' => 'STRING',
			'valeur' => 'STRING',
			'*' => 'ALL' // Champ joker *
		]
	];
	$b->select[] = '.valeur';

	return $b;
}

/*
 * Fonctions de transformation donnee => tableau
 */

/**
 * file -> tableau
 *
 * @param string $data
 * @return array
 */
function inc_file_to_array_dist($data) {
	return preg_split('/\r?\n/', $data);
}

/**
 * plugins -> tableau
 *
 * @return array
 */
function inc_plugins_to_array_dist() {
	include_spip('inc/plugin');

	return liste_chemin_plugin_actifs();
}

/**
 * xml -> tableau
 *
 * @param string $data
 * @return array
 */
function inc_xml_to_array_dist($data) {
	return @XMLObjectToArray(new SimpleXmlIterator($data));
}

/**
 *
 * object -> tableau
 *
 * @param object $object The object to convert
 * @return array
 *
 */
function inc_object_to_array($object) {
	if (!is_object($object) && !is_array($object)) {
		return $object;
	}
	if (is_object($object)) {
		$object = get_object_vars($object);
	}

	return array_map('inc_object_to_array', $object);
}

/**
 * sql -> tableau
 *
 * @param string $data
 * @return array|bool
 */
function inc_sql_to_array_dist($data) {
	# sortir le connecteur de $data
	preg_match(',^(?:(\w+):)?(.*)$,Sm', $data, $v);
	$serveur = (string)$v[1];
	$req = trim($v[2]);
	if ($s = sql_query($req, $serveur)) {
		$r = [];
		while ($t = sql_fetch($s)) {
			$r[] = $t;
		}

		return $r;
	}

	return false;
}

/**
 * json -> tableau
 *
 * @param string $data
 * @return array|bool
 */
function inc_json_to_array_dist($data) {
	try {
		$json = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
	} catch (JsonException $e) {
		$json = null;
		spip_log('Failed to parse Json data : ' . $e->getMessage(), _LOG_INFO);
	}
	return is_array($json) ? (array) $json : [];
}

/**
 * csv -> tableau
 *
 * @param string $data
 * @return array|bool
 */
function inc_csv_to_array_dist($data) {
	include_spip('inc/csv');
	[$entete, $csv] = analyse_csv($data);
	array_unshift($csv, $entete);

	include_spip('inc/charsets');
	$i = 1;
	foreach ($entete as $k => $v) {
		if (trim($v) == '') {
			$v = 'col' . $i;
		} // reperer des eventuelles cases vides
		if (is_numeric($v) and $v < 0) {
			$v = '__' . $v;
		} // ne pas risquer d'ecraser une cle numerique
		if (is_numeric($v)) {
			$v = '_' . $v;
		} // ne pas risquer d'ecraser une cle numerique
		$v = strtolower(preg_replace(',\W+,', '_', translitteration($v)));
		foreach ($csv as &$item) {
			$item[$v] = &$item[$k];
		}
		$i++;
	}

	return $csv;
}

/**
 * RSS -> tableau
 *
 * @param string $data
 * @return array|bool
 */
function inc_rss_to_array_dist($data) {
	$tableau = null;
	include_spip('inc/syndic');
	if (is_array($rss = analyser_backend($data))) {
		$tableau = $rss;
	}

	return $tableau;
}

/**
 * atom, alias de rss -> tableau
 *
 * @param string $data
 * @return array|bool
 */
function inc_atom_to_array_dist($data) {
	$rss_to_array = charger_fonction('rss_to_array', 'inc');

	return $rss_to_array($data);
}

/**
 * glob -> tableau
 * lister des fichiers selon un masque, pour la syntaxe cf php.net/glob
 *
 * @param string $data
 * @return array|bool
 */
function inc_glob_to_array_dist($data) {
	$a = glob(
		$data,
		GLOB_MARK | GLOB_NOSORT | GLOB_BRACE
	);

	return $a ?: [];
}

/**
 * YAML -> tableau
 *
 * @param string $data
 * @return bool|array
 * @throws Exception
 */
function inc_yaml_to_array_dist($data) {
	include_spip('inc/yaml-mini');
	if (!function_exists('yaml_decode')) {
		throw new Exception('YAML: impossible de trouver la fonction yaml_decode');

		return false;
	}

	return yaml_decode($data);
}


/**
 * pregfiles -> tableau
 * lister des fichiers a partir d'un dossier de base et selon une regexp.
 * pour la syntaxe cf la fonction spip preg_files
 *
 * @param string $dir
 * @param string $regexp
 * @param int $limit
 * @return array|bool
 */
function inc_pregfiles_to_array_dist($dir, $regexp = -1, $limit = 10000) {
	return (array)preg_files($dir, $regexp, $limit);
}

/**
 * ls -> tableau
 * ls : lister des fichiers selon un masque glob
 * et renvoyer aussi leurs donnees php.net/stat
 *
 * @param string $data
 * @return array|bool
 */
function inc_ls_to_array_dist($data) {
	$glob_to_array = charger_fonction('glob_to_array', 'inc');
	$a = $glob_to_array($data);
	foreach ($a as &$v) {
		$b = (array)@stat($v);
		foreach ($b as $k => $ignore) {
			if (is_numeric($k)) {
				unset($b[$k]);
			}
		}
		$b['file'] = preg_replace('`/$`', '', $v) ;
		$v = array_merge(
			pathinfo($v),
			$b
		);
	}

	return $a;
}

/**
 * Object -> tableau
 *
 * @param Object $object
 * @return array|bool
 */
function XMLObjectToArray($object) {
	$xml_array = [];
	for ($object->rewind(); $object->valid(); $object->next()) {
		if (array_key_exists($key = $object->key(), $xml_array)) {
			$key .= '-' . uniqid();
		}
		$vars = get_object_vars($object->current());
		if (isset($vars['@attributes'])) {
			foreach ($vars['@attributes'] as $k => $v) {
				$xml_array[$key][$k] = $v;
			}
		}
		if ($object->hasChildren()) {
			$xml_array[$key][] = XMLObjectToArray(
				$object->current()
			);
		} else {
			$xml_array[$key][] = strval($object->current());
		}
	}

	return $xml_array;
}
