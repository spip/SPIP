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


// executer une seule fois
if (defined("_INC_URLS2")) return;
define("_INC_URLS2", "1");


/*

- Comment utiliser ce jeu d'URLs ?

Il faut recopier le fichier htaccess-propres.txt sous le nom .htaccess
dans le repertoire de base du site SPIP (attention a ne pas ecraser
d'autres reglages que vous pourriez avoir mis dans ce fichier)

*/


function _generer_url_propre($type, $id_objet) {
	$table = "spip_".$type."s";
	$col_id = "id_".$type;

	// D'abord, essayer de recuperer l'URL existante si possible
	$query = "SELECT url_propre, titre FROM $table WHERE $col_id=$id_objet";
	$result = spip_query($query);
	if (!($row = spip_fetch_array($result))) return "";
	if ($row['url_propre']) return $row['url_propre'];

	// Sinon, creer l'URL
	include_ecrire("inc_filtres.php3");
	include_ecrire("inc_charsets.php3");
	$url = translitteration(corriger_caracteres(
		supprimer_tags(supprimer_numero(extraire_multi($row['titre'])))
		));
	$url = @preg_replace(',[[:punct:][:space:]]+,u', ' ', $url);
	// S'il reste des caracteres non latins, utiliser l'id a la place
	if (preg_match(",[^a-zA-Z0-9 ],", $url)) {
		$url = $type.$id_objet;
	}
	else {
		$mots = explode(' ', $url);
		$url = '';
		foreach ($mots as $mot) {
			if (!$mot) continue;
			$url2 = $url.'-'.$mot;
			if (strlen($url2) > 35) {
				break;
			}
			$url = $url2;
		}
		$url = substr($url, 1);
		//echo "$url<br>";
		if (strlen($url) < 2) $url = $type.$id_objet;
	}

	// Verifier les eventuels doublons et mettre a jour
	$lock = "url $type $id_objet";
	spip_get_lock($lock, 10);
	$query = "SELECT $col_id FROM $table
		WHERE url_propre='".addslashes($url)."'";
	if (spip_num_rows(spip_query($query)) > 0) {
		$url = $url.','.$id_objet;
	}
	$query = "UPDATE $table SET url_propre='".addslashes($url)."' WHERE $col_id=$id_objet";
	spip_query($query);
	spip_release_lock($lock);

	return $url;
}

function generer_url_article($id_article) {
	$url = _generer_url_propre('article', $id_article);
	if (!$url) $url = "article.php3?id_article=$id_article";
	return $url;
}

function generer_url_rubrique($id_rubrique) {
	$url = _generer_url_propre('rubrique', $id_rubrique);
	if (!$url) $url = "rubrique.php3?id_rubrique=$id_rubrique";
	else $url = '-'.$url.'-';
	return $url;
}

function generer_url_breve($id_breve) {
	$url = _generer_url_propre('breve', $id_breve);
	if (!$url) $url = "breve.php3?id_breve=$id_breve";
	else $url = '+'.$url.'+';
	return $url;
}

function generer_url_forum($id_forum, $show_thread=false) {
	include_ecrire('inc_forum.php3');
	return generer_url_forum_dist($id_forum, $show_thread);
}

function generer_url_mot($id_mot) {
	$url = _generer_url_propre('mot', $id_mot);
	if (!$url) $url = "mot.php3?id_mot=$id_mot";
	else $url = '+-'.$url.'-+';
	return $url;
}

function generer_url_auteur($id_auteur) {
	return "auteur.php3?id_auteur=$id_auteur";
}

function generer_url_document($id_document) {
	if (intval($id_document) <= 0)
		return '';
	if ((lire_meta("creer_htaccess")) == 'oui')
		return "spip_acces_doc.php3?id_document=$id_document";
	if ($row = @spip_fetch_array(spip_query("SELECT fichier FROM spip_documents WHERE id_document = $id_document")))
		return ($row['fichier']);
	return '';
}

function recuperer_parametres_url($fond, $url) {
	global $contexte;

	// Migration depuis anciennes URLs ?
	if ($GLOBALS['_SERVER']['REQUEST_METHOD'] != 'POST' &&
		preg_match(',(^|/)((article|breve|rubrique|mot)\.php3?([\?&].*)?)$,', $url, $regs)) {
		$type = $regs[3];
		$id_objet = intval($GLOBALS['id_'.$type]);
		if ($id_objet) {
			$func = "generer_url_$type";
			$url_propre = $func($id_objet);
			if ($url_propre
			AND ($url_propre<>$regs[2])) {
				http_status(301);
				Header("Location: $url_propre");
				exit;
			}
		}
		return;
	}

	$url_propre = $GLOBALS['_SERVER']['REDIRECT_url_propre'];
	if (!$url_propre) $url_propre = $GLOBALS['HTTP_ENV_VARS']['url_propre'];
	if (!$url_propre) $url_propre = substr($url, strrpos($url, '/') + 1);
	if (!$url_propre) return;

	// Detecter les differents types d'objets demandes
	if (preg_match(',^\+-(.*)-\+$,', $url_propre, $regs)) {
		$type = 'mot';
		$url_propre = $regs[1];
	}
	else if (preg_match(',^-(.*)-$,', $url_propre, $regs)) {
		$type = 'rubrique';
		$url_propre = $regs[1];
	}
	else if (preg_match(',^\+(.*)\+$,', $url_propre, $regs)) {
		$type = 'breve';
		$url_propre = $regs[1];
	}
	else {
		$type = 'article';
	}

	$table = "spip_".$type."s";
	$col_id = "id_".$type;
	$query = "SELECT $col_id FROM $table
		WHERE url_propre='".addslashes($url_propre)."'";
	$result = spip_query($query);
	if (spip_num_rows($result) == 1) {
		$row = spip_fetch_array($result);
		$contexte[$col_id] = $row[$col_id];
	}

	return;
}

?>