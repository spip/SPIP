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


//
// Des fonctions diverses utilisees lors du calcul d'une page ; ces fonctions
// bien pratiques n'ont guere de logique organisationnelle ; elles sont
// appelees par certaines balises au moment du calcul des pages. (Peut-on
// trouver un modele de donnees qui les associe physiquement au fichier
// definissant leur balise ???
//

// ON TROUVERA EN QUEUE DE FICHIER LES FONCTIONS FAISANT DES APPELS SQL


if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/rubriques'); # pour calcul_branche()

// Pour les documents comme pour les logos, le filtre |fichier donne
// le chemin du fichier apres 'IMG/' ;  peut-etre pas d'une purete
// remarquable, mais a conserver pour compatibilite ascendante.
// -> http://www.spip.net/fr_article901.html
function calcule_fichier_logo($on) {
	$r = ereg_replace("^" . _DIR_IMG, "", $on);
	return $r;
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
		# supprimer le IMG/
		return calcule_fichier_logo(extraire_attribut($logo, 'src'));


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

# retourne la profondeur d'une rubrique

function sql_profondeur($id) {
	$n = 0;
	while ($id) {
		$n++;
		$id = sql_parent($id);
	}
	return $n;
}


function sql_parent($id_rubrique) {
	list($id) = spip_abstract_fetsel(array(id_parent), 
			array('spip_rubriques'), 
			array("id_rubrique=" . intval($id_rubrique)));
	return $id;
}

function sql_rubrique($id_article) {
	$row = spip_abstract_fetsel(array('id_rubrique'),
			array('spip_articles'),
			array("id_article=" . intval($id_article)));
	return $row['id_rubrique'];
}

function sql_auteurs($id_article, $table, $id_boucle, $serveur='') {
	$auteurs = "";
	if ($id_article) {
		$result_auteurs = spip_abstract_select(
			array('auteurs.id_auteur', 'auteurs.nom'),
			array('spip_auteurs AS auteurs',
				'spip_auteurs_articles AS lien'), 
			array("lien.id_article=$id_article",
				"auteurs.id_auteur=lien.id_auteur"),
			'',array(),'','',1, 
			$table, $id_boucle, $serveur);

		while($row_auteur = spip_abstract_fetch($result_auteurs, $serveur)) {
			$nom_auteur = typo($row_auteur['nom']);
			$url_auteur = generer_url_auteur($row_auteur['id_auteur']);
			if ($url_auteur) {
				$auteurs[] = "<a href=\"mailto:$email_auteur\">$nom_auteur</a>";
			} else {
				$auteurs[] = "$nom_auteur";
			}
		}
	}
	return (!$auteurs) ? "" : join($auteurs, ", ");
}

function sql_petitions($id_article, $table, $id_boucle, $serveur, &$Cache) {
	$retour = spip_abstract_fetsel(
		array('texte'),
		array('spip_petitions'),
		array("id_article=".intval($id_article)),
		'',array(),'','',1, 
		$table, $id_boucle, $serveur);

	if (!$retour) return '';
	# cette page est invalidee par toute petition
	$Cache['varia']['pet'.$id_article] = 1;
	# ne pas retourner '' car le texte sert aussi de presence
	return ($retour['texte'] ? $retour['texte'] : ' ');
}

# retourne le chapeau d'un article, et seulement s'il est publie

function sql_chapo($id_article) {
	if ($id_article)
	return spip_abstract_fetsel(array('chapo'),
		array('spip_articles'),
		array("id_article=".intval($id_article),
		"statut='publie'"));
}

# retourne le champ 'accepter_forum' d'un article
function sql_accepter_forum($id_article) {
	static $cache = array();

	if (!$id_article) return;

	if (!isset($cache[$id_article])) {
		$row = spip_abstract_fetsel(array('accepter_forum'),
			array('spip_articles'),
			array("id_article=".intval($id_article)));
		$cache[$id_article] = $row['accepter_forum'];
	}

	return $cache[$id_article];
}


// Calcul de la rubrique associee a la requete
// (selection de squelette specifique par id_rubrique & lang)

function sql_rubrique_fond($contexte) {

	if ($id = intval($contexte['id_rubrique'])) {
		$row = spip_abstract_fetsel(array('lang'),
					    array('spip_rubriques'),
					    array("id_rubrique=$id"));
		if ($row['lang'])
			$lang = $row['lang'];
		return array ($id, $lang);
	}

	if ($id  = intval($contexte['id_breve'])) {
		$row = spip_abstract_fetsel(array('id_rubrique', 'lang'),
			array('spip_breves'), 
			array("id_breve=$id"));
		$id_rubrique_fond = $row['id_rubrique'];
		if ($row['lang'])
			$lang = $row['lang'];
		return array($id_rubrique_fond, $lang);
	}

	if ($id = intval($contexte['id_syndic'])) {
		$row = spip_abstract_fetsel(array('id_rubrique'),
			array('spip_syndic'),
			array("id_syndic=$id"));
		$id_rubrique_fond = $row['id_rubrique'];
		$row = spip_abstract_fetsel(array('lang'),
			array('spip_rubriques'),
			array("id_rubrique='$id_rubrique_fond'"));
		if ($row['lang'])
			$lang = $row['lang'];
		return array($id_rubrique_fond, $lang);
	}

	if ($id = intval($contexte['id_article'])) {
		$row = spip_abstract_fetsel(array('id_rubrique', 'lang'),
			array('spip_articles'),
			array("id_article=$id"));
		$id_rubrique_fond = $row['id_rubrique'];
		if ($row['lang'])
			$lang = $row['lang'];
		return array($id_rubrique_fond, $lang);
	}
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
