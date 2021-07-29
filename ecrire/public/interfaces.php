<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Définition des noeuds de l'arbre de syntaxe abstraite
 *
 * @package SPIP\Core\Compilateur\AST
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Description d'un contexte de compilation
 *
 * Objet simple pour stocker le nom du fichier, la ligne, la boucle
 * permettant entre autre de localiser le lieu d'une erreur de compilation.
 * Cette structure est nécessaire au traitement d'erreur à l'exécution.
 *
 * Le champ code est inutilisé dans cette classe seule, mais harmonise
 * le traitement d'erreurs.
 *
 * @package SPIP\Core\Compilateur\AST
 */
class Contexte {
	/**
	 * Description du squelette
	 *
	 * Sert pour la gestion d'erreur et la production de code dependant du contexte
	 *
	 * Peut contenir les index :
	 *
	 * - nom : Nom du fichier de cache
	 * - gram : Nom de la grammaire du squelette (détermine le phraseur à utiliser)
	 * - sourcefile : Chemin du squelette
	 * - squelette : Code du squelette
	 * - id_mere : Identifiant de la boucle parente
	 * - documents : Pour embed et img dans les textes
	 * - session : Pour un cache sessionné par auteur
	 * - niv : Niveau de tabulation
	 *
	 * @var array
	 */
	public $descr = array();

	/**
	 * Identifiant de la boucle
	 *
	 * @var string
	 */
	public $id_boucle = '';

	/**
	 * Numéro de ligne dans le code source du squelette
	 *
	 * @var int
	 */
	public $ligne = 0;

	/**
	 * Langue d'exécution
	 *
	 * @var string
	 */
	public $lang = '';

	/**
	 * Résultat de la compilation: toujours une expression PHP
	 *
	 * @var string
	 */
	public $code = '';
}


/**
 * Description d'un texte
 *
 * @package SPIP\Core\Compilateur\AST
 **/
class Texte {
	/**
	 * Type de noeud
	 *
	 * @var string
	 */
	public $type = 'texte';

	/**
	 * Le texte
	 *
	 * @var string
	 */
	public $texte;

	/**
	 * Contenu avant le texte.
	 *
	 * Vide ou apostrophe simple ou double si le texte en était entouré
	 *
	 * @var string|array
	 */
	public $avant = "";

	/**
	 * Contenu après le texte.
	 *
	 * Vide ou apostrophe simple ou double si le texte en était entouré
	 *
	 * @var string|array
	 */
	public $apres = "";

	/**
	 * Numéro de ligne dans le code source du squelette
	 *
	 * @var int
	 */
	public $ligne = 0;
}

/**
 * Description d'une inclusion de squelette
 *
 * @package SPIP\Core\Compilateur\AST
 **/
class Inclure {
	/**
	 * Type de noeud
	 *
	 * @var string
	 */
	public $type = 'include';

	/**
	 * Nom d'un fichier inclu
	 *
	 * - Objet Texte si inclusion d'un autre squelette
	 * - chaîne si inclusion d'un fichier PHP directement
	 *
	 * @var string|Texte
	 */
	public $texte;

	/**
	 * Inutilisé, propriété générique de l'AST
	 *
	 * @var string|array
	 */
	public $avant = '';

	/**
	 * Inutilisé, propriété générique de l'AST
	 *
	 * @var string|array
	 */
	public $apres = '';

	/**
	 * Numéro de ligne dans le code source du squelette
	 *
	 * @var int
	 */
	public $ligne = 0;

	/**
	 * Valeurs des paramètres
	 *
	 * @var array
	 */
	public $param = array();
}


/**
 * Description d'une boucle
 *
 * @package SPIP\Core\Compilateur\AST
 **/
class Boucle {
	/**
	 * Type de noeud
	 *
	 * @var string
	 */
	public $type = 'boucle';

	/**
	 * Identifiant de la boucle
	 *
	 * @var string
	 */
	public $id_boucle;

	/**
	 * Identifiant de la boucle parente
	 *
	 * @var string
	 */
	public $id_parent = '';

