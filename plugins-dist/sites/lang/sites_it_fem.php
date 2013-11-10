<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/sites?lang_cible=it_fem
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'articles_dispo' => 'En attente', # NEW
	'articles_meme_auteur' => 'Tous les articles de cet auteur', # NEW
	'articles_off' => 'Bloqués', # NEW
	'articles_publie' => 'Publiés', # NEW
	'articles_refuse' => 'Supprimés', # NEW
	'articles_tous' => 'Tous', # NEW
	'aucun_article_syndic' => 'Aucun article syndiqué', # NEW
	'avis_echec_syndication_01' => 'La syndication è fallita: il backend indicato è indecifrabile o non propone alcun articolo.',
	'avis_echec_syndication_02' => 'La syndication è fallita: impossibile accedere al backend di questo sito.',
	'avis_site_introuvable' => 'Sito introvabile',
	'avis_site_syndique_probleme' => 'Attenzione: si è verificato un errore nella syndication del sito; il sistema è temporaneamente fuori uso.
Verifica l\'indirizzo del file per la syndication di (<b>@url_syndic@</b>) e prova nuovamente a recuperare le informazioni remote.',
	'avis_sites_probleme_syndication' => 'Si sono verificati alcuni problemi nella syndication di questi siti',
	'avis_sites_syndiques_probleme' => 'Si sono verificati alcuni problemi nella syndication di questi siti',

	// B
	'bouton_exporter' => 'Exporter', # NEW
	'bouton_importer' => 'Importer', # NEW
	'bouton_radio_modere_posteriori' => 'moderato a posteriori', # MODIF
	'bouton_radio_modere_priori' => 'moderato a priori', # MODIF
	'bouton_radio_non_syndication' => 'Nessuna syndication',
	'bouton_radio_syndication' => 'Syndication:',

	// C
	'confirmer_purger_syndication' => 'Êtes-vous certain de vouloir supprimer tous les articles syndiqués de ce site ?', # NEW

	// E
	'entree_adresse_fichier_syndication' => 'Indirizzo del file di syndication:',
	'entree_adresse_site' => '<b>Indirizzo del sito</b> [Obbligatorio]',
	'entree_description_site' => 'Descrizione del sito',
	'erreur_fichier_format_inconnu' => 'Le format du fichier @fichier@ n\'est pas pris en charge.', # NEW
	'erreur_fichier_incorrect' => 'Impossible de lire le fichier.', # NEW

	// F
	'form_prop_nom_site' => 'Nome del sito',

	// I
	'icone_article_syndic' => 'Article syndiqué', # NEW
	'icone_articles_syndic' => 'Articles syndiqués', # NEW
	'icone_controler_syndication' => 'Publication des articles syndiqués', # NEW
	'icone_modifier_site' => 'Modifica il sito',
	'icone_referencer_nouveau_site' => 'Inserisci un nuovo sito in repertorio',
	'icone_site_reference' => 'Sites référencés', # NEW
	'icone_supprimer_article' => 'Supprimer cet article', # NEW
	'icone_supprimer_articles' => 'Supprimer ces articles', # NEW
	'icone_valider_article' => 'Valider cet article', # NEW
	'icone_valider_articles' => 'Valider ces articles', # NEW
	'icone_voir_sites_references' => 'Vedi i siti in repertorio',
	'info_1_site_importe' => '1 site a été importé', # NEW
	'info_a_valider' => '[da convalidare]',
	'info_aucun_site_importe' => 'Aucun site n\'a pu être importé', # NEW
	'info_bloquer' => 'bloccare',
	'info_bloquer_lien' => 'bloccare questo link',
	'info_derniere_syndication' => 'L\'ultima <em>syndication</em> di questo sito è stata effettuata il',
	'info_liens_syndiques_1' => 'link in syndication',
	'info_liens_syndiques_2' => 'sono in attesa di convalida.',
	'info_nb_sites_importes' => '@nb@ sites ont été importés', # NEW
	'info_nom_site_2' => '<b>Nome del sito</b> [Obbligatorio]',
	'info_panne_site_syndique' => 'Il sito in syndication non funziona',
	'info_probleme_grave' => 'problema di',
	'info_question_proposer_site' => 'Chi può proporre i siti da citare?',
	'info_retablir_lien' => 'ripristinare questo link',
	'info_site_attente' => 'Sito Web in attesa di convalida',
	'info_site_propose' => 'Sito proposto il:',
	'info_site_reference' => 'Sito repertoriato in linea',
	'info_site_refuse' => 'Sito Web rifiutato',
	'info_site_syndique' => 'Questo è un sito in syndication...', # MODIF
	'info_site_valider' => 'Siti da convalidare',
	'info_sites_referencer' => 'Inserisci un sito in repertorio',
	'info_sites_refuses' => 'I siti rifiutati',
	'info_statut_site_1' => 'Questo sito è:',
	'info_statut_site_2' => 'Pubblicato',
	'info_statut_site_3' => 'Proposto',
	'info_statut_site_4' => 'Nel cestino', # MODIF
	'info_syndication' => 'syndication:',
	'info_syndication_articles' => 'articolo/i',
	'item_bloquer_liens_syndiques' => 'Blocca i link in syndication per la convalida',
	'item_gerer_annuaire_site_web' => 'Gestisci un repertorio di siti Web',
	'item_non_bloquer_liens_syndiques' => 'Non bloccare i link provenienti da una syndication',
	'item_non_gerer_annuaire_site_web' => 'Disattiva il repertorio di siti Web',
	'item_non_utiliser_syndication' => 'Non attivare la syndication automatica',
	'item_utiliser_syndication' => 'Attiva la syndication automatica',

	// L
	'label_exporter_avec_mots_cles_1' => 'Exporter les mots-clés sous forme de tags', # NEW
	'label_exporter_id_parent' => 'Exporter les sites de la rubrique', # NEW
	'label_exporter_publie_seulement_1' => 'Exporter uniquement les sites publiés', # NEW
	'label_fichier_import' => 'Fichier HTML', # NEW
	'label_importer_les_tags_1' => 'Importer les tags sous forme de mot-clé', # NEW
	'label_importer_statut_publie_1' => 'Publier automatiquement les sites', # NEW
	'lien_mise_a_jour_syndication' => 'Aggiorna adesso',
	'lien_nouvelle_recuperation' => 'Tenta nuovamente di ripristinare i dati',
	'lien_purger_syndication' => 'Effacer tous les articles syndiqués', # NEW

	// N
	'nombre_articles_syndic' => '@nb@ articles syndiqués', # NEW

	// S
	'statut_off' => 'Supprimé', # NEW
	'statut_prop' => 'En attente', # NEW
	'statut_publie' => 'Publié', # NEW
	'syndic_choix_moderation' => 'Come comportarsi con i prossimi link provenienti da questo sito?',
	'syndic_choix_oublier' => 'Come comportarsi con i link che non compaiono pi&ugrave; nel file di syndication?',
	'syndic_choix_resume' => 'Alcuni siti diffondono il testo completo degli articoli. Nel caso esso sia disponibile desiderate metterlo in syndication:',
	'syndic_lien_obsolete' => 'link non pi&ugrave; valido',
	'syndic_option_miroir' => 'bloccarli automaticamente',
	'syndic_option_oubli' => 'cancellarli (dopo @mois@ mesi)',
	'syndic_option_resume_non' => 'il contenuto completo degli articoli (in formato HTML)',
	'syndic_option_resume_oui' => 'un semplice riassunto (in formato testo)',
	'syndic_options' => 'Opzioni per la syndication:',

	// T
	'texte_expliquer_export_bookmarks' => 'Vous pouvez exporter une liste de sites au format Marque-page HTML,
	pour vous permettre ensuite de l\'importer dans votre navigateur ou dans un service en ligne', # NEW
	'texte_expliquer_import_bookmarks' => 'Vous pouvez importer une liste de sites au format Marque-page HTML,
	en provenance de votre navigateur ou d\'un service en ligne de gestion des Marques-pages.', # NEW
	'texte_liens_sites_syndiques' => 'I link provenienti dai siti in syndication
