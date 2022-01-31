<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function trier_rss($texte) {
	if (preg_match_all(',<item.*</item>\s*?,Uims', $texte, $matches, PREG_SET_ORDER)) {
		$placeholder = '<!--REINSERT-->';
		$items = [];
		foreach ($matches as $match) {
			if (preg_match(',<dc:date>(.*)</dc:date>,Uims', $match[0], $r)) {
				$items[strtotime($r[1])] = trim($match[0]);
				$texte = str_replace($match[0], unique($placeholder), $texte);
			}
		}
		krsort($items);
		$texte = str_replace($placeholder, implode("\n\t", $items) . "\n", $texte);
	}

	return $texte;
}
