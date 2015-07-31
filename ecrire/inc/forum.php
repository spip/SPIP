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

// tous les boutons de controle d'un forum
// nb : les forums prives (privrac ou prive), une fois effaces
// (privoff), ne sont pas revalidables ; le forum d'admin (privadm)
// n'est pas effacable

// http://doc.spip.org/@boutons_controle_forum
function boutons_controle_forum($id_forum, $forum_stat, $forum_id_auteur=0, $ref, $forum_ip) {
	$controle = '';


	// selection du logo et des boutons correspondant a l'etat du forum
	switch ($forum_stat) {
		# forum sous un article dans l'espace prive
		case "prive":
			$logo = "forum-interne-24.gif";
			$valider = false;
			$valider_repondre = false;
			$supprimer = 'privoff';
			break;
		# forum des administrateurs
		case "privadmin":
			$logo = "forum-admin-24.gif";
			$valider = false;
			$valider_repondre = false;
			$supprimer = false;
			break;
		# forum de l'espace prive, supprime (non revalidable,
		# d'ailleurs on ne sait plus a quel type de forum il appartenait)
		case "privoff":
			$logo = "forum-interne-24.gif";
			$valider = false;
			$valider_repondre = false;
			$supprimer = false;
			break;
		# forum general de l'espace prive
		case "privrac":
			$logo = "forum-interne-24.gif";
			$valider = false;
			$valider_repondre = false;
			$supprimer = 'privoff';
			break;

		# forum publie sur le site public
		case "publie":
			$logo = "forum-public-24.gif";
			$valider = false;
			$valider_repondre = false;
			$supprimer = 'off';
			break;
		# forum supprime sur le site public
		case "off":
			$logo = "forum-public-24.gif";
			$valider = 'publie';
			$valider_repondre = false;
			$supprimer = false;
			$controle = "<br /><FONT COLOR='red'><B>"._T('info_message_supprime')." $forum_ip</B></FONT>";
			if($forum_id_auteur)
				$controle .= " - <a href='" . generer_url_ecrire('auteurs_edit', "id_auteur=$forum_id_auteur") .
				  "'>" ._T('lien_voir_auteur'). "</a>";
			break;
		# forum propose (a moderer) sur le site public
		case "prop":
			$logo = "forum-public-24.gif";
			$valider = 'publie';
			$valider_repondre = true;
			$supprimer = 'off';
			break;
		default:
			return;
	}

	$lien = str_replace('&amp;', '&', self()) . "#id$id_forum";
	if ($supprimer)
	  $controle .= icone(_T('icone_supprimer_message'), generer_action_auteur('instituer_forum',"$id_forum-$supprimer", _DIR_RESTREINT_ABS . $lien),
			$logo,
			"supprimer.gif", 'right', 'non');

	if ($valider)
	  $controle .= icone(_T('icone_valider_message'), generer_action_auteur('instituer_forum',"$id_forum-$valider", _DIR_RESTREINT_ABS . $lien),
			$logo,
			"creer.gif", 'right', 'non');

	if ($valider_repondre) {
	  $dblret =  rawurlencode(_DIR_RESTREINT_ABS . $lien);
	  $controle .= icone(_T('icone_valider_message') . " &amp; " .   _T('lien_repondre_message'), generer_action_auteur('instituer_forum',"$id_forum-$valider", generer_url_public('forum', "$ref&id_forum=$id_forum&retour=$dblret", true)),
			     $logo,
			     "creer.gif", 'right', 'non');
	}

	return $controle;
}

// recuperer le critere SQL qui selectionne nos forums
// http://doc.spip.org/@critere_statut_controle_forum
function critere_statut_controle_forum($page, $id_rubrique=0) {
	if (!$id_rubrique) {
		$from = 'spip_forum AS F';
		$where = "";
		$and = "";
	} else {
		$from = 'spip_forum AS F, spip_articles AS A';
		$where = "A.id_secteur=$id_rubrique AND F.id_article=A.id_article";
		$and = ' AND ';
	}
   
	switch ($page) {
	case 'public':
		$and .= "F.statut IN ('publie', 'off', 'prop') AND F.texte!=''";
		break;
	case 'prop':
		$and .= "F.statut='prop'";
		break;
	case 'interne':
		$and .= "F.statut IN ('prive', 'privrac', 'privoff', 'privadm') AND F.texte!=''";
		break;
	case 'vide':
		$and .= "F.statut IN ('publie', 'off', 'prive', 'privrac', 'privoff', 'privadm') AND F.texte=''";
		break;
	default:
		$where = '0=1';
		$and ='';
		break;
	}
	return array($from, "$where$and");
}

// Index d'invalidation des forums
// http://doc.spip.org/@calcul_index_forum
function calcul_index_forum($id_article, $id_breve, $id_rubrique, $id_syndic) {
	if ($id_article) return 'a'.$id_article; 
	if ($id_breve) return 'b'.$id_breve;
	if ($id_rubrique) return 'r'.$id_rubrique;
	if ($id_syndic) return 's'.$id_syndic;
}

