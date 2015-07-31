<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_SITES")) return;
define("_INC_SITES", "1");


if ($supprimer_lien = $GLOBALS["supprimer_lien"]) {
	spip_query("UPDATE spip_syndic_articles SET statut='refuse' WHERE id_syndic_article='$supprimer_lien'");
}

if ($ajouter_lien = $GLOBALS["ajouter_lien"]) {
	spip_query("UPDATE spip_syndic_articles SET statut='publie' WHERE id_syndic_article='$ajouter_lien'");
}


function recuperer_page($url) {
	$http_proxy = lire_meta("http_proxy");
	if (!eregi("^http://", $http_proxy))
		$http_proxy = '';

	$f = ($http_proxy) ? 0 : @fopen($url, "rb");

	if (!$f) {
		for (;;) {
			$t = @parse_url($url);
			$host = $t['host'];
			if (!($port = $t['port'])) $port = 80;
			$query = $t['query'];
			if (!($path = $t['path'])) $path = "/";

			if ($http_proxy) {
				$t2 =  @parse_url($http_proxy);
				$proxy_host = $t2['host'];
				if (!($proxy_port = $t2['port'])) $proxy_port = 80;
				$f = @fsockopen($proxy_host, $proxy_port);
			} else
				$f = @fsockopen($host, $port);

			if (!$f) return;

			if ($http_proxy) {
				fputs($f, "GET http://$host" . (($port != 80) ? ":$port" : "") . $path . ($query ? "?$query" : "") . " HTTP/1.0\nHost: $host\n\n");
			}
			else
				fputs($f, "GET $path" . ($query ? "?$query" : "") . " HTTP/1.0\nHost: $host\n\n");

			$s = trim(fgets($f, 16384));
			if (ereg('^HTTP/[0-9]+\.[0-9]+ ([0-9]+)', $s, $r)) {
				$status = $r[1];
			}
			else return;
			while ($s = trim(fgets($f, 16384))) {
				if (ereg('^Location: (.*)', $s, $r)) {
					$location = $r[1];
				}
			}
			if ($status >= 300 AND $status < 400 AND $location) $url = $location;
			else if ($status != 200) return;
			else break;
			fclose($f);
		}
	}
	while (!feof($f)) {
		$result .= fread($f, 16384);
	}
	fclose($f);
	return $result;
}


function analyser_site($url) {
	$texte = recuperer_page($url);
	if (!$texte) return false;
	$result = '';
	if (ereg('<channel[^>]*>(.*)</channel>', $texte, $regs)) {
		$result['syndic'] = true;
		$result['url_syndic'] = $url;
		$channel = $regs[1];
		if (ereg('<title[^>]*>([^<]*)</title>', $channel, $r)) {
			$result['nom_site'] = $r[1];
		}
		if (ereg('<link[^>]*>([^<]*)</link>', $channel, $r)) {
			$result['url_site'] = $r[1];
		}

		// si le channel n'a pas de description, ne pas prendre celle d'un article
		if ($a = strpos($channel, '<item>'))
			$channel_desc = substr ($channel, 0, $a);
		else
			$channel_desc = $channel;
		if (ereg('<description[^>]*>([^<]*)</description>', $channel_desc, $r)) {
			$result['descriptif'] = $r[1];
		}
	}
	else {
		$result['syndic'] = false;
		$result['url_site'] = $url;
		if (eregi('<head>(.*)', $texte, $regs)) {
			$head = eregi_replace('</head>.*', '', $regs[1]);
		}
		else $head = $texte;
		if (eregi('<title[^>]*>(.*)', $head, $regs)) {
			$result['nom_site'] = supprimer_tags(eregi_replace('</title>.*', '', $regs[1]));
		}
		if (eregi('<meta[[:space:]]+(name|http\-equiv)[[:space:]]*=[[:space:]]*[\'"]?description[\'"]?[[:space:]]+(content|value)[[:space:]]*=[[:space:]]*[\'"]([^>]+)[\'"]>', $head, $regs)) {
			$result['descriptif'] = supprimer_tags($regs[3]);
		}
	}
	return $result;
}


