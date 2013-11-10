<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/filtres');
// http://doc.spip.org/@calendrier_categories
function calendrier_categories($table, $num, $objet)
{
  if (function_exists('generer_calendrier_class'))
    return generer_calendrier_class($table, $num, $objet);
  else {
    // cf agenda.css
    $num= Sql::getfetsel((($objet != 'id_breve') ? 'id_secteur' : 'id_rubrique') . " AS id", $table, "$objet=$num");

    return 'calendrier-couleur' . (($num%14)+1);
  }
}

// ici on prend en fait le jour, la veille et le lendemain

// http://doc.spip.org/@quete_calendrier_jour
function quete_calendrier_jour($annee,$mois,$jour) {
	$avant = "'" . date("Y-m-d", mktime(0,0,0,$mois,$jour-1,$annee)) . "'";
	$apres = "'" . date("Y-m-d", mktime(1,1,1,$mois,$jour+1,$annee)) .
	" 23:59:59'";
	return array($avant, $apres);
}

// retourne un tableau de 2 tableaux indexes par des dates
// - le premier indique les evenements du jour, sans indication de duree
// - le deuxime indique les evenements commencant ce jour, avec indication de duree

// http://doc.spip.org/@quete_calendrier_interval
function quete_calendrier_interval($limites) {
	include_spip('inc/urls');
	list($avant, $apres) = $limites;
	$evt = array();
	quete_calendrier_interval_articles($avant, $apres, $evt);
	quete_calendrier_interval_breves($avant, $apres, $evt);
	quete_calendrier_interval_rubriques($avant, $apres, $evt);
	return array($evt, quete_calendrier_interval_rv($avant, $apres));
}

// http://doc.spip.org/@quete_calendrier_interval_forums
function  quete_calendrier_interval_forums($limites, &$evenements) {
	list($avant, $apres) = $limites;
	$result=Sql::select("DISTINCT titre, date_heure, id_forum",	"spip_forum", "date_heure >= $avant AND date_heure < $apres", '',  "date_heure");
	while($row=Sql::fetch($result)){
		$amj = date_anneemoisjour($row['date_heure']);
		$id = $row['id_forum'];
		if (autoriser('voir','forum',$id))
			$evenements[$amj][]=
			array(
				'URL' => generer_url_entite($id, 'forum'),
				'CATEGORIES' => 'calendrier-couleur7',
				'SUMMARY' => $row['titre'],
				'DTSTART' => date_ical($row['date_heure']));
	}
}

# 3 fonctions retournant les evenements d'une periode
# le tableau retourne est indexe par les balises du format ics
# afin qu'il soit facile de produire de tels documents.
# L'URL de chacun de ces evenements est celle de l'espace prive
# pour faciliter la navigation, ce qu'on obtient utilisant
# le 4e argument des fonctions generer_url_ecrire_$table

// http://doc.spip.org/@quete_calendrier_interval_articles
function quete_calendrier_interval_articles($avant, $apres, &$evenements) {

  $result=Sql::select('id_article, titre, date, descriptif, chapo,  lang', 'spip_articles', "statut='publie' AND date >= $avant AND date < $apres", '', "date");

	// tables traduites
	$objets = explode(',', $GLOBALS['meta']['multi_objets']);

	if (in_array('spip_articles', $objets)) {
	  include_spip('inc/lang_liste');
	  $langues = $GLOBALS['codes_langues'];
	} else $langues = array();
	while($row=Sql::fetch($result)){
		$amj = date_anneemoisjour($row['date']);
		$id = $row['id_article'];
		if (autoriser('voir','article',$id))
			$evenements[$amj][]=
			    array(
				'CATEGORIES' => calendrier_categories('spip_articles', $id, 'id_article'),
				'DESCRIPTION' => $row['descriptif'] ? $row['descriptif'] : $langues[$row['lang']],
				'SUMMARY' => $row['titre'],
				'URL' => generer_url_ecrire_objet('article',$id, '','','prop'));
	}
}

// http://doc.spip.org/@quete_calendrier_interval_rubriques
function quete_calendrier_interval_rubriques($avant, $apres, &$evenements) {

  $result=Sql::select('DISTINCT R.id_rubrique, titre, descriptif, date', 'spip_rubriques AS R, spip_documents_liens AS L', "statut='publie' AND	date >= $avant AND	date < $apres AND	R.id_rubrique = L.id_objet AND L.objet='rubrique'",'', "date");
	while($row=Sql::fetch($result)){
		$amj = date_anneemoisjour($row['date']);
		$id = $row['id_rubrique'];
		if (autoriser('voir','rubrique',$id))
			$evenements[$amj][]=
			    array(
				'CATEGORIES' => calendrier_categories('spip_rubriques', $id, 'id_rubrique'),
				'DESCRIPTION' => $row['descriptif'],
				'SUMMARY' => $row['titre'],
				'URL' => generer_url_ecrire_objet('rubrique',$id, '','', 'prop'));
	}
}

