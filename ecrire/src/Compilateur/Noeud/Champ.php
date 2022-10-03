<?php

namespace Spip\Compilateur\Noeud;

/**
 * Retro compatiblité un Champ, c'est une Balise.
 */
class Champ extends Balise
{
	/**
	 * {@inheritDoc}
	 */
	public string $type = 'champ';
}
