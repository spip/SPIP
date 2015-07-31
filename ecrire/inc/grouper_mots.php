<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/presentation');
include_spip('inc/actions');
include_spip('base/abstract_sql');

// http://doc.spip.org/@inc_grouper_mots_dist
function inc_grouper_mots_dist($id_groupe, $total) {
	global $connect_statut, $spip_lang_right, $spip_lang;

	$presenter_liste = charger_fonction('presenter_liste', 'inc');

	// ceci sert a la fois:
	// - a construire le nom du parametre d'URL indiquant la tranche
	// - a donner un ID a la balise ou greffer le retour d'Ajax
	// tant pour la prochaine tranche que pour le retrait de mot
	$tmp_var = "editer_mots-$id_groupe";
	$url = generer_url_ecrire('grouper_mots',"id_groupe=$id_groupe");

	$select = 'id_mot, id_groupe, titre, descriptif, '
	. sql_multi ("titre", $spip_lang);

	$requete = array('SELECT' => $select, 'FROM' => 'spip_mots', 'WHERE' => "id_groupe=$id_groupe", 'ORDER BY' => 'multi');

	$tableau = array();
	$occurrences = calculer_liens_mots($id_groupe);
	if ($connect_statut=="0minirezo") {
		$styles = array(array('arial11'), array('arial1', 100), array('arial1', 130));
	} else {
		$styles = array(array('arial11'), array('arial1', 100));
	}
	return $presenter_liste($requete, 'presenter_groupe_mots_boucle', $tableau, array($occurrences, $total, $deb_aff), false, $styles, $tmp_var, '', '', $url);
}

// http://doc.spip.org/@afficher_groupe_mots_boucle
function presenter_groupe_mots_boucle($row, $own)
{
	global $connect_statut;
	$puce_statut = charger_fonction('puce_statut', 'inc');

	list($occurrences, $total, $deb_aff) = $own;
	$id_mot = $row['id_mot'];
	$id_groupe = $row['id_groupe'];
	$titre = typo($row['titre']);
	$descriptif = entites_html($row['descriptif']);
	$droit = autoriser('modifier', 'mot', $id_mot, null, array('id_groupe' => $id_groupe));

	if ($droit OR $occurrences['articles'][$id_mot] > 0) {
		$h = generer_url_ecrire('mots_edit', "id_mot=$id_mot&redirect=" . generer_url_retour('mots_tous') . "#editer_mots-$id_groupe");
		if ($descriptif)  $descriptif = " title=\"$descriptif\"";
		$cle = $puce_statut($id_mot, 'publie', $id_groupe, 'mot');
		$titre = "<a href='$h' $descriptif>$cle $titre</a>";
	}
	$vals = array($titre);

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

	$ns = isset($occurrences['syndic'][$id_mot]) ? $occurrences['syndic'][$id_mot] : 0;
	if ($ns == 1)
		$texte_lie[] = _T('info_1_site');
	else if ($ns > 1)
		$texte_lie[] = $ns." "._T('info_sites');

	$nr = isset($occurrences['rubriques'][$id_mot]) ? $occurrences['rubriques'][$id_mot] : 0;
	if ($nr == 1)
		$texte_lie[] = _T('info_une_rubrique_02');
	else if ($nr > 1)
		$texte_lie[] = $nr." "._T('info_rubriques_02');

	$texte_lie = pipeline('afficher_nombre_objets_associes_a',array('args'=>array('objet'=>'mot','id_objet'=>$id_mot),'data'=>$texte_lie));
	$texte_lie = join($texte_lie,", ");

	$vals[] = $texte_lie;

	if ($droit) {
		$clic =  '<small>'
		._T('info_supprimer_mot')
		. "&nbsp;<img style='vertical-align: bottom;' src='"
		. chemin_image('croix-rouge.gif')
		. "' alt='X' width='7' height='7' />"
		. '</small>';

		if ($nr OR $na OR $ns OR $nb)
			$href = "<a href='"
			. generer_url_ecrire("mots_tous","conf_mot=$id_mot&na=$na&nb=$nb&nr=$nr&ns=$ns&son_groupe=$id_groupe") . "#editer_mots-$id_groupe"
			. "'>$clic</a>";
		else {
			$href = generer_supprimer_mot($id_mot, $id_groupe, $clic, $total, $deb_aff);
		} 

		$vals[] = "<div style='text-align:right;'>$href</div>";
	} 
	
	return $vals;
}

// http://doc.spip.org/@generer_supprimer_mot
function generer_supprimer_mot($id_mot, $id_groupe, $clic, $total, $deb_aff='')
{
	$cont = ($total > 1)
	? ''
	: "function(r) {jQuery('#editer_mots-$id_groupe-supprimer').css('visibility','visible');}";

	return ajax_action_auteur('editer_mots', "$id_groupe,$id_mot,,,",'grouper_mots', "id_groupe=$id_groupe&$deb_aff", array($clic,''), '', $cont);
}

//
// Calculer les nombres d'elements (articles, etc.) lies a chaque mot
//

// http://doc.spip.org/@calculer_liens_mots
function calculer_liens_mots($id_groupe)
{
	$aff_articles = sql_in('O.statut',  ($GLOBALS['connect_statut'] =="0minirezo")  ? array('prepa','prop','publie') : array('prop','publie'));

	$res = sql_allfetsel("COUNT(*) as cnt, L.id_mot", "spip_mots_articles AS L LEFT JOIN spip_mots AS M ON L.id_mot=M.id_mot LEFT JOIN spip_articles AS O ON L.id_article=O.id_article", "M.id_groupe=$id_groupe AND $aff_articles", "L.id_mot");
	$articles = array();
	foreach($res as $row) $articles[$row['id_mot']] = $row['cnt'];

	$rubriques = array();
	$res = sql_allfetsel("COUNT(*) as cnt, L.id_mot", "spip_mots_rubriques AS L LEFT JOIN spip_mots AS M ON L.id_mot=M.id_mot", "M.id_groupe=$id_groupe", "L.id_mot");
	foreach($res as $row) $rubriques[$row['id_mot']] = $row['cnt'];
  
	$breves = array();
	$res = sql_allfetsel("COUNT(*) as cnt, L.id_mot", "spip_mots_breves AS L LEFT JOIN spip_mots AS M ON L.id_mot=M.id_mot LEFT JOIN spip_breves AS O ON L.id_breve=O.id_breve", "M.id_groupe=$id_groupe AND $aff_articles", "L.id_mot");
	foreach($res as $row) $breves[$row['id_mot']] = $row['cnt'];

	$syndic = array(); 
	$res = sql_allfetsel("COUNT(*) as cnt, L.id_mot", "spip_mots_syndic AS L LEFT JOIN spip_mots AS M ON L.id_mot=M.id_mot LEFT JOIN spip_syndic AS O ON L.id_syndic=O.id_syndic", "M.id_groupe=$id_groupe AND $aff_articles", "L.id_mot");
	foreach($res as $row) $syndic[$row['id_mot']] = $row['cnt'];

	return array('articles' => $articles, 
		'breves' => $breves, 
		'rubriques' => $rubriques, 
		'syndic' => $syndic);
}
?>
