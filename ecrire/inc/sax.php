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

// http://doc.spip.org/@PhraseurXML
class PhraseurXML {

// http://doc.spip.org/@debutElement
function debutElement($parser, $name, $attrs)
{
  global $phraseur_xml;

  if ($phraseur_xml->elements)
    validerElement($parser, $name, $attrs);

  $depth = &$phraseur_xml->depth;
  $contenu = &$phraseur_xml->contenu;
  $ouvrant = &$phraseur_xml->ouvrant;
  $reperes = &$phraseur_xml->reperes;
  $res = &$phraseur_xml->res;

  $t = isset($ouvrant[$depth]) ? $ouvrant[$depth] : ' ';
  // espace initial signifie: deja integree au resultat
  if ($t[0] != ' ')
    {
      $res .= '<' . $t . '>';
      $ouvrant[$depth] = ' ' . $t;
    }
  $t = $contenu[$depth];
  // n'indenter que s'il y a un separateur avant
  $res .= ereg_replace("[\n\t ]+$",  "\n$depth", $t);
  $contenu[$depth] = "";
  $att = '';
  $sep = ' ';
  foreach ($attrs as $k => $v) {
	if ($phraseur_xml->attributs)
	  validerAttribut($parser, $k, $v, $name);
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

// http://doc.spip.org/@finElement
function finElement($parser, $name)
{
  global $phraseur_xml;
  $depth = &$phraseur_xml->depth;
  $contenu = &$phraseur_xml->contenu;
  $ouvrant = &$phraseur_xml->ouvrant;
  $res = &$phraseur_xml->res;

  $ouv = $ouvrant[$depth];
  if ($ouv[0] != ' ')
      $ouvrant[$depth] = ' ' . $ouv;
  else $ouv= "";
  $t = $contenu[$depth];
  $depth = substr($depth, 2);
  $t = ereg_replace("[\n\t ]+$", "\n" . $depth, $t);
  // fusion <balise></balise> en <balise />.
  // ATTENTION, en presence d'attributs ne le faire que si la DTD le dit:
  // pour les autres, certains clients http croient que fusion ==> pas d'att
  // en particulier pour les balises Script et A.
  if ($t || (($ouv !=$name) AND $phraseur_xml->elements[$name][0] != 'EMPTY'))
    $res .= ($ouv ? ('<' . $ouv . '>') : '') . $t . "</" . $name . ">";
  else
    $res .= ($ouv ? ('<' . $ouv  . ' />') : ("</" .  $name . ">"));
}

// http://doc.spip.org/@textElement
function textElement($parser, $data)
{
  global $phraseur_xml;
  $depth = &$phraseur_xml->depth;
  $contenu = &$phraseur_xml->contenu;
  $contenu[$depth] .= preg_match('/^script/',$phraseur_xml->ouvrant[$depth])
    ? $data
    : entites_html($data);
}

// http://doc.spip.org/@PiElement
function PiElement($parser, $target, $data)
{
  global $phraseur_xml, $xml_parser;
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


// http://doc.spip.org/@defautElement
function defautElement($parser, $data)
{
  global $phraseur_xml;
  $depth = &$phraseur_xml->depth;
  $contenu = &$phraseur_xml->contenu;

  if (!isset($contenu[$depth])) $contenu[$depth]='';
  $contenu[$depth] .= $data;
}

// http://doc.spip.org/@xml_parsefile
function xml_parsefile($phraseur, $file)
{
	if (!($fp = fopen($file, "r"))) {
		include_spip('inc/minipres');
		echo minipres("Impossible d'ouvrir le fichier XML");
		exit;
	}
	while ($data = fread($fp, 4096)) {
		if (!xml_parse($phraseur,
			       str_replace('&#8217;',"'",$data), feof($fp))) {
			return (sprintf("erreur XML : %s ligne %d",
				xml_error_string(xml_get_error_code($phraseur)),
				xml_get_current_line_number($phraseur)));
		}
	}
	return "";
}

// http://doc.spip.org/@xml_parsestring
function xml_parsestring($phraseur, $data)
{
	global $phraseur_xml;

	$phraseur_xml->contenu[$phraseur_xml->depth] ='';

	if (!xml_parse($phraseur, $data, true)) {
	  // ne pas commencer le message par un "<" (cf inc_sax_dist)
		return  xml_error_string(xml_get_error_code($phraseur)) .
		  coordonnees_erreur($phraseur) . '<br />' .
		  (!$phraseur_xml->depth ? '' :
		   (
		    _L("derni&egrave;re balise non referm&eacute;e&nbsp;: ") .
		    "<tt>" .
		    $phraseur_xml->ouvrant[$phraseur_xml->depth] .
		    "</tt>" .
		    _L(" ligne ") .
		    $phraseur_xml->reperes[$phraseur_xml->depth] .
		    '<br />' ));
	}
	foreach ($phraseur_xml->idrefs as $idref) {
		list($nom, $ligne, $col) = $idref;
		if (!isset($phraseur_xml->ids[$nom]))
		      $phraseur_xml->err[]= " <p><b>$nom</b>"
		      . _L(" ID inconnu ligne ")
		      . $ligne
		      . _L(" colonne ")
		      . $col;
	}
	if ($phraseur_xml->err)
		return join('<br />', $phraseur_xml->err) . '<br />';

	return $phraseur_xml->res;
}

 var $depth = "";
 var $res = "";
 var $contenu = array();
 var $ouvrant = array();
 var $reperes = array();
 var $elements = array();
 var $entites = array();
 var $attributs = array();
 var $ids = array();
 var $idrefs = array();
 var $err = array();
}

// http://doc.spip.org/@inc_sax_dist
function inc_sax_dist($page, $apply=false) {
	global $phraseur_xml, $xml_parser;

	$xml_parser = xml_parser_create($GLOBALS['meta']['charset']);
	xml_set_element_handler($xml_parser,
			array($phraseur_xml, "debutElement"),
			array($phraseur_xml, "finElement"));
	xml_set_character_data_handler($xml_parser, array($phraseur_xml, "textElement"));
	xml_set_processing_instruction_handler($xml_parser, array($phraseur_xml, 'PiElement'));
	xml_set_default_handler($xml_parser, array($phraseur_xml, "defautElement"));
	xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
	unset($GLOBALS['xhtml_error']);

	if ($apply) {
		ob_start();
		$page();
		$page = ob_get_contents();
		ob_end_clean();
	}
	if ($GLOBALS['xml_validation']
	AND $validateur = charger_fonction('validateur', 'inc', true)) {
		$validateur($page);
		if (isset($phraseur_xml->entites['HTMLsymbol']))
			$page = unicode2charset(html2unicode($page, true));
	}
	$res = $phraseur_xml->xml_parsestring($xml_parser, $page);
	xml_parser_free($xml_parser);

	if ($res[0] != '<')
		$GLOBALS['xhtml_error'] = $res;
	else
		$page = $res;
	return $page;
}

// http://doc.spip.org/@coordonnees_erreur
function coordonnees_erreur($xml_parser)
{
  return
	_L(" ligne ") .
	xml_get_current_line_number($xml_parser) .
	_L(" colonne ") .
	xml_get_current_column_number($xml_parser);
}

$GLOBALS['phraseur_xml'] = new PhraseurXML();
// exemple d'appel en ligne de commande:
#$error = $phraseur_xml->xml_parsefile($xml_parser, $_SERVER['argv'][1]);

?>
