<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_IMPORT")) return;
define("_ECRIRE_INC_IMPORT", "1");


include_local ("inc_acces.php3");


if ($GLOBALS['flag_strpos_3']) {

function xml_fetch_tag($f, &$before, $gz=false)
{
	global $buf, $pos, $abs_pos;
	global $flag_str_replace;
	$_fread = ($gz) ? gzread : fread;
	$_feof = ($gz) ? gzeof : feof;
	$_ftell = ($gz) ? gztell : ftell;
	$buf_len = 1024;
	$q = $pos;
	for (;;) {
		$p = $q;
		$q = strpos($buf, '<', $p);
		if (!$q AND substr($buf, $p, 1) != '<') {
			if ($_feof($f)) return false;
			$before .= substr($buf, $p);
			$abs_pos = $_ftell($f);
			$buf = $_fread($f, $buf_len);
			$q = 0;
			continue;
		}
		$before .= substr($buf, $p, $q - $p);
		if (++$q >= strlen($buf)) {
			if ($_feof($f)) return false;
			$abs_pos = $_ftell($f);
			$buf = $_fread($f, $buf_len);
			$q = 0;
		}
		break;
	}

	$tag = '';
	for (;;) {
		$p = $q;
		$q = strpos($buf, '>', $p);
		if (!$q AND substr($buf, $p, 1) != '>') {
			if ($_feof($f)) return false;
			$tag .= substr($buf, $p);
			$abs_pos = $_ftell($f);
			$buf = $_fread($f, $buf_len);
			$q = 0;
			continue;
		}
		$pos = $q + 1;
		$tag .= substr($buf, $p, $q - $p);
		if ($flag_str_replace)
			$before = str_replace('&amp;', '&', str_replace('&lt;', '<', $before));
		else
			$before = ereg_replace('&amp;', '&', ereg_replace('&lt;', '<', $before));
		return $tag;
	}
}

} else {

function xml_fetch_tag($f, &$before, $gz=false)
{
	global $buf, $pos, $abs_pos;
	$_fread = ($gz) ? gzread : fread;
	$_feof = ($gz) ? gzeof : feof;
	$_ftell = ($gz) ? gztell : ftell;
	$buf_len = 512;
	$q = $pos;
	for (;;) {
		$p = $q;
		$q = strpos($b = substr($buf, $p), '<');
		if (!$q AND substr($b, 0, 1) != '<') {
			if ($_feof($f)) return false;
			$before .= $b;
			$abs_pos = $_ftell($f);
			$buf = $_fread($f, $buf_len);
			$q = 0;
			continue;
		}
		$before .= substr($buf, $p, $q);
		$q += $p + 1;
		if ($q >= strlen($buf)) {
			if ($_feof($f)) return false;
			$abs_pos = $_ftell($f);
			$buf = $_fread($f, $buf_len);
			$q = 0;
		}
		break;
	}

	$tag = '';
	for (;;) {
		$p = $q;
		$q = strpos($b = substr($buf, $p), '>');
		if (!$q AND substr($b, 0, 1) != '>') {
			if ($_feof($f)) return false;
			$tag .= $b;
			$abs_pos = $_ftell($f);
			$buf = $_fread($f, $buf_len);
			$q = 0;
			continue;
		}
		$tag .= substr($buf, $p, $q);
		$pos = $q + $p + 1;
		$before = ereg_replace('&amp;', '&', ereg_replace('&lt;', '<', $before));
		return $tag;
	}
}

}


function xml_parse_tag($texte) {
	list($tag, $atts) = split('[[:space:]]+', $texte, 2);
	$result[0] = $tag;
	$result[1] = '';
	if (!$atts) return $result;
	while (ereg('^([^[:space:]]+)[[:space:]]*=[[:space:]]*"([^"]*)"([[:space:]]+(.*))?', $atts, $regs)) {
		$result[1][$regs[1]] = $regs[2];
		$atts = $regs[4];
	}
	return $result;
}


function import_debut($f, $gz=false) {
	$b = "";
	while ($t = xml_fetch_tag($f, $b, $gz)) {
		$r = xml_parse_tag($t);
		if ($r[0] == "SPIP") return $r;
		$b = "";
	}
	return false;
}


