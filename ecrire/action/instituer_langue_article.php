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

// http://doc.spip.org/@action_instituer_langue_article_dist
function action_instituer_langue_article_dist() {

	include_spip('inc/actions');
	$var_f = charger_fonction('controler_action_auteur', 'inc');
	$var_f();

	$arg = _request('arg');
	$changer_lang = _request('changer_lang');

	if ($GLOBALS['meta']['multi_articles'] == 'oui' AND $changer_lang) {
		list($id_article, $id_rubrique) = preg_split('/\W/', $arg);
		if ($changer_lang != "herit")
			spip_query("UPDATE spip_articles SET lang=" . spip_abstract_quote($changer_lang) . ", langue_choisie='oui' WHERE id_article=$id_article");
		else {
			$langue_parent = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=" . $id_rubrique));
			$langue_parent=$langue_parent['lang'];
			spip_query("UPDATE spip_articles SET lang=" . spip_abstract_quote($langue_parent) . ", langue_choisie='non' WHERE id_article=$id_article");
			include_spip('inc/lang');
			calculer_langues_utilisees();
		}
	}
}
?>
