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

function encours_suivi()
{
	$lister_objets = charger_fonction('lister_objets','inc');
	$res = '';

	// Les articles a valider
	//

	$res .=  $lister_objets('articles',array('titre'=>_T('info_articles_proposes'),'statut'=>'prop', 'par'=>'date'));


	return pipeline('accueil_encours',$res);

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
	$lister_objets = charger_fonction('lister_objets','inc');

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

	$date_now = date('Y-m-d H:i:s');
	if ($GLOBALS['meta']["post_dates"] == "non"
	AND $connect_statut == '0minirezo')
		echo $lister_objets('articles',array('titre'=>_T('info_article_a_paraitre'),'statut'=>'publie', 'par'=>'date', 'where'=>'date>'.sql_quote($date_now), 'date_sens'=>1));


	$s = encours_suivi();
	if ($s)
		echo "<h2>"._T('texte_en_cours_validation')."</h2>" . $s;

	// Les articles recents
	//
	echo "<h2>"._T('articles_recents')."</h2>";
	$contexte = array('titre'=>_T('articles_recents'),'statut'=>'publie', 'par'=>'date','nb'=>5);
	if ($GLOBALS['meta']["post_dates"] == "non")
		$contexte['where']='date<='.sql_quote($date_now);
	echo $lister_objets('articles',$contexte);

	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'suivi_edito'),'data'=>''));

	echo fin_gauche(), fin_page();
}
?>
