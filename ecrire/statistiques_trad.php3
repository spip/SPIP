<?php

include ("inc.php3");

debut_page(_T('titre_page_etat_traductions'), "asuivre", "plan-trad");

if ($connect_statut == '0minirezo') {
	echo "<br>";
	barre_onglets("traductions", "bilan");
}

debut_gauche();


debut_droite();

if ($connect_statut != '0minirezo') {
	echo _T('avis_non_acces_page');
	fin_page();
	exit;
}

//
// Statistiques des traductions
//

debut_cadre_relief("langues-24.gif");

$langues = explode(',', lire_meta('langues_multilingue'));

$traduits = array();
$conflits = array();

while (list(, $trad_lang) = each($langues)) {
	$query = "SELECT COUNT(*) AS total, SUM(t.statut='publie') AS traduits, SUM(t.statut='publie' AND t.date_modif < a.date_modif) AS conflits ".
		"FROM spip_articles AS a LEFT JOIN spip_articles AS t ".
		"ON (a.id_article = t.id_trad AND t.lang = '$trad_lang') ".
		"WHERE a.statut='publie' AND a.lang!='$trad_lang' AND (a.id_trad=0 OR a.id_trad=a.id_article)";
	$result = spip_query($query);
	if (!($row = mysql_fetch_array($result))) continue;

	if (!$row['total']) continue;
	$traduits[$trad_lang] = 1.0 * $row['traduits'] / $row['total'];
	$conflits[$trad_lang] = 1.0 * $row['conflits'] / $row['total'];
}

echo "<table cellpadding='2' cellspacing='0' border='0' width='100%'>";
$ifond = 1;

arsort($traduits);

while (list($trad_lang, $coef_traduits) = each($traduits)) {
	$coef_conflits = $conflits['trad_lang'];
	$coef_traduits -= $coef_conflits;

	if ($ifond==0) {
		$ifond=1;
		$couleur="";
	}
	else {
		$ifond=0;
		$couleur="$couleur_claire";
	}

	$largeur = 200;
	$vert = intval(($largeur - 2) * $coef_traduits);
	$rouge = intval(($largeur - 2) * $coef_conflits);

	echo "<tr bgcolor='$couleur'>";
	$dir = lang_dir($trad_lang, '', ' dir=rtl');
	echo "<td width='100%'><font face='Verdana,Arial,Sans,sans-serif' size='2'>";
	echo "<a$dir href='plan_trad.php3?trad_lang=$trad_lang'>".traduire_nom_langue($trad_lang)."</a>";
	echo " : ".intval($coef_traduits * 100)."%";
	echo "</font></td>";
	echo "<td>";
	
	echo "<table cellpadding='0' cellspacing='0' border='0' width='$largeur' height='8'>";
	echo "<tr><td align='left'>";
	echo "<img src='img_pack/jauge-fond.gif' height='8' width='$largeur' style='position: relative;'>";
	if ($vert) echo "<img src='img_pack/jauge-vert.gif' width='$vert' height='8' style='position: relative; top: -8px; left: 1px;'>";
	if ($rouge) echo "<img src='img_pack/jauge-rouge.gif' width='$rouge' height='8' style='position: relative; top: -8px; left: 1px;'>";
	echo "<img src='img_pack/rien.gif' height='8' width='1' border='0'>";
	echo "</td></tr></table>\n";

	echo "</td>";
	echo "</tr>";
}
echo "</table>";

echo "<p><font face='Verdana,Arial,Sans,sans-serif' size='2'>"._T('texte_bilan_traductions')."</font>";

fin_cadre_relief();

fin_page();

?>

