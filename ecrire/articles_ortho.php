<?php

include("inc.php");

include_spip("ecrire.php");
include_spip("ortho.php");
include_spip("layer.php"); // Pour $browser_name

$articles_surtitre = lire_meta("articles_surtitre");
$articles_soustitre = lire_meta("articles_soustitre");
$articles_descriptif = lire_meta("articles_descriptif");
$articles_urlref = lire_meta("articles_urlref");
$articles_chapeau = lire_meta("articles_chapeau");
$articles_ps = lire_meta("articles_ps");
$articles_redac = lire_meta("articles_redac");
$articles_mots = lire_meta("articles_mots");


//
// Lire l'article
//

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
	$lang_article = $row["lang"];
}
if (!$lang_article) $lang_article = lire_meta('langue_site');

// pour l'affichage du virtuel
unset($virtuel);
if (substr($chapo, 0, 1) == '=') {
	$virtuel = substr($chapo, 1);
	$chapo = "";
}

$champs = array('surtitre', 'titre', 'soustitre', 'descriptif', 'chapo', 'texte', 'ps');
$echap = array();
$ortho = "";

//
// Affichage HTML
//

// Gros hack IE pour le "position: fixed"
$code_ie = "<!--[if IE]>
<style type=\"text/css\" media=\"screen\">
body {
	height: 100%; margin: 0px; padding: 0px;
	overflow: hidden;
}
.ortho-content {
	position: absolute; left: 0px;
	height: 100%; margin: 0px; padding: 0px;
	width: 72%;
	overflow-y: auto;
}
#ortho-fixed {
	position: absolute; right: 0px; width: 25%;
	height: 100%; margin: 0px; padding: 0px;
	overflow: hidden;
}
.ortho-padding {
	padding: 12px;
}
</style>
<script type=\"text/javascript\">
onload = function() { ortho-content.focus(); }
</script>
<![endif]-->";

debut_html("Orthographe", $code_ie);

changer_typo('','article'.$id_article);

// Ajouts et suppressions de mots par l'utilisateur
gerer_dico_ortho($lang_article);

//
// Panneau de droite
//
echo "<div id='ortho-fixed'>";
echo "<div class='ortho-padding serif'>";

debut_cadre_enfonce();

foreach ($champs as $champ) {
	$$champ = preparer_ortho($$champ, $lang_article);
	$ortho .= $$champ." ";
}
$result_ortho = corriger_ortho($ortho, $lang_article);
if (is_array($result_ortho)) {
	$mots = $result_ortho['mauvais'];
	if ($erreur = $result_ortho['erreur']) {
		echo "<b>Attention&nbsp;: votre texte contient trop de fautes, aucune correction n'est sugg&eacute;r&eacute;e ".
			"afin de ne pas surcharger le syst&egrave;me.</b><p>\n";
		echo "<b>Commencez par corriger les fautes les plus &eacute;videntes et r&eacute;essayez ensuite.</b><p>";
	}
	else {
		echo "<b>Les mots mal orthographi&eacute;s sont surlign&eacute;s en rouge. Vous pouvez cliquer ".
			"sur chaque mot pour afficher des suggestions de correction.</b><p>\n";
	}

	panneau_ortho($result_ortho);

	foreach ($champs as $champ) {
		list($$champ, $echap[$champ]) = echappe_html($$champ);
		$$champ = souligner_ortho($$champ, $lang_article, $result_ortho);
		$echap[$champ] = afficher_ortho($echap[$champ]);
	}
}
else {
	$erreur = $result_ortho;
	echo "<b>Aucun dictionnaire n'a &eacute;t&eacute; trouv&eacute; pour cette langue (";
	echo traduire_nom_langue($lang_article);
	echo "). ";
	echo "Le syst&egrave;me ne peut pas v&eacute;rifier l'orthographe de ce texte.</b>";
	foreach ($champs as $champ) {
		$$champ = afficher_ortho($$champ);
	}
}

fin_cadre_enfonce();

echo "</div>";
echo "</div>";

//
// Colonne de gauche : textes de l'article
//
echo "<div class='ortho-content'>";
echo "<div class='ortho-padding serif'>";

debut_cadre_relief();

if ($surtitre) {
	echo "<span $dir_lang><font face='arial,helvetica' size='3'><b>";
	echo typo($surtitre);
	echo "</b></font></span>\n";
}
gros_titre($titre);

if ($soustitre) {
	echo "<span $dir_lang><font face='arial,helvetica' size='3'><b>";
	echo typo($soustitre);
	echo "</b></font></span>\n";
}

if ($descriptif OR $url_site OR $nom_site) {
	echo "<p><div align='left' style='padding: 5px; border: 1px dashed #aaaaaa; background-color: #e4e4e4;' $dir_lang>";
	echo "<font size='2' face='Verdana,Arial,Sans,sans-serif'>";
	$texte_case = ($descriptif) ? "{{"._T('info_descriptif')."}} $descriptif\n\n" : '';
	$texte_case .= ($nom_site.$url_site) ? "{{"._T('info_urlref')."}} [".$nom_site."->".$url_site."]" : '';
	echo propre($texte_case, $echap['descriptif']);
	echo "</font>";
	echo "</div>";
}



//////////////////////////////////////////////////////
// Corps de l'article
//

echo "\n\n<div align='justify'>";

if ($virtuel) {
	debut_boite_info();
	echo _T('info_renvoi_article')." ".propre("<center>[->$virtuel]</center>");
	fin_boite_info();
}
else {
	echo "<div $dir_lang><b>";
	echo justifier(propre($chapo, $echap['chapo']));
	echo "</b></div>\n\n";

	echo "<div $dir_lang>".justifier(propre($texte, $echap['texte']))."</div>";

	if ($ps) {
		echo debut_cadre_enfonce();
		echo "<div $dir_lang><font size='2' face='Verdana,Arial,Sans,sans-serif'>";
		echo justifier("<b>"._T('info_ps')."</b> ".propre($ps, $echap['ps']));
		echo "</font></div>";
		echo fin_cadre_enfonce();
	}

	if ($les_notes) {
		echo debut_cadre_relief();
		echo "<div $dir_lang><font size='2'>";
		echo justifier("<b>"._T('info_notes')."&nbsp;:</b> ".$les_notes);
		echo "</font></div>";
		echo fin_cadre_relief();
	}

	if ($champs_extra AND $extra) {
		include_spip("extra.php");
		extra_affichage($extra, "articles");
	}
}


echo "</div>";


fin_cadre_relief();

html_background();
echo "</div>";
echo "</div>";

fin_html();

?>
