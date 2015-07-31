<?php
/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');

function encours_suivi()
{
	global $connect_statut;


	$res = '';

	// Les articles a valider
	//

	$res .=  afficher_objets('article',_T('info_articles_proposes'), array("WHERE" => "statut='prop'", 'ORDER BY' => "date DESC"));

	//
	// Les breves a valider
	//
	$res .= afficher_objets('breve',afficher_plus(generer_url_ecrire('breves'))._T('info_breves_valider'), array("FROM" => 'spip_breves', 'WHERE' => "statut='prepa' OR statut='prop'", 'ORDER BY' => "date_heure DESC"), true);

	//
	// Les sites references a valider
	//
	if ($GLOBALS['meta']['activer_sites'] != 'non') {
		$res .= afficher_objets('site',afficher_plus(generer_url_ecrire('sites_tous')).'<b>' . _T('info_site_valider') . '</b>', array("FROM" => 'spip_syndic', 'WHERE' => "statut='prop'", 'ORDER BY'=> "nom_site"));
	}

	if ($connect_statut == '0minirezo') {
	//
	// Les sites a probleme
	//
	  if ($GLOBALS['meta']['activer_sites'] != 'non') {
		$res .= afficher_objets('site',afficher_plus(generer_url_ecrire('sites_tous')). '<b>' . _T('avis_sites_syndiques_probleme') . '</b>', array('FROM' => 'spip_syndic', 'WHERE' => "(syndication='off' OR syndication='sus') AND statut='publie'", 'ORDER BY' => 'nom_site'));
	}

	// Les articles syndiques en attente de validation
		$cpt = sql_countsel("spip_syndic_articles", "statut='dispo'");
		if ($cpt)
			$res .= "\n<br /><small><a href='"
			. generer_url_ecrire("sites_tous","")
			. "' style='color: black;'>"
			. $cpt
			. " "
			. _T('info_liens_syndiques_1')
			. " "
			. _T('info_liens_syndiques_2')
			. "</a></small>";

	}
	
	$res = pipeline('accueil_encours',$res);

	if (!$res) return '';

	return 
	"<h2>"._T('texte_en_cours_validation')."</h2>"
	. $res;
}

// http://doc.spip.org/@etat_base_accueil
function etat_base_suivi()
{
	global $spip_display, $spip_lang_left, $connect_id_rubrique;

	$where = count($connect_id_rubrique) ? sql_in('id_rubrique', $connect_id_rubrique)	: '';

	$res = '';

	$q = sql_select("COUNT(*) AS cnt, statut", 'spip_articles', '', 'statut', '','', "COUNT(*)<>0");
  
	$cpt = array();
	$cpt2 = array();
	$defaut = $where ? '0/' : '';
	while($row = sql_fetch($q)) {
	  $cpt[$row['statut']] = $row['cnt'];
	  $cpt2[$row['statut']] = $defaut;
	}
	if ($cpt) {
		if ($where) {
			$q = sql_select("COUNT(*) AS cnt, statut", 'spip_articles', $where, "statut");
			while($row = sql_fetch($q)) {
				$r = $row['statut'];
				$cpt2[$r] = intval($row['cnt']) . '/';
			}
		}
		$res .= "<div class='accueil_informations articles verdana1'>";
		$res .= afficher_plus(generer_url_ecrire("articles_page",""))."<b>"._T('info_articles')."</b>";
		$res .= "<ul style='margin:0px; padding-$spip_lang_left: 20px; margin-bottom: 5px;'>";
		if (isset($cpt['prepa'])) $res .= "<li>"._T("texte_statut_en_cours_redaction").": ".$cpt2['prepa'] . $cpt['prepa'] .'</li>';
		if (isset($cpt['prop'])) $res .= "<li>"._T("texte_statut_attente_validation").": ".$cpt2['prop'] . $cpt['prop'] . '</li>';
		if (isset($cpt['publie'])) $res .= "<li><b>"._T("texte_statut_publies").": ".$cpt2['publie'].$cpt['publie'] ."</b>" . '</li>';
		$res .= "</ul>";
		$res .= "</div>";
	}

	$q = sql_select("COUNT(*) AS cnt, statut", 'spip_breves', '', 'statut', '','', "COUNT(*)<>0");

	$cpt = array();
	$cpt2 = array();
	$defaut = $where ? '0/' : '';
	while($row = sql_fetch($q)) {
	  $cpt[$row['statut']] = $row['cnt'];
	  $cpt2[$row['statut']] = $defaut;
	}
 
	if ($cpt) {
		if ($where) {
			$q = sql_select("COUNT(*) AS cnt, statut", 'spip_breves', $where, "statut");
			while($row = sql_fetch($q)) {
				$r = $row['statut'];
				$cpt2[$r] = intval($row['cnt']) . '/';
			}
		}
		$res .= "<div class='accueil_informations breves verdana1'>";
		$res .= afficher_plus(generer_url_ecrire("breves",""))."<b>"._T('info_breves_02')."</b>";
		$res .= "<ul style='margin:0px; padding-$spip_lang_left: 20px; margin-bottom: 5px;'>";
		if (isset($cpt['prop'])) $res .= "<li>"._T("texte_statut_attente_validation").": ".$cpt2['prop'].$cpt['prop'] . '</li>';
		if (isset($cpt['publie'])) $res .= "<li><b>"._T("texte_statut_publies").": ".$cpt2['publie'] .$cpt['publie'] . "</b>" .'</li>';
		$res .= "</ul>";
		$res .= "</div>";
	}

	$res .= "<div class='accueil_informations auteurs verdana1'>";
	$res .= suivi_liste_participants();
	$res .= "</div>";

	return pipeline('accueil_informations',$res) ;
}


