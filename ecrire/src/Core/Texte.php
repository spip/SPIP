<?php

namespace Spip\Core;

/**
 * Description d'un texte.
 **/
class Texte {
	/**
	 * Type de noeud
	 *
	 * @var string
	 */
	public $type = 'texte';

	/**
	 * Le texte
	 *
	 * @var string
	 */
	public $texte;

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

	/**
	 * Numéro de ligne dans le code source du squelette
	 *
	 * @var int
	 */
	public $ligne = 0;
}
