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

// http://doc.spip.org/@init_var_mode
function init_var_mode(){
	static $done = false;
	if (!$done) {
		// On fixe $GLOBALS['var_mode']
		$GLOBALS['var_mode'] = false;
		$GLOBALS['var_preview'] = false;
		$GLOBALS['var_images'] = false;
		if (isset($_GET['var_mode'])) {
			// tout le monde peut calcul/recalcul
			if ($_GET['var_mode'] == 'calcul'
			OR $_GET['var_mode'] == 'recalcul')
				$GLOBALS['var_mode'] = $_GET['var_mode'];
		
			// preview et debug necessitent une autorisation
			else if ($_GET['var_mode'] == 'preview'
			OR $_GET['var_mode'] == 'debug') {
				include_spip('inc/autoriser');
				if (autoriser(
					($_GET['var_mode'] == 'preview')
						? 'previsualiser'
						: 'debug'
				)) {
					// preview ?
					if ($_GET['var_mode'] == 'preview') {
						// forcer le compilo et ignorer les caches existants
						$GLOBALS['var_mode'] = 'recalcul';
						// truquer les boucles et ne pas enregistrer de cache
						$GLOBALS['var_preview'] = true;
					}
					// seul cas ici: 'debug'
					else { 
						$GLOBALS['var_mode'] = $_GET['var_mode'];
					}
					spip_log($GLOBALS['visiteur_session']['nom']
						. " ".$GLOBALS['var_mode']);
				}
				// pas autorise ?
				else {
					// si on n'est pas connecte on se redirige
					if (!$GLOBALS['visiteur_session']) {
						include_spip('inc/headers');
						redirige_par_entete(generer_url_public('login',
						'url='.rawurlencode(
						parametre_url(self(), 'var_mode', $_GET['var_mode'], '&')
						), true));
					}
					// sinon tant pis
				}
			}
			else if ($_GET['var_mode'] == 'images'){
				// forcer le compilo et ignorer les caches existants
				$GLOBALS['var_mode'] = 'calcul';
				// indiquer qu'on doit recalculer les images
				$GLOBALS['var_images'] = true;
			}
		}		
		$done = true;
	}
}

// http://doc.spip.org/@traiter_formulaires_dynamiques
function traiter_formulaires_dynamiques(){
	static $done = false;
	if (!$done) {
		// traiter les appels de bloc ajax
		if (($v=_request('var_ajax'))
		 AND ($v!=='form')
		 AND ($args = _request('var_ajax_env'))
		 AND ($cle = _request('var_ajax_cle')) ){ 
			 if ((include_spip('inc/securiser_action'))
			 AND ($cle == calculer_cle_action($args))) {
				$args = unserialize(base64_decode($args));
				if ($fond = $args['fond_ajax']){
					include_spip('public/parametrer');
					$contexte = calculer_contexte();
					$contexte = array_merge($args, $contexte);
					$page = evaluer_fond($fond,$contexte);
					include_spip('inc/actions');
					ajax_retour($page['texte']);
					exit();
				}
			}
			include_spip('inc/actions');
			ajax_retour('signature ajax incorrecte');
			exit();
		}
		// traiter les formulaires dynamiques simplifies en charger/verifier/traiter
		if (($form = _request('formulaire_action'))
		 AND ($cle = _request('formulaire_action_cle'))
		 AND (($args = _request('formulaire_action_args'))!==NULL)
		 AND (include_spip('inc/securiser_action'))
		 AND ($cle == calculer_cle_action($form . $args))) {
			$args = unserialize(base64_decode($args));
			if (
			 (!($verifier = charger_fonction("verifier","formulaires/$form/",true))
			   || (count($_POST["erreurs_$form"] = call_user_func_array($verifier,$args))==0))
			 && ($traiter = charger_fonction("traiter","formulaires/$form/",true))
			 ) {
				$_POST["message_ok_$form"] = call_user_func_array($traiter,$args);
				// traiter peut retourner soit un message, soit un array(editable,message)
				if (is_array($_POST["message_ok_$form"]))
					list($_POST["editable_$form"],$_POST["message_ok_$form"]) = $_POST["message_ok_$form"];
			}
			// si le formulaire a ete soumis en ajax, on le renvoie direct !
			if (_request('var_ajax')){
				if (find_in_path('formulaire_.php','balise/',true)) {
					include_spip('inc/actions');
					array_unshift($args,$form);
					ajax_retour(inclure_balise_dynamique(call_user_func_array('balise_formulaire__dyn',$args),false),false);
					exit;
				}
			}
		}
		$done = true;
	}
}

