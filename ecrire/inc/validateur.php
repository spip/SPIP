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

define('_REGEXP_DOCTYPE',
	'/^\s*(<[?][^>]*>\s*)?<!DOCTYPE\s+(\w+)\s+(\w+)\s*([^>]*)>/');


// http://doc.spip.org/@inc_validateur_dist
function inc_validateur_dist($data)
{
	global $phraseur_xml;


	if (!preg_match(_REGEXP_DOCTYPE, $data, $r))
		return array();
	list(,,$topelement, $avail,$suite) = $r;

	if (!preg_match('/^"([^"]*)"\s*(.*)$/', $suite, $r))
		if (!preg_match("/^'([^']*)'\s*(.*)$/", $suite, $r))
			return array();
	list(,$rotlvl, $suite) = $r;

	if (!$suite) {
		$grammaire = $rotlvl;
		$rotlvl = '';
	} else {
		if (!preg_match('/^"([^"]*)"\s*$/', $suite, $r))
			if (!preg_match("/^'([^']*)'\s*$/", $suite, $r))
				return array();
		$grammaire = $r[1];
	}

	$dtd = '';
	if ($avail == 'SYSTEM')
	  $file = $grammaire;
	else
	  $file = _DIR_CACHE . preg_replace('/[^\w.]/','_', $grammaire);

	if (@is_readable($file)) {
		lire_fichier($file, $dtd);
	} else {
		if ($avail == 'PUBLIC') {
			include_spip('inc/distant');
			if ($dtd = recuperer_page($grammaire))
				ecrire_fichier($file, $dtd); 
		}
	}
	if (!$dtd) {
		spip_log("DTD $grammaire inaccessible");
		return array();
	}

	$res = array();

	// les entites publiques sont declarees vides. A ameliorer a terme
	if (preg_match_all('/<!ENTITY\s+%\s+([.\w]+)\s+(PUBLIC)?\s*"([^"]*)"\s*("[^"]*")?\s*>/', $dtd, $r, PREG_SET_ORDER)) {
	  foreach($r as $m) {
	    list(,$nom, $type, $val) = $m;
	    $res[$nom] =  $type ? '': expanserEntite($val, $res) ;
	  }
	} 
	$phraseur_xml->entites = $res;

	// reperer pour chaque noeud ses fils potentiels.
	// mais tant pis pour leur eventuel ordre de succession (, * +):
	// les cas sont rares et si aberrants que interet/temps-de-calcul -> 0
	$res = array();
	if (preg_match_all('/<!ELEMENT\s+(\w+)([^>]*)>/', $dtd, $r, PREG_SET_ORDER)) {
	  foreach($r as $m) {
	    list(,$nom, $val) = $m;
	    $val = expanserEntite($val, $phraseur_xml->entites);
	    $val = array_values(preg_split('/\W+/', $val,-1,PREG_SPLIT_NO_EMPTY));
	    $res[$nom]= $val;
	    foreach ($val as $k) {
		if (!isset($phraseur_xml->peres[$k])
		OR !in_array($nom, $phraseur_xml->peres[$k]))
		  $phraseur_xml->peres[$k][]= $nom;
	    }
	  }
	  foreach ($phraseur_xml->peres as $k => $v) {
	    asort($v);
	    $phraseur_xml->peres[$k] = $v;
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
		foreach($r2 as $m2) {
			$v = preg_match('/^\w+$/', $m2[2]) ? $m2[2]
			  : ('/^' . preg_replace('/\s+/', '', $m2[2]) . '$/');
			$att[$m2[1]] = array($v, $m2[5]);
		}
	    }
	    $res[$nom] = $att;
	  }
	}
	$phraseur_xml->attributs = $res;
	spip_log("DTD $topelement ($avail) $rotlvl $grammaire ". strlen($dtd) . ' octets ' . count($phraseur_xml->entites)  . ' entites, ' . count($phraseur_xml->elements)  . ' elements');
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

		$phraseur_xml->err[]= " <b>$name</b>"
		. _L(' balise inconnue ')
		.  coordonnees_erreur($parser);
	else {
	  $depth = $phraseur_xml->depth;
	  $ouvrant = $phraseur_xml->ouvrant;
	  if (isset($ouvrant[$depth])) {
	    if (preg_match('/^\s*(\w+)/', $ouvrant[$depth], $r)) {
	      $pere = $r[1];
	      if (isset($phraseur_xml->elements[$pere]))
		if (!@in_array($name, $phraseur_xml->elements[$pere])) {
		  $bons_peres = @join ('</b>, <b>', $phraseur_xml->peres[$name]);
		  $phraseur_xml->err[]= " <b>$name</b>" 
		    . _L(" n'est pas un fils de ")
		    . '<b>'
		    .  $pere
		    . '</b>'
		    . (!$bons_peres ? '' 
		       : (_L( '<p style="font-size: 80%"> mais de <b>') . $bons_peres . '</b></p>'))
		    .  coordonnees_erreur($parser);
		}
	    }
	  }
	  if (isset($phraseur_xml->attributs[$name])) {
		  foreach ($phraseur_xml->attributs[$name] as $n => $v)
		    { if (($v[1] == '#REQUIRED') AND (!isset($attrs[$n])))
			$phraseur_xml->err[]= " <b>$n</b>"
			  . '&nbsp;:&nbsp;'
			  . _L(" attribut obligatoire mais absent dans ")
			  . "<b>$name</b>"
			  .  coordonnees_erreur($parser);
		    }
	  }
	}
}


// http://doc.spip.org/@validerAttribut
function validerAttribut($parser, $name, $val, $bal)
{
	global $phraseur_xml;

	// Si la balise est inconnue, eviter d'insister
	if (!isset($phraseur_xml->attributs[$bal]))
		return ;
		
	$a = $phraseur_xml->attributs[$bal];
	if (!isset($a[$name]))
		$phraseur_xml->err[]= " <b>$name</b>"
		. _L(' attribut inconnu de ')
		. "<b>$bal</b>"
		.  coordonnees_erreur($parser);
	else{
		$type =  $a[$name][0];
		if ($type[0]=='/') {
		    if (!preg_match($a[$name][0], $val)) {
		      $phraseur_xml->err[]= " <p><b>$val</b>"
		      . _L(" valeur de l'attribut ")
		      . "<b>$name</b>"
		      . _L(' de ')
		      . "<b>$bal</b>"
		      . _L(" n'est pas conforme au motif</p><p>")
		      . "<b>" . $a[$name][0] . "</b></p>"
		      .  coordonnees_erreur($parser);
		    }
		} elseif ($type == 'ID') {
		  if (isset($phraseur_xml->ids[$val])) {
		      $phraseur_xml->err[]= " <p><b>$val</b>"
		      . _L(" valeur de l'attribut ")
		      . "<b>$name</b>"
		      . _L(' de ')
		      . "<b>$bal</b>"
		      . _L(" incorrect ")
		      .  coordonnees_erreur($parser);
		      list($l,$c) = $phraseur_xml->ids[$val];
		      $phraseur_xml->err[]= " <p><b>$val</b>"
		      . _L(" vu auparavant ")
		      . $l
		      . "  "
		      . $c;
		  } else $phraseur_xml->ids[$val] = array(xml_get_current_line_number($parser), xml_get_current_column_number($parser));
		} elseif ($type == 'IDREF') {
			$phraseur_xml->idrefs[] = array($val, xml_get_current_line_number($parser), xml_get_current_column_number($parser));
		}
	}
}
?>
