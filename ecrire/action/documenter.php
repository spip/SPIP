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

include_spip('action/supprimer');

function action_documenter_dist($arg)
{
	$var_f = charger_fonction('controler_action_auteur', 'inc');
	$var_f();

	$arg = _request('arg');

	if (!preg_match(",^(-?)(\d+)\W(\w+)\W?(\d*)$,", $arg, $r))
		spip_log("action_documenter $arg pas compris");
	else {
		list($x, $sign, $id, $type, $vignette) = $r;
		if ($vignette)
			supprimer_document_et_vignette($vignette);
		else {
			if ($sign)
				$x = spip_query("SELECT docs.id_document FROM spip_documents AS docs, spip_documents_".$type."s AS l, spip_types_documents AS lestypes WHERE l.id_$type=$id AND l.id_document=docs.id_document AND docs.mode='document' AND docs.id_type=lestypes.id_type AND lestypes.extension IN ('gif', 'jpg', 'png')");
			else $x = spip_query("SELECT docs.* FROM spip_documents AS docs, spip_documents_".$type."s AS l, spip_types_documents AS lestypes  WHERE l.id_$type=$id AND l.id_document=docs.id_document AND docs.mode='document'  AND docs.id_type=lestypes.id_type AND lestypes.extension NOT IN ('gif', 'jpg', 'png')");
			while($r = spip_fetch_array($x)) {
				supprimer_document_et_vignette($r['id_document']);
			}
		}
		if ($type == 'rubrique') {
			include_spip('inc/rubriques');
			calculer_rubriques();
		}
	}
}
?>