// fonction principale declenchant tout le service
// elle-meme ne fait que traiter les cas particuliers, puis passe la main.
// http://doc.spip.org/@public_assembler_dist
function public_assembler_dist($fond, $connect='') {
	  global $forcer_lang, $ignore_auth_http;

	// multilinguisme
	if ($forcer_lang AND ($forcer_lang!=='non')) {
		include_spip('inc/lang');
		verifier_lang_url();
	}
	if ($l = isset($_GET['lang'])) {
		$l = lang_select($_GET['lang']);
	}

	// Si envoi pour un forum, enregistrer puis rediriger
	if (isset($_POST['confirmer_forum'])
	OR (isset($_POST['ajouter_mot']) AND $GLOBALS['afficher_texte']=='non')) {
		include_spip('inc/headers');
		$forum_insert = charger_fonction('forum_insert', 'inc');
		redirige_par_entete($forum_insert());
	}
	
	traiter_formulaires_dynamiques();
	
	// si signature de petition, l'enregistrer avant d'afficher la page
	// afin que celle-ci contienne la signature
	if (isset($_GET['var_confirm'])) {
		include_spip('balise/formulaire_signature');
		reponse_confirmation($_GET['var_confirm']);
	}
	
	init_var_mode();
	
	if ($l) lang_select();
	return assembler_page ($fond, $connect);
}

// fonction pour l'envoi de fichier
// http://doc.spip.org/@envoyer_page
function envoyer_page($fond, $contexte)
{
	$page = inclure_page($fond, $contexte);
	if (!is_array($page['entetes'])) {
		include_spip('inc/headers');
		redirige_par_entete(generer_url_public('404'));
	}
	envoyer_entetes($page['entetes']);
	echo $page['texte'];
}

// Envoyer les entetes, en retenant ceux qui sont a usage interne
// et demarrent par X-Spip-...
// http://doc.spip.org/@envoyer_entetes
function envoyer_entetes($entetes) {
	foreach ($entetes as $k => $v)
	#	if (strncmp($k, 'X-Spip-', 7))
			@header("$k: $v");
}