function syndic_a_jour($now_id_syndic, $statut = 'off') {
	spip_query("UPDATE spip_syndic SET syndication='$statut', date_syndic=NOW() WHERE id_syndic='$now_id_syndic'");
	
	$query = "SELECT * FROM spip_syndic WHERE id_syndic='$now_id_syndic'";
	$result = spip_query($query);
	if ($row = spip_fetch_array($result))
		$la_query=$row["url_syndic"];
	else
		return;

	$moderation = $row['moderation'];
	if ($moderation == 'oui')
		$moderation = 'dispo';	// a valider
	else
		$moderation = 'publie';	// en ligne sans validation

	$le_retour=recuperer_page($la_query);

	if (strlen($le_retour)>10){

		$i=0;
		$item="";
		while(ereg("<item[>[:space:]]",$le_retour,$regs)){
			$debut_item=strpos($le_retour,$regs[0]);
			$fin_item=strpos($le_retour,"</item>")+strlen("</item>");
			$item[$i]=substr($le_retour,$debut_item,$fin_item-$debut_item);

			$debut_texte=substr($le_retour,"0",$debut_item);
			$fin_texte=substr($le_retour,$fin_item,strlen($le_retour));
			$le_retour=$debut_texte.$fin_texte;
			$i++;
		}
		if (count($item)>1){
			for($i = 0 ; $i < count($item) ; $i++){
				ereg("<title>(.*)</title>",$item[$i],$match);
				$le_titre=addslashes($match[1]);
				$match="";
				ereg("<link>(.*)</link>",$item[$i],$match);
				$le_lien=addslashes($match[1]);
				$match="";
				ereg("<date>(.*)</date>",$item[$i],$match);
				$la_date=addslashes($match[1]);
				$match="";
				ereg("<author>(.*)</author>",$item[$i],$match);
				$les_auteurs=addslashes($match[1]);
				$match="";
				ereg("<description[^>]*>([^<]*)</description>",$item[$i],$match);
				$la_description=addslashes($match[1]);
				
				$match="";
				if (strlen($la_date) < 4) $la_date=date("Y-m-j H:i:00");
												
				$query_deja="SELECT * FROM spip_syndic_articles WHERE url=\"$le_lien\" AND id_syndic=$now_id_syndic";
				$result_deja=spip_query($query_deja);
				if (spip_num_rows($result_deja)==0){
					$query_syndic="INSERT INTO spip_syndic_articles SET id_syndic=\"$now_id_syndic\", titre=\"$le_titre\", url=\"$le_lien\", date=\"$la_date\", lesauteurs=\"$les_auteurs\", statut='$moderation', descriptif=\"$la_description\"";
					$result_syndic=spip_query($query_syndic);
					
					// Indexation pour moteur
					$id_syndic_article=spip_insert_id();
				}
			}
			spip_query("UPDATE spip_syndic SET syndication='oui' WHERE id_syndic='$now_id_syndic'");
		} 
		else if (ereg("document\.write", $le_retour)) {

			while ($i < 50 AND eregi("<a[[:space:]]+href[[:space:]]*=[[:space:]]*\"?([^\">]+)\"?[^>]*>(.*)",$le_retour,$reg)){
				$i++;
				
				$le_lien = addslashes(stripslashes($reg[1]));
				$la_suite = $reg[2];
				
				$pos_fin = strpos($la_suite, "</a");
				$pos_fin2 = strpos($la_suite, "</A");
				if ($pos_fin2 > $pos_fin) $pos_fin = $pos_fin2;
				
				$le_titre = substr($la_suite, 0, $pos_fin);
				$le_titre = addslashes(stripslashes($le_titre));
				$le_titre = ereg_replace("<[^>]*>","",$le_titre);
				$le_retour = substr($la_suite, $pos_fin + 4, strlen($le_retour));
				
				echo "<li> $le_titre / $le_lien";
				
			
				if (strlen($la_date) < 4) $la_date=date("Y-m-j H:i:00");
												
				$query_deja="SELECT * FROM spip_syndic_articles WHERE url=\"$le_lien\" AND id_syndic=$now_id_syndic";
				$result_deja=spip_query($query_deja);
				if (spip_num_rows($result_deja)==0){
					$query_syndic="INSERT INTO spip_syndic_articles SET id_syndic=\"$now_id_syndic\", titre=\"$le_titre\", url=\"$le_lien\", date=\"$la_date\", lesauteurs=\"$les_auteurs\", statut='$moderation'";
					$result_syndic=spip_query($query_syndic);
					
					// Indexation pour moteur
					$id_syndic_article=spip_insert_id();
				}
			}
			spip_query("UPDATE spip_syndic SET syndication='oui', date_syndic=NOW() WHERE id_syndic='$now_id_syndic'");
		}
		else
			return "La syndication a &eacute;chou&eacute; : le backend indiqu&eacute; est ind&eacute;chiffrable ou ne propose aucun article.";
	} else
		return "La syndication a &eacute;chou&eacute; : impossible d'acc&eacute;der au backend de ce site.";
}


