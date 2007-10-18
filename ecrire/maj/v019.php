<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

function maj_v019_dist($version_installee, $version_cible)
{
	if (1.926 >= $version_installee) {
		include_spip('maj/v019_pre193');
		v019_pre193($version_installee, $version_cible);
	}
	maj_while($version_installee, $version_cible);
}

/*--------------------------------------------------------------------- */
/*			 Nouvelle gestion des MAJ			*/
/* ca coincide avec l'état de la 1.9.2, mais c'est un peu retroactif	*/
/*--------------------------------------------------------------------- */

	// FLV est incrustable, la MAJ precedente l'avait oublie
$GLOBALS['maj'][1][931] = array(
	array('spip_query', "UPDATE spip_types_documents SET `inclus`='embed' WHERE `extension`='flv'")
	);

	// Ajout de spip_forum.date_thread, et on essaie de le remplir
	// a coup de table temporaire (est-ce autorise partout... sinon
	// tant pis, ca ne marchera que pour les forums recemment modifies)
$GLOBALS['maj'][1][932] = array(
	array('sql_alter', "TABLE spip_forum ADD `date_thread` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL"),
	array('sql_alter', "TABLE spip_forum ADD INDEX `date_thread` (`date_thread`)"),

	array('spip_query', "DROP TABLE IF EXISTS spip_tmp"),
	array('spip_query', "CREATE TEMPORARY TABLE spip_tmp SELECT `id_thread`,MAX(`date_heure`) AS dt FROM spip_forum GROUP BY `id_thread`"),
	array('sql_alter', "TABLE spip_tmp ADD INDEX `p` (`id_thread`)"),
	array('spip_query', "UPDATE spip_forum AS F JOIN spip_tmp AS T ON F.id_thread=T.id_thread SET F.date_thread=T.dt"),
	array('spip_query', "DROP TABLE spip_tmp"),
	);


// Retrait de _DIR_IMG dans le champ fichier de la table des doc
function maj_1_934 () {
	  $dir_img = substr(_DIR_IMG,strlen(_DIR_RACINE));
	  $n = strlen($dir_img) + 1;
	  spip_query("UPDATE spip_documents SET `fichier`=substring(fichier,$n) WHERE `fichier` LIKE " . _q($dir_img . '%'));
}

$GLOBALS['maj'][1][934] = array(array('maj_1_934'));

function maj_1_935 () {
	include_spip('inc/texte');
	foreach(array('article'=>'id_article','rubrique'=>'id_rubrique','breve'=>'id_breve') as $type => $id_table_objet){
		$table_objet = "$type"."s";
		$chapo = $type=='article' ? ",a.chapo":"";
		$res = spip_query("SELECT a.$id_table_objet,a.texte $chapo FROM spip_documents_$table_objet AS d JOIN spip_$table_objet AS a ON a.$id_table_objet=d.$id_table_objet GROUP BY $id_table_objet");
		while ($row = sql_fetch($res)){
			$GLOBALS['doublons_documents_inclus'] = array();
			traiter_modeles(($chapo?$row['chapo']:"").$row['texte'],true); // detecter les doublons
			if (count($GLOBALS['doublons_documents_inclus'])){
					$id = $row[$id_table_objet];
					$liste = "(".implode(",$id,'oui'),(",$GLOBALS['doublons_documents_inclus']).",$id,'oui')";
					spip_query("REPLACE INTO spip_documents_$table_objet (`id_document`,`$id_table_objet`,`vu`) VALUES $liste");
				}
			}
	}
}

$GLOBALS['maj'][1][935] = array(
	array('sql_alter', "TABLE spip_documents_articles ADD `vu` ENUM('non', 'oui') DEFAULT 'non' NOT NULL"),
	array('sql_alter', "TABLE spip_documents_rubriques ADD `vu` ENUM('non', 'oui') DEFAULT 'non' NOT NULL"),
	array('sql_alter', "TABLE spip_documents_breves ADD `vu` ENUM('non', 'oui') DEFAULT 'non' NOT NULL"),
	array('maj_1_935')
	);

