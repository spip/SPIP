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

// http://doc.spip.org/@fragments_memoriser_dist
function fragments_memoriser_dist()
{
	global $flag_ob,$connect_id_auteur, $id_ajax_fonc;

	if ($flag_ob) ob_start();

	$res = spip_fetch_array(spip_query("SELECT variables FROM spip_ajax_fonc	WHERE id_ajax_fonc =" . spip_abstract_quote($id_ajax_fonc) . " AND id_auteur=$connect_id_auteur"));

	if ($res = unserialize($res["variables"])) {
		
		foreach($res as $i => $k) $$i = $k;

		include_spip('inc/presentation');		

		if ($fonction == "afficher_articles") {
			afficher_articles ($titre_table, $requete,
				$afficher_visites, $afficher_auteurs);
		}

		elseif ($fonction == "afficher_articles_trad") {
			afficher_articles_trad ($titre_table, $requete,
				$afficher_visites, $afficher_auteurs);
		}
	}

	if ($flag_ob) {
			$res = ob_get_contents();
			ob_end_clean();
			return $res;
	}
}
?>
