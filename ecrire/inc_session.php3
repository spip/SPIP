<?php

	/*
	 * Gestion de l'authentification par sessions
	 * a utiliser pour valider l'acces (bloquant)
	 * ou pour reconnaitre un utilisateur (non bloquant)
	 *
	 */

	//
	// Ce fichier ne sera execute qu'une fois
	if (defined("_ECRIRE_INC_SESSION")) return;
	define("_ECRIRE_INC_SESSION", "1");

	// cree le cookie correspondant a l'auteur
	// attention aux trous de securite ;)
	function cree_cookie_session ($auteur) {
		if ($auteur->id_auteur > 0) {
			$session = md5($auteur->id_auteur . $auteur->pass); // ici creer le numero de session
			$cookie = $auteur->id_auteur ."@". $auteur->login ."@". $session;
			return $cookie;
		}
	}

	// cree le cookie admin correspondant a l'auteur
	function cree_cookie_admin ($auteur) {
		if ($auteur->id_auteur > 0) {
			$cookie = $auteur->id_auteur ."@". $auteur->login ."@". $auteur->nom ."@". $auteur->email;
			return $cookie;
		}
	}

	function verifie_cookie_session ($cookie) {
		if (list(,$id_auteur,$login,$session) = decode_cookie_session ($cookie)) {
			if ($id_auteur > 0) {
				include_ecrire ("inc_connect.php3");
				$query = "SELECT * FROM spip_auteurs WHERE id_auteur=$id_auteur AND login='$login'";
				$result = spip_query ($query);
				if ($auteur = mysql_fetch_object ($result)) {
					if ($session == md5($id_auteur . $auteur->pass)) { // ici verifier le num de session dans la base
						$auteur->pass = '';		// securite
						$auteur->htpass = '';
						return $auteur;
					}
				}
			}
		}
	}

	function decode_cookie_session ($cookie) {
		if (eregi("^([0-9]+)@(.*)@([0-9A-Z]+)$", $cookie, $regs))
		return $regs;
		// list(,$id_auteur,$login,$session) = decode_cookie_session($cookie)
	}

	function pose_cookie_session ($cookie, $cookie_admin='') {
		global $redirect;
		$cookie_pose = false;

		// est-ce qu'il faut poser le cookie ?
		if ($GLOBALS['HTTP_COOKIE_VARS']['spip_session'] == $cookie)
			return true;

		// est-ce qu'on peut le faire ?
		if (headers_sent()) // OR $GLOBALS['pose_cookie'] == 'fini')
			return false;

		// est-ce qu'il faut popper vers la racine ?
		$my_uri = $GLOBALS['REQUEST_URI'];
		if (ereg("/ecrire/", $my_uri)) {
			if (!$redirect)
				$redirect = $my_uri;
			@header("Location: ../spip_cookie.php3?cookie_session=$cookie&redirect=".rawurlencode($redirect));
			return false;
		}

		// on pose
		if ($cookie) {
			// un cookie spip_session d'authentification,
			// qui meurt avec le navigateur
			setcookie ('spip_session', $cookie);

			// un cookie spip_admin qui n'authentifie pas
			// mais conserve des infos deux semaines
			setcookie ('spip_admin', $cookie, time() + 14*24*3600);

			$cookie_pose = true;
		}
		return $cookie_pose;
	}


	function lit_cookie_session () {
		return $GLOBALS['HTTP_COOKIE_VARS']['spip_session'];
	}


	function supprime_cookie_session () {
		setcookie ('spip_session', '', time() - 24*3600);
		setcookie ('spip_admin', '', time() - 24*3600);
	}


	// $login est optionnel
	function affiche_formulaire_login ($login, $redirect) {
		if (ereg("/ecrire/", $GLOBALS[REQUEST_URI]))
			$zap = "../";

		echo "<form action='$zap"."spip_cookie.php3' method='post'>".
			"<tt>&nbsp;login <input type='text' name='session_login' value='$login' size=10 style='font-size: 10pt'><br>".
			"sesame <input type='password' name='session_password' value='' size=10 style='font-size: 10pt'></tt>";

		if ($redirect) echo
			"<input type='hidden' name='redirect' value='$redirect'>";

		echo "</form>";
	}

?>