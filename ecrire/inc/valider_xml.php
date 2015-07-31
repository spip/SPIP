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

include_spip('inc/sax');

// http://doc.spip.org/@validerElement
function validerElement($phraseur, $name, $attrs)
{
	global $phraseur_xml;

	if (!isset($phraseur_xml->dtc->elements[$name]))

		$phraseur_xml->err[]= " <b>$name</b> "
		. _T('zxml_inconnu_balise')
		. ' '
		.  coordonnees_erreur($phraseur);
	else {
	// controler les filles illegitimes, ca suffit 
	  $depth = $phraseur_xml->depth;
	  $ouvrant = $phraseur_xml->ouvrant;
	  if (isset($ouvrant[$depth])) {
	    if (preg_match('/^\s*(\w+)/', $ouvrant[$depth], $r)) {
	      $pere = $r[1];
	      if (isset($phraseur_xml->dtc->elements[$pere]))
		if (!@in_array($name, $phraseur_xml->dtc->elements[$pere])) {
	          $bons_peres = @join ('</b>, <b>', $phraseur_xml->dtc->peres[$name]);
	          $phraseur_xml->err[]= " <b>$name</b> "
	            . _T('zxml_non_fils')
	            . ' <b>'
	            .  $pere
	            . '</b>'
	            . (!$bons_peres ? ''
	               : ('<p style="font-size: 80%"> '._T('zxml_mais_de').' <b>'. $bons_peres . '</b></p>'))
		    .  coordonnees_erreur($phraseur);
		} else if ($phraseur_xml->dtc->regles[$pere][0]=='/') {
		  $phraseur_xml->fratrie[substr($depth,2)].= "$name ";
		}
	    }
	  }
	  // Init de la suite des balises a memoriser si regle difficile
	  if ($phraseur_xml->dtc->regles[$name][0]=='/')
	    $phraseur_xml->fratrie[$depth]='';
	  if (isset($phraseur_xml->dtc->attributs[$name])) {
		  foreach ($phraseur_xml->dtc->attributs[$name] as $n => $v)
		    { if (($v[1] == '#REQUIRED') AND (!isset($attrs[$n])))
			$phraseur_xml->err[]= " <b>$n</b>"
			  . '&nbsp;:&nbsp;'
			  . _T('zxml_obligatoire_attribut')
			  . " <b>$name</b>"
			  .  coordonnees_erreur($phraseur);
		    }
	  }
	}
}


// http://doc.spip.org/@validerAttribut
function validerAttribut($phraseur, $name, $val, $bal)
{
	global $phraseur_xml;

	// Si la balise est inconnue, eviter d'insister
	if (!isset($phraseur_xml->dtc->attributs[$bal]))
		return ;
		
	$a = $phraseur_xml->dtc->attributs[$bal];
	if (!isset($a[$name])) {
		$bons = join(', ',array_keys($a));
		if ($bons)
		  $bons = " title=' " .
		    _T('zxml_connus_attributs') .
		    '&nbsp;: ' .
		    $bons .
		    "'";
		$bons .= " style='font-weight: bold'";

		$phraseur_xml->err[]= " <b>$name</b> "
		. _T('zxml_inconnu_attribut').' '._T('zxml_de')
		. " <a$bons>$bal</a> ("
		. _T('zxml_survoler')
		. ")"
		.  coordonnees_erreur($phraseur);
	} else{
		$type =  $a[$name][0];
		if (!preg_match('/^\w+$/', $type))
			valider_motif($phraseur, $name, $val, $bal, $type);
		else if (function_exists($f = 'validerAttribut_' . $type))
			$f($phraseur, $name, $val, $bal);
	}
}

// http://doc.spip.org/@validerAttribut_ID
function validerAttribut_ID($phraseur, $name, $val, $bal)
{
	global $phraseur_xml;

	if (isset($phraseur_xml->ids[$val])) {
		list($l,$c) = $phraseur_xml->ids[$val];
		$phraseur_xml->err[]= " <p><b>$val</b> "
		      . _T('zxml_valeur_attribut')
		      . " <b>$name</b> "
		      . _T('zxml_de')
		      . " <b>$bal</b> "
		      . _T('zxml_vu')
		      . " (L$l,C$c)"
		      .  coordonnees_erreur($phraseur);
	} else {
		valider_motif($phraseur, $name, $val, $bal, _REGEXP_ID);
		$phraseur_xml->ids[$val] = array(xml_get_current_line_number($phraseur), xml_get_current_column_number($phraseur));
	}
}

// http://doc.spip.org/@validerAttribut_IDREF
function validerAttribut_IDREF($phraseur, $name, $val, $bal)
{
	global $phraseur_xml;
	$phraseur_xml->idrefs[] = array($val, xml_get_current_line_number($phraseur), xml_get_current_column_number($phraseur));
}

// http://doc.spip.org/@validerAttribut_IDREFS
function validerAttribut_IDREFS($phraseur, $name, $val, $bal)
{
	global $phraseur_xml;

	$phraseur_xml->idrefss[] = array($val, xml_get_current_line_number($phraseur), xml_get_current_column_number($phraseur));
}

// http://doc.spip.org/@valider_motif
function valider_motif($phraseur, $name, $val, $bal, $motif)
{
	global $phraseur_xml;

	if (!preg_match($motif, $val)) {
		$phraseur_xml->err[]= " <p><b>$val</b> "
		. _T('zxml_valeur_attribut')
		. " <b>$name</b> "
		. _T('zxml_de')
		. " <b>$bal</b> "
		. _T('zxml_non_conforme')
		. "</p><p>"
		. "<b>" . $motif . "</b></p>"
		.  coordonnees_erreur($phraseur);
	}
}

