<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


//
if (!defined("_ECRIRE_INC_VERSION")) return;

function separateurs_indexation($requete = false) {
	// Merci a Herve Lefebvre pour son apport sur cette fonction
	$liste = "],:;*\"!\r\n\t\\/)}{[|@<>$%";

	// pour autoriser les recherches en vietnamien,
	// ne pas eliminer les accents de translitteration
	if (!$requete)
		$liste .= "'`?\~.^+(-";

	// windowzeries iso-8859-1
	$charset = $GLOBALS['meta']['charset'];
	if ($charset == 'iso-8859-1')
		$liste .= chr(187).chr(171).chr(133).chr(145).chr(146).chr(180).chr(147).chr(148);

	return $liste;
}

function nettoyer_chaine_indexation($texte) {
	global $translitteration_complexe;
	include_ecrire("inc_charsets.php3");

	// translitteration complexe (vietnamien, allemand)
	if ($translitteration_complexe) {
		$texte_c = translitteration_complexe ($texte);
		$texte_c = " ".strtr($texte_c, "'`?~.^+(-", "123456789");
	}

	$texte = translitteration($texte).$texte_c;

	return $texte;
}

function indexer_chaine($texte, $val = 1, $min_long = 3) {
	global $index, $mots, $translitteration_complexe;

	// Nettoyer les tags, entites HTML, signes diacritiques...
	$texte = ' '.ereg_replace("<[^>]*>"," ",$texte).' ';
	$texte = nettoyer_chaine_indexation($texte);

	// Nettoyer les caracteres non-alphanumeriques
	$regs = separateurs_indexation();
	$texte = strtr($texte, $regs, ereg_replace('.', ' ', $regs));

	// Cas particulier : sigles d'au moins deux lettres
	$texte = ereg_replace(" ([A-Z][0-9A-Z]{1,".($min_long - 1)."}) ", ' \\1___ ', $texte);
	$texte = strtolower($texte);

	// Separer les mots
	$table = preg_split("/ +/", $texte);

	while (list(, $mot) = each($table)) {
		if (strlen($mot) > $min_long) {
			$h = substr(md5($mot), 0, 16);
			$index[$h] += $val/(1+$translitteration_complexe);
			$mots .= ",(0x$h,'$mot')";
		}
	}
}

function deja_indexe($type, $id_objet) {
	$table_index = 'spip_index_'.table_objet($type);
	$col_id = 'id_'.$type;
	$query = "SELECT $col_id FROM $table_index WHERE $col_id=$id_objet LIMIT 1";
	$n = @spip_num_rows(@spip_query($query));
	return ($n > 0);
}


// Extracteur des documents 'txt'
function extracteur_txt($fichier, &$charset) {
	lire_fichier($fichier, $contenu);

	// Reconnaitre le BOM utf-8 (0xEFBBBF)
	include_ecrire('inc_charsets.php3');
	if (bom_utf8($contenu))
		$charset = 'utf-8';

	return $contenu;
}

// Extracteur des documents 'html'
function extracteur_html($fichier, &$charset) {
	lire_fichier($fichier, $contenu);

	// Importer dans le charset local
	include_ecrire('inc_charsets.php3');
	$contenu = transcoder_page($contenu);
	$charset = $GLOBALS['meta']['charset'];

	return $contenu;
}

// Quels formats sait-on extraire ?
$GLOBALS['extracteur'] = array (
	'txt'   => 'extracteur_txt',
	'pas'   => 'extracteur_txt',
	'c'     => 'extracteur_txt',
	'css'   => 'extracteur_txt',
	'html'  => 'extracteur_html'
);

// Indexer le contenu d'un document
function indexer_contenu_document ($row) {
	global $extracteur;

	if ($row['mode'] == 'vignette') return;
	list($extension) = spip_fetch_array(spip_query(
		"SELECT extension FROM spip_types_documents
		WHERE id_type = ".$row['id_type']
	));

	// Voir si on sait lire le contenu (eventuellement en chargeant le
	// fichier extract_pdf.php dans find_in_path() )
	if ($plugin = find_in_path('extract_'.$extension.'.php')) {
		include_local($plugin);
	}
	if (function_exists($lire = $extracteur[$extension])) {
		// Voir si on a deja une copie du doc distant
		// Note: si copie_locale() charge le doc, elle demande une reindexation
		if (!$fichier = copie_locale($row['fichier'], 'test')) {
			spip_log("pas de copie locale de '$fichier'");
			return;
		}
		// par defaut, on pense que l'extracteur va retourner ce charset
		$charset = 'iso-8859-1'; 
		// lire le contenu
		$contenu = $lire($fichier, $charset);
		if (!$contenu) {
			spip_log("Echec de l'extraction de '$fichier'");
		} else {
			// Ne retenir que les 50 premiers ko
			$contenu = substr($contenu, 0, 50000);
			// importer le charset
			$contenu = importer_charset($contenu, $charset);
			// Indexer le texte
			indexer_chaine($contenu, 1);
		}
	} else {
		spip_log("pas d'extracteur '$extension' fonctionnel");
	}
}


