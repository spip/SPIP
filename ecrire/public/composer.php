<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
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
function public_composer_dist($squelette, $mime_type, $gram, $source, $connect) {

	$nom = $mime_type . ($connect ?  "_$connect" : '') . '_' . md5($squelette);

	// si squelette est deja en memoire (INCLURE  a repetition)
	if (function_exists($nom))
		return $nom;

	$phpfile = sous_repertoire(_DIR_SKELS,'',false,true) . $nom . '.php';

	// si squelette est deja compile et perenne, le charger
	if (!squelette_obsolete($phpfile, $source)
	AND lire_fichier ($phpfile, $contenu,
	array('critique' => 'oui', 'phpcheck' => 'oui'))) 
		eval('?'.'>'.$contenu);

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
		if ($GLOBALS['var_mode'] == 'debug') {
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
		in_array($GLOBALS['var_mode'], array('recalcul','preview','debug'))
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
function invalideur_session(&$Cache) {
	$Cache['session']=spip_session();
	return '';
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
@define('_INTRODUCTION_SUITE', '&nbsp;(...)');

// http://doc.spip.org/@filtre_introduction_dist
function filtre_introduction_dist($descriptif, $texte, $longueur, $connect) {
	// Si un descriptif est envoye, on l'utilise directement
	if (strlen($descriptif))
		return $descriptif;

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
	$texte = nettoyer_raccourcis_typo($intro ? $intro : $texte);

	// On coupe
	$texte = couper($texte, $longueur, _INTRODUCTION_SUITE);

	// on nettoie un peu car ce sera traite par traiter_raccourcis()
	return preg_replace(',([|]\s*)+,S', '; ', $texte);
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
	else {
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
		foreach ($reference as $element=>$id) {
			if ((strpos($element, "id_") === 0) AND $id) {
				$x = substr($element, 3);
				if ($x == 'secteur') $x = 'rubrique';
				$desc = trouver_table(table_objet($x));
				if ($desc) {
					$table = $desc['table'];
					$exposer[$element][$id] = true;
					if (isset($desc['field']['id_rubrique'])) {
						$row = sql_fetsel(array('id_rubrique'), array($table), array("$element=" . _q($id)));
						$hierarchie = calculer_hierarchie($row['id_rubrique']);
						foreach (split(',',$hierarchie) as $id_rubrique)
							$exposer['id_rubrique'][$id_rubrique] = true;
					}
				}
			}
		}
	}

	// And the winner is...
	return isset($exposer[$type]) ? isset($exposer[$type][$id]) : '';
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

	if (!($row = sql_fetsel(array('extension', 'id_vignette', 'fichier', 'mode'), array('spip_documents'), array("id_document = $id_document"), '','','','','','','',$connect))) {
		// pas de document. Ne devrait pas arriver
		spip_log("Erreur du compilateur doc $id_document inconnu");
		return ''; 
	}

	$extension = $row['extension'];
	$id_vignette = $row['id_vignette'];
	$fichier = get_spip_doc($row['fichier']);
	$mode = $row['mode'];

	// Y a t il une vignette personnalisee ?
	// Ca va echouer si c'est en mode distant. A revoir.
	if ($id_vignette) {
		$vignette = sql_fetsel(array('fichier'),
			   array('spip_documents'),
			   array("id_document = $id_vignette"), '','','','','','','',$connect);
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

	// Retrouver le type mime
	$ex = sql_fetch(sql_select(
		array('mime_type'),
		array('spip_types_documents'),
		array("extension = " . _q($extension))));
	$mime = $ex['mime_type'];

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
		  if ($img = _DIR_RACINE.copie_locale($fichier)
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
		return set_spip_doc(extraire_attribut($logo, 'src'));

	// Calculer le code html complet (cf. calcule_logo)
	$logo = inserer_attribut($logo, 'alt', '');
	$logo = inserer_attribut($logo, 'class', 'spip_logos');
	if ($align)
		$logo = inserer_attribut($logo, 'align', $align);

	if ($lien)
		$logo = "<a href='$lien' type='$mime'>$logo</a>";

	return $logo;
}


// les balises dynamiques et EMBED ont des filtres sans arguments
// car en fait ce sont des arguments pas des filtres.
// Si le besoin s'en fait sentir, il faudra recuperer la 2e moitie du tableau

// http://doc.spip.org/@argumenter_balise
function argumenter_balise($fonctions, $sep) {
	$res = array();
	if ($fonctions)
		foreach ($fonctions as $f)
			$res[] = str_replace('\'', '\\\'', str_replace('\\', '\\\\',$f[0]));
	return ("'" . join($sep, $res) . "'");
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

// Ajouter "&lang=..." si la langue de base n'est pas celle du site.
// Si le 2e parametre est "-1", c'est qu'on n'a pas pu
// determiner la table a la compil, on le fait maintenant.
// Il faudrait encore completer: on ne connait pas la langue
// pour une boucle forum sans id_article ou id_rubrique donné par le contexte
// http://doc.spip.org/@lang_parametres_forum
function lang_parametres_forum($qs, $lang) {
	if ($lang == -1 AND preg_match(',id_(\w+)=([0-9]+),', $qs, $r)) {
		$lang = quete_lang($r[2], $r[1]);
	}
  // Si ce n'est pas la meme que celle du site, l'ajouter aux parametres
	if ($lang AND $lang <> $GLOBALS['meta']['langue_site'])
		return $qs . "&lang=" . $lang;

	return $qs;
}

// La fonction presente dans les squelettes compiles

// http://doc.spip.org/@spip_optim_select
function spip_optim_select ($select = array(), $from = array(), 
			    $where = array(), $join=array(),
			    $groupby = '', $orderby = array(), $limit = '',
			    $sousrequete = '', $having = array(),
			    $table = '', $id = '', $serveur='') {

// retirer les criteres vides:
// {X ?} avec X absent de l'URL
// {par #ENV{X}} avec X absent de l'URL
// IN sur collection vide (ce dernier devrait pouvoir etre fait a la compil)

	$menage = false;
	foreach($where as $k => $v) { 
		if ((!$v) OR ($v==1) OR ($v=='0=0')) {
			unset($where[$k]);
			$menage = true;
		}
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
	
	for($k = count($join); $k > 0; $k--) {
		list($t,$c) = $join[$k];
		$cle = "L$k";
		if (!$menage
		OR spip_optim_joint($cle, $select)
		OR spip_optim_joint($cle, $join)
		OR spip_optim_joint($cle, $where))
			$where[]= "$t.$c=$cle.$c";
		else { unset($from[$cle]); unset($join[$k]);}
	}

	return sql_select($select, $from, $where,
		  $groupby, array_filter($orderby), $limit,
		  $sousrequete, $having,
		  $table, $id, $serveur);

}

//condition suffisante (mais non necessaire) pour qu'une jointure soit inutile

// http://doc.spip.org/@spip_optim_joint
function spip_optim_joint($cle, $exp)
{
	if (!is_array($exp))
		return	(strpos($exp, "$cle.") === false) ? false : true;
	else {
		foreach($exp as $v) {
			if (spip_optim_joint($cle, $v)) return true;
		}
		return false;
	}
}
?>
