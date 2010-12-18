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

// http://doc.spip.org/@aff_statistique_visites_popularite
function aff_statistique_visites_popularite($serveur, $id_article, &$classement, &$liste){
	// Par popularite
	$result = sql_select("id_article, titre, popularite, visites", "spip_articles", "statut='publie' AND popularite > 0", "", "popularite DESC",'','',$serveur);
	$out = '';
	while ($row = sql_fetch($result,$serveur)) {
		$l_article = $row['id_article'];
		$liste++;
		$classement[$l_article] = $liste;

		if ($liste <= 30) {
			$articles_vus[] = $l_article;
			$out .= statistiques_populaires($row, $id_article, $liste);
		}
	}
	$recents = array();
	$q = sql_select("id_article", "spip_articles", "statut='publie' AND popularite > 0", "", "date DESC", "10",'',$serveur);
	while ($r = sql_fetch($q,$serveur))
		if (!in_array($r['id_article'], $articles_vus))
			$recents[]= $r['id_article'];

	if ($recents) {
		$result = sql_select("id_article, titre, popularite, visites", "spip_articles", "statut='publie' AND " . sql_in('id_article', $recents), "", "popularite DESC",'','',$serveur);

		$out .= "</ul><div style='text-align: center'>[...]</div>" .
		"<ul class='classement'>";
		while ($row = sql_fetch($result,$serveur)) {
			$l_article = $row["id_article"];
			$out .= statistiques_populaires($row, $id_article, $classement[$l_article]);
		}
	}

	return !$out ? '' : (
		"<div class='cadre cadre-e'>\n"
		."<div class='cadre_padding verdana1 spip_x-small'>"
		.typo(_T('info_visites_plus_populaires'))
		."<ul class='classement'>"
		.$out

		."</ul>"
		
		."<div class='arial11'><b>"._T('info_comment_lire_tableau')."</b><br />"._T('texte_comment_lire_tableau')."</div>"

		."</div>"
		."</div>");
}

function statistiques_populaires($row, $id_article, $classement)
{
	$titre = typo(supprime_img($row['titre'], ''));
	$l_article = $row['id_article'];

	if ($l_article == $id_article){
		return "<li class='on'><em>$classement.</em>$titre</li>";
	} else {
		$visites = $row['visites'];
		$popularite = round($row['popularite']);
		return "<li><em>$classement.</em><a href='" . generer_url_ecrire("statistiques_visites","id_article=$l_article") . "' title='"._T('info_popularite_3', array('popularite' => $popularite, 'visites' => $visites))."'>$titre</a></li>";
	}
}

// http://doc.spip.org/@aff_statistique_visites_par_visites
function aff_statistique_visites_par_visites($serveur='', $id_article=0, $classement= array()) {
	$res = "";
	// Par visites depuis le debut
	$result = sql_select("id_article, titre, popularite, visites", "spip_articles", "statut='publie' AND popularite > 0", "", "visites DESC", "30",'',$serveur);

	while ($row = sql_fetch($result,$serveur)) {
		$titre = typo(supprime_img($row['titre'],''));
		$l_article = $row['id_article'];

		if ($l_article == $id_article){
			$res.= "<li class='on'><em>"
			. $classement[$l_article]
			. ".</em>$titre</li>";
		} else {
			$t = _T('info_popularite_4',
				array('popularite' => round($row['popularite']), 'visites' =>  $row['visites']));
			$h = generer_url_ecrire("statistiques_visites","id_article=$l_article");
			$out = "<a href='$h'\ntitle='$t'>$titre</a>";
			$res.= "<li><em>"
				. $classement[$l_article]
				. ".</em>$out</li>";
		}
	}

	if (!$res) return '';

	return "<div class='cadre cadre-e' style='padding: 5px;'>"
	  . "<div class='cadre_padding verdana1 spip_x-small'>"
	  . typo(_T('info_affichier_visites_articles_plus_visites'))
	  . "<ul class='classement'>"
	  . $res
	  . '</ul></div></div>';
}

// http://doc.spip.org/@http_img_rien
function http_img_rien($width, $height, $class='', $title='') {
	return http_img_pack('rien.gif', $title,
		"width='$width' height='$height'"
		. (!$class ? '' : (" class='$class'"))
		. (!$title ? '' : (" title=\"$title\"")));
}