//
// calcule la page et les entetes
//
// http://doc.spip.org/@assembler_page
function assembler_page ($fond, $connect='') {
	global $flag_preserver,$lastmodified,
		$use_cache;

	// Cette fonction est utilisee deux fois
	$cacher = charger_fonction('cacher', 'public');
	// Garnir ces quatre parametres avec les infos sur le cache
	// Si un resultat est retourne, c'est un message d'impossibilite
	$res = $cacher(NULL, $use_cache, $chemin_cache, $page, $lastmodified);
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
			$GLOBALS['contexte'] = $page['contexte'];
		}
		// sinon analyser le contexte & calculer la page
		else {
			$parametrer = charger_fonction('parametrer', 'public');
			$page = $parametrer($fond, '', $chemin_cache, $connect);

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
function inclure_page($fond, $contexte_inclus, $connect='') {
	global $lastmodified;
	if (!defined('_PAS_DE_PAGE_404'))
		define('_PAS_DE_PAGE_404',1);

	$contexte_inclus['fond'] = $fond; // securite, necessaire pour calculer correctement le cache

	// Si on a inclus sans fixer le critere de lang, on prend la langue courante
	if (!isset($contexte_inclus['lang']))
		$contexte_inclus['lang'] = $GLOBALS['spip_lang'];

	if ($contexte_inclus['lang'] != $GLOBALS['meta']['langue_site']) {
		$lang_select = lang_select($contexte_inclus['lang']);
	} else $lang_select ='';

	init_var_mode();

	$cacher = charger_fonction('cacher', 'public');
	// Garnir ces quatre parametres avec les infos sur le cache :
	// emplacement, validite, et, s'il est valide, contenu & age
	$cacher($contexte_inclus, $use_cache, $chemin_cache, $page, $lastinclude);

	// Une fois le chemin-cache decide, on ajoute la date (et date_redac)
	// dans le contexte inclus, pour que les criteres {age} etc fonctionnent
	
	// ATTENTION : les balises dynamiques passent par la, et l'ajout de la date/heure/seconde rend
	// tout cache invalide, meme si le reste des arguments est constant
	// probleme possible de perf ici
	if (!isset($contexte_inclus['date']))
		$contexte_inclus['date'] = date('Y-m-d H:i:s');
	if (!isset($contexte_inclus['date_redac']))
		$contexte_inclus['date_redac'] = $contexte_inclus['date'];
	// il faut enlever le fond de contexte inclus car sinon il prend la main
	// dans les sous inclusions -> boucle infinie d'inclusion identique
	unset($contexte_inclus['fond']);

	// Si use_cache vaut 0, la page a ete tiree du cache et se trouve dans $page
	if (!$use_cache) {
		$lastmodified = max($lastmodified, $lastinclude);
	}
	// sinon on la calcule
	else {
		$parametrer = charger_fonction('parametrer', 'public');
		$page = $parametrer($fond, $contexte_inclus, $chemin_cache, $connect);

		$lastmodified = time();
		// et on l'enregistre sur le disque
		if ($chemin_cache
		AND $page['entetes']['X-Spip-Cache'] > 0)
			$cacher($contexte_inclus, $use_cache, $chemin_cache, $page,
				$lastmodified);
	}
	if ($lang_select) lang_select();

	return $page;
}


# Attention, un appel explicite a cette fonction suppose certains include
# (voir l'exemple de spip_inscription et spip_pass)
# $echo = faut-il faire echo ou return

// http://doc.spip.org/@inclure_balise_dynamique
function inclure_balise_dynamique($texte, $echo=true, $ligne=0) {
	global $contexte_inclus; # provisoire : c'est pour le debuggueur

	if (is_array($texte)) {

		list($fond, $delainc, $contexte_inclus) = $texte;

		// delais a l'ancienne, c'est pratiquement mort
		$d = isset($GLOBALS['delais']) ? $GLOBALS['delais'] : NULL;
		$GLOBALS['delais'] = $delainc;
		$page = inclure_page($fond, $contexte_inclus);
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

		if ($page['process_ins'] == 'html') {
				$texte = $page['texte'];
		} else {
				ob_start();
				xml_hack($page, true);
				eval('?' . '>' . $page['texte']);
				$texte = ob_get_contents();
				xml_hack($page);
				ob_end_clean();
		}
		if (isset($contexte_inclus['_pipeline'])
		 && is_array($contexte_inclus['_pipeline'])
		 && isset($GLOBALS['spip_pipeline'][reset($contexte_inclus['_pipeline'])])){
			$texte = pipeline(reset($contexte_inclus['_pipeline']),array(
			  'data'=>$texte,
			  'args'=>end($contexte_inclus['_pipeline'])));
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
	if (isset($_SERVER['HTTP_REFERER']) || isset($_GET['var_recherche'])) {
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
			$f = charger_fonction('sax', 'inc');
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
	if (defined('_PAS_DE_PAGE_404'))
		return "erreur";
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

// fonction permettant de recuperer le resultat du calcul d'un squelette
// pour une inclusion dans un flux
// http://doc.spip.org/@recuperer_fond
function recuperer_fond($fond, $contexte=array(), $trim=true, $connect='') {
	$options = array('trim' => $trim);

	$texte = "";
	foreach(is_array($fond) ? $fond : array($fond) as $f){
		$page = evaluer_fond($f, $contexte, $options, $connect);
		$texte .= $trim ? rtrim($page['texte']) : $page['texte'];
	}

	return $trim ? ltrim($texte) : $texte;
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

	$fond = 'modeles/'.$type;

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
			$fond = 'modeles/'.$type.'_'.$soustype;
			if (!find_in_path($fond.'.html')) {
				$fond = 'modeles/'.$type;
				$class = $soustype;
			}
			// enlever le sous type des params
			$params = array_diff($params,array($soustype));
		}
	}

	// en cas d'echec : si l'objet demande a une url, on cree un petit encadre
	// avec un lien vers l'objet ; sinon on passe la main au suivant
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
	if (isset($arg_list['ajax']) && $arg_list['ajax']=='ajax'){
		$contexte['fond_ajax']=$contexte['fond'];
		$contexte['fond']='fond/ajax';
		unset($arg_list['ajax']);
	}
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
	$page = evaluer_fond($contexte['fond'], $contexte, array(), $connect);
	$retour = trim($page['texte']);

	// Lever un drapeau (global) si le modele utilise #SESSION
	// a destination de public/parametrer
	if (isset($page['invalideurs'])
	AND isset($page['invalideurs']['session']))
		$GLOBALS['cache_utilise_session'] = $page['invalideurs']['session'];

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
	return $retour;
}

// Appeler avant et apres chaque eval()
// http://doc.spip.org/@xml_hack
function xml_hack(&$page, $echap = false) {
	if ($echap)
		$page['texte'] = str_replace('<'.'?xml', "<\1?xml", $page['texte']);
	else
		$page['texte'] = str_replace("<\1?xml", '<'.'?xml', $page['texte']);
}

?>
