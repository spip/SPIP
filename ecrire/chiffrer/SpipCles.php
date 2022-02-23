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

final class SpipCles {
	private static array $instances = [];

	private string $file = _DIR_ETC . 'cles.php';
	private Cles $cles;

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
		$this->cles = new Cles($this->read());
	}

	public function getSecretSite(bool $autoInit = true): ?string {
		return $this->getKey('secret_du_site', $autoInit);
	}
	public function getSecretAuth(bool $autoInit = false): ?string {
		return $this->getKey('secret_des_auth', $autoInit);
	}

	public function save(): bool {
		return ecrire_fichier_securise($this->file, $this->cles->toJson());
	}

	/**
	 * Fournir une sauvegarde chiffree des cles (a l'aide d'une autre clé, comme le pass d'un auteur)
	 *
	 * @param string $withKey Clé de chiffrage de la sauvegarde
	 * @return string Contenu de la sauvegarde chiffrée générée
	 */
	public function backup(
		#[\SensitiveParameter]
		string $withKey
	): string {
		if (count($this->keys)) {
			return Chiffrement::chiffrer($this->cles->toJson(), $withKey);
		}
		return '';
	}

	/**
	 * Restaurer les cles manquantes depuis une sauvegarde chiffree des cles
	 * (si la sauvegarde est bien valide)
	 *
	 * @param string $backup Sauvegarde chiffrée (générée par backup())
	 * @param int $id_auteur
	 * @param string $pass
	 * @return void
	 */
	public function restore(
		string $backup,
		#[\SensitiveParameter]
		string $password_clair,
		#[\SensitiveParameter]
		string $password_hash,
		int $id_auteur
	): bool {
		if (empty($backup)) {
			return false;
		}

		$sauvegarde = Chiffrement::dechiffrer($backup, $password_clair);
		$json = json_decode($sauvegarde, true);
		if (!$json) {
			return false;
		}

		// cela semble une sauvegarde valide
		$cles_potentielles = array_map('base64_decode', $json);

		// il faut faire une double verif sur secret_des_auth
		// pour s'assurer qu'elle permet bien de decrypter le pass de l'auteur qui fournit la sauvegarde
		// et par extension tous les passwords
		if (!empty($cles_potentielles['secret_des_auth'])) {
			if (!Password::verifier($password_clair, $password_hash, $cles_potentielles['secret_des_auth'])) {
				spip_log("Restauration de la cle `secret_des_auth` par id_auteur $id_auteur erronnee, on ignore", 'chiffrer' . _LOG_INFO_IMPORTANTE);
				unset($cles_potentielles['secret_des_auth']);
			}
		}

		// on merge les cles pour recuperer les cles manquantes
		$restauration = false;
		foreach ($cles_potentielles as $name => $key) {
			if (!$this->cles->has($name)) {
				$this->cles->set($name, $key);
				spip_log("Restauration de la cle $name par id_auteur $id_auteur", 'chiffrer' . _LOG_INFO_IMPORTANTE);
				$restauration = true;
			}
		}
		return $restauration;
	}

	private function getKey(string $name, bool $autoInit): ?string {
		if ($this->cles->has($name)) {
			return $this->cles->get($name);
		}
		if ($autoInit) {
			$this->cles->generate($name);
			$this->save();
			return $this->cles->get($name);
		}
		return null;
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
