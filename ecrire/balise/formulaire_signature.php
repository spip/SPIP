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

//
// Formulaire de signature d'une petition
//

include_spip('base/abstract_sql');

// Contexte necessaire lors de la compilation

// Il *faut* demander petition, meme si on ne s'en sert pas dans l'affichage,
// car on doit obtenir la jointure avec la table des petitions pour verifier 
// si une petition est attachee a l'article.

// http://doc.spip.org/@balise_FORMULAIRE_SIGNATURE
function balise_FORMULAIRE_SIGNATURE ($p) {
	return calculer_balise_dynamique($p,'FORMULAIRE_SIGNATURE', array('id_article', 'petition'));
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

	else {
		// aller chercher dans la base la petition associee
		if ($r = sql_fetsel("texte, site_obli, message", 'spip_petitions', "id_article = ".intval($args[0]))) {
			$args[2] = $r['texte'];
			// le signataire doit-il donner un site ?
			$args[3] = ($r['site_obli'] == 'oui') ? ' ':'';
			// le signataire peut-il proposer un commentaire
			$args[4] = ($r['message'] == 'oui') ? ' ':'';
		}
		return $args;
	}
}
?>
