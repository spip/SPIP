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

final class SpipCles extends Cles {
	private static array $instances = [];

	private string $file = _DIR_ETC . "cles.php";

	public static function instance(string $file = ''): self {
		if (empty(self::$instances[$file])) {
			self::$instances[$file] = new self($file);
		}
		return self::$instances[$file];
	}

	private function __construct(string $file = '') {
		if ($file) {
			$this->file = $file;
		}
		parent::__construct($this->read());
	}

	private function getKey(string $name, bool $autoInit): ?string {
		if ($this->has($name)) {
			return $this->get($name);
		}
		if ($autoInit) {
			$this->generate($name);
			$this->save();
			return $this->get($name);
		}
        return null;
	}

	public function getSecretSite(bool $autoInit = true): ?string {
		return $this->getKey('secret_du_site', $autoInit);
	}
	public function getSecretAuth(bool $autoInit = false): ?string {
		return $this->getKey('secret_des_auth', $autoInit);
	}

	public function save(): bool {
		return ecrire_fichier_securise($this->file, $this->toJson());
	}
	private function read(): array {
		lire_fichier_securise($this->file, $json);
		if (
			$json
			and $json = \json_decode($json, true)
			and is_array($json)
		) {
			return array_map('base64_decode', $json);
		}
		return [];
	}

}
