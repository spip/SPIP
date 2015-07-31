<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_FORUM")) return;
define("_INC_FORUM", "1");


include_ecrire("inc_connect.php3");
include_ecrire("inc_meta.php3");
include_ecrire("inc_admin.php3");
include_ecrire("inc_acces.php3");
include_ecrire("inc_texte.php3");
include_ecrire("inc_filtres.php3");
include_ecrire("inc_mail.php3");
if (file_exists("inc-urls.php3")) {
	include_local ("inc-urls.php3");
}
else {
	include_local ("inc-urls-dist.php3");
}


function generer_pass_forum($email = '') {
	$passw = creer_pass_aleatoire(9, $email);
	$passw = ereg_replace("[./]", "a", $passw);
	$passw = ereg_replace("[I1l]", "L", $passw);
	$passw = ereg_replace("[0O]", "o", $passw);
	return $passw;
}

function generer_hash_forum($email, $id_auteur) {
	return calculer_action_auteur("forum public $email", $id_auteur);
}

function poser_cookie_forum($email, $id_auteur, $duree = 30) {
	$hash = generer_hash_forum($email, $id_auteur);
	setcookie("spip_forum_email", $email, time() + (3600 * 24 * $duree));
	setcookie("spip_forum_hash", $hash, time() + (3600 * 24 * $duree));
}

function enlever_cookie_forum() {
	setcookie("spip_forum_email", "", time() - 3600 * 24);
	setcookie("spip_forum_hash", "", time() - 3600 * 24);
}

function decoder_hash_forum($email, $hash) {
	if (!$email OR !$hash) return false;
	include_ecrire("inc_connect.php3");
	$query = "SELECT * FROM spip_auteurs WHERE email='$email'";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		if (verifier_action_auteur("forum public $email", $hash, $row[0])) {
			$ok = true;
			break;
		}
	}
	if ($ok) return $row;
	else return false;
}


function forum_abonnement() {
	global $HTTP_COOKIE_VARS;
	$email = $HTTP_COOKIE_VARS['spip_forum_email'];
	$hash = $HTTP_COOKIE_VARS['spip_forum_hash'];
	$row = decoder_hash_forum($email, $hash);

	echo "<div class='spip_encadrer'>";
	if (!$row) {
		echo "";
		echo "\nVotre e-mail d'inscription :<BR><INPUT TYPE='text' CLASS='forml' NAME='email_forum_abo' VALUE='$email' SIZE='14'>";
		echo "\n<BR>Votre mot de passe :<BR><INPUT TYPE='password' CLASS='forml' NAME='pass_forum_abo' VALUE='' SIZE='14'>";
		echo "\n<BR><FONT SIZE=2>Pour participer &agrave; ce forum, vous devez indiquer l'identifiant personnel qui vous a &eacute;t&eacute; fourni. Si vous l'avez oubli&eacute;, ou si vous n'en n'avez pas encore, cliquez ci-dessous pour vous inscrire.</FONT>";
		echo "\n<BR>[<A HREF=\"#formulaire_forum\" onMouseDown=\"window.open('spip_pass.php3','myWindow','scrollbars=yes,resizable=yes,width=400,height=200')\">Recevoir votre identifiant</A>]<P>";
		echo "";
	}
	else {
		$id_auteur = $row['id_auteur'];
		$hash_email = calculer_action_auteur("email $email", $id_auteur);
		echo "\n<BR><FONT SIZE=2>Vous &ecirc;tes identifi&eacute; sous l'adresse e-mail $email.</FONT><P>";
		echo "\n<INPUT TYPE='hidden' NAME='forum_id_auteur' VALUE='$id_auteur'>";
		echo "\n<INPUT TYPE='hidden' NAME='hash_email' VALUE='$hash_email'>";
	}
	echo "</div><p>";
}


