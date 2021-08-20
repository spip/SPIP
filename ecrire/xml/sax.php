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

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/charsets');
include_spip('xml/interfaces');

/**
 * Encoder les entites
 *
 * @param string $texte
 * @return string
 */
function xml_entites_html($texte) {
	if (
		!is_string($texte) or !$texte
		or strpbrk($texte, "&\"'<>") == false
	) {
		return $texte;
	}

	if (!function_exists('spip_htmlspecialchars')) {
		include_spip('inc/filtres_mini');
	}
	$texte = spip_htmlspecialchars($texte, ENT_QUOTES);

	return $texte;
}

// https://code.spip.net/@xml_debutElement
function xml_debutElement($phraseur, $name, $attrs) {
	$depth = $phraseur->depth;

	$t = isset($phraseur->ouvrant[$depth]) ? $phraseur->ouvrant[$depth] : ' ';
	// espace initial signifie: deja integree au resultat
	if ($t[0] != ' ') {
		$phraseur->res .= '<' . $t . '>';
		$phraseur->ouvrant[$depth] = ' ' . $t;
	}
	$t = $phraseur->contenu[$depth];
	// n'indenter que s'il y a un separateur avant
	$phraseur->res .= preg_replace("/[\n\t ]+$/", "\n$depth", $t);
	$phraseur->contenu[$depth] = '';
	$att = '';
	$sep = ' ';
	foreach ($attrs as $k => $v) {
		$delim = strpos($v, "'") === false ? "'" : '"';
		$val = xml_entites_html($v);
		$att .= $sep . $k . '=' . $delim
			. ($delim !== '"' ? str_replace('&quot;', '"', $val) : $val)
			. $delim;
		$sep = "\n $depth";
	}
	$phraseur->depth .= '  ';
	$phraseur->contenu[$phraseur->depth] = '';
	$phraseur->ouvrant[$phraseur->depth] = $name . $att;
	$phraseur->reperes[$phraseur->depth] = xml_get_current_line_number($phraseur->sax);
}

// https://code.spip.net/@xml_finElement
function xml_finElement($phraseur, $name, $fusion_bal = false) {
	$ouv = $phraseur->ouvrant[$phraseur->depth];

	if ($ouv[0] != ' ') {
		$phraseur->ouvrant[$phraseur->depth] = ' ' . $ouv;
	} else {
		$ouv = '';
	}
	$t = $phraseur->contenu[$phraseur->depth];
	$phraseur->depth = substr($phraseur->depth, 2);
	$t = preg_replace("/[\n\t ]+$/", "\n" . $phraseur->depth, $t);

	// fusion <balise></balise> en <balise />.
	// ATTENTION,  certains clients http croient que fusion ==> pas d'atttributs
	// en particulier pour les balises Script et A.
	// en presence d'attributs ne le faire que si la DTD est dispo et d'accord
	// (param fusion_bal)

	if ($t || (($ouv != $name) and !$fusion_bal)) {
		$phraseur->res .= ($ouv ? ('<' . $ouv . '>') : '') . $t . '</' . $name . '>';
	} else {
		$phraseur->res .= ($ouv ? ('<' . $ouv . ' />') : ('</' . $name . '>'));
	}
}

// https://code.spip.net/@xml_textElement
function xml_textElement($phraseur, $data) {
	$depth = $phraseur->depth;
	$phraseur->contenu[$depth] .= preg_match('/^script/', $phraseur->ouvrant[$depth])
		? $data
		: xml_entites_html($data);
}

function xml_piElement($phraseur, $target, $data) {
	$depth = $phraseur->depth;

	if (strtolower($target) != 'php') {
		$phraseur->contenu[$depth] .= $data;
	} else {
		ob_start();
		eval($data);
		$data = ob_get_contents();
		ob_end_clean();
		$phraseur->contenu[$depth] .= $data;
	}
}


// https://code.spip.net/@xml_defautElement
function xml_defaultElement($phraseur, $data) {
	$depth = $phraseur->depth;

	if (!isset($phraseur->contenu[$depth])) {
		$phraseur->contenu[$depth] = '';
	}
	$phraseur->contenu[$depth] .= $data;
}

// https://code.spip.net/@xml_parsestring
function xml_parsestring($phraseur, $data) {
	$phraseur->contenu[$phraseur->depth] = '';

	if (!xml_parse($phraseur->sax, $data, true)) {
		coordonnees_erreur(
			$phraseur,
			xml_error_string(xml_get_error_code($phraseur->sax))
			. "<br />\n" .
			(!$phraseur->depth ? '' :
				('(' .
					_T('erreur_balise_non_fermee') .
					' <tt>' .
					$phraseur->ouvrant[$phraseur->depth] .
					'</tt> ' .
					_T('ligne') .
					' ' .
					$phraseur->reperes[$phraseur->depth] .
			") <br />\n"))
		);
	}
}

// https://code.spip.net/@coordonnees_erreur
function coordonnees_erreur($phraseur, $msg) {
	$entete_length = substr_count($phraseur->entete, "\n");
	$phraseur->err[] = [
		$msg,
		xml_get_current_line_number($phraseur->sax) + $entete_length,
		xml_get_current_column_number($phraseur->sax)
	];
}

