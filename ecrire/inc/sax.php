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

include_spip('inc/filtres');
include_spip('inc/charsets');

// http://doc.spip.org/@xml_debutElement
function xml_debutElement($parser, $name, $attrs)
{
	global $phraseur_xml;
	$depth = &$phraseur_xml->depth;
	$contenu = &$phraseur_xml->contenu;
	$ouvrant = &$phraseur_xml->ouvrant;
	$reperes = &$phraseur_xml->reperes;

	$t = isset($ouvrant[$depth]) ? $ouvrant[$depth] : ' ';
	// espace initial signifie: deja integree au resultat
	if ($t[0] != ' ')
	  {
	    $phraseur_xml->res .= '<' . $t . '>';
	    $ouvrant[$depth] = ' ' . $t;
	  }
	$t = $contenu[$depth];
	// n'indenter que s'il y a un separateur avant
	$phraseur_xml->res .= ereg_replace("[\n\t ]+$",  "\n$depth", $t);
	$contenu[$depth] = "";
	$att = '';
	$sep = ' ';
	foreach ($attrs as $k => $v) {
	  $delim = strpos($v, "'") === false ? "'" : '"';
	  $val = entites_html($v);
	  $att .= $sep .  $k . "=" . $delim
	    . ($delim !== '"' ? str_replace('&quot;', '"', $val) : $val)
	    . $delim;
	  $sep = "\n $depth";
	}
	$depth .= '  ';
	$contenu[$depth] = "";
	$ouvrant[$depth] = $name . $att;
	$reperes[$depth] = xml_get_current_line_number($parser);
}

// http://doc.spip.org/@xml_finElement
function xml_finElement($parser, $name, $fusion_bal=false)
{
	global $phraseur_xml;
	$depth = &$phraseur_xml->depth;
	$contenu = &$phraseur_xml->contenu;
	$ouvrant = &$phraseur_xml->ouvrant;

	$ouv = $ouvrant[$depth];
	if ($ouv[0] != ' ')
	  $ouvrant[$depth] = ' ' . $ouv;
	else $ouv= "";
	$t = $contenu[$depth];
	$depth = substr($depth, 2);
	$t = ereg_replace("[\n\t ]+$", "\n" . $depth, $t);

  // fusion <balise></balise> en <balise />.
  // ATTENTION,  certains clients http croient que fusion ==> pas d'atttributs
  // en particulier pour les balises Script et A.
  // en presence d'attributs ne le faire que si la DTD est dispo et d'accord
  // (param fusion_bal)

	if ($t || (($ouv != $name) AND !$fusion_bal))
	  $phraseur_xml->res .= ($ouv ? ('<' . $ouv . '>') : '') . $t . "</" . $name . ">";
	else
	  $phraseur_xml->res .= ($ouv ? ('<' . $ouv  . ' />') : ("</" .  $name . ">"));
}

// http://doc.spip.org/@xml_textElement
function xml_textElement($parser, $data)
{
	global $phraseur_xml;

	$depth = &$phraseur_xml->depth;
	$contenu = &$phraseur_xml->contenu;
	$contenu[$depth] .= preg_match('/^script/',$phraseur_xml->ouvrant[$depth])
	  ? $data
	  : entites_html($data);
}

// http://doc.spip.org/@xml_PiElement
function xml_PiElement($parser, $target, $data)
{
	global $phraseur_xml;
	$depth = &$phraseur_xml->depth;
	$contenu = &$phraseur_xml->contenu;
	if (strtolower($target) != "php")
	  $contenu[$depth] .= $data;
	else {
		ob_start();
		eval($data);
		$data = ob_get_contents();
		ob_end_clean();
		$contenu[$depth] .= $data;
	}
}


// http://doc.spip.org/@xml_defautElement
function xml_defautElement($parser, $data)
{
	global $phraseur_xml;
	$depth = &$phraseur_xml->depth;
	$contenu = &$phraseur_xml->contenu;

	if (!isset($contenu[$depth])) $contenu[$depth]='';
	$contenu[$depth] .= $data;
}

// http://doc.spip.org/@xml_parsestring
function xml_parsestring($phraseur, $data)
{
	global $phraseur_xml;
	$phraseur_xml->contenu[$phraseur_xml->depth] ='';

	if (!xml_parse($phraseur, $data, true)) {
	  // ne pas commencer le message par un "<" (cf inc_sax_dist)
	  $phraseur_xml->err = array(
	    xml_error_string(xml_get_error_code($phraseur)) .
		  coordonnees_erreur($phraseur) . "<br />\n" .
		  (!$phraseur_xml->depth ? '' :
		   (
		    _L("derni&egrave;re balise non referm&eacute;e&nbsp;: ") .
		    "<tt>" .
		    $phraseur_xml->ouvrant[$phraseur_xml->depth] .
		    "</tt>" .
		    _L(" ligne ") .
		    $phraseur_xml->reperes[$phraseur_xml->depth] .
		    "<br />\n" )));
	}
}

// http://doc.spip.org/@coordonnees_erreur
function coordonnees_erreur($xml_parser)
{
  return
    ' ' .
	xml_get_current_line_number($xml_parser) .
    ' ' .
	xml_get_current_column_number($xml_parser);
}

// http://doc.spip.org/@inc_sax_dist
function inc_sax_dist($page, $apply=false)
{
	global $phraseur_xml;

	// init par defaut si pas fait (espace public)
	if (!isset($GLOBALS['phraseur_xml'])) {
		$indenter_xml = charger_fonction('indenter_xml', 'inc');
		return $indenter_xml($page, $apply);
	}

	$xml_parser = xml_parser_create($GLOBALS['meta']['charset']);

	xml_set_element_handler($xml_parser,
			array($phraseur_xml, "debutElement"),
			array($phraseur_xml, "finElement"));

	xml_set_character_data_handler($xml_parser,
				       array($phraseur_xml, "textElement"));

	xml_set_processing_instruction_handler($xml_parser,
				       array($phraseur_xml, 'PiElement'));

	xml_set_default_handler($xml_parser,
				array($phraseur_xml, "defautElement"));

	xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);

	unset($GLOBALS['xhtml_error']);

	if ($apply) {
		ob_start();
		$page();
		$page = ob_get_contents();
		ob_end_clean();
	}

	$res = $phraseur_xml->phraserTout($xml_parser, $page);

	xml_parser_free($xml_parser);

	if ($res[0] == '<') return $res;

	$GLOBALS['xhtml_error'] = $res;
	return $page;
}

?>
