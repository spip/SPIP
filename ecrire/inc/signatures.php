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

charger_generer_url();

// http://doc.spip.org/@message_de_signature
function message_de_signature($row)
{
  return propre(echapper_tags($row['message']));
}

// http://doc.spip.org/@inc_signatures_dist
function inc_signatures_dist($script, $id, $debut, $where, $order, $limit='') {

	# filtre de duree (a remplacer par une vraie pagination)
	#$where .= ($where ? " AND " : "") . "date_time>DATE_SUB(NOW(),INTERVAL 180 DAY)";
	if ($id_article) { 
		$args = "id_article=$id_article&";
		$where .= " AND id_article=$id_article";
	}
	else $args = "";

	$evt = (_SPIP_AJAX == 1);

	$a = "editer_signature-$id";

	$q = spip_query("SELECT date_time FROM spip_signatures " . ($where ? "WHERE $where" : '') . " ORDER BY date_time DESC");

	while ($row = spip_fetch_array($q)) {
		if($c++%$limit==0) {	
			if ($c > 1) $res .= " | ";
			$date = (affdate_court($row['date_time']));
			if ($c == ($debut+1))
				$res .= "<font size='3'><b>$c</b></font>";
			else {
				$h = generer_url_ecrire($script, $args ."debut=".($c-1));
				if ($evt)
					$evt = "\nonclick="
					. ajax_action_declencheur($h,$a);
				$res .= http_href("$h$a", $c, $date, '', '', $evt);
			}
		}
	}

	$limit = (!$limit AND !$debut) ? '' : (($debut ? "$debut," : "") . $limit);
#	($limit . ($debut ? " OFFSET $debut" : "")); #PG
	$request = spip_query("SELECT * FROM spip_signatures " .  ($where ? " WHERE $where" : "") .  ($order ? " ORDER BY $order" : "") . (!$limit ? ''  : " LIMIT $limit"));

 	while($row=spip_fetch_array($request)){
		$res .= signatures_edit($script, $id, $debut, $row);
	}
	return $res;
}

// http://doc.spip.org/@signatures_edit
function signatures_edit($script, $id, $debut, $row) {

		global $couleur_foncee;

		$id_signature = $row['id_signature'];
		$id_article = $row['id_article'];
		$date_time = $row['date_time'];
		$nom_email= typo(echapper_tags($row['nom_email']));
		$ad_email = echapper_tags($row['ad_email']);
		$nom_site = typo(echapper_tags($row['nom_site']));
		$url_site = echapper_tags($row['url_site']);
		$statut = $row['statut'];
		
		$arg = ($statut=="publie") ? "-$id_signature" : $id_signature;
		$res = "";
		
		if ($statut=="poubelle"){
			$res .= "<table width='100%' cellpadding='2' cellspacing='0' border='0'><tr><td bgcolor='#FF0000'>";
		}
		
		$res .= "<table width='100%' cellpadding='3' cellspacing='0'><tr><td bgcolor='$couleur_foncee' class='verdana2' style='color: white;'><b>"
		.  ($nom_site ? "$nom_site / " : "")
		.  $nom_email
		.  "</b></td></tr>"
		.  "<tr><td bgcolor='#FFFFFF' class='serif'>";
				
		if ($statut=="publie"){
			$res .= icone (_T('icone_supprimer_signature'),
				redirige_action_auteur('editer_signatures', $arg, $script, "id_article=$id&debut=$debut"),
				"forum-interne-24.gif", 
				"supprimer.gif",
				"right",
				false);
		} elseif ($statut=="poubelle"){
			$res .= icone (_T('icone_valider_signature'),
				redirige_action_auteur('editer_signatures', $arg, $script, "id_article=$id&debut=$debut"),
				"forum-interne-24.gif", 
				"creer.gif",
				"right",
				false);
		}
		
		$res .= "<font size='2'>".date_interface($date_time)."</font><br />";
		if ($statut=="poubelle"){
			$res .= "<font size='1' color='red'>"._T('info_message_efface')."</font><br />";
		}
		if (strlen($url_site)>6 AND strlen($nom_site)>0){
			$res .= "<font size='1'>"._T('info_site_web')."</font> <a href='$url_site'>$nom_site</a><br />";
		}
		if (strlen($ad_email)>0){
			$res .= "<font size='1'>"._T('info_adresse_email')."</font> <a href='mailto:$ad_email'>$ad_email</a><br />";
		}

		$res .= "<p>" . message_de_signature($row) ."</p>";
		
		$titre = spip_fetch_array(spip_query("SELECT titre FROM spip_articles WHERE id_article=$id_article"));

		if (!$id)
			$res .= "<span class='arial1' style='float: $spip_lang_right; color: black; padding-$spip_lang_left: 4px;'><b>"
			. _T('info_numero_abbreviation')
			. $id_article
			. " </b></span>";
		
		$res .= "<a href='"
		.  (($statut == 'publie') ? 
		   generer_url_action('redirect', "id_article=$id_article") :
		   generer_url_ecrire('articles', "id_article=$id_article"))
		. "'>"
		. typo($titre['titre'])
		. "</a>"
		. "</td></tr></table>";
		
		if ($statut=="poubelle"){
			$res .= "</td></tr></table>";
		}

		return "<p>$res</p>";
}
?>