//
// $f = handle fichier
// $gz = flag utilisation zlib
//
// importe un objet depuis le fichier, retourne true si ok, false si erreur ou fin de fichier
//

function import_objet_1_2($f, $gz=false) {
	global $import_ok, $pos, $abs_pos;

	$import_ok = false;
	$b = '';
	if (!($type = xml_fetch_tag($f, $b, $gz))) return false;
	if ($type == '/SPIP') return !($import_ok = true);
	$id = "id_$type";
	for (;;) {
		$b = '';
		if (!($col = xml_fetch_tag($f, $b, $gz))) return false;
		if ($col == ("/$type")) break;
		$value = '';
		if (!xml_fetch_tag($f, $value, $gz)) return false;
		if (substr($col, 0, 5) == 'lien:') {
			$type_lien = substr($col, 5);
			$liens[$type_lien][] = '('.$$id.','.$value.')';
		}
		else if ($col != 'maj') {
			$cols[] = $col;
			$values[] = '"'.addslashes($value).'"';
			if (substr($col, 0, 3) == 'id_') $$col = $value;
		}
	}

	$table = 'spip_'.$type;
	if ($type != 'forum' AND $type != 'syndic') $table .= 's';
	$query = "REPLACE $table (" . join(',', $cols) . ') VALUES (' . join(',', $values) . ')';
	mysql_query($query);

	if ($type == 'article') {
		mysql_query("DELETE FROM spip_auteurs_articles WHERE id_article=$id_article");
	}
	else if ($type == 'mot') {
		mysql_query("DELETE FROM spip_mots_articles WHERE id_mot=$id_mot");
		mysql_query("DELETE FROM spip_mots_breves WHERE id_mot=$id_mot");
	}
	else if ($type == 'auteur') {
		mysql_query("DELETE FROM spip_auteurs_rubriques WHERE id_auteur=$id_auteur");
	}
	else if ($type == 'message') {
		mysql_query("DELETE FROM spip_auteurs_messages WHERE id_message=$id_message");
	}
	if ($liens) {
		reset($liens);
		while (list($type_lien, $t) = each($liens)) {
			if ($type == 'auteur' OR $type == 'mot') $table_lien = 'spip_'.$type.'s_'.$type_lien.'s';
			else $table_lien = 'spip_'.$type_lien.'s_'.$type.'s';
			$query = "INSERT $table_lien ($id, id_$type_lien) VALUES ".join(',', $t);
			mysql_query($query);
		}
	}

	$p = $pos + $abs_pos;
	ecrire_meta("status_restauration", "$p");

	return $import_ok = true;
}


function import_objet_0_0($f, $gz=false) {
	global $import_ok, $pos, $abs_pos;

	$import_ok = false;
	$b = '';
	if (!($type = xml_fetch_tag($f, $b, $gz))) return false;
	if ($type == '/SPIP') return !($import_ok = true);
	$is_art = ($type == 'article');
	$is_mot = ($type == 'mot');
	for (;;) {
		$b = '';
		if (!($col = xml_fetch_tag($f, $b, $gz))) return false;
		if ($col == ("/$type")) break;
		$value = '';
		if (!xml_fetch_tag($f, $value, $gz)) return false;
		if ($is_art AND $col == 'id_auteur') {
			$auteurs[] = $value;
		}
		else if ($is_mot AND $col == 'id_article') {
			$articles[] = $value;
		}
		else if ($is_mot AND $col == 'id_breve') {
			$breves[] = $value;
		}
		else if ($col != 'maj') {
			$cols[] = $col;
			$values[] = '"'.addslashes($value).'"';
			if ($is_art && ($col == 'id_article')) $id_article = $value;
			if ($is_mot && ($col == 'id_mot')) $id_mot = $value;
		}
	}

	$table = "spip_$type";
	if ($type != 'forum' AND $type != 'syndic') $table .= 's';
	$query = "REPLACE $table (" . join(",", $cols) . ") VALUES (" . join(",", $values) . ")";
	mysql_query($query);

	if ($is_art && $id_article) {
		$query = "DELETE FROM spip_auteurs_articles WHERE id_article=$id_article";
		mysql_query($query);
		if ($auteurs) {
			reset ($auteurs);
			while (list(, $auteur) = each($auteurs)) {
				$query = "INSERT spip_auteurs_articles (id_auteur, id_article) VALUES ($auteur, $id_article)";
				mysql_query($query);
			}
		}
	}
	if ($is_mot && $id_mot) {
		$query = "DELETE FROM spip_mots_articles WHERE id_mot=$id_mot";
		mysql_query($query);
		$query = "DELETE FROM spip_mots_breves WHERE id_mot=$id_mot";
		mysql_query($query);
		if ($articles) {
			reset ($articles);
			while (list(, $article) = each($articles)) {
				$query = "INSERT spip_mots_articles (id_mot, id_article) VALUES ($id_mot, $article)";
				mysql_query($query);
			}
		}
		if ($breves) {
			reset ($breves);
			while (list(, $breve) = each($breves)) {
				$query = "INSERT spip_mots_breves (id_mot, id_breve) VALUES ($id_mot, $breve)";
				mysql_query($query);
			}
		}
	}

	$p = $pos + $abs_pos;
	ecrire_meta("status_restauration", "$p");
//	ecrire_metas();

	return $import_ok = true;
}

