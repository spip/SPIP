<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Définition des noeuds de l'arbre de syntaxe abstraite
 *
 * @package SPIP\Compilateur\AST
**/

if (!defined('_ECRIRE_INC_VERSION')) return;

global $table_criteres_infixes;
$table_criteres_infixes = array('<', '>', '<=', '>=', '==', '===', '!=', '!==', '<>',  '?');

global $exception_des_connect;
$exception_des_connect[] = ''; // ne pas transmettre le connect='' par les inclure

/**
 * Déclarer les interfaces de la base pour le compilateur
 * 
 * On utilise une fonction qui initialise les valeurs,
 * sans écraser d'eventuelles prédéfinition dans mes_options
 * et les envoie dans un pipeline
 * pour les plugins
 *
 * @link http://doc.spip.org/@declarer_interfaces
 *
 * @return void
 */
function declarer_interfaces(){
	global $exceptions_des_tables, $table_des_tables, $table_date, $table_titre, $table_statut;

	$table_des_tables['articles']='articles';
	$table_des_tables['auteurs']='auteurs';
	$table_des_tables['rubriques']='rubriques';
	$table_des_tables['hierarchie']='rubriques';

	// definition des statuts de publication
	global $tables_statut;
	$table_statut = array();

	//
	// tableau des tables de jointures
	// Ex: gestion du critere {id_mot} dans la boucle(ARTICLES)
	global $tables_jointures;
	$tables_jointures = array();
	$tables_jointures['spip_jobs'][] = 'jobs_liens';

	global  $exceptions_des_jointures;
	#$exceptions_des_jointures['titre_mot'] = array('spip_mots', 'titre'); // pour exemple
	$exceptions_des_jointures['profondeur'] = array('spip_rubriques', 'profondeur');

	global  $table_des_traitements;

	define('_TRAITEMENT_TYPO', 'typo(%s, "TYPO", $connect, $Pile[0])');
	define('_TRAITEMENT_RACCOURCIS', 'propre(%s, $connect, $Pile[0])');
	define('_TRAITEMENT_TYPO_SANS_NUMERO', 'typo(supprimer_numero(%s), "TYPO", $connect, $Pile[0])');

	$table_des_traitements['BIO'][]= _TRAITEMENT_RACCOURCIS;
	$table_des_traitements['CHAPO'][]= _TRAITEMENT_RACCOURCIS;
	$table_des_traitements['DATE'][]= 'normaliser_date(%s)';
	$table_des_traitements['DATE_REDAC'][]= 'normaliser_date(%s)';
	$table_des_traitements['DATE_MODIF'][]= 'normaliser_date(%s)';
	$table_des_traitements['DATE_NOUVEAUTES'][]= 'normaliser_date(%s)';
	$table_des_traitements['DESCRIPTIF'][]= _TRAITEMENT_RACCOURCIS;
	$table_des_traitements['INTRODUCTION'][]= 'PtoBR('. _TRAITEMENT_RACCOURCIS .')';
	$table_des_traitements['NOM_SITE_SPIP'][]= _TRAITEMENT_TYPO;
	$table_des_traitements['NOM'][]= _TRAITEMENT_TYPO_SANS_NUMERO;
	$table_des_traitements['AUTEUR'][]= _TRAITEMENT_TYPO;
	$table_des_traitements['PS'][]= _TRAITEMENT_RACCOURCIS;
	$table_des_traitements['SOURCE'][]= _TRAITEMENT_TYPO;
	$table_des_traitements['SOUSTITRE'][]= _TRAITEMENT_TYPO;
	$table_des_traitements['SURTITRE'][]= _TRAITEMENT_TYPO;
	$table_des_traitements['TAGS'][]= '%s';
	$table_des_traitements['TEXTE'][]= _TRAITEMENT_RACCOURCIS;
	$table_des_traitements['TITRE'][]= _TRAITEMENT_TYPO_SANS_NUMERO;
	$table_des_traitements['TYPE'][]= _TRAITEMENT_TYPO;
	$table_des_traitements['DESCRIPTIF_SITE_SPIP'][]= _TRAITEMENT_RACCOURCIS;
	$table_des_traitements['SLOGAN_SITE_SPIP'][]= _TRAITEMENT_TYPO;
	$table_des_traitements['ENV'][]= 'entites_html(%s,true)';

	// valeur par defaut pour les balises non listees ci-dessus
	$table_des_traitements['*'][]= false; // pas de traitement, mais permet au compilo de trouver la declaration suivante
	// toujours securiser les DATA
	$table_des_traitements['*']['DATA']= 'safehtml(%s)';
	// expliciter pour VALEUR qui est un champ calcule et ne sera pas protege par le catch-all *
	$table_des_traitements['VALEUR']['DATA']= 'safehtml(%s)';


	// gerer l'affectation en 2 temps car si le pipe n'est pas encore declare, on ecrase les globales
	$interfaces = pipeline('declarer_tables_interfaces',
			array(
			'table_des_tables'=>$table_des_tables,
			'exceptions_des_tables'=>$exceptions_des_tables,
			'table_date'=>$table_date,
			'table_titre'=>$table_titre,
			'tables_jointures'=>$tables_jointures,
			'exceptions_des_jointures'=>$exceptions_des_jointures,
			'table_des_traitements'=>$table_des_traitements,
			'table_statut'=>$table_statut,
			));
	if ($interfaces){
			$table_des_tables = $interfaces['table_des_tables'];
			$exceptions_des_tables = $interfaces['exceptions_des_tables'];
			$table_date = $interfaces['table_date'];
			$table_titre = $interfaces['table_titre'];
			$tables_jointures = $interfaces['tables_jointures'];
			$exceptions_des_jointures = $interfaces['exceptions_des_jointures'];
			$table_des_traitements = $interfaces['table_des_traitements'];
	    $table_statut = $interfaces['table_statut'];
	}
}

declarer_interfaces();

?>
