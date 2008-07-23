<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

class IndenteurXML {

// http://doc.spip.org/@debutElement
function debutElement($phraseur, $name, $attrs)
{ xml_debutElement($phraseur, $name, $attrs);}

// http://doc.spip.org/@finElement
function finElement($phraseur, $name)
{ xml_finElement($phraseur, $name);}

// http://doc.spip.org/@textElement
function textElement($phraseur, $data)
{ xml_textElement($phraseur, $data);}

// http://doc.spip.org/@PiElement
function PiElement($phraseur, $target, $data)
{ xml_PiElement($phraseur, $target, $data);}

// http://doc.spip.org/@defautElement
function defautElement($phraseur, $data)
{  xml_defautElement($phraseur, $data);}

// http://doc.spip.org/@phraserTout
function phraserTout($phraseur, $data)
{
	xml_parsestring($phraseur, $data);
	return !$this->err ?  $this->res : join('<br />', $this->err) . '<br />';
}

 var $depth = "";
 var $res = "";
 var $err = array();
 var $contenu = array();
 var $ouvrant = array();
 var $reperes = array();

 var $dtc = NULL;
}

// http://doc.spip.org/@xml_indenter_dist
function xml_indenter_dist($page, $apply=false)
{
	$sax = charger_fonction('sax', 'xml');
	return $sax($page, $apply, $GLOBALS['phraseur_xml'] = new IndenteurXML());

}

?>
