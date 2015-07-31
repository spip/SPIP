<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/meta');
include_spip("inc/indexation");
include_spip('inc/texte');
include_spip('inc/documents');
include_spip('inc/forum');
include_spip('inc/distant');
include_spip('inc/rubriques'); # pour calcul_branche (cf critere branche)

# Charge un squelette (au besoin le compile) 
# et retoune le nom de sa fonction principale, ou '' s'il est indefini
# Charge egalement un fichier homonyme de celui du squelette
# mais de suffixe '_fonctions.php' pouvant contenir:
# 1. des filtres
# 2. des fonctions de traduction de balise, de critere et de boucle
# 3. des declaration de tables SQL supplementaires
# Toutefois pour 2. et 3. preferer la technique de la surcharge

function public_executer_squelette($squelette, $mime_type, $gram, $sourcefile) {

	$nom = $mime_type . '_' . md5($squelette);

	// si squelette est deja en memoire (INCLURE  a repetition)
	if (function_exists($nom))
		return $nom;

	$phpfile = sous_repertoire(_DIR_CACHE, 'skel') . $nom . '.php';

	// si squelette est deja compile et perenne, le charger
	if (!squelette_obsolete($phpfile, $sourcefile)
	AND lire_fichier ($phpfile, $contenu,
	array('critique' => 'oui', 'phpcheck' => 'oui'))) 
		eval('?'.'>'.$contenu);

	@include_once($squelette . '_fonctions'.'.php3'); # compatibilite
	@include_once($squelette . '_fonctions'.'.php');

	// tester si le eval ci-dessus a mis le squelette en memoire

	if (function_exists($nom)) return $nom;

	// charger le source, si possible, et compiler 
	if (lire_fichier ($sourcefile, $skel)) {
		$f = charger_fonction('compiler_squelette', 'public');
		$skel_code = $f($skel, $nom, $gram, $sourcefile);
	}

	// Tester si le compilateur renvoie une erreur

	if (is_array($skel_code))
		erreur_squelette($skel_code[0], $skel_code[1]);
	else {
		if ($GLOBALS['var_mode'] == 'debug') {
			include_spip('public/debug');
			debug_dumpfile ($skel_code, $nom, 'code');
		}
		eval('?'.'>'.$skel_code);
		if (function_exists($nom)) {
			ecrire_fichier ($phpfile, $skel_code);
			return $nom;
		} else {
			erreur_squelette($sourcefile, _L('Erreur de compilation'));
		}
	}
}

