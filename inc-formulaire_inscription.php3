<?php

include_ecrire('inc_abstract_sql.php3');

global $balise_FORMULAIRE_INSCRIPTION_collecte ;
$balise_FORMULAIRE_INSCRIPTION_collecte = array('mail_inscription', 'nom_inscription');

function balise_FORMULAIRE_INSCRIPTION_stat($args, $filtres)
{
  list($mail_inscription, $nom_inscription) = $args;
  return ((lire_meta('accepter_inscriptions') != 'oui') ? '' :
	  array('redac', $mail_inscription, $nom_inscription));
}

function balise_FORMULAIRE_INSCRIPTION_dyn($type, $mail_inscription, $nom_inscription) {
	if ($type == 'redac') {
		$statut = "nouveau";
	}
	else if ($type == 'forum') {
		$statut = "6forum";
	}
	else return ''; // tentative de hack...?

	if (test_mail_ins($type, $mail_inscription) && $nom_inscription) {
		include(_FILE_CONNECT);
		// envoyer les identifiants si l'abonne n'existe pas déjà.
		if (!$row = spip_fetch_array(spip_query("SELECT statut, id_auteur, login, pass FROM spip_auteurs WHERE email='".addslashes($mail_inscription)."' LIMIT 1")))
		  {
			include_ecrire("inc_acces.php3");
			$pass = creer_pass_aleatoire(8, $mail_inscription);
			$login = test_login($mail_inscription);
			$mdpass = md5($pass);
			$htpass = generer_htpass($pass);
			$r = spip_insert('spip_auteurs', 
					 '(nom, email, login, pass, statut, htpass)',
					 "('".addslashes($nom_inscription)."',  '".addslashes($mail_inscription)."', '$login', '$mdpass', '$statut', '$htpass')");
			ecrire_acces();
			return envoyer_inscription($mail_inscription, $statut, $type, $login, $pass);
		  }

		else {
		  // existant mais encore muet, renvoyer les infos
			if ($row['statut'] == 'nouveau') {
			  return (envoyer_inscription($mail_inscription, $row['statut'], $type, $row['login'], $row['pass']));
			} else {
				if ($row['statut'] == '5poubelle')
		  // dead
				  return _T('form_forum_access_refuse');
				else  
		  // deja inscrit
				  return _T('form_forum_email_deja_enregistre');
			}
		}
	}
	// demande du formulaire
	else {
		if (!$nom_inscription) 
		  {
		       return array("formulaire_inscription-dist",0);
		  }
		else {
		  spip_log("Mail incorrect: '$mail_inscription'");
		  return _L('adresse mail incorrecte');
		}
	}
}

// fonction qu'on peut redefinir pour filtrer selon l'adresse mail
// cas general: controler juste que l'adresse n'est pas vide

function test_mail_ins($type, $mail_inscription) {
  return trim($mail_inscription);
}

	// envoyer identifiants par mail
function envoyer_inscription($mail, $statut, $type, $login, $pass) {
	$nom_site_spip = lire_meta("nom_site");
	$adresse_site = lire_meta("adresse_site");
	
	$message = _T('form_forum_message_auto')."\n\n"._T('form_forum_bonjour')."\n\n";
	if ($type == 'forum') {
		$message .= _T('form_forum_voici1', array('nom_site_spip' => $nom_site_spip, 'adresse_site' => $adresse_site)) . "\n\n";
	} else {
		$message .= _T('form_forum_voici2', array('nom_site_spip' => $nom_site_spip, 'adresse_site' => $adresse_site)) . "\n\n";
	}
	$message .= "- "._T('form_forum_login')." $login\n";
	$message .= "- "._T('form_forum_pass')." $pass\n\n";

	include_ecrire("inc_mail.php3");
	if (envoyer_mail($mail, "[$nom_site_spip] "._T('form_forum_identifiants'), $message))
	  return _T('form_forum_identifiant_mail');
	else
	  return _T('form_forum_probleme_mail');
}


function test_login($mail) {
	if (strpos($mail, "@") > 0) $login_base = substr($mail, 0, strpos($mail, "@"));
	else $login_base = $mail;

	$login_base = strtolower($login_base);
	$login_base = ereg_replace("[^a-zA-Z0-9]", "", $login_base);
	if (!$login_base) $login_base = "user";

	for ($i = 0; ; $i++) {
		if ($i) $login = $login_base.$i;
		else $login = $login_base;
		$query = "SELECT id_auteur FROM spip_auteurs WHERE login='$login'";
		$result = spip_query($query);
		if (!spip_num_rows($result)) break;
	}

	return $login;
}

?>
