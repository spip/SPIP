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
include_spip('inc/actions');
include_spip('base/abstract_sql');

// http://doc.spip.org/@exec_mots_tous_dist
function exec_mots_tous_dist()
{
  global $acces_comite, $acces_forum, $acces_minirezo, $new, $articles, $breves, $change_type, $conf_mot, $connect_statut, $connect_toutes_rubriques, $descriptif, $id_groupe, $modifier_groupe, $obligatoire, $rubriques, $spip_lang, $spip_lang_right, $supp_group, $syndic, $texte, $unseul;

  $id_groupe = intval($id_groupe);

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
debut_page(_T('titre_page_mots_tous'), "naviguer", "mots");
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

// Preliminaire: confirmation de suppression d'un mot lie à qqch
// (cf fin de afficher_groupe_mots_boucle executee a l'appel precedent)
// Faudrait ajaxer ça.

  if ($conf_mot = intval($conf_mot)) {
	$row = spip_fetch_array(spip_query("SELECT * FROM spip_mots WHERE id_mot=$conf_mot"));
	$id_mot = $row['id_mot'];
	$titre_mot = typo($row['titre']);
	$type_mot = typo($row['type']);

	if (($na = intval($na)) == 1) {
		$texte_lie = _T('info_un_article')." ";
	} else if ($na > 1) {
		$texte_lie = _T('info_nombre_articles', array('nb_articles' => $na)) ." ";
	}
	if (($nb = intval($nb)) == 1) {
		$texte_lie .= _T('info_une_breve')." ";
	} else if ($nb > 1) {
		$texte_lie .= _T('info_nombre_breves', array('nb_breves' => $nb))." ";
	}
	if (($ns = intval($ns)) == 1) {
		$texte_lie .= _T('info_un_site')." ";
	} else if ($ns > 1) {
		$texte_lie .= _T('info_nombre_sites', array('nb_sites' => $ns))." ";
	}
	if (($nr = intval($nr)) == 1) {
		$texte_lie .= _T('info_une_rubrique')." ";
	} else if ($nr > 1) {
		$texte_lie .= _T('info_nombre_rubriques', array('nb_rubriques' => $nr))." ";
	}

	debut_boite_info();
	echo "<div class='serif'>";
	echo _T('info_delet_mots_cles', array('titre_mot' => $titre_mot, 'type_mot' => $type_mot, 'texte_lie' => $texte_lie));

	echo "<UL>";
	echo "<LI><B><A href='", 
	  redirige_action_auteur('editer_mot', ",$id_mot,,,",'mots_tous'),
	  "'>",
	  _T('item_oui'),
	  "</A>,</B> ",
	  _T('info_oui_suppression_mot_cle');
	echo "<LI><B><A href='" . generer_url_ecrire("mots_tous","") . "'>"._T('item_non')."</A>,</B> "._T('info_non_suppression_mot_cle');
	echo "</UL>";
	echo "</div>";
	fin_boite_info();
	echo "<br />";
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

	echo "<br />";
	if ($acces_minirezo == "oui") echo "> "._T('info_administrateurs')." &nbsp;&nbsp;";
	if ($acces_comite == "oui") echo "> "._T('info_redacteurs')." &nbsp;&nbsp;";
	if ($acces_forum == "oui") echo "> "._T('info_visiteurs_02')." &nbsp;&nbsp;";

	echo "</font>";
	if ($descriptif) {
		echo "<div style='border: 1px dashed #aaaaaa;'>";
		echo "<font size='2' face='Verdana,Arial,Sans,sans-serif'>";
		echo "<b>",_T('info_descriptif'),"</b> ";
		echo propre($descriptif);
		echo "&nbsp; ";
		echo "</font>";
		echo "</div>";
	}

	if (strlen($texte)>0){
		echo "<FONT FACE='Verdana,Arial,Sans,sans-serif'>";
		echo propre($texte);
		echo "</FONT>";
	}

	//
	// Afficher les mots-cles du groupe
	//
	$supprimer_groupe = afficher_groupe_mots($id_groupe);

	echo $supprimer_groupe;

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


//
// Calculer les nombres d'elements (articles, etc.) lies a chaque mot
//

// http://doc.spip.org/@calculer_liens_mots
function calculer_liens_mots()
{

if ($GLOBALS['connect_statut'] =="0minirezo") $aff_articles = "'prepa','prop','publie'";
else $aff_articles = "'prop','publie'";

 $articles = array();
 $result_articles = spip_query("SELECT COUNT(*) as cnt, lien.id_mot FROM spip_mots_articles AS lien, spip_articles AS article	WHERE article.id_article=lien.id_article AND article.statut IN ($aff_articles) GROUP BY lien.id_mot");
 while ($row =  spip_fetch_array($result_articles)){
	$articles[$row['id_mot']] = $row['cnt'];
}


 $rubriques = array();
 $result_rubriques = spip_query("SELECT COUNT(*) AS cnt, lien.id_mot FROM spip_mots_rubriques AS lien, spip_rubriques AS rubrique WHERE rubrique.id_rubrique=lien.id_rubrique GROUP BY lien.id_mot");

 while ($row = spip_fetch_array($result_rubriques)){
	$rubriques[$row['id_mot']] = $row['cnt'];
}

 $breves = array();
 $result_breves = spip_query("SELECT COUNT(*) AS cnt, lien.id_mot FROM spip_mots_breves AS lien, spip_breves AS breve	WHERE breve.id_breve=lien.id_breve AND breve.statut IN ($aff_articles) GROUP BY lien.id_mot");

 while ($row = spip_fetch_array($result_breves)){
	$breves[$row['id_mot']] = $row['cnt'];
}

 $syndic = array(); 
 $result_syndic = spip_query("SELECT COUNT(*) AS cnt, lien.id_mot FROM spip_mots_syndic AS lien, spip_syndic AS syndic WHERE syndic.id_syndic=lien.id_syndic AND syndic.statut IN ($aff_articles) GROUP BY lien.id_mot");
 while ($row = spip_fetch_array($result_syndic)){
	$sites[$row['id_mot']] = $row['cnt'];

 }

 return array('articles' => $articles, 
	      'breves' => $breves, 
	      'rubriques' => $rubriques, 
	      'syndic' => $syndic);
}

// http://doc.spip.org/@afficher_groupe_mots
function afficher_groupe_mots($id_groupe) {
	global $connect_id_auteur, $connect_statut;
	global $spip_lang_right, $couleur_claire, $spip_lang;

	$jjscript = array("fonction" => "afficher_groupe_mots",
			  "id_groupe" => $id_groupe);
	$jjscript = (serialize($jjscript));
	$hash = "0x".substr(md5($connect_id_auteur.$jjscript), 0, 16);
	$tmp_var = substr($hash, 2, 6);
			
	$javascript = "charger_id_url('" . generer_url_ecrire('memoriser',"&var_ajax=1&id_ajax_fonc=::id_ajax_fonc::::deb::", true) . "','$tmp_var')";

	$select = 'id_mot, titre, ' . creer_objet_multi ("titre", $spip_lang);
	$from = 'spip_mots';
	$where = "id_groupe=$id_groupe" ;

	$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM $from WHERE $where"));

	if (! ($cpt = $cpt['n'])) return '' ;

	$occurrences = calculer_liens_mots();

	$res_proch = spip_query("SELECT id_ajax_fonc FROM spip_ajax_fonc WHERE hash=$hash AND id_auteur=$connect_id_auteur ORDER BY id_ajax_fonc DESC LIMIT 1");
	if ($row = spip_fetch_array($res_proch)) {
			$id_ajax_fonc = $row["id_ajax_fonc"];
	} else  {
			include_spip('base/abstract_sql');
			$id_ajax_fonc = spip_abstract_insert("spip_ajax_fonc", "(id_auteur, variables, hash, date)", "($connect_id_auteur, " . spip_abstract_quote($jjscript) . ", $hash, NOW())");
	}

	$nb_aff = 1.5 * _TRANCHES;
	$deb_aff = intval(_request('t_' .$tmp_var));
	$limit = ($deb_aff >= 0 ? "$deb_aff, $nb_aff" : "99999");

	if ($cpt > $nb_aff) {
		$nb_aff = (_TRANCHES); 
		$tranches = afficher_tranches_requete($cpt, 3, $tmp_var, $javascript, $nb_aff);
	} else $tranches = '';


	$table = array();
	$result = spip_query("SELECT $select FROM $from WHERE $where ORDER BY multi LIMIT  $limit");
	while ($row = spip_fetch_array($result)) {
		$table[] = afficher_groupe_mots_boucle($row, $occurrences);
	}

	if ($connect_statut=="0minirezo") {
			$largeurs = array('', 100, 130);
			$styles = array('arial11', 'arial1', 'arial1');
		}
	else {
			$largeurs = array('', 100);
			$styles = array('arial11', 'arial1');
	}

	$res = http_img_pack("searching.gif", "*", "style='visibility: hidden; position: absolute; $spip_lang_right: 0px; top: -20px;' id='img_$tmp_var'") 
	  . "<div class='liste'>"
	  . "<table border='0' cellspacing='0' cellpadding='3' width='100%'>"
	  . str_replace("::id_ajax_fonc::", "$id_ajax_fonc", $tranches)
	  . afficher_liste($largeurs, $table, $styles)
	  . "</table>"
	  . "</div>";
		
	if ($deb_aff) return $res;

	return "<div id='$tmp_var' style='position: relative;'>$res</div>";
}

// http://doc.spip.org/@afficher_groupe_mots_boucle
function afficher_groupe_mots_boucle($row, $occurrences)
{
	global $connect_statut, $connect_toutes_rubriques;

	$id_mot = $row['id_mot'];
	$titre_mot = typo($row['titre']);
			
	if ($connect_statut == "0minirezo" OR $occurrences['articles'][$id_mot] > 0)
		$titre_mot = "<a href='" .
		  generer_url_ecrire('mots_edit', "id_mot=$id_mot&redirect=" . generer_url_retour('mots_tous')) .
		  "' class='liste-mot'>$titre_mot</a>";

	$vals = array($titre_mot);

	$texte_lie = array();

	$na = isset($occurrences['articles'][$id_mot]) ? $occurrences['articles'][$id_mot] : 0;
	if ($na == 1)
		$texte_lie[] = _T('info_1_article');
	else if ($na > 1)
		$texte_lie[] = $na." "._T('info_articles_02');

	$nb = isset($occurrences['breves'][$id_mot]) ? $occurrences['breves'][$id_mot] : 0;
	if ($nb == 1)
		$texte_lie[] = _T('info_1_breve');
	else if ($nb > 1)
		$texte_lie[] = $nb." "._T('info_breves_03');

	$ns = isset($occurrences['sites'][$id_mot]) ? $occurrences['sites'][$id_mot] : 0;
	if ($ns == 1)
		$texte_lie[] = _T('info_1_site');
	else if ($ns > 1)
		$texte_lie[] = $ns." "._T('info_sites');

	$nr = isset($occurrences['rubriques'][$id_mot]) ? $occurrences['rubriques'][$id_mot] : 0;
	if ($nr == 1)
		$texte_lie[] = _T('info_une_rubrique_02');
	else if ($nr > 1)
		$texte_lie[] = $nr." "._T('info_rubriques_02');

	$texte_lie = join($texte_lie,", ");
				
	$vals[] = $texte_lie;

	if (acces_mots()) {
		$href = ($nr OR $na OR $ns OR $nb)
		? generer_url_ecrire("mots_tous","conf_mot=$id_mot&na=$na&nb=$nb&nr=$nr&ns=$ns")
		: redirige_action_auteur('editer_mot', ",$id_mot,,,",'mots_tous');

		$vals[] = "<div style='text-align:right;'><a href='$href'>"
		. _T('info_supprimer_mot')
		. "&nbsp;<img src='"
		. _DIR_IMG_PACK
		. "croix-rouge.gif' alt='X' width='7' height='7' align='bottom' /></a></div>";
	} 
	
	return $vals;			
}
?>