	/**
	 * Partie avant toujours affichee
	 *
	 * @var string|array
	 */
	public $preaff = '';

	/**
	 * Partie optionnelle avant
	 *
	 * @var string|array
	 */
	public $avant = '';

	/**
	 * Pour chaque élément
	 *
	 * @var string|array
	 */
	public $milieu = '';

	/**
	 * Partie optionnelle après
	 *
	 * @var string|array
	 */
	public $apres = '';

	/**
	 * Partie alternative, si pas de résultat dans la boucle
	 *
	 * @var string|array
	 */
	public $altern = '';

	/**
	 * Partie apres toujours affichee
	 *
	 * @var string|array
	 */
	public $postaff = '';


	/**
	 * La boucle doit-elle sélectionner la langue ?
	 *
	 * @var string|null
	 */
	public $lang_select;

	/**
	 * Alias de table d'application de la requête ou nom complet de la table SQL
	 *
	 * @var string|null
	 */
	public $type_requete;

	/**
	 * La table est elle optionnelle ?
	 *
	 * Si oui, aucune erreur ne sera générée si la table demandée n'est pas présente
	 *
	 * @var bool
	 */
	public $table_optionnelle = false;

	/**
	 * Nom du fichier de connexion
	 *
	 * @var string
	 */
	public $sql_serveur = '';

	/**
	 * Paramètres de la boucle
	 *
	 * Description des paramètres passés à la boucle, qui servent ensuite
	 * au calcul des critères
	 *
	 * @var array
	 */
	public $param = array();

	/**
	 * Critères de la boucle
	 *
	 * @var Critere[]
	 */
	public $criteres = array();

	/**
	 * Textes insérés entre 2 éléments de boucle (critère inter)
	 *
	 * @var string[]
	 */
	public $separateur = array();

	/**
	 * Liste des jointures possibles avec cette table
	 *
	 * Les jointures par défaut de la table sont complétées en priorité
	 * des jointures déclarées explicitement sur la boucle
	 *
	 * @see base_trouver_table_dist()
	 * @var array
	 */
	public $jointures = array();

	/**
	 * Jointures explicites avec cette table
	 *
	 * Ces jointures sont utilisées en priorité par rapport aux jointures
	 * normales possibles pour retrouver les colonnes demandées extérieures
	 * à la boucle.
	 *
	 * @var string|bool
	 */
	public $jointures_explicites = false;

	/**
	 * Nom de la variable PHP stockant le noms de doublons utilisés "$doublons_index"
	 *
	 * @var string|null
	 */
	public $doublons;

	/**
	 * Code PHP ajouté au début de chaque itération de boucle.
	 *
	 * Utilisé entre autre par les critères {pagination}, {n-a,b}, {a/b}...
	 *
	 * @var string
	 */
	public $partie = "";

	/**
	 * Nombre de divisions de la boucle, d'éléments à afficher,
	 * ou de soustractions d'éléments à faire
	 *
	 * Dans les critères limitant le nombre d'éléments affichés
	 * {a,b}, {a,n-b}, {a/b}, {pagination b}, b est affecté à total_parties.
	 *
	 * @var string
	 */
	public $total_parties = "";

	/**
	 * Code PHP ajouté avant l'itération de boucle.
	 *
	 * Utilisé entre autre par les critères {pagination}, {a,b}, {a/b}
	 * pour initialiser les variables de début et de fin d'itération.
	 *
	 * @var string
	 */
	public $mode_partie = '';

	/**
	 * Identifiant d'une boucle qui appelle celle-ci de manière récursive
	 *
	 * Si une boucle est appelée de manière récursive quelque part par
	 * une autre boucle comme <BOUCLE_rec(boucle_identifiant) />, cette
	 * boucle (identifiant) reçoit dans cette propriété l'identifiant
	 * de l'appelant (rec)
	 *
	 * @var string
	 */
	public $externe = '';

	// champs pour la construction de la requete SQL

