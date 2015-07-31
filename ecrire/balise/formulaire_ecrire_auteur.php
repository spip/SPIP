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

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('base/abstract_sql');

// On prend l'email dans le contexte de maniere a ne pas avoir a le
// verifier dans la base ni a le devoiler au visiteur


// http://doc.spip.org/@balise_FORMULAIRE_ECRIRE_AUTEUR
function balise_FORMULAIRE_ECRIRE_AUTEUR ($p) {
	return calculer_balise_dynamique($p,'FORMULAIRE_ECRIRE_AUTEUR', array('id_auteur', 'id_article', 'email'));
}

// http://doc.spip.org/@balise_FORMULAIRE_ECRIRE_AUTEUR_stat
function balise_FORMULAIRE_ECRIRE_AUTEUR_stat($args, $context_compil) {
	include_spip('inc/filtres');
	// Pas d'id_auteur ni d'id_article ? Erreur de contexte
	$id = intval($args[1]);
	if (!$args[0] AND !$id) {
		$msg = array('zbug_champ_hors_motif',
				array ('champ' => 'FORMULAIRE_ECRIRE_AUTEUR',
					'motif' => 'AUTEURS/ARTICLES'));

		erreur_squelette($msg, $context_compil);
		return '';
	}
	// Si on est dans un contexte article,
	// sortir tous les mails des auteurs de l'article
	if (!$args[0] AND $id) {
		$r = '';
		$s = sql_allfetsel('email',
				   'spip_auteurs AS A LEFT JOIN spip_auteurs_liens AS L ON (A.id_auteur=L.id_auteur AND L.objet=\'article\')',
				   "A.email != '' AND L.id_objet=$id");
		foreach($s as $row) {
			if (email_valide($row['email']))
				$r .= ', '.$row['email'];
		}
		$args[2] = substr($r, 2);
	}

	// On ne peut pas ecrire a un auteur dont le mail n'est pas valide
	if (!$args[2] OR !email_valide($args[2]))
		return '';

	// OK
	return $args;
}

?>
