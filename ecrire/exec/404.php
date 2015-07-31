<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Un exec d'erreur
 */
function exec_404_dist(){

	$exec = _request('exec');

	$titre = "exec_$exec";
	$navigation = "";
	$extra = "";

	include_spip('inc/presentation'); // alleger les inclusions avec un inc/presentation_mini
	$commencer_page = charger_fonction('commencer_page','inc');
	echo $commencer_page($titre);

	echo debut_gauche("404_$exec",true);
	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'404','exec_erreur'=>$exec),'data'=>''));

	echo creer_colonne_droite("404",true);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'404','exec_erreur'=>$exec),'data'=>''));

	echo debut_droite("404",true);
	echo "<h1 class='grostitre'>"._T('fichier_introuvable',array('fichier'=>$exec))."</h1>";
	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'404','exec_erreur'=>$exec),'data'=>''));

	echo fin_gauche(),fin_page();
}

?>
