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
include_spip('base/abstract_sql');
include_spip('inc/plugin');

function maj_vieille_base_charger_dist($version_cible){
	$vieilles_bases = array(
	'1.813'=>'1813',
	'1.821'=>'1821',
	'1.915'=>'1915',
	'1.917'=>'1917',
	'1.927'=>'1927',
	'10000'=>'10000',
	'12000'=>'13000',
	);
	$version = false;
	foreach($vieilles_bases as $v=>$n){
		if (!$version OR spip_version_compare($v,$version_cible,'<'))
			$version = $n;
	}

	/*
	include_spip('base/serial');
	include_spip('base/auxiliaires');
	$GLOBALS['nouvelle_base']['tables_principales'] = $GLOBALS['tables_principales'];
	$GLOBALS['nouvelle_base']['tables_auxiliaires'] = $GLOBALS['tables_auxiliaires'];*/

	unset($GLOBALS['tables_principales']);
	unset($GLOBALS['tables_auxiliaires']);
	unset($GLOBALS['tables_images']);
	unset($GLOBALS['tables_sequences']);
	unset($GLOBALS['tables_documents']);
	unset($GLOBALS['tables_mime']);

	// chargera les descriptions de table
	$create = charger_fonction('create',"maj/vieille_base/$version");
	if (!isset($GLOBALS['tables_auxiliaires']['spip_meta']['field']['impt']))
			$GLOBALS['tables_auxiliaires']['spip_meta']['field']['impt'] = "ENUM('non', 'oui') DEFAULT 'oui' NOT NULL";

	return $version;
}

?>
