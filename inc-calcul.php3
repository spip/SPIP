<?php


// Ce fichier ne sera execute qu'une fois
if (defined("_INC_CALCUL")) return;
define("_INC_CALCUL", "1");

//
// Ce fichier calcule une page en executant un squelette.
//

include_ecrire("inc_index.php3");
include_ecrire("inc_texte.php3");
include_ecrire("inc_filtres.php3");
include_ecrire("inc_lang.php3");
include_ecrire("inc_documents.php3");
include_local("inc-forum.php3");
include_local("inc-calcul-outils.php3");

#include_local("inc-calcul_html");	# anciens noms des fichiers
#include_local("inc-calcul_mysql");


// Ce fichier peut contenir une affectation de $dossier_squelettes  indiquant
// le repertoire du source des squelettes (les pseudo-html avec <BOUCLE...)

if (@file_exists("mes_fonctions.php3")) 
    include_local ("mes_fonctions.php3");


// Gestionnaire d'URLs
if (@file_exists("inc-urls.php3"))
	include_local("inc-urls.php3");
else
	include_local("inc-urls-".$GLOBALS['type_urls'].".php3");


// Le squelette compile est-il trop vieux ?
function squelette_obsolete($skel, $squelette) {
	return (
		($GLOBALS['recalcul'] == 'oui')
		OR !@file_exists($skel)
		OR (@filemtime($squelette) > ($date = @filemtime($skel)))
		OR (@filemtime('mes_fonctions.php3') > $date)
		OR (@filemtime('ecrire/mes_options.php3') > $date)
	);
}


// Charge un squelette (en demande au besoin la compilation)
function charger_squelette ($squelette) {
	$ext = $GLOBALS['extension_squelette'];
	$nom = $ext . '_' . md5($squelette);
	$sourcefile = $squelette . ".$ext";

	// le squelette est-il deja en memoire (<inclure> a repetition)
	if (function_exists($nom)) return $nom;

	$phpfile = 'CACHE/skel_' . $nom . '.php';

	// le squelette est-il deja compile, lisible, etc ?
	if (!squelette_obsolete($phpfile, $sourcefile)
	      AND lire_fichier ($phpfile, $contenu,
				array('critique' => 'oui', 'phpcheck' => 'oui'))) 
		eval('?'.'>'.$contenu);
	if (!function_exists($nom)) {
		// sinon charger le compilateur et tester le source
		include_local("inc-compilo.php3");
		lire_fichier ($sourcefile, $skel);
	}

	// ce fichier peut contenir deux sortes de choses:
	// 1. les filtres utilises par le squelette
	// 2. d'eventuels ajouts a $tables_principales
	// Le point 1 exige qu'il soit lu dans tous les cas.
	// Le point 2 exige qu'il soit lu apres inc-compilo
	// (car celui-ci initialise $tables_principales) mais avant la compil

	$f = $squelette . '_fonctions.php3';
	if (file_exists($f)) include($f);

	if (function_exists($nom))  return $nom;
	$skel_code = calculer_squelette($skel, $nom, $ext, $sourcefile);
	// Tester si le compilateur renvoie une erreur

	if (is_array($skel_code)) 
		{
			erreur_squelette($skel_code[0], $skel_code[1]) ; 
			$skel_compile = '';
			$skel_code = '';
		  }
	else
		$skel_compile = "<"."?php\n" . $skel_code ."\n?".">";

	// Parler au debugguer
	if ($GLOBALS['var_debug'] AND 
	    $GLOBALS['debug_objet'] == $nom
	    AND $GLOBALS['debug_affiche'] == 'code')
		debug_dumpfile ($skel_compile);
		
		// Evaluer le squelette
	eval($skel_code);
	if (function_exists($nom)) {
		ecrire_fichier ($phpfile, $skel_compile);
		return $nom;
	}

		// en cas d'erreur afficher les boutons de debug
	echo "<hr /><h2>".
		_L("Erreur dans la compilation du squelette").
		" $sourcefile</h2>" .
		$GLOBALS['bouton_admin_debug'] = true;
		debug_dumpfile ($skel_compile);
}

# Provoque la recherche du squelette $fond d'une $lang donnee,
# et l'applique sur un $contexte pour un certain $cache.
# Retourne un tableau de 3 elements:
# 'texte' => la page calculee
# 'process_ins' => 'html' ou 'php' si presence d'un '< ?php'
# 'invalideurs' => les invalideurs (cf inc-calcul-squel)

# En cas d'erreur process_ins est absent et texte est un tableau de 2 chaines

