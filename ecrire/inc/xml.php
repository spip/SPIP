<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Outils pour lecture de XML
 *
 * @package SPIP\Core\XML
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Lit un fichier xml donné et renvoie son arbre.
 *
 * @example
 *     ```
 *     include_spip('inc/xml');
 *     $arbre = spip_xml_load("example.xml");
 *     ```
 *
 * @uses spip_xml_parse()
 *
 * @param string $fichier
 *     Chemin local ou URL distante du fichier XML
 * @param bool $strict
 *     true pour râler si une balise n'est pas correctement fermée, false sinon.
 * @param bool $clean ?
 * @param int $taille_max
 *     Taille maximale si fichier distant
 * @param string|array $datas
 *     Données à envoyer pour récupérer le fichier distant
 * @param int $profondeur ?
 * @return array|bool
 *     - array : l'arbre XML,
 *     - false si l'arbre xml ne peut être créé ou est vide
 **/
function spip_xml_load($fichier, $strict = true, $clean = true, $taille_max = 1048576, $datas = '', $profondeur = -1) {
	$contenu = '';
	if (tester_url_absolue($fichier)) {
		include_spip('inc/distant');
		$contenu = recuperer_url($fichier, ['taille_max' => $taille_max, 'datas' => $datas]);
		$contenu = $contenu['page'] ?? '';
	} else {
		lire_fichier($fichier, $contenu);
	}
	$arbre = [];
	if ($contenu) {
		$arbre = spip_xml_parse($contenu, $strict, $clean, $profondeur);
	}

	return count($arbre) ? $arbre : false;
}

if (!defined('_SPIP_XML_TAG_SPLIT')) {
	define('_SPIP_XML_TAG_SPLIT', '{<([^:>][^>]*?)>}sS');
}

/**
 * Parse une chaine XML donnée et retourne un tableau.
 *
 * @see spip_xml_aplatit() pour l'inverse
 *
 * @param string $texte
 *     Texte XML
 * @param bool $strict
 *     true pour râler si une balise n'est pas correctement fermée, false sinon.
 * @param bool $clean ?
 * @param int $profondeur ?
 * @return array|bool
 *     - array : l'arbre XML,
 *     - false si l'arbre xml ne peut être créé ou est vide
 **/
function spip_xml_parse(&$texte, $strict = true, $clean = true, $profondeur = -1) {
	$out = [];
	// enlever les commentaires
	$charset = 'AUTO';
	if ($clean === true) {
		if (preg_match(",<\?xml\s(.*?)encoding=['\"]?(.*?)['\"]?(\s(.*))?\?>,im", $texte, $regs)) {
			$charset = $regs[2];
		}
		$texte = preg_replace(',<!--(.*?)-->,is', '', $texte);
		$texte = preg_replace(',<\?(.*?)\?>,is', '', $texte);
		include_spip('inc/charsets');
		$clean = $charset;
		//$texte = importer_charset($texte,$charset);
	}
	if (is_string($clean)) {
		$charset = $clean;
	}
	$txt = $texte;

	// tant qu'il y a des tags
	$chars = preg_split(_SPIP_XML_TAG_SPLIT, $txt, 2, PREG_SPLIT_DELIM_CAPTURE);
	while (count($chars) >= 2) {
		// tag ouvrant
		//$chars = preg_split("{<([^>]*?)>}s",$txt,2,PREG_SPLIT_DELIM_CAPTURE);

		// $before doit etre vide ou des espaces uniquements!
		$before = trim($chars[0]);

		if (strlen($before) > 0) {
			return importer_charset($texte, $charset);
		}//$texte; // before non vide, donc on est dans du texte

		$tag = rtrim($chars[1]);
		$txt = $chars[2];

		if (strncmp($tag, '![CDATA[', 8) == 0) {
			return importer_charset($texte, $charset);
		}//$texte;
		if (substr($tag, -1) == '/') { // self closing tag
			$tag = rtrim(substr($tag, 0, strlen($tag) - 1));
			$out[$tag][] = '';
		} else {
			$closing_tag = preg_split(",\s|\t|\n|\r,", trim($tag));
			$closing_tag = reset($closing_tag);
			// tag fermant
			$ncclos = strlen("</$closing_tag>");
			$p = strpos($txt, "</$closing_tag>");
			if ($p !== false and (strpos($txt, '<') < $p)) {
				$nclose = 0;
				$nopen = 0;
				$d = 0;
				while (
					$p !== false
					and ($morceau = substr($txt, $d, $p - $d))
					and (($nopen += preg_match_all(
						'{<' . preg_quote($closing_tag) . '(\s*>|\s[^>]*[^/>]>)}is',
						$morceau,
						$matches,
						PREG_SET_ORDER
					)) > $nclose)
				) {
					$nclose++;
					$d = $p + $ncclos;
					$p = strpos($txt, "</$closing_tag>", $d);
				}
			}
			if ($p === false) {
				if ($strict) {
					$out[$tag][] = "erreur : tag fermant $tag manquant::$txt";

					return $out;
				} else {
					return importer_charset($texte, $charset);
				}//$texte // un tag qui constitue du texte a reporter dans $before
			}
			$content = substr($txt, 0, $p);
			$txt = substr($txt, $p + $ncclos);
			if ($profondeur == 0 or strpos($content, '<') === false) { // eviter une recursion si pas utile
			$out[$tag][] = importer_charset($content, $charset);
			}//$content;
			else {
				$out[$tag][] = spip_xml_parse($content, $strict, $clean, $profondeur - 1);
			}
		}
		$chars = preg_split(_SPIP_XML_TAG_SPLIT, $txt, 2, PREG_SPLIT_DELIM_CAPTURE);
	}
	if (count($out) && (strlen(trim($txt)) == 0)) {
		return $out;
	} else {
		return importer_charset($texte, $charset);
	}//$texte;
}

