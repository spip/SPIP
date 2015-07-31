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


// http://doc.spip.org/@exec_demande_mise_a_jour_dist
function exec_demande_mise_a_jour_dist() {
	// on fait la verif du path avant tout,
	// et l'installation des qu'on est dans la colonne principale
	// si jamais la liste des plugins actifs change, il faut faire un refresh du hit
	// pour etre sur que les bons fichiers seront charges lors de l'install
	include_spip('inc/plugin');
	if (actualise_plugins_actifs()==-1){
		include_spip('inc/headers');
		redirige_par_entete(self());
	}

	include_spip('inc/presentation');
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page('','','','',true,false,false);
	echo "<br /><br /><h4><span style='color: red'>",	_T('info_message_technique'),"</span><br /> ",
	_T('info_procedure_maj_version'),
	"</h4><div>",
	_T('info_administrateur_site_01'),
	" <a href='" . generer_url_ecrire("upgrade","reinstall=non") . "'>",
	_T('info_administrateur_site_02'),
	"</a></div>";
	echo fin_page();
}
?>
