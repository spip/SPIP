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

// Cas particulier introduit en http://trac.rezo.net/trac/spip/changeset/10335
function maj_v019_38()
{
	sql_alter("TABLE spip_urls CHANGE `maj` date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL");
}

/*--------------------------------------------------------------------- */
/*			 Nouvelle gestion des MAJ			*/
/* ca coincide avec l'état de la 1.9.2, mais c'est un peu retroactif	*/
/*--------------------------------------------------------------------- */

	// FLV est embeddable, l'upgrade precedent l'avait oublie
function maj_1_931 () {
	spip_query("UPDATE spip_types_documents SET `inclus`='embed' WHERE `extension`='flv'");
}

	// Ajout de spip_forum.date_thread, et on essaie de le remplir
	// a coup de table temporaire (est-ce autorise partout... sinon
	// tant pis, ca ne marchera que pour les forums recemment modifies)
function maj_1_932 () {
	sql_alter("TABLE spip_forum ADD `date_thread` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
	sql_alter("TABLE spip_forum ADD INDEX `date_thread` (`date_thread`)");

	spip_query("DROP TABLE IF EXISTS spip_tmp");
	spip_query("CREATE TEMPORARY TABLE spip_tmp SELECT `id_thread`,MAX(`date_heure`) AS dt FROM spip_forum GROUP BY `id_thread`");
	sql_alter("TABLE spip_tmp ADD INDEX `p` (`id_thread`)");
	spip_query("UPDATE spip_forum AS F JOIN spip_tmp AS T ON F.id_thread=T.id_thread SET F.date_thread=T.dt");
	spip_query("DROP TABLE spip_tmp");
}


// Retrait de _DIR_IMG dans le champ fichier de la table des doc
function maj_1_934 () {
	  $dir_img = substr(_DIR_IMG,strlen(_DIR_RACINE));
	  $n = strlen($dir_img) + 1;
	  spip_query("UPDATE spip_documents SET `fichier`=substring(fichier,$n) WHERE `fichier` LIKE " . _q($dir_img . '%'));
}

function maj_1_935 () {
	sql_alter("TABLE spip_documents_articles ADD `vu` ENUM('non', 'oui') DEFAULT 'non' NOT NULL");
	sql_alter("TABLE spip_documents_rubriques ADD `vu` ENUM('non', 'oui') DEFAULT 'non' NOT NULL");
	sql_alter("TABLE spip_documents_breves ADD `vu` ENUM('non', 'oui') DEFAULT 'non' NOT NULL");
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

function maj_1_937 () {
		// convertir les champs blob des tables spip en champs texte
	convertir_un_champ_blob_en_text("spip_articles","texte","LONGTEXT");
	convertir_un_champ_blob_en_text("spip_articles","extra","LONGTEXT");
	convertir_un_champ_blob_en_text("spip_auteurs","extra","LONGTEXT");
	convertir_un_champ_blob_en_text("spip_breves","texte","LONGTEXT");
	convertir_un_champ_blob_en_text("spip_breves","extra","LONGTEXT");
	convertir_un_champ_blob_en_text("spip_messages","texte","LONGTEXT");
	convertir_un_champ_blob_en_text("spip_mots","texte","LONGTEXT");
	convertir_un_champ_blob_en_text("spip_mots","extra","LONGTEXT");
	convertir_un_champ_blob_en_text("spip_groupes_mots","texte","LONGTEXT");
	convertir_un_champ_blob_en_text("spip_rubriques","texte","LONGTEXT");
	convertir_un_champ_blob_en_text("spip_rubriques","extra","LONGTEXT");
	convertir_un_champ_blob_en_text("spip_syndic","nom_site","LONGTEXT");
	convertir_un_champ_blob_en_text("spip_syndic","descriptif","TEXT");
	convertir_un_champ_blob_en_text("spip_syndic","extra","LONGTEXT");
	convertir_un_champ_blob_en_text("spip_syndic_articles","descriptif","LONGTEXT");
	convertir_un_champ_blob_en_text("spip_petitions","texte","LONGTEXT");
	convertir_un_champ_blob_en_text("spip_ortho_cache","suggest","TEXT");
}


function maj_1_938 () {
	// Des champs NULL a l'installation
	// Ajouter un champ extension aux spip_documents, et le
	// remplir avec les valeurs ad hoc
	sql_alter("TABLE spip_documents ADD `extension` VARCHAR(10) NOT NULL DEFAULT ''");
	sql_alter("TABLE spip_documents ADD INDEX `extension` (`extension`)");
		$s = spip_query("SELECT `id_type`,`extension` FROM spip_types_documents");
		while ($t = sql_fetch($s)) {
			spip_query("UPDATE spip_documents
				SET `extension`="._q($t['extension'])
				." WHERE `id_type`="._q($t['id_type']));
		}
	sql_alter("TABLE spip_documents DROP INDEX `id_type`, DROP `id_type`");
		## supprimer l'autoincrement avant de supprimer la PRIMARY KEY
	sql_alter("TABLE spip_types_documents CHANGE `id_type` `id_type` BIGINT( 21 ) NOT NULL "); 
	sql_alter("TABLE spip_types_documents DROP PRIMARY KEY");

	sql_alter("TABLE spip_types_documents DROP `id_type`");
	sql_alter("TABLE spip_types_documents DROP INDEX `extension`");

		## recreer la PRIMARY KEY sur spip_types_documents.extension
	sql_alter("TABLE spip_types_documents ADD PRIMARY KEY (`extension`)");
}

function maj_1_939 () {
	serie_alter('1939',
		array(
		"TABLE spip_visites CHANGE `visites` `visites` INT UNSIGNED DEFAULT '0' NOT NULL",
		"TABLE spip_visites_articles CHANGE `visites` `visites` INT UNSIGNED DEFAULT '0' NOT NULL",
		"TABLE spip_referers CHANGE `visites` `visites` INT UNSIGNED DEFAULT '0' NOT NULL",
		"TABLE spip_referers CHANGE `visites_jour` `visites_jour` INT UNSIGNED DEFAULT '0' NOT NULL",
		"TABLE spip_referers CHANGE `visites_veille` `visites_veille` INT UNSIGNED DEFAULT '0' NOT NULL",
		"TABLE spip_referers_articles CHANGE `visites` `visites` INT UNSIGNED DEFAULT '0' NOT NULL"
		));
}

function  maj_1_940 ($version_installee, $version_cible) {
	spip_query("DROP TABLE spip_caches");
}


function maj_1_941 () {
	spip_query("UPDATE spip_meta SET `valeur` = '' WHERE `nom`='preview' AND `valeur`='non' ");
	spip_query("UPDATE spip_meta SET `valeur` = ',0minirezo,1comite,' WHERE `nom`='preview' AND `valeur`='1comite' ");
	spip_query("UPDATE spip_meta SET `valeur` = ',0minirezo,' WHERE `nom`='preview' AND `valeur`='oui' ");
}

function maj_1_942 () {
	sql_alter("TABLE spip_auteurs CHANGE `statut` `statut` varchar(255)  DEFAULT '0' NOT NULL");
	sql_alter("TABLE spip_breves CHANGE `statut` `statut` varchar(6)  DEFAULT '0' NOT NULL");
	sql_alter("TABLE spip_messages CHANGE `statut` `statut` varchar(6)  DEFAULT '0' NOT NULL");
	sql_alter("TABLE spip_rubriques CHANGE `statut` `statut` varchar(10) DEFAULT '0' NOT NULL");
	sql_alter("TABLE spip_rubriques CHANGE `statut_tmp` `statut_tmp` varchar(10) DEFAULT '0' NOT NULL");
	sql_alter("TABLE spip_syndic CHANGE `statut` `statut` varchar(10) DEFAULT '0' NOT NULL");
	sql_alter("TABLE spip_syndic_articles CHANGE `statut` `statut` varchar(10) DEFAULT '0' NOT NULL");
	sql_alter("TABLE spip_forum CHANGE `statut` `statut` varchar(8) DEFAULT '0' NOT NULL");
	sql_alter("TABLE spip_signatures CHANGE `statut` `statut` varchar(10) DEFAULT '0' NOT NULL");
}


	// suppression de l'indexation dans la version standard
function maj_1_943 () {
	foreach(array(
		'articles', 'auteurs', 'breves', 'mots', 'rubriques', 'documents', 'syndic', 'forum', 'signatures'
		) as $type) {
		sql_alter("TABLE spip_$type DROP KEY `idx`");
		sql_alter("TABLE spip_$type DROP `idx`");
		}
	spip_query("DROP TABLE spip_index");
	spip_query("DROP TABLE spip_index_dico");
 }

function maj_1_944 () {
	sql_alter("TABLE spip_documents CHANGE `taille` `taille` integer");
	sql_alter("TABLE spip_documents CHANGE `largeur` `largeur` integer");
	sql_alter("TABLE spip_documents CHANGE `hauteur` `hauteur` integer");
}

function maj_1_945()
{
  serie_alter('1945',
		array(
  "TABLE spip_petitions CHANGE `email_unique` `email_unique` CHAR (3) DEFAULT '' NOT NULL",
  "TABLE spip_petitions CHANGE `site_obli` `site_obli` CHAR (3) DEFAULT '' NOT NULL",
  "TABLE spip_petitions CHANGE `site_unique` `site_unique` CHAR (3) DEFAULT '' NOT NULL",
  "TABLE spip_petitions CHANGE `message` `message` CHAR (3) DEFAULT '' NOT NULL",
  "TABLE spip_petitions CHANGE `texte` `texte` LONGTEXT DEFAULT '' NOT NULL",
  "TABLE spip_articles CHANGE `surtitre` `surtitre` text DEFAULT '' NOT NULL",
  "TABLE spip_articles CHANGE `titre` `titre` text DEFAULT '' NOT NULL",
  "TABLE spip_articles CHANGE `soustitre` `soustitre` text DEFAULT '' NOT NULL",
  "TABLE spip_articles CHANGE `descriptif` `descriptif` text DEFAULT '' NOT NULL",
  "TABLE spip_articles CHANGE `chapo` `chapo` mediumtext DEFAULT '' NOT NULL",
  "TABLE spip_articles CHANGE `texte` `texte` longtext DEFAULT '' NOT NULL",
  "TABLE spip_articles CHANGE `ps` `ps` mediumtext DEFAULT '' NOT NULL",
  "TABLE spip_articles CHANGE `accepter_forum` `accepter_forum` CHAR(3) DEFAULT '' NOT NULL",
  "TABLE spip_articles CHANGE `nom_site` `nom_site` tinytext DEFAULT '' NOT NULL",
  "TABLE spip_articles CHANGE `url_site` `url_site` VARCHAR(255) DEFAULT '' NOT NULL",
  "TABLE spip_articles CHANGE `url_propre` `url_propre` VARCHAR(255) DEFAULT '' NOT NULL",
  "TABLE spip_auteurs CHANGE `nom` `nom` text DEFAULT '' NOT NULL",
  "TABLE spip_auteurs CHANGE `bio` `bio` text DEFAULT '' NOT NULL",
  "TABLE spip_auteurs CHANGE `email` `email` tinytext DEFAULT '' NOT NULL",
  "TABLE spip_auteurs CHANGE `nom_site` `nom_site` tinytext DEFAULT '' NOT NULL",
  "TABLE spip_auteurs CHANGE `url_site` `url_site` text DEFAULT '' NOT NULL",
  "TABLE spip_auteurs CHANGE `pass` `pass` tinytext DEFAULT '' NOT NULL",
  "TABLE spip_auteurs CHANGE `low_sec` `low_sec` tinytext DEFAULT '' NOT NULL",
  "TABLE spip_auteurs CHANGE `pgp` `pgp` TEXT DEFAULT '' NOT NULL",
  "TABLE spip_auteurs CHANGE `htpass` `htpass` tinytext DEFAULT '' NOT NULL",
  "TABLE spip_breves CHANGE `titre` `titre` text DEFAULT '' NOT NULL",
  "TABLE spip_breves CHANGE `texte` `texte` longtext DEFAULT '' NOT NULL",
  "TABLE spip_breves CHANGE `lien_titre` `lien_titre` text DEFAULT '' NOT NULL",
  "TABLE spip_breves CHANGE `lien_url` `lien_url` text DEFAULT '' NOT NULL",
  "TABLE spip_messages CHANGE `titre` `titre` text DEFAULT '' NOT NULL",
  "TABLE spip_messages CHANGE `texte` `texte` longtext DEFAULT '' NOT NULL",
  "TABLE spip_messages CHANGE `type` `type` varchar(6) DEFAULT '' NOT NULL",
  "TABLE spip_messages CHANGE `rv` `rv` varchar(3) DEFAULT '' NOT NULL",
  "TABLE spip_mots CHANGE `titre` `titre` text DEFAULT '' NOT NULL",
  "TABLE spip_mots CHANGE `descriptif` `descriptif` text DEFAULT '' NOT NULL",
  "TABLE spip_mots CHANGE `texte` `texte` longtext DEFAULT '' NOT NULL",
  "TABLE spip_mots CHANGE `type` `type` text DEFAULT '' NOT NULL",
  "TABLE spip_mots CHANGE `url_propre` `url_propre` VARCHAR(255) DEFAULT '' NOT NULL",
  "TABLE spip_groupes_mots CHANGE `titre` `titre` text DEFAULT '' NOT NULL",
  "TABLE spip_groupes_mots CHANGE `descriptif` `descriptif` text DEFAULT '' NOT NULL",
  "TABLE spip_groupes_mots CHANGE `texte` `texte` longtext DEFAULT '' NOT NULL",
  "TABLE spip_groupes_mots CHANGE `unseul` `unseul` varchar(3) DEFAULT '' NOT NULL",
  "TABLE spip_groupes_mots CHANGE `obligatoire` `obligatoire` varchar(3) DEFAULT '' NOT NULL",
  "TABLE spip_groupes_mots CHANGE `articles` `articles` varchar(3) DEFAULT '' NOT NULL",
  "TABLE spip_groupes_mots CHANGE `breves` `breves` varchar(3) DEFAULT '' NOT NULL",
  "TABLE spip_groupes_mots CHANGE `rubriques` `rubriques` varchar(3) DEFAULT '' NOT NULL",
  "TABLE spip_groupes_mots CHANGE `syndic` `syndic` varchar(3) DEFAULT '' NOT NULL",
  "TABLE spip_groupes_mots CHANGE `minirezo` `minirezo` varchar(3) DEFAULT '' NOT NULL",
  "TABLE spip_groupes_mots CHANGE `comite` `comite` varchar(3) DEFAULT '' NOT NULL",
  "TABLE spip_groupes_mots CHANGE `forum` `forum` varchar(3) DEFAULT '' NOT NULL",
  "TABLE spip_rubriques CHANGE `titre` `titre` text DEFAULT '' NOT NULL",
  "TABLE spip_rubriques CHANGE `descriptif` `descriptif` text DEFAULT '' NOT NULL",
  "TABLE spip_rubriques CHANGE `texte` `texte` longtext DEFAULT '' NOT NULL",
  "TABLE spip_rubriques CHANGE `url_propre` `url_propre` VARCHAR(255) DEFAULT '' NOT NULL",
  "TABLE spip_documents CHANGE `extension` `extension` VARCHAR(10) DEFAULT '' NOT NULL",
  "TABLE spip_documents CHANGE `titre` `titre` text DEFAULT '' NOT NULL",
  "TABLE spip_documents CHANGE `date` `date` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
  "TABLE spip_documents CHANGE `descriptif` `descriptif` text DEFAULT '' NOT NULL",
  "TABLE spip_documents CHANGE `fichier` `fichier` varchar(255) DEFAULT '' NOT NULL",
  "TABLE spip_types_documents CHANGE `extension` `extension` varchar(10) DEFAULT '' NOT NULL",
  "TABLE spip_types_documents CHANGE `titre` `titre` text DEFAULT '' NOT NULL",
  "TABLE spip_types_documents CHANGE `descriptif` `descriptif` text DEFAULT '' NOT NULL",
  "TABLE spip_types_documents CHANGE `mime_type` `mime_type` varchar(100) DEFAULT '' NOT NULL",
  "TABLE spip_syndic CHANGE `nom_site` `nom_site` text DEFAULT '' NOT NULL",
  "TABLE spip_syndic CHANGE `url_site` `url_site` text DEFAULT '' NOT NULL",
  "TABLE spip_syndic CHANGE `url_syndic` `url_syndic` text DEFAULT '' NOT NULL",
  "TABLE spip_syndic CHANGE `descriptif` `descriptif` text DEFAULT '' NOT NULL",
  "TABLE spip_syndic CHANGE `url_propre` `url_propre` VARCHAR(255) DEFAULT '' NOT NULL",
  "TABLE spip_syndic CHANGE `syndication` `syndication` VARCHAR(3) DEFAULT '' NOT NULL",
  "TABLE spip_syndic_articles CHANGE `titre` `titre` text DEFAULT '' NOT NULL",
  "TABLE spip_syndic_articles CHANGE `url` `url` VARCHAR(255) DEFAULT '' NOT NULL",
  "TABLE spip_syndic_articles CHANGE `lesauteurs` `lesauteurs` text DEFAULT '' NOT NULL",
  "TABLE spip_syndic_articles CHANGE `descriptif` `descriptif` text DEFAULT '' NOT NULL",
  "TABLE spip_forum CHANGE `titre` `titre` text DEFAULT '' NOT NULL",
  "TABLE spip_forum CHANGE `texte` `texte` mediumtext DEFAULT '' NOT NULL",
  "TABLE spip_forum CHANGE `auteur` `auteur` text DEFAULT '' NOT NULL",
  "TABLE spip_forum CHANGE `email_auteur` `email_auteur` text DEFAULT '' NOT NULL",
  "TABLE spip_forum CHANGE `nom_site` `nom_site` text DEFAULT '' NOT NULL",
  "TABLE spip_forum CHANGE `url_site` `url_site` text DEFAULT '' NOT NULL",
  "TABLE spip_forum CHANGE `ip` `ip` varchar(16) DEFAULT '' NOT NULL",
  "TABLE spip_signatures CHANGE `nom_email` `nom_email` text DEFAULT '' NOT NULL",
  "TABLE spip_signatures CHANGE `ad_email` `ad_email` text DEFAULT '' NOT NULL",
  "TABLE spip_signatures CHANGE `nom_site` `nom_site` text DEFAULT '' NOT NULL",
  "TABLE spip_signatures CHANGE `url_site` `url_site` text DEFAULT '' NOT NULL",
  "TABLE spip_signatures CHANGE `message` `message` mediumtext DEFAULT '' NOT NULL"));
}

// http://trac.rezo.net/trac/spip/changeset/10150
function maj_1_946()
{
	sql_alter("TABLE spip_forum DROP INDEX `id_parent`");
	sql_alter("TABLE spip_forum DROP INDEX `id_article`");
	sql_alter("TABLE spip_forum DROP INDEX `id_breve`");
	sql_alter("TABLE spip_forum DROP INDEX `id_syndic`");
	sql_alter("TABLE spip_forum DROP INDEX `id_rubrique`");
	sql_alter("TABLE spip_forum DROP INDEX `date_thread`");
	sql_alter("TABLE spip_forum DROP INDEX `statut`");
	sql_alter("TABLE spip_forum ADD INDEX `optimal` (`statut`,`id_parent`,`id_article`,`date_heure`,`id_breve`,`id_syndic`,`id_rubrique`)");
}

// http://trac.rezo.net/trac/spip/changeset/10151
function maj_1_947()
{
	sql_alter("TABLE spip_articles DROP INDEX `url_site`");
	sql_alter("TABLE spip_articles DROP INDEX `date_modif`");
	sql_alter("TABLE spip_auteurs  DROP INDEX `lang`");
}

	// mauvaise manip
function maj_1_949()
{
	sql_alter("TABLE spip_versions DROP INDEX `date`");
	sql_alter("TABLE spip_versions DROP INDEX `id_auteur`");
}

function maj_1_950()
{
  // oubli de gerer le prefixe lors l'introduction de l'abstraction
  // => on relance les dernieres MAJ en mode silencieux pour mettre au carre.
	@maj_1_946();
	@maj_1_947();
	@maj_1_949();
	global $tables_auxiliaires;
	include_spip('base/auxiliaires');
	$v = $tables_auxiliaires[$k='spip_urls'];
	sql_create($k, $v['field'], $v['key'], false, false);

	// assurer date et pas maj avant la recopie
	sql_alter("TABLE spip_urls CHANGE maj date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL");

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

// http://trac.rezo.net/trac/spip/changeset/10210
// Erreur dans maj_1_948():
// // http://trac.rezo.net/trac/spip/changeset/10194
// // Gestion du verrou SQL par PHP

function maj_1_951()
{
	sql_alter("TABLE spip_versions CHANGE `id_version` `id_version` bigint(21) DEFAULT 0 NOT NULL");
}


// Transformation des documents :
// - image => mode=image
// - vignette => mode=vignette
function maj_1_952()
{

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

function maj_1_953()
{
	global $tables_principales;
	include_spip('base/create');
	creer_base_types_doc($tables_principales['spip_types_documents']);
}

function maj_1_954()
{
		//pas de psd en <img> 
		spip_query("UPDATE spip_types_documents SET `inclus`='non' WHERE `extension`='psd'");
		//ajout csv
		spip_query("INSERT IGNORE INTO spip_types_documents (`extension`, `titre`) VALUES ('csv', 'CSV')");
		spip_query("UPDATE spip_types_documents SET `mime_type`='text/csv' WHERE `extension`='csv'");
		//ajout mkv
		spip_query("INSERT IGNORE INTO spip_types_documents (`extension`, `titre`, `inclus`) VALUES ('mkv', 'Matroska Video', 'embed')");
		spip_query("UPDATE spip_types_documents SET `mime_type`='video/x-mkv' WHERE `extension`='mkv'");
		//ajout mka
		spip_query("INSERT IGNORE INTO spip_types_documents (`extension`, `titre`, `inclus`) VALUES ('mka', 'Matroska Audio', 'embed')");
		spip_query("UPDATE spip_types_documents SET `mime_type`='audio/x-mka' WHERE `extension`='mka'");
		//ajout kml
		spip_query("INSERT IGNORE INTO spip_types_documents (`extension`, `titre`) VALUES ('kml', 'Keyhole Markup Language')");
		spip_query("UPDATE spip_types_documents SET `mime_type`='application/vnd.google-earth.kml+xml' WHERE `extension`='kml'");
		//ajout kmz
		spip_query("INSERT IGNORE INTO spip_types_documents (`extension`, `titre`) VALUES ('kmz', 'Google Earth Placemark File')");
		spip_query("UPDATE spip_types_documents SET `mime_type`='application/vnd.google-earth.kmz' WHERE `extension`='kmz'");
}

function maj_1_955()
{
	sql_alter("TABLE spip_urls CHANGE `maj` date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL");
}

function maj_1_956()
{
	## repasser la fin de la mise a jour vers 1.938 qui contenait une erreur'
	## supprimer l'autoincrement avant de supprimer la PRIMARY KEY
	sql_alter("TABLE spip_types_documents CHANGE `id_type` `id_type` BIGINT( 21 ) NOT NULL "); 
	sql_alter("TABLE spip_types_documents DROP PRIMARY KEY");

	sql_alter("TABLE spip_types_documents DROP `id_type`");
	sql_alter("TABLE spip_types_documents DROP INDEX `extension`");

	## recreer la PRIMARY KEY sur spip_types_documents.extension
	sql_alter("TABLE spip_types_documents ADD PRIMARY KEY (`extension`)");
}

// PG veut une valeur par defaut a l'insertion
// http://trac.rezo.net/trac/spip/changeset/10482

function maj_1_957()
{
	sql_alter("TABLE spip_mots CHANGE `id_groupe` `id_groupe` bigint(21) DEFAULT 0 NOT NULL");
	sql_alter("TABLE spip_documents CHANGE `mode` `mode` ENUM('vignette', 'image', 'document') DEFAULT 'document' NOT NULL");
}
?>
