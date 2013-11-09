<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

class IndenteurXML
{

	// http://doc.spip.org/@debutElement
	function debutElement($phraseur, $name, $attrs)
	{
		xml_debutElement($this, $name, $attrs);
	}

	// http://doc.spip.org/@finElement
	function finElement($phraseur, $name)
	{
		xml_finElement($this, $name);
	}

	// http://doc.spip.org/@textElement
	function textElement($phraseur, $data)
	{
		xml_textElement($this, $data);
	}

	function piElement($phraseur, $target, $data)
	{
		xml_PiElement($this, $target, $data);
	}
	
	// http://doc.spip.org/@defautElement
	function defaultElement($phraseur, $data)
	{
		xml_defaultElement($this, $data);
	}

	// http://doc.spip.org/@phraserTout
	function phraserTout($phraseur, $data)
	{
		xml_parsestring($this, $data);
	}

	public $res = "";
	public $err = array();
	public $contenu = array();
	public $ouvrant = array();
	public $reperes = array();
	public $entete = '';
	public $page = '';
	public $dtc = NULL;
	public $sax = NULL;
}
