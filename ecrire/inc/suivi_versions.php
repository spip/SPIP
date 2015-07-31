<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

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


// Retourne le titre de la rubrique demandee, pour affichage de la chaine
// "deplace de XX vers YY"
// http://doc.spip.org/@titre_rubrique
function titre_rubrique($id_rubrique) {
	if (!$id = intval($id_rubrique))
		return _T('info_sans_titre');

	return typo(sql_getfetsel('titre', 'spip_rubriques', "id_rubrique=$id"));
}


// http://doc.spip.org/@afficher_suivi_versions
function afficher_suivi_versions ($debut = 0, $id_secteur = 0, $uniq_auteur = false, $lang = "", $court = false) {
	
	changer_typo($lang);
	$lang_dir = lang_dir($lang);
	$nb_aff = 10;
	$champs = array('surtitre', 'titre', 'soustitre', 'descriptif', 'nom_site', 'url_site', 'chapo', 'texte', 'ps');

	if ($uniq_auteur) {
		$req_where = " AND articles.statut IN ('prepa','prop','publie')"; 
		$req_where .= " AND versions.id_auteur = $uniq_auteur";
	} else {
		$req_where = " AND articles.statut IN ('prop','publie')";
	}
	
	if (strlen($lang) > 0)
		$req_where .= " AND articles.lang=" . sql_quote($lang);

	if ($id_secteur > 0)
		$req_where .= " AND articles.id_secteur = ".intval($id_secteur);

	$req_where = "versions.id_version > 1 $req_where";

	$req_sel = "versions.id_version, versions.id_auteur, versions.date, versions.id_article, articles.statut, articles.titre";

	$req_from = 'spip_versions AS versions LEFT JOIN spip_articles AS articles ON versions.id_article = articles.id_article';

	$revisions = '';
	$items = array();
	$result = sql_select($req_sel, $req_from, $req_where, '', 'versions.date DESC', "$debut, $nb_aff");
	while ($row = sql_fetch($result)) {
			$id_article = $row['id_article'];
			if (autoriser('voir','article',$id_article)){
				$statut = $row['statut'];
				$id_version = $row['id_version'];
				$id_auteur = $row['id_auteur'];
				$date = $row['date'];
				$titre = typo(supprime_img($row['titre'],''));
				
				// l'id_auteur peut etre un numero IP (edition anonyme)
				if ($id_auteur == intval($id_auteur)
				AND $row_auteur = sql_fetsel('nom,email', 'spip_auteurs', "id_auteur = ".sql_quote($id_auteur))) {
					$nom = typo($row_auteur["nom"]);
					$email = $row_auteur['email'];
				} else {
					$nom = $id_auteur;
					$email = '';
				}

				$aff = revisions_bouton($id_article, $id_auteur, $id_version, $titre, $statut, $date, $lang_dir, $nom);
				if (!$court) {
						$bouton_id = "b$id_version-$id_article-$id_auteur";
						$aff = bouton_block_depliable($aff,false,$bouton_id)
						  . debut_block_depliable(false,$bouton_id)
						  . safehtml(revisions_diff ($id_article, $id_version, $court))
						  . fin_block();
				}
				$revisions .= "\n<div class='tr_liste' style='padding: 5px; border-top: 1px solid #aaaaaa;'>$aff</div>";
			}
	}
	if (!$revisions) return '';
	else return 
	  revisions_entete_boite($court, $debut, $id_secteur, $lang, $nb_aff, $req_from, $req_where, $uniq_auteur)
	  . $revisions
	  . fin_block()
	  . fin_cadre();
}

// http://doc.spip.org/@revisions_diff
function revisions_diff ($id_article, $id_version, $court=true)
{
	$textes = revision_comparee($id_article, $id_version, 'diff');
	if (!is_array($textes)) return $textes;
	$rev = '';
	$nb = 0;
	foreach ($textes as $var => $t) {
		if ($n=strlen($t)) {
			if ($court)
				$nb += $n;
			else {
				$aff = propre_diff($t);
				if ($GLOBALS['les_notes']) {
					$aff .= '<p>'.$GLOBALS['les_notes'].'</p>';
					$GLOBALS['les_notes'] = '';
				}
				$rev .= "<blockquote class='serif1'>$aff</blockquote>";
			}
		}
	}
	return $court ? _T('taille_octets', array('taille' => $nb)) : $rev;
}

// http://doc.spip.org/@revisions_bouton
function revisions_bouton($id_article, $id_auteur, $id_version, $titre, $statut, $date, $lang_dir, $nom)
{
	$titre_bouton = "<span class='arial2'>";
	$titre_bouton .= puce_statut($statut);
	$titre_bouton .= "\n&nbsp;<a class='$statut' style='font-weight: bold;' href='" . generer_url_ecrire("articles_versions","id_article=$id_article") . "'>$titre</a>";
	$titre_bouton .= "<span class='arial1' dir='$lang_dir'>";
	$titre_bouton .= "\n".date_relative($date)." "; # laisser un peu de privacy aux redacteurs
	$titre_bouton .= "</span>";
	if (strlen($nom)>0) $titre_bouton .= "($nom)";
	$titre_bouton .= "</span>";
	return $titre_bouton;
}

// http://doc.spip.org/@revisions_entete_boite
function revisions_entete_boite($court, $debut, $id_secteur, $lang, $nb_aff, $req_from, $req_where, $uniq_auteur)
{

	$titre_table =  '<b>' . _T('icone_suivi_revisions').aide('suivimodif')  . '</b>';
	if ($court)
		$titre_table = afficher_plus(generer_url_ecrire("suivi_revisions"))
		. $titre_table;

	$total = sql_countsel($req_from, $req_where);
	if ($total >= 150) $total = 149;
	$id_liste = 't'.substr(md5("$req_where 149"),0,8);
	$bouton = bouton_block_depliable($titre_table,true,$id_liste);
	$revisions = debut_cadre('liste',"historique-24.gif",'',$bouton)
	. debut_block_depliable(true,$id_liste);
		
	if ($total > $nb_aff) {
		$nb_tranches = ceil($total / $nb_aff);
			
		$revisions .= "\n<div class='arial2' style='background-color: #dddddd; padding: 5px;'>\n";
		
		for ($i = 0; $i < $nb_tranches; $i++) {
			if ($i > 0) $revisions .= " | ";
			if ($i*$nb_aff == $debut)
				$revisions .= "<b>";
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
	return $revisions;
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
		$id_diff = sql_getfetsel("id_version", "spip_versions", "id_article=" . intval($id_article) . " AND id_version < " . intval($id_version), "", "id_version DESC", "1");
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
				$champs = liste_champs_versionnes('spip_articles');
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
				// cas particulier : id_rubrique
				if (in_array($champ, array('id_rubrique'))) {
					$textes[$champ] = _T('version_deplace_rubrique',
										 array('from'=> titre_rubrique($old[$champ])
											   ,'to'=>titre_rubrique($new[$champ]))
										 );
				}
				
				// champs textuels
				else {
					$diff = new Diff(new DiffTexte);
					$n = preparer_diff($new[$champ]);
					$o = preparer_diff($old[$champ]);
					$textes[$champ] = afficher_diff($diff->comparer($n,$o));
					if ($format == 'diff' OR $format == 'apercu')
						$textes[$champ] = afficher_para_modifies($textes[$champ], ($format == 'apercu'));
				}
			}
		}
	}

	// que donner par defaut ? (par exemple si id_version=1)
	if (!$textes)
		$textes = recuperer_version($id_article, $id_version);

	return $textes;
}

?>
