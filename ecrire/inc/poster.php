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

// Recuperer le reglage des forums publics de l'article x
// http://doc.spip.org/@get_forums_publics
function get_forums_publics($id_article=0) {

	if ($id_article) {
		$res = spip_query("SELECT accepter_forum FROM spip_articles WHERE id_article=$id_article");

		if ($obj = spip_fetch_array($res))
			return $obj['accepter_forum'];
	} else { // dans ce contexte, inutile
		return substr($GLOBALS['meta']["forums_publics"],0,3);
	}
	return $GLOBALS['meta']["forums_publics"];
}

// Cree le formulaire de modification du reglage des forums de l'article
// http://doc.spip.org/@formulaire_poster
function inc_poster_dist($id_article, $script, $args, $flag=false) {

	global $spip_lang_right, $options, $connect_statut;

	if (!($options == "avancees" && $connect_statut=='0minirezo' && $flag))
	  return '';

	$statut_forum = get_forums_publics($id_article);

	$nb_forums = spip_fetch_array(spip_query("SELECT COUNT(*) AS count FROM spip_forum WHERE id_article=$id_article 	AND statut IN ('publie', 'off', 'prop')"));

	if ($nb_forums) {
		$r = icone_horizontale(_T('icone_suivi_forum', array('nb_forums' => $nb_forums)), generer_url_ecrire("articles_forum","id_article=$id_article"), "suivi-forum-24.gif", "", false);
	} else 	$r = '';

	$r = "\n\t"
	. _T('info_fonctionnement_forum')
	. "\n\t<select name='change_accepter_forum'
		class='fondl'
		style='font-size:10px;'
		onchange=\"findObj_forcer('valider_poster_$id_article').style.visibility='visible';\"
		>";

	foreach (array(
		'pos'=>_T('bouton_radio_modere_posteriori'),
		'pri'=>_T('bouton_radio_modere_priori'),
		'abo'=>_T('bouton_radio_modere_abonnement'),
		'non'=>_T('info_pas_de_forum'))
		as $val => $desc) {
		$r .= "\n\t<option";
		if ($statut_forum == $val)
			$r .= " selected='selected'";
		$r .= " value='$val'>".$desc."</option>";
	}
	$r .= "\n\t</select>\n";

	$r .= "<div align='$spip_lang_right' id='valider_poster_$id_article'"
	. " class='visible_au_chargement'"
	. ">\n\t<input type='submit' class='fondo' style='font-size:10px' value='"
	. _T('bouton_changer')
	. "' /></div>\n";

	$r = ajax_action_auteur('poster', $id_article, $script, $args, $r);

	return ajax_action_greffe("poster-$id_article", $r);
}
?>
