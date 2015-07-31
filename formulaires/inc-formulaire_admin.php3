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

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

global $balise_FORMULAIRE_ADMIN_collecte ;
$balise_FORMULAIRE_ADMIN_collecte = array();

# on ne peut rien dire au moment de l'execution du squelette

function balise_FORMULAIRE_ADMIN_stat($args, $filtres) {
	return $args;
}

# les boutons admin sont mis d'autorite si absents
# donc une variable statique controle si FORMULAIRE_ADMIN a ete vu.
# Toutefois, si c'est le debuger qui appelle,
# il peut avoir recopie le code dans ses donnees et il faut le lui refounir.
# Pas question de recompiler: ca fait boucler !
# Le debuger transmet donc ses donnees, et cette balise y retrouve son petit.

function balise_FORMULAIRE_ADMIN_dyn($float='', $debug='') {
  global $var_preview, $use_cache, $forcer_debug, $xhtml;

	global $id_article, $id_breve, $id_rubrique, $id_mot, $id_auteur, $id_syndic;
	static $dejafait = false;

	if (!$GLOBALS['spip_admin'])
		return '';

	if (!is_array($debug)) {
		if ($dejafait)
			return '';
	} else {
		if ($dejafait) {
			$res = '';
			foreach($debug['sourcefile'] as $k => $v) {
				if (strpos($v,'formulaire_admin.') !== false)
					return $debug['resultat'][$k . 'tout'];
			}
			return '';
		}
	}
	$dejafait = true;

	// repartir de zero pour les boutons car clean_link a pu etre utilisee
	$link = new Link();
	$link->delVar('var_mode');
	$link->delVar('var_mode_objet');
	$link->delVar('var_mode_affiche');
	$action = $link->getUrl();
	$action = ($action . ((strpos($action, '?') === false) ? '?' : '&'));

	// Ne pas afficher le bouton 'Modifier ce...' si l'objet n'existe pas
	foreach (array('article', 'breve', 'rubrique', 'mot', 'auteur', 'syndic') as $type) {
		$id_type = id_table_objet($type);
		if (!($$id_type = intval($$id_type)
		AND $s = spip_query(
		"SELECT $id_type FROM spip_".table_objet($type)."
		WHERE $id_type=".$$id_type)
		AND spip_num_rows($s)))
			$$id_type='';
		else {
			$objet_affiche = $type;
			break;
		}
	}

	// Bouton statistiques
	if ($GLOBALS['meta']["activer_statistiques"] != "non" 
	AND $id_article
	AND !$var_preview
	AND ($GLOBALS['auteur_session']['statut'] == '0minirezo')) {
		if ($s = spip_query("SELECT id_article
		FROM spip_articles WHERE statut='publie'
		AND id_article = $id_article")
		AND spip_fetch_array($s)) {
			include_ecrire ("public-stats");
			$r = afficher_raccourci_stats($id_article);
			$visites = $r['visites'];
			$popularite = $r['popularite'];
			$statistiques = generer_url_ecrire('statistiques_visites', "id_article=$id_article", true);
		}
	}

	// Bouton de debug
	$debug =
	(
		(	$forcer_debug
			OR $GLOBALS['bouton_admin_debug']
			OR (
				$GLOBALS['var_mode'] == 'debug'
				AND $GLOBALS['_COOKIE']['spip_debug']
			)
		) AND (
			$GLOBALS['auteur_session']['statut'] == '0minirezo'
		) AND (
			!$var_preview
		)
	) ? 'debug' : '';
	$analyser = !$xhtml ? "" :
		(($xhtml === 'spip_sax') ?
		($action . "var_mode=debug&var_mode_affiche=validation") :
		('http://validator.w3.org/check?uri='
		. urlencode("http://" . $_SERVER['HTTP_HOST'] . nettoyer_uri())));

	// hack - ne pas avoir la rubrique si un autre bouton est deja present
	if ($id_article OR $id_breve) unset ($id_rubrique);

	// Pas de "modifier ce..." ? -> donner "acces a l'espace prive"
	if (!($id_article || $id_rubrique || $id_auteur || $id_breve || $id_mot || $id_syndic))
		$ecrire = _DIR_RESTREINT_ABS;

	// Bouton "preview" si l'objet demande existe et est previsualisable
	if (!$GLOBALS['var_preview'] AND (
	(($GLOBALS['meta']['preview']=='1comite'
		AND $GLOBALS['auteur_session']['statut'] =='1comite')
	OR ($GLOBALS['meta']['preview']<>''
		AND $GLOBALS['auteur_session']['statut'] =='0minirezo'))
	)) {
		if ($objet_affiche == 'article' AND $GLOBALS['meta']['post_dates'] != 'oui')
			$postdates = "OR (statut='publie' AND date>NOW())";

		if ($objet_affiche == 'article'
		OR $objet_affiche == 'breve'
		OR $objet_affiche == 'rubrique'
		OR $objet_affiche == 'syndic')
			if (spip_num_rows(spip_query(
			"SELECT id_$objet_affiche FROM spip_".table_objet($objet_affiche)."
			WHERE ".id_table_objet($objet_affiche)."=".$$id_type."
			AND (
				(statut IN ('prop', 'prive'))
				$postdates
			)")))
				$preview = 'preview';
	}

	return array('formulaire_admin', 0, 
		array(
			'id_article' => $id_article,
			'id_rubrique' => $id_rubrique,
			'id_auteur' => $id_auteur,
			'id_breve' => $id_breve,
			'id_mot' => $id_mot,
			'id_syndic' => $id_syndic,
			'voir_article' => generer_url_ecrire('articles', "id_article=$id_article", true),
			'voir_breve' => generer_url_ecrire('breves_voir', "id_breve=$id_breve", true),
			'voir_rubrique' => generer_url_ecrire('naviguer', "id_rubrique=$id_rubrique", true),
			'voir_mot' => generer_url_ecrire('mots_edit', "id_mot=$id_mot", true),
			'voir_site' => generer_url_ecrire('sites', "id_syndic=$id_syndic", true),
			'voir_auteur' => generer_url_ecrire('auteurs_edit', "id_auteur=$id_auteur", true),
			'ecrire' => $ecrire,
			'action' => $action,
			'preview' => $preview,
			'debug' => $debug,
			'popularite' => ceil($popularite),
			'statistiques' => $statistiques,
			'visites' => intval($visites),
			'use_cache' => ($use_cache ? '' : ' *'),
			'divclass' => $float,
			'analyser' => $analyser,
			'xhtml_error' => $GLOBALS['xhtml_error']
			)
		     );
}

// Un outil pour le bouton d'amin "statistiques"
function afficher_raccourci_stats($id_article) {
	$query = "SELECT visites, popularite FROM spip_articles WHERE id_article=$id_article AND statut='publie'";
	$result = spip_query($query);
	if ($row = @spip_fetch_array($result)) {
		$visites = intval($row['visites']);
		$popularite = ceil($row['popularite']);

		return array('visites' => $visites, 'popularite' => $popularite);
	}
}


?>
