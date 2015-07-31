<?php

include ("inc.php3");
include_ecrire ("inc_acces.php3");
include_ecrire ("inc_logos.php3");
include_ecrire ("inc_session.php3");
include_ecrire ("inc_filtres.php3");

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
	gros_titre(_T('info_acces_interdit'));
	exit;
}

//
// Recuperer l'auteur (id_auteur) ... ou l'inventer
//
unset($auteur);

if ($id_auteur) {
	$auteur = spip_fetch_array(spip_query("SELECT * FROM spip_auteurs WHERE id_auteur=$id_auteur"));
	$new = false;	// eviter hack
} else {
	$auteur['nom'] = filtrer_entites(_T('item_nouvel_auteur'));
	$auteur['statut'] = '1comite';
	$auteur['source'] = 'spip';
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
	if (($login<>$old_login) AND $connect_statut == '0minirezo' AND $connect_toutes_rubriques AND $auteur['source'] == 'spip') {
		if ($login) {
			if (strlen($login) < 4)
				$echec .= "<p>"._T('info_login_trop_court');
			else if (spip_num_rows(spip_query("SELECT * FROM spip_auteurs WHERE login='".addslashes($login)."' AND id_auteur!=$id_auteur AND statut!='5poubelle'")))
				$echec .= "<p>"._T('info_login_existant');
			else if ($login != $old_login) {
				$modif_login = true;
				$auteur['login'] = $login;
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
	if ($connect_statut == '0minirezo') { // seuls les admins peuvent modifier l'email
		if ($email !='' AND !email_valide($email)) {
			$echec .= "<p>"._T('info_email_invalide');
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
		effacer_low_sec($connect_id_auteur);
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
			$add_extra
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
	echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=1><B>"._T('titre_cadre_numero_auteur')."&nbsp;:</B></FONT>";
	echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=6><B>$id_auteur</B></FONT>";
	echo "</CENTER>";
	fin_boite_info();
}

debut_droite();


//
// Formulaire d'edition de l'auteur
//

if ($echec){
	debut_cadre_relief();
	echo '<img src="img_pack/warning.gif" alt="'._T('info_avertissement').'" width="48" height="48" align="left">';
	echo "<font color='red'>$echec <p>"._T('info_recommencer')."</font>";
	fin_cadre_relief();
	echo "<p>";
}


debut_cadre_formulaire();
echo "<FORM ACTION='auteur_infos.php3?id_auteur=$id_auteur' METHOD='post'>";
echo "<INPUT TYPE='Hidden' NAME='id_auteur' VALUE=\"$id_auteur\">";


//
// Infos personnelles
//

echo "<FONT FACE='Georgia,Garamond,Times,serif' SIZE='3'>";

debut_cadre_relief("fiche-perso-24.gif");

echo _T('titre_cadre_signature_obligatoire');
echo "("._T('entree_nom_pseudo').")<BR>";
echo "<INPUT TYPE='text' NAME='nom' CLASS='formo' VALUE=\"".entites_html($auteur['nom'])."\" SIZE='40'><P>";

echo "<B>"._T('entree_adresse_email')."</B>";
if ($connect_statut == "0minirezo") {
	echo "<br><INPUT TYPE='text' NAME='email' CLASS='formo' VALUE=\"".entites_html($auteur['email'])."\" SIZE='40'><P>\n";
}
else {
	echo "&nbsp;: <tt>".$auteur['email']."</tt>";
	echo "<br>("._T('info_reserve_admin').")\n";
	echo "<P>";
}

echo "<B>"._T('entree_infos_perso')."</B><BR>";
echo "("._T('entree_biographie').")<BR>";
echo "<TEXTAREA NAME='bio' CLASS='forml' ROWS='4' COLS='40' wrap=soft>";
echo entites_html($auteur['bio']);
echo "</TEXTAREA>\n";

	if ($champs_extra) {
		include_ecrire("inc_extra.php3");
		extra_saisie($auteur['extra'], 'auteurs', $auteur['statut']);
	}

fin_cadre_relief();
echo "<p>";

if ($options == "avancees") {
	debut_cadre_relief("cadenas-24.gif");
	echo "<B>"._T('entree_cle_pgp')."</B><BR>";
	echo "<TEXTAREA NAME='pgp' CLASS='forml' ROWS='4' COLS='40' wrap=soft>";
	echo entites_html($auteur['pgp']);
	echo "</TEXTAREA>\n";
	fin_cadre_relief();
	echo "<p>";
}
else {
	echo "<input type='hidden' name='pgp' value=\"".entites_html($auteur['pgp'])."\">";
}

debut_cadre_relief("site-24.gif");
echo "<B>"._T('entree_nom_site')."</B><BR>";
echo "<INPUT TYPE='text' NAME='nom_site_auteur' CLASS='forml' VALUE=\"".entites_html($auteur['nom_site'])."\" SIZE='40'><P>\n";

echo "<B>"._T('entree_url')."</B><BR>";
echo "<INPUT TYPE='text' NAME='url_site' CLASS='forml' VALUE=\"".entites_html($auteur['url_site'])."\" SIZE='40'>\n";
fin_cadre_relief();
echo "<p>";


//
// Login et mot de passe :
// accessibles seulement aux admins non restreints et l'auteur lui-meme
//

if ($auteur['source'] != 'spip') {
	$edit_login = false;
	$edit_pass = false;
}
else if (($connect_statut == "0minirezo") AND $connect_toutes_rubriques) {
	$edit_login = true;
	$edit_pass = true;
}
else if ($connect_id_auteur == $id_auteur) {
	$edit_login = false;
	$edit_pass = true;
}
else {
	$edit_login = false;
	$edit_pass = false;
}

debut_cadre_relief("base-24.gif");

// Avertissement en cas de modifs de ses propres donnees
if (($edit_login OR $edit_pass) AND $connect_id_auteur == $id_auteur) {
	debut_cadre_enfonce();
	echo '<img src="img_pack/warning.gif" alt="'._T('info_avertissement').'" width="48" height="48" align="right">';
	echo "<b>"._T('texte_login_precaution')."</b>\n";
	fin_cadre_enfonce();
	echo "<p>";
}

// Un redacteur n'a pas le droit de modifier son login !
if ($edit_login) {
	echo "<B>"._T('item_login')."</B> ";
	echo "<font color='red'>("._T('texte_plus_trois_car').")</font> :<BR>";
	echo "<INPUT TYPE='text' NAME='login' CLASS='formo' VALUE=\"".entites_html($auteur['login'])."\" SIZE='40'><P>\n";
}
else {
	echo "<fieldset style='padding:5'><legend><B>"._T('item_login')."</B><BR></legend><br><b>".$auteur['login']."</b> ";
	echo "<i> ("._T('info_non_modifiable').")</i><p>";
}

// On ne peut modifier le mot de passe en cas de source externe (par exemple LDAP)
if ($edit_pass) {
	echo "<B>"._T('entree_nouveau_passe')."</B> ";
	echo "<font color='red'>("._T('info_plus_cinq_car').")</font> :<BR>";
	echo "<INPUT TYPE='password' NAME='new_pass' CLASS='formo' VALUE=\"\" SIZE='40'><BR>\n";
	echo _T('info_confirmer_passe')."<BR>";
	echo "<INPUT TYPE='password' NAME='new_pass2' CLASS='formo' VALUE=\"\" SIZE='40'><P>\n";
}
fin_cadre_relief();
echo "<p>";


//
// Seuls les admins voient le menu 'statut', mais les admins restreints ne
// pourront l'utiliser que pour mettre un auteur a la poubelle
//

$statut = $auteur['statut']; // pour aller plus vite

if ($connect_statut == "0minirezo"
	AND ($connect_toutes_rubriques OR $statut != "0minirezo")
	AND $connect_id_auteur != $id_auteur) {
	debut_cadre_relief();
	echo "<center><B>"._T('info_statut_auteur')." </B> ";
	echo " <SELECT NAME='statut' SIZE=1 CLASS='fondl'>";

	if ($connect_statut == "0minirezo" AND $connect_toutes_rubriques)
		echo "<OPTION".mySel("0minirezo",$statut).">"._T('item_administrateur_2');

	echo "<OPTION".mySel("1comite",$statut).">"._T('intem_redacteur');

	if (($statut == '6forum') OR (lire_meta('accepter_visiteurs') == 'oui') OR (lire_meta('forums_publics') == 'abo'))
		echo "<OPTION".mySel("6forum",$statut).">"._T('item_visiteur');
	echo "<OPTION".mySel("5poubelle",$statut)." style='background:url(img_pack/rayures-sup.gif)'>&gt; "._T('texte_statut_poubelle');

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
		echo _T('info_admin_gere_toutes_rubriques');
	} else {
		echo _T('info_admin_gere_rubriques')."\n";
		echo "<ul style='list-style-image: url(img_pack/rubrique-12.gif)'>";
		while ($row_admin = spip_fetch_array($result_admin)) {
			$id_rubrique = $row_admin["id_rubrique"];
			$titre = typo($row_admin["titre"]);
			echo "<li>$titre";
			if ($connect_toutes_rubriques AND $connect_id_auteur != $id_auteur) {
				echo " <font size=1>[<a href='auteur_infos.php3?id_auteur=$id_auteur&supp_rub=$id_rubrique'>"._T('lien_supprimer_rubrique')."</a>]</font>";
			}
			$toutes_rubriques .= "$id_rubrique,";
		}
		echo "</ul>";
		$toutes_rubriques = ",$toutes_rubriques";
	}

	if ($connect_toutes_rubriques AND $connect_id_auteur != $id_auteur) {
		if (spip_num_rows($result_admin) == 0) {
			echo "<p><B>"._T('info_restreindre_rubrique')."</b><BR>";
		} else {
			echo "<p><B>"._T('info_ajouter_rubrique')."</b><BR>";
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

echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondo' NAME='Valider' VALUE='"._T('bouton_valider')."'></DIV>";

echo "</font>";

echo "</form>";
fin_cadre_formulaire();
echo "&nbsp;<p>";

fin_page();

?>
