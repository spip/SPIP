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
include_spip('inc/config');

// http://doc.spip.org/@exec_config_fonctions_dist
function exec_config_fonctions_dist()
{
  global $connect_statut, $connect_toutes_rubriques, $changer_config;
	if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
		echo _T('avis_non_acces_page');
		exit;

	}

	init_config();
	if ($changer_config == 'oui') appliquer_modifs_config();

	global $flag_revisions, $options ;

	pipeline('exec_init',array('args'=>array('exec'=>'config_fonctions'),'data'=>''));
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_config_fonctions'), "configuration", "configuration");

	echo "<br><br><br>";
	gros_titre(_T('titre_config_fonctions'));
	echo barre_onglets("configuration", "fonctions");

	debut_gauche();
	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'config_fonctions'),'data'=>''));
	creer_colonne_droite();
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'config_fonctions'),'data'=>''));
	debut_droite();
	lire_metas();

	echo generer_url_post_ecrire('config_fonctions');
	echo "<input type='hidden' name='changer_config' value='oui'>";

//
// Activer/desactiver la creation automatique de vignettes
//
	vignettes_config();

//
// Indexation pour moteur de recherche
//
	moteur_config();

//
// Activer les statistiques
//
	statistiques_config();

//
// Notification de modification des articles
//
	if ($options == "avancees") notification_config();

//
// Gestion des revisions des articles
//
	if ($flag_revisions AND $options == "avancees") versions_config();

//
// Correcteur d'orthographe
//
	correcteur_config();

//
// Previsualisation sur le site public
//
	previsu_config();

//
// Utilisation d'un proxy pour aller lire les sites syndiques
//
	if ($options == 'avancees') proxy_config();

//
// Creer fichier .htpasswd ?
//
	if ($options == "avancees") htpasswd_config();

//
// Creer fichier .htaccess dans les repertoires de documents 
//

/* if ($options == "avancees" AND !$REMOTE_USER ) htaccess_config();*/

	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'config_fonctions'),'data'=>''));
	echo "</form>";

	echo fin_page();
}


// http://doc.spip.org/@afficher_choix_vignette
function afficher_choix_vignette($process) {
	static $cpt_cellule = 0;
	global $couleur_foncee;
	//global $taille_preview;
	$taille_preview = 120;

	$border = ($process == $GLOBALS['meta']['image_process']);

	// Ici on va tester les capacites de GD independamment des tests realises
	// dans les images spip_image -- qui servent neanmoins pour la qualite
	/* if (function_exists('imageformats')) {
		
	} */

	$retour = '';

	if($cpt_cellule>=3) {
		$cpt_cellule = 0;
		$retour .= "\n</tr><tr>\n";
	}
	else {
		$cpt_cellule += 1;
	}

	$retour .= "<td  width='".($taille_preview+4)."'><div align='center' valign='bottom' width='".($taille_preview+4)."'".
	($border ? "style='border:2px;border-style: dotted; border-color: $couleur_foncee;'" : '').
	"><a href='" . generer_url_ecrire("config_fonctions", "image_process=$process"). 
	  "'><img src='". generer_url_action("tester", "arg=$process").
	  "' /></a><br />";
	$retour .= $border ? "<b>$process</b>" : "$process";
	$retour .= "</div></td>\n";
	return $retour;
}

