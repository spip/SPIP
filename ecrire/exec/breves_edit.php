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
include_spip('inc/documents');
include_spip ('inc/barre');

// http://doc.spip.org/@exec_breves_edit_dist
function exec_breves_edit_dist()
{
	global $connect_statut, $connect_id_rubrique, $spip_ecran;

    $id_breve = intval(_request('id_breve'));
    $id_rubrique  = intval(_request('id_rubrique'));
    $new = _request('new');

    if ($new != "oui") {
	$result = spip_query("SELECT * FROM spip_breves WHERE id_breve=$id_breve");

	
	if ($row=spip_fetch_array($result)) {
		$id_breve=$row['id_breve'];
		$titre=$row['titre'];
		$texte=$row['texte'];
		$lien_titre=$row['lien_titre'];
		$lien_url=$row['lien_url'];
		$statut=$row['statut'];
		$id_rubrique=$row['id_rubrique'];
		$extra = $row['extra'];
	} else die ("<h3>"._T('info_acces_interdit')."</h3>");

    } else {
	$titre = filtrer_entites(_T('titre_nouvelle_breve'));
	$texte = "";
	$onfocus = " onfocus=\"if(!antifocus){this.value='';antifocus=true;}\"";
	$lien_titre='';
	$lien_url='';
	$statut = "prop";
	$row = spip_fetch_array(spip_query("SELECT id_secteur FROM spip_rubriques WHERE id_rubrique = ".intval($id_rubrique)));
	$id_rubrique = $row['id_secteur'];
}

pipeline('exec_init',array('args'=>array('exec'=>'breves_edit','id_breve'=>$id_breve),'data'=>''));

$commencer_page = charger_fonction('commencer_page', 'inc');
echo $commencer_page(_T('titre_page_breves_edit', array('titre' => $titre)), "naviguer", "breves", $id_rubrique);


debut_grand_cadre();

echo afficher_hierarchie($id_rubrique);

fin_grand_cadre();
debut_gauche();
if ($new != 'oui' AND ($connect_statut=="0minirezo" OR $statut=="prop")) {
	# affichage sur le cote des images, en reperant les inserees
	# note : traiter_modeles($texte, true) repere les doublons
	# aussi efficacement que propre(), mais beaucoup plus rapidement
	traiter_modeles("$titre$texte", true);
	afficher_documents_colonne($id_breve, "breve");
}
echo pipeline('affiche_gauche',array('args'=>array('exec'=>'breves_edit','id_breve'=>$id_breve),'data'=>''));
creer_colonne_droite();
echo pipeline('affiche_droite',array('args'=>array('exec'=>'breves_edit','id_breve'=>$id_breve),'data'=>''));
debut_droite();
debut_cadre_formulaire();


if ($new != "oui") {
	echo "\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>";
	echo "\n<tr>";
	echo "<td>";
		icone(_T('icone_retour'), generer_url_ecrire("breves_voir","id_breve=$id_breve"), "breve-24.gif", "rien.gif");
	
	echo "</td>";
	echo "\n<td>", http_img_pack("rien.gif", ' ', "width='10'"), "</td>\n";
	echo "<td width='100%'>";
	echo _T('info_modifier_breve');
	gros_titre($titre);
	echo "</td></tr></table><br />";
}


if ($connect_statut=="0minirezo" OR $statut=="prop" OR $new == "oui") {
	if ($id_breve) $lien = "id_breve=$id_breve";

	$titre = entites_html($titre);
	$lien_titre = entites_html($lien_titre);

	$form = _T('entree_titre_obligatoire')
	. "<input type='text' class='formo' name='titre' value=\"$titre\" size='40' $onfocus />"


	/// Dans la rubrique....
	. "<input type='hidden' name='id_rubrique_old' value=\"$id_rubrique\" /><p />";

	if ($id_rubrique == 0) $logo_parent = "racine-site-24.gif";
	else {
		$result=spip_query("SELECT id_parent FROM spip_rubriques WHERE id_rubrique='$id_rubrique'");

		while($row=spip_fetch_array($result)){
			$parent_parent=$row['id_parent'];
		}
		if ($parent_parent == 0) $logo_parent = "secteur-24.gif";
		else $logo_parent = "rubrique-24.gif";
	}


	$form .= debut_cadre_couleur("$logo_parent", true, "",_T('entree_interieur_rubrique').aide ("brevesrub"));

	// appel du script a la racine, faut choisir 
	// on prend le dernier secteur cree
	// dans une liste restreinte si admin restreint

	if (!$id_rubrique) {
		$in = !$connect_id_rubrique ? ''
		  : (' AND id_rubrique IN (' . join(',', $connect_id_rubrique) . ')');
		$row_rub = spip_fetch_array(spip_query("SELECT id_rubrique FROM spip_rubriques WHERE id_parent=0$in ORDER BY id_rubrique DESC LIMIT 1"));		
		$id_rubrique = $row_rub['id_rubrique'];
	}

	// selecteur de rubrique (en general pas d'ajax car toujours racine)
	$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc');
	$form .= $chercher_rubrique($id_rubrique, 'breve', ($statut == 'publie'));

	$form .= fin_cadre_couleur(true);
	
	if ($spip_ecran == "large") $rows = 28;
	else $rows = 15;
	
	$form .= "<p /><b>"._T('entree_texte_breve')."</b><br />\n"
	. afficher_barre('document.formulaire.texte')
	. "<textarea name='texte' ".$GLOBALS['browser_caret']." rows='$rows' class='formo' cols='40'>"
	. entites_html($texte)
	. "</textarea><p />\n"
	. _T('entree_liens_sites')
	. aide ("breveslien")
	. "<br />\n"
	. _T('info_titre')."<br />\n"
	. "<input type='text' class='forml' name='lien_titre' value=\"$lien_titre\" size='40' /><br />\n"
	. _T('info_url')."<br />\n"
	. "<input type='text' class='forml' name='lien_url' value=\"$lien_url\" size='40' /><p />";

	if ($GLOBALS['champs_extra']) {
		include_spip('inc/extra');
		$form .= extra_saisie($extra, 'breves', $id_rubrique);
	}

	if ($connect_statut=="0minirezo" AND acces_rubrique($id_rubrique)) {
		$form .= debut_cadre_relief('', true)
		. "<b>"._T('entree_breve_publiee')."</b>\n"
		. "<select name='statut' size='1' class='fondl'>\n"
		. "<option".mySel("prop",$statut)." style='background-color: white'>"._T('item_breve_proposee')."</option>\n"
		. "<option".mySel("refuse",$statut). http_style_background('rayures-sup.gif'). ">"._T('item_breve_refusee')."</option>\n"
		. "<option".mySel("publie",$statut)." style='background-color: #B4E8C5'>"._T('item_breve_validee')."</option>\n"
		. "</select>".aide ("brevesstatut")."<p />\n"
		. fin_cadre_relief(true);
	}
	$form .= "<p align='right'><input type='submit' value='"._T('bouton_enregistrer')."' class='fondo' /></p>";

	echo generer_action_auteur('editer_breve',
		$new ? $new : $id_breve,
		generer_url_ecrire('breves_voir'),
		$form,
		" method='post' name='formulaire'"
	);

}
else
	echo "<h2>"._T('info_page_interdite')."</h2>";

fin_cadre_formulaire();
echo fin_page();

}

?>
