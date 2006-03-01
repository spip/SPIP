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


include_spip('inc/presentation');
include_spip('inc/texte');
include_spip('inc/rubriques');
include_spip('inc/logos');
include_spip('inc/mots');
include_spip('inc/date');
include_spip('inc/documents');
include_spip('inc/forum');
include_ecrire ("inc_abstract_sql");

  // 28 paremetres, qui dit mieux ?
  // moi ! elle en avait 61 en premiere approche

function exec_affiche_articles_dist($id_article, $ajout_auteur, $change_accepter_forum, $change_petition, $changer_virtuel, $cherche_auteur, $cherche_mot, $debut, $email_unique, $flag_auteur, $flag_editable, $langue_article, $message, $nom_select, $nouv_auteur, $nouv_mot, $rubrique_article, $site_obli, $site_unique, $supp_auteur, $supp_mot, $texte_petition, $titre_article, $lier_trad)
{
  global $options, $spip_display, $spip_lang_left, $spip_lang_right, $dir_lang;

$query = "SELECT * FROM spip_articles WHERE id_article='$id_article'";
$result = spip_query($query);

if ($row = spip_fetch_array($result)) {
	$id_article = $row["id_article"];
	$surtitre = $row["surtitre"];
	$titre = $row["titre"];
	$soustitre = $row["soustitre"];
	$id_rubrique = $row["id_rubrique"];
	$descriptif = $row["descriptif"];
	$nom_site = $row["nom_site"];
	$url_site = $row["url_site"];
	$chapo = $row["chapo"];
	$texte = $row["texte"];
	$ps = $row["ps"];
	$date = $row["date"];
	$statut_article = $row["statut"];
	$maj = $row["maj"];
	$date_redac = $row["date_redac"];
	$visites = $row["visites"];
	$referers = $row["referers"];
	$extra = $row["extra"];
	$id_trad = $row["id_trad"];
	$id_version = $row["id_version"];
}

// pour l'affichage du virtuel
unset($virtuel);
if (substr($chapo, 0, 1) == '=') {
	$virtuel = substr($chapo, 1);
}

if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2})", $date_redac, $regs)) {
	$annee_redac = $regs[1];
	$mois_redac = $regs[2];
	$jour_redac = $regs[3];
	$heure_redac = $regs[4];
	$minute_redac = $regs[5];
	if ($annee_redac > 4000) $annee_redac -= 9000;
}

if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2})", $date, $regs)) {
	$annee = $regs[1];
	$mois = $regs[2];
	$jour = $regs[3];
	$heure = $regs[4];
	$minute = $regs[5];
 }



debut_page("&laquo; $titre_article &raquo;", "documents", "articles");

debut_grand_cadre();

afficher_hierarchie($id_rubrique);

fin_grand_cadre();

//
// Affichage de la colonne de gauche
//

debut_gauche();

boite_info_articles($id_article, $statut_article, $visites, $id_version);

//
// Logos de l'article et Boites de configuration avancee
//

 boites_de_config_articles($id_article, $id_rubrique, $flag_editable,
			  $change_accepter_forum, $change_petition,
			  $email_unique, $site_obli, $site_unique,
			  $message, $texte_petition,
			  $changer_virtuel, $virtuel);
 
//
// Affichage de la colonne de droite
//

debut_droite();

changer_typo('','article'.$id_article);

debut_cadre_relief();

//
// Titre, surtitre, sous-titre
//

$modif = titres_articles($titre, $statut_article,$surtitre, $soustitre, $descriptif, $url_site, $nom_site, $flag_editable, $id_article);


echo "<div class='serif' align='$spip_lang_left'>";

dates_articles($id_article, $flag_editable, $statut_article, $date,$annee, $mois, $jour, $heure, $minute, $date_redac, $annee_redac, $mois_redac, $jour_redac, $heure_redac, $minute_redac);

//
// Liste des auteurs de l'article
//

echo "<a name='auteurs'></a>";

if ($flag_editable AND $options == 'avancees') {
	$bouton = bouton_block_invisible("auteursarticle");
}

debut_cadre_enfonce("auteur-24.gif", false, "", $bouton._T('texte_auteurs').aide ("artauteurs"));

//
// Recherche d'auteur
//

$supprimer_bouton_creer_auteur = rechercher_auteurs_articles($cherche_auteur, $id_article, $ajout_auteur, $flag_editable, $nouv_auteur, $supp_auteur);

//
// Afficher les auteurs
//

$les_auteurs = afficher_auteurs_articles($id_article, $flag_editable);

//
// Ajouter un auteur
//

ajouter_auteurs_articles($id_article, $les_auteurs, $flag_editable, $rubrique_article, $supprimer_bouton_creer_auteur);

fin_cadre_enfonce(false);

//
// Liste des mots-cles de l'article
//

if ($options == 'avancees' AND $GLOBALS['meta']["articles_mots"] != 'non') {
  formulaire_mots('articles', $id_article, $nouv_mot, $supp_mot, $cherche_mot, $flag_editable, generer_url_ecrire("articles","id_article=$id_article"));
}

 langues_articles($id_article, $langue_article, $flag_editable, $id_rubrique, $id_trad, $dir_lang, $nom_select, $lier_trad);


afficher_statut_articles($id_article, $rubrique_article, $statut_article);


 afficher_corps_articles($virtuel, $chapo, $texte, $ps, $extra);

if ($flag_editable) {
	echo "\n\n<div align='$spip_lang_right'><br />";
	bouton_modifier_articles($id_article, $modif,_T('texte_travail_article', $modif), "warning-24.gif", "");
	echo "</div>";
}

//
// Documents associes a l'article
//

 if ($spip_display != 4)
 afficher_documents_non_inclus($id_article, "article", $flag_editable);

//
// "Demander la publication"
//

if ($flag_auteur AND $statut_article == 'prepa') {
	echo "<P>";
	debut_cadre_relief();
	echo	"<center>",
		"<B>"._T('texte_proposer_publication')."</B>",
		aide ("artprop"),
		generer_url_post_ecrire("articles", "id_article=$id_article"),
		"<input type='hidden' name='statut_nouv' value='prop' />\n",
		"<input type='submit' class='fondo' value=\"", 
		_T('bouton_demande_publication'),
		"\" />\n",
		"</form>",
		"</center>";
	fin_cadre_relief();
}

echo "</div>";

echo "</div>";
fin_cadre_relief();

affiche_forums_article($id_article, $titre, $debut);

fin_page();

}

function boite_info_articles($id_article, $statut_article, $visites, $id_version)
{
  global $connect_statut, $options, $flag_revisions;

	debut_boite_info();
 
	echo "<div align='center'>\n";

	echo "<font face='Verdana,Arial,Sans,sans-serif' size='1'><b>"._T('info_numero_article')."</b></font>\n";
	echo "<br><font face='Verdana,Arial,Sans,sans-serif' size='6'><b>$id_article</b></font>\n";

	voir_en_ligne ('article', $id_article, $statut_article);


	$activer_statistiques = $GLOBALS['meta']["activer_statistiques"];

	if ($connect_statut == "0minirezo" AND $statut_article == 'publie' AND $visites > 0 AND $activer_statistiques != "non" AND $options == "avancees"){
	icone_horizontale(_T('icone_evolution_visites', array('visites' => $visites)), generer_url_ecrire("statistiques_visites","id_article=$id_article"), "statistiques-24.gif","rien.gif");
}

	if ((($GLOBALS['meta']["articles_versions"]=='oui') && $flag_revisions)
		AND $id_version>1 AND $options == "avancees") {
	icone_horizontale(_T('info_historique_lien'), generer_url_ecrire("articles_versions","id_article=$id_article"), "historique-24.gif", "rien.gif");
}

	// Correction orthographique
	if ($GLOBALS['meta']['articles_ortho'] == 'oui') {
		$js_ortho = "onclick=\"window.open(this.href, 'spip_ortho', 'scrollbars=yes, resizable=yes, width=740, height=580'); return false;\"";
		icone_horizontale(_T('ortho_verifier'), generer_url_ecrire("articles_ortho", "id_article=$id_article"), "ortho-24.gif", "rien.gif", 'echo', $js_ortho);
	}

	echo "</div>\n";
	
	fin_boite_info();
}

