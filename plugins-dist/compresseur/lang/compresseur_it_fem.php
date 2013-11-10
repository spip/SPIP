<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/compresseur?lang_cible=it_fem
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// I
	'info_compresseur_titre' => 'Ottimizzazione e compressione',
	'info_question_activer_compactage_css' => 'Si desidera attivare la compressione dei fogli di stile (CSS)?', # MODIF
	'info_question_activer_compactage_js' => 'Si desidera attivare la compressione degli script (javascript)?', # MODIF
	'info_question_activer_compresseur' => 'Si desidera attivare la compressione del flusso HTTP?', # MODIF
	'item_compresseur_closure' => 'Utiliser Google Closure Compiler [expérimental]', # NEW
	'item_compresseur_css' => 'Activer la compression des feuilles de styles (CSS)', # NEW
	'item_compresseur_html' => 'Activer la compression du HTML', # NEW
	'item_compresseur_js' => 'Activer la compression des scripts (javascript)', # NEW

	// T
	'texte_compacter_avertissement' => 'Fare attenzione a non attivare queste opzioni durante la fase di sviluppo del sito: gli elementi compressi sono completamente illeggibili.',
	'texte_compacter_script_css' => 'SPIP può comprimere gli script javascript e i fogli di stile CSS, per registrarli nei file statici; ciò accelera la visualizzazione del sito.',
	'texte_compresseur_page' => 'SPIP può comprimere automaticamente tutte le pagine che invia ai
visitatori del sito. Questa impostazione permette di ottimizzare la banda passante (il
sito è più rapido in una connessione a bassa velocità), ma
richiede una maggiore potenza del server.',
	'titre_compacter_script_css' => 'Compressione degli script e dei CSS',
	'titre_compresser_flux_http' => 'Compressione del flusso HTTP' # MODIF
);

?>
