<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/filtres');
include_spip('inc/charsets');
include_spip('xml/interfaces');

// http://doc.spip.org/@xml_debutElement
function xml_debutElement($phraseur, $name, $attrs)
{
	$depth = $phraseur->depth;

	$t = isset($phraseur->ouvrant[$depth]) ? $phraseur->ouvrant[$depth] : ' ';
	// espace initial signifie: deja integree au resultat
	if ($t[0] != ' ')
	  {
	    $phraseur->res .= '<' . $t . '>';
	    $phraseur->ouvrant[$depth] = ' ' . $t;
	  }
	$t = $phraseur->contenu[$depth];
	// n'indenter que s'il y a un separateur avant
	$phraseur->res .= preg_replace("/[\n\t ]+$/",  "\n$depth", $t);
	$phraseur->contenu[$depth] = "";
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
	$phraseur->depth .= '  ';
	$phraseur->contenu[$phraseur->depth] = "";
	$phraseur->ouvrant[$phraseur->depth] = $name . $att;
	$phraseur->reperes[$phraseur->depth] = xml_get_current_line_number($phraseur->sax);
}

// http://doc.spip.org/@xml_finElement
function xml_finElement($phraseur, $name, $fusion_bal=false)
{
	$ouv = $phraseur->ouvrant[$phraseur->depth];

	if ($ouv[0] != ' ')
		$phraseur->ouvrant[$phraseur->depth] = ' ' . $ouv;
	else $ouv= "";
	$t = $phraseur->contenu[$phraseur->depth];
	$phraseur->depth = substr($phraseur->depth, 2);
	$t = preg_replace("/[\n\t ]+$/", "\n" . $phraseur->depth, $t);

  // fusion <balise></balise> en <balise />.
  // ATTENTION,  certains clients http croient que fusion ==> pas d'atttributs
  // en particulier pour les balises Script et A.
  // en presence d'attributs ne le faire que si la DTD est dispo et d'accord
  // (param fusion_bal)

	if ($t || (($ouv != $name) AND !$fusion_bal))
	  $phraseur->res .= ($ouv ? ('<' . $ouv . '>') : '') . $t . "</" . $name . ">";
	else
	  $phraseur->res .= ($ouv ? ('<' . $ouv  . ' />') : ("</" .  $name . ">"));
}

// http://doc.spip.org/@xml_textElement
function xml_textElement($phraseur, $data)
{
	$depth = $phraseur->depth;
	$phraseur->contenu[$depth] .= preg_match('/^script/',$phraseur->ouvrant[$depth])
	  ? $data
	  : entites_html($data);
}

function xml_piElement($phraseur, $target, $data)
{
	$depth = $phraseur->depth;

	if (strtolower($target) != "php")
	  $phraseur->contenu[$depth] .= $data;
	else {
		ob_start();
		eval($data);
		$data = ob_get_contents();
		ob_end_clean();
		$phraseur->contenu[$depth] .= $data;
	}
}


// http://doc.spip.org/@xml_defautElement
function xml_defaultElement($phraseur, $data)
{
	$depth = $phraseur->depth;

	if (!isset($phraseur->contenu[$depth])) $phraseur->contenu[$depth]='';
	$phraseur->contenu[$depth] .= $data;
}

// http://doc.spip.org/@xml_parsestring
function xml_parsestring($phraseur, $data)
{
	$phraseur->contenu[$phraseur->depth] ='';

	if (!xml_parse($phraseur->sax, $data, true)) {
		coordonnees_erreur($phraseur,
			xml_error_string(xml_get_error_code($phraseur->sax))
			. "<br />\n" .
			(!$phraseur->depth ? '' :
			 ('(' .
			  _T('erreur_balise_non_fermee') .
			  " <tt>" .
			  $prhaseur->ouvrant[$phraseur->depth] .
			  "</tt> " .
			  _T('ligne') .
			  " " .
			  $phraseur->reperes[$phraseur->depth] .
			  ") <br />\n" )));
	}
}

// http://doc.spip.org/@coordonnees_erreur
function coordonnees_erreur($phraseur, $msg)
{
	$entete_length = substr_count($phraseur->entete,"\n");
	$phraseur->err[] = array($msg,
		xml_get_current_line_number($phraseur->sax) + $entete_length,
		xml_get_current_column_number($phraseur->sax));
}

