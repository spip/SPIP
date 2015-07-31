<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');
include_spip('inc/statistiques');

// Donne la hauteur du graphe en fonction de la valeur maximale
// Doit etre un entier "rond", pas trop eloigne du max, et dont
// les graduations (divisions par huit) soient jolies :
// on prend donc le plus proche au-dessus de x de la forme 12,16,20,40,60,80,100
// http://doc.spip.org/@maxgraph
function maxgraph($max) {
	switch (strlen($max)) {
	case 0:
		$maxgraph = 1;
		break;
	case 1:
		$maxgraph = 16;
		break;
	case 2:
		$maxgraph = (floor($max / 8) + 1) * 8;
		break;
	case 3:
		$maxgraph = (floor($max / 80) + 1) * 80;
		break;
	default:
		$maxgraph = (floor($max / (2 * pow(10, strlen($max)-2))) + 1) * 2 * pow(10, strlen($max)-2);
	}
	return $maxgraph;
}

// http://doc.spip.org/@http_img_rien
function http_img_rien($width, $height, $class='', $title='') {
	return http_img_pack('rien.gif', $title, 
		"width='$width' height='$height'" 
		. (!$class ? '' : (" class='$class'"))
		. (!$title ? '' : (" title=\"$title\"")));
}

// pondre les stats sous forme d'un fichier csv tres basique
// http://doc.spip.org/@statistiques_csv
function statistiques_csv($id) {

	$filename = 'stats_'.($id ? 'article'.$id : 'total').'.csv';
	header('Content-Type: text/csv');
	header('Content-Disposition: attachment; filename='.$filename);
	
	if ($id)
		$s = sql_select("date, visites", "spip_visites_articles", "id_article=$id", "", "date");
	else
		$s = sql_select("date, visites", "spip_visites", "", "", "date");
	while ($t = sql_fetch($s)) {
		echo $t['date'].";".$t['visites']."\n";
	}
}

// http://doc.spip.org/@exec_statistiques_visites_dist
function exec_statistiques_visites_dist()
{
	$id_article = intval(_request('id_article'));
	$aff_jours = intval(_request('aff_jours'));
	if (!$aff_jours) $aff_jours = 105;

	// nombre de referers a afficher
	$limit = intval(_request('limit'));
	if ($limit == 0) $limit = 100;

	if (!autoriser('voirstats', $id_article ? 'article':'', $id_article)) {
	  include_spip('inc/minipres');
	  echo minipres();
	} else {
		if (_request('format') == 'csv')
			statistiques_csv($id_article);
		else exec_statistiques_visites_args($id_article, $aff_jours, $limit);
	}
}

