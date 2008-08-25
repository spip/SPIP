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
 // Ce fichier calcule une page en executant un squelette.
//

include_spip('inc/lang');
include_spip('inc/acces');

// NB: Ce fichier peut initialiser $dossier_squelettes (old-style)
// donc il faut l'inclure "en globals"
if ($f = find_in_path('mes_fonctions.php')
OR $f = find_in_path('mes_fonctions.php3')) {
	global $dossier_squelettes;
	include ($f);
}
if (@is_readable(_DIR_TMP."charger_plugins_fonctions.php")){
	// chargement optimise precompile
	include_once(_DIR_TMP."charger_plugins_fonctions.php");
}

//
// Contexte : lors du calcul d'une page spip etablit le contexte a partir
// des variables $_GET et $_POST, et leur ajoute la date
// Note : pour hacker le contexte depuis le fichier d'appel (page.php),
// il est recommande de modifier $_GET['toto'] (meme si la page est
// appelee avec la methode POST).
//
// http://doc.spip.org/@calculer_contexte
function calculer_contexte() {
	$contexte = array();
	foreach($_GET as $var => $val) {
		if (strpos($var, 'var_') !== 0)
			$contexte[$var] = $val;
	}
	foreach($_POST as $var => $val) {
		if (strpos($var, 'var_') !== 0)
			$contexte[$var] = $val;
	}

	if (($a = _request('date')) !== null)
		$contexte['date'] = $contexte['date_redac'] = normaliser_date($a);
	else
		$contexte['date'] = $contexte['date_redac'] = date("Y-m-d H:i:s");

	return $contexte;
}


// http://doc.spip.org/@echapper_php_callback
function echapper_php_callback($r) {
	static $src = array();
	static $dst = array();

	// si on recoit un tableau, on est en mode echappement
	// on enregistre le code a echapper dans dst, et le code echappe dans src
	if (is_array($r)) {
		$dst[] = $r[0];
		return $src[] = '___'.md5($r[0]).'___';
	}

	// si on recoit une chaine, on est en mode remplacement
	$r = str_replace($src, $dst, $r);
	$src = $dst = array(); // raz de la memoire
	return $r;
}

// http://doc.spip.org/@analyse_resultat_skel
function analyse_resultat_skel($nom, $cache, $corps, $source='') {
	$headers = array();

	// Recupere les < ?php header('Xx: y'); ? > pour $page['headers']
	// note: on essaie d'attrapper aussi certains de ces entetes codes
	// "a la main" dans les squelettes, mais evidemment sans exhaustivite
	if (preg_match_all(
	'/(<[?]php\s+)@?header\s*\(\s*.([^:]*):\s*([^)]*)[^)]\s*\)\s*[;]?\s*[?]>/ims',
	$corps, $regs, PREG_SET_ORDER))
	foreach ($regs as $r) {
		$corps = str_replace($r[0], '', $corps);
		# $j = Content-Type, et pas content-TYPE.
		$j = join('-', array_map('ucwords', explode('-', strtolower($r[2]))));
		$headers[$j] = $r[3];
	}

	// S'agit-il d'un resultat constant ou contenant du code php
	$process_ins = (
		strpos($corps,'<'.'?') === false
		OR strpos(str_replace('<'.'?xml', '', $corps),'<'.'?') === false
	)
		? 'html'
		: 'php';

	// traiter #FILTRE{} ?
	if (isset($headers['X-Spip-Filtre'])
	AND strlen($headers['X-Spip-Filtre'])) {
		// proteger les <INCLUDE> et tous les morceaux de php
		if ($process_ins == 'php')
			$corps = preg_replace_callback(',<[?](\s|php|=).*[?]>,UimsS',
				echapper_php_callback, $corps);
		foreach (explode('|', $headers['X-Spip-Filtre']) as $filtre) {
			$corps = appliquer_filtre($corps, $filtre);
		}
		// restaurer les echappements
		$corps = echapper_php_callback($corps);
		unset($headers['X-Spip-Filtre']);
	}

	return array('texte' => $corps,
		'squelette' => $nom,
		'source' => $source,
		'process_ins' => $process_ins,
		'invalideurs' => $cache,
		'entetes' => $headers,
		'duree' => isset($headers['X-Spip-Cache']) ? intval($headers['X-Spip-Cache']) : 0 
	);
}

