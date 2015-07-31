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
include_spip('inc/date');
include_spip('base/abstract_sql');

function exec_message_edit_dist()
{
global
  $connect_id_auteur,
  $connect_statut,
  $dest,
  $id_message,
  $new,
  $rv,
  $spip_lang_rtl,
  $type;

 $id_message =  intval($id_message);
 $dest = intval($dest);

// Droits
if ($new=='oui') {
	switch ($type) {
		case 'affich':
			$ok = ($connect_statut == '0minirezo');
			break;
		case 'pb':
		case 'rv':
		case 'normal':
			$ok = true;
			break;
		default:
			$ok = false;
	}

	if (!$ok) {
		debut_page(_T('info_acces_refuse'));
		debut_gauche();
		debut_droite();
		echo "<b>"._T('avis_non_acces_message')."</b><p>";
		fin_page();
		exit;
	}

	$mydate = date("YmdHis", time() - 2 * 24 * 3600);
	spip_query("DELETE FROM spip_messages WHERE (statut = 'redac') AND (date_heure < $mydate)");

	if ($type == 'pb') $statut = 'publie';
	else $statut = 'redac';
	$titre = filtrer_entites(_T('texte_nouveau_message'));
	$id_message = spip_abstract_insert("spip_messages", "(titre, date_heure, statut, type, id_auteur)", "(" . spip_abstract_quote($titre) . ", NOW(), '$statut', '$type', $connect_id_auteur)");
	
	if ($rv) {
		spip_query("UPDATE spip_messages SET rv='oui', date_heure=" . spip_abstract_quote($rv . ' 12:00:00') . ", date_fin= " . spip_abstract_quote($rv . ' 13:00:00') . " WHERE id_message = $id_message");
	}

	if ($type != "affich"){
		spip_abstract_insert('spip_auteurs_messages',
			"(id_auteur,id_message,vu)",
			"('$connect_id_auteur','$id_message','oui')");
		if ($dest) {
			spip_abstract_insert('spip_auteurs_messages',
				"(id_auteur,id_message,vu)",
				"('$dest','$id_message','non')");
		}
		else if ($type == 'normal') $ajouter_auteur = true;
	}
	$onfocus = " onfocus=\"if(!antifocus){this.value='';antifocus=true;}\"";
 } else $onfocus = $ajouter_auteur = '';

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

debut_page(_T('titre_page_message_edit'), "redacteurs", "messagerie");

if (!($expediteur = $connect_id_auteur OR ($type == 'affich' AND $connect_statut == '0minirezo'))) die();

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

 echo generer_url_post_ecrire('message',"id_message=$id_message");

 debut_gauche();

debut_droite();

	echo "<div class='arial2'>";
	echo "<font face='Verdana,Arial,Sans,sans-serif' size='2' color='green'><b>$le_type</b></font><p>";
	
	if ($type == "affich")
		echo "<font face='Verdana,Arial,Sans,sans-serif' size='1' color='red'>"._T('texte_message_edit')."</font></p><p>";
	

	echo "<input type='hidden' name='modifier_message' value='oui'/>";
	echo "<input type='hidden' name='id_message' value='$id_message'/>";
	echo "<input type='hidden' name='changer_rv' value='$id_message'/>";
	echo _T('texte_titre_obligatoire')."<br />";
	echo "<input type='text' class='formo' name='titre' value=\"$titre\" size='40' $onfocus />";

	if ($ajouter_auteur) {
		echo "</p><p><b>"._T('info_nom_destinataire')."</b><br />";
		echo "<input type='text' class='formo' name='cherche_auteur' value='' size='40'/>";
	} else if ($dest) {
		$nom = spip_fetch_array(spip_query("SELECT nom FROM spip_auteurs WHERE id_auteur=$dest"));
		echo "</p><p><b>",
		  _T('info_nom_destinataire'),
		  "</b>&nbsp;:&nbsp;&nbsp; ",
		  $nom['nom'],
		  "<br /><br />";
	}
	echo "<p />";


	//////////////////////////////////////////////////////
	// Fixer rendez-vous?
	//
	if ($rv == "oui") $fonction = "rv.gif";	else $fonction = "";
	debut_cadre_trait_couleur("$logo.gif", false, $fonction, _T('titre_rendez_vous'));
	afficher_si_rdv($date_heure, $date_fin, ($rv == "oui")); 
	fin_cadre_trait_couleur();

	echo "<p><b>"._T('info_texte_message_02')."</b><br />";
	echo "<textarea name='texte' rows='20' class='formo' cols='40'>";
	echo $texte;
	echo "</textarea></p><br />\n";

	echo "<p align='right'><input type='submit' name='valider' value='"._T('bouton_valider')."' class='fondo'/></p>";
	echo "</div>";
	echo "</form>";

fin_page();
}

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
			
	$res = "<div><input type='radio' name='rv' value='non' id='rv_off'" .
		(!$choix ? "checked='checked' " : '') .
		" onclick=\"changeVisible(this.checked, 'heure-rv', 'none', 'block');\"/>" .
		"<label for='rv_off'>".
		_T('item_non_afficher_calendrier').
		"</label>";
	echo ($choix  ? $res : "<b>$res</b>") . "</div>";

	$res = "<input type='radio' name='rv' value='oui' id='rv_on' " .
		($choix ? "checked='checked' " : '') .
		"onclick=\"changeVisible(this.checked, 'heure-rv', 'block', 'none');\"/>" . 
		"<label for='rv_on'>".
		_T('item_afficher_calendrier').
		"</label>";
	echo '<div>' . (!$choix  ? $res : "<b>$res</b>") . '</div>';
	
	$display = ($choix ? "block" : "none");
	
	echo "<div id='heure-rv' style='display: $display; padding-top: 4px; padding-left: 24px;'>",
	  afficher_jour_mois_annee_h_m($date_heure, $heures_debut, $minutes_debut),
	  " <br /><img src='puce$spip_lang_rtl.gif' alt=' '/> &nbsp; ",
	  afficher_jour_mois_annee_h_m($date_fin, $heures_fin, $minutes_fin, '_fin'),
	  "</div>";
}

?>