function exec_statistiques_visites_args($id_article, $aff_jours, $limit)
{
	$titre = $pourarticle = "";
	$class = " class='arial1 spip_x-small'";
	$style = 'color: #999999';

	if ($id_article){
		$result = sql_select("titre, visites, popularite", "spip_articles", "statut='publie' AND id_article=$id_article");

		if ($row = sql_fetch($result)) {
			$titre = typo($row['titre']);
			$total_absolu = $row['visites'];
			$val_popularite = round($row['popularite']);
		}
	} else {
		$row = sql_fetsel("SUM(visites) AS total_absolu", "spip_visites");
		$total_absolu = $row ? $row['total_absolu'] : 0;
		$val_popularite = 0;
	}

	if ($titre) $pourarticle = " "._T('info_pour')." &laquo; $titre &raquo;";

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_statistiques_visites').$pourarticle, "statistiques_visites", "statistiques");
	echo gros_titre(_T('titre_evolution_visite')."<html>".aide("confstat")."</html>",'', false);
//	barre_onglets("statistiques", "evolution");
	if ($titre) echo gros_titre($titre,'', false);

	echo debut_gauche('', true);

	echo "<br />";

	echo "<div class='iconeoff' style='padding: 5px;'>";
	echo "<div class='verdana1 spip_x-small'>";
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
	
	// Par popularite
	$result = sql_select("id_article, titre, popularite, visites", "spip_articles", "statut='publie' AND popularite > 0", "", "popularite DESC");

	if (sql_count($result)) {
		echo "<br />\n";
		echo "<div class='iconeoff' style='padding: 5px;'>\n";
		echo "<div class='verdana1 spip_x-small'>";
		echo typo(_T('info_visites_plus_populaires'));
		$open = "<ol style='padding-left:40px; font-size:x-small;color:#666666;'>";
		echo $open;
		$liste = 0;
		while ($row = sql_fetch($result)) {
			$titre = typo(supprime_img($row['titre'],''));
			$l_article = $row['id_article'];
			$visites = $row['visites'];
			$popularite = round($row['popularite']);
			$liste++;
			$classement[$l_article] = $liste;
			
			if ($liste <= 30) {
				$articles_vus[] = $l_article;
			
				if ($l_article == $id_article){
					echo "\n<li><b>$titre</b></li>";
				} else {
					echo "\n<li><a href='" . generer_url_ecrire("statistiques_visites","id_article=$l_article") . "' title='"._T('info_popularite', array('popularite' => $popularite, 'visites' => $visites))."'>$titre</a></li>";
				}
			}
		}
		$articles_vus = join($articles_vus, ",");
		$recents = "";
		$q = sql_select("id_article", "spip_articles", "statut='publie' AND popularite > 0", "", "date DESC", "10");

		while ($r = sql_fetch($q)) $recents .= ',' . $r['id_article'];
		if ($recents)
			$recents = " AND id_article IN (" . substr($recents, 1) . ')';
			
		$result_suite = sql_select("id_article, titre, popularite, visites", "spip_articles", "statut='publie'$recents AND id_article NOT IN ($articles_vus)", "", "popularite DESC");

		if (sql_count($result_suite) > 0) {
			echo "</ol><div style='text-align: center'>[...]</div>",$open;
			while ($row = sql_fetch($result_suite)) {
				$titre = typo(supprime_img($row['titre'], ''));
				$l_article = $row['id_article'];
				$visites = $row['visites'];
				$popularite = round($row['popularite']);
				$numero = $classement[$l_article];
				
				if ($l_article == $id_article){
					echo "\n<li><b>$titre</b></li>";
				} else {
					echo "\n<li><a href='" . generer_url_ecrire("statistiques_visites","id_article=$l_article") . "' title='"._T('info_popularite_3', array('popularite' => $popularite, 'visites' => $visites))."'>$titre</a></li>";
				}
			}
		}
			
		echo "</ol>";

		echo "<b>"._T('info_comment_lire_tableau')."</b><br />"._T('texte_comment_lire_tableau');

		echo "</div>";
		echo "</div>";
	}

	// Par visites depuis le debut
	$result = sql_select("id_article, titre, popularite, visites", "spip_articles", "statut='publie' AND popularite > 0", "", "visites DESC", "30");

	$n = sql_count($result);
	if ($n 	OR $id_article)
		echo creer_colonne_droite('', true);

	if ($id_article) {
		echo bloc_des_raccourcis(icone_horizontale(_T('icone_retour_article'), generer_url_ecrire("articles","id_article=$id_article"), "article-24.gif","rien.gif", false));
	}

	if ($n) {
		echo "<br /><div class='iconeoff' style='padding: 5px;'>";
		echo "<div style='overflow:hidden;' class='verdana1 spip_x-small'>";
		echo typo(_T('info_affichier_visites_articles_plus_visites'));
		echo "<ol style='padding-left:40px; font-size:x-small;color:#666666;'>";

		while ($row = sql_fetch($result)) {
			$titre = typo(supprime_img($row['titre'],''));
			$l_article = $row['id_article'];
			$visites = $row['visites'];
			$popularite = round($row['popularite']);
			$numero = $classement[$l_article];
				
			if ($l_article == $id_article){
				echo "\n<li><b>$titre</b></li>";
			} else {
				echo "\n<li><a href='" . generer_url_ecrire("statistiques_visites","id_article=$l_article") . "'\ntitle='"._T('info_popularite_4', array('popularite' => $popularite, 'visites' => $visites))."'>$titre</a></li>";
				}
		}
		echo "</ol>";
		echo "</div>";
		echo "</div>";
	}

	echo debut_droite('', true);

	$vis = "visites";
	if ($id_article) {
			$table = "spip_visites_articles";
			$table_ref = "spip_referers_articles";
			$where = "id_article=$id_article";
	} else {
			$table = "spip_visites";
			$table_ref = "spip_referers";
			$where = "0=0";
	}

	$select = "UNIX_TIMESTAMP(date) AS date, UNIX_TIMESTAMP(date) AS date_time, visites";
	$where2 = " date > DATE_SUB(NOW(),INTERVAL $aff_jours DAY)";
	$order = "date";

	$mode = statistiques_mode(self());
	echo debut_cadre_relief("statistiques-24.gif", true);
	echo statistiques_tous($select, $table, $where, $where2, $groupby, $order, $total_absolu, $val_popularite, $class, $style, $aff_jours, $classement, $id_article, $liste);
	echo fin_cadre_relief(true), $mode;

	if ($id_article AND  $n = sql_countsel('spip_signatures', "id_article=$id_article")) {

		echo "<br />", gros_titre(_L('Nombre de signatures par jour'),'', false);

		$stable = "spip_signatures";
		$swhere = "id_article=$id_article";
		$sselect = "UNIX_TIMESTAMP(date_time) AS date_time, (ROUND(UNIX_TIMESTAMP(date_time) / (24*3600)) *  (24*3600)) AS date, COUNT(*) AS visites";
		$swhere2 = " date_time > DATE_SUB(NOW(),INTERVAL 420 DAY)";
		$sgroupby = 'date';
		$sorder = "date_time";

		echo debut_cadre_relief("statistiques-24.gif", true);
		echo statistiques_tous($sselect, $stable, $swhere, $swhere2, $sgroupby, $sorder, $n, 0, $class, $style, $aff_jours);
		echo fin_cadre_relief(true), $mode;

		echo "<br />", gros_titre(_L('Nombre de signatures par mois'),'', false);
		echo statistiques_par_mois(sql_select("FROM_UNIXTIME(UNIX_TIMESTAMP(date_time),'%Y-%m') AS date_unix, COUNT(*) AS total_visites", 'spip_signatures',  "id_article=$id_article AND date_time > DATE_SUB(NOW(),INTERVAL 2700 DAY)", 'date_unix', "date_unix"), 0, $class, $style);
	}

	$r = sql_select("referer, referer_md5, $vis AS vis", $table_ref, $where, "", "vis DESC", $limit);

	$res = aff_referers ($r, $limit, generer_url_ecrire('statistiques_visites', ($id_article?"id_article=$id_article&":'').('limit=' . strval($limit+200))));
	if ($res) {
		echo gros_titre(_T("onglet_origine_visites"),'', false);

		echo "<div style='font-size:small;overflow:hidden;' class='verdana1'><br />";
		echo $res;
		echo "<br /></div>";	
	}

	echo fin_gauche(), fin_page();	
}

