<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Fonctions et filtres du compresseur
 * 
 * @package SPIP\Compresseur\Fonctions
 */
if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Minifier un fichier JS ou CSS
 * 
 * Si la source est un chemin, on retourne un chemin avec le contenu minifié
 * dans _DIR_VAR/cache_$format/
 * Si c'est un flux on le renvoit compacté
 * Si on ne sait pas compacter, on renvoie ce qu'on a recu
 *
 * @param string $source
 *     Contenu à minifier ou chemin vers un fichier dont on veut minifier le contenu
 * @param string $format
 *     Format de la source (js|css). 
 * @return string
 *     - Contenu minifié (si la source est un contenu)
 *     - Chemin vers un fichier ayant le contenu minifié (si source est un fichier)
 */
function minifier($source, $format = null) {
	if (!$format AND preg_match(',\.(js|css)$,', $source, $r))
		$format = $r[1];
	include_spip('inc/compresseur_minifier');
	if (!function_exists($minifier = 'minifier_'.$format))
		return $source;

	// Si on n'importe pas, est-ce un fichier ?
	if (!preg_match(',[\s{}],', $source)
	AND preg_match(',\.'.$format.'$,i', $source, $r)
	AND file_exists($source)) {
		// si c'est une css, il faut reecrire les url en absolu
  	if ($format=='css')
  		$source = url_absolue_css($source);

		$f = basename($source,'.'.$format);
		$f = sous_repertoire (_DIR_VAR, 'cache-'.$format)
		. preg_replace(",(.*?)(_rtl|_ltr)?$,","\\1-minify-"
		. substr(md5("$source-minify"), 0,4) . "\\2", $f, 1)
		. '.' . $format;

		if ((@filemtime($f) > @filemtime($source))
		AND (!defined('_VAR_MODE') OR _VAR_MODE != 'recalcul'))
			return $f;

		if (!lire_fichier($source, $contenu))
			return $source;

		// traiter le contenu
		$contenu = $minifier($contenu);

		// ecrire le fichier destination, en cas d'echec renvoyer la source
		if (ecrire_fichier($f, $contenu, true))
			return $f;
		else
			return $source;
	}

	// Sinon simple minification de contenu
	return $minifier($source);
}

/**
 * Synonyme historique de minifier, pour compatibilite
 *
 * @deprecated Utiliser minifier()
 * 
 * @param string $source
 * @param string $format
 * @return string
 */
function compacte($source, $format = null){
	return minifier($source, $format);
}

/**
 * Compacte les éléments CSS et JS d'un <head> HTML
 * 
 * Cette fonction vérifie les réglages du site et traite le compactage
 * des css et/ou js d'un <head>
 * 
 * Un fichier .gz est crée pour chaque, qui peut etre utilisé par apache
 * et lui éviter de recompresser à chaque hit, avec les directives suivantes :
 * 
 * <IfModule mod_gzip.c>
 * mod_gzip_on                   Yes
 * mod_gzip_can_negotiate        Yes
 * mod_gzip_static_suffix        .gz
 * AddEncoding              gzip .gz
 * mod_gzip_item_include         file       \.(js|css)$
 * </IfModule>
 *
 * @see compacte_head_files()
 * 
 * @param string $flux
 *     Partie de contenu du head HTML
 * @return string
 *     Partie de contenu du head HTML
 */
function compacte_head($flux){
	include_spip('inc/compresseur');
	if (!defined('_INTERDIRE_COMPACTE_HEAD')){
		// dans l'espace prive on compacte toujours, c'est concu pour
		if ((!test_espace_prive() AND $GLOBALS['meta']['auto_compress_css'] == 'oui') OR (test_espace_prive() AND !defined('_INTERDIRE_COMPACTE_HEAD_ECRIRE')))
			$flux = compacte_head_files($flux,'css');
		if ((!test_espace_prive() AND $GLOBALS['meta']['auto_compress_js'] == 'oui') OR (test_espace_prive() AND !defined('_INTERDIRE_COMPACTE_HEAD_ECRIRE')))
			$flux = compacte_head_files($flux,'js');
	}
	return $flux;
}

/**
 * Embarquer sous forme URI Scheme un fichier
 *
 * Une URI Scheme est de la forme data:xxx/yyy;base64,....
 * 
 * Experimental
 *
 * @filtre embarque_fichier
 * 
 * @staticvar array $mime
 *     Couples (extension de fichier => type myme)
 * @param string $src
 *     Chemin du fichier
 * @param string $base
 *     Le chemin de base à partir duquel chercher $src
 * @param int $maxsize
 *     Taille maximale des fichiers à traiter
 * @return string
 *     URI Scheme du fichier si la compression est faite,
 *     URL du fichier sinon (la source)
 */
function filtre_embarque_fichier ($src, $base="", $maxsize = 4096) {
	static $mime = array();
	$extension = substr(strrchr($src,'.'),1);
	$filename = $base . $src;
	#var_dump("$base:$src:$filename");

	if (!file_exists($filename)
		OR filesize($filename)>$maxsize
		OR !lire_fichier($filename, $contenu))
		return $src;

	if (!isset($mime[$extension]))
		$mime[$extension] = Sql::getfetsel('mime_type','spip_types_documents','extension='.Sql::quote($extension));

	$base64 = base64_encode($contenu);
	$encoded = 'data:'.$mime[$extension].';base64,'.$base64;
	#var_dump($encoded);

	return $encoded;
}

/**
 * Embarquer le 'src' d'une balise html en URI Scheme
 *
 * Experimental
 *
 * @param string $img
 *     Code HTML d'une image
 * @param int $maxsize
 *     Taille maximale des fichiers à traiter
 * @return string
 *     Code HTML de l'image, avec la source en URI Scheme si cela a été possible.
 */
function filtre_embarque_src ($img, $maxsize = 4096){
	$src = extraire_attribut($img,'src');
	if ($src2=filtre_embarque_fichier($src, "", $maxsize) AND $src2!= $src) {
		$img = inserer_attribut($img, 'src', $src2);
	}
	return $img;
}
?>
