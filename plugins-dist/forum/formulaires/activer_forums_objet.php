<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// Recuperer le reglage des forums publics de l'article x
// http://doc.spip.org/@get_forums_publics
function get_forums_publics($id_objet=0, $objet='article') {

	if ($objet=='article' AND $id_objet) {
		$obj = Sql::fetsel("accepter_forum", "spip_articles", "id_article=".intval($id_objet));

		if ($obj) return $obj['accepter_forum'];
	} else { // dans ce contexte, inutile
		return substr($GLOBALS['meta']["forums_publics"],0,3);
	}
	return $GLOBALS['meta']["forums_publics"];
}

/**
 * Charger
 *
 * @param int $id_article
 * @return array
 */
function formulaires_activer_forums_objet_charger_dist($id_objet, $objet='article')
{
	if (!autoriser('modererforum', $objet, $id_objet))
		return false;

	include_spip('inc/presentation');
	$nb_forums = Sql::countsel("spip_forum", "objet=".Sql::quote($objet)." AND id_objet=".intval($id_objet)." AND statut IN ('publie', 'off', 'prop', 'spam')");
	$editable = ($objet=='article')?true:false;
	if (!$editable AND !$nb_forums)
		return false;

	return array(
		'editable' => $editable,
		'objet' => $objet,
		'id_objet' => $id_objet,
		'accepter_forum' => get_forums_publics($id_objet, $objet),
		'_suivi_forums' => $nb_forums?_T('forum:icone_suivi_forum', array('nb_forums' => $nb_forums)):"",
	);
	
}

/**
 * Traiter
 *
 * @param int $id_objet
 * @param string $objet
 * @return array
 */
function formulaires_activer_forums_objet_traiter_dist($id_objet, $objet='article')
{
	include_spip('inc/autoriser');
	if ($objet=='article' AND autoriser('modererforum', $objet, $id_objet)) {
		$statut = _request('accepter_forum');
		Sql::updateq("spip_articles", array("accepter_forum" => $statut), "id_article=". intval($id_objet));
		
		if ($statut == 'abo') {
			ecrire_meta('accepter_visiteurs', 'oui');
		}
		include_spip('inc/invalideur');
		suivre_invalideur("id='$objet/$id_objet'");
	}
		
	return array('message_ok'=>_T('config_info_enregistree'),'editable'=>true);
}

?>