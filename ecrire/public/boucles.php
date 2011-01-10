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
				ON (l.id_objet=rr.id_rubrique AND l.objet=\'rubrique\')"
			// test conditionne par la presence du plugin forum, en attendant le champ statut sur la table documents
			. (test_plugin_actif('forum')?" LEFT JOIN spip_forum AS ff	ON (l.id_objet=ff.id_forum AND l.objet=\'forum\')":"");

		$boucle->group[] = "$id_table.id_document";

		if (_VAR_PREVIEW) {
			array_unshift($boucle->where,"'(aa.statut IN (\'publie\',\'prop\') OR bb.statut  IN (\'publie\',\'prop\') OR rr.statut IN (\'publie\',\'prive\')"
			.(test_plugin_actif('forum')? " OR ff.statut IN (\'publie\',\'prop\')":"")
			.")'");
		} else {
			$postdates = ($GLOBALS['meta']['post_dates'] == 'non')
				? ' AND \'.quete_condition_postdates(\'aa.date\').\''
				: '';
			array_unshift($boucle->where,"'((aa.statut = \'publie\'$postdates) OR bb.statut = \'publie\' OR rr.statut = \'publie\'"
			.(test_plugin_actif('forum')? " OR ff.statut=\'publie\'":"")
			.")'");
		}
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


?>
