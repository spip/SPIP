<?php

// This is a SPIP language file  --  Ceci est un fichier langue de SPIP

$GLOBALS['i18n_en'] = array(


// 0
'0_URL' => 'http://listes.rezo.net/mailman/listinfo/spip-dev',
'0_langue' => 'english [en]',
'0_liste' => 'spip-en@rezo.net',
'0_mainteneur' => 'kandalaft@MCR1.POPTEL.ORG.UK',


// A
'avis_acces_interdit' => 'Access forbidden.',
'avis_archive_incorrect' => 'archive file is not a valid SPIP file',
'avis_archive_invalide' => 'archive file is not valid',
'avis_article_modifie' => 'Warning, @nom_auteur_modif@ has worked on this article @date_diff@ minutes ago',
'avis_attention' => 'CAUTION!',
'avis_aucun_resultat' => 'No results found.',
'avis_champ_incorrect_type_objet' => 'Invalid field name @name@ for object of type @type@',
'avis_chemin_invalide_1' => 'The path you have selected',
'avis_chemin_invalide_2' => 'does not seem valid. Please return to the previous page and verify the information provided.',
'avis_colonne_inexistante' => 'Column @col@ does not exist',
'avis_connexion_echec_1' => 'Connection to MySQL server failed.',
'avis_connexion_echec_2' => 'Go back to the previous page, and verify the information you have provided.',
'avis_connexion_echec_3' => '<B>N.B.</B> On a number of servers, you must <B>request</B> the activation of your access to MySQL database before you can use it. If you are not able to connect, make sure that you actually made this request.',
'avis_connexion_ldap_echec_1' => 'Connection to the LDAP server failed.',
'avis_connexion_ldap_echec_2' => 'Go back to the previous page, and verify the information you have provided.',
'avis_connexion_ldap_echec_3' => 'Alternatively, do not use LDAP support to import users.',
'avis_conseil_selection_mot_cle' => '<b>Important group:</b> It is very strongly advised to select a keyword in this group.',
'avis_deplacement_rubrique' => 'Warning! This section contains @contient_breves@ news item@scb@: if you move it , please check this box to confirm.',
'avis_destinataire_obligatoire' => 'You must select a recipient before sending this message.',
'avis_echec_syndication_01' => 'Syndication failed: either the selected backend is unreadable or it does not offer any article.',
'avis_echec_syndication_02' => 'Syndication failed: could not reach the backend of this site.',
'avis_erreur' => 'Error: see below\';',
'avis_erreur_connexion' => 'Connection error',
'avis_erreur_connexion_mysql' => 'MySQL connection error',
'avis_erreur_fonction_contexte' => 'Programming error. This function should not be called in this context.',
'avis_erreur_mysql' => 'MySQL error',
'avis_erreur_sauvegarde' => 'Error in backup (@type@ @id_objet@)!',
'avis_erreur_version_archive' => '<B>Warning! The file @archive@ corresponds to
				a version of SPIP other than the one you have
				installed.</B> You are facing great
				difficulties: risk of destruction of your database
				various dysfunctions in the site, etc. Do not
				submit this import request.<p>For more
				information, please refer to <A HREF=\'http://www.uzine.net/article1489.html\'>
                                the SPIP documentation</A>.',
'avis_espace_interdit' => '<B>Forbidden area</B><p>SPIP is already installed.',
'avis_lecture_noms_bases_1' => 'The installer could not read the names of the installed databases.',
'avis_lecture_noms_bases_2' => 'Either no database is available, or the feature allowing the listing of the databases was disabled
		for security reasons (which is the case with a lot of hosts).',
'avis_lecture_noms_bases_3' => 'In case the second alternative was true, it is possible that a database named after your login could be usable:',
'avis_non_acces_message' => 'You do not have access to this message.',
'avis_non_acces_page' => 'You do not have access to this page.',
'avis_operation_echec' => 'The operation failed.',
'avis_probleme_archive' => 'Reading error in file @archive@',
'avis_site_introuvable' => 'Site not found',
'avis_site_syndique_probleme' => 'Warning: the syndication of this site encountered a problem; consequently the system is temporarily interrupted. Please verify the address of this site\'s syndication file (<b>@url_syndic@</b>), and try again to perform a new recovery of information.',
'avis_sites_probleme_syndication' => 'These sites encountered a syndication problem',
'avis_sites_syndiques_probleme' => 'These syndicated sites generated a problem',
'avis_suppression_base' => 'WARNING, data deletion is irreversible',
'avis_version_mysql' => 'Your version of MySQL (@version_mysql@) does not allow auto repair of the database tables.',


// B
'bouton_acces_ldap' => 'Add an access to LDAP >>',
'bouton_ajouter' => 'Add',
'bouton_ajouter_document' => 'ADD A DOCUMENT',
'bouton_ajouter_image' => 'ADD AN IMAGE',
'bouton_annonce' => 'ANNOUNCEMENT',
'bouton_changer' => 'Submit',
'bouton_checkbox_envoi_message' => 'possibility to send a message',
'bouton_checkbox_indiquer_site' => 'You must enter the name of a Web site',
'bouton_checkbox_qui_attribue_mot_cle_administrateurs' => 'site administrators',
'bouton_checkbox_qui_attribue_mot_cle_redacteurs' => 'editors',
'bouton_checkbox_qui_attribue_mot_cle_visiteurs' => 'visitors of the public site when they post a message on a forum.',
'bouton_checkbox_signature_unique_email' => 'only one signature per e-mail address',
'bouton_checkbox_signature_unique_site' => 'only one signature per Web site',
'bouton_chercher' => 'Search',
'bouton_choisir' => 'Select',
'bouton_demande_publication' => 'Request the publication of this article',
'bouton_effacer_index' => 'Delete indexing',
'bouton_effacer_tout' => 'Delete ALL',
'bouton_envoi_message_02' => 'SEND A MESSAGE',
'bouton_envoyer_message' => 'Final message: send',
'bouton_forum_petition' => 'FORUM & PETITION',
'bouton_modifier' => 'Modify',
'bouton_pense_bete' => 'PERSONAL MEMO',
'bouton_radio_activer_messagerie' => 'Enable internal messaging',
'bouton_radio_activer_messagerie_interne' => 'Enable internal messaging',
'bouton_radio_activer_petition' => 'Activating the petition',
'bouton_radio_afficher' => 'Show',
'bouton_radio_apparaitre_liste_redacteurs_connectes' => 'Appear in the list of connected editors',
'bouton_radio_articles_futurs' => 'to future articles only (no action on the database).',
'bouton_radio_articles_tous' => 'to all articles without any exception.',
'bouton_radio_articles_tous_sauf_forum_desactive' => 'to all articles, except those with disabled forums.',
'bouton_radio_desactiver_messagerie' => 'Disable messaging',
'bouton_radio_desactiver_messagerie_interne' => 'Disable internal messaging',
'bouton_radio_enregistrement_obligatoire' => 'Compulsory registration (
		users must subscribe by providing their e-mail address before
		being able to post contributions).',
'bouton_radio_envoi_annonces' => 'Send editorial announcements',
'bouton_radio_envoi_annonces_adresse' => 'Send announcements to the address:',
'bouton_radio_envoi_liste_nouveautes' => 'Send latest news list',
'bouton_radio_moderation_priori' => 'Beforehand moderation (
	contributions will be shown only after validation by
	administrators).',
'bouton_radio_modere_abonnement' => 'moderation by subscription',
'bouton_radio_modere_posteriori' => 'afterwards moderation',
'bouton_radio_modere_priori' => 'beforehand moderation',
'bouton_radio_non_apparaitre_liste_redacteurs_connectes' => 'Do not appear in the list of connected editors',
'bouton_radio_non_envoi_annonces' => 'Do not send any announcements',
'bouton_radio_non_envoi_annonces_editoriales' => 'Do not send any editorial announcements',
'bouton_radio_non_envoi_liste_nouveautes' => 'Do not send latest news list',
'bouton_radio_non_syndication' => 'No syndication',
'bouton_radio_occidental' => 'Western alphabet (<tt>iso-8859-1</tt>): supported by all browsers, but only

		displays West European languages (English, French, German...).',
'bouton_radio_pas_petition' => 'No petition',
'bouton_radio_personnalise' => 'Custom character set: choose this option if you want

		to use a specific character set',
'bouton_radio_petition_activee' => 'Petition activated',
'bouton_radio_publication_immediate' => 'Immediate publication of messages
	(contributions will be shown the moment they are sent, administrators can
	delete them then).',
'bouton_radio_sauvegarde_compressee' => 'save as compressed in <b>ecrire/data/dump.xml.gz</b>',
'bouton_radio_sauvegarde_non_compressee' => 'save as uncompressed in <b>ecrire/data/dump.xml</b>',
'bouton_radio_supprimer_petition' => 'Delete the petition',
'bouton_radio_syndication' => 'Syndication:',
'bouton_radio_universel' => 'Universal alphabet (<tt>utf-8</tt>): displays all the languages but not supported
		by all browsers at the time being.',
'bouton_recharger_page' => 'reload this page',
'bouton_redirection' => 'REDIRECT',
'bouton_relancer_installation' => 'Re-launch installation',
'bouton_restaurer_base' => 'Restore the database',
'bouton_suivant' => 'Next',
'bouton_telecharger' => 'Upload',
'bouton_tenter_recuperation' => 'Repairing attempt',
'bouton_test_proxy' => 'Test the proxy',
'bouton_valider' => 'Submit',
'bouton_vider_cache' => 'Empty the cache',
'bouton_voir_message' => 'Preview message before validating',


// D
'date_avant_jc' => 'B.C.',
'date_fmt_heures_minutes' => '@h@h@m@min',
'date_fmt_jour_heure' => '@jour@ at @heure@',
'date_fmt_jour_mois' => '@nommois@ @jour@',
'date_fmt_jour_mois_annee' => '@nommois@ @jour@, @annee@',
'date_fmt_mois_annee' => '@nommois@ @annee@',
'date_jour_1' => 'Sunday',
'date_jour_2' => 'Monday',
'date_jour_3' => 'Tuesday',
'date_jour_4' => 'Wednesday',
'date_jour_5' => 'Thursday',
'date_jour_6' => 'Friday',
'date_jour_7' => 'Saturday',
'date_mois_1' => 'January',
'date_mois_10' => 'October',
'date_mois_11' => 'November',
'date_mois_12' => 'December',
'date_mois_2' => 'February',
'date_mois_3' => 'March',
'date_mois_4' => 'April',
'date_mois_5' => 'May',
'date_mois_6' => 'June',
'date_mois_7' => 'July',
'date_mois_8' => 'August',
'date_mois_9' => 'September',
'date_mot_heures' => 'H',
'date_saison_1' => 'winter',
'date_saison_2' => 'spring',
'date_saison_3' => 'summer',
'date_saison_4' => 'autumn',
'dirs_commencer' => '<NEW>  afin de commencer r&eacute;ellement l\'installation',
'dirs_preliminaire' => '<NEW> Pr&eacute;liminaire : <B>R&eacute;gler les droits d\'acc&egrave;s</B>',
'dirs_probleme_droits' => '<NEW> <b>Probl&egrave;me de droits d\'acc&egrave;s</b>',
'dirs_repertoires_suivants' => '<NEW> <B>Les r&eacute;pertoires suivants ne sont pas accessibles en &eacute;criture&nbsp;: <ul>@bad_dirs@.</ul> </B>
		<P>Pour y rem&eacute;dier, utilisez votre client FTP afin de r&eacute;gler les droits d\'acc&egrave;s de chacun
		de ces r&eacute;pertoires. La proc&eacute;dure est expliqu&eacute;e en d&eacute;tail dans le guide d\'installation.
		<P>Une fois cette manipulation effectu&eacute;e, vous pourrez ',


