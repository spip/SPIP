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
}

$GLOBALS['maj'][13135] = array(array('maj_13135'));

// Type flac: http://flac.sourceforge.net
$GLOBALS['maj'][13333] = array(array('upgrade_types_documents'));

// http://archives.rezo.net/spip-zone.mbox/200903.mbox/%3Cbfc33ad70903141606q2e4c53f2k4fef6b45e611a04f@mail.gmail.com%3E

$GLOBALS['maj'][13833] = array(
array('sql_alter',"TABLE spip_documents_liens ADD INDEX objet(id_objet,objet)"))
;

// Fin upgrade commun branche 2.0

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

// La version 14588 etait une mauvaise piste:
// Retour en arriere pour ceux qui l'ont subi, ne rien faire sinon
if (@$GLOBALS['meta']['version_installee'] >= 14588) {

	// "mode" est un mot-cle d'Oracle
	$GLOBALS['maj'][14588] = array(
	array('sql_alter',"TABLE spip_documents  DROP INDEX mode"),
	array('sql_alter',"TABLE spip_documents  CHANGE mode genre ENUM('vignette', 'image', 'document') DEFAULT 'document' NOT NULL"),
	array('sql_alter',"TABLE spip_documents  ADD INDEX genre(genre)")
			       );
	// solution moins intrusive au pb de mot-cle d'Oracle, retour avant 14588
	$GLOBALS['maj'][14598] = array(
	array('sql_alter',"TABLE spip_documents  DROP INDEX genre"),
	array('sql_alter',"TABLE spip_documents  CHANGE genre mode ENUM('vignette', 'image', 'document') DEFAULT 'document' NOT NULL"),
	array('sql_alter',"TABLE spip_documents  ADD INDEX mode(mode)")
			       );
}

// Restauration correcte des types mime des fichiers Ogg
// http://trac.rezo.net/trac/spip/ticket/1941
// + Types de fichiers : f4a/f4b/f4p/f4v/mpc http://en.wikipedia.org/wiki/Flv#File_formats
// + Report du commit oublié : http://trac.rezo.net/trac/spip/changeset/14272
$GLOBALS['maj'][15676] = array(array('upgrade_types_documents'));

// Type de fichiers : webm http://en.wikipedia.org/wiki/Flv#File_formats
$GLOBALS['maj'][15827] = array(array('upgrade_types_documents'));

$GLOBALS['maj'][16428] = array(
	array('maj_liens','auteur'), // creer la table liens
	array('maj_liens','auteur','article'),
	array('sql_drop_table',"spip_auteurs_articles"),
	array('maj_liens','auteur','rubrique'),
	array('sql_drop_table',"spip_auteurs_rubriques"),
	array('maj_liens','auteur','message'),
	array('sql_drop_table',"spip_auteurs_messages"),
);

