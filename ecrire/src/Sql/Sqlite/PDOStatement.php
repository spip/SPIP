<?php

namespace Spip\Sql\Sqlite;

/**
 * Pouvoir retrouver le PDO utilisé pour générer un résultat de requête.
 */
final class PDOStatement extends \PDOStatement {
	private function __construct(private \PDO &$PDO)
	{
	}

	public function getPDO(): \PDO {
		return $this->PDO;
	}
}
