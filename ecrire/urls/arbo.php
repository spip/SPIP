<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return; // securiser

define('URLS_ARBO_EXEMPLE', '/article/titre');

// TODO: une interface permettant de verifier qu'on veut effectivment modifier
// une adresse existante
define('CONFIRMER_MODIFIER_URL', false);

/**
 * - Comment utiliser ce jeu d'URLs ?
 * Recopiez le fichier "htaccess.txt" du repertoire de base du site SPIP sous
 * le sous le nom ".htaccess" (attention a ne pas ecraser d'autres reglages
 * que vous pourriez avoir mis dans ce fichier) ; si votre site est en
 * "sous-repertoire", vous devrez aussi editer la ligne "RewriteBase" ce fichier.
 * Les URLs definies seront alors redirigees vers les fichiers de SPIP.
 * 
 * Choisissez "arbo" dans les pages de configuration d'URL
 *
 * SPIP calculera alors ses liens sous la forme "Mon-titre-d-article".
 * Variantes :
 * 
 * Terminaison :
 * les terminaisons ne *sont pas* stockees en base, elles servent juste
 * a rendre les url jolies ou conformes a un usage
 * pour avoir des url terminant par html
 * define ('_terminaison_urls_arbo', '.html');
 * 
 * pour preciser des terminaisons particulieres pour certains types
 * $GLOBALS['url_arbo_terminaisons']=array(
 * 'rubrique' => '/',
 * 'mot' => '',
 * 'groupe' => '/',
 * 'defaut' => '.html');
 * 
 * pour avoir des url numeriques (id) du type 12/5/4/article/23
 * define ('_URLS_ARBO_MIN',255);
 * 
 * 
 * pour conserver la casse des titres dans les url
 * define ('_url_arbo_minuscules',0);
 * 
 * pour choisir le caractere de separation titre-id en cas de doublon 
 * (ne pas utiliser '/')
 * define ('_url_arbo_sep_id','-');
 * 
 * pour modifier la hierarchie apparente dans la constitution des urls
 * ex pour que les mots soient classes par groupes
 * $GLOBALS['url_arbo_parents']=array(
 *			  'article'=>array('id_rubrique','rubrique'),
 *			  'rubrique'=>array('id_parent','rubrique'),
 *			  'breve'=>array('id_rubrique','rubrique'),
 *			  'site'=>array('id_rubrique','rubrique'),
 * 				'mot'=>array('id_groupe','groupes_mot'));
 * 
 * pour personaliser les types
 * $GLOBALS['url_arbo_types']=array(
 * 'rubrique'=>'', // pas de type pour les rubriques
 * 'article'=>'a',
 * 'mot'=>'tags'
 * );
 * 
 */


define ('_debut_urls_arbo', '');
define ('_terminaison_urls_arbo', '');
define ('_url_arbo_sep_id','-');
define ('_url_arbo_minuscules',1);

// Ces chaines servaient de marqueurs a l'epoque ou les URL propres devaient
// indiquer la table ou les chercher (articles, auteurs etc),
// et elles etaient retirees par les preg_match dans la fonction ci-dessous.
// Elles sont a present definies a "" pour avoir des URL plus jolies
// mais les preg_match restent necessaires pour gerer les anciens signets.

#define('_MARQUEUR_URL', serialize(array('rubrique1' => '-', 'rubrique2' => '-', 'breve1' => '+', 'breve2' => '+', 'site1' => '@', 'site2' => '@', 'auteur1' => '_', 'auteur2' => '_', 'mot1' => '+-', 'mot2' => '-+')));
define('_MARQUEUR_URL', false);

function url_arbo_parent($type){
	static $parents = null;
	if (is_null($parents)){
		$parents = array(
			  'article'=>array('id_rubrique','rubrique'),
			  'rubrique'=>array('id_parent','rubrique'),
			  'breve'=>array('id_rubrique','rubrique'),
			  'site'=>array('id_rubrique','rubrique'));
		if (isset($GLOBALS['url_arbo_parents']) AND !isset($_REQUEST['url_arbo_parents'])){
			$parents = array_merge($parents,$GLOBALS['url_arbo_parents']);
		}			  
	}
	return (isset($parents[$type])?$parents[$type]:'');
}

