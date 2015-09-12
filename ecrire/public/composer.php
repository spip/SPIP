<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2015                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Compose un squelette : compile le squelette au besoin et vérifie
 * la validité du code compilé
 * 
 * @package SPIP\Core\Compilateur\Composer
**/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/texte');
include_spip('inc/documents');
include_spip('inc/distant');
include_spip('inc/rubriques'); # pour calcul_branche (cf critere branche)
include_spip('inc/acces'); // Gestion des acces pour ical
include_spip('inc/actions');
include_spip('public/iterateur');
include_spip('public/interfaces');
include_spip('public/quete');

# Charge et retourne un composeur ou '' s'il est inconnu. Le compile au besoin
# Charge egalement un fichier homonyme de celui du squelette
# mais de suffixe '_fonctions.php' pouvant contenir:
# 1. des filtres
# 2. des fonctions de traduction de balise, de critere et de boucle
# 3. des declaration de tables SQL supplementaires
# Toutefois pour 2. et 3. preferer la technique de la surcharge

// http://code.spip.net/@public_composer_dist
function public_composer_dist($squelette, $mime_type, $gram, $source, $connect='') {

	$nom = calculer_nom_fonction_squel($squelette, $mime_type, $connect);

	//  si deja en memoire (INCLURE  a repetition) c'est bon.
	if (function_exists($nom)) return $nom;

	if (defined('_VAR_MODE') AND _VAR_MODE == 'debug')
		$GLOBALS['debug_objets']['courant'] = $nom;

	$phpfile = sous_repertoire(_DIR_SKELS,'',false,true) . $nom . '.php';

	// si squelette est deja compile et perenne, le charger
	if (!squelette_obsolete($phpfile, $source)){
		include_once $phpfile;
		#if (!squelette_obsolete($phpfile, $source)
		#  AND lire_fichier ($phpfile, $skel_code,
		#  array('critique' => 'oui', 'phpcheck' => 'oui'))){
		## eval('?'.'>'.$skel_code);
		#	 spip_log($skel_code, 'comp')
		#}
	}

	if (file_exists($lib = $squelette . '_fonctions'.'.php')){
		include_once $lib;
	}

	// tester si le eval ci-dessus a mis le squelette en memoire

	if (function_exists($nom)) return $nom;

	// charger le source, si possible, et compiler 
	if (lire_fichier ($source, $skel)) {
		$compiler = charger_fonction('compiler', 'public');
		$skel_code = $compiler($skel, $nom, $gram, $source, $connect);
	}
	else spip_log ("Impossible de lire le fichier de squelette '$source'");

	// Ne plus rien faire si le compilateur n'a pas pu operer.
	if (!isset($skel_code) or !$skel_code) return false;

	foreach($skel_code as $id => $boucle) {
		$f = $boucle->return;
		if (@eval("return true; $f ;") ===  false) {
		// Code syntaxiquement faux (critere etc mal programme')
			$msg = _T('zbug_erreur_compilation');
			erreur_squelette($msg, $boucle);
			// continuer pour trouver d'autres fautes eventuelles
			// mais prevenir que c'est mort
			$nom = '';
		} 
		// Contexte de compil inutile a present
		// (mais la derniere valeur de $boucle est utilisee ci-dessous)
		$skel_code[$id] = $f;
	}

	$code = '';
	if ($nom) {
		// Si le code est bon, concatener et mettre en cache
		if (function_exists($nom))
			$code = squelette_traduit($skel, $source, $phpfile, $skel_code);
		else {
		// code semantiquement faux: bug du compilateur
		// $boucle est en fait ici la fct principale du squelette
			$msg = _T('zbug_erreur_compilation');
			erreur_squelette($msg, $boucle);
			$nom = '';
		}
	}

	if (defined('_VAR_MODE') AND _VAR_MODE == 'debug') {

		// Tracer ce qui vient d'etre compile
		$GLOBALS['debug_objets']['code'][$nom . 'tout'] = $code;

		// si c'est ce que demande le debusqueur, lui passer la main
		if ($GLOBALS['debug_objets']['sourcefile']
		AND (_request('var_mode_objet') == $nom)
		AND (_request('var_mode_affiche') == 'code')  )
			erreur_squelette();
	}
	return $nom ? $nom : false;
}

function squelette_traduit($squelette, $sourcefile, $phpfile, $boucles)
{

	// Le dernier index est '' (fonction principale)
	$noms = substr(join (', ', array_keys($boucles)), 0, -2);
	if (CODE_COMMENTE)
	$code = "
/*
 * Squelette : $sourcefile
 * Date :      ".gmdate("D, d M Y H:i:s", @filemtime($sourcefile))." GMT
 * Compile :   ".gmdate("D, d M Y H:i:s", time())." GMT
 * " . (!$boucles ?  "Pas de boucle" :	("Boucles :   " . $noms)) ."
 */ " ;

	$code = '<'. "?php\n" . $code . join('', $boucles)  . "\n?" .'>';
	if (!defined('_VAR_NOCACHE') OR !_VAR_NOCACHE)
		ecrire_fichier($phpfile, $code);
	return $code;
}

