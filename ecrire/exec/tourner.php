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

function exec_tourner_dist()
{
	global $id_document, $script, $id, $type, $ancre;
	$id = intval($id);
	$id_document = intval($id_document);

	include_spip('inc/documents');
	include_spip('inc/presentation');

	echo formulaire_tourner($id_document, array(), $script, 'ajax', $type);
}

?>