// E
'email' => 'e-mail',
'email_2' => 'e-mail:',
'entree_adresse_annuaire' => 'Directory\'s address',
'entree_adresse_email' => 'Your e-mail address',
'entree_adresse_fichier_syndication' => 'Address of &laquo;backend&raquo; file for syndication:',
'entree_adresse_site' => '<b>Site URL</b> [Compulsory]',
'entree_base_donnee_1' => 'Database address',
'entree_base_donnee_2' => '(Often, this address matches the address of your site, sometimes it corresponds to the name &laquo;localhost&raquo;, and sometimes it is left completely empty.)',
'entree_biographie' => 'Short biography in a few words.',
'entree_breve_publiee' => 'Is this news item to be published?',
'entree_chemin_acces' => '<B>Enter</B> the path:',
'entree_cle_pgp' => 'Your PGP key',
'entree_contenu_rubrique' => '(Content of the section in a few words.)',
'entree_description_site' => 'Site description',
'entree_dimensions' => 'Size:',
'entree_identifiants_connexion' => 'Your connection identifiers...',
'entree_informations_connexion_ldap' => 'Please fill this form with the LDAP connection information. You will be provided with this information by your system or network administrator.',
'entree_infos_perso' => 'Who are you?',
'entree_interieur_rubrique' => 'In section:',
'entree_liens_sites' => '<B>Hypertext link</B> (reference, site to visit...)',
'entree_login' => 'Your login',
'entree_login_connexion_1' => 'Connection login',
'entree_login_connexion_2' => '(Sometimes matches your FTP access login and sometimes left empty)',
'entree_login_ldap' => 'Initial LDAP login',
'entree_mot_passe' => 'Your password',
'entree_mot_passe_1' => 'Connection password',
'entree_mot_passe_2' => '(Sometimes matches your FTP access password and sometimes left empty)',
'entree_nom_fichier' => 'Please enter the filename @texte_compresse@:',
'entree_nom_pseudo' => 'Your name or alias',
'entree_nom_pseudo_1' => '(Your name or alias)',
'entree_nom_site' => 'Your site\'s name',
'entree_nouveau_passe' => 'New password',
'entree_passe_ldap' => 'Password',
'entree_port_annuaire' => 'Port number of the directory',
'entree_signature' => 'Signature',
'entree_texte_breve' => 'Text of news item',
'entree_titre_document' => 'Document title:',
'entree_titre_image' => 'Image title:',
'entree_titre_obligatoire' => '<B>Title</b> [Compulsory]<BR>',
'entree_url' => 'Your site\'s URL',


// F
'form_deja_inscrit' => '<NEW> Vous &ecirc;tes d&eacute;j&agrave; inscrit.',
'form_email_non_valide' => '<NEW> Votre adresse email n\'est pas valide.',
'form_forum_access_refuse' => '<NEW> Vous n\'avez plus acc&egrave;s &agrave; ce site.',
'form_forum_bonjour' => '<NEW> Bonjour,',
'form_forum_email_deja_enregistre' => '<NEW> Cette adresse e-mail est d&eacute;j&agrave; enregistr&eacute;e, vous pouvez donc utiliser votre mot de passe habituel.',
'form_forum_identifiant_mail' => '<NEW> Votre nouvel identifiant vient de vous &ecirc;tre envoy&eacute; par email.',
'form_forum_identifiants' => '<NEW> Identifiants personnels',
'form_forum_indiquer_nom_email' => '<NEW> Indiquez ici votre nom et votre adresse email. Votre identifiant personnel vous parviendra rapidement, par courrier &eacute;lectronique.',
'form_forum_login' => '<NEW> login :',
'form_forum_message_auto' => '<NEW> (ceci est un message automatique)',
'form_forum_pass' => '<NEW> mot de passe :',
'form_forum_probleme_mail' => '<NEW> Probl&egrave;me de mail&nbsp;: l\'identifiant ne peut pas &ecirc;tre envoy&eacute;.',
'form_forum_voici1' => '<NEW> Voici vos identifiants pour pouvoir participer aux forums
du site \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\"@nom_site_spip@\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\" (@adresse_site@/) :',
'form_forum_voici2' => '<NEW> Voici vos identifiants pour proposer des articles sur
le site \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\"@nom_site_spip@\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\" (@adresse_site@/ecrire/) :',
'form_indiquer_email' => '<NEW> Veuillez indiquer votre adresse email.',
'form_indiquer_nom' => '<NEW> Veuillez indiquer votre nom.',
'form_indiquer_nom_site' => '<NEW> Veuillez indiquer le nom de votre site.',
'form_pet_adresse_site' => '<NEW> Adresse de votre site',
'form_pet_aucune_signature' => '<NEW> Aucune signature ne correspond &agrave; ce code...',
'form_pet_confirmation' => '<NEW> Veuillez confirmer votre signature :',
'form_pet_deja_enregistre' => '<NEW> Ce site est d&eacute;j&agrave; enregistr&eacute;',
'form_pet_deja_signe' => '<NEW> Vous avez d&eacute;j&agrave; sign&eacute; ce texte.',
'form_pet_envoi_mail_confirmation' => '<NEW> Un courrier &eacute;lectronique de confirmation vient de vous &ecirc;tre envoy&eacute;. Vous devrez visiter l\'adresse Web mentionn&eacute;e dans ce courrier pour valider votre signature.',
'form_pet_mail_confirmation' => '<NEW> Bonjour,

Vous avez demand&eacute; &agrave; signer la p&eacute;tition :
@titre@.

Vous avez fourni les informations suivantes :
    Nom: @nom_email@
    Site: @nom_site@ - @url_site@


IMPORTANT...
Pour valider votre signature, il suffit de vous connecter &agrave;
l\'adresse ci-dessous (dans le cas contraire, votre demande
sera rejet&eacute;e) :

    @url@


Merci de votre participation
',
'form_pet_message_commentaire' => '<NEW> Un message, un commentaire&nbsp;?',
'form_pet_nom_site2' => '<NEW> <B>Nom de votre site Web',
'form_pet_probleme_technique' => '<NEW> Probl&egrave;me technique, les signatures sont temporairement suspendues.',
'form_pet_signature_pasprise' => '<NEW> Votre signature n\'est pas prise en compte.',
'form_pet_signature_validee' => '<NEW> Votre signature est valid&eacute;e. Elle appara&icirc;tra lors de la prochaine mise &agrave; jour du site. Merci&nbsp;!',
'form_pet_site_deja_enregistre' => '<NEW> Ce site est d&eacute;j&agrave; enregistr&eacute;',
'form_pet_url_invalide' => '<NEW> L\'URL que vous avez indiqu&eacute;e n\'est pas valide.',
'form_pet_votre_email' => '<NEW> Votre adresse email',
'form_pet_votre_nom' => '<NEW> Votre nom ou pseudo',
'form_pet_votre_site' => '<NEW> Si vous avez un site Web, vous pouvez l\'indiquer ci-dessous',
'form_prop_confirmer_envoi' => '<NEW> Confirmer l\'envoi',
'form_prop_description' => '<NEW> Description/commentaire',
'form_prop_enregistre' => '<NEW> Votre proposition est enregistr&eacute;e, elle appara&icirc;tra en ligne apr&egrave;s validation par les responsables de ce site.',
'form_prop_envoyer' => '<NEW> Envoyer un message',
'form_prop_indiquer_email' => '<NEW> Veuillez indiquer une adresse email valide',
'form_prop_indiquer_nom_site' => '<NEW> Veuillez indiquer le nom du site.',
'form_prop_indiquer_sujet' => '<NEW> Veuillez indiquer un sujet',
'form_prop_message_envoye' => '<NEW> Message envoy&eacute;',
'form_prop_nom_site' => '<NEW> Nom du site',
'form_prop_non_enregistre' => '<NEW> Votre proposition n\'a pas &eacute;t&eacute; enregistr&eacute;e.',
'form_prop_sujet' => '<NEW> Sujet',
'form_prop_url_site' => '<NEW> Adresse (URL) du site',
'forum_acces_refuse' => '<NEW> Vous n\'avez plus acc&egrave;s &agrave; ces forums.',
'forum_attention_dix_caracteres' => '<NEW> <b>Attention&nbsp;!</b> votre message fait moins de dix caract&egrave;res.',
'forum_attention_trois_caracteres' => '<NEW> <b>Attention&nbsp;!</b> votre titre fait moins de trois caract&egrave;res.',
'forum_avez_selectionne' => '<NEW> Vous avez s&eacute;lectionn&eacute;&nbsp;:',
'forum_cliquer_retour' => '<NEW> Cliquez <a href=\'@retour_forum@\'>ici</a> pour continuer.',
'forum_creer_paragraphes' => '<NEW> (Pour cr&eacute;er des paragraphes, laissez simplement des lignes vides.)',
'forum_forum' => '<NEW> forum',
'forum_info_modere' => '<NEW> Ce forum est mod&eacute;r&eacute; &agrave; priori&nbsp;: votre contribution n\'appara&icirc;tra qu\'apr&egrave;s avoir &eacute;t&eacute; valid&eacute;e par un administrateur du site.',
'forum_lien_hyper' => '<NEW> <B>Lien hypertexte</B> (optionnel)',
'forum_message_definitif' => '<NEW> Message d&eacute;finitif : envoyer au site',
'forum_message_trop_long' => '<NEW> Votre message est trop long. La taille maximale est de 20000 caract&egrave;res.',
'forum_ne_repondez_pas' => '<NEW> Ne repondez pas a ce mail mais sur le forum a l\'adresse suivante :',
'forum_non_inscrit' => '<NEW> Vous n\'&ecirc;tes pas inscrit, ou l\'adresse ou le mot de passe sont erron&eacute;s.',
'forum_page_url' => '<NEW> (Si votre message se r&eacute;f&egrave;re &agrave; un article publi&eacute; sur le Web, ou &agrave; une page fournissant plus d\'informations, veuillez indiquer ci-apr&egrave;s le titre de la page et son adresse URL.)',
'forum_par_auteur' => '<NEW> par @auteur@',
'forum_poste_par' => '<NEW> Message poste@parauteur@ a la suite de votre article.',
'forum_probleme_database' => '<NEW> Probl&egrave;me de base de donn&eacute;es, votre message n\'a pas &eacute;t&eacute; enregistr&eacute;.',
'forum_qui_etes_vous' => '<NEW> <B>Qui &ecirc;tes-vous ?</B> (optionnel)',
'forum_texte' => '<NEW> Texte de votre message :',
'forum_titre' => '<NEW> Titre :',
'forum_titre_erreur' => '<NEW> Erreur...',
'forum_url' => '<NEW> URL :',
'forum_valider' => '<NEW> Valider ce choix',
'forum_voir_avant' => '<NEW> Voir ce message avant de le poster',
'forum_votre_email' => '<NEW> Votre adresse email :',
'forum_votre_nom' => '<NEW> Votre nom (ou pseudonyme) :',
'forum_vous_enregistrer' => '<NEW> Pour participer &agrave;
		ce forum, vous devez vous enregistrer au pr&eacute;alable. Merci
		d\'indiquer ci-dessous l\'identifiant personnel qui vous a
		&eacute;t&eacute; fourni. Si vous n\'&ecirc;tes pas enregistr&eacute;, vous devez',
'forum_vous_inscrire' => '<NEW> vous inscrire </a> au pr&eacute;alable.',


