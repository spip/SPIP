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
	else
		$via_proxy = " (proxy $http_proxy)";

	spip_log("chargement $url$via_proxy");

	for ($i=0;$i<10;$i++) {	// dix tentatives maximum en cas d'entetes 301...
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

		if ($f) {
			if ($http_proxy)
				fputs($f, "GET http://$host" . (($port != 80) ? ":$port" : "") . $path . ($query ? "?$query" : "") . " HTTP/1.0\r\n");
			else
				fputs($f, "GET $path" . ($query ? "?$query" : "") . " HTTP/1.0\r\n");

			fputs($f, "Host: $host\r\n");
			fputs($f, "User-Agent: SPIP-".$GLOBALS['spip_version_affichee']." (http://www.uzine.net/spip)\r\n");
			if ($referer = lire_meta("adresse_site"))
				fputs($f, "Referer: $referer/\r\n");
			fputs($f,"\r\n");

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
		else {
			if (!$GLOBALS['tester_proxy'])
				$f = @fopen($url, "rb");
			break;
		}
	}

	if (!$f) {
		spip_log("ECHEC chargement $url$via_proxy");
		$result = '';
	} else {
		while (!feof($f))
			$result .= fread($f, 16384);
		fclose($f);
	}

	return $result;
}


function transcoder_page($texte) {
	include_ecrire('inc_charsets.php3');

	// Si le backend precise son charset et que celui-ci est connu de SPIP,
	// decoder puis recoder
	if (ereg('<\\?xml[[:space:]]([^>]*[[:space:]])?encoding[[:space:]]*=[[:space:]]*[\'"]([-_a-zA-Z0-9]+)[\'"]', $texte, $regs)) {
		$charset_page = strtolower($regs[2]);
		$texte = importer_charset($texte, $charset_page);
	}
	// Si le backend ne precise pas, on considere qu'il est iso-8859-1 : il faut
	// alors forcer la conversion
	else $texte = importer_charset($texte, 'iso-8859-1', true);

	return $texte;
}


function analyser_site($url) {
	include_ecrire("inc_filtres.php3");

	$texte = transcoder_page(recuperer_page($url));
	if (!$texte) return false;
	$result = '';
	if (ereg('<channel[^>]*>(.*)</channel>', $texte, $regs)) {
		$result['syndic'] = true;
		$result['url_syndic'] = $url;
		$channel = $regs[1];
		if (ereg('<title[^>]*>([^<]*)</title>', $channel, $r)) {
			$result['nom_site'] = filtrer_entites($r[1]);
		}
		if (ereg('<link[^>]*>([^<]*)</link>', $channel, $r)) {
			$result['url_site'] = filtrer_entites($r[1]);
		}

		// si le channel n'a pas de description, ne pas prendre celle d'un article
		if ($a = strpos($channel, '<item>'))
			$channel_desc = substr($channel, 0, $a);
		else
			$channel_desc = $channel;
		if (ereg('<description[^>]*>([^<]*)</description>', $channel_desc, $r)) {
			$result['descriptif'] = filtrer_entites($r[1]);
		}
	}
	else {
		$result['syndic'] = false;
		$result['url_site'] = $url;
		if (eregi('<head>(.*)', $texte, $regs)) {
			$head = filtrer_entites(eregi_replace('</head>.*', '', $regs[1]));
		}
		else $head = $texte;
		if (eregi('<title[^>]*>(.*)', $head, $regs)) {
			$result['nom_site'] = filtrer_entites(supprimer_tags(eregi_replace('</title>.*', '', $regs[1])));
		}
		if (eregi('<meta[[:space:]]+(name|http\-equiv)[[:space:]]*=[[:space:]]*[\'"]?description[\'"]?[[:space:]]+(content|value)[[:space:]]*=[[:space:]]*[\'"]([^>]+)[\'"]>', $head, $regs)) { //"
			$result['descriptif'] = filtrer_entites(supprimer_tags($regs[3]));
		}
	}
	return $result;
}