function retour_forum($id_rubrique, $id_parent, $id_article, $id_breve, $id_syndic) {
	global $REQUEST_URI, $HTTP_GET_VARS, $PATH_TRANSLATED;
	$forums_publics = lire_meta("forums_publics");

	if ($forums_publics == "non") return;

	$lien = substr($REQUEST_URI, strrpos($REQUEST_URI, '/') + 1);

	$retour = $HTTP_GET_VARS['retour'];
	if ($retour)
		$retour = rawurlencode($retour);
	else 
		$retour = rawurlencode($lien);

	$fich = $PATH_TRANSLATED;
	if ($p = strrpos($PATH_TRANSLATED, '/')) $fich = substr($fich, $p + 1);
	if ($p = strpos($fich, '?')) $fich = substr($fich, 0, $p);

	$ret .= "\n<A NAME='formulaire_forum'>";
	$ret .= "\n<FORM ACTION='$fich' METHOD='post'>";
	$ret .= "\n<B>VOTRE MESSAGE...</B><p>";
	
	if ($forums_publics == "priori") {
		$ret.= "Ce forum est mod&eacute;r&eacute; &agrave; priori&nbsp;: votre contribution n'appara&icirc;tra qu'apr&egrave;s avoir &eacute;t&eacute; valid&eacute;e par un administrateur du site.<P>";
	}
	
	if ($forums_publics == "abonnement") {
		$ret.= '<?php include("inc-forum.php3"); forum_abonnement(); ?'.'>';
	}
	
	$ret .= "\n";

	// recuperer le titre
	if (! $titre) {
		if ($id_parent)
			$titre_select = "SELECT titre FROM spip_forum WHERE id_forum = $id_parent";
		else if ($id_rubrique)
			$titre_select = "SELECT titre FROM spip_rubriques WHERE id_rubrique = $id_rubrique";
		else if ($id_article)
			$titre_select = "SELECT titre FROM spip_articles WHERE id_article = $id_article";
		else if ($id_breve)
			$titre_select = "SELECT titre FROM spip_breves WHERE id_breve = $id_breve";
		else if ($id_syndic)
			$titre_select = "SELECT nom_site FROM spip_syndic WHERE id_syndic = $id_syndic";
	
		$res = mysql_fetch_object(mysql_query($titre_select));
		$titre = '> ' . ereg_replace ('^[>[:space:]]*', '', $res->titre);
	}

	$seed = (double) (microtime() + 1) * time() * 1000000;
	@mt_srand($seed);
	$alea = @mt_rand();
	if (!$alea) {
		srand($seed);
		$alea = rand();
	}
	$id_rubrique = (int) $id_rubrique;
	$id_parent = (int) $id_parent;
	$id_article = (int) $id_article;
	$id_breve = (int) $id_breve;
	$id_syndic = (int) $id_syndic;
	$hash = calculer_action_auteur("ajout_forum $id_rubrique $id_parent $id_article $id_breve $id_syndic $alea");

	$ret .= "\n<div class='spip_encadrer'><B>Titre :</B><BR>";

	$titre = htmlspecialchars($titre);

	$ret .= "\n<INPUT TYPE='text' CLASS='forml' NAME='titre' VALUE=\"$titre\" SIZE='40'></div>";

	$ret .= "\n<INPUT TYPE='Hidden' NAME='ajout_forum' VALUE=\"oui\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='forum_id_rubrique' VALUE=\"$id_rubrique\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='forum_id_parent' VALUE=\"$id_parent\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='forum_id_article' VALUE=\"$id_article\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='forum_id_breve' VALUE=\"$id_breve\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='forum_id_syndic' VALUE=\"$id_syndic\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='alea' VALUE=\"$alea\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='hash' VALUE=\"$hash\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='retour_forum' VALUE=\"$retour\">";

	$ret .= "\n<p><div class='spip_encadrer'><B>Texte de votre message :</B><BR>";
	$ret .= "\n(Pour cr&eacute;er des paragraphes, laissez simplement des lignes vides.)<BR>";
	$ret .= "\n<TEXTAREA NAME='texte' ROWS='12' CLASS='forml' COLS='40' wrap=soft>";
//		$ret.= $texte;
	$ret .= "\n</TEXTAREA></div>\n";

	$ret .= "\n<p><div class='spip_encadrer'><B>Lien hypertexte</B> (optionnel)<BR>";
	$ret .= "\n(Si votre message se r&eacute;f&egrave;re &agrave; un article publi&eacute; sur le Web, ou &agrave; une page fournissant plus d'informations, veuillez indiquer ci-apr&egrave;s le titre de la page et son adresse URL.)<BR>";
	$ret .= "\nTitre :<BR>";
	$ret .= "\n<INPUT TYPE='text' CLASS='forml' NAME='nom_site_forum' VALUE=\"$nom_site_forum\" SIZE='40'><BR>";

	$lien_url = "http://";
	$ret .= "\nURL :<BR>";
	$ret .= "\n<INPUT TYPE='text' CLASS='forml' NAME='url_site' VALUE=\"$url_site\" SIZE='40'></div>";

	$ret .= "\n<p><div class='spip_encadrer'><B>Qui &ecirc;tes-vous ?</B> (optionnel)<BR>";
	$ret .= "\nVotre nom (ou pseudonyme) :<BR>";
	$ret .= "\n<INPUT TYPE='text' CLASS='forml' NAME='auteur' VALUE=\"$auteur\" SIZE='40'><BR>";

	$ret .= "\nVotre adresse email :<BR>";
	$ret .= "\n<INPUT TYPE='text' CLASS='forml' NAME='email_auteur' VALUE=\"$email_auteur\" SIZE='40'></div><P>";

	$ret .= "\n<DIV ALIGN='right'><INPUT TYPE='submit' NAME='Valider' CLASS='spip_bouton' VALUE='Envoyer ce message'></DIV>";
	$ret .= "</FORM>";

	return $ret;
}



