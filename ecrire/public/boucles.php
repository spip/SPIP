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


//
// Ce fichier definit les boucles standard de SPIP
//

if (!defined('_ECRIRE_INC_VERSION')) return;

//
// Boucle standard, sans condition rajoutee
//
// http://doc.spip.org/@boucle_DEFAUT_dist
function boucle_DEFAUT_dist($id_boucle, &$boucles) {
	return calculer_boucle($id_boucle, $boucles); 
}


//
// <BOUCLE(BOUCLE)> boucle dite recursive
//
// http://doc.spip.org/@boucle_BOUCLE_dist
function boucle_BOUCLE_dist($id_boucle, &$boucles) {

	return calculer_boucle($id_boucle, $boucles); 
}

//
// <BOUCLE(ARTICLES)>
//
// http://doc.spip.org/@boucle_ARTICLES_dist
function boucle_ARTICLES_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$mstatut = $id_table .'.statut';

	// Restreindre aux elements publies
	if (!isset($boucle->modificateur['criteres']['statut'])) {
		if (!$GLOBALS['var_preview']) {
			if ($GLOBALS['meta']["post_dates"] == 'non')
				array_unshift($boucle->where,array("'<'", "'$id_table" . ".date'", "sql_quote(quete_date_postdates())"));
			array_unshift($boucle->where,array("'='", "'$mstatut'", "'\\'publie\\''"));
		} else
			array_unshift($boucle->where,array("'IN'", "'$mstatut'", "'(\\'publie\\',\\'prop\\')'"));
	}
	return calculer_boucle($id_boucle, $boucles); 
}

//
// <BOUCLE(AUTEURS)>
//
// http://doc.spip.org/@boucle_AUTEURS_dist
function boucle_AUTEURS_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$mstatut = $id_table .'.statut';

	// Restreindre aux elements publies
	if (!isset($boucle->modificateur['criteres']['statut'])) {
		// Si pas de lien avec un article, selectionner
		// uniquement les auteurs d'un article publie
		if (!$GLOBALS['var_preview'])
		if (!isset($boucle->modificateur['lien']) AND !isset($boucle->modificateur['tout'])) {
			fabrique_jointures($boucle, array(
				array($id_table, array('spip_auteurs_articles'), 'id_auteur'),
							  array('', array('spip_articles'), 'id_article')), true, $boucle->show, $id_table);
			$t = array_search('spip_articles', $boucle->from);
			array_unshift($boucle->where,
				array("'='", "'$t.statut'", "'\\'publie\\''"));
			if ($GLOBALS['meta']['post_dates'] == 'non')
				array_unshift($boucle->where,
					array("'<='", "'$t.date'", "sql_quote(quete_date_postdates())"));
		}
		// pas d'auteurs poubellises
		array_unshift($boucle->where,array("'!='", "'$mstatut'", "'\\'5poubelle\\''"));
	}

	return calculer_boucle($id_boucle, $boucles); 
}

//
// <BOUCLE(BREVES)>
//
// http://doc.spip.org/@boucle_BREVES_dist
function boucle_BREVES_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$mstatut = $id_table .'.statut';

	// Restreindre aux elements publies
	if (!isset($boucle->modificateur['criteres']['statut'])) {
		if (!$GLOBALS['var_preview'])
			array_unshift($boucle->where,array("'='", "'$mstatut'", "'\\'publie\\''"));
		else
			array_unshift($boucle->where,array("'IN'", "'$mstatut'", "'(\\'publie\\',\\'prop\\')'"));
	}

	return calculer_boucle($id_boucle, $boucles); 
}


