<?php

namespace Spip\Compilateur\Noeud;

/**
 * Description d'une inclusion de squelette.
 **/
class Inclure
{
	/** Type de noeud */
	public string $type = 'include';

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

	/** Numéro de ligne dans le code source du squelette */
	public int $ligne = 0;

	/**
	 * Valeurs des paramètres
	 *
	 * FIXME: type unique.
	 * @var false|array
	 *     - false: erreur de syntaxe
	 */
	public $param = [];

	/** Source des filtres (compatibilité) (?) */
	public array $fonctions = [];

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
}
