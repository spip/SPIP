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

class Password {
	/**
	 * verifier qu'un mot de passe en clair est correct a l'aide de son hash
	 *
	 * Le mot de passe est poivre via la cle secret_des_auth
	 *
	 * @param string $password_clair
	 * @param string $password_hash
	 * @param string $key
	 * @return bool
	 */
	public static function verifier(
		#[\SensitiveParameter]
		string $password_clair,
		#[\SensitiveParameter]
		string $password_hash,
		#[\SensitiveParameter]
		?string $key = null
	): bool {
		$key ??= self::getDefaultKey();
		if ($key) {
			$pass_poivre = hash_hmac('sha256', $password_clair, $key);
			return password_verify($pass_poivre, $password_hash);
		}
		spip_log('Aucune clé pour vérifier le mot de passe', 'chiffrer' . _LOG_INFO_IMPORTANTE);
		return false;
	}

	/**
	 * Calculer un hash salé du mot de passe
	 * @param string $password_clair
	 * @param string $salt
	 * @return string
	 */
	public static function hacher(
		#[\SensitiveParameter]
		string $password_clair,
		#[\SensitiveParameter]
		?string $key = null
	): ?string {
		$key ??= self::getDefaultKey();
		// ne pas fournir un hash errone si la cle nous manque
		if ($key) {
			$pass_poivre = hash_hmac('sha256', $password_clair, $key);
			return password_hash($pass_poivre, PASSWORD_DEFAULT);
		}
		spip_log('Aucune clé pour chiffrer le mot de passe', 'chiffrer' . _LOG_INFO_IMPORTANTE);
		return null;
	}

	private static function getDefaultKey(): ?string {
		$keys = SpipCles::instance();
		return $keys->getSecretAuth();
	}
}
