<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return; // securiser

define('URLS_PROPRES_EXEMPLE', 'Titre-de-l-article -Rubrique-');

// TODO: une interface permettant de verifier qu'on veut effectivment modifier
// une adresse existante
define('CONFIRMER_MODIFIER_URL', false);

/*

- Comment utiliser ce jeu d'URLs ?

Recopiez le fichier "htaccess.txt" du repertoire de base du site SPIP sous
le sous le nom ".htaccess" (attention a ne pas ecraser d'autres reglages
que vous pourriez avoir mis dans ce fichier) ; si votre site est en
"sous-repertoire", vous devrez aussi editer la ligne "RewriteBase" ce fichier.
Les URLs definies seront alors redirigees vers les fichiers de SPIP.

Dans les pages de configuration, choisissez 'propres' comme type d'url

SPIP calculera alors ses liens sous la forme
	"Mon-titre-d-article".

La variante 'propres2' ajoutera '.html' aux adresses generees : 
	"Mon-titre-d-article.html"

Variante 'qs' (experimentale) : ce systeme fonctionne en "Query-String",
c'est-a-dire sans utilisation de .htaccess ; les adresses sont de la forme
	"/?Mon-titre-d-article"
*/

define ('_terminaison_urls_propres', '');
define ('_debut_urls_propres', '');

// Ces chaines servaient de marqueurs a l'epoque ou les URL propres devaient
// indiquer la table ou les chercher (articles, auteurs etc),
// et elles etaient retirees par les preg_match dans la fonction ci-dessous.
// Elles peuvent a present etre definies a "" pour avoir des URL plus jolies.
// Les preg_match restent necessaires pour gerer les anciens signets.

define('_MARQUEUR_URL', serialize(array('rubrique1' => '-', 'rubrique2' => '-', 'breve1' => '+', 'breve2' => '+', 'site1' => '@', 'site2' => '@', 'auteur1' => '_', 'auteur2' => '_', 'mot1' => '+-', 'mot2' => '-+')));

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


if (!function_exists('creer_chaine_url')) {
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
	@define('_URLS_PROPRES_MAX', 35);
	@define('_URLS_PROPRES_MIN', 3);
	$titre = supprimer_tags(supprimer_numero(extraire_multi($objet['titre'])));
	$url = translitteration(corriger_caracteres($titre));

	// on va convertir tous les caracteres de ponctuation et espaces
	// a l'exception de l'underscore (_), car on veut le conserver dans l'url
	$url = str_replace('_', chr(7), $url);
	$url = @preg_replace(',[[:punct:][:space:]]+,u', ' ', $url);
	$url = str_replace(chr(7), '_', $url);

	// S'il reste trop de caracteres non latins, les gerer comme wikipedia
	// avec rawurlencode :
	if (preg_match_all(",[^a-zA-Z0-9 _]+,", $url, $r, PREG_SET_ORDER)) {
		foreach ($r as $regs) {
			$url = substr_replace($url, rawurlencode($regs[0]),
				strpos($url, $regs[0]), strlen($regs[0]));
		}
	}

	// S'il reste trop peu, on retombe sur article12
	if (strlen($url) < _URLS_PROPRES_MIN) {
		$url = $objet['type'].$objet['id_objet'];
	}

	// Sinon couper les mots et les relier par des tirets
	else {
		$mots = preg_split(",[^a-zA-Z0-9_%]+,", $url);
		$url = '';
		foreach ($mots as $mot) {
			if (!strlen($mot)) continue;
			$url2 = $url.'-'.$mot;

			// Si on depasse _URLS_PROPRES_MAX caracteres, s'arreter
			// ne pas compter 3 caracteres pour %E9 mais un seul
			$long = preg_replace(',%.,', '', $url2);
			if (strlen($long) > _URLS_PROPRES_MAX) {
				break;
			}

			$url = $url2;
		}
		$url = substr($url, 1);

		// On enregistre en utf-8 dans la base
		$url = rawurldecode($url);

		if (strlen($url) < _URLS_PROPRES_MIN) # pourquoi  "-1" avant ?
			$url = $objet['type'].$objet['id_objet']; // 'article12'
	}

	$x['data'] = $url;

	return $x;
}
}


// Trouver l'URL associee a la n-ieme cle primaire d'une table SQL

