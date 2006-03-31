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

function article_select($id_article, $id_rubrique, $lier_trad, $new)
{
  global $connect_id_auteur, $spip_lang; 
  $id_article = intval($id_article);
  $id_rubrique =  intval($id_rubrique);
  $lier_trad =  intval($lier_trad);

// ESSAI pour "Joindre un document" depuis l'espace prive (UPLOAD_DIRECT)
/*if ($GLOBALS['action'] AND $GLOBALS['doc']) {
	global $action, $doc;
	if ($var_nom = charger_fonction($action, 'action'))
		$var_nom($doc);
	else
		spip_log("fonction $var_nom indisponible");
#	return;
}*/

  if ($id_article) {
	$result = spip_query("SELECT * FROM spip_articles WHERE id_article=$id_article");

	if ($row = spip_fetch_array($result)) {
		$titre = $row["titre"];
		$id_rubrique = $row["id_rubrique"];
		$id_secteur = $row['id_secteur'];
		$statut = $row['statut'];

		$result_auteur = spip_query("SELECT * FROM spip_auteurs_articles WHERE id_article=$id_article AND id_auteur=$connect_id_auteur");

		$flag_auteur = (spip_num_rows($result_auteur) > 0);

		$flag_editable = (acces_rubrique($id_rubrique) OR ($flag_auteur > 0 AND ($statut == 'prepa' OR $statut == 'prop' OR $new == 'oui')));
 	}
}
else if ($new=='oui') {
	if ($lier_trad) {
		// Pas de langue choisie par defaut
		$changer_lang = '';

		// Recuperer les donnees de la traduction
		$query = "SELECT * FROM spip_articles WHERE id_article=$lier_trad";
		$result = spip_query($query);
	
		if ($row = spip_fetch_array($result)) {
			$row['titre'] = filtrer_entites(_T('info_nouvelle_traduction')).' '.$row["titre"];

		}
		$langues_autorisees = $GLOBALS['meta']['langues_multilingue'];
		
		// Regler la langue, si possible
		if (ereg(",$spip_lang,", ",$langues_autorisees,")) {
			if ($GLOBALS['meta']['multi_articles'] == 'oui') {
				// Si le menu de langues est autorise sur les articles,
				// on peut changer la langue quelle que soit la rubrique
				$changer_lang = $spip_lang;
			}
			else if ($GLOBALS['meta']['multi_rubriques'] == 'oui') {
				// Chercher la rubrique la plus adaptee pour accueillir l'article
				if ($GLOBALS['meta']['multi_secteurs'] == 'oui') 
					$id_parent = 0;
				else {
					$query = "SELECT id_parent FROM spip_rubriques WHERE id_rubrique=$id_rubrique";
					$row_rub = spip_fetch_array(spip_query($query));
					$id_parent = $row_rub['id_parent'];
				}
				$query = "SELECT id_rubrique FROM spip_rubriques WHERE lang='$spip_lang' AND id_parent=$id_parent";
				if ($row_rub = spip_fetch_array(spip_query($query))) {
					$id_rubrique = $row['id_secteur'] = $row['id_rubrique'] = $row_rub['id_rubrique'];
					$changer_lang = 'herit';
				}
			}
		}
	}
	else {
		// Nouvel article : titre par defaut
		$row['titre'] = filtrer_entites(_T('info_nouvel_article'));
		$row['onfocus'] = " onfocus=\"if(!antifocus){this.value='';antifocus=true;}\"";
		$row['id_rubrique'] = $id_rubrique;
	}
	if (!$row['id_secteur']) {
		$row_rub = spip_fetch_array(spip_query("SELECT id_secteur FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
		$row['id_secteur'] = $row_rub['id_secteur'];
	}
	$flag_editable = true;
 }

	return $flag_editable ? $row : false;


}

?>
