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

// http://doc.spip.org/@controle_signatures
function inc_signatures_dist($script, $id, $debut, $where, $order, $limit='') {
	global $couleur_foncee;

	$where = tronconne_signatures($script, $id, $debut, $where, $limit);
	$limit = (!$limit AND !$debut) ? '' : (($debut ? "$debut," : "") . $limit);
#	($limit . ($debut ? " OFFSET $debut" : "")); #PG
	$request = spip_query("SELECT * FROM spip_signatures " .  ($where ? " WHERE $where" : "") .  ($order ? " ORDER BY $order" : "") . (!$limit ? ''  : " LIMIT $limit"));

 	while($row=spip_fetch_array($request)){
		$id_signature = $row['id_signature'];
		$id_article = $row['id_article'];
		$date_time = $row['date_time'];
		$nom_email= typo(echapper_tags($row['nom_email']));
		$ad_email = echapper_tags($row['ad_email']);
		$nom_site = typo(echapper_tags($row['nom_site']));
		$url_site = echapper_tags($row['url_site']);
		$statut = $row['statut'];
		
		echo "<p>";
		
		if ($statut=="poubelle"){
			echo "<table width='100%' cellpadding='2' cellspacing='0' border='0'><tr><td bgcolor='#FF0000'>";
		}
		
		echo "<Table width='100%' cellpadding='3' cellspacing='0'><tr><td bgcolor='$couleur_foncee' class='verdana2' style='color: white;'><b>",
		  ($nom_site ? "$nom_site / " : ""),
		  $nom_email,
		  "</b></td></tr>",
		  "<tr><td bgcolor='#FFFFFF' class='serif'>";
				
		if ($statut=="publie"){
			icone (_T('icone_supprimer_signature'),
			       redirige_action_auteur('editer_signatures', "-$id_signature", $script, "id_article=$id&debut=$debut"),
			       "forum-interne-24.gif", 
			       "supprimer.gif",
			       "right");
		}
		if ($statut=="poubelle"){
			icone (_T('icone_valider_signature'),
			       redirige_action_auteur('editer_signatures', $id_signature, $script, "id_article=$id&debut=$debut"),
			       "forum-interne-24.gif", 
			       "creer.gif",
			       "right");
		}
		
		echo "<font size='2'>".date_interface($date_time)."</font><br />";
		if ($statut=="poubelle"){
			echo "<font size='1' color='red'>"._T('info_message_efface')."</font><br />";
		}
		if (strlen($url_site)>6 AND strlen($nom_site)>0){
			echo "<font size='1'>"._T('info_site_web')."</font> <a href='$url_site'>$nom_site</a><br />";
		}
		if (strlen($ad_email)>0){
			echo "<font size='1'>"._T('info_adresse_email')."</font> <a href='mailto:$ad_email'>$ad_email</a><br />";
		}

		echo "<p>",message_de_signature($row),"</p>";
		
		$titre = spip_fetch_array(spip_query("SELECT titre FROM spip_articles WHERE id_article=$id_article"));

		if (!$id)
		  echo "<span class='arial1' style='float: $spip_lang_right; color: black; padding-$spip_lang_left: 4px;'><b>",
		    _T('info_numero_abbreviation'),
		    $id_article,
		    " </b></span>";
		
		echo "<a href='",
		  (($statut == 'publie') ? 
		   generer_url_action('redirect', "id_article=$id_article") :
		   generer_url_ecrire('articles', "id_article=$id_article")),
		  "'>",
		  typo($titre['titre']),
		  "</a>";

		echo "</td></tr></table>";
		
		if ($statut=="poubelle"){
			echo "</td></tr></table>";
		}
	}
}

// http://doc.spip.org/@tronconne_signatures
function tronconne_signatures($script, $id_article, $debut, $where, $limit=10)
{
	# filtre de duree (a remplacer par une vraie pagination)
	#$where .= ($where ? " AND " : "") . "date_time>DATE_SUB(NOW(),INTERVAL 180 DAY)";
	if ($id_article) { 
		$args = "id_article=$id_article&";
		$where .= " AND id_article=$id_article";
	}
	else $args = "";

	$res = spip_query("SELECT date_time FROM spip_signatures " . ($where ? "WHERE $where" : '') . " ORDER BY date_time DESC");

	while ($row = spip_fetch_array($res)) {
		if($c++%$limit==0) {	
			if ($c > 1) echo " | ";
			$date = entites_html(affdate_court($row['date_time']));
			if ($c == ($debut+1))
				echo "<font size='3'><b>$c</b></font>";
			else
			  echo "<a alt=\"$date\" title=\"$date\" href='", generer_url_ecrire($script, $args ."debut=".($c-1)), "'>$c</a>";
		}
	}
	return $where;
}

?>
