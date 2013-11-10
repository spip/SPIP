<?php
/**
 * Gestion du téléporteur HTTP \ Zip. 
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Teleporteur
 */
 

/**
 * Déballer le fichier au format zip dans le répertoire $dest
 * en utilisant le dossier temporaire $tmp si besoin
 *
 * @param string $archive
 *     Chemin du fichier zip
 * @param string $dest
 *     Répertoire où on veut décompresser
 * @param string $tmp
 *     Répertoire de stockage temporaire
 * @return bool|string
 *     Répertoire où a été décompressé le zip, false sinon.
 */
function teleporter_http_deballe_zip_dist($archive, $dest, $tmp){
	$status = teleporter_http_charger_zip(
		array(
			'archive' => $archive, // normalement l'url source mais on l'a pas ici
			'fichier' => $archive,
			'dest' => $dest,
			'tmp' => $tmp,
			'extract' => true,
			'root_extract' => true, # extraire a la racine de dest
		)
	);
	// le fichier .zip est la et bien forme
	if (is_array($status)
	  AND is_dir($status['target'])) {
		return $status['target'];
	}
	// fichier absent
	else if ($status == -1) {
		spip_log("dezip de $archive impossible : fichier absent","teleport"._LOG_ERREUR);
		return false;
	}
	// fichier la mais pas bien dezippe
	else {
		spip_log("probleme lors du dezip de $archive","teleport"._LOG_ERREUR);
		return false;
	}
}


/**
 * Charger un zip à partir d'un tableau d'options descriptives
 *
 * @param array $quoi
 *     Tableau d'options
 * @return array|bool|int|string
 *     En cas de réussite, Tableau décrivant le zip, avec les index suivant :
 *     - files : la liste des fichiers présents dans le zip,
 *     - size : la taille décompressée
 *     - compressed_size : la taille compressée
 *     - dirname : répertoire où les fichiers devront être décompréssés
 *     - tmpname : répertoire temporaire où les fichiers sont décompressés
 *     - target : cible sur laquelle décompresser les fichiers...
 */
function teleporter_http_charger_zip($quoi = array()){
	if (!$quoi)
		return false;

	foreach (array(	'remove' => 'spip',
					'rename' => array(),
					'edit' => array(),
					'root_extract' => false, # extraire a la racine de dest ?
					'tmp' => sous_repertoire(_DIR_CACHE, 'chargeur')
				)
				as $opt=>$def) {
		isset($quoi[$opt]) || ($quoi[$opt] = $def);
	}

	if (!@file_exists($fichier = $quoi['fichier']))
		return 0;

	include_spip('inc/pclzip');
	$zip = new PclZip($fichier);
	$list = $zip->listContent();

	$racine = http_deballe_recherche_racine($list);
	$quoi['remove'] = $racine;

	// si pas de racine commune, reprendre le nom du fichier zip
	// en lui enlevant la racine h+md5 qui le prefixe eventuellement
	// cf action/charger_plugin L74
	if (!strlen($nom = basename($racine)))
		$nom = preg_replace(",^h[0-9a-f]{8}-,i","",basename($fichier, '.zip'));

	$dir_export = $quoi['root_extract']
		? $quoi['dest']
		: $quoi['dest'] . $nom;
	$dir_export = rtrim($dir_export,'/').'/';

	$tmpname = $quoi['tmp'].$nom.'/';

	// choisir la cible selon si on veut vraiment extraire ou pas
	$target = $quoi['extract'] ? $dir_export : $tmpname;

	// ici, il faut vider le rep cible si il existe deja, non ?
	if (is_dir($target))
		supprimer_repertoire($target);

	// et enfin on extrait
	$ok = $zip->extract(
		PCLZIP_OPT_PATH,
			$target
		,
		PCLZIP_OPT_SET_CHMOD, _SPIP_CHMOD,
		PCLZIP_OPT_REPLACE_NEWER,
		PCLZIP_OPT_REMOVE_PATH, $quoi['remove']
	);
	if ($zip->error_code < 0) {
		spip_log('charger_decompresser erreur zip ' . $zip->error_code .' pour paquet: ' . $quoi['archive'],"teleport"._LOG_ERREUR);
		return //$zip->error_code
			$zip->errorName(true);
	}

	spip_log('charger_decompresser OK pour paquet: ' . $quoi['archive'],"teleport");

	$size = $compressed_size = 0;
	$removex = ',^'.preg_quote($quoi['remove'], ',').',';
	foreach ($list as $a => $f) {
		$size += $f['size'];
		$compressed_size += $f['compressed_size'];
		$list[$a] = preg_replace($removex,'',$f['filename']);
	}

	// Indiquer par un fichier install.log
	// a la racine que c'est chargeur qui a installe ce plugin
	ecrire_fichier($target.'install.log',
		"installation: charger_plugin\n"
		."date: ".gmdate('Y-m-d\TH:i:s\Z', time())."\n"
		."source: ".$quoi['archive']."\n"
	);


	return array(
		'files' => $list,
		'size' => $size,
		'compressed_size' => $compressed_size,
		'dirname' => $dir_export,
		'tmpname' => $tmpname,
		'target' => $target,
	);
}