// I
'icone_a_suivre' => 'Launch pad',
'icone_activer_cookie' => 'Place a cookie',
'icone_admin_site' => 'Site administration',
'icone_afficher_auteurs' => 'Show authors',
'icone_afficher_visiteurs' => 'Show visitors',
'icone_aide_ligne' => 'Online help',
'icone_arret_discussion' => 'Stop participation to this discussion',
'icone_articles' => 'Articles',
'icone_auteurs' => 'Authors',
'icone_breves' => 'News',
'icone_calendrier' => 'Calendar',
'icone_configuration_site' => 'Site configuration',
'icone_configurer_site' => 'Configure your site',
'icone_creation_groupe_mots' => 'Create a new keywords group',
'icone_creation_mots_cles' => 'Create a new keyword',
'icone_creer_auteur' => 'Create a new author and associate him with this article',
'icone_creer_mot_cle' => 'Create a new keyword and link it to this article',
'icone_creer_nouvel_auteur' => 'Create a new author',
'icone_creer_rubrique' => 'Create a section',
'icone_creer_rubrique_2' => 'Create a new section',
'icone_creer_sous_rubrique' => 'Create a sub-section',
'icone_deconnecter' => 'Disconnect',
'icone_doc_rubrique' => 'Sections documents',
'icone_ecrire_article' => 'Write a new article',
'icone_ecrire_nouvel_article' => 'News in this section',
'icone_edition_site' => 'Site edit',
'icone_envoyer_message' => 'Send this message',
'icone_evolution_visites' => 'Visits evolution<br>@visites@ visits@aff_ref@',
'icone_evolution_visites_2' => 'Visits evolution',
'icone_forum_administrateur' => 'Administrators\' forum',
'icone_forum_interne' => 'Internal forum',
'icone_forum_suivi' => 'Forums follow-up',
'icone_forums_petitions' => 'Forums / petitions',
'icone_informations_personnelles' => 'Personal information',
'icone_interface_complet' => 'complete interface',
'icone_interface_simple' => 'Simplified interface',
'icone_maintenance_site' => 'Site maintenance',
'icone_messagerie_personnelle' => 'Personal messaging',
'icone_modif_groupe_mots' => 'Modify this keywords group',
'icone_modifier_article' => 'Modify this article',
'icone_modifier_breve' => 'Modify this news item',
'icone_modifier_informations_personnelles' => 'Modify your personal information',
'icone_modifier_message' => 'Modify this message',
'icone_modifier_rubrique' => 'Modify this section',
'icone_modifier_site' => 'Modify this site',
'icone_mots_cles' => 'Keywords',
'icone_nouvelle_breve' => 'Write a new news item',
'icone_poster_message' => 'Post a message',
'icone_publier_breve' => 'Publish this news item',
'icone_referencer_nouveau_site' => 'Reference a new site',
'icone_refuser_breve' => 'Reject this news item',
'icone_repartition_actuelle' => 'Show actual distribution',
'icone_repartition_debut' => 'Show distribution from the start',
'icone_retour' => 'Back',
'icone_retour_article' => 'Back to the article',
'icone_rubriques' => 'Sections',
'icone_sauver_site' => 'Site backup',
'icone_site_entier' => 'The entire site',
'icone_sites_references' => 'Referenced sites',
'icone_statistiques' => 'Site statistics',
'icone_statistiques_visites' => 'Visits statistics',
'icone_suivi_forum' => 'Follow-up of public forum: @nb_forums@ contribution(s)',
'icone_suivi_forums' => 'Follow-up/manage forums',
'icone_suivi_pettions' => 'Follow-up/manage petitions',
'icone_supprimer_cookie' => 'Delete cookie',
'icone_supprimer_document' => 'Delete this document',
'icone_supprimer_groupe_mots' => 'Delete this group',
'icone_supprimer_image' => 'Delete this image',
'icone_supprimer_message' => 'Delete this message',
'icone_supprimer_rubrique' => 'Delete this section',
'icone_supprimer_signature' => 'Delete this signature',
'icone_tous_articles' => 'All your articles',
'icone_tous_auteur' => 'All the authors',
'icone_valider_message' => 'Validate this message',
'icone_valider_signature' => 'Validate this signature',
'icone_visiter_site' => 'Visit the site',
'icone_voir_en_ligne' => 'View online',
'icone_voir_sites_references' => 'Show referenced sites',
'icone_voir_tous_mots_cles' => 'Show all keywords',
'image_administrer_rubrique' => 'You can manage this section',
'info_1_article' => '1 article',
'info_1_breve' => '1 news item',
'info_1_site' => '1 site',
'info_a_suivre' => 'LAUNCH PAD&raquo;',
'info_a_valider' => '[to be validated]',
'info_acces_interdit' => 'Access forbidden',
'info_acces_refuse' => 'Access denied',
'info_action' => 'Action: @action@',
'info_activer_cookie' => 'You can activate an <b>administration cookie</b>, which allows you

 to switch easily between the public site and the private area.',
'info_activer_forum_public' => '<I>To enable public forums, please choose their default mode of moderation:</I>',
'info_admin_gere_rubriques' => 'This administrator manages the following sections:',
'info_admin_gere_toutes_rubriques' => 'This administrator manages <b>all the sections</b>.',
'info_administrateur' => 'Administrator',
'info_administrateur_1' => 'Administrator',
'info_administrateur_2' => 'of the site (<i>use with caution</i>)',
'info_administrateur_site_01' => 'If you are a site administrator, please',
'info_administrateur_site_02' => 'click on this link',
'info_administrateurs' => 'Administrators',
'info_administrer_rubrique' => 'You can manage this section',
'info_administrer_rubriques' => 'You can manage this section and its sub-sections',
'info_adresse' => 'to the address:',
'info_adresse_email' => 'E-MAIL ADDRESS:',
'info_adresse_non_indiquee' => 'You did not specify an address to test!',
'info_adresse_url' => 'Your public site\'s URL',
'info_afficher_visites' => 'Show visits for:',
'info_affichier_visites_articles_plus_visites' => 'Show visits for <b>the most visited articles since the beginning:</b>',
'info_aide' => 'HELP:',
'info_aide_en_ligne' => 'SPIP Online Help',
'info_ajout_image' => 'When you add images as attached documents to an article,
		SPIP can automatically create vignettes (thumbnails) from
		inserted images. This will allow, for instance, the automated
		creation of a gallery or a portfolio.',
'info_ajout_participant' => 'The following participant has been added:',
'info_ajouter_mot' => 'Add this keyword',
'info_ajouter_rubrique' => 'Add another section to manage:',
'info_annonce' => 'ANNOUNCEMENT',
'info_annonce_nouveautes' => 'Latest news announcements',
'info_annonces_generales' => 'General announcements:',
'info_annuler_blocage_priori' => 'Cancel
				this beforehand blocking',
'info_anterieur' => 'previous',
'info_appliquer_choix_moderation' => 'Apply this choice of moderation:',
'info_article' => 'article',
'info_article_2' => 'articles',
'info_article_a_paraitre' => 'Post-dated articles to be published',
'info_article_propose' => 'Article submitted',
'info_article_publie' => 'Article published',
'info_article_redaction' => 'Article in progress',
'info_article_refuse' => 'Article rejected',
'info_article_supprime' => 'Article deleted',
'info_articles' => 'Articles',
'info_articles_02' => 'articles',
'info_articles_2' => 'Articles',
'info_articles_a_valider' => 'Articles to be validated',
'info_articles_auteur' => 'This author\'s articles',
'info_articles_lies_mot' => 'Articles associated with this keyword',
'info_articles_proposes' => 'Articles submitted',
'info_articles_trouves' => 'Articles found',
'info_articles_trouves_dans_texte' => 'Articles found (in the text)',
'info_attente_validation' => 'Your articles pending validation',
'info_aujourdhui' => 'today:',
'info_auteur_message' => 'MESSAGE SENDER:',
'info_auteurs' => 'Authors',
'info_auteurs_nombre' => 'author(s):',
'info_auteurs_par_tri' => 'Authors@partri@',
'info_auteurs_trouves' => 'Authors found',
'info_authentification_externe' => 'External authentication',
'info_authentification_ftp' => 'Authentication (by FTP).',
'info_avertissement' => 'Warning',
'info_base_installee' => 'The structure of your database is installed.',
'info_base_restauration' => 'Restoration of the database in progress.',
'info_bloquer_lien' => 'block this link',
'info_breves' => 'Does your site use the news system?',
'info_breves_02' => 'News',
'info_breves_03' => 'news items',
'info_breves_2' => 'news',
'info_breves_liees_mot' => 'News associated with this keyword',
'info_breves_touvees' => 'News items found',
'info_breves_touvees_dans_texte' => 'News items found (in the text)',
'info_breves_valider' => 'News items to be validated',
'info_changer_nom_groupe' => 'Change the name of this group:',
'info_chapeau' => 'Deck',
'info_chapeau_2' => 'Deck:',
'info_chemin_acces_1' => 'Options: <B>Access path in directory</b>',
'info_chemin_acces_2' => 'From now on, you have to configure the access path to the directory information. This information is essential to read the users profiles stored in the directory.',
'info_chemin_acces_annuaire' => 'Options: <B>Access path in directory</B>',
'info_choix_base' => 'Third step:',
'info_classement_1' => '<sup>st</sup> out of @liste@',
'info_classement_2' => '<sup>th</sup> out of @liste@',
'info_code_acces' => 'Do not forget your own access codes!',
'info_comment_lire_tableau' => 'How to read this graphic',
'info_confirmer_passe' => 'Confirm new password:',
'info_connexion_base' => 'Second step: <B>Attempting to connect to database</B>',
'info_connexion_ldap_ok' => '<B>Your LDAP connection succeeded.</B><P> You can go to the next step.',
'info_connexion_mysql' => 'First step: <B>Your MySQL connection</b>',
'info_connexion_ok' => 'Connection succeeded.',
'info_connexion_refusee' => 'Connection denied',
'info_contact' => 'Contact',
'info_contact_developpeur' => 'Please contact a developer.',
'info_contenance' => 'This site contains:',
'info_contenu_articles' => 'Articles content',
'info_contribution' => 'forum contributions',
'info_copyright' => 'is a free software distributed <a href=\'license.txt\'>under GPL license.</a>',
'info_cours_edition' => 'In progress',
'info_creation_mots_cles' => 'Create and configure the site\'s keywords here',
'info_creation_paragraphe' => '(To create paragraphs, you simply leave blank lines.)',
'info_creation_rubrique' => 'Before being able to write articles,<BR> you must create at least one section.<BR>',
'info_creation_tables' => 'Fourth step: <B>Creation of the database tables</b>',
'info_creer_base' => '<B>Create</b> a new database:',
'info_creer_repertoire' => 'Please create a file or a directory called:',
'info_creer_repertoire_2' => 'inside the sub-directory <b>ecrire/data/</b>, then',
'info_dans_espace_prive' => '[in the private area]',
'info_dans_groupe' => 'In group:',
'info_dans_rubrique' => 'In section:',
'info_date_publication_anterieure' => 'Date of earlier publication:',
'info_date_referencement' => 'DATE OF REFERENCING THIS SITE:',
'info_delet_mots_cles' => 'You requested the deletion of keyword

<B>@titre_mot@</b> (@type_mot@). This keyword being linked to

<b>@texte_lie@</b>you must confirm this decision:',
'info_demander_blocage_priori' => 'Request
				a blocking beforehand',
'info_deplier' => 'Unfold',
'info_derniere_etape' => 'Last step: <B>It is completed!',
'info_derniere_syndication' => 'The last syndication of this site was carried out on',
'info_derniers_articles_publies' => 'Your latest published articles',
'info_desactiver_forum_public' => 'Disable the use of public

	forums. Public forums could be allowed on a case by case

	basis for the articles; they will be forbidden for the sections, news, etc.',
'info_desactiver_messagerie_personnelle' => 'You can enable or disable your personal messaging on this site.',
'info_descriptif' => 'Description:',
'info_descriptif_nombre' => 'description(s):',
'info_description' => 'Description:',
'info_description_2' => 'Description:',
'info_dimension' => 'Size:',
'info_discussion_cours' => 'Discussions in progress',
'info_document' => 'Document',
'info_donner_rendez_vous' => 'This button allows you to fix an appointment with another participant.',
'info_echange_message' => 'SPIP allows the exchange of messages and the creation of private
		discussion forums between participants to the site. You can enable or
		disable this feature.',
'info_ecire_message_prive' => 'Write a private message',
'info_ecrire_article' => 'Before being able to write articles, you must create at least one section.',
'info_email_invalide' => 'Invalid e-mail address.',
'info_email_webmestre' => 'Webmaster\'s e-mail address (optional)',
'info_en_cours_validation' => 'Your articles in progress',
'info_en_ligne' => 'Online now:',
'info_en_test_1' => '&nbsp;(testing)',
'info_en_test_2' => '(testing)',
'info_entrer_code_alphabet' => 'Enter the code of the character set to be used:',
'info_envoi_email_automatique' => 'Automated mailing',
'info_envoi_forum' => 'Send forums to articles authors',
'info_envoyer_maintenant' => 'Send now',
'info_envoyer_message_prive' => 'Send a private message to this author',
'info_erreur_requete' => 'Error in query:',
'info_erreur_restauration' => 'Restoration error: file not found.',
'info_etape_suivante' => 'Go to the next step',
'info_etape_suivante_1' => 'You can move to the next step.',
'info_etape_suivante_2' => 'You can move to the next step.',
'info_exportation_base' => 'export database to @archive@',
'info_facilite_suivi_activite' => 'To ease the follow-up of the site\'s editorial;
		activities, SPIP sends by mail, to an editor\'s mailing list for instance,
		the announcement of
		publications requests and articles validations.',
'info_fichiers_authent' => 'Authentication file ".htpasswd"',
'info_fini' => 'It is finished!',
'info_fonctionnement_forum' => 'Forum operation:',
'info_format_image' => 'Image format to be used for the creation of vignettes: @gd_formats@.',
'info_format_non_defini' => 'undefined format',
'info_forum_administrateur' => 'administrators\' forum',
'info_forum_interne' => 'internal forum',
'info_forum_ouvert' => 'In the site\'s private area, a forum is open to all
		registered editors. Below, you can enable an
		extra forum reserved for the administrators.',
