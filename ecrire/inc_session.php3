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
	function cree_cookie_session ($id_auteur) {
		if ($id_auteur > 0) {
			$query = "SELECT * FROM spip_auteurs WHERE id_auteur=$id_auteur";
			include_ecrire ("inc_connect.php3");
			$result = spip_query ($query);
			if ($auteur = mysql_fetch_object ($result)) {
				$session = md5($id_auteur . $auteur->pass); // ici creer le numero de session

				if (! ereg("^[^@]+@[^@]+$", $auteur->email))
					$auteur->email = '@';

				$cookie = $id_auteur ."@". $auteur->email ."@". $auteur->nom
							."@". $session;

				return $cookie;
			}
		}
	}

	function verifie_cookie_session ($cookie) {
		if (list(,$id_auteur,$email,$nom,$session) = decode_cookie_session ($cookie)) {
			if ($id_auteur > 0) {
				include_ecrire ("inc_connect.php3");
				$query = "SELECT * FROM spip_auteurs WHERE id_auteur=$id_auteur";
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
		if (eregi("^([0-9]+)@([^@]*@[^@]*)@(.*)@([0-9A-Z]+)$", $cookie, $regs))
		return $regs;
		// list(,$id_auteur,$email,$nom,$session) = decode_cookie ($cookie)
	}

	function pose_cookie_session ($cookie) {
		$cookie_pose = false;

		// est-ce qu'il faut poser le cookie ?
		if ($GLOBALS['HTTP_COOKIE_VARS']['spip_session'] == $cookie)
			return true;

		// est-ce qu'on peut faire qqchose ?
		if (headers_sent() OR $GLOBALS['pose_cookie'] == 'fini')
			return false;

		// est-ce qu'il faut popper vers la racine ?
		$my_uri = $GLOBALS['REQUEST_URI'];
		if (ereg("/ecrire/", $my_uri)) {
			@header("Location: ../spip_session.php3?cookie=$cookie&redirect=".rawurlencode($my_uri));
			return false;
		}

		// on pose
		if ($cookie) {
			setcookie ('spip_session', $cookie);
			$cookie_pose = true;
		}
		return $cookie_pose;
	}


	function lit_cookie_session () {
		return $COOKIE_VARS['spip_session'];
	}


	function supprime_cookie_session () {
		setcookie ('spip_session', '');
	}


	// $login est optionnel
	function affiche_formulaire_login ($login, $redirect) {
		if (ereg("/ecrire/", $GLOBALS[REQUEST_URI]))
			$zap = "../";

		echo "<form action='$zap"."spip_session.php3' method='post'>".
			"<tt>&nbsp;login <input type='text' name='session_login' value='$login' size=8><br>".
			"sesame <input type='password' name='session_password' value='' size=8></tt>";

		if ($redirect) echo
			"<input type='hidden' name='redirect' value='$redirect'>";

		echo "</form>";
	}

?>