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
include_spip('inc/distant');
include_spip('inc/ortho');

// http://doc.spip.org/@exec_articles_ortho_dist
function exec_articles_ortho_dist()
{
  global
    $browser_name,
    $champs_extra,
    $chapo,
    $descriptif,
    $dir_lang,
    $id_article,
    $les_notes,
    $ps,
    $soustitre,
    $spip_lang_left,
    $spip_lang_right,
    $surtitre,
    $texte,
    $titre;


//charset_texte('utf-8');

//
// Lire l'article
//
  $id_article = intval($id_article);

  $result = spip_query("SELECT * FROM spip_articles WHERE id_article='$id_article'");


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
if (!$lang_article) $lang_article = $GLOBALS['meta']['langue_site'];
changer_typo($lang_article); # pour l'affichage du texte

// pour l'affichage du virtuel

if (substr($chapo, 0, 1) == '=') {
	$virtuel = substr($chapo, 1);
	$chapo = "";
}


$echap = array();
$ortho = "";

//
// Affichage HTML
//

// http://doc.spip.org/@debut_html
function debut_html($titre = "", $rubrique="") {
	include_spip('inc/headers');

	$nom_site_spip = entites_html(textebrut(typo($GLOBALS['meta']["nom_site"])));
	if (!$nom_site_spip) $nom_site_spip=  _T('info_mon_site_spip');
	$titre = textebrut(typo($titre));

	http_no_cache();
	echo _DOCTYPE_ECRIRE .
	  html_lang_attributes(),
	  "<head>\n" .
	  "<title>[$nom_site_spip] $titre</title>\n";
	echo f_jQuery("");
	echo  $rubrique, "\n";
	echo envoi_link($nom_site_spip),
  http_script("$(document).ready(function(){
    verifForm();
    if(jQuery.browser.msie) document.getElementById('ortho-content').focus();
  });");

	// Fin des entetes
	echo "\n</head>\n";
	echo "<body ",  _ATTRIBUTES_BODY, ">";
}


// Gros hack IE pour le "position: fixed"
$code_ie = "<!--[if IE]>
<style type=\"text/css\" media=\"screen\">
	body {
		height: 100%; margin: 0px; padding: 0px;
		overflow: hidden;
	}
	.ortho-content {
		position: absolute; $spip_lang_left: 0px;
		height: 100%; margin: 0px; padding: 0px;
		width: 72%;
		overflow-y: auto;
	}
	#ortho-fixed {
		position: absolute; $spip_lang_right: 0px; width: 25%;
		height: 100%; margin: 0px; padding: 0px;
		overflow: hidden;
	}
	.ortho-padding {
		padding: 12px;
	}
</style>
<![endif]-->";

debut_html(_T('ortho_orthographe').' &laquo;'.$titre.'&raquo;', $code_ie);


// Ajouts et suppressions de mots par l'utilisateur
gerer_dico_ortho($lang_article);

//
// Panneau de droite
//
echo "<div id='ortho-fixed'>";
echo "<div class='ortho-padding serif'>";

debut_cadre_enfonce();

$champs = array('surtitre', 'titre', 'soustitre', 'descriptif', 'chapo', 'texte', 'ps');
foreach ($champs as $champ) {
	$ortho .= $$champ." ";
}
$ortho = preparer_ortho($ortho, $lang_article);
$result_ortho = corriger_ortho($ortho, $lang_article);
if (is_array($result_ortho)) {
	$mots = $result_ortho['mauvais'];
	if ($erreur = $result_ortho['erreur']) {
		echo "<b>"._T('ortho_trop_de_fautes').aide('corrortho')."</b><p>\n";
		echo "<b>"._T('ortho_trop_de_fautes2')."</b><p>";
	}
	else {
		echo "<b>"._T('ortho_mode_demploi').aide('corrortho')."</b><p>\n";
	}

	panneau_ortho($result_ortho);
}
else {
	$erreur = $result_ortho;
	echo "<b>"._T('ortho_dico_absent').aide('corrortho')." (";
	echo traduire_nom_langue($lang_article);
	echo "). ";
	echo _T('ortho_verif_impossible')."</b>";
}

