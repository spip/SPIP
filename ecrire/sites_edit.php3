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

include_ecrire ("inc_sites.php3");

$proposer_sites = lire_meta("proposer_sites");

function premiere_rubrique(){
 	$query="SELECT * FROM spip_rubriques WHERE id_parent='0' ORDER BY titre LIMIT 0,1";
 	$result=spip_query($query);

	while($row=spip_fetch_array($result)){
		$my_rubrique=$row['id_rubrique'];
	}
	return $my_rubrique;

}

function enfant($leparent){
	global $id_parent;
	global $id_rubrique;
	static $i = 0, $premier = 1;
	global $statut;
	global $connect_toutes_rubriques;
	global $couleur_claire, $spip_lang_left;
	global $browser_name;


	$i++;
 	$query="SELECT * FROM spip_rubriques WHERE id_parent='$leparent' ORDER BY 0+titre, titre";
 	$result=spip_query($query);

	while($row=spip_fetch_array($result)){
		$my_rubrique=$row['id_rubrique'];
		$titre=typo($row['titre']);
		$statut_rubrique=$row['statut'];
		$lang_rub = $row['lang'];
		$langue_choisie_rub = $row['langue_choisie'];
		$style = "";
		$espace = "";

		// si l'article est publie il faut etre admin pour avoir le menu
		// sinon le menu est present en entier (proposer un article)
		if ($statut != "publie" OR acces_rubrique($my_rubrique)) {
			$rubrique_acceptable = true;
		} else {
			$rubrique_acceptable = false;
		}

		if (eregi("mozilla", $browser_name)) {
			$style .= "padding-$spip_lang_left: 16px; ";
			$style .= "margin-$spip_lang_left: ".(($i-1)*16)."px;";
		} else {
			for ($count = 0; $count <= $i; $count ++) $espace .= "&nbsp;&nbsp;&nbsp;&nbsp;";
		}

		$img = _DIR_IMG_PACK . 'rubrique-12.gif';
		switch ($i) {
		case 1:
			$espace= "";
			$img = _DIR_IMG_PACK . 'secteur-12.gif';
			$style .= "font-weight: bold;";
			$style .= "background-color: $couleur_claire;";
			break;
		case 2:
			$style .= "color: #202020;";
			$style .= "font-weight: bold;";
			$style .= "border-bottom: 1px solid $couleur_claire;";
			break;
		case 3:
			$style .= "color: #404040;";
			break;
		case 4:
			$style .= "color: #606060;";
			break;
		case 5:
			$style .= "color: #808080;";
			break;
		default:
			$style .= "color: #A0A0A0;";
			break;
		}

		$style .= "background: url($img) $spip_lang_left no-repeat;";

		if ($rubrique_acceptable) {
			if ($i == 1 && !$premier) echo "<OPTION VALUE='$my_rubrique'>\n"; // sert a separer les secteurs
			$titre = couper($titre." ", 50); // largeur maxi
			if (lire_meta('multi_rubriques') == 'oui' AND ($langue_choisie_rub == "oui" OR $leparent == 0)) $titre = $titre." [".traduire_nom_langue($lang_rub)."]";
			echo "<OPTION".mySel($my_rubrique,$id_rubrique)." style=\"$style\">$espace".supprimer_tags($titre)."\n";
		}
		$premier = 0;
		enfant($my_rubrique);
	}
	$i=$i-1;
}



$proposer_sites = lire_meta("proposer_sites");

$query = "SELECT * FROM spip_syndic WHERE id_syndic='$id_syndic'";
$result = spip_query($query);
if ($row = spip_fetch_array($result)) {
	$id_syndic = $row["id_syndic"];
	$id_rubrique = $row["id_rubrique"];
	$nom_site = $row["nom_site"];
	$url_site = $row["url_site"];
	$url_syndic = $row["url_syndic"];
	$descriptif = $row["descriptif"];
	$syndication = $row["syndication"];
	$extra=$row["extra"];
}
else {
	$syndication = 'non';
	$new = 'oui';
}
if (!$id_rubrique > 0) $id_rubrique = premiere_rubrique();



debut_page(_T('info_site_reference_2'), "documents", "sites");


debut_grand_cadre();

afficher_hierarchie($id_rubrique);

fin_grand_cadre();


debut_gauche();
debut_droite();
debut_cadre_formulaire();


echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'>";

if ($new != 'oui') {
	echo "<td>";
	icone(_T('icone_retour'), "sites.php3?id_syndic=$id_syndic", 'site-24.gif', "rien.gif");
	echo "</td>";
	echo "<td>". http_img_pack('rien.gif', " ", "width='10'") . "</td>\n";
}
echo "<td width='100%'>";
echo _T('titre_referencer_site');
gros_titre($nom_site);
echo "</td></tr></table>";
echo "<p>";



