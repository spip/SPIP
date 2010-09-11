<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/actions');

// http://doc.spip.org/@inc_iconifier_dist
function inc_iconifier_dist($objet, $id,  $script, $visible=false, $flag_modif=true) {
	global $logo_libelles;
	// compat avec anciens appels
	if (substr($objet,0,3)=='id_')
		$objet = substr($objet,3);

	return recuperer_fond('prive/editer/logo',array('objet'=>$objet,'id_objet'=>$id,'editable'=>$flag_modif));

}

?>
