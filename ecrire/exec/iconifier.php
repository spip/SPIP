<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');
include_spip('inc/texte');

// http://doc.spip.org/@exec_iconifier_dist
function exec_iconifier_dist()
{
	global $connect_id_auteur, $connect_toutes_rubriques;;

	$script = _request("script");
	$type = _request("type");
	$id = intval(_request($type));
  
	if (!preg_match('/^\w+$/', "$type$script"))
		die(_T('info_acces_interdit'));

	if ($type == 'id_rubrique')
	  $droit = acces_rubrique($id_rubrique);
	elseif ($type == 'id_auteur')
	  $droit = (($id == $connect_id_auteur) OR $connect_toutes_rubriques);
	elseif ($type == 'id_mot')
	  $droit = $connect_toutes_rubriques;
	else {
		$table=substr($type, 3) . (($type == 'id_syndic') ? '' : 's');
		$row = spip_fetch_array(spip_query("SELECT id_rubrique, statut FROM spip_$table WHERE $type=$id"));
		$droit = acces_rubrique($row['id_rubrique']);
		if (!$droit AND  ($row['statut'] == 'prepa' OR $row['statut'] == 'prop' OR $row['statut'] == 'poubelle'))
			$droit = spip_num_rows(determiner_auteurs_article($id_article, "id_auteur=$connect_id_auteur"));
	}

	if (!$droit) {
		spip_log("Tentative d'intrusion de " . $GLOBALS['auteur_session']['nom'] . " dans " . $GLOBALS['exec']);
		include_spip('inc/minipres');
		echo minipres(_T('info_acces_interdit'));
	}

	$iconifier = charger_fonction('iconifier', 'inc');
	
	$ret = $iconifier($type, $id, $script,"iconifier");
	
	if(_request("iframe")=="iframe") {
    $ret = "<div class='upload_answer upload_document_added'>$ret</div>";
    echo $ret;
    die;
  }
  ajax_retour($ret);
}
?>
