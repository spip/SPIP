<?php

include ("inc.php3");

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
}

function my_sel($num, $tex, $comp) {
  return "<option value='$num'" . (($num != $comp) ? '' : " selected='selected'") .
    ">$tex</option>\n";
}

function afficher_mois($mois, $attributs){
  return
	"<select $attributs>\n" .
	my_sel("01", _T('date_mois_1'), $mois) .
	my_sel("02", _T('date_mois_2'), $mois) .
	my_sel("03", _T('date_mois_3'), $mois) .
	my_sel("04", _T('date_mois_4'), $mois) .
	my_sel("05", _T('date_mois_5'), $mois) .
	my_sel("06", _T('date_mois_6'), $mois) .
	my_sel("07", _T('date_mois_7'), $mois) .
	my_sel("08", _T('date_mois_8'), $mois) .
	my_sel("09", _T('date_mois_9'), $mois) .
	my_sel("10", _T('date_mois_10'), $mois) .
	my_sel("11", _T('date_mois_11'), $mois) .
	my_sel("12", _T('date_mois_12'), $mois) .
	"</select>\n";
}

function afficher_annee($annee, $attributs) {
	echo "<select $attributs>\n";
	if ($annee < 1996) echo	my_sel($annee,$annee,$annee);
	for ($i=date("Y") - 1; $i < date("Y") + 3; $i++) {
		echo my_sel($i,$i,$annee);
	}
	echo "</select>\n";
}

function afficher_jour($jour, $attributs){
	echo "<select $attributs>\n";
	for($i=1;$i<32;$i++){
		if ($i<10){$aff="&nbsp;".$i;}else{$aff=$i;}
		echo my_sel($i,$aff,$jour);
	}
	echo "</select>\n";
}

function afficher_jour_mois_annee_h_m($date, $heures, $minutes, $suffixe='')
{
  afficher_jour(jour($date), "name='jour$suffixe' size='1' class='fondl'");
  echo '<br />', afficher_mois(mois($date), "name='mois$suffixe' size='1' class='fondl'");
  echo '<br />';
 afficher_annee(annee($date), "name='annee$suffixe' size='1' class='fondl'");

  echo "<br /> <input type='text' class='fondl' name='heures' value=\"".$heures."\" size='3'/>&nbsp;".majuscules(_T('date_mot_heures'))."&nbsp;",
    "<input type='text' class='fondl' name='minutes' value=\"$minutes\" size='3'/>";
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
		
  $res = "<div><br /><input type='radio' name='rv' value='non' id='rv_off'" .
	(!$choix ? "checked='checked' " : '') .
	" onclick=\"changeVisible(this.checked, 'heure-rv', 'none', 'block');\"/>" .
	"<label for='rv_off'><b>".
    #			  _T('item_non_afficher_calendrier').
	_L('Ce message ne concerne pas un rendez-vous').
	"</b></label>";
  echo ($choix  ? $res : "<b>$res</b>") . "</div>";

  $res = "<br /><input type='radio' name='rv' value='oui' id='rv_on' " .
    ($choix ? "checked='checked' " : '') .
    "onclick=\"changeVisible(this.checked, 'heure-rv', 'block', 'none');\"/>" . " <label for='rv_on'>".
    #			  _T('item_afficher_calendrier').
    _L('Ce message concerne le rendez-vous suivant').
    "</label>";
  echo '<p>' . (!$choix  ? $res : "<b>$res</b>") . '</p>';
  echo "<div id='heure-rv' style='display: block; padding-top: 4px; padding-left: 24px;'>";

  echo _L('Du '), '<br />';
  afficher_jour_mois_annee_h_m($date_heure, $heures_debut, $minutes_debut);

#  echo " <br /><img src='puce$spip_lang_rtl.gif' alt=' '/> &nbsp; ";
  echo  '<br /><br />', _L('Au '), '<br />';
  afficher_jour_mois_annee_h_m($date_fin,
			       $heures_fin,
			       $minutes_fin,
			       '_fin');
  echo "</div>";
}


if ($new == "oui") {
	$mydate = date("YmdHis", time() - 2 * 24 * 3600);
	$query = "DELETE FROM spip_messages WHERE (statut = 'redac') AND (date_heure < $mydate)";
	$result = spip_query($query);

	if ($type == 'pb') $statut = 'publie';
	else $statut = 'redac';

	$query = "INSERT INTO spip_messages (titre, date_heure, statut, type, id_auteur) VALUES ('".addslashes(filtrer_entites(_T('texte_nouveau_message')))."', NOW(), '$statut', '$type', $connect_id_auteur)";
	$result = spip_query($query);
	$id_message = spip_insert_id();
	
	if ($rv) {
		spip_query("UPDATE spip_messages SET rv='oui', date_heure='$rv 12:00:00', date_fin= '$rv 13:00:00' WHERE id_message = $id_message");
	}

	if ($type != "affich"){
		spip_query("INSERT INTO spip_auteurs_messages (id_auteur,id_message,vu) VALUES ('$connect_id_auteur','$id_message','oui')");
		if ($dest) {
			spip_query("INSERT INTO spip_auteurs_messages (id_auteur,id_message,vu) VALUES ('$dest','$id_message','non')");
		}
		else if ($type == 'normal') $ajouter_auteur = true;
	}
	$onfocus = " onfocus=\"if(!antifocus){this.value='';antifocus=true;}\"";
 }

$row = spip_fetch_array(spip_query("SELECT * FROM spip_messages WHERE id_message=$id_message"));

$id_message = $row['id_message'];
$date_heure = $row["date_heure"];
$date_fin = $row["date_fin"];
$titre = entites_html($row["titre"]);
$texte = entites_html($row["texte"]);
$type = $row["type"];
$statut = $row["statut"];
$page = $row["page"];
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

echo "<form action='message.php3?id_message=$id_message' method='post'>";

 debut_gauche();
	//////////////////////////////////////////////////////
	// Fixer rendez-vous?
	//

	if ($rv == "oui") $fonction = "rv.gif";	else $fonction = "";

	debut_cadre_trait_couleur("$logo.gif", false, $fonction, 
				  _T('titre_rendez_vous'));

afficher_si_rdv($date_heure, $date_fin, ($rv != "oui")); 

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
	}

	echo "<p />";

		fin_cadre_trait_couleur();

	echo "<p><b>"._T('info_texte_message_02')."</b><br />";
	echo "<textarea name='texte' rows='20' class='formo' cols='40'>";
	echo $texte;
	echo "</textarea></p><br />\n";

	echo "<p align='right'><input type='submit' name='valider' value='"._T('bouton_valider')."' class='fondo'/></p>";
	echo "</div>";
	echo "</form>";

fin_page();

?>
