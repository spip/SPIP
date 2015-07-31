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


// http://doc.spip.org/@exec_demande_mise_a_jour_dist
function exec_demande_mise_a_jour_dist() {
	include_spip('inc/presentation');
	debut_page();
	echo "<blockquote><blockquote><h4><font color='red'>",
	_T('info_message_technique'),
	"</font><br> ",
	_T('info_procedure_maj_version'),
	"</h4>",
	_T('info_administrateur_site_01'),
	" <a href='" . generer_url_ecrire("upgrade","reinstall=non") . "'>",
	_T('info_administrateur_site_02'),
	"</a></blockquote></blockquote><p>";
	echo fin_page();
}
?>