function ajout_forum() {
	global $texte, $titre, $nom_site_forum, $url_site, $auteur, $email_auteur, $retour_forum;
	global $forum_id_rubrique, $forum_id_parent, $forum_id_article, $forum_id_breve, $forum_id_auteur, $forum_id_syndic, $alea, $hash;
	global $hash_email, $email_forum_abo, $pass_forum_abo;
	global $HTTP_HOST, $REQUEST_URI, $HTTP_COOKIE_VARS, $REMOTE_ADDR;

	if (!$GLOBALS['db_ok']) {
		die ("<h4>Probl&egrave;me de base de donn&eacute;es, votre message n'a pas &eacute;t&eacute; enregistr&eacute;.</h4>");
	}

	$texte = addslashes($texte);
	$titre = addslashes($titre);
	$nom_site_forum = addslashes($nom_site_forum);
	$auteur = addslashes($auteur);
	$retour_forum = rawurldecode($retour_forum);
	$forums_publics = lire_meta("forums_publics");

	if (!verifier_action_auteur("ajout_forum $forum_id_rubrique $forum_id_parent $forum_id_article $forum_id_breve $forum_id_syndic $alea", $hash)) {
		@header("Location: $retour_forum");
		exit;
	}
	if ((strlen($texte) + strlen($titre) + strlen($nom_site_forum) + strlen($url_site) + strlen($auteur) + strlen($email_auteur)) > 20 * 1024) {
		die ("<h4>Votre message est trop long. La taille maximale est de 20000 caract&egrave;res.</h4>
		Cliquez <a href='$retour_forum'>ici</a> pour continuer.<p>");
	}
	if (strlen($texte) < 10 OR strlen($titre) < 3) {
		die ("<h4>Le texte ou le titre de votre message est trop court. </h4>
		Cliquez <a href='$retour_forum'>ici</a> pour continuer.<p>");
	}

	if ($forums_publics == 'abonnement') {
		$cookie_email = $HTTP_COOKIE_VARS['spip_forum_email'];
		if ($hash_email && $forum_id_auteur) {
			if (verifier_action_auteur("email $cookie_email", $hash_email, $forum_id_auteur)) {
				$query = "SELECT * FROM spip_auteurs WHERE id_auteur=$forum_id_auteur AND email='$cookie_email'";
				$result = mysql_query($query);
				if ($row = mysql_fetch_array($result)) $ok = true;
			}
		}
		if (!$ok) {
			if ($email_forum_abo && $pass_forum_abo) {
				$query = "SELECT * FROM spip_auteurs WHERE email='$email_forum_abo'";
				$result = mysql_query($query);
				$mdpass = md5($pass_forum_abo);
				while ($row = mysql_fetch_array($result)) {
					if ($mdpass == $row['pass']) {
						$ok = true;
						poser_cookie_forum($email_forum_abo, $row[0]);
						break;
					}
				}
			}
			else {
				enlever_cookie_forum();
				die ("<h4>Vous devez indiquer votre adresse et votre mot de passe.</h4>
				Cliquez <a href='$retour_forum'>ici</a> pour continuer.<p>");
			}
		}

		if ($ok) {
			$id_auteur = $row[0];
			$statut = $row[8];
			
			if ($statut == '5poubelle') {
				die ("<h4>Vous n'avez plus acc&egrave;s &agrave; ces forums.</h4>Cliquez <a href='$retour_forum'>ici</a> pour continuer.<p>");
			}
		}
		else {
			die ("<h4>Vous n'&ecirc;tes pas inscrit, ou l'adresse ou le mot de passe sont erron&eacute;s.</h4>
			Cliquez <a href='$retour_forum'>ici</a> pour continuer.<p>");
		}
	}

	unset($where);
	if ($forum_id_article) $where[] = "id_article=$forum_id_article";
	if ($forum_id_rubrique) $where[] = "id_rubrique=$forum_id_rubrique";
	if ($forum_id_breve) $where[] = "id_breve=$forum_id_breve";
	if ($forum_id_parent) $where[] = "id_forum=$forum_id_parent";
	if ($where) {
		$query = "SELECT fichier FROM spip_forum_cache WHERE ".join(' OR ', $where);
		$result = mysql_query($query);
		unset($fichiers);
		while ($row = mysql_fetch_array($result)) {
			$fichier = $row[0];
			@unlink("CACHE/$fichier");
			$fichiers[] = $fichier;
		}
		if ($fichiers) {
			$fichiers = join(',', $fichiers);
			$query = "DELETE FROM spip_forum_cache WHERE fichier IN ($fichiers)";
			mysql_query($query);
		}
	}

	switch($forums_publics) {
		case "non":
			$etat = "off";
			break;
		case "priori":
			$etat = "prop";
			break;
		default:
			$etat = "publie";
			break;
	}

	$query_forum = "INSERT spip_forum (id_parent, id_rubrique, id_article, id_breve, date_heure, titre, texte, nom_site, url_site, auteur, email_auteur, ip, statut, id_auteur, id_syndic)
	VALUES ($forum_id_parent, $forum_id_rubrique, $forum_id_article, $forum_id_breve, NOW(), \"$titre\", \"$texte\", \"$nom_site_forum\", \"$url_site\", \"$auteur\", \"$email_auteur\", \"$REMOTE_ADDR\",\"$etat\",\"$id_auteur\", \"$forum_id_syndic\")";
	$result_forum = mysql_query($query_forum);

	$texte = stripslashes($texte);
	$titre = stripslashes($titre);
	$auteur = stripslashes($auteur);

	// Envoi d'un mail aux auteurs
	$prevenir_auteurs = lire_meta("prevenir_auteurs");
	if ($prevenir_auteurs == "oui") {
		if ($id_article = $forum_id_article) {
			$url = generer_url_article($id_article);
			$adresse_site = lire_meta("adresse_site");
			$nom_site_spip = lire_meta("nom_site");
			if ($url[0] == '/') {
				$url = "http://$HTTP_HOST$url";
			}
			else {
				$url = "http://$HTTP_HOST".substr($REQUEST_URI, 0, strrpos($REQUEST_URI, '/') + 1).$url;
			}
			$courr = "(ceci est un message automatique)\n\n";
			$courr .= "Message poste ";
			if (strlen($auteur) > 2) {
				$courr .= "par $auteur ";
				if ($email_auteur) $courr .= "<$email_auteur> ";
			}
			$courr .= "a la suite de votre article.\n";
			$courr .= "Ne repondez pas a ce mail mais sur le forum a l'adresse suivante :\n";
			$courr .= "$url\n";
			$courr .= "\n\n".$titre."\n\n".textebrut(propre($texte))."\n\n$nom_site_forum\n$url_site\n";
			$sujet = "[$nom_site_spip] [forum] $titre";
			$query = "SELECT spip_auteurs.* FROM spip_auteurs, spip_auteurs_articles AS lien WHERE lien.id_article='$id_article' AND spip_auteurs.id_auteur=lien.id_auteur";
			$result = mysql_query($query);

			while ($row = mysql_fetch_array($result)) {
				$email_auteur = trim($row[3]);
				if (strlen($email_auteur) < 3) continue;
				envoyer_mail($email_auteur, $sujet, $courr);
			}
		}
	}

	@header("Location: $retour_forum");
	exit;
}

?>