// http://doc.spip.org/@vignettes_config
function vignettes_config()
{
  global $image_process, $spip_lang_left, $spip_lang_right;

	debut_cadre_trait_couleur("image-24.gif");

	debut_cadre_relief("", false, "", _T("info_image_process_titre"));

	echo "<p class='verdana2'>";
	echo _T('info_image_process');
	echo "</p>";
		// application du choix de vignette
	if ($image_process) {
			// mettre a jour les formats graphiques lisibles
		switch ($image_process) {
				case 'gd1':
				case 'gd2':
					$formats_graphiques = $GLOBALS['meta']['gd_formats_read'];
					break;
				case 'netpbm':
					$formats_graphiques = $GLOBALS['meta']['netpbm_formats'];
					break;
				case 'convert':
				case 'imagick':
					$formats_graphiques = 'gif,jpg,png';
					break;
				default: #debug
					$formats_graphiques = '';
					$image_process = 'non';
					break;
			}
		ecrire_meta('formats_graphiques', $formats_graphiques);
		ecrire_meta('image_process', $image_process);
		ecrire_metas();
	} else 	$formats_graphiques = $GLOBALS['meta']["formats_graphiques"];

	echo "<table width='100%' align='center'><tr>";
	$nb_process = 0;

	// Tester les formats
	if ( /* GD disponible ? */
	function_exists('ImageGif')
	OR function_exists('ImageJpeg')
	OR function_exists('ImagePng')
	) {
		$nb_process ++;
		echo afficher_choix_vignette($p = 'gd1');

		if (function_exists("ImageCreateTrueColor")) {
			echo afficher_choix_vignette($p = 'gd2');
			$nb_process ++;
		}
	}

	if (_PNMSCALE_COMMAND!='') {
		echo afficher_choix_vignette($p = 'netpbm');
		$nb_process ++;
	}

	if (function_exists('imagick_readimage')) {
		echo afficher_choix_vignette('imagick');
		$nb_process ++;
	}

	if (_CONVERT_COMMAND!='') {
		echo afficher_choix_vignette($p = 'convert');
		$nb_process ++;
	}

	$cell = $nb_process%3?(3-$nb_process%3):0;
	while($cell--)
		echo "\n".'<td>&nbsp;</td>';

	echo "</tr></table>\n";
	
	echo "<p class='verdana2'>";
	echo _T('info_image_process2');
	echo "</p>";
	
	fin_cadre_relief();


	//
	// Une fois le process choisi, proposer vignettes
	//
	
	$creer_preview = $GLOBALS['meta']["creer_preview"];
	$taille_preview = $GLOBALS['meta']["taille_preview"];
	if ($taille_preview < 10) $taille_preview = 120;

	if (strlen($formats_graphiques) > 0) {
		debut_cadre_trait_couleur("", false, "", _T('info_generation_miniatures_images'));
		
		echo "<p class='verdana2'>";
		echo _T('info_ajout_image');
		echo "</p>\n";
		echo "<p class='verdana2'>";


		$block = "'block', 'none'"; 
		echo bouton_radio("creer_preview", "oui", _T('item_choix_generation_miniature'), $creer_preview == "oui", "changeVisible(this.checked, 'config-preview', $block);");

		if ($creer_preview == "oui") $style = "display: block;";
		else $style = "display: none;";
	
			echo "<div id='config-preview' class='verdana2' style='$style margin-$spip_lang_left: 40px;'>"._T('info_taille_maximale_vignette');
			echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<INPUT TYPE='text' NAME='taille_preview' VALUE='$taille_preview' class='fondl' size=5>";
			echo " "._T('info_pixels').'<br /><br /></div>';
			
		$block= "'none', 'block'";
		echo bouton_radio("creer_preview", "non", _T('item_choix_non_generation_miniature'), $creer_preview != "oui", "changeVisible(this.checked, 'config-preview', $block);");
	
		echo "<div style='text-align:$spip_lang_right'><INPUT TYPE='submit' VALUE='"._T('bouton_valider')."' CLASS='fondo'></div>";
		
		fin_cadre_trait_couleur();
	}


	fin_cadre_trait_couleur();

	echo "<p>";
}

// http://doc.spip.org/@moteur_config
function moteur_config()
{
	global $spip_lang_right;

	debut_cadre_trait_couleur("racine-site-24.gif", false, "", _T('info_moteur_recherche').aide ("confmoteur"));


	$activer_moteur = $GLOBALS['meta']["activer_moteur"];

	echo "<div class='verdana2'>";
		echo _T('info_question_utilisation_moteur_recherche');
	echo "</div>";

	echo "<div class='verdana2'>";
	afficher_choix('activer_moteur', $activer_moteur,
		array('oui' => _T('item_utiliser_moteur_recherche'),
			'non' => _T('item_non_utiliser_moteur_recherche')), ' &nbsp; ');
	echo "</div>";
	echo "<div style='text-align:$spip_lang_right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></div>";

	fin_cadre_trait_couleur();
		
	echo "<p>";
}

