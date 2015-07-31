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

include_spip('inc/boutons');
include_spip('base/objets');

function inc_icone_renommer_dist($fond,$fonction){
	$size = 24;
	if (preg_match("/(?:-([0-9]{1,3}))?([.](gif|png))?$/i",$fond,$match)
		AND ((isset($match[0]) AND $match[0]) OR (isset($match[1]) AND $match[1]))) {
		if (isset($match[1]) AND $match[1]) {
			$size = $match[1];
		}
		$type = substr($fond,0,-strlen($match[0]));
		if (!isset($match[2]) OR !$match[2]) {
			$fond .= ".png";
		}
	}
	else {
		$type = $fond;
		$fond .= ".png";
	}

	$rtl = false;
	if (preg_match(',[-_]rtl$,i',$type,$match)){
		$rtl = true;
		$type = substr($type,0,-strlen($match[0]));
	}

	// objet_type garde invariant tout ce qui ne commence par par id_, spip_
	// et ne finit pas par un s, sauf si c'est une exception declaree
	$type = objet_type($type, false);

	$dir = "images/";
	$f = "$type-$size.png";
	if ($icone = find_in_theme($dir.$f)){
		$dir = dirname($icone);
		$fond = $icone;

		if ($rtl
			AND $fr = "$type-rtl-$size.png"
			AND file_exists($dir.'/'.$fr))
			$type = "$type-rtl";

		$action = $fonction;
		if ($action=="supprimer.gif"){
			$action = "del";
		}
		elseif ($action=="creer.gif"){
			$action = "new";
		}
		elseif ($action=="edit.gif"){
			$action = "edit";
		}
		if (!in_array($action,array('del','new','edit')))
			$action = "";
		if ($action){
			if ($fa = "$type-$action-$size.png"
			AND file_exists($dir.'/'.$fa)){
				$fond = $dir .'/'. $fa;
				$fonction = "";
			}
			else {
				$fonction = "$action-$size.png";
			}
		}
		// c'est bon !
		return array($fond,$fonction);
	}

	return array($fond,$fonction);
}
?>
