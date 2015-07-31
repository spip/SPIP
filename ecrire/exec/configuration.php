<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');

// http://doc.spip.org/@exec_configuration_dist
function exec_configuration_dist(){

	if (!autoriser('configurer', 'configuration')) {
		include_spip('inc/minipres');
		echo minipres();
	} else {

	$config = charger_fonction('config', 'inc');
	$config();

	pipeline('exec_init',array('args'=>array('exec'=>'configuration'),'data'=>''));

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_configuration'), "configuration", "configuration");

	echo gros_titre(_T('titre_configuration'),'', false);
	echo barre_onglets("configuration", "contenu");

	echo debut_gauche('', true);
	echo avertissement_config();

	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'configuration'),'data'=>''));
	echo creer_colonne_droite('', true);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'configuration'),'data'=>''));
	echo debut_droite('', true);


	echo debut_cadre_trait_couleur("article-24.gif", true, "", _T('titre_les_articles'));
	$articles = charger_fonction('articles', 'configuration');
	echo $articles();
	$futurs = charger_fonction('futurs', 'configuration');
	echo $futurs();
	$redirection = charger_fonction('redirection', 'configuration');
	echo $redirection();
	echo fin_cadre_trait_couleur(true);

	$rubriques = charger_fonction('rubriques', 'configuration');
	echo $rubriques();

	$breves = charger_fonction('breves', 'configuration');
	echo $breves();

	$mots = charger_fonction('mots', 'configuration');
	echo $mots();

	$logos = charger_fonction('logos', 'configuration');
	echo $logos();

	$documents = charger_fonction('documents', 'configuration');
	echo $documents();

	$syndications = charger_fonction('syndications', 'configuration');
	echo $syndications();

	$res = pipeline('affiche_milieu',array('args'=>array('exec'=>'configuration'),'data'=>''));
	if ($res)
		echo $res;

	echo fin_gauche(), fin_page();
	}
}
?>
