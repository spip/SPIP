<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/filtres');

// http://doc.spip.org/@action_referencer_traduction_dist
function action_referencer_traduction_dist() {
	
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	if (preg_match(",^(\d+)$,", $arg, $r)
	AND $trad = intval(_request('lier_trad'))) {
		include_spip('action/editer_article');
		if ($err = article_referent($r[1], array('lier_trad' => $trad)))
			redirige_par_entete(urldecode(_request('redirect')) . $err);
	} elseif (preg_match(",^(\d+)\D-(\d+)$,", $arg, $r))  {
	  // supprimer le lien de traduction
		spip_query("UPDATE spip_articles SET id_trad=0 WHERE id_article=" . $r[1]);
		// Verifier si l'ancien groupe ne comporte plus qu'un seul article. Alors mettre a zero.
		$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_articles WHERE id_trad=" . $r[2]));

		if ($cpt['n'] == 1)
			spip_query("UPDATE spip_articles SET id_trad = 0 WHERE id_trad=" . $r[2]);
	} elseif (preg_match(",^(\d+)\D(\d+)\D(\d+)$,", $arg, $r)) {
	  // modifier le groupe de traduction de $r[1] (SQL le trouvera)
		spip_query("UPDATE spip_articles SET id_trad = " . $r[3] . " WHERE id_trad =" . $r[2]);
	} elseif (preg_match(",^(\d+)\D(\d+)$,", $arg, $r)) {
		instituer_langue_article($r[1],$r[2]);
	} else {
		spip_log("action_referencer_traduction_dist $arg pas compris");
	}
}

// http://doc.spip.org/@instituer_langue_article
function instituer_langue_article($id_article, $id_rubrique) {

	$changer_lang = _request('changer_lang');

	if ($GLOBALS['meta']['multi_articles'] == 'oui' AND $changer_lang) {
		if ($changer_lang != "herit") {
			spip_query("UPDATE spip_articles SET lang=" . _q($changer_lang) . ", langue_choisie='oui' WHERE id_article=$id_article");
			include_spip('inc/rubriques');
			calculer_langues_utilisees();
		} else {
			$langue_parent = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=" . $id_rubrique));
			$langue_parent=$langue_parent['lang'];
			spip_query("UPDATE spip_articles SET lang=" . _q($langue_parent) . ", langue_choisie='non' WHERE id_article=$id_article");
		}
	}
}
?>