// http://doc.spip.org/@statistiques_config
function statistiques_config()
{
	global $spip_lang_right;

	debut_cadre_trait_couleur("statistiques-24.gif", false, "", _T('info_forum_statistiques').aide ("confstat"));

	$activer_statistiques = $GLOBALS['meta']["activer_statistiques"];

	echo "<div class='verdana2'>";
	echo _T('info_question_gerer_statistiques');
	echo "</div>";

	echo "<div class='verdana2'>";
afficher_choix('activer_statistiques', $activer_statistiques,
	array('oui' => _T('item_gerer_statistiques'),
		'non' => _T('item_non_gerer_statistiques')), ' &nbsp; ');
	echo "</div>";
	echo "<div style='text-align:$spip_lang_right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></div>";

	fin_cadre_trait_couleur();
	
	echo "<p>";
}

// http://doc.spip.org/@notification_config
function notification_config()
{
	global $spip_lang_right;

	debut_cadre_trait_couleur("article-24.gif", false, "", _T('info_travail_colaboratif').aide("artmodif"));
	$articles_modif = $GLOBALS['meta']["articles_modif"];


	echo "<div class='verdana2'>";
	echo _T('texte_travail_collaboratif');
	echo "</div>";

	echo "<div class='verdana2'>";
	afficher_choix('articles_modif', $articles_modif,
		array('oui' => _T('item_activer_messages_avertissement'),
			'non' => _T('item_non_activer_messages_avertissement')));
	echo "</div>";
	echo "<div style='text-align:$spip_lang_right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></div>";

	fin_cadre_trait_couleur();

	echo "<p>";
}

// http://doc.spip.org/@versions_config
function versions_config()
 {
	global $spip_lang_right;

	debut_cadre_trait_couleur("historique-24.gif", false, "", _T('info_historique_titre').aide("suivimodif"));
	$articles_versions = $GLOBALS['meta']["articles_versions"];


	echo "<div class='verdana2'>";
	echo _T('info_historique_texte');
	echo "</div>";

	echo "<div class='verdana2'>";
	afficher_choix('articles_versions', $articles_versions,
		array('oui' => _T('info_historique_activer'),
			'non' => _T('info_historique_desactiver')));
	echo "</div>";
	echo "<div style='text-align:$spip_lang_right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></div>";
	
	fin_cadre_trait_couleur();

	echo "<p>";
}


// http://doc.spip.org/@correcteur_config
function correcteur_config()
{

	global $spip_lang_right;
	debut_cadre_trait_couleur("ortho-24.gif", false, "", _T('ortho_orthographe').aide("corrortho"));
	$articles_ortho = $GLOBALS['meta']["articles_ortho"];

	echo "<div class='verdana2'>";
	echo _T('ortho_avis_privacy');
	echo "</div>";

	echo "<div class='verdana2'>";
	echo "<blockquote class='spip'>";
	echo _T('ortho_avis_privacy2');
	echo "</blockquote>\n";
	echo "</div>";

	echo "<div class='verdana2'>";
	afficher_choix('articles_ortho', $articles_ortho,
		array('oui' => _T('info_ortho_activer'),
			'non' => _T('info_ortho_desactiver')));
	echo "</div>";
	echo "<div style='text-align:$spip_lang_right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></div>";
	
	fin_cadre_trait_couleur();

	echo "<p>";
}