// http://doc.spip.org/@accueil_liste_participants
function suivi_liste_participants()
{
	global $spip_lang_left;

	$q = sql_select("COUNT(*) AS cnt, statut", 'spip_auteurs', sql_in("statut", $GLOBALS['liste_des_statuts']), 'statut', '','', "COUNT(*)<>0");

	$cpt = array();
	while($row=sql_fetch($q)) $cpt[$row['statut']] = $row['cnt']; 

	if (!$cpt) return '';

	       
	$res = afficher_plus(generer_url_ecrire("auteurs"))."<b>"._T('icone_auteurs')."</b>"
	. "<ul style='margin:0px; padding-$spip_lang_left: 20px; margin-bottom: 5px;'>";
		
	foreach($GLOBALS['liste_des_statuts'] as $k => $v) {
	  if (isset($cpt[$v])) $res .= "<li>" . _T($k) . ": " .$cpt[$v] . '</li>';
	}

	$res .= "</ul>";

	return $res; 
}

// http://doc.spip.org/@exec_accueil_dist
function exec_suivi_edito_dist()
{
  global $id_rubrique, $connect_statut, $connect_id_auteur, $spip_display, $connect_id_rubrique;

	$id_rubrique =  intval($id_rubrique);
 	pipeline('exec_init',array('args'=>array('exec'=>'suivi_edito','id_rubrique'=>$id_rubrique),'data'=>''));

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('info_suivi_activite'), "accueil", "accueil");

	echo gros_titre(_T('info_suivi_activite'),"",false);
	echo debut_gauche("",true);

	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'suivi_edito'),'data'=>''));
	echo debut_boite_info(true),
	  etat_base_suivi(),
	  fin_boite_info(true);

	echo creer_colonne_droite("", true);
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'suivi_edito'),'data'=>''));

	echo debut_droite("", true);

	if ($GLOBALS['meta']["post_dates"] == "non"
	AND $connect_statut == '0minirezo')
		echo afficher_objets('article',_T('info_article_a_paraitre'), array("WHERE" => "statut='publie' AND date>".sql_quote(date('Y-m-d H:i:s')), 'ORDER BY' => "date"));


	echo encours_suivi();

 	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'suivi_edito'),'data'=>''));

	// Les articles recents
	//
	echo "<h2>"._T('articles_recents')."</h2>";
	echo afficher_objets('article',
	#afficher_plus(generer_url_ecrire('articles_page')) .
	_T('articles_recents'), array("WHERE" => "statut='publie'" .($GLOBALS['meta']["post_dates"] == "non"
		? " AND date<".sql_quote(date('Y-m-d H:i:s')) : ''),
		'ORDER BY' => "date DESC", 'LIMIT' => '0,4'));

	// Dernieres modifications d'articles
	if (($GLOBALS['meta']['articles_versions'] == 'oui')) {
		include_spip('inc/suivi_versions');
		echo afficher_suivi_versions (0, 0, false, "", true);
	}

	echo fin_gauche(), fin_page();
}
?>