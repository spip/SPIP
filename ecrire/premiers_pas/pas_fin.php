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

function premiers_pas_pas_fin_dist(){
	global $spip_lang_right, $spip_lang_left;
	global $connect_id_auteur;
		
	$texte = "Bravo, vous avez termin&eacute; l'installation de SPIP !<br/>Vous pouvez maintenant commencer &agrave; l'utiliser.";
	echo premiers_pas_etapes('fin',_L("Commencer &agrave; utiliser SPIP"),$texte);
}

?>