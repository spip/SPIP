<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/compresseur?lang_cible=ca
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// I
	'info_compresseur_titre' => 'Optimitzacions i compressió',
	'info_question_activer_compactage_css' => 'Voleu activar la compressió dels fulls d\'estil (CSS)?', # MODIF
	'info_question_activer_compactage_js' => 'Voleu activat la compressió dels scripts (javascript)?', # MODIF
	'info_question_activer_compresseur' => 'Voleu activar la compressió del flux HTTP?', # MODIF
	'item_compresseur_closure' => 'Utiliser Google Closure Compiler [expérimental]', # NEW
	'item_compresseur_css' => 'Activer la compression des feuilles de styles (CSS)', # NEW
	'item_compresseur_html' => 'Activer la compression du HTML', # NEW
	'item_compresseur_js' => 'Activer la compression des scripts (javascript)', # NEW

	// T
	'texte_compacter_avertissement' => 'Estigueu alerta de no activar aquestes opcions durant el desenvolupament del vostre lloc: els elements compactats perdent tota llegibilitat. ',
	'texte_compacter_script_css' => 'SPIP pot compactar els scripts javascript i els fulls d\'estil CSS, per enregistrar-los a dins dels fitxers estàtics; això accelera la presentació del lloc Web.',
	'texte_compresseur_page' => 'SPIP pot comprimir automàticament cada pàgina que envia als visitants del lloc Web. Aquest reglatge permet optimitzar l\'ample de banda (el lloc és més ràpid darrere una connexió més lenta), però demana més potència al sevidor.',
	'titre_compacter_script_css' => 'Compressió dels scripts i el CSS',
	'titre_compresser_flux_http' => 'Compressió del flux HTTP' # MODIF
);

?>