// Le squelette compile est-il trop vieux ?
function squelette_obsolete($skel, $squelette) {
	return (
		($GLOBALS['var_mode'] AND $GLOBALS['var_mode']<>'calcul')
		OR !@file_exists($skel)
		OR (@filemtime($squelette) > ($date = @filemtime($skel)))
		OR (@filemtime('mes_fonctions.php') > $date)
		OR (@filemtime('mes_fonctions.php3') > $date)  # compatibilite
		OR (defined('_FILE_OPTIONS') AND @filemtime(_FILE_OPTIONS) > $date)
	);
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

function calcule_fichier_logo($on) {
	return ereg_replace("^" . _DIR_IMG, "", $on);
}

// Renvoie le code html pour afficher un logo, avec ou sans survol, lien, etc.

function affiche_logos($logos, $lien, $align) {

	list ($arton, $artoff) = $logos;

	if (!$arton) return $artoff;

	if ($taille = @getimagesize($arton)) {
		$taille = " ".$taille[3];
	}

	if ($artoff)
		$mouseover = " onmouseover=\"this.src='$artoff'\" "
			."onmouseout=\"this.src='$arton'\"";

	$milieu = "<img src=\"$arton\" alt=\"\""
		. ($align ? " align=\"$align\"" : '') 
		. $taille
		. $mouseover
		. ' style="border-width: 0px;" class="spip_logos" />';

	return (!$lien ? $milieu :
		('<a href="' .
		 quote_amp($lien) .
		'">' .
		$milieu .
		'</a>'	 ));
}

//
// Retrouver le logo d'un objet (et son survol)
//

function calcule_logo($type, $onoff, $id, $id_rubrique, $ff) {
	include_spip('inc/logos');

	$table_logos = array (
	'ARTICLE' => 'art',
	'AUTEUR' =>  'aut',
	'BREVE' =>  'breve',
	'MOT' => 'mot',
	'RUBRIQUE' => 'rub',
	'SITE' => 'site'
	);
	$type = $table_logos[$type];
	$nom = strtolower($onoff);

	while (1) {
	  $on = cherche_logo($id, $type, $nom);
		if ($on) {
			if ($ff)
			  return  (array('', "$on[2].$on[3]"));
			else {
				$off = ($onoff != 'ON') ? '' :
				  cherche_logo($id, $type, 'off');
				return array ($on[0], ($off ? $off[0] : ''));
			}
		}
		else if ($id_rubrique) {
			$type = 'rub';
			$id = $id_rubrique;
			$id_rubrique = 0;
		} else if ($id AND $type == 'rub')
			$id = sql_parent($id);
		else return array('','');
	}
}

//
// fonction standard de calcul de la balise #INTRODUCTION
// on peut la surcharger en definissant dans mes_fonctions :
// function introduction($type,$texte,$chapo,$descriptif) {...}
//
function calcul_introduction ($type, $texte, $chapo='', $descriptif='') {
	if (function_exists("introduction"))
		return introduction ($type, $texte, $chapo, $descriptif);

	switch ($type) {
		case 'articles':
			if ($descriptif)
				return propre($descriptif);
			else if (substr($chapo, 0, 1) == '=')	// article virtuel
				return '';
			else
				return PtoBR(propre(supprimer_tags(couper_intro($chapo."\n\n\n".$texte, 500))));
			break;
		case 'breves':
			return PtoBR(propre(supprimer_tags(couper_intro($texte, 300))));
			break;
		case 'forums':
			return PtoBR(propre(supprimer_tags(couper_intro($texte, 600))));
			break;
		case 'rubriques':
			if ($descriptif)
				return propre($descriptif);
			else
				return PtoBR(propre(supprimer_tags(couper_intro($texte, 600))));
			break;
	}
}


//
// Balises dynamiques
//

// elles sont traitees comme des inclusions
function synthetiser_balise_dynamique($nom, $args, $file, $lang, $ligne) {
	return
		('<'.'?php 
include_spip(\'inc/lang\');
lang_select("'.$lang.'");
include_once("'
		. $file
		. '");
inclure_balise_dynamique(balise_'
		. $nom
		. '_dyn('
		. join(", ", array_map('argumenter_squelette', $args))
		. "),1, $ligne);
lang_dselect();
?"
		.">");
}
function argumenter_squelette($v) {

	if (!is_array($v))
		return "'" . texte_script($v) . "'";
	else  return 'array(' . join(", ", array_map('argumenter_squelette', $v)) . ')';
}

// verifier leurs arguments et filtres, et calculer le code a inclure
function executer_balise_dynamique($nom, $args, $filtres, $lang, $ligne) {
	if (!$file = include_spip('balise/' . strtolower($nom)))
		die ("pas de balise dynamique pour #". strtolower($nom)." !");

	// Y a-t-il une fonction de traitement filtres-arguments ?
	$f = 'balise_' . $nom . '_stat';
	if (function_exists($f))
		$r = $f($args, $filtres);
	else
		$r = $args;
	if (!is_array($r))
		return $r;
	else
		return synthetiser_balise_dynamique($nom, $r, $file, $lang, $ligne);
}


//
// FONCTIONS FAISANT DES APPELS SQL
//

# NB : a l'exception des fonctions pour les balises dynamiques

function calculer_hierarchie($id_rubrique, $exclure_feuille = false) {

	if (!$id_rubrique = intval($id_rubrique))
		return '0';

	$hierarchie = ",$id_rubrique";

	do {
		$id_rubrique = sql_parent($id_rubrique);
		$hierarchie = "," . $id_rubrique . $hierarchie;
	} while ($id_rubrique);

	return substr($hierarchie,1);
}


function calcul_exposer ($id, $type, $reference) {
	static $exposer;
	static $ref_precedente;

	// Que faut-il exposer ? Tous les elements de $reference
	// ainsi que leur hierarchie ; on ne fait donc ce calcul
	// qu'une fois (par squelette) et on conserve le resultat
	// en static.
	if ($reference<>$ref_precedente) {
		$ref_precedente = $reference;

		$exposer = array();
		foreach ($reference as $element=>$id_element) {
			if ($element == 'id_secteur') $element = 'id_rubrique';
			if ($x = table_from_primary($element)) {
				list($table,$hierarchie) = $x;
				$exposer[$element][$id_element] = true;
				if ($hierarchie) {
					list ($id_rubrique) = spip_abstract_fetsel(
array('id_rubrique'), 
array($table),
array("$element=$id_element"));
				$hierarchie = calculer_hierarchie($id_rubrique);
				foreach (split(',',$hierarchie) as $id_rubrique)
					$exposer['id_rubrique'][$id_rubrique] = true;
				}
			}
		}
	}

	// And the winner is...
	return $exposer[$type][$id];
}

function table_from_primary($id) {
	global $tables_principales;
	include_spip('base/serial');
	foreach ($tables_principales as $k => $v) {
		if ($v['key']['PRIMARY KEY'] == $id)
			return array($k, array_key_exists('id_rubrique', $v['field']));
	}
	return '';
}

// fonction appelee par la balise #LOGO_DOCUMENT
function calcule_logo_document($id_document, $doubdoc, &$doublons, $flag_fichier, $lien, $align, $params) {
	if (!$id_document) return '';
	if ($doubdoc) $doublons["documents"] .= ','.$id_document;

	if (!($row = spip_abstract_select(array('id_type', 'id_vignette', 'fichier', 'mode'), array('spip_documents AS documents'), array("id_document = $id_document"))))
		// pas de document. Ne devrait pas arriver
		return ''; 

	list($id_type, $id_vignette, $fichier, $mode) = spip_abstract_fetch($row);

	// Lien par defaut = l'adresse du document
	## if (!$lien) $lien = $fichier;

	// Y a t il une vignette personnalisee ?
	if ($id_vignette) {
		if ($res = spip_abstract_select(array('fichier'),
		array('spip_documents AS documents'),
		array("id_document = $id_vignette"))) {
			list($vignette) = spip_abstract_fetch($res);
			if (@file_exists($vignette))
				$logo = generer_url_document($id_vignette);
		}
	} else if ($mode == 'vignette') {
		$logo = generer_url_document($id_document);
		if (!@file_exists($logo))
			$logo = '';
	}

	// taille maximum [(#LOGO_DOCUMENT{300,52})]
	list($x,$y) = split(',', ereg_replace("[}{]", "", $params)); 


	if ($logo AND @file_exists($logo)) {
		if ($x OR $y)
			$logo = reduire_image($logo, $x, $y);
		else {
			$size = @getimagesize($logo);
			$logo = "<img src='$logo' ".$size[3]." />";
		}
	}
	else {
		// Retrouver l'extension
		list($extension) =
			spip_abstract_fetch(spip_abstract_select(array('extension'),
			array('spip_types_documents AS documents'),
			array("id_type = " . intval($id_type))));
		if (!$extension) $extension = 'txt';

		// Pas de vignette, mais un fichier image -- creer la vignette
		if (strstr($GLOBALS['meta']['formats_graphiques'], $extension)) {
		  if ($img = copie_locale($fichier)
			AND @file_exists($img)) {
				if (!$x AND !$y) {
					$logo = reduire_image($img);
				} else {
					# eviter une double reduction
					$size = @getimagesize($img);
					$logo = "<img src='$img' ".$size[3]." />";
				}
			}
		}

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
		return ereg_replace("^" . _DIR_IMG, "", (extraire_attribut($logo, 'src')));


	// Calculer le code html complet (cf. calcule_logo)
	$logo = inserer_attribut($logo, 'alt', '');
	$logo = inserer_attribut($logo, 'style', 'border-width: 0px;');
	$logo = inserer_attribut($logo, 'class', 'spip_logos');
	if ($align)
		$logo = inserer_attribut($logo, 'align', $align);

	if ($lien)
		$logo = "<a href='$lien'>$logo</a>";

	return $logo;
}


// fonction appelee par la balise #EMBED
function calcule_embed_document($id_document, $filtres, &$doublons, $doubdoc) {
	if ($doubdoc && $id_document) $doublons["documents"] .= ', ' . $id_document;
	return embed_document($id_document, $filtres, false);
}

// cherche les documents numerotes dans un texte traite par propre()
// et affecte les doublons['documents']
function traiter_doublons_documents(&$doublons, $letexte) {
	if (preg_match_all(
	',<(span|div\s)[^>]*class=["\']spip_document_([0-9]+) ,',
	$letexte, $matches, PREG_PATTERN_ORDER))
		$doublons['documents'] .= "," . join(',', $matches[2]);
	return $letexte;
}


// les balises dynamiques et EMBED ont des filtres sans arguments 
// car en fait ce sont des arguments pas des filtres.
// Si le besoin s'en fait sentir, il faudra recuperer la 2e moitie du tableau 

function argumenter_balise($fonctions, $sep) {
  $res = array();
  if ($fonctions)
    foreach ($fonctions as $f) $res[] =
      str_replace('\'', '\\\'', str_replace('\\', '\\\\',$f[0]));
  return ("'" . join($sep, $res) . "'");
}

// fonction appelee par la balise #NOTES
function calculer_notes() {
	$r = $GLOBALS["les_notes"];
	$GLOBALS["les_notes"] = "";
	$GLOBALS["compt_note"] = 0;
	$GLOBALS["marqueur_notes"] ++;
	return $r;
}

// Ajouter "&lang=..." si la langue de base n'est pas celle du site
function lang_parametres_forum($s) {
	// ne pas se fatiguer si le site est unilingue (plus rapide)
	if (strstr($GLOBALS['meta']['langues_utilisees'], ',')
	// chercher l'identifiant qui nous donnera la langue
	AND preg_match(',(id_(article|breve|rubrique|syndic)=([0-9]+)),', $s, $r)){
		list($lang) = spip_abstract_fetsel(array('lang'),
						   array("spip_" . $r[2] .'s'),
						   array($r[1]));

	// Si ce n'est pas la meme que celle du site, l'ajouter aux parametres
		if ($lang AND $lang <> $GLOBALS['meta']['langue_site'])
			return $s . "&lang=$lang";
	}

	return $s;
}

// La fonction presente dans les squelettes compiles

function spip_optim_select ($select = array(), $from = array(), 
			    $where = array(), $join=array(),
			    $groupby = '', $orderby = array(), $limit = '',
			    $sousrequete = '', $cpt = '',
			    $table = '', $id = '', $serveur='') {

// retirer les criteres vides:
// {X ?} avec X absent de l'URL
// {par #ENV{X}} avec X absent de l'URL
// IN sur collection vide

	foreach($where as $k => $v) { 
		if ((!$v) OR ($v==1) OR ($v=='0=0')) {
			unset($where[$k]);
		}
	}
// Construire les clauses determinant les jointures.
// Il faudrait retirer celles seulement utiles aux criteres finalement absents
// (et nettoyer $from en consequence)
// mais la condition necessaire et suffisante n'est pas triviale

	foreach($join as $k => $v) {
		list($t,$c) = $v;
		$where[]= "$t.$c=L$k.$c";
	}
	return spip_abstract_select($select, $from, $where,
		  $groupby, array_filter($orderby), $limit,
		  $sousrequete, $cpt,
		  $table, $id, $serveur);

}
?>
