<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_INDEX")) return;
define("_ECRIRE_INC_INDEX", "1");

// Merci a phpDig (Antoine Bajolet) pour la fonction originale
function nettoyer_chaine_indexation($texte) {
	$accents =
		/* A */ chr(192).chr(193).chr(194).chr(195).chr(196).chr(197).
		/* a */ chr(224).chr(225).chr(226).chr(227).chr(228).chr(229).
		/* O */ chr(210).chr(211).chr(212).chr(213).chr(214).chr(216).
		/* o */ chr(242).chr(243).chr(244).chr(245).chr(246).chr(248).
		/* E */ chr(200).chr(201).chr(202).chr(203).
		/* e */ chr(232).chr(233).chr(234).chr(235).
		/* Cc */ chr(199).chr(231).
		/* I */ chr(204).chr(205).chr(206).chr(207).
		/* i */ chr(236).chr(237).chr(238).chr(239).
		/* U */ chr(217).chr(218).chr(219).chr(220).
		/* u */ chr(249).chr(250).chr(251).chr(252).
		/* yNn */ chr(255).chr(209).chr(241);
	$texte = ereg_replace("<[^<]*>", "", $texte);
	return strtolower(strtr($texte,
		$accents,
		"AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn"));
}

// Merci a Herve Lefebvre pour son apport sur cette fonction
function separateurs_indexation() {
	return "]_.,;`:*'\"«»?!\r\n\t\\/\~(){}[|&@<>$%#".
		chr(133).chr(145).chr(146).chr(180).chr(147).chr(148);
}

function indexer_chaine($texte, $val = 1, $min_long = 3) {
	global $index, $mots;

	$texte = nettoyer_chaine_indexation($texte);
	$regs = separateurs_indexation();
	$texte = strtr($texte, $regs, "                                                           ");
	$table = split(" +([^ ]{0,$min_long} +)*", $texte);
	while (list(, $mot) = each($table)) {
		$h = substr(md5($mot), 0, 16);
		$index[$h] += $val;
		$mots .= ",(0x$h,'$mot')";
	}
}

function deja_indexe($type, $id_objet) {
	$table_index = 'spip_index_'.$type.'s';
	$col_id = 'id_'.$type;
	$query = "SELECT COUNT(*) FROM $table_index WHERE $col_id=$id_objet";
	list($n) = @mysql_fetch_array(@spip_query($query));
	return ($n > 0);
}

