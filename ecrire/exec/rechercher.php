<?php
/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/actions');
include_spip('inc/texte');

// http://doc.spip.org/@exec_rechercher_dist
function exec_rechercher_dist(){
	$id = intval(_request('id'));
	$exclus = intval(_request('exclus'));
	$rac = spip_htmlentities(_request('rac'));
	$type = _request('type');
	$do = _request('do');
	if (preg_match('/^\w*$/', $do))
		$r = exec_rechercher_args($id, $type, $exclus, $rac, $do);
	else $r = '';
	ajax_retour($r);
}

// http://doc.spip.org/@exec_rechercher_args
function exec_rechercher_args($id, $type, $exclus, $rac, $do){
	if (!$do) $do = 'aff';

	$where = preg_split(",\s+,", $type);
	if ($where){
		foreach ($where as $k => $v){
			$where[$k] = "'%" . substr(str_replace("%", "\%", sql_quote($v,'','string')), 1, -1) . "%'";
		}
		$where_titre = ("(titre LIKE " . join(" AND titre LIKE ", $where) . ")");
		$where_desc = ("(descriptif LIKE " . join(" AND descriptif LIKE ", $where) . ")");
		$where_id = ("(id_rubrique = " . intval($type) . ")");
	} else {
		$where_titre = " 1=2";
		$where_desc = " 1=2";
		$where_id = " 1=2";
	}

	if ($exclus){
		include_spip('inc/rubriques');
		$where_exclus = " AND " . sql_in('id_rubrique', calcul_branche_in($exclus), 'NOT');
	} else  $where_exclus = '';

	$res = sql_select("id_rubrique, id_parent, titre", "spip_rubriques", "$where_id$where_exclus");

	$points = $rub = array();

	while ($row = sql_fetch($res)){
		$id_rubrique = $row["id_rubrique"];
		$rub[$id_rubrique]["titre"] = typo($row["titre"]);
		$rub[$id_rubrique]["id_parent"] = $row["id_parent"];
		$points[$id_rubrique] = $points[$id_rubrique]+3;
	}
	$res = sql_select("id_rubrique, id_parent, titre", "spip_rubriques", "$where_titre$where_exclus");

	while ($row = sql_fetch($res)){
		$id_rubrique = $row["id_rubrique"];
		$rub[$id_rubrique]["titre"] = typo($row["titre"]);
		$rub[$id_rubrique]["id_parent"] = $row["id_parent"];
		if (isset($points[$id_rubrique]))
			$points[$id_rubrique] += 2;
		else $points[$id_rubrique] = 0;
	}
	$res = sql_select("id_rubrique, id_parent, titre", "spip_rubriques", "$where_desc$where_exclus");

	while ($row = sql_fetch($res)){
		$id_rubrique = $row["id_rubrique"];
		$rub[$id_rubrique]["titre"] = typo($row["titre"]);
		$rub[$id_rubrique]["id_parent"] = $row["id_parent"];
		if (isset($points[$id_rubrique]))
			$points[$id_rubrique] += 1;
		else $points[$id_rubrique] = 0;
	}

	if ($points){
		arsort($points);
		$style = " style='background-image: url(" . chemin_image('secteur-12.png') . ")'";
		foreach ($rub as $k => $v){
			$rub[$k]['atts'] = ($v["id_parent"] ? $style : '')
				. " class='petite-rubrique'";
		}
	}

	return (proposer_item($points, $rub, $rac, $type, $do));
}

// Resultat de la recherche interactive demandee par la fonction JS
// onkey_rechercher qui testera s'il comporte une seule balise au premier niveau
// car cela qui indique qu'un seul resultat a ete trouve.
// ==> attention a composer le message d'erreur avec au moins 2 balises

// http://doc.spip.org/@proposer_item
function proposer_item($ids, $titles, $rac, $type, $do){

	if (!$ids)
		return "<br /><br /><div style='padding: 5px; color: red;'><b>"
		. spip_htmlentities($type)
		. "</b> :  " . _T('avis_aucun_resultat') . "</div>";

	$ret = '';
	$info = generer_url_ecrire('informer', "type=rubrique&rac=$rac&id=");

	$onClick = "aff_selection(this.firstChild.title,'$rac" . "_selection','$info', event)";

	$ondbClick = "$do(this.firstChild.firstChild.nodeValue,this.firstChild.title,'selection_rubrique', 'id_parent');";

	foreach ($ids as $id => $bof){

		$titre = strtr(str_replace("'", "&#8217;", str_replace('"', "&#34;", textebrut($titles[$id]["titre"]))), "\n\r", "  ");

		$ret .= "<div class='highlight off'\nonclick=\"changerhighlight(this); "
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
