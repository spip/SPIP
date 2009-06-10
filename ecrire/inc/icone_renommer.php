<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/boutons');

function inc_icone_renommer_dist($fond,$fonction){

	$size = 24;
	if (preg_match("/-([0-9]{1,3})[.](gif|png)$/i",$fond,$match))
		$size = $match[1];
	$type = preg_replace("/(-[0-9]{1,3})?[.](gif|png)$/i","",$fond);

	$rtl = false;
	if (preg_match(',[-_]rtl$,i',$type)){
		$rtl = true;
		$type = preg_replace(',[-_]rtl$,i','',$type);
	}

	$remplacement = array(
		'historique'=>'revision',
		//'secteur'=>'rubrique',
		'racine-site'=>'site',
		'mot-cle'=>'mot',
	);
	if (isset($remplacement[$type]))
		$type = $remplacement[$type];

	$dir = "images/";
	$f = "$type-$size.png";
	if ($icone = find_in_skin($dir.$f)){
		$dir = dirname($icone);
		$fond = $icone;

		if ($rtl
			AND $fr = "$type-rtl-$size.png"
			AND file_exists($dir.'/'.$fr))
			$type = "$type-rtl";

		$action = "";
		if ($fonction=="supprimer.gif"){
			$action = "del";
		}
		elseif ($fonction=="creer.gif"){
			$action = "new";
		}
		elseif ($fonction=="edit.gif"){
			$action = "edit";
		}
		if ($action
			AND $fa = "$type-$action-$size.png"
			AND file_exists($dir.'/'.$fa)){
			$fond = $dir .'/'. $fa;
			$fonction = "";
		}
		// c'est bon !
		return array($fond,$fonction);
	}

	return array($fond,$fonction);
}
?>