'info_forum_statistiques' => 'Visits statistics',
'info_gauche_admin_effacer' => '<B>Only administrators have access to this page.</B><P> It provides access to various technical maintenance tasks. Some of them give rise to a specific authentication process requiring an FTP access to the Web site.',
'info_gauche_admin_tech' => '<B>Only administrators have access to this page.</B><P> It provides access to various
maintenance tasks. Some of them give rise to a specific authentication process
requiring an FTP access to the Web site.',
'info_gauche_admin_vider' => '<B>Only administrators have access to this page.</B><P> It provides access to various
maintenance tasks. Some of them give rise to a specific authentication process
requiring an FTP access to the Web site.',
'info_gauche_auteurs' => 'You will find here all the site\'s authors.
	The status of each one is indicated by the colour of his icon (editor = green; administrator = yellow).',
'info_gauche_auteurs_exterieurs' => 'External authors, without any access to the site, are indicated by a blue icon;

		deleted authors by a dustbin.',
'info_gauche_messagerie' => 'Messaging allows you to exchange messages amongst editors, to preserve memos (for your personal use) or to display announcements on the homepage of the private area (if you are an administrator).',
'info_gauche_numero_auteur' => 'AUTHOR NUMBER:',
'info_gauche_numero_breve' => 'NEWS ITEM NUMBER',
'info_gauche_statistiques_referers' => 'This page displays the list of <I>referrers</I>, i.e. the sites containing links to your own site, only for today: actually this list is initialised every 24 hours.',
'info_gauche_suivi_forum' => 'The <I>forums follow-up</i> page is a management tool of your site (not a discussion or editing area). It displays all the contributions of the public forum of this article and allows you to manage these contributions.',
'info_gauche_suivi_forum_2' => 'The <I>forums follow-up</I> page is a management tool of your site (not a discussion or editing). It displays all the contributions of the public forum of this article and allows you to manage these contributions.',
'info_gauche_visiteurs_enregistres' => 'You will find here the visitors registered
	in the public area of the site (forums by subscription).',
'info_generation_miniatures_images' => 'Generating images thumbnails',
'info_grand_ecran' => 'Large display',
'info_groupe_important' => 'Important group',
'info_hebergeur_desactiver_envoi_email' => 'Some hosts disable automated mail sending
		on their servers. In this case the following features
		of SPIP cannot be implemented.',
'info_hier' => 'yesterday:',
'info_identification_publique' => 'Your public identity...',
'info_image_aide' => 'HELP',
'info_impossible_lire_page' => '<B>Error!</b> The page could not be read <tt><html>@test_proxy@</html></tt> through the proxy <tt>',
'info_inclusion_directe' => 'Direct inclusion:',
'info_inclusion_vignette' => 'Include vignette:',
'info_informations_personnelles' => 'Fifth step: <B>Personal information</B>',
'info_inscription_automatique' => 'Automated registration of new editors',
'info_installation_systeme_publication' => 'Publication system installation ...',
'info_installer_documents' => 'You can automatically install all the documents which are in the folder <i>upload</i>.',
'info_installer_ftp' => 'As an administrator, you can install (by FTP) files in the folder ecrire/upload in order to select them later directly from here.',
'info_installer_images' => 'You can install images of formats JPEG, GIF et PNG.',
'info_installer_images_dossier' => 'Install images in folder /ecrire/upload to be able to select them here.',
'info_installer_tous_documents' => 'Install all the documents',
'info_interface_complete' => 'complete interface',
'info_interface_simple' => 'Simplified interface',
'info_jeu_caractere' => 'Character set of the site',
'info_joindre_document_article' => 'You can attach to your article documents of types',
'info_joindre_document_rubrique' => 'You can add to this section documents of types',
'info_joindre_documents_article' => 'You can attach to your article documents of types:',
'info_l_article' => 'the article',
'info_la_breve' => 'the news item',
'info_la_rubrique' => 'the section',
'info_laisser_champs_vides' => 'leave these fields empty)',
'info_langue_defaut' => 'Default language:',
'info_langue_principale' => 'Main site language',
'info_langues' => 'Site\'s languages',
'info_langues_proposees' => 'Available languages:',
'info_largeur_vignette' => '@largeur_vignette@ x @hauteur_vignette@ pixels',
'info_ldap_ok' => 'LDAP authentication is installed.',
'info_les_auteurs_1' => 'by @les_auteurs@',
'info_les_auteurs_2' => 'on',
'info_lettre_heures' => ':',
'info_lien' => 'link:',
'info_lien_hypertexte' => 'Hypertext link:',
'info_liens_syndiques_1' => 'syndicated links',
'info_liens_syndiques_2' => 'pending validation.',
'info_liens_syndiques_3' => 'forums',
'info_liens_syndiques_4' => 'are',
'info_liens_syndiques_5' => 'forum',
'info_liens_syndiques_6' => 'is',
'info_liens_syndiques_7' => 'pending validation.',
'info_liste_redacteurs_connectes' => 'List of connected editors',
'info_login_existant' => 'This login already exists.',
'info_login_trop_court' => 'Login too short.',
'info_mail_fournisseur' => 'you@isp.com',
'info_maximum' => 'maximum:',
'info_message' => 'Message from',
'info_message_efface' => 'MESSAGE DELETED',
'info_message_en_redaction' => 'Your messages in progress',
'info_message_supprime' => 'MESSAGE DELETED',
'info_message_technique' => 'Technical message:',
'info_messagerie_interne' => 'Internal messaging',
'info_mise_a_niveau_base' => 'MySQL database upgrade',
'info_mise_a_niveau_base_2' => '{{Warning!}} You have installed a version of
		SPIP files {older} than the one that was
		previously on this site: your database is at risk of being lost
		and your site will not work properly anymore.<br>{{Reinstall
		SPIP files.}}',
'info_mise_en_ligne' => 'Date of online publication:',
'info_mode_fonctionnement_defaut_forum_public' => 'Default operation mode of public forums',
'info_modification_parametres_securite' => 'modifying security parameters',
'info_modifier_breve' => 'Modify the news item:',
'info_modifier_rubrique' => 'Modify the section:',
'info_modifier_titre' => 'Modify: @titre@',
'info_mois_courant' => 'During the month:',
'info_mon_site_spip' => 'My SPIP site',
'info_mot_cle_ajoute' => 'The following keyword was added to',
'info_moteur_recherche' => 'Integrated search engine',
'info_mots_cles' => 'Keywords',
'info_mots_cles_association' => 'Keywords in this group can be associated with:',
'info_moyenne' => 'average:',
'info_nexen_1' => 'Your host is Nexen Services.',
'info_nexen_2' => 'Protection of the directory <tt>ecrire/data/</tt> should be applied through',
'info_nexen_3' => 'the webmaster\'s area',
'info_nexen_4' => 'Please apply the protection of this directory manually (a couple of login/password is required).',
'info_nom' => 'Name',
'info_nom_destinataire' => 'Name of recipient',
'info_nom_non_utilisateurs_connectes' => 'Your name does not appear in the list of connected users.',
'info_nom_site' => 'Your site\'s name',
'info_nom_site_2' => '<b>Site name</b> [Compulsory]',
'info_nom_utilisateurs_connectes' => 'Your name appears in the list of connected users.',
'info_nombre_articles' => '@nb_articles@ articles,',
'info_nombre_breves' => '@nb_breves@ news items,',
'info_nombre_en_ligne' => 'Online now:',
'info_nombre_partcipants' => 'PARTICIPANTS TO THE DISCUSSION:',
'info_nombre_rubriques' => '@nb_rubriques@ sections,',
'info_nombre_sites' => '@nb_sites@ sites,',
'info_non_deplacer' => 'Do not move...',
'info_non_envoi_annonce_dernieres_nouveautes' => 'SPIP can send the site\'s latest news announcements regularly.

		(recently published articles and news).',
'info_non_envoi_liste_nouveautes' => 'Do not send latest news list',
'info_non_modifiable' => 'cannot be modified',
'info_non_resultat' => 'No results for "@cherche_mot@"',
'info_non_suppression_mot_cle' => 'I do not want to delete this keyword.',
'info_non_utilisation_messagerie' => 'You are not using the internal messaging of this site.',
'info_notes' => 'Footnotes',
'info_nouveau' => '(New)',
'info_nouveau_message' => 'YOU HAVE A NEW MESSAGE',
'info_nouveau_pense_bete' => 'This button allows you to create a new personal memo.',
'info_nouveaux_message' => 'New messages',
'info_nouveaux_messages' => 'YOU HAVE @total_messages@ NEW MESSAGES',
'info_nouvel_article' => 'New article',
'info_numero_article' => 'ARTICLE NUMBER:',
'info_obligatoire_02' => '[Compulsory]',
'info_option_email' => 'When a site visitor posts a message to the forum

		associated with an article, the article\'s authors can be

		informed of this message by e-mail. Do you wish to use this option?',
'info_option_faire_suivre' => 'Forward forums messages to articles authors',
'info_option_ne_pas_faire_suivre' => 'Do not forward forums messages',
'info_options_avancees' => 'ADVANCED OPTIONS',
'info_ou' => 'or...',
'info_oui_suppression_mot_cle' => 'I want to delete this keyword permanently.',
'info_page_interdite' => 'Forbidden page',
'info_panne_site_syndique' => 'Syndicated site out of order',
'info_par_nombre_article' => '(by number of articles)',
'info_par_tri' => '(By @tri@)',
'info_pas_de_forum' => 'no forum',
'info_passe_trop_court' => 'Password too short.',
'info_passes_identiques' => 'The two passwords are not identical.',
'info_pense_bete' => 'MEMO',
'info_pense_bete_ancien' => 'Your old memos',
'info_petit_ecran' => 'Small display',
'info_pixels' => 'pixels',
'info_plus_cinq_car' => 'more than 5 characters',
'info_plus_cinq_car_2' => '(More than 5 characters)',
'info_plus_trois_car' => '(More than 3 characters)',
'info_plusieurs_mots_trouves' => 'Several keywords were found for "@cherche_mot@":',
'info_popularite' => 'popularity: @popularite@; visits: @visites@',
'info_popularite_2' => 'site popularity:',
'info_popularite_3' => 'popularity:&nbsp;@popularite@; visits:&nbsp;@visites@',
'info_popularite_4' => 'popularity:&nbsp;@popularite@; visits:&nbsp;@visites@',
'info_popularite_5' => 'popularity:',
'info_portfolio_automatique' => 'Automated portfolio:',
'info_post_scriptum' => 'Postscript',
'info_post_scriptum_2' => 'Postscript:',
'info_pour' => 'for',
'info_premier_resultat' => '[@debut_limit@ first results out of @total@]',
'info_premier_resultat_sur' => '[@debut_limit@ first results out of @total@]',
'info_probleme_grave' => 'error of',
'info_procedez_par_etape' => 'please proceed step by step',
'info_procedure_maj_version' => 'the upgrade procedure should be ran to adapt
	the database to the new version of SPIP.',
'info_propose_1' => '[@nom_site_spip@] Submits: @titre@',
'info_propose_2' => 'Article submitted
-----------------',
'info_propose_3' => 'The article "@titre@" is submitted for publication.',
'info_propose_4' => 'You are invited to review it and to give your opinion',
'info_propose_5' => 'in the forum linked to it. It is available at the address:',
'info_ps' => 'P.S.',
'info_publie_01' => 'The article "@titre@" was validated by @connect_nom@.',
'info_publie_1' => '[@nom_site_spip@] PUBLISHES: @titre@',
'info_publie_2' => 'Article published
-----------------',
'info_publies' => 'Your articles published online',
'info_question_gerer_statistiques' => 'Should your site manage visits statistics?',
'info_question_inscription_nouveaux_redacteurs' => 'Do you allow the registration of new editors from

		the published site? If you agree, visitors could register

		through an automated form, and will access then the private area to

		tender their own articles. <blockquote><i>During the registration process,

		users receive an automated e-mail

		providing them with their access code to the private site. Some

		hosts disable mail sending on their

		servers: in that case, automated registration cannot be

		implemented.',
'info_question_mots_cles' => 'Do you wish to use keywords in your site?',
'info_question_proposer_site' => 'Who can propose referenced sites?',
'info_question_referers' => 'Should your site preserve <i>referrers</i>
		(addresses of external links directing to your site)?',
'info_question_utilisation_moteur_recherche' => 'Do you wish to use the search engine integrated to SPIP?

	(Disabling it speeds up the performance of the system.)',
