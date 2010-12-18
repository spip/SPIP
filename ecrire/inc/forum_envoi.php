<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/presentation');

// http://doc.spip.org/@inc_forum_envoi_dist
function inc_forum_envoi_dist($id, $id_parent, $script, $statut, $titre_message, $texte, $modif_forum, $nom_site, $url_site) {

	$id_parent= intval($id_parent);
	$id = intval($id);
	$row = sql_fetsel("*", "spip_forum", "id_forum=$id_parent");

	// s'il existe, afficher le message auquel on repond
	if (!$row)
		$row = array('titre' =>'', 'texte' =>'', 'id_forum' =>0);
	else $row = forum_envoi_parent($row);

	// apres le premier appel, afficher la saisie precedente
	if ($modif_forum == "oui") {
	  $row['texte'] = forum_envoi_entete($row['texte'], $row['titre'], $texte, $titre_message, $nom_site, $url_site);
	}

	// determiner le retour et l'action

	list($script,$retour) = preg_split(',\?,', urldecode($script));
	if (function_exists($f = 'forum_envoi_' . $script))
		list($table, $objet, $titre, $num, $retour, $id, $corps) =
			$f($id, $row, $retour);
	else $table = $objet = $titre = $num = $retour = $corps ='';

	if (!$titre_message) {
		if ($table) {
			$titre_message = sql_getfetsel($titre, "spip_$table", "$objet=$id");
		} else 	$titre_message = _T('texte_nouveau_message');
	}

	$h = _AJAX ? '' : generer_url_ecrire($script, $retour);

	$form = forum_envoi_formulaire($id, $h, $statut, $texte, $titre_message, $nom_site, $url_site);

	return forum_envoi_form($id, $row, $script, $statut, $titre, $row['texte'] . $corps, $form, $objet, $retour);
}

// http://doc.spip.org/@forum_envoi_form
function forum_envoi_form($id, $row, $script, $statut, $titre, $corps, $form, $objet, $args) {

	$id_parent = $row['id_forum'];
	$cat = $id . '/'
	  . $id_parent . '/'
	  . $statut . '/'
	  . $script . '/'
	  . $objet;

	// si reponse directe a l'article etc, reincruster tout le forum
	// sinon incruster juste le fil
	$args .= "#poster_forum_prive" 
	 . (!$id_parent ? '' : ("-" . $row['id_thread']));

	$corps .= "\n<div>&nbsp;</div>" .
	  debut_cadre_formulaire(($statut == 'privac') ? "" : 'background-color: #dddddd;', true) .
$form
	. "<div style='text-align: right'>"
	. "<input type='submit' value='"
	. _T('bouton_voir_message')
	. "' /></div>"
	. fin_cadre_formulaire(true);

	if (_AJAX)
	  return ajax_action_post('poster_forum_prive',$cat, $script, $args, $corps, array(),'','', "&id=$id&id_parent=$id_parent&statut=$statut");
	else return redirige_action_auteur('poster_forum_prive',$cat, $script, $args, $corps, "\nmethod='post' id='formulaire'");
}

// Chercher a quoi on repond pour l'afficher au debut

// http://doc.spip.org/@forum_envoi_parent
function forum_envoi_parent($row)
{
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
function forum_envoi_formulaire($id, $retour, $statut, $texte, $titre, $nom_site, $url_site)
{

	return (!$retour ? '' : "<div class='entete-formulaire'>".icone(_T('icone_retour'), $retour, forum_logo($statut), '','', false)."</div>")
		. "<div class='formulaire_spip formulaire_editer formulaire_editer_message_forum'>"
		."<ul>"
		."<li class='obligatoire'><label for='titre_message'>"
	  	. _T('info_titre')
	  	."</label>"
	  	. "<input id='titre_message' name='titre_message' type='text' value=\""
	  	. entites_html($titre)
	  	. "\"   class='text' />\n"
		."</li>"
		."<li class='haut'><label for='texte' >"
	  	. _T('info_texte_message')
	  	."</label>"
			."<textarea name='texte' id='texte' rows='13' class='textarea'>\n"
			. $texte
			. "</textarea>"
			."<input type='hidden' name='modif_forum' value='oui' />\n"
	  	."</li>"		
	  . (!($statut != 'perso')
		   ? ''
		   : (
				"<li class='fieldset'><fieldset>"
				."<h3 class='legend'>"._T('info_lien_hypertexte')."</h3>"
				."<p class='explication'>". _T('texte_lien_hypertexte')."</p>\n"
				."<ul>"
				. "<li><label for='nom_site'>"
				. _T('form_prop_nom_site')
				. "</label>"
				. "<input type='text' id='nom_site' name='nom_site' value=\""
					. entites_html($nom_site)
					. "\" class='text' /></li>"
				. "<li><label for='url_site'>"
				. _T('info_url')
				."</label>"
				. "<input type='text' id='url_site' name='url_site' value=\"".entites_html($url_site)
				. "\" class='text' /></li>"
				."</ul></fieldset></li>"
				))
		."</ul></div>";
	  
}

// http://doc.spip.org/@forum_envoi_entete
function forum_envoi_entete($parent, $titre_parent, $texte, $titre, $nom_site, $url_site)
{
	global $spip_lang_rtl;

	return "\n<br /><br /><table width='100%' cellpadding='0' cellspacing='0' border='0'>"
		. (!$parent ? '' : "<tr><td colspan='2'>$parent</td></tr>")
		. "<tr>"
		. (!$parent ? "<td colsan='2'"
			: (" <td style='width: 10px; background-image: url("
			   . chemin_image('forum-vert.gif')
			   . ");'>"
			   . http_img_pack('rien.gif', ' ', " style='width: 0px; height: 0px; border: 0px;'")
			   . "</td>\n<td "))
		.  " style='width: 100%' valign='top' rowspan='2'>"
		.  debut_cadre_thread_forum("", true, "", typo($titre))
		. propre($texte)
		. (!$nom_site ? '' : "<p><a href='$url_site'>$nom_site</a></p>")
		. "\n<div style='text-align: right'><input type='submit' name='valider_forum' value='"
		. _T('bouton_envoyer_message')
		. "'\nonclick='AjaxNamedSubmit(this)' /></div>"
		. fin_cadre_thread_forum(true)
		. "</td>"
		. "</tr>\n"
		. (!$parent ? ''
			: ("<tr><td valign='top' style='width: 10px; background-image: url("
			  . chemin_image('rien.gif')
			  . ");'>"
			  .  http_img_pack("forum-droite$spip_lang_rtl.gif",
					    '&nbsp;', 
					   " style='width: 10px; height: 13px'")
		      . "</td>\n</tr>"))
		. "</table>";
}
?>
