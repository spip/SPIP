<?php

namespace Spip\Core;

/**
 * Description d'une inclusion de squelette.
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