'info_qui_attribue_mot_cle' => 'The keywords in this group can be assigned by:',
'info_racine_site' => 'Site root',
'info_recharger_page' => 'Please reload this page in a few moments.',
'info_recherche_auteur_a_affiner' => 'Too many results for "@cherche_auteur@"; please refine your search.',
'info_recherche_auteur_ok' => 'Several editors were found for "@cherche_auteur@":',
'info_recherche_auteur_zero' => '<B>No results for "@cherche_auteur@".',
'info_rechercher' => 'Search',
'info_rechercher_02' => 'Search:',
'info_recommencer' => 'Please try again.',
'info_redacteur_1' => 'R&eacute;dacteur',
'info_redacteur_2' => 'having access to the private area (<i>recommended</i>)',
'info_redacteurs' => 'Editors',
'info_redaction_en_cours' => 'EDITING IN PROGRESS',
'info_redirection' => 'Redirection',
'info_refuses' => 'Your articles rejected',
'info_reglage_ldap' => 'Options: <B>Adjusting LDAP import</b>',
'info_remplacer_vignette' => 'Replace the default vignette by a customised logo:',
'info_remplacer_vignette_defaut' => 'Replace the default vignette with a customised logo:',
'info_renvoi_article' => '<B>Redirection.</B> This article refers to the page:',
'info_reserve_admin' => 'Only administrators can modify this address.',
'info_restauration_sauvegarde' => 'restoring the backup @archive@',
'info_restreindre_rubrique' => 'Restrict management to section:',
'info_resultat_recherche' => 'Search results:',
'info_retablir_lien' => 'restore this link',
'info_retirer_mot' => 'Remove this keyword',
'info_retirer_mots' => 'Remove all keywords',
'info_rubriques' => 'Sections',
'info_rubriques_02' => 'sections',
'info_rubriques_liees_mot' => 'Sections associated with this keyword',
'info_rubriques_trouvees' => 'Sections found',
'info_rubriques_trouvees_dans_texte' => 'Sections found (in the text)',
'info_sans_titre' => 'Untitled',
'info_sans_titre_2' => 'untitled',
'info_sauvegarde' => 'Backup',
'info_sauvegarde_articles' => 'Backup the articles',
'info_sauvegarde_articles_sites_ref' => 'Backup articles of referenced sites',
'info_sauvegarde_auteurs' => 'Backup the authors',
'info_sauvegarde_breves' => 'Backup the news',
'info_sauvegarde_documents' => 'Backup the documents',
'info_sauvegarde_echouee' => 'If the backup fails (&laquo;Maximum execution time exceeded&raquo;),',
'info_sauvegarde_forums' => 'Backup the forums',
'info_sauvegarde_groupe_mots' => 'Backup keywords groups',
'info_sauvegarde_messages' => 'Backup the messages',
'info_sauvegarde_mots_cles' => 'Backup the keywords',
'info_sauvegarde_petitions' => 'Backup the petitions',
'info_sauvegarde_refers' => 'Backup the referrers',
'info_sauvegarde_reussi_01' => 'Backup successful.',
'info_sauvegarde_reussi_02' => 'The database has been saved in <b>ecrire/data/@archive@</b>. You can',
'info_sauvegarde_reussi_03' => 'return to the management',
'info_sauvegarde_reussi_04' => 'of your site.',
'info_sauvegarde_rubriques' => 'Backup the sections',
'info_sauvegarde_signatures' => 'Backup petitions signatures',
'info_sauvegarde_sites_references' => 'Backup referenced sites',
'info_sauvegarde_type_documents' => 'Backup documents types',
'info_sauvegarde_visites' => 'Backup the visits',
'info_selection_chemin_acces' => '<b>Select</b> below the access path in the directory:',
'info_selection_un_seul_mot_cle' => 'You can select <b>only one keyword</b> at a time in this group.',
'info_selectionner_fichier' => 'You can select a file from the folder <i>upload</i>',
'info_selectionner_fichier_2' => 'Select a file:',
'info_signatures' => 'signatures',
'info_site' => 'Site',
'info_site_2' => 'site:',
'info_site_attente' => 'Web site pending validation',
'info_site_min' => 'site',
'info_site_propose' => 'Site submitted on:',
'info_site_reference' => 'Referenced sites online',
'info_site_reference_2' => 'Referenced site',
'info_site_refuse' => 'Web site rejected',
'info_site_syndique' => 'This site is syndicated...',
'info_site_valider' => 'Sites to be validated',
'info_site_web' => 'WEB SITE:',
'info_sites' => 'sites',
'info_sites_lies_mot' => 'Referenced sites associated with this keyword',
'info_sites_proxy' => 'Using a proxy',
'info_sites_referencer' => 'Referencing a site',
'info_sites_refuses' => 'Rejected sites',
'info_sites_trouves' => 'Sites found',
'info_sites_trouves_dans_texte' => 'Sites found (in the text)',
'info_sous_titre' => 'Subtitle:',
'info_statut_administrateur' => 'Administrator',
'info_statut_auteur' => 'This author\'s status:',
'info_statut_efface' => 'Deleted',
'info_statut_redacteur' => 'Editor',
'info_statut_site_1' => 'This site is:',
'info_statut_site_2' => 'Published',
'info_statut_site_3' => 'Submitted',
'info_statut_site_4' => 'To the dustbin',
'info_statut_utilisateurs_1' => 'Default status of imported users',
'info_statut_utilisateurs_2' => 'Choose the status that is attributed to the persons present in the LDAP directory when they connect for the first time. Later, you can modify this value for each author on a case by case basis.',
'info_suivi_activite' => 'Follow-up of editorial activity',
'info_supprimer_mot' => 'delete this keyword',
'info_supprimer_vignette' => 'delete the vignette',
'info_sur_site_public' => '[on the public site]',
'info_surtitre' => 'Top title:',
'info_symbole_bleu' => 'The symbol <B>blue</B> indicates a <B>memo</B>: i.e. a message for your personal use.',
'info_symbole_jaune' => 'The symbol <B>yellow</B> indicates an <B>announcement to all editors</B>: it can be edited by all administrators, and is visible to all editors.',
'info_symbole_vert' => 'The symbol <B>green</B> indicates the <B>messages exchanged with other users</B> of the site.',
'info_syndication' => 'syndication:',
'info_taille_maximale_vignette' => 'Maximum size of vignettes generated by the system:',
'info_telecharger' => 'Upload from your computer:',
'info_telecharger_nouveau_logo' => 'Upload a new logo:',
'info_telecharger_ordinateur' => 'Upload from your computer:',
'info_terminer_installation' => 'You can now finish the standard installation process.',
'info_texte' => 'Text',
'info_texte_explicatif' => 'Explanatory text',
'info_texte_long' => '(text is too long: it will appear in several parts which will be collated after validation.)',
'info_texte_message' => 'Text of your message:',
'info_texte_message_02' => 'Text of message',
'info_titre' => 'Title:',
'info_titre_mot_cle' => 'Name or title of this keyword',
'info_total' => 'total:',
'info_tous_articles_en_redaction' => 'All the articles in progress',
'info_tous_articles_presents' => 'All the articles published in this section',
'info_tous_les' => 'every:',
'info_tous_redacteur' => 'Announcements to all editors',
'info_tous_redacteurs' => 'Announcements to all editors',
'info_tous_resultats_enregistres' => '[all the results are recorded]',
'info_tout_afficher' => 'Show all',
'info_tout_site' => 'The entire site',
'info_travail_colaboratif' => 'Collaborative work on articles',
'info_trop_resultat' => 'Too many results for "@cherche_mot@"; please refine the search.',
'info_un_article' => 'an article,',
'info_un_mot' => 'One keyword at a time',
'info_un_site' => 'a site,',
'info_une_breve' => 'a news item,',
'info_une_rubrique' => 'a section,',
'info_une_rubrique_02' => '1 section',
'info_url' => 'URL:',
'info_url_site' => 'SITE\'S URL:',
'info_utilisation_messagerie_interne' => 'You are using the internal messaging of this site.',
'info_utilisation_spip' => 'SPIP is now ready to be used...',
'info_valider_lien' => 'validate this link',
'info_verifier_image' => ', please make sure your images have been transferred correctly.',
'info_vignette_defaut' => 'Default vignette',
'info_vignette_personnalisee' => 'Customised vignette',
'info_visite' => 'visit:',
'info_visites' => 'visits:',
'info_visites_par_mois' => 'Monthly display:',
'info_visites_plus_populaires' => 'Show visits for <b>the most popular articles</b> and for <b>the last published articles:</b>',
'info_visiteur_1' => 'Visitor',
'info_visiteur_2' => 'of the public site',
'info_visiteurs' => 'Visitors',
'info_visiteurs_02' => 'Public site visitors',
'info_vos_rendez_vous' => 'Your future appointments',
'infos_vos_pense_bete' => 'Your memos',
'install_select_langue' => '<NEW> S&eacute;lectionnez une langue puis cliquez sur le bouton &laquo;&nbsp;suivant&nbsp;&raquo; pour lancer la proc&eacute;dure d\'installation.',
'intem_redacteur' => 'editor',
'item_accepter_inscriptions' => 'Allow registrations',
'item_activer_forum_administrateur' => 'Enable administrators forum',
'item_activer_messages_avertissement' => 'Activate warning messages',
'item_administrateur' => 'Administrator',
'item_administrateur_2' => 'administrator',
'item_afficher_calendrier' => 'Display in calendar',
'item_ajout_mots_cles' => 'Authorise the addition of keywords to forums',
'item_autoriser_documents_joints' => 'Authorise documents attached to articles',
'item_autoriser_documents_joints_rubriques' => 'Authorise documents in the sections',
'item_bloquer_liens_syndiques' => 'Block syndicated links for validation',
'item_breve_proposee' => 'News item submitted',
'item_breve_refusee' => 'NO - News item rejected',
'item_breve_validee' => 'YES - News item validated',
'item_choix_administrateurs' => 'administrators',
'item_choix_generation_miniature' => 'Generate images thumbnails automatically.',
'item_choix_non_generation_miniature' => 'Do not generate images thumbnails.',
'item_choix_redacteurs' => 'editors',
'item_choix_visiteurs' => 'visitors of the public site',
'item_creer_fichiers_authent' => 'Create .htpasswd files',
'item_desactiver_forum_administrateur' => 'Disable administrators forum',
'item_efface' => 'Deleted',
'item_gerer_annuaire_site_web' => 'Manage Web sites directory',
'item_gerer_referers' => 'Manage referrers',
'item_gerer_statistiques' => 'Manage statistics',
'item_limiter_recherche' => 'Limit the search to information contained in your site',
'item_login' => 'Login',
'item_mots_cles_association_articles' => 'the articles',
'item_mots_cles_association_breves' => 'the news',
'item_mots_cles_association_rubriques' => 'the sections',
'item_mots_cles_association_sites' => 'the referenced or syndicated sites.',
'item_non' => 'No',
'item_non_accepter_inscriptions' => 'Do not allow registrations',
'item_non_activer_messages_avertissement' => 'No warning messages',
'item_non_afficher_calendrier' => 'Do not display in calendar',
'item_non_ajout_mots_cles' => 'Do not authorise the addition of keywords to forums',
'item_non_autoriser_documents_joints' => 'Do not authorise documents in articles',
'item_non_autoriser_documents_joints_rubriques' => 'Do not authorise documents in the sections',
'item_non_bloquer_liens_syndiques' => 'Do not block the links emanating from syndication',
'item_non_creer_fichiers_authent' => 'Do not create these files',
'item_non_gerer_annuaire_site_web' => 'Disable Web sites directory',
'item_non_gerer_referers' => 'Do not manage referrers',
'item_non_gerer_statistiques' => 'Do not manage statistics',
'item_non_limiter_recherche' => 'Extend the search to the content of referenced sites',
'item_non_publier_articles' => 'Do not publish the articles before their publication dates.',
'item_non_utiliser_breves' => 'Do not use the news',
'item_non_utiliser_config_groupe_mots_cles' => 'Do not use the advanced configuration of keyword groups',
'item_non_utiliser_moteur_recherche' => 'Do not use the engine',
'item_non_utiliser_mots_cles' => 'Do not use keywords',
'item_non_utiliser_syndication' => 'Do not use automated syndication',
'item_nouvel_auteur' => 'New author',
'item_nouvelle_breve' => 'New news item',
'item_nouvelle_rubrique' => 'New section',
'item_oui' => 'Yes',
'item_premier' => '1st',
'item_publier_articles' => 'Publish the articles disregarding their publication dates.',
'item_redacteur' => 'Editor',
'item_reponse_article' => 'Reply to the article',
'item_utiliser_breves' => 'Use the news',
'item_utiliser_config_groupe_mots_cles' => 'Use the advanced configuration of keyword groups',
'item_utiliser_moteur_recherche' => 'Use the search engine',
'item_utiliser_mots_cles' => 'Use keywords',
'item_utiliser_syndication' => 'Use automated syndication',
'item_visiteur' => 'visitor',


