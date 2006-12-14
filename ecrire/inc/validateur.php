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

define('_REGEXP_DOCTYPE',
	'/^\s*<!DOCTYPE\s+(\w+)\s+(\w+)\s+(.)([^\3>]*)\3\s+(.)([^\5>]*)\5[^>]*>/');

function inc_validateur_dist($data)
{
  global $phraseur_xml;

	if (!preg_match(_REGEXP_DOCTYPE, $data, $r))
		return array();

	list(,$ns, $type, $s, $nom, $s2, $grammaire) = $r;

	include_spip('inc/distant');
	$dtd = recuperer_page($grammaire);
	preg_match_all('/<!ELEMENT\s+(\w+)[^>]*>/', $dtd, $r);
	$phraseur_xml->elements = $r[1];

	$res = array();
	// on ignore les entites publiques. A ameliorer a terme
	if (preg_match_all('/<!ENTITY\s+%\s+([.\w]+)\s+"([^"]*)"\s*>/', $dtd, $r, PREG_SET_ORDER)) {
	  foreach($r as $m) {
	    list(,$nom, $val) = $m;
	    if (preg_match_all('/%([.\w]+);/', $val, $r2, PREG_SET_ORDER)) {
	      foreach($r2 as $m2)
		$val = str_replace($m2[0], $res[$m2[1]], $val);
	    }
	    $res[$nom] = $val;
	  }
	}
	$phraseur_xml->entites = $res;

	$res = array();
	if (preg_match_all('/<!ATTLIST\s+(\S+)\s+([^>]*)>/', $dtd, $r, PREG_SET_ORDER)) {
	  foreach($r as $m) {
	    list(,$nom, $val) = $m;
	    if (preg_match_all('/%([.\w]+);/', $val, $r2, PREG_SET_ORDER)) {
		foreach($r2 as $m2)
	  // parfois faux suite au non chargement des entites publiques
		  if ($x = $phraseur_xml->entites[$m2[1]])
		    $val = str_replace($m2[0], $x, $val);
	    }
	    $att = array();
	    if (preg_match_all("/\s*(\S+)\s+(([(][^)]*[)])|(\S+))\s+(\S+)(\s*'[^']*')?/", $val, $r2, PREG_SET_ORDER)) {
	      foreach($r2 as $m2)
		$att[$m2[1]] = $m2[5];
	    }
	    $res[$nom] = $att;
	  }
	}
	$phraseur_xml->attributs = $res;
}

function validerElement($parser, $name)
{
	global $phraseur_xml;

	if (!$phraseur_xml->elements) return;

	if (!in_array($name, $phraseur_xml->elements))

		$phraseur_xml->err[]= $name 
		. '&nbsp;:&nbsp;'
		. _L('balise inconnue ')
		. _L('ligne ')
		. xml_get_current_line_number($parser)
		. '<br />';
}


function validerAttribut($parser, $name, $val, $bal)
{
  global $phraseur_xml;

	if ($a = $phraseur_xml->attributs[$bal] 
	    AND !isset($a[$name]))

		$phraseur_xml->err[]= $name 
		. '&nbsp;:&nbsp;'
		. _L('attribut inconnu de ')
		. $bal 
		. _L(' ligne ')
		. xml_get_current_line_number($parser)
		. '<br />';
}


?>
