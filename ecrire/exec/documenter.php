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

// http://doc.spip.org/@exec_documenter_dist
function exec_documenter_dist()
{
	global $id_document, $id, $type, $ancre, $script;
	$id = intval($id);
	$id_document = intval($id_document);

	if (!($type == 'article' 
		? acces_article($id)
		: acces_rubrique($id))) {
		spip_log("Tentative d'intrusion de " . $GLOBALS['auteur_session']['nom'] . " dans " . $GLOBALS['exec']);
		include_spip('inc/minipres');
		minipres(_T('info_acces_interdit'));
	}

	include_spip('inc/documents');
	include_spip('inc/presentation');

	return formulaire_documenter($id_document, array(), $script, $type, $id, $ancre);
}
?>
