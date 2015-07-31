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

include_spip('inc/texte'); // utile pour l'espace public, deja fait sinon

function xml_ical_dist($rss, $intro = '') {

	// entetes
	$u =
'BEGIN:VCALENDAR
CALSCALE:GREGORIAN
X-WR-CALNAME;VALUE=TEXT:'.filtrer_ical($intro['title']).'
X-WR-RELCALID:'.filtrer_ical(url_absolue($intro['url'])).'
';

	// elements
	if (is_array($rss)) {
		usort($rss, 'trier_par_date');
		foreach ($rss as $article) {

			// Regler la date de fin a h+60min
			if (!$article['enddate'])
				$article['enddate'] = date_ical($article['date'],60);
			else
				$article['enddate'] = date_ical($article['enddate']);

			// Type d'evenement
			if ($article['type'] == 'todo')
				$type = 'VTODO';
			else
				$type = 'VEVENT';

			$u .=
'BEGIN:'.$type.'
SUMMARY:'.filtrer_ical($article['title']).'
URL:'.filtrer_ical(url_absolue($article['url'])).'
DTSTAMP:'. date_ical($article['date']).'
DTSTART:'. date_ical($article['date']).'
DTEND:'. $article['enddate'].'
DESCRIPTION:'.filtrer_ical(liens_absolus($article['description'])).'
ORGANIZER:'.filtrer_ical($article['author']).'
CATEGORIES:--
END:'.$type.'
';
		}
	}

	// pied
	$u .= 'END:VCALENDAR';

	header('Content-Type: text/calendar; charset=utf-8');
	echo $u;
}
?>
