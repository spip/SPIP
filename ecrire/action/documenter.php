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

include_spip('action/supprimer');

// http://doc.spip.org/@action_documenter_dist
function action_documenter_dist()
{
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	if (!preg_match(",^(-?)(\d+)\W(\w+)\W?(\d*)$,", $arg, $r))
		spip_log("action_documenter $arg pas compris");
	else action_documenter_post($r);
}

// http://doc.spip.org/@action_documenter_post
function action_documenter_post($r)
{
	list($x, $sign, $id, $type, $vignette) = $r;
	if ($vignette){
		// on ne supprime pas, on dissocie
		// supprimer_document_et_vignette($vignette);
		// on dissocie, mais si le doc est utilise dans le texte, il sera reassocie ..., donc condition sur vu !
		spip_query("DELETE FROM spip_documents_".$type."s WHERE id_$type=$id AND id_document=$vignette AND vu='non'");
	}
	else {
			if ($sign)
				$x = spip_query("SELECT docs.id_document FROM spip_documents AS docs, spip_documents_".$type."s AS l, spip_types_documents AS lestypes WHERE l.id_$type=$id AND l.id_document=docs.id_document AND docs.mode='document' AND docs.id_type=lestypes.id_type AND lestypes.extension IN ('gif', 'jpg', 'png')");
			else $x = spip_query("SELECT docs.* FROM spip_documents AS docs, spip_documents_".$type."s AS l, spip_types_documents AS lestypes  WHERE l.id_$type=$id AND l.id_document=docs.id_document AND docs.mode='document'  AND docs.id_type=lestypes.id_type AND lestypes.extension NOT IN ('gif', 'jpg', 'png')");
			while($r = spip_fetch_array($x)) {
				//supprimer_document_et_vignette($r['id_document']);
				// on dissocie, mais si le doc est utilise dans le texte, il sera reassocie ..., donc condition sur vu !
				spip_query("DELETE FROM spip_documents_".$type."s WHERE id_$type=$id AND id_document=".$r['id_document']." AND vu='non'");
			}
	}
	if ($type == 'rubrique') {
			include_spip('inc/rubriques');
			calculer_rubriques();
	}
}
?>
