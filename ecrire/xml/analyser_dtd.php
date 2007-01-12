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

include_spip('xml/interfaces');

function charger_dtd($grammaire, $avail)
{
	spip_timer('dtd');
	$dtc = new DTC;
	analyser_dtd($grammaire, $avail, $dtc);

	// tri final pour presenter les suggestions de corrections
	foreach ($dtc->peres as $k => $v) {
		asort($v);
		$dtc->peres[$k] = $v;
	  } 
	  
	spip_log("Analyser DTD $avail $grammaire (" . spip_timer('dtd') . ") " . count($dtc->macros)  . ' macros, ' . count($dtc->elements)  . ' elements, ' . count($dtc->attributs) . " listes d'attributs, " . count($dtc->entites) . " entites");
#	$r = $dtc->regles; ksort($r);foreach($r as $l => $v) echo "<b>$l</b> '$v' ", join (', ',array_keys($dtc->attributs[$l])), "<br />\n";exit;
	return $dtc;
}

// Compiler une regle de production en une Regexp qu'on appliquera sur la
// suite des noms de balises separes par des espaces. Du coup:
// supprimer #PCDATA etc, ca ne sert pas pour le controle des balises;
// supprimer les virgules (les sequences sont implicites dans une Regexp)
// conserver | + * ? ( ) qui ont la meme signification en DTD et en Regexp;
// faire suivre chaque nom d'un espace (et supprimer les autres) ...
// et parentheser le tout pour que  | + * ? s'applique dessus.

// http://doc.spip.org/@compilerRegle
function compilerRegle($val)
{
	$x = str_replace('\s*()\s*','',
		preg_replace('/\s*,\s*/','',
		preg_replace('/(\w+)\s*/','(\1 )',
		preg_replace('/\s*([(+*|])\s*/','\1',
		preg_replace('/\s*#\w+\s*[,|]?\s*/','', $val)))));
	return $x;
}


// http://doc.spip.org/@analyser_dtd
function analyser_dtd($loc, $avail, &$dtc)
{
	if ($avail == 'SYSTEM')
	  $file = $loc;
	else {
	  $file = sous_repertoire(_DIR_DTD);
	  $file .= preg_replace('/[^\w.]/','_', $loc);
	}

	$dtd = '';
	if (@is_readable($file)) {
		lire_fichier($file, $dtd);
	} else {
		if ($avail == 'PUBLIC') {
			include_spip('inc/distant');
			if ($dtd = recuperer_page($loc))
				ecrire_fichier($file, $dtd); 
		}
	}

	if (!$dtd = ltrim($dtd)) {
		spip_log("DTD $loc inaccessible");
		return array();
	}

	while ($dtd) {
		if ($dtd[0] != '<')
			$r = analyser_dtd_lexeme($dtd, $dtc, $loc);
		elseif ($dtd[1] != '!')
			$r = analyser_dtd_pi($dtd, $dtc, $loc);
		else switch ($dtd[3]) {
	  case '%' : $r = analyser_dtd_data($dtd, $dtc, $loc); break;
	  case 'T' : $r = analyser_dtd_attlist($dtd, $dtc, $loc);break;
	  case 'L' : $r = analyser_dtd_element($dtd, $dtc, $loc);break;
	  case 'N' : $r = analyser_dtd_entity($dtd, $dtc, $loc);break;
	  case 'O' : $r = analyser_dtd_notation($dtd, $dtc, $loc);break;
	  case '-' : $r = analyser_dtd_comment($dtd, $dtc, $loc); break;
	  default: $r = -1;
	  }

	  if (!is_string($r)) {
	    spip_log("erreur $r dans la DTD  " . substr($dtd,0,80) . ".....");
	    return array();
	  }
	  $dtd = $r;
 
	}
}

// http://doc.spip.org/@analyser_dtd_comment
function analyser_dtd_comment($dtd, &$dtc, $grammaire){
	// ejecter les commentaires, surtout quand ils contiennent du code.
	// Option /s car sur plusieurs lignes parfois

	if (!preg_match('/^<!--.*?-->\s*(.*)$/s',$dtd, $m))
		return -6;
	return $m[1];
}

// http://doc.spip.org/@analyser_dtd_pi
function analyser_dtd_pi($dtd, &$dtc, $grammaire){
	if (!preg_match('/^<\?.*?>\s*(.*)$/s', $dtd, $m))
		return -10;
	return $m[1];
}

// http://doc.spip.org/@analyser_dtd_lexeme
function analyser_dtd_lexeme($dtd, &$dtc, $grammaire){
	if (!preg_match(_REGEXP_ENTITY_DEF,$dtd, $m))
		return -9;

	list(,$s) = $m;
	$n = $dtc->macros[$s];
	if (is_array($n)) {
	    // en cas d'inclusion, l'espace de nom est le meme
		analyser_dtd($n[1], $n[0], $dtc);
	}
	
	return ltrim(substr($dtd,strlen($m[0])));
}

// il faudrait prevoir plusieurs niveaux d'inclusion.
// (Ruby en utilise mais l'erreur est transparente. Scandaleux coup de pot)