// https://code.spip.net/@spip_xml_aplatit
function spip_xml_aplatit($arbre, $separateur = ' ') {
	$s = '';
	if (is_array($arbre)) {
		foreach ($arbre as $tag => $feuille) {
			if (is_array($feuille)) {
				if ($tag !== intval($tag)) {
					$f = spip_xml_aplatit($feuille, $separateur);
					if (strlen($f)) {
						$tagf = explode(' ', $tag);
						$tagf = $tagf[0];
						$s .= "<$tag>$f</$tagf>";
					} else {
						$s .= "<$tag />";
					}
				} else {
					$s .= spip_xml_aplatit($feuille);
				}
				$s .= $separateur;
			} else {
				$s .= "$feuille$separateur";
			}
		}
	}

	return strlen($separateur) ? substr($s, 0, -strlen($separateur)) : $s;
}

// https://code.spip.net/@spip_xml_tagname
function spip_xml_tagname($tag) {
	if (preg_match(',^([a-z][\w:]*),i', $tag, $reg)) {
		return $reg[1];
	}

	return '';
}

// https://code.spip.net/@spip_xml_decompose_tag
function spip_xml_decompose_tag($tag) {
	$tagname = spip_xml_tagname($tag);
	$liste = [];
	$tag = ltrim(strpbrk($tag, " \n\t"));
	$p = strpos($tag, '=');
	while ($p !== false) {
		$attr = trim(substr($tag, 0, $p));
		$tag = ltrim(substr($tag, $p + 1));
		$quote = $tag[0];
		$p = strpos($tag, $quote, 1);
		$cont = substr($tag, 1, $p - 1);
		$liste[$attr] = $cont;
		$tag = substr($tag, $p + 1);
		$p = strpos($tag, '=');
	}

	return [$tagname, $liste];
}

/**
 * Recherche dans un arbre XML généré par `spip_xml_parse()` (ou une branche de cet arbre)
 * les clés de l'arbre qui valident la regexp donnée.
 *
 * Les branches qui valident la regexp sont retournées dans le tableau `$matches`.
 *
 * @see spip_xml_parse()
 * @see spip_xml_decompose_tag()
 *
 * @param string $regexp
 *     Expression régulière
 * @param array $arbre
 *     Arbre XML
 * @param array $matches
 *     Branches de l'arbre validant la rexgep
 * @param bool $init ?
 * @return bool
 *     false si aucun élément ne valide l'expression régulière, true sinon.
 **/
function spip_xml_match_nodes($regexp, &$arbre, &$matches, $init = true) {
	if ($init) {
		$matches = [];
	}
	if (is_array($arbre) && count($arbre)) {
		foreach (array_keys($arbre) as $tag) {
			if (preg_match($regexp, $tag)) {
				$matches[$tag] = &$arbre[$tag];
			}
			if (is_array($arbre[$tag])) {
				foreach (array_keys($arbre[$tag]) as $occurences) {
					spip_xml_match_nodes($regexp, $arbre[$tag][$occurences], $matches, false);
				}
			}
		}
	}

	return (count($matches));
}