// Le squelette compile est-il trop vieux ?
// http://code.spip.net/@squelette_obsolete
function squelette_obsolete($skel, $squelette) {
	static $date_change = null;
	// ne verifier la date de mes_fonctions et mes_options qu'une seule fois
	// par hit
	if (is_null($date_change)){
		if (@file_exists($fonc = 'mes_fonctions.php'))
			$date_change = @filemtime($fonc); # compatibilite
		if (defined('_FILE_OPTIONS'))
			$date_change = max($date_change,@filemtime(_FILE_OPTIONS));
	}
	return (
		(defined('_VAR_MODE') AND in_array(_VAR_MODE, array('recalcul','preview','debug')))
		OR !@file_exists($skel)
		OR ((@file_exists($squelette)?@filemtime($squelette):0)
			> ($date = @filemtime($skel)))
		OR ($date_change > $date)
	);
}

// Activer l'invalideur de session
// http://code.spip.net/@invalideur_session
function invalideur_session(&$Cache, $code=NULL) {
	$Cache['session']=spip_session();
	return $code;
}


// http://code.spip.net/@analyse_resultat_skel
function analyse_resultat_skel($nom, $cache, $corps, $source='') {
	static $filtres = array();
	$headers = array();

	// Recupere les < ?php header('Xx: y'); ? > pour $page['headers']
	// note: on essaie d'attrapper aussi certains de ces entetes codes
	// "a la main" dans les squelettes, mais evidemment sans exhaustivite
	if (stripos($corps,'header')!==false
		AND preg_match_all(
	'/(<[?]php\s+)@?header\s*\(\s*.([^:\'"]*):?\s*([^)]*)[^)]\s*\)\s*[;]?\s*[?]>/ims',
	$corps, $regs, PREG_SET_ORDER)){
		foreach ($regs as $r) {
			$corps = str_replace($r[0], '', $corps);
			# $j = Content-Type, et pas content-TYPE.
			$j = join('-', array_map('ucwords', explode('-', strtolower($r[2]))));

			if ($j=='X-Spip-Filtre' AND isset($headers[$j]))
				$headers[$j].="|".$r[3];
			else
				$headers[$j] = $r[3];
		}
	}
	// S'agit-il d'un resultat constant ou contenant du code php
	$process_ins = (
		strpos($corps,'<'.'?') === false
		OR
		 (strpos($corps,'<'.'?xml')!==false AND
		  strpos(str_replace('<'.'?xml', '', $corps),'<'.'?') === false)
	)
		? 'html'
		: 'php';

	$skel = array(
		'squelette' => $nom,
		'source' => $source,
		'process_ins' => $process_ins,
		'invalideurs' => $cache,
		'entetes' => $headers,
		'duree' => isset($headers['X-Spip-Cache']) ? intval($headers['X-Spip-Cache']) : 0
	);

	// traiter #FILTRE{} et filtres
  if (!isset($filtres[$nom])) {
	  $filtres[$nom] = pipeline('declarer_filtres_squelettes',array('args'=>$skel,'data'=>array()));
  }
	if (count($filtres[$nom]) OR (isset($headers['X-Spip-Filtre']) AND strlen($headers['X-Spip-Filtre']))) {
		include_spip('public/sandbox');
		$corps = sandbox_filtrer_squelette($skel,$corps,strlen($headers['X-Spip-Filtre'])?explode('|', $headers['X-Spip-Filtre']):array(),$filtres[$nom]);
		unset($headers['X-Spip-Filtre']);

		if ($process_ins=='html'){
			$skel['process_ins'] = (
				strpos($corps,'<'.'?') === false
				OR
				 (strpos($corps,'<'.'?xml')!==false AND
				  strpos(str_replace('<'.'?xml', '', $corps),'<'.'?') === false)
			)
				? 'html'
				: 'php';
		}
	}

	$skel['entetes'] = $headers;
	$skel['texte'] = $corps;

	return $skel;
}

//
// Des fonctions diverses utilisees lors du calcul d'une page ; ces fonctions
// bien pratiques n'ont guere de logique organisationnelle ; elles sont
// appelees par certaines balises au moment du calcul des pages. (Peut-on
// trouver un modele de donnees qui les associe physiquement au fichier
// definissant leur balise ???
//


