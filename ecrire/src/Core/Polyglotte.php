<?php

namespace Spip\Core;

/**
 * Description d'un texte polyglotte.
 * 
 * a.k.a. <multi>
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
