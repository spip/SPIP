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

if (!defined("_ECRIRE_INC_VERSION")) return;


// La fonction de notification de base, qui dispatche le travail
// http://doc.spip.org/@inc_notifications_dist
function inc_notifications_dist($quoi, $id=0, $options=array()) {
	
	// charger les fichiers qui veulent ajouter des definitions
	// ou faire des trucs aussi dans le pipeline, ca fait deux api pour le prix d'une ...
	pipeline('notifications',array('args'=>array('quoi'=>$quoi,'id'=>$id,'options'=>$options)));

	if (function_exists($f = 'notifications_'.$quoi)
	OR function_exists($f = $f.'_dist')) {
		spip_log("$f($quoi,$id"
			.($options?",".serialize($options):"")
			.")");
		$f($quoi, $id, $options);
	}
}

// Fonction appelee par divers pipelines
// http://doc.spip.org/@notifications_instituerarticle_dist
function notifications_instituerarticle_dist($quoi, $id_article, $options) {

	// ne devrait jamais se produire
	if ($options['statut'] == $options['statut_ancien']) {
		spip_log("statut inchange");
		return;
	}

	include_spip('inc/texte');

	if ($options['statut'] == 'publie')
		notifier_publication_article($id_article);

	if ($options['statut'] == 'prop' AND $options['statut_ancien'] != 'publie')
		notifier_proposition_article($id_article);
}


// http://doc.spip.org/@extrait_article
function extrait_article($row) {
	include_spip('inc/texte');
	
	$envoyer_mail = charger_fonction('envoyer_mail','inc');
	$titre = nettoyer_titre_email($row['titre']);
	$id_article = $row['id_article'];
	$chapo = $row['chapo'];
	$texte = $row['texte'];
	$date = $row['date'];
	$statut = $row['statut'];

	$les_auteurs = "";
	$result_auteurs = sql_select("nom", "spip_auteurs AS A, spip_auteurs_articles AS L", "L.id_article=$id_article AND A.id_auteur=L.id_auteur");

	while ($row = sql_fetch($result_auteurs)) {
		if ($les_auteurs) $les_auteurs .= ', ';
		$les_auteurs .= trim(supprimer_tags(typo($row['nom'])));
	}

	$extrait = "** $titre **\n";
	if ($les_auteurs) $extrait .= _T('info_les_auteurs_1', array('les_auteurs' => $les_auteurs));
	if ($statut == 'publie') $extrait .= " "._T('date_fmt_nomjour_date', array('nomjour'=>nom_jour($date), 'date'=>affdate($date)));
	$extrait .= "\n\n".textebrut(propre(couper("$chapo<p>$texte ", 700)))."\n\n";
	return $extrait;
}


// http://doc.spip.org/@notifier_publication_article
function notifier_publication_article($id_article) {

	$envoyer_mail = charger_fonction('envoyer_mail','inc');
	$adresse_suivi = $GLOBALS['meta']["adresse_suivi"];
	$nom_site_spip = nettoyer_titre_email($GLOBALS['meta']["nom_site"]);
	$suivi_edito = $GLOBALS['meta']["suivi_edito"];

	if ($suivi_edito == "oui") {
		$row = sql_fetsel("*", "spip_articles", "id_article = $id_article");
		if ($row) {

			$l = lang_select($row['lang']);

			$url = generer_url_entite_absolue($id_article, 'article');

			$titre = nettoyer_titre_email($row['titre']);

			$sujet = _T('info_publie_1', array('nom_site_spip' => $nom_site_spip, 'titre' => $titre));
			$courr = _T('info_publie_2')."\n\n";

			$nom = $GLOBALS['visiteur_session']['nom'];
			$nom = trim(supprimer_tags(typo($nom)));
			$courr .= _T('info_publie_01', array('titre' => $titre, 'connect_nom' => $nom))
				. "\n\n"
				. extrait_article($row)
				. "-> " . $url
				. "\n";
			$envoyer_mail($adresse_suivi, $sujet, $courr);
			if ($l) lang_select();
		}
	}
}

// http://doc.spip.org/@notifier_proposition_article
function notifier_proposition_article($id_article) {
	$envoyer_mail = charger_fonction('envoyer_mail','inc');
	$adresse_suivi = $GLOBALS['meta']["adresse_suivi"];
	$nom_site_spip = nettoyer_titre_email($GLOBALS['meta']["nom_site"]);
	$suivi_edito = $GLOBALS['meta']["suivi_edito"];

	if ($suivi_edito == "oui") {
		$row = sql_fetsel("*", "spip_articles", "id_article = $id_article");
		if ($row) {

			if ($l = $row['lang']) $l = lang_select($l);

			$titre = nettoyer_titre_email($row['titre']);

			$sujet = _T('info_propose_1', array('nom_site_spip' => $nom_site_spip, 'titre' => $titre));
			$envoyer_mail($adresse_suivi,
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
			if ($l) lang_select();
		}
	}
}

?>