<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/forum?lang_cible=fr_tu
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'aucun_message_forum' => 'Aucun message de forum',

	// B
	'bouton_radio_articles_futurs' => 'aux articles futurs uniquement (pas d\'action sur la base de données).',
	'bouton_radio_articles_tous' => 'à tous les articles sans exception.',
	'bouton_radio_articles_tous_sauf_forum_desactive' => 'à tous les articles, sauf ceux dont le forum est désactivé.',
	'bouton_radio_enregistrement_obligatoire' => 'Enregistrement obligatoire (les
		utilisateurs doivent s\'abonner en fournissant leur adresse e-mail avant de
		pouvoir poster des contributions).',
	'bouton_radio_moderation_priori' => 'Modération à priori (les
	contributions ne s\'affichent publiquement qu\'après validation par les
	administrateurs).', # MODIF
	'bouton_radio_modere_abonnement' => 'sur abonnement',
	'bouton_radio_modere_posteriori' => 'modération à posteriori', # MODIF
	'bouton_radio_modere_priori' => 'modération à priori', # MODIF
	'bouton_radio_publication_immediate' => 'Publication immédiate des messages
	(les contributions s\'affichent dès leur envoi, les administrateurs peuvent
	les supprimer ensuite).',

	// D
	'documents_interdits_forum' => 'Documents interdits dans le forum',

	// E
	'erreur_enregistrement_message' => 'Ton message n\'a pas pu être enregistré en raison d\'un problème technique',

	// F
	'form_pet_message_commentaire' => 'Un message, un commentaire ?',
	'forum' => 'Forum',
	'forum_acces_refuse' => 'Tu n\'as plus accès à ces forums.',
	'forum_attention_dix_caracteres' => '<b>Attention !</b> ton message doit contenir au moins dix caractères.',
	'forum_attention_message_non_poste' => 'Attention, vous n\'avez pas posté votre message !', # NEW
	'forum_attention_nb_caracteres_mini' => '<b>Attention !</b> votre message doit contenir au moins @min@ caractères.', # NEW
	'forum_attention_trois_caracteres' => '<b>Attention !</b> ton titre doit contenir au moins trois caractères.',
	'forum_attention_trop_caracteres' => '<b>Attention !</b> ton message est trop long (@compte@ caractères) : pour pouvoir être enregistré, il ne doit pas dépasser @max@ caractères.', # MODIF
	'forum_avez_selectionne' => 'Tu as sélectionné :',
	'forum_cliquer_retour' => 'Clique <a href=\'@retour_forum@\'>ici</a> pour continuer.',
	'forum_forum' => 'forum',
	'forum_info_modere' => 'Ce forum est modéré à priori : ta contribution n\'apparaîtra qu\'après avoir été validée par un administrateur du site.', # MODIF
	'forum_lien_hyper' => '<b>Lien hypertexte</b> (optionnel)', # MODIF
	'forum_message' => 'Votre message', # NEW
	'forum_message_definitif' => 'Message définitif : envoyer au site',
	'forum_message_trop_long' => 'Ton message est trop long. La taille maximale est de 20000 caractères.', # MODIF
	'forum_ne_repondez_pas' => 'Ne réponds pas à ce mail, mais sur le forum à l\'adresse suivante:',
	'forum_page_url' => '(Si ton message se réfère à un article publié sur le Web, ou à une page fournissant plus d\'informations, indique ci-après le titre de la page et son adresse.)',
	'forum_permalink' => 'Lien permanent vers le commentaire', # NEW
	'forum_poste_par' => 'Message posté@parauteur@ à la suite de ton article « @titre@ ».', # MODIF
	'forum_poste_par_court' => 'Message posté@parauteur@.', # NEW
	'forum_poste_par_generique' => 'Message posté@parauteur@ (@objet@ « @titre@ »).', # NEW
	'forum_qui_etes_vous' => '<b>Qui es-tu ?</b> (optionnel)', # MODIF
	'forum_saisie_texte_info' => 'Ce formulaire accepte les raccourcis SPIP <code>[-&gt;url] {{gras}} {italique} &lt;quote&gt; &lt;code&gt;</code> et le code HTML <code>&lt;q&gt; &lt;del&gt; &lt;ins&gt;</code>. Pour créer des paragraphes, laissez simplement des lignes vides.', # NEW
	'forum_texte' => 'Texte de ton message :', # MODIF
	'forum_titre' => 'Titre :', # MODIF
	'forum_url' => 'URL :', # MODIF
	'forum_valider' => 'Valider ce choix',
	'forum_voir_avant' => 'Voir ce message avant de le poster', # MODIF
	'forum_votre_email' => 'Ton adresse email :', # MODIF
	'forum_votre_nom' => 'Ton nom (ou pseudonyme) :', # MODIF
	'forum_vous_enregistrer' => 'Pour participer à
		ce forum, tu dois t\'enregistrer au préalable. Merci
		d\'indiquer ci-dessous l\'identifiant personnel qui t\'a
		été fourni. Si tu n\'es pas enregistré, tu dois',
	'forum_vous_inscrire' => 't\'inscrire.',

	// I
	'icone_bruler_message' => 'Signaler comme Spam',
	'icone_bruler_messages' => 'Signaler comme Spam',
	'icone_legitimer_message' => 'Signaler comme licite',
	'icone_poster_message' => 'Poster un message',
	'icone_suivi_forum' => 'Suivi du forum public : @nb_forums@ contribution(s)',
	'icone_suivi_forums' => 'Suivre/gérer les forums',
	'icone_supprimer_message' => 'Supprimer ce message',
	'icone_supprimer_messages' => 'Supprimer ces messages',
	'icone_valider_message' => 'Valider ce message',
	'icone_valider_messages' => 'Valider ces messages',
	'icone_valider_repondre_message' => 'Valider & Répondre à ce message', # MODIF
	'info_1_message_forum' => '1 message de forum',
	'info_activer_forum_public' => '<i>Pour activer les forums publics, choisis leur mode
 de modération par défaut:</i>', # MODIF
	'info_appliquer_choix_moderation' => 'Appliquer ce choix de modération :',
	'info_config_forums_prive' => 'Dans l’espace privé du site, tu peux activer plusieurs types de forums :', # MODIF
	'info_config_forums_prive_admin' => 'Un forum réservé aux administrateurs du site :',
	'info_config_forums_prive_global' => 'Un forum global, ouvert à tous les rédacteurs :',
	'info_config_forums_prive_objets' => 'Un forum sous chaque article, brève, site référencé, etc. :',
	'info_desactiver_forum_public' => 'Désactiver l\'utilisation des forums
	publics. Les forums publics pourront être autorisés au cas par cas
	sur les articles ; ils seront interdits sur les rubriques, brèves, etc.',
	'info_envoi_forum' => 'Envoi des forums aux auteurs des articles',
	'info_fonctionnement_forum' => 'Fonctionnement du forum :',
	'info_forums_liees_mot' => 'Les messages de forum liés à ce mot',
	'info_gauche_suivi_forum_2' => 'La page de <i>suivi des forums</i> est un outil de gestion de ton site (et non un espace de discussion ou de rédaction). Elle affiche toutes les contributions des forums du site, aussi bien celles du site public que de l\'espace privé et te permet de gérer ces contributions.', # MODIF
	'info_liens_syndiques_3' => 'forums',
	'info_liens_syndiques_4' => 'sont',
	'info_liens_syndiques_5' => 'forum',
	'info_liens_syndiques_6' => 'est',
	'info_liens_syndiques_7' => 'en attente de validation',
	'info_liens_texte' => 'Lien(s) contenu(s) dans le texte du message',
	'info_liens_titre' => 'Lien(s) contenu(s) dans le titre du message',
	'info_mode_fonctionnement_defaut_forum_public' => 'Mode de fonctionnement par défaut des forums publics',
	'info_nb_messages_forum' => '@nb@ messages de forum',
	'info_option_email' => 'Lorsqu\'un visiteur du site poste un nouveau message dans le forum associé à un article, les auteurs de l\'article peuvent être prévenus de ce message par e-mail. Indiquer pour chaque type de forum s\'il faut utiliser cette option.',
	'info_pas_de_forum' => 'pas de forum',
	'info_question_visiteur_ajout_document_forum' => 'Si tu souhaites autoriser les visiteurs à joindre des documents (images, sons...) à leurs messages de forums, indique ci-dessous la liste des extensions de documents autorisés pour les forums (ex: gif, jpg, png, mp3).', # MODIF
	'info_question_visiteur_ajout_document_forum_format' => 'Si tu souhaites autoriser tous les types de documents considérés comme fiables par SPIP, mettre une étoile. Pour ne rien autoriser, ne rien indiquer', # MODIF
	'info_selectionner_message' => 'Sélectionner les messages :', # NEW
	'interface_formulaire' => 'Interface formulaire',
	'interface_onglets' => 'Interface avec onglets',
	'item_activer_forum_administrateur' => 'Activer le forum des administrateurs',
	'item_config_forums_prive_global' => 'Activer le forum des rédacteurs',
	'item_config_forums_prive_objets' => 'Activer ces forums',
	'item_desactiver_forum_administrateur' => 'Désactiver le forum des administrateurs',
	'item_non_config_forums_prive_global' => 'Désactiver le forum des rédacteurs',
	'item_non_config_forums_prive_objets' => 'Désactiver ces forums',

	// L
	'label_selectionner' => 'Sélectionner :', # NEW
	'lien_reponse_article' => 'Réponse à l\'article',
	'lien_reponse_breve_2' => 'Réponse à la brève',
	'lien_reponse_message' => 'Réponse au message', # NEW
	'lien_reponse_rubrique' => 'Réponse à la rubrique',
	'lien_reponse_site_reference' => 'Réponse au site référencé :', # MODIF
	'lien_vider_selection' => 'Vider la selection', # NEW

	// M
	'messages_aucun' => 'Aucun',
	'messages_meme_auteur' => 'Tous les messages de cet auteur',
	'messages_meme_email' => 'Tous les messages de cet email',
	'messages_meme_ip' => 'Tous les messages de cette IP',
	'messages_off' => 'Supprimés',
	'messages_perso' => 'Personnels',
	'messages_privadm' => 'Administrateurs',
	'messages_prive' => 'Privés',
	'messages_privoff' => 'Supprimés',
	'messages_privrac' => 'Généraux',
	'messages_prop' => 'Proposés',
	'messages_publie' => 'Publiés',
	'messages_spam' => 'Spam',
	'messages_tous' => 'Tous',

	// O
	'onglet_messages_internes' => 'Messages internes',
	'onglet_messages_publics' => 'Messages publics',
	'onglet_messages_vide' => 'Messages sans texte',

	// R
	'repondre_message' => 'Répondre à ce message',

	// S
	'statut_off' => 'Supprimé',
	'statut_original' => 'original',
	'statut_prop' => 'Proposé',
	'statut_publie' => 'Publié',
	'statut_spam' => 'Spam',

	// T
	'text_article_propose_publication_forum' => 'N\'hésite pas à donner ton avis grâce au forum attaché à cet article (en bas de page).',
	'texte_en_cours_validation' => 'Les articles, brèves, forums ci dessous sont proposés à la publication.', # MODIF
	'texte_en_cours_validation_forum' => 'N\'hésite pas à donner ton avis grâce aux forums qui leur sont attachés.',
	'texte_messages_publics' => 'Messages publics sur :',
	'titre_cadre_forum_administrateur' => 'Forum privé des administrateurs',
	'titre_cadre_forum_interne' => 'Forum interne',
	'titre_config_forums_prive' => 'Forums de l’espace privé',
	'titre_forum' => 'Forum',
	'titre_forum_suivi' => 'Suivi des forums',
	'titre_page_forum_suivi' => 'Suivi des forums',
	'titre_selection_action' => 'Sélection', # NEW
	'tout_voir' => '>Voir tous les messages',

	// V
	'voir_messages_objet' => 'voir les messages'
);

?>