function afficher_sites($titre_table, $requete) {
	global $couleur_claire;
	global $connect_id_auteur;
	
	$activer_messagerie = lire_meta("activer_messagerie");
	$activer_statistiques = lire_meta("activer_statistiques");

	$tranches = afficher_tranches_requete($requete, 3);

	if ($tranches) {
		//echo "<P><TABLE WIDTH=100% CELLPADDING=0 CELLSPACING=0 BORDER=0><TR><TD WIDTH=100% BACKGROUND=''>";
		debut_cadre_relief("site-24.gif");

		echo "<TABLE WIDTH=100% CELLPADDING=3 CELLSPACING=0 BORDER=0>";
		bandeau_titre_boite($titre_table, true);

		echo $tranches;

	 	$result = spip_query($requete);
		$num_rows = spip_num_rows($result);

		$ifond = 0;
		$premier = true;
		
		$compteur_liste = 0;
		while ($row = spip_fetch_array($result)) {
			$ifond = $ifond ^ 1;
			$couleur = ($ifond) ? '#FFFFFF' : $couleur_claire;

			$id_syndic=$row["id_syndic"];
			$id_rubrique=$row["id_rubrique"];
			$nom_site=typo($row["nom_site"]);
			$url_site=$row["url_site"];
			$url_syndic=$row["url_syndic"];
			$description=propre($row["description"]);
			$syndication=$row["syndication"];
			$statut=$row["statut"];
			$date=$row["date"];
			$moderation=$row['moderation'];
			
			$tous_id[] = $id_syndic;

			echo "<tr bgcolor='$couleur'>";

			echo "<td class='arial2'>";
			$link = new Link("sites.php3?id_syndic=$id_syndic");
			$redirect = new Link;
			$link->addVar('redirect', $redirect->getUrl());
			echo "<A HREF=\"".$link->getUrl()."\">";
			if ($statut=='publie') {
				if (acces_restreint_rubrique($id_rubrique))
					$puce = 'puce-verte-anim.gif';
				else
					$puce='puce-verte.gif';
			}
			else if ($statut=='prop') {
				if (acces_restreint_rubrique($id_rubrique))
					$puce = 'puce-blanche-anim.gif';
				else
					$puce='puce-blanche.gif';
			}
			else if ($statut=='refuse') {
				if (acces_restreint_rubrique($id_rubrique))
					$puce = 'puce-poubelle-anim.gif';
				else
					$puce='puce-poubelle.gif';
			}
			
			
			if ($syndication == "off") {
					$puce = 'puce-orange-anim.gif';
			}

			echo "<img src='img_pack/$puce' width='7' height='7' border='0'>&nbsp;&nbsp;";
			if ($moderation == 'oui')
				echo "<i>".typo($nom_site)."</i>";
			else
				echo typo($nom_site);

			echo "</a> &nbsp;&nbsp; <font size='1'>[<a href='$url_site'>visiter ce site</a>]</font>";
			echo "</td>";
			
			echo "<td class='arial1' align='right'> &nbsp;";
			if ($syndication == "off") {
				echo "<font color='red'>probl&egrave;me de </font>";
			}
			if ($syndication == "oui" or $syndication == "off"){
				echo "<font color='red'>syndication :</font>";
			}
			echo "</td>";					
			echo "<td class='arial1'>";
			if ($syndication == "oui" OR $syndication == "off") {
				$result_art = spip_query("SELECT COUNT(*) FROM spip_syndic_articles WHERE id_syndic='$id_syndic'");
				list($total_art) = spip_fetch_row($result_art);
				echo " $total_art article(s)";
			} else {
				echo "&nbsp;";
			}
			echo "</td>";					
			echo "</tr></n>";
		}
		echo "</TABLE>";
		//echo "</TD></TR></TABLE>";
		fin_cadre_relief();
	}
	return $tous_id;
}


