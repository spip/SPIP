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

function affiche_auteur_info_dist($id_auteur, $auteur,  $echec)
{
  global $connect_id_auteur;

  if ($connect_id_auteur == $id_auteur)
	debut_page($auteur['nom'], "auteurs", "perso");
  else
	debut_page($auteur['nom'],"auteurs","redacteurs");

  echo "<br><br><br>";

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
	echo http_img_pack("warning.gif", _T('info_avertissement'), "width='48' height='48' align='left'");
	echo "<font color='red'>$echec <p>"._T('info_recommencer')."</font>";
	fin_cadre_relief();
	echo "<p>";
  }

  debut_cadre_formulaire();
  formulaire_auteur_infos($id_auteur, $auteur, $onfocus, $champs_extra, $redirect);
  fin_cadre_formulaire();
  echo "&nbsp;<p />";

  fin_page();
}

function formulaire_auteur_infos($id_auteur, $auteur, $onfocus, $champs_extra, $redirect, $apparait=true)
{
  global $connect_statut, $connect_toutes_rubriques,$connect_id_auteur, $options ;

  echo "<form  method='POST' action='auteur_infos.php3",
  (!$id_auteur ? "'>" :
   ("?id_auteur=$id_auteur'><input type='hidden' name='id_auteur' value='$id_auteur' />"));


//
// Infos personnelles
//

echo "<div class='serif'>";

debut_cadre_relief("fiche-perso-24.gif", false, "", _T("icone_informations_personnelles"));

echo _T('titre_cadre_signature_obligatoire');
echo "("._T('entree_nom_pseudo').")<BR>";
echo "<INPUT TYPE='text' NAME='nom' CLASS='formo' VALUE=\"".entites_html($auteur['nom'])."\" SIZE='40' $onfocus><P>";

echo "<B>"._T('entree_adresse_email')."</B>";

if ($connect_statut == "0minirezo"
AND ($connect_toutes_rubriques OR $auteur['statut']<>'0minirezo')) {
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

debut_cadre_enfonce("site-24.gif", false, "", _T('info_site_web'));
echo "<B>"._T('entree_nom_site')."</B><BR>";
echo "<INPUT TYPE='text' NAME='nom_site_auteur' CLASS='forml' VALUE=\"".entites_html($auteur['nom_site'])."\" SIZE='40'><P>\n";

echo "<B>"._T('entree_url')."</B><BR>";
echo "<INPUT TYPE='text' NAME='url_site' CLASS='forml' VALUE=\"".entites_html($auteur['url_site'])."\" SIZE='40'>\n";
fin_cadre_enfonce();
	echo "<p>";

if ($options == "avancees") {
	debut_cadre_enfonce("cadenas-24.gif", false, "", _T('entree_cle_pgp'));
	echo "<TEXTAREA NAME='pgp' CLASS='forml' ROWS='4' COLS='40' wrap=soft>";
	echo entites_html($auteur['pgp']);
	echo "</TEXTAREA>\n";
	fin_cadre_enfonce();
	echo "<p>";
}
else {
	echo "<input type='hidden' name='pgp' value=\"".entites_html($auteur['pgp'])."\">";
}

echo "<p>";
	if ($champs_extra) {
		include_ecrire("inc_extra.php3");
		extra_saisie($auteur['extra'], 'auteurs', $auteur['statut']);
	}

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
	echo http_img_pack("warning.gif", _T('info_avertissement'), "width='48' height='48' align='right'");
	echo "<b>"._T('texte_login_precaution')."</b>\n";
	fin_cadre_enfonce();
	echo "<p>";
}

// Un redacteur n'a pas le droit de modifier son login !
if ($edit_login) {
	echo "<B>"._T('item_login')."</B> ";
	echo "<font color='red'>("._T('texte_plus_trois_car').")</font> :<BR>";
	echo "<INPUT TYPE='text' NAME='new_login' CLASS='formo' VALUE=\"".entites_html($auteur['login'])."\" SIZE='40'><P>\n";
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
echo "<p />";


//
// Apparaitre dans la liste des redacteurs connectes
//

 if ($apparait) apparait_auteur_infos($id_auteur, $auteur);

// Afficher le formulaire de changement de statut (cf. inc_acces.php3)
afficher_formulaire_statut_auteur ($id_auteur, $auteur['statut']);


echo "<INPUT NAME='ajouter_id_article' VALUE='$ajouter_id_article' TYPE='hidden'>\n";
echo "<INPUT NAME='redirect' VALUE='$redirect' TYPE='hidden'>\n";
echo "<INPUT NAME='redirect_ok' VALUE='oui' TYPE='hidden'>\n";

echo "<DIV align='right'><INPUT TYPE='submit' CLASS='fondo' NAME='Valider' VALUE='"._T('bouton_enregistrer')."'></DIV>";

echo "</div>";

echo "</form>";

}
//
// Apparaitre dans la liste des redacteurs connectes
//

function apparait_auteur_infos($id_auteur, $auteur)
{
	global $connect_id_auteur ;
	if ($connect_id_auteur == $id_auteur) {

		debut_cadre_relief("messagerie-24.gif", false, "", _T('info_liste_redacteurs_connectes'));
		
		echo "<div>"._T('texte_auteur_messagerie')."</div>";	

		if ($auteur['imessage']=="non"){
			echo "<INPUT TYPE='radio' NAME='perso_activer_imessage' VALUE='oui' id='perso_activer_imessage_on'>";
			echo " <label for='perso_activer_imessage_on'>"._T('bouton_radio_apparaitre_liste_redacteurs_connectes')."</label> ";
			echo "<BR><INPUT TYPE='radio' NAME='perso_activer_imessage' VALUE='non' CHECKED id='perso_activer_imessage_off'>";
			echo " <B><label for='perso_activer_imessage_off'>"._T('bouton_radio_non_apparaitre_liste_redacteurs_connectes')."</label></B> ";
		} else {
			echo "<INPUT TYPE='radio' NAME='perso_activer_imessage' VALUE='oui' id='perso_activer_imessage_on' CHECKED>";
			echo " <B><label for='perso_activer_imessage_on'>"._T('bouton_radio_apparaitre_liste_redacteurs_connectes')."</label></B> ";

			echo "<BR><INPUT TYPE='radio' NAME='perso_activer_imessage' VALUE='non' id='perso_activer_imessage_off'>";
			echo " <label for='perso_activer_imessage_off'>"._T('bouton_radio_non_apparaitre_liste_redacteurs_connectes')."</label> ";
		}

	fin_cadre_relief();
	echo "<p />";
	}
}

?>