// Indexer les documents, auteurs, mots-cles associes a l'objet
function indexer_elements_associes($objet, $id_objet, $associe, $valeur) {
	switch ($associe) {
		case 'document':
			$r = spip_query("SELECT doc.titre, doc.descriptif
				FROM spip_documents AS doc,
				spip_documents_".table_objet($objet)." AS lien
				WHERE lien.".id_table_objet($objet)."=$id_objet
				AND doc.id_document=lien.id_document");
			while ($row = spip_fetch_array($r)) {
				indexer_chaine($row['titre'],2 * $valeur);
				indexer_chaine($row['descriptif'],1 * $valeur);
			}
			break;

		case 'auteur':
			$r = spip_query("SELECT auteurs.nom
				FROM spip_auteurs AS auteurs,
				spip_auteurs_".table_objet($objet)." AS lien
				WHERE lien.".id_table_objet($objet)."=$id_objet
				AND auteurs.id_auteur=lien.id_auteur");
			while ($row = spip_fetch_array($r)) {
				indexer_chaine($row['nom'], 1 * $valeur, 2);
			}
			break;

		case 'mot':
			$r = spip_query("SELECT mots.titre, mots.descriptif
				FROM spip_mots AS mots,
				spip_mots_".table_objet($objet)." AS lien
				WHERE lien.".id_table_objet($objet)."=$id_objet
				AND mots.id_mot = lien.id_mot");
			while ($row = spip_fetch_array($r)) {
				indexer_chaine($row['titre'],4 * $valeur);
				indexer_chaine($row['descriptif'],1 * $valeur);
			}
			break;
	}
}


function indexer_objet($type, $id_objet, $forcer_reset = true) {
	global $index, $mots, $translitteration_complexe;

	$table = 'spip_'.table_objet($type);
	$table_index = 'spip_index_'.table_objet($type);
	$col_id = id_table_objet($type);

	if (!$id_objet) return;
	if (!$forcer_reset AND deja_indexe($type, $id_objet)) {
		spip_log ("$type $id_objet deja indexe");
		spip_query("UPDATE $table SET idx='oui' WHERE $col_id=$id_objet");
		return;
	}
	// marquer "en cours d'indexation"
	spip_query("UPDATE $table SET idx='idx' WHERE $col_id=$id_objet");

	include_ecrire("inc_texte.php3");

	spip_log("indexation $type $id_objet");
	$index = '';
	$mots = '';

	$query = "SELECT * FROM $table WHERE $col_id=$id_objet";
	$result = spip_query($query);
	$row = spip_fetch_array($result);

	if (!$row) return;

	// translitteration complexe ?
	if (!$lang = $row['lang']) $lang = $GLOBALS['meta']['langue_site'];
	if ($lang == 'de' OR $lang=='vi') {
		$translitteration_complexe = 1;
		spip_log ('-> translitteration complexe');
	} else $translitteration_complexe = 0;

	switch($type) {
	case 'article':
		indexer_chaine($row['titre'], 8);
		indexer_chaine($row['soustitre'], 5);
		indexer_chaine($row['surtitre'], 5);
		indexer_chaine($row['descriptif'], 4);
		indexer_chaine($row['chapo'], 3);
		indexer_chaine($row['texte'], 1);
		indexer_chaine($row['ps'], 1);
		indexer_chaine($row['nom_site'], 1);
		indexer_chaine(@join(' ', unserialize($row['extra'])), 1);
		indexer_elements_associes('article', $id_objet, 'document', 1);
		indexer_elements_associes('article', $id_objet, 'auteur', 10);
		indexer_elements_associes('article', $id_objet, 'mot', 3);
		break;

	case 'breve':
		indexer_chaine($row['titre'], 8);
		indexer_chaine($row['texte'], 2);
		indexer_chaine(@join(' ', unserialize($row['extra'])), 1);
		indexer_elements_associes('breve', $id_objet, 'document', 1);
		indexer_elements_associes('breve', $id_objet, 'mot', 3);
		break;

	case 'rubrique':
		indexer_chaine($row['titre'], 8);
		indexer_chaine($row['descriptif'], 5);
		indexer_chaine($row['texte'], 1);
		indexer_chaine(@join(' ', unserialize($row['extra'])), 1);
		indexer_elements_associes('rubrique', $id_objet, 'document', 1);
		indexer_elements_associes('rubrique', $id_objet, 'mot', 3);
		break;

	case 'auteur':
		indexer_chaine($row['nom'], 5, 2);
		indexer_chaine($row['bio'], 1);
		indexer_chaine(@join(' ', unserialize($row['extra'])), 1);
		break;

	case 'mot':
		indexer_chaine($row['titre'], 8);
		indexer_chaine($row['descriptif'], 5);
		indexer_chaine($row['texte'], 1);
		indexer_chaine(@join(' ', unserialize($row['extra'])), 1);
		break;

	case 'signature':
		indexer_chaine($row['nom_email'], 2, 2);
		indexer_chaine($row['ad_email'], 2);
		indexer_chaine($row['nom_site'], 2);
		indexer_chaine($row['url_site'], 1);
		indexer_chaine($row['message'], 1);
		break;

	case 'syndic':
		indexer_chaine($row['nom_site'], 50);
		indexer_chaine($row['descriptif'], 30);
		indexer_elements_associes('syndic', $id_objet, 'document', 1);
		indexer_elements_associes('syndic', $id_objet, 'mot', 3);

		// Ajouter les titres des articles syndiques de ce site, le cas echeant
		if ($row['syndication'] = "oui") {
			$query_syndic = "SELECT titre FROM spip_syndic_articles
			WHERE id_syndic=$id_objet AND statut='publie'
			ORDER BY date DESC LIMIT 100";
			$result_syndic = spip_query($query_syndic);
			while ($row_syndic = spip_fetch_array($result_syndic)) {
				indexer_chaine($row_syndic['titre'], 5);
			}
		}
		// Aller chercher la page d'accueil
		if ($GLOBALS['meta']["visiter_sites"] == "oui") {
			include_ecrire ("inc_sites.php3");
			spip_log ("indexation contenu syndic ".$row['url_site']);
			indexer_chaine(supprimer_tags(
				recuperer_page($row['url_site'], true, false, 50000)
				), 1);
		}
		break;

	//
	// Cas tres particulier du forum :
	// on indexe le thread comme un tout
	case 'forum':
		// 1. chercher la racine du thread
		$id_forum = $id_objet;
		while ($row['id_parent']) {
			$id_forum = $row['id_parent'];
			$s = spip_query("SELECT id_forum,id_parent FROM spip_forum WHERE id_forum=$id_forum");
			$row = spip_fetch_array($s);
		}

		// 2. chercher tous les forums du thread
		// (attention le forum de depart $id_objet n'appartient pas forcement
		// a son propre thread car il peut etre le fils d'un forum non 'publie')
		$thread="$id_forum";
		$fini = false;
		while (!$fini) {
			$s = spip_query("SELECT id_forum FROM spip_forum WHERE id_parent IN ($thread) AND id_forum NOT IN ($thread) AND statut='publie'");
			if (spip_num_rows($s) == 0) $fini = true;
			while ($t = spip_fetch_array($s))
				$thread.=','.$t['id_forum'];
		}
		
		// 3. marquer le thread comme "en cours d'indexation"
		spip_log("-> indexation thread $thread");
		spip_query("UPDATE spip_forum SET idx='idx'
			WHERE id_forum IN ($thread,$id_objet) AND idx!='non'");

		// 4. Indexer le thread
		$s = spip_query("SELECT * FROM spip_forum
			WHERE id_forum IN ($thread) AND idx!='non'");
		while ($row = spip_fetch_array($s)) {
			indexer_chaine($row['titre'], 3);
			indexer_chaine($row['texte'], 1);
			indexer_chaine($row['auteur'], 2, 2);
			indexer_chaine($row['email_auteur'], 2);
			indexer_chaine($row['nom_site'], 2);
			indexer_chaine($row['url_site'], 1);
		}

		// 5. marquer le thread comme "indexe"
		spip_query("UPDATE spip_forum SET idx='oui'
			WHERE id_forum IN ($thread,$id_objet) AND idx!='non'");

		// 6. Changer l'id_objet en id_forum de la racine du thread
		$id_objet = $id_forum;

		break;


	case 'document':
		// 1. Indexer le descriptif
		indexer_chaine($row['titre'], 20);
		indexer_chaine($row['descriptif'], 10);
		indexer_chaine(preg_replace(',^(IMG/|.*://),', '', $row['fichier']), 1);
		indexer_elements_associes('document', $id_objet, 'mot', 3);

		// 2. Indexer le contenu si on sait le lire
		indexer_contenu_document($row);
		break;


	} // switch

	$query = "DELETE FROM $table_index WHERE $col_id=$id_objet";
	$result = spip_query($query);

	if ($index) {
		if ($mots) {
			$mots = "INSERT IGNORE INTO spip_index_dico (hash, dico) VALUES ".substr($mots,1);	// supprimer la virgule du debut
			spip_query($mots);
		}
		reset($index);
		while (list($hash, $points) = each($index)) {
		  spip_query("INSERT INTO $table_index (hash, points, $col_id) VALUES (0x$hash,".ceil($points).",$id_objet)");
		}
	}

	// marquer "indexe"
	spip_query("UPDATE $table SET idx='oui' WHERE $col_id=$id_objet");
}

/*
	Valeurs du champ 'idx' de la table spip_objet(s)
	'' ne sait pas
	'1' ˆ (re)indexer
	'oui' deja indexe
	'idx' en cours
	'non' ne jamais indexer
*/

// API pour l'espace prive
function marquer_indexer ($objet, $id_objet) {
	spip_log ("demande indexation $objet $id_objet");
	$table = 'spip_'.table_objet($objet);
	$id = id_table_objet($objet);
	spip_query ("UPDATE $table SET idx='1' WHERE $id=$id_objet AND idx!='non'");
}

// A garder pour compatibilite bouton memo...
function indexer_article($id_article) {
	marquer_indexer('article', $id_article);
}

// n'indexer que les objets publies
function critere_indexation($type) {
	switch ($type) {
		case 'article':
		case 'breve':
		case 'rubrique':
		case 'syndic':
		case 'forum':
		case 'signature':
			$critere = "statut='publie'";
			break;
		case 'auteur':
			$critere = "statut IN ('0minirezo', '1comite')";
			break;
		case 'mot':
		case 'document':
		default:
			$critere = '1=1';
			break;
	}
	return $critere;
}

function effectuer_une_indexation($nombre_indexations = 1) {

	// chercher un objet a indexer dans chacune des tables d'objets
	$vu = array();
	$types = array('article','auteur','breve','mot','rubrique','signature','syndic','forum','document');

	while (list(,$type) = each($types)) {
		$table_objet = 'spip_'.table_objet($type);
		$table_index = 'spip_index_'.table_objet($type);

		$critere = critere_indexation($type);

		if ($type == 'syndic' OR $type == 'document')
			$limit = 1;
		else
			$limit = $nombre_indexations;

		$s = spip_query("SELECT id_$type, idx FROM $table_objet WHERE idx IN ('','1','idx') AND $critere ORDER BY idx='idx',idx='' LIMIT $limit");
		while ($t = spip_fetch_array($s)) {
			$vu[$type] .= $t[0].", ";
			indexer_objet($type, $t[0], $t[1]);
		}
	}
	return $vu;
}

function executer_une_indexation_syndic() {
	$id_syndic = 0;
	if ($row = spip_fetch_array(spip_query("SELECT id_syndic FROM spip_syndic WHERE statut='publie' AND date_index < DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY date_index LIMIT 1"))) {
		$id_syndic = $row['id_syndic'];
		spip_query("UPDATE spip_syndic SET date_index=NOW() WHERE id_syndic=$id_syndic");
		marquer_indexer('syndic', $id_syndic);
	}
	return $id_syndic;
}

function creer_liste_indexation() {
	$types = array('article','auteur','breve','mot','rubrique','syndic','forum','signature','document');
	while (list(,$type) = each($types)) {
		$table = 'spip_'.table_objet($type);
		spip_query("UPDATE $table SET idx='1' WHERE idx!='non'");
	}
}

function purger_index() {
		spip_query("DELETE FROM spip_index_articles");
		spip_query("DELETE FROM spip_index_auteurs");
		spip_query("DELETE FROM spip_index_breves");
		spip_query("DELETE FROM spip_index_mots");
		spip_query("DELETE FROM spip_index_rubriques");
		spip_query("DELETE FROM spip_index_syndic");
		spip_query("DELETE FROM spip_index_forum");
		spip_query("DELETE FROM spip_index_signatures");
		spip_query("DELETE FROM spip_index_documents");
		spip_query("DELETE FROM spip_index_dico");
}

// cree la requete pour une recherche en txt integral
function requete_txt_integral($objet, $hash_recherche) {
	$table = "spip_".table_objet($objet);
	$index_table = "spip_index_".table_objet($objet);
	$id_objet = "id_".$objet;
	return "SELECT objet.*, SUM(rec.points) AS points
		FROM $table AS objet, $index_table AS rec
		WHERE objet.$id_objet = rec.$id_objet
		AND rec.hash IN ($hash_recherche)
		GROUP BY objet.$id_objet
		ORDER BY points DESC
		LIMIT 10";
}

// rechercher un mot dans le dico
// retourne deux methodes : lache puis strict
function requete_dico($val) {
	$min_long = 3;

	// cas normal
	if (strlen($val) > $min_long) {
		return array("dico LIKE '".addslashes($val)."%'", "dico = '".addslashes($val)."'");
	} else
		return array("dico = '".addslashes($val)."___'", "dico = '".addslashes($val)."___'");
}


// decode la chaine recherchee et la traduit en hash
function requete_hash ($rech) {
	// recupere les mots de la recherche
	$translitteration_complexe = true;
	$rech = nettoyer_chaine_indexation($rech);
	$regs = separateurs_indexation(true)." ";
	$rech = strtr($rech, $regs, ereg_replace('.', ' ', $regs));
	$s = preg_split("/ +/", $rech);
	unset($dico);
	unset($h);

	// cherche les mots dans le dico
	while (list(, $val) = each($s)) {
		list($rq, $rq_strict) = requete_dico ($val);
		if ($rq)
			$dico[] = $rq;
		if ($rq_strict)
			$dico_strict[] = $rq_strict;
	}

	// Attention en MySQL 3.x il faut passer par HEX(hash)
	// alors qu'en MySQL 4.1 c'est interdit !
	$vers = spip_fetch_array(spip_query("SELECT VERSION()"));
	if (substr($vers[0], 0, 1) >= 4
	AND substr($vers[0], 2, 1) >= 1 ) {
		$hex_fmt = '';
		$select_hash = 'hash AS h';
	} else {
		$hex_fmt = '0x';
		$select_hash = 'HEX(hash) AS h';
	}

	// compose la recherche dans l'index
	if ($dico_strict) {
		$query2 = "SELECT $select_hash FROM spip_index_dico WHERE "
			.join(" OR ", $dico_strict);
		$result2 = spip_query($query2);
		while ($row2 = spip_fetch_array($result2))
			$h_strict[] = $hex_fmt.$row2['h'];
	}
	if ($dico) {
		$query2 = "SELECT $select_hash FROM spip_index_dico WHERE "
			.join(" OR ", $dico);
		$result2 = spip_query($query2);
		while ($row2 = spip_fetch_array($result2))
			$h[] = $hex_fmt.$row2['h'];
	}
	if ($h_strict)
		$hash_recherche_strict = join(",", $h_strict);
	else
		$hash_recherche_strict = "0";

	if ($h)
		$hash_recherche = join(",", $h);
	else
		$hash_recherche = "0";

	return array($hash_recherche, $hash_recherche_strict);
}


function prepare_recherche($recherche, $type = 'id_article', $table='articles') {
	static $cache = array();

	if (!$cache[$type][$recherche]) {

		if (!$cache['hash'][$recherche])
			$cache['hash'][$recherche] = requete_hash($recherche);
		list($hash_recherche, $hash_recherche_strict)
			= $cache['hash'][$recherche];

		$strict = array();
		if ($hash_recherche_strict)
			foreach (split(',',$hash_recherche_strict) as $h)
				$strict[$h] = 99;

		$points = array();
		$s = spip_query ("SELECT hash,points,$type as id
			FROM spip_index_$table
			WHERE hash IN ($hash_recherche)");

		while ($r = spip_fetch_array($s))
			$points[$r['id']]
			+= (1 + $strict[$r['hash']]) * $r['points'];
		spip_free_result($s);

		arsort($points, SORT_NUMERIC);

		# calculer le {id_article IN()} et le {... as points}
		if (!count($points)) {
			$cache[$type][$recherche] = array('', '');
		} else {
			$ids = array();
			$select = '0';
			foreach ($points as $id => $p)
				$listes_ids[$p] .= ','.$id;
			foreach ($listes_ids as $p => $liste_ids)
				$select .= "+$p*(".calcul_mysql_in("$table.$type", substr($liste_ids, 1)).") ";

			$cache[$type][$recherche] = array($select, 
							  '('.calcul_mysql_in("$table.$type", join(',',array_keys($points))).')');
		}
	}

	return $cache[$type][$recherche];
}

?>
