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
function balise_FORMULAIRE_SITE_stat($args, $context_compil) {

	// Pas d'id_rubrique ? Erreur de squelette
	if (!$args[0]) {
		include_spip('public/interfaces');
		$p = new Contexte;
		$p->descr = array('sourcefile' => $context_compil[0],
				  'nom' => $context_compil[1]);
		$p->id_boucle = $context_compil[2];
		$p->ligne = $context_compil[3];
		$p->lang = $context_compil[4];
		$msg = array('zbug_champ_hors_motif',
				array ('champ' => 'FORMULAIRE_SITE',
					'motif' => 'RUBRIQUES'));
		return erreur_squelette($msg, $p);
	}
	// Verifier que les visisteurs sont autorises a proposer un site

	return (($GLOBALS['meta']["proposer_sites"] != 2) ? '' : $args);
}

?>
