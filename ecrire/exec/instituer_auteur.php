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

// http://doc.spip.org/@exec_instituer_auteur_dist
function exec_instituer_auteur_dist()
{
	$script = _request('script');
	$id_auteur = intval(_request('id_auteur'));
	if (!preg_match('/^\w+$/', $script))
	      {
		echo minipres();
		exit;
	      }

	$r = spip_fetch_array(spip_query("SELECT statut FROM spip_auteurs WHERE id_auteur=$id_auteur"));

	$instituer_auteur = charger_fonction('instituer_auteur', 'inc');
	ajax_retour($instituer_auteur($id_auteur, $r['statut'], $script));
}
?>