/**
 * Calcul d'une introduction
 *
 * L'introduction est prise dans le descriptif s'il est renseigné,
 * sinon elle est calculée depuis le texte : à ce moment là,
 * l'introduction est prise dans le contenu entre les balises
 * `<intro>` et `</intro>` si présentes, sinon en coupant le
 * texte à la taille indiquée.
 * 
 * Cette fonction est utilisée par la balise #INTRODUCTION
 *
 * @param string $descriptif
 *     Descriptif de l'introduction
 * @param string $texte
 *     Texte à utiliser en absence de descriptif
 * @param string $longueur
 *     Longueur de l'introduction
 * @param string $connect
 *     Nom du connecteur à la base de données
 * @param string $suite
 *     points de suite si on coupe (par defaut _INTRODUCTION_SUITE et sinon &nbsp;(...)
 * @return string
 *     Introduction calculée
**/
function filtre_introduction_dist($descriptif, $texte, $longueur, $connect, $suite=null) {
	// Si un descriptif est envoye, on l'utilise directement
	if (strlen($descriptif))
		return appliquer_traitement_champ($descriptif,'introduction','',array(),$connect);

	// De preference ce qui est marque <intro>...</intro>
	$intro = '';
	$texte = preg_replace(",(</?)intro>,i", "\\1intro>", $texte); // minuscules
	while ($fin = strpos($texte, "</intro>")) {
		$zone = substr($texte, 0, $fin);
		$texte = substr($texte, $fin + strlen("</intro>"));
		if ($deb = strpos($zone, "<intro>") OR substr($zone, 0, 7) == "<intro>")
			$zone = substr($zone, $deb + 7);
		$intro .= $zone;
	}

	// [12025] On ne *PEUT* pas couper simplement ici car c'est du texte brut,
	// qui inclus raccourcis et modeles
	// un simple <articlexx> peut etre ensuite transforme en 1000 lignes ...
	// par ailleurs le nettoyage des raccourcis ne tient pas compte
	// des surcharges et enrichissement de propre
	// couper doit se faire apres propre
	//$texte = nettoyer_raccourcis_typo($intro ? $intro : $texte, $connect);

	// Cependant pour des questions de perfs on coupe quand meme, en prenant
	// large et en se mefiant des tableaux #1323

	if (strlen($intro))
		$texte = $intro;

	else
	if (strpos("\n".$texte, "\n|")===false
	  AND strlen($texte) > 2.5*$longueur){
		if (strpos($texte,"<multi")!==false)
			$texte = extraire_multi($texte);
		$texte = couper($texte, 2*$longueur);
	}

	// ne pas tenir compte des notes
	if ($notes = charger_fonction('notes', 'inc', true))
		$notes('','empiler');
	// Supprimer les modèles avant le propre afin d'éviter qu'ils n'ajoutent du texte indésirable
	// dans l'introduction.
	$texte = supprime_img($texte, '');
	$texte = appliquer_traitement_champ($texte,'introduction','',array(),$connect);

	if ($notes)
		$notes('','depiler');

	if (is_null($suite)) {
		$suite = (defined('_INTRODUCTION_SUITE')?_INTRODUCTION_SUITE:'&nbsp;(...)');
	}
	$texte = couper($texte, $longueur, $suite);
	// comme on a coupe il faut repasser la typo (on a perdu les insecables)
	$texte = typo($texte,true,$connect,array());

	// et reparagrapher si necessaire (coherence avec le cas descriptif)
	// une introduction a tojours un <p>
	if ($GLOBALS['toujours_paragrapher'])
		// Fermer les paragraphes
		$texte = paragrapher($texte, $GLOBALS['toujours_paragrapher']);

	return $texte;
}

//
// Balises dynamiques
//

/** Code PHP pour inclure une balise dynamique à l'exécution d'une page */
define('CODE_INCLURE_BALISE', '<' . '?php 
include_once("%s");
if ($lang_select = "%s") $lang_select = lang_select($lang_select);
inserer_balise_dynamique(balise_%s_dyn(%s), array(%s));
if ($lang_select) lang_select();
?'
       .'>');

/**
 * Synthétise une balise dynamique : crée l'appel à l'inclusion
 * en transmettant les arguments calculés et le contexte de compilation.
 *
 * @uses argumenter_squelette() Pour calculer les arguments de l'inclusion
 * 
 * @param string $nom
 *     Nom de la balise dynamique
 * @param array $args
 *     Liste des arguments calculés
 * @param string $file
 *     Chemin du fichier de squelette à inclure
 * @param array $context_compil
 *     Tableau d'informations sur la compilation
 * @return string
 *     Code PHP pour inclure le squelette de la balise dynamique
**/
function synthetiser_balise_dynamique($nom, $args, $file, $context_compil) {
	if (strncmp($file,"/",1)!==0){
		$file = './" . _DIR_RACINE . "'.$file;
	}
	$r = sprintf(CODE_INCLURE_BALISE,
	       $file,
	       $context_compil[4]?$context_compil[4]:'',
	       $nom,
	       join(', ', array_map('argumenter_squelette', $args)),
	       join(', ', array_map('_q', $context_compil)));
	return $r;
}

