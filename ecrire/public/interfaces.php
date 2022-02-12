<?php

use Spip\Core\Boucle;

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
	public $descr = [];

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
	public $avant = '';

	/**
	 * Contenu après le texte.
	 *
	 * Vide ou apostrophe simple ou double si le texte en était entouré
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
	public $param = [];
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
	public $nom_champ = '';

	/**
	 * Module de langue où chercher la clé de traduction. Exemple 'medias'
	 *
	 * @var string
	 */
	public $module = '';

	/**
	 * Arguments à passer à la chaîne
	 *
	 * @var array
	 */
	public $arg = [];

	/**
	 * Filtres à appliquer au résultat
	 *
	 * @var array
	 */
	public $param = [];

	/**
	 * Source des filtres  (compatibilité) (?)
	 *
	 * @var array|null
	 */
	public $fonctions = [];

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
	 * @var Boucle[]
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
	public $descr = [];

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
	public $traductions = [];

	/**
	 * Numéro de ligne dans le code source du squelette
	 *
	 * @var int
	 */
	public $ligne = 0;
}


global $table_criteres_infixes;
$table_criteres_infixes = ['<', '>', '<=', '>=', '==', '===', '!=', '!==', '<>', '?'];

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
	$GLOBALS['table_des_traitements']['NOM_SITE']['spip_auteurs'] = 'entites_html(%s)';
	$GLOBALS['table_des_traitements']['NOM']['spip_auteurs'] = 'safehtml(' . _TRAITEMENT_TYPO_SANS_NUMERO . ')';
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
