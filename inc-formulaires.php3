<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_FORMULAIRES")) return;
define("_INC_FORMULAIRES", "1");

function test_pass() {
	include_ecrire("inc_acces.php3");
	for (;;) {
		$passw = creer_pass_aleatoire();
		$query = "SELECT statut FROM spip_signatures WHERE statut='$passw'";
		$result = spip_query($query);
		if (!spip_num_rows($result)) break;
	}
	return $passw;
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

function erreur($zetexte){
	global $spip_lang_rtl;
 	return "<BR><IMG SRC='puce$spip_lang_rtl.gif' BORDER=0> $zetexte";
}


function formulaire_signature($id_article) {
	global $val_confirm, $nom_email, $adresse_email, $message, $nom_site, $url_site, $url_page;

	include_ecrire("inc_texte.php3");
	include_ecrire("inc_filtres.php3");

	echo "<div class='formulaire'>";
	echo "<a name='sp$id_article'></a>\n";

	if ($val_confirm) {
		$query_sign = "SELECT * FROM spip_signatures WHERE statut='$val_confirm'";
		$result_sign = spip_query($query_sign);
		if (spip_num_rows($result_sign) > 0) {
			while($row = spip_fetch_array($result_sign)) {
				$id_signature = $row['id_signature'];
				$id_article = $row['id_article'];
				$date_time = $row['date_time'];
				$nom_email = $row['nom_email'];
				$ad_email = $row['ad_email'];
				$nom_site=$row['nom_site'];
				$url_site=$row['url_site'];
				$message=$row['message'];
				$statut=$row['statut'];
			}

			$query_petition="SELECT * FROM spip_petitions WHERE id_article=$id_article";
		 	$result_petition=spip_query($query_petition);

			while($row=spip_fetch_array($result_petition)) {
				$id_article=$row['id_article'];
				$email_unique=$row['email_unique'];
				$site_obli=$row['site_obli'];
				$site_unique=$row['site_unique'];
				$message_petition=$row['message'];
				$texte_petition=$row['texte'];
			}

			if ($email_unique=="oui") {
				$email=addslashes($adresse_email);
				$query="SELECT * FROM spip_signatures WHERE id_article=$id_article AND ad_email='$email' AND statut='publie'";
				$result=spip_query($query);
				if (spip_num_rows($result)>0){
					$texte .= erreur(_L("Vous avez d&eacute;j&agrave; sign&eacute; ce texte."));
					$refus = "oui";
				}
			}

			if ($site_unique=="oui") {
				$site=addslashes($url_site);
				$query="SELECT * FROM spip_signatures WHERE id_article=$id_article AND url_site='$site' AND statut='publie'";
				$result=spip_query($query);
				if (spip_num_rows($result)>0){
					$texte .= erreur(_L("Ce site est d&eacute;j&agrave; enregistr&eacute;"));
					$refus = "oui";
				}
			}

			if ($refus=="oui") {
				$texte .= erreur(_L("Vous &ecirc;tes d&eacute;j&agrave; inscrit."));
			}
			else {
				$query = "UPDATE spip_signatures SET statut=\"publie\" WHERE id_signature='$id_signature'";
				$result = spip_query($query);

				$texte .= erreur(_L("Votre signature est valid&eacute;e. Elle appara&icirc;tra lors de la prochaine mise &agrave; jour du site. Merci&nbsp;!"));
			}
		}else{
			$texte .= erreur(_L("Aucune signature ne correspond &agrave; ce code..."));
		}
		echo "<div class='reponse_formulaire'>$texte</div>";
	}
	else if ($nom_email AND $adresse_email) {
		if ($GLOBALS['db_ok']) {
			include_ecrire("inc_mail.php3");

			$query_petition = "SELECT * FROM spip_petitions WHERE id_article=$id_article";
		 	$result_petition = spip_query($query_petition);

			while($row = spip_fetch_array($result_petition)) {
				$id_article = $row['id_article'];
				$email_unique = $row['email_unique'];
				$site_obli = $row['site_obli'];
				$site_unique = $row['site_unique'];
				$message_petition = $row['message'];
				$texte_petition = $row['texte'];
			}

			if (strlen($nom_email) < 2) {
				$reponse_signature .= erreur(_L("Veuillez indiquer votre nom."));
				$refus = "oui";
			}

			if ($adresse_email == _T('info_mail_fournisseur')) {
				$reponse_signature .= erreur(_L("Veuillez indiquer votre adresse email."));
				$refus = "oui";
			}

			if ($email_unique == "oui") {
				$email = addslashes($adresse_email);
				$query = "SELECT * FROM spip_signatures WHERE id_article=$id_article AND ad_email='$email' AND statut='publie'";
				$result = spip_query($query);
				if (spip_num_rows($result) > 0) {
					$reponse_signature .= erreur(_L("Vous avez d&eacute;j&agrave; sign&eacute; ce texte."));
					$refus = "oui";
				}
			}

			if (!email_valide($adresse_email)) {
				$reponse_signature .= erreur(_L("Votre adresse email n'est pas valide."));
				$refus = "oui";
			}

			if ($site_obli == "oui") {
				if (!$nom_site) {
					$reponse_signature .= erreur(_L("Veuillez indiquer le nom de votre site."));
					$refus = "oui";
				}
				include_local ("ecrire/inc_sites.php3");

				if (!recuperer_page($url_site)) {
					$reponse_signature .= erreur(_L("L'URL que vous avez indiqu&eacute;e n'est pas valide."));
					$refus = "oui";
				}
			}
			if ($site_unique == "oui") {
				$site = addslashes($url_site);
				$query = "SELECT * FROM spip_signatures WHERE id_article=$id_article AND url_site='$site' AND (statut='publie' OR statut='poubelle')";
				$result = spip_query($query);
				if (spip_num_rows($result) > 0) {
					$reponse_signature .= erreur(_L("Ce site est d&eacute;j&agrave; enregistr&eacute;"));
					$refus = "oui";
				}
			}

			$passw = test_pass();

		 	if ($refus == "oui") {
		  		$reponse_signature.= "<P><FONT COLOR='red'><B>"._L("Votre signature n'est pas prise en compte.")."</B></FONT><P>";
			}
			else {
				$query_site = "SELECT titre FROM spip_articles WHERE id_article=$id_article";
				$result_site = spip_query($query_site);
				while($row = spip_fetch_array($result_site)) {
					$titre = $row['titre'];
				}

				$link = new Link($url_page);
				$link->addVar('val_confirm', $passw);
				$url = $link->getUrl("sp$id_article");

				$messagex = _L("Bonjour,

Vous avez demand&eacute; &agrave; signer la p&eacute;tition :
$titre.

Vous avez fourni les informations suivantes :
    Nom: $nom_email
    Site: $nom_site - $url_site


IMPORTANT...
Pour valider votre signature, il suffit de vous connecter &agrave;
l'adresse ci-dessous (dans le cas contraire, votre demande
sera rejet&eacute;e) :

    $url


Merci de votre participation
");

				envoyer_mail($adresse_email, _L("Veuillez confirmer votre signature :")." ".$titre, $messagex);

				$reponse_signature.="<P><B>"._L("Un courrier &eacute;lectronique de confirmation vient de vous &ecirc;tre envoy&eacute;. Vous devrez visiter l'adresse Web mentionn&eacute;e dans ce courrier pour valider votre signature.")."</B>";

				$nom_email = addslashes($nom_email);
				$nom_site = addslashes($nom_site);
				$message = addslashes($message);

		 		$query = "INSERT INTO spip_signatures (id_article, date_time, nom_email, ad_email, nom_site, url_site, message, statut) ".
		 			"VALUES ('$id_article', NOW(), '$nom_email', '$adresse_email', '$nom_site', '$url_site', '$message', '$passw')";
				$result = spip_query($query);
			}
		}
		else {
			$reponse_signature = _L("Probl&egrave;me technique, les signatures sont temporairement suspendues.");
		}
		echo "<div class='reponse_formulaire'>$reponse_signature</div>";
	}

	else {
		$query_petition = "SELECT * FROM spip_petitions WHERE id_article=$id_article";
 		$result_petition = spip_query($query_petition);

		if ($row_petition = spip_fetch_array($result_petition)) {
			$id_article = $row_petition['id_article'];
			$email_unique = $row_petition['email_unique'];
			$site_obli = $row_petition['site_obli'];
			$site_unique = $row_petition['site_unique'];
			$message_petition = $row_petition['message'];
			$texte_petition = $row_petition['texte'];

			$link = new Link;
			$url = lire_meta("adresse_site").'/'.$link->getUrl();
			$link = new Link;
			$link->addVar('url_page', $url);
			echo $link->getForm('POST', "sp$id_article");

			echo propre($texte_petition);

			echo "<p><fieldset><B>"._L("Votre nom ou pseudo")."</B><BR>";
			echo "<input type=\"text\" class=\"forml\" name=\"nom_email\" value=\"\" size=\"20\">";

			echo "<p><B>"._L("Votre adresse email")."</B><BR>";
			echo "<input type=\"text\" class=\"forml\" name=\"adresse_email\" value=\"\" size=\"20\"></fieldset>";

			echo "<P><fieldset>";
			if ($site_obli != "oui") {
				echo  "<B>"._L("Si vous avez un site Web, vous pouvez l'indiquer ci-dessous")."</B><p>";
			}
			echo _L("<B>Nom de votre site Web")."</B><BR>";
			echo "<input type=\"text\" class=\"forml\" name=\"nom_site\" value=\"\" size=\"20\">";

			echo "<p><B>"._L("Adresse de votre site")."</B><BR>";
			echo "<input type=\"text\" class=\"forml\" name=\"url_site\" value=\"http://\" size=\"20\"></fieldset>";

			if ($message_petition == "oui") {
				echo "<p><fieldset>";

				echo "<B>"._L("Un message, un commentaire&nbsp;?")."</B><BR>";
				echo "<textarea name=\"message\" rows=\"3\" class=\"forml\" cols=\"20\" wrap='soft'>";
				echo "</textarea></fieldset><p>\n";
			}
			else {
				echo "<input type=\"hidden\" name=\"message\" value=\"\">";
			}

			echo "<DIV ALIGN=\"right\"><INPUT TYPE=\"submit\" NAME=\"Valider\" CLASS=\"spip_bouton\" VALUE=\""._L("Valider")."\">";
			echo "</DIV></FORM>\n";
		}
	}
	echo "</div>\n";
}


// inscrire les visiteurs dans l'espace public (statut 6forum) ou prive (statut nouveau->1comite)
function formulaire_inscription($type) {
	$request_uri = $GLOBALS["REQUEST_URI"];
	global $mail_inscription;
	global $nom_inscription;

	if ($type == 'redac') {
		if (lire_meta("accepter_inscriptions") != "oui") return;
		$statut = "nouveau";
	}
	else if ($type == 'forum') {
		$statut = "6forum";
	}
	else {
		return; // tentative de hack...?
	}

	if ($mail_inscription && $nom_inscription) {
				$query = "SELECT * FROM spip_auteurs WHERE email='$mail_inscription'";
		$result = spip_query($query);

		echo "<div class='reponse_formulaire'>";

		// l'abonne existe deja.
	 	if ($row = spip_fetch_array($result)) {
			$id_auteur = $row['id_auteur'];
			$statut = $row['statut'];

			unset ($continue);
			if ($statut == '5poubelle')
				echo "<b>"._L("Vous n'avez plus acc&egrave;s &agrave; ce site.")."</b>";
			else if ($statut == 'nouveau') {
				spip_query ("DELETE FROM spip_auteurs WHERE id_auteur=$id_auteur");
				$continue = true;
			} else
				echo "<b>"._L("Cette adresse e-mail est d&eacute;j&agrave; enregistr&eacute;e, vous pouvez donc utiliser votre mot de passe habituel.")."</b>";
		} else
			$continue = true;

		// envoyer identifiants par mail
		if ($continue) {
			include_ecrire("inc_acces.php3");
			$pass = creer_pass_aleatoire(8,$mail_inscription);
			$login = test_login($mail_inscription);
			$mdpass = md5($pass);
			$htpass = generer_htpass($pass);
			$query = "INSERT INTO spip_auteurs (nom, email, login, pass, statut, htpass) ".
				"VALUES ('".addslashes($nom_inscription)."', '".addslashes($mail_inscription)."', '$login', '$mdpass', '$statut', '$htpass')";
			$result = spip_query($query);
			ecrire_acces();

			$nom_site_spip = lire_meta("nom_site");
			$adresse_site = lire_meta("adresse_site");

			$message = _L("(ceci est un message automatique)")."\n\n"._L("Bonjour,")."\n\n";
			if ($type == 'forum') {
				$message .= _L("Voici vos identifiants pour pouvoir participer aux forums
du site \"$nom_site_spip\" ($adresse_site/) :");
			}
			else {
				$message .= _L("Voici vos identifiants pour proposer des articles sur
le site \"$nom_site_spip\" ($adresse_site/ecrire/) :") . "\n\n";
			}
			$message .= "- "._L("login :")." $login\n";
			$message .= "- "._L("mot de passe :")." $pass\n\n";

			if (envoyer_mail($mail_inscription, "[$nom_site_spip] "._L("Identifiants personnels"), $message)) {
				echo _L("Votre nouvel identifiant vient de vous &ecirc;tre envoy&eacute; par email.");
			}
			else {
				echo _L("Probl&egrave;me de mail&nbsp;: l'identifiant ne peut pas &ecirc;tre envoy&eacute;.");
			}
		}
		echo "</div>";
	}
	else {
		echo _L("Indiquez ici votre nom et votre adresse email. Votre identifiant personnel vous parviendra rapidement, par courrier &eacute;lectronique.");
		$link = $GLOBALS['clean_link'];
		echo $link->getForm('GET');
		echo  "<P><B>"._L("Votre nom ou pseudo")."</B><BR>";
		echo  "<INPUT TYPE=\"text\" CLASS=\"forml\" NAME=\"nom_inscription\" VALUE=\"\" SIZE=\"30\">";
		echo  "<P><B>"._L("Votre adresse email")."</B><BR>";
		echo  "<INPUT TYPE=\"text\" CLASS=\"forml\" NAME=\"mail_inscription\" VALUE=\"\" SIZE=\"30\">";
		echo  "<DIV ALIGN=\"right\"><INPUT TYPE=\"submit\" NAME=\"Valider\" CLASS=\"spip_bouton\" VALUE=\""._L("Valider")."\">";
		echo  "</DIV></FORM>";
	}
}


function formulaire_site($la_rubrique) {
	$request_uri = $GLOBALS["REQUEST_URI"];
	global $nom_site;
	global $url_site;
	global $description_site;

	if ($nom_site) {
		// Tester le nom du site
		if (strlen ($nom_site) < 2){
			$reponse_signature .= erreur(_L("Veuillez indiquer le nom du site."));
			$refus = "oui";
		}

		// Tester l'URL du site
		include_ecrire("inc_sites.php3");
		if (!recuperer_page($url_site)) {
			$reponse_signature .= erreur(_L("L'URL que vous avez indiqu&eacute;e n'est pas valide."));
			$refus = "oui";
		}

		// Integrer a la base de donnees
		echo "<div class='reponse_formulaire'>";
		
		if ($refus !="oui"){
			$nom_site = addslashes($nom_site);
			$url_site = addslashes($url_site);
			$description_site = addslashes($description_site);
			
			$query = "INSERT INTO spip_syndic (nom_site, url_site, id_rubrique, descriptif, date, date_syndic, statut, syndication) ".
				"VALUES ('$nom_site', '$url_site', $la_rubrique, '$description_site', NOW(), NOW(), 'prop', 'non')";
			$result = spip_query($query);
			echo _L("Votre proposition est enregistr&eacute;e, elle appara&icirc;tra en ligne apr&egrave;s validation par les responsables de ce site.");
		}
		else {
			echo $reponse_signature;
			echo "<p> "._L("Votre proposition n'a pas &eacute;t&eacute; enregistr&eacute;e.");
		}
		
		echo "</div>";
	}
	else {
		$link = $GLOBALS['clean_link'];
		echo $link->getForm('POST');
		echo  "<P><div class='spip_encadrer'><B>"._L("Nom du site")."</B><BR>";
		echo  "<INPUT TYPE=\"text\" CLASS=\"forml\" NAME=\"nom_site\" VALUE=\"\" SIZE=\"30\">";
		echo  "<P><B>"._L("Adresse (URL) du site")."</B><BR>";
		echo  "<INPUT TYPE=\"text\" CLASS=\"forml\" NAME=\"url_site\" VALUE=\"\" SIZE=\"30\"></div>";
		echo  "<P><B>"._L("Description/commentaire")."</B><BR>";
		echo "<TEXTAREA NAME='description_site' ROWS='5' CLASS='forml' COLS='40' wrap=soft></textarea>";
		echo  "<DIV ALIGN=\"right\"><INPUT TYPE=\"submit\" NAME=\"Valider\" CLASS=\"spip_bouton\" VALUE=\""._L("Valider")."\">";
		echo  "</DIV></FORM>";
		}
}

function formulaire_ecrire_auteur($id_auteur, $email_auteur) {
	global $flag_wordwrap;

	include_ecrire("inc_texte.php3");
	include_ecrire("inc_filtres.php3");
	include_ecrire("inc_mail.php3");

	$affiche_formulaire = true;
	if ($GLOBALS['texte_message_auteur'.$id_auteur]) {
		if ($GLOBALS['sujet_message_auteur'.$id_auteur] == "")
			$erreur .= erreur(_L("Veuillez indiquer un sujet"));
		else if (! email_valide($GLOBALS['email_message_auteur'.$id_auteur]) )
			$erreur .= erreur(_L("Veuillez indiquer une adresse email valide"));
		else if ($GLOBALS['valide_message_auteur'.$id_auteur]) {  // verifier hash ?
			$GLOBALS['texte_message_auteur'.$id_auteur] .= "\n\n-- Envoi via le site  ".lire_meta('nom_site')." (".lire_meta('adresse_site')."/) --\n";
			envoyer_mail($email_auteur,
				$GLOBALS['sujet_message_auteur'.$id_auteur],
				$GLOBALS['texte_message_auteur'.$id_auteur], $GLOBALS['email_message_auteur'.$id_auteur],
				"X-Originating-IP: ".$GLOBALS['REMOTE_ADDR']);
			$erreur .= erreur(_L("Message envoy&eacute;"));
			$affiche_formulaire = false;
		} else { //preview
			echo "<p><div class='spip_encadrer'>Sujet : <b>".$GLOBALS['sujet_message_auteur'.$id_auteur]."</b></div>";
			if ($flag_wordwrap)
				$GLOBALS['texte_message_auteur'.$id_auteur] = wordwrap($GLOBALS['texte_message_auteur'.$id_auteur]);
			echo "<pre>".entites_html($GLOBALS['texte_message_auteur'.$id_auteur])."</pre>";
			$affiche_formulaire = false;
			$link = $GLOBALS['clean_link'];
			$link->addVar('email_message_auteur'.$id_auteur, $GLOBALS['email_message_auteur'.$id_auteur]);
			$link->addVar('sujet_message_auteur'.$id_auteur, $GLOBALS['sujet_message_auteur'.$id_auteur]);
			$link->addVar('texte_message_auteur'.$id_auteur, $GLOBALS['texte_message_auteur'.$id_auteur]);
			$link->addVar('valide_message_auteur'.$id_auteur, 'oui');
			echo $link->getForm('POST');
			echo "<DIV ALIGN=\"right\"><INPUT TYPE=\"submit\" NAME=\"Confirmer\" CLASS=\"spip_bouton\" VALUE=\""._L("Confirmer l'envoi")."\">";
			echo "</DIV></FORM>";
		}
	}

	if ($erreur)
		echo "<P><div class='spip_encadrer'><B>$erreur<BR>&nbsp;</B></div></P>\n";

	if ($affiche_formulaire) {
		$retour = $GLOBALS['REQUEST_URI'];
		$link = $GLOBALS['clean_link'];
		echo $link->getForm('POST');
		echo "<div class='spip_encadrer'><P><B>"._L("Votre adresse email")."</B><BR>";
		echo  "<INPUT TYPE=\"text\" CLASS=\"forml\" NAME=\"email_message_auteur$id_auteur\" VALUE=\"".entites_html($GLOBALS['email_message_auteur'.$id_auteur])."\" SIZE=\"30\">\n";
		echo  "<P><B>"._L("Sujet")."</B><BR>";
		echo  "<INPUT TYPE=\"text\" CLASS=\"forml\" NAME=\"sujet_message_auteur$id_auteur\" VALUE=\"".entites_html($GLOBALS['sujet_message_auteur'.$id_auteur])."\" SIZE=\"30\">\n";
		echo  "<P><TEXTAREA NAME='texte_message_auteur$id_auteur' ROWS='10' CLASS='forml' COLS='40' wrap=soft>".entites_html($GLOBALS['texte_message_auteur'.$id_auteur])."</textarea></div>\n";
		echo  "<DIV ALIGN=\"right\"><INPUT TYPE=\"submit\" NAME=\"Valider\" CLASS=\"spip_bouton\" VALUE=\""._L("Envoyer un message")."\">";
		echo  "</DIV></FORM>";
	}
}

?>
