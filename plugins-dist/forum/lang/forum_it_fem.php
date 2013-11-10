<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/forum?lang_cible=it_fem
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'aucun_message_forum' => 'Aucun message de forum', # NEW

	// B
	'bouton_radio_articles_futurs' => 'solo agli articoli inseriti dopo questa modifica (nessuna modifica al database attuale).',
	'bouton_radio_articles_tous' => 'a tutti gli articoli senza eccezioni.',
	'bouton_radio_articles_tous_sauf_forum_desactive' => 'a tutti gli articoli salvo quelli per cui il forum è disattivato.',
	'bouton_radio_enregistrement_obligatoire' => 'Registrazione obbligatoria (le
utenti devono abbonarsi fornendo il loro indirizzo email prima di
poter inviare contributi).',
	'bouton_radio_moderation_priori' => 'Moderazione a priori (i
contributi saranno pubblicati previa autorizzazione delle
amministratori).', # MODIF
	'bouton_radio_modere_abonnement' => 'solo su abbonamento',
	'bouton_radio_modere_posteriori' => 'moderato a posteriori', # MODIF
	'bouton_radio_modere_priori' => 'moderato a priori', # MODIF
	'bouton_radio_publication_immediate' => 'Pubblicazione immediata dei messaggi
(i contributi sono pubblicati subito dopo il loro invio, le amministratrici possono
cancellarli successivamente).',

	// D
	'documents_interdits_forum' => 'Documenti vietati nel forum',

	// E
	'erreur_enregistrement_message' => 'Votre message n\'a pas pu être enregistré en raison d\'un problème technique', # NEW

	// F
	'form_pet_message_commentaire' => 'Un messaggio, un commento?',
	'forum' => 'Forum',
	'forum_acces_refuse' => 'Non hai accesso a questi forum.',
	'forum_attention_dix_caracteres' => '<b>Attenzione!</b> il messaggio deve contenere almeno dieci caratteri.',
	'forum_attention_message_non_poste' => 'Attention, vous n\'avez pas posté votre message !', # NEW
	'forum_attention_nb_caracteres_mini' => '<b>Attention !</b> votre message doit contenir au moins @min@ caractères.', # NEW
	'forum_attention_trois_caracteres' => '<b>Attenzione!</b> il titolo deve contenere almeno tre caratteri.',
	'forum_attention_trop_caracteres' => '<b>Attenzione!</b> il messaggio è troppo lungo (@compte@ caratteri): per poter essere registrato esso non deve essere più lungo di @max@ caratteri.', # MODIF
	'forum_avez_selectionne' => 'Hai selezionato:',
	'forum_cliquer_retour' => 'Clicca <a href=\'@retour_forum@\'>qui</a> per continuare.',
	'forum_forum' => 'forum',
	'forum_info_modere' => 'Questo forum è moderato a priori: il tuo contributo apparirà solo dopo essere stato approvato da un\'amministratrice del sito.', # MODIF
	'forum_lien_hyper' => '<b>Link ipertestuale</b> (opzionale)', # MODIF
	'forum_message' => 'Votre message', # NEW
	'forum_message_definitif' => 'Messaggio definitivo: invialo al sito',
	'forum_message_trop_long' => 'Il tuo messaggio è troppo lungo. La dimensione massima è di 20000 caratteri.', # MODIF
	'forum_ne_repondez_pas' => 'Non rispondere a questo email ma intervieni sul forum all\'indirizzo seguente:', # MODIF
	'forum_page_url' => '(Se il tuo messaggio si riferisce ad un articolo pubblicato sul Web o ad una pagina contenente maggiori informazioni, è possibile indicare di seguito il titolo della pagina ed il suo indirizzo URL.)',
	'forum_permalink' => 'Lien permanent vers le commentaire', # NEW
	'forum_poste_par' => 'Messaggio inviato da @parauteur@ in calce all\'articolo  « @titre@ ».', # MODIF
	'forum_poste_par_court' => 'Message posté@parauteur@.', # NEW
	'forum_poste_par_generique' => 'Message posté@parauteur@ (@objet@ « @titre@ »).', # NEW
	'forum_qui_etes_vous' => '<b>Chi sei?</b> (opzionale)', # MODIF
	'forum_saisie_texte_info' => 'Ce formulaire accepte les raccourcis SPIP <code>[-&gt;url] {{gras}} {italique} &lt;quote&gt; &lt;code&gt;</code> et le code HTML <code>&lt;q&gt; &lt;del&gt; &lt;ins&gt;</code>. Pour créer des paragraphes, laissez simplement des lignes vides.', # NEW
	'forum_texte' => 'Testo del messaggio:', # MODIF
	'forum_titre' => 'Titolo:', # MODIF
	'forum_url' => 'URL:', # MODIF
	'forum_valider' => 'Conferma la scelta',
	'forum_voir_avant' => 'Vedi il messaggio prima di inviarlo', # MODIF
	'forum_votre_email' => 'Indirizzo email:', # MODIF
	'forum_votre_nom' => 'Nome (o pseudonimo):', # MODIF
	'forum_vous_enregistrer' => 'È necessario iscriversi