$GLOBALS['maj'][1][937] = array(
		// convertir les champs blob des tables spip en champs texte
	array('convertir_un_champ_blob_en_text',"spip_articles","texte","LONGTEXT"),
	array('convertir_un_champ_blob_en_text',"spip_articles","extra","LONGTEXT"),
	array('convertir_un_champ_blob_en_text',"spip_auteurs","extra","LONGTEXT"),
	array('convertir_un_champ_blob_en_text',"spip_breves","texte","LONGTEXT"),
	array('convertir_un_champ_blob_en_text',"spip_breves","extra","LONGTEXT"),
	array('convertir_un_champ_blob_en_text',"spip_messages","texte","LONGTEXT"),
	array('convertir_un_champ_blob_en_text',"spip_mots","texte","LONGTEXT"),
	array('convertir_un_champ_blob_en_text',"spip_mots","extra","LONGTEXT"),
	array('convertir_un_champ_blob_en_text',"spip_groupes_mots","texte","LONGTEXT"),
	array('convertir_un_champ_blob_en_text',"spip_rubriques","texte","LONGTEXT"),
	array('convertir_un_champ_blob_en_text',"spip_rubriques","extra","LONGTEXT"),
	array('convertir_un_champ_blob_en_text',"spip_syndic","nom_site","LONGTEXT"),
	array('convertir_un_champ_blob_en_text',"spip_syndic","descriptif","TEXT"),
	array('convertir_un_champ_blob_en_text',"spip_syndic","extra","LONGTEXT"),
	array('convertir_un_champ_blob_en_text',"spip_syndic_articles","descriptif","LONGTEXT"),
	array('convertir_un_champ_blob_en_text',"spip_petitions","texte","LONGTEXT"),
	array('convertir_un_champ_blob_en_text',"spip_ortho_cache","suggest","TEXT"),
	);


function maj_1_938 () {

	$s = spip_query("SELECT `id_type`,`extension` FROM spip_types_documents");
	while ($t = sql_fetch($s)) {
			spip_query("UPDATE spip_documents
				SET `extension`="._q($t['extension'])
				." WHERE `id_type`="._q($t['id_type']));
	}
}

$GLOBALS['maj'][1][938] = array(
	// Des champs NULL a l'installation
	// Ajouter un champ extension aux spip_documents, et le
	// remplir avec les valeurs ad hoc
	array('sql_alter', "TABLE spip_documents ADD `extension` VARCHAR(10) NOT NULL DEFAULT ''"),
	array('sql_alter', "TABLE spip_documents ADD INDEX `extension` (`extension`)"),
	array('maj_1_938'),

	array('sql_alter', "TABLE spip_documents DROP INDEX `id_type`, DROP `id_type`"),
		## supprimer l'autoincrement avant de supprimer la PRIMARY KEY
	array('sql_alter', "TABLE spip_types_documents CHANGE `id_type` `id_type` BIGINT( 21 ) NOT NULL ") ,
	array('sql_alter', "TABLE spip_types_documents DROP PRIMARY KEY"),
	array('sql_alter', "TABLE spip_types_documents DROP `id_type`"),
	array('sql_alter', "TABLE spip_types_documents DROP INDEX `extension`"),

		## recreer la PRIMARY KEY sur spip_types_documents.extension
	array('sql_alter', "TABLE spip_types_documents ADD PRIMARY KEY (`extension`)"),
	);

$GLOBALS['maj'][1][939] = array(
	array('sql_alter', "TABLE spip_visites CHANGE `visites` `visites` INT UNSIGNED DEFAULT '0' NOT NULL"),
	array('sql_alter', "TABLE spip_visites_articles CHANGE `visites` `visites` INT UNSIGNED DEFAULT '0' NOT NULL"),
	array('sql_alter', "TABLE spip_referers CHANGE `visites` `visites` INT UNSIGNED DEFAULT '0' NOT NULL"),
	array('sql_alter', "TABLE spip_referers CHANGE `visites_jour` `visites_jour` INT UNSIGNED DEFAULT '0' NOT NULL"),
	array('sql_alter', "TABLE spip_referers CHANGE `visites_veille` `visites_veille` INT UNSIGNED DEFAULT '0' NOT NULL"),
	array('sql_alter', "TABLE spip_referers_articles CHANGE `visites` `visites` INT UNSIGNED DEFAULT '0' NOT NULL")
	);

$GLOBALS['maj'][1][940] = array(
				array('spip_query', "DROP TABLE spip_caches"),
	);


