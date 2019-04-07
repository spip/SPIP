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

/**
 * Gestion d'export de données au format CSV
 *
 * @package SPIP\Core\CSV\Export
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/charsets');
include_spip('inc/filtres');
include_spip('inc/texte');

/**
 * Exporter un champ pour un export CSV : pas de retour a la ligne,
 * et echapper les guillements par des doubles guillemets
 *
 * @param string $champ
 * @return string
 */
function exporter_csv_champ($champ) {
	#$champ = str_replace("\r", "\n", $champ);
	#$champ = preg_replace(",[\n]+,ms", "\n", $champ);
	#$champ = str_replace("\n", ", ", $champ);
	$champ = preg_replace(',[\s]+,ms', ' ', $champ);
	$champ = str_replace('"', '""', $champ);

	return '"' . $champ . '"';
}

/**
 * Exporter une ligne complete au format CSV, avec delimiteur fourni
 *
 * @uses exporter_csv_champ()
 *
 * @param array $ligne
 * @param string $delim
 * @param string|null $importer_charset
 *     Si défini exporte dans le charset indiqué
 * @return string
 */
function exporter_csv_ligne($ligne, $delim = ', ', $importer_charset = null) {
	$output = join($delim, array_map('exporter_csv_champ', $ligne)) . "\r\n";
	if ($importer_charset) {
		$output = unicode2charset(html2unicode(charset2unicode($output)), $importer_charset);
	}

	return $output;
}

/**
 * Exporte une ressource sous forme de fichier CSV
 *
 * La ressource peut etre un tableau ou une resource SQL issue d'une requete
 * L'extension est choisie en fonction du delimiteur :
 * - si on utilise ',' c'est un vrai csv avec extension csv
 * - si on utilise ';' ou tabulation c'est pour E*cel, et on exporte en iso-truc, avec une extension .xls
 *
 * @uses exporter_csv_ligne()
 *
 * @param string $titre
 *   titre utilise pour nommer le fichier
 * @param array|resource $resource
 * @param string $delim
 *   delimiteur
 * @param array $entetes
 *   tableau d'en-tetes pour nommer les colonnes (genere la premiere ligne)
 * @param bool $envoyer
 *   pour envoyer le fichier exporte (permet le telechargement)
 * @return string
 */
function inc_exporter_csv_dist($titre, $resource, $delim = ', ', $entetes = null, $envoyer = true) {

	$filename = preg_replace(',[^-_\w]+,', '_', translitteration(textebrut(typo($titre))));

	if ($delim == 'TAB') {
		$delim = "\t";
	}
	if (!in_array($delim, array(',', ';', "\t"))) {
		$delim = ',';
	}

	$charset = $GLOBALS['meta']['charset'];
	$importer_charset = null;
	if ($delim == ',') {
		$extension = 'csv';
	} else {
		$extension = 'xls';
		# Excel n'accepte pas l'utf-8 ni les entites html... on transcode tout ce qu'on peut
		$importer_charset = $charset = 'iso-8859-1';
	}
	$filename = "$filename.$extension";

	if ($entetes and is_array($entetes) and count($entetes)) {
		$output = exporter_csv_ligne($entetes, $delim, $importer_charset);
	}

	// on passe par un fichier temporaire qui permet de ne pas saturer la memoire
	// avec les gros exports
	$fichier = sous_repertoire(_DIR_CACHE, 'export') . $filename;
	$fp = fopen($fichier, 'w');
	$length = fwrite($fp, $output);

	while ($row = is_array($resource) ? array_shift($resource) : sql_fetch($resource)) {
		$output = exporter_csv_ligne($row, $delim, $importer_charset);
		$length += fwrite($fp, $output);
	}
	fclose($fp);

	if ($envoyer) {
		header("Content-Type: text/comma-separated-values; charset=$charset");
		header("Content-Disposition: attachment; filename=$filename");
		//non supporte
		//Header("Content-Type: text/plain; charset=$charset");
		header("Content-Length: $length");
		ob_clean();
		flush();
		readfile($fichier);
	}

	return $fichier;
}
