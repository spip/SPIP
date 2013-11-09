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
 * Description d'un texte
 *
 * @package SPIP\Compilateur\AST
**/
class Texte
{
	/**
	 * Type de noeud 
	 * @var string */
	public $type = 'texte';

	/**
	 * Le texte
	 * @var string */
	public $texte;

	/**
	 * Contenu avant le texte.
	 *
	 * Vide ou apostrophe simple ou double si le texte en était entouré
	 * @var string|array */
	public $avant = "";

	/**
	 * Contenu après le texte.
	 *
	 * Vide ou apostrophe simple ou double si le texte en était entouré
	 * @var string|array */
	public $apres = "";

	/**
	 * Numéro de ligne dans le code source du squelette
	 * @var int  */
	public $ligne = 0;
}

/**
 * Description d'une inclusion de squelette
 *
 * @package SPIP\Compilateur\AST
**/
class Inclure
{
	/**
	 * Type de noeud 
	 * @var string */
	public $type = 'include';

	/**
	 * Nom d'un fichier inclu
	 * 
	 * - Objet Texte si inclusion d'un autre squelette
	 * - chaîne si inclusion d'un fichier PHP directement
	 * @var string|Texte */
	public $texte;

	/**
	 * Inutilisé, propriété générique de l'AST
	 * @var string|array */
	public $avant = '';

	/**
	 * Inutilisé, propriété générique de l'AST
	 * @var string|array */
	public $apres = '';

	/**
	 * Numéro de ligne dans le code source du squelette
	 * @var int  */
	public $ligne = 0;

	/**
	 * Valeurs des paramètres
	 * @var array */
	public $param = array();
}


/**
 * Description d'une boucle
 *
 * @package SPIP\Compilateur\AST
**/
class Boucle
{
	/**
	 * Type de noeud 
	 * @var string */
	public $type = 'boucle';

	/**
	 * Identifiant de la boucle
	 * @var string */
	public $id_boucle;

	/**
	 * Identifiant de la boucle parente
	 * @var string */
	public $id_parent ='';

	/**
	 * Partie optionnelle avant
	 * @var string|array */
	public $avant = '';

	/**
	 * Pour chaque élément
	 * @var string|array */
	public $milieu = '';

	/**
	 * Partie optionnelle après
	 * @var string|array */
	public $apres = '';

	/**
	 * Partie alternative, si pas de résultat dans la boucle
	 * @var string|array */
	public $altern = '';

	/**
	 * La boucle doit-elle sélectionner la langue ?
	 * @var string|null */
	public $lang_select;

	/**
	 * Alias de table d'application de la requête ou nom complet de la table SQL
	 * @var string|null */
	public $type_requete;

	/**
	 * La table est elle optionnelle ?
	 * 
	 * Si oui, aucune erreur ne sera générée si la table demandée n'est pas présente
	 * @var bool */
	public $table_optionnelle = false;

	/**
	 * Nom du fichier de connexion
	 * @var string */
	public $sql_serveur = '';

	/**
	 * Paramètres de la boucle
	 * 
	 * Description des paramètres passés à la boucle, qui servent ensuite
	 * au calcul des critères
	 *
	 * @var array */
	public $param = array();

	/**
	 * Critères de la boucle
	 * @var Critere[] */
	public $criteres = array();

	/**
	 * Textes insérés entre 2 éléments de boucle (critère inter)
	 * @var string[] */
	public $separateur = array();

	/**
	 * Liste des jointures possibles avec cette table
	 *
	 * Les jointures par défaut de la table sont complétées en priorité
	 * des jointures déclarées explicitement sur la boucle
	 * @see base_trouver_table_dist()
	 * @var array */
	public $jointures = array();

	/**
	 * Jointures explicites avec cette table
	 *
	 * Ces jointures sont utilisées en priorité par rapport aux jointures
	 * normales possibles pour retrouver les colonnes demandées extérieures
	 * à la boucle.
	 * @var string|bool */
	public $jointures_explicites = false;

	/**
	 * Nom de la variable PHP stockant le noms de doublons utilisés "$doublons_index"
	 * @var string|null */
	public $doublons;

	public $partie, $total_parties,$mode_partie='';
	public $externe = ''; # appel a partir d'une autre boucle (recursion)
	// champs pour la construction de la requete SQL
	public $select = array();
	public $from = array();
	public $from_type = array();
	public $where = array();
	public $join = array();
	public $having = array();
	public $limit;
	public $group = array();
	public $order = array();
	public $default_order = array();
	public $date = 'date' ;
	public $hash = "" ;
	public $in = "" ;
	public $sous_requete = false;
	public $hierarchie = '';
	public $statut = false; # definition/surcharge du statut des elements retournes
	// champs pour la construction du corps PHP
	public $show = array();
	public $id_table;
	public $primary;
	public $return;
	public $numrows = false;
	public $cptrows = false;

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
	 * @var array */
	public $descr = array();

	/**
	 * Numéro de ligne dans le code source du squelette
	 * @var int */
	public $ligne = 0;


	public $modificateur = array(); // table pour stocker les modificateurs de boucle tels que tout, plat ..., utilisable par les plugins egalement

