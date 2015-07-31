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
include_spip('inc/actions');

// http://doc.spip.org/@formulaire_virtualiser
function inc_virtualiser_dist($id_article, $flag, $virtuel, $script, $args)
{
	global $spip_lang_right;

	$http = ($virtuel ? "" : "http://");

	$res = "<input type='text' name='virtuel' class='formo' style='font-size:9px;' value='"
	. $http
	. $virtuel
	. "' size='40' /><br />\n"
	. "<font face='Verdana,Arial,Sans,sans-serif' size='2'>"
	. "(<b>"._T('texte_article_virtuel')
	. "&nbsp;:</b> "
	. _T('texte_reference_mais_redirige')
	. ")</font>"
	. "\n<div align='$spip_lang_right'><input type='submit' class='fondo' value='"
	. _T('bouton_changer')
	. "' style='font-size:10px' /></div>";

	$res = ajax_action_auteur('virtualiser', $id_article, $script, $args, $res);
	return ajax_action_greffe("virtualiser-$id_article", $res);
}
?>