# La recherche est assuree par la fonction cherche_squelette
# definie dans inc-chercher, fichier non charge s'il existe un fichier
# mon-chercher dans $dossier_squelettes ou dans le rep principal de Spip,
# pour charger une autre definition de cette fonction.

# L'execution est precedee du chargement eventuel d'un fichier homonyme
# de celui du squelette mais d'extension .php  pouvant contenir:
# - des filtres
# - des fonctions de traduction de balise (cf inc-index-squel)

function cherche_page ($cache, $contexte, $fond, $id_rubrique, $lang='')  {
	global $dossier_squelettes, $delais;

	/*
	$dir = "$dossier_squelettes/mon-chercher.php3";
	if (file_exists($dir)) {
		include($dir);
		} else  */ { 
		include_local("inc-chercher.php3"); # a renommer
	 }

	// Choisir entre $fond-dist.html, $fond=7.html, etc?
	$skel = chercher_squelette($fond,
		$id_rubrique,
		$dossier_squelettes ? "$dossier_squelettes/" :'',
		$lang
	);

	// Charger le squelette et recuperer sa fonction principale
	// (compilation automatique au besoin)

	$fonc = charger_squelette($skel);

	// Calculer la page a partir du main() du skel compile
	$page =  $fonc(array('cache' => $cache), array($contexte));

	// Passer la main au debuggueur)
	if ($GLOBALS['var_debug'] AND $GLOBALS['debug_objet'] == $fonc
	AND $GLOBALS['debug_affiche'] == 'resultat') {
		debug_dumpfile ($page['texte']);
	}

	# flag pour spip_error_handler(), cf inc-admin ??
	$page['squelette'] = $skel;

	// Nettoyer le resultat si on est fou de XML
	if ($GLOBALS['xhtml']) {
		include_ecrire("inc_tidy.php");
		$page['texte'] = xhtml($page['texte']);
	}

	// Entrer les invalideurs dans la base
	if ($delais>0) {
		include_ecrire('inc_invalideur.php3');
		maj_invalideurs($cache, $page['invalideurs'], $delais);
	}

	// Retourner la structure de la page
	return $page;
}

// Etablit le contexte initial a partir des globales
function calculer_contexte() {
	foreach($GLOBALS['HTTP_GET_VARS'] as $var => $val) {
		if (!eregi("^(recalcul|submit|var_.*)$", $var))
			$contexte[$var] = $val;
	}
	foreach($GLOBALS['HTTP_POST_VARS'] as $var => $val) {
		if (!eregi("^(recalcul|submit|var_.*)$", $var))
			$contexte[$var] = $val;
	}

	if ($GLOBALS['date'])
		$contexte['date'] = $contexte['date_redac'] = normaliser_date($GLOBALS['date']);
	else
		$contexte['date'] = $contexte['date_redac'] = date("Y-m-d H:i:s");

	return $contexte;
}

function calculer_page_globale($cache, $contexte_local, $fond) {
	global $spip_lang;

	// Gestion des URLs personnalises - sale mais historique
	if (function_exists("recuperer_parametres_url")) {
		global $contexte;
		$contexte = $contexte_local;
		recuperer_parametres_url($fond, nettoyer_uri());

		// remettre les globales pour le bouton "Modifier cet article"
		if (is_array($contexte))
			foreach ($contexte as $var=>$val)
				if (substr($var,0,3) == 'id_')
					$GLOBALS[$var] = $val;
		$contexte_local = $contexte;
	}

	$id_rubrique_fond = 0;

	// Si inc-urls veut fixer la langue, se baser ici
	$lang = $contexte_local['lang'];

	// Chercher le fond qui va servir de squelette
	if ($r = sql_rubrique_fond($contexte_local,
	$lang ? $lang : lire_meta('langue_site')))
		list($id_rubrique_fond, $lang) = $r;

	if (!$GLOBALS['forcer_lang'])
		lang_select($lang);

	// Go to work !
	$page = cherche_page($cache, $contexte_local, $fond, $id_rubrique_fond, $spip_lang);

	$signal = array();
	foreach(array('id_parent', 'id_rubrique', 'id_article', 'id_auteur',
	'id_breve', 'id_forum', 'id_secteur', 'id_syndic', 'id_syndic_article',
	'id_mot', 'id_groupe', 'id_document') as $val) {
		if ($contexte_local[$val])
			$signal['contexte'][$val] = intval($contexte_local[$val]);
	}

	$page['signal'] = $signal;

	return $page;
}


