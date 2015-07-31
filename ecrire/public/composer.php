<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/texte');
include_spip('inc/documents');
include_spip('inc/distant');
include_spip('inc/rubriques'); # pour calcul_branche (cf critere branche)
include_spip('inc/acces'); // Gestion des acces pour ical
include_spip('public/interfaces');
include_spip('public/quete');

# Charge et retourne un composeur ou '' s'il est inconnu. Le compile au besoin
# Charge egalement un fichier homonyme de celui du squelette
# mais de suffixe '_fonctions.php' pouvant contenir:
# 1. des filtres
# 2. des fonctions de traduction de balise, de critere et de boucle
# 3. des declaration de tables SQL supplementaires
# Toutefois pour 2. et 3. preferer la technique de la surcharge

// http://doc.spip.org/@public_composer_dist
function public_composer_dist($squelette, $mime_type, $gram, $source, $connect='') {

	$nom = calculer_nom_fonction_squel($squelette, $mime_type, $connect);

	//  si deja en memoire (INCLURE  a repetition) c'est bon.

	if (function_exists($nom)) return array($nom);

	if (isset($GLOBALS['var_mode']) && ($GLOBALS['var_mode'] == 'debug'))
		$GLOBALS['debug_objets']['courant'] = $nom;

	$phpfile = sous_repertoire(_DIR_SKELS,'',false,true) . $nom . '.php';

	// si squelette est deja compile et perenne, le charger
	if (!squelette_obsolete($phpfile, $source)
	AND lire_fichier ($phpfile, $skel_code,
	array('critique' => 'oui', 'phpcheck' => 'oui')))
		eval('?'.'>'.$skel_code);
#	spip_log($skel_code, 'comp')
	if (@file_exists($lib = $squelette . '_fonctions'.'.php'))
		include_once $lib;

	// tester si le eval ci-dessus a mis le squelette en memoire

	if (function_exists($nom)) return array($nom, $skel_code);

	// charger le source, si possible, et compiler 
	if (lire_fichier ($source, $skel)) {
		$compiler = charger_fonction('compiler', 'public');
		$skel_code = $compiler($skel, $nom, $gram, $source, $connect);
	}

	// Ne plus rien faire si le compilateur n'a pas pu operer.
	if (!$skel_code) return false;

	foreach($skel_code as $id => $boucle) {
		$f = $boucle->return;
		if (@eval("return true; $f ;") ===  false) {
		// Code syntaxiquement faux (critere etc mal programme')
			$msg = array('zbug_erreur_compilation');
			erreur_squelette($msg, $boucle);
			// continuer pour trouver d'autres fautes eventuelles
			// mais prevenir que c'est mort
			$nom = '';
		} 
		// Contexte de compil inutile a present
		// (mais la derniere valeur de $boucle est utilisee ci-dessous)
		$skel_code[$id] = $f;
	}

	if ($nom) {
		// Si le code est bon, concatener et mettre en cache
		if (function_exists($nom))
			$code = squelette_traduit($skel, $source, $phpfile, $skel_code);
		else {
		// code semantiquement faux: bug du compilateur
		// $boucle est en fait ici la fct principale du squelette
			$msg = array('zbug_erreur_compilation');
			erreur_squelette($msg, $boucle);
			$nom = '';
		}
	}

	if (isset($GLOBALS['var_mode']) AND $GLOBALS['var_mode'] == 'debug') {

		// Tracer ce qui vient d'etre compile
		$GLOBALS['debug_objets']['code'][$nom . 'tout'] = $code;

		// si c'est ce que demande le debusqueur, lui passer la main
		if ($GLOBALS['debug_objets']['sourcefile']
		AND (_request('var_mode_objet') == $nom)
		AND (_request('var_mode_affiche') == 'code')  )
			erreur_squelette();
	}
	return $nom ? array($nom, $code) : false;
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
	if (!isset($GLOBALS['var_nocache']) OR !$GLOBALS['var_nocache'])
		ecrire_fichier($phpfile, $code);
	return $code;
}

// Le squelette compile est-il trop vieux ?
// http://doc.spip.org/@squelette_obsolete
function squelette_obsolete($skel, $squelette) {
	static $date_change = null;
	// ne verifier la date de mes_fonctions et mes_options qu'une seule fois
	// par hit
	if (is_null($date_change)){
		if (@file_exists($fonc = 'mes_fonctions.php')
			OR @file_exists($fonc = 'mes_fonctions.php3'))
			$date_change = @filemtime($fonc); # compatibilite
		if (defined('_FILE_OPTIONS'))
			$date_change = max($date_change,@filemtime(_FILE_OPTIONS));
	}
	return (
		(isset($GLOBALS['var_mode']) AND in_array($GLOBALS['var_mode'], array('recalcul','preview','debug')))
		OR !@file_exists($skel)
		OR ((@file_exists($squelette)?@filemtime($squelette):0)
			> ($date = @filemtime($skel)))
		OR ($date_change > $date)
	);
}

