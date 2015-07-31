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

if (!defined("_ECRIRE_INC_VERSION")) return;

//
// calcule la page et les entetes
// determine le contexte donne par l'URL (en tenant compte des reecritures) 
// grace a la fonction de passage d'URL a id (reciproque dans urls/*php)
//

// http://doc.spip.org/@assembler
function assembler($fond, $connect='') {

	global $flag_preserver,$lastmodified, $use_cache, $contexte;

	$contexte = calculer_contexte();

	$page = $fond .
		preg_replace('/[?].*$/', '', 
		preg_replace(',\.[a-zA-Z0-9]*$,', '', $GLOBALS['REQUEST_URI']));

	// Cette fonction est utilisee deux fois
	$cacher = charger_fonction('cacher', 'public');
	// Les quatre derniers parametres sont modifes par la fonction:
	// emplacement, validite, et, s'il est valide, contenu & age
	$res = $cacher($GLOBALS['contexte'], $use_cache, $chemin_cache, $page, $lastmodified);
	// Si un resultat est retourne, c'est un message d'impossibilite
	if ($res) {return array('texte' => $res);}

	if (!$chemin_cache || !$lastmodified) $lastmodified = time();

	$headers_only = ($_SERVER['REQUEST_METHOD'] == 'HEAD');

	// Pour les pages non-dynamiques (indiquees par #CACHE{duree,cache-client})
	// une perennite valide a meme reponse qu'une requete HEAD (par defaut les
	// pages sont dynamiques)
	if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
	AND !$GLOBALS['var_mode']
	AND $chemin_cache
	AND isset($page['entetes'])
	AND isset($page['entetes']['Cache-Control'])
	AND strstr($page['entetes']['Cache-Control'],'max-age=')
	AND !strstr($_SERVER['SERVER_SOFTWARE'],'IIS/')
	) {
		$since = preg_replace('/;.*/', '',
			$_SERVER['HTTP_IF_MODIFIED_SINCE']);
		$since = str_replace('GMT', '', $since);
		if (trim($since) == gmdate("D, d M Y H:i:s", $lastmodified)) {
			$page['status'] = 304;
			$headers_only = true;
		}
	}

	// Si requete HEAD ou Last-modified compatible, ignorer le texte
	// et pas de content-type (pour contrer le bouton admin de inc-public)
	if ($headers_only) {
		$page['entetes']["Connection"] = "close";
		$page['texte'] = "";
	} else {
		// si la page est prise dans le cache
		if (!$use_cache)  {
		// Informer les boutons d'admin du contexte
		// (fourni par assembler_contexte lors de la mise en cache)
			$contexte = $page['contexte'];
		}
		// ATTENTION, gestion des URLs transformee par le htaccess
		// 1. $contexte est global car cette fonction le modifie.
		// 2. $fond est passe par reference, pour la meme raison
		// Bref,  les URL dites propres ont une implementation sale.
		// Interdit de nettoyer, faut assumer l'histoire.
		// et calculer la page
		else {
			$renommer = generer_url_entite();
			if (!$renommer) {
				// compatibilite <= 1.9.2
				if (function_exists('recuperer_parametres_url'))
					$renommer = 'recuperer_parametres_url';
			}
			if ($renommer)	$renommer($fond, nettoyer_uri());
			$parametrer = charger_fonction('parametrer', 'public');
			$page = $parametrer($fond, $GLOBALS['contexte'], $chemin_cache, $connect);

			// Ajouter les scripts avant de mettre en cache
			$page['insert_js_fichier'] = pipeline("insert_js",array("type" => "fichier","data" => array()));
			$page['insert_js_inline'] = pipeline("insert_js",array("type" => "inline","data" => array()));
			
			// Stocker le cache sur le disque
			if ($chemin_cache)
				$cacher(NULL, $use_cache, $chemin_cache, $page, $lastmodified);
		}

		if ($chemin_cache) $page['cache'] = $chemin_cache;

		auto_content_type($page);

		$flag_preserver |=  headers_sent();

		// Definir les entetes si ce n'est fait 
		if (!$flag_preserver) {
			if ($GLOBALS['flag_ob']) {
				// Si la page est vide, produire l'erreur 404 ou message d'erreur pour les inclusions
				if (trim($page['texte']) === ''
				AND $GLOBALS['var_mode'] != 'debug'
				AND !isset($page['entetes']['Location']) // cette page realise une redirection, donc pas d'erreur
				) {
					$page = message_erreur_404();
				}
				// pas de cache client en mode 'observation'
				if ($GLOBALS['var_mode']) {
					$page['entetes']["Cache-Control"]= "no-cache,must-revalidate";
					$page['entetes']["Pragma"] = "no-cache";
				}
			}
		}
	}

	// Entete Last-Modified:
	// eviter d'etre incoherent en envoyant un lastmodified identique
	// a celui qu'on a refuse d'honorer plus haut (cf. #655)
	if ($lastmodified
	AND !isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
	AND !isset($page['entetes']["Last-Modified"]))
		$page['entetes']["Last-Modified"]=gmdate("D, d M Y H:i:s", $lastmodified)." GMT";

	return $page;
}