$GLOBALS['maj'][1][941] = array(
	array('spip_query', "UPDATE spip_meta SET `valeur` = '' WHERE `nom`='preview' AND `valeur`='non' "),
	array('spip_query', "UPDATE spip_meta SET `valeur` = ',0minirezo,1comite,' WHERE `nom`='preview' AND `valeur`='1comite' "),
	array('spip_query', "UPDATE spip_meta SET `valeur` = ',0minirezo,' WHERE `nom`='preview' AND `valeur`='oui' "),
	);

$GLOBALS['maj'][1][942] = array(
	array('sql_alter', "TABLE spip_auteurs CHANGE `statut` `statut` varchar(255)  DEFAULT '0' NOT NULL"),
	array('sql_alter', "TABLE spip_breves CHANGE `statut` `statut` varchar(6)  DEFAULT '0' NOT NULL"),
	array('sql_alter', "TABLE spip_messages CHANGE `statut` `statut` varchar(6)  DEFAULT '0' NOT NULL"),
	array('sql_alter', "TABLE spip_rubriques CHANGE `statut` `statut` varchar(10) DEFAULT '0' NOT NULL"),
	array('sql_alter', "TABLE spip_rubriques CHANGE `statut_tmp` `statut_tmp` varchar(10) DEFAULT '0' NOT NULL"),
	array('sql_alter', "TABLE spip_syndic CHANGE `statut` `statut` varchar(10) DEFAULT '0' NOT NULL"),
	array('sql_alter', "TABLE spip_syndic_articles CHANGE `statut` `statut` varchar(10) DEFAULT '0' NOT NULL"),
	array('sql_alter', "TABLE spip_forum CHANGE `statut` `statut` varchar(8) DEFAULT '0' NOT NULL"),
	array('sql_alter', "TABLE spip_signatures CHANGE `statut` `statut` varchar(10) DEFAULT '0' NOT NULL")
	);


	// suppression de l'indexation dans la version standard
$GLOBALS['maj'][1][943] = array(
	array('sql_alter', "TABLE spip_articles DROP KEY `idx`"),
	array('sql_alter', "TABLE spip_articles DROP `idx`"),
	array('sql_alter', "TABLE spip_auteurs DROP KEY `idx`"),
	array('sql_alter', "TABLE spip_auteurs DROP `idx`"),
	array('sql_alter', "TABLE spip_breves DROP KEY `idx`"),
	array('sql_alter', "TABLE spip_breves DROP `idx`"),
	array('sql_alter', "TABLE spip_mots DROP KEY `idx`"),
	array('sql_alter', "TABLE spip_mots DROP `idx`"),
	array('sql_alter', "TABLE spip_rubriques DROP KEY `idx`"),
	array('sql_alter', "TABLE spip_rubriques DROP `idx`"),
#	array('sql_alter', "TABLE spip_documents DROP KEY `idx`"),
	array('sql_alter', "TABLE spip_documents DROP `idx`"),
	array('sql_alter', "TABLE spip_syndic DROP KEY `idx`"),
	array('sql_alter', "TABLE spip_syndic DROP `idx`"),
	array('sql_alter', "TABLE spip_forum DROP KEY `idx`"),
	array('sql_alter', "TABLE spip_forum DROP `idx`"),
	array('sql_alter', "TABLE spip_signatures DROP KEY `idx`"),
	array('sql_alter', "TABLE spip_signatures DROP `idx`"),

	array('spip_query', "DROP TABLE spip_index"),
	array('spip_query', "DROP TABLE spip_index_dico"),
	);

$GLOBALS['maj'][1][944] = array(
				array('sql_alter', "TABLE spip_documents CHANGE `taille` `taille` integer"),
				array('sql_alter', "TABLE spip_documents CHANGE `largeur` `largeur` integer"),
				array('sql_alter', "TABLE spip_documents CHANGE `hauteur` `hauteur` integer")
	);

