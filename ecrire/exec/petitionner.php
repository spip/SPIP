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

// http://doc.spip.org/@exec_petitionner_dist
function exec_petitionner_dist()
{
	global $id_article, $script;
	$id_article = intval($id_article);

	if (!autoriser('modifier','article',$id_article)) {

		echo minipres();
		exit;
	}

	$petitionner = charger_fonction('petitionner', 'inc');
	ajax_retour($petitionner($id_article, $script, "&id_article=$id_article", 'ajax'));
}
?>
