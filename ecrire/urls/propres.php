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

if (!defined("_ECRIRE_INC_VERSION")) return; // securiser
if (!function_exists('generer_url_article')) { // si la place n'est pas prise
include_spip('base/abstract_sql');

/*

- Comment utiliser ce jeu d'URLs ?

Recopiez le fichier "htaccess.txt" du repertoire de base du site SPIP sous
le sous le nom ".htaccess" (attention a ne pas ecraser d'autres reglages
que vous pourriez avoir mis dans ce fichier) ; si votre site est en
"sous-repertoire", vous devrez aussi editer la ligne "RewriteBase" ce fichier.
Les URLs definies seront alors redirigees vers les fichiers de SPIP.

Definissez ensuite dans ecrire/mes_options.php :
	< ?php $type_urls = 'propres'; ? >
SPIP calculera alors ses liens sous la forme "Mon-titre-d-article".

Variante 'propres2' :
	< ?php $type_urls = 'propres2'; ? >
ajoutera '.html' aux adresses generees : "Mon-titre-d-article.html"

Variante 'qs' (experimentale) : ce systeme fonctionne en "Query-String",
c'est-a-dire sans utilisation de .htaccess ; les adresses sont de la forme
"/?Mon-titre-d-article"
	< ?php $type_urls = 'qs'; ? >
*/

define ('_terminaison_urls_propres', '');
define ('_debut_urls_propres', '');

// Ces chaines servaient de marqueurs a l'epoque ou les URL propres devaient
// indiquer la table ou les chercher (articles, auteurs etc),
// et elles etaient retirees par les preg_match dans la fonction ci-dessous.
// Elles sont a present definies a "" pour avoir des URL plus jolies
// mais les preg_match restent necessaires pour gerer les anciens signets.

/*
define ('_marqueur_rubrique', '-');
define ('_marqueur_auteur', '_');
define ('_marqueur_breve', '+');
define ('_marqueur_site', '@');
define ('_marqueur_mot_d', '+-');
define ('_marqueur_mot_f', '-+');
*/
define ('_marqueur_rubrique', '');
define ('_marqueur_auteur', '');
define ('_marqueur_breve', '');
define ('_marqueur_site', '');
define ('_marqueur_mot_d', '');
define ('_marqueur_mot_f', '');

// Retire les marqueurs de type dans une URL propre ancienne maniere

// http://doc.spip.org/@retirer_marqueurs_url_propre
function retirer_marqueurs_url_propre($url_propre)
{
	if (preg_match(',^\+\-(.*?)\-\+$,', $url_propre, $regs)) {
		return $regs[1];
	}
	else if (preg_match(',^-(.*?)-?$,', $url_propre, $regs)) {
		return $regs[1];
	}
	else if (preg_match(',^\+(.*?)\+?$,', $url_propre, $regs)) {
		return $regs[1];
	}
	else if (preg_match(',^_(.*?)_?$,', $url_propre, $regs)) {
		return $regs[1];
	}
	else if (preg_match(',^@(.*?)@?$,', $url_propre, $regs)) {
		return $regs[1];
	}
	// les articles n'ont pas de marqueur
	return $url_propre;
}


// http://doc.spip.org/@_generer_url_propre
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

	//  Recuperer une URL propre correspondant a l'objet.
	$row = sql_fetsel("U.url, U.date, O.$champ_titre", "$table AS O LEFT JOIN spip_urls AS U ON (U.type='$type' AND U.id_objet=O.$col_id)", "O.$col_id=$id_objet", '', 'U.date DESC', 1);

	if (!$row) return ""; # objet inexistant

	$url_propre = $row['url'];

	// Se contenter de cette URL si
	// elle existe et qu'on n'est pas un admin invoquant spip_redirect.

	if ($url_propre AND 
	    (_request('action') != 'redirect' OR
	     $GLOBALS['auteur_session']['statut'] != '0minirezo'))
		return $url_propre;

	// Sinon, creer une URL
	include_spip('inc/filtres');
	$url = translitteration(corriger_caracteres(
		supprimer_tags(supprimer_numero(extraire_multi($row['titre'])))
		));
	$url = @preg_replace(',[[:punct:][:space:]]+,u', ' ', $url);

	// S'il reste trop de caracteres non latins, ou trop peu
	// de caracteres latins, utiliser l'id a la place
	if (preg_match(",([^a-zA-Z0-9 ].*){5},", $url, $r)
	OR strlen($url)<3) {
		$url = $type.$id_objet;
	}
	else {
		$mots = preg_split(",[^a-zA-Z0-9]+,", $url);
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

	// Eviter de tamponner les URLs a l'ancienne (cas d'un article
	// intitule "auteur2")
	if ($type == 'article'
	AND preg_match(',^(article|breve|rubrique|mot|auteur)[0-9]+$,', $url))
		$url = $url.','.$id_objet;

	// Le redirect n'était pas du a un chgt de titre. Rien de neuf.
	if ($url == $url_propre) return $url;

	$set = array('url' => $url, 'type' => $type, 'id_objet' => $id_objet);
	// Si l'insertion echoue, c'est une violation d'unicite.
	// Soit c'est un Come Back d'une ancienne url propre de l'objet
	// Soit c'est un vrai conflit. Rajouter l'ID jusqu'a ce que ca passe, 
	// mais se casser avant que ca ne casse.

	while (@sql_insertq('spip_urls', $set) <= 0) {
		$where = "U.type='$type' AND U.id_objet=$id_objet AND url=";
		if (sql_countsel('spip_urls AS U', $where  ._q($set['url']))) {
			sql_update('spip_urls AS U', array('date' => 'NOW()'), $where  ._q($set['url']));
			spip_log("reordonne $type $id_objet");
			return $set['url'];
		}
		$set['url'] .= ','.$id_objet;
		if (strlen($set['url']) > 200)
			return $url_propre; //serveur out ? retourner au mieux
		elseif (sql_countsel('spip_urls AS U', $where  ._q($set['url'])))
			return $set['url']; 
	}

	spip_log("Creation de l'url propre '" . $set['url'] . "' pour $col_id=$id_objet");

	return $set['url'];
}

// http://doc.spip.org/@generer_url_article
function generer_url_article($id_article, $args='', $ancre='') {
	$url = _generer_url_propre('article', $id_article);
	if ($url)
		$url = _debut_urls_propres . $url . _terminaison_urls_propres 
		. (!$args ? ''
		: (((strpos(_debut_urls_propres, '?')===false) ? '?' : '&') . $args));
	else
		$url = get_spip_script('./')."?page=article&id_article=$id_article" . ($args ? "&$args" : '');
	if ($ancre) $url .= "#$ancre";
	return $url;
}

// http://doc.spip.org/@generer_url_rubrique
function generer_url_rubrique($id_rubrique, $args='', $ancre='') {
	$url = _generer_url_propre('rubrique', $id_rubrique);
	if ($url)
		$url = _debut_urls_propres . _marqueur_rubrique . $url._marqueur_rubrique._terminaison_urls_propres
		. (!$args ? ''
		: (((strpos(_debut_urls_propres, '?')===false) ? '?' : '&') . $args));
	else
		$url = get_spip_script('./')."?page=rubrique&id_rubrique=$id_rubrique" . ($args ? "&$args" : '');
	if ($ancre) $url .= "#$ancre";
	return $url;
}

// http://doc.spip.org/@generer_url_breve
function generer_url_breve($id_breve, $args='', $ancre='') {
	$url = _generer_url_propre('breve', $id_breve);
	if ($url)
		$url = _debut_urls_propres . _marqueur_breve . $url._marqueur_breve._terminaison_urls_propres
		. (!$args ? ''
		: (((strpos(_debut_urls_propres, '?')===false) ? '?' : '&') . $args));
	else
		$url = get_spip_script('./')."?page=breve&id_breve=$id_breve"  . ($args ? "&$args" : '');
	if ($ancre) $url .= "#$ancre";
	return $url;
}

// http://doc.spip.org/@generer_url_forum
function generer_url_forum($id_forum, $args='', $ancre='') {
	include_spip('inc/forum');
	return generer_url_forum_dist($id_forum, $args, $ancre);
}

// http://doc.spip.org/@generer_url_mot
function generer_url_mot($id_mot, $args='', $ancre='') {
	$url = _generer_url_propre('mot', $id_mot);
	if ($url)
		$url = _debut_urls_propres . _marqueur_mot_d . $url._marqueur_mot_f._terminaison_urls_propres
		. (!$args ? ''
		: (((strpos(_debut_urls_propres, '?')===false) ? '?' : '&') . $args));
	else
		$url = get_spip_script('./')."?page=mot&id_mot=$id_mot" . ($args ? "&$args" : '');
	if ($ancre) $url .= "#$ancre";
	return $url;
}

// http://doc.spip.org/@generer_url_auteur
function generer_url_auteur($id_auteur, $args='', $ancre='') {
	$url = _generer_url_propre('auteur', $id_auteur);
	if ($url)
		$url = _debut_urls_propres . _marqueur_auteur . $url._marqueur_auteur._terminaison_urls_propres
		. (!$args ? ''
		: (((strpos(_debut_urls_propres, '?')===false) ? '?' : '&') . $args));
	else
		$url = get_spip_script('./')."?page=auteur&id_auteur=$id_auteur" . ($args ? "&$args" : '');
	if ($ancre) $url .= "#$ancre";
	return $url;
}

// http://doc.spip.org/@generer_url_site
function generer_url_site($id_syndic, $args='', $ancre='') {
	$url = _generer_url_propre('site', $id_syndic);
	if ($url)
		$url = _debut_urls_propres . _marqueur_site . $url._marqueur_site._terminaison_urls_propres
		. (!$args ? ''
		: (((strpos(_debut_urls_propres, '?')===false) ? '?' : '&') . $args));
	else
		$url = get_spip_script('./')."?page=site&id_syndic=$id_syndic" . ($args ? "&$args" : '');
	if ($ancre) $url .= "#$ancre";
	return $url;
}

// http://doc.spip.org/@generer_url_document
function generer_url_document($id_document, $args='', $ancre='') {
	include_spip('inc/documents');
	return generer_url_document_dist($id_document, $args, $ancre);
}

// retrouve le fond et les parametres d'une URL propre
// http://doc.spip.org/@urls_propres_dist
function urls_propres_dist(&$fond, $url) {
	global $contexte;
	$id_objet = $type = 0;

	// Migration depuis anciennes URLs ?
	if ($_SERVER['REQUEST_METHOD'] != 'POST') {
		if (preg_match(
		',(^|/)(article|breve|rubrique|mot|auteur|site)(\.php3?|[0-9]+\.html)'
		.'([?&].*)?$,', $url, $regs)
		) {
			$type = $regs[3];
			$id_table_objet = id_table_objet($type);
			$id_objet = intval($GLOBALS[$id_table_objet]);
		}

		/* Compatibilite urls-page */
		else if (preg_match(
		',[?/&](article|breve|rubrique|mot|auteur|site)[=]?([0-9]+),',
		$url, $regs)) {
			$type = $regs[1];
			$id_objet = $regs[2];
		}
	}
	if ($id_objet) {
		$func = "generer_url_$type";
		$url_propre = $func($id_objet);
		if (strlen($url_propre)
		AND !strstr($url,$url_propre)) {
			include_spip('inc/headers');
			http_status(301);
			// recuperer les arguments supplementaires (&debut_xxx=...)
			$reste = preg_replace('/^&/','?',
				preg_replace("/[?&]$id_table_objet=$id_objet/",'',$regs[5]));
			$reste .= preg_replace('/&/','?',
				preg_replace('/[?&]'.$type.'[=]?'.$id_objet.'/','',
				substr($url, strpos($url,'?'))));
			redirige_par_entete("$url_propre$reste");
		}
	}
	/* Fin compatibilite anciennes urls */

	// Chercher les valeurs d'environnement qui indiquent l'url-propre
	if (isset($_SERVER['REDIRECT_url_propre']))
		$url_propre = $_SERVER['REDIRECT_url_propre'];
	elseif (isset($_ENV['url_propre']))
		$url_propre = $_ENV['url_propre'];
	else {
		$url = substr($url, strrpos($url, '/') + 1);
		$url_propre = preg_replace(',[?].*,', '', $url);
	}
	// Mode Query-String ?

	if (!$url_propre
	AND preg_match(',([?])([^=/?&]+)(&.*)?$,', $GLOBALS['REQUEST_URI'], $r)) {
		$url_propre = $r[2];
	}

	if (!$url_propre) return;

	// Compatilibite avec propres2
	$url_propre = preg_replace(',\.html$,i', '', $url_propre);

	// Compatibilite avec les anciennes URL propres

	$url_propre = retirer_marqueurs_url_propre($url_propre);

	$row = sql_fetch(spip_query("SELECT id_objet, type FROM spip_urls WHERE url=" . _q($url_propre)));

	if ($row) {
		$type = $row['type'];
		$col_id = id_table_objet($type);
		$contexte[$col_id] = $row['id_objet'];
	}

	if ($type AND ($adapter_le_fond OR $fond='type_urls')) {

	$fond =  ($type == 'syndic') ?  'site' : $type;
	}
}
} // function_exists
?>
