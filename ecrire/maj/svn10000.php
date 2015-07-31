<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

/*--------------------------------------------------------------------- */
/*	Gestion des MAJ par tableau indexe par le numero SVN du chgt	*/
/*--------------------------------------------------------------------- */

// Type cls et sty pour LaTeX
$GLOBALS['maj'][10990] = array(array('upgrade_types_documents'));

// Type 3gp: http://www.faqs.org/rfcs/rfc3839.html
// Aller plus vite pour les vieilles versions en redeclarant une seule les doc
unset($GLOBALS['maj'][10990]);
$GLOBALS['maj'][11042] = array(array('upgrade_types_documents'));


// Un bug permettait au champ 'upload' d'etre vide, provoquant
// l'impossibilite de telecharger une image
// http://trac.rezo.net/trac/spip/ticket/1238
$GLOBALS['maj'][11171] = array(
	array('spip_query', "UPDATE spip_types_documents SET upload='oui' WHERE upload IS NULL OR upload!='non'")
);

function maj_11268() {
	global $tables_auxiliaires;
	include_spip('base/auxiliaires');
	$v = $tables_auxiliaires[$k='spip_resultats'];
	sql_create($k, $v['field'], $v['key'], false, false);
}
$GLOBALS['maj'][11268] = array(array('maj_11268'));


function maj_11276 () {
	include_spip('maj/v019');
	maj_1_938();
}
$GLOBALS['maj'][11276] = array(array('maj_11276'));

// reparer les referers d'article, qui sont vides depuis [10572]
function maj_11388 () {
	$s = sql_select('referer_md5', 'spip_referers_articles', "referer='' OR referer IS NULL");
	while ($t = sql_fetch($s)) {
		$k = sql_fetsel('referer', 'spip_referers', 'referer_md5='.sql_quote($t['referer_md5']));
		if ($k['referer']) {
			spip_query('UPDATE spip_referers_articles
			SET referer='.sql_quote($k['referer']).'
			WHERE referer_md5='.sql_quote($t['referer_md5'])
			." AND (referer='' OR referer IS NULL)"
			);
		}
	}
}
$GLOBALS['maj'][11388] = array(array('maj_11388'));

// reparer spip_mots.type = titre du groupe
function maj_11431 () {
	spip_query("UPDATE spip_mots AS a LEFT JOIN spip_groupes_mots AS b ON (a.id_groupe = b.id_groupe) SET a.type=b.titre");
}
$GLOBALS['maj'][11431] = array(array('maj_11431'));

?>
