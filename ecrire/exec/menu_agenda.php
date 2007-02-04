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

include_spip('inc/presentation');

// http://doc.spip.org/@exec_menu_agenda_dist
function exec_menu_agenda_dist() {

	list($evtm, $evtt, $evtr) = http_calendrier_messages(date("Y"), date("m"), date("d"));

	$ret = "<table><tr>"
		. "<td style='width: 200px; vertical-align: top;' >"
		. "<div>"
		. $evtm
		. "</div>"
		. "</td>"
		.  (!$evtt ? '' :
			( "<td style='width: 10px; vertical-align: top'> &nbsp; </td>"
			. "<td style='width: 200px; color: black; vertical-align: top'>"
			. "<div>&nbsp;</div>$evtt</td>"))
		  . "</tr></table>";

	ajax_retour($ret);
}

?>