// Cf ramener_page +cherche_page_incluante+ cherche_page_incluse chez ESJ
function calculer_page($chemin_cache, $elements, $delais, $inclusion=false) {

	// Inclusion
	if ($inclusion) {
		$contexte_inclus = $elements['contexte'];
		$page = cherche_page($chemin_cache,
			$contexte_inclus,
			$elements['fond'],
			$contexte_inclus['id_rubrique']
		);
	}
	else {

		// Page globale
		// si le champ chapo commence par '=' c'est une redirection.
		if ($id_article = intval($GLOBALS['id_article'])) {
			$page = sql_chapo($id_article);
			if ($page) {
				$page = $page['chapo'];
				if (substr($page, 0, 1) == '=') {
					include_ecrire('inc_texte.php3');
					list(,$page) = extraire_lien(array('','','',
					substr($page, 1)));
					if ($page) { // sinon les navigateurs pataugent
						$page = addslashes($page);
						return array('texte' =>
						("<". "?php header(\"Location: $page\"); ?" . ">"),
						'process_ins' => 'php');
					}
				}
			}
		}
		$page = calculer_page_globale($chemin_cache,
			$elements['contexte'],
			$elements['fond']);
	}

	$page['signal']['process_ins'] = $page['process_ins'];
	$signal = "<!-- ".str_replace("\n", " ",
	serialize($page['signal']))." -->\n";

	// Enregistrer le fichier cache
	if ($delais > 0 AND !$GLOBALS['var_debug']
	AND empty($GLOBALS['HTTP_POST_VARS']))
		ecrire_fichier($chemin_cache, $signal.$page['texte']);

	return $page;
}

// Cette fonction est systematiquement appelee par les squelettes
// pour constuire une requete SQL de type "lecture" (SELECT) a partir
// de chaque boucle.
// Elle construit et exe'cute une reque^te SQL correspondant a` une balise
// Boucle ; elle notifie une erreur SQL dans le flux de sortie et termine
// le processus.
// Sinon, retourne la ressource interrogeable par spip_abstract_fetch.
// Recoit en argument:
// - le tableau des champs a` ramener
// - le tableau des tables a` consulter
// - le tableau des conditions a` remplir
// - le crite`re de regroupement
// - le crite`re de classement
// - le crite`re de limite
// - une sous-requete e'ventuelle (MySQL > 4.1)
// - un compteur de sous-requete
// - le nom de la table
// - le nom de la boucle (pour le message d'erreur e'ventuel)
// - le serveur sollicite

function spip_abstract_select (
	$select = array(), $from = array(), $where = '',
	$groupby = '', $orderby = '', $limit = '',
	$sousrequete = '', $cpt = '',
	$table = '', $id = '', $serveur='') {

	if (!$serveur)
	  // le serveur par defaut est celui de inc_connect.php
	  // tout est deja pret, notamment la fonction suivante:
	  $f = 'spip_mysql_select';
	else {
	  // c'est un autre; est-il deja charge ?
		$f = 'spip_' . $serveur . '_select';
		if (!function_exists($f)) {
		  // non, il est decrit dans le fichier ad hoc
			$d = 'inc_connect-' . $serveur .'.php3';
			if (file_exists('ecrire/' . $d))
				include_ecrire($d);
			serveur_defini($f, $serveur);
		}
	}
	return $f($select, $from, $where,
		  $groupby, $orderby, $limit,
		  $sousrequete, $cpt,
		  $table, $id, $serveur);
}

function serveur_defini($f, $serveur) {
  if (function_exists($f)) return $f;
  include_local("inc-admin.php3");
  erreur_squelette(_T('info_erreur_squelette'),
		   $serveur . 
		   _L(' serveur SQL indefini'));
}

// Les 3 fonctions suivantes exploitent le resultat de la precedente,
// si l'include ne les a pas definies, erreur immediate

function spip_abstract_fetch($res, $serveur='')
{
  if (!$serveur) return spip_fetch_array($res);
  $f = serveur_defini('spip_' . $serveur . '_fetch', $serveur);
  return $f($res);
}

function spip_abstract_count($res, $serveur='')
{
  if (!$serveur) return spip_num_rows($res);
  $f = serveur_defini('spip_' . $serveur . '_count', $serveur);
  return $f($res);
}

function spip_abstract_free($res, $serveur='')
{
  if (!$serveur) return spip_free_result($res);
  $f = serveur_defini('spip_' . $serveur . '_free', $serveur);
  return $f($res);
}

# une composition tellement fréquente...

function spip_abstract_fetsel(
	$select = array(), $from = array(), $where = '',
	$groupby = '', $orderby = '', $limit = '',
	$sousrequete = '', $cpt = '',
	$table = '', $id = '', $serveur='') {
	return spip_abstract_fetch(spip_abstract_select(
$select, $from, $where,	$groupby, $orderby, $limit,
$sousrequete, $cpt, $table, $id, $serveur),
				   $serveur);
}
?>
