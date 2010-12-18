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

// Recuperer le reglage des forums publics de l'article x
// http://doc.spip.org/@get_forums_publics
function get_forums_publics($id_article=0) {

	if ($id_article) {
		$obj = sql_fetsel("accepter_forum", "spip_articles", "id_article=$id_article");

		if ($obj) return $obj['accepter_forum'];
	} else { // dans ce contexte, inutile
		return substr($GLOBALS['meta']["forums_publics"],0,3);
	}
	return $GLOBALS['meta']["forums_publics"];
}

// Cree le formulaire de modification du reglage des forums de l'article
// http://doc.spip.org/@inc_regler_moderation_dist
function inc_regler_moderation_dist($id_article, $script, $args) {
	include_spip('inc/presentation');

	global $spip_lang_right;

	$statut_forum = get_forums_publics($id_article);
	$choix_forum = $GLOBALS['liste_des_forums'];
	$opt = '';
	foreach ($choix_forum as $desc => $val) {
		$opt .= "\n\t<option";
		if ($statut_forum == $val)
			$opt .= " selected='selected'";
		$opt .= " value='$val'>"._T($desc)."</option>";
	}


	$nb_forums = sql_countsel("spip_forum", "id_article=$id_article AND statut IN ('publie', 'off', 'prop', 'spam')");

	if ($nb_forums) {
		$res = '<!-- visible -->' // message pour l'appelant
		. icone_horizontale(
			_T('icone_suivi_forum', array('nb_forums' => $nb_forums)),
			generer_url_ecrire("articles_forum","id_article=$id_article"),
			"suivi-forum-24.gif",
			"",
			false
		);
	} else
		$res = '';

	$res .= "\n\t<label for='change_accepter_forum'>"
	. _T('info_fonctionnement_forum') ."</label>"
	. "\n\t<select name='change_accepter_forum' id='change_accepter_forum'
		class='spip_xx-small'
		onchange=\"findObj_forcer('valider_regler_moderation_$id_article').style.visibility='visible';\"
		>"
	. $opt
	."\n\t</select><br />\n";

	$atts = " style='float: $spip_lang_right' id='valider_regler_moderation_$id_article' class='visible_au_chargement'";

	$res = ajax_action_post('regler_moderation', $id_article, $script, $args, $res,_T('bouton_changer'), $atts);

	return ajax_action_greffe("regler_moderation", $id_article, $res);
}
?>
