<?php

include ("inc.php3");
include_ecrire ("inc_acces.php3");
include_ecrire ("inc_logos.php3");
include_ecrire ("inc_session.php3");
include_ecrire ("inc_filtres.php3");
include_ecrire ("inc_listes.php3");

// securite
$id_auteur = floor($id_auteur);

// menu statuts
function mySel($varaut,$variable) {
	$retour = " VALUE=\"$varaut\"";
	if ($variable==$varaut){
		$retour.= " SELECTED";
	}
	return $retour;
}

//
// Auteurs a acces restreint
//
function afficher_auteur_rubriques($leparent){
	global $id_parent;
	global $id_rubrique;
	global $toutes_rubriques;
	global $i;
	
	$i++;
 	$query="SELECT * FROM spip_rubriques WHERE id_parent=$leparent ORDER BY titre";
 	$result=spip_query($query);

	while($row=spip_fetch_array($result)){
		$my_rubrique=$row['id_rubrique'];
		$titre=typo($row['titre']);
	
		if (!ereg(",$my_rubrique,","$toutes_rubriques")){
			$espace="";
			for ($count=0;$count<$i;$count++){$espace.="&nbsp;&nbsp;";}
			$espace .= "|";
			if ($i==1)
				$espace = "*";

			echo "<OPTION VALUE='$my_rubrique'>$espace $titre\n";
			afficher_auteur_rubriques($my_rubrique);
		}
	}
	$i=$i-1;
}

// modif auteur restreint
if ($connect_toutes_rubriques AND $add_rub=floor($add_rub)){
	$query = "INSERT INTO spip_auteurs_rubriques (id_auteur,id_rubrique) VALUES($id_auteur,$add_rub)";
	$result = spip_query($query);
}
if ($connect_toutes_rubriques AND $supp_rub=floor($supp_rub)){
	$query = "DELETE FROM spip_auteurs_rubriques WHERE id_auteur=$id_auteur AND id_rubrique=$supp_rub";
	$result = spip_query($query);
}

// securite
if ($connect_statut != "0minirezo" AND $connect_id_auteur != $id_auteur) {
	gros_titre("Acc&egrave;s interdit");
	exit;
}

//
// Recuperer l'auteur (id_auteur) ... ou l'inventer
//
unset($auteur);

if ($id_auteur) {
	$auteur = spip_fetch_array(spip_query("SELECT * FROM spip_auteurs WHERE id_auteur=$id_auteur"));
} else if ($new == 'oui') {	// creation
	$auteur['nom'] = 'Nouvel auteur';
	$auteur['statut'] = '1comite';
}