function url_arbo_terminaison($type){
	static $terminaison_types = null;
	if ($terminaison_types==null){
		$terminaison_types = array('rubrique' => '/','mot' => '','defaut' => defined('_terminaison_urls_arbo')?_terminaison_urls_arbo:'.html');
		if (isset($GLOBALS['url_arbo_terminaisons']))
			$terminaison_types = array_merge($terminaison_types,$GLOBALS['url_arbo_terminaisons']);
	}
	// si c'est un appel avec type='' c'est pour avoir la liste des terminaisons
	if (!$type)
		return array_unique(array_values($terminaison_types));
	if (isset($terminaison_types[$type]))
		return $terminaison_types[$type];
	elseif (isset($terminaison_types['defaut']))
		return $terminaison_types['defaut'];
	return "";
}

function url_arbo_type($type){
	// par defaut les rubriques ne sont pas typees, mais le reste oui
	static $synonymes_types = null;
	if (!$synonymes_types){
		$synonymes_types = array('rubrique'=>'');
		if (isset($GLOBALS['url_arbo_types']) AND is_array($GLOBALS['url_arbo_types']))
			$synonymes_types = array_merge($synonymes_types,$GLOBALS['url_arbo_types']);
	}
	// si c'est un appel avec type='' c'est pour avoir la liste inversee des synonymes
	if (!$type)
		return array_flip($synonymes_types);
	return 
	    ($t=(isset($synonymes_types[$type])?$synonymes_types[$type]:$type))  // le type ou son synonyme
	  . ($t?'/':''); // le / eventuel pour separer, si le synonyme n'est pas vide
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
	@define('_URLS_ARBO_MAX', 35);
	@define('_URLS_ARBO_MIN', 3);
	$titre = supprimer_tags(supprimer_numero(extraire_multi($objet['titre'])));
	$url = translitteration(corriger_caracteres($titre));
	if (_url_arbo_minuscules)
		$url = strtolower($url);
	$url = @preg_replace(',([^[:cntrl:][:alnum:]_]|[[:space:]])+,u', ' ', $url);
	// S'il reste trop de caracteres non latins, les gerer comme wikipedia
	// avec rawurlencode :
	if (preg_match_all(",[^a-zA-Z0-9 _]+,", $url, $r, PREG_SET_ORDER)) {
		foreach ($r as $regs) {
			$url = substr_replace($url, rawurlencode($regs[0]),
				strpos($url, $regs[0]), strlen($regs[0]));
		}
	}

	// S'il reste trop peu, on retombe sur article/12
	if (strlen($url) < _URLS_ARBO_MIN) {
		$url = $objet['id_objet'];
	}

	// Sinon couper les mots et les relier par des tirets
	else {
		$mots = preg_split(",[^a-zA-Z0-9_%]+,", $url);
		$url = '';
		foreach ($mots as $mot) {
			if (!$mot) continue;
			$url2 = $url.'-'.$mot;

			// Si on depasse _URLS_ARBO_MAX caracteres, s'arreter
			// ne pas compter 3 caracteres pour %E9 mais un seul
			$long = preg_replace(',%.,', '', $url2);
			if (strlen($long) > _URLS_ARBO_MAX) {
				break;
			}

			$url = $url2;
		}
		$url = substr($url, 1);

		// On enregistre en utf-8 dans la base
		$url = rawurldecode($url);

		if (strlen($url) < _URLS_ARBO_MIN)
			$url = $objet['id_objet']; // '12'
	}
	
	$x['data'] = 
		url_arbo_type($objet['type']) // le type ou son synonyme
	  . $url; // le titre

	return $x;
}
}

// http://doc.spip.org/@declarer_url_arbo_rec
function declarer_url_arbo_rec($url,$type,$parent,$type_parent){
	if (is_null($parent)){
		return $url;
	}
	if($parent==0)
		return rtrim($url,'/');
	else {
		$url_parent = declarer_url_arbo($type_parent?$type_parent:'rubrique',$parent);
		return rtrim($url_parent,'/') . '/' . rtrim($url,'/');
	}
}

