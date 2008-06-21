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
if (!function_exists('generer_url_article')) { // si la place n'est pas prise


// TODO: une interface permettant de verifier qu'on veut effectivment modifier
// une adresse existante
define('CONFIRMER_MODIFIER_URL', false);

include_spip('base/abstract_sql');

/**
 * - Comment utiliser ce jeu d'URLs ?
 * Recopiez le fichier "htaccess.txt" du repertoire de base du site SPIP sous
 * le sous le nom ".htaccess" (attention a ne pas ecraser d'autres reglages
 * que vous pourriez avoir mis dans ce fichier) ; si votre site est en
 * "sous-repertoire", vous devrez aussi editer la ligne "RewriteBase" ce fichier.
 * Les URLs definies seront alors redirigees vers les fichiers de SPIP.
 * 
 * Definissez ensuite dans ecrire/mes_options.php :
 * 	< ?php $type_urls = 'arbo'; ? >
 * SPIP calculera alors ses liens sous la forme "Mon-titre-d-article".
 * Variantes :
 * pour avoir des url terminant par html
 * define ('_terminaison_urls_arbo', '.html');
 * 
 * pour avoir des url numeriques (id) du type 12/5/4/article/23
 * define ('_URLS_ARBO_MIN',255);
 * 
 * pour avoir des url sans les types 
 * define ('_urls_arbo_sans_type',1);
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
 * 
 */


define ('_debut_urls_arbo', '');
define ('_terminaison_urls_arbo', '');
define ('_url_arbo_sep_id','-');
define ('_urls_arbo_sans_type',0);
define ('_url_arbo_minuscules',1);

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

	$x['data'] = ((_urls_arbo_sans_type OR $objet['type']=='rubrique')?'':$objet['type'].'/').$url;

	return $x;
}

// http://doc.spip.org/@_generer_url_arbo
function _generer_url_arbo($url,$type,$parent,$type_parent){
	if (is_null($parent)){
		return $url;
	}
	elseif($type!='rubrique') {
		$url_parent = _generer_url_propre($type_parent?$type_parent:'rubrique',$parent);
		return $url_parent . (substr($url_parent,-1)=='/'?'':'/') .$url;
	}
	else{
		if($parent==0)
			return $url.'/';
		else
			return _generer_url_propre($type_parent?$type_parent:'rubrique',$parent). $url.'/';
	}
}

