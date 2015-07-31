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

// http://doc.spip.org/@exec_tourner_dist
function exec_tourner_dist()
{
	global $id_document, $script, $id, $type, $ancre;
	$id = intval($id);
	$id_document = intval($id_document);

	if (!($type == 'article' 
		? autoriser('modifier','article',$id)
		: autoriser('publierdans','rubrique',$id))) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}

	$tourner = charger_fonction('tourner', 'inc');
	ajax_retour($tourner($id_document, array(), $script, 'ajax', $type));
}

?>