//
// Contexte : lors du calcul d'une page spip etablit le contexte a partir
// des variables $_GET et $_POST, purgees des fausses variables var_*
// Note : pour hacker le contexte depuis le fichier d'appel (page.php),
// il est recommande de modifier $_GET['toto'] (meme si la page est
// appelee avec la methode POST).
//
// http://doc.spip.org/@calculer_contexte
function calculer_contexte() {

	$contexte = array();
	foreach($_GET as $var => $val) {
		if (strpos($var, 'var_') !== 0 AND $var != 'PHPSESSID')
			$contexte[$var] = $val;
	}
	foreach($_POST as $var => $val) {
		if (strpos($var, 'var_') !== 0)
			$contexte[$var] = $val;
	}

	return $contexte;
}

//
// 2 fonctions pour compatibilite arriere. Sont probablement superflues
//

// http://doc.spip.org/@auto_content_type
function auto_content_type($page)
{
	global $flag_preserver;
	if (!isset($flag_preserver))
	  {
		$flag_preserver = preg_match("/header\s*\(\s*.content\-type:/isx",$page['texte']) || (isset($page['entetes']['Content-Type']));
	  }
}

// http://doc.spip.org/@inclure_page
function inclure_page($fond, $contexte, $connect='') {

	global $lastmodified;

	// enlever le fond de contexte inclus car sinon il prend la main
	// dans les sous inclusions -> boucle infinie d'inclusion identique
	unset($contexte['fond']);
	// mais le donner pour le calcul du cache
	$page = $fond; 
	$cacher = charger_fonction('cacher', 'public');
	// Les quatre derniers parametres sont modifes par la fonction:
	// emplacement, validite, et, s'il est valide, contenu & age
	$res = $cacher($contexte, $use_cache, $chemin_cache, $page, $lastinclude);
	if ($res) {return array('texte' => $res);}

	// Si use_cache vaut 0, la page a ete tiree du cache et se trouve dans $page
	if (!$use_cache) {
		$lastmodified = max($lastmodified, $lastinclude);
	} else {
		$parametrer = charger_fonction('parametrer', 'public');
		$page = $parametrer($fond, $contexte, $chemin_cache, $connect);
		$lastmodified = time();
		// et on l'enregistre sur le disque
		if ($chemin_cache
		AND $page['entetes']['X-Spip-Cache'] > 0)
			$cacher($contexte, $use_cache, $chemin_cache, $page,
				$lastmodified);
	}

	return $page;
}

# Attention, un appel explicite a cette fonction suppose certains include
# $echo = faut-il faire echo ou return

