<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/filtres');
include_spip('inc/acces');

// http://doc.spip.org/@action_editer_auteur_dist
function action_editer_auteur_dist() {
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	if (!preg_match(",^(\d+)$,", $arg, $r)) {
		$r = "action_editer_auteur_dist $arg pas compris";
		spip_log($r);
	} else {
		$url = action_legender_auteur_post($r);
		redirige_par_entete($url);
	}
}

// http://doc.spip.org/@action_legender_auteur_post
function action_legender_auteur_post($r) {
	global $auteur_session;

	$bio = _request('bio');
	$email = trim(_request('email'));
	$new_login = _request('new_login');
	$new_pass = _request('new_pass');
	$new_pass2 = _request('new_pass2');
	$nom_site_auteur = _request('nom_site_auteur');
	$perso_activer_imessage = _request('perso_activer_imessage');
	$pgp = _request('pgp');
	$redirect = _request('redirect');
	$statut = _request('statut');
	$url_site = _request('url_site');

	$echec = array();

	list($tout, $id_auteur, $ajouter_id_article,$x,$s) = $r;
//
// si id_auteur est hors table, c'est une creation sinon une modif
//
	if ($id_auteur) {
		$auteur = sql_fetsel("nom, login, bio, email, nom_site, url_site, pgp, extra, id_auteur, source, imessage", "spip_auteurs", "id_auteur=$id_auteur");
	  }
	if (!$auteur) {
		$id_auteur = 0;
		if ($s) {
		  if (in_array($s,$GLOBALS['liste_des_statuts']))
		    $statut = $s;
		  else {
		    $statut = $GLOBALS['liste_des_statuts']['info_redacteurs'];
		    spip_log("action_editer_auteur_dist: statut $s incompris;  $statut s'y susbstitue ");
		    // statut par defaut

		  }
		}
		$auteur = array();
		$auteur['source'] = 'spip';
	  }

	  // login et mot de passe
	$modif_login = false;
	$old_login = $auteur['login'];

	if (($new_login<>$old_login)
	AND ($auteur['source'] == 'spip' OR !spip_connect_ldap())
	AND autoriser('modifier','auteur', $id_auteur, NULL, array('restreintes'=>1))) {
		if ($new_login) {
			if (strlen($new_login) < 4)
				$echec[]= 'info_login_trop_court';
			else {
				$n = sql_countsel('spip_auteurs', "login=" . sql_quote($new_login) . " AND id_auteur!=$id_auteur AND statut!='5poubelle'");
				if ($n)
					$echec[]= 'info_login_existant';
				else if ($new_login != $old_login) {
					$modif_login = true;
					$auteur['login'] = $new_login;
				}
			}
		} else {
		// suppression du login

			$auteur['login'] = '';
			$modif_login = true;
		}
	}

	// changement de pass, a securiser en jaja ?

	if ($new_pass AND ($statut != '5poubelle') AND $auteur['login'] AND $auteur['source'] == 'spip'
	AND autoriser('modifier','auteur', $id_auteur)) {
		if ($new_pass != $new_pass2)
			$echec[]= 'info_passes_identiques';
		else if ($new_pass AND strlen($new_pass) < 6)
			$echec[]= 'info_passe_trop_court';
		else {
			$modif_login = true;
		}
	}

	if ($new_pass AND ($id_auteur OR $auteur['source'] == 'spip')) {
		$htpass = generer_htpass($new_pass);
		$alea_actuel = creer_uniqid();
		$alea_futur = creer_uniqid();
		$pass = md5($alea_actuel.$new_pass);
		$auteur['pass'] = $pass;
		$auteur['htpass'] = $htpass;
		$auteur['alea_actuel'] = $alea_actuel;
		$auteur['alea_futur'] = $alea_futur;
		$auteur['low_sec'] = '';
	}

	if ($modif_login AND ($auteur['id_auteur']<>$auteur_session['id_auteur'])) {
		// supprimer les sessions de cet auteur
		$session = charger_fonction('session', 'inc');
		$session($auteur['id_auteur']);
	}

	// seuls les admins peuvent modifier le mail
	// les admins restreints ne peuvent modifier celui des autres admins

	if (autoriser('modifier', 'auteur', $id_auteur, NULL, array('mail'=>1))) {
		if ($email !='' AND !email_valide($email)) 
			$echec[]= 'info_email_invalide';
		$auteur['email'] = $email;
	}

	if ($auteur_session['id_auteur'] == $id_auteur) {
		$auteur['imessage'] = $perso_activer_imessage;
	}

	// variables sans probleme
	$auteur['bio'] = corriger_caracteres($bio);
	$auteur['pgp'] = corriger_caracteres($pgp);
	$auteur['nom'] = corriger_caracteres(_request('nom'));
	$auteur['nom_site'] = corriger_caracteres($nom_site_auteur); // attention mix avec $nom_site_spip ;(
	$auteur['url_site'] = vider_url($url_site, false);

	// recoller les champs du extra
	if ($GLOBALS['champs_extra']) {
		include_spip('inc/extra');
		$auteur['extra'] = extra_update('auteurs', $id_auteur);
	} else
		$auteur['extra'] = '';

	//
	// Modifications de statut
	//

	if ($statut
	AND autoriser('modifier', 'auteur', $id_auteur, NULL, array('statut'=>$statut))) {
			$auteur["statut"] = $statut;
	}

	// l'entrer dans la base
	if (!$echec) {
		if (!$auteur['id_auteur']) { // creation si pas d'id
			$auteur['id_auteur'] = $id_auteur = sql_insertq("spip_auteurs", array('nom' => 'temp', 'statut' => $statut));

			// recuperer l'eventuel logo charge avant la creation
			$id_hack = 0 - $GLOBALS['auteur_session']['id_auteur'];
			$chercher_logo = charger_fonction('chercher_logo', 'inc');
			if (list($logo) = $chercher_logo($id_hack, 'id_auteur', 'on'))
				rename($logo, str_replace($id_hack, $id_auteur, $logo));
			if (list($logo) = $chercher_logo($id_hack, 'id_auteur', 'off'))
				rename($logo, str_replace($id_hack, $id_auteur, $logo));
		}

		// Restreindre avant de declarer l'auteur
		// (section critique sur les droits)
		$restreintes = _request('restreintes');
		if ($id_parent = intval(_request('id_parent'))) {
			if (is_array($restreintes))
				$restreintes[] = $id_parent;
			else
				$restreintes = array($id_parent);
		}
		if (is_array($restreintes)
		AND autoriser('modifier', 'auteur', $id_auteur, NULL, array('restreint'=>$restreintes))) {
			sql_delete("spip_auteurs_rubriques", "id_auteur=".sql_quote($id_auteur));
			foreach (array_unique($restreintes) as $id_rub)
				if ($id_rub = intval($id_rub)) // si '0' on ignore
					sql_insertq('spip_auteurs_rubriques', array('id_auteur' => $id_auteur, 'id_rubrique'=>$id_rub));
		}

		sql_updateq('spip_auteurs', $auteur, "id_auteur=".$auteur['id_auteur']);
	}

	// Lier a un article
	if ($id_article = intval(_request('lier_id_article'))
	AND autoriser('modifier', 'article', $id_article)) {
		sql_insertq('spip_auteurs_articles', array('id_article' => $id_article, 'id_auteur' =>$id_auteur));
	}

	// Notifications, gestion des revisions, reindexation...
	pipeline('post_edition',
		array(
			'args' => array(
				'table' => 'spip_auteurs',
				'id_objet' => $id_auteur
			),
			'data' => $auteur
		)
	);

	// .. mettre a jour les fichiers .htpasswd et .htpasswd-admin
	ecrire_acces();

	// .. mettre a jour les sessions de cet auteur
	$sauve = $GLOBALS['auteur_session'];
	include_spip('inc/session');
	foreach(preg_files(_DIR_SESSIONS, '/'.$id_auteur.'_.*\.php') as $session) {
		$GLOBALS['auteur_session'] = array();
		include $session; # $GLOBALS['auteur_session'] est alors l'auteur cible
		foreach (array('nom', 'login', 'email', 'statut', 'bio', 'pgp', 'nom_site', 'url_site') AS $var)
			if (isset($auteur[$var]))
				$GLOBALS['auteur_session'][$var] = $auteur[$var];
		ecrire_fichier_session($session, $GLOBALS['auteur_session']);
	}
	$GLOBALS['auteur_session'] = $sauve;

	$echec = $echec ? '&echec=' . join('@@@', $echec) : '';

	$redirect = rawurldecode($redirect);

	if ($echec) {
		// revenir au formulaire de saisie
		$ret = !$redirect
			? '' 
			: ('&redirect=' . rawurlencode($redirect));

		return generer_url_ecrire('auteur_infos',
			"id_auteur=$id_auteur$echec$ret",'&');
	} else {
		// modif: renvoyer le resultat ou a nouveau le formulaire si erreur
		if (!$redirect)
			$redirect = generer_url_ecrire("auteur_infos", "id_auteur=$id_auteur", '&', true);

		return $redirect;
	}
}
?>