function statistiques_tous($select, $table, $where, $where2, $groupby, $order, $total_absolu, $val_popularite, $class, $style, $aff_jours, $classement=array(), $id_article=0, $liste=0)
{
	$date_debut = '';
	$log = array();
	$result = sql_select($select, $table, "$where AND $where2", $groupby, $order);
	while ($row = sql_fetch($result)) {
		$date = $row['date'];
		if (!$date_debut) $date_debut = $date;
		$log[$date] = $row['visites'];
	}

	// S'il y a au moins cinq minutes de stats :-)
	if (!count($log)) return;

	// les visites du jour
	$date_today = max(array_keys($log));
	$visites_today = $log[$date_today];
	// sauf s'il n'y en a pas :
	if (time()-$date_today>3600*24) {
		$date_today = time();
		$visites_today=0;
	}
	
	$nb_jours = floor(($date_today-$date_debut)/(3600*24));
	$max = max($log);
	$maxgraph = maxgraph($max);
	$rapport = 200 / $maxgraph;

	if (count($log) < 420) $largeur = floor(450 / ($nb_jours+1));
	if ($largeur < 1) {
		$largeur = 1;
		$agreg = ceil(count($log) / 420);	
	} else {
		$agreg = 1;
		if ($largeur > 50) $largeur = 50;
	}

	$largeur_abs = 420 / $aff_jours;
	
	if ($largeur_abs > 1) {
		$inc = ceil($largeur_abs / 5);
		$aff_jours_plus = 420 / ($largeur_abs - $inc);
		$aff_jours_moins = 420 / ($largeur_abs + $inc);
	}
	
	if ($largeur_abs == 1) {
		$aff_jours_plus = 840;
		$aff_jours_moins = 210;
	}
	
	if ($largeur_abs < 1) {
		$aff_jours_plus = 420 * ((1/$largeur_abs) + 1);
		$aff_jours_moins = 420 * ((1/$largeur_abs) - 1);
	}
	
	$pour_article = $id_article ? "&id_article=$id_article" : '';

	$zoom = '';

	$row = sql_fetsel($select, $table, $where, $groupby,  $order, "1");
	$date_premier = $row[$order];

	if ($date_premier < $date_debut)
		$zoom= http_href(generer_url_ecrire("statistiques_visites","aff_jours=$aff_jours_plus$pour_article"),
			 http_img_pack('loupe-moins.gif',
				       _T('info_zoom'). '-', 
				       "style='border: 0px; vertical-align: middle;'"),
			 "&nbsp;");
	if ( (($date_today - $date_debut) / (24*3600)) > 30)
		$zoom .= http_href(generer_url_ecrire("statistiques_visites","aff_jours=$aff_jours_moins$pour_article"), 
			 http_img_pack('loupe-plus.gif',
				       _T('info_zoom'). '+', 
				       "style='border: 0px; vertical-align: middle;'"),
			 "&nbsp;");

	if (flag_svg()) {
		list($moyenne,$val_prec, $res) = stat_logsvg($aff_jours, $agreg, $date_today, $id_article, $log, $total_absolu, $visites_today);
	} else {
		list($moyenne,$val_prec, $res) = stat_log1($agreg, $class, $date_debut, $date_today, $id_article, $largeur, $log, $max, $maxgraph, $rapport, $style, $val_popularite, $visites_today);
	}

	// cette ligne donne la moyenne depuis le debut
	// (desactive au profit de la moyenne "glissante")
	# $moyenne =  round($total_absolu / ((date("U")-$date_premier)/(3600*24)));

	$res .= "<span class='arial1 spip_x-small'>"
	. _T('texte_statistiques_visites')
	. "</span><br />"
	. "<table cellpadding='0' cellspacing='0' border='0' width='100%'><tr style='width:100%;'>"
	. "\n<td valign='top' style='width: 33%; ' class='verdana1'>"
	. _T('info_maximum')." "
	. $max . "<br />"
	. _T('info_moyenne')." "
	. round($moyenne). "</td>"
	. "\n<td valign='top' style='width: 33%; ' class='verdana1'>"
	. '<a href="'
	. generer_url_ecrire("statistiques_referers","")
	. '" title="'._T('titre_liens_entrants').'">'
	. _T('info_aujourdhui')
	. '</a> '
	. $visites_today;

	if ($val_prec > 0) $res .= '<br /><a href="' . generer_url_ecrire("statistiques_referers","jour=veille").'"  title="'._T('titre_liens_entrants').'">'._T('info_hier').'</a> '.$val_prec;
	if ($id_article) $res .= "<br />"._T('info_popularite_5').' '.$val_popularite;

	$res .= "</td>"
	."\n<td valign='top' style='width: 33%; ' class='verdana1'>"
	."<b>"
	._T('info_total')." "
	.$total_absolu."</b>";
	
	if ($id_article) {
		if ($classement[$id_article] > 0) {
			if ($classement[$id_article] == 1)
			      $ch = _T('info_classement_1', array('liste' => $liste));
			else
			      $ch = _T('info_classement_2', array('liste' => $liste));
			$res .= "<br />".$classement[$id_article].$ch;
		}
	} elseif ($table != 'spip_signatures') {
	  $res .= "<span class='spip_x-small'><br />"
	._T('info_popularite_2')." " . ceil($GLOBALS['meta']['popularite_total']) . "</span>";
	}
	$res .= "</td></tr></table>";	

///////////// Affichage par mois

	  if ((count($log) > 60) AND ($table <> 'spip_signatures')) {
		$res .= "<br />";
		$res .= "<span class='verdana1 spip_small'><b>"
	._T('info_visites_par_mois')."</b></span>";

		$res .= statistiques_par_mois(sql_select("FROM_UNIXTIME(UNIX_TIMESTAMP(date),'%Y-%m') AS date_unix, SUM(visites) AS total_visites", $table,  "$where AND date > DATE_SUB(NOW(),INTERVAL 2700 DAY)", 'date_unix', "date"), $visites_today, $class, $style);
	}
	return $zoom . $res;
}

