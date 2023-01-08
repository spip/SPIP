<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// Correction typographique francaise

function typographie_fr_dist($letexte) {

	static $trans;

	// Nettoyer 160 = nbsp ; 187 = raquo ; 171 = laquo ; 176 = deg ;
	// 147 = ldquo; 148 = rdquo; ' = zouli apostrophe
	if (!$trans) {
		$trans = [
			"'" => '&#8217;',
			'&nbsp;' => '~',
			'&raquo;' => '&#187;',
			'&laquo;' => '&#171;',
			'&rdquo;' => '&#8221;',
			'&ldquo;' => '&#8220;',
			'&deg;' => '&#176;'
		];
		$chars = [160 => '~', 187 => '&#187;', 171 => '&#171;', 148 => '&#8221;', 147 => '&#8220;', 176 => '&#176;'];
		$chars_trans = array_keys($chars);
		$chars = array_values($chars);
		$chars_trans = implode(' ', array_map('chr', $chars_trans));
		$chars_trans = unicode2charset(charset2unicode($chars_trans, 'iso-8859-1', 'forcer'));
		$chars_trans = explode(' ', $chars_trans);
		foreach ($chars as $k => $r) {
			$trans[$chars_trans[$k]] = $r;
		}
	}

	$letexte = strtr($letexte, $trans);

	$cherche1 = [
		/* 1 */
		'/((?:^|[^\#0-9a-zA-Z\&])[\#0-9a-zA-Z]*)\;/S',
		/* 2 */
		'/&#187;| --?,|(?::(?!:)| %)(?:\W|$)/S',
		/* 3 */
		'/([^[<(!?.])([!?][!?\.]*)/iS',
		/* 4 */
		'/&#171;|(?:M(?:M?\.|mes?|r\.?)|[MnN]&#176;) /S'
	];
	$remplace1 = [
		/* 1 */
		'\1~;',
		/* 2 */
		'~\0',
		/* 3 */
		'\1~\2',
		/* 4 */
		'\0~'
	];
	$letexte = preg_replace($cherche1, $remplace1, $letexte);
	$letexte = preg_replace('/ *~+ */S', '~', $letexte);

	$cherche2 = [
		'/([^-\n]|^)--([^-]|$)/S',
		',(' . _PROTOCOLES_STD . ')~((://[^"\'\s\[\]\}\)<>]+)~([?]))?,S',
		'/~/'
	];
	$remplace2 = [
		'\1&mdash;\2',
		'\1\3\4',
		'&nbsp;'
	];
	$letexte = preg_replace($cherche2, $remplace2, $letexte);

	return $letexte;
}
