<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

# petit moteur de recherche sur les rubriques

function exec_rubriquer_dist()
{
	global $id;
	$id = intval($id);

	include_spip('inc/texte');
	include_spip('inc/mini_nav');
	ajax_retour(mini_nav($id, "aff_nav_recherche", 
			"document.location.href='" . generer_url_ecrire('naviguer', "id_rubrique=::sel::") .
			      "';", 0, true));
}
