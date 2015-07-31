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
	if (!autoriser('detruire')) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}

	pipeline('exec_init',array('args'=>array('exec'=>'admin_effacer'),'data'=>''));

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_admin_effacer'), "configuration", "base");

	echo "\n<br /><br /><br />";
	echo gros_titre(_T('titre_admin_effacer'),'',false);
	echo barre_onglets("administration", "effacer");

	echo debut_gauche('',true);
	echo debut_boite_info(true);

	echo _T('info_gauche_admin_effacer');

	echo fin_boite_info(true);
	
	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'admin_effacer'),'data'=>''));	  
	
	echo creer_colonne_droite('',true);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'admin_effacer'),'data'=>''));	  
	
	echo debut_droite('',true);
	echo debut_cadre_trait_couleur('',true,'',_T('texte_effacer_base'));

	$res = "\n<input type='hidden' name='reinstall' value='non' />";

	$res = generer_form_ecrire('delete_all', $res, '', _T('bouton_effacer_tout'));

	echo 
		'<img src="' . _DIR_IMG_PACK . 'warning.gif" alt="',
	  	_T('info_avertissement'),
		"\" style='width: 48px; height: 48px; float: right;margin: 10px;' />",
		_T('texte_admin_effacer_01'),
		"<br class='nettoyeur' />",
		"\n<div style='text-align: center'>",
		debut_boite_alerte(),
		"\n<div class='serif'>",
		"\n<b>"._T('avis_suppression_base')."&nbsp;!</b>",
		$res,
		"\n</div>",
		fin_boite_alerte(),
		"</div>";

	echo fin_cadre_relief(true);
	
	echo debut_cadre_trait_couleur('',true,'',_L('Effacer les statistiques') /*_T('texte_effacer_statistiques')*/);

	$res = generer_form_ecrire('delete_statistiques', "", '', _L('Effacer les statistiques')/*_T('bouton_effacer_statistiques')*/);

	echo 
		'<img src="' . _DIR_IMG_PACK . 'warning.gif" alt="',
	  	_T('info_avertissement'),
		"\" style='width: 48px; height: 48px; float: right;margin: 10px;' />",
		//_T('texte_admin_effacer_stats'),
		_L('Cette commande efface toutes les donn&eacute;es li&eacute;es aux statistiques de visite du site, y compris la popularit&eacute; des articles.'),
		"<br class='nettoyeur' />",
		"\n<div style='text-align: center'>",
		"\n<div class='serif'>",
		"\n<b>"._T('avis_suppression_base')."&nbsp;!</b>",
		$res,
		"\n</div>",
		"</div>";

	echo fin_cadre_relief(true);

	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'admin_effacer'),'data'=>''));	  

	echo fin_gauche(), fin_page();



}
?>
