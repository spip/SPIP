<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_EXPORT")) return;
define("_ECRIRE_INC_EXPORT", "1");



$GLOBALS['version_archive'] = '1.2';


// Conversion timestamp MySQL (format ascii) en Unix (format integer)
function mysql_timestamp_to_time($maj)
{
	$t_an = substr($maj, 0, 4);
	$t_mois = substr($maj, 4, 2);
	$t_jour = substr($maj, 6, 2);
	$t_h = substr($maj, 8, 2);
	$t_min = substr($maj, 10, 2);
	$t_sec = substr($maj, 12, 2);
	return mktime ($t_h, $t_min, $t_sec, $t_mois, $t_jour, $t_an, 0);
}

function build_begin_tag($tag) {
	return "<$tag>";
}

function build_end_tag($tag) {
	return "</$tag>";
}

// Conversion texte -> xml (ajout d'entites)
if ($flag_str_replace) {
	function text_to_xml($string) {
		return str_replace('<', '&lt;', str_replace('&', '&amp;', $string));
	}
}
else {
	function text_to_xml($string) {
		return ereg_replace('<', '&lt;', ereg_replace('&', '&amp;', $string));
	}
}


//
// Exportation generique d'objets (fichier ou retour de fonction)
//
function export_objets($result, $type, $file = 0, $gz = false) {
	$_fputs = ($gz) ? gzputs : fputs;
	$nfields = mysql_num_fields($result);
	for ($i = 0; $i < $nfields; ++$i) $fields[$i] = mysql_field_name($result, $i);
	while ($row = mysql_fetch_array($result)) {
		$string .= build_begin_tag($type) . "\n";
		for ($i = 0; $i < $nfields; ++$i) {
			$string .= '<'.$fields[$i].'>' . text_to_xml($row[$i]) . '</'.$fields[$i].'>' . "\n";
		}
		if ($type == 'article') {
			$query = 'SELECT id_auteur FROM spip_auteurs_articles WHERE id_article='.$row['id_article'];
			$res2 = mysql_query($query);
			while($row2 = mysql_fetch_array($res2)) {
				$string .= '<lien:auteur>' . $row2[0] . '</lien:auteur>' . "\n";
			}
			mysql_free_result($res2);
		}
		else if ($type == 'message') {
			$query = 'SELECT id_auteur FROM spip_auteurs_messages WHERE id_message='.$row['id_message'];
			$res2 = mysql_query($query);
			while($row2 = mysql_fetch_array($res2)) {
				$string .= '<lien:auteur>' . $row2[0] . '</lien:auteur>' . "\n";
			}
			mysql_free_result($res2);
		}
		else if ($type == 'auteur') {
			$query = 'SELECT id_rubrique FROM spip_auteurs_rubriques WHERE id_auteur='.$row['id_auteur'];
			$res2 = mysql_query($query);
			while($row2 = mysql_fetch_array($res2)) {
				$string .= '<lien:rubrique>' . $row2[0] . '</lien:rubrique>' . "\n";
			}
			mysql_free_result($res2);
		}
		else if ($type == 'mot') {
			$query = 'SELECT id_article FROM spip_mots_articles WHERE id_mot='.$row['id_mot'];
			$res2 = mysql_query($query);
			while($row2 = mysql_fetch_array($res2)) {
				$string .= '<lien:article>' . $row2[0] . '</lien:article>' . "\n";
			}
			mysql_free_result($res2);
			$query = 'SELECT id_breve FROM spip_mots_breves WHERE id_mot='.$row['id_mot'];
			$res2 = mysql_query($query);
			while($row2 = mysql_fetch_array($res2)) {
				$string .= '<lien:breve>' . $row2[0] . '</lien:breve>' . "\n";
			}
			mysql_free_result($res2);
		}
		$string .= build_end_tag($type) . "\n\n";
		if ($file) {
			$_fputs($file, $string);
			$string = '';
		}
	}
	mysql_free_result($result);
	if (!$file) return $string;
}



?>