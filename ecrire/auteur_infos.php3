<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

include ("inc.php3");

$var_f = find_in_path("inc_auteur_infos.php");
if ($var_f)
  include($var_f);
 else
   include_ecrire("inc_auteur_infos.php");

// securite
$id_auteur = floor($id_auteur);

//
// Recuperer id_auteur ou se preparer a l'inventer
//
unset($auteur);

if ($id_auteur) {
	$auteur = spip_fetch_array(spip_query("SELECT * FROM spip_auteurs WHERE id_auteur=$id_auteur"));
	$new = false;	// eviter hack
} else {
	if (!$auteur['nom'] = $titre) {
		$auteur['nom'] = filtrer_entites(_T('item_nouvel_auteur'));
		$onfocus = " onfocus=\"if(!antifocus){this.value='';antifocus=true;}\"";
	}
	$auteur['statut'] = '1comite'; // statut par defaut a la creation
	$auteur['source'] = 'spip';
}

// securite

if (!statut_modifiable_auteur($id_auteur, $auteur)) {
	gros_titre(_T('info_acces_interdit'));
	exit;
 }


//
// Modification (et creation si besoin)
//

// si on poste un nom, c'est qu'on modifie une fiche auteur
if (strval($nom)!='') {
	$auteur['nom'] = corriger_caracteres($nom);

	// login et mot de passe
	unset ($modif_login);
	$old_login = $auteur['login'];
	if (($new_login<>$old_login) AND $connect_statut == '0minirezo' AND $connect_toutes_rubriques AND $auteur['source'] == 'spip') {
		if ($new_login) {
			if (strlen($new_login) < 4)
				$echec .= "<p>"._T('info_login_trop_court');
			else if (spip_num_rows(spip_query("SELECT * FROM spip_auteurs WHERE login='".addslashes($new_login)."' AND id_auteur!=$id_auteur AND statut!='5poubelle'")))
				$echec .= "<p>"._T('info_login_existant');
			else if ($new_login != $old_login) {
				$modif_login = true;
				$auteur['login'] = $new_login;
			}
		}
		// suppression du login
		else {
			$auteur['login'] = '';
			$modif_login = true;
		}
	}

	// changement de pass, a securiser en jaja ?
	if ($new_pass AND ($statut != '5poubelle') AND $auteur['login'] AND $auteur['source'] == 'spip') {
		if ($new_pass != $new_pass2)
			$echec .= "<p>"._T('info_passes_identiques');
		else if ($new_pass AND strlen($new_pass) < 6)
			$echec .= "<p>"._T('info_passe_trop_court');
		else {
			$modif_login = true;
			$auteur['new_pass'] = $new_pass;
		}
	}

	if ($modif_login) {
		include_ecrire('inc_session.php3');
		zap_sessions ($auteur['id_auteur'], true);
		if ($connect_id_auteur == $auteur['id_auteur'])
			supprimer_session($GLOBALS['spip_session']);
	}

	// email
	// seuls les admins peuvent modifier l'email
	// les admins restreints peuvent modifier l'email des redacteurs
	// mais pas des autres admins
	if ($connect_statut == '0minirezo'
	AND ($connect_toutes_rubriques OR $statut<>'0minirezo')) { 
		if ($email !='' AND !email_valide($email)) {
			$echec .= "<p>"._T('info_email_invalide');
			$auteur['email'] = $email;
		} else
			$auteur['email'] = $email;
	}

	if ($connect_id_auteur == $id_auteur) {
		if ($perso_activer_imessage) {
			$query = "UPDATE spip_auteurs SET imessage='$perso_activer_imessage' WHERE id_auteur=$id_auteur";
			$result = spip_query($query);
			$auteur['imessage'] = $perso_activer_imessage;
		}
	}

	// variables sans probleme
	$auteur['bio'] = corriger_caracteres($bio);
	$auteur['pgp'] = corriger_caracteres($pgp);
	$auteur['nom_site'] = corriger_caracteres($nom_site_auteur); // attention mix avec $nom_site_spip ;(
	$auteur['url_site'] = vider_url($url_site);

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
		include_ecrire("inc_extra.php3");
		$extra = extra_recup_saisie("auteurs");
		$add_extra = ", extra = '".addslashes($extra)."'";
	} else
		$add_extra = '';

	// l'entrer dans la base
	if (!$echec) {
		if (!$auteur['id_auteur']) { // creation si pas d'id
		  $auteur['id_auteur'] = spip_abstract_insert("spip_auteurs", "(nom)", "('temp')");

			$id_auteur = $auteur['id_auteur'];

			if ($ajouter_id_article = intval($ajouter_id_article))
				spip_query("INSERT INTO spip_auteurs_articles (id_auteur, id_article) VALUES ($id_auteur, $ajouter_id_article)");
		}

		$query = "UPDATE spip_auteurs SET $query_pass
			nom='".addslashes($auteur['nom'])."',
			login='".addslashes($auteur['login'])."',
			bio='".addslashes($auteur['bio'])."',
			email='".addslashes($auteur['email'])."',
			nom_site='".addslashes($auteur['nom_site'])."',
			url_site='".addslashes($auteur['url_site'])."',
			pgp='".addslashes($auteur['pgp'])."'
			$add_extra
			WHERE id_auteur=".$auteur['id_auteur'];
		spip_query($query) OR die($query);
	}
}

// Appliquer des modifications de statut
modifier_statut_auteur($auteur, $_POST['statut'], $_POST['id_parent'], $_GET['supp_rub']);


// Si on modifie la fiche auteur, reindexer et modifier htpasswd
if ($nom OR $statut) {
	if (lire_meta('activer_moteur') == 'oui') {
		include_ecrire ("inc_index.php3");
		marquer_indexer('auteur', $id_auteur);
	}

	// Mettre a jour les fichiers .htpasswd et .htpasswd-admin
	ecrire_acces();
}

// Redirection
if (!$echec AND $redirect_ok == "oui") {
	redirige_par_entete($redirect ? rawurldecode($redirect) : "auteurs_edit.php3?id_auteur=$id_auteur");
}


if (function_exists('affiche_auteur_info'))
  $var_nom = 'affiche_auteur_info';
 else
  $var_nom = 'affiche_auteur_info_dist';

$var_nom($id_auteur, $auteur,  $echec, $redirect, $ajouter_id_article);

?>
