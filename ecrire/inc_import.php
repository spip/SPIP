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

include_ecrire("inc_acces");
include_ecrire("inc_filtres");

function xml_fetch_tag($f, &$before, $gz=false) {
	global $buf, $pos, $abs_pos;
	static $buf_len = 1000;
	$_fread = ($gz) ? gzread : fread;
	$_feof = ($gz) ? gzeof : feof;
	$_ftell = ($gz) ? gztell : ftell;
	$p = $pos;

	$q = @strpos($buf, '<', $p);
	while (!$q AND substr($buf, $p, 1) != '<') {
		if ($_feof($f)) return false;
		$before .= substr($buf, $p);
		$buf = $_fread($f, $buf_len);
		$p = 0;
		$q = strpos($buf, '<');
	}
	$before .= substr($buf, $p, $q - $p);
	$tag = '';
	$p = ++$q;
	$q = @strpos($buf, '>', $p);
	while (!$q AND substr($buf, $p, 1) != '>') {
		if ($_feof($f)) return false;
		$tag .= substr($buf, $p);
		$buf = $_fread($f, $buf_len);
		$p = 0;
		$q = strpos($buf, '>');
	}
	$pos = $q + 1;
	$tag .= substr($buf, $p, $q - $p);
	$before = str_replace('&amp;', '&', str_replace('&lt;', '<', $before));
	$abs_pos = $_ftell($f) - strlen($buf);
	return $tag;
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
		if ($r[0] == '?xml' AND $r[1]['encoding'])
			ecrire_meta('charset_restauration', strtolower($r[1]['encoding']));
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
	static $time_javascript;

	if (time() - $time_javascript > 3) {	// 3 secondes
		affiche_progression_javascript($abs_pos);
		$time_javascript = time();
	}

	static $tables;
	if (!$tables) $tables = array(
		'article' => 'spip_articles',
		'auteur' => 'spip_auteurs',
		'breve' => 'spip_breves',
		'document' => 'spip_documents',
		'forum' => 'spip_forum',
		'groupe_mots' => 'spip_groupes_mots',
		'message' => 'spip_messages',
		'mot' => 'spip_mots',
		'petition' => 'spip_petitions',
		'rubrique' => 'spip_rubriques',
		'signature' => 'spip_signatures',
		'syndic' => 'spip_syndic',
		'syndic_article' => 'spip_syndic_articles',
		'type_document' => 'spip_types_documents'
	);

	$import_ok = false;
	$b = '';
	// Lire le type d'objet
	if (!($type = xml_fetch_tag($f, $b, $gz))) return false;
	if ($type == '/SPIP') return !($import_ok = true);
	$id = "id_$type";
	$id_objet = 0;

	// Lire les champs de l'objet
	for (;;) {
		$b = '';
		if (!($col = xml_fetch_tag($f, $b, $gz))) return false;
		if ($col == '/'.$type) break;
		$value = '';
		if (!xml_fetch_tag($f, $value, $gz)) return false;
		if (substr($col, 0, 5) == 'lien:') {
			$type_lien = substr($col, 5);
			$liens[$type_lien][] = '('.$id_objet.','.$value.')';
		}
		else if ($col != 'maj') {
			// tentative de restauration d'une base sauvegardee avec le champ 'images' ; d'experience, ca arrive...
			// mieux vaut accepter que canner silencieusement...
			if (($type == 'article') && ($col == 'images'))
			{
				if ($value) {		// ne pas afficher de message si on a un champ suppl mais vide
					echo "--><br><font color='red'><b>"._T('avis_erreur_sauvegarde', array('type' => $type, 'id_objet' => $id_objet))."</b></font>\n<font color='black'>"._T('avis_colonne_inexistante', array('col' => $col));
					if ($col == 'images') echo _T('info_verifier_image');
					echo "</font>\n<!--";
					$GLOBALS['erreur_restauration'] = true;
				}
			}
			else {
				$cols[] = $col;
				$values[] = '"'.addslashes($value).'"';
				if ($col == $id) $id_objet = $value;
			}
		}
	}

	$table = $tables[$type];
	$query = "REPLACE $table (" . join(',', $cols) . ') VALUES (' . join(',', $values) . ')';
	if (!spip_query($query)) {
		echo "--><br><font color='red'><b>"._T('avis_erreur_mysql')."</b></font>\n<font color='black'><tt>".spip_sql_error()."</tt></font>\n<!--";
		$GLOBALS['erreur_restauration'] = true;
	}

	if ($type == 'article') {
		spip_query("DELETE FROM spip_auteurs_articles WHERE id_article=$id_objet");
		spip_query("DELETE FROM spip_documents_articles WHERE id_article=$id_objet");
	}
	else if ($type == 'rubrique') {
		spip_query("DELETE FROM spip_auteurs_rubriques WHERE id_rubrique=$id_objet");
		spip_query("DELETE FROM spip_documents_rubriques WHERE id_rubrique=$id_objet");
	}
	else if ($type == 'breve') {
		spip_query("DELETE FROM spip_documents_breves WHERE id_breve=$id_objet");
	}
	else if ($type == 'mot') {
		spip_query("DELETE FROM spip_mots_articles WHERE id_mot=$id_objet");
		spip_query("DELETE FROM spip_mots_breves WHERE id_mot=$id_objet");
		spip_query("DELETE FROM spip_mots_forum WHERE id_mot=$id_objet");
		spip_query("DELETE FROM spip_mots_rubriques WHERE id_mot=$id_objet");
		spip_query("DELETE FROM spip_mots_syndic WHERE id_mot=$id_objet");
	}
	else if ($type == 'auteur') {
		spip_query("DELETE FROM spip_auteurs_rubriques WHERE id_auteur=$id_objet");
	}
	else if ($type == 'message') {
		spip_query("DELETE FROM spip_auteurs_messages WHERE id_message=$id_objet");
	}
	if ($liens) {
		reset($liens);
		while (list($type_lien, $t) = each($liens)) {
			if ($type == 'auteur' OR $type == 'mot' OR $type == 'document')
				if ($type_lien == 'syndic' OR $type_lien == 'forum') $table_lien = 'spip_'.$type.'s_'.$type_lien;
				else $table_lien = 'spip_'.$type.'s_'.$type_lien.'s';
			else
				$table_lien = 'spip_'.$type_lien.'s_'.$type.'s';
			$query = "INSERT INTO $table_lien ($id, id_$type_lien) VALUES ".join(',', $t);
			spip_query($query);
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
		else if ($is_mot AND $col == 'id_forum') {
			$forums[] = $value;
		}
		else if ($is_mot AND $col == 'id_rubrique') {
			$rubriques[] = $value;
		}
		else if ($is_mot AND $col == 'id_syndic') {
			$syndics[] = $value;
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
	spip_query($query);

	if ($is_art && $id_article) {
		$query = "DELETE FROM spip_auteurs_articles WHERE id_article=$id_article";
		spip_query($query);
		if ($auteurs) {
			reset ($auteurs);
			while (list(, $auteur) = each($auteurs)) {
				$query = "INSERT INTO spip_auteurs_articles (id_auteur, id_article) VALUES ($auteur, $id_article)";
				spip_query($query);
			}
		}
	}
	if ($is_mot && $id_mot) {
		$query = "DELETE FROM spip_mots_articles WHERE id_mot=$id_mot";
		spip_query($query);
		$query = "DELETE FROM spip_mots_breves WHERE id_mot=$id_mot";
		spip_query($query);
		$query = "DELETE FROM spip_mots_forum WHERE id_mot=$id_mot";
		spip_query($query);
		$query = "DELETE FROM spip_mots_rubriques WHERE id_mot=$id_mot";
		spip_query($query);
		$query = "DELETE FROM spip_mots_syndic WHERE id_mot=$id_mot";
		spip_query($query);
		if ($articles) {
			reset ($articles);
			while (list(, $article) = each($articles)) {
				$query = "INSERT INTO spip_mots_articles (id_mot, id_article) VALUES ($id_mot, $article)";
				spip_query($query);
			}
		}
		if ($breves) {
			reset ($breves);
			while (list(, $breve) = each($breves)) {
				$query = "INSERT INTO spip_mots_breves (id_mot, id_breve) VALUES ($id_mot, $breve)";
				spip_query($query);
			}
		}
		if ($forums) {
			reset ($forums);
			while (list(, $forum) = each($forums)) {
				$query = "INSERT INTO spip_mots_forum (id_mot, id_forum) VALUES ($id_mot, $forum)";
				spip_query($query);
			}
		}
		if ($rubriques) {
			reset ($rubriques);
			while (list(, $rubrique) = each($rubriques)) {
				$query = "INSERT INTO spip_mots_rubriques (id_mot, id_rubrique) VALUES ($id_mot, $id_rubrique)";
				spip_query($query);
			}
		}
		if ($syndics) {
			reset ($syndics);
			while (list(, $syndic) = each($syndics)) {
				$query = "INSERT INTO spip_mots_syndic (id_mot, id_syndic) VALUES ($id_mot, $syndic)";
				spip_query($query);
			}
		}
	}

	$p = $pos + $abs_pos;
	ecrire_meta("status_restauration", "$p");

	return $import_ok = true;
}

function import_objet($f, $gz = false) {
	return import_objet_1_2($f, $gz);
}

function import_fin() {
	// Effacer l'ancien acces admin
	spip_query("DELETE FROM spip_auteurs WHERE id_auteur=0");

	if ($charset = $GLOBALS['meta']['charset_restauration'])
		ecrire_meta('charset', $charset);
	effacer_meta("charset_restauration");
	effacer_meta("status_restauration");
	effacer_meta("debut_restauration");
	effacer_meta("date_optimisation");
	ecrire_meta('calculer_rubriques', 'oui');
	ecrire_metas();
}

function import_abandon() {
	// Probleme pour restaurer l'ancien acces admin : il conserve un id_auteur = 0

	effacer_meta("charset_restauration");
	effacer_meta("status_restauration");
	effacer_meta("debut_restauration");
	effacer_meta("date_optimisation");
	ecrire_metas();
}

function import_tables($f, $tables, $gz=false) {

	global $import_ok;
	global $auth_htaccess;
	global $connect_id_auteur;
	$_fseek = ($gz) ? gzseek : fseek;

	// utiliser une version fraiche des metas (ie pas le cache)
	include_ecrire('inc_meta');
	lire_metas();

	$s = spip_query("SELECT UNIX_TIMESTAMP(maj) AS d
		FROM spip_meta WHERE nom='debut_restauration'");
	list($my_date) = spip_fetch_array($s);

	if (!$my_date) {
		spip_log("importation: debut_restauration absent");
		return false;
	}

	$my_pos = $GLOBALS['meta']["status_restauration"];

	if (!$my_pos) {
		// Debut de l'importation
		ecrire_meta('charset_restauration', 'iso-8859-1');
		if (!($r = import_debut($f, $gz))) {
			ecrire_meta("erreur", _T('avis_archive_incorrect'));
			spip_log("importation: avis_archive_incorrect");
			return false;
		}
		else {
			// Bidouille pour garder l'acces admin actuel pendant toute la restauration
			$query = "UPDATE spip_auteurs SET id_auteur=0 WHERE id_auteur=$connect_id_auteur";
			spip_query($query);

			$version_archive = $r[1]['version_archive'];
			ecrire_meta('version_archive_restauration', $version_archive);
		}
	}
	else {
		// Reprise de l'importation
		$_fseek($f, $my_pos);
		$version_archive = $GLOBALS['meta']['version_archive_restauration'];
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
		ecrire_meta("erreur", _T('avis_archive_invalide'));
		return false;
	}

	// Mise a jour du fichier htpasswd

	ecrire_acces();

	// Destruction des entrees non restaurees

	detruit_non_restaurees($my_date, $tables);

	import_fin();

	affiche_progression_javascript('100 %');

	return true;
}


// Destruction des entrees non restaurees

function detruit_non_restaurees($my_date, $tables)
{
	
	foreach ($tables as $v) 
	  spip_query("DELETE FROM $v WHERE UNIX_TIMESTAMP(maj) < $my_date");
}


function affiche_progression_javascript($abs_pos) {
	global $affiche_progression_pourcent;
	include_ecrire('inc_charsets');

	flush();
	echo "<script type='text/javascript'><!--\n";

	if ($abs_pos == '100 %') {
		$taille = $abs_pos;
		if ($GLOBALS['erreur_restauration'])
			echo "document.progression.recharge.value='".str_replace("'", "\\'", unicode_to_javascript(_T('avis_erreur')))."';\n";
		else
			echo "document.progression.recharge.value='".str_replace("'", "\\'", unicode_to_javascript(_T('info_fini')))."';\n";
	}
	else if (! $affiche_progression_pourcent)
		$taille = ereg_replace("&nbsp;", " ", taille_en_octets($abs_pos));
	else
		$taille = floor(100 * $abs_pos / $affiche_progression_pourcent)." %";

	echo "document.progression.taille.value='$taille';\n";
	echo "//--></script>\n";
	flush();
}

function import_all_continue($tables)
{
	global $meta, $flag_gz, $buf, $pos, $abs_pos;

	@ignore_user_abort(1);

	$request = unserialize($meta['request_restauration']);

	$archive = _DIR_SESSIONS . $request['archive'];

	if (!@is_readable($archive)) {
		import_abandon();
		minipres(_T('info_base_restauration'),
			 _T('info_erreur_restauration') .
			 "<br /><br /><a href='./'>"._T('info_sauvegarde_reussi_03').
			 "</a> "._T('info_sauvegarde_reussi_04'));
	}


	$my_pos = $meta["status_restauration"];

	if (ereg("\.gz$", $archive)) {
			$affiche_progression_pourcent = false;
			$taille = taille_en_octets($my_pos);
			$gz = true;
	} else {
			$affiche_progression_pourcent = filesize($archive);
			$taille = floor(100 * $my_pos / $affiche_progression_pourcent)." %";
			$gz = false;
		}
	install_debut_html(_T('info_base_restauration'));
        echo "<p><table cellpadding='6' border='0'><tr><td width='100%' bgcolor='red'>";
        echo "<table width='100%' cellpadding='12' border='0'><tr><td width='100%' bgcolor
='white'>";
	echo "<form name='progression'><center><input type='text' size=10 style='text-align:center;' name='taille' value='$taille'><br>
		<input type='text' class='forml' size='80' name='recharge' value='"._T('info_recharger_page')."'></center></form>";
        echo "</td></tr></table>";
        echo "</td></tr></table><br /><br >";

	$_fopen = ($gz) ? gzopen : fopen;
	$f = $_fopen($archive, "rb");
	$pos = 0;
	$buf = "";
	$res = import_tables($f, $tables, $gz);
	spip_log("Restauration: " . ($res ? "finie" : "echec"));
	if ($res)
		import_abandon();
	else	import_fin();
	echo " <a href='./'>",_T('info_sauvegarde_reussi_03'),"</a> ",_T('info_sauvegarde_reussi_04');
	install_fin_html();
	flush();
}

?>
