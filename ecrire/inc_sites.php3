<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_SITES")) return;
define("_INC_SITES", "1");


// Moderation manuelle des liens
if (!_DIR_RESTREINT AND $GLOBALS['connect_statut'] == '0minirezo') {
	if ($supprimer_lien = $GLOBALS["supprimer_lien"])
		spip_query("UPDATE spip_syndic_articles SET statut='refuse'
		WHERE id_syndic_article='$supprimer_lien'");
	if ($ajouter_lien = $GLOBALS["ajouter_lien"])
		spip_query("UPDATE spip_syndic_articles SET statut='publie'
		WHERE id_syndic_article='$ajouter_lien'");
}

// Function glue_url : le pendant de parse_url (cf doc spip.net/parse_url)
function glue_url ($url){
	if (!is_array($url)){
		return false;
	}
	// scheme
	$uri = (!empty($url['scheme'])) ? $url['scheme'].'://' : '';
	// user & pass
	if (!empty($url['user'])){
		$uri .= $url['user'].':'.$url['pass'].'@';
	}
	// host
	$uri .= $url['host'];
	// port
	$port = (!empty($url['port'])) ? ':'.$url['port'] : '';
	$uri .= $port;
	// path
	$uri .= $url['path'];
// fragment or query
	if (isset($url['fragment'])){
		$uri .= '#'.$url['fragment'];
	} elseif (isset($url['query'])){
		$uri .= '?'.$url['query'];
	}
	return $uri;
}

// Ne pas afficher la partie 'password' du proxy
function no_password_proxy_url($http_proxy) {
	if ($p = @parse_url($http_proxy)
	AND $p['pass']) {
		$p['pass'] = '****';
		$http_proxy = glue_url($p);
	}
	return $http_proxy;
}

//
// Recupere une page sur le net
// et au besoin l'encode dans le charset local
//
// options : get_headers si on veut recuperer les entetes
// taille_max : arreter le contenu au-dela (0 = seulement les entetes)
// Par defaut taille_max = 1Mo.
function recuperer_page($url, $munge_charset=false, $get_headers=false, $taille_max = 1048576) {
	$http_proxy = lire_meta("http_proxy");
	if (!eregi("^http://", $http_proxy))
		$http_proxy = '';
	else
		$via_proxy = " (proxy $http_proxy)";

	spip_log("chargement $url$via_proxy");

	for ($i=0;$i<10;$i++) {	// dix tentatives maximum en cas d'entetes 301...
		$t = @parse_url($url);
		$host = $t['host'];
		if ($t['scheme'] == 'http') {
			$scheme = 'http'; $scheme_fsock='';
		} else {
			$scheme = $t['scheme']; $scheme_fsock=$scheme.'://';
		}
		if (!($port = $t['port'])) $port = 80;
		$query = $t['query'];
		if (!($path = $t['path'])) $path = "/";

		if ($http_proxy) {
			$t2 = @parse_url($http_proxy);
			$proxy_host = $t2['host'];
			$proxy_user = $t2['user'];
			$proxy_pass = $t2['pass'];
			if (!($proxy_port = $t2['port'])) $proxy_port = 80;
			$f = @fsockopen($proxy_host, $proxy_port);
		} else
			$f = @fsockopen($scheme_fsock.$host, $port);

		if ($taille_max == 0)
			$get = 'HEAD';
		else
			$get = 'GET';

		if ($f) {
			if ($http_proxy)
				fputs($f, "$get $scheme://$host" . (($port != 80) ? ":$port" : "") . $path . ($query ? "?$query" : "") . " HTTP/1.0\r\n");
			else
				fputs($f, "$get $path" . ($query ? "?$query" : "") . " HTTP/1.0\r\n");

			fputs($f, "Host: $host\r\n");
			fputs($f, "User-Agent: SPIP-".$GLOBALS['spip_version_affichee']." (http://www.spip.net/)\r\n");

			// Proxy authentifiant
			if ($proxy_user) {
				fputs($f, "Proxy-Authorization: Basic "
				. base64_encode($proxy_user . ":" . $proxy_pass) . "\r\n");
			}
			// Referer = c'est nous !
			if ($referer = lire_meta("adresse_site"))
				fputs($f, "Referer: $referer/\r\n");

			// Fin des entetes envoyees par SPIP
			fputs($f,"\r\n");

			// Reponse du serveur distant
			$s = trim(fgets($f, 16384));
			if (ereg('^HTTP/[0-9]+\.[0-9]+ ([0-9]+)', $s, $r)) {
				$status = $r[1];
			}
			else return;

			// Entetes HTTP de la page
			$headers = '';
			while ($s = trim(fgets($f, 16384))) {
				$headers .= $s."\n";
				if (eregi('^Location: (.*)', $s, $r)) {
					$location = $r[1];
					spip_log($s);
				}
			}
			if ($status >= 300 AND $status < 400 AND $location) $url = $location;
			else if ($status != 200) return;
			else break; # ici on est content
			fclose($f);
			$f = false;
		}
		else {
			if (!$GLOBALS['tester_proxy'])
				$f = @fopen($url, "rb");
			break;
		}
	}

	// Contenu de la page
	if (!$f) {
		spip_log("ECHEC chargement $url$via_proxy");
		$result = '';
	} else {
		while (!feof($f) AND strlen($result)<$taille_max)
			$result .= fread($f, 16384);
		fclose($f);
	}

	// Faut-il l'importer dans notre charset local ?
	if ($munge_charset) {
		include_ecrire('inc_charsets.php3');
		$result = transcoder_page ($result, $headers);
	}

	return ($get_headers ? $headers."\n" : '').$result;
}


function trouver_format($texte) {
	$syndic_version = '';
	
	// Chercher un numero de version
	if (ereg('(rss|feed)[[:space:]](([^>]*[[:space:]])*)version[[:space:]]*=[[:space:]]*[\'"]([-_a-zA-Z0-9\.]+)[\'"]', $texte, $regs)) {
		$syndic_version = $regs[4];
	} else {
		if (strpos($texte,'rdf:RDF')) {
			$syndic_version = '1.0';
		}
	}
	return $syndic_version;
}

function analyser_site($url) {
	include_ecrire("inc_filtres.php3");

	$texte = recuperer_page($url, true);
	if (!$texte) return false;
	$result = '';
	
	// definir les regexp pour decoder
	// il faut deux etapes pour link sous Atom0.3
	$syndic_version = trouver_format($texte);

	switch ($syndic_version) {
		case "1.0" :
			$site_regexp = array(
				'channel'     => '<channel[^>]*>(.*)</channel>',
				'link1'       => '<link[^>]*>([^<]*)</link>',
				'link2'       => '(.*)',
				'item'        => '<item[^>]*>',
				'description' => '<description[^>]*>([^<]*)</description>'
			);
			break;
		case "0.3" :
			$site_regexp = array(
				'channel'     => '<feed[^>]*>(.*)</feed>',
				'link1'       => '<link[[:space:]]([^<]*)rel[[:space:]]*=[[:space:]]*[\'"]alternate[\'"]([^<]*)/>',
				'link2'       => 'href[[:space:]]*=[[:space:]]*[\'"]([^["|\']]+)[\'"]',
				'item'        => '<entry[^>]*>',
				'description' => '<tagline[^>]*>([^<]*)</tagline>'
			);
			break;
		case "0.91" :
		case "0.92" :
		case "2.0" :
		default :	# backend defectueux, mais il faut une regexp
			$site_regexp = array(
				'channel'     =>'<channel[^>]*>(.*)</channel>',
				'link1'       => '<link[^>]*>([^<]*)</link>',
				'link2'       => '(.*)',
				'item'        => '<item[^>]*>',
				'description' => '<description[^>]*>([^<]*)</description>'
			);
			break;
	}

	if (ereg($site_regexp['channel'], $texte, $regs)) {
		$result['syndic'] = true;
		$result['url_syndic'] = $url;
		$channel = $regs[1];
		if (ereg('<title[^>]*>(([^<]|<[^/]|</[^t]>|</t[^i]>)*)</title>', $channel, $r))
			$result['nom_site'] = supprimer_tags(filtrer_entites($r[1]));
		if (ereg($site_regexp['link1'], $channel, $regs)) {
			if (ereg($site_regexp['link2'], $regs[1].$regs[2], $r))
				$result['url_site'] = filtrer_entites($r[1]);
		}

		// si le channel n'a pas de description, ne pas prendre celle d'un article
		list($channel_desc,$drop) = split($site_regexp['item'], $channel, 2);
		if (ereg($site_regexp['description'], $channel_desc, $r))
			$result['descriptif'] = filtrer_entites($r[1]);
	}
	else {
		$result['syndic'] = false;
		$result['url_site'] = $url;
		if (eregi('<head>(.*)', $texte, $regs))
			$head = filtrer_entites(eregi_replace('</head>.*', '', $regs[1]));
		else
			$head = $texte;
		if (eregi('<title[^>]*>(.*)', $head, $regs))
			$result['nom_site'] = filtrer_entites(supprimer_tags(eregi_replace('</title>.*', '', $regs[1])));
		if (eregi('<meta[[:space:]]+(name|http\-equiv)[[:space:]]*=[[:space:]]*[\'"]?description[\'"]?[[:space:]]+(content|value)[[:space:]]*=[[:space:]]*[\'"]([^>]+)[\'"]>', $head, $regs))
			$result['descriptif'] = filtrer_entites(supprimer_tags($regs[3]));
	}
	return $result;
}

// Inserer les references aux fichiers joints
function traiter_les_enclosures_rss($enclosures,$id_syndic,$lelien) {

	list($id_syndic_article) = spip_fetch_array(spip_query(
	"SELECT id_syndic_article FROM spip_syndic_articles
	WHERE id_syndic=$id_syndic AND url='$lelien'"));

	// deja vu ?
	if (spip_num_rows(spip_query("SELECT id_document FROM spip_documents_syndic
	WHERE id_syndic_article=$id_syndic_article")) > 0)
		return;

	foreach ($enclosures as $enclosure) {
		$enclosure = $enclosure[0];
		// url et type sont obligatoires
		if (preg_match(',[[:space:]]url=[\'"]?(https?://[^\'"]*),i',
		$enclosure, $enc_regs_url)
		AND preg_match(',[[:space:]]type=[\'"]?([^\'"]*),i',
		$enclosure, $enc_regs_type)) {

			$url = urldecode($enc_regs_url[1]);
			$type = $enc_regs_type[1];

			// Verifier que le content-type nous convient
			list($id_type) = spip_fetch_array(spip_query("SELECT id_type
			FROM spip_types_documents WHERE mime_type='$type'"));
			if (!$id_type) {die ("pas de id_type pour $type");}#continue;

			// length : optionnel (non bloquant)
			if (preg_match(',[[:space:]]length=[\'"]?([^\'"]*),i',
			$enclosure, $enc_regs_length)) {
				$taille = intval($enc_regs_length[1]);
			} else {
				$taille = 0;
			}

			// Inserer l'enclosure dans la table spip_documents
			if ($t = spip_fetch_array(spip_query("SELECT id_document FROM
			spip_documents WHERE fichier='$url' AND distant='oui'")))
				$id_document = $t['id_document'];
			else {
				spip_query("INSERT INTO spip_documents
				(id_type, titre, fichier, date, distant, taille, mode)
				VALUES ($id_type,'','$url',NOW(),'oui',$taille, 'document')");
				$id_document = spip_insert_id();
			}

			// lier avec l'article syndique
			spip_query("INSERT INTO spip_documents_syndic
			(id_document, id_syndic, id_syndic_article)
			VALUES ($id_document, $id_syndic, $id_syndic_article)");

			$n++;
		}
	}

	return $n; #nombre d'enclosures integrees
}


function syndic_a_jour($now_id_syndic, $statut = 'off') {
	include_ecrire("inc_texte.php3");
	include_ecrire("inc_filtres.php3");

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
	if (!spip_get_lock("syndication $url_syndic")) return;

	spip_query("UPDATE spip_syndic SET syndication='$statut', date_syndic=NOW() WHERE id_syndic='$now_id_syndic'");

	$le_retour = recuperer_page($url_syndic, true);
	$erreur = "";
	$les_auteurs_du_site = "";

	// definir les regexp pour decoder
	// il faut deux etapes pour link sous Atom0.3
	$syndic_version = trouver_format($le_retour);
	switch ($syndic_version) {
		case "0.91" :
		case "0.92" :
		case "2.0" :
			$syndic_regexp = array(
				'item'           => ',<item[>[:space:]],i',
				'itemfin'        => '</item>',
				'link1'          => '<link[^>]*>([^<]*)</link>',
				'link2'          => '(.*)',
				'date1'          => ',<pubDate>([^<]*)</pubDate>,Uims',
				'date2'          => ',<[[:alpha:]]{2}:date>([^<]*)</[[:alpha:]]{2}:date>,Uims',
				'author1'        => '<[[:alpha:]]{2}:[Cc]reator>([^<]*)</[[:alpha:]]{2}:[Cc]reator>',
				'author2'        => '(.*)',
				'authorbis'      => '<author>([^<]*)</author>',
				'description'    => ',<description[^>]*>(.*?)</description[^>]*>,ims',
				'descriptionbis' =>     ',<content[^>]*>(.*?)</content[^>]*>,ims'
			);
			break;
		case "1.0" :
			$syndic_regexp = array(
				'item'           => ',<item[>[:space:]],i',
				'itemfin'        => '</item>',
				'link1'          => '<link[^>]*>([^<]*)</link>',
				'link2'          => '(.*)',
				'date1'          => ',<[[:alpha:]]{2}:date>([^<]*)</[[:alpha:]]{2}:date>,Uims',
				'date2'          => ',<pubDate>([^<]*)</pubDate>,Uims',
				'author1'        => '<[[:alpha:]]{2}:[Cc]reator>([^<]*)</[[:alpha:]]{2}:[Cc]reator>',
				'author2'        => '(.*)',
				'authorbis'      => '<author>([^<]*)</author>',
				'description'    => ',<description[^>]*>(.*?)</description[^>]*>,ims',
				'descriptionbis' =>     ',<content[^>]*>(.*?)</content[^>]*>,ims'
			);
			break;
		case "0.3" :
			$syndic_regexp = array('channel'=>'<feed[^>]*>(.*)</feed>',
				'item'           => ',<entry[>[:space:]],i',
				'itemfin'        => '</entry>',
				'link1'          => '<link[[:space:]]([^<]*)rel[[:space:]]*=[[:space:]]*[\'"]alternate[\'"]([^<]*)/>',
				'link2'          => 'href[[:space:]]*=[[:space:]]*[\'"]([^"|^\']+)[\'"]',
				'date1'          => ',<modified>([^<]*)</modified>,Uims',
				'date2'          => ',<issued>([^<]*)</issued>,Uims',
				'author1'        => '<author>(.*<name>.*</name>.*)</author>',
				'author2'        => '<name>([^<]*)</name>',
				'authorbis'      => '<[[:alpha:]]{2}:[Cc]reator>([^<]*)</[[:alpha:]]{2}:[Cc]reator>',
				'description'    => ',<summary[^>]*>(.*?)</summary[^>]*>,ims',
				'descriptionbis' => ',<content[^>]*>(.*?)</content[^>]*>,ims'
			);
			break;
		default :
			// format de syndication non reconnu
			$erreur = _T('avis_echec_syndication_02');
			$le_retour = '';
			break;
	}

	if ($le_retour) {
		// Echapper les CDATA
		$echappe_cdata = array();
		if (preg_match_all(',<!\[CDATA\[(.*)]]>,Uims', $le_retour, $regs, PREG_SET_ORDER)) {
			foreach ($regs as $n => $reg) {
				$echappe_cdata[$n] = $reg[1];
				$le_retour = str_replace($reg[0], "@@@SPIP_CDATA$n@@@",
					$le_retour);
			}
		}

		// chercher un auteur dans le fil au cas ou les entry n'en auraient pas
		list($channel_head) = preg_split($syndic_regexp['item'], $le_retour, 2);
		if (ereg($syndic_regexp['author1'],$channel_head,$mat)) {
			if (ereg($syndic_regexp['author2'],$mat[1],$match))
				$les_auteurs_du_site = $match[1];
		}


		$items = array();
		while (preg_match($syndic_regexp['item'],$le_retour,$regs)) {
			$debut_item=strpos($le_retour,$regs[0]);
			$fin_item=strpos($le_retour,$syndic_regexp['itemfin'])+strlen($syndic_regexp['itemfin']);
			$items[]=substr($le_retour,$debut_item,$fin_item-$debut_item);

			$debut_texte=substr($le_retour,"0",$debut_item);
			$fin_texte=substr($le_retour,$fin_item,strlen($le_retour));
			$le_retour=$debut_texte.$fin_texte;
		}
		if (count($items)) {
			$now = time();
			foreach ($items as $item) {

				$data = array();
				unset($error);

				// URL (obligatoire)
				if (ereg($syndic_regexp['link1'],$item,$match)) {
					$link_match = $match[1].$match[2];
					if (ereg($syndic_regexp['link2'], $link_match, $mat))
						$data['url'] = addslashes(filtrer_entites($mat[1]));
				}
				// guid n'est un URL que si marque de <guid permalink="true">
				else if (eregi("<guid.*>[[:space:]]*(https?:[^<]*)</guid>",$item,$match))
					$data['url'] = addslashes(filtrer_entites($match[1]));
				else $error = 'url';
				# note http://static.userland.com/gems/backend/gratefulDead.xml
				# n'a que des enclosures, sans url ni titre... tant pis...

				// Titre (semi-obligatoire)
				if
(preg_match(",<title>(.*?)</title>,ims",$item,$match))
					$data['titre'] = $match[1];
				else if (($syndic_version==0.3) AND (strlen($letitre)==0))
					if (ereg('title[[:space:]]*=[[:space:]]*[\'"]([^"\']+)[\'"]',$link_match,$mat))
						$data['titre']=$mat[1]; 
				if (!$data['titre'] = trim($data['titre']))
					$data['titre'] = _T('ecrire:info_sans_titre');

				// Date
				$la_date = "";
				if (preg_match(",<date>([^<]*)</date>,Uims",$item,$match))
					$la_date = $match[1];
				if (preg_match($syndic_regexp['date1'],$item,$match))
					$la_date = $match[1];
				else if (preg_match($syndic_regexp['date2'],$item,$match))
					$la_date = $match[1];
				if ($la_date) {
					// http://www.w3.org/TR/NOTE-datetime
					if (ereg('^([0-9]+-[0-9]+-[0-9]+T[0-9]+:[0-9]+(:[0-9]+)?)(\.[0-9]+)?(Z|([-+][0-9][0-9]):[0-9]+)$', $la_date, $match)) {
						$la_date = str_replace("T", " ", $match[1])." GMT";
						$la_date = strtotime($la_date) - intval($match[5]) * 3600;
					}
					else
						$la_date = strtotime($la_date);
				}
				if ($la_date < $now - 365 * 24 * 3600 OR $la_date > $now + 48 * 3600)
					$la_date = $now;

				// Auteur
				if (ereg($syndic_regexp['author1'],$item,$mat)) {
					if (ereg($syndic_regexp['author2'],$mat[1],$match))
						$data['lesauteurs'] = $match[1];
				}
				else if (ereg($syndic_regexp['authorbis'],$item,$match))
					$data['lesauteurs'] = $match[1];
				else $data['lesauteurs'] = $les_auteurs_du_site;

				// Description
				if (preg_match($syndic_regexp['description'],$item,$match)) {
					$data['descriptif'] = $match[1];
				}
				else if (preg_match($syndic_regexp['descriptionbis'],$item,$match)) {
					$data['descriptif'] = $match[1];
				} else $data['descriptif'] = "";

				// Nettoyer les donnees et remettre les CDATA en place
				foreach ($data as $var => $val) {
					$data[$var] = filtrer_entites($data[$var]);
					foreach ($echappe_cdata as $n => $e)
						$data[$var] = str_replace("@@@SPIP_CDATA$n@@@",$e, $data[$var]);
					$data[$var] = trim(textebrut($data[$var]));
					if ($var == 'descriptif')
						$data[$var] = couper($data[$var], 300);
				}

				// Creer le lien s'il est nouveau - cle=(id_syndic,url)
				$le_lien = addslashes($data['url']);
				if (!$error)
				if (spip_num_rows(spip_query(
					"SELECT * FROM spip_syndic_articles
					WHERE url='".addslashes($data['url'])."'
					AND id_syndic=$now_id_syndic"
				)) == 0 and !spip_sql_error()) {
					spip_query("INSERT INTO spip_syndic_articles
					(id_syndic, url, date, statut) VALUES
					('$now_id_syndic', '$le_lien',
					FROM_UNIXTIME($la_date), '$moderation')");
					$liens_ajoutes ++;
				}

				// Mise a jour du contenu (titre,auteurs,description)
				if (!$error)
				spip_query ("UPDATE spip_syndic_articles SET
				titre='".addslashes($data['titre'])."',
				lesauteurs='".addslashes($data['lesauteurs'])."',
				descriptif='".addslashes($data['descriptif'])."'
				WHERE id_syndic='$now_id_syndic' AND url='$le_lien'");

				// Honorer le <lastbuilddate> en forcant la date
				if (!$error)
				if (preg_match(',<(lastbuilddate|modified)>([^<>]+)</\1>,i',
				$item, $regs)
				AND $lastbuilddate = strtotime(trim($regs[2]))
				// pas dans le futur
				AND $lastbuilddate < time()) {
					spip_query("UPDATE spip_syndic_articles
					SET date = FROM_UNIXTIME($lastbuilddate)
					WHERE id_syndic='$now_id_syndic' AND url='$le_lien'");
				}

				// Attraper les URLs des pieces jointes <enclosure>
				if (!$error)
				if (preg_match_all(',<enclosure[[:space:]][^<>]+>,i', $item,
				$enclosures, PREG_SET_ORDER)) {
					traiter_les_enclosures_rss($enclosures,$now_id_syndic,$le_lien);
				}
			}

			spip_query("UPDATE spip_syndic SET syndication='oui' WHERE id_syndic='$now_id_syndic'");
		}
		else $erreur = _T('avis_echec_syndication_01');
	}
	else $erreur = _T('avis_echec_syndication_02');

	// Ne pas oublier de liberer le verrou
	spip_release_lock($url_syndic);

	if ($liens_ajoutes) {
		spip_log("Syndication: $liens_ajoutes nouveau(x) lien(s)");
		include_ecrire('inc_rubriques.php3');
		calculer_rubriques();
	}

	return $erreur;
}


function afficher_sites($titre_table, $requete) {
	global $couleur_claire, $couleur_foncee, $spip_lang_left, $spip_lang_right;
	global $connect_id_auteur;

	$tranches = afficher_tranches_requete($requete, 3);

	if ($tranches) {
//		debut_cadre_relief("site-24.gif");
		if ($titre_table) echo "<div style='height: 12px;'></div>";
		echo "<div class='liste'>";
		bandeau_titre_boite2($titre_table, "site-24.gif", $couleur_claire, "black");
		echo "<table width='100%' cellpadding='2' cellspacing='0' border='0'>";

		echo $tranches;

	 	$result = spip_query($requete);
		$num_rows = spip_num_rows($result);

		$ifond = 0;
		$premier = true;
		
		$compteur_liste = 0;
		while ($row = spip_fetch_array($result)) {
			$vals = '';
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

			//echo "<tr bgcolor='$couleur'>";

			//echo "<td class='arial2'>";
			$link = new Link("sites.php3?id_syndic=$id_syndic");
			switch ($statut) {
			case 'publie':
				if (acces_restreint_rubrique($id_rubrique))
					$puce = 'puce-verte-anim.gif';
				else
					$puce='puce-verte-breve.gif';
				$title = _T('info_site_reference');
				break;
			case 'prop':
				if (acces_restreint_rubrique($id_rubrique))
					$puce = 'puce-orange-anim.gif';
				else
					$puce='puce-orange-breve.gif';
				$title = _T('info_site_attente');
				break;
			case 'refuse':
				if (acces_restreint_rubrique($id_rubrique))
					$puce = 'puce-poubelle-anim.gif';
				else
					$puce='puce-poubelle-breve.gif';
				$title = _T('info_site_refuse');
				break;
			}
			if ($syndication == "off") {
				$puce = 'puce-orange-anim.gif';
				$title = _T('info_panne_site_syndique');
			}

			$s = "<a href=\"".$link->getUrl()."\" title=\"$title\">";

			if ($spip_display != 1 AND $spip_display != 4 AND lire_meta('image_process') != "non") {
				include_ecrire("inc_logos.php3");
				$logo = decrire_logo("siteon$id_syndic");
				if ($logo) {
					$fichier = $logo[0];
					$taille = $logo[1];
					$taille_x = $logo[3];
					$taille_y = $logo[4];
					$taille = image_ratio($taille_x, $taille_y, 26, 20);
					$w = $taille[0];
					$h = $taille[1];
					$fid = $logo[2];
					$hash = calculer_action_auteur ("reduire $w $h");

					$s.= "<div style='float: $spip_lang_right; margin-top: -2px; margin-bottom: -2px;'><img src='../spip_image_reduite.php3?img="._DIR_IMG."$fichier&taille_x=$w&taille_y=$h&hash=$hash&hash_id_auteur=$connect_id_auteur' alt='$fichier' width='$w' height='$h' border='0'></div>";
					
				}
			}


			$s .= http_img_pack($puce, $statut, "width='7' height='7' border='0'") ."&nbsp;&nbsp;";
			
			$s .= typo($nom_site);
			/*if ($moderation == 'oui')
				$s .= "<i>".typo($nom_site)."</i>";
			else
				$s .= typo($nom_site);
			*/
			$s .= "</a> &nbsp;&nbsp; <font size='1'>[<a href='$url_site'>"._T('lien_visite_site')."</a>]</font>";
			$vals[] = $s;
			
			//echo "</td>";

			$s = "";
			//echo "<td class='arial1' align='right'> &nbsp;";
			if ($syndication == "off") {
				$s .= "<font color='red'>"._T('info_probleme_grave')." </font>";
			}
			if ($syndication == "oui" or $syndication == "off"){
				$s .= "<font color='red'>"._T('info_syndication')."</font>";
			}
				$vals[] = $s;
			//echo "</td>";					
			//echo "<td class='arial1'>";
			$s = "";
			if ($syndication == "oui" OR $syndication == "off") {
				$result_art = spip_query("SELECT COUNT(*) FROM spip_syndic_articles WHERE id_syndic='$id_syndic'");
				list($total_art) = spip_fetch_array($result_art);
				$s .= " $total_art "._T('info_syndication_articles');
			} else {
				$s .= "&nbsp;";
			}
			$vals[] = $s;
			//echo "</td>";					
			//echo "</tr></n>";
			$table[] = $vals;
		}
		spip_free_result($result);
		
		$largeurs = array('','','');
		$styles = array('arial11', 'arial1', 'arial1');
		afficher_liste($largeurs, $table, $styles);
		echo "</table>";
		//fin_cadre_relief();
		echo "</div>\n";
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
	global $spip_lang_rtl, $spip_lang_right;

	$adresse_page = substr($REQUEST_URI, strpos($REQUEST_URI, "/ecrire")+8, strlen($REQUEST_URI));
	$adresse_page = ereg_replace("\&?debut\_liste\_sites\[$n_liste_sites\]\=[0-9]+","",$adresse_page);
	$adresse_page = ereg_replace("\&?(ajouter\_lien|supprimer_lien)\=[0-9]+","",$adresse_page);

	if (ereg("\?",$adresse_page)) $lien_url = "&";
	else $lien_url = "?";

	$lien_url .= "debut_liste_sites[".$n_liste_sites."]=".$debut_liste_sites[$n_liste_sites]."&";

	$cols = 2;
	if ($connect_statut == '0minirezo') $cols ++;
	if ($afficher_site) $cols ++;

	$tranches = afficher_tranches_requete($requete, $cols);

	if (strlen($tranches)) {

		if ($titre_table) echo "<div style='height: 12px;'></div>";
		echo "<div class='liste'>";
		//debut_cadre_relief("rubrique-24.gif");

		if ($titre_table) {
			bandeau_titre_boite2($titre_table, "site-24.gif", "#999999", "white");
		}
		echo "<table width=100% cellpadding=3 cellspacing=0 border=0 background=''>";

		echo $tranches;

		$result = spip_query($requete);

		$table = '';
		while ($row = spip_fetch_array($result)) {
			$vals = '';

			$id_syndic_article=$row["id_syndic_article"];
			$id_syndic=$row["id_syndic"];
			$titre=typo($row["titre"]);
			$url=$row["url"];
			$date=$row["date"];
			$lesauteurs=typo($row["lesauteurs"]);
			$statut=$row["statut"];
			$descriptif=propre($row["descriptif"]);

			
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

			$s = http_img_pack($puce, $statut, "width='7' height='7' border='0'");
			$vals[] = $s;

			$s = "<a href='$url'>$titre</a>";
			if (strlen($lesauteurs) > 0) $s .= " ($lesauteurs)";

			// S'il y a des fichiers joints (enclosures), on les affiche ici
			if (spip_num_rows($q = spip_query("SELECT docs.* FROM spip_documents AS docs, spip_documents_syndic AS lien WHERE lien.id_syndic_article = $id_syndic_article AND lien.id_document = docs.id_document"))) {
				include_ecrire('inc_documents.php3');
				while ($t = spip_fetch_array($q)) {
					$s .= '&nbsp;<a href="' . $t['fichier'] . '">'
					. http_img_pack('attachment.gif', 'height="15" width="15"
					border="0" title="'.entites_html($t['fichier']).'"').'</a>';
				}
			}

			if (strlen($descriptif) > 0) $s .= "<div class='arial1'>$descriptif</div>";
			$vals[] = $s;

			// $my_sites cache les resultats des requetes sur les sites
			if ($afficher_site) {
				if (!$my_sites[$id_syndic])
					$my_sites[$id_syndic] = spip_fetch_array(spip_query(
						"SELECT * FROM spip_syndic WHERE id_syndic=$id_syndic"));

				$aff = $my_sites[$id_syndic]['nom_site'];
				if ($my_sites[$id_syndic]['moderation'] == 'oui')
					$s = "<i>$aff</i>";
				else
					$s = $aff;
					
				$s = "<a href='sites.php3?id_syndic=$id_syndic'>$aff</a>";

				$vals[] = $s;
			}

			
			if ($connect_statut == '0minirezo'){
				if ($statut == "publie"){
					$s =  "[<a href='".$adresse_page.$lien_url."id_syndic=$id_syndic&supprimer_lien=$id_syndic_article'><font color='black'>"._T('info_bloquer_lien')."</font></a>]";
				
				}
				else if ($statut == "refuse"){
					$s =  "[<a href='".$adresse_page.$lien_url."id_syndic=$id_syndic&ajouter_lien=$id_syndic_article'>"._T('info_retablir_lien')."</a>]";
				}
				else if ($statut == "dispo") {
					$s = "[<a href='".$adresse_page.$lien_url."id_syndic=$id_syndic&ajouter_lien=$id_syndic_article'>"._T('info_valider_lien')."</a>]";
				}
				$vals[] = $s;
			}
					
			$table[] = $vals;
		}
		spip_free_result($result);

		
		if ($afficher_site) {
			$largeurs = array(7, '', '100');
			$styles = array('','arial11', 'arial1');
		} else {
			$largeurs = array(7, '');
			$styles = array('','arial11');
		}
		if ($connect_statut == '0minirezo') {
			$largeurs[] = '80';
			$styles[] = 'arial1';
		}
		
		afficher_liste($largeurs, $table, $styles);

		echo "</TABLE>";
		//fin_cadre_relief();
		echo "</div>";
	}
	return $tous_id;
}


//
// Effectuer la syndication d'un unique site, retourne 0 si aucun a faire.
//

function executer_une_syndication() {
	$id_syndic = 0;
	if ($row = spip_fetch_array(spip_query("SELECT * FROM spip_syndic WHERE syndication='sus' AND statut='publie' AND date_syndic < DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY date_syndic LIMIT 0,1"))) {
		$id_syndic = $row["id_syndic"];
		syndic_a_jour($id_syndic);
	}

	if ($row = spip_fetch_array(spip_query("SELECT * FROM spip_syndic WHERE syndication='oui' AND statut='publie' AND date_syndic < DATE_SUB(NOW(), INTERVAL 2 HOUR) ORDER BY date_syndic LIMIT 0,1"))) {
		$id_syndic = $row["id_syndic"];
		syndic_a_jour($id_syndic, 'sus');
	}
	return $id_syndic;
}

?>