// http://doc.spip.org/@declarer_url_propre
function declarer_url_propre($type, $id_objet) {
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table(table_objet($type));
	$table = $desc['table'];
	$champ_titre = $desc['titre'];
	$col_id =  @$desc['key']["PRIMARY KEY"];
	if (!$col_id) return false; // Quand $type ne reference pas une table

	$id_objet = intval($id_objet);


	//  Recuperer une URL propre correspondant a l'objet.
	$row = sql_fetsel("U.url, U.date, O.$champ_titre", "$table AS O LEFT JOIN spip_urls AS U ON (U.type='$type' AND U.id_objet=O.$col_id)", "O.$col_id=$id_objet", '', 'U.date DESC', 1);

	if (!$row) return ""; # Quand $id_objet n'est pas un numero connu

	$url_propre = $row['url'];

	// Se contenter de cette URL si elle existe ;
	// sauf si on invoque par "voir en ligne" avec droit de modifier l'url

	// l'autorisation est verifiee apres avoir calcule la nouvelle url propre
	// car si elle ne change pas, cela ne sert a rien de verifier les autorisations
	// qui requetent en base
	$modifier_url = $GLOBALS['var_urls'];
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

	// verifier l'autorisation, maintenant qu'on est sur qu'on va agir
	if ($modifier_url) {
		include_spip('inc/autoriser');
		$modifier_url = autoriser('modifierurl', $type, $id_objet);
	}

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
		$vieux = sql_fetsel('*', 'spip_urls', 'url='.sql_quote($set['url']))
		// l'objet a une url plus recente
		AND $courant = sql_fetsel('*', 'spip_urls',
			'type='.sql_quote($vieux['type']).' AND id_objet='.sql_quote($vieux['id_objet'])
			.' AND date>'.sql_quote($vieux['date']), '', 'date DESC', 1
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
			sql_updateq('spip_urls', $set, 'url='.sql_quote($set['url']));
			sql_update('spip_urls', array('date' => 'NOW()'), 'url='.sql_quote($set['url']));
		}

		// Sinon
		else
		
		// Soit c'est un Come Back d'une ancienne url propre de l'objet
		// Soit c'est un vrai conflit. Rajouter l'ID jusqu'a ce que ca passe, 
		// mais se casser avant que ca ne casse.
		do {
			$where = "U.type='$type' AND U.id_objet=$id_objet AND url=";
			if (sql_countsel('spip_urls AS U', $where  .sql_quote($set['url']))) {
				sql_update('spip_urls AS U', array('date' => 'NOW()'), $where  .sql_quote($set['url']));
				spip_log("reordonne $type $id_objet");
				return $set['url'];
			}
			else {
				$set['url'] .= ','.$id_objet;
				if (strlen($set['url']) > 200)
					return $url_propre; //serveur out ? retourner au mieux
				elseif (sql_countsel('spip_urls AS U', $where . sql_quote($set['url']))) {
					sql_update('spip_urls', array('date' => 'NOW()'), 'url='.sql_quote($set['url']));
					return $set['url']; 
				}
			}
		} while (@sql_insertq('spip_urls', $set) <= 0);
	}

	sql_update('spip_urls', array('date' => 'NOW()'), 'url='.sql_quote($set['url']));
	spip_log("Creation de l'url propre '" . $set['url'] . "' pour $col_id=$id_objet");

	return $set['url'];
}

// http://doc.spip.org/@_generer_url_propre
function _generer_url_propre($type, $id, $args='', $ancre='') {

	if ($generer_url_externe = charger_fonction("generer_url_$type",'urls',true))
		return $generer_url_externe($id, $args, $ancre);

	if ($type == 'document') {
		include_spip('inc/documents');
		return generer_url_document_dist($id, $args, $ancre);
	}

	// Mode compatibilite pour conserver la distinction -Rubrique-
	if (_MARQUEUR_URL) {
		$marqueur = unserialize(_MARQUEUR_URL);
		$marqueur1 = $marqueur[$type.'1']; // debut '+-'
		$marqueur2 = $marqueur[$type.'2']; // fin '-+'
	} else
		$marqueur1 = $marqueur2 = '';
	// fin

	// Mode propre
	$propre = declarer_url_propre($type, $id);

	if ($propre === false) return ''; // objet inconnu. raccourci ? 

	if ($propre) {
		$url = _debut_urls_propres
			. $marqueur1
			. $propre
			. $marqueur2
			. _terminaison_urls_propres;

		// Repositionne l'URL par rapport a la racine du site (#GLOBALS)
		$url = str_repeat('../', _DIR_RACINE?$GLOBALS['profondeur_url']-1:$GLOBALS['profondeur_url']).$url;
	} else {

	// objet connu mais sans possibilite d'URL lisible, revenir au defaut

		if ($type == 'site')
			$id_type = 'id_syndic';
		else
			$id_type = 'id_'.$type;
		$url = get_spip_script('./')."?"._SPIP_PAGE."=$type&$id_type=$id";
	}

	// Ajouter les args
	if ($args)
		$url .= ((strpos($url, '?')===false) ? '?' : '&') . $args;

	// Ajouter l'ancre
	if ($ancre)
		$url .= "#$ancre";

	return $url;
}

