<?php

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

include_ecrire('inc_abstract_sql.php3');

// Balise independante du contexte
global $balise_FORMULAIRE_INSCRIPTION_collecte ;
$balise_FORMULAIRE_INSCRIPTION_collecte = array();

// args[0] est le parametre 'focus' -- [(#FORMULAIRE_INSCRIPTION{focus})]
function balise_FORMULAIRE_INSCRIPTION_stat($args, $filtres) {
	if (lire_meta('accepter_inscriptions') != 'oui')
		return '';
	else
		return array('redac', ($args[0] == 'focus' ? 'nom_inscription' : ''));
}

function balise_FORMULAIRE_INSCRIPTION_dyn($mode, $focus) {

	// Si une inscription est autorisee, on enregistre le demandeur
	// comme 'nouveau' et on lui envoie ses codes par email ; lors de
	// sa premiere connexion il obtiendra son statut final (auth->activer())
	if (!(($mode == 'redac' AND lire_meta('accepter_inscriptions') == 'oui')
	OR ($mode == 'forum' AND (
		lire_meta('accepter_visiteurs') == 'oui'
		OR lire_meta('forums_publics') == 'abo'
		)
	    )))
		return _T('pass_rien_a_faire_ici');

	// recuperer les donnees envoyees
	$mail_inscription = trim(_request('mail_inscription'));
	$nom_inscription = _request('nom_inscription');

	if (!$nom_inscription) 
		$message = '';
	elseif (!test_mail_ins($mode, $mail_inscription))
		$message = _T('info_email_invalide');
	else	$message = message_inscription($mail_inscription,
					       $nom_inscription,
					       false,
					       ($mode == 'forum')  ?
					       'form_forum_voici1' :
					       'form_forum_voici2');

	return array("formulaire_inscription", $GLOBALS['delais'],
			array('focus' => $focus,
				'target' => _request('target'),
				'message' => $message,
				'mode' => $mode,
				'self' => $GLOBALS["clean_link"]->getUrl()
				));
}

// fonction qu'on peut redefinir pour filtrer selon l'adresse mail
// cas general: controler juste que l'adresse n'est pas vide et est valide

function test_mail_ins($mode, $mail) {
	return  ($mail = trim($mail)) AND email_valide($mail);
}

// creer un nouvel utilisateur et lui envoyer un mail avec ses identifiants

function message_inscription($mail_inscription, $nom_inscription, $force, $mode) {
	$s = spip_query("SELECT statut, id_auteur, login
		FROM spip_auteurs WHERE email='".addslashes($mail_inscription)."'");
	$row = spip_fetch_array($s);

	if (!$row) {
	// il n'existe pas, creer les identifiants 
		$login = test_login($nom_inscription, $mail_inscription);
		$pass = creer_pass_pour_auteur(spip_abstract_insert('spip_auteurs', 
				'(nom, email, login, statut)',
				"('".
				addslashes($nom_inscription) .
				"', '".
				addslashes($mail_inscription) .
				"', '" .
				$login .
				"', 'nouveau')"));

		return envoyer_inscription($mail_inscription, 'nouveau', $mode, $login, $pass, $nom_inscription);
	} else {
		// existant mais encore muet, ou ressucite: renvoyer les infos
		if ((($row['statut'] == 'nouveau') && !$force) ||
		(($row['statut'] == '5poubelle') && $force)) {
			// recreer le pass
			$pass = creer_pass_pour_auteur($row['id_auteur']);
			return envoyer_inscription($mail_inscription, $row['statut'], $mode,
				     $row['login'], $pass, $nom_inscription);
		} else {
			// irrecuperable
			if ($row['statut'] == '5poubelle')
				return_T('form_forum_access_refuse');
			else
				// deja inscrit
				return _T('form_forum_email_deja_enregistre');
		}
	}
}


// envoyer identifiants par mail
function envoyer_inscription($mail, $statut, $mode, $login, $pass, $nom) {
	$nom_site_spip = lire_meta("nom_site");
	$adresse_site = lire_meta("adresse_site");
	
	$message = _T('form_forum_message_auto')."\n\n"
	  . _T('form_forum_bonjour', array('nom'=>$nom))."\n\n"
	  . _T($mode, array('nom_site_spip' => $nom_site_spip, 'adresse_site' => $adresse_site)) . "\n\n"
	  . "- "._T('form_forum_login')." $login\n"
	  . "- "._T('form_forum_pass')." $pass\n\n";

	include_ecrire("inc_mail.php3");
	if (envoyer_mail($mail,
			 "[$nom_site_spip] "._T('form_forum_identifiants'),
			 $message))
		return _T('form_forum_identifiant_mail');
	else
		return _T('form_forum_probleme_mail');
}

function test_login($nom, $mail) {
	include_ecrire('inc_charsets.php3');
	$nom = strtolower(translitteration($nom));
	$login_base = ereg_replace("[^a-zA-Z0-9_]", "_", $nom);

	// il faut eviter que le login soit vraiment trop court
	if (strlen($login_base) < 3) {
		$mail = strtolower(translitteration(preg_replace('/@.*/', '', $mail)));
		$login_base = ereg_replace("[^a-zA-Z0-9]", "_", $nom);
	}
	if (strlen($login_base) < 3)
		$login_base = 'user';

	// eviter aussi qu'il soit trop long (essayer d'attraper le prenom)
	if (strlen($login_base) > 10) {
		$login_base = preg_replace("/^(.{4,}(_.{1,7})?)_.*/",
			'\1', $login_base);
		$login_base = substr($login_base, 0,13);
	}

	$login = $login_base;

	for ($i = 1; ; $i++) {
	  if (!spip_num_rows(spip_query("SELECT id_auteur FROM spip_auteurs WHERE login='$login' LIMIT 1")))
			return $login;
		$login = $login_base.$i;
	}
}

function creer_pass_pour_auteur($id_auteur) {
	include_ecrire("inc_acces.php3");
	$pass = creer_pass_aleatoire(8, $id_auteur);
	$mdpass = md5($pass);
	$htpass = generer_htpass($pass);
	spip_query("UPDATE spip_auteurs
		SET pass='$mdpass', htpass='$htpass'
		WHERE id_auteur = ".intval($id_auteur));
	ecrire_acces();
	
	return $pass;
}

?>
