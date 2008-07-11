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

include_spip('inc/texte');
include_spip('inc/documents');
include_spip('inc/forum');
include_spip('inc/distant');
include_spip('inc/rubriques'); # pour calcul_branche (cf critere branche)
include_spip('public/debug'); # toujours prevoir le pire
include_spip('public/interfaces');

# Charge et retourne un composeur, i.e. la fonction principale d'un squelette
# ou '' s'il est inconnu. Le compile au besoin
# Charge egalement un fichier homonyme de celui du squelette
# mais de suffixe '_fonctions.php' pouvant contenir:
# 1. des filtres
# 2. des fonctions de traduction de balise, de critere et de boucle
# 3. des declaration de tables SQL supplementaires
# Toutefois pour 2. et 3. preferer la technique de la surcharge

// http://doc.spip.org/@public_composer_dist
function public_composer_dist($squelette, $nom, $gram, $source, $connect) {

	$phpfile = sous_repertoire(_DIR_SKELS,'',false,true) . $nom . '.php';

	// si squelette est deja compile et perenne, le charger
	if (!squelette_obsolete($phpfile, $source)
	AND lire_fichier ($phpfile, $contenu,
	array('critique' => 'oui', 'phpcheck' => 'oui')))
		eval('?'.'>'.$contenu);
#	spip_log($contenu, 'comp')
	if (@file_exists($fonc = $squelette . '_fonctions'.'.php')
	OR @file_exists($fonc = $squelette . '_fonctions'.'.php3')) {
		include_once $fonc;
	}

	// tester si le eval ci-dessus a mis le squelette en memoire

	if (function_exists($nom)) return $nom;

	// charger le source, si possible, et compiler 
	if (lire_fichier ($source, $skel)) {
		$compiler = charger_fonction('compiler', 'public');
		$skel_code = $compiler($skel, $nom, $gram, $source, $connect);
	}

	// Tester si le compilateur renvoie une erreur
	if (is_array($skel_code))
		erreur_squelette($skel_code[0], $skel_code[1]);
	else {
		if (isset($GLOBALS['var_mode']) AND $GLOBALS['var_mode'] == 'debug') {
			debug_dumpfile ($skel_code, $nom, 'code');
		}
		eval('?'.'>'.$skel_code);
		if (function_exists($nom)) {
			ecrire_fichier ($phpfile, $skel_code);
			return $nom;
		} else {
			erreur_squelette(_T('zbug_erreur_compilation'), $source);
		}
	}
}

