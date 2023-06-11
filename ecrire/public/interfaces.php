<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Définition des noeuds de l'arbre de syntaxe abstraite
 *
 * @package SPIP\Core\Compilateur\AST
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


global $table_criteres_infixes;
$table_criteres_infixes = ['<', '>', '<=', '>=', '==', '===', '!=', '!==', '<>', '?'];

global $exception_des_connect;
$exception_des_connect[] = ''; // ne pas transmettre le connect='' par les inclure

/** @deprecated 4.2 Uses nampespaced classes */
class_alias(\Spip\Compilateur\Noeud\Boucle::class, 'Boucle');
class_alias(\Spip\Compilateur\Noeud\Champ::class, 'Champ');
class_alias(\Spip\Compilateur\Noeud\Contexte::class, 'Contexte');
class_alias(\Spip\Compilateur\Noeud\Critere::class, 'Critere');
class_alias(\Spip\Compilateur\Noeud\Polyglotte::class, 'Polyglotte');
class_alias(\Spip\Compilateur\Noeud\Texte::class, 'Texte');

/**
 * Déclarer les interfaces de la base pour le compilateur
 *
 * On utilise une fonction qui initialise les valeurs,
 * sans écraser d'eventuelles prédéfinition dans mes_options
 * et les envoie dans un pipeline
 * pour les plugins
 *
 * @return void
 */
