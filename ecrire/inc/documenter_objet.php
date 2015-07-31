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

// http://doc.spip.org/@naviguer_doc
function inc_documenter_objet_dist($id, $type = "article", $script, $flag_editable=true) {
	global $spip_lang_left;

	// Joindre ?
	if  ($GLOBALS['meta']["documents_$type"]=='non'
	OR !autoriser('joindredocument', $type, $id)
	OR !$flag_editable)
		$res = '';
	else {
		$joindre = charger_fonction('joindre', 'inc');
		$res = $joindre(array(
			'cadre' => 'relief',
			'icone' => 'image-24.gif',
			'fonction' => 'creer.gif',
			'titre' => _T('titre_joindre_document'),
			'script' => $script,
			'args' => "id_$type=$id",
			'id' => $id,
			'intitule' => _T('info_telecharger_ordinateur'),
			'mode' => 'document',
			'type' => $type,
			'ancre' => '',
			'id_document' => 0,
			'iframe_script' => generer_url_ecrire("documenter","id_$type=$id&type=$type",true)
		));

	// eviter le formulaire upload qui se promene sur la page
	// a cause des position:relative incompris de MSIE

	  if ($GLOBALS['browser_name']!="MSIE") {
		$res = "\n<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n<tr><td>&nbsp;</td><td style='text-align: $spip_lang_left;width: 50%;'>\n$res</td></tr></table>";
	  }

	  $res .= http_script('',"async_upload.js")
	 . http_script('$("form.form_upload").async_upload(async_upload_portfolio_documents);');

	}

	$documenter = charger_fonction('documenter', 'inc');

	return "<div id='portfolio'>".$documenter($id, $type, 'portfolio', $flag_editable)."</div><br />"
	."<div id='documents'>". $documenter($id, $type, 'documents', $flag_editable)."</div>"
	. $res;
}


?>
