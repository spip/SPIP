<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;


// La fonction de notification de base, qui dispatche le travail
// http://doc.spip.org/@inc_notifications_dist
function inc_notifications_dist($quoi, $id=0, $options=array()) {
	if (function_exists($f = 'notifications_'.$quoi)
	OR function_exists($f = $f.'_dist')) {
		spip_log("$f($quoi,$id"
			.($options?",".serialize($options):"")
			.")");
		$f($quoi, $id, $options);
	}
}

// Fonction appelee par divers pipelines
// http://doc.spip.org/@notifications_instituerarticle
function notifications_instituerarticle($quoi, $id_article, $options) {

	// ne devrait jamais se produire
	if ($options['statut'] == $options['statut_ancien']) {
		spip_log("statut inchange");
		return;
	}

	include_spip('inc/lang');
	include_spip('inc/texte');
	include_spip('inc/mail');

	if ($options['statut'] == 'publie')
		notifier_publication_article($id_article);

	if ($options['statut'] == 'prop' AND $options['statut_ancien'] != 'publie')
		notifier_proposition_article($id_article);
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
	return $extrait;
}


// http://doc.spip.org/@notifier_publication_article
function notifier_publication_article($id_article) {
	$adresse_suivi = $GLOBALS['meta']["adresse_suivi"];
	$nom_site_spip = nettoyer_titre_email($GLOBALS['meta']["nom_site"]);
	$suivi_edito = $GLOBALS['meta']["suivi_edito"];

	if ($suivi_edito == "oui") {
		$result = spip_query("SELECT * FROM spip_articles WHERE id_article = $id_article");

		if ($row = spip_fetch_array($result)) {

			// selectionne langue
			$lang_utilisateur = $GLOBALS['spip_lang'];
			changer_langue($row['lang']);

			// URL de l'article
			charger_generer_url();
			$url = url_absolue(generer_url_article($id_article, true));

			$titre = nettoyer_titre_email($row['titre']);

			$sujet = _T('info_publie_1', array('nom_site_spip' => $nom_site_spip, 'titre' => $titre));
			$courr = _T('info_publie_2')."\n\n";

			$nom = $GLOBALS['auteur_session']['nom'];
			$nom = trim(supprimer_tags(typo($nom)));
			$courr .= _T('info_publie_01', array('titre' => $titre, 'connect_nom' => $nom))
				. "\n\n"
				. extrait_article($row)
				. "-> " . $url
				. "\n";
			envoyer_mail($adresse_suivi, $sujet, $courr);

			// reinstalle la langue utilisateur (au cas ou)
			changer_langue($lang_utilisateur);
		}
	}
}

