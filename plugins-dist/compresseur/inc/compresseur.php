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
 * Fonctions d'aide pour le compresseur
 * 
 * @package SPIP\Compresseur\Fonctions
 */
if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Ecrire la balise javascript pour insérer le fichier compressé
 * 
 * C'est cette fonction qui décide où il est le plus pertinent
 * d'insérer le fichier, et dans quelle forme d'ecriture
 *
 * @param string $flux
 *   Contenu du head nettoyé des fichiers qui ont été compressé
 * @param int $pos
 *   Position initiale du premier fichier inclu dans le fichier compressé
 * @param string $src
 *   Nom du fichier compressé
 * @param string $comments
 *   Commentaires à insérer devant
 * @return string
 *   Code HTML de la balise <script>
 */
function compresseur_ecrire_balise_js_dist(&$flux, $pos, $src, $comments = ""){
	$comments .= "<script type='text/javascript' src='$src'></script>";
  $flux = substr_replace($flux,$comments,$pos,0);
  return $flux;
}

/**
 * Ecrire la balise CSS pour insérer le fichier compressé
 * 
 * C'est cette fonction qui décide ou il est le plus pertinent
 * d'insérer le fichier, et dans quelle forme d'écriture
 *
 * @param string $flux
 *   Contenu du head nettoyé des fichiers qui ont ete compressé
 * @param int $pos
 *   Position initiale du premier fichier inclu dans le fichier compressé
 * @param string $src
 *   Nom du fichier compressé
 * @param string $comments
 *   Commentaires à insérer devant
 * @param string $media
 *   Type de media si précisé (print|screen...)
 * @return string
 *   Code HTML de la balise <link>
 */
function compresseur_ecrire_balise_css_dist(&$flux, $pos, $src, $comments = "", $media=""){
	$comments .= "<link rel='stylesheet'".($media?" media='$media'":"")." href='$src' type='text/css' />";
  $flux = substr_replace($flux,$comments,$pos,0);
	return $flux;
}

/**
 * Extraire les balises CSS à compacter
 * 
 * @param string $flux
 *     Contenu HTML dont on extrait les balises CSS
 * @param string $url_base
 * @return array
 *     Couples (balise => src)
 */
function compresseur_extraire_balises_css_dist($flux, $url_base){
	$balises = extraire_balises($flux,'link');
	$files = array();
	foreach ($balises as $s){
		if (extraire_attribut($s, 'rel') === 'stylesheet'
			AND (!($type = extraire_attribut($s, 'type'))
				OR $type == 'text/css')
			AND is_null(extraire_attribut($s, 'name')) # css nommee : pas touche
			AND is_null(extraire_attribut($s, 'id'))   # idem
			AND !strlen(strip_tags($s))
			AND $src = preg_replace(",^$url_base,",_DIR_RACINE,extraire_attribut($s, 'href')))
			$files[$s] = $src;
	}
	return $files;
}

/**
 * Extraire les balises JS à compacter
 * 
 * @param string $flux
 *     Contenu HTML dont on extrait les balises CSS
 * @param string $url_base
 * @return array
 *     Couples (balise => src)
 */
function compresseur_extraire_balises_js_dist($flux, $url_base){
	$balises = extraire_balises($flux,'script');
	$files = array();
	foreach ($balises as $s){
		if (extraire_attribut($s, 'type') === 'text/javascript'
			AND is_null(extraire_attribut($s, 'id')) # script avec un id : pas touche
			AND $src = extraire_attribut($s, 'src')
			AND !strlen(strip_tags($s)))
			$files[$s] = $src;
	}
	return $files;
}

/**
 * Compacter (concaténer+minifier) les fichiers format CSS ou JS
 * du head.
 *
 * Repérer fichiers statiques vs. url squelettes
 * Compacte le tout dans un fichier statique posé dans local/
 *
 * @param string $flux
 *    Contenu du <head> de la page html
 * @param string $format
 *    css ou js
 * @return string
 *    Contenu compressé du <head> de la page html
 */
