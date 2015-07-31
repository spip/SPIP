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

if (!defined("_ECRIRE_INC_VERSION")) return;


// Fonctione appelee par divers pipelines
function notifications($pipeline, $x) {

	// En cas de changement de statut d'article vers 'publie' ou 'prop',
	// envoyer un mail
	if ($pipeline == 'pre_enregistre_contenu'
	AND $x['args']['table'] == 'spip_articles'
	AND (
		$x['data']['statut'] == 'publie'
		OR $x['data']['statut'] == 'prop')
	) {

		$statut = $x['data']['statut'];
		$id_article = intval($x['args']['id_objet']);
		$result = spip_query("SELECT statut FROM spip_articles WHERE id_article=$id_article");
		if ($row = spip_fetch_array($result))
			$statut_ancien = $row['statut'];
		else
			return $x;

		// ne devrait jamais se produire
		if ($statut == $statut_ancien)
			return $x;

		include_spip('inc/lang');
		include_spip('inc/texte');
		include_spip('inc/mail');

		if ($statut == 'publie')
			envoyer_mail_publication($id_article);

		if ($statut == 'prop' AND $statut_ancien != 'publie')
			envoyer_mail_proposition($id_article);
	}

	return $x;
}


// http://doc.spip.org/@extrait_article
function extrait_article($row) {
	include_spip('inc/texte');
	
	$id_article = $row['id_article'];
	$titre = nettoyer_titre_email($row['titre']);
	$chapo = $row['chapo'];
	$texte = $row['texte'];
	$date = $row['date'];
	$statut = $row['statut'];

	$les_auteurs = "";
	$result_auteurs = spip_query("SELECT nom FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien WHERE lien.id_article=$id_article AND auteurs.id_auteur=lien.id_auteur");

	while ($row = spip_fetch_array($result_auteurs)) {
		if ($les_auteurs) $les_auteurs .= ', ';
		$les_auteurs .= trim(supprimer_tags(typo($row['nom'])));
	}

	$extrait = "** $titre **\n";
	if ($les_auteurs) $extrait .= _T('info_les_auteurs_1', array('les_auteurs' => $les_auteurs));
	if ($statut == 'publie') $extrait .= " "._T('date_fmt_nomjour_date', array('nomjour'=>nom_jour($date), 'date'=>affdate($date)));
	$extrait .= "\n\n".textebrut(propre(couper_intro("$chapo<p>$texte", 700)))."\n\n";
	if ($statut == 'publie') 
		$extrait .= "-> "
			. generer_url_action("redirect", "id_article=$id_article", true)
			. "\n\n";
	return $extrait;
}


// http://doc.spip.org/@envoyer_mail_publication
function envoyer_mail_publication($id_article) {
	$adresse_suivi = $GLOBALS['meta']["adresse_suivi"];
	$nom_site_spip = nettoyer_titre_email($GLOBALS['meta']["nom_site"]);
	$suivi_edito = $GLOBALS['meta']["suivi_edito"];

	if ($suivi_edito == "oui") {
		$result = spip_query("SELECT * FROM spip_articles WHERE id_article = $id_article");

		if ($row = spip_fetch_array($result)) {

			// selectionne langue
			$lang_utilisateur = $GLOBALS['spip_lang'];
			changer_langue($row['lang']);

			$titre = nettoyer_titre_email($row['titre']);

			$sujet = _T('info_publie_1', array('nom_site_spip' => $nom_site_spip, 'titre' => $titre));
			$courr = _T('info_publie_2')."\n\n";

			$nom = $GLOBALS['auteur_session']['nom'];
			$nom = trim(supprimer_tags(typo($nom)));
			$courr .= _T('info_publie_01', array('titre' => $titre, 'connect_nom' => $nom))."\n\n\n";
			$courr = $courr . extrait_article($row);
			envoyer_mail($adresse_suivi, $sujet, $courr);

			// reinstalle la langue utilisateur (au cas ou)
			changer_langue($lang_utilisateur);
		}
	}
}

// http://doc.spip.org/@envoyer_mail_proposition
function envoyer_mail_proposition($id_article) {
	$adresse_suivi = $GLOBALS['meta']["adresse_suivi"];
	$nom_site_spip = nettoyer_titre_email($GLOBALS['meta']["nom_site"]);
	$suivi_edito = $GLOBALS['meta']["suivi_edito"];

	if ($suivi_edito == "oui") {
		$row = spip_fetch_array(spip_query("SELECT * FROM spip_articles WHERE id_article = $id_article"));
		if ($row) {

			$lang_utilisateur = $GLOBALS['spip_lang'];
			changer_langue($row['lang']);

			$titre = nettoyer_titre_email($row['titre']);

			$sujet = _T('info_propose_1', array('nom_site_spip' => $nom_site_spip, 'titre' => $titre));
			envoyer_mail($adresse_suivi,
				$sujet,
				_T('info_propose_2')
				."\n\n" 
				. _T('info_propose_3', array('titre' => $titre))
				."\n" 
				. _T('info_propose_4')
				."\n" 
				. _T('info_propose_5')
				."\n" 
				. generer_url_ecrire("articles", "id_article=$id_article", true)
				. "\n\n\n" 
				. extrait_article($row)
			);
			changer_langue($lang_utilisateur);
		}
	}
}


?>