//
// <BOUCLE(FORUMS)>
//
// http://doc.spip.org/@boucle_FORUMS_dist
function boucle_FORUMS_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$mstatut = $id_table .'.statut';
	// Par defaut, selectionner uniquement les forums sans mere
	// Les criteres {tout} et {plat} inversent ce choix
	if (!isset($boucle->modificateur['tout']) AND !isset($boucle->modificateur['plat'])) {
		array_unshift($boucle->where,array("'='", "'$id_table." ."id_parent'", 0));
	}
	// Restreindre aux elements publies
	if (!$boucle->modificateur['criteres']['statut']) {
		if ($GLOBALS['var_preview'])
			array_unshift($boucle->where,array("'IN'", "'$mstatut'", "'(\\'publie\\',\\'prive\\')'"));		
		else
			array_unshift($boucle->where,array("'='", "'$mstatut'", "'\\'publie\\''"));
	}

	return calculer_boucle($id_boucle, $boucles); 
}


//
// <BOUCLE(SIGNATURES)>
//
// http://doc.spip.org/@boucle_SIGNATURES_dist
function boucle_SIGNATURES_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$mstatut = $id_table .'.statut';

	// Restreindre aux elements publies
	if (!isset($boucle->modificateur['criteres']['statut'])
	AND !isset($boucle->modificateur['tout'])) {

		array_unshift($boucle->where,array("'='", "'$mstatut'", "'\\'publie\\''"));
	}
	return calculer_boucle($id_boucle, $boucles); 
}


//
// <BOUCLE(DOCUMENTS)>
//
// http://doc.spip.org/@boucle_DOCUMENTS_dist
function boucle_DOCUMENTS_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;

	// on ne veut pas des fichiers de taille nulle,
	// sauf s'ils sont distants (taille inconnue)
	array_unshift($boucle->where,array("'($id_table.taille > 0 OR $id_table.distant=\\'oui\\')'"));

	// Supprimer les vignettes
	if (!isset($boucle->modificateur['criteres']['mode'])
	AND !isset($boucle->modificateur['criteres']['tout'])) {
		array_unshift($boucle->where,array("'!='", "'$id_table.mode'", "'\\'vignette\\''"));
	}

	// Pour une boucle generique (DOCUMENTS) sans critere de lien, verifier
	// qu notre document est lie a un element publie
	// (le critere {tout} permet de les afficher tous quand meme)
	// S'il y a un critere de lien {id_article} par exemple, on zappe
	// ces complications (et tant pis si la boucle n'a pas prevu de
	// verification du statut de l'article)
	if ((!isset($boucle->modificateur['tout']) OR !$boucle->modificateur['tout'])
	AND (!isset($boucle->modificateur['criteres']['id_objet']) OR !$boucle->modificateur['criteres']['id_objet'])
	) {
		# Espace avant LEFT JOIN indispensable pour insertion de AS
		# a refaire plus proprement

		## la boucle par defaut ignore les documents de forum
		$boucle->from[$id_table] = "spip_documents LEFT JOIN spip_documents_liens AS l
			ON $id_table.id_document=l.id_document
			LEFT JOIN spip_articles AS aa
				ON (l.id_objet=aa.id_article AND l.objet=\'article\')
			LEFT JOIN spip_breves AS bb
				ON (l.id_objet=bb.id_breve AND l.objet=\'breve\')
			LEFT JOIN spip_rubriques AS rr
				ON (l.id_objet=rr.id_rubrique AND l.objet=\'rubrique\')
			LEFT JOIN spip_forum AS ff
				ON (l.id_objet=ff.id_forum AND l.objet=\'forum\')
		";
		$boucle->group[] = "$id_table.id_document";

		if ($GLOBALS['var_preview']) {
			array_unshift($boucle->where,"'(aa.statut IN (\'publie\',\'prop\') OR bb.statut  IN (\'publie\',\'prop\') OR rr.statut IN (\'publie\',\'prive\') OR ff.statut IN (\'publie\',\'prop\'))'");
		} else {
			$postdates = ($GLOBALS['meta']['post_dates'] == 'non')
				? ' AND aa.date<=\'.sql_quote(quete_date_postdates()).\''
				: '';
			array_unshift($boucle->where,"'((aa.statut = \'publie\'$postdates) OR bb.statut = \'publie\' OR rr.statut = \'publie\' OR ff.statut=\'publie\')'");
		}
	}


	return calculer_boucle($id_boucle, $boucles);
}

//
// <BOUCLE(RUBRIQUES)>
//
// http://doc.spip.org/@boucle_RUBRIQUES_dist
function boucle_RUBRIQUES_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$mstatut = $id_table .'.statut';

	// Restreindre aux elements publies
	if (!isset($boucle->modificateur['criteres']['statut'])) {
		if (!$GLOBALS['var_preview'])
			if (!isset($boucle->modificateur['tout']))
				array_unshift($boucle->where,array("'='", "'$mstatut'", "'\\'publie\\''"));
	}

	return calculer_boucle($id_boucle, $boucles); 
}


//
// <BOUCLE(HIERARCHIE)>
//
// http://doc.spip.org/@boucle_HIERARCHIE_dist
function boucle_HIERARCHIE_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table . ".id_rubrique";

// Si la boucle mere est une boucle RUBRIQUES il faut ignorer la feuille
// sauf en presence du critere {tout} (vu par phraser_html)

	$boucle->hierarchie = 'if (!($id_rubrique = intval('
	. calculer_argument_precedent($boucle->id_boucle, 'id_rubrique', $boucles)
	. ")))\n\t\treturn '';\n\t"
	. '$hierarchie = '
	. (isset($boucle->modificateur['tout']) ? '",$id_rubrique"' : "''")
	. ";\n\t"
	. 'while ($id_rubrique = sql_getfetsel("id_parent","spip_rubriques","id_rubrique=" . $id_rubrique,"","","", "", $connect)) { 
		$hierarchie = ",$id_rubrique$hierarchie";
	}
	if (!$hierarchie) return "";
	$hierarchie = substr($hierarchie,1);';

	$boucle->where[]= array("'IN'", "'$id_table'", '"($hierarchie)"');

        $order = "FIELD($id_table, \$hierarchie)";
	if (!isset($boucle->default_order[0]) OR $boucle->default_order[0] != " DESC")
		$boucle->default_order[] = "\"$order\"";
	else
		$boucle->default_order[0] = "\"$order DESC\"";
	return calculer_boucle($id_boucle, $boucles); 
}


