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
include_spip('inc/config');
include_spip('inc/meta');

// http://doc.spip.org/@exec_config_multilang_dist
function exec_config_multilang_dist()
{

	if (!autoriser('configurer', 'multilang')) {
		echo minipres();
		exit;
	}

	lire_metas();

	pipeline('exec_init',array('args'=>array('exec'=>'config_multilang'),'data'=>''));
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_config_contenu'), "configuration", "langues");

	echo "<br /><br /><br />";
	gros_titre(_T('info_langues'));

	init_config();

	echo barre_onglets("config_lang", "multi");

	debut_gauche();
	
	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'config_multilang'),'data'=>''));
	creer_colonne_droite();
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'config_multilang'),'data'=>''));
debut_droite();

	$referenceur = charger_fonction('referenceur', 'configuration');
	echo $referenceur();

	calculer_langues_utilisees();

	if ($GLOBALS['meta']['multi_articles'] == "oui"
	OR $GLOBALS['meta']['multi_rubriques'] == "oui"
	OR count(explode(',',$GLOBALS['meta']['langues_utilisees'])) > 1) {
		$locuteur = charger_fonction('locuteur', 'configuration');
		echo $locuteur();
	}

	echo fin_gauche(), fin_page();
}
?>
