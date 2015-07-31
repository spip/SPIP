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

class IndenteurXML {

function debutElement($phraseur, $name, $attrs)
{ xml_debutElement($phraseur, $name, $attrs);}

function finElement($phraseur, $name)
{ xml_finElement($phraseur, $name);}

function textElement($phraseur, $data)
{ xml_textElement($phraseur, $data);}

function PiElement($phraseur, $target, $data)
{ xml_PiElement($phraseur, $target, $data);}

function defautElement($phraseur, $data)
{  xml_defautElement($phraseur, $data);}

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
 var $entites = array();
}

function inc_indenter_xml_dist($page, $apply=false)
{
	$sax = charger_fonction('sax', 'inc');
	return $sax($page, $apply, $GLOBALS['phraseur_xml'] = new IndenteurXML());

}

?>
