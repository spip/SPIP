<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/forum?lang_cible=sv
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'aucun_message_forum' => 'Aucun message de forum', # NEW

	// B
	'bouton_radio_articles_futurs' => 'endast för framtida artiklar (ingen inverkan på databasen).',
	'bouton_radio_articles_tous' => 'på alla artiklar utan undantag.',
	'bouton_radio_articles_tous_sauf_forum_desactive' => 'på alla artiklar utom de utan forum.',
	'bouton_radio_enregistrement_obligatoire' => 'Krav på registrering (användare måste registrera sig genom att tillhandahålla sin e-postadress innan de kan bidra).  being able to post contributions).',
	'bouton_radio_moderation_priori' => 'Moderering i förhand (bidrag visas endast efter att de godkänts av en administatör).', # MODIF
	'bouton_radio_modere_abonnement' => 'registrering krävs',
	'bouton_radio_modere_posteriori' => 'moderering i efterhand', # MODIF
	'bouton_radio_modere_priori' => 'moderering i förhand', # MODIF
	'bouton_radio_publication_immediate' => 'Omedelbar publicering av meddelanden (bidrag visas direkt efter att de skickas, administratörer kan radera dom senare):',

	// D
	'documents_interdits_forum' => 'Dokument är inte tillåtna i forumet',

	// E
	'erreur_enregistrement_message' => 'Votre message n\'a pas pu être enregistré en raison d\'un problème technique', # NEW

	// F
	'form_pet_message_commentaire' => 'Något meddelande eller kommentar?',
	'forum' => 'Forum',
	'forum_acces_refuse' => 'Du har inte längre tillgång till dessa forum.',
	'forum_attention_dix_caracteres' => '<b>Varning!</b> Ditt meddelande måste vara minst 10 tecken långt.',
	'forum_attention_message_non_poste' => 'Attention, vous n\'avez pas posté votre message !', # NEW
	'forum_attention_nb_caracteres_mini' => '<b>Attention !</b> votre message doit contenir au moins @min@ caractères.', # NEW
	'forum_attention_trois_caracteres' => '<b>Varning!</b> Din rubrik måste vara minst tre tecken lång.',
	'forum_attention_trop_caracteres' => '<b>Warning !</b> Ditt meddelande är för långt (@compte@ characters) : För att det skall kunna skrivas får meddelandet inte innehålla mer än @max@ tecken.', # MODIF
	'forum_avez_selectionne' => 'Du har valt:',
	'forum_cliquer_retour' => 'Klicka <a href=\'@retour_forum@\'>här</a> för att fortsätta.',
	'forum_forum' => 'forum',
	'forum_info_modere' => 'Detta forum är modererat: ditt bidrag kommer att synas först när någon moderator har godkänt  det.', # MODIF
	'forum_lien_hyper' => '<b>Länk</b> (valfritt)', # MODIF
	'forum_message' => 'Votre message', # NEW
	'forum_message_definitif' => 'Slutligt meddelande: skicka till sajten',
	'forum_message_trop_long' => 'Ditt meddelande är för långt. Det får inte vara mer än 2000 tecken.', # MODIF
	'forum_ne_repondez_pas' => 'Svara inte på detta brev utan i forumet på denna adress:', # MODIF
	'forum_page_url' => '(Om ditt meddelande refererar till en artikel publicerad på webben eller till en sida med mer information, ange namnet på sidan och dess adress nedan).',
	'forum_permalink' => 'Lien permanent vers le commentaire', # NEW
	'forum_poste_par' => 'Meddelande inskickat@parauteur@ efter din artikel', # MODIF
	'forum_poste_par_court' => 'Message posté@parauteur@.', # NEW
	'forum_poste_par_generique' => 'Message posté@parauteur@ (@objet@ « @titre@ »).', # NEW
	'forum_qui_etes_vous' => '<b>Vem är du?</b> (valfritt)', # MODIF
	'forum_saisie_texte_info' => 'Ce formulaire accepte les raccourcis SPIP <code>[-&gt;url] {{gras}} {italique} &lt;quote&gt; &lt;code&gt;</code> et le code HTML <code>&lt;q&gt; &lt;del&gt; &lt;ins&gt;</code>. Pour créer des paragraphes, laissez simplement des lignes vides.', # NEW
	'forum_texte' => 'Texten i meddelandet:', # MODIF
	'forum_titre' => 'Ämne:', # MODIF
	'forum_url' => 'URL:', # MODIF
	'forum_valider' => 'Granska valet',
	'forum_voir_avant' => 'Förhandsgranska meddelandet innan du skickar det', # MODIF
	'forum_votre_email' => 'Din epost-adress', # MODIF
	'forum_votre_nom' => 'Ditt namn (eller alias):', # MODIF
	'forum_vous_enregistrer' => 'För att delta
 i detta forum måste du vara registrerad. Var vänlig
  att skriv in ditt användarnamn som du fick.
 Om du inte är registrerad, måste du  ',
	'forum_vous_inscrire' => 'registrera dig.',

	// I
	'icone_bruler_message' => 'Signaler comme Spam', # NEW
	'icone_bruler_messages' => 'Signaler comme Spam', # NEW
	'icone_legitimer_message' => 'Signaler comme licite', # NEW
	'icone_poster_message' => 'Anslå ett meddelande',
	'icone_suivi_forum' => 'Uppföljning av publika forum: @nb_forums@ bidrag',
	'icone_suivi_forums' => 'Hantera forumen',
	'icone_supprimer_message' => 'Radera meddelandet',
	'icone_supprimer_messages' => 'Supprimer ces messages', # NEW
	'icone_valider_message' => 'Godkänn meddelandet',
	'icone_valider_messages' => 'Valider ces messages', # NEW
	'icone_valider_repondre_message' => 'Valider & Répondre à ce message', # NEW
	'info_1_message_forum' => '1 message de forum', # NEW
	'info_activer_forum_public' => '<i>för att aktivera publika forum, var vänlig och välj moderationssätt:</i>', # MODIF
	'info_appliquer_choix_moderation' => 'Aktivera ditt val av moderering:',
	'info_config_forums_prive' => 'Dans l’espace privé du site, vous pouvez activer plusieurs types de forums :', # NEW
	'info_config_forums_prive_admin' => 'Un forum réservé aux administrateurs du site :', # NEW
	'info_config_forums_prive_global' => 'Un forum global, ouvert à tous les rédacteurs :', # NEW
	'info_config_forums_prive_objets' => 'Un forum sous chaque article, brève, site référencé, etc. :', # NEW
	'info_desactiver_forum_public' => 'Avaktivera de publika forumen.
 Publika forum kan tillåtas  för
 enskilda artiklar; de kommer inte att vara aktiva för avdelningae, nyheter, osv.',
	'info_envoi_forum' => 'Skicka foruminlägg till artikelredaktörerna',
	'info_fonctionnement_forum' => 'Fonctionnement du forum :', # NEW
	'info_forums_liees_mot' => 'Les messages de forum liés à ce mot', # NEW
	'info_gauche_suivi_forum_2' => 'La page de <i>suivi des forums</i> est un outil de gestion de votre site (et non un espace de discussion ou de rédaction). Elle affiche toutes les contributions des forums du site, aussi bien celles du site public que de l\'espace privé et vous permet de gérer ces contributions.', # NEW
	'info_liens_syndiques_3' => 'forum',
	'info_liens_syndiques_4' => 'är',
	'info_liens_syndiques_5' => 'forum',
	'info_liens_syndiques_6' => 'är',
	'info_liens_syndiques_7' => 'i väntan på validering.',
	'info_liens_texte' => 'Lien(s) contenu(s) dans le texte du message', # NEW
	'info_liens_titre' => 'Lien(s) contenu(s) dans le titre du message', # NEW
	'info_mode_fonctionnement_defaut_forum_public' => 'Valt funktionssätt för de publika forumen',
	'info_nb_messages_forum' => '@nb@ messages de forum', # NEW
	'info_option_email' => 'When a site visitor posts a message to the forum
		associated with an article, the article\'s authors can be
		informed of this message by e-mail. Do you wish to use this option?', # MODIF
	'info_pas_de_forum' => 'inget forum',
	'info_question_visiteur_ajout_document_forum' => 'Do you wish to authorise visitors to attach documenst (images, sound files, ...) to their forum messages?', # MODIF
	'info_question_visiteur_ajout_document_forum_format' => 'If so, give below the list of extensions for the file types which are to be authorised (e.g. gif, jpg, png, mp3).', # MODIF
	'info_selectionner_message' => 'Sélectionner les messages :', # NEW
	'interface_formulaire' => 'Interface formulaire', # NEW
	'interface_onglets' => 'Interface avec onglets', # NEW
	'item_activer_forum_administrateur' => 'Aktivera administratörernas forum',
	'item_config_forums_prive_global' => 'Aktivera redaktörernas forum',
	'item_config_forums_prive_objets' => 'Aktivera dessa forum',
	'item_desactiver_forum_administrateur' => 'Avaktivera administratörernas forum',
	'item_non_config_forums_prive_global' => 'Avaktivera redaktörernas forum',
	'item_non_config_forums_prive_objets' => 'Avaktivera dessa forum',

	// L
	'label_selectionner' => 'Sélectionner :', # NEW
	'lien_reponse_article' => 'Svara på artikeln',
	'lien_reponse_breve_2' => 'Skriv ett svar på nyheten',
	'lien_reponse_message' => 'Réponse au message', # NEW
	'lien_reponse_rubrique' => 'Skriv ett svar till avdelningen',
	'lien_reponse_site_reference' => 'Réponse au site référencé :', # MODIF
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
	'onglet_messages_internes' => 'Interna meddelanden',
	'onglet_messages_publics' => 'Publika meddelanden',
	'onglet_messages_vide' => 'Meddelanden utan text',

	// R
	'repondre_message' => 'Svara på meddelandet',

	// S
	'statut_off' => 'Supprimé', # NEW
	'statut_original' => 'original',
	'statut_prop' => 'Proposé', # NEW
	'statut_publie' => 'Publié', # NEW
	'statut_spam' => 'Spam', # NEW

	// T
	'text_article_propose_publication_forum' => 'N\'hésitez pas à donner votre avis grâce au forum attaché à cet article (en bas de page).', # NEW
	'texte_en_cours_validation' => 'Les articles, brèves, forums ci dessous sont proposés à la publication.', # NEW
	'texte_en_cours_validation_forum' => 'N\'hésitez pas à donner votre avis grâce aux forums qui leur sont attachés.', # NEW
	'texte_messages_publics' => 'Messages publics sur :', # NEW
	'titre_cadre_forum_administrateur' => 'Administratörernas privata forum ',
	'titre_cadre_forum_interne' => 'Internt forum',
	'titre_config_forums_prive' => 'Forums de l’espace privé', # NEW
	'titre_forum' => 'Forum',
	'titre_forum_suivi' => 'Suivi des forums', # NEW
	'titre_page_forum_suivi' => 'Suivi des forums', # NEW
	'titre_selection_action' => 'Sélection', # NEW
	'tout_voir' => 'Voir tous les messages', # NEW

	// V
	'voir_messages_objet' => 'voir les messages' # NEW
);

?>
