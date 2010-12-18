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

// http://doc.spip.org/@exec_delete_all_dist
function exec_delete_all_dist()
{
	include_spip('inc/autoriser');
	if (!autoriser('detruire')) {
		include_spip('inc/minipres');
		echo minipres();
	} else {
		$res = liste_tables_en_base('delete');
		if (!$res) {
		  	include_spip('inc/minipres');
			spip_log("Erreur base de donnees");
			echo minipres(_T('info_travaux_titre'), _T('titre_probleme_technique'). "<p><tt>".sql_errno()." ".sql_error()."</tt></p>");
		} else {
			include_spip('inc/headers');
			$res = "\n<ol style='text-align:left'><li>\n" .
			  join("</li>\n<li>", $res) .
			  '</li></ol>';
			$admin = charger_fonction('admin', 'inc');
			$res = $admin('delete_all', _T('titre_page_delete_all'), $res);
			if (!$res)
				redirige_url_ecrire('install','');
			else echo $res;
		}
	}
}

function liste_tables_en_base($name)
{
	$res = sql_alltable();
	$c = "type='checkbox' checked='checked'";
	foreach ($res as $k => $t) {
		$res[$k] = "<input $c value='$t' id='$name_$t' name='$name"
			. "[]' />\n"
			. $t
			. " ("
			.  sql_countsel($t)
	  		. ")";
	}
	return $res;
}
?>
