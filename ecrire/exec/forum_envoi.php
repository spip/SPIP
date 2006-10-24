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

// http://doc.spip.org/@exec_forum_envoi_dist
function exec_forum_envoi_dist()
{
	forum_envoi(  
		    intval(_request('id')),
		    intval(_request('id_parent')),
		    _request('modif_forum'),
		    _request('nom_site'),
		    _request('statut'),
		    _request('texte'),
		    _request('titre_message'),
		    _request('url_site'),
		    _request('script'),
		    _request('valider_forum'));
}

function forum_envoi(  
		     $id,
		     $id_parent,
		     $modif_forum,
		     $nom_site,
		     $statut,
		     $texte,
		     $titre_message,
		     $url_site,
		     $script,
		     $valider_forum)
{
	global     $options, $spip_lang_rtl;

	if ($id_parent) {
		$result = spip_query("SELECT * FROM spip_forum WHERE id_forum=$id_parent");
		if ($row = spip_fetch_array($result)) {
			$id_article = $row['id_article'];
			$id_breve = $row['id_breve'];
			$id_rubrique = $row['id_rubrique'];
			$id_message = $row['id_message'];
			$id_syndic = $row['id_syndic'];
			$statut = $row['statut'];
			$titre_parent = typo($row['titre']);
			$texte_parent = $row['texte'];
			$auteur_parent = $row['auteur'];
			$id_auteur_parent = $row['id_auteur'];
			$date_heure_parent = $row['date_heure'];
			$nom_site_parent = $row['nom_site'];
			$url_site_parent = $row['url_site'];

			$parent = debut_cadre_forum("forum-interne-24.gif", true, "", $titre_parent)
			. "<span class='arial2'>$date_heure_parent</span> ";

			if ($id_auteur_parent) {
				$formater_auteur = charger_fonction('formater_auteur', 'inc');
				list($s, $mail, $nom, $w, $p) = $formater_auteur($id_auteur_parent);
				$parent .="$mail&nbsp;$nom";
			} else 	$parent .=" " . typo($auteur_parent);

			$parent .= justifier(propre($texte_parent));

			if (strlen($url_site_parent) > 10 AND $nom_site_parent) {
				$parent .="<p align='left'><font face='Verdana,Arial,Sans,sans-serif'><b><a href='$url_site_parent'>$nom_site_parent</a></b></font></p>";
			}
			$parent .= fin_cadre_forum(true);
		}

	} else $parent = $titre_parent = '';

	if ($script == 'articles') {
	  $table ='articles';
	  $objet = 'id_article';
	  $titre = 'titre';
	  $num = _T('info_numero_article');
	  if (!$id)  $id = $id_article;
	} elseif ($script == 'breves_voir') {
	  $table = 'breves';
	  $objet = 'id_breve';
	  $titre = 'titre';
	  $num = _T('info_gauche_numero_breve');
	  if (!$id)  $id = $id_breve;
	} elseif ($script == 'message') {
	  $table = 'messages';
	  $objet = 'id_message';
	  $titre = 'titre';
	  $num = _T('message') . ' ' ._T('info_numero_abbreviation');
	  if (!$id)  $id = $id_message;
	} elseif ($script == 'rubriques') {
	  $table = 'rubriques';
	  $objet = 'id_rubrique';
	  $titre = 'titre';
	  $num = _T('titre_numero_rubrique');
	  if (!$id)  $id = $id_rubrique;
	} elseif ($script == 'sites') {
	  $table = 'syndic';
	  $objet = 'id_syndic';
	  $titre = 'nom_site';
	  $num = _T('titre_site_numero');
	  if (!$id)  $id = $id_syndic;
	} else {
	  $table = 'forum';
	  $objet = 'id_forum';
	  $titre = 'titre'; 
	  $id = 0;
	  $titre_page = filtrer_entites(_T('texte_nouveau_message'));
	  $num = '';
	}

	if ($num) {
		$q = spip_query("SELECT $titre AS titre FROM spip_$table WHERE $objet=$id");
		$q = spip_fetch_array($q);
		$titre_page = $q['titre'];
		$num  = "<br />("
		  . str_replace(':','',strtolower($num))
		  . $id
		  . ", "
		  ._T('info_forum_interne') . ')';
	}

	if ($id_message) debut_page(_T('titre_page_forum_envoi'), "accueil", "messagerie");
	else debut_page(_T('titre_page_forum_envoi'), "accueil");
	debut_gauche();
	debut_droite();
	gros_titre($titre_page . $num);

	if ($statut == "prive") $logo = "forum-interne-24.gif";
	else if ($statut == "privadm") $logo = "forum-admin-24.gif";
	else if ($statut == "privrac") $logo = "forum-interne-24.gif";
	else $logo = "forum-public-24.gif";

	$corps = "\n<table border='0' cellpadding='0' cellspacing='0' background='' width='100%'><tr><td>"
	  . icone(_T('icone_retour'), generer_url_ecrire($script, "$objet=$id"), $logo, '','', false)
	  ."</td>"
	  ."\n<td><img src='"
	  . _DIR_IMG_PACK
	  . "rien.gif' width='10' border='0' /></td><td width=\"100%\">"
	  ."<b>"._T('info_titre')."</b><br />"
	  . "<input type='text' class='formo' name='titre_message' value=\""
	  . entites_html($titre_message ? $titre_message : $titre_page)
	  . "\" size='40' />\n"
	  . "</td></tr></table>"
	  .
	  "<p><b>" .
	  _T('info_texte_message') .
	  "</b><br />\n" .
	  _T('info_creation_paragraphe') .
	  "<br />\n" .
	  afficher_barre('document.formulaire.texte', true) .
	  "<textarea name='texte' " .
	  $GLOBALS['browser_caret'] .
	  " rows='15' class='formo' cols='40' wrap='soft'>" .
	  entites_html($texte) .
	  "</textarea></p><p>\n";

	if (!$modif_forum OR $modif_forum == "oui") {
		$corps .="<input type='hidden' name='modif_forum' value='oui' />\n";
 }
	if ($statut != 'perso' AND $options == "avancees") {
		$corps .="<b>"._T('info_lien_hypertexte')."</b><br />\n"
		  . _T('texte_lien_hypertexte')."<br />\n"
		  . _T('texte_titre_02')."<br />\n"
		  . "<input type='text' class='forml' name='nom_site' value=\"".entites_html($nom_site)."\" size='40' /><br />\n"
		  . _T('info_url')
		  ."<br />\n"
		  . "<input type='text' class='forml' name='url_site' value=\"".entites_html($url_site)
		  . "\" size='40' /></p>";
	}

	$corps = debut_cadre_formulaire(($statut == 'privac') ? "" : 'background-color: #dddddd;', true)
	. $corps
	. "<div align='right'><input class='fondo' type='submit' value='"
	. _T('bouton_voir_message')
	. "' /></div>"
	. fin_cadre_formulaire(true);

	if ($modif_forum == "oui") {
		$corps = 
		 
		 "\n<table width='100%' cellpadding='0' cellspacing='0' border='0'>"
		. (!$parent ? '' : "<tr><td colspan='2'>$parent</td></tr>")
		. "\n<tr>"
		. (!$parent ? "<td colsan='2'"
			: (" <td width='10' height='13' valign='top' background='"
			   . _DIR_IMG_PACK
			   . "forum-vert.gif'" 
			   . ">"
			   . http_img_pack('rien.gif', ' ', "width='10' height='13' border='0'")
			   . "</td>\n<td "))
		.  " width='100%' valign='top' rowspan='2'>"
		.  debut_cadre_thread_forum("", true, "", typo($titre_message))
		. propre($texte)
		. (!$nom_site ? '' : "<p><a href='$url_site'>$nom_site</a></p>")
		. "\n<div align='right'><input class='fondo' type='submit' name='valider_forum' value='"
		. _T('bouton_envoyer_message')
		. "' /></div>"
		. fin_cadre_thread_forum(true)
		. "</td>"
		. "</tr>\n"
		. (!$parent ? ''
			: ("<tr><td width='10' valign='top' background='"
			  . _DIR_IMG_PACK
			  . "rien.gif'>"
			  .  http_img_pack("forum-droite$spip_lang_rtl.gif", $titre_parent, "width='10' height='13' border='0'")
		      . "</td>\n</tr>"))
		. "</table>"
		. "\n<div>&nbsp;</div>"
		. $corps;
		$parent = '';
	}

	$cat = intval($id) . '/'
	  . intval($id_parent) . '/'
	  . $statut . '/'
	  . $script . '/'
	  . $objet;

	echo  $parent,
	  "\n<div>&nbsp;</div>"
	  . redirige_action_auteur('editer_forum',$cat, $script, "$objet=$id", $corps, "")
	  .  "<a id='formulaire'></a>"
	  . fin_page();
}

?>
