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

if (!defined("_ECRIRE_INC_VERSION")) return;

@define('_CONTEXTE_IGNORE_VARIABLES',"/(^var_|^PHPSESSID$)/");
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
		preg_replace(',\.[a-zA-Z0-9]*$,', '', 
		preg_replace('/[?].*$/', '', $GLOBALS['REQUEST_URI']));

	// Cette fonction est utilisee deux fois
	$cacher = charger_fonction('cacher', 'public');
	// Les quatre derniers parametres sont modifies par la fonction:
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
		// (fourni par $renommer ci-dessous lors de la mise en cache)
			$contexte = $page['contexte'];

			// vider les globales url propres qui ne doivent plus etre utilisees en cas
			// d'inversion url => objet
			unset($_SERVER['REDIRECT_url_propre']);
			unset($_ENV['url_propre']);
		}
		// ATTENTION, gestion des URLs transformee par le htaccess
		// $renommer = 'urls_propres_dist';
		// renvoie array($contexte, $type, $url_redirect, $nfond)
		// $nfond n'est retourne que si l'url est definie apres le ?
		// et risque d'etre effacee par un form en get
		// elle est utilisee par form_hidden exclusivement
		// Compat ascendante si le retour est null:
		// 1. $contexte est global car cette fonction le modifie.
		// 2. $fond est passe par reference, pour la meme raison
		// et calculer la page
		else {
			$renommer = generer_url_entite();
			if ($renommer) {
				$url = nettoyer_uri();
				$a = $renommer($url, $fond, $contexte);
				if (is_array($a)) {
					list($ncontexte, $type, $url_redirect, $nfond) = $a;
					if (strlen($url_redirect)
					AND $url !== $url_redirect) {
						spip_log("Redirige $url vers $url_redirect");
						include_spip('inc/headers');
						http_status(301);
						redirige_par_entete($url_redirect);
					}
					if (isset($nfond))
						$fond = $nfond;
					else if ($fond == ''
					OR $fond == 'type_urls' /* compat avec htaccess 2.0.0 */
					)
						$fond = ($type === 'syndic') ? 'site' : $type;
					if (isset($ncontexte))
						$contexte = $ncontexte;
				}
			}
			// compatibilite <= 1.9.2
			elseif (function_exists('recuperer_parametres_url'))
				recuperer_parametres_url($fond, nettoyer_uri());

			// vider les globales url propres qui ne doivent plus etre utilisees en cas
			// d'inversion url => objet
			unset($_SERVER['REDIRECT_url_propre']);
			unset($_ENV['url_propre']);

			// squelette par defaut
			if (!strlen($fond))
				$fond = 'sommaire';

			// preparer le contexte
			$parametrer = charger_fonction('parametrer', 'public');
			$page = $parametrer($fond, $GLOBALS['contexte'], $chemin_cache, $connect);

			// Stocker le cache sur le disque
			if ($page AND $chemin_cache)
				$cacher(NULL, $use_cache, $chemin_cache, $page, $lastmodified);
		}

		if ($page AND $chemin_cache) $page['cache'] = $chemin_cache;

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
				  $code = ($page !== false) ?
				    '404 Not Found' : '503 Service Unavailable';
				  $page = message_erreur_404('', $code);
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
		if (!preg_match(_CONTEXTE_IGNORE_VARIABLES,$var))
			$contexte[$var] = $val;
	}
	foreach($_POST as $var => $val) {
		if (!preg_match(_CONTEXTE_IGNORE_VARIABLES,$var))
			$contexte[$var] = $val;
	}

	return $contexte;
}

//
// fonction pour compatibilite arriere, probablement superflue
//

// http://doc.spip.org/@auto_content_type
function auto_content_type($page)
{
	global $flag_preserver;
	if (!isset($flag_preserver))
	  {
	    $flag_preserver = ($page && preg_match("/header\s*\(\s*.content\-type:/isx",$page['texte']) || (isset($page['entetes']['Content-Type'])));
	  }
}

// http://doc.spip.org/@inclure_page
function inclure_page($fond, $contexte, $connect='') {

	global $lastmodified;

	// enlever le fond de contexte inclus car sinon il prend la main
	// dans les sous inclusions -> boucle infinie d'inclusion identique
	// (cette precaution n'est probablement plus utile)
	unset($contexte['fond']);
	$page = $fond; 
	$cacher = charger_fonction('cacher', 'public');
	// Les quatre derniers parametres sont modifies par la fonction:
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
		if ($page AND $chemin_cache
		AND $page['entetes']['X-Spip-Cache'] > 0)
			$cacher($contexte, $use_cache, $chemin_cache, $page,
				$lastmodified);
	}

	return $page;
}

// Fonction inseree par le compilateur dans le code compile.
// Elle recoit un contexte pour inclure un squelette, 
// et les valeurs du contexte de compil prepare par memoriser_contexte_compil
// elle-meme appelee par calculer_balise_dynamique dans references.php:
// 0: sourcefile
// 1: codefile
// 2: id_boucle
// 3: ligne
// 4: langue