// L
'lien_afficher_icones_seuls' => 'Show icons only',
'lien_afficher_texte_icones' => 'Show icons and text',
'lien_afficher_texte_seul' => 'Show text only',
'lien_ajout_destinataire' => 'Add this recipient',
'lien_ajouter_auteur' => 'Add this author',
'lien_ajouter_participant' => 'Add a participant',
'lien_email' => 'e-mail',
'lien_forum_public' => 'Manage this article\'s public forum',
'lien_icones_interface' => 'The interface icons are from <a href=\'http://jimmac.musichall.cz/\'>Jakub \'Jimmac\' Steiner</a>.',
'lien_liberer' => 'release',
'lien_mise_a_jour_syndication' => 'Update now',
'lien_modifer_date' => 'Modify the date',
'lien_nom_site' => 'SITE\'S NAME:',
'lien_nouvea_pense_bete' => 'NEW MEMO',
'lien_nouveau_message' => 'NEW MESSAGE',
'lien_nouvelle_annonce' => 'NEW ANNOUNCEMENT',
'lien_nouvelle_recuperation' => 'Try to perform a new retrieval of data',
'lien_petitions' => 'PETITION',
'lien_popularite' => 'popularity: @popularite@%',
'lien_racine_site' => 'SITE ROOT',
'lien_reessayer' => 'try again',
'lien_rendez_vous' => 'AN APPOINTMENT',
'lien_rendez_vous_02' => '@total_messages@ APPOINTMENTS',
'lien_repondre_message' => 'Reply to this message',
'lien_reponse_article' => 'Reply to the article',
'lien_reponse_breve' => 'Reply to the news item',
'lien_reponse_breve_2' => 'Reply to the news item',
'lien_reponse_rubrique' => 'Reply to the section',
'lien_reponse_site_reference' => 'Reply to the referenced site:',
'lien_retirer_auteur' => 'Remove author',
'lien_retrait_particpant' => 'remove this participant',
'lien_site' => 'site',
'lien_supprimer' => 'delete',
'lien_supprimer_rubrique' => 'delete this section',
'lien_test_format_image' => 'Test image formats that this site can use to create vignettes',
'lien_tout_afficher' => 'Show all',
'lien_tout_deplier' => 'Expand all',
'lien_tout_replier' => 'Collapse all',
'lien_trier_nom' => 'Sort by name',
'lien_trier_nombre_articles' => 'Sort by number of articles',
'lien_trier_statut' => 'Sort by status',
'lien_visite_site' => 'visit this site',
'lien_visites' => '@visites@ visits',
'lien_voir_auteur' => 'Check this author',
'lien_voir_en_ligne' => 'VIEW ONLINE:',
'lnfo_liens' => 'links:',
'login_acces_prive' => '<NEW> acc&egrave;s &agrave; l\'espace priv&eacute;',
'login_autre_identifiant' => '<NEW> se connecter sous un autre identifiant',
'login_connexion_refusee' => '<NEW> Connexion refus&eacute;e.',
'login_cookie_accepte' => '<NEW> Veuillez r&eacute;gler votre navigateur pour qu\'il les accepte (au moins pour ce site).',
'login_cookie_oblige' => '<NEW> Pour vous identifier de fa&ccedil;on s&ucirc;re sur ce site, vous devez accepter les cookies.',
'login_deconnexion_ok' => '<NEW> D&eacute;connexion effectu&eacute;e.',
'login_erreur_pass' => '<NEW> Erreur de mot de passe.',
'login_espace_prive' => '<NEW> espace priv&eacute;',
'login_identifiant_inconnu' => '<NEW> L\'identifiant &laquo; @login@ &raquo; est inconnu.',
'login_identification' => '<NEW> identification',
'login_login' => '<NEW> Login :',
'login_login2' => '<NEW> Login (identifiant de connexion au site)&nbsp;:',
'login_login_pass_incorrect' => '<NEW> (Login ou mot de passe incorrect.)',
'login_motpasseoublie' => '<NEW> mot&nbsp;de&nbsp;passe&nbsp;oubli&eacute;&nbsp;?',
'login_non_securise' => '<NEW> Attention, ce formulaire n\'est pas s&eacute;curis&eacute;.
			Si vous ne voulez pas que votre mot de passe puisse &ecirc;tre
			intercept&eacute; sur le r&eacute;seau, veuillez activer Javascript 
			dans votre navigateur et',
'login_nouvelle_tentative' => '<NEW> Nouvelle tentative',
'login_par_ici' => '<NEW> Vous &ecirc;tes enregistr&eacute;... par ici...',
'login_pass2' => '<NEW> Mot de passe&nbsp;:',
'login_preferez_refuser' => '<NEW> <b>Si vous pr&eacute;f&eacute;rez refuser les cookies</b>, une autre m&eacute;thode de connexion (moins s&eacute;curis&eacute;e) est &agrave; votre disposition&nbsp;:',
'login_recharger' => '<NEW> recharger cette page',
'login_retour_public' => '<NEW> Retour au site public',
'login_retour_site' => '<NEW> Retour au site public',
'login_retoursitepublic' => '<NEW> retour&nbsp;au&nbsp;site&nbsp;public',
'login_sans_cookiie' => '<NEW> Identification sans cookie',
'login_sinscrire' => '<NEW> s\'inscrire',
'login_test_navigateur' => '<NEW> test navigateur/reconnexion',
'login_verifiez_navigateur' => '<NEW> (V&eacute;rifiez toutefois que votre navigateur n\'a pas m&eacute;moris&eacute; votre mot de passe...)',
'logo_article' => 'ARTICLE\'S LOGO',
'logo_auteur' => 'AUTHOR\'S LOGO',
'logo_breve' => 'NEWS ITEM\'S LOGO',
'logo_mot_cle' => 'KEYWORD\'S LOGO',
'logo_rubrique' => 'SECTION\'S LOGO',
'logo_site' => 'THIS SITE\'S LOGO',
'logo_standard_rubrique' => 'STANDARD LOGO FOR SECTIONS',
'logo_survol' => 'HOVERING LOGO',


// M
'menu_aide_articles' => 'Articles',
'menu_aide_articles_auteurs' => 'Authors',
'menu_aide_articles_chapeau' => 'Deck',
'menu_aide_articles_choix_rubrique' => 'Selecting the section',
'menu_aide_articles_date' => 'Date',
'menu_aide_articles_date_anterieure' => 'Date of earlier publication',
'menu_aide_articles_descriptif_rapide' => 'Brief description',
'menu_aide_articles_en_cours_modification' => 'Articles in editing',
'menu_aide_articles_logos' => 'Article\'s logo',
'menu_aide_articles_proposer' => 'Submitting an article',
'menu_aide_articles_raccourcis_typo' => 'Typographical shortcuts',
'menu_aide_articles_redirection' => 'Redirection of an article',
'menu_aide_articles_statut' => 'Article status',
'menu_aide_articles_texte' => 'Text',
'menu_aide_articles_titres' => 'Title, top title, subtitle',
'menu_aide_breves' => 'News',
'menu_aide_breves_breves' => 'News',
'menu_aide_breves_choix' => 'Selecting a section',
'menu_aide_breves_lien' => 'Hypertext link',
'menu_aide_breves_logo' => 'News item\'s logo',
'menu_aide_breves_statut' => 'News item\'s status',
'menu_aide_images_doc' => 'Images and documents',
'menu_aide_images_doc_ftp' => 'Installing files via FTP',
'menu_aide_images_doc_inserer' => 'Inserting images',
'menu_aide_images_doc_joindre' => 'Attaching documents',
'menu_aide_installation_choix_base' => 'Choosing your database',
'menu_aide_installation_connexion_mysql' => 'Your mySQL connection',
'menu_aide_installation_ftp' => 'Authentication by FTP',
'menu_aide_installation_informations_personnelles' => 'Personal information',
'menu_aide_installation_probleme_squelette' => 'A matter of template?',
'menu_aide_installation_reactuliser_droits' => 'Setting up permissions',
'menu_aide_installation_spip' => 'SPIP Installation',
'menu_aide_interface_perso' => 'Personal interface customisation',
'menu_aide_interface_perso_cookie' => 'The administration cookie',
'menu_aide_interface_perso_deconnecter' => 'Disconnect',
'menu_aide_interface_perso_simplifiee' => 'Simplified / complete interface',
'menu_aide_messagerie' => 'Internal messaging',
'menu_aide_messagerie_calendrier' => 'The calendar',
'menu_aide_messagerie_configuration_perso' => 'Messaging customisation',
'menu_aide_messagerie_pense_bete' => 'Memos',
'menu_aide_messagerie_utilisateurs' => 'Messages between users',
'menu_aide_mots_cles' => 'Keywords',
'menu_aide_mots_cles_groupes' => 'Keyword groups',
'menu_aide_mots_cles_mots_cles' => 'Keywords',
'menu_aide_mots_cles_principe' => 'The keywords principle',
'menu_aide_rubriques' => 'Sections',
'menu_aide_rubriques_choix' => 'Selecting a section',
'menu_aide_rubriques_logo' => 'Section\'s logo',
'menu_aide_rubriques_structure' => 'Hierarchical structure of the sections',
'menu_aide_sites' => 'Referenced sites',
'menu_aide_sites_articles_syndiques' => 'Syndicated articles',
'menu_aide_sites_proxy' => 'Using a proxy',
'menu_aide_sites_referencer' => 'Referencing a site',
'menu_aide_sites_syndiquer' => 'Syndicated sites',
'menu_aide_suivi_forum' => 'Forums follow-up',
'menu_aide_suivi_forum_articles_postes' => 'Post-dated articles',
'menu_aide_suivi_forum_configuration' => 'Precise configuration',
'menu_aide_suivi_forum_contenu_articles' => 'Articles content',
'menu_aide_suivi_forum_envoi_emails' => 'Automated e-mailing',
'menu_aide_suivi_forum_fonctionnement' => 'Operating the forums',
'menu_aide_suivi_forum_messagerie_interne' => 'Internal messaging',
'menu_aide_suivi_forum_moteur_recherche' => 'Integrated search engine',
'menu_aide_suivi_forum_nom_adresse' => 'Name and URL of your site',
'menu_aide_suivi_forum_statistiques' => 'Visits statistics',
'menu_aide_suivi_forum_suivi' => 'Forums follow-up',
'menu_aide_suivi_forum_systeme_breves' => 'The news system',
'mois_non_connu' => 'unknown',


// O
'onglet_affacer_base' => 'Delete the database',
'onglet_auteur' => 'The author',
'onglet_contenu_site' => 'The site\'s content',
'onglet_evolution_visite' => 'Visits evolution',
'onglet_fonctions_avances' => 'Advanced functions',
'onglet_informations_personnelles' => 'Personal Information',
'onglet_interactivite' => 'Interactivity',
'onglet_langue' => 'Site\'s languages',
'onglet_messagerie' => 'Messaging',
'onglet_messages_internes' => 'Internal messages',
'onglet_messages_publics' => 'Public messages',
'onglet_messages_vide' => 'Messages without text',
'onglet_origine_visites' => 'Visits Origin',
'onglet_repartition_actuelle' => 'now',
'onglet_repartition_debut' => 'from the start',
'onglet_repartition_rubrique' => 'Distribution by section',
'onglet_save_restaur_base' => 'Backup/restore the database',
'onglet_vider_cache' => 'Empty the cache',


// P
'pass_choix_pass' => '<NEW> Veuillez choisir votre nouveau mot de passe :',
'pass_erreur' => '<NEW> Erreur',
'pass_erreur_acces_refuse' => '<NEW> <b>Erreur :</b> vous n\'avez plus acc&egrave;s &agrave; ce site.',
'pass_erreur_code_inconnu' => '<NEW> <b>Erreur :</b> ce code ne correspond &agrave; aucun des visiteurs ayant acc&egrave;s &agrave; ce site.',
'pass_erreur_non_enregistre' => '<NEW> <b>Erreur :</b> l\'adresse <tt>@email_oubli@</tt> n\'est pas enregistr&eacute;e sur ce site.',
'pass_erreur_non_valide' => '<NEW> <b>Erreur :</b> cet email <tt>@email_oubli@</tt> n\'est pas valide !',
'pass_erreur_probleme_technique' => '<NEW> <b>Erreur :</b> &agrave; cause d\'un probl&egrave;me technique, l\'email ne peut pas &ecirc;tre envoy&eacute;.',
'pass_espace_prive_bla' => '<NEW> L\'espace priv&eacute; de ce site est ouvert aux
		visiteurs, apr&egrave;s inscription. Une fois enregistr&eacute;,
		vous pourrez consulter les articles en cours de r&eacute;daction,
		proposer des articles et participer &agrave; tous les forums.',
