<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_INDEX")) return;
define("_ECRIRE_INC_INDEX", "1");


function separateurs_indexation($requete = false) {
	// Merci a Herve Lefebvre pour son apport sur cette fonction
	$liste = "],:;*\"!\r\n\t\\/){}[|@<>$%";

	// en vietnamien ne pas eliminer les accents de translitteration
	if (!(lire_meta('langue_site') == 'vi' AND $requete))
		$liste .= "'`?\~.^+(-";

	// windowzeries iso-8859-1
	$charset = lire_meta('charset');
	if ($charset == 'iso-8859-1')
		$liste .= chr(187).chr(171).chr(133).chr(145).chr(146).chr(180).chr(147).chr(148);

	return $liste;
}

function spip_split($reg, $texte) {
	global $flag_pcre;
	if ($flag_pcre)
		return preg_split("/$reg/", $texte);
	else
		return split($reg, $texte);
}

function nettoyer_chaine_indexation($texte) {
	include_ecrire("inc_charsets.php3");
	$texte = strtolower(translitteration($texte));

	if (lire_meta('langue_site') == 'vi')
		$texte = strtr($texte, "'`?~.^+(-", "123456789");

	return $texte;
}

function indexer_chaine($texte, $val = 1, $min_long = 3) {
	global $index, $mots;

	$texte = ' '.ereg_replace("<[^>]*>"," ",$texte).' ';	// supprimer_tags()
	$texte = nettoyer_chaine_indexation($texte);

	$regs = separateurs_indexation();
	$texte = strtr($texte, $regs, ereg_replace('.', ' ', $regs));
	$table = spip_split(" +", $texte);

	while (list(, $mot) = each($table)) {
		if (strlen($mot) > $min_long or
			(ereg("[A-Z][A-Z][A-Z]", $mot) and $mot = strtolower($mot).'_')) {
				$h = substr(md5($mot), 0, 16);
				$index[$h] += $val;
				$mots .= ",(0x$h,'$mot')";
			}
	}
}

function deja_indexe($type, $id_objet) {
	$table_index = 'spip_index_'.$type.'s';
	$col_id = 'id_'.$type;
	$query = "SELECT COUNT(*) FROM $table_index WHERE $col_id=$id_objet";
	list($n) = @spip_fetch_array(@spip_query($query));
	return ($n > 0);
}