function inserer_balise_dynamique($contexte_exec, $contexte_compil)
{
	if (!is_array($contexte_exec))
		echo $contexte_exec; // message d'erreur etc
	else {
		if ($contexte_compil[4] AND empty($contexte_exec[2]['lang']))
			$contexte_exec[2]['lang'] = $contexte_compil[4];
		inclure_balise_dynamique($contexte_exec, true, $contexte_compil);
	}
}

// Attention, un appel explicite a cette fonction suppose certains include
// $echo = faut-il faire echo ou return

// http://doc.spip.org/@inclure_balise_dynamique
function inclure_balise_dynamique($texte, $echo=true, $contexte_compil=array())
{
	if (is_array($texte)) {

		list($fond, $delainc, $contexte_inclus) = $texte;

		// delais a l'ancienne, c'est pratiquement mort
		$d = isset($GLOBALS['delais']) ? $GLOBALS['delais'] : NULL;
		$GLOBALS['delais'] = $delainc;

		$page = recuperer_fond($fond,$contexte_inclus,array('trim'=>false, 'raw' => true, 'compil' => $contexte_compil));

		$texte = $page['texte'];

		$GLOBALS['delais'] = $d;
		// Faire remonter les entetes
		if (is_array($page['entetes'])) {
			// mais pas toutes
			unset($page['entetes']['X-Spip-Cache']);
			unset($page['entetes']['Content-Type']);
			if (isset($GLOBALS['page']) AND is_array($GLOBALS['page'])) {
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

	if ($GLOBALS['var_mode'] == 'debug') {
		// compatibilite : avant on donnait le numero de ligne ou rien.
		$ligne =  intval(isset($contexte_compil[3]) ? $contexte_compil[3] : $contexte_compil);
		$GLOBALS['debug_objets']['resultat'][$ligne] = $texte;
	}
	if ($echo)
		echo $texte;
	else
		return $texte;

}

// Traiter var_recherche ou le referrer pour surligner les mots
// http://doc.spip.org/@f_surligne
function f_surligne ($texte) {
	if (!$GLOBALS['html']) return $texte;
	$rech = _request('var_recherche');
	if (!$rech AND !isset($_SERVER['HTTP_REFERER'])) return $texte;
	include_spip('inc/surligne');
	return surligner_mots($texte, $rech);
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
function message_erreur_404 ($erreur= "", $code='404 Not Found') {
	static $deja = false;
	if ($deja) return "erreur";
	$deja = true;
	$contexte_inclus = array(
		'erreur' => _T($erreur),
		'code' => $code,
		'lang' => $GLOBALS['spip_lang']
	);
	$page = inclure_page('404', $contexte_inclus);
	$page['status'] = intval($code);
	return $page;
}

// temporairement ici : a mettre dans le futur inc/modeles
// creer_contexte_de_modele('left', 'autostart=true', ...) renvoie un array()
// http://doc.spip.org/@creer_contexte_de_modele
function creer_contexte_de_modele($args) {
	$contexte = array();
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
			if (!trouve_modele($fond = ($type.'_'.$soustype))) {
				$fond = '';
				$class = $soustype;
			}
			// enlever le sous type des params
			$params = array_diff($params,array($soustype));
		}
	}

	// Si ca marche pas en precisant le sous-type, prendre le type
	if (!$fond AND !trouve_modele($fond = $type))
		return false;
	$fond = 'modeles/'.$fond;
	// Creer le contexte
	$contexte = array( 
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


	// Appliquer le modele avec le contexte
	$retour = recuperer_fond($fond, $contexte);


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

	$page = inclure_page($fond, $contexte, $connect);

	if (!$page) return $page;

	if ($page['process_ins'] != 'html') {
		ob_start();
		xml_hack($page, true);
		eval('?' . '>' . $page['texte']);
		$page['texte'] = ob_get_contents();
		xml_hack($page);
		$page['process_ins'] = 'html';
		ob_end_clean();
	}
	page_base_href($page['texte']);

	// Lever un drapeau (global) si le fond utilise #SESSION
	// a destination de public/parametrer
	// pour remonter vers les inclusions appelantes
	// il faut bien lever ce drapeau apres avoir evalue le fond
	// pour ne pas faire descendre le flag vers les inclusions appelees
	if (isset($page['invalideurs'])
	AND isset($page['invalideurs']['session']))
		$GLOBALS['cache_utilise_session'] = $page['invalideurs']['session'];

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
	AND isset($GLOBALS['html']) AND $GLOBALS['html']
	AND $GLOBALS['profondeur_url']>0
	AND ($poshead = strpos($texte,'</head>'))!==FALSE){
		$head = substr($texte,0,$poshead);
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
			$texte = $head . substr($texte,$poshead);
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
