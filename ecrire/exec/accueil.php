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

// http://doc.spip.org/@encours_accueil
function encours_accueil()
{
	include_spip("exec/suivi_edito");
	$res = encours_suivi();
	if (!$res) return '';

	return 
	"<div style='position:relative;display:inline;'>" 
	. debut_cadre_couleur_foncee("",true, "", _T('texte_en_cours_validation'))
	. $res
	. bouton_spip_rss('a_suivre')
	. fin_cadre_couleur_foncee(true)
	. "</div>";
}


// Cartouche du site, avec le nombre d'articles et autres objets ajoutes par les plugins

// http://doc.spip.org/@etat_base_accueil
function etat_base_accueil()
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
		$res .= afficher_plus_info(generer_url_ecrire("articles_page",""))."<b>"._T('info_articles')."</b>";
		$res .= "<ul style='margin:0px; padding-$spip_lang_left: 20px; margin-bottom: 5px;'>";
		if (isset($cpt['prepa'])) $res .= "<li>"._T("texte_statut_en_cours_redaction").": ".$cpt2['prepa'] . $cpt['prepa'] .'</li>';
		if (isset($cpt['prop'])) $res .= "<li>"._T("texte_statut_attente_validation").": ".$cpt2['prop'] . $cpt['prop'] . '</li>';
		if (isset($cpt['publie'])) $res .= "<li><b>"._T("texte_statut_publies").": ".$cpt2['publie'].$cpt['publie'] ."</b>" . '</li>';
		$res .= "</ul>";
		$res .= "</div>";
	}


	$res .= "<div class='accueil_informations auteurs verdana1'>";
	$res .= accueil_liste_participants();
	$res .= "</div>";

	return pipeline('accueil_informations',$res) ;
}


// http://doc.spip.org/@accueil_liste_participants
function accueil_liste_participants()
{
	global $spip_lang_left;

	$q = sql_select("COUNT(*) AS cnt, statut", 'spip_auteurs', sql_in("statut", $GLOBALS['liste_des_statuts']), 'statut', '','', "COUNT(*)<>0");

	$cpt = array();
	while($row=sql_fetch($q)) $cpt[$row['statut']] = $row['cnt']; 

	if (!$cpt) return '';

	       
	$res = afficher_plus_info(generer_url_ecrire("auteurs"))."<b>"._T('icone_auteurs')."</b>"
	. "<ul style='margin:0px; padding-$spip_lang_left: 20px; margin-bottom: 5px;'>";
		
	foreach($GLOBALS['liste_des_statuts'] as $k => $v) {
	  if (isset($cpt[$v])) $res .= "<li>" . _T($k) . ": " .$cpt[$v] . '</li>';
	}

	$res .= "</ul>";

	return $res; 
}

function exec_accueil_navigation(){
	$nom = typo($GLOBALS['meta']["nom_site"]);
	if (!$nom) $nom=  _T('info_mon_site_spip');
	return  debut_cadre_relief("racine-24.png", true, "", $nom)
		. etat_base_accueil()
		. fin_cadre_relief(true);
}

// http://doc.spip.org/@exec_accueil_dist
function exec_accueil_dist()
{
  global $id_rubrique, $connect_statut, $connect_id_auteur, $spip_display, $connect_id_rubrique;

	$id_rubrique =  intval($id_rubrique);
 	pipeline('exec_init',array('args'=>array('exec'=>'accueil','id_rubrique'=>$id_rubrique),'data'=>''));

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_index'), "accueil", "accueil");

	echo debut_gauche("",true);

	if ($spip_display != 4) {
		echo pipeline('affiche_gauche',
						array('args'=>array('exec'=>'accueil','id_rubrique'=>$id_rubrique),
								'data'=>exec_accueil_navigation()));
	}

	echo creer_colonne_droite("", true);

	echo pipeline('affiche_droite',array('args'=>array('exec'=>'accueil','id_rubrique'=>$id_rubrique),'data'=>''));

	echo debut_droite("", true);
	
	$lister_objets = charger_fonction('lister_objets','inc');

	$date_now = date('Y-m-d H:i:s');
	if ($GLOBALS['meta']["post_dates"] == "non"
	AND $connect_statut == '0minirezo')
		echo $lister_objets('articles',array('titre'=>_T('info_article_a_paraitre'),'statut'=>'publie', 'par'=>'date', 'where'=>'date>'.sql_quote($date_now), 'date_sens'=>1));

	// Les articles recents
	//
	$contexte = array('titre'=>_T('articles_recents'),'statut'=>'publie', 'par'=>'date','nb'=>5);
	if ($GLOBALS['meta']["post_dates"] == "non")
		$contexte['where']='date<='.sql_quote($date_now);
	echo $lister_objets('articles',$contexte);

//
// Vos articles en cours 
//

	echo $lister_objets('articles',array('titre'=>afficher_plus_info(generer_url_ecrire('articles_page'))._T('info_en_cours_validation'),'statut'=>'prepa', 'par'=>'date','id_auteur'=>$GLOBALS['visiteur_session']['id_auteur']));


	echo encours_accueil();

	include_spip('inc/presenter_enfants');
	if (!$connect_id_rubrique)
		echo afficher_enfant_rub() . "<div class='nettoyeur'></div>";

 	echo pipeline('affiche_milieu',array('args'=>array('exec'=>'accueil'),'data'=>''));

	echo fin_gauche(), fin_page();
}
?>