	/**
	 * Liste des champs à récupérer par la boucle
	 *
	 * Expression 'table.nom_champ' ou calculée 'nom_champ AS x'
	 *
	 * @var string[]
	 */
	public $select = array();

	/**
	 * Liste des alias / tables SQL utilisées dans la boucle
	 *
	 * L'index est un identifiant (xx dans spip_xx assez souvent) qui servira
	 * d'alias au nom de la table ; la valeur est le nom de la table SQL désirée.
	 *
	 * L'index 0 peut définir le type de sources de données de l'itérateur DATA
	 *
	 * @var string[]
	 */
	public $from = array();

	/**
	 * Liste des alias / type de jointures utilisées dans la boucle
	 *
	 * L'index est le nom d'alias (comme pour la propriété $from), et la valeur
	 * un type de jointure parmi 'INNER', 'LEFT', 'RIGHT', 'OUTER'.
	 *
	 * Lorsque le type n'est pas déclaré pour un alias, c'est 'INNER'
	 * qui sera utilisé par défaut (créant donc un INNER JOIN).
	 *
	 * @var string[]
	 */
	public $from_type = array();

	/**
	 * Liste des conditions WHERE de la boucle
	 *
	 * Permet de restreindre les éléments retournés par une boucle
	 * en fonctions des conditions transmises dans ce tableau.
	 *
	 * Ce tableau peut avoir plusieurs niveaux de profondeur.
	 *
	 * Les éléments du premier niveau sont reliés par des AND, donc
	 * chaque élément ajouté directement au where par
	 * $boucle->where[] = array(...) ou $boucle->where[] = "'expression'"
	 * est une condition AND en plus.
	 *
	 * Par contre, lorsqu'on indique un tableau, il peut décrire des relations
	 * internes différentes. Soit $expr un tableau d'expressions quelconques de 3 valeurs :
	 * $expr = array(operateur, val1, val2)
	 *
	 * Ces 3 valeurs sont des expressions PHP. L'index 0 désigne l'opérateur
	 * à réaliser tel que :
	 *
	 * - "'='" , "'>='", "'<'", "'IN'", "'REGEXP'", "'LIKE'", ... :
	 *    val1 et val2 sont des champs et valeurs à utiliser dans la comparaison
	 *    suivant cet ordre : "val1 operateur val2".
	 *    Exemple : $boucle->where[] = array("'='", "'articles.statut'", "'\"publie\"'");
	 * - "'AND'", "'OR'", "'NOT'" :
	 *    dans ce cas val1 et val2 sont également des expressions
	 *    de comparaison complètes, et peuvent être eux-même des tableaux comme $expr
	 *    Exemples :
	 *    $boucle->where[] = array("'OR'", $expr1, $expr2);
	 *    $boucle->where[] = array("'NOT'", $expr); // val2 n'existe pas avec NOT
	 *
	 * D'autres noms sont possibles pour l'opérateur (le nombre de valeurs diffère) :
	 * - "'SELF'", "'SUBSELECT'" : indiquent des sous requêtes
	 * - "'?'" : indique une condition à faire évaluer (val1 ? val2 : val3)
	 *
	 * @var array
	 */
	public $where = array();

	public $join = array();
	public $having = array();
	public $limit;
	public $group = array();
	public $order = array();
	public $default_order = array();
	public $date = 'date';
	public $hash = "";
	public $in = "";
	public $sous_requete = false;

	/**
	 * Code PHP qui sera ajouté en tout début de la fonction de boucle
	 *
	 * Il sert à insérer le code calculant une hierarchie
	 *
	 * @var string
	 */
	public $hierarchie = '';

	// champs pour la construction du corps PHP

	/**
	 * Description des sources de données de la boucle
	 *
	 * Description des données de la boucle issu de trouver_table
	 * dans le cadre de l'itérateur SQL et contenant au moins l'index 'field'.
	 *
	 * @see base_trouver_table_dist()
	 * @var array
	 */
	public $show = array();

