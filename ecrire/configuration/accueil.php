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

function  configuration_bloc_votre_site()
{
	$adresse_site = entites_html($GLOBALS['meta']["adresse_site"]);
	$nom_site = entites_html($GLOBALS['meta']["nom_site"]);
	$email_webmaster = entites_html($GLOBALS['meta']["email_webmaster"]);
	$descriptif_site = entites_html($GLOBALS['meta']["descriptif_site"]);

	return debut_cadre_relief("", true, "", _T('info_nom_site').aide ("confnom"))
	. "<input type='text' name='nom_site' value=\"$nom_site\" size='40' class='forml' />"
	. fin_cadre_relief(true)

	. debut_cadre_relief("", true, "", _T('info_adresse_url'))
	. "<input type='text' name='adresse_site' value=\"$adresse_site/\" size='40' class='forml' />"
	. fin_cadre_relief(true)

	. debut_cadre_relief("", true, "", _T('entree_description_site'))
	. "<textarea name='descriptif_site' class='forml' rows='4' cols='40'>$descriptif_site</textarea>"
	. fin_cadre_relief(true)

	. "<div>&nbsp;</div>"
	
	. debut_cadre_relief("", true, "", _T('info_email_webmestre'))
	. "<input type='text' name='email_webmaster' value=\"$email_webmaster\" size='40' class='formo' />"
	. fin_cadre_relief(true);
}

function configuration_accueil_dist()
{
	$res = configuration_bloc_votre_site();

	$res = 
	debut_cadre_couleur("racine-site-24.gif", true).
	ajax_action_post('configurer', 'accueil', 'configuration','',$res)
	. fin_cadre_couleur(true)
	;

	return ajax_action_greffe('configurer-accueil','', $res);
}
?>