// http://doc.spip.org/@valider_idref
function valider_idref(&$own, $nom, $ligne, $col)
{
	if (!isset($own->ids[$nom]))
		$own->err[]= " <p><b>$nom</b> "
		. _T('zxml_inconnu_id')
		. " "
		. $ligne
		. " "
		. $col;
}

// http://doc.spip.org/@inc_valider_passe2_dist
function inc_valider_passe2_dist(&$own)
{
	if (!$own->err) {
		foreach ($own->idrefs as $idref) {
			list($nom, $ligne, $col) = $idref;
			valider_idref($own, $nom, $ligne, $col);
		}
		foreach ($own->idrefss as $idref) {
			list($noms, $ligne, $col) = $idref;
			foreach(preg_split('/\s+/', $noms) as $nom)
				valider_idref($own, $nom, $ligne, $col);
		}
	}
}

class ValidateurXML {

// http://doc.spip.org/@debutElement
function debutElement($phraseur, $name, $attrs)
{ 
	global $phraseur_xml;

	if ($phraseur_xml->dtc->elements)
		validerElement($phraseur, $name, $attrs);

	xml_debutElement($phraseur, $name, $attrs);
	$depth = &$phraseur_xml->depth;
	$phraseur_xml->debuts[$depth] =  strlen($phraseur_xml->res);
	foreach ($attrs as $k => $v) {
		validerAttribut($phraseur, $k, $v, $name);
	}
}

// http://doc.spip.org/@finElement
function finElement($phraseur, $name)
{
	global $phraseur_xml;

	$depth = &$phraseur_xml->depth;
	$contenu = &$phraseur_xml->contenu;

	$n = strlen($phraseur_xml->res);
	$c = strlen(trim($contenu[$depth]));
	$k = $phraseur_xml->debuts[$depth];

	$regle = $phraseur_xml->dtc->regles[$name];
	$vide = ($regle  == 'EMPTY');
	// controler que les balises devant etre vides le sont 
	if ($vide) {
	  if ($n <> ($k + $c))
			$phraseur_xml->err[]= " <p><b>$name</b> "
			. _T('zxml_nonvide_balise')
			.  coordonnees_erreur($phraseur);
	// pour les regles PCDATA ou iteration de disjonction, tout est fait
	} elseif ($regle AND ($regle != '*')) {
		if ($regle == '+') {
		    // iteration de disjonction non vide: 1 balise au -
			if ($n == $k) {
				$phraseur_xml->err[]= " <p>\n<b>$name</b> "
				  . _T('zxml_vide_balise')
				  .  coordonnees_erreur($phraseur);
			}
		} else {
			$f = $phraseur_xml->fratrie[substr($depth,2)];
			if (!preg_match($regle, $f))
				$phraseur_xml->err[]= " <p>\n<b>$name</b> "
				  .  _T('zxml_succession_fils_incorrecte')
				  . '&nbsp;: <b>'
				  . $f
				  . '</b>'
				  .  coordonnees_erreur($phraseur);
		}

	}
	xml_finElement($phraseur, $name, $vide);
}

// http://doc.spip.org/@textElement
function textElement($phraseur, $data)
{	xml_textElement($phraseur, $data);}

// http://doc.spip.org/@PiElement
function PiElement($phraseur, $target, $data)
{	xml_PiElement($phraseur, $target, $data);}

// Denonciation des entitees XML inconnues
// Pour contourner le bug de conception de SAX qui ne signale pas si elles
// sont dans un attribut, les  entites les plus frequentes ont ete
// transcodees au prealable  (sauf & < > " que SAX traite correctement).
// On ne les verra donc pas passer a cette etape, contrairement a ce que 
// le source de la page laisse legitimement supposer. 

// http://doc.spip.org/@defautElement
function defautElement($phraseur, $data)
{	
	global $phraseur_xml;

	if (!preg_match('/^<!--/', $data)
	AND (preg_match_all('/&([^;]*)?/', $data, $r, PREG_SET_ORDER)))
		foreach ($r as $m) {
			list($t,$e) = $m;
			if (!isset($phraseur_xml->dtc->entites[$e]))
				$phraseur_xml->err[]= " <b>$e</b> "
				  . _T('zxml_inconnu_entite')
				  . ' '
				  .  coordonnees_erreur($phraseur);
		}

	xml_defautElement($phraseur, $data);
}

// http://doc.spip.org/@phraserTout
function phraserTout($phraseur, $data)
{ 
	xml_parsestring($phraseur, $data);

	if (!$this->dtc OR preg_match(',^' . _MESSAGE_DOCTYPE . ',', $data)) {
		$GLOBALS['xhtml_error'] .= 'DOCTYPE ? 0 0<br />';
		$this->err[]= ('DOCTYPE ? 0 0<br />');
	} else {
		$valider_passe2 = charger_fonction('valider_passe2', 'inc');
		$valider_passe2($this);
	}
	return !$this->err ?  $this->res : join('<br />', $this->err) . '<br />';
}

 var $depth = "";
 var $res = "";
 var $contenu = array();
 var $ouvrant = array();
 var $reperes = array();

 var $dtc = NULL;
 var $err = array();
 var $ids = array();
 var $idrefs = array();
 var $idrefss = array();
 var $debuts = array();
 var $fratrie = array();
}

// http://doc.spip.org/@inc_valider_xml_dist
function inc_valider_xml_dist($page, $apply=false)
{
	spip_timer('valider');
	$sax = charger_fonction('sax', 'inc');
	$sax = $sax($page, $apply, $GLOBALS['phraseur_xml'] = new ValidateurXML());
	spip_log("validation : " . spip_timer('valider'));
	return $sax;
}
?>