if ($new == 'oui'){

	$proposer_sites = lire_meta("proposer_sites");
	if ($connect_statut == '0minirezo' OR $proposer_sites > 0) {
		debut_cadre_relief("site-24.gif");
		
		$link = new Link('sites.php3');
		$link->addVar('id_rubrique', $id_rubrique);
		$link->addVar('new', 'oui');
		$link->addVar('redirect', $clean_link->getUrl());
		$link->addVar('analyser_site', 'oui');
		echo $link->getForm();
		
		echo "<font face='Verdana,Arial,Sans,sans-serif' size=2>"._T('texte_referencement_automatique')."</font>";
		echo "<div align='right'><input type=\"text\" name=\"url\" class='fondl' value=\"http://\">";
		echo "<input type=\"submit\" name=\"submit\" value=\""._T('bouton_ajouter')."\" class='fondo'>";
		
		fin_cadre_relief();
		echo "</form>";
		
		echo "<p><b>"._T('texte_non_fonction_referencement')."</b>";
		$cadre_ouvert = true;
		debut_cadre_enfonce("site-24.gif");
		
	}

}


$link = new Link($target);
$link->addVar('new');
$link->addVar('modifier_site', 'oui');
$link->addVar('syndication_old', $syndication);
echo $link->getForm('POST');

$nom_site = entites_html($nom_site);
$url_site = entites_html($url_site);
$url_syndic = entites_html($url_syndic);

echo _T('info_nom_site_2')."<br>";
echo "<input type='text' class='formo' name='nom_site' value=\"$nom_site\" size='40'><p>";
if (strlen($url_site)<8) $url_site="http://";
echo _T('entree_adresse_site')."<br>";
echo "<input type='text' class='formo' name='url_site' value=\"$url_site\" size='40'><p>";



	if ($id_rubrique == 0) $logo_parent = "racine-site-24.gif";
	else {
		$query = "SELECT id_parent FROM spip_rubriques WHERE id_rubrique='$id_rubrique'";
		$result=spip_query($query);
		while($row=spip_fetch_array($result)){
			$parent_parent=$row['id_parent'];
		}
		if ($parent_parent == 0) $logo_parent = "secteur-24.gif";
		else $logo_parent = "rubrique-24.gif";
	}

	debut_cadre_couleur("$logo_parent", false, "", _T('entree_interieur_rubrique'));
	echo "<select name='id_rubrique' style='background-color:#ffffff; font-size:90%; width:100%; max-height: 24px; font-face:verdana,arial,helvetica,sans-serif;' size=1>\n";
	enfant(0);
	echo "</select>\n";
	fin_cadre_couleur();

echo "<p /><b>"._T('entree_description_site')."</b><br>";
echo "<textarea name='descriptif' rows='8' class='forml' cols='40' wrap=soft>";
echo $descriptif;
echo "</textarea>\n";

$activer_syndic = lire_meta("activer_syndic");

echo "<input type='hidden' name='syndication_old' value=\"$syndication\">";

if ($activer_syndic != "non") {
	debut_cadre_enfonce();
	if ($syndication == "non") {
		echo "<INPUT TYPE='radio' NAME='syndication' VALUE='non' id='syndication_non' CHECKED>";
	}
	else {
		echo "<INPUT TYPE='radio' NAME='syndication' VALUE='non' id='syndication_non'>";
	}
	echo " <b><label for='syndication_non'>"._T('bouton_radio_non_syndication')."</label></b><p>";

	if ($syndication == "non") {
		echo "<INPUT TYPE='radio' NAME='syndication' VALUE='oui' id='syndication_oui'>";
	}
	else {
		echo "<INPUT TYPE='radio' NAME='syndication' VALUE='oui' id='syndication_oui' CHECKED>";
	}
	echo " <b><label for='syndication_oui'>"._T('bouton_radio_syndication')."</label></b>";
	echo aide("rubsyn");


	echo "<table cellpadding=0 cellspacing=0 border=0 width=100%><tr><td width=50>&nbsp;</td><td>";

	if (strlen($url_syndic) < 8) $url_syndic = "http://";
	echo _T('entree_adresse_fichier_syndication');
	echo "<br>";

	// cas d'une liste de flux detectee par feedfinder : menu
	if (preg_match(',^select: (.+),', $url_syndic, $regs)) {
		$feeds = explode(' ',$regs[1]);
		echo "<select name='url_syndic'>\n";
		foreach ($feeds as $feed) {
			echo '<option value="'.entites_html($feed).'">'.$feed."</option>\n";
		}
		echo "</select>\n";
	}
	// cas normal
	else {
		echo "<INPUT TYPE='text' CLASS='formo' NAME='url_syndic' VALUE=\"$url_syndic\" SIZE='40'><P>";
		echo "<INPUT TYPE='hidden' NAME='old_syndic' VALUE=\"$url_syndic\"";
	}
	echo "</td></tr></table>";

	fin_cadre_enfonce();
} 
else {
	echo "<INPUT TYPE='Hidden' NAME='syndication' VALUE=\"$syndication\">";
	echo "<INPUT TYPE='hidden' NAME='url_syndic' VALUE=\"$url_syndic\"";
}


if ($champs_extra) {
		include_ecrire("inc_extra.php3");
		extra_saisie($extra, 'sites', $id_secteur);
	}


echo "<div ALIGN='right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_enregistrer')."' CLASS='fondo'></div>";
echo "</FORM>";

if ($cadre_ouvert) fin_cadre_enfonce();

fin_cadre_formulaire();

fin_page();

?>