'pass_forum_bla' => '<NEW> Vous avez demand&eacute; &agrave; intervenir sur un forum
		r&eacute;serv&eacute; aux visiteurs enregistr&eacute;s.',
'pass_indiquez_cidessous' => '<NEW> Indiquez ci-dessous l\'adresse email sous laquelle vous
			vous &ecirc;tes pr&eacute;c&eacute;demment enregistr&eacute;. Vous
			recevrez un email vous indiquant la marche &agrave; suivre pour
			r&eacute;cup&eacute;rer votre acc&egrave;s.',
'pass_mail_passcookie' => '<NEW> (ceci est un message automatique)
Pour retrouver votre acc&egrave;s au site
@nom_site_spip@ (@adresse_site@)

Veuillez vous rendre &agrave; l\'adresse suivante :

    @adresse_site@/spip_pass.php3?p=@cookie@

Vous pourrez alors entrer un nouveau mot de passe
et vous reconnecter au site.

',
'pass_mot_oublie' => '<NEW> Mot de passe oubli&eacute;',
'pass_nouveau_enregistre' => '<NEW> Votre nouveau mot de passe a &eacute;t&eacute; enregistr&eacute;.',
'pass_nouveau_pass' => '<NEW> Nouveau mot de passe',
'pass_ok' => '<NEW> OK',
'pass_oubli_mot' => '<NEW> Oubli du mot de passe',
'pass_quitter_fenetre' => '<NEW> Quitter cette fen&ecirc;tre',
'pass_rappel_login' => '<NEW> Rappel : votre identifiant (login) est &laquo; @login@ &raquo;.',
'pass_recevoir_mail' => '<NEW> Vous allez recevoir un email vous indiquant comment retrouver votre acc&egrave;s au site.',
'pass_retour_public' => '<NEW> Retour sur le site public',
'pass_rien_a_faire_ici' => '<NEW> Rien &agrave; faire ici.',
'pass_vousinscrire' => '<NEW> Vous inscrire sur ce site',


// S
'stats_visites_et_popularite' => '<NEW> @visites@ visites&nbsp;; popularit&eacute;&nbsp;: @popularite@',


// T
'taille_ko' => '@taille@&nbsp;kb',
'taille_mo' => '@taille@&nbsp;Mb',
'taille_octets' => '@taille@ bytes',
'text_article_propose_publication' => 'Article submitted for publication. Do not hesitate to give your opinion through the forum attached to this article (at the bottom of the page).',
'texte_acces_ldap_anonyme_1' => 'Some LDAP servers do not allow any anonymous access. In this case you must indicate an initial access identifier to be able to search for information in the directory afterwards. However, in most cases the following fields can be left empty.',
'texte_actualite_site_1' => 'This page records the status of the site and allows you to follow up your contributions. You can find here your unfinished articles as well as the articles and news on which you are invited to give an opinion, and a reminder of your previous contributions.<p><hr><p>When you become familiar with the interface, click on the &laquo;',
'texte_actualite_site_2' => 'complete interface',
'texte_actualite_site_3' => '&raquo; to make more features available.',
'texte_admin_effacer_01' => 'This command deletes <i>all</i> the content of the database,
including <i>all</i> the access parameters for editors and administrators. After executing it, you should
reinstall SPIP in order to recreate a new database and the first administrator\'s access.',
'texte_admin_tech_01' => 'This option allows you to save 
the content of the database in a file stored in the directory <i>ecrire/data/</i>.
Also remember to retrieve the whole <i>IMG/</i>, directory, which contains
the images used in the articles and sections.',
'texte_admin_tech_02' => 'Warning: this backup can ONLY be restored
	in a site installed under the same version of SPIP. It is a common mistake
	to backup the database before upgrading
	SPIP... For more information refer to [SPIP documentation->http://www.uzine.net/article1489.html].',
'texte_admin_tech_03' => 'You can choose to save the file in a compressed form, to 
	speed up its transfer to your machine or to a backup server and save some disk space.',
'texte_adresse_annuaire_1' => '( If your directory is installed on the same machine as your Web site, it is probably &laquo;localhost&raquo;.)',
'texte_ajout_auteur' => 'The following author was added to the article:',
'texte_annuaire_ldap_1' => 'If you have access to a (LDAP) directory, you can use it to automatically import users under SPIP.',
'texte_article_statut' => 'This article is:',
'texte_article_virtuel' => 'Virtual article',
'texte_article_virtuel_reference' => '<b>Virtual article:</b> referenced article in your SPIP site, but redirected to another URL. To remove redirection, delete the above URL.',
'texte_aucun_resultat_auteur' => 'No results for "@cherche_auteur@".',
'texte_auteur_messagerie' => 'This site can continuously monitor the list of connected editors, which allows you to exchange messages in real time (if messaging is disabled above, the list of connected editors is itself disabled). You can decide not to appear in this list (you are then, &laquo;invisible&raquo; for the other users).',
'texte_auteur_messagerie_1' => 'This site allows the exchange of messages and the creation of private discussion forums between participants to the site. You can decide not to participate to this exchange.',
'texte_auteurs' => 'THE AUTHORS',
'texte_breves' => 'News are short and simple texts which allow

	the publication online of concise information, the management of

	a press review, a calendar of events...',
'texte_choix_base_1' => 'Select your database:',
'texte_choix_base_2' => 'MySQL server contains several databases.',
'texte_choix_base_3' => '<B>Select</B> below the one that your host attributed to you:',
'texte_choix_langue_defaut' => 'Please select below the default language of your site, as well as the languages which will be available to the editors.',
'texte_commande_vider_tables_indexation' => 'Use this command to empty the indexing tables used
			by the search engine integrated to SPIP. It will allow you
			to save some disk space.',
'texte_comment_lire_tableau' => 'The rank of the article,

		in the popularity classification, is indicated in the

		margin; the article popularity (an estimate of

		the number of daily visits it will have if the actual pace of

		traffic is maintained) and the number of visits recorded

		since the beginning are displayed in the balloon that

		appears as the mouse hovers over the title.',
'texte_compresse_ou_non' => '(this one could be compressed or not)',
'texte_compte_element' => '@count@ element',
'texte_compte_elements' => '@count@ elements',
'texte_config_groupe_mots_cles' => 'Do you wish to activate the advanced configuration keyword groups,

			by specifying, for instance that a unique word per group

			could be selected, that a group is important...?',
'texte_connexion_mysql' => 'Refer to the information provided to you by your host: it should give you, if your host supports MySQL, the connection codes to MySQL server.',
'texte_contenu_article' => '(Content of the article in a few words.)',
'texte_contenu_articles' => 'Based on the layout chosen for your site, you can decide
		that some articles elements are not to be used.
		Use the following list to choose which elements should be available.',
'texte_crash_base' => 'If your database
			crashed, you can try to repair it
			automatically.',
'texte_creation_automatique_vignette' => 'Automated creation of preview vignettes is enabled in this site. if you install, through this form, images in the format(s) @gd_formats@, they will be coupled with a vignette which maximum size is @taille_preview@ pixels.',
'texte_creer_rubrique' => 'Before being able to write articles,<BR> you must create a section.',
'texte_date_creation_article' => 'DATE OF ARTICLE CREATION:',
'texte_date_publication_anterieure' => 'DATE OF EARLIER PUBLICATION',
'texte_date_publication_anterieure_nonaffichee' => 'Hide date of earlier publication.',
'texte_date_publication_article' => 'DATE OF ONLINE PUBLICATION:',
'texte_demander_blocage_priori' => 'Future links
				coming from this site will be blocked beforehand.',
'texte_descriptif_petition' => 'Petition description',
'texte_descriptif_rapide' => 'Brief description',
'texte_documents_associes' => 'The following documents are associated with the article,,
				but they were not directly
				inserted. Based on the public site\'s layout,
				they could appear as attached documents.',
'texte_documents_joints' => 'You can allow the addition of documents (office files, images,
	multimedia, etc.) to articles and/or sections. These files
	could then be referenced in
	the article or displayed separately.<p>',
'texte_documents_joints_2' => 'This setting does not stop the insertion of images directly in the articles.',
'texte_effacer_base' => 'Delete the SPIP database',
'texte_effacer_donnees_indexation' => 'Delete indexing data',
'texte_en_cours_validation' => 'The following articles and news are submitted for publication. Do not hesitate to give your opinion through the forums attached to them.',
'texte_enrichir_mise_a_jour' => 'You can enrich the layout of your text by using &laquo;typographical shortcuts&raquo;.',
'texte_erreur_mise_niveau_base' => 'Database error during upgrade.
						The image <B>@fichier@</B> could not be passed (article @id_article@).<p>
						Note carefully this reference, retry the upgrade procedure,
						and finally make sure that the images still appear
						in the articles.',
'texte_fichier_authent' => '<b>Should SPIP create special <tt>.htpasswd</tt>

		and <tt>.htpasswd-admin</tt> files in the directory <tt>ecrire/data/</tt>?</b><p>

		These files can be used to restrict access to authors

		and administrators in other parts of your site

		(for instance, external statistical programme).<p>

		If you have not used such files before you can leave this option

		with its default value (no creation 

		of files).',
'texte_inc_auth_1' => 'You identified yourself with the login
		<B>@auth_login@</B>, but it does not exist in the database (anymore). 
		Try to',
'texte_inc_auth_2' => 'reconnect',
'texte_inc_auth_3' => 'having quit then
		restarted your browser if necessary.',
'texte_inc_config' => 'The modifications entered below influence notably

	 the functioning of the site. You are advised not to deal with them unless you are

	familiar with the functioning of the SPIP system. <P align="justify"><B>More

	generally, you are strongly advised

	to let the main webmaster of your site deal with this page.</b>',
