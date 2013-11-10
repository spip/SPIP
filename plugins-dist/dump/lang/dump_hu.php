<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://www.spip.net/trad-lang/
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'aucune_donnee' => 'vide', # NEW
	'avis_probleme_ecriture_fichier' => 'Problème d\'écriture du fichier @fichier@', # NEW

	// B
	'bouton_restaurer_base' => 'Adatbázis resztaurálása',

	// C
	'confirmer_ecraser_base' => 'Oui, je veux écraser ma base avec cette sauvegarde', # NEW
	'confirmer_ecraser_tables_selection' => 'Oui, je veux écraser les tables sélectionnées avec cette sauvegarde', # NEW

	// D
	'details_sauvegarde' => 'Détails de la sauvegarde :', # NEW

	// E
	'erreur_aucune_donnee_restauree' => 'Aucune donnée restaurée', # NEW
	'erreur_connect_dump' => 'Un serveur nommé « @dump@ » existe déjà. Renommez-le.', # NEW
	'erreur_creation_base_sqlite' => 'Impossible de créer une base SQLite pour la sauvegarde', # NEW
	'erreur_nom_fichier' => 'Ce nom de fichier n\'est pas autorisé', # NEW
	'erreur_restaurer_verifiez' => 'Corrigez l\'erreur pour pouvoir restaurer.', # NEW
	'erreur_sauvegarde_deja_en_cours' => 'Vous avez déjà une sauvegarde en cours', # NEW
	'erreur_sqlite_indisponible' => 'Impossible de faire une sauvegarde SQLite sur votre hébergement', # NEW
	'erreur_table_absente' => 'Table @table@ absente', # NEW
	'erreur_table_donnees_manquantes' => 'Table @table@, données manquantes', # NEW
	'erreur_taille_sauvegarde' => 'La sauvegarde semble avoir échoué. Le fichier @fichier@ est vide ou absent.', # NEW

	// I
	'info_aucune_sauvegarde_trouvee' => 'Aucune sauvegarde trouvée', # NEW
	'info_restauration_finie' => 'C\'est fini !. La sauvegarde @archive@ a été restaurée dans votre site. Vous pouvez', # NEW
	'info_restauration_sauvegarde' => 'A @archive@ nevű mentés resztaurálása', # MODIF
	'info_sauvegarde' => 'Mentés',
	'info_sauvegarde_reussi_02' => 'Az adatbázis mentve lett a @archive@ nevű mappába. Lehet',
	'info_sauvegarde_reussi_03' => 'visszatérni',
	'info_sauvegarde_reussi_04' => 'honlapja üzemeltetéséhez.',
	'info_selection_sauvegarde' => 'Vous avez choisi de restaurer la sauvegarde @fichier@. Cette opération est irréversible.', # NEW

	// L
	'label_nom_fichier_restaurer' => 'Ou indiquez le nom du fichier à restaurer', # NEW
	'label_nom_fichier_sauvegarde' => 'Nom du fichier pour la sauvegarde', # NEW
	'label_selectionnez_fichier' => 'Sélectionnez un fichier dans la liste', # NEW

	// N
	'nb_donnees' => '@nb@ enregistrements', # NEW

	// R
	'restauration_en_cours' => 'Restauration en cours', # NEW

	// S
	'sauvegarde_en_cours' => 'Sauvegarde en cours', # NEW
	'sauvegardes_existantes' => 'Sauvegardes existantes', # NEW
	'selectionnez_table_a_restaurer' => 'Sélectionnez les tables à restaurer', # NEW

	// T
	'texte_admin_tech_01' => 'Ez az opció lehetővé teszi az adatbázis tartalmának a mentését egy fájlban, ami az @dossier@ mappában található. Ne felejtse el megmenteni az @img@ mappa teljes tartalmát, ami a cikkekben és a rovatokban használt dokumentumokat, illetve képeket tartalmazza.',
	'texte_admin_tech_02' => 'Vigyázat: ezt a mentést lehet resztaurálni CSAK egy azonos verziójű SPIP honlapon. Tehát nem szabad  « üríteni az adatbázis » abban a reményben, hogy újratelepítheti a mentést egy verziói fejlesztés után... Érdeklődjön <a href="@spipnet@">az SPIP dokumentációja (Fr)</a>.', # MODIF
	'texte_restaurer_base' => 'Egy megmentett adatbázis tartalmának resztaurálása',
	'texte_restaurer_sauvegarde' => 'Ez az opció lehetővé teszi egy már megmentett adatbázis resztaurálását.
  Azért a mentést tartalmazó fájlt kell helyezni a @dossier@ mappába.
  Legyen óvatos ezzel a lehetőséggel : <b>a módosítások, esetleges vesztések visszavonhatatlanok.</b>',
	'texte_sauvegarde' => 'Az adatbázis tartalmának mentése',
	'texte_sauvegarde_base' => 'Adatbázis mentése',
	'tout_restaurer' => 'Restaurer toutes les tables', # NEW
	'tout_sauvegarder' => 'Sauvegarder toutes les tables', # NEW

	// U
	'une_donnee' => '1 enregistrement' # NEW
);

?>
