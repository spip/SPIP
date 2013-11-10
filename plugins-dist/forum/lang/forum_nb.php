<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://www.spip.net/trad-lang/
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'aucun_message_forum' => 'Aucun message de forum', # NEW

	// B
	'bouton_radio_articles_futurs' => 'bare for fremtidige artikler (ingen handling mot databasen).',
	'bouton_radio_articles_tous' => 'for alle artikler uten unntak.',
	'bouton_radio_articles_tous_sauf_forum_desactive' => 'for alle artikler, untatt de med avslåtte forum.',
	'bouton_radio_enregistrement_obligatoire' => 'Enregistrement obligatoire (les
		utilisateurs doivent s\'abonner en fournissant leur adresse e-mail avant de
		pouvoir poster des contributions).', # NEW
	'bouton_radio_moderation_priori' => 'Forhåndsmoderasjon (
 bidrag vil bare bli vist etter å ha blitt godkjent av
 administratorer).',
	'bouton_radio_modere_abonnement' => 'moderasjon etter påmelding', # MODIF
	'bouton_radio_modere_posteriori' => 'endring i etterkant', # MODIF
	'bouton_radio_modere_priori' => 'endring på forhånd', # MODIF
	'bouton_radio_publication_immediate' => 'Umiddelbar publisering av meldinger
 (bidrag vil bli vist det øyeblikket de er sendt, deretter kan
 administratorer slette dem).',

	// D
	'documents_interdits_forum' => 'Documents interdits dans le forum', # NEW

	// E
	'erreur_enregistrement_message' => 'Votre message n\'a pas pu être enregistré en raison d\'un problème technique', # NEW

	// F
	'form_pet_message_commentaire' => 'Har du en melding eller kommentar?',
	'forum' => '<NEW> Forum',
	'forum_acces_refuse' => 'Du har ikke tilgang til forumene lengre.',
	'forum_attention_dix_caracteres' => '<b>Advarsel!</b> meldingen er kortere enn ti tegn.', # MODIF
	'forum_attention_message_non_poste' => 'Attention, vous n\'avez pas posté votre message !', # NEW
	'forum_attention_trois_caracteres' => '<b>Advarsel!</b> emnet er kortere enn tre tegn.', # MODIF
	'forum_attention_trop_caracteres' => '<b>Advarsel !</b> meldinger er for lang (@compte@ tegn) : for at den skal kunne lagres må den ikke inneholde mer enn @max@ tegn.',
	'forum_avez_selectionne' => 'Du har valgt:',
	'forum_cliquer_retour' => 'Klikk  <a href=\'@retour_forum@\'>her</a> for å fortsette.',
	'forum_forum' => 'forum',
	'forum_info_modere' => 'Dette forumet er overvåket: ditt innlegg vil vises etter å ha blitt sjekket av en administrator.',
	'forum_lien_hyper' => '<b>Lenke</b> (valgfritt)', # MODIF
	'forum_message' => 'Votre message', # NEW
	'forum_message_definitif' => 'Siste melding: send til vev-sted',
	'forum_message_trop_long' => 'Meldingen er for lang. Maksimal lengde er 20000 tegn.',
	'forum_ne_repondez_pas' => 'Ikke svar på denne e-posten, men i forumet på følgende adresse:', # MODIF
	'forum_page_url' => '(Dersom meldingen din refererer til en side på veven vennligst legg inn tittel og adressen til siden nedenfor).',
	'forum_permalink' => 'Lien permanent vers le commentaire', # NEW
	'forum_poste_par' => 'Melding kommet @parauteur@ til din artikkel "@titre@".',
	'forum_qui_etes_vous' => '<b>Hvem er du?</b> (valgfritt)', # MODIF
	'forum_saisie_texte_info' => 'Ce formulaire accepte les raccourcis SPIP <code>[-&gt;url] {{gras}} {italique} &lt;quote&gt; &lt;code&gt;</code> et le code HTML <code>&lt;q&gt; &lt;del&gt; &lt;ins&gt;</code>. Pour créer des paragraphes, laissez simplement des lignes vides.', # NEW
	'forum_texte' => 'Meldingstekst:', # MODIF
	'forum_titre' => 'Emne:', # MODIF
	'forum_url' => 'URI:', # MODIF
	'forum_valider' => 'Bekreft dette valget',
	'forum_voir_avant' => 'Forhåndsvis melding først ', # MODIF
	'forum_votre_email' => 'Epostadresse:', # MODIF
	'forum_votre_nom' => 'Navn (eller kallenavn):', # MODIF
	'forum_vous_enregistrer' => 'Før man kan delta in
  dette forumet må man registrere seg.
  Vennligst logg inn.
  Dersom du ikke har registrert deg må du',
	'forum_vous_inscrire' => 'registrer.',

	// I
	'icone_bruler_message' => 'Signaler comme Spam', # NEW
	'icone_bruler_messages' => 'Signaler comme Spam', # NEW
	'icone_legitimer_message' => 'Signaler comme licite', # NEW
	'icone_poster_message' => 'Poster un message', # NEW
	'icone_suivi_forum' => 'Suivi du forum public : @nb_forums@ contribution(s)', # NEW
	'icone_suivi_forums' => 'Behandle forum',
	'icone_supprimer_message' => 'Slett melding',
	'icone_supprimer_messages' => 'Supprimer ces messages', # NEW
	'icone_valider_message' => 'Bekreft denne meldingen',
	'icone_valider_messages' => 'Valider ces messages', # NEW
	'icone_valider_repondre_message' => 'Valider &amp; Répondre à ce message', # NEW
	'info_1_message_forum' => '1 message de forum', # NEW
	'info_activer_forum_public' => '<i>Pour activer les forums publics, veuillez choisir leur mode
	de modération par défaut:</i>', # MODIF
	'info_appliquer_choix_moderation' => 'Appliquer ce choix de modération :', # NEW
	'info_config_forums_prive' => 'Dans l’espace privé du site, vous pouvez activer plusieurs types de forums :', # NEW
	'info_config_forums_prive_admin' => 'Un forum réservé aux administrateurs du site :', # NEW
	'info_config_forums_prive_global' => 'Un forum global, ouvert à tous les rédacteurs :', # NEW
	'info_config_forums_prive_objets' => 'Un forum sous chaque article, brève, site référencé, etc. :', # NEW
	'info_desactiver_forum_public' => 'Désactiver l\'utilisation des forums
	publics. Les forums publics pourront être autorisés au cas par cas
	sur les articles ; ils seront interdits sur les rubriques, brèves, etc.', # NEW
	'info_envoi_forum' => 'Envoi des forums aux auteurs des articles', # NEW
	'info_fonctionnement_forum' => 'Fonctionnement du forum :', # NEW
	'info_forums_liees_mot' => 'Les messages de forum liés à ce mot', # NEW
	'info_gauche_suivi_forum_2' => 'La page de <i>suivi des forums</i> est un outil de gestion de votre site (et non un espace de discussion ou de rédaction). Elle affiche toutes les contributions des forums du site, aussi bien celles du site public que de l\'espace privé et vous permet de gérer ces contributions.', # NEW
	'info_liens_syndiques_3' => 'forums', # NEW
	'info_liens_syndiques_4' => 'sont', # NEW
	'info_liens_syndiques_5' => 'forum', # NEW
	'info_liens_syndiques_6' => 'est', # NEW
	'info_liens_syndiques_7' => 'en attente de validation', # NEW
	'info_liens_texte' => 'Lien(s) contenu(s) dans le texte du message', # NEW
	'info_liens_titre' => 'Lien(s) contenu(s) dans le titre du message', # NEW
	'info_mode_fonctionnement_defaut_forum_public' => 'Mode de fonctionnement par défaut des forums publics', # NEW
	'info_nb_messages_forum' => '@nb@ messages de forum', # NEW
	'info_option_email' => 'When a site visitor posts a message to the forum
		associated with an article, the article\'s authors can be
		informed of this message by e-mail. Do you wish to use this option?', # MODIF
	'info_pas_de_forum' => 'pas de forum', # NEW
	'info_question_visiteur_ajout_document_forum' => 'Souhaitez-vous autoriser les visiteurs à joindre des documents (images, sons...) à leurs messages de forums ?', # MODIF
	'info_question_visiteur_ajout_document_forum_format' => 'Le cas échéant, indiquer ci-dessous la liste des extensions de documents autorisés pour les forums (ex: gif, jpg, png, mp3).', # MODIF
	'interface_formulaire' => 'Interface formulaire', # NEW
	'interface_onglets' => 'Interface avec onglets', # NEW
	'item_activer_forum_administrateur' => 'Activer le forum des administrateurs', # NEW
	'item_config_forums_prive_global' => 'Activer le forum des rédacteurs', # NEW
	'item_config_forums_prive_objets' => 'Activer ces forums', # NEW
	'item_desactiver_forum_administrateur' => 'Désactiver le forum des administrateurs', # NEW
	'item_non_config_forums_prive_global' => 'Désactiver le forum des rédacteurs', # NEW
	'item_non_config_forums_prive_objets' => 'Désactiver ces forums', # NEW

	// L
	'lien_reponse_article' => 'Svar på artikkelen',
	'lien_reponse_breve_2' => 'Svar på nyheten',
	'lien_reponse_message' => 'Réponse au message', # NEW
	'lien_reponse_rubrique' => 'Svar på seksjonen',
	'lien_reponse_site_reference' => 'Réponse au site référencé :', # MODIF

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
	'onglet_messages_internes' => 'Interne meldinger',
	'onglet_messages_publics' => 'Offentlige meldinger',
	'onglet_messages_vide' => 'Meldinger uten tekst',

	// R
	'repondre_message' => '<NEW> Répondre à ce message',

	// S
	'statut_off' => 'Supprimé', # NEW
	'statut_original' => 'original', # NEW
	'statut_prop' => 'Proposé', # NEW
	'statut_publie' => 'Publié', # NEW
	'statut_spam' => 'Spam', # NEW

	// T
	'text_article_propose_publication_forum' => 'N\'hésitez pas à donner votre avis grâce au forum attaché à cet article (en bas de page).', # NEW
	'texte_en_cours_validation' => 'Les articles, brèves, forums ci dessous sont proposés à la publication.', # NEW
	'texte_en_cours_validation_forum' => 'N\'hésitez pas à donner votre avis grâce aux forums qui leur sont attachés.', # NEW
	'texte_messages_publics' => 'Messages publics sur&nbsp;:', # NEW
	'titre_cadre_forum_administrateur' => 'Forum privé des administrateurs', # NEW
	'titre_cadre_forum_interne' => 'Internforum',
	'titre_config_forums_prive' => 'Forums de l’espace privé', # NEW
	'titre_forum' => 'Internforum', # MODIF
	'titre_forum_suivi' => 'Oppfølging av forum',
	'titre_page_forum_suivi' => 'Oppfølging av forum',
	'titre_selection_action' => 'Sélection', # NEW
	'tout_voir' => 'Voir tous les messages', # NEW

	// V
	'voir_messages_objet' => 'voir les messages' # NEW
);

?>