// retrouve le fond et les parametres d'une URL propre
// ou produit une URL propre si on donne un parametre
// http://doc.spip.org/@urls_propres_dist
function urls_propres_dist($i, &$entite, $args='', $ancre='') {
	global $contexte;

	if (is_numeric($i))
		return _generer_url_propre($entite, $i, $args, $ancre);

	$url = $i;
	$id_objet = $type = 0;

	// Migration depuis anciennes URLs ?
	if (
		// traiter les injections du type domaine.org/spip.php/cestnimportequoi/ou/encore/plus/rubrique23
	  $GLOBALS['profondeur_url']<=0
	  AND $_SERVER['REQUEST_METHOD'] != 'POST') {
		if (preg_match(
		',(^|/)(article|breve|rubrique|mot|auteur|site)(\.php3?|[0-9]+(\.html)?)'
		.'([?&].*)?$,', $url, $regs)
		) {
			$type = $regs[2];
			$id_table_objet = id_table_objet($type);
			$id_objet = intval(_request($id_table_objet));
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
		$url_propre = generer_url_entite($id_objet, $type, $args, $ancre);
		if (strlen($url_propre)
		AND !strstr($url,$url_propre)) {
			include_spip('inc/headers');
			http_status(301);
			// recuperer les arguments supplementaires (&debut_xxx=...)
			$reste = preg_replace('/^&/','?',
				preg_replace("/[?&]$id_table_objet=$id_objet/",'',$regs[5]));
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
		// ne prendre que le segment d'url qui correspond, en fonction de la profondeur calculee
		$url = ltrim($url,'/');
		$url = explode('/',$url);
		while (count($url)>$GLOBALS['profondeur_url']+1)
			array_shift($url);
		$url = implode('/',$url);
		$url_propre = preg_replace(',[?].*,', '', $url);
	}

	// Mode Query-String ?
	if (!$url_propre
	AND preg_match(',[?]([^=/?&]+)(&.*)?$,', $GLOBALS['REQUEST_URI'], $r)) {
		$url_propre = $r[1];
	}

	if (!$url_propre) return; // qu'est-ce qu'il veut ???
	
	// gerer le cas de retour depuis des urls arbos
	// mais si url arbo ne trouve pas, on veut une 404 par securite
	if ($GLOBALS['profondeur_url']>0){
		$urls_anciennes = charger_fonction('arbo','urls');
		$urls_anciennes($url_propre,$entite);
		return;
	}
	
	include_spip('base/abstract_sql'); // chercher dans la table des URLS

	// Compatilibite avec propres2
	$url_propre = preg_replace(',\.html$,i', '', $url_propre);
	
	// Compatibilite avec les anciens marqueurs d'URL propres
	// Tester l'entree telle quelle (avec 'url_libre' des sites ont pu avoir des entrees avec marqueurs dans la table spip_urls)
	if (!$row = sql_fetsel('id_objet, type, date', 'spip_urls', 'url='.sql_quote($url_propre))) {
		// Sinon enlever les marqueurs eventuels
		$url_propre = retirer_marqueurs_url_propre($url_propre);

		$row = sql_fetsel('id_objet, type, date', 'spip_urls', 'url='.sql_quote($url_propre));
	}

	if ($row) {
		$type = $row['type'];

		// Redirection 301 si l'url est vieux
		if ($recent = sql_fetsel('url, date', 'spip_urls',
		'type='.sql_quote($row['type']).' AND id_objet='.sql_quote($row['id_objet'])
		.' AND date>'.sql_quote($row['date']), '', 'date DESC', 1)) {
			// Mode compatibilite pour conserver la distinction -Rubrique-
			if (_MARQUEUR_URL) {
				$marqueur = unserialize(_MARQUEUR_URL);
				$marqueur1 = $marqueur[$type.'1']; // debut '+-'
				$marqueur2 = $marqueur[$type.'2']; // fin '-+'
			} else
				$marqueur1 = $marqueur2 = '';
			$recent = $marqueur1 . $recent['url'] . $marqueur2;
			spip_log('Redirige '.$url_propre.' vers '.$recent);
			include_spip('inc/headers');
			redirige_par_entete($recent);
		}

		$col_id = id_table_objet($type);
		$contexte[$col_id] = $row['id_objet'];
		$entite = $row['type'];
	}

	if ($entite=='type_urls') {
		if ($type)
			$entite =  ($type == 'syndic') ?  'site' : $type;
		else {
			$entite = '404';
			$contexte['erreur'] = ''; // qu'afficher ici ?  l'url n'existe pas... on ne sait plus dire de quel type d'objet il s'agit
		}
	}
}
?>
