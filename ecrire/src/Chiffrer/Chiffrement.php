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

/** 
 * Chiffrement / déchiffrement symétrique. 
 * 
 * @link https://fr.wikipedia.org/wiki/Cryptographie_sym%C3%A9trique
 * @link https://www.php.net/manual/fr/book.sodium.php
 */
class Chiffrement {

	/** Chiffre un message en utilisant une clé (le secret_du_site par défaut) ou un mot de passe */
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
		if (strlen($key) !== \SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
			$key = self::generateKeyFromPassword($key);
		}
		$nonce = random_bytes(\SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$padded_message = sodium_pad($message, 16);
		$encrypted = sodium_crypto_secretbox($padded_message, $nonce, $key);
		$encoded = base64_encode($nonce . $encrypted);
		sodium_memzero($key);
		sodium_memzero($nonce);
		spip_log("chiffrer($message)=$encoded", 'chiffrer' . _LOG_DEBUG);
		return $encoded;
	}

	/** Déchiffre un message en utilisant une clé (le secret_du_site par défaut) ou un mot de passe */	
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
		if (strlen($key) !== \SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
			$key = self::generateKeyFromPassword($key);
		}
		$decoded = base64_decode($encoded);
		$nonce = mb_substr($decoded, 0, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
		$encrypted_result = mb_substr($decoded, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
		$padded_message = sodium_crypto_secretbox_open($encrypted_result, $nonce, $key);
		$message = sodium_unpad($padded_message, 16);
		sodium_memzero($key);
		sodium_memzero($nonce);
		if ($message !== false) {
			spip_log("dechiffrer($encoded)=$message", 'chiffrer' . _LOG_DEBUG);
			return $message;
		}
		spip_log("dechiffrer() chiffre corrompu `$encoded`", 'chiffrer' . _LOG_DEBUG);
		return null;
	}

	/** Génère une clé de la taille attendue pour le chiffrement */
	public static function keygen(): string {
		return sodium_crypto_secretbox_keygen();
	}

	/**
	 * Retourne une clé de la taille attendue pour le chiffrement
	 *
	 * Notamment si on utilise un mot de passe comme clé, il faut le hacher
	 * pour servir de clé à la taille correspondante.
	 */
	private static function generateKeyFromPassword(string $password): string {
		if (strlen($password) === \SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
			return $password;
		}
		$hashed = hash('sha256', $password);
		return substr($hashed, 0, \SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
	}

	/** Retourne la clé de chiffrement par défaut (le secret_du_site) */
	private static function getDefaultKey(): ?string {
		$keys = SpipCles::instance();
		return $keys->getSecretSite();
	}
}
