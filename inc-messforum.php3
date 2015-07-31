<?php

include_ecrire('inc_forum.php3');
include_local('inc-forum.php3');

// Ce fichier inclus par inc-public a un comportement special
// Voir commentaires dans celui-ci et dans inc-forum

function prevenir_auteurs($auteur, $email_auteur, $id_article, $texte, $titre)
{
	include_ecrire('inc_texte.php3');
	include_ecrire('inc_filtres.php3');
	include_ecrire('inc_mail.php3');
	// Gestionnaire d'URLs
	if (@file_exists("inc-urls.php3"))
	  include_local("inc-urls.php3");
	else
	  include_local("inc-urls-".$GLOBALS['type_urls'].".php3");
	$url = ereg_replace('^/', '', generer_url_article($id_article));
	$adresse_site = lire_meta("adresse_site");
	$nom_site_spip = lire_meta("nom_site");
	$url = "$adresse_site/$url";
	$courr = _T('form_forum_message_auto')."\n\n";
	$parauteur = '';
	if (strlen($auteur) > 2) {
		$parauteur = " "._T('forum_par_auteur',
				    array('auteur' => $auteur));
		if ($email_auteur)
			$parauteur .= " <$email_auteur>";
	}
	$courr .= _T('forum_poste_par',
		     array('parauteur' => $parauteur))."\n";
	$courr .= _T('forum_ne_repondez_pas')."\n";
	$courr .= "$url\n";
	$courr .= "\n\n".$titre."\n\n".textebrut(propre($texte)).
	  "\n\n$nom_site_forum\n$url_site\n";
	$sujet = "[$nom_site_spip] ["._T('forum_forum')."] $titre";
	$result = spip_query("SELECT auteurs.* FROM spip_auteurs AS auteurs,
				spip_auteurs_articles AS lien
				WHERE lien.id_article='$id_article'
				AND auteurs.id_auteur=lien.id_auteur");

	while ($row = spip_fetch_array($result)) {
		$email_auteur = trim($row["email"]);
		if (strlen($email_auteur) < 3) continue;
		envoyer_mail($email_auteur, $sujet, $courr);
	}
}			

$retour_forum = rawurldecode($retour);
$forum_id_article = intval($id_article);
$forum_id_rubrique = intval($id_rubrique);
$forum_id_forum = intval($id_forum);
$forum_id_breve = intval($id_breve);
$forum_id_syndic = intval($id_syndic);
$slash_texte = addslashes($texte);
$slash_titre = addslashes($titre);
$slash_nom_site_forum = addslashes($nom_site_forum);
$slash_url_site = addslashes($url_site);
$id_message = intval($id_message);

// Nature du forum
if (!$id_auteur)
	$id_auteur = intval($GLOBALS['auteur_session']['id_auteur']);

if ($forum_id_article) {
	$r = spip_fetch_array(spip_query("SELECT accepter_forum FROM spip_articles WHERE id_article=$forum_id_article"));
	if ($r)
		$forums_publics = $r['accepter_forum'];
	else
		$forums_publics = lire_meta("forums_publics");
} else {
	$forums_publics = substr(lire_meta("forums_publics"),0,3);
}

if ($forums_publics == "abo") {
	if ($auteur_session) {
		$statut = $auteur_session['statut'];
		if (!$statut OR $statut == '5poubelle') {
			die ("<h4>"._T('forum_acces_refuse'). "</h4>" . 
			_T('forum_cliquer_retour', array('retour_forum' => $retour_forum)).
			"<p>");
		}
	}
	else {
		die ("<h4>"._T('forum_non_inscrit'). "</h4>" . 
		_T('forum_cliquer_retour', array('retour_forum' => $retour_forum)).
		"<p>");
	}

	// Ne pas autoriser de changement de nom si forum sur abonnement
	$auteur = $auteur_session['nom'];
	$email_auteur = $auteur_session['email'];
 } 

$slash_auteur = addslashes($auteur);
$slash_email_auteur = addslashes($email_auteur);

if ((strlen($slash_texte) + strlen($slash_titre) + strlen($slash_nom_site_forum) + strlen($slash_url_site) + strlen($slash_auteur) + strlen($slash_email_auteur)) > 20 * 1024) {
	die ("<h4>"._T('forum_message_trop_long')."</h4>\n" .
	_T('forum_cliquer_retour', array('retour_forum' => $retour_forum)).
	"<p>");
}

spip_query("DELETE FROM spip_mots_forum WHERE id_forum='$id_message'");
if ($ajouter_mot) {
	for (reset($ajouter_mot);$key=key($ajouter_mot);next($ajouter_mot))
		$les_mots .= ",".join($ajouter_mot[$key],",");
	$les_mots = explode(",", $les_mots);
	for ($index = 0; $index < count($les_mots); $index++){
		$le_mot = $les_mots[$index];
		if ($le_mot > 0)
		spip_query("INSERT INTO spip_mots_forum (id_mot, id_forum)
		VALUES ('$le_mot', '$id_message')");
	}
}

$validation_finale = (strlen($confirmer) > 0 OR
	($afficher_texte=='non' AND $ajouter_mot));
$statut = ((!$validation_finale) ? 'redac' : 
	(($forums_publics == 'non') ? 'off' :
	(($forums_publics == 'pri') ? 'prop' : 'publie')));

if ($forum_id_forum > 0) $id_thread = $forum_id_forum;
else $id_thread = $id_message;

spip_query("UPDATE spip_forum SET id_parent = $forum_id_forum,
	id_rubrique =$forum_id_rubrique,
	id_article = $forum_id_article,
	id_breve = $forum_id_breve,
	id_syndic = $forum_id_syndic,
	id_auteur = $id_auteur,
	id_thread = $id_thread,
	date_heure = NOW(),
	titre = \"$slash_titre\",
	texte = \"$slash_texte\",
	nom_site = \"$slash_nom_site_forum\",
	url_site = \"$slash_url_site\",
	auteur = \"$slash_auteur\",
	email_auteur = \"$slash_email_auteur\",
	ip = \"$REMOTE_ADDR\",
	statut = \"$statut\"
	WHERE id_forum = '$id_message'
");

//calculer_threads();

if ($validation_finale) {
	include_ecrire("inc_admin.php3");
	if (!(verifier_action_auteur("ajout_forum $forum_id_rubrique".
	" $forum_id_forum $forum_id_article $forum_id_breve".
	" $forum_id_syndic $alea", $hash))) {
		header("Status: 404");
		exit;
	} else {
	  if (lire_meta("prevenir_auteurs") == "oui" AND ($afficher_texte != "non") AND ($id_article = $forum_id_article)) {
			prevenir_auteurs($auteur, $email_auteur, $id_article, $texte, $titre);

		}
		// Poser un cookie pour ne pas retaper le nom / email
		$cookie_user = array('nom' => $auteur, 'email' => $email_auteur);
		spip_setcookie('spip_forum_user', serialize($cookie_user));

		//
		// INVALIDATION DES CACHES LIES AUX FORUMS
		//
		include_ecrire('inc_invalideur.php3');
		if ($statut == 'publie') {
			suivre_invalideur ("id='id_forum/" .
				calcul_index_forum($forum_id_article,
					$forum_id_breve,
					$forum_id_rubrique,
					$forum_id_syndic) . "'");
		}

		$redirect = $retour_forum;
	}
}

?>
