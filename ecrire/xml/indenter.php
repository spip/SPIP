<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

class IndenteurXML {

	// https://code.spip.net/@debutElement
	public function debutElement($phraseur, $name, $attrs) {
		xml_debutElement($this, $name, $attrs);
	}

	// https://code.spip.net/@finElement
	public function finElement($phraseur, $name) {
		xml_finElement($this, $name);
	}

	// https://code.spip.net/@textElement
	public function textElement($phraseur, $data) {
		xml_textElement($this, $data);
	}

	public function piElement($phraseur, $target, $data) {
		xml_PiElement($this, $target, $data);
	}

	// https://code.spip.net/@defautElement
	public function defaultElement($phraseur, $data) {
		xml_defaultElement($this, $data);
	}

	// https://code.spip.net/@phraserTout
	public function phraserTout($phraseur, $data) {
		xml_parsestring($this, $data);
	}

	public $depth = '';
	public $res = '';
	public $err = array();
	public $contenu = array();
	public $ouvrant = array();
	public $reperes = array();
	public $entete = '';
	public $page = '';
	public $dtc = null;
	public $sax = null;
}

// https://code.spip.net/@xml_indenter_dist
function xml_indenter_dist($page, $apply = false) {
	$sax = charger_fonction('sax', 'xml');
	$f = new IndenteurXML();
	$sax($page, $apply, $f);
	if (!$f->err) {
		return $f->entete . $f->res;
	}
	spip_log('indentation impossible ' . count($f->err) . ' erreurs de validation');

	return $f->entete . $f->page;
}