$GLOBALS['maj'][1][945] = array(
  array('sql_alter', "TABLE spip_petitions CHANGE `email_unique` `email_unique` CHAR (3) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_petitions CHANGE `site_obli` `site_obli` CHAR (3) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_petitions CHANGE `site_unique` `site_unique` CHAR (3) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_petitions CHANGE `message` `message` CHAR (3) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_petitions CHANGE `texte` `texte` LONGTEXT DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_articles CHANGE `surtitre` `surtitre` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_articles CHANGE `titre` `titre` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_articles CHANGE `soustitre` `soustitre` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_articles CHANGE `descriptif` `descriptif` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_articles CHANGE `chapo` `chapo` mediumtext DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_articles CHANGE `texte` `texte` longtext DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_articles CHANGE `ps` `ps` mediumtext DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_articles CHANGE `accepter_forum` `accepter_forum` CHAR(3) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_articles CHANGE `nom_site` `nom_site` tinytext DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_articles CHANGE `url_site` `url_site` VARCHAR(255) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_articles CHANGE `url_propre` `url_propre` VARCHAR(255) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_auteurs CHANGE `nom` `nom` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_auteurs CHANGE `bio` `bio` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_auteurs CHANGE `email` `email` tinytext DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_auteurs CHANGE `nom_site` `nom_site` tinytext DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_auteurs CHANGE `url_site` `url_site` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_auteurs CHANGE `pass` `pass` tinytext DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_auteurs CHANGE `low_sec` `low_sec` tinytext DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_auteurs CHANGE `pgp` `pgp` TEXT DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_auteurs CHANGE `htpass` `htpass` tinytext DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_breves CHANGE `titre` `titre` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_breves CHANGE `texte` `texte` longtext DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_breves CHANGE `lien_titre` `lien_titre` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_breves CHANGE `lien_url` `lien_url` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_messages CHANGE `titre` `titre` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_messages CHANGE `texte` `texte` longtext DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_messages CHANGE `type` `type` varchar(6) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_messages CHANGE `rv` `rv` varchar(3) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_mots CHANGE `titre` `titre` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_mots CHANGE `descriptif` `descriptif` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_mots CHANGE `texte` `texte` longtext DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_mots CHANGE `type` `type` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_mots CHANGE `url_propre` `url_propre` VARCHAR(255) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_groupes_mots CHANGE `titre` `titre` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_groupes_mots CHANGE `descriptif` `descriptif` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_groupes_mots CHANGE `texte` `texte` longtext DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_groupes_mots CHANGE `unseul` `unseul` varchar(3) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_groupes_mots CHANGE `obligatoire` `obligatoire` varchar(3) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_groupes_mots CHANGE `articles` `articles` varchar(3) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_groupes_mots CHANGE `breves` `breves` varchar(3) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_groupes_mots CHANGE `rubriques` `rubriques` varchar(3) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_groupes_mots CHANGE `syndic` `syndic` varchar(3) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_groupes_mots CHANGE `minirezo` `minirezo` varchar(3) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_groupes_mots CHANGE `comite` `comite` varchar(3) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_groupes_mots CHANGE `forum` `forum` varchar(3) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_rubriques CHANGE `titre` `titre` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_rubriques CHANGE `descriptif` `descriptif` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_rubriques CHANGE `texte` `texte` longtext DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_rubriques CHANGE `url_propre` `url_propre` VARCHAR(255) DEFAULT '' NOT NULL"),
#    array('sql_alter', "TABLE spip_documents CHANGE `extension` `extension` VARCHAR(10) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_documents CHANGE `titre` `titre` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_documents CHANGE `date` `date` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL"),
    array('sql_alter', "TABLE spip_documents CHANGE `descriptif` `descriptif` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_documents CHANGE `fichier` `fichier` varchar(255) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_types_documents CHANGE `extension` `extension` varchar(10) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_types_documents CHANGE `titre` `titre` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_types_documents CHANGE `descriptif` `descriptif` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_types_documents CHANGE `mime_type` `mime_type` varchar(100) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_syndic CHANGE `nom_site` `nom_site` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_syndic CHANGE `url_site` `url_site` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_syndic CHANGE `url_syndic` `url_syndic` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_syndic CHANGE `descriptif` `descriptif` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_syndic CHANGE `url_propre` `url_propre` VARCHAR(255) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_syndic CHANGE `syndication` `syndication` VARCHAR(3) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_syndic_articles CHANGE `titre` `titre` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_syndic_articles CHANGE `url` `url` VARCHAR(255) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_syndic_articles CHANGE `lesauteurs` `lesauteurs` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_syndic_articles CHANGE `descriptif` `descriptif` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_forum CHANGE `titre` `titre` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_forum CHANGE `texte` `texte` mediumtext DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_forum CHANGE `auteur` `auteur` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_forum CHANGE `email_auteur` `email_auteur` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_forum CHANGE `nom_site` `nom_site` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_forum CHANGE `url_site` `url_site` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_forum CHANGE `ip` `ip` varchar(16) DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_signatures CHANGE `nom_email` `nom_email` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_signatures CHANGE `ad_email` `ad_email` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_signatures CHANGE `nom_site` `nom_site` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_signatures CHANGE `url_site` `url_site` text DEFAULT '' NOT NULL"),
    array('sql_alter', "TABLE spip_signatures CHANGE `message` `message` mediumtext DEFAULT '' NOT NULL")
  );


