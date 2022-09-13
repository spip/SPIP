<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Ce fichier regroupe les fonctions permettant de calculer la page et les entêtes
 *
 * Determine le contexte donne par l'URL (en tenant compte des reecritures)
 * grace a la fonction de passage d'URL a id (reciproque dans urls/*php)
 *
 * @package SPIP\Core\Compilateur\Assembler
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// En cas de modification, il faut aussi actualiser la regexp de nettoyer_uri_var() dans inc/utils.php
if (!defined('_CONTEXTE_IGNORE_VARIABLES')) {
	define('_CONTEXTE_IGNORE_VARIABLES', '/(^var_|^PHPSESSID$|^fbclid$|^utm_)/');
}

function assembler($fond, string $connect = '') {

	$chemin_cache = null;
	$lastmodified = null;
	$res = null;
	// flag_preserver est modifie ici, et utilise en globale
	// use_cache sert a informer le bouton d'admin pr savoir s'il met un *
	// contexte est utilise en globale dans le formulaire d'admin

	$GLOBALS['contexte'] = calculer_contexte();
	$page = ['contexte_implicite' => calculer_contexte_implicite()];
	$page['contexte_implicite']['cache'] = $fond . preg_replace(
		',\.[a-zA-Z0-9]*$,',
		'',
		preg_replace('/[?].*$/', '', $GLOBALS['REQUEST_URI'])
	);
	// Cette fonction est utilisee deux fois
	$cacher = charger_fonction('cacher', 'public', true);
	// Les quatre derniers parametres sont modifies par la fonction:
	// emplacement, validite, et, s'il est valide, contenu & age
	if ($cacher) {
		$res = $cacher($GLOBALS['contexte'], $GLOBALS['use_cache'], $chemin_cache, $page, $lastmodified);
	} else {
		$GLOBALS['use_cache'] = -1;
	}
	// Si un resultat est retourne, c'est un message d'impossibilite
	if ($res) {
		return ['texte' => $res];
	}

	if (!$chemin_cache || !$lastmodified) {
		$lastmodified = time();
	}

	$headers_only = ($_SERVER['REQUEST_METHOD'] == 'HEAD');
	$calculer_page = true;

	// Pour les pages non-dynamiques (indiquees par #CACHE{duree,cache-client})
	// une perennite valide a meme reponse qu'une requete HEAD (par defaut les
	// pages sont dynamiques)
	if (
		isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
		and (!defined('_VAR_MODE') or !_VAR_MODE)
		and $chemin_cache
		and isset($page['entetes'])
		and isset($page['entetes']['Cache-Control'])
		and strstr($page['entetes']['Cache-Control'], 'max-age=')
		and !strstr($_SERVER['SERVER_SOFTWARE'], 'IIS/')
	) {
		$since = preg_replace(
			'/;.*/',
			'',
			$_SERVER['HTTP_IF_MODIFIED_SINCE']
		);
		$since = str_replace('GMT', '', $since);
		if (trim($since) == gmdate('D, d M Y H:i:s', $lastmodified)) {
			$page['status'] = 304;
			$headers_only = true;
			$calculer_page = false;
		}
	}

	// Si requete HEAD ou Last-modified compatible, ignorer le texte
	// et pas de content-type (pour contrer le bouton admin de inc-public)
	if (!$calculer_page) {
		$page['texte'] = '';
	} else {
		// si la page est prise dans le cache
		if (!$GLOBALS['use_cache']) {
			// Informer les boutons d'admin du contexte
			// (fourni par urls_decoder_url ci-dessous lors de la mise en cache)
			$GLOBALS['contexte'] = $page['contexte'];

			// vider les globales url propres qui ne doivent plus etre utilisees en cas
			// d'inversion url => objet
			// plus necessaire si on utilise bien la fonction urls_decoder_url
			#unset($_SERVER['REDIRECT_url_propre']);
			#unset($_ENV['url_propre']);
		} else {
			// Compat ascendante :
			// 1. $contexte est global
			// (a evacuer car urls_decoder_url gere ce probleme ?)
			// et calculer la page
			if (!test_espace_prive()) {
				include_spip('inc/urls');
				[$fond, $GLOBALS['contexte'], $url_redirect] = urls_decoder_url(
					nettoyer_uri(),
					$fond,
					$GLOBALS['contexte'],
					true
				);
			}
			// squelette par defaut
			if (!strlen($fond ?? '')) {
				$fond = 'sommaire';
			}

			// produire la page : peut mettre a jour $lastmodified
			$produire_page = charger_fonction('produire_page', 'public');
			$page = $produire_page(
				$fond,
				$GLOBALS['contexte'],
				$GLOBALS['use_cache'],
				$chemin_cache,
				null,
				$page,
				$lastmodified,
				$connect
			);
			if ($page === '') {
				$erreur = _T(
					'info_erreur_squelette2',
					['fichier' => spip_htmlspecialchars($fond) . '.' . _EXTENSION_SQUELETTES]
				);
				erreur_squelette($erreur);
				// eviter des erreurs strictes ensuite sur $page['cle'] en PHP >= 5.4
				$page = ['texte' => '', 'erreur' => $erreur];
			}
		}

		if ($page and $chemin_cache) {
			$page['cache'] = $chemin_cache;
		}

		auto_content_type($page);

		$GLOBALS['flag_preserver'] |= headers_sent();

		// Definir les entetes si ce n'est fait
		if (!$GLOBALS['flag_preserver']) {
			// Si la page est vide, produire l'erreur 404 ou message d'erreur pour les inclusions
			if (
				trim($page['texte']) === ''
				and _VAR_MODE !== 'debug'
				and !isset($page['entetes']['Location']) // cette page realise une redirection, donc pas d'erreur
			) {
				$GLOBALS['contexte']['fond_erreur'] = $fond;
				$page = message_page_indisponible($page, $GLOBALS['contexte']);
			}
			// pas de cache client en mode 'observation'
			if (defined('_VAR_MODE') and _VAR_MODE) {
				$page['entetes']['Cache-Control'] = 'no-cache,must-revalidate';
				$page['entetes']['Pragma'] = 'no-cache';
			}
		}
	}

	// Entete Last-Modified:
	// eviter d'etre incoherent en envoyant un lastmodified identique
	// a celui qu'on a refuse d'honorer plus haut (cf. #655)
	if (
		$lastmodified
		and !isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
		and !isset($page['entetes']['Last-Modified'])
	) {
		$page['entetes']['Last-Modified'] = gmdate('D, d M Y H:i:s', $lastmodified) . ' GMT';
	}

	// fermer la connexion apres les headers si requete HEAD
	if ($headers_only) {
		$page['entetes']['Connection'] = 'close';
	}

	return $page;
}