// Le bouton pour CSV et pour passer de svg a htm

function statistiques_mode($lui)
{
	if (flag_svg()) {
		$lien = 'non'; $alter = 'HTML';
	} else {
		$lien = 'oui'; $alter = 'SVG';
	}

	return "\n<div style='text-align:".$GLOBALS['spip_lang_right'] . ";' class='verdana1 spip_x-small'>"
		. "<a href='". parametre_url($lui, 'var_svg', $lien)."'>$alter</a>" 
		. " | <a href='"
	  	. parametre_url($lui, 'format', 'csv')."'>CSV</a>"
		. "</div>\n";
}

function stat_log1($agreg, $class, $date_debut, $date_today, $id_article, $largeur, $log, $max, $maxgraph, $rapport, $style, $val_popularite, $visites_today) {
	$res = '';

	$test_agreg = $decal = $jour_prec = $val_prec =0;

	// Presentation graphique (rq: on n'affiche pas le jour courant)
	foreach ($log as $key => $value) {
		# quand on atteint aujourd'hui, stop
		if ($key == $date_today) break; 

		$test_agreg ++;
		
		if ($test_agreg == $agreg) {	
				
			$test_agreg = 0;
			
			if ($decal == 30) $decal = 0;
			$decal ++;
			$tab_moyenne[$decal] = $value;
			// Inserer des jours vides si pas d'entrees	
			if ($jour_prec > 0) {
				$ecart = floor(($key-$jour_prec)/((3600*24)*$agreg)-1);
				for ($i=0; $i < $ecart; $i++){
					if ($decal == 30) $decal = 0;
					$decal ++;
					$tab_moyenne[$decal] = $value;
	
					$ce_jour=date("Y-m-d", $jour_prec+(3600*24*($i+1)));
					$jour = nom_jour($ce_jour).' '.affdate_jourcourt($ce_jour);
	
					reset($tab_moyenne);
					$moyenne = 0;
					while (list(,$val_tab) = each($tab_moyenne))
						$moyenne += $val_tab;
					$moyenne = $moyenne / count($tab_moyenne);
		
					$hauteur_moyenne = round(($moyenne) * $rapport) - 1;
					$res .= "\n<td style='width: ${largeur}px'>";
					$difference = ($hauteur_moyenne) -1;
					$moyenne = round($moyenne,2); // Pour affichage harmonieux
					$tagtitle= attribut_html(supprimer_tags("$jour | "
						._T('info_visites')." | "
						._T('info_moyenne')." $moyenne"));
					if ($difference > 0) {	
						$res .= http_img_rien($largeur,1, 'trait_moyen', $tagtitle);
						$res .= http_img_rien($largeur, $hauteur_moyenne, '', $tagtitle);
					}
					$res .= http_img_rien($largeur,1,'trait_bas', $tagtitle);
					$res .= "</td>";
				}
			}
	
			$ce_jour=date("Y-m-d", $key);
			$jour = nom_jour($ce_jour).' '.affdate_jourcourt($ce_jour);
	
			reset($tab_moyenne);
			
			$moyenne = 0;
			while (list(,$val_tab) = each($tab_moyenne))
					$moyenne += $val_tab;
			$moyenne = $moyenne / count($tab_moyenne);
			$hauteur_moyenne = round($moyenne * $rapport) - 1;
			$hauteur = round($value * $rapport) - 1;
			$moyenne = round($moyenne,2); // Pour affichage harmonieux
			$res .= "\n<td style='width: ${largeur}px'>";
	
			$tagtitle= attribut_html(supprimer_tags("$jour | "
				._T('info_visites')." ".$value));
	
			if ($hauteur > 0){
				if ($hauteur_moyenne > $hauteur) {
					$difference = ($hauteur_moyenne - $hauteur) -1;
					$res .= http_img_rien($largeur, 1,'trait_moyen',$tagtitle);
					$res .= http_img_rien($largeur, $difference, '', $tagtitle);
					$res .= http_img_rien($largeur,1, "trait_haut", $tagtitle);
					if (date("w",$key) == "0") // Dimanche en couleur foncee
						$res .= http_img_rien($largeur, $hauteur, "couleur_dimanche", $tagtitle);
					else
						 $res .= http_img_rien($largeur,$hauteur, "couleur_jour", $tagtitle);
				} else if ($hauteur_moyenne < $hauteur) {
						$difference = ($hauteur - $hauteur_moyenne) -1;
						$res .= http_img_rien($largeur,1,"trait_haut", $tagtitle);
						if (date("w",$key) == "0") // Dimanche en couleur foncee
							$couleur =  'couleur_dimanche';
						else
							$couleur = 'couleur_jour';
						$res .= http_img_rien($largeur, $difference, $couleur, $tagtitle);
						$res .= http_img_rien($largeur,1,"trait_moyen", $tagtitle);
						$res .= http_img_rien($largeur, $hauteur_moyenne, $couleur, $tagtitle);
				} else {
					  $res .= http_img_rien($largeur, 1, "trait_haut", $tagtitle);
					  if (date("w",$key) == "0") // Dimanche en couleur foncee
						$res .= http_img_rien($largeur, $hauteur, "couleur_dimanche", $tagtitle);
					  else
						  $res .= http_img_rien($largeur,$hauteur, "couleur_jour", $tagtitle);
				}
			}
			$res .= http_img_rien($largeur, 1, 'trait_bas', $tagtitle);
			$res .= "</td>\n";
			
			$jour_prec = $key;
			$val_prec = $value;
		}
	}
	
	// Dernier jour
	$hauteur = round($visites_today * $rapport)	- 1;
	$total_absolu = $total_absolu + $visites_today;
	$res .= "\n<td style='width: ${largeur}px'>";
	// prevision de visites jusqu'a minuit
	// basee sur la moyenne (site) ou popularite (article)
	if (! $id_article) $val_popularite = $moyenne;
	$prevision = (1 - (date("H")*60 + date("i"))/(24*60)) * $val_popularite;
	$hauteurprevision = ceil($prevision * $rapport);
	// Afficher la barre tout en haut
	if ($hauteur+$hauteurprevision>0)
	  $res .= http_img_rien($largeur, 1, "trait_haut");
	// preparer le texte de survol (prevision)
	$tagtitle= attribut_html(supprimer_tags(_T('info_aujourdhui')." $visites_today &rarr; ".(round($prevision,0)+$visites_today)));
	// afficher la barre previsionnelle
	if ($hauteurprevision>0)
		$res .= http_img_rien($largeur, $hauteurprevision,'couleur_prevision', $tagtitle);
	// afficher la barre deja realisee
	if ($hauteur>0)
		$res .= http_img_rien($largeur, $hauteur, 'couleur_realise', $tagtitle);
	// et afficher la ligne de base
	$res .= http_img_rien($largeur, 1, 'trait_bas')
	. "</td>";
	
	$res = "\n<table cellpadding='0' cellspacing='0' border='0'><tr>" .
	  "\n<td ".http_style_background("fond-stats.gif").">"
	. "\n<table cellpadding='0' cellspacing='0' border='0' class='bottom'><tr>"
	. "\n<td style='background-color: black'>" . http_img_rien(1, 200) . "</td>"
	. $res 

	. "\n<td style='background-color: black'>" .http_img_rien(1, 1) ."</td>"
	. "</tr></table>"
	. "</td>" 
	. "\n<td ".http_style_background("fond-stats.gif")."  valign='bottom'>" . http_img_rien(3, 1, 'trait_bas') ."</td>"
	. "\n<td>" . http_img_rien(5, 1) ."</td>" 
	. "\n<td valign='top'>"
	. statistiques_echelle($maxgraph, $class, $style) 
	. "</td>"  
	. "</tr></table>"
	. statistiques_nom_des_mois($date_debut, $date_today, ($largeur / (24*3600*$agreg)));
	return array($moyenne, $val_prec, $res);
}

