<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
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
	list($url, $query) = explode('?', $url,2);
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

	if (preg_match(',^(mailto|javascript):,iS', $lien))
		return $lien;
	if (preg_match(',^([a-z0-9]+://.*?)(/.*)?$,iS', $lien, $r))
		return $r[1].resolve_path($r[2]);

	# L'url site spip est un lien absolu aussi
	if ($lien == $GLOBALS['meta']['adresse_site']){
		return $lien;
	}

	# lien relatif, il faut verifier l'url de base
	# commencer par virer la chaine de get de l'url de base
	if (preg_match(',^(.*?://[^/]+)(/.*?/?)?([^/#?]*)([?][^#]*)?(#.*)?$,S', $url, $regs)) {
		$debut = $regs[1];
		$dir = !strlen($regs[2]) ? '/' : $regs[2];
		$mot = $regs[3];
		$get = isset($regs[4])?$regs[4]:"";
		$hash = isset($regs[5])?$regs[5]:"";
	}
	#var_dump(array('url'=>$url,'debut'=>$debut,'dir'=>$dir,'mot'=>$mot,'get'=>$get,'hash'=>$hash));
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

// un filtre pour transformer les URLs relatives en URLs absolues ;
// ne s'applique qu'aux textes contenant des liens
// http://doc.spip.org/@liens_absolus
function liens_absolus($texte, $base='') {
	if (preg_match_all(',(<(a|link)[[:space:]]+[^<>]*href=["\']?)([^"\' ><[:space:]]+)([^<>]*>),imsS', 
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


?>