// Donne la hauteur du graphe en fonction de la valeur maximale
// Doit etre un entier "rond", pas trop eloigne du max, et dont
// les graduations (divisions par huit) soient jolies :
// on prend donc le plus proche au-dessus de x de la forme 12,16,20,40,60,80,100
// http://doc.spip.org/@maxgraph
function maxgraph($max) {

	switch ($n =strlen($max)) {
	case 0:
		return 1;
	case 1:
		return 16;
	case 2:
		return (floor($max / 8) + 1) * 8;
	case 3:
		return (floor($max / 80) + 1) * 80;
	default:
		$dix = 2 * pow(10, $n-2);
		return (floor($max / $dix) + 1) * $dix;
	}
}

// http://doc.spip.org/@cadre_stat
function cadre_stat($stats, $table, $id_article)
{
	if (!$stats) return '';
	return debut_cadre_relief("statistiques-24.gif", true)
	. join('', $stats)
	. fin_cadre_relief(true)
	. statistiques_mode($table, $id_article);
}

// http://doc.spip.org/@statistiques_collecte_date
function statistiques_collecte_date($count, $date, $table, $where, $serveur)
{
	$result = sql_select("$count AS n, $date AS d", $table, $where, 'd', 'd', '','', $serveur);
	$log = array();

	while ($r = sql_fetch($result,$serveur)) $log[$r['d']] = $r['n'];
	return $log;
}

// http://doc.spip.org/@statistiques_tous
function statistiques_tous($log, $id_article, $table, $where, $order, $serveur, $duree, $interval, $total, $popularite, $liste='', $classement=array(), $script='')
{
	$r = array_keys($log);
	$date_fin = max($r);
	$today = strtotime(date('Y-m-d 01:00:01'));
	if ($today-$date_fin>$interval){
		$log[$today] = 0;
		$r = array_keys($log);
		$date_fin = max($r);
	}
	$date_debut = min($r);
	$date_premier = sql_getfetsel("UNIX_TIMESTAMP($order) AS d", $table, $where, '', $order, 1,'',$serveur);
	$last = $log[$date_fin];
	$max = max($log);
	$maxgraph = maxgraph($max);
	$rapport = 200 / $maxgraph;
	$agreg = ceil(count($log) / 420);
	$x = ceil(($date_fin-$date_debut)/$interval);
	$largeur = ($agreg > 1) ? 1 : (!$x ? 420 :  floor(420 / $x));

	if ($largeur > 50) $largeur = 50; elseif ($largeur < 1) $largeur = 1;

	list($moyenne,$prec, $res) = stat_log1($log, $agreg, $date_fin, $largeur, $rapport, $interval, $script);

	$stats = "\n<table cellpadding='0' cellspacing='0' border='0'><tr>" .
	  "\n<td ".http_style_background("fond-stats.gif").">"
	. "\n<table cellpadding='0' cellspacing='0' border='0' class='bottom'><tr>"
	. "\n<td style='background-color: black'>" . http_img_rien(1, 200) . "</td>"
	. $res
	  . (!is_array($liste) ? '' : // prevision que pour les visites
	     statistiques_prevision($id_article, $largeur, $moyenne, $rapport, $popularite, $last))
	. "\n<td style='background-color: black'>"
	. http_img_rien(1, 1) ."</td>"
	. "</tr></table>"
	. "</td>\n<td "
	. http_style_background("fond-stats.gif")."  valign='bottom'>"
	. http_img_rien(3, 1, 'trait_bas') ."</td>"
	. "\n<td>" . http_img_rien(5, 1) ."</td>"
	. "\n<td valign='top'>"
	. statistiques_echelle($maxgraph)
	. "</td>"
	. "</tr></table>";

	$total = "<b>" .  _T('info_total') ." " . $total."</b>";

	if  (is_array($liste)) {
		$liste = statistiques_classement($id_article, $classement, $liste);
		$legend = statistiques_nom_des_mois($date_debut, $date_fin, $largeur, $interval,$agreg)
		  . "<span class='arial1 spip_x-small'>"
		  . _T('texte_statistiques_visites')
		  . "</span><br />";
		$resume =  statistiques_resume($max, $moyenne, $last, $prec, $popularite);
	} else {
	  $legend = "<table width='100%'><tr><td width='50%'>" .
	    affdate_heure(date("Y-m-d H:i:s", $date_debut)) .
	    "</td><td width='50%' align='right'>" .
	    affdate_heure(date("Y-m-d H:i:s", $date_fin)) .
	    '</td></tr></table>';
	  $resume = '';
	}

	$legend .=
	  "<table cellpadding='0' cellspacing='0' border='0' width='100%'><tr style='width:100%;'>"
	. $resume
	. "\n<td valign='top' style='width: 33%; ' class='verdana1'>"
	. $total
	. $liste
	. "</td></tr></table>";

	$x = (!$duree) ? 1 : (420/ $duree);
	$zoom = statistiques_zoom($id_article, $x, $date_premier, $date_debut, $date_fin);
	return array($zoom, $stats, $mark, $legend);
}

