<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

namespace Spip\Chiffrer;

/** Gestion des clés d’authentification / chiffrement de SPIP */
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

	/**
	 * Retourne le secret du site (shorthand)
	 * @uses self::getSecretSite()
	 */
	public static function secret_du_site(): ?string {
		return (self::instance())->getSecretSite();
	}

	private function __construct(string $file = '') {
		if ($file) {
			$this->file = $file;
		}
		$this->cles = new Cles($this->read());
	}

	/**
	 * Renvoyer le secret du site
	 *
	 * Le secret du site doit rester aussi secret que possible, et est eternel
	 * On ne doit pas l'exporter
	 *
	 * Le secret est partagé entre une clé disque et une clé bdd
	 *
	 * @return string
	 */
	public function getSecretSite(bool $autoInit = true): ?string {
		$key = $this->getKey('secret_du_site', $autoInit);
		$meta = $this->getMetaKey('secret_du_site', $autoInit);
		// conserve la même longeur.
		return $key ^ $meta;
	}

	/** Renvoyer le secret des authentifications */
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
		if (count($this->cles)) {
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
			// si l'ecriture de fichier a bien marche on peut utiliser la cle
			if ($this->save()) {
				return $this->cles->get($name);
			}
			// sinon loger et annule la cle generee car il ne faut pas l'utiliser
			spip_log("Echec ecriture du fichier cle ".$this->file." ; impossible de generer une cle $name", 'chiffrer' . _LOG_ERREUR);
			$this->cles->delete($name);
		}
		return null;
	}

	private function getMetaKey(string $name, bool $autoInit = true): ?string {
		if (!isset($GLOBALS['meta'][$name])) {
			include_spip('base/abstract_sql');
			$GLOBALS['meta'][$name] = sql_getfetsel('valeur', 'spip_meta', 'nom = ' . sql_quote($name, '', 'string'));
		}
		$key = base64_decode($GLOBALS['meta'][$name] ?? '');
		if (strlen($key) === \SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
			return $key;
		}
		if (!$autoInit) {
			return null;
		}
		$key = Chiffrement::keygen();
		ecrire_meta($name, base64_encode($key), 'non');
		lire_metas(); // au cas ou ecrire_meta() ne fonctionne pas

		return $key;
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
