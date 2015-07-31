<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2015                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

class IndenteurXML {

// http://code.spip.net/@debutElement
function debutElement($phraseur, $name, $attrs)
{ xml_debutElement($this, $name, $attrs);}

// http://code.spip.net/@finElement
function finElement($phraseur, $name)
{ xml_finElement($this, $name);}

// http://code.spip.net/@textElement
function textElement($phraseur, $data)
{ xml_textElement($this, $data);}

function piElement($phraseur, $target, $data)
{ xml_PiElement($this, $target, $data);}

// http://code.spip.net/@defautElement
function defaultElement($phraseur, $data)
{  xml_defaultElement($this, $data);}

// http://code.spip.net/@phraserTout
function phraserTout($phraseur, $data)
{
	xml_parsestring($this, $data);
}

 var $depth = "";
 var $res = "";
 var $err = array();
 var $contenu = array();
 var $ouvrant = array();
 var $reperes = array();
 var $entete = '';
 var $page = '';
 var $dtc = NULL;
 var $sax = NULL;
}

// http://code.spip.net/@xml_indenter_dist
function xml_indenter_dist($page, $apply=false)
{
	$sax = charger_fonction('sax', 'xml');
	$f = new IndenteurXML();
	$sax($page, $apply, $f);
	if (!$f->err) return $f->entete . $f->res;
	spip_log("indentation impossible " . count($f->err) . " erreurs de validation");
	return $f->entete . $f->page;
}

?>
