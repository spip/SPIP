<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// Fichier source, a modifier dans svn://zone.spip.org/spip-zone/_core_/plugins/compresseur/lang/
if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// I
	'info_compresseur_titre' => 'Optimisations et compression',
	'info_question_activer_compactage_css' => 'CSS',
	'info_question_activer_compactage_js' => 'Scripts',
	'info_question_activer_compresseur' => 'HTML',
	'item_compresseur_closure' => 'Utiliser Google Closure Compiler [expérimental]',
	'item_compresseur_css' => 'Activer la compression des feuilles de style (CSS)',
	'item_compresseur_html' => 'Activer la compression du HTML',
	'item_compresseur_js' => 'Activer la compression des scripts (javascript)',

	// T
	'texte_compacter_avertissement' => 'Attention à ne pas activer ces options durant le développement de votre site : les éléments compactés perdent toute lisibilité.',
	'texte_compacter_script_css' => 'SPIP peut compacter les scripts javascript et les feuilles de style CSS, pour les enregistrer dans des fichiers statiques ; cela accélère l\'affichage du site.',
	'texte_compresseur_page' => 'SPIP peut compresser automatiquement chaque page qu\'il envoie aux
visiteurs du site. Ce réglage permet d\'optimiser la bande passante (le
site est plus rapide derrière une liaison à faible débit), mais
demande plus de puissance au serveur.',
	'titre_compacter_script_css' => 'Compactage des scripts et CSS',
	'titre_compresser_flux_http' => 'Compression du HTML'
);

?>
