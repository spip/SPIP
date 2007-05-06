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

// http://doc.spip.org/@exec_configuration_dist
function exec_configuration_dist(){
	global $spip_display;

	if (!autoriser('configurer', 'configuration')) {
		echo minipres();
		exit;
	}

	init_config();
	lire_metas();

	pipeline('exec_init',array('args'=>array('exec'=>'configuration'),'data'=>''));

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_configuration'), "configuration", "configuration");
	
	echo "<br /><br /><br />\n";
	gros_titre(_T('titre_configuration'));
	echo barre_onglets("configuration", "contenu");
	
	debut_gauche();

	//
	// Le logo de notre site, c'est site{on,off}0.{gif,png,jpg}
	//
	if ($spip_display != 4) {
		$iconifier = charger_fonction('iconifier', 'inc');
		echo $iconifier('id_syndic', 0, 'configuration');
	}

	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'configuration'),'data'=>''));
	creer_colonne_droite();
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'configuration'),'data'=>''));
	debut_droite();

	echo avertissement_config();

	$accueil = charger_fonction('accueil', 'configuration');
	echo $accueil();

	echo debut_cadre_trait_couleur("article-24.gif", true, "", _T('titre_les_articles'));
	$articles = charger_fonction('articles', 'configuration');
	echo $articles();
	$futurs = charger_fonction('futurs', 'configuration');
	echo $futurs();
	echo fin_cadre_trait_couleur(true);

	$breves = charger_fonction('breves', 'configuration');
	echo $breves();

	$mots = charger_fonction('mots', 'configuration');
	echo $mots();

	$syndications = charger_fonction('syndications', 'configuration');
	echo $syndications();

	$documents = charger_fonction('documents', 'configuration');
	echo $documents();

	$res = pipeline('affiche_milieu',array('args'=>array('exec'=>'configuration'),'data'=>''));
	if ($res)
		echo ajax_action_post('configuration', '', 'configuration', '', $res);

	echo fin_gauche(), fin_page();
}
?>