/**
 * Calcul le contexte de la page
 *
 * lors du calcul d'une page spip etablit le contexte a partir
 * des variables $_GET et $_POST, purgees des fausses variables var_*
 *
 * Note : pour hacker le contexte depuis le fichier d'appel (page.php),
 * il est recommande de modifier $_GET['toto'] (meme si la page est
 * appelee avec la methode POST).
 *
 * @return array Un tableau du contexte de la page
 */
function calculer_contexte() {

	$contexte = [];
	foreach ($_GET as $var => $val) {
		if (!preg_match(_CONTEXTE_IGNORE_VARIABLES, $var)) {
			$contexte[$var] = $val;
		}
	}
	foreach ($_POST as $var => $val) {
		if (!preg_match(_CONTEXTE_IGNORE_VARIABLES, $var)) {
			$contexte[$var] = $val;
		}
	}

	return $contexte;
}

/**
 * Calculer le contexte implicite, qui n'apparait pas dans le ENV d'un cache
 * mais est utilise pour distinguer deux caches differents
 *
 * @staticvar string $notes
 * @return array
 */
function calculer_contexte_implicite() {
	static $notes = null;
	if (is_null($notes)) {
		$notes = charger_fonction('notes', 'inc', true);
	}
	$contexte_implicite = [
		'squelettes' => $GLOBALS['dossier_squelettes'], // devrait etre 'chemin' => $GLOBALS['path_sig'], ?
		'host' => ($_SERVER['HTTP_HOST'] ?? null),
		'https' => ($_SERVER['HTTPS'] ?? ''),
		'espace' => test_espace_prive(),
		'marqueur' => ($GLOBALS['marqueur'] ?? ''),
		'marqueur_skel' => ($GLOBALS['marqueur_skel'] ?? ''),
		'notes' => $notes ? $notes('', 'contexter_cache') : '',
		'spip_version_code' => $GLOBALS['spip_version_code'],
	];
	if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
		$contexte_implicite['host'] .= '|' . $_SERVER['HTTP_X_FORWARDED_HOST'];
	}

	return $contexte_implicite;
}

