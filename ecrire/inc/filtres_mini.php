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

//
// Filtres d'URLs
//

// Nettoyer une URL contenant des ../
//
// resolve_url('/.././/truc/chose/machin/./.././.././hopla/..');
// inspire (de loin) par PEAR:NetURL:resolvePath
//
// http://doc.spip.org/@resolve_path
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

// 
// Suivre un lien depuis une adresse donnee -> nouvelle adresse
//
// suivre_lien('http://rezo.net/sous/dir/../ect/ory/fi.html..s#toto',
// 'a/../../titi.coco.html/tata#titi');
// http://doc.spip.org/@suivre_lien
function suivre_lien($url, $lien) {

	if (preg_match(',^(mailto|javascript|data):,iS', $lien))
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

// un filtre pour transformer les URLs relatives en URLs absolues ;
// ne s'applique qu'aux #URL_XXXX
// http://doc.spip.org/@url_absolue
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

// un filtre pour transformer les URLs relatives en URLs absolues ;
// ne s'applique qu'aux textes contenant des liens
// http://doc.spip.org/@liens_absolus
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

//
// Ce filtre public va traiter les URL ou les <a href>
//
// http://doc.spip.org/@abs_url
function abs_url($texte, $base='') {
	if ($GLOBALS['mode_abs_url'] == 'url')
		return url_absolue($texte, $base);
	else
		return liens_absolus($texte, $base);
}

/**
* htmlspecialchars wrapper (PHP >= 5.4 compat issue)
*
* @param string $string
* @param int $flags
* @param string $encoding
* @param bool $double_encode
* @return string
*/
function spip_htmlspecialchars($string, $flags=null, $encoding='ISO-8859-1', $double_encode = true){
	if (is_null($flags)) {
		if (!defined('PHP_VERSION_ID') OR PHP_VERSION_ID < 50400)
			$flags = ENT_COMPAT;
		else
			$flags = ENT_COMPAT|ENT_HTML401;
	}

	if (!defined('PHP_VERSION_ID') OR PHP_VERSION_ID < 50203)
		return htmlspecialchars($string,$flags,$encoding);
	else
		return htmlspecialchars($string,$flags,$encoding,$double_encode);
}

/**
* htmlentities wrapper (PHP >= 5.4 compat issue)
*
* @param string $string
* @param int $flags
* @param string $encoding
* @param bool $double_encode
* @return string
*/
function spip_htmlentities($string,$flags=null,$encoding = 'ISO-8859-1',$double_encode = true){
	if (is_null($flags)) {
		if (!defined('PHP_VERSION_ID') OR PHP_VERSION_ID < 50400)
			$flags = ENT_COMPAT;
		else
			$flags = ENT_COMPAT|ENT_HTML401;
	}

	if (!defined('PHP_VERSION_ID') OR PHP_VERSION_ID < 50203)
		return htmlentities($string,$flags,$encoding);
	else
		return htmlentities($string,$flags,$encoding,$double_encode);
}
?>