	/**
	 * Nom de la table SQL principale de la boucle, sans son préfixe
	 *
	 * @var string
	 */
	public $id_table;

	/**
	 * Nom de la clé primaire de la table SQL principale de la boucle
	 *
	 * @var string
	 */
	public $primary;

	/**
	 * Code PHP compilé de la boucle
	 *
	 * @var string
	 */
	public $return;

	public $numrows = false;
	public $cptrows = false;

	/**
	 * Description du squelette
	 *
	 * Sert pour la gestion d'erreur et la production de code dependant du contexte
	 *
	 * Peut contenir les index :
	 *
	 * - nom : Nom du fichier de cache
	 * - gram : Nom de la grammaire du squelette (détermine le phraseur à utiliser)
	 * - sourcefile : Chemin du squelette
	 * - squelette : Code du squelette
	 * - id_mere : Identifiant de la boucle parente
	 * - documents : Pour embed et img dans les textes
	 * - session : Pour un cache sessionné par auteur
	 * - niv : Niveau de tabulation
	 *
	 * @var array
	 */
	public $descr = array();

	/**
	 * Numéro de ligne dans le code source du squelette
	 *
	 * @var int
	 */
	public $ligne = 0;


	public $modificateur = array(); // table pour stocker les modificateurs de boucle tels que tout, plat ..., utilisable par les plugins egalement

	/**
	 * Type d'itérateur utilisé pour cette boucle
	 *
	 * - 'SQL' dans le cadre d'une boucle sur une table SQL
	 * - 'DATA' pour l'itérateur DATA, ...
	 *
	 * @var string
	 */
	public $iterateur = ''; // type d'iterateur

	/**
	 * @var array $debug Textes qui seront insérés dans l’entête de boucle du mode debug
	 */
	public $debug = [];

	/** 
	 * Index de la boucle dont le champ présent dans cette boucle est originaire,
	 * notamment si le champ a été trouve dans une boucle parente
	 * 
	 * Tableau nom du champ => index de boucle
	 * 
	 * @var array $index_champ 
	*/
	public $index_champ = [];

	// obsoletes, conserves provisoirement pour compatibilite
	public $tout = false;
	public $plat = false;
	public $lien = false;
}

/**
 * Description d'un critère de boucle
 *
 * Sous-noeud de Boucle
 *
 * @package SPIP\Core\Compilateur\AST
 **/
class Critere {
	/**
	 * Type de noeud
	 *
	 * @var string
	 */
	public $type = 'critere';

	/**
	 * Opérateur (>, <, >=, IN, ...)
	 *
	 * @var null|string
	 */
	public $op;

	/**
	 * Présence d'une négation (truc !op valeur)
	 *
	 * @var null|string
	 */
	public $not;

	/**
	 * Présence d'une exclusion (!truc op valeur)
	 *
	 * @var null|string
	 */
	public $exclus;

	/**
	 * Présence d'une condition dans le critère (truc ?)
	 *
	 * @var bool
	 */
	public $cond = false;

	/**
	 * Paramètres du critère
	 * - $param[0] : élément avant l'opérateur
	 * - $param[1..n] : éléments après l'opérateur
	 *
	 * @var array
	 */
	public $param = array();

	/**
	 * Numéro de ligne dans le code source du squelette
	 *
	 * @var int
	 */
	public $ligne = 0;
}

/**
 * Description d'un champ (balise SPIP)
 *
 * @package SPIP\Core\Compilateur\AST
 **/
class Champ {
	/**
	 * Type de noeud
	 *
	 * @var string
	 */
	public $type = 'champ';

	/**
	 * Nom du champ demandé. Exemple 'ID_ARTICLE'
	 *
	 * @var string|null
	 */
	public $nom_champ;

	/**
	 * Identifiant de la boucle parente si explicité
	 *
	 * @var string|null
	 */
	public $nom_boucle = '';

	/**
	 * Partie optionnelle avant
	 *
	 * @var null|string|array
	 */
	public $avant;

	/**
	 * Partie optionnelle après
	 *
	 * @var null|string|array
	 */
	public $apres;