/**
 * Crée le code PHP pour transmettre des arguments (généralement pour une inclusion)
 *
 * @param array|string $v
 *     Arguments à transmettre :
 *
 *    - string : un simple texte à faire écrire
 *    - array : couples ('nom' => 'valeur') liste des arguments et leur valeur
 * @return string
 *
 *    - Code PHP créant le tableau des arguments à transmettre,
 *    - ou texte entre quote `'` (si `$v` était une chaîne)
**/
function argumenter_squelette($v) {

	if (!is_array($v))
		return "'" . texte_script($v) . "'";
	else {
		$out = array();
		foreach($v as $k=>$val) 
			$out [] = argumenter_squelette($k) . '=>' . argumenter_squelette($val);
	  return 'array(' . join(", ", $out) . ')';
	}
}


/**
 * Calcule et retourne le code PHP retourné par l'exécution d'une balise
 * dynamique.
 *
 * Vérifier les arguments et filtres et calcule le code PHP à inclure.
 *
 * - charge le fichier PHP de la balise dynamique dans le répertoire
 *   `balise/`, soit du nom complet de la balise, soit d'un nom générique
 *    (comme 'formulaire_.php'). Dans ce dernier cas, le nom de la balise
 *    est ajouté en premier argument.
 * - appelle une éventuelle fonction de traitement des arguments `balise_NOM_stat()`
 * - crée le code PHP de la balise si une fonction `balise_NOM_dyn()` (ou variantes)
 *   est effectivement trouvée.
 *
 * @uses synthetiser_balise_dynamique()
 *     Pour calculer le code PHP d'inclusion produit
 *
 * @param string $nom
 *     Nom de la balise dynamique
 * @param array $args
 *     Liste des arguments calculés de la balise
 * @param array $context_compil
 *     Tableau d'informations sur la compilation
 * @return string
 *     Code PHP d'exécutant l'inclusion du squelette (ou texte) de la balise dynamique
**/
function executer_balise_dynamique($nom, $args, $context_compil) {
	$nomfonction = $nom;
	$nomfonction_generique = "";

	// Calculer un nom générique (ie. 'formulaire_' dans 'formulaire_editer_article')
	if (false !== ($p = strpos($nom, "_"))) {
		$nomfonction_generique = substr($nom, 0, $p+1);
	}

	if (!$fonction_balise = charger_fonction($nomfonction, 'balise', true)) {
		if ($nomfonction_generique and $fonction_balise = charger_fonction($nomfonction_generique, 'balise', true)) {
			// et injecter en premier arg le nom de la balise 
			array_unshift($args, $nom);
			$nomfonction = $nomfonction_generique;
		}
	}

	if (!$fonction_balise) {
		$msg = array('zbug_balise_inexistante',array('from'=>'CVT', 'balise'=>$nom));
		erreur_squelette($msg, $context_compil);
		return '';
	}

	// retrouver le fichier qui a déclaré la fonction
	// même si la fonction dynamique est déclarée dans un fichier de fonctions.
	// Attention sous windows, getFileName() retourne un antislash. 
	$reflector = new ReflectionFunction($fonction_balise);
	$file = str_replace('\\', '/', $reflector->getFileName());
	if (strncmp($file, str_replace('\\', '/', _ROOT_RACINE), strlen(_ROOT_RACINE)) === 0) {
		$file = substr($file, strlen(_ROOT_RACINE));
	}

	// Y a-t-il une fonction de traitement des arguments ?
	$f = 'balise_' . $nomfonction . '_stat';

	$r = !function_exists($f) ? $args : $f($args, $context_compil); 

	if (!is_array($r)) return $r;

	// verifier que la fonction dyn est la, 
	// sinon se replier sur la generique si elle existe
	if (!function_exists('balise_' . $nomfonction . '_dyn')) {
		if ($nomfonction_generique
			AND $file = include_spip("balise/" .strtolower($nomfonction_generique))
			AND function_exists('balise_' . $nomfonction_generique . '_dyn')) {
			// et lui injecter en premier arg le nom de la balise 
			array_unshift($r, $nom);
			$nomfonction = $nomfonction_generique;
			if (!_DIR_RESTREINT) {
				$file = _DIR_RESTREINT_ABS . $file;
			}
		} else {
			$msg = array('zbug_balise_inexistante',array('from'=>'CVT', 'balise'=>$nom));
			erreur_squelette($msg, $context_compil);
			return '';
		}
	}

	return synthetiser_balise_dynamique($nomfonction, $r, $file, $context_compil);

}

