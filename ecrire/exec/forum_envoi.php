<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
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
		    _request('script'),
		    _request('statut'),
		    _request('titre_message'),
		    _request('texte'),
		    _request('modif_forum'),
		    _request('nom_site'),
		    _request('url_site'));
}

// http://doc.spip.org/@forum_envoi
function forum_envoi(
		     $id,
		     $id_parent,
		     $script,
		     $statut,
		     $titre_message,
		     $texte,
		     $modif_forum,
		     $nom_site,
		     $url_site)
{
	// trouver a quoi on repond
	$row = forum_envoi_parent($id_parent);

	// apres le premier appel, afficher la saisie precedente
	if ($modif_forum == "oui") {
		$row['texte'] = forum_envoi_entete($row, $texte, $titre_message, $nom_site, $url_site);
	}

	// determiner le retour et l'action
	list($script,$retour) = split('\?', urldecode($script));
	if (function_exists($f = 'forum_envoi_' . $script))
	  list($table, $objet, $titre, $num, $retour, $id, $corps) =
	    $f($id, $row, $retour);
	else $table = $objet = $titre = $num = $retour = $corps ='';

	if (!$titre_message) {
		if ($table) {
			$q = spip_query("SELECT $titre AS titre FROM spip_$table WHERE $objet=$id");
			$q = spip_fetch_array($q);
			$titre_message = $q['titre'];
		} else 	$titre_message = _T('texte_nouveau_message');
	}

	// construire le formulaire de saisie
	$form =  forum_envoi_formulaire($id, generer_url_ecrire($script, $retour), $statut, $texte, $titre_message, $nom_site, $url_site);

	// afficher le tout
	forum_envoi_affiche($id, $id_parent, $script, $statut, $titre_message, $row['texte'] . $corps, $id_message, $form, $num, $objet, $retour);
}

// Chercher a quoi on repond pour l'afficher au debut

function forum_envoi_parent($id)
{
	$r = spip_query("SELECT * FROM spip_forum WHERE id_forum=" . _q($id));
	if (!$row = spip_fetch_array($r))
		return array('titre' =>'', 'texte' =>'', 'id_message' =>'');

	$titre = typo($row['titre']);
	$texte = $row['texte'];
	$auteur = $row['auteur'];
	$id_auteur = $row['id_auteur'];
	$date_heure = $row['date_heure'];
	$nom_site = $row['nom_site'];
	$url_site = $row['url_site'];
	
	$parent = debut_cadre_forum("forum-interne-24.gif", true, "", $titre)
	  . "<span class='arial2'>$date_heure</span> ";

	if ($id_auteur) {
		$formater_auteur = charger_fonction('formater_auteur', 'inc');
		list($s, $mail, $nom, $w, $p) = $formater_auteur($id_auteur);
		$parent .="$mail&nbsp;$nom";
	} else 	$parent .=" " . typo($auteur);

	$parent .= justifier(propre($texte));

	if (strlen($url_site) > 10 AND $nom_site) {
		$parent .="<p style='text-align: left; font-weight: bold;' class='verdana1'><a href='$url_site'>$nom_site</a></p>";
	}
	$parent .= fin_cadre_forum(true);

	$row['texte'] = $parent;
	
	return $row;
}

function forum_envoi_affiche($id, $id_parent, $script, $statut, $titre, $corps, $id_message, $form, $num, $objet, $retour) {

	$cat = intval($id) . '/'
	  . intval($id_parent) . '/'
	  . $statut . '/'
	  . $script . '/'
	  . $objet;

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('texte_nouveau_message'), "accueil", $id_message ? "messagerie" : "accueil");
	debut_gauche();
	debut_droite();
	gros_titre(($num ? "$num $id<br />" :'') . $titre);

	$corps .= "\n<div>&nbsp;</div>"
	.  debut_cadre_formulaire(($statut == 'privac') ? "" : 'background-color: #dddddd;', true)
	. $form
	. "<div style='text-align: right'><input class='fondo' type='submit' value='"
	. _T('bouton_voir_message')
	. "' /></div>"
	. fin_cadre_formulaire(true);

	echo "\n<div>&nbsp;</div>"
	  . redirige_action_auteur('poster_forum_prive',$cat, $script, $retour, $corps, "\nmethod='post' id='formulaire'")
	  . fin_gauche()
	  . fin_page();
}

// http://doc.spip.org/@forum_envoi_articles
function forum_envoi_articles($id, $row, $retour) {
	$table ='articles';
	$objet = 'id_article';
	$titre = 'titre';
	$num = _T('info_numero_article');
	if (!$id)  $id = $row['id_article'];
	if (!$retour) $retour = "$objet=$id"; 
	return array($table, $objet, $titre, $num, $retour, $id, '');
}

// http://doc.spip.org/@forum_envoi_breves_voir
function forum_envoi_breves_voir($id, $row, $retour) {
	$table = 'breves';
	$objet = 'id_breve';
	$titre = 'titre';
	$num = _T('info_gauche_numero_breve');
	if (!$id)  $id = $row['id_breve'];
	if (!$retour) $retour = "$objet=$id"; 
	return array($table, $objet, $titre, $num, $retour, $id, '');
}

// http://doc.spip.org/@forum_envoi_message
function forum_envoi_message($id, $row, $retour) {
	$table = 'messages';
	$objet = 'id_message';
	$titre = 'titre';
	$num = _T('message') . ' ' ._T('info_numero_abbreviation');
	if (!$id)  $id = $row['id_message'];
	if (!$retour) $retour = "$objet=$id"; 
	return array($table, $objet, $titre, $num, $retour, $id, '');
}

