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

	public function __construct(
		protected array $command,
		protected array $info = []
	)
	{
	}
}
