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

// http://doc.spip.org/@generer_url_ecrire_forum
function urls_generer_url_ecrire_forum_dist($id, $args='', $ancre='', $public=null, $connect='') {
	$a = "id_forum=" . intval($id);
	if (is_null($public) AND !$connect)
		$public = objet_test_si_publie('forum', $id, $connect);
	$h = ($public OR $connect)
	?  generer_url_entite_absolue($id, 'forum', $args, $ancre, $connect)
	: (generer_url_ecrire('controler_forum', "debut_forum=@$id" . ($args ? "&$args" : ''))
		. ($ancre ? "#$ancre" : ''));
	return $h;
}