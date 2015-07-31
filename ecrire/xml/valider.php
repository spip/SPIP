<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// Validateur XML en deux passes, fonde sur SAX pour la premiere
// Faudrait faire deux classes car pour la premiere passe
// on a les memes methodes et variables que l'indenteur

class ValidateurXML {

// http://doc.spip.org/@validerElement
function validerElement($phraseur, $name, $attrs)
{
	if (!($p = isset($this->dtc->elements[$name]))) {
		if ($p = strpos($name, ':')) {
			$name = substr($name, $p+1);
			$p = isset($this->dtc->elements[$name]);
		}
		if (!$p) {
			coordonnees_erreur($this," <b>$name</b>&nbsp;: "
				. _T('zxml_inconnu_balise'));
			return; 
		}
	}
	// controler les filles illegitimes, ca suffit 
	$depth = $this->depth;
	$ouvrant = $this->ouvrant;
#	spip_log("trouve $name apres " . $ouvrant[$depth]);
	if (isset($ouvrant[$depth])) {
	    if (preg_match('/^\s*(\w+)/', $ouvrant[$depth], $r)) {
	      $pere = $r[1];
#	      spip_log("pere $pere");
	      if (isset($this->dtc->elements[$pere])) {
		$fils = $this->dtc->elements[$pere];
#		spip_log("rejeton $name fils " . @join(',',$fils));
		if (!($p = @in_array($name, $fils))) {
			if ($p = strpos($name, ':')) {
				$p = substr($name, $p+1);
				$p = @in_array($p, $fils);
			}
		}
		if (!$p) {
	          $bons_peres = @join ('</b>, <b>', $this->dtc->peres[$name]);
		  coordonnees_erreur($this, " <b>$name</b> "
	            . _T('zxml_non_fils')
	            . ' <b>'
	            .  $pere
	            . '</b>'
	            . (!$bons_peres ? ''
	               : ('<p style="font-size: 80%"> '._T('zxml_mais_de').' <b>'. $bons_peres . '</b></p>')));
		} else if ($this->dtc->regles[$pere][0]=='/') {
			$frat = substr($depth,2);
			if (!isset($this->fratrie[$frat])) {
				$this->fratrie[$frat] = '';
			}
			$this->fratrie[$frat] .= "$name ";
		}
	      }
	    }
	}
	// Init de la suite des balises a memoriser si regle difficile
	if ($this->dtc->regles[$name][0]=='/')
	    $this->fratrie[$depth]='';
	if (isset($this->dtc->attributs[$name])) {
		  foreach ($this->dtc->attributs[$name] as $n => $v)
		    { if (($v[1] == '#REQUIRED') AND (!isset($attrs[$n])))
			coordonnees_erreur($this, " <b>$n</b>"
			  . '&nbsp;:&nbsp;'
			  . _T('zxml_obligatoire_attribut')
			  . " <b>$name</b>");
		    }
	}
}

// http://doc.spip.org/@validerAttribut
function validerAttribut($phraseur, $name, $val, $bal)
{
	// Si la balise est inconnue, eviter d'insister
	if (!isset($this->dtc->attributs[$bal]))
		return ;
		
	$a = $this->dtc->attributs[$bal];
	if (!isset($a[$name])) {
		$bons = join(', ',array_keys($a));
		if ($bons)
		  $bons = " title=' " .
		    _T('zxml_connus_attributs') .
		    '&nbsp;: ' .
		    $bons .
		    "'";
		$bons .= " style='font-weight: bold'";
		coordonnees_erreur($this, " <b>$name</b> "
		. _T('zxml_inconnu_attribut').' '._T('zxml_de')
		. " <a$bons>$bal</a> ("
		. _T('zxml_survoler')
		. ")");
	} else{
		$type =  $a[$name][0];
		if (!preg_match('/^\w+$/', $type))
			$this->valider_motif($phraseur, $name, $val, $bal, $type);
		else if (method_exists($this, $f = 'validerAttribut_' . $type))
			$this->$f($phraseur, $name, $val, $bal);
#		else spip_log("$type type d'attribut inconnu");
	}
}

function validerAttribut_NMTOKEN($phraseur, $name, $val, $bal)
{
	$this->valider_motif($phraseur, $name, $val, $bal, _REGEXP_NMTOKEN);
}

function validerAttribut_NMTOKENS($phraseur, $name, $val, $bal)
{
	$this->valider_motif($phraseur, $name, $val, $bal, _REGEXP_NMTOKENS);
}

// http://doc.spip.org/@validerAttribut_ID
function validerAttribut_ID($phraseur, $name, $val, $bal)
{
	if (isset($this->ids[$val])) {
		list($l,$c) = $this->ids[$val];
		coordonnees_erreur($this, " <p><b>$val</b> "
		      . _T('zxml_valeur_attribut')
		      . " <b>$name</b> "
		      . _T('zxml_de')
		      . " <b>$bal</b> "
		      . _T('zxml_vu')
		      . " (L$l,C$c)");
	} else {
		$this->valider_motif($phraseur, $name, $val, $bal, _REGEXP_ID);
		$this->ids[$val] = array(xml_get_current_line_number($phraseur), xml_get_current_column_number($phraseur));
	}
}

// http://doc.spip.org/@validerAttribut_IDREF
function validerAttribut_IDREF($phraseur, $name, $val, $bal)
{
	$this->idrefs[] = array($val, xml_get_current_line_number($phraseur), xml_get_current_column_number($phraseur));
}

// http://doc.spip.org/@validerAttribut_IDREFS
function validerAttribut_IDREFS($phraseur, $name, $val, $bal)
{
	$this->idrefss[] = array($val, xml_get_current_line_number($phraseur), xml_get_current_column_number($phraseur));
}

// http://doc.spip.org/@valider_motif
function valider_motif($phraseur, $name, $val, $bal, $motif)
{
	if (!preg_match($motif, $val)) {
		coordonnees_erreur($this, "<b>$val</b> "
		. _T('zxml_valeur_attribut')
		. " <b>$name</b> "
		. _T('zxml_de')
		. " <b>$bal</b> "
		. _T('zxml_non_conforme')
		. "</p><p>"
		. "<b>" . $motif . "</b>");
	}
}

// http://doc.spip.org/@valider_idref
function valider_idref($nom, $ligne, $col)
{
	if (!isset($this->ids[$nom]))
		$this->err[]= array(" <p><b>$nom</b> " . _T('zxml_inconnu_id'), $ligne, $col);
}

// http://doc.spip.org/@valider_passe2
function valider_passe2()
{
	if (!$this->err) {
		foreach ($this->idrefs as $idref) {
			list($nom, $ligne, $col) = $idref;
			$this->valider_idref($nom, $ligne, $col);
		}
		foreach ($this->idrefss as $idref) {
			list($noms, $ligne, $col) = $idref;
			foreach(preg_split('/\s+/', $noms) as $nom)
				$this->valider_idref($nom, $ligne, $col);
		}
	}
}

// http://doc.spip.org/@debutElement
function debutElement($phraseur, $name, $attrs)
{ 
	if ($this->dtc->elements)
		$this->validerElement($phraseur, $name, $attrs);

	if ($f = $this->process['debut']) $f($this, $name, $attrs);
	$depth = $this->depth;
	$this->debuts[$depth] =  strlen($this->res);
	foreach ($attrs as $k => $v) {
		$this->validerAttribut($phraseur, $k, $v, $name);
	}
}

// http://doc.spip.org/@finElement
function finElement($phraseur, $name)
{
	$depth = $this->depth;
	$contenu = $this->contenu;

	$n = strlen($this->res);
	$c = strlen(trim($contenu[$depth]));
	$k = $this->debuts[$depth];

	$regle = $this->dtc->regles[$name];
	$vide = ($regle  == 'EMPTY');
	// controler que les balises devant etre vides le sont 
	if ($vide) {
		if ($n <> ($k + $c))
			coordonnees_erreur($this, " <p><b>$name</b> "
					   . _T('zxml_nonvide_balise'));
	// pour les regles PCDATA ou iteration de disjonction, tout est fait
	} elseif ($regle AND ($regle != '*')) {
		if ($regle == '+') {
		    // iteration de disjonction non vide: 1 balise au -
			if ($n == $k) {
				coordonnees_erreur($this, "<p>\n<b>$name</b> "
				  . _T('zxml_vide_balise'));
			}
		} else {
			$f = $this->fratrie[substr($depth,2)];
			if (!preg_match($regle, $f)) {
				coordonnees_erreur($this,
				" <p>\n<b>$name</b> "
				  .  _T('zxml_succession_fils_incorrecte')
				  . '&nbsp;: <b>'
				  . $f
				  . '</b>');
			}
		}

	}
	if ($f = $this->process['fin']) $f($this, $name, $vide);
}

// http://doc.spip.org/@textElement
function textElement($phraseur, $data)
{	
	if (trim($data)) {
		$d = $this->depth;
		$d = $this->ouvrant[$d];
		preg_match('/^\s*(\S+)/', $d, $m);
		if ($this->dtc->pcdata[$m[1]]) {
			coordonnees_erreur($this, " <p><b>". $m[1] . "</b> "
			. _T('zxml_nonvide_balise') // message a affiner
			);
		}
	}
	if ($f = $this->process['text']) $f($this, $data);
}

function piElement($phraseur, $target, $data)
{
	if ($f = $this->process['pi']) $f($this, $target, $data);
}

// Denonciation des entitees XML inconnues
// Pour contourner le bug de conception de SAX qui ne signale pas si elles
// sont dans un attribut, les  entites les plus frequentes ont ete
// transcodees au prealable  (sauf & < > " que SAX traite correctement).
// On ne les verra donc pas passer a cette etape, contrairement a ce que 
// le source de la page laisse legitimement supposer. 

// http://doc.spip.org/@defautElement
function defaultElement($phraseur, $data)
{
	if (!preg_match('/^<!--/', $data)
	AND (preg_match_all('/&([^;]*)?/', $data, $r, PREG_SET_ORDER)))
		foreach ($r as $m) {
			list($t,$e) = $m;
			if (!isset($this->dtc->entites[$e]))
				coordonnees_erreur($this, " <b>$e</b> "
				  . _T('zxml_inconnu_entite')
				  . ' '
				  );
		}
	if (isset($this->process['default']) AND ($f = $this->process['default'])) {
		$f($this, $data);
	}
}

// http://doc.spip.org/@phraserTout
function phraserTout($phraseur, $data)
{ 
	xml_parsestring($this, $data);

	if (!$this->dtc OR preg_match(',^' . _MESSAGE_DOCTYPE . ',', $data)) {
		$this->err[]= array('DOCTYPE ?', 0, 0);
	} else {
		$this->valider_passe2($this);
	}
}

// Init
function ValidateurXML($process=array()) {
	if (is_array($process)) $this->process = $process;
}

 var $ids = array();
 var $idrefs = array();
 var $idrefss = array();
 var $debuts = array();
 var $fratrie = array();

 var $dtc = NULL;
 var $sax = NULL;
 var $depth = "";
 var $entete = '';
 var $page = '';
 var $res = "";
 var $err = array();
 var $contenu = array();
 var $ouvrant = array();
 var $reperes = array();
 var $process =  array(
			'debut' => 'xml_debutElement',
			'fin' => 'xml_finElement',
			'text' => 'xml_textElement',
			'pi' => 'xml_piElement',
			'default' => 'xml_defaultElement'
				 );
}

// Retourne une structure ValidateurXML, dont le champ "err" est un tableau
// ayant comme entrees des sous-tableaux [message, ligne, colonne]

// http://doc.spip.org/@xml_valider_dist
function xml_valider_dist($page, $apply=false, $process=false, $doctype='', $charset=null)
{
	$f = new ValidateurXML($process);
	$sax = charger_fonction('sax', 'xml');
	return $sax($page, $apply, $f, $doctype, $charset);
}
?>