	public $iterateur = ''; // type d'iterateur

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
 * @package SPIP\Compilateur\AST
**/
class Critere
{
	/**
	 * Type de noeud 
	 * @var string */
	public $type = 'critere';

	/**
	 * Opérateur (>, <, >=, IN, ...) 
	 * @var null|string */
	public $op;

	/**
	 * Présence d'une négation (truc !op valeur)
	 * @var null|string */
	public $not;

	/**
	 * Présence d'une exclusion (!truc op valeur)
	 * @var null|string */
	public $exclus;

	/**
	 * Paramètres du critère
	 * - $param[0] : élément avant l'opérateur
	 * - $param[1..n] : éléments après l'opérateur
	 * @var array */
	public $param = array();

	/**
	 * Numéro de ligne dans le code source du squelette
	 * @var int */
	public $ligne = 0;
}

/**
 * Description d'un champ (balise SPIP)
 *
 * @package SPIP\Compilateur\AST
**/
class Champ
{
	/**
	 * Type de noeud 
	 * @var string */
	public $type = 'champ';

	/**
	 * Nom du champ demandé. Exemple 'ID_ARTICLE' 
	 * @var string|null */
	public $nom_champ;

	/**
	 * Identifiant de la boucle parente si explicité
	 * @var string|null */
	public $nom_boucle= '';

	/**
	 * Partie optionnelle avant
	 * @var null|string|array */
	public $avant;

	/**
	 * Partie optionnelle après
	 * @var null|string|array */
	public $apres;

	/**
	 * Étoiles : annuler des automatismes
	 *
	 * - '*' annule les filtres automatiques
	 * - '**' annule en plus les protections de scripts
	 * @var null|string */
	public $etoile;

	/**
	 * Arguments et filtres explicites sur la balise
	 * - $param[0] contient les arguments de la balise
	 * - $param[1..n] contient les filtres à appliquer à la balise
	 * @var array */
	public $param = array(); 

	/**
	 * Source des filtres  (compatibilité) (?)
	 * @var array|null */
	public $fonctions = array();

	/**
	 * Identifiant de la boucle
	 * @var string */
	public $id_boucle = '';

	/**
	 * AST du squelette, liste de toutes les boucles
	 * @var Boucles[] */
	public $boucles;

	/**
	 * Alias de table d'application de la requête ou nom complet de la table SQL
	 * @var string|null */
	public $type_requete;

	/**
	 * Résultat de la compilation: toujours une expression PHP
	 * @var string */
	public $code = '';

	/**
	 * Interdire les scripts
	 * 
	 * false si on est sûr de cette balise
	 * @see interdire_scripts()
	 * @var bool */
	public $interdire_scripts = true;

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
	 * @var array */
	public $descr = array();

	/**
	 * Numéro de ligne dans le code source du squelette
	 * @var int */
	public $ligne = 0;

	/**
	 * Drapeau pour reperer les balises calculées par une fonction explicite
	 * @var bool */
	public $balise_calculee = false;
}


/**
 * Description d'une chaîne de langue
**/
class Idiome
{
	/**
	 * Type de noeud 
	 * @var string */
	public $type = 'idiome';

	/**
	 * Clé de traduction demandée. Exemple 'item_oui'
	 * @var string */
	public $nom_champ = "";

	/**
	 * Module de langue où chercher la clé de traduction. Exemple 'medias'
	 * @var string */
	public $module = "";

	/**
	 * Arguments à passer à la chaîne
	 * @var array */
	public $arg = array();

	/**
	 * Filtres à appliquer au résultat
	 * @var array */
	public $param = array();

	/**
	 * Source des filtres  (compatibilité) (?)
	 * @var array|null */
	public $fonctions = array();

	/**
	 * Inutilisé, propriété générique de l'AST
	 * @var string|array */
	public $avant = '';

	/**
	 * Inutilisé, propriété générique de l'AST
	 * @var string|array */
	public $apres = '';

	/**
	 * Identifiant de la boucle
	 * @var string */
	public $id_boucle = '';

	/**
	 * AST du squelette, liste de toutes les boucles
	 * @var Boucles[] */
	public $boucles;

	/**
	 * Alias de table d'application de la requête ou nom complet de la table SQL
	 * @var string|null */
	public $type_requete;

	/**
	 * Résultat de la compilation: toujours une expression PHP
	 * @var string */
	public $code = '';

	/**
	 * Interdire les scripts
	 * @see interdire_scripts()
	 * @var bool */
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
	 * @var array */
	public $descr = array();

	/**
	 * Numéro de ligne dans le code source du squelette
	 * @var int */
	public $ligne = 0;
}

/**
 * Description d'un texte polyglotte (<multi>)
 *
 * @package SPIP\Compilateur\AST
**/
class Polyglotte
{
	/**
	 * Type de noeud 
	 * @var string */
	public $type = 'polyglotte';

	/**
	 * Tableau des traductions possibles classées par langue
	 *
	 * Tableau code de langue => texte
	 * @var array */
	public $traductions = array();

	/**
	 * Numéro de ligne dans le code source du squelette
	 * @var int */
	public $ligne = 0;
}
