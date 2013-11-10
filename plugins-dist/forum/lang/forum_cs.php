<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/forum?lang_cible=cs
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'aucun_message_forum' => 'Aucun message de forum', # NEW

	// B
	'bouton_radio_articles_futurs' => 'pouze k budoucím článkům (žádná akce v databázi).',
	'bouton_radio_articles_tous' => 'ke všem článkům bez výjimky.',
	'bouton_radio_articles_tous_sauf_forum_desactive' => 'ke všem článkům s výjimkou těch, kde je vypnutá diskusní skupina.',
	'bouton_radio_enregistrement_obligatoire' => 'Povinná registrace (uživatelé se musí
  nejprve přihlásit zadáním své elektronické adresy
  a teprve potom mohou zasílat své příspěvky).',
	'bouton_radio_moderation_priori' => 'Moderovaná skupina (příspěvky
 budou zveřejněny až po schválení jedním
 ze správců).', # MODIF
	'bouton_radio_modere_abonnement' => 'na základě přihlášení',
	'bouton_radio_modere_posteriori' => 'moderování ex post', # MODIF
	'bouton_radio_modere_priori' => 'moderování předem', # MODIF
	'bouton_radio_publication_immediate' => 'Okamžité zveřejňování zpráv
 (příspěvky se zobrazují okamžitě,
 správci je mohou odstranit dodatečně).',

	// D
	'documents_interdits_forum' => 'Dokumenty jsou ve fóru zakázané',

	// E
	'erreur_enregistrement_message' => 'Votre message n\'a pas pu être enregistré en raison d\'un problème technique', # NEW

	// F
	'form_pet_message_commentaire' => 'Chcete nám něco sdělit nebo máte nějaké připomínky?',
	'forum' => 'Fórum',
	'forum_acces_refuse' => 'K těmto diskusním skupinám již nemáte přístup.',
	'forum_attention_dix_caracteres' => '<b>Varování!</b> Zpráva musí obsahovat minimálně deset znaků.',
	'forum_attention_message_non_poste' => 'Attention, vous n\'avez pas posté votre message !', # NEW
	'forum_attention_nb_caracteres_mini' => '<b>Attention !</b> votre message doit contenir au moins @min@ caractères.', # NEW
	'forum_attention_trois_caracteres' => '<b>Varování!</b> Název musí obsahovat minimálně deset znaků.',
	'forum_attention_trop_caracteres' => '<b>Varování !</b> vaše zpráva je příliš dlouhá(@compte@ znaků) : aby jí bylo možné uložit, nesmí obsahovat více než  @max@ znaků.', # MODIF
	'forum_avez_selectionne' => 'Zvolili jste:',
	'forum_cliquer_retour' => 'Pokračujte klepnutím <a href=\'@retour_forum@\'>na tento odkaz</a>.',
	'forum_forum' => 'diskusní skupina',
	'forum_info_modere' => 'Tato diskusní skupina je moderovaná: vaše příspěvky se zobrazí až po schválení správcem tohoto webu.', # MODIF
	'forum_lien_hyper' => '<b>Hypertextový odkaz</b> (volitelné)', # MODIF
	'forum_message' => 'Votre message', # NEW
	'forum_message_definitif' => 'Závěrečná zpráva: odesláno na web',
	'forum_message_trop_long' => 'Zpráva je příliš dlouhá. Maximum je 20.000 znaků.', # MODIF
	'forum_ne_repondez_pas' => 'Neodpovídejte na tuto zprávu. Odpověď zveřejněte v rámci skupiny na adrese:', # MODIF
	'forum_page_url' => '(Týká-li se zpráva článku zveřejněného na webu nebo stránky s doplňujícími informacemi, zadejte dále název stránky a její adresu).',
	'forum_permalink' => 'Lien permanent vers le commentaire', # NEW
	'forum_poste_par' => 'Zpráva od posted@parauteur@ na základě vašeho článku.', # MODIF
	'forum_poste_par_court' => 'Message posté@parauteur@.', # NEW
	'forum_poste_par_generique' => 'Message posté@parauteur@ (@objet@ « @titre@ »).', # NEW
	'forum_qui_etes_vous' => '<b>Kdo jste?</b> (volitelné)', # MODIF
	'forum_saisie_texte_info' => 'Ce formulaire accepte les raccourcis SPIP <code>[-&gt;url] {{gras}} {italique} &lt;quote&gt; &lt;code&gt;</code> et le code HTML <code>&lt;q&gt; &lt;del&gt; &lt;ins&gt;</code>. Pour créer des paragraphes, laissez simplement des lignes vides.', # NEW
	'forum_texte' => 'Text zprávy:', # MODIF
	'forum_titre' => 'Předmět:', # MODIF
	'forum_url' => 'Adresa URL:', # MODIF
	'forum_valider' => 'Potvrdit výběr',
	'forum_voir_avant' => 'Před odesláním zprávu zobrazit', # MODIF
	'forum_votre_email' => 'Váš e-mail:', # MODIF
	'forum_votre_nom' => 'Vaše jméno (resp. přezdívka):', # MODIF
	'forum_vous_enregistrer' => 'Této diskusní skupiny se můžete účastnit
  až po zaregistrování. Děkujeme vám
  za zadání identifikačních údajů.
  Nejste-li doposud zaregistrováni, musíte ',
	'forum_vous_inscrire' => 'tak učinit nyní.',

	// I
	'icone_bruler_message' => 'Signaler comme Spam', # NEW
	'icone_bruler_messages' => 'Signaler comme Spam', # NEW
	'icone_legitimer_message' => 'Signaler comme licite', # NEW
	'icone_poster_message' => 'Odeslat zprávu',
	'icone_suivi_forum' => 'Sledování veřejné diskusní skupiny: @nb_forums@ příspěvky',
	'icone_suivi_forums' => 'Správa diskusních skupin',
	'icone_supprimer_message' => 'Ostranit zprávu',
	'icone_supprimer_messages' => 'Supprimer ces messages', # NEW
	'icone_valider_message' => 'Schválit zprávu',
	'icone_valider_messages' => 'Valider ces messages', # NEW
	'icone_valider_repondre_message' => 'Valider & Répondre à ce message', # NEW
	'info_1_message_forum' => '1 message de forum', # NEW
	'info_activer_forum_public' => '<i>Chcete-li zapnout veřejné diskusní skupiny musíte zvolit jejich 
 standardní způsob moderování:</i>', # MODIF
	'info_appliquer_choix_moderation' => 'Použít tento způsob moderování:',
	'info_config_forums_prive' => 'In the editing area of the site several differnt kinds of forum may be activated:', # NEW
	'info_config_forums_prive_admin' => 'A forum reserved for site administrators:', # NEW
	'info_config_forums_prive_global' => 'A global forum, open to all the authors:', # NEW
	'info_config_forums_prive_objets' => 'A forum attached to each article, news item, referenced site, etc.:', # NEW
	'info_desactiver_forum_public' => 'Vypnout veřejné diskusní skupiny
