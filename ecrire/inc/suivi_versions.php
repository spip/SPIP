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

include_spip('inc/revisions');
include_spip('inc/diff');

// http://doc.spip.org/@afficher_para_modifies
function afficher_para_modifies ($texte, $court = false) {
	// Limiter la taille de l'affichage
	if ($court) $max = 200;
	else $max = 2000;
	
	$paras = explode ("\n",$texte);
	for ($i = 0; $i < count($paras) AND strlen($texte_ret) < $max; $i++) {
		if (strpos($paras[$i], '"diff-')) $texte_ret .= $paras[$i]."\n\n";
#		if (strlen($texte_ret) > $max) $texte_ret .= '(...)';
	}
	$texte = $texte_ret;
	return $texte;
}

// http://doc.spip.org/@afficher_suivi_versions
function afficher_suivi_versions ($debut = 0, $id_secteur = 0, $uniq_auteur = false, $lang = "", $court = false, $rss = false) {
	global $dir_lang;
	
	$nb_aff = 10;
	$champs = array('surtitre', 'titre', 'soustitre', 'descriptif', 'nom_site', 'url_site', 'chapo', 'texte', 'ps');

	if ($uniq_auteur) {
		$req_where = " AND articles.statut IN ('prepa','prop','publie')"; 
		$req_where .= " AND versions.id_auteur = $uniq_auteur";
	} else {
		$req_where = " AND articles.statut IN ('prop','publie')";
	}
	
	if (strlen($lang) > 0)
		$req_where .= " AND articles.lang=" . _q($lang);

	if ($id_secteur > 0)
		$req_where .= " AND articles.id_secteur = ".intval($id_secteur);

	$req_where = "versions.id_article = articles.id_article AND versions.id_version > 1 $req_where";

	$result = spip_query("SELECT versions.*, articles.statut, articles.titre FROM spip_versions AS versions, spip_articles AS articles WHERE $req_where ORDER BY versions.date DESC LIMIT $debut, $nb_aff");

	if (spip_num_rows($result) > 0) {

		$revisions = '';

		// Afficher l'entete de la boite
		if (!$rss) {
			$titre_table =  '<b>' . _T('icone_suivi_revisions').aide('suivimodif')  . '</b>';
			if ($court)
				$titre_table = afficher_plus(generer_url_ecrire("suivi_revisions"))
				. $titre_table;

			$revisions .= "\n<div style='height: 12px;'></div>";
			$revisions .= "\n<div class='liste'>";
			$revisions .= bandeau_titre_boite2($titre_table, "historique-24.gif", "white", "black", false);
	
			$total = spip_num_rows(spip_query("SELECT versions.*, articles.statut, articles.titre FROM spip_versions AS versions, spip_articles AS articles WHERE $req_where LIMIT 0, 149"));
		
			if ($total > $nb_aff) {
				$nb_tranches = ceil($total / $nb_aff);
			
				$revisions .= "\n<div class='arial2' style='background-color: #dddddd; padding: 5px;'>";
		
				for ($i = 0; $i < $nb_tranches; $i++) {
					if ($i > 0) $revisions .= " | ";
					if ($i*$nb_aff == $debut) $revisions .= "<b>";
					else {
					  $next = ($i * $nb_aff);
$revisions .= "<a href='".generer_url_ecrire('suivi_revisions', "debut=$next&id_secteur=$id_secteur&id_auteur=$uniq_auteur&lang_choisie=$lang")."'>";
					}
					$revisions .= (($i * $nb_aff) + 1);
					if ($i*$nb_aff == $debut) $revisions .= "</b>";
					else $revisions .= "</a>";
				}
				$revisions .= "</div>";
			}
		}

		// Afficher les 10 elements
		while ($row = spip_fetch_array($result)) {
			$id_version = $row['id_version'];
			$id_auteur = $row['id_auteur'];
			$date = $row['date'];
			$id_article = $row['id_article'];
			if (autoriser('voir','article',$id_article)){
				$statut = $row['statut'];
				$titre = typo($row['titre']);
				
				// l'id_auteur peut etre un numero IP (edition anonyme)
				if ($row_auteur = spip_fetch_array(spip_query("SELECT nom,email FROM spip_auteurs	WHERE id_auteur = '".addslashes($id_auteur)."'"))) {
					$nom = typo($row_auteur["nom"]);
					$email = $row_auteur['email'];
				} else {
					$nom = $id_auteur;
					$email = '';
				}
		
				if (!$rss) {
					$logo_statut = "puce-".puce_statut($statut).".gif";
					$revisions .= "\n<div class='tr_liste' style='padding: 5px; border-top: 1px solid #aaaaaa;'>";
		
					$revisions .= "<span class='arial2'>";
					if (!$court) $revisions .= bouton_block_visible("$id_version-$id_article-$id_auteur");
					$revisions .= "<img src='" . _DIR_IMG_PACK . "$logo_statut' alt=' ' />&nbsp;";
					$revisions .= "<a class='$statut' style='font-weight: bold;' href='" . generer_url_ecrire("articles_versions","id_article=$id_article") . "'>$titre</a>";
					$revisions .= "</span>";
					$revisions .= "<span class='arial1'$dir_lang>";
					$revisions .= " ".date_relative($date)." "; # laisser un peu de privacy aux redacteurs
					if (strlen($nom)>0) $revisions .= "($nom)";
					$revisions .= "</span>";
				} else {
					$item = array(
						'title' => $titre,
						'url' => generer_url_ecrire("articles_versions","id_article=$id_article&id_version=$id_version"),
						'date' => $date,
						'author' => $nom,
						'email' => $email
					);
				}
	
				// "court" n'affiche pas les modifs
				if (!$court) {
					$textes = revision_comparee($id_article, $id_version, 'diff');
					if (!$rss)
						$revisions .= debut_block_visible("$id_version-$id_article-$id_auteur");
	
					if (is_array($textes))
					foreach ($textes as $var => $t) {
						if (strlen($t) > 0) {
							if (!$rss) $revisions .= "<blockquote class='serif1'>";
							$aff = propre_diff($t);
							if ($GLOBALS['les_notes']) {
								$aff .= '<p>'.$GLOBALS['les_notes'];
								$GLOBALS['les_notes'] = '';
							}
							if (!$rss) {
								$revisions .= $aff;
								$revisions .= "</blockquote>";
							} else
								$item['description'] = $aff;
						}
					}
					if (!$rss) $revisions .= fin_block();
				}
				
				if (!$rss) $revisions .= "</div>";
	
				if ($rss)
					$items[] = $item;
			}
		}		
		if (!$rss) $revisions .= "</div>";
	}

	if ($rss)
		return $items;
	else return $revisions;
}

