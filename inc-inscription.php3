<?php

global $inscription_array;
$inscription_array = array();

function inscription_stat($args, $filtres)
{
  return ((lire_meta('accepter_inscriptions') != 'oui') ? '' :
	  array("'redac'"));
}

function inscription_dyn($type) {

	switch (status_inscription($type)) {
		case 1: return '';
		case 2: return '';
		case 3: return "<div class='reponse_formulaire'><b>" ._T('form_forum_identifiant_mail') . "</b></div>";
		case 4: return "<div class='reponse_formulaire'><b>" ._T('form_forum_probleme_mail')  . "</b></div>";
		case 5: return "<div class='reponse_formulaire'><b>" ._T('form_forum_access_refuse')  . "</b></div>";
		case 6: return "<div class='reponse_formulaire'><b>" ._T('form_forum_email_deja_enregistre')  . "</b></div>";
		case 7: 

			$link = new Link;
			$url = $link->getUrl();
			$url = quote_amp($url);
			return  _T('form_forum_indiquer_nom_email') .
			  "<form method='post' action='$url' style='border: 0px; margin: 0px;'>\n" .
			  "<div><b>"._T('form_pet_votre_nom')."</b></div>" .
			  "<div><input type=\"text\" class=\"forml\" name=\"nom_inscription\" value=\"\" size=\"30\" /></div>" .
			  "<div><b>"._T('form_pet_votre_email')."</b></div>" .
			  "<div><input type=\"text\" class=\"forml\" name=\"mail_inscription\" value=\"\" size=\"30\" /></div>" .
			  "<div align=\"right\"><input type=\"submit\" class=\"spip_bouton\" value=\""._T('bouton_valider')."\" /></div>" .
			  "</form>";
	}
}

function status_inscription($type) {

	if ($type == 'redac') {
		if (lire_meta("accepter_inscriptions") != "oui") return 1;
		$statut = "nouveau";
	}
	else if ($type == 'forum') {
		$statut = "6forum";
	}
	else return 2; // tentative de hack...?

	global $mail_inscription, $nom_inscription;

	if ($mail_inscription && $nom_inscription) {
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
				  return 5;
				else  
		  // deja inscrit
				  return 6;
			}
		}
	}
	// demande du formulaire
	else return 7;
}

	// envoyer identifiants par mail
function envoyer_inscription($mail, $statut, $type, $pass, $login) {
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
	  return 3;
	else
	  return 4;
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
