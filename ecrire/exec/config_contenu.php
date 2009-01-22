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

// http://doc.spip.org/@exec_config_contenu_dist
function exec_config_contenu_dist()
{
	if (!autoriser('configurer', 'contenu')) {
		include_spip('inc/minipres');
		echo minipres();
	} else {

	$config = charger_fonction('config', 'inc');
	$config();

	pipeline('exec_init',array('args'=>array('exec'=>'config_contenu'),'data'=>''));
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_config_contenu'), "configuration", "configuration");

	echo "<br /><br /><br />\n";
	echo gros_titre(_T('titre_page_config_contenu'),'', false);
	echo barre_onglets("configuration", "interactivite");

	echo debut_gauche('', true);

	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'config_contenu'),'data'=>''));
	echo creer_colonne_droite('', true);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'config_contenu'),'data'=>''));
	echo debut_droite('', true);


	$redacteurs = charger_fonction('redacteurs', 'configuration');
	$visiteurs = charger_fonction('visiteurs', 'configuration');

	$messagerie_agenda = charger_fonction('messagerie_agenda', 'configuration');

	$annonces = charger_fonction('annonces', 'configuration');

	$res = "<div class='contenu_public'>";


	$res.= "<h3>"._T('titre_config_contenu_public')."</h3>\n";



	$res.= "</div>";

	$res.= "<div class='contenu_prive'>";

	/*
	 * Inscriptions de redacteurs et visiteurs depuis le site public
	 * (la balise FORMULAIRE_INSCRIPTION sert au deux)
	 */
	$res.=  $redacteurs() .  $visiteurs() . "<br />";



	$res.= "<h3>"._T('titre_config_contenu_prive')."</h3>\n";

	$res.= $messagerie_agenda();

	$res.= "</div>";

	$res.= "<div class='contenu_notifications'>";

	/*
	 * mails automatiques
	 *
	 */
	$res.= "<h3>"._T('titre_config_contenu_notifications')."</h3>\n";

	$res.=  $annonces();

	$res.= "</div>";

//
// Choix supplementaires proposees par les plugins
//
	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'config_contenu'),'data'=>$res));

	echo fin_gauche(), fin_page();
	}
}

?>
