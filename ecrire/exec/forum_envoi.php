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

include_spip('inc/presentation');
include_spip('inc/barre');
include_spip('inc/forum');
include_spip('base/abstract_sql');

// http://doc.spip.org/@exec_forum_envoi_dist
function exec_forum_envoi_dist()
{
global
  $url,
  $connect_id_auteur,
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

if ($valider_forum AND ($statut!='')) {
	$titre_message = corriger_caracteres($titre_message);
	$texte = corriger_caracteres($texte);

	spip_abstract_insert('spip_forum', "(titre, texte, date_heure, nom_site, url_site, statut, id_auteur,	auteur, email_auteur, id_rubrique, id_parent, id_article, id_breve,	id_message, id_syndic)", "(" . spip_abstract_quote($titre_message) . ", " . spip_abstract_quote($texte) . ", NOW(), " . spip_abstract_quote($nom_site) . ", " . spip_abstract_quote($url_site) . ", " . spip_abstract_quote($statut) . ", $connect_id_auteur, " . spip_abstract_quote($GLOBALS['auteur_session']['nom']) . ", " . spip_abstract_quote($GLOBALS['auteur_session']['email']) . ",	'$id_rubrique', '$id_parent', '$id_article', '$id_breve',	'$id_message', '$id_syndic')");

	calculer_threads();

	if ($id_message > 0) {
		spip_query("UPDATE spip_auteurs_messages SET vu = 'non' WHERE id_message='$id_message'");

	}
	redirige_par_entete(rawurldecode($url));
}

if ($id_message) debut_page(_T('titre_page_forum_envoi'), "accueil", "messagerie");
else debut_page(_T('titre_page_forum_envoi'), "accueil");
debut_gauche();
debut_droite();

 $titre_parent = '';
if ($id_parent) {
	$result = spip_query("SELECT * FROM spip_forum WHERE id_forum=$id_parent");
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

    if ($titre_parent) {
	debut_cadre_forum("forum-interne-24.gif", false, "", typo($titre_parent));
	echo "<span class='arial2'>$date_heure_parent</span>";
	echo " ".typo($auteur_parent);

	if ($id_auteur_parent) {
		$bouton_auteur = charger_fonction('bouton_auteur', 'inc');
		$bouton = $bouton_auteur($id_auteur_parent);
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
 }


if ($modif_forum == "oui") {
	debut_cadre_thread_forum("", false, "", typo($titre_message));

	echo propre($texte);

	if (strlen($nom_site)>0) {
		echo "<p><a href='$url_site'>$nom_site</a>";
	}

	echo generer_url_post_ecrire('forum_envoi',"",'formulaire');
	echo "<p><div align='right'><input class='fondo' type='submit' name='valider_forum' value='",_T('bouton_envoyer_message'),"'></div>";

	fin_cadre_thread_forum();
	if ($titre_parent) {
		echo "</td></tr><tr>",
		  "<td width=10 valign='top' background='",
		  _DIR_IMG_PACK ,
		  "rien.gif'>",
		  http_img_pack("forum-droite$spip_lang_rtl.gif", $titre_parent, "width='10' height='13' border='0'"),
		  "</td>\n</tr></table>";
	}
}
else {
	echo generer_url_post_ecrire('forum_envoi',"",'formulaire');
}

echo "<div id='formulaire'>&nbsp;</div>";

debut_cadre_formulaire(($statut == 'privac') ? "" : 'background-color: #dddddd;');

echo "<TABLE BORDER=0 CELLPADDING=0 CELLSPACING=0 BACKGROUND='' WIDTH=\"100%\"><TR><TD>";
$forum_stat = $statut;
if ($forum_stat == "prive") $logo = "forum-interne-24.gif";
 else if ($forum_stat == "privadm") $logo = "forum-admin-24.gif";
 else if ($forum_stat == "privrac") $logo = "forum-interne-24.gif";
 else $logo = "forum-public-24.gif";

icone(_T('icone_retour'), rawurldecode($url), $logo);
echo "</TD>";

echo "<TD><IMG SRC='" . _DIR_IMG_PACK . "rien.gif' WIDTH=10 BORDER=0></td><TD WIDTH=\"100%\">";
echo "<B>"._T('info_titre')."</B><BR>";
$titre_message = entites_html($titre_message);
echo "<INPUT TYPE='text' CLASS='formo' NAME='titre_message' VALUE=\"$titre_message\" SIZE='40'><P>\n";
echo "</TD></TR></TABLE>";

if (!$modif_forum OR $modif_forum == "oui") {
	echo "<input type='hidden' name='modif_forum' value='oui'>\n";
}

 echo "<input type='hidden' name='url' value=\"$url\" />\n",
   "<input type='hidden' name='id_rubrique' value=\"", $id_rubrique, "\" />\n",
   "<input type='hidden' name='id_parent' value=\"", $id_parent, "\" />\n",
   "<input type='hidden' name='id_article' value=\"", $id_article, "\" />\n",
   "<input type='hidden' name='id_breve' value=\"", $id_breve, "\" />\n",
   "<input type='hidden' name='id_message' value=\"", $id_message, "\" />\n",
   "<input type='hidden' name='id_syndic' value=\"", $id_syndic, "\" />\n",
   "<input type='hidden' name='statut' value=\"", $statut, "\" />\n",
   "<p><b>",
   _T('info_texte_message'),
   "</b><br />\n",
   _T('info_creation_paragraphe'),
   "<br />\n",
   afficher_barre('document.formulaire.texte', true),
   "<textarea name='texte' ",
   $GLOBALS['browser_caret'],
   " rows='15' class='formo' cols='40' wrap=soft>",
   entites_html($texte),
   "</textarea><p>\n";

if ($statut != 'perso' AND $options == "avancees") {
	echo "<B>"._T('info_lien_hypertexte')."</B><BR>";
	echo _T('texte_lien_hypertexte')."<BR>";
	echo _T('texte_titre_02')."<BR>";
	echo "<INPUT TYPE='text' CLASS='forml' NAME='nom_site' VALUE=\"".entites_html($nom_site)."\" SIZE='40'><BR>";

	$lien_url="http://";
	echo _T('info_url')."<BR>";
	echo "<INPUT TYPE='text' CLASS='forml' NAME='url_site' VALUE=\"".entites_html($url_site)."\" SIZE='40'><P>";
}

echo "<div align='right'><input class='fondo' type='submit' value='"._T('bouton_voir_message')."'></div>",
	 "</form>";

echo fin_page();
fin_cadre_formulaire();
}

?>
