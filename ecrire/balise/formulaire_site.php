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

// Le contexte indique dans quelle rubrique le visiteur peut proposer le site


// http://doc.spip.org/@balise_FORMULAIRE_SITE
function balise_FORMULAIRE_SITE ($p) {
  return calculer_balise_dynamique($p,'FORMULAIRE_SITE', array('id_rubrique'));
}

// http://doc.spip.org/@balise_FORMULAIRE_SITE_stat
function balise_FORMULAIRE_SITE_stat($args, $context_compil) {

	// Pas d'id_rubrique ? Erreur de contexte
	if (!$args[0]) {
		$msg = array('zbug_champ_hors_motif',
				array ('champ' => 'FORMULAIRE_SITE',
					'motif' => 'RUBRIQUES'));
		erreur_squelette($msg, $context_compil);
		return '';
	}
	// Verifier que les visisteurs sont autorises a proposer un site

	return (($GLOBALS['meta']["proposer_sites"] != 2) ? '' : $args);
}

?>