// http://doc.spip.org/@quete_calendrier_interval_breves
function quete_calendrier_interval_breves($avant, $apres, &$evenements) {
  $result=Sql::select("id_breve, titre, date_heure, id_rubrique", 'spip_breves',	"statut='publie' AND date_heure >= $avant AND date_heure < $apres", '', "date_heure");
	while($row=Sql::fetch($result)){
		$amj = date_anneemoisjour($row['date_heure']);
		$id = $row['id_breve'];
		$ir = $row['id_rubrique'];
		if (autoriser('voir','breve',$id))
			$evenements[$amj][]=
			array(
			      'URL' => generer_url_ecrire_objet('breve',$id, '','', 'prop'),
			      'CATEGORIES' => calendrier_categories('spip_breves', $ir, 'id_breve'),
			      'SUMMARY' => $row['titre']);
	}
}

// http://doc.spip.org/@quete_calendrier_interval_rv
function quete_calendrier_interval_rv($avant, $apres) {
	include_spip('inc/session');
	$connect_id_auteur = session_get('id_auteur');

	$evenements= array();
	if (!$connect_id_auteur) return $evenements;
	$result=Sql::select("M.id_message, M.titre, M.texte, M.date_heure, M.date_fin, M.type",
		"spip_messages AS M LEFT JOIN spip_auteurs_liens AS L ON (L.id_objet=M.id_message)",
		"((L.objet='message' AND (L.id_auteur=$connect_id_auteur OR M.type='affich')) OR (L.objet IS NULL AND M.id_auteur=$connect_id_auteur AND ".Sql::in('M.type',array('pb','affich'))."))"
		." AND M.rv='oui' AND ((M.date_fin >= $avant OR M.date_heure >= $avant) AND M.date_heure <= $apres) AND M.statut='publie'",
		"M.id_message", "M.date_heure");
	while($row=Sql::fetch($result)){
		$date_heure=$row["date_heure"];
		$date_fin=$row["date_fin"];
		$type=$row["type"];
		$id_message=$row['id_message'];

		if ($type=="pb")
		  $cat = 'calendrier-couleur2';
		else {
		  if ($type=="affich")
		  $cat = 'calendrier-couleur4';
		  else {
		    if ($type!="normal")
		      $cat = 'calendrier-couleur12';
		    else {
		      $cat = 'calendrier-couleur9';
		      $auteurs = array_map('array_shift', Sql::allfetsel("nom", "spip_auteurs AS A LEFT JOIN spip_auteurs_liens AS L ON L.id_auteur=A.id_auteur", "(L.objet='message' AND L.id_objet=$id_message AND (A.id_auteur!=$connect_id_auteur))"));
		    }
		  }
		}

		$jour_avant = substr($avant, 9,2);
		$mois_avant = substr($avant, 6,2);
		$annee_avant = substr($avant, 1,4);
		$jour_apres = substr($apres, 9,2);
		$mois_apres = substr($apres, 6,2);
		$annee_apres = substr($apres, 1,4);
		$ical_apres = date_anneemoisjour("$annee_apres-$mois_apres-".sprintf("%02d",$jour_apres));

		// Calcul pour les semaines a cheval sur deux mois
 		$j = 0;
		$amj = date_anneemoisjour("$annee_avant-$mois_avant-".sprintf("%02d", $j+($jour_avant)));

		while ($amj <= $ical_apres) {
		if (!($amj == date_anneemoisjour($date_fin) AND preg_match(",00:00:00,", $date_fin)))  // Ne pas prendre la fin a minuit sur jour precedent
			$evenements[$amj][$id_message]=
			  array(
				'URL' => generer_url_ecrire("message","id_message=$id_message"),
				'DTSTART' => date_ical($date_heure),
				'DTEND' => date_ical($date_fin),
				'DESCRIPTION' => $row['texte'],
				'SUMMARY' => $row['titre'],
				'CATEGORIES' => $cat,
				'ATTENDEE' => (count($auteurs) == 0) ? '' : join($auteurs,", "));

			$j ++;
			$ladate = date("Y-m-d",mktime (1,1,1,$mois_avant, ($j + $jour_avant), $annee_avant));

			$amj = date_anneemoisjour($ladate);

		}

	}
  return $evenements;
}

// http://doc.spip.org/@quete_calendrier_agenda
function quete_calendrier_agenda ($annee, $mois) {
	include_spip('inc/session');
	$connect_id_auteur = session_get('id_auteur');

	$rv = array();
	if (!$connect_id_auteur) return $rv;
	$date = date("Y-m-d", mktime(0,0,0,$mois, 1, $annee));
	$mois = mois($date);
	$annee = annee($date);

	// rendez-vous personnels dans le mois
	$result_messages = Sql::select("M.titre AS summary, M.texte AS description, M.id_message AS uid, M.date_heure", "spip_messages AS M, spip_auteurs_liens AS L", "((L.id_auteur=$connect_id_auteur AND L.id_objet=M.id_message AND L.objet='message') OR M.type='affich') AND M.rv='oui' AND M.date_heure >='$annee-$mois-1' AND date_heure < DATE_ADD('$annee-$mois-1', INTERVAL 1 MONTH) AND M.statut='publie'");
	while($row=Sql::fetch($result_messages)) {
		$rv[journum($row['date_heure'])] = $row;
	}
	return $rv;
}

?>