// http://doc.spip.org/@inclure_balise_dynamique
function inclure_balise_dynamique($texte, $echo=true, $ligne=0) {
	global $contexte_inclus; # provisoire : c'est pour le debuggueur

	if (is_array($texte)) {

		list($fond, $delainc, $contexte_inclus) = $texte;

		// delais a l'ancienne, c'est pratiquement mort
		$d = isset($GLOBALS['delais']) ? $GLOBALS['delais'] : NULL;
		$GLOBALS['delais'] = $delainc;

		// les balises dynamiques passent toujours leur $fond
		// si un 'fond' est present dans le contexte il vient d'autre part (de la bdd par exemple:p)
		// et c'est le crash assure
		$contexte_inclus['fond'] = $fond;
		$page = recuperer_fond($fond,$contexte_inclus,array('trim'=>false, 'raw' => true));

		$texte = $page['texte'];

		// attention $contexte_inclus a pu changer pendant l'eval ci dessus
		$GLOBALS['delais'] = $d;
		// Faire remonter les entetes
		if (is_array($page['entetes'])) {
			// mais pas toutes
			unset($page['entetes']['X-Spip-Cache']);
			unset($page['entetes']['Content-Type']);
			if (is_array($GLOBALS['page'])) {
				if (!is_array($GLOBALS['page']['entetes']))
					$GLOBALS['page']['entetes'] = array();
				$GLOBALS['page']['entetes'] = 
					array_merge($GLOBALS['page']['entetes'],$page['entetes']);
			}
		}
		// on se refere a $page['contexte'] a la place
		if (isset($page['contexte']['_pipeline'])) {
			$pipe = is_array($page['contexte']['_pipeline'])?reset($page['contexte']['_pipeline']):$page['contexte']['_pipeline'];
			$args = is_array($page['contexte']['_pipeline'])?end($page['contexte']['_pipeline']):array();
			$args['contexte'] = $page['contexte'];
			unset($args['contexte']['_pipeline']); // par precaution, meme si le risque de boucle infinie est a priori nul
			if (isset($GLOBALS['spip_pipeline'][$pipe]))
				$texte = pipeline($pipe,array(
				  'data'=>$texte,
				  'args'=>$args));
		}
	}

	if ($GLOBALS['var_mode'] == 'debug')
		$GLOBALS['debug_objets']['resultat'][$ligne] = $texte;

	if ($echo)
		echo $texte;
	else
		return $texte;

}

// Traiter var_recherche ou le referrer pour surligner les mots
// http://doc.spip.org/@f_surligne
function f_surligne ($texte) {
	if ($GLOBALS['html']
	AND (isset($_SERVER['HTTP_REFERER']) OR isset($_GET['var_recherche']))) {
		include_spip('inc/surligne');
		$texte = surligner_mots($texte);
	}
	return $texte;
}

// Valider/indenter a la demande.
// http://doc.spip.org/@f_tidy
function f_tidy ($texte) {
	global $xhtml;

	if ($xhtml # tidy demande
	AND $GLOBALS['html'] # verifie que la page avait l'entete text/html
	AND strlen($texte)
	AND !headers_sent()) {
		# Compatibilite ascendante
		if (!is_string($xhtml)) $xhtml ='tidy';

		if (!$f = charger_fonction($xhtml, 'inc', true)) {
			spip_log("tidy absent, l'indenteur SPIP le remplace");
			$f = charger_fonction('sax', 'xml');
		}
		return $f($texte);
	}

	return $texte;
}

// Offre #INSERT_HEAD sur tous les squelettes (bourrin)
// a activer dans mes_options via :
// $spip_pipeline['affichage_final'] .= '|f_insert_head';
// http://doc.spip.org/@f_insert_head
function f_insert_head($texte) {
	if (!$GLOBALS['html']) return $texte;
	include_spip('public/admin'); // pour strripos

	($pos = stripos($texte, '</head>'))
	    || ($pos = stripos($texte, '<body>'))
	    || ($pos = 0);

	if (false === strpos(substr($texte, 0,$pos), '<!-- insert_head -->')) {
		$insert = "\n".pipeline('insert_head','<!-- f_insert_head -->')."\n";
		$texte = substr_replace($texte, $insert, $pos, 0);
	}

	return $texte;
}