'texte_inc_meta_1' => 'As a site administrator, please',
'texte_inc_meta_2' => 'verify write permissions',
'texte_inc_meta_3' => 'over the directory ecrire/',
'texte_informations_personnelles_1' => 'The system will provide you now with a custom access to the site.',
'texte_informations_personnelles_2' => '(Note: if it is a reinstallation, and your access is still working, you can',
'texte_introductif_article' => '(Introductory Text to the article.)',
'texte_jeu_caractere' => 'This option is useful if your site displays alphabets

	different from the roman alphabet (that is &laquo;western&raquo;) and its derivatives.

	In this case, the default setting must be changed in order to use

	a suitable character set. Also, remember to adapt

	the site accordingly (<tt>#CHARSET</tt> tag).',
'texte_jeu_caractere_2' => 'This setting is not retroactive.
	Consequently, text already entered might be wrongly
	displayed after modifying the setting. Anyway,
	you can always revert to the previous setting.',
'texte_lien_hypertexte' => '(If your message refers to an article published on the Web, or to a page providing more information, please enter here the title of the page and its URL.)',
'texte_liens_sites_syndiques' => 'Links emanating from syndicated sites could
			be blocked beforehand; the following
			setting show the default setting of
			syndicated sites after their creation. It
			is, then, possible anyway to
			block each link individually, or to
			choose, for each site, to block the links coming
			from any particular site.',
'texte_liens_syndication' => 'Future links
				coming from this site will be displayed immediately on the public site.',
'texte_login_ldap_1' => '(Keep empty for anonymous access or enter complete path, for instance &laquo;<tt>uid=smith, ou=users, dc=my-domain, dc=com</tt>&raquo;.)',
'texte_login_precaution' => 'Warning! This is the login with which you are connected now.

	Use this form with caution...',
'texte_message_edit' => 'Warning: this message can be modified by all the site administrators, and it appears to all editors. Use the announcements only to stress important events in the site\'s life.',
'texte_messages_publics' => 'Public Messages of the article:',
'texte_mise_a_niveau_base_1' => 'You have just updated SPIP files.
	Now you must upgrade the site\'s
	database.',
'texte_modifier_article' => 'Modify the article:',
'texte_moteur_recherche_active' => '<b>The search engine is enabled.</b> use this command

		if you wish to execute a quick re-indexing (after restoring

		a backup for instance). You should note that the documents modified in

		a normal way (from the SPIP interface) are automatically

		indexed again: therefore this command is only useful in exceptional circumstances.',
'texte_moteur_recherche_non_active' => 'The search engine is not enabled.',
'texte_mots_cles' => 'Keywords allow you to create topical links between your articles
		irrespective of their section location. That way you can
		enrich the navigation of your site or even use these properties
		to customise the articles in your templates.',
'texte_mots_cles_dans_forum' => 'Do you wish to allow the use of keywords that could be selected by visitors, in the public site forums? (Warning: this option is rather intricate to use properly.)',
'texte_non_compresse' => '<i>uncompressed</i> (your server does not support this feature)',
'texte_non_fonction_referencement' => 'You can choose not to use this automated feature, and enter the elements concerning that site manually...',
'texte_nouveau_message' => 'New message',
'texte_nouveau_mot' => 'New keyword',
'texte_nouvelle_version_spip_1' => 'You have just installed a new version of SPIP.',
'texte_nouvelle_version_spip_2' => 'This new version requires an update more thorough than usual. If you are the webmaster of this site, please delete the file <tt>inc_connect.php3</tt> of the directory <tt>ecrire</tt> and restart installation in order to update your database connection parameters. <p>(NB.: if you forgot your connection parameters, have a look at the file <tt>inc_connect.php3</tt> before deleting it...)',
'texte_operation_echec' => 'Go back to the previous page, select another database or create a new one. Verify the information provided by your host.',
'texte_plus_trois_car' => 'more than 3 characters',
'texte_plusieurs_articles' => 'Several authors were found for "@cherche_auteur@":',
'texte_port_annuaire' => '(Default value is generally suitable.)',
'texte_proposer_publication' => 'When your article is finished,<br> you can submitted it for publication.',
'texte_proxy' => 'In some cases (intranet, protected networks...),

		it is necessary to use a <I>proxy HTTP</i> to reach the syndicated sites.

		Should there be a proxy, enter its address below, thus

		<tt><html>http://proxy:8080</html></tt>. Generally,

		you will leave this box empty.',
'texte_publication_articles_post_dates' => 'Which behaviour should SPIP adopt concerning articles which

		publication have been set to

		a future date?',
'texte_rappel_selection_champs' => '[Remember to select this field correctly.]',
'texte_recalcul_page' => 'If you want
to refresh only one page, you would rather do it from the public area and use the button &laquo; refresh &raquo;.',
'texte_recapitiule_liste_documents' => 'This page sums up the list of documents that you have placed in the sections. To modify each document\'s information, follow the link to its section\'s page.',
'texte_recuperer_base' => 'Repair the database',
'texte_reference_mais_redirige' => 'referenced article in your SPIP site, but redirected to another URL.',
'texte_referencement_automatique' => '<b>Automated site referencing</b><br>You can reference a Web site quickly by indicating below the desired URL, or the address of its backend file. SPIP will automatically pick up the information concerning that site (title, description...).',
'texte_requetes_echouent' => '<B>When some MySQL queries fail

		systematically and without any apparent reason, it is possible

		that the database itself

		is the culprit.</b>

		<p>MySQL has at its disposal a repair feature of its tables

		when they have been accidentally corrupted.

		Here, you can try to execute this repair; in

		case of failure, you should keep a copy of the display, which might contain

		clues on what went wrong...

		<p>If the problem remains, contact your 

		host.',
'texte_restaurer_base' => 'Restore the content of the database backup',
'texte_restaurer_sauvegarde' => 'This option allows you to restore a previous
backup of the database. To achieve this, the file containing the backup should have been
stored in the directory <i>ecrire/data/</i>.
Be careful with this feature: <b>Any potential modifications or losses are
irreversible.</b>',
'texte_sauvegarde' => 'Backup the content of the database',
'texte_sauvegarde_base' => 'Backup the database',
'texte_sauvegarde_compressee' => 'Backup will be done in the uncompressed file <b>ecrire/data/dump.xml</b>.',
'texte_selection_langue_principale' => '<NEW> Vous pouvez s&eacute;lectionner ci-dessous la &laquo;&nbsp;langue principale&nbsp;&raquo; du site. Ce choix ne vous oblige - heureusement&nbsp;! - pas &agrave; &eacute;crire vos articles dans la langue s&eacute;lectionn&eacute;e, mais permet de d&eacute;terminer&nbsp;:
	<ul><li> le format par d&eacute;faut des dates sur le site public&nbsp;;</li>
	<li> la nature du moteur typographique que SPIP doit utiliser pour le rendu des textes&nbsp;;</li>
	<li> la langue utilis&eacute;e dans les formulaires du site public&nbsp;;</li>
	<li> la langue pr&eacute;sent&eacute;e par d&eacute;faut dans l\'espace priv&eacute;.</li></ul>',
'texte_signification' => 'Red bars represent cumulative entries (total of sub-sections), green bars represent the number of visits for each section.',
'texte_sous_titre' => 'Subtitle',
'texte_statistiques_visites' => '(dark bars:  Sunday / dark curve: average evolution)',
'texte_statut_attente_validation' => 'pending validation',
'texte_statut_en_cours_redaction' => 'editing in progress',
'texte_statut_poubelle' => 'to the dustbin',
'texte_statut_propose_evaluation' => 'submitted for evaluation',
'texte_statut_publie' => 'published online',
'texte_statut_publies' => 'published online',
'texte_statut_refuse' => 'rejected',
'texte_statut_refuses' => 'rejected',
'texte_suppression_fichiers' => 'Use this command to delete all the files
in the SPIP cache. This allows you, amongst other things, to force the refreshing of all the pages in case you
entered important modifications on the graphics or the structure of the site.',
'texte_sur_titre' => 'Top title',
'texte_syndication' => 'If a site allows it, it is possible to retrieve automatically

		the list of its latest material. To achieve this, you must activate the syndication. 

		<blockquote><i>Some hosts disable this function; 

		in this case, you cannot use the content syndication

		from your site.</i></blockquote>',
'texte_table_ok' => ': this table is OK.',
'texte_tables_indexation_vides' => 'Indexing tables of the engine are empty.',
'texte_tentative_recuperation' => 'Repairing attempt',
'texte_tenter_reparation' => 'Attempt to repair the database',
'texte_test_proxy' => 'To try this proxy, enter here the address of a Web site
				that you wish to test.',
'texte_titre_02' => 'Subject:',
'texte_titre_obligatoire' => '<B>Title</b> [Compulsory]',
'texte_travail_article' => '@nom_auteur_modif@ has worked on this article @date_diff@ minutes ago',
'texte_travail_collaboratif' => 'If it is frequent that several editors

		work on the same article, the system

		can show the recently &laquo;opened&raquo; articles

		in order to avoid simultaneous modifications.

		This option is disabled by default

		to avoid displaying untimely warning

		messages.',
'texte_trop_resultats_auteurs' => 'Too many results for "@cherche_auteur@"; please refine the search.',
'texte_unpack' => 'downloading the latest version',
'texte_utilisation_moteur_syndiques' => 'When you use the search engine integrated to SPIP, you can perform searches on sites and articles syndicated in different manners. <br><img src=\'puce.gif\'>The most simple one is to search only in the titles and descriptions of the articles. <br><img src=\'puce.gif\'> A second method, much more powerful, allows SPIP to search also in the text of the referenced sites. If you reference a site, SPIP will perform the search in the site\'s text itself.',
'texte_utilisation_moteur_syndiques_2' => 'This method forces SPIP to visit the referenced sites regularly, which could cause a drop in the performance of your own site.',
'texte_vide' => 'empty',
'texte_vider_cache' => 'Empty the cache',
'titre_admin_effacer' => 'Technical maintenance',
'titre_admin_tech' => 'Technical maintenance',
'titre_admin_vider' => 'Technical maintenance',
'titre_ajouter_mot_cle' => 'ADD A KEYWORD:',
'titre_articles_syndiques' => 'Syndicated articles pulled out from this site',
'titre_breve_proposee' => 'Submitted news item',
'titre_breve_publiee' => 'News item published',
'titre_breve_refusee' => 'News item rejected',
'titre_breves' => 'News',
'titre_cadre_afficher_article' => 'Show the articles:',
'titre_cadre_ajouter_auteur' => 'ADD AN AUTHOR:',
'titre_cadre_forum_administrateur' => 'Administrators private forum',
'titre_cadre_forum_interne' => 'Internal forum',
'titre_cadre_interieur_rubrique' => 'In section',
'titre_cadre_numero_auteur' => 'AUTHOR NUMBER',
'titre_cadre_raccourcis' => 'SHORTCUTS:',
'titre_cadre_signature_obligatoire' => '<B>Signature</B> [Compulsory]<BR>',
'titre_changer_couleur_interface' => 'Changing interface colour',
'titre_config_fonctions' => 'Site configuration',
'titre_config_groupe_mots_cles' => 'Configuration of keywords groups',
'titre_configuration' => 'Site configuration',
'titre_connexion_ldap' => 'Options: <B>Your LDAP connection</b>',
'titre_dernier_article_syndique' => 'Latest syndicated articles',
'titre_documents_joints' => 'Attached documents',
'titre_evolution_visite' => 'Visits evolution',
'titre_forum' => 'Internal forum',
'titre_forum_suivi' => 'Forums follow-up',
'titre_gauche_mots_edit' => 'KEYWORD NUMBER:',
'titre_groupe_mots' => 'KEYWORDS GROUP:',
'titre_image_admin_article' => 'You can administer this article',
'titre_image_administrateur' => 'Administrator',
'titre_image_aide' => 'Help on this item',
'titre_image_auteur_supprime' => 'Author deleted',
'titre_image_redacteur' => 'Editor without access',
'titre_image_redacteur_02' => 'Editor',
'titre_image_visiteur' => 'Visitor',
'titre_joindre_document' => 'ATTACH A DOCUMENT',
'titre_les_articles' => 'ARTICLES',
'titre_liens_entrants' => 'Incoming links of the day',
'titre_mots_cles' => 'KEYWORDS',
'titre_mots_cles_dans_forum' => 'Keywords in the public site forums',
'titre_mots_tous' => 'Keywords',
'titre_naviguer_dans_le_site' => 'Browse the site...',
'titre_nouveau_groupe' => 'New group',
'titre_nouvelle_breve' => 'New news item',
'titre_nouvelle_rubrique' => 'New section',
'titre_numero_rubrique' => 'SECTION NUMBER:',
'titre_page_admin_effacer' => 'Technical maintenance: deleting the database',
'titre_page_admin_vider' => 'Technical maintenance: managing the cache',
'titre_page_articles_edit' => 'Modify: @titre@',
'titre_page_articles_page' => 'Articles',
'titre_page_articles_tous' => 'The entire site',
'titre_page_auteurs' => 'Visitors',
'titre_page_breves' => 'News',
'titre_page_breves_edit' => 'Modify the news item: &laquo;@titre@&raquo;',
'titre_page_calendrier' => 'Calendar @nom_mois@ @annee@',
'titre_page_config_contenu' => 'Site configuration',
'titre_page_config_fonctions' => 'Site configuration',
'titre_page_configuration' => 'Site configuration',
'titre_page_controle_petition' => 'Petitions follow-up',
'titre_page_delete_all' => 'total and irreversible deletion',
'titre_page_documents_liste' => 'Sections documents',
'titre_page_forum' => 'Administrators forum',
'titre_page_forum_envoi' => 'Send a message',
'titre_page_forum_suivi' => 'Forums follow-up',
'titre_page_index' => 'Your private area',
'titre_page_message_edit' => 'Write a message',
'titre_page_messagerie' => 'Your messaging',
'titre_page_mots_tous' => 'Keywords',
'titre_page_recherche' => 'Search results @recherche@',
'titre_page_sites_tous' => 'Referenced sites',
'titre_page_statistiques' => 'Statistics by section',
'titre_page_statistiques_referers' => 'Statistics (incoming links)',
'titre_page_statistiques_visites' => 'Visits statistics',
'titre_page_upgrade' => 'SPIP upgrade',
'titre_probleme_technique' => 'Warning: a technical problem (MySQL server) prevents access to this part of the site.<p>Thank you for your understanding.',
'titre_publication_articles_post_dates' => 'Publication of post dated articles',
'titre_publier_document' => 'PUBLISH A DOCUMENT IN THIS SECTION',
'titre_referencement_sites' => 'Sites referencing and syndication',
'titre_referencer_site' => 'Reference the site:',
'titre_rendez_vous' => 'APPOINTMENTS:',
'titre_reparation' => 'Repair',
'titre_site_numero' => 'SITE NUMBER:',
'titre_sites_proposes' => 'Submitted sites',
'titre_sites_references_rubrique' => 'Referenced sites in this section',
'titre_sites_syndiques' => 'Syndicated sites',
'titre_sites_tous' => 'Referenced sites',
'titre_statistiques' => 'Site statistics',
'titre_suivi_petition' => 'Petitions follow-up',
'titre_syndication' => 'Sites syndication',
'titre_titre_document' => 'Document title:'

);


?>
