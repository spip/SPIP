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

// http://doc.spip.org/@exec_sites_edit_dist
function exec_sites_edit_dist()
{
  global $champs_extra, $connect_statut, $descriptif, $id_rubrique, $id_secteur, $id_syndic, $new, $nom_site, $syndication, $url_site, $url_syndic;

$result = spip_query("SELECT * FROM spip_syndic WHERE id_syndic=" . intval($id_syndic));

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
	if (!intval($id_rubrique)) {
		$row = spip_fetch_array(spip_query("SELECT id_rubrique FROM spip_rubriques WHERE id_parent='0' ORDER BY titre LIMIT 1"));
		$id_rubrique = $row['id_rubrique'];
	}
}
pipeline('exec_init',array('args'=>array('exec'=>'sites_edit','id_syndic'=>$id_syndic),'data'=>''));

debut_page(_T('info_site_reference_2'), "naviguer", "sites", "", "", $id_rubrique);

debut_grand_cadre();

afficher_hierarchie($id_rubrique);

fin_grand_cadre();

debut_gauche();
echo pipeline('affiche_gauche',array('args'=>array('exec'=>'sites_edit','id_syndic'=>$id_syndic),'data'=>''));
creer_colonne_droite();
echo pipeline('affiche_droite',array('args'=>array('exec'=>'sites_edit','id_syndic'=>$id_syndic),'data'=>''));	  
debut_droite();
debut_cadre_formulaire();

echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'>";

if ($new != 'oui') {
	echo "<td>";
	icone(_T('icone_retour'), generer_url_ecrire("sites","id_syndic=$id_syndic"), 'site-24.gif', "rien.gif");
	echo "</td>";
	echo "<td>". http_img_pack('rien.gif', " ", "width='10'") . "</td>\n";
}
echo "<td width='100%'>";
echo _T('titre_referencer_site');
gros_titre($nom_site);
echo "</td></tr></table>";
echo "<p>";



if ($new == 'oui'){

	if ($connect_statut == '0minirezo' OR $GLOBALS['meta']["proposer_sites"] > 0) {
		debut_cadre_relief("site-24.gif");
		
		echo generer_url_post_ecrire('sites', "id_parent=$id_rubrique"),
		  "<input type='hidden' name='new' value='oui' />\n",
		  "<input type='hidden' name='analyser_site' value='oui' />\n",
		  "<font face='Verdana,Arial,Sans,sans-serif' size='2'>",
		  _T('texte_referencement_automatique'),
		  "</font>",
		  "\n<div align='right'><input type=\"text\" name=\"url\" class='fondl' size='40' value=\"http://\" />\n",
		  "<input type=\"submit\"  value=\""._T('bouton_ajouter')."\" class='fondo' />\n",
		  "</form>";
		fin_cadre_relief();
		
		echo "\n<p><blockquote><b>"._T('texte_non_fonction_referencement')."</b>";
		$cadre_ouvert = true;
		debut_cadre_enfonce("site-24.gif");
	}
}

$nom_site = entites_html($nom_site);
$url_site = entites_html($url_site);
$url_syndic = entites_html($url_syndic);

 echo generer_url_post_ecrire('sites', ($id_syndic ? "id_syndic=$id_syndic" : "new=$new") . "&modifier_site=oui&syndication_old=$syndication");
echo _T('info_nom_site_2')."<br />\n";
echo "<input type='text' class='formo' name='nom_site' value=\"$nom_site\" size='40'>\n<p>";
if (strlen($url_site)<8) $url_site="http://";
echo _T('entree_adresse_site')."<br />\n";
echo "<input type='text' class='formo' name='url_site' value=\"$url_site\" size='40'>\n<p>";

	if ($id_rubrique == 0) $logo_parent = "racine-site-24.gif";
	else {
		$result=spip_query("SELECT id_parent FROM spip_rubriques WHERE id_rubrique='$id_rubrique'");

		while($row=spip_fetch_array($result)){
			$parent_parent=$row['id_parent'];
		}
		if ($parent_parent == 0) $logo_parent = "secteur-24.gif";
		else $logo_parent = "rubrique-24.gif";
	}

	debut_cadre_couleur("$logo_parent", false, "", _T('entree_interieur_rubrique'));

	// selecteur de rubriques
	$selecteur_rubrique = charger_fonction('chercher_rubrique', 'inc');
	$restreint = ($GLOBALS['statut'] == 'publie');
	echo $selecteur_rubrique($id_rubrique, 'site', $restreint);

	fin_cadre_couleur();

echo "<p /><b>"._T('entree_description_site')."</b><br />\n";
echo "<textarea name='descriptif' rows='8' class='forml' cols='40' wrap=soft>";
echo entites_html($descriptif);
echo "</textarea>\n";

$activer_syndic = $GLOBALS['meta']["activer_syndic"];

echo "\n<input type='hidden' name='syndication_old' value=\"$syndication\">";

if ($activer_syndic != "non") {
	debut_cadre_enfonce('feed.png');
	if ($syndication == "non") {
		echo "\n<input type='radio' name='syndication' value='non' id='syndication_non' CHECKED>";
	}
	else {
		echo "\n<input type='radio' name='syndication' value='non' id='syndication_non'>";
	}
	echo " <b><label for='syndication_non'>",
		_T('bouton_radio_non_syndication'),
		"</label></b>\n<p>";

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
	echo "<br />\n";

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
		echo "<INPUT TYPE='text' CLASS='formo' NAME='url_syndic' VALUE=\"$url_syndic\" SIZE='40'>\n<P>";
		echo "<INPUT TYPE='hidden' NAME='old_syndic' VALUE=\"$url_syndic\">\n";
	}
	echo "</td></tr></table>";

	fin_cadre_enfonce();
} 
else {
	echo "\n<INPUT TYPE='Hidden' NAME='syndication' VALUE=\"$syndication\">";
	echo "\n<INPUT TYPE='hidden' NAME='url_syndic' VALUE=\"$url_syndic\"";
}


if ($champs_extra) {
		include_spip('inc/extra');
		extra_saisie($extra, 'sites', intval($id_secteur));
	}


echo "\n<div ALIGN='right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_enregistrer')."' CLASS='fondo'></div>";
echo "\n</form>";

if ($cadre_ouvert) {
	fin_cadre_enfonce();
	echo "</blockquote>\n";
}

fin_cadre_formulaire();

fin_page();
}
?>
