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

if (!defined("_ECRIRE_INC_VERSION")) return; // securiser
if (!function_exists('generer_url_article')) { // si la place n'est pas prise

/*

- Comment utiliser ce jeu d'URLs ?

Recopiez le fichier "htaccess.txt" du repertoire de base du site SPIP sous
le sous le nom ".htaccess" (attention a ne pas ecraser d'autres reglages
que vous pourriez avoir mis dans ce fichier) ; si votre site est en
"sous-repertoire", vous devrez aussi editer la ligne "RewriteBase" ce fichier.
Les URLs definies seront alors redirigees vers les fichiers de SPIP.

Definissez ensuite dans ecrire/mes_options.php3 :
	type_urls = 'propres';
SPIP calculera alors ses liens sous la forme "Mon-titre-d-article".

Variante 'propres2' :
	type_urls = 'propres2';
ajoutera '.html' aux adresses generees : "Mon-titre-d-article.html"

Variante 'qs' : ce systeme fonctionne en "Query-String", c'est-a-dire
sans utilisation de .htaccess ; les adresses sont de la forme
"/?Mon-titre-d-article"
	type_urls = 'qs';

*/

if (!defined('_terminaison_urls_propres'))
	define ('_terminaison_urls_propres', '');
if (!defined('_debut_urls_propres'))
	define ('_debut_urls_propres', '');

function _generer_url_propre($type, $id_objet) {
	$table = "spip_".table_objet($type);
	$col_id = id_table_objet($type);

	// Auteurs : on prend le nom
	if ($type == 'auteur')
		$champ_titre = 'nom AS titre';
	else if ($type == 'site' OR $type=='syndic')
		$champ_titre = 'nom_site AS titre';
	else
		$champ_titre = 'titre';

	// Mots-cles : pas de champ statut
	if ($type == 'mot')
		$statut = "'publie' as statut";
	else
		$statut = 'statut';

	// D'abord, essayer de recuperer l'URL existante si possible
	$result = spip_query("SELECT url_propre, $statut, $champ_titre
	FROM $table WHERE $col_id=$id_objet");
	if (!($row = spip_fetch_array($result))) return ""; # objet inexistant

	// Si l'on n'est pas dans spip_redirect.php3 sur un objet non publie
	// ou en preview (astuce pour corriger un url-propre) + admin connecte
	// Ne pas recalculer l'url-propre,
	// sauf si :
	// 1) il n'existe pas, ou
	// 2) l'objet n'est pas 'publie' et on est admin connecte, ou
	// 3) on le demande explicitement (preview) et on est admin connecte
	if (function_exists('spip_action_redirect_dist') AND
	($GLOBALS['preview'] OR ($row['statut'] <> 'publie'))
	AND $GLOBALS['auteur_session']['statut'] == '0minirezo')
		$modif_url_propre = true;

	if ($row['url_propre'] AND !$modif_url_propre)
		return $row['url_propre'];

	// Sinon, creer l'URL
	include_ecrire("inc_filtres");
	include_ecrire("inc_charsets");
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
		WHERE url_propre='".addslashes($url)."' AND $col_id != $id_objet";
	if (spip_num_rows(spip_query($query)) > 0) {
		$url = $url.','.$id_objet;
	}

	// Eviter de tamponner les URLs a l'ancienne (cas d'un article
	// intitule "auteur2")
	if ($type == 'article'
	AND preg_match(',^(article|breve|rubrique|mot|auteur)[0-9]+$,', $url))
		$url = $url.','.$id_objet;

	// Mettre a jour dans la base
	$query = "UPDATE $table SET url_propre='".addslashes($url)."' WHERE $col_id=$id_objet";
	spip_query($query);
	spip_release_lock($lock);

	spip_log("Creation de l'url propre '$url' pour $col_id=$id_objet");

	return $url;
}

function generer_url_article($id_article) {
	$url = _generer_url_propre('article', $id_article);
	if ($url)
		return _debut_urls_propres . $url . _terminaison_urls_propres;
	else
		return "article.php3?id_article=$id_article";
}

function generer_url_rubrique($id_rubrique) {
	$url = _generer_url_propre('rubrique', $id_rubrique);
	if ($url)
		return _debut_urls_propres . '-'.$url.'-'._terminaison_urls_propres;
	else
		return "rubrique.php3?id_rubrique=$id_rubrique";
}

function generer_url_breve($id_breve) {
	$url = _generer_url_propre('breve', $id_breve);
	if ($url)
		return _debut_urls_propres . '+'.$url.'+'._terminaison_urls_propres;
	else
		return "breve.php3?id_breve=$id_breve";
}

function generer_url_forum($id_forum, $show_thread=false) {
	include_ecrire('inc_forum');
	return generer_url_forum_dist($id_forum, $show_thread);
}

function generer_url_mot($id_mot) {
	$url = _generer_url_propre('mot', $id_mot);
	if ($url)
		return _debut_urls_propres . '+-'.$url.'-+'._terminaison_urls_propres;
	else
		return "mot.php3?id_mot=$id_mot";
}

function generer_url_auteur($id_auteur) {
	$url = _generer_url_propre('auteur', $id_auteur);
	if ($url)
		return _debut_urls_propres . '_'.$url.'_'._terminaison_urls_propres;
	else
		return "auteur.php3?id_auteur=$id_auteur";
}

function generer_url_site($id_syndic) {
	$url = _generer_url_propre('site', $id_syndic);
	if ($url)
		return _debut_urls_propres . '@'.$url.'@'._terminaison_urls_propres;
	else
		return "site.php3?id_syndic=$id_syndic";
}

function generer_url_document($id_document) {
	if (intval($id_document) <= 0)
		return '';
	if (($GLOBALS['meta']["creer_htaccess"]) == 'oui')
		return generer_url_action('autoriser',"arg=$id_document");
	if ($row = @spip_fetch_array(spip_query("SELECT fichier FROM spip_documents WHERE id_document = $id_document")))
		return ($row['fichier']);
	return '';
}

function recuperer_parametres_url(&$fond, $url) {
	global $contexte;

	// Migration depuis anciennes URLs ?
	if ($GLOBALS['_SERVER']['REQUEST_METHOD'] != 'POST' &&
preg_match(',(^|/)((article|breve|rubrique|mot|auteur|site)(\.php3?|[0-9]+\.html)([?&].*)?)$,', $url, $regs)) {
		$type = $regs[3];
		$id_objet = intval($GLOBALS[$id_table_objet = id_table_objet($type)]);
		if ($id_objet) {
			$func = "generer_url_$type";
			$url_propre = $func($id_objet);
			if ($url_propre
			AND ($url_propre<>$regs[2])) {
				include_ecrire('inc_headers');
				http_status(301);
				// recuperer les arguments supplementaires (&debut_xxx=...)
				$reste = preg_replace('/^&/','?',
					preg_replace("/[?&]$id_table_objet=$id_objet/",'',$regs[5]));
				redirige_par_entete("$url_propre$reste");
			}
		}
		return;
	}

	// Chercher les valeurs d'environnement qui indiquent l'url-propre
	$url_propre = $GLOBALS['_SERVER']['REDIRECT_url_propre'];
	if (!$url_propre)
		$url_propre = $GLOBALS['HTTP_ENV_VARS']['url_propre'];
	if (!$url_propre) {
		$url = substr($url, strrpos($url, '/') + 1);
		$url_propre = preg_replace(',[?].*,', '', $url);
	}
	// Mode Query-String ?
	$adapter_le_fond = false;
	if (!$url_propre
	AND preg_match(',([?])([^=/?&]+)(&.*)?$,', $GLOBALS['REQUEST_URI'], $r)) {
		$url_propre = $r[2];
		$adapter_le_fond = true;
	}
	if (!$url_propre) return;

	// Compatilibite avec propres2
	$url_propre = preg_replace(',\.html$,i', '', $url_propre);

	// Detecter les differents types d'objets demandes
	if (preg_match(',^\+-(.*?)-?\+?$,', $url_propre, $regs)) {
		$type = 'mot';
		$url_propre = $regs[1];
	}
	else if (preg_match(',^-(.*?)-?$,', $url_propre, $regs)) {
		$type = 'rubrique';
		$url_propre = $regs[1];
	}
	else if (preg_match(',^\+(.*?)\+?$,', $url_propre, $regs)) {
		$type = 'breve';
		$url_propre = $regs[1];
	}
	else if (preg_match(',^_(.*?)_?$,', $url_propre, $regs)) {
		$type = 'auteur';
		$url_propre = $regs[1];
	}
	else if (preg_match(',^@(.*?)@?$,', $url_propre, $regs)) {
		$type = 'syndic';
		$url_propre = $regs[1];
	}
	else {
		$type = 'article';
		preg_match(',^(.*)$,', $url_propre, $regs);
		$url_propre = $regs[1];
	}

	$table = "spip_".table_objet($type);
	$col_id = id_table_objet($type);
	$query = "SELECT $col_id FROM $table
		WHERE url_propre='".addslashes($url_propre)."'";
	$result = spip_query($query);
	if ($row = spip_fetch_array($result)) {
		$contexte[$col_id] = $row[$col_id];
	}

	// En mode Query-String, on fixe ici le $fond utilise
	if ($adapter_le_fond)
		$fond = $type;

	return;
}
 }

?>
