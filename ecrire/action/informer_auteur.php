<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@action_informer_auteur_dist
function action_informer_auteur_dist() {
	include_spip('base/abstract_sql');
	include_spip('inc/json');

	$row = array();
	if ($login=_request('var_login')){
		include_spip('inc/identifier_login');
		$row = informer_login($login);
		unset($row['id_auteur']);
	}
	echo json_export($row);
}


?>