// http://doc.spip.org/@forum_envoi_naviguer
function forum_envoi_naviguer($id, $row, $retour) {
	$table = 'rubriques';
	$objet = 'id_rubrique';
	$titre = 'titre';
	$num = _T('titre_numero_rubrique');
	if (!$id)  $id = $row['id_rubrique'];
	if (!$retour) $retour = "$objet=$id"; 
	return array($table, $objet, $titre, $num, $retour, $id, '');
}

// http://doc.spip.org/@forum_envoi_sites
function forum_envoi_sites($id, $row, $retour) {
	$table = 'syndic';
	$objet = 'id_syndic';
	$titre = 'nom_site';
	$num = _T('titre_site_numero');
	if (!$id)  $id = $row['id_syndic'];
	if (!$retour) $retour = "$objet=$id"; 
	return array($table, $objet, $titre, $num, $retour, $id, '');
}

// http://doc.spip.org/@forum_envoi_forum
function forum_envoi_forum($id, $row, $retour) {

	$table = $titre = $num = '';
	$id = 0; // pour forcer la creation dans action/poster
	$objet = 'id_forum';
	$debut = intval(_request('debut'));
	$retour = ("debut=$debut"); 
	$corps .= "<input type='hidden' name='debut' value='$debut' />";
	return array($table, $objet, $titre, $num, $retour, $id, $corps);
}

// http://doc.spip.org/@forum_envoi_forum_admin
function forum_envoi_forum_admin($id, $row, $retour) {
	return forum_envoi_forum($id, $row, $retour);
}

// http://doc.spip.org/@forum_envoi_formulaire
function forum_envoi_formulaire($id, $retour, $statut, $texte, $titre_page, $nom_site, $url_site)
{
	if ($statut == "prive") $logo = "forum-interne-24.gif";
	else if ($statut == "privadm") $logo = "forum-admin-24.gif";
	else if ($statut == "privrac") $logo = "forum-interne-24.gif";
	else $logo = "forum-public-24.gif";

	return "\n<table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td>"
	  . icone(_T('icone_retour'), $retour, $logo, '','', false)
	  ."</td><td style='width: 100%'>"
	  ."<b><label for='titre_message'>"
	  . _T('info_titre')
	  ."</label></b><br />\n"
	  . "<input id='titre_message' name='titre_message' type='text' value=\""
	  . entites_html($titre_page)
	  . "\" size='40'  class='formo' />\n"
	  . "</td></tr></table><br />"
	  .
	  "<b><label for='texte'>" .
	  _T('info_texte_message') .
	  "</label></b><br />\n" .
	  _T('info_creation_paragraphe') .
	  "<br />\n" .
	  afficher_textarea_barre($texte, true) .
	  "<input type='hidden' name='modif_forum' value='oui' />\n" .
	  (!($statut != 'perso')
	   ? ''
	   : ("<b>"._T('info_lien_hypertexte')."</b><br />\n"
		. _T('texte_lien_hypertexte')."<br />\n"
		. "<label for='nom_site'>"
		. _T('form_prop_nom_site')
		. "</label><br />\n"
		. "<input type='text' id='nom_site' name='nom_site' value=\""
	        . entites_html($nom_site)
	        . "\" size='40' class='forml' />"
		. "<label for='url_site'>"
		. _T('info_url')
		."</label><br /><br />\n"
		. "<input type='text' class='forml' id='url_site' name='url_site' value=\"".entites_html($url_site)
		. "\" size='40' />"	      ));
}

// http://doc.spip.org/@forum_envoi_entete
function forum_envoi_entete($row, $texte, $titre_texte, $nom_site, $url_site)
{
	global $spip_lang_rtl;

	$parent = $row['texte'];
	$titre_parent = $row['titre'];
	return "\n<table width='100%' cellpadding='0' cellspacing='0' border='0'>"
		. (!$parent ? '' : "<tr><td colspan='2'>$parent</td></tr>")
		. "<tr>"
		. (!$parent ? "<td colsan='2'"
			: (" <td style='width: 10px; background-image: url("
			   . _DIR_IMG_PACK
			   . "forum-vert.gif" 
			   . ");'>"
			   . http_img_pack('rien.gif', ' ', " style='width: 0px; height: 0px; border: 0px;'")
			   . "</td>\n<td "))
		.  " style='width: 100%' valign='top' rowspan='2'>"
		.  debut_cadre_thread_forum("", true, "", typo($titre_texte))
		. propre($texte)
		. (!$nom_site ? '' : "<p><a href='$url_site'>$nom_site</a></p>")
		. "\n<div style='text-align: right'><input class='fondo' type='submit' name='valider_forum' value='"
		. _T('bouton_envoyer_message')
		. "' /></div>"
		. fin_cadre_thread_forum(true)
		. "</td>"
		. "</tr>\n"
		. (!$parent ? ''
			: ("<tr><td valign='top' style='width: 10px; background-image: url("
			  . _DIR_IMG_PACK
			  . "rien.gif);'>"
			  .  http_img_pack("forum-droite$spip_lang_rtl.gif", $titre_parent, " style='width: 10px; height: 13px'")
		      . "</td>\n</tr>"))
		. "</table>";
}
?>