$GLOBALS['maj'][1][946] = array(
    array('sql_alter', "TABLE spip_forum DROP INDEX `id_parent`"),
    array('sql_alter', "TABLE spip_forum DROP INDEX `id_article`"),
    array('sql_alter', "TABLE spip_forum DROP INDEX `id_breve`"),
    array('sql_alter', "TABLE spip_forum DROP INDEX `id_syndic`"),
    array('sql_alter', "TABLE spip_forum DROP INDEX `id_rubrique`"),
    array('sql_alter', "TABLE spip_forum DROP INDEX `date_thread`"),
    array('sql_alter', "TABLE spip_forum DROP INDEX `statut`"),
    array('sql_alter', "TABLE spip_forum ADD INDEX `optimal` (`statut`,`id_parent`,`id_article`,`date_heure`,`id_breve`,`id_syndic`,`id_rubrique`)")
	);


$GLOBALS['maj'][1][947] = array(

    array('sql_alter', "TABLE spip_articles DROP INDEX `url_site`"),
    array('sql_alter', "TABLE spip_articles DROP INDEX `date_modif`"),
    array('sql_alter', "TABLE spip_auteurs  DROP INDEX `lang`")
	);

	// mauvaise manip
$GLOBALS['maj'][1][949] = array(

    array('sql_alter', "TABLE spip_versions DROP INDEX `date`"),
    array('sql_alter', "TABLE spip_versions DROP INDEX `id_auteur`")
	);


function maj_1_950($installee) {
  // oubli de gerer le prefixe lors l'introduction de l'abstraction
  // => Relancer les MAJ concernees si la version dont on part les avait fait
	if ($installe >= 1.946) serie_alter('950a', $GLOBALS['maj'][1][946]); 
	if ($installe >= 1.947) serie_alter('950b', $GLOBALS['maj'][1][947]);
	if ($installe >= 1.949)	@serie_alter('950c', $GLOBALS['maj'][1][949]); 
	global $tables_auxiliaires;
	include_spip('base/auxiliaires');
	$v = $tables_auxiliaires[$k='spip_urls'];
	sql_create($k, $v['field'], $v['key'], false, false);


	foreach(array('article'=>'id_article',
		      'rubrique'=>'id_rubrique',
		      'breve'=>'id_breve',
		      'auteur' => 'id_auteur', 
		      'mot' => 'id_mot', 
		      'syndic' => 'id_syndic') as $type => $id_objet){
		$table = ($type == 'syndic') ? $type : ($type ."s");
		$date = ($type == 'breve') ? 'date_heure' : 
		  (($type == 'auteur') ? 'maj' : 
		   (($type == 'mot') ? 'maj' : 'date'));
		$q = @sql_select("url_propre AS url, $id_objet AS id_objet, '$type' AS type, $date as date", "spip_$table", "url_propre<>''");
		if (!$q) return; // anormal, mais ne pas boucler en erreur
		while ($r = sql_fetch($q)) sql_replace('spip_urls', $r);
		spip_log("table $table : " . sql_count($q) . " urls propres copiees");
		sql_alter("TABLE spip_$table DROP INDEX `url_propre`");
		sql_alter("TABLE spip_$table DROP `url_propre`");
	}
}

// Donner a la fonction ci-dessus le numero de version installee
// AVANT que la mise a jour ait commencee
$GLOBALS['maj'][1][950] =  array(array('maj_1_950', $GLOBALS['meta']['version_installee'] ));

// Erreur dans maj_1_948():
// // http://trac.rezo.net/trac/spip/changeset/10194
// // Gestion du verrou SQL par PHP

$GLOBALS['maj'][1][951] = array(

  array('sql_alter', "TABLE spip_versions CHANGE `id_version` `id_version` bigint(21) DEFAULT 0 NOT NULL")
	);


// Transformation des documents :
// - image => mode=image
// - vignette => mode=vignette

