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

include_spip('inc/config');
include_spip('inc/plugin');
include_spip('inc/presentation');
include_spip('inc/layer');
include_spip('inc/actions');
include_spip('inc/securiser_action');

// http://doc.spip.org/@exec_admin_plugin_dist
function exec_charger_plugin_dist($retour='') {

	if (!autoriser('configurer', 'plugins')) {
		include_spip('inc/minipres');
		echo minipres();
	} else {

		$commencer_page = charger_fonction('commencer_page', 'inc');
		echo $commencer_page(_T('icone_admin_plugin'), "configuration", "plugin");
		echo "<br />\n";
		echo "<br />\n";

		echo gros_titre(_T('icone_admin_plugin'),'',false);
		echo barre_onglets("plugins", "charger_plugin");

		echo debut_gauche('plugin',true);
		echo debut_boite_info(true);
		$s = "";
		$s .= _T('info_gauche_admin_tech');
		echo $s;
		echo fin_boite_info(true);

		echo pipeline('affiche_gauche',
			array(
			'args'=>array('exec'=>'charger_plugin'),
			'data'=>''
			)
		);

		echo debut_droite('plugin', true);
		// voire si on peut creer le repertoure auto/ sans rien demander
		sous_repertoire(_DIR_PLUGINS_AUTO, '', true, true);
		
		echo "<div class='liste-plugins formulaire_spip'>";
		include_spip('inc/charger_plugin');
		echo formulaire_charger_plugin($retour);
		echo "</div>";

		echo pipeline('affiche_milieu',
			array(
			'args'=>array('exec'=>'charger_plugin'),
			'data'=>''
			)
		);

		echo fin_gauche(), fin_page();
	}
}

?>
