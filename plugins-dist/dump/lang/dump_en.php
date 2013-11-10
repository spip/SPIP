<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/dump?lang_cible=en
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'aucune_donnee' => 'empty',
	'avis_probleme_ecriture_fichier' => 'Problem writing the file @file@',

	// B
	'bouton_restaurer_base' => 'Restore the database',

	// C
	'confirmer_ecraser_base' => 'Yes, I want to overwrite my database with the backup',
	'confirmer_ecraser_tables_selection' => 'Yes, I want to overwrite the selected tables with the backup',

	// D
	'details_sauvegarde' => 'Details of the backup:',

	// E
	'erreur_aucune_donnee_restauree' => 'No data restored',
	'erreur_connect_dump' => 'A server named "@dump@" already exists. Rename it.',
	'erreur_creation_base_sqlite' => 'Unable to create a SQLite database for backup',
	'erreur_nom_fichier' => 'This file name is not allowed',
	'erreur_restaurer_verifiez' => 'Resolve the error in order to restore.',
	'erreur_sauvegarde_deja_en_cours' => 'You already have a backup in progress',
	'erreur_sqlite_indisponible' => 'Unable to make a SQLite backup on your hosting provider',
	'erreur_table_absente' => 'Table @table@ missing',
	'erreur_table_donnees_manquantes' => 'Table @table@, datas missing',
	'erreur_taille_sauvegarde' => 'The backup appears to have failed. @fichier@ file is empty or absent.',

	// I
	'info_aucune_sauvegarde_trouvee' => 'No backup found',
	'info_restauration_finie' => 'It\'s over!. The backup @archive@ has been restored to your site. you can',
	'info_restauration_sauvegarde' => 'Restoring the backup @archive@',
	'info_sauvegarde' => 'Backup',
	'info_sauvegarde_reussi_02' => 'The database has been saved in @archive@. You can',
	'info_sauvegarde_reussi_03' => 'return to the management',
	'info_sauvegarde_reussi_04' => 'of your site.',
	'info_selection_sauvegarde' => 'You have chosen to restore the backup @file@. This is irreversible.',

	// L
	'label_nom_fichier_restaurer' => 'Or specify the file name to restore',
	'label_nom_fichier_sauvegarde' => 'File name for backup',
	'label_selectionnez_fichier' => 'Select a file in the list',

	// N
	'nb_donnees' => '@nb@ records',

	// R
	'restauration_en_cours' => 'Restoration in progress',

	// S
	'sauvegarde_en_cours' => 'Backup in progress',
	'sauvegardes_existantes' => 'Existing backups',
	'selectionnez_table_a_restaurer' => 'Select the tables to restore',

	// T
	'texte_admin_tech_01' => 'This option lets you save the content of the database as a file in the directory @dossier@. Do not forget to retrieve the whole @img@ directory, which contains the images and documents used in the articles and sections.',
	'texte_admin_tech_02' => 'Warning: this backup can ONLY be restored by the same version of SPIP that created it. You cannot "empty the database" and expect to reinstall the backup after an upgrade...  Refer to <a href="@spipnet@">SPIP documentation</a>.',
	'texte_restaurer_base' => 'Restore a database content backup',
	'texte_restaurer_sauvegarde' => 'This option allows you to restore a previous backup of the database. For this, the file containing the backup should have been stored in the directory @dossier@.
Be very careful with this feature: <b>any potential modifications or losses are irreversible.</b>',
	'texte_sauvegarde' => 'Backup database content',
	'texte_sauvegarde_base' => 'Backup the database',
	'tout_restaurer' => 'Restore all the tables',
	'tout_sauvegarder' => 'Backup all the tables',

	// U
	'une_donnee' => '1 record'
);

?>
