<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_FORUM")) return;
define("_INC_FORUM", "1");

include_ecrire('inc_meta.php3');
include_ecrire('inc_admin.php3');
include_ecrire('inc_acces.php3');
include_ecrire('inc_texte.php3');
include_ecrire('inc_filtres.php3');
include_ecrire('inc_lang.php3');
include_ecrire('inc_mail.php3');
include_ecrire('inc_barre.php3');

if (@file_exists("inc-urls.php3")) {
	include_local ("inc-urls.php3");
}
else {
	include_local ("inc-urls-dist.php3");
}

// fabrique un bouton d'attribut Name $n, d'attribut Value $v et autres $a

function boutonne($a, $n, $v) {
     return "\n<input $a name='$n' value=\"$v\" />";
}

// Re'ponse a` un forum 

function retour_forum($id_rubrique, $id_parent, $id_article, $id_breve, $id_syndic, $titre, $table, $forums_publics, $url, $hidden) {

	global $REMOTE_ADDR, $id_message, $afficher_texte, $spip_forum_user;

	// Recuperer le message a previsualiser
	if ($id_message = intval($GLOBALS[HTTP_POST_VARS][id_message]))  {
		$titre = $GLOBALS[HTTP_POST_VARS][titre];
		$texte = $GLOBALS[HTTP_POST_VARS][texte];
		$auteur = $GLOBALS[HTTP_POST_VARS][auteur];
		$email_auteur = $GLOBALS[HTTP_POST_VARS][email_auteur];
		$nom_site_forum = $GLOBALS[HTTP_POST_VARS][nom_site_forum];
		$url_site = $GLOBALS[HTTP_POST_VARS][url_site];

		if ($afficher_texte != 'non') {
			$bouton = 
			  "<div style='font-size: 120%; font-weigth: bold;'>" .
			  typo($titre) .
			  "</div><p /><b><a href='mailto:" .
			  entites_html($email_auteur) .
			  "'>" .
			  typo($auteur) .
			  "</a></b><p />" .
			  propre($texte) .
			  "<p /><a href='" .
			  entites_html($url_site) .
			  "'>" .
			  typo($nom_site_forum) .
			  "</a>";

			// Verifier mots associes au message
			$result_mots = spip_query("
SELECT	mots.id_mot, mots.titre, mots.type
FROM	spip_mots_forum AS lien,
	spip_mots AS mots 
WHERE	id_forum='$id_message'
    AND mots.id_mot = lien.id_mot 
GROUP BY mots.id_mot
");
			if (spip_num_rows($result_mots)>0) 
			  {
			    $bouton .= "<p>".
			      _T('forum_avez_selectionne') .
			      "</p><ul>";
			    while ($row = spip_fetch_array($result_mots)) {
			      $les_mots[$row['id_mot']] = "checked='checked'";
			      $presence_mots = true;
			      $bouton .= "<li class='font-size=80%'> ".
				$row['type'] .
				"&nbsp;: <b>" .
				$row['titre'] . 
				"</b></li>";
			    }
			    $bouton .= '</ul>';
			  }

			if (strlen($texte) < 10 AND !$presence_mots) {
				$bouton .= "<p align='right' style='color: red;'>"._T('forum_attention_dix_caracteres')."</p>\n";
			}
			else if (strlen($titre) < 3 AND $afficher_texte <> "non") {
				$bouton .= "<p align='right' style='color: red;'>"._T('forum_attention_trois_caracteres')."</p>";
			}
			else {
				$bouton .= "<div align='right'><input type='submit' name='confirmer' class='spip_bouton' value='"._T('forum_message_definitif')."' /></div>";
			}
			$bouton = "<div class='spip_encadrer'>$bouton</div>\n<br />";
		}
	} else {
		// Si premiere edition, initialiser l'auteur
	  	// puis s'accorder une nouvelle entre'e dans la table
		if ($spip_forum_user && is_array($cookie_user = unserialize($spip_forum_user))) {
			$auteur = $cookie_user['nom'];
			$email_auteur = $cookie_user['email'];
		}
		else {
			$auteur = $GLOBALS['auteur_session']['nom'];
			$email_auteur = $GLOBALS['auteur_session']['email'];
		}
		spip_query("
INSERT INTO spip_forum (date_heure, titre, ip, statut)
VALUES (NOW(), \"".addslashes($titre)."\", \"$REMOTE_ADDR\", \"redac\")
");
		$id_message = spip_insert_id();
	}

	        // Generation d'une valeur de securite pour validation
        $seed = (double) (microtime() + 1) * time() * 1000000;
        @mt_srand($seed);
        $alea = @mt_rand();
        if (!$alea) {srand($seed);$alea = rand();}
        $forum_id_rubrique = intval($id_rubrique);
        $forum_id_parent = intval($id_parent);
        $forum_id_article = intval($id_article);
        $forum_id_breve = intval($id_breve);
        $forum_id_syndic = intval($id_syndic);
        $hash = calculer_action_auteur("ajout_forum $forum_id_rubrique $forum_id_parent $forum_id_article $forum_id_breve $forum_id_syndic $alea");
	$titre = entites_html($titre);
	if (!$url_site) $url_site = "http://";
	if ($forums_publics == "abo") $disabled = " disabled='disabled'";

	if ((lire_meta("mots_cles_forums") == "oui") && ($table != 'forum'))
	  $table= table_des_mots($table, $les_mots);
	else $table = '';


	$url = quote_amp($url);
	return ("<form action='$url' method='post' name='formulaire'>\n$hidden" .
		boutonne("type='hidden'", 'id_message', $id_message) .
		boutonne("type='hidden'", 'alea', $alea) .
                boutonne("type='hidden'", 'hash', $hash) .
  		(($afficher_texte == "non") ?
		 (boutonne("type='hidden'", 'titre', $titre) .
		  $table .
		  "\n<br /><div align='right'>" .
		  boutonne("type='submit' class='spip_bouton'",
			   'Valider',
			   _T('forum_valider'). "</div>")) :
		 ($bouton . "<div class='spip_encadrer'><b>"._T('forum_titre')."</b>\n<br />".
		  boutonne("type='text' class='forml' size='40'", 'titre', $titre) . "</div>\n<br />"
		  ."<div class='spip_encadrer'><b>" .
		  _T('forum_texte') .
		  "</b>\n<br />" .
		  _T('info_creation_paragraphe') .
		  "\n<br /> " .
		  afficher_barre('formulaire', 'texte', true) .
		  "<textarea name='texte' " .
		  afficher_claret() .
		  " rows='12' class='forml' cols='40'>" .
		  entites_html($texte) .
		  "</textarea></div>" .
		  $table  .
		 "\n<br /><div class='spip_encadrer'>" .
		  _T('forum_lien_hyper') .
		  "\n<br />" .
		  _T('forum_page_url') .
		  "\n<br />" .
		  _T('forum_titre') .
		  "\n<br />" .
		  boutonne("type='text' class='forml' size='40'",
			   'nom_site_forum',
			   entites_html($nom_site_forum)) .
		  "\n<br />" .
		  _T('forum_url') .
		  "\n<br />" .
		  boutonne("type='text' class='forml'  size='40'", 
			   'url_site',
			   entites_html($url_site)) .
		  "</div>\n<br /><div class='spip_encadrer'>" .
		  _T('forum_qui_etes_vous') .
		  "\n<br />" .
		  _T('forum_votre_nom') .
		  "\n<br />" .
		  boutonne("type='text' class='forml' size='40'$disabled",
			   'auteur',
			   entites_html($auteur)) .
		  "\n<br />" .
		  _T('forum_votre_email') .
		  "\n<br />" .
		  boutonne("type='text' class='forml' size='40'$disabled",
			   'email_auteur',
			   entites_html($email_auteur)) .
		  "</div>\n<br /><div align='right'>" .
		  boutonne("type='submit' class='spip_bouton'",
			   'Valider',
			   _T('forum_voir_avant')) . 
		  "</div>\n</form>")));
}

function table_des_mots($table, $les_mots) {
  global $afficher_groupe;

  $result_groupe = spip_query("
SELECT * 
FROM spip_groupes_mots 
WHERE 6forum = 'oui' 
AND $table = 'oui' " . ((!$afficher_groupe) ? '' : ("
AND id_groupe IN (" . join($afficher_groupe, ", ")))
);
  
  $ret = '';
  while ($row_groupe = spip_fetch_array($result_groupe)) {
    $id_groupe = $row_groupe['id_groupe'];
    $titre_groupe = propre($row_groupe['titre']);
    $unseul = ($row_groupe['unseul']== 'oui') ? 'radio' : 'checkbox';
    $result =spip_query("SELECT * FROM spip_mots WHERE id_groupe='$id_groupe'");
    $total_rows = spip_num_rows($result);
    
    if ($total_rows > 0){
      $ret .= "\n<p /><div class='spip_encadrer' style='font-size: 80%;'>";
      $ret.= "<b>$titre_groupe&nbsp;:</b>";
      $ret .= "<table cellpadding='0' cellspacing='0' border='0' width='100%'>\n";
      $ret .= "<tr><td width='47%' valign='top'>";
      $i = 0;
      
      while ($row = spip_fetch_array($result)) {
	$id_mot = $row['id_mot'];
	$titre_mot = propre($row['titre']);
	$descriptif_mot = propre($row['descriptif']);
	
	if ($i >= ($total_rows/2) AND $i < $total_rows)
	  {
	    $i = $total_rows + 1;
	    $ret .= "</td><td width='6%'>&nbsp;</td><td width='47%' valign='top'>";
	}
	
	$ret .= boutonne("type='$unseul' id='mot$id_mot' " . $les_mots[$id_mot],
			 "ajouter_mot[$id_groupe][]", 
			 $id_mot) .
	  afficher_petits_logos_mots($id_mot) .
	  "<b><label for='mot$id_mot'>$titre_mot</label></b><br />";
	if (strlen($descriptif_mot) > 0) $ret .= "$descriptif_mot<br />";
	$i++;
      }
      
      $ret .= "</td></tr></table>";
      $ret .= "</div>";
    }
  }
  return $ret;
}

function afficher_petits_logos_mots($id_mot) {
  $image = cherche_image_nommee("moton$id_mot", $GLOBALS['dossier_images']);
  if ($image) {
    $taille = getimagesize($image);
    $largeur = $taille[0];
    $hauteur = $taille[1];
    if ($largeur < 100 AND $hauteur < 100)
      return "<img src='$image' align='middle' width='$largeur' height='$hauteur' hspace='1' vspace='1' alt=' ' border=0 class='spip_image'> ";
  }
  return "";
}

?>
