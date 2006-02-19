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

function controle_signatures($script, $id, $debut, $where, $order, $limit=10) {
	global $couleur_foncee;
	
	$where = tronconne_signatures($script, $id, $debut, $where, $limit);
	$request = spip_query("SELECT * FROM spip_signatures " .
			      ($where ? " WHERE $where" : "") .
			      ($order ? " ORDER BY $order" : "") .
			      " LIMIT $limit" .
			      ($debut ? " OFFSET $debut" : ""));

 	while($row=spip_fetch_array($request)){
		$id_signature = $row['id_signature'];
		$id_article = $row['id_article'];
		$date_time = $row['date_time'];
		$nom_email= typo(echapper_tags($row['nom_email']));
		$ad_email = echapper_tags($row['ad_email']);
		$nom_site = typo(echapper_tags($row['nom_site']));
		$url_site = echapper_tags($row['url_site']);
		$statut = $row['statut'];
		
		echo "<P>";
		
		if ($statut=="poubelle"){
			echo "<TABLE WIDTH=100% CELLPADDING=2 CELLSPACING=0 BORDER=0><TR><TD BGCOLOR='#FF0000'>";
		}
		
		echo "<TABLE WIDTH=100% CELLPADDING=3 CELLSPACING=0><TR><TD BGCOLOR='$couleur_foncee' class='verdana2' style='color: white;'><b>",
		  ($nom_site ? "$nom_site / " : ""),
		  $nom_email,
		  "</b></TD></TR>",
		  "<TR><TD BGCOLOR='#FFFFFF' class='serif'>";
				
		if ($statut=="publie"){
			icone (_T('icone_supprimer_signature'), generer_url_ecrire($script, "supp_petition=$id_signature&debut=$debut"),
			       "forum-interne-24.gif", 
			       "supprimer.gif",
			       "right");
		}
		if ($statut=="poubelle"){
			icone (_T('icone_valider_signature'), generer_url_ecrire($script, "add_petition=$id_signature&debut=$debut"),
			       "forum-interne-24.gif", 
			       "creer.gif",
			       "right");
		}
		
		echo "<FONT SIZE=2>".date_relative($date_time)."</FONT><BR>";
		if ($statut=="poubelle"){
			echo "<FONT SIZE=1 COLOR='red'>"._T('info_message_efface')."</FONT><BR>";
		}
		if (strlen($url_site)>6 AND strlen($nom_site)>0){
			echo "<FONT SIZE=1>"._T('info_site_web')."</FONT> <A HREF='$url_site'>$nom_site</A><BR>";
		}
		if (strlen($ad_email)>0){
			echo "<FONT SIZE=1>"._T('info_adresse_email')."</FONT> <A HREF='mailto:$ad_email'>$ad_email</A><BR>";
		}

		echo "<p>",message_de_signature($row),"</p>";
		
		list($titre) = spip_fetch_array(spip_query("SELECT titre FROM spip_articles WHERE id_article=$id_article"));	

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
		  typo($titre),
		  "</a>";

		echo "</TD></TR></TABLE>";
		
		if ($statut=="poubelle"){
			echo "</TD></TR></TABLE>";
		}
	}
}

function tronconne_signatures($script, $id_article, $debut, $where, $limit)
{
	if ($id_article) { 
		$args = "id_article=$id_article&";
		$where .= ($where ? " AND " : "") . "id_article=$id_article";
	}
	else $args = "";

	$res = spip_query("SELECT date_time FROM spip_signatures WHERE $where AND date_time>DATE_SUB(NOW(),INTERVAL 180 DAY)ORDER BY date_time DESC");

	while ($row = spip_fetch_array($res)) {
		if($c++%10==0) {	
			if ($c > 1) echo " | ";
			$date = entites_html(affdate_court($row['date_time']));
			if ($c == ($debut+1))
				echo "<FONT SIZE=3><B>$c</B></FONT>";
			else
			  echo "<A alt=\"$date\" title=\"$date\" href='", generer_url_ecrire($script, $args ."debut=".($c-1)), "'>$c</A>";
		}
	}
	return $where;
}

?>
