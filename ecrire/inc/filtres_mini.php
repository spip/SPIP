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

/**
 * Filtres d'URL et de liens
 *
 * @package SPIP\Core\Filtres\Liens
**/

if (!defined('_ECRIRE_INC_VERSION')) return;


/**
 * Nettoyer une URL contenant des `../`
 *
 * Inspiré (de loin) par PEAR:NetURL:resolvePath
 * @example
 *     ```
 *     resolve_path('/.././/truc/chose/machin/./.././.././hopla/..');
 *     ```
 * 
 * @param string $url URL
 * @return string URL nettoyée
**/
function resolve_path($url) {
	list($url, $query) = array_pad(explode('?', $url, 2), 2, null);
	while (preg_match(',/\.?/,', $url, $regs)		# supprime // et /./
	OR preg_match(',/[^/]*/\.\./,S', $url, $regs)	# supprime /toto/../
	OR preg_match(',^/\.\./,S', $url, $regs))		# supprime les /../ du haut
		$url = str_replace($regs[0], '/', $url);

	if ($query)
		$url .= '?'.$query;

	return '/'.preg_replace(',^/,S', '', $url);
}


/**
 * Suivre un lien depuis une URL donnée vers une nouvelle URL
 *
 * @uses resolve_path()
 * @example
 *     ```
 *     suivre_lien(
 *         'http://rezo.net/sous/dir/../ect/ory/fi.html..s#toto',
 *         'a/../../titi.coco.html/tata#titi');
 *     ```
 *
 * @param string $url URL de base
 * @param string $lien Lien ajouté à l'URL
 * @return string URL complète.
**/
function suivre_lien($url, $lien) {

	if (preg_match(',^(mailto|javascript):,iS', $lien))
		return $lien;
	if (preg_match(';^((?:[a-z]{3,7}:)?//.*?)(/.*)?$;iS', $lien, $r))
		return $r[1].resolve_path($r[2]);

	# L'url site spip est un lien absolu aussi
	if ($lien == $GLOBALS['meta']['adresse_site']){
		return $lien;
	}

	# lien relatif, il faut verifier l'url de base
	# commencer par virer la chaine de get de l'url de base
	if (preg_match(';^((?:[a-z]{3,7}:)?//[^/]+)(/.*?/?)?([^/#?]*)([?][^#]*)?(#.*)?$;S', $url, $regs)) {
		$debut = $regs[1];
		$dir = !strlen($regs[2]) ? '/' : $regs[2];
		$mot = $regs[3];
		$get = isset($regs[4])?$regs[4]:"";
		$hash = isset($regs[5])?$regs[5]:"";
	}
	switch (substr($lien,0,1)) {
		case '/':
			return $debut . resolve_path($lien);
		case '#':
			return $debut . resolve_path($dir.$mot.$get.$lien);
		case '':
			return $debut . resolve_path($dir.$mot.$get.$hash);
		default:
			return $debut . resolve_path($dir.$lien);
	}
}


/**
 * Transforme une URL relative en URL absolue
 *
 * S'applique sur une balise SPIP d'URL.
 * 
 * @filtre url_absolue
 * @link http://www.spip.net/4127
 * @uses suivre_lien()
 * @example
 *     ```
 *     [(#URL_ARTICLE|url_absolue)]
 *     [(#CHEMIN{css/theme.css}|url_absolue)]
 *     ```
 * 
 * @param string $url URL
 * @param string $base URL de base de destination (par défaut ce sera l'URL de notre site)
 * @return string Texte ou URL (en absolus)
**/
function url_absolue($url, $base='') {
	if (strlen($url = trim($url)) == 0)
		return '';
	if (!$base)
		$base = url_de_base() . (_DIR_RACINE ? _DIR_RESTREINT_ABS : '');
	return suivre_lien($base, $url);
}

/**
 * Supprimer le protocole d'une url absolue
 * pour le rendre implicite (URL commencant par "//")
 * @param string $url_absolue
 * @return string
 */
function protocole_implicite($url_absolue){
	return preg_replace(";^[a-z]{3,7}://;i","//",$url_absolue);
}

/**
 * Transforme les URLs relatives en URLs absolues
 *
 * Ne s'applique qu'aux textes contenant des liens
 *
 * @filtre liens_absolus
 * @uses url_absolue()
 * @link http://www.spip.net/4126
 * 
 * @param string $texte Texte
 * @param string $base URL de base de destination (par défaut ce sera l'URL de notre site)
 * @return string Texte avec des URLs absolues
**/
function liens_absolus($texte, $base='') {
	if (preg_match_all(',(<(a|link|image)[[:space:]]+[^<>]*href=["\']?)([^"\' ><[:space:]]+)([^<>]*>),imsS', 
	$texte, $liens, PREG_SET_ORDER)) {
		foreach ($liens as $lien) {
			$abs = url_absolue($lien[3], $base);
			if ($abs <> $lien[3] and !preg_match('/^#/',$lien[3]))
				$texte = str_replace($lien[0], $lien[1].$abs.$lien[4], $texte);
		}
	}
	if (preg_match_all(',(<(img|script)[[:space:]]+[^<>]*src=["\']?)([^"\' ><[:space:]]+)([^<>]*>),imsS', 
	$texte, $liens, PREG_SET_ORDER)) {
		foreach ($liens as $lien) {
			$abs = url_absolue($lien[3], $base);
			if ($abs <> $lien[3])
				$texte = str_replace($lien[0], $lien[1].$abs.$lien[4], $texte);
		}
	}
	return $texte;
}


/**
 * Transforme une URL ou des liens en URL ou liens absolus
 *
 * @filtre abs_url
 * @link http://www.spip.net/4128
 * @global mode_abs_url Pour connaître le mode (url ou texte)
 * 
 * @param string $texte Texte ou URL
 * @param string $base URL de base de destination (par défaut ce sera l'URL de notre site)
 * @return string Texte ou URL (en absolus)
**/
function abs_url($texte, $base='') {
	if ($GLOBALS['mode_abs_url'] == 'url')
		return url_absolue($texte, $base);
	else
		return liens_absolus($texte, $base);
}


?>
