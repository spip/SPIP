<?php

namespace Spip\Compilateur\Noeud;

/**
 * Description d'une Balise.
 */
class Balise
{
	/** Type de noeud */
	public string $type = 'balise';

	/** Nom du champ demandé. Exemple 'ID_ARTICLE' */
	public ?string $nom_champ;

	/** Identifiant de la boucle parente si explicité */
	public ?string $nom_boucle = '';

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
	 */
	public ?string $etoile;

	/**
	 * Arguments et filtres explicites sur la balise
	 *
	 * - $param[0] contient les arguments de la balise
	 * - $param[1..n] contient les filtres à appliquer à la balise
	 *
	 * FIXME: type unique.
	 * @var false|array
	 *     - false: erreur de syntaxe
	 */
	public $param = [];

	/** Source des filtres (compatibilité) (?) */
	public array $fonctions = [];

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
	public array $boucles;

	/** Alias de table d'application de la requête ou nom complet de la table SQL */
	public ?string $type_requete;

	/** Résultat de la compilation: toujours une expression PHP */
	public string $code = '';

	/**
	 * Interdire les scripts
	 *
	 * false si on est sûr de cette balise
	 *
	 * @see interdire_scripts()
	 */
	public bool $interdire_scripts = true;

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

	/** Numéro de ligne dans le code source du squelette*/
	public int $ligne = 0;

	/** Drapeau pour reperer les balises calculées par une fonction explicite */
	public bool $balise_calculee = false;
}
