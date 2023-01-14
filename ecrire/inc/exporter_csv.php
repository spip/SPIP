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
 * @param int $nb
 * @param array $ligne
 * @param string $delim
 * @param string|null $importer_charset
 *     Si défini exporte dans le charset indiqué
 * @param callable $callback
 * @return string
 */
function exporter_csv_ligne_numerotee($nb, $ligne, $delim = ',', $importer_charset = null, ?callable $callback = null) {
	if ($callback) {
		$ligne = $callback($nb, $ligne, $delim, $importer_charset);
	}
	$output = join($delim, array_map('exporter_csv_champ', $ligne)) . "\r\n";
	if ($importer_charset) {
		$output = str_replace('’', '\'', $output);
		$output = unicode2charset(html2unicode(charset2unicode($output)), $importer_charset);
	}
	return $output;
}

/**
 * @deprecated 4.0
 *
 * @param $ligne
 * @param string $delim
 * @param null $importer_charset
 * @return string
 */
function exporter_csv_ligne($ligne, $delim = ',', $importer_charset = null) {
	return exporter_csv_ligne_numerotee(null, $ligne, $delim, $importer_charset);
}

/**
 * Exporte une ressource sous forme de fichier CSV
 *
 * La ressource peut etre un tableau ou une resource SQL issue d'une requete
 * Si elle n'est pas précisée dans les options, l'extension est choisie en fonction du délimiteur :
 * - si on utilise ',' c'est un vrai csv avec extension csv
 * - si on utilise ';' ou tabulation c'est pour E*cel, et on exporte en iso-truc, avec une extension .xls
 *
 * @uses exporter_csv_ligne()
 *
 * @param string $titre
 *   titre utilise pour nommer le fichier
 * @param array|resource $resource
 * @param array $options
 *   string $extension : `csv` | `xls`, choisie par défaut en fonction du délimiteur
 *   string $delim : delimiteur : `,` | `;` | `\t` | `TAB`
 *   array $entetes : tableau d'en-tetes pour nommer les colonnes (genere la premiere ligne)
 *   bool $envoyer : pour envoyer le fichier exporte (permet le telechargement)
 *   string $charset : charset de l'export si different de celui du site
 *   callable callback : fonction callback a appeler sur chaque ligne pour mettre en forme/completer les donnees
 * @return string
 */
function inc_exporter_csv_dist($titre, $resource, $options = []) {

	// support ancienne syntaxe
	// inc_exporter_csv_dist($titre, $resource, $delim = ', ', $entetes = null, $envoyer = true)
	if (is_string($options)) {
		$args = func_get_args();
		$options = [];
		foreach ([2 => 'delim', 3 => 'entetes', 4 => 'envoyer'] as $k => $option) {
			if (!empty($args[$k])) {
				$options[$option] = $args[$k];
			}
		}
	}

	$default_options = [
		'extension' => null, // choix auto par défaut
		'delim' => ',',
		'entetes' => null,
		'envoyer' => true,
		'charset' => null,
		'callback' => null,
	];
	$options = array_merge($default_options, $options);

	$filename = preg_replace(',[^-_\w]+,', '_', translitteration(textebrut(typo($titre))));

	// Délimiteur : normaliser
	if ($options['delim'] == 'TAB') {
		$options['delim'] = "\t";
	}
	if (!in_array($options['delim'], [',', ';', "\t"])) {
		$options['delim'] = ',';
	}

	// Extension : celle indiquée en option, sinon choisie selon le délimiteur
	$options['extension'] = (in_array($options['extension'], ['csv', 'xls']) ? $options['extension'] : null);
	$extension = $options['extension'] ?? ($options['delim'] === ',' ? 'csv' : 'xls');

	// Charset : celui indiqué en option, sinon celui compatible excel si nécessaire, sinon celui du site
	// Excel n'accepte pas l'utf-8 ni les entites html... on transcode tout ce qu'on peut
	$charset_site = $GLOBALS['meta']['charset'];
	$charset_excel = ($extension === 'xls' ? 'iso-8859-1' : null);
	$charset = $options['charset'] ?? $charset_excel ?? $charset_site;
	$importer_charset = (($charset === $charset_site) ? null : $charset);

	$filename = "$filename.$extension";

	$output = '';
	$nb = 0;
	if (!empty($options['entetes']) and is_array($options['entetes'])) {
		$output = exporter_csv_ligne_numerotee($nb, $options['entetes'], $options['delim'], $importer_charset, $options['callback']);
	}
	// les donnees commencent toujours a la ligne 1, qu'il y ait ou non des entetes
	$nb++;

	if ($options['envoyer']) {
		$disposition = ($options['envoyer'] === 'attachment' ? 'attachment' : 'inline');
		header("Content-Type: text/comma-separated-values; charset=$charset");
		header("Content-Disposition: $disposition; filename=$filename");

		// Vider tous les tampons
		$level = @ob_get_level();
		while ($level--) {
			@ob_end_flush();
		}
	}

	// si envoyer=='attachment' on passe par un fichier temporaire
	// sinon on ecrit directement sur stdout
	if ($options['envoyer'] and $options['envoyer'] !== 'attachment') {
		$fichier = 'php://output';
	}
	else {
	$fichier = sous_repertoire(_DIR_CACHE, 'export') . $filename;
	}

	$fp = fopen($fichier, 'w');
	$length = fwrite($fp, $output);

	while ($row = is_array($resource) ? array_shift($resource) : sql_fetch($resource)) {
		$output = exporter_csv_ligne_numerotee($nb, $row, $options['delim'], $importer_charset, $options['callback']);
		$length += fwrite($fp, $output);
		$nb++;
	}
	fclose($fp);

	if ($options['envoyer']) {
		if ($options['envoyer'] === 'attachment') {
			header("Content-Length: $length");
			readfile($fichier);
		}
		// si on a envoye inline, c'est deja tout bon
		exit;
	}

	return $fichier;
}
