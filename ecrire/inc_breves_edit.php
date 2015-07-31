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

include_ecrire("inc_presentation");
include_ecrire("inc_rubriques");
include_ecrire ("inc_documents");
include_ecrire ("inc_barre");

function breves_edit_dist()
{
global
  $champs_extra,
  $connect_statut,
  $id_breve,
  $id_rubrique,
  $lien_titre,
  $lien_url,
  $new,
  $spip_ecran,
  $texte;

$id_breve = intval($id_breve);

if ($new != "oui") {
	$query = "SELECT * FROM spip_breves WHERE id_breve=$id_breve";
	$result = spip_query($query);
	
	if ($row=spip_fetch_array($result)) {
		$id_breve=$row['id_breve'];
		$titre=$row['titre'];
		$texte=$row['texte'];
		$lien_titre=$row['lien_titre'];
		$lien_url=$row['lien_url'];
		$statut=$row['statut'];
		$id_rubrique=$row['id_rubrique'];
		$extra = $row['extra'];
	}
}
else {
	$titre = filtrer_entites(_T('titre_nouvelle_breve'));
	$texte = "";
	$onfocus = " onfocus=\"if(!antifocus){this.value='';antifocus=true;}\"";
	$lien_titre='';
	$lien_url='';
	$statut = "prop";
	$id_rubrique = intval($id_rubrique);
}


debut_page(_T('titre_page_breves_edit', array('titre' => $titre)), "documents", "breves");


debut_grand_cadre();

afficher_hierarchie($id_rubrique);

fin_grand_cadre();
debut_gauche();
if ($new != 'oui' AND ($connect_statut=="0minirezo" OR $statut=="prop")) {
	maj_documents($id_breve, 'breve');
	afficher_documents_colonne($id_breve, "breve", true);
}
debut_droite();
debut_cadre_formulaire();


if ($new != "oui") {
	echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
	echo "<tr width='100%'>";
	echo "<td>";
		icone(_T('icone_retour'), generer_url_ecrire("breves_voir","id_breve=$id_breve"), "breve-24.gif", "rien.gif");
	
	echo "</td>";
	echo "<td>", http_img_pack("rien.gif", ' ', "width='10'"), "</td>\n";
	echo "<td width='100%'>";
	echo _T('info_modifier_breve');
	gros_titre($titre);
	echo "</td></tr></table>";
	echo "<p>";
}


if ($connect_statut=="0minirezo" OR $statut=="prop" OR $new == "oui") {
	if ($id_breve) $lien = "?id_breve=$id_breve";
	echo "<form action='" . generer_url_ecrire("breves_voir","$lien") . "' method='post' name='formulaire'>";

	echo "<INPUT TYPE='Hidden' NAME='modifier_breve' VALUE=\"oui\">";
	echo "<INPUT TYPE='Hidden' NAME='id_breve' VALUE=\"$id_breve\">";
	echo "<INPUT TYPE='Hidden' NAME='statut_old' VALUE=\"$statut\">";
	if ($new == "oui") echo "<INPUT TYPE='Hidden' NAME='new' VALUE=\"oui\">";

	$titre = entites_html($titre);
	$lien_titre = entites_html($lien_titre);

	echo _T('entree_titre_obligatoire');
	echo "<INPUT TYPE='text' CLASS='formo' NAME='titre' VALUE=\"$titre\" SIZE='40' $onfocus>";


	/// Dans la rubrique....
	echo "<INPUT TYPE='Hidden' NAME='id_rubrique_old' VALUE=\"$id_rubrique\"><p />";

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


	debut_cadre_couleur("$logo_parent", false, "",_T('entree_interieur_rubrique').aide ("brevesrub"));

	// selecteur de rubrique (pas d'ajax car toujours racine)
	include_ecrire('inc_rubriques');
	echo selecteur_rubrique_html($id_rubrique, 'breve', ($statut == 'publie'));

	fin_cadre_couleur();
	
	if ($spip_ecran == "large") $rows = 28;
	else $rows = 15;
	
	echo "<p /><B>"._T('entree_texte_breve')."</B><BR>";
	echo afficher_barre('document.formulaire.texte');
	echo "<TEXTAREA NAME='texte' ".$GLOBALS['browser_caret']." ROWS='$rows' CLASS='formo' COLS='40' wrap=soft>";
	echo $texte;
	echo "</TEXTAREA><P>\n";


	echo _T('entree_liens_sites').aide ("breveslien")."<BR>";
	echo _T('info_titre')."<BR>";
	echo "<INPUT TYPE='text' CLASS='forml' NAME='lien_titre' VALUE=\"$lien_titre\" SIZE='40'><BR>";

	if (strlen($lien_url) < 8) $lien_url="http://";
	echo _T('info_url')."<BR>";
	echo "<INPUT TYPE='text' CLASS='forml' NAME='lien_url' VALUE=\"$lien_url\" SIZE='40'><P>";

	if ($champs_extra) {
		include_ecrire("inc_extra");
		extra_saisie($extra, 'breves', $id_rubrique);
	}

	if ($connect_statut=="0minirezo" AND acces_rubrique($id_rubrique)) {
		debut_cadre_relief();
		echo "<B>"._T('entree_breve_publiee')."</B>\n";

		echo "<SELECT NAME='statut' SIZE=1 CLASS='fondl'>\n";
		
		echo "<OPTION".mySel("prop",$statut)." style='background-color: white'>"._T('item_breve_proposee')."\n";		
		echo "<OPTION".mySel("refuse",$statut). http_style_background('rayures-sup.gif'). ">"._T('item_breve_refusee')."\n";		
		echo "<OPTION".mySel("publie",$statut)." style='background-color: #B4E8C5'>"._T('item_breve_validee')."\n";		

		echo "</SELECT>".aide ("brevesstatut")."<P>\n";
		fin_cadre_relief();
	}
	else {
		echo "<INPUT TYPE='Hidden' NAME='statut' VALUE=\"$statut\">";
	}
	echo "<P ALIGN='right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_enregistrer')."' CLASS='fondo'  >";
	echo "</FORM>";
}
else echo "<H2>"._T('info_page_interdite')."</H2>";

fin_cadre_formulaire();
fin_page();
}

?>