//
// <BOUCLE(SYNDICATION)>
//
// http://doc.spip.org/@boucle_SYNDICATION_dist
function boucle_SYNDICATION_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$mstatut = $id_table .'.statut';

	// Restreindre aux elements publies

	if (!isset($boucle->modificateur['criteres']['statut'])) {
		if (!$GLOBALS['var_preview']) {
			array_unshift($boucle->where,array("'='", "'$mstatut'", "'\\'publie\\''"));
		} else
			array_unshift($boucle->where,array("'IN'", "'$mstatut'", "'(\\'publie\\',\\'prop\\')'"));
	}
	return calculer_boucle($id_boucle, $boucles); 
}

//
// <BOUCLE(SYNDIC_ARTICLES)>
//
// http://doc.spip.org/@boucle_SYNDIC_ARTICLES_dist
function boucle_SYNDIC_ARTICLES_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table;
	$mstatut = $id_table .'.statut';

	// Restreindre aux elements publies, sauf critere contraire
	if (isset($boucle->modificateur['criteres']['statut']) AND $boucle->modificateur['criteres']['statut']) {}
	else if ($GLOBALS['var_preview'])
		array_unshift($boucle->where,array("'IN'", "'$mstatut'", "'(\\'publie\\',\\'prop\\')'"));
	else {
		$jointure = array_search("spip_syndic", $boucle->from);
		if (!$jointure) {
			fabrique_jointures($boucle, array(array($id_table, array('spip_syndic'), 'id_syndic')), true, $boucle->show, $id_table);
			$jointure = array_search('spip_syndic', $boucle->from);
		}
		array_unshift($boucle->where,array("'='", "'$mstatut'", "'\\'publie\\''"));
		$boucle->where[]= array("'='", "'$jointure" . ".statut'", "'\\'publie\\''");

	}
	return calculer_boucle($id_boucle, $boucles);
}

?>
