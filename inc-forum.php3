<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_FORUM")) return;
define("_INC_FORUM", "1");


include_ecrire("inc_meta.php3");
include_ecrire("inc_admin.php3");
include_ecrire("inc_acces.php3");
include_ecrire("inc_texte.php3");
include_ecrire("inc_filtres.php3");
include_ecrire("inc_mail.php3");
if (file_exists("inc-urls.php3")) {
	include_local ("inc-urls.php3");
}
else {
	include_local ("inc-urls-dist.php3");
}

// dupliquee dans ecrire/articles.php3 ; mais je ne sais pas ou l'installer (Fil)...
function get_forums_publics($id_article=0) {
	$forums_publics = lire_meta("forums_publics");
	if ($id_article) {
		$query = "SELECT accepter_forum FROM spip_articles WHERE id_article=$id_article";
		$res = spip_query($query);
		if ($obj = spip_fetch_object($res))
			$forums_publics = $obj->accepter_forum;
	} else {
		$forums_publics = substr(lire_meta("forums_publics"),0,3);
	}
	return $forums_publics;
}

function afficher_petits_logos_mots($id_mot) {
	$racine = "IMG/moton$id_mot";
	if (file_exists("$racine.gif")) {
		$image = "$racine.gif";
	} elseif (file_exists("$racine.jpg")) {
		$image = "$racine.jpg";
	} elseif (file_exists("$racine.png")) {
		$image = "$racine.png";
	}
	
	if ($image) {
		$taille = getimagesize($image);
		$largeur = $taille[0];
		$hauteur = $taille[1];
		if ($largeur < 100 AND $hauteur < 100)
			return "<IMG SRC='$image' align='middle' WIDTH='$largeur' HEIGHT='$hauteur' HSPACE='1' VSPACE='1' ALT=' ' BORDER=0 class='spip_image'> ";
		else return "";
	} else {
		return "";
	}
}


function decoder_hash_forum($email, $hash) {
	if (!$email OR !$hash) return false;
	$query = "SELECT * FROM spip_auteurs WHERE email='$email'";
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) {
		if (verifier_action_auteur("forum public $email", $hash, $row['id_auteur'])) {
			$ok = true;
			break;
		}
	}
	if ($ok) return $row;
	else return false;
}


