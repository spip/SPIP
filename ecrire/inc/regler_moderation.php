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

// Recuperer le reglage des forums publics de l'article x
// http://doc.spip.org/@get_forums_publics
function get_forums_publics($id_article=0) {

	if ($id_article) {
		$res = spip_query("SELECT accepter_forum FROM spip_articles WHERE id_article=$id_article");

		if ($obj = sql_fetch($res))
			return $obj['accepter_forum'];
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

	$nb_forums = sql_fetch(spip_query("SELECT COUNT(*) AS count FROM spip_forum WHERE id_article=$id_article AND statut IN ('publie', 'off', 'prop')"));
	$nb_forums = $nb_forums['count'];

	if ($nb_forums) {
		$r = '<!-- visible -->' // message pour l'appelant
		. icone_horizontale(
			_T('icone_suivi_forum', array('nb_forums' => $nb_forums)),
			generer_url_ecrire("articles_forum","id_article=$id_article"),
			"suivi-forum-24.gif",
			"",
			false
		);
	} else
		$r = '';

	$r .= "\n\t"
	. _T('info_fonctionnement_forum')
	. "\n\t<select name='change_accepter_forum'
		class='fondl spip_xx-small'
		onchange=\"findObj_forcer('valider_regler_moderation_$id_article').style.visibility='visible';\"
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
	$r .= "\n\t</select><br />\n";

	$atts = " style='float: $spip_lang_right' id='valider_regler_moderation_$id_article' class='fondo visible_au_chargement spip_xx-small'";

	$r = ajax_action_post('regler_moderation', $id_article, $script, $args, $r,_T('bouton_changer'), $atts);

	return ajax_action_greffe("regler_moderation", $id_article, $r);
}
?>