function indexer_objet($type, $id_objet, $forcer_reset = true, $full = true) {
	global $index, $mots;

	if (!$id_objet OR (!$forcer_reset AND deja_indexe($type, $id_objet))) return;

	$index = '';
	$mots = "INSERT IGNORE INTO spip_index_dico (hash, dico) VALUES (0,'')";

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
		while ($row = spip_fetch_array($result)) {
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
		while ($row = spip_fetch_array($result2)) {
			indexer_chaine($row['titre'], 12);
			indexer_chaine($row['descriptif'], 3);
		}
	
		$query3 = "SELECT auteurs.* FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien WHERE lien.id_article=$id_objet AND auteurs.id_auteur=lien.id_auteur";
		$result3 = spip_query($query3);
		while ($row = spip_fetch_array($result3)) {
			indexer_chaine($row['nom'], 10);
		}
		break;

	case 'breve':
		$query = "SELECT * FROM spip_breves WHERE id_breve=$id_objet";
		$result = spip_query($query);
		while($row = spip_fetch_array($result)) {
			indexer_chaine($row['titre'], 8);
			if ($full) {
				indexer_chaine($row['texte'], 2);
			}
		}
		break;

	case 'rubrique':
		$query = "SELECT * FROM spip_rubriques WHERE id_rubrique=$id_objet";
		$result = spip_query($query);
		while($row = spip_fetch_array($result)) {
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
		while($row = spip_fetch_array($result)){
			indexer_chaine($row['nom'], 5);
			if ($full) {
				indexer_chaine($row['bio'], 1);
			}
		}
		break;

	case 'mot':
		$query = "SELECT * FROM spip_mots WHERE id_mot=$id_objet";
		$result = spip_query($query);
		while($row = spip_fetch_array($result)){
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
		while($row = spip_fetch_array($result)) {
			indexer_chaine($row['nom_site'], 50);
			indexer_chaine($row['descriptif'], 30);

			if ($full) {
				// Ajouter les titres des articles syndiques de ce site, le cas echeant
				if ($row['syndication'] = "oui") {
					$query_syndic = "SELECT titre FROM spip_syndic_articles WHERE id_syndic=$id_objet ORDER BY date DESC LIMIT 0,100";
					$result_syndic = spip_query($query_syndic);
					while ($row_syndic = spip_fetch_array($result_syndic)) {
						indexer_chaine($row_syndic['titre'], 5);
					}
				}
				// Aller chercher la page d'accueil
				if (lire_meta("visiter_sites") == "oui") {
					include_ecrire ("inc_sites.php3");
					spip_log ("indexation contenu syndic ".$row['url_site']);
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
		spip_query("INSERT INTO $table_index (hash, points, $col_id) VALUES ".join(',',$q));
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
			while ($row = spip_fetch_array($result)) {
				$id_syndic = $row['id_syndic'];
				spip_query("UPDATE spip_syndic SET date_index=NOW() WHERE id_syndic=$id_syndic");
				indexer_syndic($id_syndic);
			}
		}
	}
}

function creer_liste_indexation() {
	$fichier_index = 'data/.index';
	$elements = array('article', 'breve', 'mot', 'auteur', 'rubrique', 'syndic');

	while (list(,$element) = each ($elements)) {
		$table = "spip_".$element."s";
		if ($element == 'syndic') $table = 'spip_syndic';
		switch($element) {
			case 'article':
			case 'breve':
			case 'syndic':
				$statut = "WHERE statut='publie'";
				break;
			case 'auteur':
				$statut = "WHERE statut IN ('0minirezo', '1comite')";
				break;
			default:
				$statut = '';
		}

		$res = spip_query("SELECT id_$element FROM $table $statut");
		while ($row = spip_fetch_array($res))
			$liste .= "$element ".$row["id_$element"]."\n";
	}

	if ($f = @fopen("$fichier_index", "w")) {
		@fputs($f, $liste);
		@fclose($f);
	}
}

function purger_index() {
		spip_query("DELETE FROM spip_index_articles");
		spip_query("DELETE FROM spip_index_auteurs");
		spip_query("DELETE FROM spip_index_breves");
		spip_query("DELETE FROM spip_index_mots");
		spip_query("DELETE FROM spip_index_rubriques");
		spip_query("DELETE FROM spip_index_syndic");
		spip_query("DELETE FROM spip_index_dico");
}

// cree la requete pour une recherche en txt integral
function requete_txt_integral($objet, $hash_recherche) {
	if ($objet == 'syndic') {
		$table = "spip_".$objet;
		$index_table = "spip_index_".$objet;
	} else {
		$table = "spip_".$objet."s";
		$index_table = "spip_index_".$objet."s";
	}
	$id_objet = "id_".$objet;
	return "SELECT objet.*, SUM(idx.points) AS points
		FROM $table AS objet, $index_table AS idx
		WHERE objet.$id_objet = idx.$id_objet
		AND idx.hash IN ($hash_recherche)
		GROUP BY objet.$id_objet
		ORDER BY points DESC
		LIMIT 0,10";
}

// rechercher un mot dans le dico
function requete_dico ($val) {
	$min_long = 3;

	// cas particulier translitteration vietnamien
	if (lire_meta('langue_site') == 'vi') {
		// 1. recuperer des accents passes sous la forme a`
		$val = strtr($val, "'`?~.^+(-","123456789");
		// 2. translitterer les accents passes en unicode
		$val = nettoyer_chaine_indexation($val);
		// 3. composer la regexp pour les caracteres accentuables mais non accentues
		while (ereg("([aeiouyd])([a-z])", $val.' ', $match))
			$val = str_replace ($match[0], $match[1].'[-1-9]?[-1-9]?'.$match[2], $val);
		return "dico REGEXP '^$val'";
	}

	// cas normal
	$val = nettoyer_chaine_indexation($val);
	if (strlen($val) > $min_long)
		return "dico LIKE '$val%'";
	else if (strlen($val) == $min_long) {
		return "dico = '".$val."_'";
	}
}


// decode la chaine recherchee et la traduit en hash
function requete_hash ($rech) {
	// recupere les mots de la recherche
	$regs = separateurs_indexation(true)." ";
	$rech = strtr($rech, $regs, ereg_replace('.', ' ', $regs));
	$s = spip_split(" +", $rech);
	unset($dico);
	unset($h);

	// cherche les mots dans le dico
	while (list(, $val) = each($s))
		if ($rq = requete_dico ($val))
			$dico[] = $rq;

	// compose la recherche dans l'index
	if ($dico) {
		$query2 = "SELECT HEX(hash) AS hx FROM spip_index_dico WHERE ".join(" OR ", $dico);
		$result2 = spip_query($query2);
		while ($row2 = spip_fetch_array($result2))
			$h[] = "0x".$row2["hx"];
	}
	if ($h)
		$hash_recherche = join(",", $h);
	else
		$hash_recherche = "0";

	return $hash_recherche;
}

?>
