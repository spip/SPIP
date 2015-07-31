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

// http://doc.spip.org/@exec_delete_all_dist
function exec_delete_all_dist()
{
	include_spip('inc/autoriser');
	if (!autoriser('detruire')) {
		include_spip('inc/minipres');
		echo minipres();
	} else {
		$q = sql_showbase();
		$res = '';
		while ($r = sql_fetch($q)) {
			$t = array_shift($r);
			$res .= "<li>"
			.  "<input type='checkbox' checked='checked' name='delete[]' id='delete_$t' value='$t'/>\n"
			. $t
			. "\n</li>";
		}
	  
		if (!$res) {
		  	include_spip('inc/minipres');
			spip_log("Erreur base de donnees");
			echo minipres(_T('info_travaux_titre'), _T('titre_probleme_technique'). "<p><tt>".sql_errno()." ".sql_error()."</tt></p>");
		} else {
			include_spip('inc/headers');
			$res = "<ol style='text-align:left'>$res</ol>";
			$admin = charger_fonction('admin', 'inc');
			$res = $admin('delete_all', _T('titre_page_delete_all'), $res);
			if ($res) echo $res; else redirige_par_entete(generer_url_ecrire('install','',true));
		}
	}
}
?>
