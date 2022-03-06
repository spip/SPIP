<?php

namespace Spip\Core\Iterateur;

abstract class AbstractIterateur
{
	/**
	 * Erreur presente ?
	 *
	 * @var bool
	 */
	public $err = false;

	public $command;

	public $info;

	public function __construct($command, $info = []) {
		$this->command = $command;
		$this->info = $info;
	}
}