// http://doc.spip.org/@xml_sax_dist
function xml_sax_dist($page, $apply=false, $phraseur=NULL)
{
	// init par defaut si pas fait (compatibilite Tidy espace public)
	if (!$phraseur) {
		$indenter_xml = charger_fonction('indenter', 'xml');
		return $indenter_xml($page, $apply);
	}

	$xml_parser = xml_parser_create($GLOBALS['meta']['charset']);

	xml_set_element_handler($xml_parser,
			array($phraseur, "debutElement"),
			array($phraseur, "finElement"));

	xml_set_character_data_handler($xml_parser,
				       array($phraseur, "textElement"));

	xml_set_processing_instruction_handler($xml_parser,
				       array($phraseur, 'piElement'));

	xml_set_default_handler($xml_parser,
				array($phraseur, "defaultElement"));

	xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);

	if ($apply) {
		ob_start();
		if (is_array($apply))
		  $r = call_user_func_array($page, $apply);
		else $r = $page();
		$page = ob_get_contents();
		ob_end_clean();
		// fonction sans aucun "echo", ca doit etre le resultat
		if (!$page) $page = $r;
	}

	// charger la DTD et transcoder les entites,
	// et escamoter le doctype que sax mange en php5 mais pas en  php4
	list($entete,$page, $dtc) = sax_bug($page);

	$phraseur->sax = $xml_parser;
	$phraseur->entete = $entete;
	$phraseur->page = $page;
	$phraseur->dtc = $dtc;
	$phraseur->phraserTout($xml_parser, $page);
	xml_parser_free($xml_parser);
}

// SAX ne dit pas si une Entite est dans un attribut ou non.
// Les eliminer toutes sinon celles des attributs apparaissent en zone texte!
// Celles fondamentales pour la lecture (lt gt quot amp) sont conservees 
// (d'ailleurs SAX ne les considere pas comme des entites dans un attribut)
// Si la DTD est dispo, on va chercher les entites dedans
// sinon on se rabat sur ce qu'en connait SPIP en standard.

// http://doc.spip.org/@sax_bug
function sax_bug($data)
{
	$r = analyser_doctype($data);

	if (!$r) {
		$data = _MESSAGE_DOCTYPE . _DOCTYPE_ECRIRE
		. preg_replace(_REGEXP_DOCTYPE, '', $data);
		$r =  analyser_doctype($data);
	}

	list($doctype, $topelement, $avail, $grammaire, $rotlvl, $len) = $r;

	include_spip('xml/analyser_dtd');
	$dtc = charger_dtd($grammaire, $avail, $rotlvl);

	// l'entete contient eventuellement < ? xml... ? >, le Doctype, 
	// et des commentaires autour d'eux
	$entete = ltrim(substr($data,0,$len));

	if ($dtc) {
		$trans = array();
		
		foreach($dtc->entites as $k => $v) {
			if (!strpos(" amp lt gt quot ", $k))
			    $trans["&$k;"] = $v;
		}
		$data = strtr(substr($data,$len), $trans);
	} else {
		$data = html2unicode(substr($data,$len), true);
	}
	return array($entete,unicode2charset($data), $dtc);
}

// Reperer le Doctype et le decomposer selon:
// http://www.freebsd.org/doc/fr_FR.ISO8859-1/books/fdp-primer/sgml-primer-doctype-declaration.html
// Si pas de Doctype et premiere balise = RSS prendre la doctype RSS 0.91:
// les autres formats RSS n'ont pas de DTD,
// mais un XML Schema que SPIP ne fait pas encore lire.
// http://doc.spip.org/@analyser_doctype
function analyser_doctype($data)
{
	if (!preg_match(_REGEXP_DOCTYPE, $data, $page)) {
		if (preg_match(_REGEXP_XML, $data, $page)) {
			list(,$pico, $topelement) = $page;
			$pico = strlen($pico);
			if ($topelement == 'rss')
				return array('',
					     'rss',
					     'PUBLIC', 
					     _DOCTYPE_RSS,
					     'rss-0.91.dtd',
					     $pico);
			else {
				$dtd = $topelement . '.dtd';
				$f = find_in_path($dtd);
				if (file_exists($f))
					return array('', $topelement, 'SYSTEM', $f, $dtd, $pico);
			}
		}
		spip_log("Dtd pas vu pour " . substr($data, 0, 100));
		return array();
	}
	list($doctype,$pico, $topelement, $avail,$suite) = $page;

	if (!preg_match('/^"([^"]*)"\s*(.*)$/', $suite, $r))
		if (!preg_match("/^'([^']*)'\s*(.*)$/", $suite, $r))
			return  array();
	list(,$rotlvl, $suite) = $r;

	if (!$suite) {
		if ($avail != 'SYSTEM') return array();
		$grammaire = $rotlvl;
		$rotlvl = '';
	} else {
		if (!preg_match('/^"([^"]*)"\s*$/', $suite, $r))
			if (!preg_match("/^'([^']*)'\s*$/", $suite, $r))
				return array();

		$grammaire = $r[1];
	}

	return array(substr($doctype,strlen($pico)), $topelement, $avail, $grammaire, $rotlvl, strlen($doctype));
}
?>