function compacte_head_files($flux,$format) {
	$url_base = url_de_base();
	$url_page = substr(generer_url_public('A'), 0, -1);
	$dir = preg_quote($url_page,',').'|'.preg_quote(preg_replace(",^$url_base,",_DIR_RACINE,$url_page),',');

	if (!$extraire_balises = charger_fonction("compresseur_extraire_balises_$format",'',true))
		return $flux;

	$files = array();
	$flux_nocomment = preg_replace(",<!--.*-->,Uims","",$flux);
	foreach ($extraire_balises($flux_nocomment, $url_base) as $s=>$src) {
		if (
			preg_match(',^('.$dir.')(.*)$,', $src, $r)
			OR (
				// ou si c'est un fichier
				$src = preg_replace(',^'.preg_quote(url_de_base(),',').',', '', $src)
				// enlever un timestamp eventuel derriere un nom de fichier statique
				AND $src2 = preg_replace(",[.]{$format}[?].+$,",".$format",$src)
				// verifier qu'il n'y a pas de ../ ni / au debut (securite)
				AND !preg_match(',(^/|\.\.),', substr($src,strlen(_DIR_RACINE)))
				// et si il est lisible
				AND @is_readable($src2)
			)
		) {
			if ($r)
				$files[$s] = explode('&', str_replace('&amp;', '&', $r[2]), 2);
			else
				$files[$s] = $src;
		}
	}

	$callbacks = array('each_min'=>'callback_minifier_'.$format.'_file');

	if ($format=="css"){
		$callbacks['each_pre'] = 'compresseur_callback_prepare_css';
		$callbacks['all_min'] = 'css_regroup_atimport';
		// ce n'est pas une callback, mais en injectant l'url de base ici
		// on differencie les caches quand l'url de base change
		// puisque la css compresse inclue l'url courante du site (en url absolue)
		// on exclue le protocole car la compression se fait en url relative au protocole
		$callbacks[] = protocole_implicite($url_base);
	}
	if ($format=='js' AND $GLOBALS['meta']['auto_compress_closure']=='oui'){
		$callbacks['all_min'] = 'minifier_encore_js';
	}

	include_spip('inc/compresseur_concatener');
	include_spip('inc/compresseur_minifier');
	if (list($src,$comms) = concatener_fichiers($files, $format, $callbacks)
		AND $src){
		$compacte_ecrire_balise = charger_fonction("compresseur_ecrire_balise_$format",'');
		$files = array_keys($files);
		// retrouver la position du premier fichier compacte
		$pos = strpos($flux,reset($files));
		// supprimer tous les fichiers compactes du flux
		$flux = str_replace($files,"",$flux);
		// inserer la balise (deleguer a la fonction, en lui donnant le necessaire)
		$flux = $compacte_ecrire_balise($flux, $pos, $src, $comms);
	}

	return $flux;
}


/**
 * Lister les fonctions de préparation des feuilles css
 * avant minification
 * 
 * @return array
 *     Liste des fonctions à appliquer sur les feuilles CSS
 */
function compresseur_liste_fonctions_prepare_css(){
	static $fonctions = null;

	if (is_null($fonctions)){
		$fonctions = array('css_resolve_atimport','urls_absolues_css');
		// les fonctions de preparation aux CSS peuvent etre personalisees
		// via la globale $compresseur_filtres_css sous forme de tableau de fonctions ordonnees
		if (isset($GLOBALS['compresseur_filtres_css']) AND is_array($GLOBALS['compresseur_filtres_css']))
			$fonctions = $GLOBALS['compresseur_filtres_css'] + $fonctions;
	}
	return $fonctions;
}


/**
 * Préparer un fichier CSS avant sa minification
 * 
 * @param string $css
 * @param bool|string $is_inline
 * @param string $fonctions
 * @return bool|int|null|string
 */
function &compresseur_callback_prepare_css(&$css, $is_inline = false, $fonctions=null) {
	if ($is_inline) return compresseur_callback_prepare_css_inline($css,$is_inline);
	if (!preg_match(',\.css$,i', $css, $r)) return $css;

	$url_absolue_css = url_absolue($css);

	if (!$fonctions) $fonctions = compresseur_liste_fonctions_prepare_css();
	elseif (is_string($fonctions)) $fonctions = array($fonctions);

	$sign = implode(",",$fonctions);
	$sign = substr(md5("$css-$sign"), 0,8);

	$file = basename($css,'.css');
	$file = sous_repertoire (_DIR_VAR, 'cache-css')
		. preg_replace(",(.*?)(_rtl|_ltr)?$,","\\1-f-" . $sign . "\\2",$file)
		. '.css';

	if ((@filemtime($file) > @filemtime($css))
		AND (!defined('_VAR_MODE') OR _VAR_MODE != 'recalcul'))
		return $file;

	if ($url_absolue_css==$css){
		if (strncmp($GLOBALS['meta']['adresse_site']."/",$css,$l=strlen($GLOBALS['meta']['adresse_site']."/"))!=0
		 OR !lire_fichier(_DIR_RACINE . substr($css,$l), $contenu)){
		 		include_spip('inc/distant');
		 		if (!$contenu = recuperer_page($css))
					return $css;
		}
	}
	elseif (!lire_fichier($css, $contenu))
		return $css;

	// retirer le protocole de $url_absolue_css
	$url_absolue_css = protocole_implicite($url_absolue_css);
	$contenu = compresseur_callback_prepare_css_inline($contenu, $url_absolue_css, $fonctions);

	// ecrire la css
	if (!ecrire_fichier($file, $contenu))
		return $css;

	return $file;
}

