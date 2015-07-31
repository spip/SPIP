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
include_spip('inc/mots');
include_spip('base/abstract_sql');

function exec_mots_tous_dist()
{
  global $acces_comite, $acces_forum, $acces_minirezo, $new, $articles, $breves, $change_type, $conf_mot, $connect_statut, $connect_toutes_rubriques, $descriptif, $id_groupe, $modifier_groupe, $obligatoire, $rubriques, $spip_lang, $spip_lang_right, $supp_group, $syndic, $texte, $unseul;

  $id_groupe = intval($id_groupe);

  if ($conf_mot = intval($conf_mot)) {
	$result = spip_query("SELECT * FROM spip_mots WHERE id_mot=$conf_mot");
	if ($row = spip_fetch_array($result)) {
		$id_mot = $row['id_mot'];
		$titre_mot = typo($row['titre']);
		$type_mot = typo($row['type']);

		if ($connect_statut=="0minirezo") $aff_articles="'prepa','prop','publie','refuse'";
		else $aff_articles="'prop','publie'";

		$nb_articles = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_mots_articles AS lien, spip_articles AS article WHERE lien.id_mot=$conf_mot AND article.id_article=lien.id_article AND (article.statut IN ($aff_articles))>0 AND article.statut!='refuse'"));
		$nb_articles = $nb_articles['n'];

		$nb_rubriques = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_mots_rubriques AS lien, spip_rubriques AS rubrique WHERE lien.id_mot=$conf_mot AND rubrique.id_rubrique=lien.id_rubrique"));
		$nb_rubriques = $nb_rubriques['n'];

		$nb_breves = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_mots_breves AS lien, spip_breves AS breve WHERE lien.id_mot=$conf_mot AND breve.id_breve=lien.id_breve AND (breve.statut IN ($aff_articles))>0 AND breve.statut!='refuse'"));
		$nb_breves = $nb_breves['n'];

		$nb_sites = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_mots_syndic AS lien, spip_syndic AS syndic WHERE lien.id_mot=$conf_mot AND syndic.id_syndic=lien.id_syndic	AND (syndic.statut IN ($aff_articles))>0 AND syndic.statut!='refuse'"));
		$nb_sites = $nb_sites['n'];

		$nb_forum = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_mots_forum AS lien, spip_forum AS forum WHERE lien.id_mot=$conf_mot AND forum.id_forum=lien.id_forum AND forum.statut='publie'"));
		$nb_forum = $nb_forum['n'];

		// si le mot n'est pas lie, on demande sa suppression
		if ($nb_articles + $nb_breves + $nb_sites + $nb_forum == 0) {
		  redirige_par_entete(generer_url_ecrire("mots_edit","supp_mot=$id_mot&redirect_ok=oui&redirect=" . generer_url_retour('mots_tous'), true));
		} // else traite plus loin (confirmation de suppression)
	}
}


  if (acces_mots()) {
	if ($modifier_groupe == "oui") {
		$change_type = (corriger_caracteres($change_type));
		$texte = (corriger_caracteres($texte));
		$descriptif = (corriger_caracteres($descriptif));

		if (!$new) {	// modif groupe
			spip_query("UPDATE spip_mots SET type=" . spip_abstract_quote($change_type) . " WHERE id_groupe=$id_groupe");


			spip_query("UPDATE spip_groupes_mots SET titre=" . spip_abstract_quote($change_type) . ", texte=" . spip_abstract_quote($texte) . ", descriptif=" . spip_abstract_quote($descriptif) . ", unseul=" . spip_abstract_quote($unseul) . ", obligatoire=" . spip_abstract_quote($obligatoire) . ", articles=" . spip_abstract_quote($articles) . ", breves=" . spip_abstract_quote($breves) . ", rubriques=" . spip_abstract_quote($rubriques) . ", syndic=" . spip_abstract_quote($syndic) . ",	minirezo=" . spip_abstract_quote($acces_minirezo) . ", comite=" . spip_abstract_quote($acces_comite) . ", forum=" . spip_abstract_quote($acces_forum) . " WHERE id_groupe=$id_groupe");

		} else {	// creation groupe
		  spip_abstract_insert('spip_groupes_mots', "(titre, texte, descriptif, unseul,  obligatoire, articles, breves, rubriques, syndic, minirezo, comite, forum)", "(" . spip_abstract_quote($change_type) . ", " . spip_abstract_quote($texte) . " , " . spip_abstract_quote($descriptif) . " , " . spip_abstract_quote($unseul) . " , " . spip_abstract_quote($obligatoire) . " , " . spip_abstract_quote($articles) . " ," . spip_abstract_quote($breves) . " , " . spip_abstract_quote($rubriques) . " , " . spip_abstract_quote($syndic) . " , " . spip_abstract_quote($acces_minirezo) . " ,  " . spip_abstract_quote($acces_comite) . " , " . spip_abstract_quote($acces_forum) . " )");
		}
	}
	if ($supp_group){
		spip_query("DELETE FROM spip_groupes_mots WHERE id_groupe=" . intval($supp_group));
	}
 }


pipeline('exec_init',array('args'=>array('exec'=>'mots_tous'),'data'=>''));
debut_page(_T('titre_page_mots_tous'), "documents", "mots");
debut_gauche();

echo pipeline('affiche_gauche',array('args'=>array('exec'=>'mots_tous'),'data'=>''));
creer_colonne_droite();
echo pipeline('affiche_droite',array('args'=>array('exec'=>'mots_tous'),'data'=>''));
debut_droite();

gros_titre(_T('titre_mots_tous'));
 if (acces_mots()) {
  echo typo(_T('info_creation_mots_cles')) . aide ("mots") ;
  }
echo "<br><br>";

/////

if ($conf_mot>0) {
	if ($nb_articles == 1) {
		$texte_lie = _T('info_un_article')." ";
	} else if ($nb_articles > 1) {
		$texte_lie = _T('info_nombre_articles', array('nb_articles' => $nb_articles)) ." ";
	}
	if ($nb_breves == 1) {
		$texte_lie .= _T('info_une_breve')." ";
	} else if ($nb_breves > 1) {
		$texte_lie .= _T('info_nombre_breves', array('nb_breves' => $nb_breves))." ";
	}
	if ($nb_sites == 1) {
		$texte_lie .= _T('info_un_site')." ";
	} else if ($nb_sites > 1) {
		$texte_lie .= _T('info_nombre_sites', array('nb_sites' => $nb_sites))." ";
	}
	if ($nb_rubriques == 1) {
		$texte_lie .= _T('info_une_rubrique')." ";
	} else if ($nb_rubriques > 1) {
		$texte_lie .= _T('info_nombre_rubriques', array('nb_rubriques' => $nb_rubriques))." ";
	}

	debut_boite_info();
	echo "<div class='serif'>";
	echo _T('info_delet_mots_cles', array('titre_mot' => $titre_mot, 'type_mot' => $type_mot, 'texte_lie' => $texte_lie));

	echo "<UL>";
	echo "<LI><B><A href='", generer_url_ecrire('mots_edit', "supp_mot=$id_mot&redirect_ok=oui&redirect=" . generer_url_retour('mots_tous')),
	  "'>",
	  _T('item_oui'),
	  "</A>,</B> ",
	  _T('info_oui_suppression_mot_cle');
	echo "<LI><B><A href='" . generer_url_ecrire("mots_tous","") . "'>"._T('item_non')."</A>,</B> "._T('info_non_suppression_mot_cle');
	echo "</UL>";
	echo "</div>";
	fin_boite_info();
	echo "<P>";
}

//
// On boucle d'abord sur les groupes de mots
//

 $result_groupes = spip_query("SELECT *, ".creer_objet_multi ("titre", "$spip_lang")." FROM spip_groupes_mots ORDER BY multi");


while ($row_groupes = spip_fetch_array($result_groupes)) {
	$id_groupe = $row_groupes['id_groupe'];
	$titre_groupe = typo($row_groupes['titre']);
	$descriptif = $row_groupes['descriptif'];
	$texte = $row_groupes['texte'];
	$unseul = $row_groupes['unseul'];
	$obligatoire = $row_groupes['obligatoire'];
	$articles = $row_groupes['articles'];
	$breves = $row_groupes['breves'];
	$rubriques = $row_groupes['rubriques'];
	$syndic = $row_groupes['syndic'];
	$acces_minirezo = $row_groupes['minirezo'];
	$acces_comite = $row_groupes['comite'];
	$acces_forum = $row_groupes['forum'];

	// Afficher le titre du groupe
	debut_cadre_enfonce("groupe-mot-24.gif", false, '', $titre_groupe);
	// Affichage des options du groupe (types d'elements, permissions...)
	echo "<font face='Verdana,Arial,Sans,sans-serif' size=1>";
	if ($articles == "oui") echo "> "._T('info_articles_2')." &nbsp;&nbsp;";
	if ($breves == "oui") echo "> "._T('info_breves_02')." &nbsp;&nbsp;";
	if ($rubriques == "oui") echo "> "._T('info_rubriques')." &nbsp;&nbsp;";
	if ($syndic == "oui") echo "> "._T('icone_sites_references')." &nbsp;&nbsp;";

	if ($unseul == "oui" OR $obligatoire == "oui") echo "<br>";
	if ($unseul == "oui") echo "> "._T('info_un_mot')." &nbsp;&nbsp;";
	if ($obligatoire == "oui") echo "> "._T('info_groupe_important')." &nbsp;&nbsp;";

	echo "<br>";
	if ($acces_minirezo == "oui") echo "> "._T('info_administrateurs')." &nbsp;&nbsp;";
	if ($acces_comite == "oui") echo "> "._T('info_redacteurs')." &nbsp;&nbsp;";
	if ($acces_forum == "oui") echo "> "._T('info_visiteurs_02')." &nbsp;&nbsp;";

	echo "</font>";
	if ($descriptif) {
		echo "<p><div style='border: 1px dashed #aaaaaa;'>";
		echo "<font size='2' face='Verdana,Arial,Sans,sans-serif'>";
		echo "<b>",_T('info_descriptif'),"</b> ";
		echo propre($descriptif);
		echo "&nbsp; ";
		echo "</font>";
		echo "</div>";
	}

	if (strlen($texte)>0){
		echo "<FONT FACE='Verdana,Arial,Sans,sans-serif'>";
		echo "<P>".propre($texte);
		echo "</FONT>";
	}

	//
	// Afficher les mots-cles du groupe
	//
	$supprimer_groupe = afficher_groupe_mots($id_groupe);

	if (acces_mots() AND !$conf_mot){
		echo "\n<table cellpadding='0' cellspacing='0' border='0' width='100%'>";
		echo "<tr>";
		echo "<td>";
		icone(_T('icone_modif_groupe_mots'), generer_url_ecrire("mots_type","id_groupe=$id_groupe"), "groupe-mot-24.gif", "edit.gif");
		echo "</td>";
		if ($supprimer_groupe) {
			echo "<td>";
			icone(_T('icone_supprimer_groupe_mots'), generer_url_ecrire("mots_tous","supp_group=$id_groupe"), "groupe-mot-24.gif", "supprimer.gif");
			echo "</td>";
			echo "<td> &nbsp; </td>"; // Histoire de forcer "supprimer" un peu plus vers la gauche
		}
		echo "<td>";
		echo "<div align='$spip_lang_right'>";
		icone(_T('icone_creation_mots_cles'), generer_url_ecrire("mots_edit","new=oui&id_groupe=$id_groupe&redirect=" . generer_url_retour('mots_tous')), "mot-cle-24.gif", "creer.gif");
		echo "</div>";
		echo "</td></tr></table>";
	}	

	fin_cadre_enfonce();

}

 if (acces_mots()  AND !$conf_mot){
	echo "<p>&nbsp;</p><div align='right'>";
	icone(_T('icone_creation_groupe_mots'), generer_url_ecrire("mots_type","new=oui"), "groupe-mot-24.gif", "creer.gif");
	echo "</div>";
}

fin_page();
}

?>
