<?php

include_ecrire('inc_texte.php3');
include_ecrire('inc_filtres.php3');
include_ecrire('inc_mail.php3');

if (file_exists("inc-urls.php3")) {
        include_local ("inc-urls.php3");
}
else {
        include_local ("inc-urls-dist.php3");
}
// fichier inclus par inc-public.
// Voir commentaires dans celui-ci et dans inc-forum

  $retour_forum = rawurldecode($retour);
  $forum_id_article = intval($forum_id_article);
  $forum_id_rubrique = intval($forum_id_rubrique);
  $forum_id_parent = intval($forum_id_parent);
  $forum_id_breve = intval($forum_id_breve);
  $forum_id_syndic = intval($forum_id_syndic);
  $slash_texte = addslashes($texte);
  $slash_titre = addslashes($titre);
  $slash_nom_site_forum = addslashes($nom_site_forum);
  $slash_url_site = addslashes($url_site);
  $id_message = intval($id_message);
  if (!$id_auteur) $id_auteur = $GLOBALS['auteur_session']['id_auteur'];
  if ($forum_id_article) {
    if ($obj = spip_fetch_object(spip_query("
SELECT accepter_forum
FROM spip_articles
WHERE id_article=$forum_id_article")))
       $forums_publics = $obj->accepter_forum;
    else $forums_publics = lire_meta("forums_publics"); 
  } else {
    $forums_publics = substr(lire_meta("forums_publics"),0,3);	  
  }
  
  if ($forums_publics == "abo") {
    if ($auteur_session) {
      $statut = $auteur_session['statut'];
      
      if (!$statut OR $statut == '5poubelle') {
	die ("<h4>"._T('forum_acces_refuse'). "</h4>" . 
	     _T('forum_cliquer_retour',
		array('retour_forum' => $retour_forum)). "<p>");
      }
    }
    else {
      die ("<h4>"._T('forum_non_inscrit'). "</h4>" .
	   _T('forum_cliquer_retour',
	      array('retour_forum' => $retour_forum))."<p>");
    }
    // Ne pas autoriser de changement de nom si forum sur abonnement
    $auteur = $auteur_session['nom'];
    $email_auteur = $auteur_session['email'];
  } 
  $slash_auteur = addslashes($auteur);
  $slash_email_auteur = addslashes($email_auteur);

  if ((strlen($slash_texte) + strlen($slash_titre) + strlen($slash_nom_site_forum) + strlen($slash_url_site) + strlen($slash_auteur) + strlen($slash_email_auteur)) > 20 * 1024) {
      die ("<h4>"._T('forum_message_trop_long')."</h4>\n" .
	   _T('forum_cliquer_retour', array('retour_forum' => $retour_forum))."<p>");
	}

  spip_query("DELETE FROM spip_mots_forum WHERE id_forum='$id_message'");
  if ($ajouter_mot){
    for (reset($ajouter_mot); $key = key($ajouter_mot); next($ajouter_mot)){
      $les_mots .= ",".join($ajouter_mot[$key],",");
    }
    $les_mots = explode(",", $les_mots);
    for ($index = 0; $index < count($les_mots); $index++){
      $le_mot = $les_mots[$index];
      if ($le_mot > 0)
	spip_query("INSERT INTO spip_mots_forum (id_mot, id_forum) VALUES ('$le_mot', '$id_message')");
    }
  }

  $validation_finale = (strlen($confirmer) > 0 OR
			($afficher_texte=='non' AND $ajouter_mot));
  $statut = ((!$validation_finale) ? 'redac' : 
			(($forums_publics == 'non') ? 'off' :
			 (($forums_publics == 'pri') ? 'prop' : 'publie')));
  spip_query("
UPDATE	spip_forum
SET	id_parent = $forum_id_parent,
	id_rubrique =$forum_id_rubrique,
	id_article = $forum_id_article,
	id_breve = $forum_id_breve,
	id_syndic = \"$forum_id_syndic\",
	id_auteur = \"$id_auteur\",
	date_heure = NOW(),
	titre = \"$slash_titre\",
	texte = \"$slash_texte\",
	nom_site = \"$slash_nom_site_forum\",
	url_site = \"$slash_url_site\",
	auteur = \"$slash_auteur\",
	email_auteur = \"$slash_email_auteur\",
	ip = \"$REMOTE_ADDR\",
	statut = \"$statut\"
WHERE	id_forum = '$id_message'
");

if ($validation_finale)
 {
    include_ecrire("inc_admin.php3");
    if (!(verifier_action_auteur("ajout_forum $forum_id_rubrique $forum_id_parent $forum_id_article $forum_id_breve $forum_id_syndic $alea",
				 $hash)))
      {header("Status: 404");exit;}
    else
    {
	// Poser un cookie pour ne pas retaper le nom / email
	$cookie_user = array('nom' => $auteur, 'email' => $email_auteur);
	spip_setcookie('spip_forum_user', serialize($cookie_user));

		// Envoi d'un mail aux auteurs
	$prevenir_auteurs = lire_meta("prevenir_auteurs");
	if ($prevenir_auteurs == "oui" AND $afficher_texte != "non") {
	  if ($id_article = $forum_id_article) {
	    $url = ereg_replace('^/', '', generer_url_article($id_article));
	    $adresse_site = lire_meta("adresse_site");
	    $nom_site_spip = lire_meta("nom_site");
	    $url = "$adresse_site/$url";
	    $courr = _T('form_forum_message_auto')."\n\n";
	    $parauteur = '';
	    if (strlen($auteur) > 2) {
	      $parauteur = " "._T('forum_par_auteur', array('auteur' => $auteur));
	      if ($email_auteur) $parauteur .= " <$email_auteur>";
	    }
	    $courr .= _T('forum_poste_par', array('parauteur' => $parauteur))."\n";
	    $courr .= _T('forum_ne_repondez_pas')."\n";
	    $courr .= "$url\n";
	    $courr .= "\n\n".$titre."\n\n".textebrut(propre($texte))."\n\n$nom_site_forum\n$url_site\n";
	    $sujet = "[$nom_site_spip] ["._T('forum_forum')."] $titre";
	    $query = "SELECT auteurs.* FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien ".
	      "WHERE lien.id_article='$id_article' AND auteurs.id_auteur=lien.id_auteur";
	    $result = spip_query($query);
	    
	    while ($row = spip_fetch_array($result)) {
	      $email_auteur = trim($row["email"]);
	      if (strlen($email_auteur) < 3) continue;
	      envoyer_mail($email_auteur, $sujet, $courr);
	    }
	  }
	}
	// destruction du formulaire (on pourrait leconserver
	// car il ne contient rien de spe'cifique (php dynamique)
	//  mais il est statistiquement peu re'utilise')
	// destruction de la page ayant de'clenche' le formulaire si non mode're'

	$cache = rawurldecode($cache);

	if (file_exists('inc-invalideur.php3'))
	  {
	    include('inc-invalideur.php3');
	    applique_invalideur(($statut == 'publie') ?
				array($var_cache, $cache) :
				array($var_cache));
	      }
	else // minimum vital 
	  {
	    @unlink($var_cache);
	    if ($statut == 'publie') @unlink($cache);
	  }
    }
    $redirect = $retour_forum;
 }
?>
