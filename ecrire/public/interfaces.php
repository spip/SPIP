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


/**
 * Description d'un contexte de compilation
 *
 * Objet simple pour stocker le nom du fichier, la ligne, la boucle
 * permettant entre autre de localiser le lieu d'une erreur de compilation.
 * Cette structure est nécessaire au traitement d'erreur à l'exécution.
 * 
 * Le champ code est inutilisé dans cette classe seule, mais harmonise
 * le traitement d'erreurs. Par contre, un noeud étendant cette classe
 * stocke dedans le code issu de la compilation du noeud.
 */
class Contexte {
	/**
	 * Description du squelette
	 *
	 * Peut contenir les index :
	 * - nom : Nom du fichier de cache
	 * - gram : Nom de la grammaire du squelette (détermine le phraseur à utiliser)
	 * - sourcefile : Chemin du squelette
	 * - squelette : Code du squelette
	 * - id_mere : Identifiant de la boucle parente
	 * - niv : Niveau de tabulation
	 *
	 * @var array 
	**/
	var $descr = array();
	/** @var string Identifiant de la boucle */
	var $id_boucle = '';
	/** @var int Numéro de ligne dans le code source du squelette */
	var $ligne = 0;
	/** @var string Langue d'exécution */
	var $lang = '';
	/** @var string Résultat de la compilation: toujours une expression PHP */
	var $code = '';
}


/**
 *  Description d'un texte
**/
class Texte {
	/** @var string Type de noeud */
	public $type = 'texte';

	/** @var string Le texte */
	public $texte;

	/**
	 * @var string
	 *    Contenu avant le texte. Vide ou apostrophe simple ou double
	 *    si le texte en était entouré
	 */
	public $avant = "";

	/**
	 * @var string
	 *    Contenu après le texte. Vide ou apostrophe simple ou double
	 *    si le texte en était entouré
	 */
	public $apres = "";

	/** @var int Numéro de ligne dans le code source du squelette */
	public $ligne = 0;
}

/**
 *  Description d'une inclusion de squelette
**/
class Inclure {
	var $type = 'include';
	var $texte;
	var $avant, $apres = ''; // inutilises mais generiques
	var $ligne = 0;
	var $param = array();  //  valeurs des params
}


/**
 * Description d'une boucle
**/
class Boucle {
	var $type = 'boucle';
	var $id_boucle;
	var $id_parent ='';
	var $avant, $milieu, $apres, $altern = '';
	var $lang_select;
	var $type_requete;
	var $table_optionnelle = false; # si ? dans <BOUCLE_x(table ?)>
	var $sql_serveur = '';
	var $param = array();
	var $criteres = array();
	var $separateur = array();
	var $jointures = array();
	var $jointures_explicites = false;
	var $doublons;
	var $partie, $total_parties,$mode_partie='';
	var $externe = ''; # appel a partir d'une autre boucle (recursion)
	// champs pour la construction de la requete SQL
	var $select = array();
	var $from = array();
	var $from_type = array();
	var $where = array();
	var $join = array();
	var $having = array();
	var $limit;
	var $group = array();
	var $order = array();
	var $default_order = array();
	var $date = 'date' ;
	var $hash = "" ;
	var $in = "" ;
	var $sous_requete = false;
	var $hierarchie = '';
	var $statut = false; # definition/surcharge du statut des elements retournes
	// champs pour la construction du corps PHP
	var $show = array();
	var $id_table;
	var $primary;
	var $return;
	var $numrows = false;
	var $cptrows = false;
	var $ligne = 0;
	var $descr =  array(); # noms des fichiers source et but etc

	var $modificateur = array(); // table pour stocker les modificateurs de boucle tels que tout, plat ..., utilisable par les plugins egalement

	var $iterateur = ''; // type d'iterateur

	// obsoletes, conserves provisoirement pour compatibilite
	var $tout = false;
	var $plat = false;
	var $lien = false;
}

/**
 * Description d'un critère de boucle
 *
 * Sous-noeud de Boucle
**/
class Critere {
	var $op;
	var $not;
	var $exclus;
	var $param = array();
	var $ligne = 0;
}

/**
 * Description d'un champ (balise SPIP)
**/
class Champ {
	var $type = 'champ';
	var $nom_champ;
	var $nom_boucle= ''; // seulement si boucle explicite
	var $avant, $apres; // tableaux d'objets
	var $etoile;
	var $param = array();  // filtre explicites
	var $fonctions = array();  // source des filtres (compatibilite)
	// champs pour la production de code
	var $id_boucle;
	var $boucles;
	var $type_requete;
	// resultat de la compilation:  toujours une expression PHP.
	// Chaine vide comme valeur par defaut (pour balise indefinie etc)
	var $code = ''; 
	var $interdire_scripts = true; // false si on est sur de cette balise
	// tableau pour la production de code dependant du contexte
	// id_mere;  pour TOTAL_BOUCLE hors du corps
	// document; pour embed et img dans les textes
	// sourcefile; pour DOSSIER_SQUELETTE
	var $descr = array();
	// pour localiser les erreurs
	var $ligne = 0;
	// un flag pour reperer les balises calculees par une fonction explicite
	var $balise_calculee = false;
}


/**
 * Description d'une chaîne de langue
**/
class Idiome {
	var $type = 'idiome';
	var $nom_champ = ""; // la chaine a traduire
	var $module = ""; // son module de definition
	var $arg = array(); // les arguments a passer a la chaine
	var $param = array(); // les filtres a appliquer au resultat
	var $fonctions = array(); // source des filtres  (compatibilite)
	var $avant, $apres; // inutilises mais faut = ci-dessus
	// champs pour la production de code, cf ci-dessus
	var $id_boucle;
	var $boucles;
	var $type_requete;
	// resultat de la compilation:  toujours une expression PHP.
	// Chaine vide comme valeur par defaut (n'arrive pas normalement)
	var $code = '';
	var $interdire_scripts = false;
	var $descr = array();
	var $ligne = 0;
}

/**
 * Description d'un texte polyglotte (<multi>)
**/
class Polyglotte {
	var $type = 'polyglotte';
	var $traductions = array(); // les textes ou choisir
	var $ligne = 0;
}



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
