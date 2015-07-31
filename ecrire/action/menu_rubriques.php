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

include_spip('inc/autoriser');
include_spip('inc/texte');
include_spip('inc/filtres');

function action_menu_rubriques_dist() {

	// si pas acces a ecrire, pas acces au menu
	// on renvoi un 401 qui fait echouer la requete ajax silencieusement
	if (!autoriser('ecrire')){
		$retour = "<ul class='cols_1'><li class='toutsite'><a href='".generer_url_ecrire('accueil')."'>"._T('public:lien_connecter')."</a></li></ul>";
		include_spip('inc/actions');
		ajax_retour($retour);
		exit;
	}

	if ($date = intval(_request('date')))
		header("Last-Modified: ".gmdate("D, d M Y H:i:s", $date)." GMT");

	$r = gen_liste_rubriques();
	if (!$r
	AND isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
	AND !strstr($_SERVER['SERVER_SOFTWARE'],'IIS/')) {
		include_spip('inc/headers');
		header('Content-Type: text/html; charset='. $GLOBALS['meta']['charset']);
		http_status(304);
		exit;
	} else {
		include_spip('inc/actions');
		$ret = menu_rubriques();
		ajax_retour($ret);
	}
}

function menu_rubriques($complet = true){
	$ret = "<li class='toutsite'><a href='".generer_url_ecrire('plan')."'>"._T('info_tout_site')."</a></li>";

	if (!$complet) {
		return "<ul class='cols_1'>$ret\n</ul>\n";
	}

	if (!isset($GLOBALS['db_art_cache'])){
		gen_liste_rubriques();
	}
	$arr_low = extraire_article(0, $GLOBALS['db_art_cache']);

	$total_lignes = $i = sizeof($arr_low);

	if ($i > 0) {
		$nb_col = min(8,ceil($total_lignes / 30));
		if ($nb_col <= 1) $nb_col =  ceil($total_lignes / 10);
		foreach( $arr_low as $id_rubrique => $titre_rubrique) {
			if (autoriser('voir','rubrique',$id_rubrique)){
			  $ret .= bandeau_rubrique($id_rubrique, $titre_rubrique, $i);
			  $i++;
			}
		}

		$ret = "<ul class='cols_$nb_col'>"
		  . $ret
		  . "\n</ul>\n";
	}
	else
		$ret = "<ul class='cols_1'>$ret\n</ul>\n";
	
	return $ret;
}

// http://doc.spip.org/@bandeau_rubrique
function bandeau_rubrique($id_rubrique, $titre_rubrique, $zdecal) {
	static $zmax = 6;

	$nav = "<a href='"
	. generer_url_entite($id_rubrique,'rubrique','','',false)
	. "'>"
	. supprimer_tags(preg_replace(',[\x00-\x1f]+,', ' ', $titre_rubrique))
	. "</a>\n";

	// Limiter volontairement le nombre de sous-menus
	if (!(--$zmax)) {
		$zmax++;
		return "\n<li>$nav</li>";
	}

	$arr_rub = extraire_article($id_rubrique, $GLOBALS['db_art_cache']);
	$i = sizeof($arr_rub);
	if (!$i) {
		$zmax++;
		return "\n<li>$nav</li>";
	}


	$nb_col = 1;
	if ($nb_rub = count($arr_rub)) {
		$nb_col = min(10,max(1,ceil($nb_rub / 10)));
	}
	$ret = "<li class='haschild'>$nav<ul class='cols_$nb_col'>";
	foreach( $arr_rub as $id_rub => $titre_rub) {
		if (autoriser('voir','rubrique',$id_rub)){
			$titre = supprimer_numero(typo($titre_rub));
			$ret .= bandeau_rubrique($id_rub, $titre, $zdecal+$i);
			$i++;
		}
	}
	$ret .= "</ul></li>\n";
	$zmax++;
	return $ret;
}


// http://doc.spip.org/@extraire_article
function extraire_article($id_p, $t) {
	return array_key_exists($id_p, $t) ?  $t[$id_p]: array();
}

// http://doc.spip.org/@gen_liste_rubriques
function gen_liste_rubriques() {

	include_spip('inc/config');
	// ici, un petit fichier cache ne fait pas de mal
	$last = lire_config('date_calcul_rubriques', 0);
	if (lire_fichier(_CACHE_RUBRIQUES, $cache)) {
		list($date,$GLOBALS['db_art_cache']) = @unserialize($cache);
		if ($date == $last) return false; // c'etait en cache :-)
	}
	// se restreindre aux rubriques utilisees recemment +secteurs

	$where = sql_in_select("id_rubrique", "id_rubrique", "spip_rubriques", "", "", "id_parent=0 DESC, date DESC", _CACHE_RUBRIQUES_MAX);

	// puis refaire la requete pour avoir l'ordre alphabetique

	$res = sql_select("id_rubrique, titre, id_parent", "spip_rubriques", $where, '', 'id_parent, 0+titre, titre');

	// il ne faut pas filtrer le autoriser voir ici
	// car on met le resultat en cache, commun a tout le monde
	$GLOBALS['db_art_cache'] = array();
	while ($r = sql_fetch($res)) {
		$t = sinon($r['titre'], _T('ecrire:info_sans_titre'));
		$GLOBALS['db_art_cache'][$r['id_parent']][$r['id_rubrique']] = supprimer_numero(typo($t));
	}

	$t = array($last ? $last : time(), $GLOBALS['db_art_cache']);
	ecrire_fichier(_CACHE_RUBRIQUES, serialize($t));
	return true;
}
?>