// Reunir en une seule table les liens de documents
//  spip_documents_articles et spip_documents_forum
function maj_liens($pivot,$l='') {

	@define('_LOG_FILTRE_GRAVITE',8);

	$exceptions_pluriel = array('forum'=>'forum','syndic'=>'syndic');

	$pivot = preg_replace(',[^\w],','',$pivot); // securite
	$pivots = (isset($exceptions_pluriel[$pivot])?$exceptions_pluriel[$pivot]:$pivot."s");
	$liens = "spip_".$pivots."_liens";
	$id_pivot = "id_".$pivot;
	// Creer spip_auteurs_liens
	global $tables_auxiliaires;
	if (!$l) {
		include_spip('base/auxiliaires');
		include_spip('base/create');
		creer_ou_upgrader_table($liens, $tables_auxiliaires[$liens], false);
	}
	else {
		// Preparer
		$l = preg_replace(',[^\w],','',$l); // securite
		$primary = "id_$l";
		$objet = ($l=='syndic'?'site':$l);
		$ls = (isset($exceptions_pluriel[$l])?$exceptions_pluriel[$l]:$l."s");
		$ancienne_table = 'spip_'.$pivots.'_'.$ls;
		$pool = 400;

		$trouver_table = charger_fonction('trouver_table','base');
		if (!$desc = $trouver_table($ancienne_table))
			return;

		// securite pour ne pas perdre de donnees
		if (!$trouver_table($liens))
			return;

		$champs = $desc['field'];
		if (isset($champs['maj'])) unset($champs['maj']);
		if (isset($champs[$primary])) unset($champs[$primary]);

		$champs = array_keys($champs);
		// ne garder que les champs qui existent sur la table destination
		if ($desc_cible = $trouver_table($liens)){
			$champs = array_intersect($champs,array_keys($desc_cible['field']));
		}

		$champs[] = "$primary as id_objet";
		$champs[] = "'$objet' as objet";
		$champs = implode(', ',$champs);

		// Recopier les donnees
		$sub_pool = 100;
		while ($ids = array_map('reset',sql_allfetsel("$primary",$ancienne_table,'','','',"0,$sub_pool"))){
			$insert = array();
			foreach($ids as $id){
				$n = sql_countsel($liens,"objet='$objet' AND id_objet=".intval($id));
				while ($t = sql_allfetsel($champs, $ancienne_table,"$primary=".intval($id),'',$id_pivot,"$n,$pool")) {
					$n+=count($t);
					// empiler en s'assurant a minima de l'unicite
					while ($r = array_shift($t))
						$insert[$r[$id_pivot].':'.$r['id_objet']] = $r;
					if (count($insert)>=$sub_pool){
						maj_liens_insertq_multi_check($liens,$insert,$tables_auxiliaires[$liens]);
						$insert = array();
					}
					// si timeout, sortir, la relance nous ramenera dans cette fonction
					// et on verifiera/repartira de la
					if (time() >= _TIME_OUT) return;
				}
				if (time() >= _TIME_OUT) return;
			}
			if (count($insert))
				maj_liens_insertq_multi_check($liens,$insert,$tables_auxiliaires[$liens]);
			sql_delete ($ancienne_table, sql_in($primary,$ids));
		}
	}
}
function maj_liens_insertq_multi_check($table,$couples,$desc=array()){
	$n_before = sql_countsel($table);
	sql_insertq_multi($table,$couples,$desc);
	$n_after = sql_countsel($table);
	if (($n_after-$n_before)==count($couples))
		return;
	// si ecart, on recommence l'insertion ligne par ligne...
	// moins rapide mais secure : seul le couple en doublon echouera, et non toute la serie
	foreach($couples as $c)
		sql_insertq($table,$c,$desc);
}

$GLOBALS['maj'][17311] = array(
	array('ecrire_meta',"multi_objets",implode(',',
				 array_diff(
					 array($GLOBALS['meta']['multi_rubriques']=='oui'?'spip_rubriques':'',$GLOBALS['meta']['multi_articles']=='oui'?'spip_articles':''),
					 array('')
				 ))),
	array('ecrire_meta',"gerer_trad_objets",implode(',',
				 array_diff(
					 array($GLOBALS['meta']['gerer_trad']=='oui'?'spip_articles':''),
					 array('')
				 ))),
);
$GLOBALS['maj'][17555] = array(
	array('sql_alter',"TABLE spip_resultats ADD table_objet varchar(30) DEFAULT '' NOT NULL"),
	array('sql_alter',"TABLE spip_resultats ADD serveur char(16) DEFAULT '' NOT NULL"),
);

$GLOBALS['maj'][17563] = array(
	array('sql_alter',"TABLE spip_articles ADD virtuel VARCHAR(255) DEFAULT '' NOT NULL"),
	array('sql_update','spip_articles',array('virtuel'=>'SUBSTRING(chapo,2)','chapo'=>"''"),"chapo LIKE '=_%'"),
);

$GLOBALS['maj'][17577] = array(
	array('maj_tables',array('spip_jobs','spip_jobs_liens')),
);

$GLOBALS['maj'][17743] = array(
	array('sql_update','spip_auteurs',array('prefs'=>'bio','bio'=>"''"),"statut='nouveau' AND bio<>''"),
);

$GLOBALS['maj'][18219] = array(
	array('sql_alter',"TABLE spip_rubriques DROP id_import"),
	array('sql_alter',"TABLE spip_rubriques DROP export"),
);

$GLOBALS['maj'][18310] = array(
	array('sql_alter',"TABLE spip_auteurs_liens CHANGE vu vu VARCHAR(6) DEFAULT 'non' NOT NULL"),
);

