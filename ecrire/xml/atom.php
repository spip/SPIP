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

function xml_atom_dist($rss, $intro = '') {
	// entetes
	$u = '<'.'?xml version="1.0" encoding="'.$GLOBALS['meta']['charset']
	.'"?'.">\n";
	$u .= '<feed xmlns="http://www.w3.org/2005/Atom"';
	if ($intro['language'])
		$u .= ' xml:lang="'.$intro['language'].'"';
	$u .= '>
	<title>'.texte_backend($intro['title']).'</title>
	<id>'.texte_backend(url_absolue($intro['url'])).'</id>
	<link href="'.texte_backend(url_absolue($intro['url'])).'"/>';
	if ($intro['description']) $u .= '<subtitle>'.texte_backend($intro['description']).'</subtitle>';
	$u .= '<link rel="self" type="application/atom+xml" href="'.texte_backend(url_absolue($_SERVER['REQUEST_URI'])).'"/>
	<updated>'.gmdate("Y-m-d\TH:i:s\Z").'</updated>'; // probleme, <updated> pourrait etre plus precis

	// elements
	if (is_array($rss)) {
		usort($rss, 'trier_par_date');
		foreach ($rss as $article) {
			$u .= "\n\t<entry";
			if ($article['lang'])
				$u .= ' xml:lang="'.texte_backend($article['lang']).'"';
			$u .= '>
		<title>'.texte_backend($article['title']).'</title>
		<id>'.texte_backend(url_absolue($article['url'])).'</id>
		<link rel="alternate" type="text/html" href="'.texte_backend(url_absolue($article['url'])).'"/>
		<published>'.date_iso($article['date']).'</published>
		<updated>'.date_iso($article['date']).'</updated>';
			if ($article['author']) {
				$u .= '
		<author><name>'.texte_backend($article['author']).'</name>';
				if ($article['email'])
					$u .= '<email>'.texte_backend($article['email']).'</email>';
				$u .= '</author>';
			}
			$u .='
		<summary type="html">'.texte_backend(liens_absolus($article['description'])).'</summary>
	</entry>
';
		}
	}

	// pied
	$u .= '
</feed>
 ';

	header('Content-Type: text/xml; charset='.$GLOBALS['meta']['charset']);
	echo $u;
}
?>
