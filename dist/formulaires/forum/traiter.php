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

include_spip('inc/acces');
include_spip('inc/texte');
include_spip('inc/forum');
include_spip('base/abstract_sql');
spip_connect();

function formulaires_forum_traiter_dist(
$titre, $table, $type, $script,
$id_rubrique, $id_forum, $id_article, $id_breve, $id_syndic,
$ajouter_mot, $ajouter_groupe, $afficher_texte, $url_param_retour){

	$message = 'ok';
	$forum_insert = charger_fonction('forum_insert', 'inc');
	include_spip('inc/headers');
	return $message . redirige_formulaire($forum_insert());
}

?>