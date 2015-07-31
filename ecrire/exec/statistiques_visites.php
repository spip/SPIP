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
include_spip('inc/statistiques');

// http://doc.spip.org/@exec_statistiques_visites_dist
function exec_statistiques_visites_dist()
{
	$id_article = intval(_request('id_article'));
	$type = _request('type');
	if (!preg_match('/^\w+$/', $type)) $type = 'day';
	$duree = intval(_request('duree'));
	if (!$duree) $duree = 105;
	$interval = intval(_request('interval'));
	if (!$interval) {
	  if ($type == 'day')
	    $interval = 3600*24;
	  else $interval = 3600;
	}

	// nombre de referers a afficher
	$limit = intval(_request('limit'));
	if ($limit == 0) $limit = 100;

	if (!autoriser('voirstats', $id_article ? 'article':'', $id_article)) {
		include_spip('inc/minipres');
		echo minipres();
	} else exec_statistiques_visites_args($id_article, $duree, $interval, $type, $limit);
}


// http://doc.spip.org/@exec_statistiques_visites_args
function exec_statistiques_visites_args($id_article, $duree, $interval, $type, $limit,$serveur='')
{
	$titre = $pourarticle = "";

	if ($id_article){
		$row = sql_fetsel("titre, visites, popularite", "spip_articles", "statut='publie' AND id_article=$id_article",'','','','',$serveur);

		if ($row) {
			$titre = typo($row['titre']);
			$total_absolu = $row['visites'];
			$val_popularite = round($row['popularite']);
		}
	} else {
		$row = sql_fetsel("SUM(visites) AS total_absolu", "spip_visites",'','','','','',$serveur);
		$total_absolu = $row ? $row['total_absolu'] : 0;
		$val_popularite = 0;
	}

	if ($titre) $pourarticle = " "._T('info_pour')." &laquo; $titre &raquo;";
	if ($serveur) {
		if ($row = sql_fetsel('valeur','spip_meta',"nom='nom_site'",'','','','',$serveur)){
			$titre = $row['valeur'].($titre?" / $titre":"");
		}
	}

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_statistiques_visites').$pourarticle, "statistiques_visites", "statistiques");
	echo gros_titre(_T('titre_evolution_visite')."<html>".aide("confstat")."</html>",'', false);
//	barre_onglets("statistiques", "evolution");
	if ($titre) echo gros_titre($titre,'', false);

	echo debut_gauche('', true);
	echo "<div class='cadre cadre-e' style='padding: 5px;'>";
	echo "<div class='cadre_padding verdana1 spip_x-small'>";
	echo typo(_T('info_afficher_visites'));
	echo "<ul>";

	if ($id_article>0) {
		echo "<li><b><a href='" . generer_url_ecrire("statistiques_visites","") . "'>"._T('info_tout_site')."</a></b></li>";
	} else {
		echo "<li><b>"._T('titre_page_articles_tous')."</b></li>";
	}

	echo "</ul>";
	echo "</div>";
	echo "</div>";
	
	$classement = array();
	$liste = 0;
	echo aff_statistique_visites_popularite($serveur, $id_article, $classement, $liste);

	// Par visites depuis le debut
	$result = aff_statistique_visites_par_visites($serveur, $id_article, $classement);

	if ($result OR $id_article)
		echo creer_colonne_droite('', true);

	if ($id_article) {
		echo bloc_des_raccourcis(icone_horizontale(_T('icone_retour_article'), generer_url_ecrire("articles","id_article=$id_article"), "article-24.gif","rien.gif", false));
	}
	echo $result;

	echo debut_droite('', true);

	if ($id_article) {
			$table = "spip_visites_articles";
			$table_ref = "spip_referers_articles";
			$where = "id_article=$id_article";
			  
	} else {
			$table = "spip_visites";
			$table_ref = "spip_referers";
			$where = "";
	}

	$order = "date";

	$where2 = $duree ? "$order > DATE_SUB(".sql_quote(date('Y-m-d H:i:s')).",INTERVAL $duree $type)": '';
	if ($where) $where2 = $where2 ?  "$where2 AND $where" : $where;

	// sur certains SQL, la division produit un entier tronque a la valeur inferieure
	// on ne peut donc faire un CEIL, il faut faire un FLOOR
	$log = statistiques_collecte_date('visites', "(FLOOR((UNIX_TIMESTAMP($order)+$interval-1) / $interval) *  $interval)", $table, $where2, $serveur);

	if ($log)
	  $res = statistiques_tous($log, $id_article, $table, $where, $order, $serveur, $duree, $interval, $total_absolu, $val_popularite,  $classement, $liste);
	  
	$mois = statistiques_collecte_date("SUM(visites)",
		"DATE_FORMAT($order,'%Y%m')", 
		$table,
		"$order > DATE_SUB(NOW(),INTERVAL 2700 DAY)"
		. ($where ? " AND $where" : ''),
		$serveur);

	if (count($mois)>1)  {
		$res[] = "<br /><span class='verdana1 spip_small'><b>"
			. _T('info_visites_par_mois')
			. "</b></span>"
			. statistiques_par_mois($mois, '');
	}
  echo cadre_stat($res, $table, $id_article);

	if ($id_article) {
		$signatures = charger_fonction('signatures', 'statistiques');
		echo $signatures($duree, $interval, $type, $id_article, $serveur);
		/*
  Il faudra optimiser les requetes de ces stats c'est vraiment trop horrible :
  plusieurs secondes d'attente sur un site comme contrib.
  par ailleurs, l'affichage presente des defauts :
  cf http://trac.rezo.net/trac/spip/ticket/1598
		$forums = charger_fonction('forums', 'statistiques');
		echo $forums($duree, $interval, $type, $id_article, $serveur);
		*/
	}


	$referenceurs = charger_fonction('referenceurs', 'inc');
	$res = $referenceurs($id_article, "visites", $table_ref, $where, '', $limit);

	if ($res) {

		// Le lien pour en afficher "plus"
		$args = ($id_article?"id_article=$id_article&" : '') . "limit=" . strval($limit+200);
		$n =  count($res);
		$plus = generer_url_ecrire('statistiques_visites', $args);
		if ($plus) {
			$plus = "<div style='text-align:right;'><b><a href='$plus'>+++</a></b></div>";
		}
		$titre = _T("onglet_origine_visites")
		. " ($n " 
		. ($n == 1 ?  _T('info_site') :  _T('info_sites'))
		  . ")";
		echo '<br />', gros_titre($titre,'', false);
		echo "<div style='overflow:hidden;' class='verdana1 spip_small'><br />";
		echo "<ul class='referers'><li>";
		echo join("</li><li>\n",$res);
		echo "</li></ul>";
		echo $plus;
		echo "<br /></div>";	
	}

	echo fin_gauche(), fin_page();	
}
?>
