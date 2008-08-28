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

// Filtre ad hoc pour le formulaire de login:
// le parametre var_login n'est pas dans le contexte pour optimiser le cache
// il faut aller le chercher a la main
function informer_auteur($bof)
{
  	include_spip('inc/json');
	include_spip('inc/identifier_login');
	$row = informer_login(_request('var_login'));
	if (is_array($row))
		unset($row['id_auteur']);
	else $row = array();
	return json_export($row);
}

?>