// http://doc.spip.org/@previsu_config
function previsu_config()
{
	global $spip_lang_right;

	debut_cadre_trait_couleur("naviguer-site.png", false, "", _T('previsualisation').aide("previsu"));
	$preview = $GLOBALS['meta']["preview"];
	# non = personne n'est autorise a previsualiser (defaut)
	# oui = les admins
	# 1comite = admins et redacteurs

	echo "<div class='verdana2'>";
	echo _T('info_preview_texte');
	echo "</div>";

	echo "<div class='verdana2'>";
	afficher_choix('preview', $preview,
		array('oui' => _T('info_preview_admin'),
			'1comite' => _T('info_preview_comite'),
			'non' => _T('info_preview_desactive')
		)
	);
	echo "</div>";
		echo "<div style='text-align:$spip_lang_right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></div>";
	
fin_cadre_trait_couleur();

	echo "<p>";
}

// http://doc.spip.org/@proxy_config
function proxy_config()
{
	global $spip_lang_right, $spip_lang_left;

	debut_cadre_trait_couleur("base-24.gif", false, "", _T('info_sites_proxy').aide ("confhttpproxy"));

	// Masquer un eventuel password authentifiant
	if ($http_proxy = $GLOBALS['meta']["http_proxy"]) {
		include_spip('inc/distant');
		$http_proxy=entites_html(no_password_proxy_url($http_proxy));
	}

	echo "<div class='verdana2'>";
	echo _T('texte_proxy');
	echo "</div>";

	echo "<div class='verdana2'>";
	echo "<INPUT TYPE='text' NAME='http_proxy' VALUE='$http_proxy' size='40' class='forml'>";

	if ($http_proxy) {
		echo "<p align='$spip_lang_left'><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=2 COLOR='#000000'>"
			. _T('texte_test_proxy');
		echo "</p>";

		echo "<p>";
		echo "<INPUT TYPE='text' NAME='test_proxy' VALUE='http://www.spip.net/' size='40' class='forml'>";
		echo "</p>";
		echo "<div style='text-align: $spip_lang_right;'><INPUT TYPE='submit' NAME='tester_proxy' VALUE='"._T('bouton_test_proxy')."' CLASS='fondo'></div>";

	}


	echo "</div>";
	echo "<div style='text-align:$spip_lang_right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></div>";
	
	fin_cadre_trait_couleur();
	echo "<p>";
}

// http://doc.spip.org/@htpasswd_config
function htpasswd_config()
{
	global $spip_lang_right;

	include_spip('inc/acces');
	ecrire_acces();

	debut_cadre_trait_couleur("cadenas-24.gif", false, "",
		_T('info_fichiers_authent'));

	$creer_htpasswd = $GLOBALS['meta']["creer_htpasswd"];

	echo "<div class='verdana2'>", _T('texte_fichier_authent', array('dossier' => '<tt>'.joli_repertoire(_DIR_TMP).'</tt>')), "</div>";

	echo "<div class='verdana2'>";
	afficher_choix('creer_htpasswd', $creer_htpasswd,
		array('oui' => _T('item_creer_fichiers_authent'),
			'non' =>  _T('item_non_creer_fichiers_authent')),
		' &nbsp; ');
	echo "</div>";
	echo "<div style='text-align:$spip_lang_right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></div>";
	
	fin_cadre_trait_couleur();

	echo "<p>";
}

##### n'est pas encore utilise #######
// http://doc.spip.org/@htaccess_config
function htaccess_config()
 {

	global $spip_lang_right;

	debut_cadre_trait_couleur("cadenas-24.gif", false, "", 
			  _L("Acc&egrave;s aux document joints par leur URL"));
#	include_spip('inc/acces'); vient d'etre fait
	$creer_htaccess = gerer_htaccess();

	echo "<div class='verdana2'>";
	echo _L("Cette option interdit la lecture des documents joints si le texte auquel ils se rattachent n'est pas publi&eacute");
	echo "</div>";

	echo "<div class='verdana2'>";
	afficher_choix('creer_htaccess', $creer_htaccess,
		       array('oui' => _L("interdire la lecture"),
			     'non' => _L("autoriser la lecture")),
		       ' &nbsp; ');
	echo "</div>";
	echo "<div style='text-align:$spip_lang_right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'></div>";
	
	fin_cadre_trait_couleur();

	echo "<p>";
}

?>
