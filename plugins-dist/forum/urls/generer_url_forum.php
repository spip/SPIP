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

function urls_generer_url_forum_dist($id_forum, $args='', $ancre='') {
	if ($id_forum = intval($id_forum)) {
		include_spip('inc/forum');
		list($type, $id,) = racine_forum($id_forum);
		if ($type) {
			if (!$ancre) $ancre = "forum$id_forum";
			return generer_url_entite($id, $type, $args, $ancre, true);
		}
	}
	return '';
}

?>