$GLOBALS['maj'][18597] = array(
	array('sql_alter',"TABLE spip_rubriques ADD profondeur smallint(5) DEFAULT '0' NOT NULL"),
	array('maj_propager_les_secteurs'),
);

$GLOBALS['maj'][18955] = array(
	array('sql_alter',"TABLE spip_auteurs_liens ADD INDEX id_objet (id_objet)"),
	array('sql_alter',"TABLE spip_auteurs_liens ADD INDEX objet (objet)"),
);


function maj_propager_les_secteurs(){
	include_spip('inc/rubriques');
	propager_les_secteurs();
}


function maj_collation_sqlite(){


	include_spip('base/dump');
	$tables = base_lister_toutes_tables();

	// rien a faire si base non sqlite
	if (strncmp($GLOBALS['connexions'][0]['type'],'sqlite',6)!==0)
		return;

	$trouver_table = charger_fonction('trouver_table','base');
	// forcer le vidage de cache
	$trouver_table('');

	// cas particulier spip_auteurs : retablir le collate binary sur le login
	$desc = $trouver_table("spip_auteurs");
	spip_log("spip_auteurs : ".var_export($desc['field'],true),"maj."._LOG_INFO_IMPORTANTE);
	if (stripos($desc['field']['login'],"BINARY")===false){
		spip_log("Retablir champ login BINARY sur table spip_auteurs","maj");
		sql_alter("table spip_auteurs change login login VARCHAR(255) BINARY");
		$trouver_table('');
		$new_desc = $trouver_table("spip_auteurs");
		spip_log("Apres conversion spip_auteurs : ".var_export($new_desc['field'],true),"maj."._LOG_INFO_IMPORTANTE);
	}

	foreach ($tables as $table){
		if (time() >= _TIME_OUT) return;
		if ($desc = $trouver_table($table)){
			$desc_collate = _sqlite_remplacements_definitions_table($desc['field']);
			if ($d=array_diff($desc['field'],$desc_collate)){
				spip_log("Table $table COLLATE incorrects","maj");

				// cas particulier spip_urls :
				// supprimer les doublons avant conversion sinon echec (on garde les urls les plus recentes)
				if ($table=='spip_urls'){
					// par date DESC pour conserver les urls les plus recentes
					$data = sql_allfetsel("*","spip_urls",'','','date DESC');
					$urls = array();
					foreach ($data as $d){
						$key = $d['id_parent']."::".strtolower($d['url']);
						if (!isset($urls[$key]))
							$urls[$key] = true;
						else {
							spip_log("Suppression doublon dans spip_urls avant conversion : ".serialize($d),"maj."._LOG_INFO_IMPORTANTE);
							sql_delete("spip_urls","id_parent=".sql_quote($d['id_parent'])." AND url=".sql_quote($d['url']));
						}
					}
				}
				foreach ($desc['field'] as $field=>$type){
					if ($desc['field'][$field]!==$desc_collate[$field]){
						spip_log("Conversion COLLATE table $table","maj."._LOG_INFO_IMPORTANTE);
						sql_alter("table $table change $field $field ".$desc_collate[$field]);
						$trouver_table('');
						$new_desc = $trouver_table($table);
						spip_log("Apres conversion $table : ".var_export($new_desc['field'],true),"maj."._LOG_INFO_IMPORTANTE);
						continue 2; // inutile de continuer pour cette table : un seul alter remet tout a jour en sqlite
					}
				}
			}
		}
	}

	// forcer le vidage de cache
	$trouver_table('');

}


$GLOBALS['maj'][19236] = array(
	array('sql_updateq','spip_meta',array('impt'=>'oui'),"nom='version_installee'"), // version base principale
	array('sql_updateq','spip_meta',array('impt'=>'oui'),"nom LIKE '%_base_version'"),  // version base plugins
	array('maj_collation_sqlite'),
);

$GLOBALS['maj'][19268] = array(
	array('supprimer_toutes_sessions'),
);


function supprimer_toutes_sessions() {
	spip_log("supprimer sessions auteur");
	$dir = opendir(_DIR_SESSIONS);
	while(($f = readdir($dir)) !== false) {
		spip_unlink(_DIR_SESSIONS . $f);
		if (time() >= _TIME_OUT) return;
	}
}

?>