// http://doc.spip.org/@_generer_url_propre
function _generer_url_propre($type, $id_objet) {
	static $urls=array();
	
	// Se contenter de cette URL si elle existe ;
	// sauf si on invoque action=redirect avec droit de modifier l'url
	$modifier_url = (
		_request('action') == 'redirect'
		AND autoriser('modifierurl', $type, $id_objet)
	);
	
	if (!isset($urls[$type][$id_objet]) OR $modifier_url) {
		$table = table_objet_sql($type);
		$col_id = id_table_objet($type);
		$id_objet = intval($id_objet);
	
		// Auteurs : on prend le nom
		if ($type == 'auteur')
			$champ_titre = 'nom AS titre';
		else if ($type == 'site' OR $type=='syndic')
			$champ_titre = 'nom_site AS titre';
		else
			$champ_titre = 'titre';
			
		// parent
		$champ_parent = tester_variable('url_arbo_parents',
			array(
			  'article'=>array('id_rubrique','rubrique'),
			  'rubrique'=>array('id_parent','rubrique'),
			  'breve'=>array('id_rubrique','rubrique'),
			  'site'=>array('id_rubrique','rubrique')));
		$sel_parent = isset($champ_parent[$type])?", O.".reset($champ_parent[$type]).' as parent':'';
	
		//  Recuperer une URL propre correspondant a l'objet.
		$row = sql_fetsel("U.url, U.date, O.$champ_titre $sel_parent", "$table AS O LEFT JOIN spip_urls AS U ON (U.type='$type' AND U.id_objet=O.$col_id)", "O.$col_id=$id_objet", '', 'U.date DESC', 1);
		if ($row){
			$urls[$type][$id_objet] = $row;
			$urls[$type][$id_objet]['type_parent'] = isset($champ_parent[$type])?end($champ_parent[$type]):'';
		}
	}

	if (!isset($urls[$type][$id_objet])) return ""; # objet inexistant

	$url_propre = $urls[$type][$id_objet]['url'];

	if (!is_null($url_propre) AND !$modifier_url)
		return _generer_url_arbo($url_propre,$type,$urls[$type][$id_objet]['parent'],$urls[$type][$id_objet]['type_parent']);

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
		return _generer_url_arbo($url_propre,$type,$urls[$type][$id_objet]['parent'],$urls[$type][$id_objet]['type_parent']);

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
				return _generer_url_arbo($urls[$type][$id_objet]['url']=$set['url'],$type,$urls[$type][$id_objet]['parent'],$urls[$type][$id_objet]['type_parent']);
			}
			else {
				$set['url'] .= _url_arbo_sep_id.$id_objet;
				if (strlen($set['url']) > 200)
					//serveur out ? retourner au mieux
					return  _generer_url_arbo($urls[$type][$id_objet]['url']=$url_propre,$type,$urls[$type][$id_objet]['parent'],$urls[$type][$id_objet]['type_parent']);
				elseif (sql_countsel('spip_urls AS U', $where . sql_quote($set['url']))) {
					sql_update('spip_urls', array('date' => 'NOW()'), 'url='.sql_quote($set['url']));
					return _generer_url_arbo($urls[$type][$id_objet]['url']=$set['url'],$type,$urls[$type][$id_objet]['parent'],$urls[$type][$id_objet]['type_parent']);
				}
			}
		} while (@sql_insertq('spip_urls', $set) <= 0);
	}

	sql_update('spip_urls', array('date' => 'NOW()'), 'url='.sql_quote($set['url']));
	spip_log("Creation de l'url propre '" . $set['url'] . "' pour $col_id=$id_objet");
	
	$urls[$type][$id_objet]['url'] = $set['url'];
	return _generer_url_arbo($urls[$type][$id_objet]['url'],$type,$urls[$type][$id_objet]['parent'],$urls[$type][$id_objet]['type_parent']);
}

// http://doc.spip.org/@_generer_url_complete
function _generer_url_complete($type, $id, $args='', $ancre='') {

	// Mode propre
	if ($propre = _generer_url_propre($type, $id)) {
		$url = _debut_urls_arbo
			. $propre
			. (substr($propre,-1)=='/'?'':_terminaison_urls_arbo);

	}

	// propre ne veut pas !
	else {
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
// http://doc.spip.org/@urls_arbo_dist
function urls_arbo_dist(&$fond, $url) {
	global $contexte;
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
		$func = "generer_url_$type";
		$url_propre = $func($id_objet);
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

	if (!$url_propre) return;

	// Compatilibite avec propres2
	$url_propre = preg_replace(',\.html$,i', '', $url_propre);
	
	// optimisation perfo
	if (preg_match(',([^/]*)/$,',$url_propre,$regs)){
		$url_propre = 'rubrique/'.$regs[1];
	}
	// recuperer tous les objets de larbo xxx/article/yyy/mot/zzzz
	$url_arbo = explode('/',$url_propre);
	while (count($url_arbo)>0){
		$url_propre = array_pop($url_arbo);
		if (count($url_arbo))
			$type = array_pop($url_arbo);
		else
			$type=null;
		// Compatibilite avec les anciens marqueurs d'URL propres
		// Tester l'entree telle quelle (avec 'url_libre' des sites ont pu avoir des entrees avec marqueurs dans la table spip_urls)
		if (is_null($type)
		  OR !$row=sql_fetsel('id_objet, type, date', 'spip_urls',array('url='.sql_quote("$type/$url_propre")))) {
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
			$contexte[$col_id] = $row['id_objet'];
			if ($type!='rubrique' OR !in_array($fond,array('article','breve','site')))
				$fond = $row['type'];
			if ($type=='rubrique')
				$url_arbo = array();
		}
	}

	if ($fond=='type_urls') {
		if ($type)
			$fond =  ($type == 'syndic') ?  'site' : $type;
		else {
			$fond = '404';
			$contexte['erreur'] = ''; // qu'afficher ici ?  l'url n'existe pas... on ne sait plus dire de quel type d'objet il s'agit
		}
	}
	define('_SET_HTML_BASE',1);
}
} // function_exists
?>