// Activer l'invalideur de session
// http://doc.spip.org/@invalideur_session
function invalideur_session(&$Cache, $code=NULL) {
	$Cache['session']=spip_session();
	return $code;
}


//
// Des fonctions diverses utilisees lors du calcul d'une page ; ces fonctions
// bien pratiques n'ont guere de logique organisationnelle ; elles sont
// appelees par certaines balises au moment du calcul des pages. (Peut-on
// trouver un modele de donnees qui les associe physiquement au fichier
// definissant leur balise ???
//

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
	'/(<[?]php\s+)@?header\s*\(\s*.([^:\']*):?\s*([^)]*)[^)]\s*\)\s*[;]?\s*[?]>/ims',
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
				'echapper_php_callback', $corps);
		foreach (explode('|', $headers['X-Spip-Filtre']) as $filtre) {
			if ($f = chercher_filtre($filtre))
				$corps = $f($corps);
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


//
// fonction standard de calcul de la balise #INTRODUCTION
// on peut la surcharger en definissant dans mes_fonctions :
// function filtre_introduction()
//
// http://doc.spip.org/@filtre_introduction_dist
function filtre_introduction_dist($descriptif, $texte, $longueur, $connect) {
	// Si un descriptif est envoye, on l'utilise directement
	if (strlen($descriptif))
		return propre($descriptif,$connect);

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
	AND strlen($texte) > 2.5*$longueur)
		$texte = couper($texte, 2*$longueur);

	// ne pas tenir compte des notes
	$notes = charger_fonction('notes', 'inc');
	$notes('','empiler');
	$texte = propre($texte,$connect);
	$notes('','depiler');

	if (!defined('_INTRODUCTION_SUITE')) define('_INTRODUCTION_SUITE', '&nbsp;(...)');
	$texte = couper($texte, $longueur, _INTRODUCTION_SUITE);

	return $texte;
}

//
// Balises dynamiques
//

// elles sont traitees comme des inclusions
// http://doc.spip.org/@synthetiser_balise_dynamique

define('CODE_INCLURE_BALISE', '<' . '?php 
include_once("./" . _DIR_RACINE . "%s");
if ($lang_select = "%s") $lang_select = lang_select($lang_select);
inserer_balise_dynamique(balise_%s_dyn(%s), array(%s));
if ($lang_select) lang_select();
?'
       .'>');


function synthetiser_balise_dynamique($nom, $args, $file, $context_compil) {
	$r = sprintf(CODE_INCLURE_BALISE,
	       $file,
	       $context_compil[4]?$context_compil[4]:'',
	       $nom,
	       join(', ', array_map('argumenter_squelette', $args)),
	       join(', ', array_map('_q', $context_compil)));
	return $r;
}

// http://doc.spip.org/@argumenter_squelette
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

// verifier leurs arguments et filtres, et calculer le code a inclure
// http://doc.spip.org/@executer_balise_dynamique
function executer_balise_dynamique($nom, $args, $context_compil) {
	$p = strpos($nom,"_");
	$nomfonction = $nom;
	$nomfonction_generique = substr($nom,0,$p+1);
	if (!$file = include_spip("balise/". strtolower($nomfonction))) {
		// pas de fichier associe, passer au traitement generique
		$file = include_spip("balise/" .strtolower($nomfonction_generique));
		if ($file) {
			// et injecter en premier arg le nom de la balise 
			array_unshift($args,$nom);
			// et passer sur la fonction generique pour la suite
			$nomfonction = $nomfonction_generique;
		}
		else {
			$msg = array('zbug_balise_inexistante',array('from'=>'CVT','balise'=>$nom));
			erreur_squelette($msg, $context_compil);
			return '';
		}
	}
	// Y a-t-il une fonction de traitement des arguments ?
	$f = 'balise_' . $nomfonction . '_stat';

	$r = !function_exists($f) ? $args : $f($args, $context_compil); 

	if (!is_array($r)) return $r;

	// verifier que la fonction dyn est la, 
	// sinon se replier sur la generique si elle existe
	if (!function_exists('balise_' . $nomfonction . '_dyn')) {
		$file = include_spip("balise/" .strtolower($nomfonction_generique));
		if (function_exists('balise_' . $nomfonction_generique . '_dyn')) {
			// et lui injecter en premier arg le nom de la balise 
			array_unshift($r,$nom);
			$nomfonction = $nomfonction_generique;
		} else {
			$msg = array('zbug_balise_inexistante',array('from'=>'CVT','balise'=>$nom));
			erreur_squelette($msg, $context_compil);
			return '';
		}
	}

	if (!_DIR_RESTREINT) 
		$file = _DIR_RESTREINT_ABS . $file;
	return synthetiser_balise_dynamique($nomfonction, $r, $file, $context_compil);
}

