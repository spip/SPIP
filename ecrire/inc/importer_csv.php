<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2019                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/charsets');

/**
 * Based on an example by ramdac at ramdac dot org
 * Returns a multi-dimensional array from a CSV file optionally using the
 * first row as a header to create the underlying data as associative arrays.
 *
 * @param string $file Filepath including filename
 * @param bool $head Use first row as header.
 * @param string $delim Specify a delimiter other than a comma.
 * @param int $len Line length to be passed to fgetcsv
 * @return array or false on failure to retrieve any rows.
 */

/**
 * Importer le charset d'une ligne
 *
 * @param string $texte
 * @return array
 */
function importer_csv_importcharset($texte) {
	// le plus frequent, en particulier avec les trucs de ms@@@
	$charset_source = 'iso-8859-1';
	// mais open-office sait faire mieux, donc mefiance !
	if (is_utf8($texte)) {
		$charset_source = 'utf-8';
	}

	return importer_charset($texte, $charset_source);
}

/**
 * enlever les accents des cles presentes dans le head,
 * sinon ca pose des problemes ...
 *
 * @param string $key
 * @return string
 */
function importer_csv_nettoie_key($key) {
	return translitteration($key);
}

/**
 * Lit un fichier csv et retourne un tableau
 * si $head est true, la premiere ligne est utilisee en header
 * pour generer un tableau associatif
 *
 * @param string $file
 * @param bool $head
 * @param string $delim
 * @param string $enclos
 * @param int $len
 * @return array
 */
function inc_importer_csv_dist($file, $head = false, $delim = ", ", $enclos = '"', $len = 10000) {
	$return = false;
	if (@file_exists($file)
		and $handle = fopen($file, "r")
	) {
		if ($head) {
			$header = fgetcsv($handle, $len, $delim, $enclos);
			if ($header) {
				$header = array_map('importer_csv_importcharset', $header);
				$header = array_map('importer_csv_nettoie_key', $header);
				$header_type = array();
				foreach ($header as $heading) {
					if (!isset($header_type[$heading])) {
						$header_type[$heading] = "scalar";
					} else {
						$header_type[$heading] = "array";
					}
				}
			}
		}
		while (($data = fgetcsv($handle, $len, $delim, $enclos)) !== false) {
			$data = array_map('importer_csv_importcharset', $data);
			if ($head and isset($header)) {
				$row = array();
				foreach ($header as $key => $heading) {
					if ($header_type[$heading] == "array") {
						if (!isset($row[$heading])) {
							$row[$heading] = array();
						}
						if (isset($data[$key]) and strlen($data[$key])) {
							$row[$heading][] = $data[$key];
						}
					} else {
						$row[$heading] = (isset($data[$key])) ? $data[$key] : '';
					}
				}
				$return[] = $row;
			} else {
				$return[] = $data;
			}
		}
	}

	return $return;
}