// Le squelette compile est-il trop vieux ?
// http://doc.spip.org/@squelette_obsolete
function squelette_obsolete($skel, $squelette) {
	return (
		(isset($GLOBALS['var_mode']) AND in_array($GLOBALS['var_mode'], array('recalcul','preview','debug')))
		OR !@file_exists($skel)
		OR ((@file_exists($squelette)?@filemtime($squelette):0)
			> ($date = @filemtime($skel)))
		OR (
			(@file_exists($fonc = 'mes_fonctions.php')
			OR @file_exists($fonc = 'mes_fonctions.php3'))
			AND @filemtime($fonc) > $date) # compatibilite
		OR (defined('_FILE_OPTIONS') AND @filemtime(_FILE_OPTIONS) > $date)
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

// Pour les documents comme pour les logos, le filtre |fichier donne
// le chemin du fichier apres 'IMG/' ;  peut-etre pas d'une purete
// remarquable, mais a conserver pour compatibilite ascendante.
// -> http://www.spip.net/fr_article901.html


// Renvoie le code html pour afficher un logo, avec ou sans survol, lien, etc.

// http://doc.spip.org/@affiche_logos
function affiche_logos($logos, $lien, $align) {

	list ($arton, $artoff) = $logos;

	if (!$arton) return $artoff;

	if ($taille = @getimagesize($arton)) {
		$taille = " ".$taille[3];
	}

	if ($artoff)
		$artoff = " onmouseover=\"this.src='$artoff'\" "
			."onmouseout=\"this.src='$arton'\"";

	$milieu = "<img src=\"$arton\" alt=\"\""
		. ($align ? " align=\"$align\"" : '') 
		. $taille
		. $artoff
		. ' class="spip_logos" />';

	return (!$lien ? $milieu :
		('<a href="' .
		 quote_amp($lien) .
		'">' .
		$milieu .
		'</a>'));
}

//
// Retrouver le logo d'un objet (et son survol)
//

// http://doc.spip.org/@calcule_logo
function calcule_logo($type, $onoff, $id, $id_rubrique, $flag_fichier) {
	$chercher_logo = charger_fonction('chercher_logo', 'inc');
	$nom = strtolower($onoff);

	while (1) {
		$on = $chercher_logo($id, $type, $nom);
		if ($on) {
			if ($flag_fichier)
				return (array('', "$on[2].$on[3]"));
			else {
				$off = ($onoff != 'ON') ? '' :
					$chercher_logo($id, $type, 'off');
				return array ($on[0], ($off ? $off[0] : ''));
			}
		}
		else if ($id_rubrique) {
			$type = 'id_rubrique';
			$id = $id_rubrique;
			$id_rubrique = 0;
		} else if ($id AND $type == 'id_rubrique')
			$id = quete_parent($id);
		else return array('','');
	}
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

	// Prendre un extrait dans la bonne langue
	$texte = extraire_multi($texte);

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
	$texte = $intro ? $intro : $texte;
	
	// On ne *PEUT* pas couper simplement ici car c'est du texte brut, qui inclus raccourcis et modeles
	// un simple <articlexx> peut etre ensuite transforme en 1000 lignes ...
	// par ailleurs le nettoyage des raccourcis ne tient pas compte des surcharges
	// et enrichissement de propre
	// couper doit se faire apres propre
	//$texte = nettoyer_raccourcis_typo($intro ? $intro : $texte, $connect);	
	
	$texte = propre($texte,$connect);

	@define('_INTRODUCTION_SUITE', '&nbsp;(...)');
	$texte = couper($texte, $longueur, _INTRODUCTION_SUITE);

	return $texte;
}

//
// Balises dynamiques
//

// elles sont traitees comme des inclusions
// http://doc.spip.org/@synthetiser_balise_dynamique
function synthetiser_balise_dynamique($nom, $args, $file, $lang, $ligne) {
	return
		('<'.'?php 
$lang_select = lang_select("'.$lang.'");
include_once(_DIR_RACINE . "'
		. $file
		. '");
inclure_balise_dynamique(balise_'
		. $nom
		. '_dyn('
		. join(", ", array_map('argumenter_squelette', $args))
		. '),1, '
		. $ligne
		. ');
if ($lang_select) lang_select();
?'
		.">");
}
// http://doc.spip.org/@argumenter_squelette
function argumenter_squelette($v) {

	if (!is_array($v))
		return "'" . texte_script($v) . "'";
	else  return 'array(' . join(", ", array_map('argumenter_squelette', $v)) . ')';
}

// verifier leurs arguments et filtres, et calculer le code a inclure
// http://doc.spip.org/@executer_balise_dynamique
function executer_balise_dynamique($nom, $args, $filtres, $lang, $ligne) {
	if (!$file = find_in_path(strtolower($nom) .'.php', 'balise/', true)) {
		// regarder si une fonction generique n'existe pas
		if (($p = strpos($nom,"_"))
		&& ($file = find_in_path(strtolower(substr($nom,0,$p+1)) .'.php', 'balise/', true))) {
			// dans ce cas, on lui injecte en premier arg le nom de la balise qu'on doit traiter
			array_unshift($args,$nom);
			$nom = substr($nom,0,$p+1);
		}
		else
			die ("pas de balise dynamique pour #". strtolower($nom)." !");
	}
	// Y a-t-il une fonction de traitement filtres-arguments ?
	$f = 'balise_' . $nom . '_stat';
	if (function_exists($f))
		$r = $f($args, $filtres);
	else
		$r = $args;
	if (!is_array($r))
		return $r;
	else {
		// verifier que la fonction dyn est la, sinon se replier sur la generique si elle existe
		if (!function_exists('balise_' . $nom . '_dyn')){
			// regarder si une fonction generique n'existe pas
			if (($p = strpos($nom,"_"))
			&& ($file = find_in_path(strtolower(substr($nom,0,$p+1)) .'.php', 'balise/', true))) {
				// dans ce cas, on lui injecte en premier arg le nom de la balise qu'on doit traiter
				array_unshift($r,$nom);
				$nom = substr($nom,0,$p+1);
			}
			else
				die ("pas de balise dynamique pour #". strtolower($nom)." !");
		}
		if (!_DIR_RESTREINT) 
			$file = _DIR_RESTREINT_ABS . $file;
		return synthetiser_balise_dynamique($nom, $r, $file, $lang, $ligne);
	}
}


//
// FONCTIONS FAISANT DES APPELS SQL
//

# NB : a l'exception des fonctions pour les balises dynamiques

// http://doc.spip.org/@calculer_hierarchie
function calculer_hierarchie($id_rubrique, $exclure_feuille = false) {

	if (!$id_rubrique = intval($id_rubrique))
		return '0';

	$hierarchie = array();

	if (!$exclure_feuille)
		$hierarchie[] = $id_rubrique;

	while ($id_rubrique = quete_parent($id_rubrique))
		array_unshift($hierarchie, $id_rubrique);

	if (count($hierarchie))
		return join(',', $hierarchie);
	else
		return '0';
}


// http://doc.spip.org/@calcul_exposer
function calcul_exposer ($id, $prim, $reference, $parent, $type) {
	static $exposer = array();
	static $ref_precedente =-1;

	// Que faut-il exposer ? Tous les elements de $reference
	// ainsi que leur hierarchie ; on ne fait donc ce calcul
	// qu'une fois (par squelette) et on conserve le resultat
	// en static.
	if (!isset($exposer[$m=md5(serialize($reference))][$prim])) {
		$principal = $reference[$type];
		if (!$principal) { // regarder si un enfant est dans le contexte, auquel cas il expose peut etre le parent courant
			$enfants = array('id_rubrique'=>array('id_article'),'id_groupe'=>array('id_mot'));
			if (isset($enfants[$type]))
				foreach($enfants[$type] as $t)
					if (isset($reference[$t])) {
						$type = $t;
						$principal = $reference[$type];
						$parent=0;
						continue;
					}
		}
		$exposer[$m][$type] = array();
		if ($principal) {
			$exposer[$m][$type][$principal] = true;
			if ($type == 'id_mot'){
				if (!$parent) {
					$parent = sql_fetsel('id_groupe','spip_mots',"id_mot=" . $principal);
					$parent = $parent['id_groupe'];
				}
				if ($parent)
					$exposer[$m]['id_groupe'][$parent] = true;
			}
			else if ($type != 'id_groupe') {
			  if (!$parent) {
			  	if ($type == 'id_rubrique')
			  		$parent = $principal;
			  	if ($type == 'id_article') {
						$parent = sql_fetsel('id_rubrique','spip_articles',"id_article=" . $principal);
						$parent = $parent['id_rubrique'];
			  	}
			  }
			  $a = split(',',calculer_hierarchie($parent));
			  foreach($a as $n) $exposer[$m]['id_rubrique'][$n] = true;
			}
		}
	}
	// And the winner is...
	return isset($exposer[$m][$prim]) ? isset($exposer[$m][$prim][$id]) : '';
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

// fonction appelee par la balise #LOGO_DOCUMENT
// http://doc.spip.org/@calcule_logo_document
function calcule_logo_document($id_document, $doubdoc, &$doublons, $flag_fichier, $lien, $align, $params, $connect='') {
	include_spip('inc/documents');

	if (!$id_document) return '';
	if ($doubdoc) $doublons["documents"] .= ','.$id_document;

	if (!($row = sql_fetsel('extension, id_vignette, fichier, mode', 'spip_documents', ("id_document = $id_document"),'','','','',$connect))) {
		// pas de document. Ne devrait pas arriver
		spip_log("Erreur du compilateur doc $id_document inconnu");
		return ''; 
	}

	$extension = $row['extension'];
	$id_vignette = $row['id_vignette'];
	$fichier = $row['fichier'];
	$mode = $row['mode'];
	$logo = '';

	// Y a t il une vignette personnalisee ?
	// Ca va echouer si c'est en mode distant. A revoir.
	if ($id_vignette) {
		$vignette = sql_fetsel('fichier','spip_documents',("id_document = $id_vignette"), '','','','',$connect);
		if (@file_exists(get_spip_doc($vignette['fichier'])))
			$logo = generer_url_document($id_vignette);
	} else if ($mode == 'vignette') {
		$logo = generer_url_document($id_document);
		if (!@file_exists($logo))
			$logo = '';
	}

	// taille maximum [(#LOGO_DOCUMENT{300,52})]
	if ($params
	AND preg_match('/{\s*(\d+),\s*(\d+)\s*}/', $params, $r)) {
		$x = intval($r[1]);
		$y = intval($r[2]);
	}

	if ($logo AND @file_exists($logo)) {
		if ($x OR $y)
			$logo = reduire_image($logo, $x, $y);
		else {
			$size = @getimagesize($logo);
			$logo = "<img src='$logo' ".$size[3]." />";
		}
	}
	else {
		// Pas de vignette, mais un fichier image -- creer la vignette
		if (strpos($GLOBALS['meta']['formats_graphiques'], $extension)!==false) {
		  if ($img = _DIR_RACINE.copie_locale(get_spip_doc($fichier))
			AND @file_exists($img)) {
				if (!$x AND !$y) {
					$logo = reduire_image($img);
				} else {
					# eviter une double reduction
					$size = @getimagesize($img);
					$logo = "<img src='$img' ".$size[3]." />";
				}
		  }
		  // cas de la vignette derriere un htaccess
		} elseif ($logo) $logo = "<img src='$logo'>";

		// Document sans vignette ni image : vignette par defaut
		if (!$logo) {
			$img = vignette_par_defaut($extension, false);
			$size = @getimagesize($img);
			$logo = "<img src='$img' ".$size[3]." />";
		}
	}

	// Reduire si une taille precise est demandee
	if ($x OR $y)
		$logo = reduire_image($logo, $x, $y);

	// flag_fichier : seul le fichier est demande
	if ($flag_fichier)
		return set_spip_doc(extraire_attribut($logo, 'src'));

	// Calculer le code html complet (cf. calcule_logo)
	$logo = inserer_attribut($logo, 'alt', '');
	$logo = inserer_attribut($logo, 'class', 'spip_logos');
	if ($align)
		$logo = inserer_attribut($logo, 'align', $align);

	if ($lien) {
		$mime = sql_getfetsel('mime_type','spip_types_documents', "extension = " . sql_quote($extension));
		$logo = "<a href='$lien' type='$mime'>$logo</a>";
	}
	return $logo;
}

// fonction appelee par la balise #NOTES
// http://doc.spip.org/@calculer_notes
function calculer_notes() {
	if (!isset($GLOBALS["les_notes"])) return '';
	if ($r = $GLOBALS["les_notes"]) {
		$GLOBALS["les_notes"] = "";
		$GLOBALS["compt_note"] = 0;
		$GLOBALS["marqueur_notes"] ++;
	}
	return $r;
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



// Ajouter "&lang=..." si la langue de base n'est pas celle du site.
// Si le 2e parametre n'est pas une chaine, c'est qu'on n'a pas pu
// determiner la table a la compil, on le fait maintenant.
// Il faudrait encore completer: on ne connait pas la langue
// pour une boucle forum sans id_article ou id_rubrique donné par le contexte
// et c'est signale par un message d'erreur abscons: "table inconnue forum".
// 
// http://doc.spip.org/@lang_parametres_forum
function lang_parametres_forum($qs, $lang) {
	if (is_array($lang) AND preg_match(',id_(\w+)=([0-9]+),', $qs, $r)) {
		$id = 'id_' . $r[1];
		if ($t = $lang[$id])
			$lang = sql_getfetsel('lang', $t, "$id=" . $r[2]);
	}
  // Si ce n'est pas la meme que celle du site, l'ajouter aux parametres

	if ($lang AND $lang <> $GLOBALS['meta']['langue_site'])
		return $qs . "&lang=" . $lang;

	return $qs;
}


// http://doc.spip.org/@match_self
function match_self($w){
	if (is_string($w)) return false;
	if (is_array($w)) {
		if (reset($w)=="SELF") return $w;
		foreach($w as $sw)
			if ($m=match_self($sw)) return $m;
	}
	return false;
}
// http://doc.spip.org/@remplace_sous_requete
function remplace_sous_requete($w,$sousrequete){
	if (is_array($w)) {
		if (reset($w)=="SELF") return $sousrequete;
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
	// remplacer les sous requetes recursives au calcul
	list($where_simples,$where_sous) = trouver_sous_requetes($where);
	//var_dump($where_sous);
	foreach($where_sous as $k=>$w) {
		$menage = true;
		// on recupere la sous requete 
		$sous = match_self($w);
		array_push($where_simples,$sous[2]);
		$where[$k] = remplace_sous_requete($w,"(".calculer_select($sous[1],$from,$from_type,array($sous[2],'0=0'),$join,array(),array(),'',$having,$table,$id,$serveur,false).")");
		array_pop($where_simples);
	}
	//var_dump($where);

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
		if (count($join[$cle])<3){
			list($t,$c) = $join[$cle];
			$carr = $c;
		}
		else
			// le nom de la cle d'arrivee est different de celui du depart, et est specifie
			list($t,$c,$carr) = $join[$cle];
		// si le nom de la jointure n'a pas ete specifiee, on prend Lx avec x sont rang dans la liste
		// pour compat avec ancienne convention
		if (is_numeric($cle))
			$cle = "L$k";
		if (!$menage
		OR isset($afrom[$cle])
		OR calculer_jointnul($cle, $select)
		OR calculer_jointnul($cle, $join)
		OR calculer_jointnul($cle, $having)
		OR calculer_jointnul($cle, $where_simples)) {
			// on garde une ecriture decomposee pour permettre une simplification ulterieure si besoin
			$afrom[$t][$cle] = array("\n" .
				(isset($from_type[$cle])?$from_type[$cle]:"INNER")." JOIN",
				$from[$cle],
				"AS $cle",
				"ON (",
				"$cle.$c",
				"=",
				"$t.$carr",
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
		
	  list($t,$c) = each($from);
	  reset($from);
	  $e = '/\b(' . "$t\\." . join("|" . $t . '\.', $equiv) . ')\b/';
	  if (!(strpos($t, ' ') OR // jointure des le depart cf boucle_doc
		 calculer_jointnul($t, $select, $e) OR
		 calculer_jointnul($t, $join, $e) OR
		 calculer_jointnul($t, $where, $e) OR
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

	$GLOBALS['debug']['aucasou'] = array ($table, $id, $serveur);
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
?>
