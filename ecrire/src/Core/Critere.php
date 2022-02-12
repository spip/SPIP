<?php

namespace Spip\Core;

/**
 * Description d'un critère de boucle.
 *
 * Sous-noeud de Boucle
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
	public $param = [];

	/**
	 * Numéro de ligne dans le code source du squelette
	 *
	 * @var int
	 */
	public $ligne = 0;
}
