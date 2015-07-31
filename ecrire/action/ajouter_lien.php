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

/**
 * Gestion de l'action ajouter_lien
 *
 * @package SPIP\Core\Liens
**/

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Action pour lier 2 objets entre eux
 *
 * L'argument attendu est `objet1-id1-objet2-id2` (type d'objet, identifiant)
 * tel que `mot-7-rubrique-3`.
 *
 * @uses objet_associer()
 * 
 * @param null|string $arg
 *     Clé des arguments. En absence utilise l'argument
 *     de l'action sécurisée.
 * @return void
 */
function action_ajouter_lien_dist($arg=null){
	if (is_null($arg)){
		$securiser_action = charger_fonction('securiser_action','inc');
		$arg = $securiser_action();
	}

	$arg = explode("-",$arg);
	list($objet_source,$ids,$objet_lie,$idl) = $arg;

	include_spip('action/editer_liens');
	objet_associer(array($objet_source=>$ids), array($objet_lie=>$idl));
}


?>