fin_cadre_enfonce();

echo "</div>";
echo "</div>";

//
// Colonne de gauche : textes de l'article
//
echo "<div class='ortho-content' id='ortho-content'>";
echo "<div class='ortho-padding serif'>";

// Traitement des champs : soulignement des mots mal orthographies
foreach ($champs as $champ) {
	switch ($champ) {
	case 'texte':
	case 'chapo':
	case 'descriptif':
	case 'ps':
		// Mettre de cote les <code>, <cadre>, etc.
		$$champ = echappe_html($$champ,'ORTHO');
		$$champ = propre($$champ);
		break;
	default:
		$$champ = typo($$champ);
		break;
	}
	// On passe en UTF-8 juste pour la correction
	$$champ = preparer_ortho($$champ, $lang_article);
	if (is_array($result_ortho))
		$$champ = souligner_ortho($$champ, $lang_article, $result_ortho);
	// Et on repasse dans le charset original pour remettre les echappements
	$$champ = afficher_ortho($$champ);
	$$champ = echappe_retour($$champ, 'ORTHO');
}
// Traitement identique pour les notes de bas de page
if ($les_notes) {
	$les_notes = preparer_ortho($les_notes, $lang_article);
	if (is_array($result_ortho))
		$les_notes = souligner_ortho($les_notes, $lang_article, $result_ortho);
	$les_notes = afficher_ortho($les_notes);
}

debut_cadre_relief();

if ($surtitre) {
	echo "<span $dir_lang><font face='arial,helvetica' size='3'><b>";
	echo $surtitre;
	echo "</b></font></span>\n";
}
gros_titre($titre);

if ($soustitre) {
	echo "<span $dir_lang><font face='arial,helvetica' size='3'><b>";
	echo $soustitre;
	echo "</b></font></span>\n";
}

if ($descriptif OR $url_site OR $nom_site) {
	echo "<p><div align='$spip_lang_left' style='padding: 5px; border: 1px dashed #aaaaaa; background-color: #e4e4e4;' $dir_lang>";
	echo "<font size='2' face='Verdana,Arial,Sans,sans-serif'>";
	$texte_case = ($descriptif) ? "{{"._T('info_descriptif')."}} $descriptif\n\n" : '';
	$texte_case .= ($nom_site.$url_site) ? "{{"._T('info_urlref')."}} [".$nom_site."->".$url_site."]" : '';
	echo $descriptif;
	echo "</font>";
	echo "</div>";
}


// Corps de l'article

echo "\n\n<div align='justify'>";

if ($virtuel) {
	debut_boite_info();
	echo _T('info_renvoi_article')." ".propre("<center>[->$virtuel]</center>");
	fin_boite_info();
}
else {
	echo "<div $dir_lang><b>";
	echo $chapo;
	echo "</b></div>\n\n";

	echo "<div $dir_lang>";
	echo $texte;
	echo "</div>";

	if ($ps) {
		echo debut_cadre_enfonce();
		echo "<div $dir_lang><font size='2' face='Verdana,Arial,Sans,sans-serif'>";
		echo "<b>"._T('info_ps')."</b> ";
		echo $ps;
		echo "</font></div>";
		echo fin_cadre_enfonce();
	}

	if ($les_notes) {
		echo debut_cadre_relief();
		echo "<div $dir_lang><font size='2'>";
		echo "<b>"._T('info_notes')."&nbsp;:</b> ".$les_notes;
		echo "</font></div>";
		echo fin_cadre_relief();
	}

	if ($champs_extra AND $extra) {
		include_spip('inc/extra');
		echo extra_affichage($extra, "articles");
	}
}


echo "</div>";


fin_cadre_relief();

// html_background();
echo "</div></div></body></html>\n";

}
?>
