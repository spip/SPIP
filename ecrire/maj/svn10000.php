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
#	sql_alter('TABLE spip_forum DROP INDEX id_message id_message');
	sql_alter('TABLE spip_forum ADD INDEX id_parent (id_parent)');
	sql_alter('TABLE spip_forum ADD INDEX id_auteur (id_auteur)');
	sql_alter('TABLE spip_forum ADD INDEX id_thread (id_thread)');
}

$GLOBALS['maj'][11790] = array(array('maj_11790'));

$GLOBALS['maj'][11794] = array(); // ajout de spip_documents_forum



$GLOBALS['maj'][11961] = array(
array('sql_alter',"TABLE spip_groupes_mots CHANGE `tables` tables_liees text DEFAULT '' NOT NULL AFTER obligatoire"), // si tables a ete cree on le renomme
array('sql_alter',"TABLE spip_groupes_mots ADD tables_liees text DEFAULT '' NOT NULL AFTER obligatoire"), // sinon on l'ajoute
array('sql_update','spip_groupes_mots',array('tables_liees'=>"''"),"articles REGEXP '.*'"), // si le champ articles est encore la, on reinit la conversion
array('sql_update','spip_groupes_mots',array('tables_liees'=>"concat(tables_liees,'articles,')"),"articles='oui'"), // sinon ces 4 requetes ne feront rien
array('sql_update','spip_groupes_mots',array('tables_liees'=>"concat(tables_liees,'breves,')"),"breves='oui'"),
array('sql_update','spip_groupes_mots',array('tables_liees'=>"concat(tables_liees,'rubriques,')"),"rubriques='oui'"),
array('sql_update','spip_groupes_mots',array('tables_liees'=>"concat(tables_liees,'syndic,')"),"syndic='oui'"),
);



// Reunir en une seule table les liens de documents
//  spip_documents_articles et spip_documents_forum
function maj_12008 () {
	// Creer spip_documents_liens
	global $tables_auxiliaires;
	include_spip('base/auxiliaires');
	$v = $tables_auxiliaires[$k='spip_documents_liens'];
	sql_create($k, $v['field'], $v['key'], false, false);

	// Recopier les donnees
	foreach (array('article', 'breve', 'rubrique', 'auteur', 'forum') as $l) {
		if ($s = sql_select('*', 'spip_documents_'.$l.'s')
		OR $s = sql_select('*', 'spip_documents_'.$l)) {
			$tampon = array();
			while ($t = sql_fetch($s)) {
				// transformer id_xx=N en (id_objet=N, objet=xx)
				$t['id_objet'] = $t["id_$l"];
				$t['objet'] = $l;
				unset($t["id_$l"]);
				unset($t['maj']);
				$tampon[] = $t;
				if (count($tampon)>10000) {
					sql_insertq_multi('spip_documents_liens',$tampon);
					$tampon = array();
				}
			}
			if (count($tampon)) {
				sql_insertq_multi('spip_documents_liens', $tampon);
			}
		}
	}
}

$GLOBALS['maj'][12008] = array(
//array('sql_drop_table',"spip_documents_liens"), // tant pis pour ceux qui ont joue a 11974
array('sql_alter',"TABLE spip_documents_liens DROP PRIMARY KEY"),
array('sql_alter',"TABLE spip_documents_liens ADD id_objet bigint(21) DEFAULT '0' NOT NULL AFTER id_document"),
array('sql_alter',"TABLE spip_documents_liens ADD objet VARCHAR (25) DEFAULT '' NOT NULL AFTER id_objet"),
array('sql_update','spip_documents_liens',array('id_objet'=>"id_article",'objet'=>"'article'"),"id_article IS NOT NULL AND id_article>0"),
array('sql_update','spip_documents_liens',array('id_objet'=>"id_rubrique",'objet'=>"'rubrique'"),"id_rubrique IS NOT NULL AND id_rubrique>0"),
array('sql_update','spip_documents_liens',array('id_objet'=>"id_breve",'objet'=>"'breve'"),"id_breve IS NOT NULL AND id_breve>0"),
array('sql_update','spip_documents_liens',array('id_objet'=>"id_auteur",'objet'=>"'auteur'"),"id_auteur IS NOT NULL AND id_auteur>0"),
array('sql_update','spip_documents_liens',array('id_objet'=>"id_forum",'objet'=>"'forum'"),"id_forum IS NOT NULL AND id_forum>0"),
array('sql_alter',"TABLE spip_documents_liens ADD PRIMARY KEY  (id_document,id_objet,objet)"),
array('sql_alter',"TABLE spip_documents_liens DROP id_article"),
array('sql_alter',"TABLE spip_documents_liens DROP id_rubrique"),
array('sql_alter',"TABLE spip_documents_liens DROP id_breve"),
array('sql_alter',"TABLE spip_documents_liens DROP id_auteur"),
array('sql_alter',"TABLE spip_documents_liens DROP id_forum"),
array('maj_12008'),
);


