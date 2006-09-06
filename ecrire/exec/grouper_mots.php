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

function exec_grouper_mots_dist()
{
	return afficher_groupe_mots(intval(_request('id_groupe')));
}

// http://doc.spip.org/@afficher_groupe_mots
function afficher_groupe_mots($id_groupe) {
	global $connect_statut;
	global $spip_lang_right, $couleur_claire, $spip_lang;

	// ceci sert a la fois:
	// - a construire le nom du parametre d'URL indiquant la tranche
	// - a donner un ID a la balise ou greffer le retour d'Ajax
	// tant pour la prochaine tranche que pour le retrait de mot
	$tmp_var = "editer_mot-$id_groupe";
	$deb_aff = _request('t_' .$tmp_var);

	$select = 'id_mot, id_groupe, titre, '
	. creer_objet_multi ("titre", $spip_lang);
	$from = 'spip_mots';
	$where = "id_groupe=$id_groupe" ;

	$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM $from WHERE $where"));

	if (! ($cpt = $cpt['n'])) return '' ;

	$occurrences = calculer_liens_mots();

	$nb_aff = 1.5 * _TRANCHES;

	if ($cpt > $nb_aff) {
		$nb_aff = _TRANCHES; 
		$tranches = afficher_tranches_requete($cpt, 3, $tmp_var, "charger_id_url('" . generer_url_ecrire('grouper_mots',"id_groupe=$id_groupe::deb::", true) . "','$tmp_var')", $nb_aff);
	} else $tranches = '';


	$table = array();
	$result = spip_query($q="SELECT $select FROM $from WHERE $where ORDER BY multi LIMIT " . ($deb_aff !== NULL ? intval($deb_aff) : 0) .", $nb_aff");
	spip_log(spip_num_rows($result) . $deb_aff);
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
	  . $tranches
	  . afficher_liste($largeurs, $table, $styles)
	  . "</table>"
	  . "</div>";
		
	return $res;
}

// http://doc.spip.org/@afficher_groupe_mots_boucle
function afficher_groupe_mots_boucle($row, $occurrences)
{
	global $connect_statut;

	$id_mot = $row['id_mot'];
	$id_groupe = $row['id_groupe'];
	$titre_mot = typo($row['titre']);
			
	if ($connect_statut == "0minirezo" OR $occurrences['articles'][$id_mot] > 0)
		$titre_mot = "<a href='" .
		  generer_url_ecrire('mots_edit', "id_mot=$id_mot&redirect=" . generer_url_retour('mots_tous') . "#editer_mot-$id_groupe") .
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
		$clic =  _T('info_supprimer_mot')
		. "&nbsp;<img src='"
		. _DIR_IMG_PACK
		. "croix-rouge.gif' alt='X' width='7' height='7' align='bottom' />";

		if ($nr OR $na OR $ns OR $nb)
			$href = "<a href='"
			. generer_url_ecrire("mots_tous","conf_mot=$id_mot&na=$na&nb=$nb&nr=$nr&ns=$ns&son_groupe=$id_groupe") . "#editer_mot-$id_groupe"
			. "'>$clic</a>";
		else $href = ajax_action_auteur('editer_mot', "$id_groupe,$id_mot,,,",'grouper_mots', "&id_groupe=$id_groupe", array($clic,''));
		$vals[] = "<div style='text-align:right;'>$href</div>";
	} 
	
	return $vals;			
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
?>
