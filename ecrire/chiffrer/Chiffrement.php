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

class Chiffrement {
	public static function keygen(): string {
		return sodium_crypto_secretbox_keygen();
	}

	public static function chiffrer(
		string $message,
		#[\SensitiveParameter]
		?string $key = null
	): ?string {
		$key ??= self::getDefaultKey();
		if (!$key) {
			spip_log("chiffrer() sans clé `$message`", 'chiffrer' . _LOG_INFO_IMPORTANTE);
			return null;
		}
		if (strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
			while (strlen($key) < SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
				$key .= $key;
			}
			$key = substr($key, 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
		}
		$nonce = random_bytes(\SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$encrypted = sodium_crypto_secretbox($message, $nonce, $key);
		$encoded = base64_encode($nonce . $encrypted);
		sodium_memzero($key);
		sodium_memzero($nonce);
		spip_log("chiffrer($message)=$encoded", 'chiffrer' . _LOG_DEBUG);
		return $encoded;
	}

	public static function dechiffrer(
		string $encoded,
		#[\SensitiveParameter]
		?string $key = null
	): ?string {
		$key ??= self::getDefaultKey();
		if (!$key) {
			spip_log("dechiffrer() sans clé `$encoded`", 'chiffrer' . _LOG_INFO_IMPORTANTE);
			return null;
		}
		if (strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
			while (strlen($key) < SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
				$key .= $key;
			}
			$key = substr($key, 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
		}
		$decoded = base64_decode($encoded);
		$nonce = mb_substr($decoded, 0, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
		$encrypted_result = mb_substr($decoded, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
		$message = sodium_crypto_secretbox_open($encrypted_result, $nonce, $key);
		sodium_memzero($key);
		sodium_memzero($nonce);
		if ($message !== false) {
			spip_log("dechiffrer($encoded)=$message", 'chiffrer' . _LOG_DEBUG);
			return $message;
		}
		spip_log("dechiffrer() chiffre corrompu `$encoded`", 'chiffrer' . _LOG_DEBUG);
		return null;
	}

	private static function getDefaultKey(): ?string {
		$keys = SpipCles::instance();
		return $keys->getSecretSite();
	}
}