/**
 * Retourne pour une clé primaire d'objet donnée les identifiants ayant un logo
 *
 * @uses type_du_logo() Pour calculer le nom du logo
 * 
 * @param string $type
 *     Nom de la clé primaire de l'objet
 * @return string
 *     Liste des identifiants ayant un logo (séparés par une virgule)
**/
function lister_objets_avec_logos ($type) {
	global $formats_logos;
	$logos = array();
	$chercher_logo = charger_fonction('chercher_logo', 'inc');
	$type = '/'
	. type_du_logo($type)
	. "on(\d+)\.("
	. join('|',$formats_logos)
	. ")$/";

	if ($d = @opendir(_DIR_LOGOS)) {
		while(($f = readdir($d))!==false) {
			if (preg_match($type, $f, $r))
				$logos[] = $r[1];
		}
	}
	@closedir($d);
	return join(',',$logos);
}


/**
 * Renvoie l'état courant des notes, le purge et en prépare un nouveau
 *
 * Fonction appelée par la balise `#NOTES`
 * 
 * @see balise_NOTES_dist()
 * @uses inc_notes_dist()
 *
 * @return string
 *     Code HTML des notes
**/
function calculer_notes() {
	$r='';
	if ($notes = charger_fonction('notes', 'inc', true)) {
		$r = $notes(array());
		$notes('','depiler');
		$notes('','empiler');
	}
	return $r;
}

/**
 * Selectionner la langue de l'objet dans la boucle
 *
 * Applique sur un item de boucle la langue de l'élément qui est parcourru.
 * Sauf dans les cas ou il ne le faut pas !
 * 
 * La langue n'est pas modifiée lorsque :
 * - la globale 'forcer_lang' est définie à true
 * - l'objet ne définit pas de langue
 * - le titre contient une balise multi.
 *
 * @param string $lang
 *     Langue de l'objet
 * @param string $lang_select
 *     'oui' si critère lang_select est présent, '' sinon.
 * @param null|string $titre
 *     Titre de l'objet
 * @return null;
**/
function lang_select_public($lang, $lang_select, $titre=null) {
	// Cas 1. forcer_lang = true et pas de critere {lang_select}
	if (isset($GLOBALS['forcer_lang']) AND $GLOBALS['forcer_lang']
	AND $lang_select !== 'oui')
		$lang = $GLOBALS['spip_lang'];

	// Cas 2. l'objet n'a pas de langue definie (ou definie a '')
	elseif (!strlen($lang))
		$lang = $GLOBALS['spip_lang'];

	// Cas 3. l'objet est multilingue !
	elseif ($lang_select !== 'oui'
	  AND strlen($titre) > 10
	  AND strpos($titre, '<multi>') !== false
	  AND strpos(echappe_html($titre), '<multi>') !== false)
		$lang = $GLOBALS['spip_lang'];

	// faire un lang_select() eventuellement sur la langue inchangee
	lang_select($lang);

	return;
}


// Si un tableau &doublons[articles] est passe en parametre,
// il faut le nettoyer car il pourrait etre injecte en SQL
// http://code.spip.net/@nettoyer_env_doublons
function nettoyer_env_doublons($envd) {
	foreach ($envd as $table => $liste) {
		$n = '';
		foreach(explode(',',$liste) as $val) {
			if ($a = intval($val) AND $val === strval($a))
				$n.= ','.$val;
		}
		if (strlen($n))
			$envd[$table] = $n;
		else
			unset($envd[$table]);
	}
	return $envd;
}

/**
 * Cherche la présence d'un opérateur SELF ou SUBSELECT
 *
 * Cherche dans l'index 0 d'un tableau, la valeur SELF ou SUBSELECT
 * indiquant pour une expression WHERE de boucle que nous sommes
 * face à une sous-requête.
 *
 * Cherche de manière récursive également dans les autres valeurs si celles-ci
 * sont des tableaux
 *
 * @param string|array $w
 *     Description d'une condition WHERE de boucle (ou une partie de cette description)
 * @return string|bool
 *     Opérateur trouvé (SELF ou SUBSELECT) sinon false.
**/
function match_self($w){
	if (is_string($w)) return false;
	if (is_array($w)) {
		if (in_array(reset($w),array("SELF","SUBSELECT"))) return $w;
		foreach(array_filter($w,'is_array') as $sw)
			if ($m=match_self($sw)) return $m;
	}
	return false;
}

/**
 * Remplace une condition décrivant une sous requête par son code 
 *
 * @param array|string $w
 *     Description d'une condition WHERE de boucle (ou une partie de cette description)
 *     qui possède une description de sous-requête
 * @param string $sousrequete
 *     Code PHP de la sous requête (qui doit remplacer la description)
 * @return array|string
 *     Tableau de description du WHERE dont la description de sous-requête
 *     est remplacée par son code.
**/
function remplace_sous_requete($w,$sousrequete){
	if (is_array($w)) {
		if (in_array(reset($w),array("SELF","SUBSELECT"))) return $sousrequete;
		foreach($w as $k=>$sw)
			$w[$k] = remplace_sous_requete($sw,$sousrequete);
	}
	return $w;
}

