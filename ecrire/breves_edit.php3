<?php

include ("inc.php3");
include_ecrire ("inc_documents.php3");

function mySel($varaut,$variable){
		$retour= " VALUE=\"$varaut\"";

	if ($variable==$varaut){
		$retour.= " SELECTED";
	}

	return $retour;
}

function enfant($leparent) {
	global $id_parent;
	global $id_rubrique;
 	$query="SELECT * FROM spip_rubriques WHERE id_parent='$leparent' ORDER BY titre";
 	$result=spip_query($query);

	while($row=spip_fetch_array($result)){
		$my_rubrique=$row['id_rubrique'];
		$titre=$row['titre'];
		$descriptif=$row['descriptif'];
		$texte=$row['texte'];
		echo "<OPTION".mySel($my_rubrique,$id_rubrique).">$titre\n";		
	}
}

if ($new != "oui") {
	$query = "SELECT * FROM spip_breves WHERE id_breve='$id_breve'";
	$result = spip_query($query);
	
	if ($row=spip_fetch_array($result)) {
		$id_breve=$row['id_breve'];
		$date_heure=$row['date_heure'];
		$titre=$row['titre'];
		$texte=$row['texte'];
		$lien_titre=$row['lien_titre'];
		$lien_url=$row['lien_url'];
		$statut=$row['statut'];
		$id_rubrique=$row['id_rubrique'];
		
		$pour_doublons = propre ("$titre.$texte");
	}
}
else {
	$titre = filtrer_entites(_T('titre_nouvelle_breve'));
	$statut = "prop";
}

if ($id_document) {
	$query_doc = "SELECT * FROM spip_documents_breves WHERE id_document=$id_document AND id_breve=$id_breve";
	$result_doc = spip_query($query_doc);
	$flag_document_editable = (spip_num_rows($result_doc) > 0);
} else {
	$flag_document_editable = false;
}


$modif_document = $GLOBALS['modif_document'];
if ($modif_document == 'oui' AND $flag_document_editable) {
	$titre_document = addslashes(corriger_caracteres($titre_document));
	$descriptif_document = addslashes(corriger_caracteres($descriptif_document));
	spip_query("UPDATE spip_documents SET titre=\"$titre_document\", descriptif=\"$descriptif_document\" WHERE id_document=$id_document");
 }




debut_page(_T('titre_page_breves_edit', array('titre' => $titre)), "documents", "breves");


debut_grand_cadre();

afficher_parents($id_rubrique);
$parents="~ <img src='img_pack/racine-site-24.gif' width=24 height=24 align='middle'> <A HREF='naviguer.php3?coll=0'><B>"._T('lien_racine_site')."</B></A> ".aide ("rubhier")."<BR>".$parents;

$parents=ereg_replace("~","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$parents);
$parents=ereg_replace("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ","",$parents);

echo "$parents";

fin_grand_cadre();
debut_gauche();
if ($new != 'oui' AND ($connect_statut=="0minirezo" OR $statut=="prop")){
	afficher_documents_colonne($id_breve, "breve", true);
}
debut_droite();
debut_cadre_formulaire();


if ($new != "oui") {
	echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
	echo "<tr width='100%'>";
	echo "<td>";
		icone(_T('icone_retour'), "breves_voir.php3?id_breve=$id_breve", "breve-24.gif", "rien.gif");
	
	echo "</td>";
		echo "<td><img src='img_pack/rien.gif' width=10></td>\n";
	echo "<td width='100%'>";
	echo _T('info_modifier_breve');
	gros_titre($titre);
	echo "</td></tr></table>";
	echo "<p>";
}


if ($connect_statut=="0minirezo" OR $statut=="prop" OR $new == "oui") {
	echo "<FORM ACTION='breves_voir.php3' METHOD='post'>";

	echo "<INPUT TYPE='Hidden' NAME='modifier_breve' VALUE=\"oui\">";
	echo "<INPUT TYPE='Hidden' NAME='id_breve' VALUE=\"$id_breve\">";
	echo "<INPUT TYPE='Hidden' NAME='statut_old' VALUE=\"$statut\">";
	if ($new == "oui") echo "<INPUT TYPE='Hidden' NAME='new' VALUE=\"oui\">";

	$titre = entites_html($titre);
	$lien_titre = entites_html($lien_titre);

	echo _T('entree_titre_obligatoire');
	echo "<INPUT TYPE='text' CLASS='formo' NAME='titre' VALUE=\"$titre\" SIZE='40'><P>";

		echo "<B>"._T('entree_interieur_rubrique')."</B>".aide ("brevesrub")."<BR>\n";



	/// Dans la rubrique....

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

	debut_cadre_relief("$logo_parent");

		echo "<SELECT NAME='id_rubrique' CLASS='forml' SIZE=1>\n";
		enfant(0);
		echo "</SELECT><P>\n";

	fin_cadre_relief();
	
	if ($spip_ecran == "large") $rows = 28;
	else $rows = 15;
	
	echo "<B>"._T('entree_texte_breve')."</B><BR>";
	echo "<TEXTAREA NAME='texte' ROWS='$rows' CLASS='formo' COLS='40' wrap=soft>";
	echo $texte;
	echo "</TEXTAREA><P>\n";


	echo _T('entree_liens_sites').aide ("breveslien")."<BR>";
	echo _T('info_titre')."<BR>";
	echo "<INPUT TYPE='text' CLASS='forml' NAME='lien_titre' VALUE=\"$lien_titre\" SIZE='40'><BR>";

	if (strlen($lien_url) < 8) $lien_url="http://";
	echo _T('info_url')."<BR>";
	echo "<INPUT TYPE='text' CLASS='forml' NAME='lien_url' VALUE=\"$lien_url\" SIZE='40'><P>";

	if (function_exists(champs_extra)) {
		$champs_suppl=champs_extra("breve", $id_breve, $id_secteur);
		include_ecrire("inc_extra.php3");
		extra_saisie(unserialize($row['extra']), $champs_suppl);
	}

	if ($connect_statut=="0minirezo" AND acces_rubrique($id_rubrique)) {
		debut_cadre_relief();
		echo "<B>"._T('entree_breve_publiee')."</B>\n";

		echo "<SELECT NAME='statut' SIZE=1 CLASS='fondl'>\n";
		
		echo "<OPTION".mySel("prop",$statut).">"._T('item_breve_proposee')."\n";		
		echo "<OPTION".mySel("refuse",$statut).">"._T('item_breve_refusee')."\n";		
		echo "<OPTION".mySel("publie",$statut).">"._T('item_breve_validee')."\n";		

		echo "</SELECT>".aide ("brevesstatut")."<P>\n";
		fin_cadre_relief();
	}
	else {
		echo "<INPUT TYPE='Hidden' NAME='statut' VALUE=\"$statut\">";
	}
	echo "<P ALIGN='right'><INPUT TYPE='submit' NAME='Valider' VALUE='"._T('bouton_valider')."' CLASS='fondo'  >";
	echo "</FORM>";
}
else echo "<H2>"._T('info_page_interdite')."</H2>";

fin_cadre_formulaire();
fin_page();

?>
