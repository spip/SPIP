<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/dump?lang_cible=nl
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'aucune_donnee' => 'leeg',
	'avis_probleme_ecriture_fichier' => 'Probleem met het wegschrijven van het bestand @fichier@',

	// B
	'bouton_restaurer_base' => 'De databank herstellen',

	// C
	'confirmer_ecraser_base' => 'Ja, ik wil mijn database overschrijven met de back-up',
	'confirmer_ecraser_tables_selection' => 'Ja, ik wil de geselecteerde tabellen met de back-up overschrijven',

	// D
	'details_sauvegarde' => 'Details van de back-up:',

	// E
	'erreur_aucune_donnee_restauree' => 'Er zijn geen gegevens teruggeplaatst',
	'erreur_connect_dump' => 'Een server met de naam  « @dump@ » bestaat reeds. Hernoemen.',
	'erreur_creation_base_sqlite' => 'Onmogelijk om een SQLite database te maken voor back-up',
	'erreur_nom_fichier' => 'Deze bestandsnaam is niet toegelaten',
	'erreur_restaurer_verifiez' => 'Corrigeer de fout om te kunnen herstellen',
	'erreur_sauvegarde_deja_en_cours' => 'De backup wordt reeds gemaakt',
	'erreur_sqlite_indisponible' => 'Het is niet mogelijk een SQlite backup te doen op je hosting.',
	'erreur_table_absente' => 'Tabel @table@ afwezig',
	'erreur_table_donnees_manquantes' => 'Tabel @table@, gegevens ontbreken',
	'erreur_taille_sauvegarde' => 'De backup blijkt misgelukt. Het bestand @fichier@ is leeg of afwezig.',

	// I
	'info_aucune_sauvegarde_trouvee' => 'Geen backup gevonden',
	'info_restauration_finie' => 'Het is gedaan !. Da backup @archive@ werd op je site teruggezet. Je kan ',
	'info_restauration_sauvegarde' => 'Restauratie van de bescherming @archive@',
	'info_sauvegarde' => 'Backup',
	'info_sauvegarde_reussi_02' => 'De databank werd bewaard in @archive@. Je kan ',
	'info_sauvegarde_reussi_03' => 'terugkeren naar het beheer',
	'info_sauvegarde_reussi_04' => 'op uw website.',
	'info_selection_sauvegarde' => 'Je hebt gekozen om de backup @fichier@ terug te zetten. Deze handeling is onomkeerbaar !',

	// L
	'label_nom_fichier_restaurer' => 'Of schrijf de naam van het te terugzetten bestand in',
	'label_nom_fichier_sauvegarde' => 'Bestandsnaam voor de backup',
	'label_selectionnez_fichier' => 'Selecteer een bestand in het lijst',

	// N
	'nb_donnees' => '@nb@ records',

	// R
	'restauration_en_cours' => 'Backup wordt teruggezet',

	// S
	'sauvegarde_en_cours' => 'Back-up wordt uitgevoerd',
	'sauvegardes_existantes' => 'Bestaande backups',
	'selectionnez_table_a_restaurer' => 'Selecteer de tabellen te terugzetten',

	// T
	'texte_admin_tech_01' => 'Deze optie laat je toe de inhoud van de databank te bewaren in een bestand dat bewaard zal worden in de map @dossier@. Vergeet ook niet de volledige map @img@ te bewaren. Zij bevat alle afbeeldingen en bijlagen bij de artikels en rubrieken.',
	'texte_admin_tech_02' => 'Waarschuwing : deze backup kan ENKEL via een website worden teruggezet die onder dezelfde versie van SPIP is geïnstalleerd.  Men mag vooral niet « de databank legen » in de hoop de backup terug te kunnen plaatsen na het updaten van SPIP… Raadpleeg <a href= "@spipnet@">de documentatie van SPIP</a>.',
	'texte_restaurer_base' => 'De inhoud van de reservekopie van de databank terugzetten',
	'texte_restaurer_sauvegarde' => 'Deze optie laat je toe een eerder genomen reservekopie van de databank
 terug te plaatsen. Hiertoe dien je het bestand met de reservekopie
 te plaatsen in de map @dossier@.
 Wees voorzichtig met het gebruik van deze optie: <b>alle wijzigingen, eventuele verliezen, zijn
  onomkeerbaar.</b>',
	'texte_sauvegarde' => 'Een reservekopie maken van de inhoud van de databank',
	'texte_sauvegarde_base' => 'Reservekopie maken van de databank',
	'tout_restaurer' => 'Alle tabellen terugzetten',
	'tout_sauvegarder' => 'Back-up van alle tabellen maken
',

	// U
	'une_donnee' => '1 record'
);

?>