// Calcul de la rubrique associee a la requete
// (selection de squelette specifique par id_rubrique & lang)

// http://doc.spip.org/@quete_rubrique_fond
function quete_rubrique_fond($contexte) {

	if (isset($contexte['id_rubrique'])
	AND $id = intval($contexte['id_rubrique'])
	AND $row = sql_fetsel('id_parent, lang', 'spip_rubriques',"id_rubrique=$id")) {
		$lang = isset($row['lang']) ? $row['lang'] : '';
		return array ($id, $lang);
	}

	if (isset($contexte['id_breve'])
	AND $id = intval($contexte['id_breve'])
	AND $row = sql_fetsel('id_rubrique, lang', 'spip_breves', "id_breve=$id")
	AND $id_rubrique_fond = $row['id_rubrique']) {
		$lang = isset($row['lang']) ? $row['lang'] : '';
		return array($id_rubrique_fond, $lang);
	}

	if (isset($contexte['id_syndic'])
	AND $id = intval($contexte['id_syndic'])
	AND $row = sql_fetsel('id_rubrique', 'spip_syndic', "id_syndic=$id")
	AND $id_rubrique_fond = $row['id_rubrique']
	AND $row = sql_fetsel('id_parent, lang', 'spip_rubriques', "id_rubrique=$id_rubrique_fond")) {
		$lang = isset($row['lang']) ? $row['lang'] : '';
		return array($id_rubrique_fond, $lang);
	}

	if (isset($contexte['id_article'])
	AND $id = intval($contexte['id_article'])
	AND $row = sql_fetsel('id_rubrique, lang', 'spip_articles', "id_article=$id")
	AND $id_rubrique_fond = $row['id_rubrique']) {
		$lang = isset($row['lang']) ? $row['lang'] : '';
		return array($id_rubrique_fond, $lang);
	}
}

# retourne le chapeau d'un article, et seulement s'il est publie

// http://doc.spip.org/@quete_chapo
function quete_chapo($id_article, $connect) {
	return sql_getfetsel('chapo', 'spip_articles', array("id_article=".intval($id_article), "statut='publie'"), '','','','',$connect);
}

# retourne le parent d'une rubrique

// http://doc.spip.org/@quete_parent
function quete_parent($id_rubrique, $connect='') {
	if (!$id_rubrique = intval($id_rubrique))
		return 0;

	return intval(sql_getfetsel('id_parent','spip_rubriques',"id_rubrique=" . $id_rubrique, '','','','',$connect));
}

# retourne la profondeur d'une rubrique

// http://doc.spip.org/@quete_profondeur
function quete_profondeur($id, $connect='') {
	$n = 0;
	while ($id) {
		$n++;
		$id = quete_parent($id, $connect);
	}
	return $n;
}

# retourne la rubrique d'un article

// http://doc.spip.org/@quete_rubrique
function quete_rubrique($id_article, $serveur) {
	return sql_getfetsel('id_rubrique', 'spip_articles',"id_article=" . intval($id_article),	'',array(), '', '', $serveur);
}

# retourne le fichier d'un document

// http://doc.spip.org/@quete_fichier
function quete_fichier($id_document, $serveur) {
	return sql_getfetsel('fichier', 'spip_documents', ("id_document=" . intval($id_document)),	'',array(), '', '', $serveur);
}

// http://doc.spip.org/@quete_petitions
function quete_petitions($id_article, $table, $id_boucle, $serveur, &$cache) {
	$retour = sql_getfetsel('texte', 'spip_petitions',("id_article=".intval($id_article)),'',array(),'','', $serveur);

	if ($retour === NULL) return '';
	# cette page est invalidee par toute petition
	$cache['varia']['pet'.$id_article] = 1;
	# ne pas retourner '' car le texte sert aussi de presence
	return $retour ? $retour : ' ';
}

