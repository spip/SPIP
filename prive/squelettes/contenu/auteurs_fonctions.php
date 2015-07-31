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
// Constante pour le nombre d'auteurs par page.
@define('AUTEURS_MIN_REDAC', "0minirezo,1comite,5poubelle");
@define('AUTEURS_DEFAUT', '');
include_spip('inc/presentation');

function auteurs_lister_statuts() {
	$statut = AUTEURS_DEFAUT . AUTEURS_MIN_REDAC;

	if (substr($statut,0,1)!=='!')
		return explode(',',$statut);

	$statut = substr($statut,1);
	$statut = explode(',',$statut);
	$statut = sql_allfetsel('DISTINCT statut','spip_auteurs',sql_in('statut',$statut,'NOT'));
	return array_map('reset',$statut);
	
}

function auteurs_navigation_statut($action,$selected,$statuts){
	// une barre de navigation entre statuts
	if (count($statuts)>1) {
		$nav = array();
		$nav[] = lien_ou_expose(parametre_url($action,'statut',''), _T('info_tout_afficher'), !in_array($selected,$statuts));
		foreach ($statuts as $statut) {
			$texte = array_search($statut, $GLOBALS['liste_des_statuts']);
			$texte = ($texte?_T($texte):$statut);

			$nav[] = lien_ou_expose(parametre_url($action, 'statut',$statut), $texte, $selected==$statut);
		}
		return $nav;
	}
	return '';
}

?>