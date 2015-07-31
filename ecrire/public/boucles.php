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
// <BOUCLE(HIERARCHIE)>
//
// http://doc.spip.org/@boucle_HIERARCHIE_dist
function boucle_HIERARCHIE_dist($id_boucle, &$boucles) {
	$boucle = &$boucles[$id_boucle];
	$id_table = $boucle->id_table . ".id_rubrique";

	// Si la boucle mere est une boucle RUBRIQUES il faut ignorer la feuille
	// sauf en presence du critere {tout} (vu par phraser_html)
	// ou {id_article} qui positionne aussi le {tout}

	$boucle->hierarchie = 'if (!($id_rubrique = intval('
	. calculer_argument_precedent($boucle->id_boucle, 'id_rubrique', $boucles)
	. ")))\n\t\treturn '';\n\t"
	. "include_spip('inc/rubriques');\n\t"
	. '$hierarchie = calcul_hierarchie_in($id_rubrique,'
	. (isset($boucle->modificateur['tout']) ? 'true':'false')
	. ");\n\t"
	. 'if (!$hierarchie) return "";'."\n\t";

	$boucle->where[]= array("'IN'", "'$id_table'", '"($hierarchie)"');

	$order = "FIELD($id_table, \$hierarchie)";
	if (!isset($boucle->default_order[0]) OR $boucle->default_order[0] != " DESC")
		$boucle->default_order[] = "\"$order\"";
	else
		$boucle->default_order[0] = "\"$order DESC\"";
	return calculer_boucle($id_boucle, $boucles); 
}


?>
