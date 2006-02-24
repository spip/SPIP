<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite


// On prend l'email dans le contexte de maniere a ne pas avoir a le
// verifier dans la base ni a le devoiler au visiteur
global $balise_FORMULAIRE_ECRIRE_AUTEUR_collecte;
$balise_FORMULAIRE_ECRIRE_AUTEUR_collecte = array('id_auteur', 'id_article', 'email');

function balise_FORMULAIRE_ECRIRE_AUTEUR_stat($args, $filtres) {
	include_ecrire('inc_filtres');

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
		$s = spip_query("SELECT auteurs.email AS email
		FROM spip_auteurs as auteurs, spip_auteurs_articles as lien
		WHERE lien.id_article=".intval($args[1])
		. " AND auteurs.id_auteur = lien.id_auteur");
		while ($row = spip_fetch_array($s))
			if ($row['email'] AND email_valide($row['email']))
				$args[2].= ','.$row['email'];
		$args[2] = substr($args[2], 1);
	}

	// On ne peut pas ecrire a un auteur dont le mail n'est pas valide
	if (!$args[2] OR !email_valide($args[2]))
		return '';

	// OK
	return $args;
}

function balise_FORMULAIRE_ECRIRE_AUTEUR_dyn($id_auteur, $id_article, $mail) {
	include_ecrire('inc_texte');
	$puce = $GLOBALS['puce'.$GLOBALS['spip_lang_rtl']];

	// id du formulaire (pour en avoir plusieurs sur une meme page)
	$id = ($id_auteur ? '_'.$id_auteur : '_ar'.$id_article);
	#spip_log("id formulaire = $id, "._request("valide".$id));
	$sujet = _request('sujet_message_auteur'.$id);
	$texte = _request('texte_message_auteur'.$id);
	$adres = _request('email_message_auteur'.$id);

	$mailko = $texte && !email_valide($adres);

	$validable = $texte && $sujet && (!$mailko);

	// doit-on envoyer le mail ?
	if ($validable
	AND $id == _request('num_formulaire_ecrire_auteur')
	AND _request('confirmer'.$id)) { 
		$texte .= "\n\n-- "._T('envoi_via_le_site')." ".supprimer_tags(extraire_multi($GLOBALS['meta']['nom_site']))." (".$GLOBALS['meta']['adresse_site']."/) --\n";
		include_ecrire("inc_mail");
		envoyer_mail($mail, $sujet, $texte, $adres,
				"X-Originating-IP: ".$GLOBALS['ip']);
		return _T('form_prop_message_envoye');
	}

	return 
		array('formulaire_ecrire_auteur', 0,
			array(
			'id' => $id,
			'mailko' => $mailko ? $puce : '',
			'mail' => $adres,
			'sujetko' => ($texte && !$sujet) ? $puce : '',
			'sujet' => $sujet,
			'texte' => $texte,
			'valide' => ($validable ? $id : ''),
			'bouton' => (_T('form_prop_envoyer')),
			'boutonconfirmation' => ($validable ?
				_T('form_prop_confirmer_envoi') :
				'')
			)
		);
}
?>
