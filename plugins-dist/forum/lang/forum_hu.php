<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/forum?lang_cible=hu
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'aucun_message_forum' => 'Aucun message de forum', # NEW

	// B
	'bouton_radio_articles_futurs' => 'csak a leendő cikkek részére (nincs hatás az adatbázisban).',
	'bouton_radio_articles_tous' => 'minden cikknek, kivétel nélkül.',
	'bouton_radio_articles_tous_sauf_forum_desactive' => 'minden cikknek, kivéve azokat, melynek nincs aktiválva a fóruma.',
	'bouton_radio_enregistrement_obligatoire' => 'Beíratkozás kötelező (a
  felhasználóknak be kell iratkozniuk azzal, hogy adják email címüket mielőbb
  küldhessenek anyagokat).',
	'bouton_radio_moderation_priori' => 'Előzetes moderálás (a
 küldött anyagok csak az adminisztrátorok adta jováhagyást kerülnek nyilvánosságra).', # MODIF
	'bouton_radio_modere_abonnement' => 'moderálva beiratkozásnál',
	'bouton_radio_modere_posteriori' => 'utólag moderált', # MODIF
	'bouton_radio_modere_priori' => 'elözőleg moderálva', # MODIF
	'bouton_radio_publication_immediate' => 'Üzenetek azonnali publikálása
 (a küldött anyagok már küldéskor megjelennek, az adminisztrátorok későb tudják törölni).',

	// D
	'documents_interdits_forum' => 'Documents interdits dans le forum', # NEW

	// E
	'erreur_enregistrement_message' => 'Votre message n\'a pas pu être enregistré en raison d\'un problème technique', # NEW

	// F
	'form_pet_message_commentaire' => 'Üzenet, hozzászólás ?',
	'forum' => 'Fórum',
	'forum_acces_refuse' => 'Már nincs hozzáférése ezekhez a fórumokhoz.',
	'forum_attention_dix_caracteres' => '<b>Vigyázat !</b> Az üzenetének legalább 10 karaktert kell tartalmaznia.',
	'forum_attention_message_non_poste' => 'Attention, vous n\'avez pas posté votre message !', # NEW
	'forum_attention_nb_caracteres_mini' => '<b>Attention !</b> votre message doit contenir au moins @min@ caractères.', # NEW
	'forum_attention_trois_caracteres' => '<b>Vigyázat !</b> A címnek legalább három karaktert kell tartalmaznia.',
	'forum_attention_trop_caracteres' => '<b>Figyelem !</b> az üzenete túl hosszú (@compte@ karakter) : legfeljebb @max@ karakterből állhat.', # MODIF
	'forum_avez_selectionne' => 'Kijelölt :',
	'forum_cliquer_retour' => 'Kattintson <a href=\'@retour_forum@\'>ide</a> a folytatáshoz.',
	'forum_forum' => 'fórum',
	'forum_info_modere' => 'Ez a fórum előre moderált : az Ön hozzászólása csak azután jelenik meg, hogy a honlap egyik adminisztrátora jóváhagyta.', # MODIF
	'forum_lien_hyper' => '<b>Hiperhivatkozás</b> (választható)', # MODIF
	'forum_message' => 'Votre message', # NEW
	'forum_message_definitif' => 'Végleges üzenet: küldés a honlapra',
	'forum_message_trop_long' => 'Az Ön üzenete túl hosszú. A maximális méret 20 000 karakter.', # MODIF
	'forum_ne_repondez_pas' => 'Ne erre az emailre válaszoljon, hanem a fórumon a következő címen:', # MODIF
	'forum_page_url' => '(Amennyiben az Ön üzenete egy interneten publikált, vagy további információt tartalmazó oldalra vonatkozik, akkor adja meg az oldal nevét, illetve URL címét.)',
	'forum_permalink' => 'Lien permanent vers le commentaire', # NEW
	'forum_poste_par' => 'Az üzenetet @parauteur@ küldte, "@titre@" című cikkére válaszul.', # MODIF
	'forum_poste_par_court' => 'Message posté@parauteur@.', # NEW
	'forum_poste_par_generique' => 'Message posté@parauteur@ (@objet@ « @titre@ »).', # NEW
	'forum_qui_etes_vous' => '<b>Kicsoda Ön?</b> (nem kötelező)', # MODIF
	'forum_saisie_texte_info' => 'Ce formulaire accepte les raccourcis SPIP <code>[-&gt;url] {{gras}} {italique} &lt;quote&gt; &lt;code&gt;</code> et le code HTML <code>&lt;q&gt; &lt;del&gt; &lt;ins&gt;</code>. Pour créer des paragraphes, laissez simplement des lignes vides.', # NEW
	'forum_texte' => 'Üzenetének szövege:', # MODIF
	'forum_titre' => 'Tárgy:', # MODIF
	'forum_url' => 'URL:', # MODIF
	'forum_valider' => 'Érvényesítse választását',
	'forum_voir_avant' => 'Küldés előtti előnézet megtekintése', # MODIF
	'forum_votre_email' => 'Az Ön email címe:', # MODIF
	'forum_votre_nom' => 'Az Öne neve (vagy beceneve):', # MODIF
	'forum_vous_enregistrer' => 'A fórum használatához
  kérjük, először regisztráljon. Lentebb adja meg
  a személyes azonosítót,
