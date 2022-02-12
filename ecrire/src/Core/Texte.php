<?php

namespace Spip\Core;

/**
 * Description d'un texte.
 **/
class Texte
{
	/** Type de noeud */
	public string $type = 'texte';

	/** Le texte */
	public string $texte;

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

	/** Numéro de ligne dans le code source du squelette */
	public int $ligne = 0;
}
