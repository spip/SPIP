<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

include ("inc.php3");

// prendre $var_* comme variables pour eviter les conflits avec les http_vars

$var_nom = "message";
$var_f = find_in_path('inc_' . $var_nom . '.php');

if ($var_f) 
  include($var_f);
else
  include_ecrire (_DIR_INCLUDE . 'inc_' . $var_nom . '.php');

$var_nom = 'affiche_' . $var_nom;

if (!function_exists($var_nom)) {
	if (function_exists($var_f = $var_nom . "_dist"))
		$var_nom =  $var_f;
	else {
	   spip_log("fonction $var_nom indisponible");
	   exit;
	}
 }

if (!spip_num_rows(spip_query("
SELECT id_auteur FROM spip_auteurs_messages WHERE id_auteur=$connect_id_auteur AND id_message=$id_message"))) {

	$row = spip_fetch_array(spip_query("SELECT type FROM spip_messages WHERE id_message=$id_message"));
	if ($row['type'] != "affich"){
		debut_page(_T('info_acces_refuse'));
		debut_gauche();
		debut_droite();
		echo "<b>"._T('avis_non_acces_message')."</b><p>";
		fin_page();
		exit;
	}
}

if ($ajout_forum AND strlen($texte) > 10 AND strlen($titre) > 2) {
	spip_query("UPDATE spip_auteurs_messages SET vu='non' WHERE id_message='$id_message'");
}

if ($modifier_message == "oui") {
	$titre = addslashes($titre);
	$texte = addslashes($texte);
	spip_query("UPDATE spip_messages SET titre='$titre', texte='$texte' WHERE id_message='$id_message'");
}

if ($changer_rv) {
	spip_query("UPDATE spip_messages SET rv='$rv' WHERE id_message='$id_message'");
}

if ($jour)
  change_date_message($id_message, $heures,$minutes,$mois, $jour, $annee, $heures_fin,$minutes_fin,$mois_fin, $jour_fin, $annee_fin);

if ($change_statut) {
	spip_query("UPDATE spip_messages SET statut='$change_statut' WHERE id_message='$id_message'");
	spip_query("UPDATE spip_messages SET date_heure=NOW() WHERE id_message='$id_message' AND rv<>'oui'");
}

if ($supp_dest) {
	spip_query("DELETE FROM spip_auteurs_messages WHERE id_message='$id_message' AND id_auteur='$supp_dest'");
}

$var_nom($id_message);

?>
