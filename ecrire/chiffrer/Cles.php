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

class Cles /* implements ContainerInterface */ {
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

	protected function set(
		string $name, 
		#[\SensitiveParameter]
		string $key
	): void {
		$this->keys[$name] = $key;
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
			return Chiffrement::chiffrer($this->toJson(), $withKey);
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
			if (!$this->has($name)) {
				$this->set($name, $key);
				spip_log("Restauration de la cle $name par id_auteur $id_auteur", 'chiffrer' . _LOG_INFO_IMPORTANTE);
				$restauration = true;
			}
		}
		return $restauration;
	}

	protected function toJson(): string {
		$json = array_map('base64_encode', $this->keys);
		return \json_encode($json);
	}
}