// http://doc.spip.org/@statistiques_resume
function statistiques_resume($max, $moyenne, $last, $prec, $popularite)
{
	return  "\n<td valign='top' style='width: 33%; ' class='verdana1'>"
	. _T('info_maximum')." "
	. $max . "<br />"
	. _T('info_moyenne')." "
	. round($moyenne). "</td>"
	. "\n<td valign='top' style='width: 33%; ' class='verdana1'>"
	. '<a href="'
	. generer_url_ecrire("statistiques_referers")
	. '" title="'._T('titre_liens_entrants').'">'
	. _T('info_aujourdhui')
	. '</a> '
	. $last
	. (($prec <= 0) ? '' :
	     ('<br /><a href="'
	      . generer_url_ecrire("statistiques_referers","jour=veille")
	      .'"  title="'._T('titre_liens_entrants').'">'
	      ._T('info_hier').'</a> '.$prec))
	. (!$popularite ? '' :
	   ("<br />"._T('info_popularite_5').' '.$popularite))
	.  "</td>";
}

// http://doc.spip.org/@statistiques_classement
function statistiques_classement($id_article, $classement, $liste)
{
	if ($id_article) {
		if ($classement[$id_article] > 0) {
			if ($classement[$id_article] == 1)
			      $ch = _T('info_classement_1', array('liste' => $liste));
			else
			      $ch = _T('info_classement_2', array('liste' => $liste));
			return "<br />".$classement[$id_article].$ch;
		}
	  } else
		return "<span class='spip_x-small'><br />"
		  ._T('info_popularite_2')." "
		  . ceil($GLOBALS['meta']['popularite_total'])
		  . "</span>";
}

// http://doc.spip.org/@statistiques_zoom
function statistiques_zoom($id_article, $largeur_abs, $date_premier, $date_debut, $date_fin)
{
	if ($largeur_abs > 1) {
		$inc = ceil($largeur_abs / 5);
		$duree_plus = 420 / ($largeur_abs - $inc);
		$duree_moins = 420 / ($largeur_abs + $inc);
	}

	if ($largeur_abs == 1) {
		$duree_plus = 840;
		$duree_moins = 210;
	}

	if ($largeur_abs < 1) {
		$duree_plus = 420 * ((1/$largeur_abs) + 1);
		$duree_moins = 420 * ((1/$largeur_abs) - 1);
	}

	$pour_article = $id_article ? "&id_article=$id_article" : '';

	$zoom = '';
	
	if ($date_premier < $date_debut)
		$zoom= lien_ou_expose(generer_url_ecrire("statistiques_visites","duree=$duree_plus$pour_article"),
			 http_img_pack('loupe-moins.gif',
				       _T('info_zoom'). '-',
				       "style='border: 0px; vertical-align: middle;'"), false, '',
			 "&nbsp;");
	if ( (($date_fin - $date_debut) / (24*3600)) > 30)
		$zoom .= lien_ou_expose(generer_url_ecrire("statistiques_visites","duree=$duree_moins$pour_article"),
			 http_img_pack('loupe-plus.gif',
				       _T('info_zoom'). '+',
				       "style='border: 0px; vertical-align: middle;'"), false, '',
			 "&nbsp;");

	return $zoom;
}

