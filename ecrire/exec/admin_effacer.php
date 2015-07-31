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

//
// Effacement total
//

// http://doc.spip.org/@exec_admin_effacer_dist
function exec_admin_effacer_dist()
{

	global $connect_statut, $connect_toutes_rubriques, $couleur_foncee;

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_admin_effacer'), "configuration", "base");


	echo "\n<br /><br /><br />";
	gros_titre(_T('titre_admin_effacer'));
	echo barre_onglets("administration", "effacer");

	debut_gauche();
	debut_boite_info();

	echo _T('info_gauche_admin_effacer');

	fin_boite_info();
	debut_droite();

	if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
		echo _T('avis_non_acces_page');
		echo fin_gauche(), fin_page();
		exit;
	}

	debut_cadre_relief();

	echo "<table border='0' cellspacing='1' cellpadding='8' width='100%'>",
		"<tr><td style='background-color: $couleur_foncee; font-weight: bold; font-size: 16px; color: #FFFFFF;' class='verdana1'>",
		_T('texte_effacer_base'),
		"</td></tr>",
		"<tr><td class='serif'>\n",
		'<img src="' . _DIR_IMG_PACK . 'warning.gif" alt="',
	  	_T('info_avertissement'),
		"\" style='width: 48px; height: 48px; float: right;margin: 10px;' />",
		_T('texte_admin_effacer_01'),
		"\n<div style='text-align: center'>",
		debut_boite_alerte(),
		"\n<div class='serif'>",
		"\n<b>"._T('avis_suppression_base')."&nbsp;!</b>",
		 generer_url_post_ecrire("delete_all", "reinstall=non"),
		"\n<div style='text-align: right'>",
		"<input class='fondo' type='submit' value='",
		_T('bouton_effacer_tout'),
		"' /></div></form>",
		"\n</div>",
		fin_boite_alerte(),
		"</div>",
		"</td></tr>",
		"</table>";

	fin_cadre_relief();

	echo fin_gauche(), fin_page();

}
?>
