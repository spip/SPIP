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

namespace Spip\Core;

/**
 * Gestion des boutons de l'interface privée
 *
 * @package SPIP\Core\Boutons
 */

if (!defined('_ECRIRE_INC_VERSION')){
	return;
}


/**
 * Classe définissant un bouton dans la barre du haut de l'interface
 * privée ou dans un de ses sous menus
 */
class Chiffrer {

	protected static array $cles;


	/**
	 * Nom du fichier de cles
	 * @return string
	 */
	protected static function nom_fichier_cles(): string{
		return _DIR_ETC . "cles.php";
	}

	/**
	 * Charger les cles de securite de SPIP dans la static
	 * @param bool $force_reload
	 * @return void
	 */
	protected static function charger_cles(bool $force_reload = false){
		if (empty(Chiffrer::$cles) or $force_reload){
			$fichier_cles = Chiffrer::nom_fichier_cles();

			$json = '';
			Chiffrer::$cles = [];
			if (lire_fichier_securise($fichier_cles, $json)
				and $json = \json_decode($json, true)
				and is_array($json)){
				Chiffrer::$cles = array_map('base64_decode', $json);
			}
		}
	}

	/**
	 * Sauvegarder les cles dans un fichier php securise
	 * @return void
	 */
	protected static function enregistrer_cles(){
		$fichier_cles = Chiffrer::nom_fichier_cles();

		$json = array_map('base64_encode', Chiffrer::$cles);
		ecrire_fichier_securise($fichier_cles, \json_encode($json));
	}


	/**
	 * Initialiser une nouvelle clé aléatoire
	 * @param string $nom_cle
	 * @return void
	 */
	protected static function initialiser_cle(string $nom_cle){
		Chiffrer::charger_cles();

		$random_length = 16;
		try {
			if (function_exists('openssl_random_pseudo_bytes')){
				$random = openssl_random_pseudo_bytes($random_length);
			} else {
				$random = random_bytes($random_length);
			}
		} catch (\Exception $e) {
			\include_spip('inc/acces');
			$random = $_SERVER['DOCUMENT_ROOT'] . ($_SERVER['SERVER_SIGNATURE'] ?? '') . \creer_uniqid();
			$random = hash('sha256', $random, true);
		}

		Chiffrer::$cles[$nom_cle] = $random;
		Chiffrer::enregistrer_cles();
	}


	/**
	 * Lire la valeur d'une cle
	 * @param string $nom_cle
	 * @return string
	 */
	public static function lire_cle(string $nom_cle, $auto_init = false): string{
		Chiffrer::charger_cles();
		if (empty(Chiffrer::$cles[$nom_cle]) and $auto_init){
			Chiffrer::initialiser_cle($nom_cle);
		}
		return Chiffrer::$cles[$nom_cle] ?? '';
	}

	/**
	 * Fournir une sauvegarde chiffree des cles (a l'aide du pass d'un auteur)
	 * @param int $id_auteur
	 * @param string $pass
	 * @return string
	 */
	public static function sauvegarde_chiffree_cles(int $id_auteur, string $pass): string {
		Chiffrer::charger_cles();
		if (!empty(Chiffrer::$cles)) {
			$json = array_map('base64_encode', Chiffrer::$cles);
			$json = json_encode($json);
			return Chiffrer::chiffrer($json, $pass);
		}
		return '';
	}

	/**
	 * Restaurer les cles manquantes depuis une sauvegarde chiffree des cles
	 * (si la sauvegarde est bien valide)
	 * @param $sauvegarde
	 * @param int $id_auteur
	 * @param string $pass
	 * @return void
	 */
	public static function restaurer_cles_depuis_sauvegarde_chiffree(string $sauvegarde_chiffree, int $id_auteur, string $password_clair, string $password_hash): bool{
		if (!empty($sauvegarde_chiffree)) {
			Chiffrer::charger_cles();
			$sauvegarde = Chiffrer::dechiffrer($sauvegarde_chiffree, $password_clair);
			if ($json = json_decode($sauvegarde, true)) {
				// cela semble une sauvegarde valide
				$cles_potentielles = array_map('base64_decode', $json);

				// il faut faire une double verif sur secret_des_auth
				// pour s'assurer qu'elle permet bien de decrypter le pass de l'auteur qui fournit la sauvegarde
				// et par extension tous les passwords
				if (!empty($cles_potentielles['secret_des_auth'])
				  and !Chiffrer::verifier_mot_de_passe($password_clair, $password_hash, $cles_potentielles['secret_des_auth'])) {
					spip_log("Restauration de la cle `secret_des_auth` par id_auteur $id_auteur erronnee, on ignore", 'chiffrer' . _LOG_INFO_IMPORTANTE);
					unset($cles_potentielles['secret_des_auth']);
				}

				// on merge les cles pour recuperer les cles manquantes
				$sauvegarder = false;
				foreach ($cles_potentielles as $k => $v) {
					if (empty(Chiffrer::$cles[$k])) {
						Chiffrer::$cles[$k] = $v;
						spip_log("Restauration de la cle $k par id_auteur $id_auteur", 'chiffrer' . _LOG_INFO_IMPORTANTE);
						$sauvegarder = true;
					}
				}
				if ($sauvegarder) {
					Chiffrer::enregistrer_cles();
					return true;
				}
			}
		}
		return false;
	}