function import_objet($f, $gz = false) {
	return import_objet_1_2($f, $gz);
}

function import_fin() {
	// Effacer l'ancien acces admin
	$query = "DELETE FROM spip_auteurs WHERE id_auteur=0";
	mysql_query($query);

	effacer_meta("status_restauration");
	effacer_meta("debut_restauration");
	effacer_meta("date_optimisation");
	ecrire_meta('calculer_rubriques', 'oui');
	ecrire_metas();
}

function import_abandon() {
	// Probleme pour restaurer l'ancien acces admin : il conserve un id_auteur = 0

	effacer_meta("status_restauration");
	effacer_meta("debut_restauration");
	effacer_meta("date_optimisation");
	ecrire_metas();
}


function import_all($f, $gz=false) {
	global $import_ok;
	global $meta;
	global $auth_htaccess;
	global $connect_id_auteur;
	$_fseek = ($gz) ? gzseek : fseek;

	$my_date = lire_meta_maj("debut_restauration");
	if (!$my_date) return false;

	$my_pos = lire_meta("status_restauration");

	if (!$my_pos) {
		// Debut de l'importation
		if (!($r = import_debut($f, $gz))) {
			ecrire_meta("erreur", "le fichier archive n'est pas un fichier SPIP");
			return false;
		}
		else {
			// Bidouille pour garder l'acces admin actuel pendant toute la restauration
			$query = "UPDATE spip_auteurs SET id_auteur=0 WHERE id_auteur=$connect_id_auteur";
			mysql_query($query);

			$version_archive = $r[1]['version_archive'];
			ecrire_meta('version_archive_restauration', $version_archive);
		}
	}
	else {
		// Reprise de l'importation
		$_fseek($f, $my_pos);
		$version_archive = lire_meta('version_archive_restauration');
	}

	// Restauration des entrees du fichier

	switch ($version_archive) {
	case '1.2':
		while (import_objet_1_2($f, $gz));
		break;
	default:
		while (import_objet_0_0($f, $gz));
		break;
	}
	if (!$import_ok) {
		ecrire_meta("erreur", "le fichier archive n'est pas valide");
		return false;
	}

	// Mise a jour du fichier htpasswd

	ecrire_acces();

	// Destruction des entrees non restaurees

	$query = "DELETE FROM spip_rubriques WHERE maj < $my_date";
	mysql_query($query);
	$query = "DELETE FROM spip_breves WHERE maj < $my_date";
	mysql_query($query);
	$query = "DELETE FROM spip_auteurs WHERE maj < $my_date";
	mysql_query($query);
	$query = "DELETE FROM spip_articles WHERE maj < $my_date";
	mysql_query($query);
	$query = "DELETE FROM spip_forum WHERE maj < $my_date";
	mysql_query($query);
	$query = "DELETE FROM spip_mots WHERE maj < $my_date";
	mysql_query($query);
	$query = "DELETE FROM spip_petitions WHERE maj < $my_date";
	mysql_query($query);
	$query = "DELETE FROM spip_signatures WHERE maj < $my_date";
	mysql_query($query);

	import_fin();

	return true;
}


?>