// https://code.spip.net/@xml_sax_dist
function xml_sax_dist($page, $apply = false, $phraseur = null, $doctype = '', $charset = null) {
	if (is_null($charset)) {
		$charset = $GLOBALS['meta']['charset'];
	}
	if ($apply) {
		ob_start();
		if (is_array($apply)) {
			$r = call_user_func_array($page, $apply);
		} else {
			$r = $page();
		}
		$page = ob_get_contents();
		ob_end_clean();
		// fonction sans aucun "echo", ca doit etre le resultat
		if (!$page) {
			$page = $r;
		}
	}

	if (!$page) {
		return '';
	}
	// charger la DTD et transcoder les entites,
	// et escamoter le doctype que sax mange en php5 mais pas en  php4
	if (!$doctype) {
		if (!$r = analyser_doctype($page)) {
			$page = _MESSAGE_DOCTYPE . _DOCTYPE_ECRIRE
				. preg_replace(_REGEXP_DOCTYPE, '', $page);
			$r = analyser_doctype($page);
		}
		list($entete, $avail, $grammaire, $rotlvl) = array_pad($r, 4, null);
		$page = substr($page, strlen($entete));
	} else {
		$avail = 'SYSTEM';
		$grammaire = $doctype;
		$rotlvl = basename($grammaire);
	}

	include_spip('xml/analyser_dtd');
	$dtc = charger_dtd($grammaire, $avail, $rotlvl);
	$page = sax_bug($page, $dtc, $charset);

	// compatibilite Tidy espace public
	if (!$phraseur) {
		$indenter_xml = charger_fonction('indenter', 'xml');

		return $indenter_xml($page, $apply);
	}

	$xml_parser = xml_parser_create($charset);

	xml_set_element_handler(
		$xml_parser,
		[$phraseur, 'debutElement'],
		[$phraseur, 'finElement']
	);

	xml_set_character_data_handler(
		$xml_parser,
		[$phraseur, 'textElement']
	);

	xml_set_processing_instruction_handler(
		$xml_parser,
		[$phraseur, 'piElement']
	);

	xml_set_default_handler(
		$xml_parser,
		[$phraseur, 'defaultElement']
	);

	xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);

	$phraseur->sax = $xml_parser;
	if (isset($entete)) {
		$phraseur->entete = $entete;
	}
	$phraseur->page = $page;
	$phraseur->dtc = $dtc;
	$phraseur->phraserTout($xml_parser, $page);
	xml_parser_free($xml_parser);
	$phraseur->sax = '';

	return $phraseur;
}

// SAX ne dit pas si une Entite est dans un attribut ou non.
// Les eliminer toutes sinon celles des attributs apparaissent en zone texte!
// Celles fondamentales pour la lecture (lt gt quot amp) sont conservees
// (d'ailleurs SAX ne les considere pas comme des entites dans un attribut)
// Si la DTD est dispo, on va chercher les entites dedans
// sinon on se rabat sur ce qu'en connait SPIP en standard.

// https://code.spip.net/@sax_bug
function sax_bug($data, $dtc, $charset = null) {
	if (is_null($charset)) {
		$charset = $GLOBALS['meta']['charset'];
	}

	if ($dtc) {
		$trans = [];

		foreach ($dtc->entites as $k => $v) {
			if (!strpos(' amp lt gt quot ', $k)) {
				$trans["&$k;"] = $v;
			}
		}
		$data = strtr($data, $trans);
	} else {
		$data = html2unicode($data, true);
	}

	return unicode2charset($data, $charset);
}

// Retirer < ? xml... ? > et autre PI, ainsi que les commentaires en debut
// afin de reperer le Doctype et le decomposer selon:
// http://www.freebsd.org/doc/fr_FR.ISO8859-1/books/fdp-primer/sgml-primer-doctype-declaration.html
// Si pas de Doctype et premiere balise = RSS prendre la doctype RSS 0.91:
// les autres formats RSS n'ont pas de DTD,
// mais un XML Schema que SPIP ne fait pas encore lire.
// https://code.spip.net/@analyser_doctype
function analyser_doctype($data) {
	if (!preg_match(_REGEXP_DOCTYPE, $data, $page)) {
		if (preg_match(_REGEXP_XML, $data, $page)) {
			list(, $entete, $topelement) = $page;
			if ($topelement == 'rss') {
				return [
					$entete,
					'PUBLIC',
					_DOCTYPE_RSS,
					'rss-0.91.dtd'
				];
			} else {
				$dtd = $topelement . '.dtd';
				$f = find_in_path($dtd);
				if (file_exists($f)) {
					return [$entete, 'SYSTEM', $f, $dtd];
				}
			}
		}
		spip_log('Dtd pas vu pour ' . substr($data, 0, 100));

		return [];
	}
	list($entete, , $topelement, $avail, $suite) = $page;

	if (!preg_match('/^"([^"]*)"\s*(.*)$/', $suite, $r)) {
		if (!preg_match("/^'([^']*)'\s*(.*)$/", $suite, $r)) {
			return [];
		}
	}
	list(, $rotlvl, $suite) = $r;

	if (!$suite) {
		if ($avail != 'SYSTEM') {
			return [];
		}
		$grammaire = $rotlvl;
		$rotlvl = '';
	} else {
		if (!preg_match('/^"([^"]*)"\s*$/', $suite, $r)) {
			if (!preg_match("/^'([^']*)'\s*$/", $suite, $r)) {
				return [];
			}
		}

		$grammaire = $r[1];
	}

	return [$entete, $avail, $grammaire, $rotlvl];
}