/**
 * Sépare les conditions de boucles simples de celles possédant des sous-requêtes.
 *
 * @param array $where
 *     Description d'une condition WHERE de boucle
 * @return array
 *     Liste de 2 tableaux :
 *     - Conditions simples (ne possédant pas de sous requêtes)
 *     - Conditions avec des sous requêtes
**/
function trouver_sous_requetes($where){
	$where_simples = array();
	$where_sous = array();
	foreach($where as $k=>$w){
		if (match_self($w)) $where_sous[$k] = $w;
		else $where_simples[$k] = $w;
	}
	return array($where_simples,$where_sous);
}


/**
 * Calcule une requête et l’exécute
 * 
 * Cette fonction est présente dans les squelettes compilés.
 * Elle peut permettre de générer des requêtes avec jointure.
 *
 * @param array $select
 * @param array $from
 * @param array $from_type
 * @param array $where
 * @param array $join
 * @param array $groupby
 * @param array $orderby
 * @param string $limit
 * @param array $having
 * @param string $table
 * @param string $id
 * @param string $serveur
 * @param bool $requeter
 * @return resource
 */
function calculer_select ($select = array(), $from = array(), 
			$from_type = array(),
      $where = array(), $join=array(),
			$groupby = array(), $orderby = array(), $limit = '',
			$having=array(), $table = '', $id = '', $serveur='', $requeter=true) {

	// retirer les criteres vides:
	// {X ?} avec X absent de l'URL
	// {par #ENV{X}} avec X absent de l'URL
	// IN sur collection vide (ce dernier devrait pouvoir etre fait a la compil)
	$menage = false;
	foreach($where as $k => $v) { 
		if (is_array($v)){
			if ((count($v)>=2) && ($v[0]=='REGEXP') && ($v[2]=="'.*'")) $op= false;
			elseif ((count($v)>=2) && ($v[0]=='LIKE') && ($v[2]=="'%'")) $op= false;
			else $op = $v[0] ? $v[0] : $v;
		} else $op = $v;
		if ((!$op) OR ($op==1) OR ($op=='0=0')) {
			unset($where[$k]);
			$menage = true;
		}
	}

	// evacuer les eventuels groupby vide issus d'un calcul dynamique
	$groupby = array_diff($groupby,array(''));

	// remplacer les sous requetes recursives au calcul
	list($where_simples,$where_sous) = trouver_sous_requetes($where);
	foreach($where_sous as $k=>$w) {
		$menage = true;
		// on recupere la sous requete 
		$sous = match_self($w);
		if ($sous[0]=='SELF') {
			// c'est une sous requete identique a elle meme sous la forme (SELF,$select,$where)
			array_push($where_simples,$sous[2]);
			$wheresub = array($sous[2],'0=0'); // pour accepter une string et forcer a faire le menage car on a surement simplifie select et where
			$jsub = $join;
			// trouver les jointures utiles a
			// reinjecter dans le where de la sous requete les conditions supplementaires des jointures qui y sont mentionnees
			// ie L1.objet='article'
			// on construit le where une fois, puis on ajoute les where complentaires si besoin, et on reconstruit le where en fonction
			$i = 0;
			do {
				$where[$k] = remplace_sous_requete($w,"(".calculer_select(
				array($sous[1]." AS id"),
				$from,
				$from_type,
				$wheresub,
				$jsub,
				array(),array(),'',
				$having,$table,$id,$serveur,false).")");
				if (!$i){
					$i = 1;
					$wherestring = calculer_where_to_string($where[$k]);
					foreach ($join as $cle=>$wj){
						if (count($wj)==4
							AND strpos($wherestring,"{$cle}.")!==FALSE
						){
							$i = 0;
							$wheresub[] = $wj[3];
							unset($jsub[$cle][3]);
						}
					}
				}
			} while ($i++<1);
		}
		if ($sous[0]=='SUBSELECT') {
			// c'est une sous requete explicite sous la forme identique a sql_select : (SUBSELECT,$select,$from,$where,$groupby,$orderby,$limit,$having)
			array_push($where_simples,$sous[3]); // est-ce utile dans ce cas ?
			$where[$k] = remplace_sous_requete($w,"(".calculer_select(
			$sous[1], # select
			$sous[2], #from
			array(), #from_type
			$sous[3]?(is_array($sous[3])?$sous[3]:array($sous[3])):array(), #where, qui peut etre de la forme string comme dans sql_select
			array(), #join
			$sous[4]?$sous[4]:array(), #groupby
			$sous[5]?$sous[5]:array(), #orderby
			$sous[6], #limit
			$sous[7]?$sous[7]:array(), #having
			$table,$id,$serveur,false
			).")");
		}
		array_pop($where_simples);
	}

	foreach($having as $k => $v) { 
		if ((!$v) OR ($v==1) OR ($v=='0=0')) {
			unset($having[$k]);
		}
	}

	// Installer les jointures.
	// Retirer celles seulement utiles aux criteres finalement absents mais
	// parcourir de la plus recente a la moins recente pour pouvoir eliminer Ln
	// si elle est seulement utile a Ln+1 elle meme inutile
	
	$afrom = array();
	$equiv = array();
	$k = count($join);
	foreach(array_reverse($join,true) as $cledef=>$j) {
		$cle = $cledef;
		// le format de join est :
		// array(table depart, cle depart [,cle arrivee[,condition optionnelle and ...]])
		$join[$cle] = array_values($join[$cle]); // recalculer les cles car des unset ont pu perturber
		if (count($join[$cle])==2) $join[$cle][] = $join[$cle][1];
		if (count($join[$cle])==3) $join[$cle][] = '';
		list($t,$c,$carr,$and) = $join[$cle];
		// si le nom de la jointure n'a pas ete specifiee, on prend Lx avec x sont rang dans la liste
		// pour compat avec ancienne convention
		if (is_numeric($cle))
			$cle = "L$k";
		if (!$menage
		OR isset($afrom[$cle])
		OR calculer_jointnul($cle, $select)
		OR calculer_jointnul($cle, array_diff_key($join, array($cle=>$join[$cle])))
		OR calculer_jointnul($cle, $having)
		OR calculer_jointnul($cle, $where_simples)) {
			// corriger les references non explicites dans select
			// ou groupby
			foreach($select as $i=>$s) {
				if ($s == $c) {
					$select[$i] = "$cle.$c AS $c";
					break;
				}
			}
			foreach($groupby as $i=>$g) {
				if ($g == $c) {
					$groupby[$i] = "$cle.$c";
					break;
				}
			}
			// on garde une ecriture decomposee pour permettre une simplification ulterieure si besoin
			// sans recours a preg_match
			// un implode(' ',..) est fait dans reinjecte_joint un peu plus bas
			$afrom[$t][$cle] = array("\n" .
				(isset($from_type[$cle])?$from_type[$cle]:"INNER")." JOIN",
				$from[$cle],
				"AS $cle",
				"ON (",
				"$cle.$c",
				"=",
				"$t.$carr",
				($and ? "AND ". $and:"") .
				")");
			if (isset($afrom[$cle])){
				$afrom[$t] = $afrom[$t] + $afrom[$cle];
				unset($afrom[$cle]);
			}
			$equiv[]= $carr;
		} else { unset($join[$cledef]);}
		unset($from[$cle]);
		$k--;
	}

	if (count($afrom)) {
		// Regarder si la table principale ne sert finalement a rien comme dans
		//<BOUCLE3(MOTS){id_article}{id_mot}> class='on'</BOUCLE3>
		//<BOUCLE2(MOTS){id_article} />#TOTAL_BOUCLE<//B2>
		//<BOUCLE5(RUBRIQUES){id_mot}{tout} />#TOTAL_BOUCLE<//B5>
		// ou dans
		//<BOUCLE8(HIERARCHIE){id_rubrique}{tout}{type='Squelette'}{inverse}{0,1}{lang_select=non} />#TOTAL_BOUCLE<//B8>
		// qui comporte plusieurs jointures
		// ou dans
		// <BOUCLE6(ARTICLES){id_mot=2}{statut==.*} />#TOTAL_BOUCLE<//B6>
		// <BOUCLE7(ARTICLES){id_mot>0}{statut?} />#TOTAL_BOUCLE<//B7>
		// penser a regarder aussi la clause orderby pour ne pas simplifier abusivement
		// <BOUCLE9(ARTICLES){recherche truc}{par titre}>#ID_ARTICLE</BOUCLE9>
		// penser a regarder aussi la clause groubpy pour ne pas simplifier abusivement
		// <BOUCLE10(EVENEMENTS){id_rubrique} />#TOTAL_BOUCLE<//B10>
		
	  list($t,$c) = each($from);
	  reset($from);
	  $e = '/\b(' . "$t\\." . join("|" . $t . '\.', $equiv) . ')\b/';
	  if (!(strpos($t, ' ') OR // jointure des le depart cf boucle_doc
		 calculer_jointnul($t, $select, $e) OR
		 calculer_jointnul($t, $join, $e) OR
		 calculer_jointnul($t, $where, $e) OR
		 calculer_jointnul($t, $orderby, $e) OR
		 calculer_jointnul($t, $groupby, $e) OR
		 calculer_jointnul($t, $having, $e))
		 && count($afrom[$t])) {
		 	reset($afrom[$t]);
		 	list($nt,$nfrom) = each($afrom[$t]);
	    unset($from[$t]);
	    $from[$nt] = $nfrom[1];
	    unset($afrom[$t][$nt]);
	    $afrom[$nt] = $afrom[$t];
	    unset($afrom[$t]);
	    $e = '/\b'.preg_quote($nfrom[6]).'\b/';
	    $t = $nfrom[4];
	    $alias = "";
	    // verifier que les deux cles sont homonymes, sinon installer un alias dans le select
	    $oldcle = explode('.',$nfrom[6]);
	    $oldcle = end($oldcle);
	    $newcle = explode('.',$nfrom[4]);
	    $newcle = end($newcle);
	    if ($newcle!=$oldcle){
		    // si l'ancienne cle etait deja dans le select avec un AS
		    // reprendre simplement ce AS
		    $as = '/\b'.preg_quote($nfrom[6]).'\s+(AS\s+\w+)\b/';
		    if (preg_match($as,implode(',',$select),$m)){
			    $alias = "";
		    }
		    else
					$alias = ", ".$nfrom[4]." AS $oldcle";
	    }
	    $select = remplacer_jointnul($t . $alias, $select, $e);
	    $join = remplacer_jointnul($t, $join, $e);
	    $where = remplacer_jointnul($t, $where, $e);
	    $having = remplacer_jointnul($t, $having, $e);
	    $groupby = remplacer_jointnul($t, $groupby, $e);
	    $orderby = remplacer_jointnul($t, $orderby, $e);
	  }
	  $from = reinjecte_joint($afrom, $from);
	}
	$GLOBALS['debug']['aucasou'] = array ($table, $id, $serveur, $requeter);
	$r = sql_select($select, $from, $where,
		$groupby, array_filter($orderby), $limit, $having, $serveur, $requeter);
	unset($GLOBALS['debug']['aucasou']);
	return $r;
}