// destruction des tables spip_documents_articles etc, cf. 12008
$GLOBALS['maj'][12009] = array(
array('sql_drop_table',"spip_documents_articles"),
array('sql_drop_table',"spip_documents_breves"),
array('sql_drop_table',"spip_documents_rubriques"),
array('sql_drop_table',"spip_documents_auteurs"), # plugin #FORMULAIRE_UPLOAD
array('sql_drop_table',"spip_documents_syndic") # plugin podcast_client
);

// destruction des champs articles breves rubriques et syndic, cf. 11961
$GLOBALS['maj'][12010] = array(
array('sql_alter',"TABLE spip_groupes_mots DROP articles"),
array('sql_alter',"TABLE spip_groupes_mots DROP breves"),
array('sql_alter',"TABLE spip_groupes_mots DROP rubriques"),
array('sql_alter',"TABLE spip_groupes_mots DROP syndic"),
);

function maj_13135 () {
	include_spip('inc/rubriques');
	calculer_prochain_postdate();

	// supprimer les eventuels vieux cache plugin qui n'utilisaient pas _chemin
	@spip_unlink(_CACHE_PLUGINS_OPT);
	@spip_unlink(_CACHE_PLUGINS_FCT);
	@spip_unlink(_CACHE_PLUGINS_VERIF);
}

$GLOBALS['maj'][13135] = array(array('maj_13135'));

// Type flac: http://flac.sourceforge.net
$GLOBALS['maj'][13333] = array(array('upgrade_types_documents'));

// http://archives.rezo.net/spip-zone.mbox/200903.mbox/%3Cbfc33ad70903141606q2e4c53f2k4fef6b45e611a04f@mail.gmail.com%3E

$GLOBALS['maj'][13833] = array(
array('sql_alter',"TABLE spip_documents_liens ADD INDEX objet(id_objet,objet)"))
;

// Fin upgrade commun branche 2.0

include_spip('inc/autoriser');
$GLOBALS['maj'][13904] = array(
array('sql_alter',"TABLE spip_auteurs ADD webmestre varchar(3)  DEFAULT 'non' NOT NULL"),
array('sql_update','spip_auteurs',array('webmestre'=>"'oui'"),sql_in("id_auteur",defined('_ID_WEBMESTRES')?explode(':',_ID_WEBMESTRES):(autoriser('configurer')?array($GLOBALS['visiteur_session']['id_auteur']):array(0)))) // le webmestre est celui qui fait l'upgrade si rien de defini
)
;

// sites plantes en mode "'su" au lieu de "sus"
$GLOBALS['maj'][13929] = array(
	array('sql_update',"spip_syndic",array('syndication'=>"'sus'"),"syndication LIKE '\\'%'")
);

// Types de fichiers m4a/m4b/m4p/m4u/m4v/dv
// Types de fichiers Open XML (cro$oft)
$GLOBALS['maj'][14558] = array(array('upgrade_types_documents'));

// refaire les upgrade dont les numeros sont inferieurs a ceux de la branche 2.0
// etre sur qu'ils sont bien unipotents(?)...
$GLOBALS['maj'][14559] = $GLOBALS['maj'][13904]+$GLOBALS['maj'][13929]+$GLOBALS['maj'][14558];

// Restauration correcte des types mime des fichiers Ogg
// http://trac.rezo.net/trac/spip/ticket/1941
// + Types de fichiers : f4a/f4b/f4p/f4v/mpc http://en.wikipedia.org/wiki/Flv#File_formats
$GLOBALS['maj'][15676] = array(array('upgrade_types_documents'));

// Type de fichiers : webm http://en.wikipedia.org/wiki/Flv#File_formats
$GLOBALS['maj'][15827] = array(array('upgrade_types_documents'));

// IP en 40 caracteres pour IP v6
$GLOBALS['maj'][15828] = array(array('sql_alter',"TABLE spip_forum CHANGE `ip` `ip` VARCHAR(40) DEFAULT '' NOT NULL"));

?>
