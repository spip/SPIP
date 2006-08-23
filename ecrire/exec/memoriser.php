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

// http://doc.spip.org/@exec_memoriser_dist
function exec_memoriser_dist()
{
	global $flag_ob;

	if ($flag_ob) ob_start();
	
	ajax_page_sql();

	if ($flag_ob) {
			$a = ob_get_contents();
			ob_end_clean();
			return $a;
	}
}

# Une fonction stockee en base de donnees 

// http://doc.spip.org/@ajax_page_sql
function ajax_page_sql()
{
	global $connect_id_auteur;
	global $id, $exclus, $col, $id_ajax_fonc, $type, $rac;
	$id = intval($id);
	$exclus = intval($exclus);
	$col = intval($col);

	$res = spip_query("SELECT variables FROM spip_ajax_fonc	WHERE id_ajax_fonc =" . spip_abstract_quote($id_ajax_fonc) . " AND id_auteur=$connect_id_auteur");
	if ($row = spip_fetch_array($res)) {
		
		$variables = unserialize($row["variables"]);
		while (list($i, $k) = each($variables)) {
			$$i = $k;
			
		}
		include_spip('inc/presentation');		
		// Appliquer la fonction
		if ($fonction == "afficher_articles") {
			afficher_articles ($titre_table, $requete,
				$afficher_visites, $afficher_auteurs);
		}

		elseif ($fonction == "afficher_articles_trad") {
			afficher_articles_trad ($titre_table, $requete,
				$afficher_visites, $afficher_auteurs);
		}
		elseif ($fonction == "afficher_groupe_mots") {
			include_spip('inc/texte');
			include_spip('exec/mots_tous');
			echo afficher_groupe_mots ($id_groupe);
		}
	}
}
?>