// Presentation graphique
// (rq: on n'affiche pas le jour courant, c'est a la charge de la prevision)
// http://doc.spip.org/@stat_log1
function stat_log1($log, $agreg, $date_today, $largeur, $rapport, $interval, $script) {
	$res = '';
	$rien = http_img_rien($largeur, 1, 'trait_bas', '');
	$test_agreg = $decal = $date_prec = $val_prec = $moyenne = 0;
	foreach ($log as $key => $value) {
		if ($key == $date_today) break;
		$test_agreg ++;
		if ($test_agreg != $agreg) continue;
		$test_agreg = 0;
		if ($decal == 30) $decal = 0;
		$decal ++;
		$evol[$decal] = $value;
		// Inserer des jours vides si pas d'entrees
		if ($date_prec > 0) {
			$ecart = (($key-$date_prec)/$agreg)-$interval;
			for ($i=$interval; $i <= $ecart; $i+=$interval){
				if ($decal == 30) $decal = 0;
				$decal ++;
				$evol[$decal] = $value;
				$m = statistiques_moyenne($evol);
				$m = statistiques_vides($date_prec+$i, $largeur, $rapport, $m, $script);
				$res .= "\n<td style='width: ${largeur}px'>$m</td>";
			}
		}
		$moyenne = round(statistiques_moyenne($evol),2);
		$hauteur = round($value * $rapport) - 1;
		$m = ($hauteur <= 0) ? '' : statistiques_jour($key, $value, $largeur, $moyenne, $hauteur, $rapport, (date("w",$key) == "0"), $script);
		$res .= "\n<td style='width: ${largeur}px'>$m$rien</td>\n";
		$date_prec = $key;
		$val_prec = $value;
	}
	return array($moyenne, $val_prec, $res);
}

// http://doc.spip.org/@statistiques_href
function statistiques_href($jour, $moyenne, $script, $value='')
{
	$ce_jour=date("Y-m-d H:i:s", $jour);
	$title = nom_jour($ce_jour) . ' '
	  . ($script ? affdate_heure($ce_jour) :
	     (affdate_jourcourt($ce_jour)  .' '.
	      (" | " ._T('info_visites')." $value | " ._T('info_moyenne')." "
	       . round($moyenne,2))));
	return attribut_html(supprimer_tags($title));
}

// http://doc.spip.org/@statistiques_vides
function statistiques_vides($prec, $largeur, $rapport, $moyenne, $script)
{
	$hauteur_moyenne = round($moyenne*$rapport)-1;
	$title = statistiques_href($prec, $moyenne, $script);
	$tagtitle = $script ? '' : $title;
	if ($hauteur_moyenne > 1) {
		$res = http_img_rien($largeur,1, 'trait_moyen', $tagtitle)
		. http_img_rien($largeur, $hauteur_moyenne, '', $tagtitle);
	} else $res = '';
	$res .= http_img_rien($largeur,1,'trait_bas', $tagtitle);
	if (!$script) return $res;
	return "<a href='$script' title='$title'>$res</a>";
}


// http://doc.spip.org/@statistiques_prevision
function statistiques_prevision($id_article, $largeur, $moyenne, $rapport, $val_popularite, $visites_today)
{
	$hauteur = round($visites_today * $rapport) - 1;
	// $total_absolu = $total_absolu + $visites_today;
	// prevision de visites jusqu'a minuit
	// basee sur la moyenne (site) ou popularite (article)
	if (! $id_article) $val_popularite = $moyenne;
	$prevision = (1 - (date("H")*60 + date("i"))/(24*60)) * $val_popularite;
	$hauteurprevision = ceil($prevision * $rapport);
	// preparer le texte de survol (prevision)
	$tagtitle= attribut_html(supprimer_tags(_T('info_aujourdhui')." $visites_today &rarr; ".(round($prevision,0)+$visites_today)));

	$res .= "\n<td style='width: ${largeur}px'>";
	if ($hauteur+$hauteurprevision>0)
	// Afficher la barre tout en haut
		$res .= http_img_rien($largeur, 1, "trait_haut");
	if ($hauteurprevision>0)
	// afficher la barre previsionnelle
		$res .= http_img_rien($largeur, $hauteurprevision,'couleur_prevision', $tagtitle);
	// afficher la barre deja realisee
	if ($hauteur>0)
		$res .= http_img_rien($largeur, $hauteur, 'couleur_realise', $tagtitle);
	// et afficher la ligne de base
	$res .= http_img_rien($largeur, 1, 'trait_bas')
	. "</td>";

	return $res;
}

