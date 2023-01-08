<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

class IndenteurXML {
	public function debutElement($phraseur, $name, $attrs) {
		xml_debutElement($this, $name, $attrs);
	}

	public function finElement($phraseur, $name) {
		xml_finElement($this, $name);
	}

	public function textElement($phraseur, $data) {
		xml_textElement($this, $data);
	}

	public function piElement($phraseur, $target, $data) {
		xml_PiElement($this, $target, $data);
	}

	public function defaultElement($phraseur, $data) {
		xml_defaultElement($this, $data);
	}

	public function phraserTout($phraseur, $data) {
		xml_parsestring($this, $data);
	}

	public $depth = '';
	public $res = '';
	public $err = [];
	public $contenu = [];
	public $ouvrant = [];
	public $reperes = [];
	public $entete = '';
	public $page = '';
	public $dtc = null;
	public $sax = null;
}

function xml_indenter_dist($page, $apply = false) {
	$sax = charger_fonction('sax', 'xml');
	$f = new IndenteurXML();
	$sax($page, $apply, $f);
	if (!$f->err) {
		return $f->entete . $f->res;
	}
	spip_log('indentation impossible ' . (is_countable($f->err) ? count($f->err) : 0) . ' erreurs de validation');

	return $f->entete . $f->page;
}