	/**
	 * Étoiles : annuler des automatismes
	 *
	 * - '*' annule les filtres automatiques
	 * - '**' annule en plus les protections de scripts
	 *
	 * @var null|string
	 */
	public $etoile;

	/**
	 * Arguments et filtres explicites sur la balise
	 *
	 * - $param[0] contient les arguments de la balise
	 * - $param[1..n] contient les filtres à appliquer à la balise
	 *
	 * @var array
	 */
	public $param = array();

	/**
	 * Source des filtres  (compatibilité) (?)
	 *
	 * @var array|null
	 */
	public $fonctions = array();

	/**
	 * Identifiant de la boucle
	 *
	 * @var string
	 */
	public $id_boucle = '';

	/**
	 * AST du squelette, liste de toutes les boucles
	 *
	 * @var Boucles[]
	 */
	public $boucles;

	/**
	 * Alias de table d'application de la requête ou nom complet de la table SQL
	 *
	 * @var string|null
	 */
	public $type_requete;

	/**
	 * Résultat de la compilation: toujours une expression PHP
	 *
	 * @var string
	 */
	public $code = '';

	/**
	 * Interdire les scripts
	 *
	 * false si on est sûr de cette balise
	 *
	 * @see interdire_scripts()
	 * @var bool
	 */
	public $interdire_scripts = true;

	/**
	 * Description du squelette
	 *
	 * Sert pour la gestion d'erreur et la production de code dependant du contexte
	 *
	 * Peut contenir les index :
	 *
	 * - nom : Nom du fichier de cache
	 * - gram : Nom de la grammaire du squelette (détermine le phraseur à utiliser)
	 * - sourcefile : Chemin du squelette
	 * - squelette : Code du squelette
	 * - id_mere : Identifiant de la boucle parente
	 * - documents : Pour embed et img dans les textes
	 * - session : Pour un cache sessionné par auteur
	 * - niv : Niveau de tabulation
	 *
	 * @var array
	 */
	public $descr = array();

	/**
	 * Numéro de ligne dans le code source du squelette
	 *
	 * @var int
	 */
	public $ligne = 0;

	/**
	 * Drapeau pour reperer les balises calculées par une fonction explicite
	 *
	 * @var bool
	 */
	public $balise_calculee = false;
}


/**
 * Description d'une chaîne de langue
 **/
class Idiome {
	/**
	 * Type de noeud
	 *
	 * @var string
	 */
	public $type = 'idiome';

	/**
	 * Clé de traduction demandée. Exemple 'item_oui'
	 *
	 * @var string
	 */
	public $nom_champ = "";

	/**
	 * Module de langue où chercher la clé de traduction. Exemple 'medias'
	 *
	 * @var string
	 */
	public $module = "";

	/**
	 * Arguments à passer à la chaîne
	 *
	 * @var array
	 */
	public $arg = array();

	/**
	 * Filtres à appliquer au résultat
	 *
	 * @var array
	 */
	public $param = array();

	/**
	 * Source des filtres  (compatibilité) (?)
	 *
	 * @var array|null
	 */
	public $fonctions = array();

	/**
	 * Inutilisé, propriété générique de l'AST
	 *
	 * @var string|array
	 */
	public $avant = '';

	/**
	 * Inutilisé, propriété générique de l'AST
	 *
	 * @var string|array
	 */
	public $apres = '';

	/**
	 * Identifiant de la boucle
	 *
	 * @var string
	 */
	public $id_boucle = '';

	/**
	 * AST du squelette, liste de toutes les boucles
	 *
	 * @var Boucles[]
	 */
	public $boucles;

	/**
	 * Alias de table d'application de la requête ou nom complet de la table SQL
	 *
	 * @var string|null
	 */
	public $type_requete;

	/**
	 * Résultat de la compilation: toujours une expression PHP
	 *
	 * @var string
	 */
	public $code = '';

