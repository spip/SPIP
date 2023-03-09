<?php

namespace Spip\Utils;

/**
 * Utilitaires pour serializer/deserializer de façon safe et pérenne
 */
class Serializer {

	protected static $default_allowed_classes = ['DateTime'];

	/**
	 * @param mixed $valeur
	 * @return string
	 * @throws \JsonException
	 */
	public static function serialize($valeur): string {
		try {
			$serializer = new JsonSerializer();
			$json = "[" . $serializer->serialize($valeur)."]";
			return $json;
		} catch (\Exception $e) {
			// Fallback, mais on devrait jamais arriver là...
			spip_log("Serializer:serialize echec sur json_encode d'une valeur ". $e->getMessage() ." | " . var_export($valeur, true), 'serialize');
			// si on est en echec avec json_encode, on se replie sur le serialize de PHP, mais on base64 ensuite pour que ce soit stockable en base de données$
			$valeur = \serialize($valeur);
			return "spipb64:" . base64_encode($valeur);
		}
	}

	/**
	 * Deserializer des données stockées en base via notre propre fonction serialize()
	 * ou eventuellement des données legacy serializées via la fonction PHP serialize()
	 *
	 * @param string $valeur
	 * @param array $options
	 *   bool $accept_legacy (true) : pour faciliter la migration on prend en charge aussi les valeurs sérialisées en PHP, cette valeur passera par défaut à false dans le futur
	 *   bool|array $allowed_classes : permet de bloquer la deserialization de tous les objets (false), de passer une liste blanche de nom de classe ou d'autoriser tout (true)
	 * @return false|mixed
	 */
	public static function unserialize(string $valeur, array $options = []) {
		$accept_legacy = ($options['accept_legacy'] ?? true);
		$allowed_classes = ($options['allowed_class'] ?? self::$default_allowed_classes);
		// cas par defaut attendu : c'est une serialization par nous même, via supercharged json_encode
		if (strpos($valeur,'[') === 0) {
			$serializer = new JsonSerializer();
			$serializer->setAllowedClasses($allowed_classes);
			try {
				$unserialized = $serializer->unserialize($valeur);
				if (is_array($unserialized)) {
					return reset($unserialized);
				}
				spip_log("Serializer:unserialize json_decode de [...] a donné une valeur innatendue " . var_export($valeur, true), 'serialize');
				return false;
			} catch (\Exception $e) {
				spip_log("Serializer:unserialize echec sur json_decode d'une valeur ". $e->getMessage() ." | " . var_export($valeur, true), 'serialize');
				return false;
			}
		}
		// gérer le fallback en base 64, mais il ne devrait jamais arriver
		elseif (strpos($valeur,'spipb64:') === 0
		  and $decode = base64_decode(substr($valeur, 8), true)) {
			$decode = \unserialize($decode, ['allowed_classes' => $allowed_classes]);
			if ($decode === false) {
				spip_log("Serializer:unserialize echec de unserialize() sur des données spipb64:" . var_export($valeur, true), 'serialize' . _LOG_ERREUR);
			}
			return $decode;
		}
		elseif($accept_legacy and self::is_serialized($valeur)) {
			spip_log("Serializer:unserialize utilisation de unserialize() sur des données legacy, mettez à jour vos données", 'serialize' . _LOG_INFO_IMPORTANTE);
			$decode = \unserialize($decode, ['allowed_classes' => $allowed_classes]);
			if ($decode === false) {
				spip_log("Serializer:unserialize echec de unserialize() sur des données legacy" . var_export($valeur, true), 'serialize' . _LOG_ERREUR);
			}
			return $decode;
		}

		return false;
	}


	public static function is_serialized( $data, $strict = true ) {
		// If it isn't a string, it isn't serialized.
		if ( ! is_string( $data ) ) {
			return false;
		}
		$data = trim( $data );
		if ( 'N;' === $data ) {
			return true;
		}
		if ( strlen( $data ) < 4 ) {
			return false;
		}
		if ( ':' !== $data[1] ) {
			return false;
		}
		if ( $strict ) {
			$lastc = substr( $data, -1 );
			if ( ';' !== $lastc && '}' !== $lastc ) {
				return false;
			}
		} else {
			$semicolon = strpos( $data, ';' );
			$brace     = strpos( $data, '}' );
			// Either ; or } must exist.
			if ( false === $semicolon && false === $brace ) {
				return false;
			}
			// But neither must be in the first X characters.
			if ( false !== $semicolon && $semicolon < 3 ) {
				return false;
			}
			if ( false !== $brace && $brace < 4 ) {
				return false;
			}
		}
		$token = $data[0];
		switch ( $token ) {
			case 's':
				if ( $strict ) {
					if ( '"' !== substr( $data, -2, 1 ) ) {
						return false;
					}
				} elseif ( false === strpos( $data, '"' ) ) {
					return false;
				}
				// Or else fall through.
			case 'a':
			case 'O':
			case 'C':
			case 'o':
			case 'E':
				return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
			case 'b':
			case 'i':
			case 'd':
				$end = $strict ? '$' : '';
				return (bool) preg_match( "/^{$token}:[0-9.E+-]+;$end/", $data );
		}
		return false;
	}

}