// Inserer au besoin les boutons admins
// http://doc.spip.org/@f_admin
function f_admin ($texte) {
	if ($GLOBALS['affiche_boutons_admin']) {
		include_spip('public/admin');
		$texte = affiche_boutons_admin($texte);
	}
	if (_request('var_mode')=='noajax'){
		$texte = preg_replace(',(class=[\'"][^\'"]*)ajax([^\'"]*[\'"]),Uims',"\\1\\2",$texte);
	}
	return $texte;
}

// Ajoute ce qu'il faut pour les clients MSIE et leurs debilites notoires
// * gestion du PNG transparent
// * images background (TODO)
// Cf. aussi inc/presentation, fonction fin_page();
// http://doc.spip.org/@f_msie
function f_msie ($texte) {
	if (!$GLOBALS['html']) return $texte;
	if ($GLOBALS['flag_preserver']) return $texte;
	
	// test si MSIE et sinon quitte
	if (
		strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'msie')
		AND preg_match('/MSIE /i', $_SERVER['HTTP_USER_AGENT'])
		AND $msiefix = charger_fonction('msiefix', 'inc')
	)
		return $msiefix($texte);
	else
		return $texte;
}


// http://doc.spip.org/@message_erreur_404
function message_erreur_404 ($erreur= "") {
	static $deja = false;
	if ($deja) return "erreur";
	$deja = true;
	if (!$erreur) {
		if (isset($GLOBALS['id_article']))
		$erreur = 'public:aucun_article';
		else if (isset($GLOBALS['id_rubrique']))
		$erreur = 'public:aucune_rubrique';
		else if (isset($GLOBALS['id_breve']))
		$erreur = 'public:aucune_breve';
		else if (isset($GLOBALS['id_auteur']))
		$erreur = 'public:aucun_auteur';
		else if (isset($GLOBALS['id_syndic']))
		$erreur = 'public:aucun_site';
	}
	$contexte_inclus = array(
		'erreur' => _T($erreur),
		'lang' => $GLOBALS['spip_lang']
	);
	$page = inclure_page('404', $contexte_inclus);
	$page['status'] = 404;
	return $page;
}


// temporairement ici : a mettre dans le futur inc/modeles
// creer_contexte_de_modele('left', 'autostart=true', ...) renvoie un array()
// http://doc.spip.org/@creer_contexte_de_modele
function creer_contexte_de_modele($args) {
	$contexte = array();
	$params = array();
	foreach ($args as $var=>$val) {
		if (is_int($var)){ // argument pas formate
			if (in_array($val, array('left', 'right', 'center'))) {
				$var = 'align';
				$contexte[$var] = $val;
			} else {
				$args = explode('=', $val);
				if (count($args)>=2) // Flashvars=arg1=machin&arg2=truc genere plus de deux args
					$contexte[trim($args[0])] = substr($val,strlen($args[0])+1);
				else // notation abregee
					$contexte[trim($val)] = trim($val);
			}
		}
		else
			$contexte[$var] = $val;
	}

	return $contexte;
}

