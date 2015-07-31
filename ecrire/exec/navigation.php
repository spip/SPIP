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

/**
 * Afficher en pleine page les sous items de navigation d'une entree principale du menu
 *
 */
function exec_navigation_dist()
{

	$menu = _request('menu');
	include_spip('inc/bandeau');
	
	$contexte = definir_barre_contexte();
	$boutons = definir_barre_boutons($contexte);
	if (!isset($boutons[$menu])){
		include_spip('inc/minipres');
		echo minipres();		
	} else {
	
		$titre = _T($boutons[$menu]->libelle);
	
		$commencer_page = charger_fonction('commencer_page', 'inc');
		echo $commencer_page($titre, "", "");

		echo debut_gauche('', true);
		echo pipeline('affiche_gauche',array('args'=>array('exec'=>'navigation', 'menu'=>$menu),'data'=>''));

		echo creer_colonne_droite('', true);
		echo pipeline('affiche_droite',array('args'=>array('exec'=>'navigation', 'menu'=>$menu),'data'=>''));
			
		echo debut_droite('', true);
		echo gros_titre($titre,'',false);

		$sous = bando_lister_sous_menu($boutons[$menu]->sousmenu,$contexte,"item");
	
		$res = $sous ? "<ul class='liste_items sous_navigation'>$sous</ul>":"";

		echo pipeline('affiche_milieu',array('args'=>array('exec'=>'navigation', 'menu'=>$menu),'data'=>$res));

		echo fin_gauche(), fin_page();
	}
}

?>