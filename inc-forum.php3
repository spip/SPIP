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

// dupliquee dans ecrire/articles.php3 ; mais je ne sais pas ou l'installer (Fil)...
function get_forums_publics($id_article=0) {
	$forums_publics = lire_meta("forums_publics");
	if ($id_article) {
		$query = "SELECT accepter_forum FROM spip_articles WHERE id_article=$id_article";
		$res = spip_query($query);
		if ($obj = mysql_fetch_object($res))
			$forums_publics = $obj->accepter_forum;
	} else {
		$forums_publics = substr(lire_meta("forums_publics"),0,3);
	}
	return $forums_publics;
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


function afficher_petits_logos_mots($id_mot) {
	$racine = "IMG/moton$id_mot";
	if (file_exists("$racine.gif")) {
		$image = "$racine.gif";
	} elseif (file_exists("$racine.jpg")) {
		$image = "$racine.jpg";
	} elseif (file_exists("$racine.png")) {
		$image = "$racine.png";
	}
	
	if ($image) {
		$taille = getimagesize($image);
		$largeur = $taille[0];
		$hauteur = $taille[1];
		if ($largeur < 50 AND $hauteur < 30)
			return "<IMG SRC='$image' align='middle' WIDTH='$largeur' HEIGHT='$hauteur' HSPACE='1' VSPACE='1' ALT=' ' BORDER=0 class='spip_image'> ";
		else return "";
	} else {
		return "";
	}
}


function decoder_hash_forum($email, $hash) {
	if (!$email OR !$hash) return false;
	$query = "SELECT * FROM spip_auteurs WHERE email='$email'";
	$result = spip_query($query);
	while ($row = mysql_fetch_array($result)) {
		if (verifier_action_auteur("forum public $email", $hash, $row['id_auteur'])) {
			$ok = true;
			break;
		}
	}
	if ($ok) return $row;
	else return false;
}


function forum_abonnement($retour) {
	if ($GLOBALS['auteur_session'])
		return true;	// autoriser le formulaire
	else {
		include_local("inc-login.php3");
		$cible = new Link($retour);

		$message_login = propre("Pour participer &agrave;
		ce forum, vous devez vous enregistrer au pr&eacute;alable. Merci
		d'indiquer ci-dessous l'identifiant personnel qui vous a
		&eacute;t&eacute; fourni.\n_ Si vous l'avez oubli&eacute;, ou si
		vous n'en avez pas encore, vous pouvez").
' <script language="JavaScript"><!--
document.write("<a href=\\"javascript:window.open(\\\'spip_pass.php3\\\', \\\'spip_pass\\\', \\\'scrollbars=yes,resizable=yes,width=740,height=580\\\'); void(0);\\"");
//--></script><noscript><a href=\'spip_pass.php3\' target=\'_blank\'></noscript>demander de nouveaux identifiants</a>.';
		login($cible, false, $message_login);
	} 
}


function retour_forum($id_rubrique, $id_parent, $id_article, $id_breve, $id_syndic, $titre='') {
	global $REQUEST_URI, $HTTP_GET_VARS, $PATH_TRANSLATED, $REMOTE_ADDR, $id_message ;
	$new = $GLOBALS["new"];
	$redac = $GLOBALS["redac"];
	$afficher_groupe = $GLOBALS["afficher_groupe"];
	$afficher_texte = $GLOBALS["afficher_texte"];

	$forums_publics = get_forums_publics($id_article);
	if ($forums_publics == "non") return;

	$lien = substr($REQUEST_URI, strrpos($REQUEST_URI, '/') + 1);

	$retour = $HTTP_GET_VARS['retour'];
	if (!$retour)
		$retour = rawurlencode($lien);

	if ($forums_publics == "abo")  // forums abo
		$ret .= '<?php include("inc-forum.php3"); if (forum_abonnement($retour)) { ?'.'>';
	else
		$ret .= '<?php { ?'.'>';

	$ret .= "\n<a name='formulaire_forum'></a>\n";
	$ret .= "\n<FORM ACTION='$lien' METHOD='post'>";
	
	if ($forums_publics == "pri") {
		$ret.= "Ce forum est mod&eacute;r&eacute; &agrave; priori&nbsp;: votre contribution n'appara&icirc;tra qu'apr&egrave;s avoir &eacute;t&eacute; valid&eacute;e par un administrateur du site.<P>";
	}
	
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
			$titre_select = "SELECT nom_site AS titre FROM spip_syndic WHERE id_syndic = $id_syndic";
		else
			$titre_select = "SELECT 'Erreur...' AS titre";	

		$res = mysql_fetch_object(spip_query($titre_select));
		$titre = '> ' . ereg_replace ('^[>[:space:]]*', '', $res->titre);
	}
	


	if ($id_message){		
		$query_forum="SELECT * FROM spip_forum WHERE ip=\"$REMOTE_ADDR\" AND id_forum=$id_message";
		$result_forum=spip_query($query_forum);
		
		while($row = mysql_fetch_array($result_forum)) {
			$titre=$row['titre'];
			$texte=$row['texte'];
			$auteur=$row['auteur'];
			$email_auteur=$row['email_auteur'];
			$nom_site_forum=$row['nom_site'];
			$url_site=$row['url_site'];
		}
	
		if (!$nouveau_document AND $afficher_texte != 'non'){
			$ret .= "<div class='spip_encadrer'>";
			if ($afficher_texte != "non"){
				$ret .= "<font size=4 color='#aaaaaa'><b>".propre($titre)."</b></font>";
				$ret .= "<p><b><a href='mailto:$email_auteur'>".propre($auteur)."</a></b>";
				$ret .= "<p>".propre($texte)."<p>";
			}
			
			$ret .= "<a href='$url_site'>".propre($nom_site_forum)."</a>";
	
	
			// Verifier mots associes au message	
			$query_mots = "SELECT mots.* FROM spip_mots_forum AS lien, spip_mots AS mots WHERE id_forum='$id_message' AND mots.id_mot = lien.id_mot GROUP BY mots.id_mot";
			$result_mots = spip_query($query_mots);
			if (mysql_num_rows($result_mots)>0) $ret .= "<p>Vous avez s&eacute;lectionn&eacute;&nbsp;:";
			while ($row = mysql_fetch_array($result_mots)) {
				$id_mot = $row['id_mot'];
				$type_mot = $row['type'];
				$titre_mot = $row['titre'];
				$les_mots[$id_mot] = true;
				$presence_mots = true;
				
				$ret.= "<li> $type_mot&nbsp;: <b>$titre_mot</b>";
				
			}
		
			if (strlen($texte) < 10 AND !$presence_mots)
				$ret .= "<p><div align='right'><font color=red><b>Attention&nbsp;!</b> votre message fait moins de dix caract&egrave;res.</font></div>\n";
			else if (strlen($titre) < 3 AND $afficher_texte <> "non")
				$ret .= "<p><div align='right'><font color=red><b>Attention&nbsp;!</b> votre titre fait moins de trois caract&egrave;res.</font></div>\n";
			else
				$ret .= "\n<p><DIV ALIGN='right'><INPUT TYPE='submit' NAME='confirmer' CLASS='spip_bouton' VALUE='Message d&eacute;finitif : envoyer au site'></DIV>";
	
			$ret .= "</div>\n<p>";
		}
	}
	
	$ret .= "\n";
	
	

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

	$titre = entites_html($titre);
	$texte = entites_html($texte);
	
	if ($afficher_texte == "non"){
		$ret .= "\n<INPUT TYPE='hidden' NAME='titre' VALUE=\"$titre\">";
	}
	else {
		$ret .= "\n<div class='spip_encadrer'><B>Titre :</B><BR>";
		$ret .= "\n<INPUT TYPE='text' CLASS='forml' NAME='titre' VALUE=\"$titre\" SIZE='40'></div>";
	}
	
	$ret .= "\n<INPUT TYPE='Hidden' NAME='id_message' VALUE=\"$id_message\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='ajout_forum' VALUE=\"oui\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='forum_id_rubrique' VALUE=\"$id_rubrique\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='forum_id_parent' VALUE=\"$id_parent\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='forum_id_article' VALUE=\"$id_article\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='forum_id_breve' VALUE=\"$id_breve\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='forum_id_syndic' VALUE=\"$id_syndic\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='alea' VALUE=\"$alea\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='hash' VALUE=\"$hash\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='retour_forum' VALUE=\"$retour\">";
	
	if ($new != "oui" AND $redac != "oui") $ret .= "\n<INPUT TYPE='Hidden' NAME='new' VALUE=\"oui\">";
	if ($new == "oui") $ret .= "\n<INPUT TYPE='Hidden' NAME='redac' VALUE=\"oui\">";
	

	
	if ($afficher_texte !="non"){
		$ret .= "\n<p><div class='spip_encadrer'><B>Texte de votre message :</B><BR>";
		$ret .= "\n(Pour cr&eacute;er des paragraphes, laissez simplement des lignes vides.)<BR>";
		$ret .= "\n<TEXTAREA NAME='texte' ROWS='12' CLASS='forml' COLS='40' wrap=soft>";
		$ret.= $texte;
		$ret .= "\n</TEXTAREA></div>\n";
	}



	/// Gestion des mots-cles
	
	$mots_cles_forums=lire_meta("mots_cles_forums");
	if ($mots_cles_forums == "oui"){
		if ($id_rubrique > 0) $table = "rubriques";
		else if ($id_article > 0) $table = "articles";
		else if ($id_breve > 0) $table = "breves";
		else if ($id_syndic > 0) $table = "syndic";
		

		if ($afficher_groupe) {
			$afficher_groupe = join($afficher_groupe, ",");
			$selectionner_groupe = "AND id_groupe IN ('$afficher_groupe')";
		}
		if ($table){
			$query_groupe = "SELECT * FROM spip_groupes_mots WHERE 6forum = 'oui' AND $table = 'oui' $selectionner_groupe";
			$result_groupe = spip_query($query_groupe);
			while ($row_groupe = mysql_fetch_array($result_groupe)) {
				$id_groupe = $row_groupe['id_groupe'];
				$titre_groupe = $row_groupe['titre'];
				$unseul_groupe = $row_groupe['unseul'];
				
				$query = "SELECT * FROM spip_mots WHERE id_groupe='$id_groupe'";
				$result = spip_query($query);
				$total_rows = mysql_num_rows($result);
				
				if ($total_rows > 0){
					$ret .= "\n<p><div class='spip_encadrer'>";
					$ret.= "<b>$titre_groupe&nbsp;:</b>";
					
					$ret .= "<table cellpadding=0 cellspacing=0 border=0 width='100%'>\n";	
					$ret .= "<tr><td width='47%' valign='top'><font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
					$i = 0;
					
					while ($row = mysql_fetch_array($result)) {
						$id_mot = $row['id_mot'];
						$titre_mot = propre($row['titre']);
						$type_mot = propre($row['type']);
						$descriptif_mot = $row['descriptif'];
					
						if ($i >= ($total_rows/2) AND $i < $total_rows){
							$i = $total_rows + 1;
							$ret .= "</font></td><td width='6%'>&nbsp;</td><td width='47%' valign='top'><font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
						}
						
						if ($les_mots[$id_mot]) $checked = "checked";
						else $checked = "";
						
						if ($unseul_groupe == 'oui'){
							$ret .= "<input type='radio' name='ajouter_mot[$id_groupe][]' value='$id_mot' $checked id='mot$id_mot'> ";
						}
						else {
							$ret .= "<input type='checkbox' name='ajouter_mot[$id_groupe][]' value='$id_mot' $checked id='mot$id_mot'> ";
						}
					
						$ret .=  afficher_petits_logos_mots($id_mot);
						$ret .= "<B><label for='mot$id_mot'>$titre_mot</label></B><br>";
						if (strlen($descriptif_mot) > 0) $ret .= "$descriptif_mot<br>";
						$i++;
					}
					
					$ret .= "</font></td></tr></table>";
				
					$ret .= "</div>";
				}
			}
		}
	}
	///////


	if ($afficher_texte != "non"){
		$ret .= "\n<p><div class='spip_encadrer'><B>Lien hypertexte</B> (optionnel)<BR>";
		$ret .= "\n(Si votre message se r&eacute;f&egrave;re &agrave; un article publi&eacute; sur le Web, ou &agrave; une page fournissant plus d'informations, veuillez indiquer ci-apr&egrave;s le titre de la page et son adresse URL.)<BR>";
		$ret .= "\nTitre :<BR>";
		$ret .= "\n<INPUT TYPE='text' CLASS='forml' NAME='nom_site_forum' VALUE=\"".entites_html($nom_site_forum)."\" SIZE='40'><BR>";

		if (!$url_site) $url_site = "http://";
		$ret .= "\nURL :<BR>";
		$ret .= "\n<INPUT TYPE='text' CLASS='forml' NAME='url_site' VALUE=\"$url_site\" SIZE='40'></div>";

		$ret .= "\n<p><div class='spip_encadrer'><B>Qui &ecirc;tes-vous ?</B> (optionnel)<BR>";

		$nom_session = $GLOBALS['auteur_session']['nom'];
		$nom_email = $GLOBALS['auteur_session']['email'];

		if (!$auteur) $auteur = $nom_session;
		if (!$email_auteur) $email_auteur = $nom_email;

		$ret .= "\nVotre nom (ou pseudonyme) :<BR>";
		$ret .= "\n<INPUT TYPE='text' CLASS='forml' NAME='auteur' VALUE=\"".entites_html($auteur)."\" SIZE='40'><BR>";

		$ret .= "\nVotre adresse email :<BR>";
		$ret .= "\n<INPUT TYPE='text' CLASS='forml' NAME='email_auteur' VALUE=\"$email_auteur\" SIZE='40'></div>";
	}

	if ($afficher_texte !="non") $ret .= "\n<p><DIV ALIGN='right'><INPUT TYPE='submit' NAME='Valider' CLASS='spip_bouton' VALUE='Voir ce message avant de le poster'></DIV>";
	else  $ret .= "\n<p><DIV ALIGN='right'><INPUT TYPE='submit' NAME='Valider' CLASS='spip_bouton' VALUE='Valider ce choix'></DIV>";
	
	$ret .= "</FORM>";

	$ret .= '<?php } ?'.'>';	// fin forums abo
	
	return $ret;
}



function ajout_forum() {
	global $texte, $titre, $nom_site_forum, $url_site, $auteur, $email_auteur, $retour_forum, $id_message, $confirmer;
	global $forum_id_rubrique, $forum_id_parent, $forum_id_article, $forum_id_breve, $forum_id_auteur, $forum_id_syndic, $alea, $hash;
	global $hash_email, $email_forum_abo, $pass_forum_abo, $ajouter_mot, $new;
	global $HTTP_HOST, $REQUEST_URI, $HTTP_COOKIE_VARS, $REMOTE_ADDR;
	$afficher_texte = $GLOBALS['afficher_texte'];
	
	if (!$GLOBALS['db_ok']) {
		die ("<h4>Probl&egrave;me de base de donn&eacute;es, votre message n'a pas &eacute;t&eacute; enregistr&eacute;.</h4>");
	}

	$texte = addslashes($texte);
	$titre = addslashes($titre);
	$nom_site_forum = addslashes($nom_site_forum);
	$auteur = addslashes($auteur);
	$retour_forum = rawurldecode($retour_forum);
	$forums_publics = get_forums_publics($forum_id_article);

	if (strlen($confirmer) > 0 AND !verifier_action_auteur("ajout_forum $forum_id_rubrique $forum_id_parent $forum_id_article $forum_id_breve $forum_id_syndic $alea", $hash)) {
		@header("Location: $retour_forum");
		exit;
	}
	if (strlen($confirmer) > 0 AND ((strlen($texte) + strlen($titre) + strlen($nom_site_forum) + strlen($url_site) + strlen($auteur) + strlen($email_auteur)) > 20 * 1024)) {
		die ("<h4>Votre message est trop long. La taille maximale est de 20000 caract&egrave;res.</h4>
		Cliquez <a href='$retour_forum'>ici</a> pour continuer.<p>");
	}

	unset($where);
	if ($forum_id_article) $where[] = "id_article=$forum_id_article";
	if ($forum_id_rubrique) $where[] = "id_rubrique=$forum_id_rubrique";
	if ($forum_id_breve) $where[] = "id_breve=$forum_id_breve";
	if ($forum_id_parent) $where[] = "id_forum=$forum_id_parent";
	if ($where) {
		$query = "SELECT fichier FROM spip_forum_cache WHERE ".join(' OR ', $where);
		$result = spip_query($query);
		unset($fichiers);
		while ($row = mysql_fetch_array($result)) {
			$fichier = $row["fichier"];
			@unlink("CACHE/$fichier");
			$fichiers[] = $fichier;
		}
		if ($fichiers) {
			$fichiers = join(',', $fichiers);
			$query = "DELETE FROM spip_forum_cache WHERE fichier IN ($fichiers)";
			spip_query($query);
		}
	}


	switch($forums_publics) {
		case "non":
			$etat = "off";
			break;
		case "pri":
			$etat = "prop";
			break;
		default:
			$etat = "publie";
			break;
	}
	
	
	
	if (!$id_auteur) $id_auteur = $GLOBALS['auteur_session']['id_auteur'];
	$auteur_session = $GLOBALS['auteur_session']['email'];
	
	if ($new == "oui"){
		$nouveau_document = true;
		if ($HTTP_GET_VARS['titre']){
			$titre = "> ".rawurldecode($HTTP_GET_VARS['titre']);
		}
		$query_forum = "INSERT INTO spip_forum (date_heure, titre, ip, statut)
			VALUES (NOW(), \"".addslashes($titre)."\", \"$REMOTE_ADDR\", \"redac\")";
		$result_forum = spip_query($query_forum);
		$id_message = mysql_insert_id();
	}

	// Ajouter les mots-cles
	$query_mots = "DELETE FROM spip_mots_forum WHERE id_forum='$id_message'";
	$result_mots = spip_query($query_mots);

	if ($ajouter_mot){
		for (reset($ajouter_mot); $key = key($ajouter_mot); next($ajouter_mot)){
			$les_mots .= ",".join($ajouter_mot[$key],",");
		}

		$les_mots = explode(",", $les_mots);
		for ($index = 0; $index < count($les_mots); $index++){
			$le_mot = $les_mots[$index];
			if ($le_mot > 0) 
				spip_query("INSERT INTO spip_mots_forum (id_mot, id_forum) VALUES ('$le_mot', '$id_message')");
		}

	}



	$query_forum = "UPDATE spip_forum
		SET id_parent = $forum_id_parent, id_rubrique =$forum_id_rubrique, id_article = $forum_id_article, id_breve = $forum_id_breve, id_syndic = \"$forum_id_syndic\", 
			date_heure = NOW(), titre = \"$titre\", texte = \"$texte\", nom_site = \"$nom_site_forum\", url_site = \"$url_site\", auteur = \"$auteur\",
			email_auteur = \"$email_auteur\",  ip = \"$REMOTE_ADDR\", statut = \"redac\", id_auteur = \"$id_auteur\" 
		WHERE id_forum = '$id_message'";

	$result_forum = spip_query($query_forum);


	if ($forums_publics == 'abo') {
	
		$cookie_email = $HTTP_COOKIE_VARS['spip_forum_email'];
		if ($hash_email && $forum_id_auteur) {
			if (verifier_action_auteur("email $cookie_email", $hash_email, $forum_id_auteur)) {
				$query = "SELECT * FROM spip_auteurs WHERE id_auteur=$forum_id_auteur AND email='$cookie_email'";
				$result = spip_query($query);
				if ($row = mysql_fetch_array($result)) $ok = true;
			}
		}
		if (!$ok) {
			if ($email_forum_abo && $pass_forum_abo) {
				$query = "SELECT * FROM spip_auteurs WHERE email='$email_forum_abo'";
				$result = spip_query($query);
				$mdpass = md5($pass_forum_abo);
				while ($row = mysql_fetch_array($result)) {
					if ($mdpass == $row['pass'] OR "$auteur_session" == "$email_forum_abo") {
						$ok = true;
						poser_cookie_forum($email_forum_abo, $row["id_auteur"]);
						
						$fich = substr($REQUEST_URI, strrpos($REQUEST_URI, '/') + 1)."&id_message=$id_message";
						
						@header("Location: $fich");
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


	
	if (strlen($confirmer) > 0 OR ($afficher_texte=='non' AND $ajouter_mot)) {
		spip_query("UPDATE spip_forum SET statut=\"$etat\" WHERE id_forum='$id_message'");
	

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
				$result = spip_query($query);

				while ($row = mysql_fetch_array($result)) {
					$email_auteur = trim($row["email"]);
					if (strlen($email_auteur) < 3) continue;
					envoyer_mail($email_auteur, $sujet, $courr);
				}
			}
		}

		@header("Location: $retour_forum");
		exit;
	}
}

?>
