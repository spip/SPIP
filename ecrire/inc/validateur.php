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

// http://doc.spip.org/@inc_validateur_dist
function inc_validateur_dist($data)
{
	global $phraseur_xml;

	if (!preg_match(_REGEXP_DOCTYPE, $data, $r))
		return array();

	list(,$ns, $type, $s, $nom, $s2, $grammaire) = $r;

	$dtd = '';
	$file = _DIR_CACHE . preg_replace('/[\W.]/','_', $grammaire);

	if (@is_readable($file)) {
		lire_fichier($file, $dtd);
	} else {
		include_spip('inc/distant');
		// il faudrait verifier que $type=PUBLIC, et sinon agir
		if ($dtd = recuperer_page($grammaire))
			ecrire_fichier($file, $dtd); 
		else	spip_log("DTD $grammaire inaccessible");
	}

	$res = array();

	// on ignore les entites publiques. A ameliorer a terme
	if (preg_match_all('/<!ENTITY\s+%\s+([.\w]+)\s+"([^"]*)"\s*>/', $dtd, $r, PREG_SET_ORDER)) {
	  foreach($r as $m) {
	    list(,$nom, $val) = $m;
	    $res[$nom] =  expanserEntite($val, $res);
	  }
	}
	$phraseur_xml->entites = $res;

	// reperer pour chaque noeud ses fils potentiels, sans repetitions,
	// pour faire une analyse syntaxique sommaire
	$res = array();
	if (preg_match_all('/<!ELEMENT\s+(\w+)([^>]*)>/', $dtd, $r, PREG_SET_ORDER)) {
	  foreach($r as $m) {
	    list(,$nom, $val) = $m;
	    $val = expanserEntite($val, $phraseur_xml->entites);
	    $val = array_values(preg_split('/\W+/', $val));
	    $res[$nom]= $val;
	  }
	}
	$phraseur_xml->elements = $res;

	$res = array();
	if (preg_match_all('/<!ATTLIST\s+(\S+)\s+([^>]*)>/', $dtd, $r, PREG_SET_ORDER)) {
	  foreach($r as $m) {
	    list(,$nom, $val) = $m;
	    $val = expanserEntite($val, $phraseur_xml->entites);
	    $att = array();
	    if (preg_match_all("/\s*(\S+)\s+(([(][^)]*[)])|(\S+))\s+(\S+)(\s*'[^']*')?/", $val, $r2, PREG_SET_ORDER)) {
	      foreach($r2 as $m2)
		$att[$m2[1]] = $m2[5];
	    }
	    $res[$nom] = $att;
	  }
	}
	$phraseur_xml->attributs = $res;
	spip_log("DTD: " . count($phraseur_xml->entites)  . ' entites, ' . count($phraseur_xml->elements)  . ' elements');
}

// http://doc.spip.org/@expanserEntite
function expanserEntite($val, $entites)
{
	if (preg_match_all('/%([.\w]+);/', $val, $r, PREG_SET_ORDER)) {
		foreach($r as $m)
	  // parfois faux suite au non chargement des entites publiques
			if ($x = $entites[$m[1]])
				$val = str_replace($m[0], $x, $val);
	}
	return $val;
}

// http://doc.spip.org/@validerElement
function validerElement($parser, $name, $attrs)
{
	global $phraseur_xml;

	if (!$phraseur_xml->elements) return;

	if (!isset($phraseur_xml->elements[$name]))

		$phraseur_xml->err[]= $name 
		. '&nbsp;:&nbsp;'
		. _L('balise inconnue ')
		. _L('ligne ')
		. xml_get_current_line_number($parser)
		. '<br />';
	else {
	  $depth = $phraseur_xml->depth;
	  $ouvrant = $phraseur_xml->ouvrant;
	  if (isset($ouvrant[$depth])) {
	    if (preg_match('/^\s*(\w+)/', $ouvrant[$depth], $r)) {
	      $pere = $r[1];
	      if (!@in_array($name, $phraseur_xml->elements[$pere]))
		$phraseur_xml->err[]= $name 
		. '&nbsp;:&nbsp;'
		. _L(" n'est pas un fils de <b>")
		. $pere
		. _L('</b> ligne ')
		. xml_get_current_line_number($parser)
		. '<br />';
	    }
	  }
	  foreach ($phraseur_xml->attributs[$name] as $n => $v)
	    { if (($v == '#REQUIRED') AND (!isset($attrs[$n])))
		$phraseur_xml->err[]= $n 
		. '&nbsp;:&nbsp;'
		. _L(" attribut obligatoire mais absent dans <b>")
		. $name
		. _L('</b> ligne ')
		. xml_get_current_line_number($parser)
		. '<br />';
	    }
	}
}


// http://doc.spip.org/@validerAttribut
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
