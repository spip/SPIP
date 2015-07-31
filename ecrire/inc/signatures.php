<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

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

	return $res."<br />\n" . join("<br />\n", $r);
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
		$res = "<div class='editer_auteurs'>$res</div>";
	}

	$res .= "<div class='spip_small date'>".date_interface($date_time)."</div>\n";
	if ($statut=="poubelle"){
		$res .= "<div class='spip_x-small info_statut'>"._T('info_message_efface')."</div>\n";
	}
	if (strlen($url_site)>6) {
		if (!$nom_site) $nom_site = _T('info_site');
		$res .= "<div class='site'><span class='spip_x-small'>"._T('info_site_web')."</span> <a href='$url_site'>$nom_site</a></div>\n";
		}

	if ($ad_email) $res .= signatures_edit_mail($id_article, $ad_email, $row);

	$res .= "<div class='texte'>" . message_de_signature($row) . "</div>";
		
	if (!$id) {
		if ($r = sql_fetsel("titre, id_rubrique", "spip_articles", "id_article=$id_article")) {
			$id_rubrique = $r['id_rubrique'];
			$titre_a = $r['titre'];
			$titre_r = supprimer_numero(sql_getfetsel("titre", "spip_rubriques", "id_rubrique=$id_rubrique"));
		        $href = generer_url_ecrire('naviguer', "id_rubrique=" . $id_rubrique);
			$h2 = generer_url_ecrire_article($id_article);
			$res .= "<div class='nettoyeur'></div><div class='reponse_a'><a title='$id_article' href='"
			  . $h2
			  . "'>"
			  . typo($titre_a)
			  . "</a><a class='reponse_a' style='float: $spip_lang_right; padding-$spip_lang_left: 4px;' href='$href' title='$id_rubrique'>"
			. typo($titre_r)
			. " </a></div>";
		}
	}

	$res = "<table class='signature' id='signature$id_signature' width='100%' cellpadding='3' cellspacing='0'>\n<tr><td class='verdana2 cartouche'>"
 		.  ($nom_site ? "$nom_site / " : "")
		.  $nom_email
		.  "</td></tr>"
		.  "\n<tr><td class='serif contenu'>"
		. $res
		. "</td></tr></table>\n";
		
	if ($statut=="poubelle") {
		$res = "<table class='signature' width='100%' cellpadding='2' cellspacing='0' border='0'><tr><td class='poubelle'>"
			. $res
			. "</td></tr></table>";
	}

	return $res;
}

function signatures_edit_mail($id_article, $ad_email, $row) {

	$email = attribut_html($ad_email);
	if (email_valide($ad_email)) {
		if ($row['statut'] != 'publie'
		AND autoriser('modererpetition', 'article', $id_article)) {
			include_spip('formulaires/signature');
			$url = generer_url_entite_absolue($id_article, 'article','','',true);
			list($titre, $url) = signature_langue($id_article, $url);

			list($sujet, $corps) = signature_demande_confirmation($id_article, $url, $row['nom_email'], $row['nom_site'], $row['url_site'], $row['message'], $titre, $row['statut']);

			include_spip('inc/filtres');
			$sujet = rawurlencode(filtrer_entites($sujet));
			$corps = rawurlencode(filtrer_entites($corps));
			$corps = "?subject=$sujet&amp;body=$corps";
		} else $corps = '';
		$email = "<a href=\"mailto:$ad_email$corps\">$email</a>";
	}
	return "<div class='ad_email'><span class='spip_x-small'>"
			._T('info_adresse_email')
			."</span> "
			. $email
			. "</div>\n";

}
?>