// http://doc.spip.org/@declarer_url_arbo
function declarer_url_arbo($type, $id_objet) {
	static $urls=array();
	
	// Se contenter de cette URL si elle existe ;
	// sauf si on invoque par "voir en ligne" avec droit de modifier l'url

	// l'autorisation est verifiee apres avoir calcule la nouvelle url propre
	// car si elle ne change pas, cela ne sert a rien de verifier les autorisations
	// qui requetent en base
	$modifier_url = $GLOBALS['var_urls'];
	
	if (!isset($urls[$type][$id_objet]) OR $modifier_url) {
		$trouver_table = charger_fonction('trouver_table', 'base');
		$desc = $trouver_table(table_objet($type));
		$table = $desc['table'];
		$col_id =  @$desc['key']["PRIMARY KEY"];
		if (!$col_id) return false; // Quand $type ne reference pas une table
		$id_objet = intval($id_objet);
	
		// Auteurs : on prend le nom
		if ($type == 'auteur')
			$champ_titre = 'nom AS titre';
		else if ($type == 'site' OR $type=='syndic')
			$champ_titre = 'nom_site AS titre';
		else
			$champ_titre = 'titre';
			
		// parent
		$champ_parent = url_arbo_parent($type);
		$sel_parent = ($champ_parent)?", O.".reset($champ_parent).' as parent':'';
	
		//  Recuperer une URL propre correspondant a l'objet.
		$row = sql_fetsel("U.url, U.date, O.$champ_titre $sel_parent", "$table AS O LEFT JOIN spip_urls AS U ON (U.type='$type' AND U.id_objet=O.$col_id)", "O.$col_id=$id_objet", '', 'U.date DESC', 1);
		if ($row){
			$urls[$type][$id_objet] = $row;
			$urls[$type][$id_objet]['type_parent'] = $champ_parent?end($champ_parent):'';
		}
	}

	if (!isset($urls[$type][$id_objet])) return ""; # objet inexistant

	$url_propre = $urls[$type][$id_objet]['url'];

	if (!is_null($url_propre) AND !$modifier_url)
		return declarer_url_arbo_rec($url_propre,$type,$urls[$type][$id_objet]['parent'],$urls[$type][$id_objet]['type_parent']);

	// Sinon, creer une URL
	$url = pipeline('creer_chaine_url',
		array(
			'data' => $url_propre,  // le vieux url_propre
			'objet' => array_merge($urls[$type][$id_objet],
				array('type' => $type, 'id_objet' => $id_objet)
			)
		)
	);

	// Eviter de tamponner les URLs a l'ancienne (cas d'un article
	// intitule "auteur2")
	if (preg_match(',^(article|breve|rubrique|mot|auteur|site)[0-9]*$,', $url, $r)
	AND $r[1] != $type)
		$url = $url._url_arbo_sep_id.$id_objet;

	// Pas de changement d'url
	if ($url == $url_propre)
		return declarer_url_arbo_rec($url_propre,$type,$urls[$type][$id_objet]['parent'],$urls[$type][$id_objet]['type_parent']);
	
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
				return declarer_url_arbo_rec($urls[$type][$id_objet]['url']=$set['url'],$type,$urls[$type][$id_objet]['parent'],$urls[$type][$id_objet]['type_parent']);
			}
			else {
				$set['url'] .= _url_arbo_sep_id.$id_objet;
				if (strlen($set['url']) > 200)
					//serveur out ? retourner au mieux
					return  declarer_url_arbo_rec($urls[$type][$id_objet]['url']=$url_propre,$type,$urls[$type][$id_objet]['parent'],$urls[$type][$id_objet]['type_parent']);
				elseif (sql_countsel('spip_urls AS U', $where . sql_quote($set['url']))) {
					sql_update('spip_urls', array('date' => 'NOW()'), 'url='.sql_quote($set['url']));
					return declarer_url_arbo_rec($urls[$type][$id_objet]['url']=$set['url'],$type,$urls[$type][$id_objet]['parent'],$urls[$type][$id_objet]['type_parent']);
				}
			}
		} while (@sql_insertq('spip_urls', $set) <= 0);
	}

	sql_update('spip_urls', array('date' => 'NOW()'), 'url='.sql_quote($set['url']));
	spip_log("Creation de l'url propre '" . $set['url'] . "' pour $col_id=$id_objet");
	
	$urls[$type][$id_objet]['url'] = $set['url'];
	return declarer_url_arbo_rec($urls[$type][$id_objet]['url'],$type,$urls[$type][$id_objet]['parent'],$urls[$type][$id_objet]['type_parent']);
}