function declarer_interfaces() {

	$GLOBALS['table_des_tables']['articles'] = 'articles';
	$GLOBALS['table_des_tables']['auteurs'] = 'auteurs';
	$GLOBALS['table_des_tables']['rubriques'] = 'rubriques';
	$GLOBALS['table_des_tables']['hierarchie'] = 'rubriques';

	// definition des statuts de publication
	$GLOBALS['table_statut'] = [];

	//
	// tableau des tables de jointures
	// Ex: gestion du critere {id_mot} dans la boucle(ARTICLES)
	$GLOBALS['tables_jointures'] = [];
	$GLOBALS['tables_jointures']['spip_jobs'][] = 'jobs_liens';

	// $GLOBALS['exceptions_des_jointures']['titre_mot'] = array('spip_mots', 'titre'); // pour exemple
	$GLOBALS['exceptions_des_jointures']['profondeur'] = ['spip_rubriques', 'profondeur'];


	if (!defined('_TRAITEMENT_TYPO')) {
		define('_TRAITEMENT_TYPO', 'typo(%s, "TYPO", $connect, $Pile[0])');
	}
	if (!defined('_TRAITEMENT_RACCOURCIS')) {
		define('_TRAITEMENT_RACCOURCIS', 'propre(%s, $connect, $Pile[0])');
	}
	if (!defined('_TRAITEMENT_TYPO_SANS_NUMERO')) {
		define('_TRAITEMENT_TYPO_SANS_NUMERO', 'supprimer_numero(typo(%s, "TYPO", $connect, $Pile[0]))');
	}
	$GLOBALS['table_des_traitements']['BIO'][] = 'safehtml(' . _TRAITEMENT_RACCOURCIS . ')';
	$GLOBALS['table_des_traitements']['NOM_SITE']['auteurs'] = 'entites_html(%s)';
	$GLOBALS['table_des_traitements']['NOM']['auteurs'] = 'safehtml(' . _TRAITEMENT_TYPO_SANS_NUMERO . ')';
	$GLOBALS['table_des_traitements']['CHAPO'][] = _TRAITEMENT_RACCOURCIS;
	$GLOBALS['table_des_traitements']['DATE'][] = 'normaliser_date(%s)';
	$GLOBALS['table_des_traitements']['DATE_REDAC'][] = 'normaliser_date(%s)';
	$GLOBALS['table_des_traitements']['DATE_MODIF'][] = 'normaliser_date(%s)';
	$GLOBALS['table_des_traitements']['DATE_NOUVEAUTES'][] = 'normaliser_date(%s)';
	$GLOBALS['table_des_traitements']['DESCRIPTIF'][] = _TRAITEMENT_RACCOURCIS;
	$GLOBALS['table_des_traitements']['INTRODUCTION'][] = _TRAITEMENT_RACCOURCIS;
	$GLOBALS['table_des_traitements']['NOM_SITE_SPIP'][] = _TRAITEMENT_TYPO;
	$GLOBALS['table_des_traitements']['NOM'][] = _TRAITEMENT_TYPO_SANS_NUMERO;
	$GLOBALS['table_des_traitements']['AUTEUR'][] = _TRAITEMENT_TYPO;
	$GLOBALS['table_des_traitements']['PS'][] = _TRAITEMENT_RACCOURCIS;
	$GLOBALS['table_des_traitements']['SOURCE'][] = _TRAITEMENT_TYPO;
	$GLOBALS['table_des_traitements']['SOUSTITRE'][] = _TRAITEMENT_TYPO;
	$GLOBALS['table_des_traitements']['SURTITRE'][] = _TRAITEMENT_TYPO;
	$GLOBALS['table_des_traitements']['TAGS'][] = '%s';
	$GLOBALS['table_des_traitements']['TEXTE'][] = _TRAITEMENT_RACCOURCIS;
	$GLOBALS['table_des_traitements']['TITRE'][] = _TRAITEMENT_TYPO_SANS_NUMERO;
	$GLOBALS['table_des_traitements']['TYPE'][] = _TRAITEMENT_TYPO;
	$GLOBALS['table_des_traitements']['DESCRIPTIF_SITE_SPIP'][] = _TRAITEMENT_RACCOURCIS;
	$GLOBALS['table_des_traitements']['SLOGAN_SITE_SPIP'][] = _TRAITEMENT_TYPO;
	$GLOBALS['table_des_traitements']['ENV'][] = 'entites_html(%s,true)';

	// valeur par defaut pour les balises non listees ci-dessus
	$GLOBALS['table_des_traitements']['*'][] = false; // pas de traitement, mais permet au compilo de trouver la declaration suivante
	// toujours securiser les DATA
	$GLOBALS['table_des_traitements']['*']['DATA'] = 'safehtml(%s)';
	// expliciter pour VALEUR qui est un champ calcule et ne sera pas protege par le catch-all *
	$GLOBALS['table_des_traitements']['VALEUR']['DATA'] = 'safehtml(%s)';


	// gerer l'affectation en 2 temps car si le pipe n'est pas encore declare, on ecrase les globales
	$interfaces = pipeline(
		'declarer_tables_interfaces',
		[
			'table_des_tables' => $GLOBALS['table_des_tables'],
			'exceptions_des_tables' => $GLOBALS['exceptions_des_tables'],
			'table_date' => $GLOBALS['table_date'],
			'table_titre' => $GLOBALS['table_titre'],
			'tables_jointures' => $GLOBALS['tables_jointures'],
			'exceptions_des_jointures' => $GLOBALS['exceptions_des_jointures'],
			'table_des_traitements' => $GLOBALS['table_des_traitements'],
			'table_statut' => $GLOBALS['table_statut'],
		]
	);
	if ($interfaces) {
		$GLOBALS['table_des_tables'] = $interfaces['table_des_tables'];
		$GLOBALS['exceptions_des_tables'] = $interfaces['exceptions_des_tables'];
		$GLOBALS['table_date'] = $interfaces['table_date'];
		$GLOBALS['table_titre'] = $interfaces['table_titre'];
		$GLOBALS['tables_jointures'] = $interfaces['tables_jointures'];
		$GLOBALS['exceptions_des_jointures'] = $interfaces['exceptions_des_jointures'];
		$GLOBALS['table_des_traitements'] = $interfaces['table_des_traitements'];
		$GLOBALS['table_statut'] = $interfaces['table_statut'];
	}
}

declarer_interfaces();
