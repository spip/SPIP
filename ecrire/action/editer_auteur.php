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
include_spip('base/abstract_sql');

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
	$email = _request('email');
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
	  $auteur = array();
	  if ($id_auteur) {
		$auteur = spip_fetch_array(spip_query("SELECT * FROM spip_auteurs WHERE id_auteur=$id_auteur"));
	  }
	  if (!$auteur) {
		$id_auteur = 0;
		$source = 'spip';
		$statut = '1comite'; // statut par defaut
		if ($s) {
		  if (in_array($s,array('0minirezo','1comite','5poubelle','6forum')))
		    $statut = $s;
		  else spip_log("action_legender_auteur_dist: statut $s incompris");
		}
	  }

	  $acces = ($id_auteur == $auteur_session['id_auteur']) ? true : " a voir ";
	  $auteur['nom'] = corriger_caracteres(_request('nom'));

	// login et mot de passe
	$modif_login = false;
	$old_login = $auteur['login'];

	if (isset($new_login)
	AND ($new_login<>$old_login)
	AND ($auteur['source'] == 'spip' OR !$GLOBALS['ldap_present'])) {
		if (admin_general($auteur_session)) {
			$acces = true;
			if ($new_login) {
				if (strlen($new_login) < 4)
					$echec[]= 'info_login_trop_court';
				else {
					$n = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_auteurs WHERE login=" . _q($new_login) . " AND id_auteur!=$id_auteur AND statut!='5poubelle'"));
					if ($n['n'])
						$echec[]= 'info_login_existant';
					else if ($new_login != $old_login) {
						$modif_login = true;
						$auteur['login'] = $new_login;
					}
				}
			}
		// suppression du login
			else {
				$auteur['login'] = '';
				$modif_login = true;
			}
		}
	}

	// changement de pass, a securiser en jaja ?

	if ($new_pass AND ($statut != '5poubelle') AND $auteur['login'] AND $auteur['source'] == 'spip') {
		if (is_string($acces))
			$acces = admin_general($auteur_session);
		if ($acces) {
			if ($new_pass != $new_pass2)
				$echec[]= 'info_passes_identiques';
			else if ($new_pass AND strlen($new_pass) < 6)
				$echec[]= 'info_passe_trop_court';
			else {
				$modif_login = true;
				$auteur['new_pass'] = $new_pass;
			}
		}
	}

	if ($modif_login AND ($auteur['id_auteur']<>$auteur_session['id_auteur'])) {
		// supprimer les sessions de cet auteur
		$session = charger_fonction('session', 'inc');
		$session($auteur['id_auteur']);
	}

	// seuls les admins peuvent modifier le mail
	// les admins restreints ne peuvent modifier celui des autres admins

	if (_request('email') AND $auteur_session['statut'] == '0minirezo') {
		if (!($ok = ($statut <> '0minirezo'))) {
			if (is_string($acces))
				$acces = admin_general($auteur_session);
		}

		if ($ok OR $acces) {
			$email = trim($email);	 
			if ($email !='' AND !email_valide($email)) 
				$echec[]= 'info_email_invalide';
			$auteur['email'] = $email;
		}
	}

	if ($auteur_session['id_auteur'] == $id_auteur) {
		if ($perso_activer_imessage) {
			spip_query("UPDATE spip_auteurs SET imessage='$perso_activer_imessage' WHERE id_auteur=$id_auteur");
			$auteur['imessage'] = $perso_activer_imessage;
		}
	}

	// variables sans probleme
	$auteur['bio'] = corriger_caracteres($bio);
	$auteur['pgp'] = corriger_caracteres($pgp);
	$auteur['nom_site'] = corriger_caracteres($nom_site_auteur); // attention mix avec $nom_site_spip ;(
	$auteur['url_site'] = vider_url($url_site, false);

	if ($new_pass) {
		$htpass = generer_htpass($new_pass);
		$alea_actuel = creer_uniqid();
		$alea_futur = creer_uniqid();
		$pass = md5($alea_actuel.$new_pass);
		$query_pass = " pass='$pass', htpass='$htpass', alea_actuel='$alea_actuel', alea_futur='$alea_futur', ";
		if ($auteur['id_auteur'])
		  effacer_low_sec($auteur['id_auteur']);
	} else
		$query_pass = '';

	// recoller les champs du extra
	if ($GLOBALS['champs_extra']) {
		include_spip('inc/extra');
		$extra = extra_update('auteurs', $id_auteur);
	} else
		$extra = '';

	// l'entrer dans la base
	if (!$echec) {
		if (!$auteur['id_auteur']) { // creation si pas d'id
			$auteur['id_auteur'] = $id_auteur = spip_abstract_insert("spip_auteurs", "(nom,statut)", "('temp','" . $statut . "')");

			// recuperer l'eventuel logo charge avant la creation
			$id_hack = 0 - $GLOBALS['auteur_session']['id_auteur'];
			$chercher_logo = charger_fonction('chercher_logo', 'inc');
			if (list($logo) = $chercher_logo($id_hack, 'id_auteur', 'on'))
				rename($logo, str_replace($id_hack, $id_auteur, $logo));
			if (list($logo) = $chercher_logo($id_hack, 'id_auteur', 'off'))
				rename($logo, str_replace($id_hack, $id_auteur, $logo));
		}

		spip_query("UPDATE spip_auteurs SET $query_pass		nom=" . _q($auteur['nom']) . ",						login=" . _q($auteur['login']) . ",					bio=" . _q($auteur['bio']) . ",						email=" . _q($auteur['email']) . ",					nom_site=" . _q($auteur['nom_site']) . ",				url_site=" . _q($auteur['url_site']) . ",				pgp=" . _q($auteur['pgp']) .					(!$extra ? '' : (", extra = " . _q($extra) . "")) .			" WHERE id_auteur=".$auteur['id_auteur']);
	}


	//
	// Modifications de statut
	//
	// TODO : autorisations correspondantes !
	//
	if ($statut = _request('statut')
	AND autoriser('modifier', 'auteur', $id_auteur, $qui = null,
	$opt = array('statut'=>$statut))) {
		if (!in_array($statut,array('0minirezo','1comite','5poubelle','6forum'))) {
		  spip_log("action_instituer_auteur_dist: $statut incompris  pour $id_auteur");
		} else {
			spip_query("UPDATE spip_auteurs SET statut="._q($statut) . " WHERE id_auteur=" . _q($id_auteur));
		}
	}

	// Rubriques restreintes
	$restreintes = _request('restreintes');
	if ($id_parent = intval(_request('id_parent'))) {
		if (is_array($restreintes))
			$restreintes[] = $id_parent;
		else
			$restreintes = array($id_parent);
	}
	if (is_array($restreintes)
	AND autoriser('modifier', 'auteur', $id_auteur, $qui = null,
	$opt = array('restreint'=>$restreintes))) {
		include_spip('base/abstract_sql');
		spip_query("DELETE FROM spip_auteurs_rubriques WHERE id_auteur="._q($id_auteur));
		foreach (array_unique($restreintes) as $id_rub)
			if ($id_rub = intval($id_rub)) // si '0' on ignore
				spip_abstract_insert('spip_auteurs_rubriques', "(id_auteur,id_rubrique)", "($id_auteur,$id_rub)");
	}


	// Lier a un article
	if ($id_article = intval(_request('lier_id_article'))
	AND autoriser('modifier', 'article', $id_article)
	) {
		spip_query("INSERT spip_auteurs_articles (id_article,id_auteur) VALUES ($id_article,$id_auteur)");
	}

	// Si on modifie la fiche auteur, reindexer
	if ($GLOBALS['meta']['activer_moteur'] == 'oui') {
		include_spip("inc/indexation");
		marquer_indexer('spip_auteurs', $id_auteur);
	}
	// ..et mettre a jour les fichiers .htpasswd et .htpasswd-admin
	ecrire_acces();

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

// http://doc.spip.org/@admin_general
function admin_general($session)
{
	static $ok;
	if (!isset($ok)) {
		include_spip('inc/auth');
        $ok = ($session['statut']=='0minirezo') 
        AND !spip_num_rows(spip_query("SELECT id_rubrique FROM spip_auteurs_rubriques WHERE id_auteur=" .$session['id_auteur'] ." AND id_rubrique!='0' LIMIT 1"));
	}

	return $ok;
}


?>
