<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/dump?lang_cible=it
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'aucune_donnee' => 'vuoto',
	'avis_probleme_ecriture_fichier' => 'Problemi nella scrittura del file @fichier@',

	// B
	'bouton_restaurer_base' => 'Ripristina il database',

	// C
	'confirmer_ecraser_base' => 'Si, io voglio sovrascrivere il mio database con il backup',
	'confirmer_ecraser_tables_selection' => 'Si, io voglio sovrascrivere le tabelle selezionate con il backup',

	// D
	'details_sauvegarde' => 'Dettagli del backup :',

	// E
	'erreur_aucune_donnee_restauree' => 'Nessun dato ripristinato',
	'erreur_connect_dump' => 'Un server chiamato « @dump@ » esiste già. Rinominatelo.',
	'erreur_creation_base_sqlite' => 'Impossibile creare un databse SQLite per il salvataggio dei backup',
	'erreur_nom_fichier' => 'Il nome del file non è permesso',
	'erreur_restaurer_verifiez' => 'Risolvere l\'errore in modo da ripristinare.',
	'erreur_sauvegarde_deja_en_cours' => 'C\'è già un backup in corso',
	'erreur_sqlite_indisponible' => 'Impossibile effettuate un backup SQLite sul tuo hosting provider',
	'erreur_table_absente' => 'Table @table@ assente',
	'erreur_table_donnees_manquantes' => 'Tabella @table@, dati mancanti',
	'erreur_taille_sauvegarde' => 'Il backup sembra essere fallito. Il file @fichier@ è vuoto o assente.',

	// I
	'info_aucune_sauvegarde_trouvee' => 'Nessun backup trovato',
	'info_restauration_finie' => 'Finito !. Il backup@archive@ è stato ripristinato sul tuo sito. Adesso puoi',
	'info_restauration_sauvegarde' => 'Ripristino del salvataggio @archive@',
	'info_sauvegarde' => 'Salvataggio',
	'info_sauvegarde_reussi_02' => 'Il database è stato salvato in @archive@. Puoi ',
	'info_sauvegarde_reussi_03' => 'tornare alla gestione',
	'info_sauvegarde_reussi_04' => 'del tuo sito.',
	'info_selection_sauvegarde' => 'Hai scelto di rispritinare il database con il file di backup @fichier@. Questa operazione è irreversibile.',

	// L
	'label_nom_fichier_restaurer' => 'Oppure indica il nome del file di backup da ripristinare',
	'label_nom_fichier_sauvegarde' => 'Nome del file per il backup',
	'label_selectionnez_fichier' => 'Selezionare un file nella lista',

	// N
	'nb_donnees' => '@nb@ record',

	// R
	'restauration_en_cours' => 'Ripristino in corso',

	// S
	'sauvegarde_en_cours' => 'Backup in corso',
	'sauvegardes_existantes' => 'Backup esistenti',
	'selectionnez_table_a_restaurer' => 'Selezionare la tabella da ripristinare',

	// T
	'texte_admin_tech_01' => 'Questa opzione permette di salvare il contenuto del database in un file che sarà conservato nella cartella @dossier@.
Non dimenticare di recuperare integralmente anche la cartella @img@, che contiene le immagini e i documenti utilizzati negli articoli e nelle rubriche.',
	'texte_admin_tech_02' => 'Attenzione: questo backup potrà essere ripristinato SOLO in un sito installato con la stessa versione di SPIP. Non è possibile « svuotare il database » pensando di ripristinare questo backup dopo aver aggiornato la versione di SPIP...
Per maggiori informazioni consulta <a href="@spipnet@">la documentazione di SPIP</a>.',
	'texte_restaurer_base' => 'Ripristina un backup del database',
	'texte_restaurer_sauvegarde' => 'Quest\'opzione permette il ripristino di un backup del database.
Il file di salvataggio deve trovarsi nella cartella @dossier@.
Attenzione: <b>le modifiche o la perdita eventuale di dati sono irreversibili.</b>',
	'texte_sauvegarde' => 'Salva il contenuto del database',
	'texte_sauvegarde_base' => 'Salva il database',
	'tout_restaurer' => 'Ripristina tutte le tabelle',
	'tout_sauvegarder' => 'Backup di tutte le tabelle',

	// U
	'une_donnee' => '1 record'
);

?>
