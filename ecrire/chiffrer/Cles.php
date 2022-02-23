<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
 * \***************************************************************************/

namespace Spip\Core\Chiffrer;

class Cles implements \Countable /* , ContainerInterface */ {
	private array $keys;
	public function __construct(array $keys) {
        $this->keys = $keys;
	}

	public function has(string $name): bool {
		return array_key_exists($name, $this->keys);
	}

	public function get(string $name): ?string {
		return $this->keys[$name] ?? null;
	}

    public function generate(string $name): string {
        $key = Chiffrement::keygen();
        $this->keys[$name] = $key;
        spip_log("Création de la cle $name", 'chiffrer' . _LOG_INFO_IMPORTANTE);
        return $key;
    }

	public function set(
		string $name, 
		#[\SensitiveParameter]
		string $key
	): void {
		$this->keys[$name] = $key;
	}

	public function count(): int {
		return count($this->keys);
	}

	public function toJson(): string {
		$json = array_map('base64_encode', $this->keys);
		return \json_encode($json);
	}
}
