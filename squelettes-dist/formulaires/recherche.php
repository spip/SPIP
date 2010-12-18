<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// chargement des valeurs par defaut des champs du formulaire
function formulaires_recherche_charger_dist($lien_filtre = NULL,$lien_arg = NULL){
	$lien = $lien_filtre ? $lien_filtre : $lien_arg;
	if ($GLOBALS['spip_lang'] != $GLOBALS['meta']['langue_site'])
		$lang = $GLOBALS['spip_lang'];
	else
		$lang='';

	return 
		array(
			'action' => ($lien ? $lien : generer_url_public('recherche')), # action specifique, ne passe pas par Verifier, ni Traiter
			'recherche' => _request('recherche'),
			'lang' => $lang
		);
}

?>