# retourne le champ 'accepter_forum' d'un article
// http://doc.spip.org/@quete_accepter_forum
function quete_accepter_forum($id_article) {
	// si la fonction est appelee en dehors d'une boucle
	// article (forum de breves), $id_article est nul
	// mais il faut neanmoins accepter l'affichage du forum
	// d'ou le 0=>'' (et pas 0=>'non').
	static $cache = array(0 => '');
	
	$id_article = intval($id_article);

	if (isset($cache[$id_article]))	return $cache[$id_article];

	return $cache[$id_article] = sql_getfetsel('accepter_forum','spip_articles',"id_article=$id_article");
}

// recuperer une meta sur un site distant (en local il y a plus simple)
// http://doc.spip.org/@quete_meta
function quete_meta($nom, $serveur) {
	return sql_getfetsel("valeur", "spip_meta", "nom=" . sql_quote($nom),
			     '','','','',$serveur);
}


# Determine les parametres d'URL (hors reecriture) et consorts
# En deduit un contexte disant si la page est une redirection ou 
# exige un squelette deductible de $fond et du contexte de langue.
# Applique alors le squelette sur le contexte et le nom du cache.
# Retourne un tableau ainsi construit
# 'texte' => la page calculee
# 'process_ins' => 'html' ou 'php' si presence d'un '< ?php'
# 'invalideurs' => les invalideurs de ce cache
# 'entetes' => headers http
# 'duree' => duree de vie du cache
# 'signal' => contexte (les id_* globales)

# En cas d'erreur process_ins est absent et texte est un tableau de 2 chaines

