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

// ATTENTION
// Cette inclusion charge executer_une_syndication pour compatibilite,
// mais cette fonction ne doit plus etre invoquee directement:
// il faut passer par cron() pour avoir un verrou portable
// Voir un exemple dans action/editer/site
include_spip('genie/syndic');

// prend un fichier backend et retourne un tableau des items lus,
// et une chaine en cas d'erreur
// http://doc.spip.org/@analyser_backend
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

		// guid n'est un URL que si marque de <guid ispermalink="true"> ;
		// attention la valeur par defaut est 'true' ce qui oblige a quelque
		// gymnastique
		if (preg_match(',<guid.*>[[:space:]]*(https?:[^<]*)</guid>,Uims',
		$item, $regs) AND preg_match(',^(true|1)?$,i',
		extraire_attribut($regs[0], 'ispermalink')))
			$data['url'] = $regs[1];

		// <link>, plus classique
		else if (preg_match(
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

		// Aucun link ni guid, mais une enclosure
		else if (preg_match(',<enclosure[^>]*>,ims', $item, $regs)
		AND $url = extraire_attribut($regs[0], 'url'))
			$data['url'] = $url;

		// pas d'url, c'est genre un compteur...
		else
			$data['url'] = '';

		// Titre (semi-obligatoire)
		if (preg_match(",<title[^>]*>(.*?)</title>,ims",$item,$match))
			$data['titre'] = $match[1];
		else if (preg_match(',<link[[:space:]][^>]*>,Uims',$item,$mat)
		AND $title = extraire_attribut($mat[0], 'title'))
			$data['titre'] = $title; 
		if (!strlen($data['titre'] = trim($data['titre'])))
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
			$data['descriptif'] = trim($match[4]);
		}
		if (preg_match(',<((content)([:[:space:]][^>]*)?)'
		.'>(.*)</\2[:>[:space:]],Uims',$item,$match)) {
			$data['content'] = trim($match[4]);
		}

		// lang
		if (preg_match(',<([^>]*xml:)?lang(uage)?'.'>([^<>]+)<,i',
			$item, $match))
			$data['lang'] = trim($match[3]);
		else if ($lang = trim(extraire_attribut($item, 'xml:lang')))
			$data['lang'] = $lang;
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
		elseif (preg_match_all(
		',<(([a-z]+:)?(subject|category|directory|keywords?|tags?|type))[^>]*/>'
		.',ims',
		$item, $matches, PREG_SET_ORDER))
			$tags = ajouter_tags($matches, $item); # array()
		// Pieces jointes :
		// chercher <enclosure> au format RSS et les passer en microformat
		// ou des microformats relEnclosure,
		// ou encore les media:content
		if (!afficher_enclosures(join(', ', $tags))) {
			if (preg_match_all(',<enclosure[[:space:]][^<>]+>,i',
			$item, $matches, PREG_PATTERN_ORDER))
				$data['enclosures'] = join(', ',
					array_map('enclosure2microformat', $matches[0]));
			else if (
			preg_match_all(',<link\b[^<>]+rel=["\']?enclosure["\']?[^<>]+>,i',
			$item, $matches, PREG_PATTERN_ORDER))
				$data['enclosures'] = join(', ',
					array_map('enclosure2microformat', $matches[0]));
			else if (
			preg_match_all(',<media:content\b[^<>]+>,i',
			$item, $matches, PREG_PATTERN_ORDER))
				$data['enclosures'] = join(', ',
					array_map('enclosure2microformat', $matches[0]));
		}
		$data['item'] = $item;

		// Nettoyer les donnees et remettre les CDATA en place
		cdata_echappe_retour($data, $echappe_cdata);
		cdata_echappe_retour($tags, $echappe_cdata);

		// passer l'url en absolue
		$data['url'] = url_absolue(filtrer_entites($data['url']), $url_syndic);

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
		// enlever le html des titre pour etre homogene avec les autres objets spip
		$data['titre'] = textebrut($data['titre']);

		$articles[] = $data;
	}

	return $articles;
}


// helas strtotime ne reconnait pas le format W3C
// http://www.w3.org/TR/NOTE-datetime
// http://doc.spip.org/@my_strtotime
function my_strtotime($la_date) {

	// format complet
	if (preg_match(
	',^(\d+-\d+-\d+[T ]\d+:\d+(:\d+)?)(\.\d+)?'
	.'(Z|([-+]\d{2}):\d+)?$,',
	$la_date, $match)) {
		$la_date = str_replace("T", " ", $match[1])." GMT";
		return strtotime($la_date) - intval($match[5]) * 3600;
	}

	// YYYY
	if (preg_match(',^\d{4}$,', $la_date, $match))
		return strtotime($match[0]."-01-01");

	// YYYY-MM
	if (preg_match(',^\d{4}-\d{2}$,', $la_date, $match))
		return strtotime($match[0]."-01");

	// utiliser strtotime en dernier ressort
	$s = strtotime($la_date);
	if ($s > 0)
		return $s;

	// YYYY-MM-DD hh:mm:ss
	if (preg_match(',^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}\b,', $la_date, $match))
		return strtotime($match[0]);


	// erreur
	spip_log("Impossible de lire le format de date '$la_date'");
	return false;
}
// A partir d'un <dc:subject> ou autre essayer de recuperer
// le mot et son url ; on cree <a href="url" rel="tag">mot</a>
// http://doc.spip.org/@creer_tag
function creer_tag($mot,$type,$url) {
	if (!strlen($mot = trim($mot))) return '';
	$mot = "<a rel=\"tag\">$mot</a>";
	if ($url)
		$mot = inserer_attribut($mot, 'href', $url);
	if ($type)
		$mot = inserer_attribut($mot, 'rel', $type);
	return $mot;
}


// http://doc.spip.org/@ajouter_tags
function ajouter_tags($matches, $item) {
	include_spip('inc/filtres');
	$tags = array();
	foreach ($matches as $match) {
		$type = ($match[3] == 'category' OR $match[3] == 'directory')
			? 'directory':'tag';
		$mot = supprimer_tags($match[0]);
		if (!strlen($mot)
		AND !strlen($mot = extraire_attribut($match[0], 'label')))
			break;
		// rechercher un url
		if ($url = extraire_attribut($match[0], 'domain')
		OR $url = extraire_attribut($match[0], 'resource')
		OR $url = extraire_attribut($match[0], 'url')
		)
			{}

		## cas particuliers
		else if (extraire_attribut($match[0], 'scheme') == 'urn:flickr:tags') {
			foreach(explode(' ', $mot) as $petit)
				if ($t = creer_tag($petit, $type,
				'http://www.flickr.com/photos/tags/'.rawurlencode($petit).'/'))
					$tags[] = $t;
			$mot = '';
		}
		else if (
			// cas atom1, a faire apres flickr
			$url = suivre_lien(extraire_attribut($match[0], 'scheme'),
				extraire_attribut($match[0], 'term'))) {
		}
		else {
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
// http://doc.spip.org/@cdata_echappe_retour
function cdata_echappe_retour(&$table, &$echappe_cdata) {
	foreach ($table as $var => $val) {
		$table[$var] = filtrer_entites($table[$var]);
		foreach ($echappe_cdata as $n => $e)
			$table[$var] = str_replace("@@@SPIP_CDATA$n@@@",
				$e, $table[$var]);
	}
}
?>
