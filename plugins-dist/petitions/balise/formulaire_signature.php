<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

//
// Formulaire de signature d'une petition
//

// Contexte necessaire lors de la compilation

// Il *faut* demander petition, meme si on ne s'en sert pas dans l'affichage,
// car on doit obtenir la jointure avec la table des petitions pour verifier 
// si une petition est attachee a l'article.

// http://doc.spip.org/@balise_FORMULAIRE_SIGNATURE
function balise_FORMULAIRE_SIGNATURE ($p) {
	return calculer_balise_dynamique($p,'FORMULAIRE_SIGNATURE', array('id_article','petition'));
}

// Verification des arguments (contexte + filtres)
// http://doc.spip.org/@balise_FORMULAIRE_SIGNATURE_stat
function balise_FORMULAIRE_SIGNATURE_stat($args, $context_compil) {

	// pas d'id_article => erreur de contexte
	if (!$args[0]) {
		$msg = array('zbug_champ_hors_motif',
				array ('champ' => 'FORMULAIRE_SIGNATURE',
				       'motif' => 'ARTICLES'));
		erreur_squelette($msg, $context_compil);
		return '';
	}
	// article sans petition => pas de balise
	else if (!$args[1])
		return '';

	// on envoie pas cet argument dans le CVT
	unset($args[1]);
	return $args;
}
?>
