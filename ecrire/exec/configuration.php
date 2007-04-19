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

// http://doc.spip.org/@exec_configuration_dist
function exec_configuration_dist(){
	global $connect_statut, $connect_toutes_rubriques, $options, $spip_lang_left, $spip_lang_right,$changer_config, $spip_display;

	include_spip('inc/presentation');
	include_spip('inc/config');

	if ($connect_statut != '0minirezo') {
		echo _T('avis_non_acces_page');
		echo fin_gauche(), fin_page();
		exit;
	}

	if (!$connect_toutes_rubriques) {
		include_spip('inc/headers');
		redirige_par_entete(generer_url_ecrire('admin_tech','',true));
	}

	//
	// Modifications
	//

	init_config();
	lire_metas();

	pipeline('exec_init',array('args'=>array('exec'=>'configuration'),'data'=>''));

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_configuration'), "configuration", "configuration");
	
	echo "<br /><br /><br />\n";
	gros_titre(_T('titre_configuration'));
	echo barre_onglets("configuration", "contenu");
	
	
	debut_gauche();

	//
	// Le logo de notre site, c'est site{on,off}0.{gif,png,jpg}
	//
	if ($spip_display != 4) {
		$iconifier = charger_fonction('iconifier', 'inc');
		echo $iconifier('id_syndic', 0, 'configuration');
	}

	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'configuration'),'data'=>''));
	creer_colonne_droite();
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'configuration'),'data'=>''));
	debut_droite();

	echo avertissement_config();

	//
	// Afficher les options de config
	//
	$action = generer_action_auteur('config', '', generer_url_ecrire('configuration'));

	echo "<form action='$action' method='post'><div>", form_hidden($action);
	echo "<input type='hidden' name='changer_config' value='oui' />";

	echo configuration_bloc_votre_site();
	echo "<p>&nbsp;</p>";

	echo configuration_bloc_les_articles();
	echo "<br />\n";

	echo configuration_bloc_les_breves();
	echo "<br />\n";

	echo configuration_bloc_mots_cles();
	echo "<br />\n";

	echo configuration_bloc_syndication();
	echo "<br />\n";

	echo configuration_bloc_documents_joints();
	
	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'configuration'),'data'=>''));	
	echo "</div></form>";
	
	echo fin_gauche(), fin_page();
}

// http://doc.spip.org/@configuration_bloc_votre_site
function configuration_bloc_votre_site($bouton = true){
	debut_cadre_couleur("racine-site-24.gif");

	$adresse_site = entites_html($GLOBALS['meta']["adresse_site"]);

	$nom_site = entites_html($GLOBALS['meta']["nom_site"]);
	$email_webmaster = entites_html($GLOBALS['meta']["email_webmaster"]);
	$descriptif_site = entites_html($GLOBALS['meta']["descriptif_site"]);


	debut_cadre_relief("", false, "", _T('info_nom_site').aide ("confnom"));
	echo "<input type='text' name='nom_site' value=\"$nom_site\" size='40' class='forml' />";
	fin_cadre_relief();

	debut_cadre_relief("", false, "", _T('info_adresse_url'));
	echo "<input type='text' name='adresse_site' value=\"$adresse_site/\" size='40' class='forml' />";
	fin_cadre_relief();

	debut_cadre_relief("", false, "", _T('entree_description_site'));
	echo "<textarea name='descriptif_site' class='forml' rows='4' cols='40'>$descriptif_site</textarea>";
	fin_cadre_relief();

	if ($options == "avancees") {
		echo "<div>&nbsp;</div>";
	
		debut_cadre_relief("", false, "", _T('info_email_webmestre'));
		echo "<input type='text' name='email_webmaster' value=\"$email_webmaster\" size='40' class='formo' />";
		fin_cadre_relief();
	}

	if ($bouton)
		echo "<div style='text-align:right;'><input type='submit' name='Valider' value='"._T('bouton_enregistrer')."' class='fondo' /></div>";

	fin_cadre_couleur();
}