	/**
	 * verifier qu'un mot de passe en clair est correct a l'aide de son hash
	 * Le mot de passe est poivre via la cle secret_des_auth
	 * Le hash est sale avec les alea en base
	 * @param string $password_clair
	 * @param string $password_hash
	 * @param string|null $cle
	 * @return bool
	 */
	public static function verifier_mot_de_passe(string $password_clair, string $password_hash, ?string $cle=null): bool {
		$cle = $cle ?? Chiffrer::lire_cle('secret_des_auth');
		$pass_poivre = hash_hmac("sha256", $password_clair, $cle);

		return password_verify($pass_poivre, $password_hash);
	}

	/**
	 * Calculer un hash salé du mot de passe
	 * @param string $password_clair
	 * @param string $salt
	 * @return string
	 */
	public static function calculer_hash_sale_mot_de_passe(string $password_clair, string $salt): string {
		$cle = Chiffrer::lire_cle('secret_des_auth');
		// ne pas fournir un sel errone si la cle nous manque
		if ($cle) {
			$pass_poivre = hash_hmac("sha256", $password_clair, $cle);
			$pass_sale = password_hash($pass_poivre, PASSWORD_DEFAULT, ['salt' => $salt]);
			return $pass_sale;
		}
		return '';
	}


	/**
	 * Chiffrer une chaine a l'aide d'une cle
	 *
	 * @param string $clair
	 *     Texte à chiffrer
	 * @param string $cle
	 *     Clé à utiliser
	 * @param string $cipher
	 *     Cipersuite à utiliser
	 * @return string
	 *       Texte chiffré
	 */
	public static function chiffrer(string $clair, ?string $cle, string $cipher = "AES-128-CBC"): string{
		$cle = $cle ?? Chiffrer::lire_cle('secret_du_site', true);

		$ivlen = openssl_cipher_iv_length($cipher);
		$iv = openssl_random_pseudo_bytes($ivlen);
		$chiffre_raw = openssl_encrypt($clair, $cipher, $cle, OPENSSL_RAW_DATA, $iv);
		$hmac = hash_hmac('sha256', $iv . $chiffre_raw, $cle, true);
		$chiffre = base64_encode($iv . $hmac . $chiffre_raw);

		spip_log("chiffrer($clair)=$chiffre", 'chiffrer' . _LOG_DEBUG);

		return $chiffre;
	}


	/**
	 * Dechiffrer
	 *
	 * @param string $chiffre
	 *     Texte à déchiffrer
	 * @param string $cle
	 *     Clé à utiliser
	 * @param string $cipher
	 *     Cipersuite à utiliser
	 * @return string
	 *       Texte déchiffré
	 */
	public static function dechiffrer(string $chiffre, ?string $cle, $cipher = "AES-128-CBC"): ?string{
		$cle = $cle ?? Chiffrer::lire_cle('secret_du_site', true);

		$c = base64_decode($chiffre);

		$ivlen = openssl_cipher_iv_length($cipher);
		$iv = substr($c, 0, $ivlen);
		$hmac = substr($c, $ivlen, $sha2len = 32);
		$chiffre_raw = substr($c, $ivlen+$sha2len);

		$clair = openssl_decrypt($chiffre_raw, $cipher, $cle, OPENSSL_RAW_DATA, $iv);

		$calcmac = hash_hmac('sha256', $iv . $chiffre_raw, $cle, $as_binary = true);
		if (hash_equals($hmac, $calcmac)){ // timing attack safe comparison
			spip_log("dechiffrer($chiffre)=$clair", 'chiffrer' . _LOG_DEBUG);
			return $clair;
		}
		else {
			spip_log("dechiffrer() chiffre corrompu `$chiffre`", 'chiffrer' . _LOG_DEBUG);
		}

		return null;
	}
}
