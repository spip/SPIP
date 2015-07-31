<?php


// Ce fichier ne sera execute qu'une fois
if (defined("_INC_CALCUL")) return;
define("_INC_CALCUL", "1");

//
// Ce fichier calcule une page en executant un squelette.
//

include_ecrire("inc_meta.php3");
include_ecrire("inc_index.php3");
include_ecrire("inc_texte.php3");
include_ecrire("inc_filtres.php3");
include_ecrire("inc_lang.php3");
include_ecrire("inc_documents.php3");
include_ecrire("inc_abstract_sql.php3");
include_ecrire("inc_forum.php3");
include_ecrire("inc_debug_sql.php3");
include_local("inc-calcul-outils.php3");

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
		$GLOBALS['var_debug']
		OR !@file_exists($skel)
		OR (@filemtime($squelette) > ($date = @filemtime($skel)))
		OR (@filemtime('mes_fonctions.php3') > $date)
		OR (@filemtime(_FILE_OPTIONS) > $date)
	);
}


# Charge un squelette (au besoin le compile) 
# et retoune le nom de sa fonction principale, ou '' s'il est indefini
# Charge egalement un fichier homonyme de celui du squelette
# mais de suffixe '_fonctions.php3' pouvant contenir:
# - des filtres
# - des fonctions de traduction de balise, de critere et de boucle
# - des declaration de tables SQL supplementaires

function charger_squelette ($squelette) {
	$ext = $GLOBALS['extension_squelette'];
	$nom = $ext . '_' . md5($squelette);
	$sourcefile = $squelette . ".$ext";

	// le squelette est-il deja en memoire (<inclure> a repetition)
	if (function_exists($nom))
		return $nom;

	$phpfile = _DIR_CACHE . 'skel_' . $nom . '.php';

	// le squelette est-il deja compile et perenne ?
	if (!squelette_obsolete($phpfile, $sourcefile)
	AND lire_fichier ($phpfile, $contenu,
	array('critique' => 'oui', 'phpcheck' => 'oui'))) 
		eval('?'.'>'.$contenu);

	// sinon, charger le compilateur et verifier que le source est lisible
	if (!function_exists($nom)) {
		include_local("inc-compilo.php3");
		lire_fichier ($sourcefile, $skel);
	}

	// Le fichier suivant peut contenir entre autres:
	// 1. les filtres utilises par le squelette
	// 2. des ajouts au compilateur
	// Le point 1 exige qu'il soit lu dans tous les cas.
	// Le point 2 exige qu'il soit lu apres inc-compilo
	// (car celui-ci initialise $tables_principales) mais avant la compil
	$f = $squelette . '_fonctions.php3';
	if (@file_exists($f)) include($f);

	if (function_exists($nom)) return $nom;

	$skel_code = calculer_squelette($skel, $nom, $ext, $sourcefile);

	// Tester si le compilateur renvoie une erreur
	if (is_array($skel_code))
		erreur_squelette($skel_code[0], $skel_code[1]);
	else {
		if ($GLOBALS['var_debug'] == 'oui') {
			include_ecrire("inc_debug_sql.php3");
			debug_dumpfile ($skel_code, $nom, 'code');
		}
		eval('?'.'>'.$skel_code);
		if (function_exists($nom)) {
			ecrire_fichier ($phpfile, $skel_code);
			return $nom;
		}
	}
}

# Provoque la recherche du squelette $fond d'une $lang donnee,
# et l'applique sur un $contexte pour un certain $cache.
# Retourne un tableau de 3 elements:
# 'texte' => la page calculee
# 'process_ins' => 'html' ou 'php' si presence d'un '< ?php'
# 'invalideurs' => les invalideurs (cf inc-calcul-squel)

# En cas d'erreur process_ins est absent et texte est un tableau de 2 chaines

# La recherche est assuree par la fonction chercher_squelette,
# definie dans inc-chercher, fichier non charge si elle est deja definie
# (typiquement dans mes_fonctions.php3)

function cherche_page ($cache, $contexte, $fond)  {
	global $dossier_squelettes, $delais;

	if (!function_exists('chercher_squelette'))
		include_local("inc-chercher.php3"); # a renommer ?

	// Choisir entre $fond-dist.html, $fond=7.html, etc?
	$id_rubrique_fond = 0;
	// Si inc-urls veut fixer la langue, on la recupere ici
	$lang = $contexte['lang'];
	// Chercher le fond qui va servir de squelette
	if ($r = sql_rubrique_fond($contexte,
	$lang ? $lang : lire_meta('langue_site')))
		list($id_rubrique_fond, $lang) = $r;

	if (!$GLOBALS['forcer_lang'])
		lang_select($lang);

	$skel = chercher_squelette($fond,
			$id_rubrique_fond,
			$dossier_squelettes ? "$dossier_squelettes/" :'',
			$GLOBALS['spip_lang']);

	// Charger le squelette et recuperer sa fonction principale
	// (compilation automatique au besoin) et calculer

	$page = array();

	if ($skel) {
		if ($fonc = charger_squelette($skel))
		  $page = $fonc(array('cache' => $cache), array($contexte));

		// Passer la main au debuggueur)
		if ($GLOBALS['var_debug'] == 'oui')
		  {
			include_ecrire("inc_debug_sql.php3");
			debug_dumpfile ($page['texte'], $fonc, 'resultat');
		  }
	}

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

// Contexte: les parametres HHTP sauf ceux ajoutés par spip + la globale date
function calculer_contexte() {
	foreach($GLOBALS['HTTP_GET_VARS'] as $var => $val) {
		if (strpos($var, 'var_') !== 0)
			$contexte[$var] = $val;
	}
	foreach($GLOBALS['HTTP_POST_VARS'] as $var => $val) {
		if (strpos($var, 'var_') !== 0)
			$contexte[$var] = $val;
	}

	if ($GLOBALS['date'])
		$contexte['date'] = $contexte['date_redac'] = normaliser_date($GLOBALS['date']);
	else
		$contexte['date'] = $contexte['date_redac'] = date("Y-m-d H:i:s");

	return $contexte;
}

function calculer_page_globale($cache, $contexte_local, $fond) {

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

	// Go to work !
	$page = cherche_page($cache, $contexte_local, $fond);

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
			$contexte_inclus, $elements['fond']);
	}
	else {

		// Page globale
		// si le champ chapo commence par '=' c'est une redirection.
		if ($id_article = intval($GLOBALS['id_article'])) {
			if ($art = sql_chapo($id_article)) {
				$chapo = $art['chapo'];
				if (substr($chapo, 0, 1) == '=') {
					include_ecrire('inc_texte.php3');
					list(,$url) = extraire_lien(array('','','',
					substr($chapo, 1)));
					if ($url) { // sinon les navigateurs pataugent
						$url = addslashes($url);
						$page = array('texte' => "<".
						"?php header(\"Location: $url\"); ?" . ">",
						'process_ins' => 'php');
					}
				}
			}
		}
		if (!$page)
			$page = calculer_page_globale($chemin_cache,
				$elements['contexte'],
				$elements['fond']);
	}

	$page['signal']['process_ins'] = $page['process_ins'];
	$signal = "<!-- ".str_replace("\n", " ",
	serialize($page['signal']))." -->\n";

	// Enregistrer le fichier cache
	if ($delais > 0 AND $GLOBALS['var_debug'] != 'oui'
	AND empty($GLOBALS['HTTP_POST_VARS']))
		ecrire_fichier($chemin_cache, $signal.$page['texte']);

	return $page;
}
?>