//
// Options des articles
//
// http://doc.spip.org/@configuration_bloc_les_articles
function configuration_bloc_les_articles(){
	global $spip_lang_left, $spip_lang_right;

	debut_cadre_trait_couleur("article-24.gif", false, "", _T('titre_les_articles'));
	//
	// Champs optionnels des articles
	//

	debut_cadre_relief("", false, "", _T('info_contenu_articles').aide ("confart"));

	$articles_surtitre = $GLOBALS['meta']["articles_surtitre"];
	$articles_soustitre = $GLOBALS['meta']["articles_soustitre"];
	$articles_descriptif = $GLOBALS['meta']["articles_descriptif"];
	$articles_chapeau = $GLOBALS['meta']["articles_chapeau"];
	$articles_ps = $GLOBALS['meta']["articles_ps"];
	$articles_redac = $GLOBALS['meta']["articles_redac"];
	$articles_urlref = $GLOBALS['meta']["articles_urlref"];

	echo "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";

	echo "<tr><td colspan='2' class='verdana2'>";
	echo _T('texte_contenu_articles');
	echo "</td></tr>";

	echo "<tr>";
	echo "<td align='$spip_lang_left' class='verdana2'>";
	echo _T('info_surtitre');
	echo "</td>";
	echo "<td align='$spip_lang_left' class='verdana2'>";
	echo afficher_choix('articles_surtitre', $articles_surtitre,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ");
	echo "</td></tr>\n";

	echo "<tr>";
	echo "<td align='$spip_lang_left' class='verdana2'>";
	echo _T('info_sous_titre');
	echo "</td>";
	echo "<td align='$spip_lang_left' class='verdana2'>";
	echo afficher_choix('articles_soustitre', $articles_soustitre,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ");
	echo "</td></tr>\n";

	echo "<tr>";
	echo "<td align='$spip_lang_left' class='verdana2'>";
	echo _T('info_descriptif');
	echo "</td>";
	echo "<td align='$spip_lang_left' class='verdana2'>";
	echo afficher_choix('articles_descriptif', $articles_descriptif,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ");
	echo "</td></tr>\n";

	echo "<tr>";
	echo "<td align='$spip_lang_left' class='verdana2'>";
	echo _T('info_chapeau_2');
	echo "</td>";
	echo "<td align='$spip_lang_left' class='verdana2'>";
	echo afficher_choix('articles_chapeau', $articles_chapeau,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ");
	echo "</td></tr>\n";

	echo "<tr>";
	echo "<td align='$spip_lang_left' class='verdana2'>";
	echo _T('info_post_scriptum_2');
	echo "</td>";
	echo "<td align='$spip_lang_left' class='verdana2'>";
	echo afficher_choix('articles_ps', $articles_ps,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ");
	echo "</td></tr>\n";

	echo "<tr>";
	echo "<td align='$spip_lang_left' class='verdana2'>";
	echo _T('info_date_publication_anterieure');
	echo "</td>";
	echo "<td align='$spip_lang_left' class='verdana2'>";
	echo afficher_choix('articles_redac', $articles_redac,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ");
	echo "</td></tr>\n";

	echo "<tr>";
	echo "<td align='$spip_lang_left' class='verdana2'>";
	echo _T('info_urlref');
	echo "</td>";
	echo "<td align='$spip_lang_left' class='verdana2'>";
	echo afficher_choix('articles_urlref', $articles_urlref,
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ");
	echo "</td></tr>\n";

	echo "<tr><td style='text-align: $spip_lang_right;' colspan='2'>";
	echo "<input type='submit' name='Valider' value='"._T('bouton_valider')."' class='fondo' />";
	echo "</td></tr>";
	echo "</table>";

	fin_cadre_relief();

	//
	// Articles post-dates
	//

	debut_cadre_relief("", false, "", _T('titre_publication_articles_post_dates').aide ("confdates"));

	$post_dates = $GLOBALS['meta']["post_dates"];

	echo "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	echo "<tr><td class='verdana2'>";
	echo _T('texte_publication_articles_post_dates');
	echo "</td></tr>";

	echo "<tr><td align='$spip_lang_left' class='verdana2'>";
	echo afficher_choix('post_dates', $post_dates,
		array('oui' => _T('item_publier_articles'),
			'non' => _T('item_non_publier_articles')));
	echo "</td></tr>\n";

	echo "<tr><td style='text-align:$spip_lang_right;'>";
	echo "<input type='submit' value='"._T('bouton_valider')."' class='fondo' />";
	echo "</td></tr>";
	echo "</table>\n";

	fin_cadre_relief();

	fin_cadre_trait_couleur();
}

//
// Actives/desactiver les breves
//
// http://doc.spip.org/@configuration_bloc_les_breves
function configuration_bloc_les_breves(){
	global $spip_lang_left, $spip_lang_right;
	debut_cadre_trait_couleur("breve-24.gif", false, "", _T('titre_breves').aide ("confbreves"));

	$activer_breves = $GLOBALS['meta']["activer_breves"];

	echo "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	echo "<tr><td class='verdana2'>";
	echo _T('texte_breves')."<br />\n";
	echo _T('info_breves');
	echo "</td></tr>";
	
	echo "<tr><td align='center' class='verdana2'>";
	echo afficher_choix('activer_breves', $activer_breves,
		array('oui' => _T('item_utiliser_breves'),
			'non' => _T('item_non_utiliser_breves')), " &nbsp; ");
	echo "</td></tr>\n";
	
	echo "<tr><td style='text-align:$spip_lang_right;'>";
	echo "<input type='submit' name='Valider' value='"._T('bouton_valider')."' class='fondo' />";
	echo "</td></tr>";
	echo "</table>\n";
	
	fin_cadre_trait_couleur();
}

//
// Gestion des mots-cles
//
// http://doc.spip.org/@configuration_bloc_mots_cles
function configuration_bloc_mots_cles(){
	global $spip_lang_left, $spip_lang_right;
	debut_cadre_trait_couleur("mot-cle-24.gif", false, "", _T('info_mots_cles'));

	$articles_mots = $GLOBALS['meta']["articles_mots"];
	$config_precise_groupes = $GLOBALS['meta']["config_precise_groupes"];
	$mots_cles_forums = $GLOBALS['meta']["mots_cles_forums"];
	$forums_publics = $GLOBALS['meta']["forums_publics"];

	echo "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	echo "<tr><td class='verdana2'>";
	echo _T('texte_mots_cles')."<br />\n";
	echo _T('info_question_mots_cles');
	echo "</td></tr>";

	echo "<tr>";
	echo "<td align='center' class='verdana2'>";


	echo bouton_radio("articles_mots", "oui", _T('item_utiliser_mots_cles'), $articles_mots == "oui", "changeVisible(this.checked, 'mots-config', 'block', 'none');");
	echo " &nbsp;";
	echo bouton_radio("articles_mots", "non", _T('item_non_utiliser_mots_cles'), $articles_mots == "non", "changeVisible(this.checked, 'mots-config', 'none', 'block');");

	//	echo afficher_choix('articles_mots', $articles_mots,
	//		array('oui' => _T('item_utiliser_mots_cles'),
	//			'non' => _T('item_non_utiliser_mots_cles')), "<br />");
	echo "</td></tr></table>";

	if ($articles_mots != "non") $style = "display: block;";
	else $style = "display: none;";
	
	echo "<div id='mots-config' style='$style'>";
	
	echo "<br />\n";
	debut_cadre_relief("", false, "", _T('titre_config_groupe_mots_cles'));

	echo "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	echo "<tr><td class='verdana2'>";
	echo _T('texte_config_groupe_mots_cles');
	echo "</td></tr>";

	echo "<tr>";
	echo "<td align='$spip_lang_left' class='verdana2'>";
	echo afficher_choix('config_precise_groupes', $config_precise_groupes,
		array('oui' => _T('item_utiliser_config_groupe_mots_cles'),
			'non' => _T('item_non_utiliser_config_groupe_mots_cles')));
	echo "</td></tr></table>";
	fin_cadre_relief();

	if ($forums_publics != "non"){
		echo "<br />\n";
		debut_cadre_relief("", false, "", _T('titre_mots_cles_dans_forum'));
		echo "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
		echo "<tr><td class='verdana2'>";
		echo _T('texte_mots_cles_dans_forum');
		echo "</td></tr>";

		echo "<tr>";
		echo "<td align='$spip_lang_left' class='verdana2'>";
		echo afficher_choix('mots_cles_forums', $mots_cles_forums,
			array('oui' => _T('item_ajout_mots_cles'),
				'non' => _T('item_non_ajout_mots_cles')));
		echo "</td></tr>";
		echo "</table>";
		fin_cadre_relief();
	}
	echo "</div>";	

	echo "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	echo "<tr><td style='text-align:$spip_lang_right;'>";
	echo "<input type='submit' name='Valider' value='"._T('bouton_valider')."' class='fondo' />";
	echo "</td></tr>";
	echo "</table>\n";

	fin_cadre_trait_couleur();
}

//
// Actives/desactiver systeme de syndication
//
// http://doc.spip.org/@configuration_bloc_syndication

// http://doc.spip.org/@configuration_bloc_syndication
function configuration_bloc_syndication(){
	global $spip_lang_left, $spip_lang_right;
	debut_cadre_trait_couleur("site-24.gif", false, "", _T('titre_referencement_sites').aide ("reference"));
	
	$activer_sites = $GLOBALS['meta']['activer_sites'];
	$activer_syndic = $GLOBALS['meta']["activer_syndic"];
	$proposer_sites = $GLOBALS['meta']["proposer_sites"];
	$visiter_sites = $GLOBALS['meta']["visiter_sites"];
	$moderation_sites = $GLOBALS['meta']["moderation_sites"];
	
	echo "\n<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	
	echo "<tr><td align='$spip_lang_left' class='verdana2'>";
	
	echo bouton_radio("activer_sites", "oui", _T('item_gerer_annuaire_site_web'), $activer_sites == "oui", "changeVisible(this.checked, 'config-site', 'block', 'none');");
	echo " &nbsp;";
	echo bouton_radio("activer_sites", "non", _T('item_non_gerer_annuaire_site_web'), $activer_sites == "non", "changeVisible(this.checked, 'config-site', 'none', 'block');");
	
	echo "</td></tr></table>\n";



	if ($activer_sites != 'non') $style = "display: block;";
	else $style = "display: none;";

	echo "<div id='config-site' style='$style'>";
	
	// Utilisateurs autorises a proposer des sites references
	//
	echo "<br />\n";
	debut_cadre_relief();
	echo "\n<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	echo "\n<tr><td style='color: #000000' class='verdana1 spip_x-small'>";
	echo _T('info_question_proposer_site');
	echo "\n<div style='text-align: center'><select name='proposer_sites' class='fondo' size='1'>\n";
	echo "<option".mySel('0',$proposer_sites).">"._T('item_choix_administrateurs')."</option>\n";
	echo "<option".mySel('1',$proposer_sites).">"._T('item_choix_redacteurs')."</option>\n";
	echo "<option".mySel('2',$proposer_sites).">"._T('item_choix_visiteurs')."</option>\n";
	echo "</select></div>\n";
	echo "</td></tr></table>\n";
	fin_cadre_relief();

	debut_cadre_relief("", false, "", _T('titre_syndication').aide ("rubsyn"));
	
	echo "\n<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	//
	// Reglage de la syndication
	//
	echo "<tr><td class='verdana2'>";
	echo _T('texte_syndication');
	echo "</td></tr>";

	echo "<tr><td align='$spip_lang_left' class='verdana2'>";

	echo bouton_radio("activer_syndic", "oui", _T('item_utiliser_syndication'), $activer_syndic == "oui", "changeVisible(this.checked, 'config-syndic', 'block', 'none');");
	echo "<br />\n";
	echo bouton_radio("activer_syndic", "non", _T('item_non_utiliser_syndication'), $activer_syndic == "non", "changeVisible(this.checked, 'config-syndic', 'none', 'block');");

	if ($activer_syndic != "non") $style = "display: block;";
	else $style = "display: none;";
			
	echo "<div id='config-syndic' style='$style'>";
		
	// Moderation par defaut des sites syndiques
	echo "<hr /><p style='text-align: $spip_lang_left'>";
	echo _T('texte_liens_sites_syndiques')."</p>";

	echo afficher_choix('moderation_sites', $moderation_sites,
		array('oui' => _T('item_bloquer_liens_syndiques'),
		'non' => _T('item_non_bloquer_liens_syndiques')));
	
	// Si indexation, activer/desactiver pages recuperees

	$activer_moteur = $GLOBALS['meta']["activer_moteur"];
	if ($activer_moteur == "oui") {
		echo "<hr /><p style='text-align: $spip_lang_left'>";
		echo _T('texte_utilisation_moteur_syndiques')." ";
		echo "</p><blockquote><p><i>"._T('texte_utilisation_moteur_syndiques_2')."</i></p></blockquote>";

		echo afficher_choix('visiter_sites', $visiter_sites,
			array('non' => _T('item_limiter_recherche'),
				'oui' => _T('item_non_limiter_recherche')));
	}
	echo "</div>";
		
	echo "</td></tr>\n";

	echo "</table>\n";

	fin_cadre_relief();
	echo "</div>";

	//
	// Gestion des flux RSS
	//

	debut_cadre_relief("feed.png", false, "", _T('ical_titre_rss'));
	
	echo "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	
	echo "<tr><td class='verdana2'>";
	echo _T('info_syndication_integrale_1',
			array('url' => generer_url_ecrire('synchro'),
			'titre' => _T("icone_suivi_activite"))
		),
		'<p>',
	  _T('info_syndication_integrale_2'),
	  '</p>';
	echo "</td></tr>";
	
	echo "<tr>";
	echo "<td align='$spip_lang_left' class='verdana2'>";
	echo afficher_choix('syndication_integrale', $GLOBALS['meta']["syndication_integrale"],
		array('oui' => _T('item_autoriser_syndication_integrale'),
			'non' => _T('item_non_autoriser_syndication_integrale')), "<br />\n");
	echo "</td></tr>";
	echo "</table>\n";
	
	fin_cadre_relief();

	echo "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	echo "<tr><td style='text-align:$spip_lang_right;'>";
	echo "<input type='submit' name='Valider' value='"._T('bouton_valider')."' class='fondo' />";
	echo "</td></tr>";
	echo "</table>\n";
	
	fin_cadre_trait_couleur();
}

//
// Gestion des documents joints
//
// http://doc.spip.org/@configuration_bloc_documents_joints
function configuration_bloc_documents_joints(){
	global $spip_lang_left, $spip_lang_right;
	debut_cadre_trait_couleur("doc-24.gif", false, "", _T('titre_documents_joints'));
	
	$documents_rubrique = $GLOBALS['meta']["documents_rubrique"];
	$documents_article = $GLOBALS['meta']["documents_article"];
	
	echo "<table border='0' cellspacing='1' cellpadding='3' width=\"100%\">";
	
	echo "<tr><td class='verdana2'>";
	echo _T('texte_documents_joints');
	echo _T('texte_documents_joints_2');
	echo "</td></tr>";
	
	echo "<tr>";
	echo "<td align='$spip_lang_left' class='verdana2'>";
	echo afficher_choix('documents_article', $documents_article,
		array('oui' => _T('item_autoriser_documents_joints'),
			'non' => _T('item_non_autoriser_documents_joints')), "<br />\n");
	echo "<br /><br />\n";
	echo afficher_choix('documents_rubrique', $documents_rubrique,
		array('oui' => _T('item_autoriser_documents_joints_rubriques'),
			'non' => _T('item_non_autoriser_documents_joints_rubriques')), "<br />\n");
	echo "</td></tr>";
	
	echo "<tr><td style='text-align:$spip_lang_right;'>";
	echo "<input type='submit' name='Valider' value='"._T('bouton_valider')."' class='fondo' />";
	echo "</td></tr>";
	echo "</table>\n";
	
	fin_cadre_trait_couleur();
}
?>