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

include_spip('inc/presentation');

// http://doc.spip.org/@exec_iconifier_dist
function exec_iconifier_dist()
{
	$script = _request('script');
	$iframe = _request('iframe');
	$type =_request('type');
	$id = intval(_request($type));
	exec_iconifier_args($id, $type, $script, $iframe);
}

// http://doc.spip.org/@exec_iconifier_args
function exec_iconifier_args($id, $type, $script, $iframe=false)
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
			if (!$droit AND  ($row['statut'] == 'prepa' OR $row['statut'] == 'prop' OR $row['statut'] == 'poubelle')) {
			  $jointure = table_jointure('auteur', 'article');
			  $droit = sql_fetsel("id_auteur", "spip_$jointure", "id_article=".sql_quote($id) . " AND id_auteur=$connect_id_auteur");
			}
		}
	}

	if (!$droit) {
		include_spip('inc/minipres');
		echo minipres();
	} else {

		$iconifier = charger_fonction('iconifier', 'inc');
		$ret = $iconifier($type, $id, $script, $visible=true);
	
		if ($iframe!=='iframe') 
			ajax_retour($ret);
		else {
			echo "<div class='upload_answer upload_document_added'>$ret</div>";
		}
	}
}
?>
