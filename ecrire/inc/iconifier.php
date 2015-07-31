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

include_spip('inc/actions');

// http://doc.spip.org/@inc_iconifier_dist
function inc_iconifier_dist($objet, $id,  $script, $visible=false, $flag_modif=true) {
	// compat avec anciens appels
	$objet = objet_type($objet);
	
	return recuperer_fond('prive/objets/editer/logo',array('objet'=>$objet,'id_objet'=>$id,'editable'=>$flag_modif));
}

?>
