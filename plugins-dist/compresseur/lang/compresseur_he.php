<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://www.spip.net/trad-lang/
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// I
	'info_compresseur_titre' => 'אופטימציה וכיווץ',
	'info_question_activer_compactage_css' => 'Souhaitez-vous activer le compactage des feuilles de style (CSS) ?', # MODIF
	'info_question_activer_compactage_js' => 'Souhaitez-vous activer le compactage des scripts (javascript) ?', # MODIF
	'info_question_activer_compresseur' => 'Voulez-vous activer la compression du flux HTTP ?', # MODIF
	'item_compresseur_closure' => 'Utiliser Google Closure Compiler [expérimental]', # NEW
	'item_compresseur_css' => 'Activer la compression des feuilles de styles (CSS)', # NEW
	'item_compresseur_html' => 'Activer la compression du HTML', # NEW
	'item_compresseur_js' => 'Activer la compression des scripts (javascript)', # NEW

	// T
	'texte_compacter_avertissement' => 'Attention à ne pas activer ces options durant le développement de votre site : les éléments compactés perdent toute lisibilité.', # NEW
	'texte_compacter_script_css' => 'SPIP peut compacter les scripts javascript et les feuilles de style CSS, pour les enregistrer dans des fichiers statiques ; cela accélère l\'affichage du site.', # NEW
	'texte_compresseur_page' => 'SPIP peut compresser automatiquement chaque page qu\'il envoie aux
visiteurs du site. Ce réglage permet d\'optimiser la bande passante (le
site est plus rapide derrière une liaison à faible débit), mais
demande plus de puissance au serveur.', # NEW
	'titre_compacter_script_css' => 'Compactage des scripts et CSS', # NEW
	'titre_compresser_flux_http' => 'Compression du flux HTTP' # MODIF
);

?>
