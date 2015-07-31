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

include_spip('inc/autoriser');
include_spip('inc/presentation');
include_spip('inc/config');
include_spip('inc/premiers_pas');

function premiers_pas_pas_1_dist(){
	global $spip_lang_right, $spip_lang_left;
	global $connect_id_auteur;
	
	$nom = "";
	$res = spip_query("SELECT nom FROM spip_auteurs WHERE id_auteur="._q($connect_id_auteur));
	if ($row = spip_fetch_array($res))
		$nom = $row['nom'];
	$texte = "<p class='verdana3' style='text-align: $spip_lang_left'>";
	$texte .= _L("Vous venez d'installer SPIP.<br/> Prenons quelques instants pour le configurer ensemble avant de commencer &agrave; l'utiliser.");
	$texte .= "</p>";
	
	echo premiers_pas_etapes(1,_L("F&eacute;licitations $nom !"),$texte);
}

function premiers_pas_pas_1_gauche_dist(){
	//
	// Le logo de notre site, c'est site{on,off}0.{gif,png,jpg}
	//
	if ($spip_display != 4) {
		$iconifier = charger_fonction('iconifier', 'inc');
		echo $iconifier('id_syndic', 0, 'accueil',true);
	}	
}

function premiers_pas_pas_1_milieu_dist(){
	include_spip('exec/configuration');
	//
	// Afficher les options de config
	//
	echo configuration_bloc_votre_site(false);
}

?>