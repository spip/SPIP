<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');
charger_generer_url();

// http://doc.spip.org/@exec_forum_dist
function exec_forum_dist()
{
	forum_affiche(intval(_request('debut')));
}

// http://doc.spip.org/@forum_affiche
function forum_affiche($debut, $admin=false)
{
	pipeline('exec_init',array('args'=>array('exec'=>'forum'),'data'=>''));

	$commencer_page = charger_fonction('commencer_page', 'inc');
	if ($admin) {
		echo $commencer_page(_T('titre_page_forum'), "forum", "privadm");
		$statutforum = 'privadm';
		$script = 'forum_admin';
		$titre = gros_titre(_T('titre_cadre_forum_administrateur'),'', false);
	} else {
		echo $commencer_page(_T('titre_forum'), "forum", "forum-interne");
		$statutforum = 'privrac';
		$script = 'forum';
		$titre = gros_titre(_T('titre_cadre_forum_interne'),'', false);
	}

  	echo debut_gauche('', true);
	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'forum'),'data'=>''));
	echo creer_colonne_droite('', true);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'forum'),'data'=>''));

	echo debut_droite('', true), $titre;

	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'forum'),'data'=>''));

	$discuter = charger_fonction('discuter', 'inc');
	echo $discuter(0, $script, '', $statutforum, $debut);
	echo fin_gauche(), fin_page();
}
?>
