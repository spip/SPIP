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

// Pour poser une variable de session
// poster sur cette action en indiquant var/val
// reponse : json contenant toutes les variables publiques de la session
// http://doc.spip.org/@action_session_dist
function action_session_dist()
{
	if ($var = _request('var')
	AND preg_match(',^[a-z_0-9-]+$,i', $var)
	) {
		if ($_SERVER['REQUEST_METHOD'] == 'POST'){
			include_spip('inc/session');
			session_set('session_'.$var, $val=_request('val'));
			#spip_log("autosave:$var:$val",'autosave');
		}
	}

	# TODO: mode lecture de session ; n'afficher que ce qu'il faut
	#echo json_encode($GLOBALS['visiteur_session']);
}


?>
