<?php

namespace Spip\I18n;

class Description {
	/** @var string code de langue (hors module) */
	public $code;
	/** @var string nom du module de langue */
	public $module;
	/** @var string langue de la traduction */
	public $langue;
	/** @var string traduction */
	public $texte;
	/** @var string var mode particulier appliqué ? */
	public $mode;
	/** @var bool Corrections des textes appliqué ? */
	public $corrections = false;
}