/**
 * Analogue a calculer_mysql_expression et autre (a unifier ?)
 * @param string|array $v
 * @param string $join
 * @return string
 */
function calculer_where_to_string($v, $join = 'AND'){
	if (empty($v))
		return '';

	if (!is_array($v)) {
		return $v;
	} else {
		$exp = "";
		if (strtoupper($join) === 'AND')
			return $exp . join(" $join ", array_map('calculer_where_to_string', $v));
		else
			return $exp . join($join, $v);
	}
}


//condition suffisante (mais non necessaire) pour qu'une table soit utile

// http://code.spip.net/@calculer_jointnul
function calculer_jointnul($cle, $exp, $equiv='')
{
	if (!is_array($exp)) {
		if ($equiv) $exp = preg_replace($equiv, '', $exp);
		return preg_match("/\\b$cle\\./", $exp);
	} else {
		foreach($exp as $v) {
			if (calculer_jointnul($cle, $v, $equiv)) return true;
		}
		return false;
	}
}

// http://code.spip.net/@reinjecte_joint
function reinjecte_joint($afrom, $from)
{
	  $from_synth = array();
	  foreach($from as $k=>$v){
	  	$from_synth[$k]=$from[$k];
	  	if (isset($afrom[$k])) {
	  		foreach($afrom[$k] as $kk=>$vv) $afrom[$k][$kk] = implode(' ',$afrom[$k][$kk]);
	  		$from_synth["$k@"]= implode(' ',$afrom[$k]);
	  		unset($afrom[$k]);
	  	}
	  }
	  return $from_synth;
}

// http://code.spip.net/@remplacer_jointnul
function remplacer_jointnul($cle, $exp, $equiv='')
{
	if (!is_array($exp)) {
		return preg_replace($equiv, $cle, $exp);
	} else {
		foreach($exp as $k => $v) {
		  $exp[$k] = remplacer_jointnul($cle, $v, $equiv);
		}
		return $exp;
	}
}

// calcul du nom du squelette
// http://code.spip.net/@calculer_nom_fonction_squel
function calculer_nom_fonction_squel($skel, $mime_type='html', $connect='')
{
	// ne pas doublonner les squelette selon qu'ils sont calcules depuis ecrire/ ou depuis la racine
	if ($l=strlen(_DIR_RACINE) AND strncmp($skel,_DIR_RACINE,$l)==0)
		$skel = substr($skel,strlen(_DIR_RACINE));
	return $mime_type
	. (!$connect ?  '' : preg_replace('/\W/',"_", $connect)) . '_'
	. md5($GLOBALS['spip_version_code'] . ' * ' . $skel . (isset($GLOBALS['marqueur_skel'])?'*'.$GLOBALS['marqueur_skel']:''));
}

?>