function indexer_objet($type, $id_objet, $forcer_reset = true, $full = true) {
	global $index, $mots;

	if (!$forcer_reset AND deja_indexe($type, $id_objet)) return;

	$index = '';
	$mots = "INSERT DELAYED IGNORE spip_index_dico (hash, dico) VALUES (0,'')";

	if ($type != 'syndic'){
		$table_index = 'spip_index_'.$type.'s';
	} else {
		$table_index = "spip_index_".$type;
	}
	$col_id = 'id_'.$type;


	switch($type) {
	case 'article':
		$query = "SELECT * FROM spip_articles WHERE id_article=$id_objet";
		$result = spip_query($query);
		while ($row = mysql_fetch_array($result)) {
			indexer_chaine($row['titre'], 8);
			indexer_chaine($row['soustitre'], 5);
			indexer_chaine($row['surtitre'], 5);
			indexer_chaine($row['descriptif'], 4);
			if ($full) {
				indexer_chaine($row['chapo'], 3);
				indexer_chaine($row['texte'], 1);
				indexer_chaine($row['ps'], 1);
			}
		}
	
		$query2 = "SELECT mots.* FROM spip_mots AS mots, spip_mots_articles AS lien WHERE lien.id_article=$id_objet AND mots.id_mot=lien.id_mot";
		$result2 = spip_query($query2);
		while ($row = mysql_fetch_array($result2)) {
			indexer_chaine($row['titre'], 12);
			indexer_chaine($row['descriptif'], 3);
		}
	
		$query3 = "SELECT auteurs.* FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien WHERE lien.id_article=$id_objet AND auteurs.id_auteur=lien.id_auteur";
		$result3 = spip_query($query3);
		while ($row = mysql_fetch_array($result3)) {
			indexer_chaine($row['nom'], 10);
		}
		break;

	case 'breve':
		$query = "SELECT * FROM spip_breves WHERE id_breve=$id_objet";
		$result = spip_query($query);
		while($row = mysql_fetch_array($result)) {
			indexer_chaine($row['titre'], 8);
			if ($full) {
				indexer_chaine($row['texte'], 2);
			}
		}
		break;

	case 'rubrique':
		$query = "SELECT * FROM spip_rubriques WHERE id_rubrique=$id_objet";
		$result = spip_query($query);
		while($row = mysql_fetch_array($result)) {
			indexer_chaine($row['titre'], 8);
			indexer_chaine($row['descriptif'], 5);
			if ($full) {
				indexer_chaine($row['texte'], 1);
			}
		}
		break;

	case 'auteur':	
		$query = "SELECT * FROM spip_auteurs WHERE id_auteur=$id_objet";
		$result = spip_query($query);
		while($row = mysql_fetch_array($result)){
			indexer_chaine($row['nom'], 5);
			if ($full) {
				indexer_chaine($row['bio'], 1);
			}
		}
		break;

	case 'mot':
		$query = "SELECT * FROM spip_mots WHERE id_mot=$id_objet";
		$result = spip_query($query);
		while($row = mysql_fetch_array($result)){
			indexer_chaine($row['titre'], 8);
			indexer_chaine($row['descriptif'], 5);
			if ($full) {
				indexer_chaine($row['texte'], 1);
			}
		}
		break;

	case 'syndic':
		$query = "SELECT * FROM spip_syndic WHERE id_syndic=$id_objet";
		$result = spip_query($query);
		while($row = mysql_fetch_array($result)) {
			indexer_chaine($row['nom_site'], 50);
			indexer_chaine($row['descriptif'], 30);

			if ($full) {
				// Ajouter les titres des articles syndiques de ce site, le cas echeant
				if ($row['syndication'] = "oui") {
					$query_syndic = "SELECT titre FROM spip_syndic_articles WHERE id_syndic=$id_objet ORDER BY date DESC LIMIT 0,100";
					$result_syndic = spip_query($query_syndic);
					while ($row_syndic = mysql_fetch_array($result_syndic)) {
						indexer_chaine($row_syndic['titre'], 5);
					}
				}
				// Aller chercher la page d'accueil
				if (lire_meta("visiter_sites") == "oui") {
					indexer_chaine(supprimer_tags(substr(recuperer_page($row['url_site']), 0, 50000)), 1);
				}
			}
		}
		break;

	} // switch

	$query = "DELETE FROM $table_index WHERE $col_id=$id_objet";
	$result = spip_query($query);

	if ($index) {
		spip_query($mots);
		reset($index);
		unset($q);
		while (list($hash, $points) = each($index)) $q[] = "(0x$hash,$points,$id_objet)";
		spip_query("INSERT $table_index (hash, points, $col_id) VALUES ".join(',',$q));
	}
}


function indexer_article($id_article, $forcer_reset = true, $full = true) {
	return indexer_objet('article', $id_article, $forcer_reset, $full);
}

function indexer_auteur($id_auteur, $forcer_reset = true, $full = true) {
	return indexer_objet('auteur', $id_auteur, $forcer_reset, $full);
}

function indexer_breve($id_breve, $forcer_reset = true, $full = true) {
	return indexer_objet('breve', $id_breve, $forcer_reset, $full);
}

function indexer_mot($id_mot, $forcer_reset = true, $full = true) {
	return indexer_objet('mot', $id_mot, $forcer_reset, $full);
}

function indexer_rubrique($id_rubrique, $forcer_reset = true, $full = true) {
	return indexer_objet('rubrique', $id_rubrique, $forcer_reset, $full);
}

function indexer_syndic($id_syndic, $forcer_reset = true, $full = true) {
	return indexer_objet('syndic', $id_syndic, $forcer_reset, $full);
}


function executer_une_indexation_syndic() {
	$visiter_sites = lire_meta("visiter_sites");
	if ($visiter_sites == "oui") {
		$query = "SELECT id_syndic FROM spip_syndic WHERE statut='publie' ".
			"AND date_index < DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY date_index LIMIT 0,1";
		if ($result = spip_query($query)) {
			while ($row = mysql_fetch_array($result)) {
				$id_syndic = $row[0];
				indexer_syndic($id_syndic);
				spip_query("UPDATE spip_syndic SET date_index=NOW() WHERE id_syndic=$id_syndic");
			}
		}
	}
}

?>