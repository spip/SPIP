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

// http://doc.spip.org/@action_ical_dist
function action_ical_dist()
{
	$id_auteur = _request('id_auteur');
	$arg = _request('arg');

	// compatibilite des URLs spip_cal.php3?id=xxx&cle=yyy (SPIP 1.8)
	if (!$id_auteur AND _request('id')) {
		$id_auteur = _request('id');
		$arg = _request('cle');
	}
	// c'est un squelette depuis la 2.0
	redirige_par_entete(generer_url_public("ical_prive", "id_auteur=$id_auteur&arg=$arg", true));
}
?>