//
// Recalculer tous les threads
//
// http://doc.spip.org/@calculer_threads
function calculer_threads() {
	// fixer les id_thread des debuts de discussion
	spip_query("UPDATE spip_forum SET id_thread=id_forum WHERE id_parent=0");

	// reparer les messages qui n'ont pas l'id_secteur de leur parent
	do {
		$discussion = "0";
		$precedent = 0;
		$r = spip_query("SELECT fille.id_forum AS id,	maman.id_thread AS thread	FROM spip_forum AS fille, spip_forum AS maman	WHERE fille.id_parent = maman.id_forum AND fille.id_thread <> maman.id_thread	ORDER BY thread");
		while ($row = spip_fetch_array($r)) {
			if ($row['thread'] == $precedent)
				$discussion .= "," . $row['id'];
			else {
				if ($precedent)
					spip_query("UPDATE spip_forum SET id_thread=$precedent WHERE id_forum IN ($discussion)");
				$precedent = $row['thread'];
				$discussion = $row['id'];
			}
		}
		spip_query("UPDATE spip_forum SET id_thread=$precedent	WHERE id_forum IN ($discussion)");
	} while ($discussion != "0");
}

// Calculs des URLs des forums (pour l'espace public)
// http://doc.spip.org/@racine_forum
function racine_forum($id_forum){
	if (!$id_forum = intval($id_forum)) return;
	$result = spip_query("SELECT id_parent, id_rubrique, id_article, id_breve, id_syndic FROM spip_forum WHERE id_forum=".$id_forum);

	if($row = spip_fetch_array($result)){
		if($row['id_parent']) {
			return racine_forum($row['id_parent']);
		}
		else {
			if($row['id_rubrique']) return array('rubrique',$row['id_rubrique'], $id_forum);
 			if($row['id_article']) return array('article',$row['id_article'], $id_forum);
			if($row['id_breve']) return array('breve',$row['id_breve'], $id_forum);
			if($row['id_syndic']) return array('site',$row['id_syndic'], $id_forum);
		}
	}
} 

// http://doc.spip.org/@generer_url_forum_dist
function generer_url_forum_dist($id_forum, $show_thread=false) {
	if (!$id_forum) return '';
	list($type, $id, $id_thread) = racine_forum($id_forum);
	if ($id_thread>0 AND $show_thread)
		$id_forum = $id_thread;
	switch($type) {
		case 'article':
			return generer_url_article($id)."#forum$id_forum";
			break;
		case 'breve':
			return generer_url_breve($id)."#forum$id_forum";
			break;
		case 'rubrique':
			return generer_url_rubrique($id)."#forum$id_forum";
			break;
		case 'site':
			return generer_url_site($id)."#forum$id_forum";
			break;
		default:
		  return '';
	}
}


// Recuperer le reglage des forums publics de l'article x
// http://doc.spip.org/@get_forums_publics
function get_forums_publics($id_article=0) {
	$forums_publics = $GLOBALS['meta']["forums_publics"];
	if ($id_article) {
		$res = spip_query("SELECT accepter_forum FROM spip_articles WHERE id_article=$id_article");

		if ($obj = spip_fetch_array($res))
			$forums_publics = $obj['accepter_forum'];
	} else { // dans ce contexte, inutile
		$forums_publics = substr($GLOBALS['meta']["forums_publics"],0,3);
	}
	return $forums_publics;
}

// Cree le formulaire de modification du reglage des forums de l'article
// http://doc.spip.org/@formulaire_poster
function formulaire_poster($id_article, $script, $args, $ajax=false) {
	global $spip_lang_right;

	$statut_forum = get_forums_publics($id_article);

	$r.= "\n\t"
	. _T('info_fonctionnement_forum')
	. "\n\t<select name='change_accepter_forum'
		class='fondl'
		style='font-size:10px;'
		onChange=\"changeVisible(true, 'valider_poster_$id_article', 'block', 'block');\"
		>";

	foreach (array(
		'pos'=>_T('bouton_radio_modere_posteriori'),
		'pri'=>_T('bouton_radio_modere_priori'),
		'abo'=>_T('bouton_radio_modere_abonnement'),
		'non'=>_T('info_pas_de_forum'))
		as $val => $desc) {
		$r .= "\n\t<option";
		if ($statut_forum == $val)
			$r .= " selected";
		$r .= " value='$val'>".$desc."</option>";
	}
	$r .= "\n\t</select>\n";

	$r .= "<div align='$spip_lang_right' id='valider_poster_$id_article'"
	. ($ajax ? '' : " class='display_au_chargement'")
	. ">\n\t<input type='submit' class='fondo' style='font-size:10px' value='"
	. _T('bouton_changer')
	. "' /></div>\n";
	return ajax_action_auteur('poster', $id_article, $r, $script, $args, $args);

}
?>
