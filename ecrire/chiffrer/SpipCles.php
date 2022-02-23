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
	private static $instance;

	private string $file = _DIR_ETC . "cles.php";

	public static function instance(): self {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		parent::__construct($this->read());
	}

	public function getSecretSite(): ?string {
		if ($this->has('secret_du_site')) {
			return $this->get('secret_du_site');
		}
        return null;
	}
	public function getSecretAuth(): ?string {
		if ($this->has('secret_des_auth')) {
			return $this->get('secret_des_auth');
		}
		return null;
	}

	public function generer(): bool {
		if (!$this->has('secret_du_site')) {
			$this->generate('secret_du_site');
		}
		if (!$this->has('secret_des_auth')) {
			$this->generate('secret_des_auth');
		}
		return $this->save();
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