// http://doc.spip.org/@public_parametrer_dist
function public_parametrer_dist($fond, $local='', $cache='', $connect='')  {
	// verifier que la fonction assembler est bien chargee (cf. #608)
	$assembler = charger_fonction('assembler', 'public');
	// et toujours charger les fonctions de generation d'URL.
	if ($GLOBALS['type_urls'] == 'page'
	AND $GLOBALS['meta']['type_urls'])
		$GLOBALS['type_urls'] = $GLOBALS['meta']['type_urls'];
	$renommer_urls= charger_fonction($GLOBALS['type_urls'], 'urls', true);
	// distinguer le premier appel des appels par inclusion
	if (!is_array($local)) {
		include_spip('inc/filtres'); // pour normaliser_date

		// ATTENTION, gestion des URLs transformee par le htaccess
		// en appelant la fonction $renommee_urls
		// 1. $contexte est global car cette fonction le modifie.
		// 2. $fond est passe par reference, pour la meme raison
		// Bref,  les URL dites propres ont une implementation sale.
		// Interdit de nettoyer, faut assumer l'histoire.
		$GLOBALS['contexte'] = calculer_contexte();
		if (!$renommer_urls) {
			// compatibilite <= 1.9.2
			charger_generer_url();
			if (function_exists('recuperer_parametres_url'))
				$renommer_urls = 'recuperer_parametres_url';
		}
		if ($renommer_urls)
			$renommer_urls($fond, nettoyer_uri());

		$local = $GLOBALS['contexte'];

		// si le champ chapo commence par '=' c'est une redirection.
		// avec un eventuel raccourci Spip
		// si le raccourci a un titre il sera pris comme corps du 302
		if ($fond == 'article'
		AND $id_article = intval($local['id_article'])) {
			$m = quete_chapo($id_article, $connect);
			if ($m[0]=='=') {
				include_spip('inc/texte');
				// les navigateurs pataugent si l'URL est vide
				if ($url = chapo_redirige(substr($m,1), true))
					return array('texte' => "<"
				. "?php header('Location: "
				. texte_script(str_replace('&amp;', '&', $url))
				. "'); echo '"
				.  addslashes($m[1])
				. "'?" . ">",
					'process_ins' => 'php');
			}
		}
	}

	// Choisir entre $fond-dist.html, $fond=7.html, etc?
	$id_rubrique_fond = 0;
	// Chercher le fond qui va servir de squelette
	if ($r = quete_rubrique_fond($local))
		list($id_rubrique_fond, $lang) = $r;

	// Si inc-urls ou un appel dynamique veut fixer la langue, la recuperer
	if (isset($local['lang']))
		$lang = $local['lang'];

	if (!isset($lang))
		$lang = $GLOBALS['meta']['langue_site'];
	$select = ((!isset($GLOBALS['forcer_lang']) OR !$GLOBALS['forcer_lang']) AND $lang <> $GLOBALS['spip_lang']);
	if ($select) $select = lang_select($lang);

	$styliser = charger_fonction('styliser', 'public');
	list($skel,$mime_type, $gram, $sourcefile) =
		$styliser($fond, $id_rubrique_fond, $GLOBALS['spip_lang'], $connect);

	$debug = (isset($GLOBALS['var_mode']) && ($GLOBALS['var_mode'] == 'debug'));
	// sauver le nom de l'eventuel squelette en cours d'execution
	// (recursion possible a cause des modeles)
	$courant = $debug ? @$GLOBALS['debug_objets']['courant'] : '';

	//  si pas deja en memoire (INCLURE  a repetition),
	// charger le squelette en specifiant les langages cibles et source
	// au cas il faudrait le compiler (source posterieure au resultat)

	$fonc = calculer_nom_fonction_squel($skel, $mime_type, $connect);

	if (!function_exists($fonc)) {

		if ($debug) {
			$GLOBALS['debug_objets']['contexte'][$sourcefile] = $local;
			$GLOBALS['debug_objets']['courant'] = $fonc;
		}
		$composer = charger_fonction('composer', 'public');
		if (!$composer($skel, $fonc, $gram, $sourcefile, $connect))
			$fonc = false;
	}

	// Appliquer le squelette compile' sur le contexte.
	// Passer le nom du cache pour produire sa destruction automatique

	if ($fonc) {
		spip_timer($a = 'calcul page '.rand(0,1000));
		$notes = calculer_notes(); // conserver les notes...

		$page = $fonc(array('cache' => $cache), array($local));

		// ... et les retablir
		if ($n = calculer_notes()) spip_log("notes ignorees par $fonc: $n");
		$GLOBALS['les_notes'] = $notes;

		// spip_log: un joli contexte
		$infos = array();
		foreach (array_filter($local) as $var => $val) {
			if (is_array($val)) $val = "[".join($val)."]";
			if (strlen("$val") > 20)
				$val = substr("$val", 0,17).'..';
			if (strstr($val,' '))
				$val = "'$val'";
			$infos[] = $var.'='.$val;
		}
		$profile = spip_timer($a);
		spip_log("calcul ($profile) [$skel] "
			. join(', ', $infos)
			.' ('.strlen($page['texte']).' octets)');

		if ($debug) {
			include_spip('public/debug');
			debug_dumpfile (strlen($page['texte'])?$page['texte']:" ", $fonc, 'resultat');
			$GLOBALS['debug_objets']['courant'] = $courant;
			$GLOBALS['debug_objets']['profile'][$sourcefile] = $profile;
		}
		// Si #CACHE{} n'etait pas la, le mettre a $delais
		if (!isset($page['entetes']['X-Spip-Cache']))
			$page['entetes']['X-Spip-Cache'] = isset($GLOBALS['delais'])?$GLOBALS['delais']:36000;

	} else
		$page = array();

	$page['contexte'] = $local;

	if ($select) lang_select();

	// Si un modele contenait #SESSION, on note l'info dans $page
	if (isset($GLOBALS['cache_utilise_session'])) {
		$page['invalideurs']['session'] = $GLOBALS['cache_utilise_session'];
		unset($GLOBALS['cache_utilise_session']);
	}

	return $page;
}

// calcul du nom du squelette
// http://doc.spip.org/@calculer_nom_fonction_squel
function calculer_nom_fonction_squel($skel, $mime_type='html', $connect='')
{
	return $mime_type
	. (!$connect ?  '' : preg_replace('/\W/',"_", $connect)) . '_'
	. md5($GLOBALS['spip_version_code'] . ' * ' . $skel);
}
?>
