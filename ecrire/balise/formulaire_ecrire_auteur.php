<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

include_spip('base/abstract_sql');

// On prend l'email dans le contexte de maniere a ne pas avoir a le
// verifier dans la base ni a le devoiler au visiteur


// http://doc.spip.org/@balise_FORMULAIRE_ECRIRE_AUTEUR
function balise_FORMULAIRE_ECRIRE_AUTEUR ($p) {
	return calculer_balise_dynamique($p,'FORMULAIRE_ECRIRE_AUTEUR', array('id_auteur', 'id_article', 'email'));
}

// http://doc.spip.org/@balise_FORMULAIRE_ECRIRE_AUTEUR_stat
function balise_FORMULAIRE_ECRIRE_AUTEUR_stat($args, $filtres) {
	include_spip('inc/filtres');

	// Pas d'id_auteur ni d'id_article ? Erreur de squelette
	if (!$args[0] AND !$args[1])
		return erreur_squelette(
			_T('zbug_champ_hors_motif',
				array ('champ' => '#FORMULAIRE_ECRIRE_AUTEUR',
					'motif' => 'AUTEURS/ARTICLES')), '');

	// Si on est dans un contexte article, sortir tous les mails des auteurs
	// de l'article
	if (!$args[0] AND $args[1]) {
		unset ($args[2]);
		$s = sql_select('email',
					  array('auteurs' => 'spip_auteurs',
						'L' => 'spip_auteurs_articles'),
					  array('auteurs.id_auteur=L.id_auteur',
						'L.id_article='.intval($args[1])));
		while ($row = sql_fetch($s)) {
			if ($row['email'] AND email_valide($row['email']))
				$args[2].= ','.$row['email'];
		}
		$args[2] = substr($args[2], 1);
	}

	// On ne peut pas ecrire a un auteur dont le mail n'est pas valide
	if (!$args[2] OR !email_valide($args[2]))
		return '';

	// OK
	return $args;
}

?>