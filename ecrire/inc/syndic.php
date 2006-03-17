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

//
// Effectuer la syndication d'un unique site, retourne 0 si aucun a faire.
//

function executer_une_syndication() {
	$id_syndic = 0;

	## valeurs modifiables dans mes_options
	## attention il est tres mal vu de prendre une periode < 20 minutes
	define('_PERIODE_SYNDICATION', 2*60);
	define('_PERIODE_SYNDICATION_SUSPENDUE', 24*60);

	// On va tenter un site 'sus' ou 'off' de plus de 24h, et le passer en 'off'
	// s'il echoue
	$s = spip_query("SELECT * FROM spip_syndic
	WHERE syndication IN ('sus','off')
	AND statut='publie'
	AND date_syndic < DATE_SUB(NOW(), INTERVAL
	"._PERIODE_SYNDICATION_SUSPENDUE." MINUTE)
	ORDER BY date_syndic LIMIT 1");
	if ($row = spip_fetch_array($s)) {
		$id_syndic = $row["id_syndic"];
		syndic_a_jour($id_syndic, 'off');
	}

	// Et un site 'oui' de plus de 2 heures, qui passe en 'sus' s'il echoue
	$s = spip_query("SELECT * FROM spip_syndic
	WHERE syndication='oui'
	AND statut='publie'
	AND date_syndic < DATE_SUB(NOW(), INTERVAL "._PERIODE_SYNDICATION." MINUTE)
	ORDER BY date_syndic LIMIT 1");
	if ($row = spip_fetch_array($s)) {
		$id_syndic = $row["id_syndic"];
		syndic_a_jour($id_syndic, 'sus');
	}
	return $id_syndic;
}


// A partir d'un <dc:subject> ou autre essayer de recuperer
// le mot et son url ; on cree <a href="url" rel="tag">mot</a>
function creer_tag($mot,$type,$url) {
	if (!strlen($mot = trim($mot))) return '';
	$mot = "<a rel=\"tag\">$mot</a>";
	if ($url)
		$mot = inserer_attribut($mot, 'href', $url);
	if ($type)
		$mot = inserer_attribut($mot, 'rel', $type);
	return $mot;
}

function ajouter_tags($matches, $item) {
	include_spip('inc/filtres');
	$tags = array();
	foreach ($matches as $match) {
		$type = ($match[3] == 'category' OR $match[3] == 'directory')
			? 'directory':'tag';
		$mot = supprimer_tags($match[0]);
		if (!strlen($mot)) break;
		// rechercher un url
		if ($url = extraire_attribut($match[0], 'domain')
		OR $url = extraire_attribut($match[0], 'resource')
		OR $url = extraire_attribut($match[0], 'url'))
			{}

		## cas particuliers
		else if (extraire_attribut($match[0], 'scheme') == 'urn:flickr:tags') {
			foreach(explode(' ', $mot) as $petit)
				if ($t = creer_tag($petit, $type,
				'http://www.flickr.com/photos/tags/'.rawurlencode($petit).'/'))
					$tags[] = $t;
			$mot = '';
		} else {
			# type del.icio.us
			foreach(explode(' ', $mot) as $petit)
				if (preg_match(',<rdf[^>]* resource=["\']([^>]*/'
				.preg_quote(rawurlencode($petit),',').')["\'],i',
				$item, $m)) {
					$mot = '';
					if ($t = creer_tag($petit, $type, $m[1]))
						$tags[] = $t;
				}
		}

		if ($t = creer_tag($mot, $type, $url))
			$tags[] = $t;
	}
	return $tags;
}


// Retablit le contenu des blocs [[CDATA]] dans un tableau
function cdata_echappe_retour(&$table, &$echappe_cdata) {
	foreach ($table as $var => $val) {
		$table[$var] = filtrer_entites($table[$var]);
		foreach ($echappe_cdata as $n => $e)
			$table[$var] = str_replace("@@@SPIP_CDATA$n@@@",
				$e, $table[$var]);
	}
}


// prend un fichier backend et retourne un tableau des items lus,
// et une chaine en cas d'erreur
function analyser_backend($rss, $url_syndic='') {
	include_spip('inc/texte'); # pour couper()

	$rss = pipeline('pre_syndication', $rss);

	// Echapper les CDATA
	$echappe_cdata = array();
	if (preg_match_all(',<!\[CDATA\[(.*)]]>,Uims', $rss,
	$regs, PREG_SET_ORDER)) {
		foreach ($regs as $n => $reg) {
			$echappe_cdata[$n] = $reg[1];
			$rss = str_replace($reg[0], "@@@SPIP_CDATA$n@@@", $rss);
		}
	}

	// supprimer les commentaires
	$rss = preg_replace(',<!--\s+.*\s-->,Ums', '', $rss);

	// simplifier le backend, en supprimant les espaces de nommage type "dc:"
	$rss = preg_replace(',<(/?)(dc):,i', '<\1', $rss);

	// chercher auteur/lang dans le fil au cas ou les items n'en auraient pas
	list($header) = preg_split(',<(item|entry)[:[:space:]>],', $rss, 2);
	if (preg_match_all(
	',<(author|creator)>(.*)</\1>,Uims',
	$header, $regs, PREG_SET_ORDER)) {
		$les_auteurs_du_site = array();
		foreach ($regs as $reg) {
			$nom = $reg[2];
			if (preg_match(',<name>(.*)</name>,Uims', $nom, $reg))
				$nom = $reg[1];
			$les_auteurs_du_site[] = trim(textebrut(filtrer_entites($nom)));
		}
		$les_auteurs_du_site = join(', ', array_unique($les_auteurs_du_site));
	} else
		$les_auteurs_du_site = '';

	if (preg_match(',<([^>]*xml:)?lang(uage)?'.'>([^<>]+)<,i',
	$header, $match))
		$langue_du_site = $match[3];

	$items = array();
	if (preg_match_all(',<(item|entry)([:[:space:]][^>]*)?'.
	'>(.*)</\1>,Uims',$rss,$r, PREG_PATTERN_ORDER))
		$items = $r[0];

	//
	// Analyser chaque <item>...</item> du backend et le transformer en tableau
	//

	if (!count($items)) return _T('avis_echec_syndication_01');

	foreach ($items as $item) {
		$data = array();

		// URL (semi-obligatoire, sert de cle)
		if (preg_match(
		',<link[^>]*[[:space:]]rel=["\']?alternate[^>]*>(.*)</link>,Uims',
		$item, $regs))
			$data['url'] = $regs[1];
		else if (preg_match(',<link[^>]*[[:space:]]rel=.alternate[^>]*>,Uims',
		$item, $regs))
			$data['url'] = extraire_attribut($regs[0], 'href');
		else if (preg_match(',<link[^>]*>(.*)</link>,Uims', $item, $regs))
			$data['url'] = $regs[1];
		else if (preg_match(',<link[^>]*>,Uims', $item, $regs))
			$data['url'] = extraire_attribut($regs[0], 'href');
		// guid n'est un URL que si marque de <guid permalink="true">
		else if (preg_match(',<guid.*>[[:space:]]*(https?:[^<]*)</guid>,Uims',
		$item, $regs))
			$data['url'] = $regs[1];
		else if (preg_match(',<enclosure[^>]*>,ims', $item, $regs)
		AND $url = extraire_attribut($regs[0], 'url'))
			$data['url'] = $url;
		else
			$data['url'] = '';

		$data['url'] = url_absolue(filtrer_entites($data['url']), $url_syndic);

		// Titre (semi-obligatoire)
		if (preg_match(",<title>(.*?)</title>,ims",$item,$match))
			$data['titre'] = $match[1];
			else if (preg_match(',<link[[:space:]][^>]*>,Uims',$item,$mat)
			AND $title = extraire_attribut($mat[0], 'title'))
				$data['titre'] = $title; 
		if (!$data['titre'] = trim($data['titre']))
			$data['titre'] = _T('ecrire:info_sans_titre');

		// Date
		$la_date = '';
		if (preg_match(',<(published|modified|issued)>([^<]*)<,Uims',
		$item,$match))
			$la_date = my_strtotime($match[2]);
		if (!$la_date AND
		preg_match(',<(pubdate)>([^<]*)<,Uims',$item, $match))
			$la_date = my_strtotime($match[2]);
		if (!$la_date AND
		preg_match(',<([a-z]+:date)>([^<]*)<,Uims',$item,$match))
			$la_date = my_strtotime($match[2]);
		if (!$la_date AND
		preg_match(',<date>([^<]*)<,Uims',$item,$match))
			$la_date = my_strtotime($match[1]);

		// controle de validite de la date
		// pour eviter qu'un backend errone passe toujours devant
		// (note: ca pourrait etre defini site par site, mais ca risque d'etre
		// plus lourd que vraiment utile)
		if ($GLOBALS['controler_dates_rss']) {
			if ($la_date < time() - 365 * 24 * 3600
			OR $la_date > time() + 48 * 3600)
				$la_date = time();
		}

		$data['date'] = $la_date;

		// Honorer le <lastbuilddate> en forcant la date
		if (preg_match(',<(lastbuilddate|updated|modified)>([^<>]+)</\1>,i',
		$item, $regs)
		AND $lastbuilddate = my_strtotime(trim($regs[2]))
		// pas dans le futur
		AND $lastbuilddate < time())
			$data['lastbuilddate'] = $lastbuilddate;

		// Auteur(s)
		if (preg_match_all(
		',<(author|creator)>(.*)</\1>,Uims',
		$item, $regs, PREG_SET_ORDER)) {
			$auteurs = array();
			foreach ($regs as $reg) {
				$nom = $reg[2];
				if (preg_match(',<name>(.*)</name>,Uims', $nom, $reg))
					$nom = $reg[1];
				$auteurs[] = trim(textebrut(filtrer_entites($nom)));
			}
			$data['lesauteurs'] = join(', ', array_unique($auteurs));
		}
		else
			$data['lesauteurs'] = $les_auteurs_du_site;

		// Description
		if (preg_match(',<((description|summary)([:[:space:]][^>]*)?)'
		.'>(.*)</\2[:>[:space:]],Uims',$item,$match)) {
			$data['descriptif'] = $match[4];
		}
		if (preg_match(',<((content)([:[:space:]][^>]*)?)'
		.'>(.*)</\2[:>[:space:]],Uims',$item,$match)) {
			$data['content'] = $match[4];
		}

		// lang
		if (preg_match(',<([^>]*xml:)?lang(uage)?'.'>([^<>]+)<,i',
			$item, $match))
			$data['lang'] = trim($match[3]);
		else
			$data['lang'] = trim($langue_du_site);

		// source et url_source  (pas trouve d'exemple en ligne !!)
		# <source url="http://www.truc.net/music/uatsap.mp3" length="19917" />
		# <source url="http://www.truc.net/rss">Site source</source>
		if (preg_match(',(<source[^>]*>)(([^<>]+)</source>)?,i',
		$item, $match)) {
			$data['source'] = trim($match[3]);
			$data['url_source'] = str_replace('&amp;', '&',
				trim(extraire_attribut($match[1], 'url')));
		}

		// tags
		# a partir de "<dc:subject>", (del.icio.us)
		# ou <media:category> (flickr)
		# ou <itunes:category> (apple)
		# on cree nos tags microformat <a rel="directory" href="url">titre</a>
		# http://microformats.org/wiki/rel-directory
		$tags = array();
		if (preg_match_all(
		',<(([a-z]+:)?(subject|category|directory|keywords?|tags?|type))[^>]*>'
		.'(.*?)</\1>,ims',
		$item, $matches, PREG_SET_ORDER))
			$tags = ajouter_tags($matches, $item); # array()
		// Pieces jointes : s'il n'y a pas de microformat relEnclosure,
		// chercher <enclosure> au format RSS et les passer en microformat
		if (!afficher_enclosures(join(', ', $tags)))
			if (preg_match_all(',<enclosure[[:space:]][^<>]+>,i',
			$item, $matches, PREG_PATTERN_ORDER))
				$data['enclosures'] = join(', ',
					array_map('enclosure2microformat', $matches[0]));
		$data['item'] = $item;

		// Nettoyer les donnees et remettre les CDATA en place
		cdata_echappe_retour($data, $echappe_cdata);
		cdata_echappe_retour($tags, $echappe_cdata);

		// Trouver les microformats (ecrase les <category> et <dc:subject>)
		if (preg_match_all(
		',<a[[:space:]]([^>]+[[:space:]])?rel=[^>]+>.*</a>,Uims',
		$data['item'], $regs, PREG_PATTERN_ORDER)) {
			$tags = $regs[0];
		}
		// Cas particulier : tags Connotea sous la forme <a class="postedtag">
		if (preg_match_all(
		',<a[[:space:]][^>]+ class="postedtag"[^>]*>.*</a>,Uims',
		$data['item'], $regs, PREG_PATTERN_ORDER))
			$tags = preg_replace(', class="postedtag",i',
			' rel="tag"', $regs[0]);

		$data['tags'] = $tags;

		$articles[] = $data;
	}

	return $articles;
}

//
// Insere un article syndique (renvoie true si l'article est nouveau)
//
function inserer_article_syndique ($data, $now_id_syndic, $statut, $url_site, $url_syndic, $resume, $documents) {

	// Creer le lien s'il est nouveau - cle=(id_syndic,url)
	$le_lien = substr($data['url'], 0,255);
	if (spip_num_rows(spip_query(
		"SELECT * FROM spip_syndic_articles
		WHERE url='".addslashes($le_lien)."'
		AND id_syndic=$now_id_syndic"
	)) == 0 and !spip_sql_error()) {
		spip_query("INSERT INTO spip_syndic_articles
		(id_syndic, url, date, statut) VALUES
		('$now_id_syndic', '".addslashes($le_lien)."',
		FROM_UNIXTIME(".$data['date']."), '$statut')");
		$ajout = true;
	}

	// Descriptif, en mode resume ou mode 'full text'
	// on prend en priorite data['descriptif'] si on est en mode resume,
	// et data['content'] si on est en mode "full syndication"
	if ($resume != 'non') {
		// mode "resume"
		$desc = strlen($data['descriptif']) ?
			$data['descriptif'] : $data['content'];
		$desc = couper(trim(textebrut($desc)), 300);
	} else {
		// mode "full syndication"
		// choisir le contenu pertinent
		// & refaire les liens relatifs
		$desc = strlen($data['content']) ?
			$data['content'] : $data['descriptif'];
		$desc = liens_absolus($desc, $url_syndic);
	}

	// Mettre a jour la date si lastbuilddate
	$update_date = $data['lastbuilddate'] ?
		"date = FROM_UNIXTIME(".$data['lastbuilddate'].")," : '';

	// tags & enclosures (preparer spip_syndic_articles.tags)
	$tags = $data['enclosures'];
	# eviter les doublons (cle = url+titre) et passer d'un tableau a une chaine
	if ($data['tags']) {
		$vus = array();
		foreach ($data['tags'] as $tag) {
			$cle = supprimer_tags($tag).extraire_attribut($tag,'href');
			$vus[$cle] = $tag;
		}
		$tags .= ($tags ? ', ' : '') . join(', ', $vus);
	}

	// Mise a jour du contenu (titre,auteurs,description,date?,source...)
	spip_query ("UPDATE spip_syndic_articles SET
	titre='".addslashes($data['titre'])."',
	".$update_date."
	lesauteurs='".addslashes($data['lesauteurs'])."',
	descriptif='".addslashes($desc)."',
	lang='".addslashes(substr($data['lang'],0,10))."',
	source='".addslashes(substr($data['source'],0,255))."',
	url_source='".addslashes(substr($data['url_source'],0,255))."',
	tags='".addslashes($tags)."'
	WHERE id_syndic='$now_id_syndic' AND url='".addslashes($le_lien)."'");

	// Point d'entree post_syndication
	pipeline('post_syndication',
		array(
			$le_lien,
			$now_id_syndic,
			$data
		)
	);

	return $ajout;
}

//
// Mettre a jour le site
//
function syndic_a_jour($now_id_syndic, $statut = 'off') {
	include_spip('inc/texte');

	$query = "SELECT * FROM spip_syndic WHERE id_syndic='$now_id_syndic'";
	$result = spip_query($query);
	if (!$row = spip_fetch_array($result))
		return;

	$url_syndic = $row['url_syndic'];
	$url_site = $row['url_site'];

	if ($row['moderation'] == 'oui')
		$moderation = 'dispo';	// a valider
	else
		$moderation = 'publie';	// en ligne sans validation

	// Section critique : n'autoriser qu'une seule syndication
	// simultanee pour un site donne
	if (!spip_get_lock("syndication $url_syndic"))
		return;

	spip_query("UPDATE spip_syndic SET syndication='$statut',
		date_syndic=NOW() WHERE id_syndic='$now_id_syndic'");

	// Aller chercher les donnees du RSS et les analyser
	include_spip('inc/distant');
	$rss = recuperer_page($url_syndic, true);
	if (!$rss)
		$articles = _T('avis_echec_syndication_02');
	else
		$articles = analyser_backend($rss, $url_syndic);

	// Les enregistrer dans la base
	if (is_array($articles)) {
		$urls = array();
		foreach ($articles as $data) {
			inserer_article_syndique ($data, $now_id_syndic, $moderation, $url_site, $url_syndic, $row['resume'], $row['documents']);
			$urls[] = $data['url'];
		}

		// moderation automatique des liens qui sont sortis du feed
		if (count($urls) > 0
		AND $row['miroir'] == 'oui') {
			spip_query("UPDATE spip_syndic_articles
				SET statut='off', maj=maj
				WHERE id_syndic=$now_id_syndic
				AND NOT (url IN ('"
				. join("','", array_map('addslashes',$urls))
				. "'))");
		}

		// suppression apres 2 mois des liens qui sont sortis du feed
		if (count($urls) > 0
		AND $row['oubli'] == 'oui') {
			$time = date('U') - 61*24*3600; # deux mois
			spip_query("DELETE FROM spip_syndic_articles
				WHERE id_syndic=$now_id_syndic
				AND UNIX_TIMESTAMP(maj) < $time
				AND UNIX_TIMESTAMP(date) < $time
				AND NOT (url IN ('"
				. join("','", array_map('addslashes',$urls))
				. "'))");
		}


		// Noter que la syndication est OK
		spip_query("UPDATE spip_syndic SET syndication='oui'
		WHERE id_syndic='$now_id_syndic'");
	}

	// Ne pas oublier de liberer le verrou
	spip_release_lock($url_syndic);

	if ($liens_ajoutes) {
		spip_log("Syndication: $liens_ajoutes nouveau(x) lien(s)");
		include_spip('inc/rubriques');
		calculer_rubriques();
	}

	// Renvoyer l'erreur le cas echeant
	if (!is_array($articles))
		return $articles;
	else
		return false; # c'est bon
}


// helas strtotime ne reconnait pas le format W3C
// http://www.w3.org/TR/NOTE-datetime
function my_strtotime($la_date) {

	// format complet
	if (preg_match(
	',^([0-9]+-[0-9]+-[0-9]+[T ][0-9]+:[0-9]+(:[0-9]+)?)(\.[0-9]+)?'
	.'(Z|([-+][0-9][0-9]):[0-9]+)?$,',
	$la_date, $match)) {
		$la_date = str_replace("T", " ", $match[1])." GMT";
		return strtotime($la_date) - intval($match[5]) * 3600;
	}

	// YYYY
	if (preg_match(',^([0-9][0-9][0-9][0-9])$,', $la_date, $match))
		return strtotime($match[1]."-01-01");

	// YYYY-MM
	if (preg_match(',^([0-9][0-9][0-9][0-9]-[0-9][0-9])$,', $la_date, $match))
		return strtotime($match[1]."-01");

	// utiliser strtotime en dernier ressort
	$s = strtotime($la_date);
	if ($s > 0)
		return $s;

	// erreur
	spip_log("Impossible de lire le format de date '$la_date'");
	return false;
}


function cron_syndic($t) {
	$r = executer_une_syndication();
	if (($GLOBALS['meta']['activer_moteur'] == 'oui') &&
	    ($GLOBALS['meta']["visiter_sites"] == 'oui')) {
		include_spip("inc/indexation");
		$r2 = executer_une_indexation_syndic();
		$r = $r && $r2;
	}
	return $r;
}

?>
