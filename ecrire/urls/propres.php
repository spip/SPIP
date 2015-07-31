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


// TODO: une interface permettant de verifier qu'on veut effectivment modifier
// une adresse existante
define('CONFIRMER_MODIFIER_URL', false);


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

#define('_MARQUEUR_URL', serialize(array('rubrique1' => '-', 'rubrique2' => '-', 'breve1' => '+', 'breve2' => '+', 'site1' => '@', 'site2' => '@', 'auteur1' => '_', 'auteur2' => '_', 'mot1' => '+-', 'mot2' => '-+')));
define('_MARQUEUR_URL', false);

// Retire les marqueurs de type dans une URL propre ancienne maniere

// http://doc.spip.org/@retirer_marqueurs_url_propre
function retirer_marqueurs_url_propre($url_propre) {
	if (preg_match(',^[+][-](.*?)[-][+]$,', $url_propre, $regs)) {
		return $regs[1];
	}
	else if (preg_match(',^([-+_@])(.*?)\1?$,', $url_propre, $regs)) {
		return $regs[2];
	}
	// les articles n'ont pas de marqueur
	return $url_propre;
}

// Pipeline pour creation d'une adresse : il recoit l'url propose par le
// precedent, un tableau indiquant le titre de l'objet, son type, son id,
// et doit donner en retour une chaine d'url, sans se soucier de la
// duplication eventuelle, qui sera geree apres
// http://doc.spip.org/@creer_chaine_url
function creer_chaine_url($x) {
	// NB: ici url_old ne sert pas, mais un plugin qui ajouterait une date
	// pourrait l'utiliser pour juste ajouter la 
	$url_old = $x['data'];
	$objet = $x['objet'];
	include_spip('inc/filtres');

	$titre = supprimer_tags(supprimer_numero(extraire_multi($objet['titre'])));
	$url = translitteration(corriger_caracteres($titre));
	$url = @preg_replace(',[[:punct:][:space:]]+,u', ' ', $url);

	// S'il reste trop de caracteres non latins, les gerer comme wikipedia
	// avec rawurlencode :
	if (preg_match_all(",[^a-zA-Z0-9 ]+,", $url, $r, PREG_SET_ORDER)) {
		foreach ($r as $regs) {
			$url = substr_replace($url, rawurlencode($regs[0]),
				strpos($url, $regs[0]), strlen($regs[0]));
		}
	}

	// S'il reste trop peu, on retombe sur article12
	if (strlen($url) < 3) {
		$url = $objet['type'].$objet['id_objet'];
	}

	// Sinon couper les mots et les relier par des tirets
	else {
		$mots = preg_split(",[^a-zA-Z0-9%]+,", $url);
		$url = '';
		foreach ($mots as $mot) {
			if (!$mot) continue;
			$url2 = $url.'-'.$mot;

			// Si on depasse _URLS_PROPRES_MAX caracteres, s'arreter
			// ne pas compter 3 caracteres pour %E9 mais un seul
			define('_URLS_PROPRES_MAX', 35);
			$long = preg_replace(',%.,', '', $url2);
			if (strlen($long) > _URLS_PROPRES_MAX) {
				break;
			}

			$url = $url2;
		}
		$url = substr($url, 1);

		// On enregistre en utf-8 dans la base
		$url = rawurldecode($url);

		if (strlen($url) < 2)
			$url = $objet['type'].$objet['id_objet']; // 'article12'
	}

	$x['data'] = $url;

	return $x;
}


// http://doc.spip.org/@_generer_url_propre
function _generer_url_propre($type, $id_objet) {
	$table = table_objet_sql($type);
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


	// Se contenter de cette URL si elle existe ;
	// sauf si on invoque action=redirect avec droit de modifier l'url
	$modifier_url = (
		_request('action') == 'redirect'
		AND autoriser('modifierurl', $type, $id_objet)
	);

	if ($url_propre AND !$modifier_url)
		return $url_propre;

	// Sinon, creer une URL
	$url = pipeline('creer_chaine_url',
		array(
			'data' => $url_propre,  // le vieux url_propre
			'objet' => array_merge($row,
				array('type' => $type, 'id_objet' => $id_objet)
			)
		)
	);

	// Eviter de tamponner les URLs a l'ancienne (cas d'un article
	// intitule "auteur2")
	if (preg_match(',^(article|breve|rubrique|mot|auteur|site)[0-9]+$,', $url, $r)
	AND $r[1] != $type)
		$url = $url.','.$id_objet;

	// Pas de changement d'url
	if ($url == $url_propre)
		return $url_propre;

	// Verifier si l'utilisateur veut effectivement changer l'URL
	if ($modifier_url
	AND CONFIRMER_MODIFIER_URL
	AND $url_propre
	AND $url != preg_replace('/,.*/', '', $url_propre)
	AND !_request('ok')) {
		die ("vous changez d'url ? $url_propre -&gt; $url");
	}

	$set = array('url' => $url, 'type' => $type, 'id_objet' => $id_objet);

	// Si l'insertion echoue, c'est une violation d'unicite.
	if (@sql_insertq('spip_urls', $set) <= 0) {

		// On veut chiper une ancienne adresse ?
		if (
		// un vieux url
		$vieux = sql_fetsel('*', 'spip_urls', 'url='._q($set['url']))
		// l'objet a une url plus recente
		AND $courant = sql_fetsel('*', 'spip_urls',
			'type='._q($vieux['type']).' AND id_objet='._q($vieux['id_objet'])
			.' AND date>'._q($vieux['date']), '', 'date DESC', 1
		)) {
			if ($modifier_url
			AND CONFIRMER_MODIFIER_URL
			AND $url != preg_replace('/,.*/', '', $url_propre)
			AND ($vieux['type'] != $set['type'] OR $vieux['id_objet'] != $set['id_objet'])
			AND !_request('ok2')) {
				die ("Vous voulez chiper l'URL de l'objet ".$courant['type']." "
					. $courant['id_objet']." qui a maintenant l'url "
					. $courant['url']);
			}

			// si oui on le chipe
			sql_updateq('spip_urls', $set, 'url='._q($set['url']));
			sql_update('spip_urls', array('date' => 'NOW()'), 'url='._q($set['url']));
		}

		// Sinon
		else
		
		// Soit c'est un Come Back d'une ancienne url propre de l'objet
		// Soit c'est un vrai conflit. Rajouter l'ID jusqu'a ce que ca passe, 
		// mais se casser avant que ca ne casse.
		do {
			$where = "U.type='$type' AND U.id_objet=$id_objet AND url=";
			if (sql_countsel('spip_urls AS U', $where  ._q($set['url']))) {
				sql_update('spip_urls AS U', array('date' => 'NOW()'), $where  ._q($set['url']));
				spip_log("reordonne $type $id_objet");
				return $set['url'];
			}
			else {
				$set['url'] .= ','.$id_objet;
				if (strlen($set['url']) > 200)
					return $url_propre; //serveur out ? retourner au mieux
				elseif (sql_countsel('spip_urls AS U', $where . _q($set['url']))) {
					sql_update('spip_urls', array('date' => 'NOW()'), 'url='._q($set['url']));
					return $set['url']; 
				}
			}
		} while (@sql_insertq('spip_urls', $set) <= 0);
	}

	sql_update('spip_urls', array('date' => 'NOW()'), 'url='._q($set['url']));
	spip_log("Creation de l'url propre '" . $set['url'] . "' pour $col_id=$id_objet");

	return $set['url'];
}