// http://doc.spip.org/@_generer_url_arbo
function _generer_url_arbo($type, $id, $args='', $ancre='') {

	if ($type == 'forum') {
		include_spip('inc/forum');
		return generer_url_forum_dist($id, $args, $ancre);
	}

	if ($type == 'document') {
		include_spip('inc/documents');
		return generer_url_document_dist($id, $args, $ancre);
	}

	// Mode propre
	$propre = declarer_url_arbo($type, $id);

	if ($propre === false) return ''; // objet inconnu. raccourci ? 

	if ($propre) {
		$url = _debut_urls_arbo
			. rtrim($propre,'/')
			. url_arbo_terminaison($type);
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


// http://doc.spip.org/@urls_arbo_dist
function urls_arbo_dist($i, &$entite, $args='', $ancre='') {
	global $contexte;

	if (is_numeric($i))
		return _generer_url_arbo($entite, $i, $args, $ancre);

	$url = $i;
	$id_objet = $type = 0;

	// Migration depuis anciennes URLs ?
	if ($_SERVER['REQUEST_METHOD'] != 'POST') {
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
		$url = substr($url, strrpos($url, '/') + 1);
		$url_propre = preg_replace(',[?].*,', '', $url);
	}

	// Mode Query-String ?
	if (!$url_propre
	AND preg_match(',[?]([^=/?&]+)(&.*)?$,', $GLOBALS['REQUEST_URI'], $r)) {
		$url_propre = $r[1];
	}

	if (!$url_propre) return; // qu'est-ce qu'il veut ???
	
	include_spip('base/abstract_sql'); // chercher dans la table des URLS

	// Compatilibite avec .htm/.html et autres terminaisons
	$t = array_diff(array_unique(array_merge(array('.html','.htm','/'),url_arbo_terminaison(''))),array(''));
	if (count($t))
		$url_propre = preg_replace('{('
		  .implode('|',array_map('preg_quote',$t)).')$}i', '', $url_propre);

	$synonymes_types = url_arbo_type('');
	$types_parents = array();
	
	// recuperer tous les objets de larbo xxx/article/yyy/mot/zzzz
	$url_arbo = explode('/',$url_propre);
	while (count($url_arbo)>0){
		$url_propre = array_pop($url_arbo);
		if (count($url_arbo))
			$type = array_pop($url_arbo);
		else
			$type=null;
		$typesyn = isset($synonymes_types[$type])?$synonymes_types[$type]:$type;
		// Compatibilite avec les anciens marqueurs d'URL propres
		// Tester l'entree telle quelle (avec 'url_libre' des sites ont pu avoir des entrees avec marqueurs dans la table spip_urls)
		if (is_null($type)
		  OR !$row=sql_fetsel('id_objet, type, date', 'spip_urls',array('url='.sql_quote("$typesyn/$url_propre")))) {
		  if (!is_null($type))
				array_push($url_arbo,$type);
			$row = sql_fetsel('id_objet, type, date', 'spip_urls',array('url='.sql_quote($url_propre)));
		}
		if ($row) {
			$type = $row['type'];
	
			// Redirection 301 si l'url est vieux
			/*if ($recent = sql_fetsel('url, date', 'spip_urls',
			'type='.sql_quote($row['type']).' AND id_objet='.sql_quote($row['id_objet'])
			.' AND date>'.sql_quote($row['date']), '', 'date DESC', 1)) {
				spip_log('Redirige '.$url_propre.' vers '.$recent['url']);
				include_spip('inc/headers');
				redirige_par_entete($recent['url']);
			}*/
	
			$col_id = id_table_objet($type);
			if (!isset($contexte[$col_id])) // n'affecter que la premiere fois un parent de type id_rubrique
				$contexte[$col_id] = $row['id_objet'];
			if (!$entite 
				OR !in_array($type,$types_parents))
				$entite = $type;

			if ($p = url_arbo_parent($type))
				$types_parents[]=end($p);
		}
	}

	if ($entite=='type_urls') {
		if ($type)
			$entite =  ($type == 'syndic') ?  'site' : $type;
		else {
			$entite = '404';
			$contexte['erreur'] = ''; // qu'afficher ici ?  l'url n'existe pas... on ne sait plus dire de quel type d'objet il s'agit
		}
	}
	define('_SET_HTML_BASE',1);
}

?>
