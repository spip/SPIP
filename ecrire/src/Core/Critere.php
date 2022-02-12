<?php

namespace Spip\Core;

/**
 * Description d'un critère de boucle.
 *
 * Sous-noeud de Boucle
 **/
class Critere {
	/** Type de noeud */
	public string $type = 'critere';

	/** Opérateur (>, <, >=, IN, ...) */
	public ?string $op;

	/** Présence d'une négation (truc !op valeur) */
	public bool $not = false;

	/** Présence d'une exclusion (!truc op valeur) */
	public string $exclus = '';

	/** Présence d'une condition dans le critère (truc ?) */
	public bool $cond = false;

	/**
	 * Paramètres du critère
	 * - $param[0] : élément avant l'opérateur
	 * - $param[1..n] : éléments après l'opérateur
	 *
	 * FIXME: type unique.
	 * @var false|array
	 *     - false: erreur de syntaxe
	 */
	public $param = [];

	/** Numéro de ligne dans le code source du squelette */
	public int $ligne = 0;
}
