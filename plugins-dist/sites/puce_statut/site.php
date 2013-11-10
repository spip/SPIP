<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-200                                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;


// http://doc.spip.org/@puce_statut_site_dist
function puce_statut_site_dist($id, $statut, $id_rubrique, $type, $ajax='', $menu_rapide=_ACTIVER_PUCE_RAPIDE){

	$t = Sql::getfetsel("syndication", "spip_syndic", "id_syndic=".intval($id));

	// cas particulier des sites en panne de syndic :
	// on envoi une puce speciale, et pas de menu de changement rapide
	if ($t == 'off' OR $t == 'sus') {
		switch ($statut) {
			case 'publie':
				$puce = 'puce-verte-anim.gif';
				$title = _T('sites:info_site_reference');
				break;
			case 'prop':
				$puce = 'puce-orange-anim.gif';
				$title = _T('sites:info_site_attente');
				break;
			case 'refuse':
			default:
				$puce = 'puce-poubelle-anim.gif';
				$title = _T('sites:info_site_refuse');
				break;
		}
		return http_img_pack($puce, $title);
	}
	else
		return puce_statut_changement_rapide($id,$statut,$id_rubrique,$type,$ajax,$menu_rapide);
}


?>