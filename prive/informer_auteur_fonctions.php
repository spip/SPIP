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

// Filtre ad hoc pour le formulaire de login:
// le parametre var_login n'est pas dans le contexte pour optimiser le cache
// il faut aller le chercher a la main
function informer_auteur($bof)
{
	include_spip('inc/json');
	include_spip('formulaires/login');
	include_spip('inc/auth');
	$row = auth_informer_login(_request('var_login'));
	if ($row AND is_array($row))
		unset($row['id_auteur']);
	else {
		// piocher les infos sur un autre login
		$n = sql_countsel('spip_auteurs',"login<>''");
		$n = (abs(crc32(_request('var_login')))%$n);
		$row = auth_informer_login(sql_getfetsel('login','spip_auteurs',"login<>''",'','',"$n,1"));
		if ($row AND is_array($row)){
			unset($row['id_auteur']);
			$row['login'] = _request('var_login');
		}
	}

	return json_export($row);
}

?>