amit kapott. Amennyiben nincs regisztrálva, akkor',
	'forum_vous_inscrire' => 'regisztráljon.',

	// I
	'icone_bruler_message' => 'Signaler comme Spam', # NEW
	'icone_bruler_messages' => 'Signaler comme Spam', # NEW
	'icone_legitimer_message' => 'Signaler comme licite', # NEW
	'icone_poster_message' => 'Üzenet küldése',
	'icone_suivi_forum' => 'A nyilvános fórum megfigyelése : @nb_forums@ hozzászlás',
	'icone_suivi_forums' => 'Figyelni/kezelni a fórumokat',
	'icone_supprimer_message' => 'Az üzenet törlése',
	'icone_supprimer_messages' => 'Supprimer ces messages', # NEW
	'icone_valider_message' => 'Az üzenet érvényesítése',
	'icone_valider_messages' => 'Valider ces messages', # NEW
	'icone_valider_repondre_message' => 'Valider & Répondre à ce message', # NEW
	'info_1_message_forum' => '1 message de forum', # NEW
	'info_activer_forum_public' => '<i>A nyilvános fórumok aktiválására, válassza az alapértelmezett
 móderálási módjukat:</i>', # MODIF
	'info_appliquer_choix_moderation' => 'Alkalmazni ezt a moderálási választást :',
	'info_config_forums_prive' => 'Dans l’espace privé du site, vous pouvez activer plusieurs types de forums :', # NEW
	'info_config_forums_prive_admin' => 'Un forum réservé aux administrateurs du site :', # NEW
	'info_config_forums_prive_global' => 'Un forum global, ouvert à tous les rédacteurs :', # NEW
	'info_config_forums_prive_objets' => 'Un forum sous chaque article, brève, site référencé, etc. :', # NEW
	'info_desactiver_forum_public' => 'A nyilvános fórumok kikapcsolása.
 A nyilvános fórumokat egyenként lehet engedélyezni
 a cikkeknél ; tiltva lesznek a rovatoknál, híreknél, stb.',
	'info_envoi_forum' => 'Fórumok küldése a cikkek szerzőinek',
	'info_fonctionnement_forum' => 'Fórum működése :',
	'info_forums_liees_mot' => 'Les messages de forum liés à ce mot', # NEW
	'info_gauche_suivi_forum_2' => 'A <i>fórumok megfigyelése</i> nevű oldal a honlap egyik kezelési eszkőze (és nem pedig vitás, vagy szerzői rész). A honlap összes (nyilvános, ill. privát rész) fórumainak hozzászólásait jeleníti meg, és ezeknek kezelését teszi lehetővé.', # MODIF
	'info_liens_syndiques_3' => 'fórumok',
	'info_liens_syndiques_4' => 'vannak',
	'info_liens_syndiques_5' => 'fórum',
	'info_liens_syndiques_6' => 'van',
	'info_liens_syndiques_7' => 'jóváhagyás alatt',
	'info_liens_texte' => 'Lien(s) contenu(s) dans le texte du message', # NEW
	'info_liens_titre' => 'Lien(s) contenu(s) dans le titre du message', # NEW
	'info_mode_fonctionnement_defaut_forum_public' => 'Nyilvános fórumok alapértelmezett működési módszere',
	'info_nb_messages_forum' => '@nb@ messages de forum', # NEW
	'info_option_email' => 'Ha egy látogató üzenetet küld egy új üzenetet a cikkhez csatolt fórumba,
  akkor a szerzők erről az üzenetről értesülhetnek emailen. Szeretne használni ezt az opciót ?', # MODIF
	'info_pas_de_forum' => 'Nincs fórum',
	'info_question_visiteur_ajout_document_forum' => 'Souhaitez-vous autoriser les visiteurs à joindre des documents (images, sons...) à leurs messages de forums ?', # MODIF
	'info_question_visiteur_ajout_document_forum_format' => 'Le cas échéant, indiquer ci-dessous la liste des extensions de documents autorisés pour les forums (ex: gif, jpg, png, mp3).', # MODIF
	'info_selectionner_message' => 'Sélectionner les messages :', # NEW
	'interface_formulaire' => 'Interface formulaire', # NEW
	'interface_onglets' => 'Interface avec onglets', # NEW
	'item_activer_forum_administrateur' => 'Admninisztrátori fórum aktiválása',
	'item_config_forums_prive_global' => 'Activer le forum des rédacteurs', # NEW
	'item_config_forums_prive_objets' => 'Activer ces forums', # NEW
	'item_desactiver_forum_administrateur' => 'Az adminisztrátori fórumok inaktiválása',
	'item_non_config_forums_prive_global' => 'Désactiver le forum des rédacteurs', # NEW
	'item_non_config_forums_prive_objets' => 'Désactiver ces forums', # NEW

	// L
	'label_selectionner' => 'Sélectionner :', # NEW
	'lien_reponse_article' => 'Hozzászólás a cikkről',
	'lien_reponse_breve_2' => 'Hozzászólás a hírhez',
	'lien_reponse_message' => 'Réponse au message', # NEW
	'lien_reponse_rubrique' => 'Hozzászólás a rubrikához',
	'lien_reponse_site_reference' => 'Hozzászólás a felvett honlaphoz :', # MODIF
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
	'onglet_messages_internes' => 'Belső üzenetek',
	'onglet_messages_publics' => 'Nyilvános üzenetek',
	'onglet_messages_vide' => 'Szöveg nélküli üzenetek',

	// R
	'repondre_message' => 'Válaszolni erre az üzenetre',

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
	'texte_messages_publics' => 'Messages publics sur :', # NEW
	'titre_cadre_forum_administrateur' => 'Privát adminisztrátori fórum',
	'titre_cadre_forum_interne' => 'Belső fórum',
	'titre_config_forums_prive' => 'Forums de l’espace privé', # NEW
	'titre_forum' => 'Fórum',
	'titre_forum_suivi' => 'A fórumok megfigyelése',
	'titre_page_forum_suivi' => 'Fórumok megfigyelése',
	'titre_selection_action' => 'Sélection', # NEW
	'tout_voir' => 'Voir tous les messages', # NEW

	// V
	'voir_messages_objet' => 'voir les messages' # NEW
);

?>