function statistiques_nom_des_mois($date_debut, $date_today, $largeur)
{
	global $spip_lang_left;

	$res = '';
	$gauche_prec = -50;
	$pas =  (24*3600);
	for ($jour = $date_debut; $jour <= $date_today; $jour += $pas) {
		
		if (date("d", $jour) == "1") {
			$newy = (date("m", $jour) == 1);
			$gauche = floor(($jour - $date_debut) * $largeur);
			if ($gauche - $gauche_prec >= 40 OR $newy) {
				$afficher = $newy ? 
				  ("<b>".annee(date("Y-m-d", $jour))."</b>")
				  : nom_mois(date("Y-m-d", $jour));

				  $res .= "<div class='arial0' style='border-$spip_lang_left: 1px solid black; padding-$spip_lang_left: 2px; padding-top: 3px; position: absolute; $spip_lang_left: ".$gauche."px; top: -1px;'>".$afficher."</div>";
				$gauche_prec = $gauche;
			}
		}
	}
	return "<div style='position: relative; height: 15px'>$res</div>";  
}

function statistiques_par_mois($query, $visites_today, $class, $style)
 {
	$entrees = array();

	while ($row = sql_fetch($query)) {
		$entrees[$row['date_unix']] = $row['total_visites'];
	}
	// Pour la derniere date, rajouter les visites du jour sauf si premier jour du mois
	if (date("d",time()) > 1) {
			$entrees[$date] += $visites_today;
		} else { // Premier jour du mois : le rajouter dans le tableau des date (car il n'etait pas dans le resultat de la requete SQL precedente)
			$entrees[date("Y-m",time())] = $visites_today;
		}
		
	if (count($entrees)>0){
		
		$max = max($entrees);
		$maxgraph = maxgraph($max);
		$rapport = 200/$maxgraph;

		$largeur = floor(420 / (count($entrees)));
		if ($largeur < 1) $largeur = 1;
		if ($largeur > 50) $largeur = 50;
	}

	$res = ''
	. "\n<table cellpadding='0' cellspacing='0' border='0'><tr>" .
		  "\n<td ".http_style_background("fond-stats.gif").">"
	. "\n<table cellpadding='0' cellspacing='0' border='0' class='bottom'><tr>"
	. "\n<td class='trait_bas'>" . http_img_rien(1, 200) ."</td>";

	$decal = 0;
	$tab_moyenne = "";
			
	while (list($key, $value) = each($entrees)) {
			
		$mois = affdate_mois_annee($key);
		if ($decal == 30) $decal = 0;
		$decal ++;
		$tab_moyenne[$decal] = $value;
		reset($tab_moyenne);
		$moyenne = 0;
		while (list(,$val_tab) = each($tab_moyenne))
			$moyenne += $val_tab;

		$moyenne = $moyenne / count($tab_moyenne);
		$hauteur_moyenne = round($moyenne * $rapport) - 1;
		$hauteur = round($value * $rapport) - 1;
		$res .= "\n<td style='width: ${largeur}px'>";

		$tagtitle= attribut_html(supprimer_tags("$mois | "
			._T('info_visites')." ".$value));

		if ($hauteur > 0){
			if ($hauteur_moyenne > $hauteur) {
				$difference = ($hauteur_moyenne - $hauteur) -1;
				$res .= http_img_rien($largeur, 1, 'trait_moyen');
				$res .= http_img_rien($largeur, $difference, '', $tagtitle);
				$res .= http_img_rien($largeur,1,"trait_haut");
				if (preg_match(",-01,",$key)){ // janvier en couleur foncee
					$res .= http_img_rien($largeur,$hauteur,"couleur_janvier", $tagtitle);
				} else {
					$res .= http_img_rien($largeur,$hauteur,"couleur_mois", $tagtitle);
				}
			}
			else if ($hauteur_moyenne < $hauteur) {
				$difference = ($hauteur - $hauteur_moyenne) -1;
				$res .= http_img_rien($largeur,1,"trait_haut", $tagtitle);
				if (preg_match(",-01,",$key)){ // janvier en couleur foncee
						$couleur =  'couleur_janvier';
				} else {
						$couleur = 'couleur_mois';
				}
				$res .= http_img_rien($largeur,$difference, $couleur, $tagtitle);
				$res .= http_img_rien($largeur,1,'trait_moyen',$tagtitle);
				$res .= http_img_rien($largeur,$hauteur_moyenne, $couleur, $tagtitle);
			} else {
				$res .= http_img_rien($largeur,1,"trait_haut", $tagtitle);
				if (preg_match(",-01,",$key)){ // janvier en couleur foncee
					$res .= http_img_rien($largeur, $hauteur, "couleur_janvier", $tagtitle);
				} else {
					$res .= http_img_rien($largeur,$hauteur, "couleur_mois", $tagtitle);
				}
			}
		}
		$res .= http_img_rien($largeur,1,'trait_bas', $tagtitle);
		$res .= "</td>\n";
	}
		
	return $res
	. "\n<td style='background-color: black'>" . http_img_rien(1, 1) . "</td>"
	. "</tr></table>"
	. "</td>" .
		  "\n<td ".http_style_background("fond-stats.gif")." valign='bottom'>" . http_img_rien(3, 1, 'trait_bas') ."</td>"
	. "\n<td>" . http_img_rien(5, 1) ."</td>"
	. "\n<td valign='top'>"
	. statistiques_echelle($maxgraph, $class, $style)
	. "</td></tr></table>";
 }