// Calcule le modele et retourne la mini-page ainsi calculee
// http://doc.spip.org/@inclure_modele
function inclure_modele($type, $id, $params, $lien, $connect='') {

	static $compteur;
	if (++$compteur>10) return ''; # ne pas boucler indefiniment

	$type = strtolower($type);

	$fond = $class = '';

	$params = array_filter(explode('|', $params));
	if ($params) {
		list(,$soustype) = each($params);
		$soustype = strtolower($soustype);
		if (in_array($soustype,
		array('left', 'right', 'center', 'ajax'))) {
			list(,$soustype) = each($params);
			$soustype = strtolower($soustype);
		}

		if (preg_match(',^[a-z0-9_]+$,', $soustype)) {
			$fond =  $type.'_'.$soustype;
			if (!find_in_path('modeles/'. $fond.'.html')) {
				$fond = '';
				$class = $soustype;
			}
			// enlever le sous type des params
			$params = array_diff($params,array($soustype));
		}
	}

	// en cas d'echec : si l'objet demande a une url, on cree un petit encadre
	// avec un lien vers l'objet ; sinon on passe la main au suivant
	if (!$fond) {
		$fond = 'modeles/'.$type;
		if (!find_in_path($fond.'.html')) {
			if (!$lien)
				$lien = calculer_url("$type$id", '', 'tout', $connect);
			if (strpos($lien[1],'spip_url') !== false)
				return false;
			else
				return '<a href="'.$lien[0].'" class="spip_modele'
				. ($class ? " $class" : '')
				. '">'.sinon($lien[2], _T('ecrire:info_sans_titre'))."</a>";
		}
	}


	// Creer le contexte
	$contexte = array( 
		'lang' => $GLOBALS['spip_lang'], 
		'fond' => $fond, 
		'dir_racine' => _DIR_RACINE # eviter de mixer un cache racine et un cache ecrire (meme si pour l'instant les modeles ne sont pas caches, le resultat etant different il faut que le contexte en tienne compte 
	); 
	// Le numero du modele est mis dans l'environnement
	// d'une part sous l'identifiant "id"
	// et d'autre part sous l'identifiant de la cle primaire supposee
	// par la fonction table_objet, 
	// qui ne marche vraiment que pour les tables std de SPIP
	// (<site1> =>> site =>> id_syndic =>> id_syndic=1)
	$_id = 'id_' . table_objet($type);
	if (preg_match('/s$/',$_id)) $_id = substr($_id,0,-1);
	$contexte['id'] = $contexte[$_id] = $id;

	if (isset($class))
		$contexte['class'] = $class;

	// Si un lien a ete passe en parametre, ex: [<modele1>->url]
	if ($lien) {
		# un eventuel guillemet (") sera reechappe par #ENV
		$contexte['lien'] = str_replace("&quot;",'"', $lien[0]);
		$contexte['lien_class'] = $lien[1];
	}

	// Traiter les parametres
	// par exemple : <img1|center>, <emb12|autostart=true> ou <doc1|lang=en>
	$arg_list = creer_contexte_de_modele($params);
	$contexte['args'] = $arg_list; // on passe la liste des arguments du modeles dans une variable args
	$contexte = array_merge($contexte,$arg_list);

	// On cree un marqueur de notes unique lie a ce modele
	// et on enregistre l'etat courant des globales de notes...
	$enregistre_marqueur_notes = $GLOBALS['marqueur_notes'];
	$enregistre_les_notes = $GLOBALS['les_notes'];
	$enregistre_compt_note = $GLOBALS['compt_note'];
	$GLOBALS['marqueur_notes'] = substr(md5(serialize($contexte)),0,8);
	$GLOBALS['les_notes'] = '';
	$GLOBALS['compt_note'] = 0;

	// Appliquer le modele avec le contexte
	$retour = recuperer_fond($fond, $contexte);

	// On restitue les globales de notes telles qu'elles etaient avant l'appel
	// du modele. Si le modele n'a pas affiche ses notes, tant pis (elles *doivent*
	// etre dans le cache du modele, autrement elles ne seraient pas prises en
	// compte a chaque calcul d'un texte contenant un modele, mais seulement
	// quand le modele serait calcule, et on aurait des resultats incoherents)
	$GLOBALS['les_notes'] = $enregistre_les_notes;
	$GLOBALS['marqueur_notes'] = $enregistre_marqueur_notes;
	$GLOBALS['compt_note'] = $enregistre_compt_note;

	// Regarder si le modele tient compte des liens (il *doit* alors indiquer
	// spip_lien_ok dans les classes de son conteneur de premier niveau ;
	// sinon, s'il y a un lien, on l'ajoute classiquement
	if (strstr(' ' . ($classes = extraire_attribut($retour, 'class')).' ',
	'spip_lien_ok')) {
		$retour = inserer_attribut($retour, 'class',
			trim(str_replace(' spip_lien_ok ', ' ', " $classes ")));
	} else if ($lien)
		$retour = "<a href='".$lien[0]."' class='".$lien[1]."'>".$retour."</a>";

	$compteur--;

	return  (isset($arg_list['ajax'])AND $arg_list['ajax']=='ajax')
	? encoder_contexte_ajax($contexte,'',$retour)
	: $retour; 
}