function syndic_a_jour($now_id_syndic, $statut = 'off') {
	$query = "SELECT * FROM spip_syndic WHERE id_syndic='$now_id_syndic'";
	$result = spip_query($query);
	if ($row = spip_fetch_array($result))
		$url_syndic = $row["url_syndic"];
	else return;
	$moderation = $row['moderation'];
	if ($moderation == 'oui')
		$moderation = 'dispo';	// a valider
	else
		$moderation = 'publie';	// en ligne sans validation

	// Section critique : n'autoriser qu'une seule syndication simultanee pour un site donne
	if (!spip_get_lock($url_syndic)) return;

	include_ecrire("inc_filtres.php3");
	spip_query("UPDATE spip_syndic SET syndication='$statut', date_syndic=NOW() WHERE id_syndic='$now_id_syndic'");

	$le_retour = transcoder_page(recuperer_page($url_syndic));
	$erreur = "";

	if ($le_retour) {
		$i = 0;
		while (ereg("<item[>[:space:]]",$le_retour,$regs)) {
			$debut_item=strpos($le_retour,$regs[0]);
			$fin_item=strpos($le_retour,"</item>")+strlen("</item>");
			$item[$i]=substr($le_retour,$debut_item,$fin_item-$debut_item);

			$debut_texte=substr($le_retour,"0",$debut_item);
			$fin_texte=substr($le_retour,$fin_item,strlen($le_retour));
			$le_retour=$debut_texte.$fin_texte;
			$i++;
		}
		if (is_array($item)) {
			for ($i = 0 ; $i < count($item) ; $i++) {
				if (ereg("<title>([^<]*)</title>",$item[$i],$match))
					$le_titre = addslashes(filtrer_entites($match[1]));
				else continue;
				if (ereg("<link>([^<]*)</link>",$item[$i],$match))
					$le_lien = addslashes(filtrer_entites($match[1]));
				else if (ereg("<guid>([^<]*)</guid>",$item[$i],$match))
					$le_lien = addslashes(filtrer_entites($match[1]));
				else continue;
				$la_date = "";
				if (ereg("<([[:alpha:]]+:)?date>([^<]*)</([[:alpha:]]+:)?date>",$item[$i],$match))
					$la_date = strtotime($match[2]);
				else if (ereg("<pubDate>([^<]*)</pubDate>",$item[$i],$match))
					$la_date = strtotime($match[1]);
				if (!$la_date) $la_date = time();
				if (ereg("<author>([^<]*)</author>",$item[$i],$match))
					$les_auteurs = addslashes(filtrer_entites($match[1]));
				else $les_auteurs = "";
				if (ereg("<description[^>]*>([^<]*)</description>",$item[$i],$match))
					$la_description = addslashes(filtrer_entites($match[1]));
				else $la_description = "";

				$query_deja = "SELECT * FROM spip_syndic_articles WHERE url=\"$le_lien\" AND id_syndic=$now_id_syndic";
				$result_deja = spip_query($query_deja);
				if ($result_deja AND (spip_num_rows($result_deja)==0)){
					$query_syndic = "INSERT INTO spip_syndic_articles (id_syndic, titre, url, date, lesauteurs, statut, descriptif) ".
						"VALUES ('$now_id_syndic', '$le_titre', '$le_lien', FROM_UNIXTIME($la_date), '$les_auteurs', '$moderation', '$la_description')";
					$result_syndic=spip_query($query_syndic);
				}
			}
			spip_query("UPDATE spip_syndic SET syndication='oui' WHERE id_syndic='$now_id_syndic'");
		}
		// syndication javascript : y a-t-il quelqu'un qui se sert de ce truc ??
		// la question est posee
		else if (ereg("document\.write", $le_retour)) {
			$i = 0;
			while ($i < 50 AND eregi("<a[[:space:]]+href[[:space:]]*=[[:space:]]*\"?([^\">]+)\"?[^>]*>(.*)",$le_retour,$reg)){ //"
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
					$query_syndic = "INSERT INTO spip_syndic_articles (id_syndic, titre, url, date, lesauteurs, statut, descriptif) ".
						"VALUES ('$now_id_syndic', '$le_titre', '$le_lien', '$la_date', '$les_auteurs', '$moderation', '$la_description')";
					$result_syndic=spip_query($query_syndic);
				}
				$i++;
			}
			spip_query("UPDATE spip_syndic SET syndication='oui', date_syndic=NOW() WHERE id_syndic='$now_id_syndic'");
		}
		else $erreur = _T('avis_echec_syndication_01');
	}
	else $erreur = _T('avis_echec_syndication_02');

	// Ne pas oublier de liberer le verrou
	spip_release_lock($url_syndic);
	return $erreur;
}