. Tyto skupiny bude možno zapnout ad hoc
 u jednotlivých článků; budou zakázány u sekcí, novinek atd.',
	'info_envoi_forum' => 'Zasílání diskusních skupin autorům článků',
	'info_fonctionnement_forum' => 'Fungování diskusní skupiny:',
	'info_forums_liees_mot' => 'Les messages de forum liés à ce mot', # NEW
	'info_gauche_suivi_forum_2' => 'Stránka pro <i>sledování diskusních skupin</i> je určena ke správě vašeho webu (nejedná se o prostor pro diskusi ani pro redigování). Jsou na ní zobrazeny všechny diskusní příspěvky z veřejné skupiny a umožňuje vám jejich správu.', # MODIF
	'info_liens_syndiques_3' => 'diskusní skupiny',
	'info_liens_syndiques_4' => 'jsou',
	'info_liens_syndiques_5' => 'diskusní skupina',
	'info_liens_syndiques_6' => 'je',
	'info_liens_syndiques_7' => 'čekající na schválení.',
	'info_liens_texte' => 'Lien(s) contenu(s) dans le texte du message', # NEW
	'info_liens_titre' => 'Lien(s) contenu(s) dans le titre du message', # NEW
	'info_mode_fonctionnement_defaut_forum_public' => 'Standardní režim veřejných diskusních skupin',
	'info_nb_messages_forum' => '@nb@ messages de forum', # NEW
	'info_option_email' => 'Autoři článku mohou být informováni emailem o tom, že návštěvníci
  webu poslali zprávu do diskusní skupiny, týkající se
  jejich článku. Chcete tuto možnost použít?', # MODIF
	'info_pas_de_forum' => 'bez diskusní skupiny',
	'info_question_visiteur_ajout_document_forum' => 'If you wish to allow visitors to attach documents (images, sound files, ...) to their forum messages, indicate below the list of file extensions which are authorised (e.g. gif, jpg, png, mp3).', # MODIF
	'info_question_visiteur_ajout_document_forum_format' => 'If you wish to authorise all document types which SPIP allows, then put an asterisk. To allow no file-types, leave empty.', # MODIF
	'info_selectionner_message' => 'Sélectionner les messages :', # NEW
	'interface_formulaire' => 'Interface formulaire', # NEW
	'interface_onglets' => 'Interface avec onglets', # NEW
	'item_activer_forum_administrateur' => 'Zapnout diskusní skupinu správců',
	'item_config_forums_prive_global' => 'Activate the authors\' forum', # NEW
	'item_config_forums_prive_objets' => 'Activate these forums', # NEW
	'item_desactiver_forum_administrateur' => 'Vypnout diskusní skupinu správců',
	'item_non_config_forums_prive_global' => 'Deactivate the authors\' forum', # NEW
	'item_non_config_forums_prive_objets' => 'Deactivate these forums', # NEW

	// L
	'label_selectionner' => 'Sélectionner :', # NEW
	'lien_reponse_article' => 'Odpověď na článek',
	'lien_reponse_breve_2' => 'Odpověď na novinku',
	'lien_reponse_message' => 'Réponse au message', # NEW
	'lien_reponse_rubrique' => 'Odpověď na sekci',
	'lien_reponse_site_reference' => 'Odpověď na web, na nějž vede odkaz:', # MODIF
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
	'onglet_messages_internes' => 'Interní zprávy',
	'onglet_messages_publics' => 'Veřejné zprávy',
	'onglet_messages_vide' => 'Zprávy bez textu',

	// R
	'repondre_message' => 'Reagovat na tuto zprávu',

	// S
	'statut_off' => 'Supprimé', # NEW
	'statut_original' => 'original', # NEW
	'statut_prop' => 'Proposé', # NEW
	'statut_publie' => 'Publié', # NEW
	'statut_spam' => 'Spam', # NEW

	// T
	'text_article_propose_publication_forum' => 'You can comment on this article by using the attached forum (at the bottom of the page).', # NEW
	'texte_en_cours_validation' => 'Les articles, brèves, forums ci dessous sont proposés à la publication.', # NEW
	'texte_en_cours_validation_forum' => 'You can give your comments by using the forums which are attached to them.', # NEW
	'texte_messages_publics' => 'Messages publics sur :', # NEW
	'titre_cadre_forum_administrateur' => 'Soukromá diskusní skupina správců',
	'titre_cadre_forum_interne' => 'Interní diskusní skupina',
	'titre_config_forums_prive' => 'Forums in the editing area', # NEW
	'titre_forum' => 'Diskusní skupina',
	'titre_forum_suivi' => 'Sledování diskusních skupin',
	'titre_page_forum_suivi' => 'Sledování diskusních skupin',
	'titre_selection_action' => 'Sélection', # NEW
	'tout_voir' => 'Voir tous les messages', # NEW

	// V
	'voir_messages_objet' => 'voir les messages' # NEW
);

?>