// Un inclure_page qui marche aussi pour l'espace prive
// fonction interne a spip, ne pas appeler directement
// pour recuperer $page complet, utiliser:
// 	recuperer_fond($fond,$contexte,array('raw'=>true))
// http://doc.spip.org/@evaluer_fond
function evaluer_fond ($fond, $contexte=array(), $connect=null) {

	if (isset($contexte['fond'])
	AND $fond === '')
		$fond = $contexte['fond'];

	$page = inclure_page($fond, $contexte, $connect);

	// Lever un drapeau (global) si le fond utilise #SESSION
	// a destination de public/parametrer
	if (isset($page['invalideurs'])
	AND isset($page['invalideurs']['session']))
		$GLOBALS['cache_utilise_session'] = $page['invalideurs']['session'];
	if ($GLOBALS['flag_ob'] AND ($page['process_ins'] != 'html')) {
		ob_start();
		xml_hack($page, true);
		eval('?' . '>' . $page['texte']);
		$page['texte'] = ob_get_contents();
		xml_hack($page);
		$page['process_ins'] = 'html';
		ob_end_clean();
	}
	page_base_href($page['texte']);

	return $page;
}


// Appeler avant et apres chaque eval()
// http://doc.spip.org/@xml_hack
function xml_hack(&$page, $echap = false) {
	if ($echap)
		$page['texte'] = str_replace('<'.'?xml', "<\1?xml", $page['texte']);
	else
		$page['texte'] = str_replace("<\1?xml", '<'.'?xml', $page['texte']);
}

// http://doc.spip.org/@page_base_href
function page_base_href(&$texte){
	if (!defined('_SET_HTML_BASE'))
		// si la profondeur est superieure a 1
		// est que ce n'est pas une url page ni une url action
		// activer par defaut
		define('_SET_HTML_BASE',
			$GLOBALS['profondeur_url'] >= (_DIR_RESTREINT?1:2)
			AND _request(_SPIP_PAGE) !== 'login'
			AND !_request('action'));

	if (_SET_HTML_BASE
	AND $GLOBALS['html']
	AND $GLOBALS['profondeur_url']>0){
		list($head, $body) = explode('</head>', $texte, 1);
		$insert = false;
		if (strpos($head, '<base')===false) 
			$insert = true;
		else {
			// si aucun <base ...> n'a de href c'est bon quand meme !
			$insert = true;
			include_spip('inc/filtres');
			$bases = extraire_balises($head,'base');
			foreach ($bases as $base)
				if (extraire_attribut($base,'href'))
					$insert = false;
		}
		if ($insert) {
			include_spip('inc/filtres_mini');
			// ajouter un base qui reglera tous les liens relatifs
			$base = url_absolue('./');
			if (($pos = strpos($head, '<head>')) !== false)
				$head = substr_replace($head, "\n<base href=\"$base\" />", $pos+6, 0);
			$texte = $head . (isset($body) ? '</head>'.$body : '');
			// gerer les ancres
			$base = $_SERVER['REQUEST_URI'];
			if (strpos($texte,"href='#")!==false)
				$texte = str_replace("href='#","href='$base#",$texte);
			if (strpos($texte, "href=\"#")!==false)
				$texte = str_replace("href=\"#","href=\"$base#",$texte);
		}
	}
}


// Envoyer les entetes, en retenant ceux qui sont a usage interne
// et demarrent par X-Spip-...
// http://doc.spip.org/@envoyer_entetes
function envoyer_entetes($entetes) {
	foreach ($entetes as $k => $v)
	#	if (strncmp($k, 'X-Spip-', 7))
			@header("$k: $v");
}

?>
