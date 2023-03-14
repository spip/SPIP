<?php

namespace Spip\Css\Vars;

/**
 * Collection de variables CSS
 * @internal
 */
class Collection implements \Stringable {
	private array $vars = [];

	public function add(string $var, string $value) {
		$this->vars[$var] = $value;
	}

	public function getString(): string {
		$string = '';
		foreach ($this->vars as $key => $value) {
			$string .= "$key: $value;\n";
		}
		return $string;
	}

	public function __toString(): string {
		return $this->getString();
	}
}