// http://doc.spip.org/@analyser_dtd_data
function analyser_dtd_data($dtd, &$dtc, $grammaire){
	if (!preg_match('/^<!\[%([^;]*);\s*\[\s*(.*?)\]\]>\s*(.*)$/s',$dtd, $m))
		return -6;
	if ($dtc->macros[$m[1]] == 'INCLUDE')
		$retour = $m[2] . $m[3];
	else $retour = $m[3]; 
	return $retour;
}

// http://doc.spip.org/@analyser_dtd_notation
function analyser_dtd_notation($dtd, &$dtc, $grammaire){
	if (!preg_match('/^<!NOTATION.*?>\s*(.*)$/s',$dtd, $m))
		return -8;
	spip_log("analyser_dtd_notation a ecrire");
	return $m[1];
}

// http://doc.spip.org/@analyser_dtd_entity
function analyser_dtd_entity($dtd, &$dtc, $grammaire)
{
	if (!preg_match(_REGEXP_ENTITY_DECL, $dtd, $m))
		return -2;

	list($t, $term, $nom, $type, $val, $q, $alt, $dtd) = $m;

	if (isset($dtc->macros[$nom]) AND $dtc->macros[$nom])
		return $dtd;
	if (isset($dtc->entites[$nom]))
		spip_log("redefinition de l'entite $nom");
	if  (!$term)
		$dtc->entites[$nom] = expanserEntite($val, $dtc->macros);
	elseif (!$type)
		$dtc->macros[$nom] = expanserEntite($val, $dtc->macros);
	elseif (!$alt)
		$dtc->macros[$nom] = expanserEntite($val, $dtc->macros);
	else {
		if (strpos($alt, '/') === false)
			$alt = preg_replace(',/[^/]+$,', '/', $grammaire)
			. $alt ;
		$dtc->macros[$nom] = array($type, $alt);
	} 
	return $dtd;
}

// Dresser le tableau des filles potentielles de l'element
// pour traquer tres vite les illegitimes.
// Si la regle a au moins une sequence (i.e. une virgule)
// ou n'est pas une itération (i.e. se termine par * ou +)
// en faire une RegExp qu'on appliquera aux balises rencontrees.
// Sinon, conserver seulement le type de l'iteration car la traque
// aura fait l'essentiel du controle sans memorisation des balises.
// Fin du controle en finElement

// http://doc.spip.org/@analyser_dtd_element
function analyser_dtd_element($dtd, &$dtc, $grammaire)
{
	if (!preg_match('/^<!ELEMENT\s+(\S+)\s+([^>]*)>\s*(.*)$/s', $dtd, $m))
		return -3;

	list(,$nom, $val, $dtd) = $m;
	$nom = expanserEntite($nom, $dtc->macros);
	$val = compilerRegle(expanserEntite($val, $dtc->macros));
	if (isset($dtc->elements[$nom])) {
		spip_log("redefinition de l'element $nom dans la DTD");
		return -4;
	}
	$filles = array();
	if ($val == '(EMPTY )')
		$dtc->regles[$nom] = 'EMPTY';
	elseif  ($val == '(ANY )') 
		$dtc->regles[$nom] = 'ANY';
	else {
		$last = substr($val,-1);
		if (preg_match('/ \w/', $val)
		OR strpos('*+', $last) === false)
			$dtc->regles[$nom] = "/^$val$/";
		else
			$dtc->regles[$nom] = $last;
			$filles = array_values(preg_split('/\W+/', $val,-1, PREG_SPLIT_NO_EMPTY));

			foreach ($filles as $k) {
				if (!isset($dtc->peres[$k]))
				  $dtc->peres[$k] = array();
				if (!in_array($nom, $dtc->peres[$k]))
					$dtc->peres[$k][]= $nom;
			}
	}
	$dtc->elements[$nom]= $filles;
	return $dtd;
}


// http://doc.spip.org/@analyser_dtd_attlist
function analyser_dtd_attlist($dtd, &$dtc, $grammaire)
{
	if (!preg_match('/^<!ATTLIST\s+(\S+)\s+([^>]*)>\s*(.*)/s', $dtd, $m))
		return -5;

	list(,$nom, $val, $dtd) = $m;
	$nom = expanserEntite($nom, $dtc->macros);
	$val = expanserEntite($val, $dtc->macros);
	if (!isset($dtc->attributs[$nom]))
		$dtc->attributs[$nom] = array();

	if (preg_match_all("/\s*(\S+)\s+(([(][^)]*[)])|(\S+))\s+([^\s']*)(\s*'[^']*')?/", $val, $r2, PREG_SET_ORDER)) {
		foreach($r2 as $m2) {
			$v = preg_match('/^\w+$/', $m2[2]) ? $m2[2]
			  : ('/^' . preg_replace('/\s+/', '', $m2[2]) . '$/');
			$m21 = expanserEntite($m2[1], $dtc->macros);
			$m25 = expanserEntite($m2[5], $dtc->macros);
			$dtc->attributs[$nom][$m21] = array($v, $m25);
		}
	}

	return $dtd;
}


// http://doc.spip.org/@expanserEntite
function expanserEntite($val, $macros)
{
	if (preg_match_all(_REGEXP_ENTITY_USE, $val, $r, PREG_SET_ORDER)){
	  foreach($r as $m) {
		  $ent = $m[1];
		  // il peut valoir ""
			if (isset($macros[$ent]))
				$val = str_replace($m[0], $macros[$ent], $val);
	  }
	}
	return trim(preg_replace('/\s+/', ' ', $val));
}



?>
