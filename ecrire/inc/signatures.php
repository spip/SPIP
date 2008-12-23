<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@message_de_signature
function message_de_signature($row)
{
	return propre(echapper_tags($row['message']));
}

// http://doc.spip.org/@inc_signatures_dist
function inc_signatures_dist($script, $id, $debut, $pas, $where, $order, $type='') {

	if ($id) { 
		$args = "id_article=$id&";
	}
	else $args = "";

	$t = sql_countsel("spip_signatures", $where);
	if ($t > $pas) {
		$res = navigation_pagination($t, $pas, generer_url_ecrire($script, $args), $debut, 'debut', false);
	} else $res = '';

	$limit = (!$pas AND !$debut) ? '' : (($debut ? "$debut," : "") . $pas);

	$arg = "debut=$debut&type=$type";

	$res .= "<br />\n";
	include_spip('inc/urls');
	$r = sql_allfetsel('*', 'spip_signatures', $where, '', $order, $limit);
	foreach($r as $k => $row)
		$r[$k] = signatures_edit($script, $id, $arg, $row);

	return "<br />\n" . join("<br />\n", $r);
}

// http://doc.spip.org/@signatures_edit
function signatures_edit($script, $id, $arg, $row) {

	global $spip_lang_right, $spip_lang_left;
	$id_signature = $row['id_signature'];
	$id_article = $row['id_article'];
	$date_time = $row['date_time'];
	$nom_email= typo(echapper_tags($row['nom_email']));
	$ad_email = echapper_tags($row['ad_email']);
	$nom_site = typo(echapper_tags($row['nom_site']));
	$url_site = echapper_tags($row['url_site']);
	$statut = $row['statut'];

	$res = !autoriser('modererpetition', 'article', $id_article) ? '' : true;

	if ($res) {
		if ($id) $arg .= "&id_article=$id_article";
		$arg .= "#signature$id_signature";
		$retour_s = redirige_action_auteur('editer_signatures', $id_signature, $script, $arg);
		$retour_a = redirige_action_auteur('editer_signatures', "-$id_signature", $script, $arg);

		$res = '';
		if  ($statut=="poubelle"){
			$res = icone_inline (_T('icone_valider_signature'),
				$retour_s,
				"forum-interne-24.gif", 
				"creer.gif",
				"right",
				false);
		} else {
			$res = icone_inline (_T('icone_supprimer_signature'),
				$retour_a,
				"forum-interne-24.gif", 
				"supprimer.gif",
				"right",
				false);
			if ($statut<>"publie") {
				$res .= icone_inline (_T('icone_relancer_signataire'),
				$retour_s,
				"forum-interne-24.gif", 
				"creer.gif",
				"right",
				false);
			}
		}
	}

	$res .= "<span class='spip_small'>".date_interface($date_time)."</span><br />\n";
	if ($statut=="poubelle"){
		$res .= "<span class='spip_x-small' style='color: red;'>"._T('info_message_efface')."</span><br />\n";
	}
	if (strlen($url_site)>6) {
			if (!$nom_site) $nom_site = _T('info_site');
			$res .= "<span class='spip_x-small'>"._T('info_site_web')."</span> <a href='$url_site'>$nom_site</a><br />\n";
		}
	if (strlen($ad_email)>0){
		$res .= "<span class='spip_x-small'>"._T('info_adresse_email')."</span> <a href='mailto:" . attribut_html($ad_email) . "'>$ad_email</a><br />\n";
	}

	$res .= message_de_signature($row);
		
	if (!$id) {
		if ($r = sql_fetsel("titre, id_rubrique", "spip_articles", "id_article=$id_article")) {
			$id_rubrique = $r['id_rubrique'];
			$titre_a = $r['titre'];
			$titre_r = supprimer_numero(sql_getfetsel("titre", "spip_rubriques", "id_rubrique=$id_rubrique"));
		        $href = generer_url_ecrire('naviguer', "id_rubrique=" . $id_rubrique);
			$h2 = generer_url_ecrire_article($id_article);
			$res .= "<br class='nettoyeur' /><a title='$id_article' href='"
			  . $h2
			  . "'>"
			  . typo($titre_a)
			  . "</a><a style='float: $spip_lang_right; color: black; padding-$spip_lang_left: 4px;' href='$href' title='$id_rubrique'>"
			. typo($titre_r)
			. " </a>";
		}
	}

		
	$res = "<table id='signature$id_signature' width='100%' cellpadding='3' cellspacing='0'>\n<tr><td class='verdana2 toile_foncee' style='color: white;'><b>"
 		.  ($nom_site ? "$nom_site / " : "")
		.  $nom_email
		.  "</b></td></tr>"
		.  "\n<tr><td style='background-color: #ffffff' class='serif'>"
		. $res
		. "</td></tr></table>\n";
		
	if ($statut=="poubelle") {
			$res = "<table width='100%' cellpadding='2' cellspacing='0' border='0'><tr><td style='background-color: #ff0000'>"
			. $res
			. "</td></tr></table>";
	}

	return $res;
}
?>
