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
	/** Chiffre un message en utilisant une clé ou un mot de passe */
	public static function chiffrer(
		string $message,
		#[\SensitiveParameter]
		string $key
	): ?string {
		// create a random salt for key derivation
		$salt = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
		$key = self::deriveKeyFromPassword($key, $salt);
		$nonce = random_bytes(\SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$padded_message = sodium_pad($message, 16);
		$encrypted = sodium_crypto_secretbox($padded_message, $nonce, $key);
		$encoded = base64_encode($salt . $nonce . $encrypted);
		sodium_memzero($key);
		sodium_memzero($nonce);
		sodium_memzero($salt);
		#spip_log("chiffrer($message)=$encoded", 'chiffrer' . _LOG_DEBUG);
		return $encoded;
	}

	/** Déchiffre un message en utilisant une clé ou un mot de passe */
	public static function dechiffrer(
		string $encoded,
		#[\SensitiveParameter]
		string $key
	): ?string {
		$decoded = base64_decode($encoded);
		$salt = substr($decoded, 0, \SODIUM_CRYPTO_PWHASH_SALTBYTES);
		$nonce = substr($decoded, \SODIUM_CRYPTO_PWHASH_SALTBYTES, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$encrypted = substr($decoded, \SODIUM_CRYPTO_PWHASH_SALTBYTES + \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$key = self::deriveKeyFromPassword($key, $salt);
		$padded_message = sodium_crypto_secretbox_open($encrypted, $nonce, $key);
		sodium_memzero($key);
		sodium_memzero($nonce);
		sodium_memzero($salt);
		if ($padded_message === false) {
			spip_log("dechiffrer() chiffre corrompu `$encoded`", 'chiffrer' . _LOG_DEBUG);
			return null;
		}
		$message = sodium_unpad($padded_message, 16);
		#spip_log("dechiffrer($encoded)=$message", 'chiffrer' . _LOG_DEBUG);
		return $message;
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
	private static function deriveKeyFromPassword(
		#[\SensitiveParameter]
		string $password,
		string $salt
	): string {
		if (strlen($password) === \SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
			return $password;
		}
		$key = sodium_crypto_pwhash(
			\SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
			$password,
			$salt,
			\SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
			\SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
		);
		sodium_memzero($password);

		return $key;
	}
}
