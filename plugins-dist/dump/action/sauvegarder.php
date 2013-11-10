<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 *
 * On arrive ici depuis le #FORMULAIRE_SAUVEGARDER
 * - l'initialisation a ete faite avant redirection
 * - on enchaine sur inc/sauvegarder, qui remplit le dump et renvoie ici a chaque timeout
 * - a chaque coup on relance inc/sauvegarder
 * - lorsque inc/sauvegarder a fini, il retourne true
 * - on renvoie vers exec=sauvegarder pour afficher le resume
 *
 */

include_spip('base/dump');
include_spip('inc/dump');

/**
 * Sauvegarder par morceaux
 * 
 * @param string $arg
 */
function action_sauvegarder_dist($arg=null){
	if (!$arg) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	$status_file = $arg;
	$redirect = parametre_url(generer_action_auteur('sauvegarder',$status_file),"step",intval(_request('step')+1),'&');

	// lancer export qui va se relancer jusqu'a sa fin
	$sauvegarder = charger_fonction('sauvegarder', 'inc');
	utiliser_langue_visiteur();
	// quand on sort de $export avec true c'est qu'on a fini
	if ($sauvegarder($status_file,$redirect)) {
		dump_end($status_file,'sauvegarder');
		include_spip('inc/headers');
		echo redirige_formulaire(generer_url_ecrire("sauvegarder",'status='.$status_file,'',true, true));
	}

	// forcer l'envoi du buffer par tous les moyens !
	echo(str_repeat("<br />\r\n",256));
	while (@ob_get_level()){
		@ob_flush();
		@flush();
		@ob_end_flush();
	}
}

?>