function afficher_syndic_articles($titre_table, $requete, $afficher_site = false) {
	global $couleur_claire;
	global $connect_statut;
	global $REQUEST_URI;
	global $debut_liste_sites;
	global $flag_editable;
	
	static $n_liste_sites;
	
	$n_liste_sites++;
	if (!$debut_liste_sites[$n_liste_sites]) $debut_liste_sites[$n_liste_sites] = 0;

	$adresse_page = substr($REQUEST_URI, strpos($REQUEST_URI, "/ecrire")+8, strlen($REQUEST_URI));
	$adresse_page = ereg_replace("\&?debut\_liste\_sites\[$n_liste_sites\]\=[0-9]+","",$adresse_page);
	$adresse_page = ereg_replace("\&?(ajouter\_lien|supprimer_lien)\=[0-9]+","",$adresse_page);

	if (ereg("\?",$adresse_page)) $lien_url = "&";
	else $lien_url = "?";

	$lien_url .= "debut_liste_sites[".$n_liste_sites."]=".$debut_liste_sites[$n_liste_sites]."&";


	$nombre_aff = 10;

	$activer_messagerie = lire_meta("activer_messagerie");
	$activer_statistiques = lire_meta("activer_statistiques");
		
 	$result = spip_query($requete);
	$num_rows = spip_num_rows($result);

	// Ne pas couper pour trop peu
	if ($num_rows <= 1.5 * $nombre_aff) $nombre_aff = $num_rows;
	
		if ($num_rows > 0) {
			//bandeau_titre_boite($titre_table);

			echo "<P><TABLE WIDTH=100% CELLPADDING=0 CELLSPACING=0 BORDER=0><TR><TD WIDTH=100% BACKGROUND=''>";
			echo "<TABLE WIDTH=100% CELLPADDING=3 CELLSPACING=0 BORDER=0>";

			bandeau_titre_boite($titre_table, true);

			if ($num_rows > $nombre_aff) {
				echo "<tr><td background='' class='arial2' colspan=4>";
				for ($i = 0; $i < $num_rows; $i = $i + $nombre_aff){
					$deb = $i + 1;
					$fin = $i + $nombre_aff;
					if ($fin > $num_rows) $fin = $num_rows;
					if ($debut_liste_sites[$n_liste_sites] == $i) {
						echo "[<B>$deb-$fin</B>] ";
					} else {
						echo "[<A HREF='".$adresse_page.$lien_url."debut_liste_sites[$n_liste_sites]=$i'>$deb-$fin</A>] ";
					}
				}
				echo "</td></tr>";
			}
		
			$ifond = 0;
			$premier = true;
			
			$compteur_liste = 0;

			while ($row = spip_fetch_array($result)) {
				if ($compteur_liste >= $debut_liste_sites[$n_liste_sites] AND $compteur_liste < $debut_liste_sites[$n_liste_sites] + $nombre_aff) {
					$ifond = $ifond ^ 1;
					$couleur = ($ifond) ? '#FFFFFF' : $couleur_claire;

					$id_syndic_article=$row["id_syndic_article"];
					$id_syndic=$row["id_syndic"];
					$titre=typo($row["titre"]);
					$url=$row["url"];
					$date=$row["date"];
					$lesauteurs=propre($row["lesauteurs"]);
					$statut=$row["statut"];
					$descriptif=$row["descriptif"];

					echo "<tr bgcolor='$couleur'>";
					
					echo "<td class='arial1'>";
					echo "<A HREF='$url'>";
					if ($statut=='publie') {
						if (acces_restreint_rubrique($id_rubrique))
							$puce = 'puce-verte-anim.gif';
						else
							$puce='puce-verte.gif';
					}
					else if ($statut == "refuse") {
							$puce = 'puce-poubelle.gif';
					}

					else if ($statut == "dispo") { // moderation : a valider
							$puce = 'puce-rouge.gif';
					}

					else if ($statut == "off") { // vieillerie
							$puce = 'puce-rouge-anim.gif';
					}

					echo "<img src='img_pack/$puce' width='7' height='7' border='0'>";

					if ($statut == "refuse")
						echo "<font color='black'>&nbsp;&nbsp;$titre</font>";
					else
						echo "&nbsp;&nbsp;".$titre;

					echo "</A>";

					if (strlen($lesauteurs)>0) echo "<br>auteur(s)&nbsp;: <font color='#336666'>$lesauteurs</font>";
					if (strlen($descriptif)>0) echo "<br>descriptif(s)&nbsp;: <font color='#336666'>$descriptif</font>";
					
					echo "</td>";
					
					// $my_sites cache les resultats des requetes sur les sites
					if ($afficher_site) {
						if (!$my_sites[$id_syndic])
							$my_sites[$id_syndic] = spip_fetch_array(spip_query(
								"SELECT * FROM spip_syndic WHERE id_syndic=$id_syndic"));
						echo "<td class='arial1' align='left'>";
						if ($my_sites[$id_syndic]['moderation'] == 'oui') echo "<i>";
						echo $my_sites[$id_syndic]['nom_site'];
						if ($my_sites[$id_syndic]['moderation'] == 'oui') echo "</i>";
						echo "</td>";
					} else echo "<td></td>";

					echo "<td class='arial1' align='right'>";
					
					if ($connect_statut == '0minirezo'){
						if ($statut == "publie"){
							echo "[<a href='".$adresse_page.$lien_url."id_syndic=$id_syndic&supprimer_lien=$id_syndic_article'><font color='black'>bloquer ce lien</font></a>]";
						
						}
						else if ($statut == "refuse"){
							echo "[<a href='".$adresse_page.$lien_url."id_syndic=$id_syndic&ajouter_lien=$id_syndic_article'>r&eacute;tablir ce lien</a>]";
						}
						else if ($statut == "dispo") {
							echo "[<a href='".$adresse_page.$lien_url."id_syndic=$id_syndic&ajouter_lien=$id_syndic_article'>valider ce lien</a>]";
						}
					}else{
						echo "&nbsp;";
					}

					echo "</td>";					
					echo "</tr></n>";

			}
			$compteur_liste++;

		}
		echo "</TABLE></TD></TR></TABLE>";
	}
}


//
// Effectuer la syndication d'un unique site
//

function executer_une_syndication() {
	$query_syndic = "SELECT * FROM spip_syndic WHERE syndication='sus' AND statut='publie' ".
			"AND date_syndic < DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY date_syndic LIMIT 0,1";
	if ($result_syndic = spip_query($query_syndic)) {
		while ($row = spip_fetch_array($result_syndic)) {
			$id_syndic = $row["id_syndic"];
			syndic_a_jour($id_syndic);
		}
	}
	$query_syndic = "SELECT * FROM spip_syndic WHERE syndication='oui' AND statut='publie' ".
			"AND date_syndic < DATE_SUB(NOW(), INTERVAL 2 HOUR) ORDER BY date_syndic LIMIT 0,1";
	if ($result_syndic = spip_query($query_syndic)) {
		while ($row = spip_fetch_array($result_syndic)) {
			$id_syndic = $row["id_syndic"];
			syndic_a_jour($id_syndic, 'sus');
		}
	}
}

?>