function afficher_sites($titre_table, $requete) {
	global $couleur_claire;
	global $connect_id_auteur;

	$activer_messagerie = lire_meta("activer_messagerie");
	$activer_statistiques = lire_meta("activer_statistiques");

	$tranches = afficher_tranches_requete($requete, 3);

	if ($tranches) {
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
			switch ($statut) {
			case 'publie':
				if (acces_restreint_rubrique($id_rubrique))
					$puce = 'puce-verte-anim.gif';
				else
					$puce='puce-verte.gif';
				$title = _T('info_site_reference');
				break;
			case 'prop':
				if (acces_restreint_rubrique($id_rubrique))
					$puce = 'puce-blanche-anim.gif';
				else
					$puce='puce-blanche.gif';
				$title = _T('info_site_attente');
				break;
			case 'refuse':
				if (acces_restreint_rubrique($id_rubrique))
					$puce = 'puce-poubelle-anim.gif';
				else
					$puce='puce-poubelle.gif';
				$title = _T('info_site_refuse');
				break;
			}
			if ($syndication == "off") {
				$puce = 'puce-orange-anim.gif';
				$title = _T('info_panne_site_syndique');
			}

			echo "<a href=\"".$link->getUrl()."\" title=\"$title\">";
			echo "<img src='img_pack/$puce' width='7' height='7' border='0'>&nbsp;&nbsp;";
			if ($moderation == 'oui')
				echo "<i>".typo($nom_site)."</i>";
			else
				echo typo($nom_site);

			echo "</a> &nbsp;&nbsp; <font size='1'>[<a href='$url_site'>"._T('lien_visite_site')."</a>]</font>";
			echo "</td>";

			echo "<td class='arial1' align='right'> &nbsp;";
			if ($syndication == "off") {
				echo "<font color='red'>"._T('info_probleme_grave')." </font>";
			}
			if ($syndication == "oui" or $syndication == "off"){
				echo "<font color='red'>"._T('info_syndication')."</font>";
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

					if (strlen($lesauteurs)>0) echo "<br>"._T('info_auteurs_nombre')." <font color='#336666'>$lesauteurs</font>";
					if (strlen($descriptif)>0) echo "<br>"._T('info_descriptif_nombre')." <font color='#336666'>$descriptif</font>";
					
					echo "</td>";
					
					// $my_sites cache les resultats des requetes sur les sites
					if ($afficher_site) {
						if (!$my_sites[$id_syndic])
							$my_sites[$id_syndic] = spip_fetch_array(spip_query(
								"SELECT * FROM spip_syndic WHERE id_syndic=$id_syndic"));
						echo "<td class='arial1' align='left'>";
						$aff = $my_sites[$id_syndic]['nom_site'];
						if ($my_sites[$id_syndic]['moderation'] == 'oui')
							echo "<i>$aff</i>";
						else
							echo $aff;
						echo "</td>";
					} else echo "<td></td>";

					echo "<td class='arial1' align='right'>";
					
					if ($connect_statut == '0minirezo'){
						if ($statut == "publie"){
							echo "[<a href='".$adresse_page.$lien_url."id_syndic=$id_syndic&supprimer_lien=$id_syndic_article'><font color='black'>"._T('info_bloquer_lien')."</font></a>]";
						
						}
						else if ($statut == "refuse"){
							echo "[<a href='".$adresse_page.$lien_url."id_syndic=$id_syndic&ajouter_lien=$id_syndic_article'>"._T('info_retablir_lien')."</a>]";
						}
						else if ($statut == "dispo") {
							echo "[<a href='".$adresse_page.$lien_url."id_syndic=$id_syndic&ajouter_lien=$id_syndic_article'>"._T('info_valider_lien')."</a>]";
						}
					} else {
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
