<?php

namespace Spip\Core;

/**
 * Description d'une chaîne de langue
 **/
class Idiome
{
	/** Type de noeud */
	public string $type = 'idiome';

	/** Clé de traduction demandée. Exemple 'item_oui' */
	public string $nom_champ = '';

	/** Module de langue où chercher la clé de traduction. Exemple 'medias' */
	public string $module = '';

	/** Arguments à passer à la chaîne */
	public array $arg = [];

	/** Filtres à appliquer au résultat */
	public array $param = [];

	/** Source des filtres (compatibilité) (?) */
	public array $fonctions = [];

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

	/** Identifiant de la boucle */
	public string $id_boucle = '';

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
	 * @see interdire_scripts()
	 */
	public bool $interdire_scripts = false;

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
	 */
	public array $descr = [];

	/** Numéro de ligne dans le code source du squelette */
	public int $ligne = 0;
}