function maj_1_952() {

	$ok = sql_alter("TABLE spip_documents CHANGE `mode` `mode` enum('vignette','image','document') DEFAULT NULL");

	if($ok) {

		$s = sql_select("v.id_document as id_document", "spip_documents as d join spip_documents as v ON d.id_vignette=v.id_document");

		$vignettes = array();
		while ($t = sql_fetch($s))
			$vignettes[] = intval($t['id_document']);

		$ok &= spip_query("UPDATE spip_documents SET `mode`='image' WHERE `mode`='vignette'");
		$ok &= spip_query("UPDATE spip_documents SET `mode`='vignette' WHERE `mode`='image' AND ".calcul_mysql_in('id_document', $vignettes));
	}
	if (!$ok) die('echec sur maj_1_952()'); 
}

$GLOBALS['maj'][1][952] = array(array('maj_1_952'));

function maj_1_953()
{
	global $tables_principales;
	include_spip('base/create');
	creer_base_types_doc($tables_principales['spip_types_documents']);
}

$GLOBALS['maj'][1][953] = array(array('maj_1_953'));

$GLOBALS['maj'][1][954] = array(

		//pas de psd en <img> 
  array('spip_query', "UPDATE spip_types_documents SET `inclus`='non' WHERE `extension`='psd'"),
		//ajout csv
    array('spip_query', "INSERT IGNORE INTO spip_types_documents (`extension`, `titre`) VALUES ('csv', 'CSV')"),
    array('spip_query', "UPDATE spip_types_documents SET `mime_type`='text/csv' WHERE `extension`='csv'"),
		//ajout mkv
    array('spip_query', "INSERT IGNORE INTO spip_types_documents (`extension`, `titre`, `inclus`) VALUES ('mkv', 'Matroska Video', 'embed')"),
    array('spip_query', "UPDATE spip_types_documents SET `mime_type`='video/x-mkv' WHERE `extension`='mkv'"),
		//ajout mka
    array('spip_query', "INSERT IGNORE INTO spip_types_documents (`extension`, `titre`, `inclus`) VALUES ('mka', 'Matroska Audio', 'embed')"),
    array('spip_query', "UPDATE spip_types_documents SET `mime_type`='audio/x-mka' WHERE `extension`='mka'"),
		//ajout kml
    array('spip_query', "INSERT IGNORE INTO spip_types_documents (`extension`, `titre`) VALUES ('kml', 'Keyhole Markup Language')"),
    array('spip_query', "UPDATE spip_types_documents SET `mime_type`='application/vnd.google-earth.kml+xml' WHERE `extension`='kml'"),
		//ajout kmz
    array('spip_query', "INSERT IGNORE INTO spip_types_documents (`extension`, `titre`) VALUES ('kmz', 'Google Earth Placemark File')"),
    array('spip_query', "UPDATE spip_types_documents SET `mime_type`='application/vnd.google-earth.kmz' WHERE `extension`='kmz'")
		);

if ($GLOBALS['meta']['version_installee'] > 1.950)
  // 1.950 lisait un bug dans auxiliaires.php corrige a present
	$GLOBALS['maj'][1][955] = array(
		  array('sql_alter', "TABLE spip_urls CHANGE `maj` date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL")
		  );

// la mise a jour vers 1.938 contient une erreur
// il faut supprimer l'autoincrement avant de supprimer la PRIMARY KEY

$GLOBALS['maj'][1][938] = array(
	array('sql_alter', "TABLE spip_types_documents CHANGE `id_type` `id_type` BIGINT( 21 ) NOT NULL ") ,
	array('sql_alter', "TABLE spip_types_documents DROP PRIMARY KEY"),
	array('sql_alter', "TABLE spip_types_documents DROP `id_type`"),
	array('sql_alter', "TABLE spip_types_documents DROP INDEX `extension`"),
	array('sql_alter', "TABLE spip_types_documents ADD PRIMARY KEY (`extension`)")
	);
// pour ceux pour qui c'est trop tard:
if ($GLOBALS['meta']['version_installee'] > 1.938)
	$GLOBALS['maj'][1][956] = $GLOBALS['maj'][1][938];

// PG veut une valeur par defaut a l'insertion
// http://trac.rezo.net/trac/spip/changeset/10482

$GLOBALS['maj'][1][957] = array(
	array('sql_alter', "TABLE spip_mots CHANGE `id_groupe` `id_groupe` bigint(21) DEFAULT 0 NOT NULL"),
    array('sql_alter', "TABLE spip_documents CHANGE `mode` `mode` ENUM('vignette', 'image', 'document') DEFAULT 'document' NOT NULL")
	);
?>