function boites_de_config_articles($id_article, $id_rubrique, $flag_editable,
				   $change_accepter_forum, $change_petition,
				   $email_unique, $site_obli, $site_unique,
				   $message, $texte_petition,
				   $changer_virtuel, $virtuel)
{
  global $connect_statut, $options, $spip_lang_right;

// Logos de l'article

	if ($id_article AND $flag_editable)
	  afficher_boite_logo('art', 'id_article', $id_article,
			      _T('logo_article').aide ("logoart"), _T('logo_survol'), 'articles');


//
// Boites de configuration avancee
//

	if ($options == "avancees" && $connect_statut=='0minirezo' && $flag_editable) {
	  echo "<p>";
	  debut_cadre_relief("forum-interne-24.gif");


	list($nb_forums) = spip_fetch_array(spip_query(
		"SELECT count(*) AS count FROM spip_forum
		WHERE id_article=$id_article
		AND statut IN ('publie', 'off', 'prop')"));

	list($nb_signatures) = spip_fetch_array(spip_query(
		"SELECT COUNT(*) AS count FROM spip_signatures
		WHERE id_article=$id_article
		AND statut IN ('publie', 'poubelle')"));


	$visible = $change_accepter_forum || $change_petition
		|| $nb_forums || $nb_signatures;

	echo "<div class='verdana1' style='text-align: center;'><b>";
	if ($visible)
		echo bouton_block_visible("forumpetition");
	else
		echo bouton_block_invisible("forumpetition");
	echo _T('bouton_forum_petition') .aide('confforums');
	echo "</b></div>";
	if ($visible)
		echo debut_block_visible("forumpetition");
	else
		echo debut_block_invisible("forumpetition");


	echo "<font face='Verdana,Arial,Sans,sans-serif' size='1'>\n";


	// Forums

	if ($nb_forums) {
		echo "<br />\n";
		icone_horizontale(_T('icone_suivi_forum', array('nb_forums' => $nb_forums)), generer_url_ecrire("articles_forum","id_article=$id_article"), "suivi-forum-24.gif", "");
	}

	// Reglage existant
	$forums_publics = get_forums_publics($id_article);

	// Modification du reglage ?
	if (isset($change_accepter_forum)
	AND $change_accepter_forum <> $forums_publics) {
		$forums_publics = $change_accepter_forum;
		modifier_forums_publics($id_article, $forums_publics);
	}

	// Afficher le formulaire de modification du reglage
	echo formulaire_modification_forums_publics($id_article, $forums_publics, generer_url_ecrire("articles","id_article=$id_article"));


	// Petitions

	if ($change_petition) {
		if ($change_petition == "on") {
			if (!$email_unique) $email_unique = "non";
			if (!$site_obli) $site_obli = "non";
			if (!$site_unique) $site_unique = "non";
			if (!$message) $message = "non";

			$texte_petition = addslashes($texte_petition);

			$query_pet = "REPLACE spip_petitions (id_article, email_unique, site_obli, site_unique, message, texte) ".
				"VALUES ($id_article, '$email_unique', '$site_obli', '$site_unique', '$message', '$texte_petition')";
			$result_pet = spip_query($query_pet);
		}
		else if ($change_petition == "off") {
			$query_pet = "DELETE FROM spip_petitions WHERE id_article=$id_article";
			$result_pet = spip_query($query_pet);
		}
	}

	$petition = spip_fetch_array(spip_query(
		"SELECT * FROM spip_petitions WHERE id_article=$id_article"));

	$email_unique=$petition["email_unique"];
	$site_obli=$petition["site_obli"];
	$site_unique=$petition["site_unique"];
	$message=$petition["message"];
	$texte_petition=$petition["texte"];

	echo "\n<form action='".generer_url_ecrire("articles","id_article=$id_article")."' method='POST'>";
	echo "\n<input type='hidden' name='id_article' value='$id_article'>";

	echo "<select name='change_petition'
		class='fondl' style='font-size:10px;'
		onChange=\"setvisibility('valider_petition', 'visible');\"
		>\n";

	if ($petition) {
		$menu = array(
			'on' => _T('bouton_radio_petition_activee'),
			'off'=> _T('bouton_radio_supprimer_petition')
		);
		$val_menu = 'on';
	} else {
		$menu = array(
			'off'=> _T('bouton_radio_pas_petition'),
			'on' => _T('bouton_radio_activer_petition')
		);
		$val_menu = 'off';
	}


	foreach ($menu as $val => $desc) {
		echo "<option";
		if ($val_menu == $val)
			echo " selected";
		echo " value='$val'>".$desc."</option>\n";
	}
	echo "</select>\n";

	if ($petition) {
		if ($nb_signatures) {
			echo "<br />\n";
			icone_horizontale($nb_signatures.'&nbsp;'. _T('info_signatures'), generer_url_ecrire("controle_petition","id_article=$id_article"), "suivi-petition-24.gif", "");
		}

		echo "<br />\n";

		if ($email_unique=="oui")
			echo "<input type='checkbox' name='email_unique' value='oui' id='emailunique' checked>";
		else
			echo "<input type='checkbox' name='email_unique' value='oui' id='emailunique'>";
		echo " <label for='emailunique'>"._T('bouton_checkbox_signature_unique_email')."</label><BR>";
		if ($site_obli=="oui")
			echo "<input type='checkbox' name='site_obli' value='oui' id='siteobli' checked>";
		else
			echo "<input type='checkbox' name='site_obli' value='oui' id='siteobli'>";
		echo " <label for='siteobli'>"._T('bouton_checkbox_indiquer_site')."</label><BR>";
		if ($site_unique=="oui")
			echo "<input type='checkbox' name='site_unique' value='oui' id='siteunique' checked>";
		else
			echo "<input type='checkbox' name='site_unique' value='oui' id='siteunique'>";
		echo " <label for='siteunique'>"._T('bouton_checkbox_signature_unique_site')."</label><BR>";
		if ($message=="oui")
			echo "<input type='checkbox' name='message' value='oui' id='message' checked>";
		else
			echo "<input type='checkbox' name='message' value='oui' id='message'>";
		echo " <label for='message'>"._T('bouton_checkbox_envoi_message')."</label>";

		echo "<P>"._T('texte_descriptif_petition')."&nbsp;:<BR>";
		echo "<TEXTAREA NAME='texte_petition' CLASS='forml' ROWS='4' COLS='10' wrap=soft>";
		echo entites_html($texte_petition);
		echo "</TEXTAREA><P>\n";

		echo "<P align='$spip_lang_right'>";
	}

	if (!$petition) echo "<span class='visible_au_chargement' id='valider_petition'>";
	echo "<INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='"._T('bouton_changer')."' STYLE='font-size:10px'>";
	if (!$petition)  echo "</span>";
	echo "</FORM>";

	echo "</font>";
	echo fin_block();

	fin_cadre_relief();

	// Redirection (article virtuel)
	debut_cadre_relief("site-24.gif");
	$visible = ($changer_virtuel || $virtuel);

	echo "<div class='verdana1' style='text-align: center;'><b>";
	if ($visible)
		echo bouton_block_visible("redirection");
	else
		echo bouton_block_invisible("redirection");
	echo _T('bouton_redirection');
	echo aide ("artvirt");
	echo "</b></div>";
	if ($visible)
		echo debut_block_visible("redirection");
	else
		echo debut_block_invisible("redirection");

	echo generer_url_post_ecrire("articles", "id_article=$id_article");
	echo "\n<INPUT TYPE='hidden' NAME='changer_virtuel' VALUE='oui'>";
	$virtuelhttp = ($virtuel ? "" : "http://");

	echo "<INPUT TYPE='text' NAME='virtuel' CLASS='formo' style='font-size:9px;' VALUE=\"$virtuelhttp$virtuel\" SIZE='40'><br>";
	echo "<font face='Verdana,Arial,Sans,sans-serif' size=2>";
	echo "(<b>"._T('texte_article_virtuel')."&nbsp;:</b> "._T('texte_reference_mais_redirige').")";
	echo "</font>";
	echo "<div align='$spip_lang_right'><INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='"._T('bouton_changer')."' STYLE='font-size:10px'></div>";
	echo "</form>";
	echo fin_block();

	fin_cadre_relief();
 }

//
// Articles dans la meme rubrique
//

meme_rubrique_articles($id_rubrique, $id_article, $options);

}

function   comparer_statut_articles($id_article, $statut_nouv, $statut_article, $statut_rubrique, $flag_auteur)
{
	if ($statut_rubrique) $ok_nouveau_statut = true;
	else if ($flag_auteur) {
		if ($statut_nouv == 'prop' AND $statut_article == 'prepa')
			$ok_nouveau_statut = true;
		else if ($statut_nouv == 'prepa' AND $statut_article == 'poubelle')
			$ok_nouveau_statut = true;
	}
	if ($ok_nouveau_statut) {
		spip_query("UPDATE spip_articles SET statut='$statut_nouv' WHERE id_article=$id_article");

		if ($statut_nouv == 'publie' AND $statut_nouv != $statut_article)
			spip_query("UPDATE spip_articles SET date=NOW() WHERE id_article=$id_article");

		$ok_nouveau_statut =  ($statut_nouv != $statut_article);

		// 'depublie' => invalider les caches
		if ($ok_nouveau_statut AND $statut_article == 'publie' 
		    AND $GLOBALS['invalider_caches']) {
		  include_spip('inc/invalideur');
		  suivre_invalideur("id='id_article/$id_article'");
		}
	}
	return $ok_nouveau_statut ;
}


function cron_articles($id_article, $statut, $statut_ancien)
{
	global $invalider_caches;

	calculer_rubriques();

	if ($statut == 'publie') {
		if ($GLOBALS['meta']['activer_moteur'] == 'oui') {
			include_spip("inc/indexation");
			marquer_indexer('article', $id_article);
		}
		include_spip('inc/mail');
		envoyer_mail_publication($id_article);
	}

	if ($statut_ancien == 'publie' AND $invalider_caches) {
	  	include_spip('inc/invalideur');
		suivre_invalideur("id='id_article/$id_article'");
	}

	if ($statut == "prop" AND $statut_ancien != 'publie') {
		include_spip('inc/mail');
		envoyer_mail_proposition($id_article);
	}
}

function meme_rubrique_articles($id_rubrique, $id_article, $options, $order='articles.date', $limit=30)
{
	global $spip_lang_right, $spip_lang_left;

	$vos_articles = spip_query("SELECT articles.id_article, articles.titre, articles.statut FROM spip_articles AS articles WHERE articles.id_rubrique='$id_rubrique' AND (articles.statut = 'publie' OR articles.statut = 'prop') AND articles.id_article != '$id_article' ORDER BY $order DESC LIMIT $limit");
	if (spip_num_rows($vos_articles) > 0) {
			echo "<div>&nbsp;</div>";
			echo "<div class='bandeau_rubriques' style='z-index: 1;'>";
			bandeau_titre_boite2(_T('info_meme_rubrique'), "article-24.gif");
			echo "<div class='plan-articles'>";
			while($row = spip_fetch_array($vos_articles)) {
				$ze_article = $row['id_article'];
				$ze_titre = typo($row['titre']);
				$ze_statut = $row['statut'];
				
				if ($options == "avancees") {
					$numero = "<div class='arial1' style='float: $spip_lang_right; color: black; padding-$spip_lang_left: 4px;'><b>"._T('info_numero_abbreviation')."$ze_article</b></div>";
				}
				echo "<a class='$ze_statut' style='font-size: 10px;' href='" . generer_url_ecrire("articles","id_article=$ze_article") . "'>$numero$ze_titre</a>";
			}
			echo "</div>";
			echo "</div>";
		}
}

function bouton_modifier_articles($id_article, $flag_modif, $mode, $ip, $im)
{
	if ($flag_modif) {
	  icone(_T('icone_modifier_article'), generer_url_ecrire("articles_edit","id_article=$id_article"), $ip, $im);
		echo "<font face='arial,helvetica,sans-serif' size='2'>$mode</font>";
		echo aide("artmodif");
	}
	else {
		icone(_T('icone_modifier_article'), generer_url_ecrire("articles_edit","id_article=$id_article"), "article-24.gif", "edit.gif");
	}

}

function titres_articles($titre, $statut_article,$surtitre, $soustitre, $descriptif, $url_site, $nom_site, $flag_editable, $id_article)
{
	global  $dir_lang, $spip_lang_left, $connect_id_auteur;

	$logo_statut = "puce-".puce_statut($statut_article).".gif";
	
	echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
	echo "<tr width='100%'><td width='100%' valign='top'>";
	
	if ($surtitre) {
		echo "<span $dir_lang><font face='arial,helvetica' size=3><b>";
		echo typo($surtitre);
		echo "</b></font></span>\n";
	 }
	 
	gros_titre($titre, $logo_statut);
	
	if ($soustitre) {
		echo "<span $dir_lang><font face='arial,helvetica' size=3><b>";
		echo typo($soustitre);
		echo "</b></font></span>\n";
	}
	
	
	if ($descriptif OR $url_site OR $nom_site) {
		echo "<p><div align='$spip_lang_left' style='padding: 5px; border: 1px dashed #aaaaaa; background-color: #e4e4e4;' $dir_lang>";
		echo "<font size=2 face='Verdana,Arial,Sans,sans-serif'>";
		$texte_case = ($descriptif) ? "{{"._T('info_descriptif')."}} $descriptif\n\n" : '';
		$texte_case .= ($nom_site.$url_site) ? "{{"._T('info_urlref')."}} [".$nom_site."->".$url_site."]" : '';
		echo propre($texte_case);
		echo "</font>";
		echo "</div>";
	}
	
	
	if ($statut_article == 'prop') {
		echo "<P><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2 COLOR='red'><B>"._T('text_article_propose_publication')."</B></FONT></P>";
	}
	
	echo "</td>";
	
	$flag_modif = false;
	
	if ($flag_editable) {
		echo "<td>". http_img_pack('rien.gif', " ", "width='5'") . "</td>\n";
		echo "<td align='center'>";
	
		// Recuperer les donnees de l'article
		if ($GLOBALS['meta']['articles_modif'] != 'non') {
			$query = "SELECT auteur_modif, UNIX_TIMESTAMP(date_modif) AS modification, UNIX_TIMESTAMP(NOW()) AS maintenant FROM spip_articles WHERE id_article='$id_article'";
			$result = spip_query($query);
	
			if ($row = spip_fetch_array($result)) {
				$auteur_modif = $row["auteur_modif"];
				$modification = $row["modification"];
				$maintenant = $row["maintenant"];
	
				$date_diff = floor(($maintenant - $modification)/60);
	
				if ($date_diff >= 0 AND $date_diff < 60 AND $auteur_modif > 0 AND $auteur_modif != $connect_id_auteur) {
					$query_auteur = "SELECT nom FROM spip_auteurs WHERE id_auteur='$auteur_modif'";
					$result_auteur = spip_query($query_auteur);
					if ($row_auteur = spip_fetch_array($result_auteur)) {
						$nom_auteur_modif = typo($row_auteur["nom"]);
					}
					$modif = array('nom_auteur_modif' => $nom_auteur_modif, 'date_diff' => $date_diff);
				}
			}
		}
		bouton_modifier_articles($id_article, $modif, _T('avis_article_modifie', $modif), "article-24.gif", "edit.gif");
	
		echo "</td>";
	 }
	echo "</tr></table>\n";
	echo "<div>&nbsp;</div>";
	return $modif;
}


function dates_articles($id_article, $flag_editable, $statut_article, $date, $annee, $mois, $jour, $heure, $minute, $date_redac, $annee_redac, $mois_redac, $jour_redac, $heure_redac, $minute_redac)
{

  global $spip_lang_left, $spip_lang_right, $options;

  if ($flag_editable AND $options == 'avancees') {
	debut_cadre_couleur();

	echo generer_url_post_ecrire("articles", "id_article=$id_article");

	if ($statut_article == 'publie') {

		echo "<div><b>";
		echo bouton_block_invisible("datepub");
		echo "<span class='verdana1'>"._T('texte_date_publication_article').'</span> ';
		echo majuscules(affdate($date)),
			"</b>".aide('artdate')."</div>";

		echo debut_block_invisible("datepub"),
		  "<div style='margin: 5px; margin-$spip_lang_left: 20px;'>",
		  afficher_jour($jour, "name='jour' size='1' class='fondl' onChange=\"setvisibility('valider_date', 'visible')\"", true),
		  afficher_mois($mois, "name='mois' size='1' class='fondl' onChange=\"setvisibility('valider_date', 'visible')\"", true),
		  afficher_annee($annee, "name='annee' size='1' class='fondl' onChange=\"setvisibility('valider_date', 'visible')\""),
		  ' - ',
		  afficher_heure($heure, "name='heure' size='1' class='fondl' onChange=\"setvisibility('valider_date', 'visible')\""),
		  afficher_minute($minute, "name='minute' size='1' class='fondl' onChange=\"setvisibility('valider_date', 'visible')\""),
		  "<span class='visible_au_chargement' id='valider_date'>",
		  " &nbsp; <INPUT TYPE='submit' NAME='Changer' CLASS='fondo' VALUE='"._T('bouton_changer')."'>",
		  "</span>",
		  "</div>",
		  fin_block();
	}
	else {
		echo "<div><b> <span class='verdana1'>"._T('texte_date_creation_article').'</span> ';
		echo majuscules(affdate($date))."</b>".aide('artdate')."</div>";
	}

	$possedeDateRedac=($annee_redac.'-'.$mois_redac.'-'.$jour_redac != '0000-00-00');
	if (($options == 'avancees' AND $GLOBALS['meta']["articles_redac"] != 'non')
	OR $possedeDateRedac) {
		if ($possedeDateRedac)
			$date_affichee = majuscules(affdate($date_redac))
#			." " ._T('date_fmt_heures_minutes', array('h' =>$heure_redac, 'm'=>$minute_redac))
			;
		else
			$date_affichee = majuscules(_T('jour_non_connu_nc'));

		echo "<div><b>";
		echo bouton_block_invisible('dateredac');
		echo "<span class='verdana1'>"
			. majuscules(_T('texte_date_publication_anterieure'))
			.'</span> '. $date_affichee ." ".aide('artdate_redac')."</b></div>";

		echo debut_block_invisible('dateredac');
		echo "<div style='margin: 5px; margin-$spip_lang_left: 20px;'>";
		echo '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
		echo '<tr><td align="$spip_lang_left">';
		echo '<input type="radio" name="avec_redac" value="non" id="avec_redac_on"';
		if (!$possedeDateRedac) echo ' checked="checked"';
		echo " onClick=\"setvisibility('valider_date_prec', 'visible')\"";
		echo ' /> <label for="avec_redac_on">'._T('texte_date_publication_anterieure_nonaffichee').'</label>';
		echo '<br /><input type="radio" name="avec_redac" value="oui" id="avec_redac_off"';
		if ($possedeDateRedac) echo ' checked="checked"';
		echo " onClick=\"setvisibility('valider_date_prec', 'visible')\"";
		echo ' /> <label for="avec_redac_off">'._T('bouton_radio_afficher').' :</label> ',
		afficher_jour($jour_redac, "name='jour_redac' class='fondl' onChange=\"setvisibility('valider_date_prec', 'visible')\"", true),
		afficher_mois($mois_redac, "name='mois_redac' class='fondl' onChange=\"setvisibility('valider_date_prec', 'visible')\"", true);
		echo "<input type='text' name='annee_redac' class='fondl' value='".$annee_redac."' size='5' maxlength='4' onClick=\"setvisibility('valider_date_prec', 'visible')\"/>";

		echo '<div align="center">',
		afficher_heure($heure_redac, "name='heure_redac' class='fondl' onChange=\"setvisibility('valider_date_prec', 'visible')\"", true),
		afficher_minute($minute_redac, "name='minute_redac' class='fondl' onChange=\"setvisibility('valider_date_prec', 'visible')\"", true),
		"</div>\n";

		echo '</td><td align="$spip_lang_right">';
		echo "<span class='visible_au_chargement' id='valider_date_prec'>";
		echo '<input type="submit" name="Changer" class="fondo" value="'._T('bouton_changer').'" />';
		echo "</span>";
		echo '</td></tr>';
		echo '</table>';
		echo "</div>";
		echo fin_block();
	}

	echo "</FORM>";
	fin_cadre_couleur();
 }
else {
	if ($statut_article == 'publie') $texte_date = _T('texte_date_publication_article');
	else $texte_date = _T('texte_date_creation_article');

	debut_cadre_couleur();
		echo "<div style='text-align:center;'><b> <span class='verdana1'>$texte_date</span> ";
		echo majuscules(affdate($date))."</b>".aide('artdate')."</div>";


		if ($annee_redac.'-'.$mois_redac.'-'.$jour_redac != '0000-00-00') {
			$date_affichee = ' : '.majuscules(affdate($date_redac));		
			echo "<div style='text-align:center;'><b> <span class='verdana1'>"._T(texte_date_publication_anterieure)."</span> ";
			echo $date_affichee."</b>".aide('artdate_redac')."</div>";
		}

	fin_cadre_couleur();
 }
}


function langues_articles($id_article, $langue_article, $flag_editable, $id_rubrique, $id_trad, $dir_lang, $nom_select, $lier_trad)
{

  global $connect_statut, $couleur_claire, $options, $connect_toutes_rubriques;

  if (($GLOBALS['meta']['multi_articles'] == 'oui')
	OR (($GLOBALS['meta']['multi_rubriques'] == 'oui') AND ($GLOBALS['meta']['gerer_trad'] == 'oui'))) {

	list($langue_article) = spip_fetch_array(spip_query("SELECT lang FROM spip_articles WHERE id_article=$id_article"));

	if ($GLOBALS['meta']['gerer_trad'] == 'oui')
		$titre_barre = _T('titre_langue_trad_article');
	else
		$titre_barre = _T('titre_langue_article');

	$titre_barre .= "&nbsp; (".traduire_nom_langue($langue_article).")";

	debut_cadre_enfonce('langues-24.gif', false, "", bouton_block_invisible('languesarticle,ne_plus_lier,lier_traductions').$titre_barre);


	// Choix langue article
	if ($GLOBALS['meta']['multi_articles'] == 'oui' AND $flag_editable) {
		echo debut_block_invisible('languesarticle');

		$row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
		$langue_parent = $row['lang'];

		debut_cadre_couleur();
		echo "<div style='text-align: center;'>";
		echo menu_langues('changer_lang', $langue_article, _T('info_multi_cet_article').' ', $langue_parent);
		echo "</div>\n";
		fin_cadre_couleur();

		echo fin_block();
	}


	// Gerer les groupes de traductions
	if ($GLOBALS['meta']['gerer_trad'] == 'oui') {
		if ($flag_editable AND $supp_trad == 'oui') { // Ne plus lier a un groupe de trad
			spip_query("UPDATE spip_articles SET id_trad = '0' WHERE id_article = $id_article");

			// Verifier si l'ancien groupe ne comporte plus qu'un seul article. Alors mettre a zero.
			$result_autres_trad= spip_query("SELECT COUNT(id_article) AS total FROM spip_articles WHERE id_trad = $id_trad");
			if ($row = spip_fetch_array($result_autres_trad))
				$nombre_autres_trad = $row["total"];
			if ($nombre_autres_trad == 1)
				spip_query("UPDATE spip_articles SET id_trad = '0' WHERE id_trad = $id_trad");

			$id_trad = 0;
		}

		// Changer article de reference de la trad
		if ($id_trad_new = intval($id_trad_new)
		AND $id_trad_old = intval($id_trad_old)
		AND $connect_statut=='0minirezo'
		AND $connect_toutes_rubriques) { 
			spip_query("UPDATE spip_articles SET id_trad = $id_trad_new WHERE id_trad = $id_trad_old");
			$id_trad = $id_trad_new;
		}

		if ($flag_editable AND $lier_trad > 0) { // Lier a un groupe de trad
			$query_lier = "SELECT id_trad FROM spip_articles WHERE id_article=$lier_trad";
			$result_lier = spip_query($query_lier);
			if ($row = spip_fetch_array($result_lier)) {
				$id_lier = $row['id_trad'];

				if ($id_lier == 0) { // Si l'article vise n'a pas deja de traduction, creer nouveau id_trad
					$nouveau_trad = $lier_trad;
				}
				else {
					if ($id_lier == $id_trad) $err = "<div>"._T('trad_deja_traduit')."</div>";
					$nouveau_trad = $id_lier;
				}

				spip_query("UPDATE spip_articles SET id_trad = $nouveau_trad WHERE id_article = $lier_trad");
				if ($id_lier > 0) spip_query("UPDATE spip_articles SET id_trad = $nouveau_trad WHERE id_trad = $id_lier");
				spip_query("UPDATE spip_articles SET id_trad = $nouveau_trad WHERE id_article = $id_article");
				if ($id_trad > 0) spip_query("UPDATE spip_articles SET id_trad = $nouveau_trad WHERE id_trad = $id_trad");

				$id_trad = $nouveau_trad;
			}
			else
				$err .= "<div>"._T('trad_article_inexistant')."</div>";

			if ($err) echo "<font color='red' size=2' face='verdana,arial,helvetica,sans-serif'>$err</font>";
		}


		// Afficher la liste des traductions
		if ($id_trad != 0) {
			$query_trad = "SELECT id_article, titre, lang, statut FROM spip_articles WHERE id_trad = $id_trad";
			$result_trad = spip_query($query_trad);
			
			
			$table='';
			while ($row = spip_fetch_array($result_trad)) {
				$vals = '';
				$id_article_trad = $row["id_article"];
				$titre_trad = $row["titre"];
				$lang_trad = $row["lang"];
				$statut_trad = $row["statut"];

				changer_typo($lang_trad);
				$titre_trad = "<span $dir_lang>$titre_trad</span>";

				if ($ifond == 1) {
					$ifond = 0;
					$bgcolor = "white";
				} else {
					$ifond = 1;
					$bgcolor = $couleur_claire;
				}


				$vals[] = http_img_pack("puce-".puce_statut($statut_trad).'.gif', "", "width='7' height='7' border='0' NAME='statut'");
				
				if ($id_article_trad == $id_trad) {
				  $vals[] = http_img_pack('langues-12.gif', "", "width='12' height='12' border='0'");
					$titre_trad = "<b>$titre_trad</b>";
				} else {
				  if ($connect_statut=='0minirezo'
				  AND $connect_toutes_rubriques)
				  	$vals[] = "<a href='" . generer_url_ecrire("articles","id_article=$id_article&id_trad_old=$id_trad&id_trad_new=$id_article_trad") . "'>". 
				    http_img_pack('langues-off-12.gif', _T('trad_reference'), "width='12' height='12' border='0'", _T('trad_reference')) . "</a>";
				  else $vals[] = http_img_pack('langues-off-12.gif', "", "width='12' height='12' border='0'");
				}

				$ret .= "</td>";

				$s = typo($titre_trad);
				if ($id_article_trad != $id_article) 
					$s = "<a href='" . generer_url_ecrire("articles","id_article=$id_article_trad") . "'>$s</a>";
				if ($id_article_trad == $id_trad)
					$s .= " "._T('trad_reference');

				$vals[] = $s;
				$vals[] = traduire_nom_langue($lang_trad);
				$table[] = $vals;
			}

			// changer_typo($spip_lang); (probleme d'affichage rtl?)

			// bloc traductions
			if (count($vals) > 0) {

				echo "<div class='liste'>";
				bandeau_titre_boite2(_T('trad_article_traduction'),'');
				echo "<table width='100%' cellspacing='0' border='0' cellpadding='2'>";
				//echo "<tr bgcolor='#eeeecc'><td colspan='4' class='serif2'><b>"._T('trad_article_traduction')."</b></td></tr>";

				$largeurs = array(7, 12, '', 100);
				$styles = array('', '', 'arial2', 'arial2');
				afficher_liste($largeurs, $table, $styles);

				echo "</table>";
				echo "</div>";

			}

			changer_typo($langue_article);
		}

		echo debut_block_invisible('lier_traductions');

		echo "<table width='100%'><tr>";
		if ($flag_editable AND $options == "avancees" AND !$ret) {
			// Formulaire pour lier a un article
			echo "<td class='arial2' width='60%'>";

			echo "<form action='" . generer_url_ecrire("articles","id_article=$id_article") . "' method='post' style='margin:0px; padding:0px;'>";
			echo _T('trad_lier');
			echo "<div align='$spip_lang_right'><input type='text' class='fondl' name='lier_trad' size='5'> <INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondl'></div>";
			echo "</form>";
			echo "</td>\n";
			echo "<td background='' width='10'> &nbsp; </td>";
			echo "<td background='" . _DIR_IMG_PACK . "tirets-separation.gif' width='2'>". http_img_pack('rien.gif', " ", "width='2' height='2'") . "</td>";
			echo "<td background='' width='10'> &nbsp; </td>";
		}
		echo "<td>";
		icone_horizontale(_T('trad_new'), generer_url_ecrire("articles_edit","new=oui&lier_trad=$id_article&id_rubrique=$id_rubrique"), "traductions-24.gif", "creer.gif");
		echo "</td>";
		if ($flag_editable AND $options == "avancees" AND $ret) {
			echo "<td background='' width='10'> &nbsp; </td>";
			echo "<td background='" . _DIR_IMG_PACK . "tirets-separation.gif' width='2'>". http_img_pack('rien.gif', " ", "width='2' height='2'") . "</td>";
			echo "<td background='' width='10'> &nbsp; </td>";
			echo "<td>";
			icone_horizontale(_T('trad_delier'), generer_url_ecrire("articles","id_article=$id_article&supp_trad=oui"), "traductions-24.gif", "supprimer.gif");
			echo "</td>\n";
		}

		echo "</tr></table>";

		echo fin_block();
	}

	fin_cadre_enfonce();
  }
}

function add_auteur_article($id_article, $nouv_auteur)
{
	$query="DELETE FROM spip_auteurs_articles WHERE id_auteur='$nouv_auteur' AND id_article='$id_article'";
		$result=spip_query($query);
		$query="INSERT INTO spip_auteurs_articles (id_auteur,id_article) VALUES ('$nouv_auteur','$id_article')";
		$result=spip_query($query);
}

function rechercher_auteurs_articles($cherche_auteur, $id_article, $ajout_auteur, $flag_editable, $nouv_auteur, $supp_auteur)
{
  global $spip_lang_left;

  $supprimer_bouton_creer_auteur = false;

  if ($cherche_auteur) {
	echo "<P ALIGN='$spip_lang_left'>";
	$query = "SELECT id_auteur, nom FROM spip_auteurs";
	$result = spip_query($query);
	$table_auteurs = array();
	$table_ids = array();
	while ($row = spip_fetch_array($result)) {
		$table_auteurs[] = $row["nom"];
		$table_ids[] = $row["id_auteur"];
	}
	$resultat = mots_ressemblants($cherche_auteur, $table_auteurs, $table_ids);
	debut_boite_info();
	if (!$resultat) {
		echo "<B>"._T('texte_aucun_resultat_auteur', array('cherche_auteur' => $cherche_auteur)).".</B><BR>";
	}
	else if (count($resultat) == 1) {
		$ajout_auteur = 'oui';
		list(, $nouv_auteur) = each($resultat);
		echo "<B>"._T('texte_ajout_auteur')."</B><BR>";
		$query = "SELECT * FROM spip_auteurs WHERE id_auteur=$nouv_auteur";
		$result = spip_query($query);
		echo "<UL>";
		while ($row = spip_fetch_array($result)) {
			$id_auteur = $row['id_auteur'];
			$nom_auteur = $row['nom'];
			$email_auteur = $row['email'];
			$bio_auteur = $row['bio'];

			echo "<LI><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2><B><FONT SIZE=3>".typo($nom_auteur)."</FONT></B>";
			echo "</FONT>\n";
		}
		echo "</UL>";
	}
	else if (count($resultat) < 16) {
		reset($resultat);
		$les_auteurs = array();
		while (list(, $id_auteur) = each($resultat)) $les_auteurs[] = $id_auteur;
		if ($les_auteurs) {
			$les_auteurs = join(',', $les_auteurs);
			echo "<B>"._T('texte_plusieurs_articles', array('cherche_auteur' => $cherche_auteur))."</B><BR>";
			$query = "SELECT * FROM spip_auteurs WHERE id_auteur IN ($les_auteurs) ORDER BY nom";
			$result = spip_query($query);
			echo "<UL class='verdana1'>";
			while ($row = spip_fetch_array($result)) {
				$id_auteur = $row['id_auteur'];
				$nom_auteur = $row['nom'];
				$email_auteur = $row['email'];
				$bio_auteur = $row['bio'];

				echo "<li><b>".typo($nom_auteur)."</b>";

				if ($email_auteur) echo " ($email_auteur)";
				echo " | <A href='", generer_url_ecrire('articles', "id_article=$id_article&ajout_auteur=oui&nouv_auteur=$id_auteur#auteurs"),
				  "'>",_T('lien_ajouter_auteur'),"</A>";

				if (trim($bio_auteur)) {
					echo "<br />".couper(propre($bio_auteur), 100)."\n";
				}
				echo "</li>\n";
			}
			echo "</UL>";
		}
	}
	else {
		echo "<B>"._T('texte_trop_resultats_auteurs', array('cherche_auteur' => $cherche_auteur))."</B><BR>";
	}

	if ($GLOBALS['connect_statut'] == '0minirezo') {
		echo "<div style='width: 200px;'>";
		$retour = urlencode(generer_url_ecrire("articles","id_article=$id_article"));
		$titre = urlencode($cherche_auteur);
		icone_horizontale(_T('icone_creer_auteur'), generer_url_ecrire("auteur_infos","new=oui&ajouter_id_article=$id_article&titre=$titre&redirect=$retour"), "redacteurs-24.gif", "creer.gif");
		echo "</div> ";

		// message pour ne pas afficher le second bouton "creer un auteur"
		$supprimer_bouton_creer_auteur = true;
	}

	fin_boite_info();
	echo "<P>";

  }

//
// Appliquer les modifications sur les auteurs
//

  if ($ajout_auteur && $flag_editable) {
	if ($nouv_auteur > 0) {
		add_auteur_article($id_article, $nouv_auteur);
	}

	if ($GLOBALS['meta']['activer_moteur'] == 'oui') {
		include_spip("inc/indexation");
		marquer_indexer('article', $id_article);
	}
  }


  if ($supp_auteur && $flag_editable) {
	$query="DELETE FROM spip_auteurs_articles WHERE id_auteur='$supp_auteur' AND id_article='$id_article'";
	$result=spip_query($query);
	if ($GLOBALS['meta']['activer_moteur'] == 'oui') {
		include_spip("inc/indexation");
		marquer_indexer('article', $id_article);
	}
  }

  return $supprimer_bouton_creer_auteur;
}

function afficher_auteurs_articles($id_article, $flag_editable)
{
	global $connect_statut, $options,$connect_id_auteur;

	$les_auteurs = array();

	$query = "SELECT * FROM spip_auteurs AS auteurs, spip_auteurs_articles AS lien ".
	"WHERE auteurs.id_auteur=lien.id_auteur AND lien.id_article=$id_article ".
	"GROUP BY auteurs.id_auteur ORDER BY auteurs.nom";
	$result = spip_query($query);

	if (spip_num_rows($result)) {
		echo "<div class='liste'>";
		echo "<table width='100%' cellpadding='3' cellspacing='0' border='0' background=''>";
		$table = array();
		while ($row = spip_fetch_array($result)) {
			$vals = array();
			$id_auteur = $row["id_auteur"];
			$nom_auteur = $row["nom"];
			$email_auteur = $row["email"];
			if ($bio_auteur = attribut_html(propre(couper($row["bio"], 100))))
			  $bio_auteur = " TITLE=\"$bio_auteur\"";
			$url_site_auteur = $row["url_site"];
			$statut_auteur = $row["statut"];
			if ($row['messagerie'] == 'non' OR $row['login'] == '') $messagerie = 'non';
			
			$les_auteurs[] = $id_auteur;

			if ($connect_statut == "0minirezo") $aff_articles = "('prepa', 'prop', 'publie', 'refuse')";
			else $aff_articles = "('prop', 'publie')";

			$query2 = "SELECT COUNT(articles.id_article) AS compteur ".
			"FROM spip_auteurs_articles AS lien, spip_articles AS articles ".
			"WHERE lien.id_auteur=$id_auteur AND articles.id_article=lien.id_article ".
			"AND articles.statut IN $aff_articles GROUP BY lien.id_auteur";
			$result2 = spip_query($query2);
			if ($result2) list($nombre_articles) = spip_fetch_array($result2);
			else $nombre_articles = 0;

			$vals[] = bonhomme_statut($row);

			$vals[] = "<A href='" . generer_url_ecrire('auteurs_edit', "id_auteur=$id_auteur") . "' $bio_auteur>".typo($nom_auteur)."</A>";

			$vals[] = bouton_imessage($id_auteur);

		
		
		if ($email_auteur) $vals[] =  "<A href='mailto:$email_auteur'>"._T('email')."</A>";
		else $vals[] =  "&nbsp;";

		if ($url_site_auteur) $vals[] =  "<A href='$url_site_auteur'>"._T('info_site_min')."</A>";
		else $vals[] =  "&nbsp;";

		if ($nombre_articles > 1) $vals[] =  $nombre_articles.' '._T('info_article_2');
		else if ($nombre_articles == 1) $vals[] =  _T('info_1_article');
		else $vals[] =  "&nbsp;";

		if ($flag_editable AND ($connect_id_auteur != $id_auteur OR $connect_statut == '0minirezo') AND $options == 'avancees') {
		  $vals[] =  "<A href='" . generer_url_ecrire("articles","id_article=$id_article&supp_auteur=$id_auteur#auteurs") . "'>"._T('lien_retirer_auteur')."&nbsp;". http_img_pack('croix-rouge.gif', "X", "width='7' height='7' border='0' align='middle'") . "</A>";
		} else {
			$vals[] = "";
		}
		
		$table[] = $vals;
		}
	
	
	$largeurs = array('14', '', '', '', '', '', '');
	$styles = array('arial11', 'arial2', 'arial11', 'arial11', 'arial11', 'arial11', 'arial1');
	afficher_liste($largeurs, $table, $styles);

	
	echo "</table></div>\n";

	$les_auteurs = join(',', $les_auteurs);
	}
	return $les_auteurs ;
}


function ajouter_auteurs_articles($id_article, $les_auteurs, $flag_editable, $rubrique_article, $supprimer_bouton_creer_auteur)
{

	global $connect_statut, $options,$connect_id_auteur, $couleur_claire ;

	if (!($flag_editable AND $options == 'avancees')) return;

	echo debut_block_invisible("auteursarticle");

	$query = "SELECT * FROM spip_auteurs WHERE ";
	if ($les_auteurs) $query .= "id_auteur NOT IN ($les_auteurs) AND ";
	$query .= "statut!='5poubelle' AND statut!='6forum' AND statut!='nouveau' ORDER BY statut, nom";
	$result = spip_query($query);
	
	echo "<table width='100%'>";
	echo "<tr>";

	if ($connect_statut == '0minirezo'
	    AND acces_rubrique($rubrique_article)
	    AND $options == "avancees"
	    AND !$supprimer_bouton_creer_auteur) {
	echo "<td width='200'>";
	$retour = urlencode(generer_url_ecrire("articles","id_article=$id_article"));
	icone_horizontale(_T('icone_creer_auteur'), generer_url_ecrire("auteur_infos","new=oui&ajouter_id_article=$id_article&redirect=$retour"), "redacteurs-24.gif", "creer.gif");
	echo "</td>";
	echo "<td width='20'>&nbsp;</td>";
	}

	echo "<td>";


	if (spip_num_rows($result) > 0) {
		echo generer_url_post_ecrire("articles", "id_article=$id_article");;
		echo "<span class='verdana1'><B>"._T('titre_cadre_ajouter_auteur')."&nbsp; </B></span>\n";
		echo "<DIV><INPUT TYPE='Hidden' NAME='id_article' VALUE=\"$id_article\">";

		if (spip_num_rows($result) > 200) {
			echo "<INPUT TYPE='text' NAME='cherche_auteur' onClick=\"setvisibility('valider_ajouter_auteur','visible');\" CLASS='fondl' VALUE='' SIZE='20'>";
			echo "<span  class='visible_au_chargement' id='valider_ajouter_auteur'>";
			echo " <INPUT TYPE='submit' NAME='Chercher' VALUE='"._T('bouton_chercher')."' CLASS='fondo'>";
			echo "</span>";
		} else {
			echo "<INPUT TYPE='Hidden' NAME='ajout_auteur' VALUE='oui'>";
			echo "<SELECT NAME='nouv_auteur' SIZE='1' STYLE='width:150px;' CLASS='fondl' onChange=\"setvisibility('valider_ajouter_auteur','visible');\">";
			$group = false;
			$group2 = false;

			while ($row = spip_fetch_array($result)) {
				$id_auteur = $row["id_auteur"];
				$nom = $row["nom"];
				$email = $row["email"];
				$statut = $row["statut"];

				$statut=str_replace("0minirezo", _T('info_administrateurs'), $statut);
				$statut=str_replace("1comite", _T('info_redacteurs'), $statut);
				$statut=str_replace("6visiteur", _T('info_visiteurs'), $statut);
				
				$premiere = strtoupper(substr(trim($nom), 0, 1));

				if ($connect_statut != '0minirezo')
					if ($p = strpos($email, '@'))
					  $email = substr($email, 0, $p).'@...';
				if ($email)
					$email = " ($email)";

				if ($statut != $statut_old) {
					echo "\n<OPTION VALUE=\"x\">";
					echo "\n<OPTION VALUE=\"x\" style='background-color: $couleur_claire;'> $statut";
				}

				if ($premiere != $premiere_old AND ($statut != _T('info_administrateurs') OR !$premiere_old)) {
				  echo "\n<OPTION VALUE=\"x\">";
				}

				$texte_option = supprimer_tags(couper(typo("$nom$email"), 40));
				echo "\n<OPTION VALUE=\"$id_auteur\">&nbsp;&nbsp;&nbsp;&nbsp;$texte_option";
				$statut_old = $statut;
				$premiere_old = $premiere;
			}
			
			echo "</SELECT>";
			echo "<span  class='visible_au_chargement' id='valider_ajouter_auteur'>";
			echo " <INPUT TYPE='submit' NAME='Ajouter' VALUE="._T('bouton_ajouter')." CLASS='fondo'>";
			echo "</span>";
		}
		echo "</div></FORM>";
	}
	
	echo "</td></tr></table>";

	echo fin_block();
}

function afficher_corps_articles($virtuel, $chapo, $texte, $ps,  $extra)
{
  global $revision_nbsp, $activer_revision_nbsp, $champs_extra, $les_notes, $dir_lang;

	echo "\n\n<div align='justify' style='padding: 10px;'>";

	if ($virtuel) {
		debut_boite_info();
		echo _T('info_renvoi_article')." ".propre("<center>[->$virtuel]</center>");
		fin_boite_info();
	} else {
		$revision_nbsp = $activer_revision_nbsp;

		if (strlen($chapo) > 0) {
			echo "<div $dir_lang style='font-size: small;'><b>";
			echo propre($chapo);
			echo "</b></div>\n\n";
		}

		echo "<div $dir_lang style='font-size: small;'>";
#	echo reduire_image(propre($texte), 500,10000);
		echo propre($texte);
		echo "<br clear='both' />";
		echo "</div>";

		if ($ps) {
			echo debut_cadre_enfonce();
			echo "<div $dir_lang><font size=2 face='Verdana,Arial,Sans,sans-serif'>";
			echo justifier("<b>"._T('info_ps')."</b> ".propre($ps));
			echo "</font></div>";
			echo fin_cadre_enfonce();
		}
		$revision_nbsp = false;

		if ($les_notes) {
			echo debut_cadre_relief();
			echo "<div $dir_lang class='arial11'>";
			echo justifier("<b>"._T('info_notes')."&nbsp;:</b> ".$les_notes);
			echo "</div>";
			echo fin_cadre_relief();
		}
		
		if ($champs_extra AND $extra) {
			include_spip('inc/extra');
			extra_affichage($extra, "articles");
		}
	}
}

function affiche_forums_article($id_article, $titre, $debut, $mute=false)
{
  global $spip_lang_left;

  echo "<BR><BR>";

  $forum_retour = generer_url_ecrire("articles","id_article=$id_article", true);
  
  if (!$mute) {
    $tm = urlencode($titre);
    echo "\n<div align='center'>";
    icone(_T('icone_poster_message'), generer_url_ecrire("forum_envoi","statut=prive&adresse_retour=" . urlencode($forum_retour) . "&id_article=$id_article&titre_message=$tm"), "forum-interne-24.gif", "creer.gif");
    echo "</div>";
  }

  echo "<P align='$spip_lang_left'>";

  $result_forum = spip_query("SELECT COUNT(*) AS cnt FROM spip_forum WHERE statut='prive' AND id_article='$id_article' AND id_parent=0");

  $total = 0;
  if ($row = spip_fetch_array($result_forum)) $total = $row["cnt"];

  if (!$debut) $debut = 0;
  $total_afficher = 8;
  if ($total > $total_afficher) {
	echo "<div class='serif2' align='center'>";
	for ($i = 0; $i < $total; $i = $i + $total_afficher){
		$y = $i + $total_afficher - 1;
		if ($i == $debut)
			echo "<FONT SIZE=3><B>[$i-$y]</B></FONT> ";
		else
			echo "[<A href='" . generer_url_ecrire("articles","id_article=$id_article&debut=$i") . "'>$i-$y</A>] ";
	}
	echo "</div>";
}

	$result_forum = spip_query("SELECT * FROM spip_forum WHERE statut='prive' AND id_article='$id_article' AND id_parent=0 ORDER BY date_heure DESC LIMIT $total_afficher OFFSET $debut");

	afficher_forum($result_forum, $forum_retour, $mute);

	if (!$debut) $debut = 0;
	$total_afficher = 8;
	if ($total > $total_afficher) {
	  echo "<div class='serif2' align='center'>";
	  for ($i = 0; $i < $total; $i = $i + $total_afficher){
		$y = $i + $total_afficher - 1;
		if ($i == $debut)
			echo "<FONT SIZE=3><B>[$i-$y]</B></FONT> ";
		else
			echo "[<A href='" . generer_url_ecrire("articles","id_article=$id_article&debut=$i") . "'>$i-$y</A>] ";
	  }
	  echo "</div>";
	}

	echo "</div>\n";
}

function afficher_statut_articles($id_article, $rubrique_article, $statut_article)
{
  global $connect_statut;

  if ($connect_statut == '0minirezo' AND acces_rubrique($rubrique_article)) {
  	
    echo generer_url_post_ecrire("articles", "id_article=$id_article"),
	  debut_cadre_relief("", true),
      "\n<center>", "<B>",_T('texte_article_statut'),"</B>",
	  "\n<SELECT NAME='statut_nouv' SIZE='1' CLASS='fondl'\n",
	  "onChange=\"document.statut.src='",
	  _DIR_IMG_PACK,
	  "' + puce_statut(options[selectedIndex].value);",
	  " setvisibility('valider_statut', 'visible');\">\n",
	 "<OPTION" , mySel("prepa", $statut_article) ," style='background-color: white'>",_T('texte_statut_en_cours_redaction'),"</OPTION>\n",
	 "<OPTION" , mySel("prop", $statut_article) , " style='background-color: #FFF1C6'>",_T('texte_statut_propose_evaluation'),"</OPTION>\n",
	 "<OPTION" , mySel("publie", $statut_article) , " style='background-color: #B4E8C5'>",_T('texte_statut_publie'),"</OPTION>\n",
	 "<OPTION" , mySel("poubelle", $statut_article),
	   http_style_background('rayures-sup.gif') , '>' ,_T('texte_statut_poubelle'),"</OPTION>\n",
	 "<OPTION" , mySel("refuse", $statut_article) , " style='background-color: #FFA4A4'>",_T('texte_statut_refuse'),"</OPTION>\n",
	  "</SELECT>",
	  " &nbsp; ",
	  http_img_pack("puce-".puce_statut($statut_article).'.gif', "", "border='0' NAME='statut'"),
	  "  &nbsp; ";

	// echo "<noscript><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></noscript>";
	echo "<span class='visible_au_chargement' id='valider_statut'>";
	echo "<INPUT TYPE='submit' VALUE='"._T('bouton_valider')."' CLASS='fondo'>";
	echo "</span>";
	echo aide ("artstatut");
	echo "</center>";
	fin_cadre_relief();
	echo "</form>";
 }
}

//
// Reunit les textes decoupes parce que trop longs
//

function trop_longs_articles($texte_plus)
{
	$nb_texte = 0;
	while ($nb_texte ++ < count($texte_plus)+1){
		$texte_ajout .= ereg_replace("<!--SPIP-->[\n\r]*","",
					     $texte_plus[$nb_texte]);
	}
	return $texte_ajout;
}


function modif_langue_articles($id_article, $id_rubrique, $changer_lang)
{

// Appliquer la modification de langue
 if ($GLOBALS['meta']['multi_articles'] == 'oui') {
	list($langue_parent) = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=" . $id_rubrique));

	if ($changer_lang) {
		if ($changer_lang != "herit")
			spip_query("UPDATE spip_articles SET lang='".addslashes($changer_lang)."', langue_choisie='oui' WHERE id_article=$id_article");
		else
			spip_query("UPDATE spip_articles SET lang='".addslashes($langue_parent)."', langue_choisie='non' WHERE id_article=$id_article");
	}
 }
}

// Passer les images/docs en "inclus=non"

function inclus_non_articles($id_article)
{
$query = "SELECT docs.id_document FROM spip_documents AS docs, spip_documents_articles AS lien WHERE lien.id_article=$id_article AND lien.id_document=docs.id_document";
$result = spip_query($query);

while($row=spip_fetch_array($result)){
	$ze_doc[]=$row['id_document'];
}

if (count($ze_doc)>0){
	$ze_docs = join($ze_doc,",");
	spip_query("UPDATE spip_documents SET inclus='non' WHERE id_document IN ($ze_docs)");
}

}

function revisions_articles ($id_article, $id_secteur, $id_rubrique, $id_rubrique_old, $change_rubrique, $new, $champs) {
{
  global $connect_id_auteur, $flag_revisions, $champs_extra;

	// Stockage des versions : creer une premier version si non-existante
	if (($GLOBALS['meta']["articles_versions"]=='oui') && $flag_revisions) {
		include_spip('inc/revisions');
		if  ($new != 'oui') {
			$query = "SELECT id_article FROM spip_versions WHERE id_article=$id_article LIMIT 1";
			if (!spip_num_rows(spip_query($query))) {
				spip_log("version initiale de l'article $id_article");
				$select = join(", ", array_keys($champs));
				$query = "SELECT $select FROM spip_articles WHERE id_article=$id_article";
				$champs_originaux = spip_fetch_array(spip_query($query));
				$id_version = ajouter_version($id_article, $champs_originaux, _T('version_initiale'), 0);

				// Remettre une date un peu ancienne pour la version initiale 
				if ($id_version == 1) // test inutile ?
				spip_query("UPDATE spip_versions
				SET date=DATE_SUB(NOW(), INTERVAL 2 HOUR)
				WHERE id_article=$id_article AND id_version=1");
			}
		}
	}

	if ($champs_extra) {
		include_spip('inc/extra');
		$champs_extra = ", extra = '".addslashes(extra_recup_saisie("articles", $id_secteur))."'";
	}

	spip_query("UPDATE spip_articles SET surtitre='" .
		   addslashes($champs['surtitre']) .
		   "', titre='" .
		   addslashes($champs['titre']) .
		   "', soustitre='" .
		   addslashes($champs['soustitre']) .
		   "', id_rubrique=" .
		   intval($id_rubrique) .
		   ", descriptif='" .
		   addslashes($champs['descriptif']) .
		   "', chapo='" .
		   addslashes($champs['chapo']) .
		   "', texte='" .
		   addslashes($champs['texte']) .
		   "', ps='" .
		   addslashes($champs['ps']) .
		   "', url_site='" .
		   addslashes($champs['url_site']) .
		   "', nom_site='" .
		   addslashes($champs['nom_site']) .
		   "'$champs_extra WHERE id_article=$id_article");

	// Stockage des versions
	if (($GLOBALS['meta']["articles_versions"]=='oui') && $flag_revisions) {
		ajouter_version($id_article, $champs, '', $connect_id_auteur);
	}

	// Changer la langue heritee
	if ($id_rubrique != $id_rubrique_old) {
		propager_les_secteurs();
		$row = spip_fetch_array(spip_query("SELECT lang, langue_choisie FROM spip_articles WHERE id_article=$id_article"));
		$langue_old = $row['lang'];
		$langue_choisie_old = $row['langue_choisie'];

		if ($langue_choisie_old != "oui") {
			$row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
			$langue_new = $row['lang'];
			if ($langue_new != $langue_old) spip_query("UPDATE spip_articles SET lang = '$langue_new' WHERE id_article = $id_article");
		}
	}

	// marquer l'article (important pour les articles nouvellement crees)
	spip_query("UPDATE spip_articles SET date_modif=NOW(), auteur_modif=$connect_id_auteur WHERE id_article=$id_article");
	calculer_rubriques();
 }
}

function insert_article($id_parent, $new)
{
	if ($new!='oui')  redirige_par_entete("./");
	// Avec l'Ajax parfois id_rubrique vaut 0... ne pas l'accepter
	if (!$id_rubrique = intval($id_parent)) {
		list($id_rubrique) = spip_fetch_array(spip_query("SELECT id_rubrique FROM spip_rubriques WHERE id_parent=0 ORDER by 0+titre,titre LIMIT 1"));
	}

	$row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));

	$id_article = spip_abstract_insert("spip_articles",
			"(id_rubrique, statut, date, accepter_forum, lang, langue_choisie)", 
			"($id_rubrique, 'prepa', NOW(), '" .
				substr($GLOBALS['meta']['forums_publics'],0,3)
				. "', '"
				. ($row["lang"] ? $row["lang"] : $GLOBALS['meta']['langue_site'])
				. "', 'non')");
	return $id_article;
}
// Y a-t-il vraiment 56 variables determinant l'edition d'un article ?

function exec_articles_dist()
{
global $ajout_auteur, $annee, $annee_redac, $avec_redac, $champs_extra, $change_accepter_forum, $change_petition, $changer_lang, $changer_virtuel, $chapo, $cherche_auteur, $cherche_mot, $connect_id_auteur, $date, $date_redac, $debut, $descriptif, $email_unique, $heure, $heure_redac, $id_article, $id_article_bloque, $id_parent, $id_rubrique_old, $id_secteur, $jour, $jour_redac, $langue_article, $lier_trad, $message, $minute, $minute_redac, $mois, $mois_redac, $new, $nom_select, $nom_site, $nouv_auteur, $nouv_mot, $ps, $row, $site_obli, $site_unique, $soustitre, $statut_nouv, $supp_auteur, $supp_mot, $surtitre, $texte, $texte_petition, $texte_plus, $titre, $titre_article, $url_site, $virtuel; 

 $id_parent = intval($id_parent);
 if (!($id_article=intval($id_article))) {
   $id_article = insert_article($id_parent, $new);
   add_auteur_article($id_article, $connect_id_auteur);
 }

// aucun doc implicitement inclus au départ.

inclus_non_articles($id_article);


if ($row = spip_fetch_array(spip_query("SELECT statut, titre, id_rubrique FROM spip_articles WHERE id_article=$id_article"))) {
	$statut_article = $row['statut'];
	$titre_article = $row['titre'];
	$id_rubrique = $row['id_rubrique'];
	$statut_rubrique = acces_rubrique($id_rubrique);
	if ($titre_article=='') $titre_article = _T('info_sans_titre');
}
else {
	$statut_article = '';
	$statut_rubrique = false;
	$id_rubrique = '0';
	if ($titre=='') $titre = _T('info_sans_titre');
}

$flag_auteur = spip_num_rows(spip_query("SELECT id_auteur FROM spip_auteurs_articles WHERE id_article=$id_article AND id_auteur=$connect_id_auteur LIMIT 1"));

if (!$statut_nouv) {
	$ok_nouveau_statut = false;
	$flag_editable = ($statut_rubrique
		OR ($flag_auteur
			AND ($statut_article == 'prepa'
				OR $statut_article == 'prop' 
				OR $statut_article == 'poubelle')));
 } else {
	$ok_nouveau_statut = comparer_statut_articles($id_article, $statut_nouv, $statut_article, $statut_rubrique, $flag_auteur);
	$flag_editable = ($statut_rubrique OR ($flag_auteur AND ($statut_nouv == 'prepa' OR $statut_nouv == 'prop')));
}

if ($flag_editable) {

if ($jour) {
	$date = format_mysql_date($annee, $mois, $jour, $heure, $minute);
	spip_query("UPDATE spip_articles SET date='$date'
		WHERE id_article=$id_article");
	calculer_rubriques();
}

if ($jour_redac) {
	if ($annee_redac<>'' AND $annee_redac < 1001) $annee_redac += 9000;

	if ($avec_redac == 'non')
		$date_redac = format_mysql_date();
	else
		$date_redac = format_mysql_date(
			$annee_redac, $mois_redac, $jour_redac,
			$heure_redac, $minute_redac);

	spip_query("UPDATE spip_articles SET date_redac='$date_redac'
		WHERE id_article=$id_article");
}


modif_langue_articles($id_article, $id_rubrique, $changer_lang);

maj_documents($id_article, 'article');

if ($changer_virtuel) {
	$virtuel = eregi_replace("^http://$", "", trim($virtuel));
	if ($virtuel) $chapo = addslashes(corriger_caracteres("=$virtuel"));
	else $chapo = "";
	spip_query("UPDATE spip_articles SET chapo='$chapo' WHERE id_article=$id_article");
}

if ($titre) {

  // prendre en compte le changement eventuel, et seulement si autorise
	if ($id_parent AND ($flag_auteur OR acces_rubrique($id_parent))) {
		$id_rubrique = $id_parent;
	}
	$champs = array(
			'surtitre' => corriger_caracteres($surtitre),
			'titre' => ($titre_article=corriger_caracteres($titre)),
			'soustitre' => corriger_caracteres($soustitre),
			'descriptif' => corriger_caracteres($descriptif),
			'nom_site' => corriger_caracteres($nom_site),
			'url_site' => corriger_caracteres($url_site),
			'chapo' => corriger_caracteres($chapo),
			'texte' => corriger_caracteres(trop_longs_articles($texte_plus) . $texte),
			'ps' => corriger_caracteres($ps))  ;

	revisions_articles ($id_article, $id_secteur, $id_rubrique, $id_rubrique_old,
			    ($flag_auteur||$statut_rubrique),
			    $new, $champs);
	$id_article_bloque = $id_article;   // message pour inc_presentation

 }
 }

exec_affiche_articles_dist($id_article, $ajout_auteur, $change_accepter_forum, $change_petition, $changer_virtuel, $cherche_auteur, $cherche_mot, $debut, $email_unique, $flag_auteur, $flag_editable, $langue_article, $message, $nom_select, $nouv_auteur, $nouv_mot, $id_rubrique, $site_obli, $site_unique, $supp_auteur, $supp_mot, $texte_petition, $titre_article, $lier_trad);

// Taches lentes


if ($ok_nouveau_statut) {
  cron_articles($id_article, $statut_nouv, $statut_article);
}
    }    
?>