function statistiques_echelle($maxgraph, $class, $style)
{
  return "<div class='verdana1 spip_x-small'>"
 . "\n<table cellpadding='0' cellspacing='0' border='0'>"
 . "\n<tr><td style='height: 15' valign='top'>"
 . "<span class='arial1 spip_x-small'><b>" .round($maxgraph) ."</b></span>"
 . "</td></tr>"
 . "\n<tr><td valign='middle' $class style='$style;height: 25px'>"
 . round(7*($maxgraph/8))
 . "</td></tr>"
 . "\n<tr><td style='height: 25px' valign='middle'>"
 . "<span class='arial1 spip_x-small'>" .round(3*($maxgraph/4)) ."</span>"
 . "</td></tr>"
 . "\n<tr><td valign='middle' $class style='$style;height: 25px'>"
 . round(5*($maxgraph/8))
 . "</td></tr>"
 . "\n<tr><td style='height: 25px' valign='middle'>"
 . "<span class='arial1 spip_x-small'><b>" .round($maxgraph/2) ."</b></span>"
 . "</td></tr>"
 . "\n<tr><td valign='middle' $class style='$style;height: 25px'>"
 . round(3*($maxgraph/8))
 . "</td></tr>"
 . "\n<tr><td style='height: 25px' valign='middle'>"
 . "<span class='arial1 spip_x-small'>" .round($maxgraph/4) ."</span>"
 . "</td></tr>"
 . "\n<tr><td valign='middle' $class style='$style;height: 25px'>"
 . round(1*($maxgraph/8))
 . "</td></tr>"
 . "\n<tr><td style='height: 10px' valign='bottom'>"
 . "<span class='arial1 spip_x-small'><b>0</b></span>"
 . "</td>"
 . "</tr>"
 . "</table></div>";
}
	