	/**
	 * Interdire les scripts
	 *
	 * @see interdire_scripts()
	 * @var bool
	 */
	public $interdire_scripts = false;

	/**
	 * Description du squelette
	 *
	 * Sert pour la gestion d'erreur et la production de code dependant du contexte
	 *
	 * Peut contenir les index :
	 * - nom : Nom du fichier de cache
	 * - gram : Nom de la grammaire du squelette (détermine le phraseur à utiliser)
	 * - sourcefile : Chemin du squelette
	 * - squelette : Code du squelette
	 * - id_mere : Identifiant de la boucle parente
	 * - documents : Pour embed et img dans les textes
	 * - session : Pour un cache sessionné par auteur
	 * - niv : Niveau de tabulation
	 *
	 * @var array
	 */
	public $descr = array();

	/**
	 * Numéro de ligne dans le code source du squelette
	 *
	 * @var int
	 */
	public $ligne = 0;
}

/**
 * Description d'un texte polyglotte (<multi>)
 *
 * @package SPIP\Core\Compilateur\AST
 **/
class Polyglotte {
	/**
	 * Type de noeud
	 *
	 * @var string
	 */
	public $type = 'polyglotte';

	/**
	 * Tableau des traductions possibles classées par langue
	 *
	 * Tableau code de langue => texte
	 *
	 * @var array
	 */
	public $traductions = array();

	/**
	 * Numéro de ligne dans le code source du squelette
	 *
	 * @var int
	 */
	public $ligne = 0;
}


global $table_criteres_infixes;
$table_criteres_infixes = array('<', '>', '<=', '>=', '==', '===', '!=', '!==', '<>', '?');

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
 * @link https://code.spip.net/@declarer_interfaces
 *
 * @return void
 */
function declarer_interfaces() {

	$GLOBALS['table_des_tables']['articles'] = 'articles';
	$GLOBALS['table_des_tables']['auteurs'] = 'auteurs';
	$GLOBALS['table_des_tables']['rubriques'] = 'rubriques';
	$GLOBALS['table_des_tables']['hierarchie'] = 'rubriques';

	// definition des statuts de publication
	$GLOBALS['table_statut'] = array();

	//
	// tableau des tables de jointures
	// Ex: gestion du critere {id_mot} dans la boucle(ARTICLES)
	$GLOBALS['tables_jointures'] = array();
	$GLOBALS['tables_jointures']['spip_jobs'][] = 'jobs_liens';

	// $GLOBALS['exceptions_des_jointures']['titre_mot'] = array('spip_mots', 'titre'); // pour exemple
	$GLOBALS['exceptions_des_jointures']['profondeur'] = array('spip_rubriques', 'profondeur');


	if (!defined('_TRAITEMENT_TYPO')) {
		define('_TRAITEMENT_TYPO', 'typo(%s, "TYPO", $connect, $Pile[0])');
	}
	if (!defined('_TRAITEMENT_RACCOURCIS')) {
		define('_TRAITEMENT_RACCOURCIS', 'propre(%s, $connect, $Pile[0])');
	}
	if (!defined('_TRAITEMENT_TYPO_SANS_NUMERO')) {
		define('_TRAITEMENT_TYPO_SANS_NUMERO', 'supprimer_numero(typo(%s, "TYPO", $connect, $Pile[0]))');
	}
	$GLOBALS['table_des_traitements']['BIO'][] = _TRAITEMENT_RACCOURCIS;
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
	$interfaces = pipeline('declarer_tables_interfaces',
		array(
			'table_des_tables' => $GLOBALS['table_des_tables'],
			'exceptions_des_tables' => $GLOBALS['exceptions_des_tables'],
			'table_date' => $GLOBALS['table_date'],
			'table_titre' => $GLOBALS['table_titre'],
			'tables_jointures' => $GLOBALS['tables_jointures'],
			'exceptions_des_jointures' => $GLOBALS['exceptions_des_jointures'],
			'table_des_traitements' => $GLOBALS['table_des_traitements'],
			'table_statut' => $GLOBALS['table_statut'],
		));
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
