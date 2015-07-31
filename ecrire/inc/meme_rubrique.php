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
// http://doc.spip.org/@inc_meme_rubrique_dist
function inc_meme_rubrique_dist($id_rubrique, $id, $type, $order='', $limit=NULL, $ajax=false)
{
	global $spip_lang_right, $spip_lang_left;
	include_spip('inc/presentation');
	include_spip('inc/afficher_objets');

	$table = table_objet_sql($type);
	if (!id_table_objet($table)) {
		spip_log("meme_rubrique: $type table inconnue");
		$type = 'article';
		$table = 'spip_articles';
	}
	$prim = 'id_' . $type;
	if (!$limit)
		$limit = defined('_MAX_ART_AFFICHES') ? _MAX_ART_AFFICHES : 10;

	$titre = ($type!='syndic'?'titre':'nom_site');
	$exec = array('article'=>'articles','breve'=>'breves_voir','syndic'=>'sites');

	$where = (($GLOBALS['visiteur_session']['statut'] == '0minirezo')
		  ? ''
		  :  "(statut = 'publie' OR statut = 'prop') AND ") 
	. "id_rubrique=$id_rubrique AND ($prim != $id)";

	$select = "$prim AS id, $titre AS titre, statut";

	$n = sql_countsel($table, $where);

	if (!$n) return '';

	if (!defined('_TRI_ARTICLES_RUBRIQUE')) define('_TRI_ARTICLES_RUBRIQUE', 'date DESC'); // surcharge possible dans mes_options.php
	$order = ($order == '') ? _TRI_ARTICLES_RUBRIQUE : "$order DESC";
	$voss = sql_select($select, $table, $where, '', "$order", $limit);

	$limit = $n - $limit;
	$retour = '';
	$puce_statut = charger_fonction('puce_statut', 'inc');
	$idom = 'rubrique_' . $type;

	while($row = sql_fetch($voss)) {
		$id = $row['id'];
		$num = afficher_numero_edit($id, $prim, $type);
		$statut = $row['statut'];
		
		// Exception pour les meme-rubrique de sites
		if ($type == "syndic") $type_statut = "site";
		else $type_statut = $type;
		
		$statut = $puce_statut($id, $statut, $id_rubrique, $type_statut);
		$href = "<a class='verdana1' href='"
		. generer_url_ecrire($exec[$type],"$prim=$id")
		. "'>"
		. sinon(typo($row['titre']), _T('info_sans_titre'))
		. "</a>";

		// Todo: refaire en css plus sains
		$retour .= "\n<div>"
				. "\n<div style='float:$spip_lang_right;width: 32%'>"
				. $num . "</div>"
				. "<div style='float:$spip_lang_left; padding-top:1px; width:18px;'>".$statut ."</div>"
				. "<div style='padding-$spip_lang_left:18px;'>".$href."</div>"
				. "<div style='clear:both; height: 3px;'></div>"
				. "</div>";
	}

	$icone =  '<b>' . _T('info_meme_rubrique')  . '</b>';
	$bouton = bouton_block_depliable(_T('info_meme_rubrique'),true,'memerub');

	$retour = 
		debut_cadre('meme-rubriques',"article-24.gif",'',$bouton)
		. debut_block_depliable(true,'memerub')
		. $retour;
	

	//	$retour .= (($limit <= 0) ? '' : "<tr><td colspan='3' style='text-align: center'>+ $limit</td></tr>");

	$retour .= fin_block()
		. fin_cadre('meme-rubriques');

	if ($ajax) return $retour;

	// id utilise dans puce_statut_article
	return "\n<div id='imgstatut$idom$id_rubrique'>$retour</div>";
}
?>