per partecipare a questo forum.  Indica qui sotto l\'ID personale
che ti è stato fornito.  Se non sei registrata, devi',
	'forum_vous_inscrire' => 'prima iscriverti.',

	// I
	'icone_bruler_message' => 'Signaler comme Spam', # NEW
	'icone_bruler_messages' => 'Signaler comme Spam', # NEW
	'icone_legitimer_message' => 'Signaler comme licite', # NEW
	'icone_poster_message' => 'Invia un messaggio',
	'icone_suivi_forum' => 'Andamento del forum pubblico: @nb_forums@ contributi',
	'icone_suivi_forums' => 'Gestione dei forum',
	'icone_supprimer_message' => 'Elimina il messaggio',
	'icone_supprimer_messages' => 'Supprimer ces messages', # NEW
	'icone_valider_message' => 'Convalida il messaggio',
	'icone_valider_messages' => 'Valider ces messages', # NEW
	'icone_valider_repondre_message' => 'Valider & Répondre à ce message', # NEW
	'info_1_message_forum' => '1 message de forum', # NEW
	'info_activer_forum_public' => '<i>Per attivare i forum pubblici, scegli il tipo
di moderazione predefinito:</i>', # MODIF
	'info_appliquer_choix_moderation' => 'Questa scelta di moderazione verrà applicata:',
	'info_config_forums_prive' => 'Nell\'area riservata del sito è possibile attivare diversi tipi di forum:', # MODIF
	'info_config_forums_prive_admin' => 'Un forum riservato alle amministratrici del sito:',
	'info_config_forums_prive_global' => 'Un forum globale, aperto a tutte le redattrici:',
	'info_config_forums_prive_objets' => 'Un forum per ogni articolo, breve, sito repertoriato, ecc.:',
	'info_desactiver_forum_public' => 'Disattiva l\'uso dei forum pubblici.
I forum pubblici potranno essere autorizzati di volta in volta
sui singoli articoli; saranno invece proibiti nelle rubriche, nelle brevi, ecc',
	'info_envoi_forum' => 'Notifica dei forum alle autrici degli articoli',
	'info_fonctionnement_forum' => 'Funzionamento del forum:',
	'info_forums_liees_mot' => 'Les messages de forum liés à ce mot', # NEW
	'info_gauche_suivi_forum_2' => 'La pagina di <i>monitoraggio dei forum</i> è uno strumento di gestione del sito (e non uno spazio di discussione o di redazione). In essa sono pubblicati tutti i contributi dei forum del sito, sia quelli del sito pubblico che quelli dell\'area riservata, permettendone la gestione.', # MODIF
	'info_liens_syndiques_3' => 'forum',
	'info_liens_syndiques_4' => 'sono',
	'info_liens_syndiques_5' => 'forum',
	'info_liens_syndiques_6' => 'è',
	'info_liens_syndiques_7' => 'in attesa di convalida',
	'info_liens_texte' => 'Lien(s) contenu(s) dans le texte du message', # NEW
	'info_liens_titre' => 'Lien(s) contenu(s) dans le titre du message', # NEW
	'info_mode_fonctionnement_defaut_forum_public' => 'Modo di funzionamento predefinito dei forum pubblici',
	'info_nb_messages_forum' => '@nb@ messages de forum', # NEW
	'info_option_email' => 'Quando un visitatore del sito inserisce un nuovo messaggio nel forum
