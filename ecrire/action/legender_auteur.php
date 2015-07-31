<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/filtres');
include_spip('inc/actions');
include_spip('inc/acces');
include_spip('base/abstract_sql');

// http://doc.spip.org/@action_legender_auteur
function action_legender_auteur()
{
        $var_f = charger_fonction('controler_action_auteur', 'inc');
        $var_f();

        $arg = _request('arg');

	$echec = array();

        if (!preg_match(",^(\d+)\D(\d*)(\D?)(.*)$,", $arg, $r)) {
		$r = "action_legender_auteur_dist $arg pas compris";
		spip_log($r);
        } else action_legender_post($r);
}

function action_legender_post($r)
{
	global $auteur_session, $bio,
	  $champs_extra,
	  $auteur_session,
	  $email,
	  $id_auteur,
	  $new_login,
	  $new_pass,
	  $new_pass2,
	  $nom,
	  $nom_site_auteur,
	  $perso_activer_imessage,
	  $pgp,
	  $redirect,
	  $statut,
	  $url_site;

	  list($tout, $id_auteur, $ajouter_id_article,$s, $n) = $r;
//
// si id_auteur est hors table, c'est une creation sinon une modif
//
	  $auteur = array();
	  if ($id_auteur) {
		$auteur = spip_fetch_array(spip_query("SELECT * FROM spip_auteurs WHERE id_auteur=$id_auteur"));
	  }
	  if (!$auteur) {
		$id_auteur = 0;
		$statut = '1comite'; // statut par defaut
		$source = 'spip';
		$nom = $n ? $n : _T('ecrire:item_nouvel_auteur');
	  } 

	  $acces = ($id_auteur == $auteur_session['id_auteur']) ? true : " a voir ";
	  $auteur['nom'] = corriger_caracteres($nom);

	// login et mot de passe
	$modif_login = false;
	$old_login = $auteur['login'];

	if (($new_login<>$old_login) AND $auteur['source'] == 'spip') {
		if (admin_general($auteur_session['id_auteur'])) {
			$acces = true;
			if ($new_login) {
				if (strlen($new_login) < 4)
					$echec[]= 'info_login_trop_court';
				else {
					$n = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_auteurs WHERE login=" . spip_abstract_quote($new_login) . " AND id_auteur!=$id_auteur AND statut!='5poubelle'"));
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
			$acces = admin_general($auteur_session['id_auteur']);
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

	if ($modif_login) {
	  // supprimer les sessions de cet auteur
		$var_f = charger_fonction('session', 'inc');
		$var_f($auteur['id_auteur']);
	}

	// seuls les admins peuvent modifier le mail
	// les admins restreints ne peuvent modifier celui des autres admins

	if (isset($email) AND $auteur_session['statut'] == '0minirezo') {
		if (!($ok = ($statut <> '0minirezo'))) {
			if (is_string($acces))
				$acces = admin_general($auteur_session['id_auteur']);
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
	if ($champs_extra) {
		include_spip('inc/extra');
		$extra = extra_recup_saisie("auteurs");
	} else
		$extra = '';

	// l'entrer dans la base
	if (!$echec) {
		if (!$auteur['id_auteur']) { // creation si pas d'id
			$auteur['id_auteur'] = $id_auteur = spip_abstract_insert("spip_auteurs", "(nom)", "('temp')");
			if ($ajouter_id_article)
				spip_abstract_insert("spip_auteurs_articles", "(id_auteur, id_article)", "($id_auteur, $ajouter_id_article)");
		}

		$n = spip_query("UPDATE spip_auteurs SET $query_pass		nom=" . spip_abstract_quote($auteur['nom']) . ",						login=" . spip_abstract_quote($auteur['login']) . ",					bio=" . spip_abstract_quote($auteur['bio']) . ",						email=" . spip_abstract_quote($auteur['email']) . ",					nom_site=" . spip_abstract_quote($auteur['nom_site']) . ",				url_site=" . spip_abstract_quote($auteur['url_site']) . ",				pgp=" . spip_abstract_quote($auteur['pgp']) .					(!$extra ? '' : (", extra = " . spip_abstract_quote($extra) . "")) .			" WHERE id_auteur=".$auteur['id_auteur']);
		if (!$n) die('UPDATE');
	}

// Si on modifie la fiche auteur, reindexer 
	if ($nom OR $statut) {
		if ($GLOBALS['meta']['activer_moteur'] == 'oui') {
			include_spip("inc/indexation");
			marquer_indexer('spip_auteurs', $id_auteur);
		}
	// ..et mettre a jour les fichiers .htpasswd et .htpasswd-admin
		ecrire_acces();
	}

	if ($echec) $echec = '&echec=' . join('@@@', $echec);

	// il faudrait rajouter OR $echec mais il y a conflit avec Ajax

	if (($init = ($tout[0]=='0'))) {
	  // tout nouveau. envoyer le formulaire de saisie du reste
	  // en transmettant le retour eventuel
	  // decode / encode car encode pas necessairement deja fait.

		$ret = !$redirect ? '' 
		  : ('&redirect=' . rawurlencode(rawurldecode($redirect)));

		$redirect = generer_url_ecrire("auteur_infos", "id_auteur=$id_auteur&initial=$init$echec$ret",true);
	} else {
	  // modif: renvoyer le resultat ou a nouveau le formulaire si erreur
		  if (!$redirect) {
		    $redirect = generer_url_ecrire("auteur_infos", "id_auteur=$id_auteur", true, true);
		    $ancre = '';
		  } else 
		    list($redirect,$anc) = split('#',rawurldecode($redirect));

		if (!$echec) 
		  $redirect .= '&initial=-1' . $anc;
		else  {
		  $redirect .= $echec . '&initial=0' . $anc;
		}
	}

	redirige_par_entete($redirect);
}

// http://doc.spip.org/@admin_general
function admin_general($id_auteur)
{
	include_spip('inc/auth');
        return (!spip_num_rows(spip_query("SELECT id_rubrique FROM spip_auteurs_rubriques WHERE id_auteur=" .$id_auteur ." AND id_rubrique!='0' LIMIT 1")));
}

?>