// http://doc.spip.org/@lister_objets_avec_logos
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
		while($f = readdir($d)) {
			if (preg_match($type, $f, $r))
				$logos[] = $r[1];
		}
	}
	@closedir($d);
	return join(',',$logos);
}

// fonction appelee par la balise #NOTES
// Renvoyer l'etat courant des notes, le purger et en preparer un nouveau
// http://doc.spip.org/@calculer_notes
function calculer_notes() {
	$notes = charger_fonction('notes', 'inc');
	$r = $notes(array());
	$notes('','depiler');
	$notes('','empiler');
	return $r;
}

// Selectionner la langue de l'objet dans la boucle, sauf dans les
// cas ou il ne le faut pas :-)
function lang_select_public($lang, $lang_select, $titre=null) {
	// Cas 1. forcer_lang = true et pas de critere {lang_select}
	if (isset($GLOBALS['forcer_lang']) AND $GLOBALS['forcer_lang']
	AND $lang_select !== 'oui')
		return;

	// Cas 2. l'objet n'a pas de langue definie (ou definie a '')
	if (!strlen($lang))
		return;

	// Cas 3. l'objet est multilingue !
	if ($lang_select !== 'oui'
	AND strlen($titre) > 10
	AND strpos($titre, '<multi>') !== false
	AND strpos(echappe_html($titre), '<multi>') !== false)
		return;

	// Tous les cas ayant ete elimines, faire le job
	$GLOBALS['spip_lang'] = $lang;
	return;
}


// Si un tableau &doublons[articles] est passe en parametre,
// il faut le nettoyer car il pourrait etre injecte en SQL
// http://doc.spip.org/@nettoyer_env_doublons
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

// http://doc.spip.org/@match_self
function match_self($w){
	if (is_string($w)) return false;
	if (is_array($w)) {
		if (in_array(reset($w),array("SELF","SUBSELECT"))) return $w;
		foreach($w as $sw)
			if ($m=match_self($sw)) return $m;
	}
	return false;
}
// http://doc.spip.org/@remplace_sous_requete
function remplace_sous_requete($w,$sousrequete){
	if (is_array($w)) {
		if (in_array(reset($w),array("SELF","SUBSELECT"))) return $sousrequete;
		foreach($w as $k=>$sw)
			$w[$k] = remplace_sous_requete($sw,$sousrequete);
	}
	return $w;
}
// http://doc.spip.org/@trouver_sous_requetes
function trouver_sous_requetes($where){
	$where_simples = array();
	$where_sous = array();
	foreach($where as $k=>$w){
		if (match_self($w)) $where_sous[$k] = $w;
		else $where_simples[$k] = $w;
	}
	return array($where_simples,$where_sous);
}

// La fonction presente dans les squelettes compiles

// http://doc.spip.org/@calculer_select
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
	//var_dump($where_sous);
	foreach($where_sous as $k=>$w) {
		$menage = true;
		// on recupere la sous requete 
		$sous = match_self($w);
		if ($sous[0]=='SELF') {
			// c'est une sous requete identique a elle meme sous la forme (SELF,$select,$where)
			array_push($where_simples,$sous[2]);
			$where[$k] = remplace_sous_requete($w,"(".calculer_select(
			$sous[1],
			$from,
			$from_type,
			array($sous[2],'0=0'), // pour accepter une string et forcer a faire le menage car on a surement simplifie select et where
			$join,
			array(),array(),'',
			$having,$table,$id,$serveur,false).")");
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
		OR calculer_jointnul($cle, array_diff($join,array($cle=>$join[$cle])))
		OR calculer_jointnul($cle, $having)
		OR calculer_jointnul($cle, $where_simples)) {
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

//condition suffisante (mais non necessaire) pour qu'une table soit utile

// http://doc.spip.org/@calculer_jointnul
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

// http://doc.spip.org/@reinjecte_joint
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

// http://doc.spip.org/@remplacer_jointnul
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
// http://doc.spip.org/@calculer_nom_fonction_squel
function calculer_nom_fonction_squel($skel, $mime_type='html', $connect='')
{
	// ne pas doublonner les squelette selon qu'ils sont calcules depuis ecrire/ ou depuis la racine
	if (strlen(_DIR_RACINE) AND substr($skel,0,strlen(_DIR_RACINE))==_DIR_RACINE)
		$skel = substr($skel,strlen(_DIR_RACINE));
	return $mime_type
	. (!$connect ?  '' : preg_replace('/\W/',"_", $connect)) . '_'
	. md5($GLOBALS['spip_version_code'] . ' * ' . $skel);
}

?>
