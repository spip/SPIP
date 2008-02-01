<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

if (!defined('_LOGIN_TROP_COURT')) define('_LOGIN_TROP_COURT', 4);

// http://doc.spip.org/@action_editer_auteur_dist
function action_editer_auteur_dist() {
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();
	$redirect = _request('redirect');
	
	if (!preg_match(",^\d+$,", $arg, $r)) {
		spip_log("action_editer_auteur_dist $arg pas compris");
	} else {
		list($id_auteur, $echec) = action_legender_auteur_post(
			_request('statut'),
			_request('nom'),
			_request('email'),
			_request('bio'),
			_request('nom_site_auteur'),
			_request('url_site'),
			_request('new_login'),
			_request('new_pass'),
			_request('new_pass2'),
			_request('perso_activer_imessage'),
			_request('pgp'),
			_request('lier_id_article'),
			intval(_request('id_parent')),
			_request('restreintes'),
			$r[0]);

			if ($echec) {
		// revenir au formulaire de saisie
				$ret = !$redirect
				? '' 
				: ('&redirect=' . $redirect);
				spip_log("echec editeur auteur: " . join(' ',$echec));
				$echec = '&echec=' . join('@@@', $echec);
				$redirect = generer_url_ecrire('auteur_infos',"id_auteur=$id_auteur$echec$ret",'&');
			} else {
			// modif: renvoyer le resultat ou a nouveau le formulaire si erreur
				if (!$redirect)
					$redirect = generer_url_ecrire("auteur_infos", "id_auteur=$id_auteur", '&', true);
				else $redirect = rawurldecode($redirect);
			}
	}
	redirige_par_entete($redirect);
}

// http://doc.spip.org/@action_legender_auteur_post
function action_legender_auteur_post($statut, $nom, $email, $bio, $nom_site_auteur, $url_site, $new_login, $new_pass, $new_pass2, $perso_activer_imessage, $pgp, $lier_id_article=0, $id_parent=0, $restreintes= NULL, $id_auteur=0) {
	global $visiteur_session;
	include_spip('inc/filtres');
	include_spip('inc/acces');

	$echec = array();

//
// si id_auteur est hors table, c'est une creation sinon une modif
//
	if ($id_auteur) {
		$auteur = sql_fetsel("nom, login, bio, email, nom_site, url_site, pgp, extra, id_auteur, source, imessage", "spip_auteurs", "id_auteur=$id_auteur");
	  }
	if (!$auteur) {
		$id_auteur = 0;
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
			if (strlen($new_login) < _LOGIN_TROP_COURT)
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

	if ($modif_login AND ($auteur['id_auteur']<>$visiteur_session['id_auteur'])) {
		// supprimer les sessions de cet auteur
		$session = charger_fonction('session', 'inc');
		$session($auteur['id_auteur']);
	}

	// seuls les admins peuvent modifier le mail
	// les admins restreints ne peuvent modifier celui des autres admins

	if (autoriser('modifier', 'auteur', $id_auteur, NULL, array('mail'=>1))) {
		$email = trim($email);
		if ($email !='' AND !email_valide($email)) 
			$echec[]= 'info_email_invalide';
		$auteur['email'] = $email;
	}

	if ($visiteur_session['id_auteur'] == $id_auteur) {
		$auteur['imessage'] = $perso_activer_imessage;
	}

	// variables sans probleme
	$auteur['bio'] = corriger_caracteres($bio);
	$auteur['pgp'] = corriger_caracteres($pgp);
	$auteur['nom'] = corriger_caracteres($nom);
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
			$id_hack = 0 - $GLOBALS['visiteur_session']['id_auteur'];
			$chercher_logo = charger_fonction('chercher_logo', 'inc');
			if (list($logo) = $chercher_logo($id_hack, 'id_auteur', 'on'))
				rename($logo, str_replace($id_hack, $id_auteur, $logo));
			if (list($logo) = $chercher_logo($id_hack, 'id_auteur', 'off'))
				rename($logo, str_replace($id_hack, $id_auteur, $logo));
		}

		// Restreindre avant de declarer l'auteur
		// (section critique sur les droits)
		if ($id_parent) {
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
	if (($id_article = intval($lier_id_article))
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
	$sauve = $GLOBALS['visiteur_session'];
	include_spip('inc/session');
	foreach(preg_files(_DIR_SESSIONS, '/'.$id_auteur.'_.*\.php') as $session) {
		$GLOBALS['visiteur_session'] = array();
		include $session; # $GLOBALS['visiteur_session'] est alors l'auteur cible
		foreach (array('nom', 'login', 'email', 'statut', 'bio', 'pgp', 'nom_site', 'url_site') AS $var)
			if (isset($auteur[$var]))
				$GLOBALS['visiteur_session'][$var] = $auteur[$var];
		ecrire_fichier_session($session, $GLOBALS['visiteur_session']);
	}
	$GLOBALS['visiteur_session'] = $sauve;

	return array($id_auteur, $echec);
}
?>
