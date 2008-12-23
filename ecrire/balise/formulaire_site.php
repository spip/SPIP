<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

// Le contexte indique dans quelle rubrique le visiteur peut proposer le site


// http://doc.spip.org/@balise_FORMULAIRE_SITE
function balise_FORMULAIRE_SITE ($p) {
  return calculer_balise_dynamique($p,'FORMULAIRE_SITE', array('id_rubrique'));
}

// http://doc.spip.org/@balise_FORMULAIRE_SITE_stat
function balise_FORMULAIRE_SITE_stat($args, $filtres) {

	// Pas d'id_rubrique ? Erreur de squelette
	if (!$args[0])
		return erreur_squelette(
			_T('zbug_champ_hors_motif',
				array ('champ' => '#FORMULAIRE_SITE',
					'motif' => 'RUBRIQUES')), '');

	// Verifier que les visisteurs sont autorises a proposer un site

	return (($GLOBALS['meta']["proposer_sites"] != 2) ? '' : $args);
}

?>