associato a un articolo, le autrici di quest\'ultimo possono essere
avvertite via email. Per ogni tipo di forum, indica se desideri utilizzare quest\'opzione.',
	'info_pas_de_forum' => 'nessun forum',
	'info_question_visiteur_ajout_document_forum' => 'Se si desidera autorizzare i visitatori ad allegare dei documenti (immagini, musiche, ecc.) ai propri messaggi nel forum, indicare qui sotto l\'elenco delle estensioni dei documenti autorizzati per i forum (per es.: gif, jpg, png, mp3).', # MODIF
	'info_question_visiteur_ajout_document_forum_format' => 'Se si desidera autorizzare tutti i tipi di documenti considerati affidabili da SPIP, mettere un asterisco. Per non autorizzare nulla, non indicare nulla.', # MODIF
	'info_selectionner_message' => 'Sélectionner les messages :', # NEW
	'interface_formulaire' => 'Interface formulaire', # NEW
	'interface_onglets' => 'Interface avec onglets', # NEW
	'item_activer_forum_administrateur' => 'Attiva il forum delle amministratrici',
	'item_config_forums_prive_global' => 'Attiva i forum delle redattrici',
	'item_config_forums_prive_objets' => 'Attiva questi forum',
	'item_desactiver_forum_administrateur' => 'Disattiva il forum delle amministratrici',
	'item_non_config_forums_prive_global' => 'Disattiva i forum delle redattrici',
	'item_non_config_forums_prive_objets' => 'Disattiva questi forum',

	// L
	'label_selectionner' => 'Sélectionner :', # NEW
	'lien_reponse_article' => 'Risposta all\'articolo',
	'lien_reponse_breve_2' => 'Risposta alla breve',
	'lien_reponse_message' => 'Réponse au message', # NEW
	'lien_reponse_rubrique' => 'Risposta alla rubrica',
	'lien_reponse_site_reference' => 'Risposta al sito repertoriato:', # MODIF
	'lien_vider_selection' => 'Vider la selection', # NEW

	// M
	'messages_aucun' => 'Aucun', # NEW
	'messages_meme_auteur' => 'Tous les messages de cet auteur', # NEW
	'messages_meme_email' => 'Tous les messages de cet email', # NEW
	'messages_meme_ip' => 'Tous les messages de cette IP', # NEW
	'messages_off' => 'Supprimés', # NEW
	'messages_perso' => 'Personnels', # NEW
	'messages_privadm' => 'Administrateurs', # NEW
	'messages_prive' => 'Privés', # NEW
	'messages_privoff' => 'Supprimés', # NEW
	'messages_privrac' => 'Généraux', # NEW
	'messages_prop' => 'Proposés', # NEW
	'messages_publie' => 'Publiés', # NEW
	'messages_spam' => 'Spam', # NEW
	'messages_tous' => 'Tous', # NEW

	// O
	'onglet_messages_internes' => 'Messaggi interni',
	'onglet_messages_publics' => 'Messaggi pubblici',
	'onglet_messages_vide' => 'Messaggi senza testo',

	// R
	'repondre_message' => 'Rispondere al messaggio',

	// S
	'statut_off' => 'Supprimé', # NEW
	'statut_original' => 'originale',
	'statut_prop' => 'Proposé', # NEW
	'statut_publie' => 'Publié', # NEW
	'statut_spam' => 'Spam', # NEW

	// T
	'text_article_propose_publication_forum' => 'Non esitare ad esprimere il tuo punto di vista nel forum di questo articolo (a fondo pagina).',
	'texte_en_cours_validation' => 'Les articles, brèves, forums ci dessous sont proposés à la publication.', # NEW
	'texte_en_cours_validation_forum' => 'Non esitare ad esprimere il tuo punto di vista utilizzando i forum ad essi dedicati.',
	'texte_messages_publics' => 'Messages publics sur :', # NEW
	'titre_cadre_forum_administrateur' => 'Forum privato delle amministratrici',
	'titre_cadre_forum_interne' => 'Forum interno',
	'titre_config_forums_prive' => 'Forum dell\'area riservata',
	'titre_forum' => 'Forum',
	'titre_forum_suivi' => 'Monitoraggio dei forum',
	'titre_page_forum_suivi' => 'Monitoraggio dei forum',
	'titre_selection_action' => 'Sélection', # NEW
	'tout_voir' => 'Voir tous les messages', # NEW

	// V
	'voir_messages_objet' => 'voir les messages' # NEW
);

?>