// Dimanche en couleur foncee
// http://doc.spip.org/@statistiques_jour
function statistiques_jour($key, $value, $largeur, $moyenne, $hauteur, $rapport, $dimanche, $script)
{
	$hauteur_moyenne = round($moyenne * $rapport) - 1;
	$title= statistiques_href($key, $moyenne, $script, $value);
	$tagtitle = $script ? '' : $title;
	$couleur = $dimanche ? "couleur_dimanche" :  "couleur_jour";
	if ($hauteur_moyenne > $hauteur) {
		$difference = ($hauteur_moyenne - $hauteur) -1;

		$res = http_img_rien($largeur, 1,'trait_moyen',$tagtitle)
		. http_img_rien($largeur, $difference, '', $tagtitle)
		. http_img_rien($largeur, 1, "trait_haut", $tagtitle)
		. http_img_rien($largeur, $hauteur, $couleur, $tagtitle);
	} else if ($hauteur_moyenne < $hauteur) {
		$difference = ($hauteur - $hauteur_moyenne) -1;
		$res = http_img_rien($largeur,1,"trait_haut", $tagtitle)
		. http_img_rien($largeur, $difference, $couleur, $tagtitle)
		. http_img_rien($largeur,1,"trait_moyen", $tagtitle)
		. http_img_rien($largeur, $hauteur_moyenne, $couleur, $tagtitle);
	} else {
		$res = http_img_rien($largeur, 1, "trait_haut", $tagtitle)
		. http_img_rien($largeur, $hauteur, $couleur, $tagtitle);
	}
	if ($script)
		$script .= "&amp;date=$key";
	else  {
		$y = date("Y", $key);
		$m = date("m", $key);
		$d = date("d", $key);
		$script = generer_url_ecrire('calendrier', 
				"type=semaine&annee=$y&mois=$m&jour=$d");
	} 
	return "<a href='$script' title='$title'>$res</a>";
}

// http://doc.spip.org/@statistiques_nom_des_mois
function statistiques_nom_des_mois($date_debut, $date_today, $largeur, $pas, $agreg)
{
	global $spip_lang_left;

	$res = '';
	$largeur /= ($pas*$agreg);
	$gauche_prec = -50;
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
				if ($gauche > 400) break; //400px max
			}
		}
	}
	return "<div style='position: relative; height: 15px'>$res</div>";
}

// http://doc.spip.org/@statistiques_par_mois
function statistiques_par_mois($entrees, $script){

	$maxgraph = maxgraph(max($entrees));
	$rapport = 200/$maxgraph;
	$largeur = floor(420 / (count($entrees)));
	if ($largeur < 1) $largeur = 1;
	if ($largeur > 50) $largeur = 50;
	$decal = 0;
	$tab_moyenne = array();

	$all = '';

	foreach($entrees as $key=>$value) {
		$key = substr($key,0,4).'-'.substr($key,4,2);
		$mois = affdate_mois_annee($key);
		if ($decal == 30) $decal = 0;
		$decal ++;
		$tab_moyenne[$decal] = $value;
		$moyenne = statistiques_moyenne($tab_moyenne);
		$hauteur_moyenne = round($moyenne * $rapport) - 1;
		$hauteur = round($value * $rapport) - 1;
		$res = '';
		$title= attribut_html(supprimer_tags("$mois | "
			._T('info_total')." ".$value));
		$tagtitle = $script ? '' : $title;
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

		if (!$script) {
			$y = annee($key);
			$m = mois($key);
			$href = generer_url_ecrire('calendrier', "type=mois&annee=$y&mois=$m&jour=1");
		} else $href = "$script&amp;date=$key";

		$all .= "\n<td style='width: ${largeur}px'><a href='"
		.  $href
		. '\' title="'
		. $title
		. '">'
		. $res
		. "</a></td>\n";
	}

	return
	  "\n<table cellpadding='0' cellspacing='0' border='0'><tr>"
	.  "\n<td ".http_style_background("fond-stats.gif").">"
	. "\n<table cellpadding='0' cellspacing='0' border='0' class='bottom'><tr>"
	. "\n<td class='trait_bas'>" . http_img_rien(1, 200) ."</td>"
	.  $all
	. "\n<td style='background-color: black'>" . http_img_rien(1, 1)
	. "</td>"
	. "</tr></table></td>"
	. "\n<td ".http_style_background("fond-stats.gif")." valign='bottom'>"
	. http_img_rien(3, 1, 'trait_bas') ."</td>"
	. "\n<td>" . http_img_rien(5, 1) ."</td>"
	. "\n<td valign='top'>"
	. statistiques_echelle($maxgraph)
	. "</td></tr></table>";
 }

