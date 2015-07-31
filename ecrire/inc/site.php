<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;


// http://doc.spip.org/@analyser_site
function analyser_site($url) {
	include_spip('inc/filtres');
	include_spip('inc/distant');

	// Accepter les URLs au format feed:// ou qui ont oublie le http://
	$url = preg_replace(',^feed://,i', 'http://', $url);
	if (!preg_match(',^[a-z]+://,i', $url)) $url = 'http://'.$url;

	$texte = recuperer_page($url, true);
	if (!$texte) return false;

	if (preg_match(',<(channel|feed)([\:[:space:]][^>]*)?'
	.'>(.*)</\1>,ims', $texte, $regs)) {
		$result['syndication'] = 'oui';
		$result['url_syndic'] = $url;
		$channel = $regs[3];

		// Pour recuperer l'entete, on supprime tous les items
		$b = array_merge(
			extraire_balises($channel, 'item'),
			extraire_balises($channel, 'entry')
		);
		$header = str_replace($b,array(),$channel);

		if ($t = extraire_balise($header, 'title'))
			$result['nom_site'] = supprimer_tags($t);
		if ($t = extraire_balises($header, 'link')) {
			foreach ($t as $link) {
				$u = supprimer_tags(filtrer_entites($link));
				if (!strlen($u))
					$u = extraire_attribut($link, 'href');
				if (strlen($u)) {
					// on installe l'url comme url du site
					// si c'est non vide, en donnant la priorite a rel=alternate
					if (preg_match(',\balternate\b,', extraire_attribut($link, 'rel'))
					OR !isset($result['url_site']))
						$result['url_site'] = filtrer_entites($u);
				}
			}
		}
		$result['url_site'] = url_absolue($result['url_site'], $url);

		if ($a = extraire_balise($header, 'description')
		OR $a = extraire_balise($header, 'tagline')) {
			$result['descriptif'] = supprimer_tags($a);
		}

		if (preg_match(',<image.*<url.*>(.*)</url>.*</image>,Uims',
		$header, $r)
		AND preg_match(',(https?://.*/.*(gif|png|jpg)),ims', $r[1], $r)
		AND $image = recuperer_infos_distantes($r[1])) {
			if (in_array($image['extension'], array('gif', 'jpg', 'png'))) {
				$result['format_logo'] = $image['extension'];
				$result['logo'] = $r[1];
			}
			else if ($image['fichier']) {
				spip_unlink($image['fichier']);
			}
		}
	}
	else {
		$result['syndication'] = 'non';
		$result['url_site'] = $url;
		if (preg_match(',<head>(.*(description|title).*)</head>,Uims', $texte, $regs)) {
			$head = filtrer_entites($regs[1]);
		} else
			$head = $texte;
		if (preg_match(',<title[^>]*>(.*),i', $head, $regs))
			$result['nom_site'] = filtrer_entites(supprimer_tags(preg_replace(',</title>.*,i', '', $regs[1])));
		if ($a = array_merge(
			extraire_balises($head, 'meta'),
			extraire_balises($head, 'http-equiv')
		)) {
			foreach($a as $meta) {
				if (extraire_attribut($meta, 'name') == 'description') {
					$desc = trim(extraire_attribut($meta, 'content'));
					if (!strlen($desc))
						$desc = trim(extraire_attribut($meta, 'value'));
					$result['descriptif'] = $desc;
				}
			}
		}

		// Cherchons quand meme un backend
		include_spip('inc/distant');
		include_spip('inc/feedfinder');
		$feeds = get_feed_from_url($url, $texte);
		// si on a a trouve un (ou plusieurs) on le note avec select:
		// ce qui constitue un signal pour exec=sites qui proposera de choisir
		// si on syndique, et quelle url.
		if (count($feeds)>=1) {
			spip_log("feedfinder.php :\n".join("\n", $feeds));
			$result['url_syndic'] = "select: ".join(' ',$feeds);
		}
	}

	return $result;
}

?>
