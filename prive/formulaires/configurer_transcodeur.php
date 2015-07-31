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

function formulaires_configurer_transcodeur_charger_dist(){
	$valeurs = array(
		'charset' => $GLOBALS['meta']["charset"],
	);

	return $valeurs;
}

function formulaires_configurer_transcodeur_verifier_dist(){
	include_spip('inc/charsets');
	
	$erreurs = array();
	if (!$charset=_request('charset'))
		$erreurs['charset'] = _T('info_obligatoire');
	elseif ($charset!='utf-8' AND !load_charset($charset))
		$erreurs['charset'] = _T('utf8_convert_erreur_orig',array('charset'=>$charset));

	return $erreurs;
}


function formulaires_configurer_transcodeur_traiter_dist(){
	$res = array('editable'=>true);
	ecrire_meta('charset', _request('charset'));
	$res['message_ok'] = _T('config_info_enregistree');
	return $res;
}
?>
