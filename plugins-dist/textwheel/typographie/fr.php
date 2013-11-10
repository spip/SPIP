<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// Correction typographique francaise

function typographie_fr($t) {

	static $trans;

	if (!isset($trans)) {
		$trans = array(
		"&nbsp;" => '~',
		"&raquo;" => '&#187;',
		"&laquo;" => '&#171;',
		"&rdquo;" => '&#8221;',
		"&ldquo;" => '&#8220;',
		"&deg;" => '&#176;',
		"'" => '&#8217;'
		);
		switch ($GLOBALS['meta']['charset']) {
			case 'utf-8':
				$trans["\xc2\xa0"] = '~';
				$trans["\xc2\xbb"] = '&#187;';
				$trans["\xc2\xab"] = '&#171;';
				$trans["\xe2\x80\x94"] = '--';
				$trans["\xe2\x80\x9d"] = '&#8221;';
				$trans["\xe2\x80\x9c"] = '&#8220;';
				$trans["\xc2\xb0"] = '&#176;';
				$trans["\xe2\x80\x89"] = '~'; # &finesp;
				break;
			default:
				$trans["\xa0"] = '~';
				$trans["\xab"] = '&#171;';
				$trans["\xbb"] = '&#187;';
				$trans["\xb0"] = '&#176;';
				break;
		}
	}

	# cette chaine ne peut pas exister,
	# cf. TYPO_PROTECTEUR dans inc/texte
	$pro = "-\x2-";

	$t = str_replace(array_keys($trans), array_values($trans), $t);

	# la typo du ; risque de clasher avec les entites &xxx;
	if (strpos($t, ';') !== false) {
		$t = str_replace(';', '~;', $t);
		$t = preg_replace(',(&#?[0-9a-z]+)~;,iS', '$1;', $t);
	}

	/* 2 ; ajout d'insecable */
	$t = preg_replace('/&#187;| --?,|(?::| %)(?:\W|$)/S', '~$0', $t);

	// {È} guillemet en italiques : ne pas doubler l'insecable 
	$t = str_replace('~{~', '~{', $t);
	$t = str_replace('~}~', '}~', $t);


	/* 3 */
	$t = preg_replace('/[!?][!?\.]*/S', "$pro~$0", $t, -1, $c);
	if ($c) {
		$t = preg_replace("/([\[<\(!\?\.])$pro~/S", '$1', $t);
		$t = str_replace("$pro", '', $t);
	}

	/* 4 */
	$t = preg_replace('/&#171;|M(?:M?\.|mes?|r\.?|&#176;) |[nN]&#176; /S', '$0~', $t);

	if (strpos($t, '~') !== false)
		$t = preg_replace("/ *~+ */S", "~", $t);

	$t = preg_replace("/--([^-]|$)/S", "$pro&mdash;$1", $t, -1, $c);
	if ($c) {
		$t = preg_replace("/([-\n])$pro&mdash;/S", "$1--", $t);
		$t = str_replace($pro, '', $t);
	}

	$t = preg_replace(',(https?|ftp|mailto)~((://[^"\'\s\[\]\}\)<>]+)~([?]))?,S', '$1$3$4', $t);
	$t = str_replace('~', '&nbsp;', $t);

	return $t;
}
