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
 * On arrive ici depuis le #FORMULAIRE_RESTAURER
 * - l'initialisation a ete faite avant redirection
 * - on enchaine sur inc/restaurer, qui remplit le dump et renvoie ici a chaque timeout
 * - a chaque coup on relance inc/restaurer
 * - lorsque inc/restaurer a fini, il retourne true
 * - on renvoie vers exec=restaurer pour afficher le resume
 *
 */

include_spip('base/dump');
include_spip('inc/dump');

/**
 * Sauvegarder par morceaux
 * 
 * @param string $arg
 */
function action_restaurer_dist($arg=null){
	if (!$arg) {
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	$status_file = $arg;
	define('_DUMP_STATUS_FILE',$status_file);
	$status_file = _DIR_TMP.basename($status_file).".txt";
	if (!lire_fichier($status_file, $status)
		OR !$status = unserialize($status)) {

		include_spip('inc/headers');
		echo redirige_formulaire(generer_url_ecrire("restaurer",'status='._DUMP_STATUS_FILE,'',true, true));
	}
	else {
		utiliser_langue_visiteur();
		$archive = "<br />".joli_repertoire($status['archive']);
		$action = _T('dump:info_restauration_sauvegarde', array('archive' => $archive));
		$admin = charger_fonction('admin', 'inc');
		echo $admin('restaurer', $action, "", true);
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