<?php

namespace Spip\Core;

/**
 * Description d'une boucle
 */
class Boucle
{
	/** Type de noeud */
	public string $type = 'boucle';

	/** Identifiant de la boucle */
	public string $id_boucle;

	/** Identifiant de la boucle parente */
	public string $id_parent = '';

	/** Un nom explicite qui peut être affecté manuellement à certaines boucles générées */
	public string $nom = '';

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
	 * Valeurs : '', 'oui', 'non'
	 */
	public string $lang_select = '';

	/**
	 * Alias de table d'application de la requête ou nom complet de la table SQL
	 *
	 * FIXME: un seul typage (string ?)
	 *
	 * @var string|false|null
	 */
	public $type_requete = null;

	/**
	 * La table est elle optionnelle ?
	 *
	 * Si oui, aucune erreur ne sera générée si la table demandée n'est pas présente
	 */
	public bool $table_optionnelle = false;

	/**
	 * Nom du fichier de connexion
	 */
	public string $sql_serveur = '';

	/**
	 * Paramètres de la boucle
	 *
	 * Description des paramètres passés à la boucle, qui servent ensuite
	 * au calcul des critères
	 *
	 * FIXME: type unique.
	 * @var false|array
	 *     - false: erreur de syntaxe
	 */
	public $param = [];

	/**
	 * Critères de la boucle
	 *
	 * FIXME: type array unique.
	 *
	 * @var string|Critere[]
	 * - string: phrasage (code brut). Il reste si erreur de critère
	 * - array: analyse correcte des critères...
	 */
	public $criteres = [];

	/**
	 * Textes insérés entre 2 éléments de boucle (critère inter)
	 *
	 * @var string[]
	 */
	public array $separateur = [];

	/**
	 * Liste des jointures possibles avec cette table
	 *
	 * Les jointures par défaut de la table sont complétées en priorité
	 * des jointures déclarées explicitement sur la boucle
	 *
	 * @see base_trouver_table_dist()
	 */
	public array $jointures = [];

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
	 */
	public string $doublons = '';

	/**
	 * Code PHP ajouté au début de chaque itération de boucle.
	 *
	 * Utilisé entre autre par les critères {pagination}, {n-a,b}, {a/b}...
	 */
	public string $partie = '';

	/**
	 * Nombre de divisions de la boucle, d'éléments à afficher,
	 * ou de soustractions d'éléments à faire
	 *
	 * Dans les critères limitant le nombre d'éléments affichés
	 * {a,b}, {a,n-b}, {a/b}, {pagination b}, b est affecté à total_parties.
	 */
	public string $total_parties = '';

	/**
	 * Code PHP ajouté avant l'itération de boucle.
	 *
	 * Utilisé entre autre par les critères {pagination}, {a,b}, {a/b}
	 * pour initialiser les variables de début et de fin d'itération.
	 */
	public string $mode_partie = '';

	/**
	 * Identifiant d'une boucle qui appelle celle-ci de manière récursive
	 *
	 * Si une boucle est appelée de manière récursive quelque part par
	 * une autre boucle comme <BOUCLE_rec(boucle_identifiant) />, cette
	 * boucle (identifiant) reçoit dans cette propriété l'identifiant
	 * de l'appelant (rec)
	 */
	public string $externe = '';

	// champs pour la construction de la requete SQL

	/**
	 * Liste des champs à récupérer par la boucle
	 *
	 * Expression 'table.nom_champ' ou calculée 'nom_champ AS x'
	 *
	 * @var string[]
	 */
	public array $select = [];

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
	public array $from = [];

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
	public array $from_type = [];

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
	 */
	public array $where = [];

	public array $join = [];
	public array $having = [];
	public $limit = '';
	public array $group = [];
	public array $order = [];
	public array $default_order = [];
	public string $date = 'date';
	public string $hash = '';
	public $in = '';
	public bool $sous_requete = false;

	/**
	 * Code PHP qui sera ajouté en tout début de la fonction de boucle
	 *
	 * Il sert à insérer le code calculant une hierarchie
	 */
	public string $hierarchie = '';

	// champs pour la construction du corps PHP

	/**
	 * Description des sources de données de la boucle
	 *
	 * Description des données de la boucle issu de trouver_table
	 * dans le cadre de l'itérateur SQL et contenant au moins l'index 'field'.
	 *
	 * @see base_trouver_table_dist()
	 */
	public array $show = [];

	/**
	 * Nom de la table SQL principale de la boucle, sans son préfixe
	 */
	public string $id_table = '';

	/**
	 * Nom de la clé primaire de la table SQL principale de la boucle
	 */
	public string $primary = '';

	/**
	 * Code PHP compilé de la boucle
	 *
	 * FIXME: un seul type (string ?)
	 *
	 * - false: boucle fautive ?
	 *
	 * @var string|false
	 */
	public $return = '';

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
	 */
	public array $descr = [];

	/** Numéro de ligne dans le code source du squelette */
	public int $ligne = 0;


	/**
	 * table pour stocker les modificateurs de boucle tels que tout, plat ...,
	 * utilisable par les plugins egalement
	 *
	 * @var array<string, mixed>
	 */
	public array $modificateur = [];

	/**
	 * Type d'itérateur utilisé pour cette boucle
	 *
	 * - 'SQL' dans le cadre d'une boucle sur une table SQL
	 * - 'DATA' pour l'itérateur DATA, ...
	 *
	 * @var string
	 */
	public string $iterateur = ''; // type d'iterateur

	/**
	 * @var array $debug Textes qui seront insérés dans l’entête de boucle du mode debug
	 */
	public array $debug = [];

	/**
	 * Index de la boucle dont le champ présent dans cette boucle est originaire,
	 * notamment si le champ a été trouve dans une boucle parente
	 *
	 * Tableau nom du champ => index de boucle
	*/
	public array $index_champ = [];

	/** Résultat de la compilation (?) (sert au débusqueur) */
	public string $code = '';

	/** Source des filtres (compatibilité) (?) */
	public array $fonctions = [];

	// obsoletes, conserves provisoirement pour compatibilite
	public $tout = false;
	public $plat = false;
	public $lien = false;
}
