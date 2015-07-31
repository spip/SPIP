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

// http://doc.spip.org/@exec_documents_colonne_dist
function exec_documents_colonne_dist()
{
	$id = intval(_request('id'));
	$show = _request('show_docs');
	$type = _request('type');

	if (!($type == 'article' 
		? autoriser('modifier','article',$id)
		: autoriser('publierdans','rubrique',$id))) {

		echo minipres();
		exit;
	}

	include_spip('inc/documents');
	include_spip('inc/presentation');
	spip_log("edcd $show");
	$script = $type."s_edit";
	$res = "";
	foreach(explode(",",$show) as $doc) {
		$res .= afficher_case_document($doc, $id, $script, $type, false);
	}
  
	ajax_retour("<div class='upload_answer upload_document_added'>". $res.	"</div>",false);
}

?>
