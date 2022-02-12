<?php

namespace Spip\Core;

/**
 * Description d'un texte polyglotte.
 *
 * a.k.a. <multi>
 **/
class Polyglotte
{
	/** Type de noeud */
	public string $type = 'polyglotte';

	/**
	 * Tableau des traductions possibles classées par langue
	 *
	 * Tableau code de langue => texte
	 */
	public array $traductions = [];

	/** Numéro de ligne dans le code source du squelette */
	public int $ligne = 0;
}
