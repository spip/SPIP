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
include_ecrire ("inc_rubriques.php3");
include_ecrire ("inc_logos.php3");
include_ecrire ("inc_mots.php3");
include_ecrire ("inc_documents.php3");
include_ecrire ("inc_abstract_sql.php3");

$f = find_in_path("inc_naviguer.php");
include($f ? $f : (_DIR_INCLUDE . "inc_naviguer.php"));

////// debut du script

$id_parent = intval($id_parent);
$id_rubrique = intval($id_rubrique);
$flag_mots = lire_meta("articles_mots");
$flag_editable = ($connect_statut == '0minirezo' AND (acces_rubrique($id_parent) OR acces_rubrique($id_rubrique))); // id_parent necessaire en cas de creation de sous-rubrique


if (!$titre) {
	if ($modifier_rubrique == "oui") calculer_rubriques();
}
else {
	// creation, le cas echeant
	if ($new == 'oui' AND $flag_editable AND !$id_rubrique) {
		$id_rubrique = spip_abstract_insert("spip_rubriques", 
			"(titre, id_parent)",
			"('"._T('item_nouvelle_rubrique')."', '$id_parent')");

		// Modifier le lien de base pour qu'il prenne en compte le nouvel id
		unset($_POST['id_parent']);
		$_POST['id_rubrique'] = $id_rubrique;
		$clean_link = new Link();
	}

	// si c'est une rubrique-secteur contenant des breves, ne deplacer
	// que si $confirme_deplace == 'oui'

	if ((spip_num_rows(spip_query("SELECT id_rubrique FROM spip_breves WHERE id_rubrique='$id_rubrique' LIMIT 1 OFFSET 0")) > 0)
	AND ($confirme_deplace != 'oui')) {
		$id_parent = 0;
	}

	if ($flag_editable) {

		if ($champs_extra) {
			include_ecrire("inc_extra.php3");
			$champs_extra = ", extra = '".addslashes(extra_recup_saisie("rubriques"))."'";
		} 
		spip_query("UPDATE spip_rubriques SET " .
(acces_rubrique($id_parent) ? "id_parent='$id_parent'," : "") . "
titre='" . addslashes($titre) ."',
descriptif='" . addslashes($descriptif) . "',
texte='" . addslashes($texte) . "'
$champs_extra
WHERE id_rubrique=$id_rubrique");
	}

	calculer_rubriques();
	calculer_langues_rubriques();

	// invalider et reindexer
	if ($invalider_caches) {
		include_ecrire ("inc_invalideur.php3");
		suivre_invalideur("id='id_rubrique/$id_rubrique'");
	}
	if (lire_meta('activer_moteur') == 'oui') {
		include_ecrire ("inc_index.php3");
		marquer_indexer('rubrique', $id_rubrique);
	}
 }

//
// Appliquer le changement de langue
//
if ($changer_lang AND $id_rubrique>0 AND lire_meta('multi_rubriques') == 'oui' AND (lire_meta('multi_secteurs') == 'non' OR $id_parent == 0) AND $flag_editable) {
	if ($changer_lang != "herit")
		spip_query("UPDATE spip_rubriques SET lang='".addslashes($changer_lang)."', langue_choisie='oui' WHERE id_rubrique=$id_rubrique");
	else {
		if ($id_parent == 0)
			$langue_parent = lire_meta('langue_site');
		else {
			$row = spip_fetch_array(spip_query("SELECT lang FROM spip_rubriques WHERE id_rubrique=$id_parent"));
			$langue_parent = $row['lang'];
		}
		spip_query("UPDATE spip_rubriques SET lang='".addslashes($langue_parent)."', langue_choisie='non' WHERE id_rubrique=$id_rubrique");
	}
	calculer_langues_rubriques();
}

//
// recuperer les infos sur cette rubrique
//

if ($row=spip_fetch_array(spip_query("SELECT * FROM spip_rubriques WHERE id_rubrique='$id_rubrique'"))){
	$id_parent=$row['id_parent'];
	$titre=$row['titre'];
	$descriptif=$row['descriptif'];
	$texte=$row['texte'];
	$statut = $row['statut'];
	$extra = $row["extra"];
	$langue_rubrique = $row['lang'];
}

if ($titre)
	$titre_page = "&laquo; ".textebrut(typo($titre))." &raquo;";
else
	$titre_page = _T('titre_naviguer_dans_le_site');


if ($id_rubrique == 0) {
	$nom_site = lire_meta("nom_site");
	$titre = _T('info_racine_site').": ".$nom_site;
}

if ($id_rubrique ==  0) $ze_logo = "racine-site-24.gif";
else if ($id_parent == 0) $ze_logo = "secteur-24.gif";
else $ze_logo = "rubrique-24.gif";



///// debut de la page
debut_page($titre_page, "documents", "rubriques");


//////// parents

debut_grand_cadre();

if ($id_rubrique  > 0) {
	afficher_hierarchie($id_parent);
}

fin_grand_cadre();

changer_typo('', 'rubrique'.$id_rubrique);

debut_gauche();

if ($spip_display != 4) {

  infos_naviguer($id_rubrique, $statut);

//
// Logos de la rubrique
//

  logo_naviguer($id_rubrique);
	
//
// Afficher les boutons de creation d'article et de breve
//

  raccourcis_naviguer($id_rubrique, $id_parent);
}

debut_droite();

debut_cadre_relief($ze_logo);

echo "\n<table cellpadding=0 cellspacing=0 border=0 width='100%'>";
echo "<tr width='100%'><td width='100%' valign='top'>";
gros_titre((!acces_restreint_rubrique($id_rubrique) ? '' :
	   http_img_pack("admin-12.gif",'', "width='12' height='12'",
			 _T('info_administrer_rubrique'))) .
	   $titre);
echo "</td>";

if ($id_rubrique > 0 AND $flag_editable) {
	echo "<td>", http_img_pack("rien.gif", ' ', "width='5'") ."</td>\n";
	echo "<td  align='$spip_lang_right' valign='top'>";
	icone(_T('icone_modifier_rubrique'), "rubriques_edit.php3?id_rubrique=$id_rubrique&retour=nav", $ze_logo, "edit.gif");
	echo "</td>";
}
echo "</tr>\n";

if (strlen($descriptif) > 1) {
	echo "<tr><td>\n";
	echo "<div align='$spip_lang_left' style='padding: 5px; border: 1px dashed #aaaaaa;'>";
	echo "<font size=2 face='Verdana,Arial,Sans,sans-serif'>";
	echo propre($descriptif."~");
	echo "</font>";
	echo "</div></td></tr>\n";
}

echo "</table>\n";

	if ($champs_extra AND $extra) {
		include_ecrire("inc_extra.php3");
		extra_affichage($extra, "rubriques");
	}


/// Mots-cles
if ($flag_mots!= 'non' AND $id_rubrique > 0) {
	echo "\n<p>";
	formulaire_mots('rubriques', $id_rubrique,  $nouv_mot, $supp_mot, $cherche_mot, $flag_editable);
}


if (strlen($texte) > 1) {
	echo "\n<p><div align='justify'><font size=3 face='Verdana,Arial,Sans,sans-serif'>";
	echo justifier(propre($texte));
	echo "&nbsp;</font></div>";
}


//
// Langue de la rubrique
//

langue_naviguer($id_rubrique, $id_parent, $flag_editable);

fin_cadre_relief();


//
// Gerer les modifications...
//

contenu_naviguer($id_rubrique, $id_parent, $ze_logo,$flag_editable);

/// Documents associes a la rubrique

if ($id_rubrique>0) {
	# modifs de la description d'un des docs joints
	if ($flag_editable) maj_documents($id_rubrique, 'rubrique');
	 afficher_documents_non_inclus($id_rubrique, "rubrique", $flag_editable);
}

////// Supprimer cette rubrique (si vide)

if (($id_rubrique>0) AND tester_rubrique_vide($id_rubrique) AND $flag_editable) {
	$link = new Link('naviguer.php3');
	$link->addVar('id_rubrique', $id_parent);
	$link->addVar('supp_rubrique', $id_rubrique);

	echo "<p><div align='center'>";
	icone(_T('icone_supprimer_rubrique'), $link->getUrl(), "$ze_logo", "supprimer.gif");
	echo "</div><p>";


}

fin_page();

?>