// http://doc.spip.org/@statistiques_echelle
function statistiques_echelle($maxgraph)
{
  return recuperer_fond('prive/stats/echelle', array('echelle' => $maxgraph));
}

// http://doc.spip.org/@statistiques_moyenne
function statistiques_moyenne($tab)
{
	if (!$tab) return 0;
	$moyenne = 0;
	foreach($tab as $v) $moyenne += $v;
	return  $moyenne / count($tab);
}


// http://doc.spip.org/@statistiques_signatures_dist
function statistiques_signatures_dist($duree, $interval, $type, $id_article, $serveur)
{
	$where = "id_article=$id_article";
	$total = sql_countsel("spip_signatures", $where);
	if (!$total) return '';

	$order = 'date_time';
	if ($duree)
		$where .= " AND $order > DATE_SUB(".sql_quote(date('Y-m-d H:i:s')).",INTERVAL $duree $type)";

	$log = statistiques_collecte_date('COUNT(*)', "(FLOOR(UNIX_TIMESTAMP($order) / $interval) *  $interval)", 'spip_signatures', $where, $serveur);

	$script = generer_url_ecrire('controle_petition', "id_article=$id_article");
	if (count($log) > 1) {
		$res = statistiques_tous($log, $id_article, "spip_signatures", "id_article=$id_article", "date_time", $serveur, $duree, $interval, $total, 0, '', array(), $script);
		$res = gros_titre(_T('titre_page_statistiques_signatures_jour'),'', false) . cadre_stat($res, 'spip_signatures', $id_article);
	} else $res = '';

	$mois = statistiques_collecte_date( "COUNT(*)",
		"DATE_FORMAT(date_time,'%Y%m')",
		"spip_signatures",
		"date_time > DATE_SUB(".sql_quote(date('Y-m-d H:i:s')).",INTERVAL 2700 DAY)"
		. (" AND id_article=$id_article"),
		$serveur);

	return "<br />"
	. $res
	. (!$mois ? '' : (
	  "<br />"
	. gros_titre(_T('titre_page_statistiques_signatures_mois'),'', false)
	. statistiques_par_mois($mois, $script)))
	. ($res ? '' : statistiques_mode("spip_signatures", $id_article))
;
}

// http://doc.spip.org/@statistiques_forums_dist
function statistiques_forums_dist($duree, $interval, $type, $id_article, $serveur)
{
	$where = "id_article=$id_article AND statut='publie'";
	$total = sql_countsel("spip_forum", $where);
	if (!$total) return '';
	$order = 'date_heure';
	$interval = 24 * 3600;
	$oldscore = 420;
	$oldlog = array();
	while ($interval >= 1) {
		$log = statistiques_collecte_date('COUNT(*)', "(FLOOR(UNIX_TIMESTAMP($order) / $interval) *  $interval)", 'spip_forum', $where, $serveur);
		if (count($log) > 3) break;
		$oldlog = $log;
		$oldinterval = $interval;
		$interval /= ($interval>3600) ?  24 : 60;
	}
	if (count($log) > 20) {
	  $interval = $oldinterval;
	  $log = $oldlog;
	}
	$script = generer_url_ecrire('articles_forum', "id_article=$id_article");
	$date = sql_getfetsel('UNIX_TIMESTAMP(date)', 'spip_articles', $where);
	$back = 10*ceil((time()-$date) / 3600);
	$jour = statistiques_tous($log, $id_article, "spip_forum", $where, "date_heure", $serveur, $back, $interval, $total, 0, '', array(), $script);

	return "<br />"
	. gros_titre(_T('titre_page_statistiques_messages_forum'),'', false)
	  . cadre_stat($jour, 'spip_forum', $id_article);
}

// Le bouton pour CSV

// http://doc.spip.org/@statistiques_mode
function statistiques_mode($table, $id=0)
{
	global $spip_lang_left;
	$t = str_replace('spip_', '', $table);
	$fond = (strstr($t, 'visites') ? 'statistiques' : $t);
	$args = array();
	if ($id) {
		$fond .= "_article"; 
		$args['id_article'] = $id;
	}
	include_spip('inc/acces');
	$args = param_low_sec($fond, $args, '', 'transmettre');
	$url = generer_url_public('transmettre', $args);
	return "<a style='float: $spip_lang_left;' href='$url'>CSV</a>";
}
?>
