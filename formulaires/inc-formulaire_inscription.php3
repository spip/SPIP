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
	if (($mode == 'redac' AND lire_meta('accepter_inscriptions') == 'oui')
	OR ($mode == 'forum' AND (
		lire_meta('accepter_visiteurs') == 'oui'
		OR lire_meta('forums_publics') == 'abo'
		)
	))
		$statut = 'nouveau';
	else
		return _T('pass_rien_a_faire_ici');

	// recuperer les donnees envoyees
	$mail_inscription = _request('mail_inscription');
	$nom_inscription = _request('nom_inscription');

	if (test_mail_ins($mode, $mail_inscription)
	AND _request('nom_inscription')) {
		// envoyer les identifiants si l'abonne n'existe pas deja.
		$s = spip_query("SELECT statut, id_auteur, login, pass
		FROM spip_auteurs WHERE email='".addslashes($mail_inscription)."'");
		if (!$row = spip_fetch_array($s)) {
			$login = test_login($nom_inscription, $mail_inscription);
			$id_auteur = spip_abstract_insert('spip_auteurs', 
				'(nom, email, login, statut)',
				"('".addslashes($nom_inscription)."',
				'".addslashes($mail_inscription)."', '$login', '$statut')");
			$pass = creer_pass_pour_auteur($id_auteur);
			$message = envoyer_inscription($mail_inscription,
				$statut, $mode, $login, $pass, $nom_inscription);
		}

		else {
			// existant mais encore muet, renvoyer les infos
			if ($row['statut'] == 'nouveau') {
				// recreer le pass
				$pass = creer_pass_pour_auteur($row['id_auteur']);
				$message = envoyer_inscription(
					$mail_inscription, $row['statut'], $mode,
					$row['login'], $pass, $nom_inscription);
			} else {
				// dead
				if ($row['statut'] == '5poubelle')
					$message = _T('form_forum_access_refuse');
				// deja inscrit
				else
					$message = _T('form_forum_email_deja_enregistre');
			}
		}
	}

	// demande du formulaire
	else {
		if (!$nom_inscription) 
			$message = '';
		else {
			spip_log("Mail incorrect: '$mail_inscription'");
			$message = _T('info_email_invalide');
		}
	}

	return array("formulaire_inscription", 0,
		array('focus' => $focus,
			'target' => _request('target'),
			'message' => $message,
			'mode' => $mode));
}

// fonction qu'on peut redefinir pour filtrer selon l'adresse mail
// cas general: controler juste que l'adresse n'est pas vide et est valide

function test_mail_ins($mode, $mail) {
	if ($mail = trim($mail))
		return email_valide($mail);
}


// envoyer identifiants par mail
function envoyer_inscription($mail, $statut, $type, $login, $pass, $nom) {
	$nom_site_spip = lire_meta("nom_site");
	$adresse_site = lire_meta("adresse_site");
	
	$message = _T('form_forum_message_auto')."\n\n"
	._T('form_forum_bonjour', array('nom'=>$nom))."\n\n";
	if ($type == 'forum') {
		$message .= _T('form_forum_voici1', array('nom_site_spip' => $nom_site_spip, 'adresse_site' => $adresse_site)) . "\n\n";
	} else {
		$message .= _T('form_forum_voici2', array('nom_site_spip' => $nom_site_spip, 'adresse_site' => $adresse_site)) . "\n\n";
	}
	$message .= "- "._T('form_forum_login')." $login\n";
	$message .= "- "._T('form_forum_pass')." $pass\n\n";

	include_ecrire("inc_mail.php3");
	if (envoyer_mail($mail,
	"[$nom_site_spip] "._T('form_forum_identifiants'), $message))
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
		$s = spip_query("SELECT id_auteur FROM spip_auteurs
		WHERE login='$login'");
		if (!spip_num_rows($s))
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
		WHERE id_auteur = ".$id_auteur);
	ecrire_acces();
	
	return $pass;
}

?>
