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
include_spip('inc/actions');

// http://doc.spip.org/@inc_virtualiser_dist
function inc_virtualiser_dist($id_article, $flag, $virtuel, $script, $args)
{
	global $spip_lang_right, $connect_statut;

	if (!($connect_statut=='0minirezo' && $flag))
	  return '';

	$res = "<input type='text' name='virtuel' class='formo spip_xx-small' value='"
	. ($virtuel ? "" : "http://")
	. $virtuel
	. "' size='40' /><br />\n"
	. "<span class='verdana1 spip_x-small'>(<b>"
	. _T('texte_article_virtuel')
	. "&nbsp;:</b>"
	.  _T('texte_reference_mais_redirige')
	. ")</span><br />";

	$res = ajax_action_post('virtualiser', $id_article, $script, $args, $res, _T('bouton_changer'), " class='fondo spip_xx-small' style='float: $spip_lang_right'")
	  . "<br class='nettoyeur' />";
	return ajax_action_greffe("virtualiser-$id_article", $res);
}
?>
