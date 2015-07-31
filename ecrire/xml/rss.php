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

function xml_rss_dist($rss, $intro = '') {
	// entetes
	$u = '<'.'?xml version="1.0" encoding="'.$GLOBALS['meta']['charset'].'"?'.">\n";

	$u .= '
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:thr="http://purl.org/syndication/thread/1.0">
<channel>
	<title>'.texte_backend($intro['title']).'</title>
	<link>'.texte_backend(url_absolue($intro['url'])).'</link>
	<description>'.texte_backend($intro['description']).'</description>
	<language>'.texte_backend($intro['language']).'</language>
	';

	// elements
	if (is_array($rss)) {
		usort($rss, 'trier_par_date');
		foreach ($rss as $article) {
			$u .= '
	<item>
		<title>'.texte_backend($article['title']).'</title>
		<link>'.texte_backend(url_absolue($article['url'])).'</link>
		<guid isPermaLink="true">'.texte_backend(url_absolue($article['url'])).'</guid>
		<dc:date>'.date_iso($article['date']).'</dc:date>
		<dc:format>text/html</dc:format>';
			if ($article['lang']) $u .= '
		<dc:language>'.texte_backend($article['lang']).'</dc:language>';
			if ($article['in_reply_to_url']) $u .= ' 
		<thr:in-reply-to ref="'.texte_backend(url_absolue($article['in_reply_to_url'])).
				'" href="'.texte_backend(url_absolue($article['in_reply_to_url'])).'" type="text/html" />';
			if ($article['author']) {
				if ($article['email'])
					$article['author'].=' <'.$article['email'].'>';

				$u .= '
		<dc:creator>'.texte_backend($article['author']).'</dc:creator>';
			}
			$u .= '
		<description>'.texte_backend(liens_absolus($article['description'])).'</description>
	</item>
';
		}
	}

	// pied
	$u .= '
	</channel>
</rss>
';
	header('Content-Type: text/xml; charset='.$GLOBALS['meta']['charset']);
	echo $u;
}
?>