// http://doc.spip.org/@_generer_url_complete
function _generer_url_complete($type, $id, $args='', $ancre='') {

	// Mode compatibilite pour conserver la distinction -Rubrique-
	if (_MARQUEUR_URL) {
		$marqueur = unserialize(_MARQUEUR_URL);
		$marqueur1 = $marqueur[$type.'1']; // debut '+-'
		$marqueur2 = $marqueur[$type.'2']; // fin '-+'
	} else
		$marqueur1 = $marqueur2 = '';
	// fin

	// Mode propre
	if ($propre = _generer_url_propre($type, $id)) {
		$url = _debut_urls_propres
			. $marqueur1
			. $propre
			. $marqueur2
			. _terminaison_urls_propres;

		// Repositionne l'URL par rapport a la racine du site (#GLOBALS)
		$url = str_repeat('../', $GLOBALS['profondeur_url']).$url;
	}

	// propre ne veut pas !
	else {
		if ($type == 'site')
			$id_type = 'id_syndic';
		else
			$id_type = 'id_'.$type;
		$url = get_spip_script('./')."?page=$type&$id_type=$id";
	}

	// Ajouter les args
	if ($args)
		$url .= ((strpos($url, '?')===false) ? '?' : '&') . $args;

	// Ajouter l'ancre
	if ($ancre)
		$url .= "#$ancre";

	return $url;
}

// http://doc.spip.org/@generer_url_article
function generer_url_article($id_article, $args='', $ancre='') {
	return _generer_url_complete('article', $id_article, $args, $ancre);
}

// http://doc.spip.org/@generer_url_rubrique
function generer_url_rubrique($id_rubrique, $args='', $ancre='') {
	return _generer_url_complete('rubrique', $id_rubrique, $args, $ancre);
}

// http://doc.spip.org/@generer_url_breve
function generer_url_breve($id_breve, $args='', $ancre='') {
	return _generer_url_complete('breve', $id_breve, $args, $ancre);
}

// http://doc.spip.org/@generer_url_forum
function generer_url_forum($id_forum, $args='', $ancre='') {
	include_spip('inc/forum');
	return generer_url_forum_dist($id_forum, $args, $ancre);
}

// http://doc.spip.org/@generer_url_mot
function generer_url_mot($id_mot, $args='', $ancre='') {
	return _generer_url_complete('mot', $id_mot, $args, $ancre);
}

// http://doc.spip.org/@generer_url_auteur
function generer_url_auteur($id_auteur, $args='', $ancre='') {
	return _generer_url_complete('auteur', $id_auteur, $args, $ancre);
}

// http://doc.spip.org/@generer_url_site
function generer_url_site($id_syndic, $args='', $ancre='') {
	return _generer_url_complete('site', $id_syndic, $args, $ancre);
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

	// Compatibilite avec les anciens marqueurs d'URL propres
	$url_propre = retirer_marqueurs_url_propre($url_propre);

	$row = sql_fetsel('id_objet, type, date', 'spip_urls', 'url='._q($url_propre));

	if ($row) {
		$type = $row['type'];

		// Redirection 301 si l'url est vieux
		if ($recent = sql_fetsel('url, date', 'spip_urls',
		'type='._q($row['type']).' AND id_objet='._q($row['id_objet'])
		.' AND date>'._q($row['date']), '', 'date DESC', 1)) {
			spip_log('Redirige '.$url_propre.' vers '.$recent['url']);
			include_spip('inc/headers');
			redirige_par_entete($recent['url']);
		}


		$col_id = id_table_objet($type);
		$contexte[$col_id] = $row['id_objet'];
	}

	if ($type AND ($adapter_le_fond OR $fond=='type_urls')) {

		$fond =  ($type == 'syndic') ?  'site' : $type;
	} else if ($fond=='type_urls') {
		$fond = '404';
		$contexte['erreur'] = ''; // qu'afficher ici ?  l'url n'existe pas... on ne sait plus dire de quel type d'objet il s'agit
	}
}
} // function_exists
?>