/**
 * Préparer du contenu CSS inline avant minification
 * 
 * @param string $contenu
 * @param string $url_base
 * @param array $fonctions
 * @return string
 */
function &compresseur_callback_prepare_css_inline(&$contenu, $url_base, $fonctions=null) {
	if (!$fonctions) $fonctions = compresseur_liste_fonctions_prepare_css();
	elseif (is_string($fonctions)) $fonctions = array($fonctions);

	// retirer le protocole de $url_base
	$url_base = protocole_implicite(url_absolue($url_base));

	foreach($fonctions as $f)
		if (function_exists($f))
			$contenu = $f($contenu, $url_base);
	
	return $contenu;
}

/**
 * Resoudre et inliner les @import
 * ceux-ci ne peuvent etre presents qu'en debut de CSS et on ne veut pas changer l'ordre des directives
 *
 * @param string $contenu
 * @param string $url_base
 * @return string
 */
function css_resolve_atimport($contenu, $url_base){
	// vite si rien a faire
	if (strpos($contenu,"@import")===false)
		return $contenu;

	$imports_non_resolvables = array();
	preg_match_all(",@import ([^;]*);,UmsS",$contenu,$matches,PREG_SET_ORDER);

	if ($matches AND count($matches)){
		foreach($matches as $m){
			$url = $media = $erreur = "";
			if (preg_match(",^\s*url\s*\(\s*['\"]?([^'\"]*)['\"]?\s*\),Ums",$m[1],$r)){
				$url = $r[1];
				$media = trim(substr($m[1],strlen($r[0])));
			}
			elseif(preg_match(",^\s*['\"]([^'\"]+)['\"],Ums",$m[1],$r)){
				$url = $r[1];
				$media = trim(substr($m[1],strlen($r[0])));
			}
			if (!$url){
				$erreur = "Compresseur : <tt>".$m[0].";</tt> non resolu dans <tt>$url_base</tt>";
			}
			else {
				$url = suivre_lien($url_base,$url);
				// url relative ?
				$root = protocole_implicite($GLOBALS['meta']['adresse_site']."/");
				if (strncmp($url,$root,strlen($root))==0){
					$url = _DIR_RACINE . substr($url,strlen($root));
				}
				else {
					// si l'url a un protocole http(s):// on ne considère qu'on ne peut pas
					// résoudre le stockage. Par exemple
					// @import url(https://fonts.googleapis.com/css?family=Ubuntu);
					// retournant un contenu différent en fonction navigateur
					// tous les @import restant seront remontes en tete de CSS en fin de concatenation
					if (preg_match(',^https?://,', $url)) {
						$url = "";
					} else {
						// protocole implicite //
						$url = "http:$url";
					}
				}

				if ($url) {
					// on renvoit dans la boucle pour que le fichier inclus
					// soit aussi processe (@import, url absolue etc...)
					$css = compresseur_callback_prepare_css($url);
					if ($css==$url
						OR !lire_fichier($css,$contenu_imported)){
						$erreur = "Compresseur : url $url de <tt>".$m[0].";</tt> non resolu dans <tt>$url_base</tt>";
					}
					else {
						if ($media){
							$contenu_imported = "@media $media{\n$contenu_imported\n}\n";
						}
						$contenu = str_replace($m[0],$contenu_imported,$contenu);
					}
				}
			}

			if ($erreur){
				$contenu = str_replace($m[0],"/* erreur @ import ".$m[1]."*/",$contenu);
				erreur_squelette($erreur);
			}
		}
	}

	return $contenu;
}

/**
 * Regrouper les @import restants dans la CSS concatenee en debut de celle-ci
 *
 * @param string $nom_tmp
 * @param string $nom
 * @return bool|string
 */
function css_regroup_atimport($nom_tmp, $nom){
	lire_fichier($nom_tmp,$contenu);
	if (!$contenu OR strpos($contenu,"@import")===false) return false; // rien a faire

	preg_match_all(",@import ([^;]*);,UmsS",$contenu,$matches,PREG_SET_ORDER);
	$imports = array_map("reset",$matches);
	$contenu = str_replace($imports,"",$contenu);
	$contenu = implode("\n",$imports)."\n".$contenu;
	ecrire_fichier($nom,$contenu,true);
	// ecrire une version .gz pour content-negociation par apache, cf. [11539]
	ecrire_fichier("$nom.gz",$contenu,true);

	return $nom;
}