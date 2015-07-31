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



/**
 * affichage des liste d'objets
 * surcharge pour aiguiller vers la mise en skel
 *
 * @param string $vue
 * @param string $titre
 * @param array $requete
 * @param string $formater
 * @param bool $force
 * @return string
 */
function inc_lister_objets_dist($vue, $contexte=array(), $force=false){
	$res = ""; // debug
	if ($vue=='sites')
		$vue='syndic';

	if (!is_array($contexte))
		return _L('$contexte doit etre un tableau dans inc/lister_objets');

	$fond = "prive/listes/$vue";
	if (!find_in_path($fond."."._EXTENSION_SQUELETTES))
		return _L("vue $vue introuvable pour lister les objets");
	
	$contexte['sinon']=($force ? $contexte['titre']:'');

	$res = recuperer_fond($fond,$contexte,array('ajax'=>true));
	if (_request('var_liste'))
		var_dump($contexte);
		
	return $res;
}

?>