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

/**
 * definir la liste des onglets dans une page de l'interface privee
 * on passe la main au pipeline "ajouter_onglets".
 */
// http://doc.spip.org/@definir_barre_onglets
function definir_barre_onglets($script) {

	$onglets=array();
	$liste_onglets = array();

	// ajouter les onglets issus des plugin via plugin.xml
	if (function_exists('onglets_plugins'))
		$liste_onglets = onglets_plugins();


	foreach($liste_onglets as $id => $infos){
		if (($parent = $infos['parent'])
			&& $parent == $script
			&& autoriser('onglet',"_$id")) {
				$onglets[$id] = new Bouton(
					find_in_theme($infos['icone']),  // icone
					$infos['titre'],	// titre
					(isset($infos['action']) and $infos['action'])
						? generer_url_ecrire($infos['action'],(isset($infos['parametres']) AND $infos['parametres'])?$infos['parametres']:'')
						: null
					);
		}
	}

	return pipeline('ajouter_onglets', array('data'=>$onglets,'args'=>$script));
}


// http://doc.spip.org/@barre_onglets
function barre_onglets($rubrique, $ongletCourant, $class="barre_onglet"){
	include_spip('inc/presentation');

	$res = '';

	foreach(definir_barre_onglets($rubrique) as $exec => $onglet) {
		$url= $onglet->url ? $onglet->url : generer_url_ecrire($exec);
		$res .= onglet(_T($onglet->libelle), $url, $exec, $ongletCourant, $onglet->icone);
	}

	return  !$res ? '' : (debut_onglet($class) . $res . fin_onglet());
}


?>