//
// Modification (et creation si besoin)
//
if ($statut) { // si on poste un nom, c'est qu'on modifie une fiche auteur
	if ($connect_statut == '0minirezo' AND ereg("^(0minirezo|1comite|5poubelle|6forum)$",$statut))	// changer le statut
		$auteur['statut'] = $statut;

	if ($nom)	// pas de nom vide
		$auteur['nom'] = corriger_caracteres($nom);

	// login et mot de passe
	unset ($modif_login);
	$old_login = $auteur['login'];
	if ($connect_statut == '0minirezo' AND $auteur['source'] == 'spip') {
		if ($login) {
			if (strlen($login) < 4)
				$echec .= "<p>Login trop court.";
			else if (spip_num_rows(spip_query("SELECT * FROM spip_auteurs WHERE login='".addslashes($login)."' AND id_auteur!=$id_auteur AND statut!='5poubelle'")))
				$echec .= "<p>Ce login existe d&eacute;j&agrave;.";
			else if ($login != $old_login) {
				$modif_login = true;
				$auteur['login'] = $login;
			}
		}
		// suppression du login
		else if ($connect_statut == '0minirezo') $auteur['login'] = '';
	}

	// changement de pass, a securiser en jaja ?
	if ($new_pass AND ($statut != '5poubelle') AND $auteur['login'] AND $auteur['source'] == 'spip') {
		if ($new_pass != $new_pass2)
			$echec .= "<p>Les deux mots de passe ne sont pas identiques.";
		else if ($new_pass AND strlen($new_pass) < 6)
			$echec .= "<p>Mot de passe trop court.";
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
	if ($connect_statut == '0minirezo') { // seuls les admins peuvent modifier l'email
		if ($email!='' AND ! email_valide($email)) {
			$echec .= "<p>Adresse email invalide.";
			$auteur['email'] = $email;
		} else
			$auteur['email'] = $email;
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
	} else
		$query_pass = '';

	// l'entrer dans la base
	if (!$echec) {
		if (!$auteur['id_auteur']) { // creation si pas d'id
			spip_query("INSERT INTO spip_auteurs (nom) VALUES ('temp')");
			$auteur['id_auteur'] = spip_insert_id();
			$id_auteur = $auteur['id_auteur'];

			if (settype($ajouter_id_article,'integer') AND ($ajouter_id_article>0))
				spip_query("INSERT INTO spip_auteurs_articles (id_auteur, id_article) VALUES ($id_auteur, $ajouter_id_article)");
		}

		$query = "UPDATE spip_auteurs SET $query_pass
			nom='".addslashes($auteur['nom'])."',
			login='".addslashes($auteur['login'])."',
			bio='".addslashes($auteur['bio'])."',
			email='".addslashes($auteur['email'])."',
			nom_site='".addslashes($auteur['nom_site'])."',
			url_site='".addslashes($auteur['url_site'])."',
			pgp='".addslashes($auteur['pgp'])."',
			statut='".addslashes($auteur['statut'])."'
			WHERE id_auteur=".$auteur['id_auteur'];
		spip_query($query) OR die($query);
	}

	if (lire_meta('activer_moteur') == 'oui') {
		include_ecrire ("inc_index.php3");
		indexer_auteur($id_auteur);
	}

	// Mettre a jour les fichiers .htpasswd et .htpasswd-admin
	ecrire_acces();
}

// Redirection
if (($redirect_ok == 'oui') AND ($redirect)) {
	@Header("Location: ".rawurldecode($redirect));
	exit; 
}



//
// Affichage
//
if ($connect_id_auteur == $id_auteur)
	debut_page($auteur['nom'], "redacteurs", "perso");
else
	debut_page($auteur['nom'],"redacteurs","redacteurs");

echo "<br><br><br>";
gros_titre($auteur['nom']);

if (($connect_statut == "0minirezo") OR $connect_id_auteur == $id_auteur) {
	$statut_auteur=$auteur['statut'];
	barre_onglets("auteur", "infos");
}

debut_gauche();

if ($id_auteur) {
	debut_boite_info();
	echo "<CENTER>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=1><B>AUTEUR NUM&Eacute;RO&nbsp;:</B></FONT>";
	echo "<BR><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=6><B>$id_auteur</B></FONT>";
	echo "</CENTER>";
	fin_boite_info();
}

debut_droite();


//
// Formulaire d'edition de l'auteur
//

if ($echec){
	debut_cadre_relief();
	echo '<img src="img_pack/warning.gif" alt="Avertissement" width="48" height="48" align="left">';
	echo "<font color='red'>$echec <p>Veuillez recommencer.</font>";
	fin_cadre_relief();	
	echo "<p>";
}


debut_cadre_formulaire();
echo "<FORM ACTION='auteur_infos.php3?id_auteur=$id_auteur' METHOD='post'>";
echo "<INPUT TYPE='Hidden' NAME='id_auteur' VALUE=\"$id_auteur\">";


//
// Infos personnelles
//

echo "<FONT FACE='Georgia,Garamond,Times,serif' SIZE=3>";

debut_cadre_relief("fiche-perso-24.gif");

echo "<B>Signature</B> [Obligatoire]<BR>";
echo "(Votre nom ou votre pseudo)<BR>";
echo "<INPUT TYPE='text' NAME='nom' CLASS='formo' VALUE=\"".entites_html($auteur['nom'])."\" SIZE='40'><P>";

echo "<B>Qui &ecirc;tes-vous ?</B><BR>";
echo "(Courte biographie en quelques mots.)<BR>";
echo "<TEXTAREA NAME='bio' CLASS='forml' ROWS='4' COLS='40' wrap=soft>";
echo entites_html($auteur['bio']);
echo "</TEXTAREA>\n";
fin_cadre_relief();

debut_cadre_relief();

echo "<B>Votre adresse email</B> <BR>";

if ($connect_statut == "0minirezo") {
	echo "<INPUT TYPE='text' NAME='email' CLASS='forml' VALUE=\"".entites_html($auteur['email'])."\" SIZE='40'><P>\n";
} else {
	echo "<B>".$auteur['email']."</B><P>";
}

echo "<B>Votre cl&eacute; PGP</B><BR>";
echo "<TEXTAREA NAME='pgp' CLASS='forml' ROWS='4' COLS='40' wrap=soft>";
echo entites_html($auteur['pgp']);
echo "</TEXTAREA>\n";
fin_cadre_relief();

debut_cadre_relief("site-24.gif");

echo "<B>Le nom de votre site</B><BR>";
echo "<INPUT TYPE='text' NAME='nom_site_auteur' CLASS='forml' VALUE=\"".entites_html($auteur['nom_site'])."\" SIZE='40'><P>\n";

echo "<B>L'adresse (URL) de votre site</B><BR>";
echo "<INPUT TYPE='text' NAME='url_site' CLASS='forml' VALUE=\"".entites_html($auteur['url_site'])."\" SIZE='40'>\n";
fin_cadre_relief();


//
// Login et mot de passe :
// accessibles seulement aux admins non restreints et l'auteur lui-meme
//

$edit_login = ($connect_statut == "0minirezo" AND $connect_toutes_rubriques AND $auteur['source'] == 'spip');
$edit_pass = ((($connect_statut == "0minirezo" AND $connect_toutes_rubriques) OR $connect_id_auteur == $id_auteur)
	AND $auteur['source'] == 'spip');

debut_cadre_relief("base-24.gif");

// Avertissement en cas de modifs de ses propres donnees
if (($edit_login OR $edit_pass) AND $connect_id_auteur == $id_auteur) {
	debut_cadre_enfonce();	
	echo '<img src="img_pack/warning.gif" alt="Avertissement" width="48" height="48" align="right">';
	echo "<b>Attention&nbsp;! Ceci est le login sous lequel vous &ecirc;tes connect&eacute; actuellement.
	<font color=\"red\">Utilisez ce formulaire avec pr&eacute;caution&nbsp;: si vous oubliez votre mot de passe, il sera impossible de le retrouver (seul un administrateur pourra vous en attribuer un nouveau).</font></b>\n";
	fin_cadre_enfonce();	
	echo "<p>";
}

// Un redacteur n'a pas le droit de modifier son login !
if ($edit_login) {
	echo "<B>Login</B> ";
	echo "<font color='red'>(plus de 3 caract&egrave;res)</font> :<BR>";
	echo "<INPUT TYPE='text' NAME='login' CLASS='formo' VALUE=\"".entites_html($auteur['login'])."\" SIZE='40'><P>\n";
}
else {
	echo "<fieldset style='padding:5'><legend><B>Login</B><BR></legend><br><b>".$auteur['login']."</b> ";
	echo "<i> (ne peut pas &ecirc;tre modifi&eacute;)</i>";
}

// On ne peut modifier le mot de passe en cas de source externe (par exemple LDAP)
if ($edit_pass) {
	echo "<B>Nouveau mot de passe</B> ";
	echo "<font color='red'>(plus de 5 caract&egrave;res)</font> :<BR>";
	echo "<INPUT TYPE='password' NAME='new_pass' CLASS='formo' VALUE=\"\" SIZE='40'><BR>\n";
	echo "Confirmer ce nouveau mot de passe :<BR>";
	echo "<INPUT TYPE='password' NAME='new_pass2' CLASS='formo' VALUE=\"\" SIZE='40'><P>\n";
}
fin_cadre_relief();


//
// Seuls les admins voient le menu 'statut', mais les admins restreints ne
// pourront l'utiliser que pour mettre un auteur a la poubelle
//

$statut = $auteur['statut']; // pour aller plus vite

if ($connect_statut == "0minirezo"
AND ($connect_toutes_rubriques OR $statut != "0minirezo")
AND $connect_id_auteur != $id_auteur) {
	debut_cadre_relief();
	echo "<center><B>Statut de cet auteur : </B> ";
	echo " <SELECT NAME='statut' SIZE=1 CLASS='fondl'>";

	if ($connect_statut == "0minirezo" AND $connect_toutes_rubriques)
		echo "<OPTION".mySel("0minirezo",$statut).">administrateur";

	echo "<OPTION".mySel("1comite",$statut).">r&eacute;dacteur";
	
	if (($statut == '6forum') OR (lire_meta('accepter_visiteurs') == 'oui') OR (lire_meta('forums_publics') == 'abo'))
		echo "<OPTION".mySel("6forum",$statut).">visiteur";
	echo "<OPTION".mySel("5poubelle",$statut).">&gt; &agrave; la poubelle";

	echo "</SELECT></center>\n";
	fin_cadre_relief();
}
else {
	echo "<INPUT TYPE='Hidden' NAME='statut' VALUE=\"$statut\">";
}

//
// Gestion restreinte des rubriques
//
if ($statut == '0minirezo') {
	debut_cadre_enfonce("secteur-24.gif");

	$query_admin = "SELECT lien.id_rubrique, titre FROM spip_auteurs_rubriques AS lien, spip_rubriques AS rubriques WHERE lien.id_auteur=$id_auteur AND lien.id_rubrique=rubriques.id_rubrique GROUP BY lien.id_rubrique";
	$result_admin = spip_query($query_admin);

	if (spip_num_rows($result_admin) == 0) {
		echo "Cet administrateur g&egrave;re <b>toutes les rubriques</b>.";
	} else {
		echo "Cet administrateur g&egrave;re les rubriques suivantes :\n";
		echo "<ul style='list-style-image: url(img_pack/rubrique-12.png)'>";
		while ($row_admin = spip_fetch_array($result_admin)) {
			$id_rubrique = $row_admin["id_rubrique"];
			$titre = typo($row_admin["titre"]);
			echo "<li>$titre";
			if ($connect_toutes_rubriques AND $connect_id_auteur != $id_auteur) {
				echo " <font size=1>[<a href='auteur_infos.php3?id_auteur=$id_auteur&supp_rub=$id_rubrique'>supprimer cette rubrique</a>]</font>";
			}
			$toutes_rubriques .= "$id_rubrique,";
		}
		echo "</ul>";
		$toutes_rubriques = ",$toutes_rubriques";
	}

	if ($connect_toutes_rubriques AND $connect_id_auteur != $id_auteur) {
		if (spip_num_rows($result_admin) == 0) {
			echo "<p><B>Restreindre la gestion &agrave; la rubrique :</b><BR>";
		} else {
			echo "<p><B>Ajouter une autre rubrique &agrave; administrer :</b><BR>";
		}
		echo "<INPUT NAME='id_auteur' VALUE='$id_auteur' TYPE='hidden'>";
		echo "<SELECT NAME='add_rub' SIZE=1 CLASS='formo'>";
		echo "<OPTION VALUE='0'>   \n";
		afficher_auteur_rubriques("0");
		echo "</SELECT>";
	}
	fin_cadre_enfonce();
}

echo "<INPUT NAME='ajouter_id_article' VALUE='$ajouter_id_article' TYPE='hidden'>\n";
echo "<INPUT NAME='redirect' VALUE='$redirect' TYPE='hidden'>\n";
echo "<INPUT NAME='redirect_ok' VALUE='oui' TYPE='hidden'>\n";

echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondo' NAME='Valider' VALUE='Valider'></DIV>";
echo "</form>";
fin_cadre_formulaire();
echo "&nbsp;<p>";

fin_page();

?>