function stat_logsvg($aff_jours, $agreg, $date_today, $id_article, $log, $total_absolu, $visites_today) {

	$total_absolu = $total_absolu + $visites_today;
	$test_agreg = $decal = $jour_prec = $val_prec = $total_loc =0;
	$n = ((3600*24)*$agreg);
	foreach ($log as $key => $value) {
		# quand on atteint aujourd'hui, stop
		if ($key == $date_today) break; 
		$test_agreg ++;
		if ($test_agreg == $agreg) {	
			$test_agreg = 0;
			if ($decal == 30) $decal = 0;
			$decal ++;
			$tab_moyenne[$decal] = $value;
			// Inserer des jours vides si pas d'entrees	
			if ($jour_prec > 0) {
				$ecart = floor(($key-$jour_prec)/$n-1);
				for ($i=0; $i < $ecart; $i++){
					if ($decal == 30) $decal = 0;
					$decal ++;
					$tab_moyenne[$decal] = $value;
					reset($tab_moyenne);
					$moyenne = 0;
					while (list(,$v) = each($tab_moyenne))
						$moyenne += $v;
					$moyenne /= count($tab_moyenne);
					// Pour affichage harmonieux
					$moyenne = round($moyenne,2); 
				}
			}
			$total_loc = $total_loc + $value;
			reset($tab_moyenne);

			$moyenne = 0;
			while (list(,$val_tab) = each($tab_moyenne))
				$moyenne += $val_tab;
			$moyenne = $moyenne / count($tab_moyenne);
			$moyenne = round($moyenne,2); // Pour affichage harmonieux
			$jour_prec = $key;
			$val_prec = $value;
		}
	}

	$res = "\n<div>"
	. "<object data='" . generer_url_ecrire('statistiques_svg',"id_article=$id_article&aff_jours=$aff_jours") . "' width='450' height='310' type='image/svg+xml'>"
	. "<embed src='" . generer_url_ecrire('statistiques_svg',"id_article=$id_article&aff_jours=$aff_jours") . "' width='450' height='310' type='image/svg+xml' />"
	. "</object>"
	. "\n</div>";

	return array($moyenne, $val_prec, $res);
}
?>
