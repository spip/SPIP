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
include_spip('inc/actions');

// http://doc.spip.org/@affiche_navigation_forum
function affiche_navigation_forum($script, $args, $debut, $i, $pack, $ancre, $query)
{
	$nav = ($i <=0) ? '' : ("<a href='" . generer_url_ecrire($script, $args) ."'>0</a> ... |\n");

	$e = (_SPIP_AJAX === 1 );

	$n = spip_num_rows($query);

	for (;$n;$n--){

		if ($i == $pack*floor($i/$pack)) {
			if ($i == $debut)
				$nav .= "<span class='spip_medium'><b>$i</b></span> |\n";
			else {
				$h = generer_url_ecrire($script, $args . "&debut=$i");
				if ($e)	$e = "\nonclick=" . ajax_action_declencheur($h,$ancre);
				$nav .= "<a href='$h'$e>$i</a> |\n";
			}
		}
		$i ++;
	}

	$h = generer_url_ecrire($script, $args . "&debut=$i");

	if ($e)	$e = "\nonclick=" . ajax_action_declencheur($h,$ancre);

	return "$nav<a href='$h'$e>...</a> |";
}


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
			$suppression = 'privoff';
			break;
		# forum des administrateurs
		case "privadmin":
			$logo = "forum-admin-24.gif";
			$valider = false;
			$valider_repondre = false;
			$suppression = false;
			break;
		# forum de l'espace prive, supprime (non revalidable,
		# d'ailleurs on ne sait plus a quel type de forum il appartenait)
		case "privoff":
			$logo = "forum-interne-24.gif";
			$valider = false;
			$valider_repondre = false;
			$suppression = false;
			break;
		# forum general de l'espace prive
		case "privrac":
			$logo = "forum-interne-24.gif";
			$valider = false;
			$valider_repondre = false;
			$suppression = 'privoff';
			break;

		# forum publie sur le site public
		case "publie":
			$logo = "forum-public-24.gif";
			$valider = false;
			$valider_repondre = false;
			$suppression = 'off';
			break;
		# forum supprime sur le site public
		case "off":
			$logo = "forum-public-24.gif";
			$valider = 'publie';
			$valider_repondre = false;
			$suppression = false;
			$controle = "<br /><span style='color: red; font-weight: bold;'>"._T('info_message_supprime')." $forum_ip</span>";
			if($forum_id_auteur)
				$controle .= " - <a href='" . generer_url_ecrire('auteur_infos', "id_auteur=$forum_id_auteur") .
				  "'>" ._T('lien_voir_auteur'). "</a>";
			break;
		# forum propose (a moderer) sur le site public
		case "prop":
			$logo = "forum-public-24.gif";
			$valider = 'publie';
			$valider_repondre = true;
			$suppression = 'off';
			break;
		# forum original (reponse a un forum modifie) sur le site public
		case "original":
			$logo = "forum-public-24.gif";
			$original = true;
			break;
		default:
			return;
	}

	$lien = str_replace('&amp;', '&', self()) . "#id$id_forum";
	if ($suppression)
	  $controle .= icone(_T('icone_supprimer_message'), generer_action_auteur('instituer_forum',"$id_forum-$suppression", _DIR_RESTREINT_ABS . $lien),
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

	// TODO: un bouton retablir l'original ?
	if ($original) {
		$controle .= "<div style='float:".$GLOBALS['spip_lang_right'].";color:green'>"
		."("
		._T('forum_info_original')
		.")</div>";
	}

	return $controle;
}

// recuperer le critere SQL qui selectionne nos forums
// http://doc.spip.org/@critere_statut_controle_forum
function critere_statut_controle_forum($page, $id_rubrique=0) {

	if (is_array($id_rubrique))   $id_rubrique = join(',',$id_rubrique);
	if (!$id_rubrique) {
		$from = 'spip_forum AS F';
		$where = "";
		$and = "";
	} else {
		if (strpos($id_rubrique,','))
		  $eq = " IN ($id_rubrique)";
		else $eq = "=$id_rubrique";
	      
		$from = 'spip_forum AS F, spip_articles AS A';
		$where = "A.id_secteur$eq AND F.id_article=A.id_article";
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

// Quand on edite un forum, on tient a conserver l'original
// sous forme d'un forum en reponse, de statut 'original'
// http://doc.spip.org/@conserver_original
function conserver_original($id_forum) {
	$s = spip_query("SELECT id_forum FROM spip_forum WHERE id_parent="._q($id_forum)." AND statut='original'");

	if (spip_num_rows($s))
		return ''; // pas d'erreur

	// recopier le forum
	$t = spip_fetch_array(
		spip_query("SELECT date_heure,titre,texte,auteur,email_auteur,nom_site,url_site,ip,id_auteur,idx,id_thread FROM spip_forum WHERE id_forum="._q($id_forum))
	);

	if ($t
	AND spip_query("INSERT spip_forum (date_heure,titre,texte,auteur,email_auteur,nom_site,url_site,ip,id_auteur,idx,id_thread) VALUES (".join(',',array_map('_q', $t)).")")) {
		$id_copie = spip_insert_id();
		spip_query("UPDATE spip_forum SET id_parent="._q($id_forum).", statut='original' WHERE id_forum=$id_copie");
		return ''; // pas d'erreur
	}

		return '&erreur';
}

// appelle conserver_original(), puis modifie le contenu via l'API inc/modifier
// http://doc.spip.org/@enregistre_et_modifie_forum
function enregistre_et_modifie_forum($id_forum, $c=false) {
	if ($err = conserver_original($id_forum)) {
		spip_log("erreur de sauvegarde de l'original, $err");
		return;
	}

	include_spip('inc/modifier');
	return revision_forum($id_forum, $c);
}

?>
