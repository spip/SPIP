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

include_spip('inc/presentation');
include_spip('inc/date');


// http://doc.spip.org/@exec_message_edit_dist
function exec_message_edit_dist()
{
	global  $connect_id_auteur, $connect_statut,   $spip_lang_rtl;

	$id_message =  intval(_request('id_message'));
	$dest = intval(_request('dest'));

	if (_request('new')=='oui') {
		$onfocus = " onfocus=\"if(!antifocus){this.value='';antifocus=true;}\"";
	} else $onfocus = '';

	$row = spip_fetch_array(spip_query("SELECT * FROM spip_messages WHERE id_message=$id_message"));

	$id_message = $row['id_message'];
	$date_heure = $row["date_heure"];
	$date_fin = $row["date_fin"];
	$titre = entites_html($row["titre"]);
	$texte = entites_html($row["texte"]);
	$type = $row["type"];
	$statut = $row["statut"];
	$rv = $row["rv"];
	$expediteur = $row["id_auteur"];

	if (!($expediteur == $connect_id_auteur OR ($type == 'affich' AND $connect_statut == '0minirezo'))) {
		echo minipres(_T('avis_non_acces_message'));
		exit;
	}

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_message_edit'), "accueil", "messagerie");

	if ($type == 'normal') {
	  $le_type = _T('bouton_envoi_message_02');
	  $logo = "message";
	}
	if ($type == 'pb') {
	  $le_type = _T('bouton_pense_bete');
	  $logo = "pense-bete";
	}
	if ($type == 'affich') {
	  $le_type = _T('bouton_annonce');
	  $logo = "annonce";
	}


	debut_gauche();
	
	if($type == 'normal' AND $dest) {
		$nom = spip_fetch_array(spip_query("SELECT nom, email FROM spip_auteurs WHERE id_auteur=$dest"));
		if (strlen($nom['email']) > 3) {
			echo "<div align='center'>";
			icone(_T('info_envoyer_message_prive'), "mailto:".$nom['email'], "envoi-message-24.gif");
			echo "</div>";
		}
	}

	debut_droite();

	echo "<div class='arial2'>";
	echo "<span style='color:green' class='verdana1 spip_small'><b>$le_type</b></span>";
	
	echo generer_url_post_ecrire('message',"id_message=$id_message");
	if ($type == "affich")
		echo "<p style='color:red;' class='verdana1 spip_x-small'>", _T('texte_message_edit'),"</p>";
	
	echo "\n<p><input type='hidden' name='modifier_message' value='oui'/>";
	echo "\n<input type='hidden' name='id_message' value='$id_message'/>";
	echo "\n<input type='hidden' name='changer_rv' value='$id_message'/>";
	echo _T('texte_titre_obligatoire')."<br />\n";
	echo "<input type='text' class='formo' name='titre' value=\"$titre\" size='40' $onfocus />";

	if (!$dest) {
		if ($type == 'normal') {
		  echo "</p><p><b>"._T('info_nom_destinataire')."</b><br />";
		  echo "<input type='text' class='formo' name='cherche_auteur' value='' size='40'/>";
		}
	} else {
		$nom = spip_fetch_array(spip_query("SELECT nom FROM spip_auteurs WHERE id_auteur=$dest"));
		echo "</p><p><b>",
		  _T('info_nom_destinataire'),
		  "</b>&nbsp;:&nbsp;&nbsp; ",
		  $nom['nom'],
		  "<br /><br />";
	}
	echo "</p>";


	//////////////////////////////////////////////////////
	// Fixer rendez-vous?
	//
	if ($rv == "oui") $fonction = "rv.gif";	else $fonction = "";
	debut_cadre_trait_couleur("$logo.gif", false, $fonction, _T('titre_rendez_vous'));
	afficher_si_rdv($date_heure, $date_fin, ($rv == "oui")); 
	fin_cadre_trait_couleur();

	echo "\n<p><b>"._T('info_texte_message_02')."</b><br />";
	echo "<textarea name='texte' rows='20' class='formo' cols='40'>";
	echo $texte;
	echo "</textarea></p><br />\n";

	echo "\n<p align='right'><input type='submit' name='valider' value='"._T('bouton_valider')."' class='fondo'/></p>";
	echo "</form>";
	echo "\n</div>";
	echo fin_gauche(), fin_page();
}

// http://doc.spip.org/@afficher_si_rdv
function afficher_si_rdv($date_heure, $date_fin, $choix)
{
	global $spip_lang_rtl;

	$heures_debut = heures($date_heure);
	$minutes_debut = minutes($date_heure);
	$heures_fin = heures($date_fin);
	$minutes_fin = minutes($date_fin);
  
	if ($date_fin == "0000-00-00 00:00:00") {
		$date_fin = $date_heure;
		$heures_fin = $heures_debut + 1;
	}
  
	if ($heures_fin >=24){
		$heures_fin = 23;
		$minutes_fin = 59;
	}
			
	$res = _T('item_non_afficher_calendrier');
	if (!$choix)  $res = "<b>$res</b>";

	echo "\n<div><input type='radio' name='rv' value='non' id='rv_off'" .
		(!$choix ? " checked='checked' " : '') .
		"\nonclick=\"changeVisible(this.checked, 'heure-rv', 'none', 'block');\"/>" .
		"<label for='rv_off'>"
	       . $res
		. "</label>"
		. "</div>";

	$res = _T('item_afficher_calendrier');
	if (!$choix)  $res = "<b>$res</b>";
	echo "\n<div><input type='radio' name='rv' value='oui' id='rv_on' " .
		($choix ? " checked='checked' " : '') .
		"\nonclick=\"changeVisible(this.checked, 'heure-rv', 'block', 'none');\"/>" . 
		"<label for='rv_on'>"
		. $res
		. "</label>"
	  . '</div>';
	
	$display = ($choix ? "block" : "none");
	
	echo "\n<div id='heure-rv' style='display: $display; padding-top: 4px; padding-left: 24px;'>",
	  afficher_jour_mois_annee_h_m($date_heure, $heures_debut, $minutes_debut),
	  "\n<br /><img src='puce$spip_lang_rtl.gif' alt=' '/> &nbsp; ",
	  afficher_jour_mois_annee_h_m($date_fin, $heures_fin, $minutes_fin, '_fin'),
	  "</div>";
}

?>
