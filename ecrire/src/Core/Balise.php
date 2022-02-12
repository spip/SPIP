<?php

namespace Spip\Core;

/**
 * Description d'une Balise
 */
class Balise
{
	/**
	 * Type de noeud
	 *
	 * @var string
	 */
	public $type = 'balise';

	/**
	 * Nom de la balise demandée. Exemple 'ID_ARTICLE'
	 *
	 * @var string|null
	 */
	public $nom;

	/**
	 * Identifiant de la boucle parente si explicité
	 *
	 * @var string
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
	 * @var string
	 */
	public $etoile = '';

	/**
	 * Arguments et filtres explicites sur la balise
	 *
	 * - $param[0] contient les arguments de la balise
	 * - $param[1..n] contient les filtres à appliquer à la balise
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
	public $descr = [];

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
