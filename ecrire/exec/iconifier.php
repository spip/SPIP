<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');
include_spip('inc/editer_auteurs'); #pour determiner_auteurs_objet()

// http://doc.spip.org/@exec_iconifier_dist
function exec_iconifier_dist()
{
	$type =_request("type");
	iconifier_args(intval(_request($type)), $type,_request("script"));
}

// http://doc.spip.org/@iconifier_args
function iconifier_args($id, $type, $script)
{
	global $connect_id_auteur, $connect_toutes_rubriques;;
	if (!preg_match('/^\w+$/', "$type$script")) {
		$droit = false;
	} else {
		if ($type == 'id_rubrique')
		  $droit = autoriser('publierdans','rubrique',$id);
		elseif ($type == 'id_auteur')
		  $droit = (($id == $connect_id_auteur) OR $connect_toutes_rubriques);
		elseif ($type == 'id_mot')
		  $droit = $connect_toutes_rubriques;
		else {
			$table=substr($type, 3) . (($type == 'id_syndic') ? '' : 's');
			$row = sql_fetsel("id_rubrique, statut", "spip_$table", "$type=$id");
			$droit = autoriser('publierdans','rubrique',$row['id_rubrique']);
			if (!$droit AND  ($row['statut'] == 'prepa' OR $row['statut'] == 'prop' OR $row['statut'] == 'poubelle'))
			  $droit = sql_count(determiner_auteurs_objet('article',$id, "id_auteur=$connect_id_auteur"));
		}
	}

	if (!$droit) {
		include_spip('inc/minipres');
		echo minipres();
	} else {

		$iconifier = charger_fonction('iconifier', 'inc');
		$ret = $iconifier($type, $id, $script, $visible=true);
	
		if (!_request("iframe")=="iframe") 
			ajax_retour($ret);
		else {
			echo "<div class='upload_answer upload_document_added'>$ret</div>";
		}
	}
}
?>
