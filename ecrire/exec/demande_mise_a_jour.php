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


// http://doc.spip.org/@exec_demande_mise_a_jour_dist
function exec_demande_mise_a_jour_dist() {
	include_spip('inc/presentation');
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page();
	echo "<blockquote><blockquote><h4><span style='color: red'>",	_T('info_message_technique'),"</span><br /> ",
	_T('info_procedure_maj_version'),
	"</h4>",
	_T('info_administrateur_site_01'),
	" <a href='" . generer_url_ecrire("upgrade","reinstall=non") . "'>",
	_T('info_administrateur_site_02'),
	"</a></blockquote></blockquote>";
	echo fin_page();
}
?>
