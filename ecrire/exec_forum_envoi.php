<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_ecrire("inc_presentation");
include_ecrire ("inc_barre");
include_ecrire ("inc_forum");

function forum_envoi_dist()
{
global
  $activer_messagerie,
  $adresse_retour,
  $connect_activer_messagerie,
  $connect_email,
  $connect_id_auteur,
  $connect_nom,
  $id_article,
  $id_breve,
  $id_message,
  $id_parent,
  $id_rubrique,
  $id_syndic,
  $modif_forum,
  $nom_site,
  $options,
  $statut,
  $texte,
  $titre_message,
  $url_site,
  $valider_forum,
  $spip_lang_rtl;

 $id_rubrique = intval($id_rubrique);
 $id_parent = intval($id_parent);
 $id_article = intval($id_article);
 $id_breve = intval($id_breve);
 $id_message = intval($id_message);
 $id_syndic = intval($id_syndic);

if ($modif_forum != "oui")
        $titre_message = ereg_replace("^([^>])", "> \\1", $titre_message);

$nom = corriger_caracteres($connect_nom);
$adresse_retour = rawurldecode($adresse_retour);

if ($valider_forum AND ($statut!='')) {
	$titre_message = corriger_caracteres($titre_message);
	$texte = corriger_caracteres($texte);

	$query = "INSERT INTO spip_forum
	(titre, texte, date_heure, nom_site, url_site, statut, id_auteur,
	auteur, email_auteur, id_rubrique, id_parent, id_article, id_breve,
	id_message, id_syndic)
	VALUES ('".addslashes($titre_message)."',
	'".addslashes($texte)."', NOW(),
	'".addslashes($nom_site)."',
	'".addslashes($url_site)."',
	'".addslashes($statut)."',
	$connect_id_auteur,
	'".addslashes($nom)."',
	'$connect_email',
	'$id_rubrique', '$id_parent', '$id_article', '$id_breve',
	'$id_message', '$id_syndic')";
	$result = spip_query($query);

	calculer_threads();

	if ($id_message > 0) {
		$query = "UPDATE spip_auteurs_messages SET vu = 'non'
			WHERE id_message='$id_message'";
		$result = spip_query($query);
	}
	redirige_par_entete($adresse_retour);
}

if ($id_message) debut_page(_T('titre_page_forum_envoi'), "asuivre", "messagerie");
else debut_page(_T('titre_page_forum_envoi'), "redacteurs");
debut_gauche();
debut_droite();


if ($id_parent) {
	$query = "SELECT * FROM spip_forum WHERE id_forum=$id_parent";
	$result = spip_query($query);
	if ($row = spip_fetch_array($result)) {
		$id_article = $row['id_article'];
		$id_breve = $row['id_breve'];
		$id_rubrique = $row['id_rubrique'];
		$id_message = $row['id_message'];
		$id_syndic = $row['id_syndic'];
		$statut = $row['statut'];
		$titre_parent = $row['titre'];
		$texte_parent = $row['texte'];
		$auteur_parent = $row['auteur'];
		$id_auteur_parent = $row['id_auteur'];
		$date_heure_parent = $row['date_heure'];
		$nom_site_parent = $row['nom_site'];
		$url_site_parent = $row['url_site'];
	}
}



if ($titre_parent) {
	debut_cadre_forum("forum-interne-24.gif", false, "", typo($titre_parent));
	echo "<span class='arial2'>$date_heure_parent</span>";
	echo " ".typo($auteur_parent);

	if ($id_auteur_parent  AND $activer_messagerie != "non" AND $connect_activer_messagerie != "non") {
		$bouton = bouton_imessage($id_auteur_parent, $row);
		if ($bouton) echo "&nbsp;".$bouton;
	}

	echo justifier(propre($texte_parent));

	if (strlen($url_site_parent) > 10 AND $nom_site_parent) {
		echo "<p align='left'><font face='Verdana,Arial,Sans,sans-serif'><b><a href='$url_site_parent'>$nom_site_parent</a></b></font>";
	}

	fin_cadre_forum();

	if ($modif_forum == "oui") {
		echo "<table width=100% cellpadding=0 cellspacing=0 border=0><tr>";
		echo "<td width='10' height='13' valign='top' background='" . _DIR_IMG_PACK . "forum-vert.gif'>",
		  http_img_pack('rien.gif', ' ', "width='10' height='13' border='0'"),
		  "</td>\n";
		echo "\n<td width=100% valign='top' rowspan='2'>";
	}
}


if ($modif_forum == "oui") {
	debut_cadre_thread_forum("", false, "", typo($titre_message));

	echo propre($texte);

	if (strlen($nom_site)>0) {
		echo "<p><a href='$url_site'>$nom_site</a>";
	}

	echo generer_url_post_ecrire('forum_envoi',"",'formulaire');
	echo "<p><div align='right'><INPUT CLASS='fondo' TYPE='submit' NAME='valider_forum' VALUE='"._T('bouton_envoyer_message')."'></div>";

	fin_cadre_thread_forum();
	if ($titre_parent) {
		echo "</td></tr><tr>";
		echo "<td width=10 valign='top' background='" . _DIR_IMG_PACK . "rien.gif'>",
		  http_img_pack("forum-droite$spip_lang_rtl.gif",
				addslashes($titre_parent),
				"width='10' height='13' border='0'"), "</td>\n";
		echo "</tr></table>";
	}
}
else {
	echo generer_url_post_ecrire('forum_envoi',"",'formulaire');
}

	echo "<div>&nbsp;</div>";


debut_cadre_formulaire(($statut == 'privac') ? "" : 'background-color: #dddddd;');

echo "<TABLE BORDER=0 CELLPADDING=0 CELLSPACING=0 BACKGROUND='' WIDTH=\"100%\"><TR><TD>";
$forum_stat = $statut;
if ($forum_stat == "prive") $logo = "forum-interne-24.gif";
 else if ($forum_stat == "privadm") $logo = "forum-admin-24.gif";
 else if ($forum_stat == "privrac") $logo = "forum-interne-24.gif";
 else $logo = "forum-public-24.gif";

icone(_T('icone_retour'), $adresse_retour, $logo);
echo "</TD>";

echo "<TD><IMG SRC='" . _DIR_IMG_PACK . "rien.gif' WIDTH=10 BORDER=0></td><TD WIDTH=\"100%\">";
echo "<B>"._T('info_titre')."</B><BR>";
$titre_message = entites_html($titre_message);
echo "<INPUT TYPE='text' CLASS='formo' NAME='titre_message' VALUE=\"$titre_message\" SIZE='40'><P>\n";
echo "</TD></TR></TABLE>";

if (!$modif_forum OR $modif_forum == "oui") {
	echo "<INPUT TYPE='Hidden' NAME='modif_forum' VALUE='oui'>\n";
}

echo "<INPUT TYPE='Hidden' NAME='adresse_retour' VALUE=\"$adresse_retour\">\n";
echo "<INPUT TYPE='Hidden' NAME='id_rubrique' VALUE=\"$id_rubrique\">\n";
echo "<INPUT TYPE='Hidden' NAME='id_parent' VALUE=\"$id_parent\">\n";
echo "<INPUT TYPE='Hidden' NAME='id_article' VALUE=\"$id_article\">\n";
echo "<INPUT TYPE='Hidden' NAME='id_breve' VALUE=\"$id_breve\">\n";
echo "<INPUT TYPE='Hidden' NAME='id_message' VALUE=\"$id_message\">\n";
echo "<INPUT TYPE='Hidden' NAME='id_syndic' VALUE=\"$id_syndic\">\n";
echo "<INPUT TYPE='Hidden' NAME='statut' VALUE=\"$statut\">\n";


echo "<p><B>"._T('info_texte_message')."</B><BR>";
echo _T('info_creation_paragraphe')."<BR>";
echo afficher_barre('document.formulaire.texte', true);
echo "<TEXTAREA NAME='texte' ".$GLOBALS['browser_caret']." ROWS='15' CLASS='formo' COLS='40' wrap=soft>";
echo entites_html($texte);
echo "</TEXTAREA><P>\n";

if ($statut != 'perso' AND $options == "avancees") {
	echo "<B>"._T('info_lien_hypertexte')."</B><BR>";
	echo _T('texte_lien_hypertexte')."<BR>";
	echo _T('texte_titre_02')."<BR>";
	echo "<INPUT TYPE='text' CLASS='forml' NAME='nom_site' VALUE=\"".entites_html($nom_site)."\" SIZE='40'><BR>";

	$lien_url="http://";
	echo _T('info_url')."<BR>";
	echo "<INPUT TYPE='text' CLASS='forml' NAME='url_site' VALUE=\"".entites_html($url_site)."\" SIZE='40'><P>";
}

echo "<DIV ALIGN='right'><INPUT CLASS='fondo' TYPE='submit' VALUE='"._T('bouton_voir_message')."'></div>";
echo "</FORM>";

fin_page();
fin_cadre_formulaire();
}

?>
