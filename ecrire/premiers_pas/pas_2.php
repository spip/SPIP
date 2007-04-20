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

function premiers_pas_pas_2_dist(){
	global $spip_lang_right, $spip_lang_left;
	global $connect_id_auteur;
		
	$texte = "";
	return premiers_pas_etapes(2,_L("Commencer &agrave; construire votre site SPIP"),$texte);
}

function premiers_pas_pas_2_milieu_dist(){
	include_spip('exec/configuration');
	//
	// Afficher les options de config
	//
	return "<p>"
	. _L("Pour commencer &agrave; construire votre site Internet, voulez vous que SPIP cr&eacute;e un premier exemple de contenus ?")
	. "</p>"
	. afficher_choix('creer_contenu', 'oui',
		array('oui' => _L("Oui, je veux que SPIP cr&eacute;e un exemple de contenus"),
		'non' => _L('Non merci, je vais cr&eacute;er moi-m&ecirc;me mes contenus')));

}

?>