//
// fonction pour compatibilite arriere, probablement superflue
//

function auto_content_type($page) {

	if (!isset($GLOBALS['flag_preserver'])) {
		$GLOBALS['flag_preserver'] = ($page && preg_match(
			'/header\s*\(\s*.content\-type:/isx',
			$page['texte']
		) || (isset($page['entetes']['Content-Type'])));
	}
}

function inclure_page($fond, $contexte, string $connect = '') {
	$use_cache = null;
	$chemin_cache = null;
	$lastinclude = null;
	$res = null;
	static $cacher, $produire_page;

	// enlever le fond de contexte inclus car sinon il prend la main
	// dans les sous inclusions -> boucle infinie d'inclusion identique
	// (cette precaution n'est probablement plus utile)
	unset($contexte['fond']);
	$page = ['contexte_implicite' => calculer_contexte_implicite()];
	$page['contexte_implicite']['cache'] = $fond;
	if (is_null($cacher)) {
		$cacher = charger_fonction('cacher', 'public', true);
	}
	// Les quatre derniers parametres sont modifies par la fonction:
	// emplacement, validite, et, s'il est valide, contenu & age
	if ($cacher) {
		$res = $cacher($contexte, $use_cache, $chemin_cache, $page, $lastinclude);
	} else {
		$use_cache = -1;
	}
	// $res = message d'erreur : on sort de la
	if ($res) {
		return ['texte' => $res];
	}

	// Si use_cache ne vaut pas 0, la page doit etre calculee
	// produire la page : peut mettre a jour $lastinclude
	// le contexte_cache envoye a cacher() a ete conserve et est passe a produire
	if ($use_cache) {
		if (is_null($produire_page)) {
			$produire_page = charger_fonction('produire_page', 'public');
		}
		$page = $produire_page($fond, $contexte, $use_cache, $chemin_cache, $contexte, $page, $lastinclude, $connect);
	}
	// dans tous les cas, mettre a jour $GLOBALS['lastmodified']
	$GLOBALS['lastmodified'] = max(($GLOBALS['lastmodified'] ?? 0), $lastinclude);

	return $page;
}

/**
 * Produire la page et la mettre en cache
 * lorsque c'est necessaire
 *
 * @param string $fond
 * @param array $contexte
 * @param int $use_cache
 * @param string $chemin_cache
 * @param array $contexte_cache
 * @param array $page
 * @param int $lastinclude
 * @param string $connect
 * @return array
 */
