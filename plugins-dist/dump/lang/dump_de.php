<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/dump?lang_cible=de
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'aucune_donnee' => 'leer',
	'avis_probleme_ecriture_fichier' => 'Probleme beim Scheiben der Datei @fichier@',

	// B
	'bouton_restaurer_base' => 'Datenbank wieder herstellen',

	// C
	'confirmer_ecraser_base' => 'Ja, ich will meine Datenbank mit dieser Sicherung überschreiben.',
	'confirmer_ecraser_tables_selection' => 'Ja, ich will die ausgewählten Tabellen mit der dieser Sicherung überschreiben.',

	// D
	'details_sauvegarde' => 'Details der Sicherung:',

	// E
	'erreur_aucune_donnee_restauree' => 'Keine Daten wieder hergestellt',
	'erreur_connect_dump' => 'Ein Server mit dem Namen« @dump@ » existiert bereits. Bitte bennen Sie ihn um.',
	'erreur_creation_base_sqlite' => 'SQLite-Datenbank für die Sicherungskopie kann nicht erstellt werden.',
	'erreur_nom_fichier' => 'Dieser Dateiname ist nicht zulässig.',
	'erreur_restaurer_verifiez' => 'Berichtigen Sie den Fehler, um die Sicherung einspielen zu können.',
	'erreur_sauvegarde_deja_en_cours' => 'Es läuft bereits eine Wiederherstellung',
	'erreur_sqlite_indisponible' => 'Auf Ihrem Server kann keine Sicherung mit SQLite angelegt werden.',
	'erreur_table_absente' => 'Tabelle @table@ fehlt',
	'erreur_table_donnees_manquantes' => 'In der Tabelle @table@ fehlen Daten',
	'erreur_taille_sauvegarde' => 'Die Sicherung ist anscheinend fehlgeschlagen. Die Datei @fichier@ ist leer oder nicht vorhanden.',

	// I
	'info_aucune_sauvegarde_trouvee' => 'Keine Sicherheitskopie gefunden',
	'info_restauration_finie' => 'Fertig ! Die Sicherung @archive@ wurde in Ihre Website eingespielt. Sie können ',
	'info_restauration_sauvegarde' => 'Wiederherstellung der Sicherung @archive@',
	'info_sauvegarde' => 'Sicherung',
	'info_sauvegarde_reussi_02' => 'Die Datenbank wurde in @archive@ gesichert. Sie können ',
	'info_sauvegarde_reussi_03' => 'zur Administration',
	'info_sauvegarde_reussi_04' => 'Ihrer Site zurückkehren.',
	'info_selection_sauvegarde' => 'Sie haben sich entschieden, die Sicherung @fichier@ einzuspielen. Dieser Vorgang kann nicht rückgängig gemacht werden.',

	// L
	'label_nom_fichier_restaurer' => 'oder geben Sie den Namen der Datei an, die wieder hergestellt werden soll.',
	'label_nom_fichier_sauvegarde' => 'Name der Sicherungsdatei',
	'label_selectionnez_fichier' => 'Wählen Sie eine Datei aus der Liste',

	// N
	'nb_donnees' => '@nb@ Sicherheitskopien',

	// R
	'restauration_en_cours' => 'Wiederherstellung läuft',

	// S
	'sauvegarde_en_cours' => 'Sicherung läuft',
	'sauvegardes_existantes' => 'Vorhandene Sicherungen',
	'selectionnez_table_a_restaurer' => 'Wählen Sie die Tabellen, die wieder hergestellt werden sollen.',

	// T
	'texte_admin_tech_01' => 'Diese Option ermöglicht es, den Inhalt der Datenbank in das Verzeichnis @dossier@ zu sichern. Vergessen Sie bitte nicht, ebenfalls den Inhalt des Verzeichnisses <i>img/</i> zu sichern, denn es enthält die Bilder und Grafiken, welche für Rubriken und Artikel verwendet werden.',
	'texte_admin_tech_02' => 'Achtung: Diese Sicherungskopie kann AUSSCHLIESSLICH in eine Website wieder eingespielt werden, die unter der gleichen Version von SPIP läuft.  So darf insbesondere die Datenbank vor einem Update nicht "geleert" werden. Bitte verwenden Sie keine Sicherungskopie, um den Inhalt einer Website nach einem Update wieder einzuspielen. Mehr dazu steht in der <a href="@spipnet@">die SPIP Dokumentation</a>.',
	'texte_restaurer_base' => 'Wiederherstellung des Inhalts der Datenbank',
	'texte_restaurer_sauvegarde' => 'Mit dieser Funktion können Sie eine Sicherungskopie Ihrer Datenbank wieder einspielen. Dazu muss die Sicherungsdatei in das Verzeichnis @dossier@ kopiert werden. Verwenden Sie diese Funktion mit der nötigen Vorsicht. <b>Änderungen und eventuelle Datenverluste können nicht wieder rückgängig gemacht werden.</b>',
	'texte_sauvegarde' => 'Inhalt der Datenbank sichern',
	'texte_sauvegarde_base' => 'Datenbank sichern',
	'tout_restaurer' => 'Alle Tabellen wieder herstellen',
	'tout_sauvegarder' => 'Alle Tabellen sichern',

	// U
	'une_donnee' => '1 Sicherheitskopie'
);

?>
