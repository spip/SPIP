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

// http://doc.spip.org/@exec_documenter_dist
function exec_documenter_dist()
{
	$type = _request("type");
	$script = _request("script"); // generalisation a tester
  $album = !_request("s") ? 'documents' :  'portfolio'; 
	$id = intval(_request(($type == 'article') ? 'id_article' : 'id_rubrique'));
	$id_auteur = $GLOBALS['auteur_session']['id_auteur'];
	$statut = $GLOBALS['auteur_session']['statut'];

	$droits = auth_rubrique($id_auteur, $statut);

	if ($type == 'rubrique')
		$editable = is_array($droits) ? $droits[$id] : is_int($droits);
	elseif (is_int($droits)) // i.e. admin complet
		$editable = true;
	else {

		$row = spip_fetch_array(spip_query("SELECT id_rubrique, statut FROM spip_articles WHERE id_article=$id"));

		$editable = (is_array($droits) AND $droits[$row['id_rubrique']]);
		if (!$editable) {
			if ($row['statut'] == 'prepa' OR $row['statut'] == 'prop')
				$editable = spip_num_rows(auteurs_article($id, "id_auteur=$id_auteur"));
		}
	}
	if (!$editable) {
		spip_log("Tentative d'intrusion de " . $GLOBALS['auteur_session']['nom'] . " dans " . $GLOBALS['exec']);
		include_spip('inc/minipres');
		echo minipres(_T('info_acces_interdit'));
	}

	$documenter = charger_fonction('documenter', 'inc');
	if(_request("iframe")=="iframe") { 
	 $res = $documenter($id, $type, "portfolio", 'ajax', '', $script).
	        $documenter($id, $type, "documents", 'ajax', '', $script);
	 echo "<div class='upload_answer upload_document_added'>".$res."</div>";
	}	else 
	 ajax_retour($documenter($id, $type, $album, 'ajax', '', $script));
}
?>