// http://doc.spip.org/@notifier_proposition_article
function notifier_proposition_article($id_article) {
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

// http://doc.spip.org/@email_notification_forum
function email_notification_forum ($t, $email) {

	// Rechercher eventuellement la langue du destinataire
	if ($l = spip_fetch_array(spip_query("SELECT lang FROM spip_auteurs WHERE email=" . _q($email))))
		lang_select($l['lang']);


	charger_generer_url();

	if ($t['statut'] == 'prop') # forum modere
		$url = generer_url_ecrire('controle_forum', "debut_id_forum=".$t['id_forum']);
	else if (function_exists('generer_url_forum'))
		$url = generer_url_forum($t['id_forum']);
	else {
		spip_log('inc-urls personnalise : ajoutez generer_url_forum() !');
		if ($t['id_article'])
			$url = generer_url_article($t['id_article']).'#'.$t['id_forum'];
		else
			$url = './';
	}

	if ($t['id_article']) {
		$article = spip_fetch_array(spip_query("SELECT titre FROM spip_articles WHERE id_article="._q($t['id_article'])));
		$titre = textebrut(typo($article['titre']));
	}

	$sujet = "[" .
	  entites_html(textebrut(typo($GLOBALS['meta']["nom_site"]))) .
	  "] ["._T('forum_forum')."] ".typo($t['titre']);

	$parauteur = (strlen($t['auteur']) <= 2) ? '' :
	  (" " ._T('forum_par_auteur', array(
	  	'auteur' => $t['auteur'])
	  ) . 
	   ($t['email_auteur'] ? ' <' . $t['email_auteur'] . '>' : ''));

	// TODO: squelettiser
	$corps = _T('form_forum_message_auto') .
		"\n\n" .
		_T('forum_poste_par', array('parauteur' => $parauteur,
	  	'titre' => $titre)).
		"\n\n"
		. (($t['statut'] == 'publie') ? _T('forum_ne_repondez_pas')."\n" : '')
		. url_absolue($url)
		. "\n\n\n** ".textebrut(typo($t['titre']))
		."\n\n* ".textebrut(propre($t['texte']))
		. "\n\n".$t['nom_site']."\n".$t['url_site']."\n";

	if ($l)
		lang_dselect();

	return array('subject' => $sujet, 'body' => $corps);
}


// cette notification s'execute quand on valide un message 'prop'ose,
// dans ecrire/inc/forum_insert.php ; ici on va notifier ceux qui ne l'ont
// pas ete a la notification forumposte (sachant que les deux peuvent se
// suivre si le forum est valide directement ('pos' ou 'abo')
// http://doc.spip.org/@notifications_forumvalide_dist
function notifications_forumvalide_dist($quoi, $id_forum) {
	$s = spip_query("SELECT * FROM spip_forum WHERE id_forum="._q($id_forum));
	if (!$t = spip_fetch_array($s))
		return;

	include_spip('inc/texte');
	include_spip('inc/filtres');
	include_spip('inc/mail');
	include_spip('inc/autoriser');


	// Qui va-t-on prevenir ?
	$tous = array();
	$pasmoi = array();

	// 1. Les auteurs de l'article ; si c'est un article, ceux qui n'ont
	// pas le droit de le moderer (les autres l'ont recu plus tot)
	if ($t['id_article']
	AND $GLOBALS['meta']['prevenir_auteurs'] == 'oui') {
		$result = spip_query("SELECT auteurs.* FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien WHERE lien.id_article="._q($t['id_article'])." AND auteurs.id_auteur=lien.id_auteur");

		while ($qui = spip_fetch_array($result)) {
			if (!autoriser('modererforum', 'article', $t['id_article'], $qui['id_auteur']))
				$tous[] = $qui['email'];
			else
				$pasmoi[] = $qui['email'];

		}
	}

	// 2. Tous les participants a ce *thread* (desactive pour l'instant)
	// TODO: proposer une case a cocher ou un lien dans le message
	// pour se retirer d'un troll (hack: replacer @ par % dans l'email)
	if (defined('_SUIVI_FORUM_THREAD')
	AND _SUIVI_FORUM_THREAD) {
		$s = spip_query("SELECT DISTINCT(email_auteur) FROM spip_forum WHERE id_thread=".$t['id_thread']." AND email_auteur != ''");
		while ($r = spip_fetch_array($s))
			$tous[] = $r['email_auteur'];
	}


	// 3. Tous les auteurs des messages qui precedent (desactive egalement)
	// (possibilite exclusive de la possibilite precedente)
	// TODO: est-ce utile, par rapport au thread ?
	else if (defined('_SUIVI_FORUMS_REPONSES')
	AND _SUIVI_FORUMS_REPONSES
	AND $t['statut'] == 'publie') {
		$id_parent = $id_forum;
		while ($r = spip_fetch_array(spip_query("SELECT email_auteur, id_parent FROM spip_forum WHERE id_forum=$id_parent AND statut='publie'"))) {
			$tous[] = $r['email_auteur'];
			$id_parent = $r['id_parent'];
		}
	}

	// Nettoyer le tableau
	// Ne pas ecrire au posteur du message, ni au moderateur qui active le mail,
	// ni aux auteurs deja notifies precedemment
	$destinataires = array();
	foreach ($tous as $m) {
		if ($m = email_valide($m)
		AND $m != trim($t['email_auteur'])
		AND $m != $GLOBALS['auteur_session']['email']
		AND !in_array($m, $pasmoi))
			$destinataires[$m]++;
	}

	//
	// Envoyer les emails
	//
	foreach (array_keys($destinataires) as $email) {
		$msg = email_notification_forum($t, $email);
		envoyer_mail($email, $msg['subject'], $msg['body']);
	}
}


// http://doc.spip.org/@notifications_forumposte_dist
function notifications_forumposte_dist($quoi, $id_forum) {
	$s = spip_query("SELECT * FROM spip_forum WHERE id_forum="._q($id_forum));
	if (!$t = spip_fetch_array($s))
		return;

	include_spip('inc/texte');
	include_spip('inc/filtres');
	include_spip('inc/mail');
	include_spip('inc/autoriser');


	// Qui va-t-on prevenir ?
	$tous = array();

	// 1. Les auteurs de l'article (si c'est un article), mais
	// seulement s'ils ont le droit de le moderer (les autres seront
	// avertis par la notifications_forumvalide).
	if ($t['id_article']
	AND $GLOBALS['meta']['prevenir_auteurs'] == 'oui') {
		$result = spip_query("SELECT auteurs.* FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien WHERE lien.id_article="._q($t['id_article'])." AND auteurs.id_auteur=lien.id_auteur");

		while ($qui = spip_fetch_array($result)) {
			if (autoriser('modererforum', 'article', $t['id_article'], $qui['id_auteur']))
				$tous[] = $qui['email'];
		}
	}

	// 2. Les moderateurs definis par mes_options
	// TODO: a passer en meta
	// define('_MODERATEURS_FORUM', 'email1,email2,email3');
	if (defined('_MODERATEURS_FORUM'))
	foreach (explode(',', _SPIP_MODERATEURS_FORUM) as $m) {
		$tous[] = $m;
	}


	// Nettoyer le tableau
	// Ne pas ecrire au posteur du message !
	$destinataires = array();
	foreach ($tous as $m) {
		if ($m = email_valide($m)
		AND $m != trim($t['email_auteur']))
			$destinataires[$m]++;
	}

	//
	// Envoyer les emails
	//
	foreach (array_keys($destinataires) as $email) {
		$msg = email_notification_forum($t, $email);
		envoyer_mail($email, $msg['subject'], $msg['body']);
	}

	// Notifier les autres si le forum est valide
	if ($t['statut'] == 'publie') {
		$notifications = charger_fonction('notifications', 'inc');
		$notifications('forumvalide', $id_forum);
	}
}

?>
