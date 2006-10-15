<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@PhraseurXML
class PhraseurXML {

// http://doc.spip.org/@debutElement
function debutElement($parser, $name, $attrs)
{
  global $phraseur_xml;
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
	$delim = strpos($v, "'") === false ? "'" : '"';
	$att .= $sep .  $k . "=" . $delim
	  . str_replace('"',  '&quot;', $phraseur_xml->translate_entities($v))
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
  // fusion <balise></balise> en <balise /> sauf pour qq unes qui hallucinent
  if ($t || ($name == 'a') || ($name == 'textarea'))
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
  $contenu[$depth] .= $phraseur_xml->translate_entities($data);
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

  $contenu[$depth] .= $data;
}

// http://doc.spip.org/@translate_entities
 function translate_entities($data)
 {
   return
    str_replace('<', '&lt;', 
		str_replace('>', '&gt;', 
			    ereg_replace('[&]([A-Za-z0-9]*[^A-Za-z0-9;])',
					 "&amp;\\1",
					 $data)));
 }

// http://doc.spip.org/@xml_parsefile
function xml_parsefile($xml_parser, $file)
{
  if (!($fp = fopen($file, "r"))) {
   die("Impossible d'ouvrir le fichier XML");
  }
  while ($data = fread($fp, 4096)) {
    if (!xml_parse($xml_parser, str_replace('&#8217;',"'",$data), feof($fp))) {
      return (sprintf("erreur XML : %s ligne %d",
		      xml_error_string(xml_get_error_code($xml_parser)),
		      xml_get_current_line_number($xml_parser)));
    }
  }
  return "";
}

// http://doc.spip.org/@xml_parsestring
function xml_parsestring($xml_parser, $data)
{
	global $phraseur_xml;
	$r = "";
	if (!xml_parse($xml_parser, $data, true)) {
	  // ne pas commencer le message par un "<" (cf spip_sax)
	  $r = xml_error_string(xml_get_error_code($xml_parser)) .
	    _L(" ligne ") .
	    xml_get_current_line_number($xml_parser) .
	    _L(" colonne ") .
	    xml_get_current_column_number($xml_parser) .
	    '<br />' .
	    _L("derni&egrave;re balise non referm&eacute;e&nbsp;: ") .
	    "<tt>" .
	    $phraseur_xml->ouvrant[$phraseur_xml->depth] .
	    "</tt>" .
	    _L(" ligne ") .
	    $phraseur_xml->reperes[$phraseur_xml->depth];

	} else $r = $phraseur_xml->res;

	xml_parser_free($xml_parser);
	return $r;
}

var $depth = "";
var $res = "";
var $contenu = array();
var $ouvrant = array();
var $reperes = array();
}

// xml_set_objet a utiliser a terme
global $phraseur_xml, $xml_parser;
$phraseur_xml = new PhraseurXML();

$xml_parser = xml_parser_create($GLOBALS['meta']['charset']);
xml_set_element_handler($xml_parser,
			array($phraseur_xml, "debutElement"),
			array($phraseur_xml, "finElement"));
xml_set_character_data_handler($xml_parser, array($phraseur_xml, "textElement"));
xml_set_processing_instruction_handler($xml_parser, array($phraseur_xml, 'PiElement'));
xml_set_default_handler($xml_parser, array($phraseur_xml, "defautElement"));
xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);

// http://doc.spip.org/@inc_sax_dist
function inc_sax_dist($page) {
	global $phraseur_xml, $xml_parser, $xhtml_error;
	$res = $phraseur_xml->xml_parsestring($xml_parser, $page);
	if ($res[0] != '<')
	  $xhtml_error = $res;
	else
	  $page = $res;
	return $page;
}

// exemple d'appel en ligne de commande:
#$error = $phraseur_xml->xml_parsefile($xml_parser, $_SERVER['argv'][1]);

?>
