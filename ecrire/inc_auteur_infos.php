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

if (!defined("_ECRIRE_INC_VERSION")) return;
include_ecrire("inc_presentation.php3");
include_ecrire("inc_texte.php3");
include_ecrire("inc_urls.php3");
include_ecrire("inc_rubriques.php3");
include_ecrire ("inc_acces.php3");
include_ecrire ("inc_logos.php3");
include_ecrire ("inc_session.php3");
include_ecrire ("inc_filtres.php3");
include_ecrire ("inc_abstract_sql.php3");

function affiche_auteur_info_dist($id_auteur, $auteur,  $echec, $redirect, $ajouter_id_article)
{
  global $connect_id_auteur;

  if ($connect_id_auteur == $id_auteur)
	debut_page($auteur['nom'], "auteurs", "perso");
  else
	debut_page($auteur['nom'],"auteurs","redacteurs");

  echo "<br><br><br>";

  debut_gauche();

  cadre_auteur_infos($id_auteur, $auteur);

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
  formulaire_auteur_infos($id_auteur, $auteur, $onfocus, $champs_extra, $redirect, $ajouter_id_article);
  fin_cadre_formulaire();
  echo "&nbsp;<p />";

  fin_page();
}


function cadre_auteur_infos($id_auteur, $auteur)
{
  global $connect_statut;

  if ($id_auteur) {
	debut_boite_info();
	echo "<CENTER>";
	echo "<FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=1><B>"._T('titre_cadre_numero_auteur')."&nbsp;:</B></FONT>";
	echo "<BR><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=6><B>$id_auteur</B></FONT>";
	echo "</CENTER>";


// "Voir en ligne" si l'auteur a un article publie
// seuls les admins peuvent "previsualiser" une page auteur
if (spip_num_rows(spip_query("SELECT lien.id_article
FROM spip_auteurs_articles AS lien,
spip_articles AS articles
WHERE lien.id_auteur=$id_auteur
AND lien.id_article=articles.id_article
AND articles.statut='publie'")))
	voir_en_ligne ('auteur', $id_auteur, 'publie');
else if ($connect_statut == '0minirezo')
	voir_en_ligne ('auteur', $id_auteur, 'prop');

	fin_boite_info();
  }
}


function formulaire_auteur_infos($id_auteur, $auteur, $onfocus, $champs_extra, $redirect, $ajouter_id_article)
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


function choix_statut_auteur($statut)
{
	global $connect_toutes_rubriques;
	return "<select name='statut' size=1 class='fondl'
		onChange=\"setvisibility('changer_statut_auteur', this.selectedIndex ? 'hidden' : 'visible');\">" .

		(!$connect_toutes_rubriques ? "" :
			("\n<option" .
			mySel("0minirezo",$statut) .
			 ">" .
			 _T('item_administrateur_2') .
			 '</option>')) .
	  "\n<option" .
	  mySel("1comite",$statut) .
	  ">" .
	  _T('intem_redacteur') .
	  '</option>' .
	  (!(($statut == '6forum')
		      OR (lire_meta('accepter_visiteurs') == 'oui')
		      OR (lire_meta('forums_publics') == 'abo')
	     OR spip_num_rows(spip_query("SELECT statut FROM spip_auteurs WHERE statut='6forum'"))) ? "" :
	   ("\n<option" .
	    mySel("6forum",$statut) .
	    ">" .
	    _T('item_visiteur') .
	    '</option>')) .
	  "\n<option" .
	  mySel("5poubelle",$statut) .
	  " style='background:url(" . _DIR_IMG_PACK . "rayures-sup.gif)'>&gt; "._T('texte_statut_poubelle') .
	  '</option>' .
	  "</select>\n";
}

//  affiche le statut de l'auteur dans l'espace prive

function afficher_formulaire_statut_auteur ($id_auteur, $statut, $post='') {
	global $connect_statut, $connect_toutes_rubriques, $connect_id_auteur;
	global $spip_lang_right;


	// S'agit-il d'un admin restreint ?
	if ($statut == '0minirezo') {
		$query_admin = "SELECT lien.id_rubrique, titre FROM spip_auteurs_rubriques AS lien, spip_rubriques AS rubriques WHERE lien.id_auteur=$id_auteur AND lien.id_rubrique=rubriques.id_rubrique GROUP BY lien.id_rubrique";
		$result_admin = spip_query($query_admin);
		$admin_restreint = (spip_num_rows($result_admin) > 0);
	}

	$droit = ( ($connect_toutes_rubriques OR $statut != "0minirezo")
		   && ($connect_id_auteur != $id_auteur));

	if ($post && $droit) {
		$url_self = $post;
		echo "<p />";
		echo "<form action='$post' method='post'>\n";
	} else
		$url_self = "auteur_infos.php3?id_auteur=$id_auteur";

	// les admins voient et peuvent modifier les droits
	// les admins restreints les voient mais 
	// ne peuvent les utiliser que pour mettre un auteur a la poubelle
	if ($connect_statut == "0minirezo") {
		debut_cadre_relief();

		if ($droit) {
		  /* Neutralisation momentanee des couches. A revoir.
		  $couches = $admin_restreint ? 
		    bouton_block_visible("statut$id_auteur") :
		    bouton_block_invisible("statut$id_auteur");
		  echo $couches;
		  */
		  echo "<b>"._T('info_statut_auteur')." </b> ";
		  echo choix_statut_auteur($statut);
		}

		// si pas admin au chargement, rien a montrer. 
		echo "<div id='changer_statut_auteur'",
		  (($statut == '0minirezo') ? '' : " style='visibility: hidden'"),
		  '>';

		echo "\n<p /><div style='arial2'>";
		// si pas admin restreint au chargement, rien a calculer
		if (!$admin_restreint) {
			if ($statut == '0minirezo') {
				echo _T('info_admin_gere_toutes_rubriques');
			}
		} else {
				echo _T('info_admin_gere_rubriques')."\n";
				echo "<ul style='list-style-image: url(" . _DIR_IMG_PACK . "rubrique-12.gif)'>";
				while ($row_admin = spip_fetch_array($result_admin)) {
					$id_rubrique = $row_admin["id_rubrique"];
					echo "<li><a href='naviguer.php3?id_rubrique=$id_rubrique'>", typo($row_admin["titre"]), "</a>";

					if ($connect_toutes_rubriques
					AND $connect_id_auteur != $id_auteur) {
					  echo "&nbsp;&nbsp;&nbsp;&nbsp;<font size='1'>[<a href='$url_self&supp_rub=$id_rubrique'>",
					    _T('lien_supprimer_rubrique'),
					    "</a>]</font>";
					}
					echo '</li>';
					$toutes_rubriques .= "$id_rubrique,";
				}
				$toutes_rubriques = ",$toutes_rubriques";

				echo "</ul>";
		}
		echo "</div>\n";

		// Ajouter une rubrique a un administrateur restreint
		if ($connect_toutes_rubriques AND $connect_id_auteur != $id_auteur) {
			echo debut_block_visible("statut$id_auteur");
			echo "\n<div id='ajax_rubrique' class='arial1'><br />\n";
			if (spip_num_rows($result_admin) == 0)
				echo "<b>"._T('info_restreindre_rubrique')."</b><br />";
			else
				echo "<b>"._T('info_ajouter_rubrique')."</b><br />";
			echo "\n<input name='id_auteur' value='$id_auteur' TYPE='hidden' />";

			// selecteur de rubrique
			include_ecrire('inc_rubriques.php3');
			echo selecteur_rubrique(0, 'auteur', false);

			echo "</div>\n";
			echo fin_block();
		}

		echo '</div>'; // fin de la balise a visibilite conditionnelle

		if ($post && $droit) {
		  echo "<div align='",
		    $spip_lang_right,
		    "'><input type='submit' class='fondo' value=\"",
		    _T('bouton_valider'),
		    "\" /></div>",
		    "</form>\n";
		}

		fin_cadre_relief();
	}
}

function statut_modifiable_auteur($id_auteur, $auteur)
{
	global $connect_statut, $connect_toutes_rubriques, $connect_id_auteur;

// on peut se changer soi-meme
	  return  (($connect_id_auteur == $id_auteur) ||
  // sinon on doit etre admin
  // et pas admin restreint pour changer un autre admin
		(($connect_statut == "0minirezo") &&
		 ($connect_toutes_rubriques OR 
		  ($auteur['statut'] != "0minirezo"))));
}

function modifier_statut_auteur (&$auteur, $statut, $add_rub='', $supp_rub='') {
	global $connect_statut, $connect_toutes_rubriques;
	// changer le statut ?
	$id_auteur= $auteur['id_auteur'];
	if (statut_modifiable_auteur($id_auteur, $auteur) &&
	    ereg("^(0minirezo|1comite|5poubelle|6forum)$",$statut)) {
			$auteur['statut'] = $statut;
			spip_query("UPDATE spip_auteurs SET statut='".$statut."'
			WHERE id_auteur=". intval($id_auteur));
	}

	// modif auteur restreint, seulement pour les admins
	if ($connect_toutes_rubriques) {
		if ($add_rub=intval($add_rub))
			spip_query("INSERT INTO spip_auteurs_rubriques
			(id_auteur,id_rubrique)
			VALUES(".$auteur['id_auteur'].", $add_rub)");

		if ($supp_rub=intval($supp_rub))
			spip_query("DELETE FROM spip_auteurs_rubriques
			WHERE id_auteur=".$auteur['id_auteur']."
			AND id_rubrique=$supp_rub");
	}
}


?>