// retourne un array() des champs modifies a la version id_version
// le format =
//    - diff => seulement les modifs (suivi_revisions)
//    - apercu => idem, mais en plus tres cout s'il y en a bcp
//    - complet => tout, avec surlignage des modifications (articles_versions)
// http://doc.spip.org/@revision_comparee
function revision_comparee($id_article, $id_version, $format='diff', $id_diff=NULL) {
	include_spip('inc/diff');

	// chercher le numero de la version precedente
	if (!$id_diff) {
		$result_diff = spip_query("SELECT id_version FROM spip_versions WHERE id_article=$id_article AND id_version<$id_version ORDER BY id_version DESC LIMIT 0,1");
		if ($result_diff) {
			$row_diff = spip_fetch_array($result_diff);
			$id_diff = $row_diff['id_version'];
		}
	}

	if ($id_version && $id_diff) {

		// si l'ordre est inverse, on remet a l'endroit
		if ($id_diff > $id_version) {
			$t = $id_version;
			$id_version = $id_diff;
			$id_diff = $t;
		}

		$old = recuperer_version($id_article, $id_diff);
		$new = recuperer_version($id_article, $id_version);

		$textes = array();

		// Mode "diff": on ne s'interesse qu'aux champs presents dans $new
		// Mode "complet": on veut afficher tous les champs
		switch ($format) {
			case 'complet':
				$champs = array('surtitre', 'titre', 'soustitre', 'descriptif', 'nom_site', 'url_site', 'chapo', 'texte', 'ps');
				break;
			case 'diff':
			case 'apercu':
			default:
				$champs = array_keys($new);
				break;
		}

		foreach ($champs as $champ) {
			// si la version precedente est partielle,
			// il faut remonter dans le temps
			$id_ref = $id_diff-1;
			while (!isset($old[$champ])
			AND $id_ref>0) {
				$prev = recuperer_version($id_article, $id_ref--);
				if (isset($prev[$champ]))
					$old[$champ] = $prev[$champ];
			}
			if (!strlen($new[$champ]) && !strlen($old[$champ])) continue;

			// si on n'a que le vieux, ou que le nouveau, on ne
			// l'affiche qu'en mode "complet"
			if ($format == 'complet')
				$textes[$champ] = strlen($new[$champ])
					? $new[$champ] : $old[$champ];

			// si on a les deux, le diff nous interesse, plus ou moins court
			if (isset($new[$champ])
			AND isset($old[$champ])) {
				$diff = new Diff(new DiffTexte);
				$n = preparer_diff($new[$champ]);
				$o = preparer_diff($old[$champ]);
				$textes[$champ] = afficher_diff($diff->comparer($n,$o));

				if ($format == 'diff' OR $format == 'apercu')
					$textes[$champ] = afficher_para_modifies($textes[$champ], ($format == 'apercu'));
			}
		}
	}

	// que donner par defaut ? (par exemple si id_version=1)
	if (!$textes)
		$textes = recuperer_version($id_article, $id_version);

	return $textes;
}

?>