function public_produire_page_dist(
	$fond,
	$contexte,
	$use_cache,
	$chemin_cache,
	$contexte_cache,
	&$page,
	&$lastinclude,
	$connect = ''
) {
	static $parametrer, $cacher;
	if (!$parametrer) {
		$parametrer = charger_fonction('parametrer', 'public');
	}
	$page = $parametrer($fond, $contexte, $chemin_cache, $connect);
	// et on l'enregistre sur le disque
	if (
		$chemin_cache
		and $use_cache > -1
		and is_array($page)
		and count($page)
		and isset($page['entetes']['X-Spip-Cache'])
		and $page['entetes']['X-Spip-Cache'] > 0
	) {
		if (is_null($cacher)) {
			$cacher = charger_fonction('cacher', 'public', true);
		}
		$lastinclude = time();
		if ($cacher) {
			$cacher($contexte_cache, $use_cache, $chemin_cache, $page, $lastinclude);
		} else {
			$use_cache = -1;
		}
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

function inserer_balise_dynamique($contexte_exec, $contexte_compil) {
	arguments_balise_dyn_depuis_modele(null, 'reset');

	if (!is_array($contexte_exec)) {
		echo $contexte_exec;
	} // message d'erreur etc
	else {
		inclure_balise_dynamique($contexte_exec, true, $contexte_compil);
	}
}

/**
 * Inclusion de balise dynamique
 * Attention, un appel explicite a cette fonction suppose certains include
 *
 * @param string|array $texte
 * @param bool $echo Faut-il faire echo ou return
 * @param array $contexte_compil Contexte de la compilation
 * @return string
 */
function inclure_balise_dynamique($texte, $echo = true, $contexte_compil = []) {
	if (is_array($texte)) {
		[$fond, $delainc, $contexte_inclus] = $texte;

		// delais a l'ancienne, c'est pratiquement mort
		$d = $GLOBALS['delais'] ?? null;
		$GLOBALS['delais'] = $delainc;

		$page = recuperer_fond(
			$fond,
			$contexte_inclus,
			['trim' => false, 'raw' => true, 'compil' => $contexte_compil]
		);

		$texte = $page['texte'];

		$GLOBALS['delais'] = $d;
		// Faire remonter les entetes
		if (
			isset($page['entetes'])
			and is_array($page['entetes'])
		) {
			// mais pas toutes
			unset($page['entetes']['X-Spip-Cache']);
			unset($page['entetes']['Content-Type']);
			if (isset($GLOBALS['page']) and is_array($GLOBALS['page'])) {
				if (!is_array($GLOBALS['page']['entetes'])) {
					$GLOBALS['page']['entetes'] = [];
				}
				$GLOBALS['page']['entetes'] =
					array_merge($GLOBALS['page']['entetes'], $page['entetes']);
			}
		}
		// _pipelines au pluriel array('nom_pipeline' => $args...) avec une syntaxe permettant plusieurs pipelines
		if (
			isset($page['contexte']['_pipelines'])
			and is_array($page['contexte']['_pipelines'])
			and count($page['contexte']['_pipelines'])
		) {
			foreach ($page['contexte']['_pipelines'] as $pipe => $args) {
				$args['contexte'] = $page['contexte'];
				unset($args['contexte']['_pipelines']); // par precaution, meme si le risque de boucle infinie est a priori nul
				$texte = pipeline(
					$pipe,
					[
						'data' => $texte,
						'args' => $args
					]
				);
			}
		}
	}

	if (defined('_VAR_MODE') and _VAR_MODE == 'debug') {
		// compatibilite : avant on donnait le numero de ligne ou rien.
		$ligne = intval($contexte_compil[3] ?? $contexte_compil);
		$GLOBALS['debug_objets']['resultat'][$ligne] = $texte;
	}
	if ($echo) {
		echo $texte;
	} else {
		return $texte;
	}
}

function message_page_indisponible($page, $contexte) {
	static $deja = false;
	if ($deja) {
		return 'erreur';
	}
	$codes = [
		'404' => '404 Not Found',
		'503' => '503 Service Unavailable',
	];

	$contexte['status'] = ($page !== false) ? '404' : '503';
	$contexte['code'] = $codes[$contexte['status']];
	$contexte['fond'] = '404'; // gere les 2 erreurs
	if (!isset($contexte['lang'])) {
		include_spip('inc/lang');
		$contexte['lang'] = $GLOBALS['spip_lang'];
	}

	$deja = true;
	// passer aux plugins qui peuvent decider d'une page d'erreur plus pertinent
	// ex restriction d'acces => 401
	$contexte = pipeline('page_indisponible', $contexte);

	// produire la page d'erreur
	$page = inclure_page($contexte['fond'], $contexte);
	if (!$page) {
		$page = inclure_page('404', $contexte);
	}
	$page['status'] = $contexte['status'];

	return $page;
}

/**
 * gerer le flag qui permet de reperer qu'une balise dynamique a ete inseree depuis un modele
 * utilisee dans les #FORMULAIRE_xx
 *
 * @param string|null $arg
 * @param string $operation
 * @return mixed
 */
function arguments_balise_dyn_depuis_modele($arg, $operation = 'set') {
	static $balise_dyn_appellee_par_modele = null;
	switch ($operation) {
		case 'read':
			return $balise_dyn_appellee_par_modele;
		case 'reset':
			$balise_dyn_appellee_par_modele = null;
			return null;
		case 'set':
		default:
			$balise_dyn_appellee_par_modele = $arg;
			return $arg;
	}
}

// temporairement ici : a mettre dans le futur inc/modeles
// creer_contexte_de_modele('left', 'autostart=true', ...) renvoie un array()
function creer_contexte_de_modele($args) {
	$contexte = [];
	foreach ($args as $var => $val) {
		if (is_int($var)) { // argument pas formate
			if (in_array($val, ['left', 'right', 'center'])) {
				$var = 'align';
				$contexte[$var] = $val;
			} else {
				$args = explode('=', $val);
				if (count($args) >= 2) { // Flashvars=arg1=machin&arg2=truc genere plus de deux args
				$contexte[trim($args[0])] = substr($val, strlen($args[0]) + 1);
				} else // notation abregee
				{
					$contexte[trim($val)] = trim($val);
				}
			}
		} else {
			$contexte[$var] = $val;
		}
	}

	return $contexte;
}

/**
 * Router eventuellement un modele vers un autre
 *   * le modele doit etre declare dans la liste 'modeles' d'une table objet
 *   * il faut avoir un routeur de modele declare pour le meme objet
 * @param string $modele
 * @param int $id
 * @param null|array $contexte
 *   contexte eventuel : attention sa presence n'est pas garantie
 *   et il ne doit etre utilise que pour trouver le id_xx si pas de $id fourni (cas appel depuis styliser)
 * @return string
 */
function styliser_modele($modele, $id, $contexte = null) {
	static $styliseurs = null;
	if (is_null($styliseurs)) {
		$tables_objet = lister_tables_objets_sql();
		foreach ($tables_objet as $table => $desc) {
			if (
				isset($desc['modeles']) and $desc['modeles']
				and isset($desc['modeles_styliser']) and $desc['modeles_styliser']
				and function_exists($desc['modeles_styliser'])
			) {
				$primary = id_table_objet($table);
				foreach ($desc['modeles'] as $m) {
					$styliseurs[$m] = ['primary' => $primary, 'callback' => $desc['modeles_styliser']];
				}
			}
		}
	}

	if (isset($styliseurs[$modele])) {
		$styliseur = $styliseurs[$modele]['callback'];
		$primary = $styliseurs[$modele]['primary'];
		if (is_null($id) and $contexte) {
			if (isset($contexte['id'])) {
				$id = $contexte['id'];
			} elseif (isset($contexte[$primary])) {
				$id = $contexte[$primary];
			}
		}
		if (is_null($id)) {
			$msg = "modeles/$modele : " . _T('zbug_parametres_inclus_incorrects', ['param' => "id/$primary"]);
			erreur_squelette($msg);
			// on passe id=0 au routeur pour tomber sur le modele par defaut et eviter une seconde erreur sur un modele inexistant
			$id = 0;
		}
		$modele = $styliseur($modele, $id);
	}

	return $modele;
}

/**
 * Calcule le modele et retourne la mini-page ainsi calculee
 *
 * @param $type string Nom du modele
 * @param $id int
 * @param $params array Paramètres du modèle
 * @param $lien array Informations du lien entourant l'appel du modèle en base de données
 * @param $connect string
 * @param $env array
 * @staticvar string $compteur
 * @return string
 */
function inclure_modele($type, $id, $params, $lien, string $connect = '', $env = []) {

	static $compteur;
	if (++$compteur > 10) {
		return '';
	} # ne pas boucler indefiniment

	$type = strtolower($type);
	$type = styliser_modele($type, $id);

	$fond = $class = '';

	$params = array_filter(explode('|', $params));
	if ($params) {
		$soustype = current($params);
		$soustype = strtolower(trim($soustype));
		if (in_array($soustype, ['left', 'right', 'center', 'ajax'])) {
			$soustype = next($params);
			$soustype = strtolower($soustype);
		}

		if (preg_match(',^[a-z0-9_]+$,', $soustype)) {
			if (!trouve_modele($fond = ($type . '_' . $soustype))) {
				$fond = '';
				$class = $soustype;
			}
			// enlever le sous type des params
			$params = array_diff($params, [$soustype]);
		}
	}

	// Si ca marche pas en precisant le sous-type, prendre le type
	if (!$fond and !trouve_modele($fond = $type)) {
		spip_log("Modele $type introuvable", _LOG_INFO_IMPORTANTE);

		return false;
	}
	$fond = 'modeles/' . $fond;
	// Creer le contexte
	$contexte = $env;
	$contexte['dir_racine'] = _DIR_RACINE; # eviter de mixer un cache racine et un cache ecrire (meme si pour l'instant les modeles ne sont pas caches, le resultat etant different il faut que le contexte en tienne compte

	// Le numero du modele est mis dans l'environnement
	// d'une part sous l'identifiant "id"
	// et d'autre part sous l'identifiant de la cle primaire
	// par la fonction id_table_objet,
	// (<article1> =>> article =>> id_article =>> id_article=1)
	$_id = id_table_objet($type);
	$contexte['id'] = $contexte[$_id] = $id;

	if (isset($class)) {
		$contexte['class'] = $class;
	}

	// Si un lien a ete passe en parametre, ex: [<modele1>->url] ou [<modele1|title_du_lien{hreflang}->url]
	if ($lien) {
		# un eventuel guillemet (") sera reechappe par #ENV
		$contexte['lien'] = str_replace('&quot;', '"', $lien['href']);
		$contexte['lien_class'] = $lien['class'];
		$contexte['lien_mime'] = $lien['mime'];
		$contexte['lien_title'] = $lien['title'];
		$contexte['lien_hreflang'] = $lien['hreflang'];
	}

	// Traiter les parametres
	// par exemple : <img1|center>, <emb12|autostart=true> ou <doc1|lang=en>
	$arg_list = creer_contexte_de_modele($params);
	$contexte['args'] = $arg_list; // on passe la liste des arguments du modeles dans une variable args
	$contexte = array_merge($contexte, $arg_list);

	// Appliquer le modele avec le contexte
	$retour = recuperer_fond($fond, $contexte, [], $connect);

	// Regarder si le modele tient compte des liens (il *doit* alors indiquer
	// spip_lien_ok dans les classes de son conteneur de premier niveau ;
	// sinon, s'il y a un lien, on l'ajoute classiquement
	if (
		strstr(
			' ' . ($classes = extraire_attribut($retour, 'class')) . ' ',
			'spip_lien_ok'
		)
	) {
		$retour = inserer_attribut(
			$retour,
			'class',
			trim(str_replace(' spip_lien_ok ', ' ', " $classes "))
		);
	} else {
		if ($lien) {
			$retour = "<a href=\"" . $lien['href'] . "\" class=\"" . $lien['class'] . "\">" . $retour . '</a>';
		}
	}

	$compteur--;

	return (isset($arg_list['ajax']) and $arg_list['ajax'] == 'ajax')
		? encoder_contexte_ajax($contexte, '', $retour)
		: $retour;
}

// Un inclure_page qui marche aussi pour l'espace prive
// fonction interne a spip, ne pas appeler directement
// pour recuperer $page complet, utiliser:
// 	recuperer_fond($fond,$contexte,array('raw'=>true))
function evaluer_fond($fond, $contexte = [], string $connect = '') {

	$page = inclure_page($fond, $contexte, $connect);

	if (!$page) {
		return $page;
	}
	// eval $page et affecte $res
	include _ROOT_RESTREINT . 'public/evaluer_page.php';

	// Lever un drapeau (global) si le fond utilise #SESSION
	// a destination de public/parametrer
	// pour remonter vers les inclusions appelantes
	// il faut bien lever ce drapeau apres avoir evalue le fond
	// pour ne pas faire descendre le flag vers les inclusions appelees
	if (
		isset($page['invalideurs'])
		and isset($page['invalideurs']['session'])
	) {
		$GLOBALS['cache_utilise_session'] = $page['invalideurs']['session'];
	}

	return $page;
}


function page_base_href(&$texte) {
	static $set_html_base = null;
	if (is_null($set_html_base)) {
		if (!defined('_SET_HTML_BASE')) {
			// si la profondeur est superieure a 1
			// est que ce n'est pas une url page ni une url action
			// activer par defaut
		$set_html_base = ((
				$GLOBALS['profondeur_url'] >= (_DIR_RESTREINT ? 1 : 2)
				and _request(_SPIP_PAGE) !== 'login'
				and !_request('action')) ? true : false);
		} else {
			$set_html_base = _SET_HTML_BASE;
		}
	}

	if (
		$set_html_base
		and isset($GLOBALS['html']) and $GLOBALS['html']
		and $GLOBALS['profondeur_url'] > 0
		and ($poshead = strpos($texte, '</head>')) !== false
	) {
		$head = substr($texte, 0, $poshead);
		$insert = false;
		$href_base = false;
		if (strpos($head, '<base') === false) {
			$insert = true;
		} else {
			// si aucun <base ...> n'a de href il faut en inserer un
			// sinon juste re-ecrire les ancres si besoin
			$insert = true;
			include_spip('inc/filtres');
			$bases = extraire_balises($head, 'base');
			foreach ($bases as $base) {
				if ($href_base = extraire_attribut($base, 'href')) {
					$insert = false;
					break;
				}
			}
		}

		if ($insert) {
			include_spip('inc/filtres_mini');
			// ajouter un base qui reglera tous les liens relatifs
			$href_base = url_absolue('./');
			$base = "\n<base href=\"$href_base\" />";
			if (($pos = strpos($head, '<head>')) !== false) {
				$head = substr_replace($head, $base, $pos + 6, 0);
			} elseif (preg_match(',<head[^>]*>,i', $head, $r)) {
				$head = str_replace($r[0], $r[0] . $base, $head);
			}
			$texte = $head . substr($texte, $poshead);
		}
		if ($href_base) {
			// gerer les ancres
			$base = $_SERVER['REQUEST_URI'];
			// pas de guillemets ni < dans l'URL qu'on insere dans le HTML
			if (strpos($base, "'") or strpos($base, '"') or strpos($base, '<')) {
				$base = str_replace(["'",'"','<'], ['%27','%22','%3C'], $base);
			}
			if (strpos($texte, "href='#") !== false) {
				$texte = str_replace("href='#", "href='$base#", $texte);
			}
			if (strpos($texte, 'href="#') !== false) {
				$texte = str_replace('href="#', "href=\"$base#", $texte);
			}
		}
	}
}


// Envoyer les entetes, en retenant ceux qui sont a usage interne
// et demarrent par X-Spip-...
function envoyer_entetes($entetes) {
	foreach ($entetes as $k => $v) { #	if (strncmp($k, 'X-Spip-', 7))
	@header(strlen($v) ? "$k: $v" : $k);
	}
}
