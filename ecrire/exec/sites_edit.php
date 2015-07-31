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
  global $connect_statut, $descriptif, $id_rubrique, $id_secteur, $id_syndic, $new, $nom_site, $syndication, $url_site, $url_syndic, $connect_id_rubrique;

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
		$in = !$connect_id_rubrique ? ''
		  : (' WHERE id_rubrique IN (' . join(',', $connect_id_rubrique) . ')');
		$row = spip_fetch_array(spip_query("SELECT id_rubrique FROM spip_rubriques$in ORDER BY id_rubrique DESC LIMIT 1"));		
		$id_rubrique = $row['id_rubrique'];
	}
}
pipeline('exec_init',array('args'=>array('exec'=>'sites_edit','id_syndic'=>$id_syndic),'data'=>''));

$commencer_page = charger_fonction('commencer_page', 'inc');
echo $commencer_page(_T('info_site_reference_2'), "naviguer", "sites", $id_rubrique);

debut_grand_cadre();

echo afficher_hierarchie($id_rubrique);

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



if ($new == 'oui'
AND (
	$connect_statut == '0minirezo' OR $GLOBALS['meta']["proposer_sites"] > 0)
) {

	$form_auto = "<font face='Verdana,Arial,Sans,sans-serif' size='2'>"
		. _T('texte_referencement_automatique')
		. "</font>"
		. "\n<div align='right'><input type=\"text\" name=\"url\" class='fondl' size='40' value=\"http://\" />\n"
		. "\n<input type='hidden' name='id_parent' value='".intval(_request('id_rubrique'))."' />\n"
		. "<input type=\"submit\"  value=\""._T('bouton_ajouter')."\" class='fondo' />\n";

	$form_auto = generer_action_auteur('editer_site',
		'auto',
		generer_url_ecrire('sites'),
		$form_auto,
		" method='post' name='formulaireauto'"
	);

	echo
		debut_cadre_relief("site-24.gif", true)
		. $form_auto
		. fin_cadre_relief(true)
		. "\n<p><blockquote><b>"._T('texte_non_fonction_referencement')."</b>";


	$cadre_ouvert = true;
	$form = debut_cadre_enfonce("site-24.gif");
}

$nom_site = entites_html($nom_site);
$url_site = entites_html($url_site);
$url_syndic = entites_html($url_syndic);


$form .= _T('info_nom_site_2')."<br />\n";
$form .= "<input type='text' class='formo' name='nom_site' value=\"$nom_site\" size='40'>\n<p>";
if (strlen($url_site)<8) $url_site="http://";
$form .= _T('entree_adresse_site')."<br />\n";
$form .= "<input type='text' class='formo' name='url_site' value=\"$url_site\" size='40'>\n<p>";

	if ($id_rubrique == 0) $logo_parent = "racine-site-24.gif";
	else {
		$result=spip_query("SELECT id_parent FROM spip_rubriques WHERE id_rubrique='$id_rubrique'");

		while($row=spip_fetch_array($result)){
			$parent_parent=$row['id_parent'];
		}
		if ($parent_parent == 0) $logo_parent = "secteur-24.gif";
		else $logo_parent = "rubrique-24.gif";
	}

	$form .= debut_cadre_couleur("$logo_parent", true, "", _T('entree_interieur_rubrique'));

	// selecteur de rubriques
	$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc');
	$restreint = ($GLOBALS['statut'] == 'publie');
	$form .= $chercher_rubrique($id_rubrique, 'site', $restreint);

	$form .= fin_cadre_couleur(true);

$form .= "<p /><b>"._T('entree_description_site')."</b><br />\n";
$form .= "<textarea name='descriptif' rows='8' class='forml' cols='40' wrap=soft>";
$form .= entites_html($descriptif);
$form .= "</textarea>\n";

$activer_syndic = $GLOBALS['meta']["activer_syndic"];

$form .= "\n<input type='hidden' name='syndication_old' value=\"$syndication\">";

if ($activer_syndic != "non") {
	$form .= debut_cadre_enfonce('feed.png', true);
	if ($syndication == "non") {
		$form .= "\n<input type='radio' name='syndication' value='non' id='syndication_non' CHECKED>";
	}
	else {
		$form .= "\n<input type='radio' name='syndication' value='non' id='syndication_non'>";
	}
	$form .= " <b><label for='syndication_non'>"
		. _T('bouton_radio_non_syndication')
		. "</label></b>\n<p>";

	if ($syndication == "non") {
		$form .= "<INPUT TYPE='radio' NAME='syndication' VALUE='oui' id='syndication_oui'>";
	}
	else {
		$form .= "<INPUT TYPE='radio' NAME='syndication' VALUE='oui' id='syndication_oui' CHECKED>";
	}
	$form .= " <b><label for='syndication_oui'>"._T('bouton_radio_syndication')."</label></b>";
	$form .= aide("rubsyn");


	$form .= "<table cellpadding=0 cellspacing=0 border=0 width=100%><tr><td width=50>&nbsp;</td><td>";

	if (strlen($url_syndic) < 8) $url_syndic = "http://";
	$form .= _T('entree_adresse_fichier_syndication');
	$form .= "<br />\n";

	// cas d'une liste de flux detectee par feedfinder : menu
	if (preg_match(',^select: (.+),', $url_syndic, $regs)) {
		$feeds = explode(' ',$regs[1]);
		$form .= "<select name='url_syndic'>\n";
		foreach ($feeds as $feed) {
			$form .= '<option value="'.entites_html($feed).'">'.$feed."</option>\n";
		}
		$form .= "</select>\n";
	}
	// cas normal
	else {
		$form .= "<input type='text' class='formo' name='url_syndic' value=\"$url_syndic\" size='40' />\n";
	}
	$form .= "</td></tr></table>";

	$form .= fin_cadre_enfonce(true);
} 


if ($GLOBALS['champs_extra']) {
	include_spip('inc/extra');
	$form .= extra_saisie($extra, 'sites', intval($id_secteur));
}


$form .= "\n<div align='right'><input type='submit' value='"._T('bouton_enregistrer')."' class='fondo' /></div>";

$form = generer_action_auteur('editer_site',
	($new == 'oui') ? $new : $id_syndic,
	generer_url_ecrire('sites'),
	$form,
	" method='post' name='formulaire'"
);

if ($cadre_ouvert) {
	$form .= fin_cadre_enfonce(true);
	$form .= "</blockquote>\n";
}

echo $form;

echo fin_cadre_formulaire(true);

echo fin_page();
}
?>
