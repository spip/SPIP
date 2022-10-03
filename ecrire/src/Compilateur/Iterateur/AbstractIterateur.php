<?php

namespace Spip\Compilateur\Iterateur;

abstract class AbstractIterateur
{
	protected string $type;

	/**
	 * Calcul du total des elements
	 *
	 * @var int|null
	 **/
	public $total = null;

	/** Erreur presente ? **/
	public bool $err = false;

	protected array $command = [];

	protected array $info = [];

	public function __construct(array $command, array $info = []) {
		$this->command = $command;
		$this->info = $info;
	}
}
