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
	// mysql only 
	// spip_query("UPDATE spip_mots AS a LEFT JOIN spip_groupes_mots AS b ON (a.id_groupe = b.id_groupe) SET a.type=b.titre");
	
	// selection des mots cles dont le type est different du groupe
	$res = sql_select(
		array("a.id_mot AS id_mot", "b.titre AS type"), 
		array("spip_mots AS a LEFT JOIN spip_groupes_mots AS b ON (a.id_groupe = b.id_groupe)"),
		array("a.type != b.titre"));
	// mise a jour de ces mots la
	if ($res){
		while ($r = sql_fetch($res)){
			sql_updateq('spip_mots', array('type'=>$r['type']), 'id_mot='.sql_quote($r['id_mot']));
		}
	}
}
$GLOBALS['maj'][11431] = array(array('maj_11431'));

// reparer spip_types_documents.id_type 
// qui est parfois encore present
function maj_11778 () {
	// si presence id_type
	$s = sql_showtable('spip_types_documents');
	if (isset($s['field']['id_type'])) {
		sql_alter('TABLE spip_types_documents CHANGE id_type id_type BIGINT(21) NOT NULL');
		sql_alter('TABLE spip_types_documents DROP id_type');
		sql_alter('TABLE spip_types_documents ADD PRIMARY KEY (extension)');
	}
}
$GLOBALS['maj'][11778] = array(array('maj_11778'));

// Optimisation des forums
function maj_11790 () {
	sql_alter('TABLE spip_forum DROP INDEX id_message id_message');
	sql_alter('TABLE spip_forum ADD INDEX id_parent (id_parent)');
	sql_alter('TABLE spip_forum ADD INDEX id_auteur (id_auteur)');
	sql_alter('TABLE spip_forum ADD INDEX id_thread (id_thread)');
}

$GLOBALS['maj'][11790] = array(array('maj_11790'));

$GLOBALS['maj'][11794] = array(); // ajout de spip_documents_forum

// Reunir en une seule table les liens de documents
//  spip_documents_articles et spip_documents_forum
function maj_11911 () {
	foreach (array('article', 'breve', 'rubrique', 'auteur', 'forum') as $l) {
		if ($s = sql_select('*', 'spip_documents_'.$l.'s')
		OR $s = sql_select('*', 'spip_documents_'.$l)) {
			$tampon = array();
			while ($t = sql_fetch($s)) {
				$keys = '('.join(',',array_keys($t)).')';
				$tampon[] = '('.join(',', array_map('sql_quote', $t)).')';
				if (count($tampon)>100) {
					sql_insert('spip_documents_liens', $keys, join(',', $tampon));
					$tampon = array();
				}
			}
			if (count($tampon)) {
				sql_insert('spip_documents_liens', $keys, join(',', $tampon));
			}
		}
	}
}
$GLOBALS['maj'][11911] = array(array('maj_11911'));

// penser a ajouter ici destruction des tables spip_documents_articles etc
// une fois qu'on aura valide la procedure d'upgrade ci-dessus


$GLOBALS['maj'][11961] = array(
array('sql_alter',"TABLE spip_groupes_mots CHANGE `tables` tables_liees text DEFAULT '' NOT NULL AFTER obligatoire"), // si tables a ete cree on le renomme
array('sql_alter',"TABLE spip_groupes_mots ADD tables_liees text DEFAULT '' NOT NULL AFTER obligatoire"), // sinon on l'ajoute
array('sql_update','spip_groupes_mots',array('tables_liees'=>"''"),"articles REGEXP '.*'"), // si le champ articles est encore la, on reinit la conversion
array('sql_update','spip_groupes_mots',array('tables_liees'=>"concat(tables_liees,'articles,')"),"articles='oui'"), // sinon ces 4 requetes ne feront rien
array('sql_update','spip_groupes_mots',array('tables_liees'=>"concat(tables_liees,'breves,')"),"breves='oui'"),
array('sql_update','spip_groupes_mots',array('tables_liees'=>"concat(tables_liees,'rubriques,')"),"rubriques='oui'"),
array('sql_update','spip_groupes_mots',array('tables_liees'=>"concat(tables_liees,'syndic,')"),"syndic='oui'"),
);

// penser a ajouter ici destruction des champs articles breves rubriques et syndic
// une fois qu'on aura valide la procedure d'upgrade ci-dessus

?>