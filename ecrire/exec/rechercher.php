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

// http://doc.spip.org/@exec_rechercher_dist
function exec_rechercher_dist()
{
	global $id, $exclus, $type, $rac;

	$id = intval($id);
	$exclus = intval($exclus);
	$rac = htmlentities($rac);

	include_spip('inc/texte');
	$where = split("[[:space:]]+", $type);
	if ($where) {
		foreach ($where as $k => $v) 
			$where[$k] = "'%" . substr(str_replace("%","\%", _q($v)),1,-1) . "%'";
		$where_titre = ("(titre LIKE " . join(" AND titre LIKE ", $where) . ")");
		$where_desc = ("(descriptif LIKE " . join(" AND descriptif LIKE ", $where) . ")");
		$where_id = ("(id_rubrique = " . join(" AND id_rubrique = ", $where) . ")");
	} else {
		$where_titre = " 1=2";
		$where_desc = " 1=2";
		$where_id = " 1=2";
	}

	if ($exclus) {
		include_spip('inc/rubriques');
		$where_exclus = " AND id_rubrique NOT IN (".calcul_branche($exclus).")";
	} else
		$where_exclus = '';

	$res = spip_query("SELECT id_rubrique, id_parent, titre FROM spip_rubriques WHERE $where_id$where_exclus");

	$points = $rub = array();

	while ($row = spip_fetch_array($res)) {
		$id_rubrique = $row["id_rubrique"];
		$rub[$id_rubrique]["titre"] = typo ($row["titre"]);
		$rub[$id_rubrique]["id_parent"] = $row["id_parent"];
		$points[$id_rubrique] = $points[$id_rubrique] + 3;
	}
	$res = spip_query("SELECT id_rubrique, id_parent, titre FROM spip_rubriques WHERE $where_titre$where_exclus");
	while ($row = spip_fetch_array($res)) {
		$id_rubrique = $row["id_rubrique"];
		$rub[$id_rubrique]["titre"] = typo ($row["titre"]);
		$rub[$id_rubrique]["id_parent"] = $row["id_parent"];
		$points[$id_rubrique] = $points[$id_rubrique] + 2;
	}
	$res = spip_query("SELECT id_rubrique, id_parent, titre FROM spip_rubriques WHERE $where_desc$where_exclus");
	while ($row = spip_fetch_array($res)) {
		$id_rubrique = $row["id_rubrique"];
		$rub[$id_rubrique]["titre"] = typo ($row["titre"]);
		$rub[$id_rubrique]["id_parent"] = $row["id_parent"];
		$points[$id_rubrique] = $points[$id_rubrique] + 1;
	}
		

	if ($points) {
		arsort($points);
		$style = " style='background-image: url(" . _DIR_IMG_PACK . "secteur-12.gif)'";
		foreach($rub as $k => $v) {
			$rub[$k]['atts'] = ($v["id_parent"] ? $style : '')
			. " class='arial11 petite-rubrique'";
		}
	}

	ajax_retour(proposer_item($points, $rub, $rac));

}

// Resultat de la recherche interactive demandee par la fonction JS
// onkey_rechercher qui testera s'il comporte une seule balise au premier niveau
// car cela qui indique qu'un seul resultat a ete trouve.
// ==> attention a composer le message d'erreur avec au moins 2 balises

function proposer_item ($ids, $titles, $rac)
{

	if (!$ids)
		return "<br /><br /><div style='padding: 5px; color: red;'><b>"
		.htmlentities($type)
		."</b> :  "._T('avis_aucun_resultat')."</div>";

	$ret = '';
	$info = generer_url_ecrire('informer', "type=rubrique&rac=$rac&id=");

	$onClick = "aff_selection(this.firstChild.title,'$rac". "_selection','$info')";

	$ondbClick = "aff_selection_titre(this.firstChild.firstChild.nodeValue,this.firstChild.title,'selection_rubrique', 'id_parent');";

	foreach($ids as $id => $bof) {
				
		$titre = strtr(str_replace("'", "&#8217;", str_replace('"', "&#34;", textebrut($titles[$id]["titre"]))), "\n\r", "  ");

		$ret .= "<div class='pashighlight'\nonClick=\"changerhighlight(this); "
		. $onClick
		. "\"\nondblclick=\""
		. $ondbClick
		. $onClick
		. " \"><div"
		. $titles[$id]["atts"]
		. " title='$id'>&nbsp; "
		. $titre
		. "</div></div>";
	}
	return $ret;
}

?>