function forum_abonnement($retour) {
	if ($GLOBALS['auteur_session'])
		return true;	// autoriser le formulaire
	else {
		include_local("inc-login.php3");

		$message_login = _T('forum_vous_enregistrer').
' <script language="JavaScript"><!--
document.write("<a href=\\"javascript:window.open(\\\'spip_pass.php3\\\', \\\'spip_pass\\\', \\\'scrollbars=yes,resizable=yes,width=480,height=450\\\'); void(0);\\"");
//--></script><noscript><a href=\'spip_pass.php3\' target=\'_blank\'></noscript>'._T('forum_vous_inscrire').'<br>';
		login('', false, $message_login);
		return false;
	} 
}


function retour_forum($id_rubrique, $id_parent, $id_article, $id_breve, $id_syndic, $titre='') {
	global $REQUEST_URI, $HTTP_GET_VARS, $PATH_TRANSLATED, $REMOTE_ADDR, $id_message ;
	$new = $GLOBALS["new"];
	$redac = $GLOBALS["redac"];
	$afficher_groupe = $GLOBALS["afficher_groupe"];
	$afficher_texte = $GLOBALS["afficher_texte"];

	$forums_publics = get_forums_publics($id_article);
	if ($forums_publics == "non") return;

	$lien = substr($REQUEST_URI, strrpos($REQUEST_URI, '/') + 1);

	$retour = $HTTP_GET_VARS['retour'];
	if (!$retour)
		$retour = rawurlencode($lien);

	if ($forums_publics == "abo")  // forums abo
		$ret .= '<'.'?php include("inc-forum.php3"); if (forum_abonnement($retour)) { ?'.'>';
	else
		$ret .= '<'.'?php { ?'.'>';

	$ret .= "\n<a name='formulaire_forum'></a>\n";
	$ret .= "\n<FORM ACTION='$lien' METHOD='post'>";
	
	if ($forums_publics == "pri") {
		$ret.= _T('forum_info_modere')."<p>";
	}
	
	// recuperer le titre
	if (! $titre) {
		if ($id_parent)
			$titre_select = "SELECT titre FROM spip_forum WHERE id_forum = $id_parent";
		else if ($id_rubrique)
			$titre_select = "SELECT titre FROM spip_rubriques WHERE id_rubrique = $id_rubrique";
		else if ($id_article)
			$titre_select = "SELECT titre FROM spip_articles WHERE id_article = $id_article";
		else if ($id_breve)
			$titre_select = "SELECT titre FROM spip_breves WHERE id_breve = $id_breve";
		else if ($id_syndic)
			$titre_select = "SELECT nom_site AS titre FROM spip_syndic WHERE id_syndic = $id_syndic";
		else
			$titre_select = "SELECT '".addslashes(_T('forum_titre_erreur'))."' AS titre";	

		$res = spip_fetch_object(spip_query($titre_select));
		$titre = '> ' . ereg_replace ('^[>[:space:]]*', '', $res->titre);
	}
	


	if ($id_message){		
		$query_forum="SELECT * FROM spip_forum WHERE ip=\"$REMOTE_ADDR\" AND id_forum=$id_message";
		$result_forum=spip_query($query_forum);
		
		while($row = spip_fetch_array($result_forum)) {
			$titre=$row['titre'];
			$texte=$row['texte'];
			$auteur=$row['auteur'];
			$email_auteur=$row['email_auteur'];
			$nom_site_forum=$row['nom_site'];
			$url_site=$row['url_site'];
		}
	
		if (!$nouveau_document AND $afficher_texte != 'non'){
			$ret .= "<div class='spip_encadrer'>";
			if ($afficher_texte != "non"){
				$ret .= "<font size=4 color='#aaaaaa'><b>".typo($titre)."</b></font>";
				$ret .= "<p><b><a href='mailto:$email_auteur'>".typo($auteur)."</a></b>";
				$ret .= "<p>".propre($texte)."<p>";
			}
			
			$ret .= "<a href='$url_site'>".typo($nom_site_forum)."</a>";
	
	
			// Verifier mots associes au message	
			$query_mots = "SELECT mots.* FROM spip_mots_forum AS lien, spip_mots AS mots WHERE id_forum='$id_message' AND mots.id_mot = lien.id_mot GROUP BY mots.id_mot";
			$result_mots = spip_query($query_mots);
			if (spip_num_rows($result_mots)>0) $ret .= "<p>"._T('forum_avez_selectionne');
			while ($row = spip_fetch_array($result_mots)) {
				$id_mot = $row['id_mot'];
				$type_mot = $row['type'];
				$titre_mot = $row['titre'];
				$les_mots[$id_mot] = true;
				$presence_mots = true;
				
				$ret.= "<li> $type_mot&nbsp;: <b>$titre_mot</b>";
				
			}
		
			if (strlen($texte) < 10 AND !$presence_mots)
				$ret .= "<p><div align='right'><font color=red>"._T('forum_attention_dix_caracteres')."</font></div>\n";
			else if (strlen($titre) < 3 AND $afficher_texte <> "non")
				$ret .= "<p><div align='right'><font color=red>"._T('forum_attention_trois_caracteres')."</font></div>\n";
			else
				$ret .= "\n<p><DIV ALIGN='right'><INPUT TYPE='submit' NAME='confirmer' CLASS='spip_bouton' VALUE='"._T('forum_message_definitif')."'></DIV>";
	
			$ret .= "</div>\n<p>";
		}
	}

	$ret .= "\n";
	
	

	$seed = (double) (microtime() + 1) * time() * 1000000;
	@mt_srand($seed);
	$alea = @mt_rand();
	if (!$alea) {
		srand($seed);
		$alea = rand();
	}
	$id_rubrique = (int) $id_rubrique;
	$id_parent = (int) $id_parent;
	$id_article = (int) $id_article;
	$id_breve = (int) $id_breve;
	$id_syndic = (int) $id_syndic;
	$hash = calculer_action_auteur("ajout_forum $id_rubrique $id_parent $id_article $id_breve $id_syndic $alea");

	$titre = entites_html($titre);
	$texte = entites_html($texte);
	
	if ($afficher_texte == "non"){
		$ret .= "\n<INPUT TYPE='hidden' NAME='titre' VALUE=\"$titre\">";
	}
	else {
		$ret .= "\n<div class='spip_encadrer'><B>"._T('forum_titre')."</B><BR>";
		$ret .= "\n<INPUT TYPE='text' CLASS='forml' NAME='titre' VALUE=\"$titre\" SIZE='40'></div>";
	}
	
	$ret .= "\n<INPUT TYPE='Hidden' NAME='id_message' VALUE=\"$id_message\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='ajout_forum' VALUE=\"oui\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='forum_id_rubrique' VALUE=\"$id_rubrique\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='forum_id_parent' VALUE=\"$id_parent\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='forum_id_article' VALUE=\"$id_article\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='forum_id_breve' VALUE=\"$id_breve\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='forum_id_syndic' VALUE=\"$id_syndic\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='alea' VALUE=\"$alea\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='hash' VALUE=\"$hash\">";
	$ret .= "\n<INPUT TYPE='Hidden' NAME='retour_forum' VALUE=\"$retour\">";
	
	if ($new != "oui" AND $redac != "oui") $ret .= "\n<INPUT TYPE='Hidden' NAME='new' VALUE=\"oui\">";
	if ($new == "oui") $ret .= "\n<INPUT TYPE='Hidden' NAME='redac' VALUE=\"oui\">";

	
	if ($afficher_texte !="non"){
		$ret .= "\n<p><div class='spip_encadrer'><B>"._T('forum_texte')."</B><BR>\n";
		$ret .= _T('forum_creer_paragraphes');
		$ret .= "<br>\n<TEXTAREA NAME='texte' ROWS='12' CLASS='forml' COLS='40' wrap=soft>";
		$ret.= $texte;
		$ret .= "\n</TEXTAREA></div>\n";
	}



	/// Gestion des mots-cles
	
	$mots_cles_forums=lire_meta("mots_cles_forums");
	if ($mots_cles_forums == "oui"){
		if ($id_rubrique > 0) $table = "rubriques";
		else if ($id_article > 0) $table = "articles";
		else if ($id_breve > 0) $table = "breves";
		else if ($id_syndic > 0) $table = "syndic";
		

		if ($afficher_groupe) {
			$afficher_groupe = join($afficher_groupe, ",");
			$selectionner_groupe = "AND id_groupe IN ($afficher_groupe)";
		}
		if ($table){
			$query_groupe = "SELECT * FROM spip_groupes_mots WHERE 6forum = 'oui' AND $table = 'oui' $selectionner_groupe";
			$result_groupe = spip_query($query_groupe);
			while ($row_groupe = spip_fetch_array($result_groupe)) {
				$id_groupe = $row_groupe['id_groupe'];
				$titre_groupe = $row_groupe['titre'];
				$unseul_groupe = $row_groupe['unseul'];
				
				$query = "SELECT * FROM spip_mots WHERE id_groupe='$id_groupe'";
				$result = spip_query($query);
				$total_rows = spip_num_rows($result);

				if ($total_rows > 0){
					$ret .= "\n<p><div class='spip_encadrer'>";
					$ret.= "<b>$titre_groupe&nbsp;:</b>";
					
					$ret .= "<table cellpadding=0 cellspacing=0 border=0 width='100%'>\n";
					$ret .= "<tr><td width='47%' valign='top'><font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
					$i = 0;
					
					while ($row = spip_fetch_array($result)) {
						$id_mot = $row['id_mot'];
						$titre_mot = propre($row['titre']);
						$type_mot = propre($row['type']);
						$descriptif_mot = $row['descriptif'];
					
						if ($i >= ($total_rows/2) AND $i < $total_rows){
							$i = $total_rows + 1;
							$ret .= "</font></td><td width='6%'>&nbsp;</td><td width='47%' valign='top'><font face='Verdana,Arial,Helvetica,sans-serif' size=2>";
						}
						
						if ($les_mots[$id_mot]) $checked = "checked";
						else $checked = "";
						
						if ($unseul_groupe == 'oui'){
							$ret .= "<input type='radio' name='ajouter_mot[$id_groupe][]' value='$id_mot' $checked id='mot$id_mot'> ";
						}
						else {
							$ret .= "<input type='checkbox' name='ajouter_mot[$id_groupe][]' value='$id_mot' $checked id='mot$id_mot'> ";
						}
					
						$ret .=  afficher_petits_logos_mots($id_mot);
						$ret .= "<B><label for='mot$id_mot'>$titre_mot</label></B><br>";
						if (strlen($descriptif_mot) > 0) $ret .= "$descriptif_mot<br>";
						$i++;
					}
					
					$ret .= "</font></td></tr></table>";
				
					$ret .= "</div>";
				}
			}
		}
	}
	///////


	if ($afficher_texte != "non"){
		$ret .= "\n<p><div class='spip_encadrer'>"._T('forum_lien_hyper')."<BR>\n";
		$ret .= _T('forum_page_url');
		$ret .= "<br>\n"._T('forum_titre');
		$ret .= "<br>\n<INPUT TYPE='text' CLASS='forml' NAME='nom_site_forum' VALUE=\"".entites_html($nom_site_forum)."\" SIZE='40'><BR>";

		if (!$url_site) $url_site = "http://";
		$ret .= "\n"._T('forum_url');
		$ret .= "<BR>\n<INPUT TYPE='text' CLASS='forml' NAME='url_site' VALUE=\"$url_site\" SIZE='40'></div>";

		$ret .= "\n<p><div class='spip_encadrer'>"._T('forum_qui_etes_vous')."<BR>";

		$nom_session = $GLOBALS['auteur_session']['nom'];
		$nom_email = $GLOBALS['auteur_session']['email'];

		if (!$auteur) $auteur = $nom_session;
		if (!$email_auteur) $email_auteur = $nom_email;

		$ret .= "\n"._T('forum_votre_nom');
		$ret .= "<BR>\n<INPUT TYPE='text' CLASS='forml' NAME='auteur' VALUE=\"".entites_html($auteur)."\" SIZE='40'><BR>\n";

		$ret .= _T('forum_votre_email');
		$ret .= "<br>\n<INPUT TYPE='text' CLASS='forml' NAME='email_auteur' VALUE=\"$email_auteur\" SIZE='40'></div>";
	}

	if ($afficher_texte !="non") $ret .= "\n<p><DIV ALIGN='right'><INPUT TYPE='submit' NAME='Valider' CLASS='spip_bouton' VALUE='"._T('forum_voir_avant')."'></DIV>";
	else  $ret .= "\n<p><DIV ALIGN='right'><INPUT TYPE='submit' NAME='Valider' CLASS='spip_bouton' VALUE='"._T('forum_valider')."'></DIV>";
	
	$ret .= "</FORM>";

	$ret .= '<'.'?php } ?'.'>';	// fin forums abo
	
	return $ret;
}



function ajout_forum() {
	global $texte, $titre, $nom_site_forum, $url_site, $auteur, $email_auteur, $retour_forum, $id_message, $confirmer;
	global $forum_id_rubrique, $forum_id_parent, $forum_id_article, $forum_id_breve, $forum_id_auteur, $forum_id_syndic, $alea, $hash;
	global $auteur_session;
	global $ajouter_mot, $new;
	global $REQUEST_URI, $HTTP_COOKIE_VARS, $REMOTE_ADDR;
	$afficher_texte = $GLOBALS['afficher_texte'];
	
	if (!$GLOBALS['db_ok']) {
		die ("<h4>"._T('forum_probleme_database')."</h4>");
	}

	$texte = addslashes($texte);
	$titre = addslashes($titre);
	$nom_site_forum = addslashes($nom_site_forum);
	$auteur = addslashes($auteur);
	$retour_forum = rawurldecode($retour_forum);
	$forums_publics = get_forums_publics($forum_id_article);

	if (strlen($confirmer) > 0 AND !verifier_action_auteur("ajout_forum $forum_id_rubrique $forum_id_parent $forum_id_article $forum_id_breve $forum_id_syndic $alea", $hash)) {
		@header("Location: $retour_forum");
		exit;
	}
	if (strlen($confirmer) > 0 AND ((strlen($texte) + strlen($titre) + strlen($nom_site_forum) + strlen($url_site) + strlen($auteur) + strlen($email_auteur)) > 20 * 1024)) {
		die ("<h4>"._T('forum_message_trop_long')."</h4>\n" .
		_T('forum_cliquer_retour', array('retour_forum' => $retour_forum))."<p>");
	}

	unset($where);
	if ($forum_id_article) $where[] = "id_article=$forum_id_article";
	if ($forum_id_rubrique) $where[] = "id_rubrique=$forum_id_rubrique";
	if ($forum_id_breve) $where[] = "id_breve=$forum_id_breve";
	if ($forum_id_parent) $where[] = "id_forum=$forum_id_parent";
	if ($where) {
		$query = "SELECT fichier FROM spip_forum_cache WHERE ".join(' OR ', $where);
		$result = spip_query($query);
		unset($fichiers);
		while ($row = spip_fetch_array($result)) {
			$fichier = $row["fichier"];
			@unlink("CACHE/$fichier");
			$fichiers[] = "'".$fichier."'";
		}
		if ($fichiers) {
			$fichiers = join(',', $fichiers);
			$query = "DELETE FROM spip_forum_cache WHERE fichier IN ($fichiers)";
			spip_query($query);
		}
	}


	switch($forums_publics) {
		case "non":
			$etat = "off";
			break;
		case "pri":
			$etat = "prop";
			break;
		default:
			$etat = "publie";
			break;
	}



	if (!$id_auteur) $id_auteur = $GLOBALS['auteur_session']['id_auteur'];
	$auteur_session = $GLOBALS['auteur_session']['email'];

	if ($new == "oui"){
		$nouveau_document = true;
		if ($HTTP_GET_VARS['titre']){
			$titre = "> ".rawurldecode($HTTP_GET_VARS['titre']);
		}
		$query_forum = "INSERT INTO spip_forum (date_heure, titre, ip, statut)
			VALUES (NOW(), \"".addslashes($titre)."\", \"$REMOTE_ADDR\", \"redac\")";
		$result_forum = spip_query($query_forum);
		$id_message = spip_insert_id();
	}

	// Ajouter les mots-cles
	$query_mots = "DELETE FROM spip_mots_forum WHERE id_forum='$id_message'";
	$result_mots = spip_query($query_mots);

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

	$query_forum = "UPDATE spip_forum
		SET id_parent = $forum_id_parent, id_rubrique =$forum_id_rubrique, id_article = $forum_id_article, id_breve = $forum_id_breve, id_syndic = \"$forum_id_syndic\",
			date_heure = NOW(), titre = \"$titre\", texte = \"$texte\", nom_site = \"$nom_site_forum\", url_site = \"$url_site\", auteur = \"$auteur\",
			email_auteur = \"$email_auteur\",  ip = \"$REMOTE_ADDR\", statut = \"redac\", id_auteur = \"$id_auteur\"
		WHERE id_forum = '$id_message'";

	$result_forum = spip_query($query_forum);


	if ($forums_publics == 'abo') {
		if ($auteur_session) {
			$statut = $auteur_session['statut'];

			if (!$statut OR $statut == '5poubelle') {
				die ("<h4>"._T('forum_acces_refuse'). "</h4>" . _T('forum_cliquer_retour', array('retour_forum' => $retour_forum)). "<p>");
			}
		}
		else {
			die ("<h4>"._T('forum_non_inscrit'). "</h4>" .
			_T('forum_cliquer_retour', array('retour_forum' => $retour_forum))."<p>");
		}
	}

	if (strlen($confirmer) > 0 OR ($afficher_texte=='non' AND $ajouter_mot)) {
		spip_query("UPDATE spip_forum SET statut=\"$etat\" WHERE id_forum='$id_message'");

		$texte = stripslashes($texte);
		$titre = stripslashes($titre);
		$auteur = stripslashes($auteur);

		// Envoi d'un mail aux auteurs
		$prevenir_auteurs = lire_meta("prevenir_auteurs");
		if ($prevenir_auteurs == "oui") {
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
				$query = "SELECT spip_auteurs.* FROM spip_auteurs, spip_auteurs_articles AS lien WHERE lien.id_article='$id_article' AND spip_auteurs.id_auteur=lien.id_auteur";
				$result = spip_query($query);

				while ($row = spip_fetch_array($result)) {
					$email_auteur = trim($row["email"]);
					if (strlen($email_auteur) < 3) continue;
					envoyer_mail($email_auteur, $sujet, $courr);
				}
			}
		}

		@header("Location: $retour_forum");
		exit;
	}
}

?>
