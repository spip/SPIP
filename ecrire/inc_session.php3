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


	// Ajoute une session dans le cache des sessions
	// ou supprimer toute session de cet auteur si $session == false
	function ajouter_session($auteur, $session) {
		$vars = array ('nom', 'login', 'email', 'session', 'statut');

		if (file_exists ('ecrire/inc_sessions_cache.php3')) {
			include ('ecrire/inc_sessions_cache.php3');
		}
		unset ($sessions[$auteur->id_auteur]);

		if ($session) {
			$id = $auteur->id_auteur;
			$sessions[$id]['nom'] = $auteur->nom;
			$sessions[$id]['login'] = $auteur->login;
			$sessions[$id]['email'] = $auteur->email;
			$sessions[$id]['statut'] = $auteur->statut;
			$sessions[$id]['session'] = $session;
		}

		$liste_sessions = array_keys($sessions);
		while (list(,$s) = each ($liste_sessions)) {
			reset ($vars);
			unset ($contenu);
			while (list(, $var) = each($vars)) {
				$contenu[] = "'$var' => '".addslashes($sessions[$s][$var])."'";
			}
			$contenu = join (",\n", $contenu);
			$texte[] = "$s => array ( $contenu )";
		}
		
		$texte = '<'.'?php $sessions = array ('."\n". join(",\n" ,$texte) . "\n); ?".'>';
		if ($myFile = fopen("ecrire/inc_sessions_cache.php3", "wb")) {
			fputs($myFile, $texte);
	 		fclose($myFile);
		}
	}

	// cree le cookie correspondant a l'auteur
	// attention aux trous de securite ;)
	function cree_cookie_session ($auteur) {
		if ($auteur->id_auteur > 0) {
			$session = md5(rand()); //numero de session
			ajouter_session($auteur, $session);
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
		if ((list(,$id,$login,$session) = decode_cookie_session ($cookie)) AND ($id > 0)) {
			if (file_exists ('ecrire/inc_sessions_cache.php3')) {
				include ('ecrire/inc_sessions_cache.php3');
			} else if (file_exists ('inc_sessions_cache.php3')) {
				include ('inc_sessions_cache.php3');
			}

			if ($session == $sessions[$id]['session']) {
				$auteur->id_auteur = $id;
				$auteur->login = $sessions[$id]['login'];
				$auteur->nom = $sessions[$id]['nom'];
				$auteur->email = $sessions[$id]['email'];
				$auteur->statut = $sessions[$id]['statut'];
				return ($auteur);
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
		if ((list(,$id,,) = decode_cookie_session(lit_cookie_session())) AND ($id > 0)) {
			$auteur->id_auteur = $id;
			ajouter_session ($auteur, false); // effacer dans le fichier de sessions
		}
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