possono essere bloccati a priori; l\'impostazione
qui sotto indica i criteri predefiniti dei siti in syndication.
Sarà comunque possibile sbloccare singolarmente ogni link,
o scegliere di bloccare i link di ogni singolo sito.', # MODIF
	'texte_messages_publics' => 'Messaggi pubblici dell\'articolo:',
	'texte_non_fonction_referencement' => 'Puoi non utilizzare questa funzione automatica, e indicare direttamente gli elementi riguardanti il sito...', # MODIF
	'texte_referencement_automatique' => '<b>Inserimento automatizzato in repertorio</b><br />È possibile repertoriare rapidamente un sito Web indicandone qui sotto l\'indirizzo URL, o l\'indirizzo del file di syndication. SPIP recupererà automaticamente le informazioni riguardanti il sito (titolo, descrizione...).', # MODIF
	'texte_referencement_automatique_verifier' => 'Controllare le informazioni fornite da <tt>@url@</tt> prima di registrare.',
	'texte_syndication' => 'Quando un sito Web lo permette, è possibile recuperarne automaticamente
la lista delle novità. A tal fine è necessario attivare la syndication.

<blockquote><i>Alcuni provider disattivano questa funzionalità; 
in questo caso, non potrai utilizzare la syndication del contenuto
a partire dal tuo sito.</i></blockquote>', # MODIF
	'titre_articles_syndiques' => 'Articoli in syndication raccolti da questo sito',
	'titre_dernier_article_syndique' => 'Ultimi articoli in syndication',
	'titre_exporter_bookmarks' => 'Exporter des Marques-pages', # NEW
	'titre_importer_bookmarks' => 'Importer des Marques-pages', # NEW
	'titre_importer_exporter_bookmarks' => 'Importer et Exporter des Marques-pages', # NEW
	'titre_page_sites_tous' => 'I siti repertoriati',
	'titre_referencement_sites' => 'Repertorio di siti e syndication',
	'titre_site_numero' => 'SITO NUMERO:',
	'titre_sites_proposes' => 'I siti proposti',
	'titre_sites_references_rubrique' => 'I siti repertoriati in questa rubrica',
	'titre_sites_syndiques' => 'I siti in syndication',
	'titre_sites_tous' => 'I siti repertoriati',
	'titre_syndication' => 'Syndication di siti',
	'tout_voir' => 'Voir tous les articles syndiqués', # NEW

	// U
	'un_article_syndic' => '1 article syndiqué' # NEW
);

?>
