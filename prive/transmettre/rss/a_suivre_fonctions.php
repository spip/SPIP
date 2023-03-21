<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function trier_rss($texte) {
	if (preg_match_all(',<item.*</item>\s*?,Uims', (string) $texte, $matches, PREG_SET_ORDER)) {
		$placeholder = '<!--REINSERT-->';
		$items = [];
		foreach ($matches as $match) {
			if (preg_match(',<dc:date>(.*)</dc:date>,Uims', $match[0], $r)) {
				$items[strtotime($r[1])] = trim($match[0]);
				$texte = str_replace($match[0], unique($placeholder), (string) $texte);
			}
		}
		krsort($items);
		$texte = str_replace($placeholder, implode("\n\t", $items) . "\n", (string) $texte);
	}

	return $texte;
}
