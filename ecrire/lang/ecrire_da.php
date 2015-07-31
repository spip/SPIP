<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/ecrire_?lang_cible=da
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'activer_plugin' => 'Activer le plugin', # NEW
	'affichage' => 'Affichage', # NEW
	'aide_non_disponible' => 'Denne del af online-hjælpen er endnu ikke tilgængelig på dansk.',
	'annuler_recherche' => 'Annuler la recherche', # NEW
	'auteur' => 'Auteur :', # NEW
	'avis_acces_interdit' => 'Ingen adgang',
	'avis_article_modifie' => 'Advarsel, @nom_auteur_modif@ har arbejdet på denne artikel for @date_diff@ minutter siden',
	'avis_aucun_resultat' => 'Ingen resultater fundet.',
	'avis_base_inaccessible' => 'Impossible de se connecter à la base de données @base@.', # NEW
	'avis_chemin_invalide_1' => 'Den sti som du har valgt',
	'avis_chemin_invalide_2' => 'ser ikke ud til at være gyldig. Gå tilbage til sidste side og kontroller de oplysninger, du har indtastet.',
	'avis_connexion_echec_1' => 'Ingen forbindelse til SQL-serveren', # MODIF
	'avis_connexion_echec_2' => 'Gå tilbage til sidste side og kontroller de oplysninger, du har indtastet',
	'avis_connexion_echec_3' => '<b>NB:</b> På mange servere skal du <b>anmode om</b> at få åbnet adgang til en SQL-database, før du kan bruge den. Hvis du ikke kan etablere en forbindelse, så kontroller venligst at du har indgivet denne anmodning.', # MODIF
	'avis_connexion_erreur_nom_base' => 'Le nom de la base ne peut contenir que des lettres, des chiffres et des tirets', # NEW
	'avis_connexion_ldap_echec_1' => 'Ingen forbindelse til LDAP-serveren',
	'avis_connexion_ldap_echec_2' => 'Gå tilbage til sidste side og kontroller de oplysninger, du har indtastet.',
	'avis_connexion_ldap_echec_3' => 'Alternativt kan du vælge ikke at benytte LDAP til at importere brugere.',
	'avis_deplacement_rubrique' => 'Advarsel! Dette afsnit indeholder @contient_breves@ nyheder@scb@: Hvis du vil flytte den, så afkryds venligst her for at bekræfte.',
	'avis_destinataire_obligatoire' => 'Du skal vælge en modtager, før du kan sende meddelelsen.',
	'avis_erreur_connexion_mysql' => 'Fejl i forbindelse til SQL',
	'avis_erreur_version_archive' => ' <b>Advarsel! Filen @archive@ hører til 
    en anden version af SPIP end den du har
    installeret.</b> Du risikerer store
    vanskeligheder: risiko for at ødelægge din database, 
    forskellige funktionsfejl på webstedet, osv. 
    Fortsæt ikke indlæsningen.<p>For mere
    information henvises til <a href="@spipnet@">,
                                SPIP-dokumentationen</A>.', # MODIF
	'avis_espace_interdit' => '<b>Forbudt område</b><p>SPIP er allerede installeret.',
	'avis_lecture_noms_bases_1' => 'Installationsprogrammet kunne ikke læse navnene på de installerede databaser.',
	'avis_lecture_noms_bases_2' => 'Enten er databasen ikke tilgængelig, eller også er funktionen, som giver oversigt
		over databaser, sat ud af kraft af sikkerhedsårsager (hvilket er tilfældet på mange servere).',
	'avis_lecture_noms_bases_3' => 'Hvis det sidstnævnte er tilfældet, er det muligt at en database, som er navngivet efter dit login, kan anvendes:',
	'avis_non_acces_message' => 'Du har ikke adgang til denne meddelelse.',
	'avis_non_acces_page' => 'Du har ikke adgang til denne side.',
	'avis_operation_echec' => 'Opgaven mislykkedes.',
	'avis_operation_impossible' => 'Opération impossible', # NEW
	'avis_probleme_archive' => 'Læsefejl i filen @archive@',
	'avis_suppression_base' => 'ADVARSEL, sletning kan ikke omgøres',
	'avis_version_mysql' => 'Din version af SQL (@version_mysql@) tillader ikke automatisk reparation af tabeller.',

	// B
	'bouton_acces_ldap' => 'Tilføj adgang til LDAP >>',
	'bouton_ajouter' => 'Tilføj',
	'bouton_ajouter_participant' => 'TILFØJ DELTAGER:',
	'bouton_annonce' => 'ANNONCERING',
	'bouton_annuler' => 'Annuler', # NEW
	'bouton_checkbox_envoi_message' => 'mulighed for at sende en meddelelse',
	'bouton_checkbox_indiquer_site' => 'obligatorisk angivelse af websted ',
	'bouton_checkbox_signature_unique_email' => 'kun en signatur pr. e-mail-adresse',
	'bouton_checkbox_signature_unique_site' => 'kun en signatur pr. websted',
	'bouton_demande_publication' => 'Anmod om at få offentliggjort denne artikel',
	'bouton_desactive_tout' => 'Tout désactiver', # NEW
	'bouton_desinstaller' => 'Désinstaller', # NEW
	'bouton_effacer_index' => 'Slet indeksering',
	'bouton_effacer_tout' => 'Slet alt',
	'bouton_envoi_message_02' => 'SEND MEDDELELSE',
	'bouton_envoyer_message' => 'Send færdig meddelelse',
	'bouton_fermer' => 'Fermer', # NEW
	'bouton_mettre_a_jour_base' => 'Mettre à jour la base de données', # NEW
	'bouton_modifier' => 'Ret',
	'bouton_pense_bete' => 'PERSONLIGT MEMO',
	'bouton_radio_activer_messagerie' => 'Tillad interne meddelelser',
	'bouton_radio_activer_messagerie_interne' => 'Tillad interne meddelelser',
	'bouton_radio_activer_petition' => 'Tillad appeller',
	'bouton_radio_afficher' => 'Vis',
	'bouton_radio_apparaitre_liste_redacteurs_connectes' => 'Medtag i listen over tilknyttede redaktører',
	'bouton_radio_desactiver_messagerie' => 'Slå meddelelsesfunktion fra',
	'bouton_radio_envoi_annonces_adresse' => 'Send nyheder til adressen:',
	'bouton_radio_envoi_liste_nouveautes' => 'Send seneste nyhedsliste',
	'bouton_radio_non_apparaitre_liste_redacteurs_connectes' => 'Medtag ikke i listen over tilknyttede redaktører',
	'bouton_radio_non_envoi_annonces_editoriales' => 'Send ingen redaktionelle nyheder',
	'bouton_radio_pas_petition' => 'Ingen appeller',
	'bouton_radio_petition_activee' => 'Appelfunktion slået til',
	'bouton_radio_supprimer_petition' => 'Slet appellen',
	'bouton_redirection' => 'VIDERESTIL',
	'bouton_relancer_installation' => 'Gentag installationen',
	'bouton_suivant' => 'Næste',
	'bouton_tenter_recuperation' => 'Reparationsforsøg',
	'bouton_test_proxy' => 'Test proxy',
	'bouton_vider_cache' => 'Tøm cache',
	'bouton_voir_message' => 'Vis indlæg før godkendelse',

	// C
	'cache_mode_compresse' => 'The cache files are saved in compressed mode.', # NEW
	'cache_mode_non_compresse' => 'The cache files are written in uncompressed mode.', # NEW
	'cache_modifiable_webmestre' => 'These parameters can be modified by the webmaster.', # NEW
	'calendrier_synchro' => 'Hvis du benytter en kalenderapplikation, der er kompatibel med <b>iCal</b>, kan du synkronisere med information på dette websted.',
	'config_activer_champs' => 'Activer les champs suivants', # NEW
	'config_choix_base_sup' => 'indiquer une base sur ce serveur', # NEW
	'config_erreur_base_sup' => 'SPIP n\'a pas accès à la liste des bases accessibles', # NEW
	'config_info_base_sup' => 'Si vous avez d\'autres bases de données à interroger à travers SPIP, avec son serveur SQL ou avec un autre, le formulaire ci-dessous, vous permet de les déclarer. Si vous laissez certains champs vides, les identifiants de connexion à la base principale seront utilisés.', # NEW
	'config_info_base_sup_disponibles' => 'Bases supplémentaires déjà interrogeables:', # NEW
	'config_info_enregistree' => 'La nouvelle configuration a été enregistrée', # NEW
	'config_info_logos' => 'Chaque élément du site peut avoir un logo, ainsi qu\'un « logo de survol »', # NEW
	'config_info_logos_utiliser' => 'Utiliser les logos', # NEW
	'config_info_logos_utiliser_non' => 'Ne pas utiliser les logos', # NEW
	'config_info_logos_utiliser_survol' => 'Utiliser les logos de survol', # NEW
	'config_info_logos_utiliser_survol_non' => 'Ne pas utiliser les logos de survol', # NEW
	'config_info_redirection' => 'En activant cette option, vous pourrez créer des articles virtuels, simples références d\'articles publiés sur d\'autres sites ou hors de SPIP.', # NEW
	'config_redirection' => 'Articles virtuels', # NEW
	'config_titre_base_sup' => 'Déclaration d\'une base supplémentaire', # NEW
	'config_titre_base_sup_choix' => 'Choisissez une base supplémentaire', # NEW
	'connexion_ldap' => 'Connexion :', # NEW
	'copier_en_local' => 'Copier en local', # NEW
	'creer_et_associer_un_auteur' => 'Créer et associer un auteur', # NEW
	'creer_et_associer_une_rubrique' => 'Créer et associer une rubrique', # NEW

	// D
	'date_mot_heures' => 'timer',

	// E
	'ecran_securite' => ' + écran de sécurité @version@', # NEW
	'email' => 'e-mail',
	'email_2' => 'e-mail:',
	'en_savoir_plus' => 'En savoir plus', # NEW
	'entree_adresse_annuaire' => 'Adresse på kataloget',
	'entree_adresse_email' => 'Din e-mail-adresse',
	'entree_adresse_email_2' => 'Adresse email', # NEW
	'entree_base_donnee_1' => 'Adresse på database',
	'entree_base_donnee_2' => '(Ofte svarer denne adresse til adressen på webstedet, undertiden er den navngivet «localhost», og undertiden skal den være blank.)',
	'entree_biographie' => 'Kort præsentation.',
	'entree_chemin_acces' => '<b>Angiv</b> stien:',
	'entree_cle_pgp' => 'Din PGP nøgle',
	'entree_cle_pgp_2' => 'Clé PGP', # NEW
	'entree_contenu_rubrique' => '(Kort beskrivelse af afsnittets indhold.)',
	'entree_identifiants_connexion' => 'Dine opkoblingsinformationer...',
	'entree_identifiants_connexion_2' => 'Identifiants de connexion', # NEW
	'entree_informations_connexion_ldap' => 'Udfyld denne side med LDAP opkoblingsinformation. Du kan indhente oplysningerne hos din system- eller netværskadministrator.',
	'entree_infos_perso' => 'Hvem er du?',
	'entree_infos_perso_2' => 'Qui est l\'auteur ?', # NEW
	'entree_interieur_rubrique' => 'I afsnit:',
	'entree_liens_sites' => '<b>Hypertekst link</b> (henvisning, websted...)',
	'entree_login' => 'Dit login',
	'entree_login_connexion_1' => 'Tilkoblingslogin',
	'entree_login_connexion_2' => '(Undertiden identisk med dit FTP-login, andre gange blank)',
	'entree_login_ldap' => 'LDAP basis-login',
	'entree_mot_passe' => 'Din adgangskode',
	'entree_mot_passe_1' => 'Tilkoblingsadgangskode',
	'entree_mot_passe_2' => '(Undertiden identisk med dit FTP-login, andre gange blank)',
	'entree_nom_fichier' => 'Indtast filnavn @texte_compresse@:',
	'entree_nom_pseudo' => 'Dit navn eller alias',
	'entree_nom_pseudo_1' => '(navn eller kaldenavn)',
	'entree_nom_pseudo_2' => 'Nom ou pseudo', # NEW
	'entree_nom_site' => 'Dit websteds navn',
	'entree_nom_site_2' => 'Nom du site de l\'auteur', # NEW
	'entree_nouveau_passe' => 'Ny adgangskode',
	'entree_passe_ldap' => 'Adgangskode',
	'entree_port_annuaire' => 'Portnummer på kataloget',
	'entree_signature' => 'Signatur',
	'entree_titre_obligatoire' => '<b>Titel</b> [Skal oplyses]<br />',
	'entree_url' => 'Dit websteds URL',
	'entree_url_2' => 'Adresse (URL) du site', # NEW
	'erreur_connect_deja_existant' => 'Un serveur existe déjà avec ce nom', # NEW
	'erreur_nom_connect_incorrect' => 'Ce nom de serveur n\'est pas autorisé', # NEW
	'erreur_plugin_desinstalation_echouee' => 'La désinstallation du plugin a echoué. Vous pouvez néanmoins le desactiver.', # NEW
	'erreur_plugin_fichier_absent' => 'Fichier absent', # NEW
	'erreur_plugin_fichier_def_absent' => 'Fichier de définition absent', # NEW
	'erreur_plugin_nom_fonction_interdit' => 'Nom de fonction interdit', # NEW
	'erreur_plugin_nom_manquant' => 'Nom du plugin manquant', # NEW
	'erreur_plugin_prefix_manquant' => 'Espace de nommage du plugin non défini', # NEW
	'erreur_plugin_tag_plugin_absent' => '&lt;plugin&gt; manquant dans le fichier de définition', # NEW
	'erreur_plugin_version_manquant' => 'Version du plugin manquante', # NEW

	// H
	'htaccess_a_simuler' => 'Avertissement: la configuration de votre serveur HTTP ne tient pas compte des fichiers @htaccess@. Pour pouvoir assurer une bonne sécurité, il faut que vous modifiiez cette configuration sur ce point, ou bien que les constantes @constantes@ (définissables dans le fichier mes_options.php) aient comme valeur des répertoires en dehors de @document_root@.', # NEW
	'htaccess_inoperant' => 'htaccess inopérant', # NEW

	// I
	'ical_info1' => 'Denne side viser flere måder til at følge med i aktiviteter på dette websted.',
	'ical_info2' => 'For mere information, besøg <a href="@spipnet@">SPIP dokumentation</a>.', # MODIF
	'ical_info_calendrier' => 'To kalendere står til rådighed. Den første er en oversigt over webstedet, der viser alle offentliggjorte artikler.Den anden indeholder både redaktionelle annonceringer og dine seneste private meddelelser. Den er forbeholdt dig i kraft af en personlig nøgle, som du kan ændre når som helst ved at forny din adgangskode.',
	'ical_methode_http' => 'Filhentning',
	'ical_methode_webcal' => 'Synkronisering (webcal://)',
	'ical_texte_js' => 'Med en linies javascript kan du nemt vise de senest offentliggjorte artikler på et websted, der tilhører dig.',
	'ical_texte_prive' => 'Denne strengt personlige kalender holder dig underrettet om private redaktionelle aktiviteter på webstedet (opgaver, personlige aftaler, indsendte artikler, nyheder ...).',
	'ical_texte_public' => 'Med denne kalender kan du følge de offentlige aktiviteter på webstedet (offentliggjorte artikler og nyheder).',
	'ical_texte_rss' => 'Du kan syndikere de seneste nyheder på dette websted i en hvilken som helst XML/RSS (Rich Site Summary) fillæser. Dette format tillader også SPIP at læse de seneste nyheder offenliggjort af andre websteder i et kompatibelt udvekslingsformat.',
	'ical_titre_js' => 'Javascript',
	'ical_titre_mailing' => 'Postliste',
	'ical_titre_rss' => '«Backend» filer',
	'icone_accueil' => 'Accueil', # NEW
	'icone_activer_cookie' => 'Opret administrationscookie',
	'icone_activite' => 'Activité', # NEW
	'icone_admin_plugin' => 'Gestion des plugins', # NEW
	'icone_administration' => 'Maintenance', # NEW
	'icone_afficher_auteurs' => 'Vis forfattere',
	'icone_afficher_visiteurs' => 'Vis besøgende',
	'icone_arret_discussion' => 'Stop deltagelse i denne diskussion',
	'icone_calendrier' => 'Kalender',
	'icone_configuration' => 'Configuration', # NEW
	'icone_creer_auteur' => 'Opret ny forfatter og tilknyt til denne artikel',
	'icone_creer_mot_cle' => 'Opret nyt nøgleord og tilknyt til denne artikel',
	'icone_creer_mot_cle_rubrique' => 'Créer un nouveau mot-clé et le lier à cette rubrique', # NEW
	'icone_creer_mot_cle_site' => 'Créer un nouveau mot-clé et le lier à ce site', # NEW
	'icone_creer_rubrique_2' => 'Opret nyt afsnit',
	'icone_edition' => 'Édition', # NEW
	'icone_envoyer_message' => 'Send denne meddelelse',
	'icone_ma_langue' => 'Ma langue', # NEW
	'icone_mes_infos' => 'Mes informations', # NEW
	'icone_mes_preferences' => 'Mes préférences', # NEW
	'icone_modifier_article' => 'Ret denne artikel',
	'icone_modifier_message' => 'Ret denne meddelelse',
	'icone_modifier_rubrique' => 'Ret dette afsnit',
	'icone_publication' => 'Publication', # NEW
	'icone_relancer_signataire' => 'Relancer le signataire', # NEW
	'icone_retour' => 'Tilbage',
	'icone_retour_article' => 'Tilbage til artikel',
	'icone_squelette' => 'Squelettes', # NEW
	'icone_suivi_publication' => 'Suivi de la publication', # NEW
	'icone_supprimer_cookie' => 'Slet cookier',
	'icone_supprimer_rubrique' => 'Slet dette afsnit',
	'icone_supprimer_signature' => 'Slet denne signatur',
	'icone_valider_signature' => 'Godkend signatur',
	'image_administrer_rubrique' => 'Du kan administrere dette afsnit',
	'impossible_modifier_login_auteur' => 'Impossible de modifier le login.', # NEW
	'impossible_modifier_pass_auteur' => 'Impossible de modifier le mot de passe.', # NEW
	'info_1_article' => '1 artikel',
	'info_1_article_syndique' => '1 article syndiqué', # NEW
	'info_1_auteur' => '1 auteur', # NEW
	'info_1_message' => '1 message', # NEW
	'info_1_mot_cle' => '1 mot-clé', # NEW
	'info_1_rubrique' => '1 rubrique', # NEW
	'info_1_site' => '1 websted',
	'info_1_visiteur' => '1 visiteur', # NEW
	'info_activer_cookie' => 'Du kan installere en <b>administrationscookie</b>, som tillader dig at skifte nemt mellem det offentlige websted og dit private afsnit.',
	'info_admin_etre_webmestre' => 'Me donner les droits de webmestre', # NEW
	'info_admin_gere_rubriques' => 'Denne administrator administrerer følgende afsnit:',
	'info_admin_gere_toutes_rubriques' => 'Denne administrator administrerer <b>alle afsnit</b>.', # MODIF
	'info_admin_gere_toutes_rubriques_2' => 'Je gère <b>toutes les rubriques</b>', # NEW
	'info_admin_je_suis_webmestre' => 'Je suis <b>webmestre</b>', # NEW
	'info_admin_statuer_webmestre' => 'Donner à cet administrateur les droits de webmestre', # NEW
	'info_admin_webmestre' => 'Cet administrateur est <b>webmestre</b>', # NEW
	'info_administrateur' => 'Administrator',
	'info_administrateur_1' => 'Administrator',
	'info_administrateur_2' => 'af webstedet (<i>anvend med forsigtighed</i>)',
	'info_administrateur_site_01' => 'Hvis du er webstedsadministrator, så',
	'info_administrateur_site_02' => 'klik på dette link',
	'info_administrateurs' => 'Administratorer',
	'info_administrer_rubrique' => 'Du kan administrere dette afsnit',
	'info_adresse' => 'til adressen:',
	'info_adresse_url' => 'Dit offentlige websteds URL',
	'info_afficher_par_nb' => 'Afficher par', # NEW
	'info_afficher_visites' => 'Vis besøg for:',
	'info_aide_en_ligne' => 'SPIP online hjælp',
	'info_ajout_image' => 'Når du vedhæfter billeder til en artikel, kan
		SPIP automatisk lave miniatureudgaver af billederne.
		Dette muliggør f.eks. automatisk oprettelse af et
		galleri eller et album.',
	'info_ajout_participant' => 'Følgende deltager er tilføjet:',
	'info_ajouter_rubrique' => 'Tilføj endnu et afsnit at administrere:',
	'info_annonce_nouveautes' => 'Seneste annonceringer',
	'info_anterieur' => 'forrige',
	'info_article' => 'artikel',
	'info_article_2' => 'artikler',
	'info_article_a_paraitre' => 'Fremdaterede artikler der skal offentliggøres',
	'info_articles_02' => 'artikler',
	'info_articles_2' => 'Artikler',
	'info_articles_auteur' => 'Denne forfatters artikler',
	'info_articles_miens' => 'Mes articles', # NEW
	'info_articles_tous' => 'Tous les articles', # NEW
	'info_articles_trouves' => 'Fundne artikler',
	'info_articles_trouves_dans_texte' => 'Artikler fundet (i teksten)',
	'info_attente_validation' => 'Dine artikler som afventer godkendelse',
	'info_aucun_article' => 'Aucun article', # NEW
	'info_aucun_article_syndique' => 'Aucun article syndiqué', # NEW
	'info_aucun_auteur' => 'Aucun auteur', # NEW
	'info_aucun_message' => 'Aucun message', # NEW
	'info_aucun_rubrique' => 'Aucune rubrique', # NEW
	'info_aucun_site' => 'Aucun site', # NEW
	'info_aucun_visiteur' => 'Aucun visiteur', # NEW
	'info_aujourdhui' => 'i dag:',
	'info_auteur_message' => 'MEDDELELSESAFSENDER:',
	'info_auteurs' => 'Forfattere',
	'info_auteurs_par_tri' => 'Forfattere@partri@',
	'info_auteurs_trouves' => 'Forfattere fundet',
	'info_authentification_externe' => 'Ekstern adgangskontrol',
	'info_avertissement' => 'Advarsel',
	'info_barre_outils' => 'avec sa barre d\'outils ?', # NEW
	'info_base_installee' => 'Din databasestruktur er installeret.',
	'info_bio' => 'Biographie', # NEW
	'info_chapeau' => 'Hoved',
	'info_chapeau_2' => 'Indledning:',
	'info_chemin_acces_1' => 'Valgmuligheder: <b>Adgangsvej til katalog</b>',
	'info_chemin_acces_2' => 'Du skal nu konfigurere adgangsvejen til kataloginformationen. Dette er vigtigt for at kunne læse de brugerprofiler, som ligger i kataloget.',
	'info_chemin_acces_annuaire' => 'Valgmuligheder: <b>Adgangsvej til katalog</b>',
	'info_choix_base' => 'Tredje skrift:',
	'info_classement_1' => '<sup>.</sup> af @liste@',
	'info_classement_2' => '<sup>.</sup> af @liste@',
	'info_code_acces' => 'Glem ikke dine egne adgangsoplysninger!',
	'info_compatibilite_html' => 'Norme HTML à suivre', # NEW
	'info_compresseur_gzip' => 'Il est recommandé de vérifier au préalable si l\'hébergeur compresse déjà systématiquement les scripts php ; pour cela, vous pouvez par exemple utiliser le service suivant : @testgzip@', # NEW
	'info_compresseur_texte' => 'Si votre serveur ne comprime pas automatiquement les pages html pour les envoyer aux internautes, vous pouvez essayer de forcer cette compression pour diminuer le poids des pages téléchargées. <b>Attention</b> : cela peut ralentir considerablement certains serveurs.', # NEW
	'info_config_suivi' => 'Hvis denne adresse svarer til en postliste, kan du nedefor angive, hvor webstedets besøgende kan lade sig registrere. Denne adresse kan være en  URL (f.eks. siden med tilmelding til listen via web), eller en e-mail adresse med et særligt emne tilknyttet (f.eks.: <tt>@adresse_suivi@?subject=abonner</tt>):',
	'info_config_suivi_explication' => 'Du kan abonnere på dette websteds postliste. Du vil så via e-mail modtage annonceringer vedrørende artikler og nyheder, der er indsendt til offentliggørelse.',
	'info_confirmer_passe' => 'Bekræft ny adgangskode:',
	'info_conflit_edition_avis_non_sauvegarde' => 'Attention, les champs suivants ont été modifiés par ailleurs. Vos modifications sur ces champs n\'ont donc pas été enregistrées.', # NEW
	'info_conflit_edition_differences' => 'Différences :', # NEW
	'info_conflit_edition_version_enregistree' => 'La version enregistrée :', # NEW
	'info_conflit_edition_votre_version' => 'Votre version :', # NEW
	'info_connexion_base' => 'Andet skrift: <b>Forsøg på opkobling til databasen</b>',
	'info_connexion_base_donnee' => 'Connexion à votre base de données', # NEW
	'info_connexion_ldap_ok' => '<b>Din LDAP-opkobling lykkedes.</b><p> Du kan gå til næste skridt.', # MODIF
	'info_connexion_mysql' => 'Første skridt: <b>Din SQL opkobling</b>',
	'info_connexion_ok' => 'Opkoblingen lykkedes.',
	'info_contact' => 'Kontakt',
	'info_contenu_articles' => 'Artiklens bestanddele',
	'info_contributions' => 'Contributions', # NEW
	'info_creation_paragraphe' => '(For at lave afsnit skal du indsætte blanke linier.)', # MODIF
	'info_creation_rubrique' => 'Før du kan skrive artikler<br /> skal du lave mindst et afsnit.<br />',
	'info_creation_tables' => 'Fjerde skridt: <b>Oprettelse af databasetabeller</b>',
	'info_creer_base' => '<b>Opret</b> en ny database:',
	'info_dans_rubrique' => 'I afsnit:',
	'info_date_publication_anterieure' => 'Dato for tidligere offentliggørelse:',
	'info_date_referencement' => 'DATO FOR HENVISNING TIL DETTE WEBSTED:',
	'info_derniere_etape' => 'Sidste skridt: <b>Det er overstået!',
	'info_derniers_articles_publies' => 'Dine senest offentliggjorte artikler',
	'info_desactiver_messagerie_personnelle' => 'Du kan åbne eller lukke for personlige meddelelser på dette websted.',
	'info_descriptif' => 'Beskrivelse:',
	'info_desinstaller_plugin' => 'supprime les données et désactive le plugin', # NEW
	'info_discussion_cours' => 'Igangværende diskussioner',
	'info_ecrire_article' => 'Før du kan lave artikler, skal du oprette mindst et afsnit.',
	'info_email_envoi' => 'Afsenderens e-mail adresse (valgfri)',
	'info_email_envoi_txt' => 'Indtast afsenderens e-mail adresse ved afsendelse af e-mails (som standard bruges modtagerens adresse som afsenderadresse) :',
	'info_email_webmestre' => 'E-mail-adresse på webmaster (valgfrit)', # MODIF
	'info_entrer_code_alphabet' => 'Indtast koden for det tegnsæt, der skal benyttes:',
	'info_envoi_email_automatique' => 'Automatisk e-mail-forsendelse',
	'info_envoyer_maintenant' => 'Send nu',
	'info_etape_suivante' => 'Gå til næste trin',
	'info_etape_suivante_1' => 'Du kan gå til næste trin.',
	'info_etape_suivante_2' => 'Du kan gå til næste trin.',
	'info_exceptions_proxy' => 'Exceptions pour le proxy', # NEW
	'info_exportation_base' => 'eksporter database til @archive@',
	'info_facilite_suivi_activite' => 'For at lette opfølgning på webstedets redaktionelle aktiviteter sender SPIP e-mails med anmodning om offentliggørelse og godkendelse til f.eks. redaktørens adresseliste.',
	'info_fichiers_authent' => 'Adgangskontrolfil ".htpasswd"',
	'info_forums_abo_invites' => 'Your site contains forums by subscription; visitors may register for them on the public site.', # NEW
	'info_gauche_admin_effacer' => '<b>Kun administratorer har adgang til denne side.</b><p> Den giver adgang til forskellige tekniske vedligeholdelsesopgaver. Nogle af dem giver anledning til en særlig adgangskontrol, der kræver FTP-adgang til siden.', # MODIF
	'info_gauche_admin_tech' => '<b>Kun administratorer har adgang til denne side.</b><p> Den giver adgang til forskellige tekniske vedligeholdelsesopgaver. Nogle af dem giver anledning til en særlig adgangskontrol, der kræver FTP-adgang til siden.', # MODIF
	'info_gauche_admin_vider' => '<b>Kun administratorer har adgang til denne side.</b><p> Den giver adgang til forskellige tekniske vedligeholdelsesopgaver. Nogle af dem giver anledning til en særlig adgangskontrol, der kræver FTP-adgang til siden.', # MODIF
	'info_gauche_auteurs' => 'Her finder du alle webstedets forfattere. Status på hver enkelt fremgår af farven på ikonet (redaktør = grøn, administrator = gul).',
	'info_gauche_auteurs_exterieurs' => 'Udenforstående forfattere uden adgang til webstedet vises med et blåt symbol; slettede forfattere repræsenteres af en papirkurv.', # MODIF
	'info_gauche_messagerie' => 'Meddelelsessystemet giver mulighed for at udveksle meddelelser mellem redaktører, for at gemme huskesedler (til personlig brug) 
	eller for at vise annonceringer i det private område (hvis du er administrator).',
	'info_gauche_numero_auteur' => 'FORFATTER NUMMER:',
	'info_gauche_statistiques_referers' => 'Denne side viser en oversigt over <i>henvisende sider</i>, dvs. websteder der har linket til dit websted alene i dag. Faktisk nulstilles oversigten med 24 timers mellemrum.',
	'info_gauche_visiteurs_enregistres' => 'Her finder du de besøgende, der er tilmeldt til webstedets offentlige afsnit (fora med tilmelding).',
	'info_generation_miniatures_images' => 'Dannelse af piktogrammer',
	'info_gerer_trad' => 'Danne link til oversættelse?',
	'info_gerer_trad_objets' => '@objets@ : gérer les liens de traduction', # NEW
	'info_hebergeur_desactiver_envoi_email' => 'Nogle webhoteller tillader ikke automatisk udsendelse af e-mails. I så fald kan følgende funktioner i SPIP ikke benyttes.',
	'info_hier' => 'i går:',
	'info_historique_activer' => 'Enable revisions follow-up', # NEW
	'info_historique_affiche' => 'Display this version', # NEW
	'info_historique_comparaison' => 'compare', # NEW
	'info_historique_desactiver' => 'Disable revisions follow-up', # NEW
	'info_historique_texte' => 'Revisions follow-up allows you to keep track of every modifications added to an article and displays the differences between successive versions.', # NEW
	'info_identification_publique' => 'Din offentlige identitet...',
	'info_image_process' => 'Vælg den bedste metode til at skabe miniaturebilleder ved at klikke på det korresponderende billede.',
	'info_image_process2' => '<b>N.B.</b> <i>If you can\'t see any image, then your server is not configured to use such tools. If you want to use these features, contact your provider\'s technical support and ask for the «GD» or «Imagick» extensions to be installed.</i>', # MODIF
	'info_images_auto' => 'Images calculées automatiquement', # NEW
	'info_informations_personnelles' => 'Femte trin: <b>Personlig information</b>',
	'info_inscription_automatique' => 'Automatisk registrering af nye redaktører',
	'info_jeu_caractere' => 'Webstedets tegnsæt',
	'info_jours' => 'dage',
	'info_laisser_champs_vides' => 'efterlad disse felter tomme)',
	'info_langues' => 'Webstedets sprog',
	'info_ldap_ok' => 'LDAP adgangskontrol er installeret.',
	'info_lien_hypertexte' => 'Hypertekst link:',
	'info_liste_nouveautes_envoyee' => 'La liste des nouveautés a été envoyée', # NEW
	'info_liste_redacteurs_connectes' => 'Oversigt over tilknyttede reaktører',
	'info_login_existant' => 'Dette login findes allerede.',
	'info_login_trop_court' => 'Login for kort.',
	'info_login_trop_court_car_pluriel' => 'Le login doit contenir au moins @nb@ caractères.', # NEW
	'info_logos' => 'Les logos', # NEW
	'info_maximum' => 'maksimum:',
	'info_meme_rubrique' => 'In the same section', # NEW
	'info_message' => 'Meddelelse fra',
	'info_message_efface' => 'MEDDELELSE SLETTET',
	'info_message_en_redaction' => 'Dine meddelelser under redaktion',
	'info_message_technique' => 'Teknisk meddelelse:',
	'info_messagerie_interne' => 'Interne meddelelser',
	'info_mise_a_niveau_base' => 'SQL databaseopgradering',
	'info_mise_a_niveau_base_2' => '{{Advarsel!}} Du har installeret en version af SPIP-filer, der er ældre end dem, der var på webstedet i forvejen. Du risikerer at miste databasen og webstedet vil ikke fungere ordentligt mere.<br />{{Geninstraller SPIP-filerne.}}',
	'info_modification_enregistree' => 'Votre modification a été enregistrée', # NEW
	'info_modifier_auteur' => 'Modifier l\'auteur :', # NEW
	'info_modifier_rubrique' => 'Ret afsnit:',
	'info_modifier_titre' => 'Ret: @titre@',
	'info_mon_site_spip' => 'Mit SPIP-websted',
	'info_mot_sans_groupe' => '(Nøgleord uden en gruppe...)',
	'info_moteur_recherche' => 'Indbygget søgemaskine',
	'info_moyenne' => 'gennemsnit:',
	'info_multi_articles' => 'Muliggøre valg af sprog til artiklerne?',
	'info_multi_cet_article' => 'Denne artikel er på:',
	'info_multi_langues_choisies' => 'Vælg de sprog der skal være til rådighed for redaktører på webstedet.
  Sprog der allerede er i brug på webstedet (de øverste på listen) kan ikke fravælges.
 ',
	'info_multi_objets' => '@objets@ : activer le menu de langue', # NEW
	'info_multi_rubriques' => 'Muliggøre sprogvalg til afsnit?',
	'info_multi_secteurs' => 'Kun for afsnit placeret i roden ?',
	'info_nb_articles' => '@nb@ articles', # NEW
	'info_nb_articles_syndiques' => '@nb@ articles syndiqués', # NEW
	'info_nb_auteurs' => '@nb@ auteurs', # NEW
	'info_nb_messages' => '@nb@ messages', # NEW
	'info_nb_mots_cles' => '@nb@ mots-clés', # NEW
	'info_nb_rubriques' => '@nb@ rubriques', # NEW
	'info_nb_sites' => '@nb@ sites', # NEW
	'info_nb_visiteurs' => '@nb@ visiteurs', # NEW
	'info_nom' => 'Navn',
	'info_nom_destinataire' => 'Navn på modtager',
	'info_nom_site' => 'Dit websteds navn',
	'info_nombre_articles' => '@nb_articles@ artikler,',
	'info_nombre_partcipants' => 'DELTAGERE I DISKUSSIONEN:',
	'info_nombre_rubriques' => '@nb_rubriques@ afsnit',
	'info_nombre_sites' => '@nb_sites@ websteder,',
	'info_non_deplacer' => 'Flyt ikke...',
	'info_non_envoi_annonce_dernieres_nouveautes' => 'SPIP kan udsende webstedets seneste indlæg regelmæssigt.
		(nyligt offentliggjorte artikler og nyheder).',
	'info_non_envoi_liste_nouveautes' => 'Send ikke oversigt over seneste nyheder',
	'info_non_modifiable' => 'kan ikke ændres',
	'info_non_suppression_mot_cle' => 'Jeg ønsker ikke at slette dette nøgleord.',
	'info_note_numero' => 'Note @numero@', # NEW
	'info_notes' => 'Fodnoter',
	'info_nouveaux_message' => 'Nye meddelelser',
	'info_nouvel_article' => 'Ny artikel',
	'info_nouvelle_traduction' => 'Ny oversættelse:',
	'info_numero_article' => 'ARTIKEL NUMMER:',
	'info_obligatoire_02' => '[Skal udfyldes]', # MODIF
	'info_option_accepter_visiteurs' => 'Allow visitors registration from the public site', # NEW
	'info_option_faire_suivre' => 'Videresend meddelelser i forummer til artiklernes forfattere',
	'info_option_ne_pas_accepter_visiteurs' => 'Refuse visitor registration', # NEW
	'info_options_avancees' => 'AVANCEREDE INDSTILLINGER',
	'info_ortho_activer' => 'Enable the spell checker.', # NEW
	'info_ortho_desactiver' => 'Disable the spell checker.', # NEW
	'info_ou' => 'eller...',
	'info_page_interdite' => 'Forbudt side',
	'info_par_nom' => 'par nom', # NEW
	'info_par_nombre_article' => '(efter antal artiker)',
	'info_par_statut' => 'par statut', # NEW
	'info_par_tri' => '\'(par @tri@)\'', # NEW
	'info_passe_trop_court' => 'Adgangskode for kort.',
	'info_passe_trop_court_car_pluriel' => 'Le mot de passe doit contenir au moins @nb@ caractères.', # NEW
	'info_passes_identiques' => 'De to adgangskoder er ikke ens.',
	'info_pense_bete_ancien' => 'Dine gamle huskesedler', # MODIF
	'info_plus_cinq_car' => 'mere end 5 tegn',
	'info_plus_cinq_car_2' => '(Mere end 5 tegn)',
	'info_plus_trois_car' => '(Mere end 3 tegn)',
	'info_popularite' => 'popularitet: @popularite@; besøg: @visites@',
	'info_popularite_4' => 'polularitet: @popularite@; besøg: @visites@',
	'info_post_scriptum' => 'Efterskrift',
	'info_post_scriptum_2' => 'Efterskrift:',
	'info_pour' => 'til',
	'info_preview_admin' => 'Only administrators have access to the preview mode', # NEW
	'info_preview_comite' => 'All authors have access to the preview mode', # NEW
	'info_preview_desactive' => 'Preview mode is disabled', # NEW
	'info_preview_texte' => 'It is possible to preview the site as if all articles and news items (which have at least the status "submitted") were already published. Should this preview mode be restricted to administrators, open to all authors, or disabled completely?', # NEW
	'info_principaux_correspondants' => 'Dine hovedbidragydere',
	'info_procedez_par_etape' => 'gå frem skridt for skridt',
	'info_procedure_maj_version' => 'opgraderingsprocdeduren bør følges for at tilpasse databasen til den nye version af SPIP.',
	'info_proxy_ok' => 'Test du proxy réussi.', # NEW
	'info_ps' => 'P.S.',
	'info_publier' => 'publier', # NEW
	'info_publies' => 'Dine offentliggjorte artikler',
	'info_question_accepter_visiteurs' => 'If your site\'s templates allow visitors to register without entering the private area, please activate the following option:', # NEW
	'info_question_inscription_nouveaux_redacteurs' => 'Vil du tillade, at nye redaktører tilmelder sig
		på det offentligt tilgængelige websted? Ja betyder, at besøgende kan tilmelde sig på en automatisk dannet formular,
		og derefter få adgang til det private område, hvor de kan vedligeholde deres egne artikler.
		<blockquote><i>Under tilmeldingen modtager brugerne en automatisk dannet e-mail med deres adgangskode til det
		private websted. Nogle webhoteller tillader ikke at der sendes e-mails fra deres servere. I så fald kan automatisk
		tilmelding ikke finde sted.', # MODIF
	'info_question_utilisation_moteur_recherche' => 'Ønsker du at anvende den søgefunktion, der findes i SPIP?
	(At fravælge søgefunktionen gør webstedet hurtigere.)',
	'info_question_vignettes_referer_non' => 'Ne pas afficher les captures des sites d\'origine des visites', # NEW
	'info_qui_edite' => '@nom_auteur_modif@ a travaillé sur ce contenu il y a @date_diff@ minutes', # MODIF
	'info_racine_site' => 'Top',
	'info_recharger_page' => 'Vær venlig at genindlæse denne side om et øjeblik.',
	'info_recherche_auteur_a_affiner' => 'For mange resultater fundet til "@cherche_auteur@"; vær venlig at afgrænse søgningen mere.',
	'info_recherche_auteur_ok' => 'Der er fundet flere redaktører til "@cherche_auteur@":',
	'info_recherche_auteur_zero' => '<b>Ingen resultater fundet til "@cherche_auteur@".',
	'info_recommencer' => 'Vær venlig at forsøge igen.',
	'info_redacteur_1' => 'Redaktør',
	'info_redacteur_2' => 'med adgang til det private område (<i>anbefalet</i>)',
	'info_redacteurs' => 'Redaktører',
	'info_redaction_en_cours' => 'REDIGERING ER IGANG',
	'info_redirection' => 'Viderestilling',
	'info_redirection_activee' => 'La redirection est activée.', # NEW
	'info_redirection_desactivee' => 'La redirection a été supprimée.', # NEW
	'info_refuses' => 'Dine artikler er afvist',
	'info_reglage_ldap' => 'Muligheder: <b>Konfigurere LDAP understøttelse</b>',
	'info_renvoi_article' => '<b>Viderestilling.</b> Denne artikel henviser til siden:',
	'info_reserve_admin' => 'Kun administratorer kan ændre denne adresse.',
	'info_restreindre_rubrique' => 'Begræns administrationsrettigheder til dette afsnit:',
	'info_resultat_recherche' => 'Søgeresultater:',
	'info_rubriques' => 'Afsnit',
	'info_rubriques_02' => 'afsnit',
	'info_rubriques_trouvees' => 'Afsnit fundet',
	'info_rubriques_trouvees_dans_texte' => 'Afsnit fundet (i teksten)',
	'info_sans_titre' => 'Uden overskrift',
	'info_selection_chemin_acces' => '<b>Vælg</b> nedenfor stien til kataloget:',
	'info_signatures' => 'underskrifter',
	'info_site' => 'Websted',
	'info_site_2' => 'websted:',
	'info_site_min' => 'websted',
	'info_site_reference_2' => 'Henvisning',
	'info_site_web' => 'WEBSTED:', # MODIF
	'info_sites' => 'websteder',
	'info_sites_lies_mot' => 'Links til websteder knyttet til dette nøgleord',
	'info_sites_proxy' => 'Brug proxy',
	'info_sites_trouves' => 'Websteder fundet',
	'info_sites_trouves_dans_texte' => 'Websteder fundet (i teksten)',
	'info_sous_titre' => 'Underrubrik:',
	'info_statut_administrateur' => 'Administrator',
	'info_statut_auteur' => 'Denne forfatters status:', # MODIF
	'info_statut_auteur_2' => 'Je suis', # NEW
	'info_statut_auteur_a_confirmer' => 'Inscription à confirmer', # NEW
	'info_statut_auteur_autre' => 'Autre statut :', # NEW
	'info_statut_efface' => 'Slettet',
	'info_statut_redacteur' => 'Redaktør',
	'info_statut_utilisateurs_1' => 'Importerede brugeres standardstatus',
	'info_statut_utilisateurs_2' => 'Vælg den status som skal tildeles personerne i LDAP kataloget, når de logger ind første gang. Senere kan du ændre værdien for hver forfatter fra sag til sag.',
	'info_suivi_activite' => 'Opfølgning på redaktionelle aktiviteter',
	'info_surtitre' => 'Hovedoverskrift:',
	'info_syndication_integrale_1' => 'Votre site propose des fichiers de syndication (voir « <a href="@url@">@titre@</a> »).', # NEW
	'info_syndication_integrale_2' => 'Souhaitez-vous transmettre les articles dans leur intégralité, ou ne diffuser qu\'un résumé de quelques centaines de caractères ?', # NEW
	'info_table_prefix' => 'Vous pouvez modifier le préfixe du nom des tables de données (ceci est indispensable lorsque l\'on souhaite installer plusieurs sites dans la même base de données). Ce préfixe s\'écrit en lettres minuscules, non accentuées, et sans espace.', # NEW
	'info_taille_maximale_images' => 'SPIP va tester la taille maximale des images qu\'il peut traiter (en millions de pixels).<br /> Les images plus grandes ne seront pas réduites.', # NEW
	'info_taille_maximale_vignette' => 'Max. størrelse på piktogram dannet af systemet:',
	'info_terminer_installation' => 'Du kan nu afslutte standardinstallationen.',
	'info_texte' => 'Tekst',
	'info_texte_explicatif' => 'Forklarende tekst',
	'info_texte_long' => '(teksten er for lang: den vil blive opdelt i flere dele, som vil blive sat sammen efter godkendelse.)',
	'info_texte_message' => 'Meddelelsens tekst:', # MODIF
	'info_texte_message_02' => 'Meddelelsens tekst',
	'info_titre' => 'Overskrift:',
	'info_total' => 'ialt:',
	'info_tous_articles_en_redaction' => 'Alle artikler undervejs',
	'info_tous_articles_presents' => 'Alle artikler offentliggjort i dette afsnit',
	'info_tous_articles_refuses' => 'Tous les articles refusés', # NEW
	'info_tous_les' => 'for hver:',
	'info_tous_redacteurs' => 'Annonceringer til alle redaktører',
	'info_tout_site' => 'Hele webstedet',
	'info_tout_site2' => 'Artiklen er ikke blevet oversat til dette sprog.',
	'info_tout_site3' => 'Artiklen er blevet oversat til dette sprig, men nogle ændringer er senere blevet tilføjet til referenceartiklen. Oversættelsen skal opdateres.   ',
	'info_tout_site4' => 'Artiklen er blevet oversat til dette sprog og oversættelsen er opdateret.',
	'info_tout_site5' => 'Den oprindelige artikel.',
	'info_tout_site6' => '<b>Advarsel:</b> kun de oprindelige artikler vises.
Oversættelserne er tilknyttet den oprindelige artikel 
i en farve, der angiver deres status:',
	'info_traductions' => 'Traductions', # NEW
	'info_travail_colaboratif' => 'Samarbejde om artikler',
	'info_un_article' => 'en artikel,',
	'info_un_site' => 'et websted,',
	'info_une_rubrique' => 'et afsnit,',
	'info_une_rubrique_02' => '1 afsnit',
	'info_url' => 'URL:',
	'info_url_proxy' => 'URL du proxy', # NEW
	'info_url_site' => 'WEBSTEDETS URL:',
	'info_url_test_proxy' => 'URL de test', # NEW
	'info_urlref' => 'Hyperlink:',
	'info_utilisation_spip' => 'SPIP er nu klar til brug...',
	'info_visites_par_mois' => 'Besøg pr. måned:',
	'info_visiteur_1' => 'Besøgende',
	'info_visiteur_2' => 'på den offentligt tilgængelige websted',
	'info_visiteurs' => 'Besøgende',
	'info_visiteurs_02' => 'Besøgende på offentligt websted',
	'info_webmestre_forces' => 'Les webmestres sont actuellement définis dans <tt>@file_options@</tt>.', # NEW
	'install_adresse_base_hebergeur' => 'Adresse de la base de données attribuée par l\'hébergeur', # NEW
	'install_base_ok' => 'La base @base@ a été reconnue', # NEW
	'install_connect_ok' => 'La nouvelle base a bien été déclarée sous le nom de serveur @connect@.', # NEW
	'install_echec_annonce' => 'L\'installation va probablement échouer, ou aboutir à un site non fonctionnel...', # NEW
	'install_extension_mbstring' => 'SPIP ne fonctionne pas avec :', # NEW
	'install_extension_php_obligatoire' => 'SPIP exige l\'extension php :', # NEW
	'install_login_base_hebergeur' => 'Login de connexion attribué par l\'hébergeur', # NEW
	'install_nom_base_hebergeur' => 'Nom de la base attribué par l\'hébergeur :', # NEW
	'install_pas_table' => 'Base actuellement sans tables', # NEW
	'install_pass_base_hebergeur' => 'Mot de passe de connexion attribué par l\'hébergeur', # NEW
	'install_php_version' => 'PHP version @version@ insuffisant (minimum = @minimum@)', # NEW
	'install_select_langue' => 'Vælg et sprog og klik derefter på knappen «næste» for at igangsætte installationen.',
	'install_select_type_db' => 'Indiquer le type de base de données :', # NEW
	'install_select_type_mysql' => 'MySQL', # NEW
	'install_select_type_pg' => 'PostgreSQL', # NEW
	'install_select_type_sqlite2' => 'SQLite 2', # NEW
	'install_select_type_sqlite3' => 'SQLite 3', # NEW
	'install_serveur_hebergeur' => 'Serveur de base de données attribué par l\'hébergeur', # NEW
	'install_table_prefix_hebergeur' => 'Préfixe de table attribué par l\'hébergeur :', # NEW
	'install_tables_base' => 'Tables de la base', # NEW
	'install_types_db_connus' => 'SPIP sait utiliser <b>MySQL</b> (le plus répandu), <b>PostgreSQL</b> et <b>SQLite</b>.', # NEW
	'install_types_db_connus_avertissement' => 'Attention : plusieurs plugins ne fonctionnent qu\'avec MySQL', # NEW
	'instituer_erreur_statut_a_change' => 'Le statut a déjà été modifié', # NEW
	'instituer_erreur_statut_non_autorise' => 'Vous ne pouvez pas choisir ce statut', # NEW
	'intem_redacteur' => 'redaktør',
	'intitule_licence' => 'Licence', # NEW
	'item_accepter_inscriptions' => 'Tillad tilmeldinger',
	'item_activer_messages_avertissement' => 'Tillad advarselsmeddelelser',
	'item_administrateur_2' => 'administrator',
	'item_afficher_calendrier' => 'Vis i kalenderen',
	'item_autoriser_documents_joints' => 'Tillad at vedhæfte dokumenter til artikler',
	'item_autoriser_documents_joints_rubriques' => 'Tillad dokumenter i afsnit',
	'item_autoriser_syndication_integrale' => 'Diffuser l\'intégralité des articles dans les fichiers de syndication', # NEW
	'item_choix_administrateurs' => 'administratorer',
	'item_choix_generation_miniature' => 'Dan miniaturepiktogrammer automatisk.',
	'item_choix_non_generation_miniature' => 'Dan ikke miniaturebilleder.',
	'item_choix_redacteurs' => 'redaktører',
	'item_choix_visiteurs' => 'besøgende på den offentlige websted',
	'item_creer_fichiers_authent' => 'Dan .htpasswd filer',
	'item_limiter_recherche' => 'Begræns søgning til information på din eget websted',
	'item_login' => 'Login',
	'item_messagerie_agenda' => 'Activer la messagerie et l’agenda', # NEW
	'item_mots_cles_association_articles' => 'artiklerne',
	'item_mots_cles_association_rubriques' => 'afsnittene',
	'item_mots_cles_association_sites' => 'de linkede eller syndikerede websteder.',
	'item_non' => 'Nej',
	'item_non_accepter_inscriptions' => 'Tillad ikke tilmelding',
	'item_non_activer_messages_avertissement' => 'Ingen advarselsmeddelelser',
	'item_non_afficher_calendrier' => 'Vis ikke i kalender',
	'item_non_autoriser_documents_joints' => 'Tillad ikke vedhæftning af dokumenter til artikler',
	'item_non_autoriser_documents_joints_rubriques' => 'Tillad ikke dokumenter i afsnit',
	'item_non_autoriser_syndication_integrale' => 'Ne diffuser qu\'un résumé', # NEW
	'item_non_compresseur' => 'Désactiver la compression', # NEW
	'item_non_creer_fichiers_authent' => 'Dan ikke disse filer',
	'item_non_gerer_statistiques' => 'Dan ikke statistik',
	'item_non_limiter_recherche' => 'Udvid søgning til indholdet i linkede websteder',
	'item_non_messagerie_agenda' => 'Désactiver la messagerie et l’agenda', # NEW
	'item_non_publier_articles' => 'Vent med at offentliggøre artikler til deres publiceringsdato.',
	'item_non_utiliser_moteur_recherche' => 'Benyt ikke søgefunktion',
	'item_nouvel_auteur' => 'Ny forfatter',
	'item_nouvelle_rubrique' => 'Nyt afsnit',
	'item_oui' => 'Ja',
	'item_publier_articles' => 'Offentliggør artikler uden hensyn til deres publiceringsdato.',
	'item_reponse_article' => 'Kommenter artiklen',
	'item_utiliser_moteur_recherche' => 'Benyt søgefunktion',
	'item_version_html_max_html4' => 'Se limiter au HTML4 sur le site public', # NEW
	'item_version_html_max_html5' => 'Permettre le HTML5', # NEW
	'item_visiteur' => 'besøgende',

	// J
	'jour_non_connu_nc' => ' ',

	// L
	'label_bando_outils' => 'Barre d\'outils', # NEW
	'label_bando_outils_afficher' => 'Afficher les outils', # NEW
	'label_bando_outils_masquer' => 'Masquer les outils', # NEW
	'label_choix_langue' => 'Selectionnez votre langue', # NEW
	'label_nom_fichier_connect' => 'Indiquez le nom utilisé pour ce serveur', # NEW
	'label_slogan_site' => 'Slogan du site', # NEW
	'label_taille_ecran' => 'Largeur de l\'ecran', # NEW
	'label_texte_et_icones_navigation' => 'Menu de navigation', # NEW
	'label_texte_et_icones_page' => 'Affichage dans la page', # NEW
	'ldap_correspondance' => 'héritage du champ @champ@', # NEW
	'ldap_correspondance_1' => 'Héritage des champs LDAP', # NEW
	'ldap_correspondance_2' => 'Pour chacun des champs SPIP suivants, indiquer le nom du champ LDAP correspondant. Laisser vide pour ne pas le remplir, séparer par des espaces ou des virgules pour essayer plusieurs champs LDAP.', # NEW
	'lien_ajout_destinataire' => 'Tilføj denne modtager',
	'lien_ajouter_auteur' => 'Tilføj denne forfatter',
	'lien_ajouter_participant' => 'Tilføj modtager',
	'lien_ajouter_une_rubrique' => 'Ajouter cette rubrique', # NEW
	'lien_email' => 'e-mail',
	'lien_nom_site' => 'WEBSTEDETS NAVN:',
	'lien_retirer_auteur' => 'Fjern forfatter',
	'lien_retirer_rubrique' => 'Retirer la rubrique', # NEW
	'lien_retirer_tous_auteurs' => 'Retirer tous les auteurs', # NEW
	'lien_retirer_toutes_rubriques' => 'Retirer toutes les rubriques', # NEW
	'lien_retrait_particpant' => 'fjern denne modtager',
	'lien_site' => 'websted',
	'lien_supprimer_rubrique' => 'slet dette afsnit',
	'lien_tout_deplier' => 'Udfold alle',
	'lien_tout_replier' => 'Sammenfold alle',
	'lien_tout_supprimer' => 'Tout supprimer', # NEW
	'lien_trier_nom' => 'Sorter efter navn',
	'lien_trier_nombre_articles' => 'Sorter efter antal artikler',
	'lien_trier_statut' => 'Sorter efter status',
	'lien_voir_en_ligne' => 'SE ONLINE:',
	'logo_article' => 'LOGO TIL ARTIKLEN', # MODIF
	'logo_auteur' => 'LOGO TIL FORFATTEREN', # MODIF
	'logo_rubrique' => 'LOGO TIL AFSNITTETS', # MODIF
	'logo_site' => 'LOGO TIL WEBSTEDETS', # MODIF
	'logo_standard_rubrique' => 'STANDARDLOGO TIL AFSNIT', # MODIF
	'logo_survol' => 'PEGEFØLSOMT LOGO', # MODIF

	// M
	'menu_aide_installation_choix_base' => 'Valg af database',
	'module_fichier_langue' => 'Sprogfil',
	'module_raccourci' => 'Genvej',
	'module_texte_affiche' => 'Vist tekst',
	'module_texte_explicatif' => 'Du kan indsætte følgende genveje i dit websteds skabeloner. De vil automatisk blive oversat til de forskellige sprog, som der findes sprogfiler til.',
	'module_texte_traduction' => 'Sprogfilen « @module@ » findes på:',
	'mois_non_connu' => 'ukendt',

	// N
	'nouvelle_version_spip' => 'La version @version@ de SPIP est disponible', # NEW

	// O
	'onglet_contenu' => 'Contenu', # NEW
	'onglet_declarer_une_autre_base' => 'Déclarer une autre base', # NEW
	'onglet_discuter' => 'Discuter', # NEW
	'onglet_documents' => 'Documents', # NEW
	'onglet_interactivite' => 'Interactivité', # NEW
	'onglet_proprietes' => 'Propriétés', # NEW
	'onglet_repartition_actuelle' => 'nu',
	'onglet_sous_rubriques' => 'Sous-rubriques', # NEW

	// P
	'page_pas_proxy' => 'Cette page ne doit pas passer par le proxy', # NEW
	'pas_de_proxy_pour' => 'Au besoin, indiquez les machines ou domaines pour lesquels ce proxy ne doit pas s\'appliquer (par exemple : @exemple@)', # NEW
	'plugin_charge_paquet' => 'Chargement du paquet @name@', # NEW
	'plugin_charger' => 'Télécharger', # NEW
	'plugin_erreur_charger' => 'erreur : impossible de charger @zip@', # NEW
	'plugin_erreur_droit1' => 'Le répertoire <code>@dest@</code> n\'est pas accessible en écriture.', # NEW
	'plugin_erreur_droit2' => 'Veuillez vérifier les droits sur ce répertoire (et le créer le cas échéant), ou installer les fichiers par FTP.', # NEW
	'plugin_erreur_zip' => 'echec pclzip : erreur @status@', # NEW
	'plugin_etat_developpement' => 'en développement', # NEW
	'plugin_etat_experimental' => 'expérimental', # NEW
	'plugin_etat_stable' => 'stable', # NEW
	'plugin_etat_test' => 'en test', # NEW
	'plugin_impossible_activer' => 'Impossible d\'activer le plugin @plugin@', # NEW
	'plugin_info_automatique1' => 'Si vous souhaitez autoriser l\'installation automatique des plugins, veuillez :', # NEW
	'plugin_info_automatique1_lib' => 'Si vous souhaitez autoriser l\'installation automatique de cette librairie, veuillez :', # NEW
	'plugin_info_automatique2' => 'créer un répertoire <code>@rep@</code> ;', # NEW
	'plugin_info_automatique3' => 'vérifier que le serveur est autorisé à écrire dans ce répertoire.', # NEW
	'plugin_info_automatique_creer' => 'à créer à la racine du site.', # NEW
	'plugin_info_automatique_exemples' => 'exemples :', # NEW
	'plugin_info_automatique_ftp' => 'Vous pouvez installer des plugins, par FTP, dans le répertoire <tt>@rep@</tt>', # NEW
	'plugin_info_automatique_lib' => 'Certains plugins demandent aussi à pouvoir télécharger des fichiers dans le répertoire <code>lib/</code>, à créer le cas échéant à la racine du site.', # NEW
	'plugin_info_automatique_liste' => 'Vos listes de plugins :', # NEW
	'plugin_info_automatique_liste_officielle' => 'les plugins officiels', # NEW
	'plugin_info_automatique_liste_update' => 'Mettre à jour les listes', # NEW
	'plugin_info_automatique_ou' => 'ou...', # NEW
	'plugin_info_automatique_select' => 'Sélectionnez ci-dessous un plugin : SPIP le téléchargera et l\'installera dans le répertoire <code>@rep@</code> ; si ce plugin existe déjà, il sera mis à jour.', # NEW
	'plugin_info_credit' => 'Crédits', # NEW
	'plugin_info_erreur_xml' => 'La déclaration de ce plugin est incorrecte', # NEW
	'plugin_info_install_ok' => 'Installation réussie', # NEW
	'plugin_info_necessite' => 'Necessite :', # NEW
	'plugin_info_non_compatible_spip' => 'Ce plugin n\'est pas compatible avec cette version de SPIP', # NEW
	'plugin_info_plugins_dist_1' => 'Les extensions ci-dessous sont chargées et activées dans le répertoire @plugins_dist@.', # NEW
	'plugin_info_plugins_dist_2' => 'Elles ne sont pas désactivables.', # NEW
	'plugin_info_telecharger' => 'à télécharger depuis @url@ et à installer dans @rep@', # NEW
	'plugin_librairies_installees' => 'Librairies installées', # NEW
	'plugin_necessite_lib' => 'Ce plugin nécessite la librairie @lib@', # NEW
	'plugin_necessite_plugin' => 'Nécessite le plugin @plugin@ en version @version@ minimum.', # NEW
	'plugin_necessite_plugin_sans_version' => 'Nécessite le plugin @plugin@', # NEW
	'plugin_necessite_spip' => 'Nécessite SPIP en version @version@ minimum.', # NEW
	'plugin_source' => 'source: ', # NEW
	'plugin_titre_automatique' => 'Installation automatique', # NEW
	'plugin_titre_automatique_ajouter' => 'Ajouter des plugins', # NEW
	'plugin_titre_installation' => 'Installation du plugin @plugin@', # NEW
	'plugin_titre_modifier' => 'Mes plugins', # NEW
	'plugin_zip_active' => 'Continuez pour l\'activer', # NEW
	'plugin_zip_adresse' => 'indiquez ci-dessous l\'adresse d\'un fichier zip de plugin à télécharger, ou encore l\'adresse d\'une liste de plugins.', # NEW
	'plugin_zip_adresse_champ' => 'Adresse du plugin ou de la liste ', # NEW
	'plugin_zip_content' => 'Il contient les fichiers suivants (@taille@),<br />prêts à installer dans le répertoire <code>@rep@</code>', # NEW
	'plugin_zip_installe_finie' => 'Le fichier @zip@ a été décompacté et installé.', # NEW
	'plugin_zip_installe_rep_finie' => 'Le fichier @zip@ a été décompacté et installé dans le répertoire @rep@', # NEW
	'plugin_zip_installer' => 'Vous pouvez maintenant l\'installer.', # NEW
	'plugin_zip_telecharge' => 'Le fichier @zip@ a été téléchargé', # NEW
	'plugins_actif_aucun' => 'Aucun plugin activé.', # NEW
	'plugins_actif_un' => 'Un plugin activé.', # NEW
	'plugins_actifs' => '@count@ plugins activés.', # NEW
	'plugins_actifs_liste' => 'Actifs', # NEW
	'plugins_compte' => '@count@ plugins', # NEW
	'plugins_disponible_un' => 'Un plugin disponible.', # NEW
	'plugins_disponibles' => '@count@ plugins disponibles.', # NEW
	'plugins_erreur' => 'Erreur dans les plugins : @plugins@', # NEW
	'plugins_liste' => 'Liste des plugins', # NEW
	'plugins_liste_dist' => 'Extensions', # NEW
	'plugins_recents' => 'Plugins récents.', # NEW
	'plugins_tous_liste' => 'Tous', # NEW
	'plugins_vue_hierarchie' => 'Hiérarchie', # NEW
	'plugins_vue_liste' => 'Liste', # NEW
	'protocole_ldap' => 'Version du protocole :', # NEW

	// Q
	'queue_executer_maintenant' => 'Exécuter maintenant', # NEW
	'queue_info_purger' => 'Vous pouvez supprimer tous les travaux en attente et ré-inialiser la liste avec les travaux périodiques', # NEW
	'queue_nb_jobs_in_queue' => '@nb@ travaux en attente', # NEW
	'queue_next_job_in_nb_sec' => 'Prochain travail dans @nb@ s', # NEW
	'queue_no_job_in_queue' => 'Aucun travail en attente', # NEW
	'queue_one_job_in_queue' => '1 travail en attente', # NEW
	'queue_purger_queue' => 'Purger la liste des travaux', # NEW
	'queue_titre' => 'Liste des travaux', # NEW

	// R
	'repertoire_plugins' => 'Répertoire :', # NEW

	// S
	'sans_heure' => 'sans heure', # NEW
	'statut_admin_restreint' => '(begrænset admin)',

	// T
	'tache_cron_asap' => 'Tache CRON @function@ (ASAP)', # NEW
	'tache_cron_secondes' => 'Tache CRON @function@ (toutes les @nb@ s)', # NEW
	'taille_cache_image' => 'Les images calculées automatiquement par SPIP (vignettes des documents, titres présentés sous forme graphique, fonctions mathématiques au format TeX...) occupent dans le répertoire @dir@ un total de @taille@.', # NEW
	'taille_cache_infinie' => 'This site does not have any fixed limit for the size of the <code>CACHE/</code> directory.', # NEW
	'taille_cache_maxi' => 'SPIP will try to limit the size of the <code>CACHE/</code> directory to approximately <b>@octets@</b> of data.', # NEW
	'taille_cache_moins_de' => 'La taille du cache est de moins de @octets@.', # NEW
	'taille_cache_octets' => 'The size of the cache is currently @octets@.', # NEW
	'taille_cache_vide' => 'The cache is empty.', # NEW
	'taille_repertoire_cache' => 'Current size of the cache', # NEW
	'text_article_propose_publication' => 'Artiklen er sendt til offentliggørelse. Hold dig ikke tilbage fra at give din mening til kende gennem det forum, der er tilknyttet artiklen (nederst på siden).', # MODIF
	'texte_acces_ldap_anonyme_1' => 'Nogle LDAP-servere tillader ikke anonym adgang. I så fald må du angive en brugeridentifikation for senere at kunne søge efter information i kataloget. Men i de fleste tilfælde kan du lade de følgende felter stå tomme.',
	'texte_admin_effacer_01' => 'Denne kommando sletter <i>hele</i> indholdet i databasen,
	herunder <i>hele</i> opsætningen for redaktører og administratorer. Når du har udført den, bør du 
	geninstallere SPIP for at danne en ny database og åbne op for den første administratoradgang.',
	'texte_adresse_annuaire_1' => '(Hvis dit katalog findes på samme server som webstedet, er det formentlig «localhost».)',
	'texte_ajout_auteur' => 'Følgende forfatter har bidraget til artiklen:',
	'texte_annuaire_ldap_1' => 'Hvis du har adgang til et LDAP-katalog, kan du anvende det til automatisk at importere brugere i SPIP.',
	'texte_article_statut' => 'Denne artikel er:',
	'texte_article_virtuel' => 'Virtuel artikel',
	'texte_article_virtuel_reference' => '<b>Virtuel artikel:</b> fremstår som en artikel på dit websted, men viderestiller til en anden URL. Slet URL\'en for at fjerne viderestillingen.',
	'texte_aucun_resultat_auteur' => 'Ingen resultater til "@cherche_auteur@".',
	'texte_auteur_messagerie' => 'Dette websted kan løbende holde øje med, hvilke redaktører der er logget ind. Dette muliggør realtidsudveksling af meddelelser (hvis udveksling af meddelser ovenfor er fravalgt, vedligeholdes oversigten over redaktører, der er online, heller ikke). Du kan vælge ikke at være synlig i oversigten (du er så «usynlig» for andre brugere).',
	'texte_auteur_messagerie_1' => 'Dette websted tillader udveksling af meddelelser og oprettelse af private diskussionsforummer mellem deltagere på webstedet. Du kan vælge ikke at deltage i udvekslingen.',
	'texte_auteurs' => 'FORFATTERNE',
	'texte_choix_base_1' => 'Vælg database:',
	'texte_choix_base_2' => 'SQL server indeholder et antal databaser.',
	'texte_choix_base_3' => '<b>Vælg</b> vælg nedenfor den database, som webhotellet har tildelt dig:',
	'texte_choix_table_prefix' => 'Préfixe des tables :', # NEW
	'texte_commande_vider_tables_indexation' => 'Brug denne kommando til at tømme de indekstabeller, som benyttes af SPIP\'s indbyggede søgefunktion.
			Derved kan du spare diskplads.',
	'texte_compatibilite_html' => 'Vous pouvez demander à SPIP de produire, sur le site public, du code compatible avec la norme <i>HTML4</i>, ou lui permettre d\'utiliser les possibilités plus modernes du <i>HTML5</i>.', # NEW
	'texte_compatibilite_html_attention' => 'Il n\'y a aucun risque à activer l\'option <i>HTML5</i>, mais si vous le faites, les pages de votre site devront commencer par la mention suivante pour rester valides : <code>&lt;!DOCTYPE html&gt;</code>.', # NEW
	'texte_compresse_ou_non' => '(denne kan være komprimeret eller ikke)',
	'texte_compte_element' => '@count@ element',
	'texte_compte_elements' => '@count@ elementer',
	'texte_conflit_edition_correction' => 'Veuillez contrôler ci-dessous les différences entre les deux versions du texte ; vous pouvez aussi copier vos modifications, puis recommencer.', # NEW
	'texte_connexion_mysql' => 'Slå op i de oplysninger, som dit webhotel har stillet til rådighed: Hvis webhotellet understøtter SQL, bør det indeholde oplysninger om opkobling.', # MODIF
	'texte_contenu_article' => '(Artiklens indhold med få ord.)',
	'texte_contenu_articles' => 'Med udgangspunkt i det layout du har valgt til dit websted, kan du vælge at nogle artikelelementer ikke skal benyttes.
		Benyt følgende liste til at bestemme, hvilke elementer der skal være til rådighed.',
	'texte_crash_base' => 'Hvis din database er brudt ned, kan du her forsøge en automatisk genopbygning.',
	'texte_creer_rubrique' => 'Før du kan skrive artikler,<br /> skal du oprette et afsnit.',
	'texte_date_creation_article' => 'DATO FOR OPRETTELSE AF ARTIKLEN:',
	'texte_date_creation_objet' => 'Date de création :', # on ajoute le ":" NEW
	'texte_date_publication_anterieure' => 'DATO FOR TIDLIGERE OFFENTLIGGØRELSE',
	'texte_date_publication_anterieure_nonaffichee' => 'Skjul dato for tidligere offentliggørelse.',
	'texte_date_publication_article' => 'DATO FOR ONLINE OFFENTLIGGØRELSE:',
	'texte_date_publication_objet' => 'Date de publication en ligne :', # NEW
	'texte_descriptif_petition' => 'Beskrivelse af appellen',
	'texte_descriptif_rapide' => 'Kort beskrivelse',
	'texte_effacer_base' => 'Slet SPIP databasen',
	'texte_effacer_donnees_indexation' => 'Slet indekseringsdata',
	'texte_effacer_statistiques' => 'Effacer les statistiques', # NEW
	'texte_en_cours_validation' => 'Følgende artikler og nyheder er foreslået offentliggjort. Tøv ikke med at give din mening til kende via de fora, som er knyttet til artiklerne.', # MODIF
	'texte_enrichir_mise_a_jour' => 'Du kan forbedre layoutet af teksten ved at benytte «typografiske koder».',
	'texte_fichier_authent' => '<b>Skal SPIP oprette specielle <tt>.htpasswd</tt>
		og <tt>.htpasswd-admin</tt> filer i kataloget @dossier@?</b><p>
		Disse filer kan benyttes til at begrænse adgangen for forfattere og administratorer til andre dele af dit websted
		(f.eks. et eksternt statistikprogram).<p>
		Hvis du ikke har benyttet sådanne filer før, kan du vælge standardværdien (ingen filoprettelse).', # MODIF
	'texte_informations_personnelles_1' => 'Systemet vil give dig en tilpasset adgang til webstedet.',
	'texte_informations_personnelles_2' => '(Bemærk: hvis det er en geninstallation og din adgang stadig fungerer, kan du', # MODIF
	'texte_introductif_article' => '(Introduktion til artiklen)',
	'texte_jeu_caractere' => 'Denne indstilling er nyttig, hvis dit websted viser andre alfabeter end det latinske alfabet (dvs. det «vestlige») og dets afledninger. 
 I så fald skal standardindstillingen ændres til et passende tegnsæt. Vi anbefaler dig at prøve med forskellige indstillinger for at finde den bedste løsning. Husk også at tilpasse webstedet tilsvarende (<tt>#CHARSET</tt> parameteren).',
	'texte_jeu_caractere_2' => 'Denne indstilling har ikke tilbagevirkende kraft.
	Tekst, der allerede er lagt ind, kan derfor blive vist forkert efter ændring af denne indstilling.
	Du kan dog altid vende tilbage til den oprindelige indstilling.',
	'texte_jeu_caractere_3' => 'Votre site est actuellement installé dans le jeu de caractères :', # NEW
	'texte_jeu_caractere_4' => 'Si cela ne correspond pas à la réalité de vos données (suite, par exemple, à une restauration de base de données), ou si <em>vous démarrez ce site</em> et souhaitez partir sur un autre jeu de caractères, veuillez indiquer ce dernier ici :', # NEW
	'texte_jeu_caractere_conversion' => 'Note : vous pouvez décider de convertir une fois pour toutes l\'ensemble des textes de votre site (articles, brèves, forums, etc.) vers l\'alphabet <tt>utf-8</tt>, en vous rendant sur <a href="@url@">la page de conversion vers l\'utf-8</a>.', # NEW
	'texte_lien_hypertexte' => '(Hvis din meddelelse henviser til en artikel der er offentliggjort på webben, eller til en side der giver flere oplysninger, så indtast her artiklens overskrift og dens URL.)',
	'texte_login_ldap_1' => '(Efterlad tom for anonym adgang eller indtast en fuldstændig sti, f.eks. «<tt>uid=hansen, ou=brugere, dc=mit-domæne, dc=dk</tt>».)',
	'texte_login_precaution' => 'Advarsel! Dette er den login, du er koblet på med nu.
	Brug denne formular med forsigtighed ...',
	'texte_message_edit' => 'Advarsel: Denne meddelelse kan ændres af alle webstedets administratorer, og den er synlig for alle redaktører. Benyt kun annonceringer til at gøre opmærksom på vigtige begivenheder på webstedet.',
	'texte_messagerie_agenda' => 'Une messagerie permet aux rédacteurs du site de communiquer entre eux directement dans l’espace privé du site. Elle est associée à un agenda.', # NEW
	'texte_mise_a_niveau_base_1' => 'Du har netop opdateret SPIP\'s filer.
	Du skal nu opdatere webstedets database.',
	'texte_modifier_article' => 'Ret artiklen:',
	'texte_moteur_recherche_active' => '<b>Søgefunktionen er valgt til.</b> Brug denne kommando, hvis du ønsker at udføre en hurtig reindeksering
		(f.eks. efter at have indlæst en sikkerhedskopi). Bemærk, at dokumenter der ændres normalt (f.eks. fra SPIP\'s brugergrænseflade)
		automatisk indekseres igen: derfor er denne kommando kun nyttig under ekstraordinære omstændigheder.',
	'texte_moteur_recherche_non_active' => 'Søgefunktionen er valgt fra.',
	'texte_multilinguisme' => 'Hvis du ønsker at administrere artikler på flere sprog med den deraf følgende større kompleksitet, kan du forsyne afsnit og/eller artikler med en sprogvalgsmenu. Denne funktion er afhængig af strukturen på websiden.', # MODIF
	'texte_multilinguisme_trad' => 'Du kan også vælge at have link mellem de forskellige sprogversioner af en artikel.', # MODIF
	'texte_non_compresse' => '<i>ukomprimeret</i> (din server understøtter ikke denne funktion)',
	'texte_nouveau_message' => 'Ny meddelelse',
	'texte_nouvelle_version_spip_1' => 'Du har netop installeret en ny version af SPIP.',
	'texte_nouvelle_version_spip_2' => 'Denne nye version kræver en mere omfattende opdatering end sædvanligt. Hvis du er webmaster på webstedet, så slet venligst filen <tt>inc_connect.php3</tt> i kataloget <tt>ecrire</tt> og genstart installationen for at opdatere dine opkoblingsparametre til databasen. <p>(NB.: hvis du har glemt dine opkoblingsparametre, så kast et blik på indholdet af filen <tt>inc_connect.php3</tt> før du sletter den...)', # MODIF
	'texte_operation_echec' => 'Gå tilbage til forrige side og vælg en anden database eller opret en ny. Kontroller de oplysninger, dit webhotel har stillet til rådighed.',
	'texte_plus_trois_car' => 'mere end 3 tegn',
	'texte_plusieurs_articles' => 'Der er fundet flere forfattere til "@cherche_auteur@":',
	'texte_port_annuaire' => '(Standardværdien passer for det meste.)',
	'texte_presente_plugin' => 'Cette page liste les plugins disponibles sur le site. Vous pouvez activer les plugins nécessaires en cochant la case correspondante.', # NEW
	'texte_proposer_publication' => 'Når din artikel er færdig,<br /> kan du indsende den til offentliggørelse.',
	'texte_proxy' => 'I nogle tilfælde (intranet, beskyttede netværk...),
		er det nødvendigt at benytte en <i>proxy HTTP</i> for at komme i kontakt med syndikerede websteder.
		Hvis der skal benyttes proxy, så indtast dens adresse her: 
		<tt><html>http://proxy:8080</html></tt>. Almindeligvis skal feltet stå tomt.',
	'texte_publication_articles_post_dates' => 'Hvad skal SPIP gøre med hensyn til artikler med en offentliggørelsesdato, der ligger ude i 
		fremtiden?',
	'texte_rappel_selection_champs' => '[Husk at vælge dette felt korrekt.]',
	'texte_recalcul_page' => 'Hvis du kun ønsker at opdatere en side, bør du gøre det ved fra det offentlige område at benytte knappen « Opdater ».',
	'texte_recapitiule_liste_documents' => 'Denne side er en oversigt over de dokumenter, du har anbragt i afsnittene. For at ændre oplysningerne om et dokument, skal du følge linket til dets afsnitsside.',
	'texte_recuperer_base' => 'Reparer databasen',
	'texte_reference_mais_redirige' => 'artikler der refereres til på dit SPIP websted, men som viderestiller til en anden URL.',
	'texte_requetes_echouent' => '<b>Når nogle SQL forespørgsler systematisk og uden tilsyneladende grund går galt, er det muligt at fejlen ligger i selve databasen.</b>
		<p>SQL har en funktion, der reparerer dens tabeller, hvis de er blevet ødelagt ved et uheld. 
		Her kan du forsøge at igangsætte denne reparationsfunktion; 
		hvis den går galt, bør du beholde en kopi af skærmbilledet, 
		som måske kan indeholde antydninger af, hvad der er galt....
		<p>Hvis problemet fortsat består, så kontakt dit webhotel.', # MODIF
	'texte_selection_langue_principale' => 'Du kan nedenfor vælge webstedets «hovedsprog». 
		Heldigvis begrænser dette valg ikke dine artikler til at skulle skrives på det valgte sprog 
		men gør det muligt at fastsætte, 
		<ul><li> standardformatet for datoer i det offentlige område</li>

		<li> hvilken typografisk funktion SPIP skal benytte til tekstformatering;</li>

		<li> det sprog der anvendes i formularer på det offentlige websted</li>

		<li> standardsproget i det private område.</li></ul>',
	'texte_sous_titre' => 'Underrubrik',
	'texte_statistiques_visites' => '(mørke bjælker:  Søndag / mørk kurve: gennemsnitsudvikling)',
	'texte_statut_attente_validation' => 'afventer godkendelse',
	'texte_statut_publies' => 'offentliggjort online',
	'texte_statut_refuses' => 'afvist',
	'texte_suppression_fichiers' => 'Brug denne kommando til at slette alle filer i SPIP\'s cache.
		Dette giver dig bl.a. mulighed for at gennemtvinge opdatering af alle sider i tilfælde af 
		at du har lavet væsentlige grafiske eller strukturelle ændringer på webstedet.',
	'texte_sur_titre' => 'Hovedoverskrift',
	'texte_table_ok' => ': denne tabel er OK.',
	'texte_tables_indexation_vides' => 'Søgefunktionens indekstabeller er tomme.',
	'texte_tentative_recuperation' => 'Reparationsforsøg',
	'texte_tenter_reparation' => 'Forsøg på at reparere databasen',
	'texte_test_proxy' => 'For at afprøve proxy\'en, kan du indtaste adressen på et websted som du ønsker at teste.',
	'texte_titre_02' => 'Emne:',
	'texte_titre_obligatoire' => '<b>Overskrift</b> [Obligatorisk]',
	'texte_travail_article' => '@nom_auteur_modif@ har arbejdet på denne artikel for @date_diff@ minutter siden',
	'texte_travail_collaboratif' => 'Hvis det sker hyppigt at flere redaktører arbejder på samme artikel, kan systemet
		vise «åbne» artikler for at undgå samtidige ændringer. Denne indstilling er som standard
		slået fra for at undgå utidige advarselsmeddelelser.',
	'texte_trop_resultats_auteurs' => 'For mange resultater til "@cherche_auteur@"; vær venlig at afgrænse søgningen yderligere.',
	'texte_unpack' => 'download af seneste version',
	'texte_utilisation_moteur_syndiques' => 'Hvis du benytter SPIP\'s indbyggede søgefunktion, kan du på forskellig vis foretage
		søgninger på websteder og i syndikerede artikler. <br /><img src=\'puce.gif\'> Den enkleste består i kun at 
		søge i artiklernes overskrifter og beskrivelser. <br /><img src=\'puce.gif\'> 
		En anden metode, som er stærkere, lader SPIP søge i teksten på de websteder, der henvises til. 
		Hvis du henviser til et websted, vil SPIP selv søge i dens tekst.', # MODIF
	'texte_utilisation_moteur_syndiques_2' => 'Denne metode tvinger SPIP til jævnligt at besøge webstedet, hvilket kan koste 
		en forringelse af svartiderne på din eget websted.',
	'texte_vide' => 'tom',
	'texte_vider_cache' => 'Tøm cachen',
	'titre_admin_effacer' => 'Teknisk vedligeholdelse',
	'titre_admin_tech' => 'Teknisk vedligeholdelse',
	'titre_admin_vider' => 'Teknisk vedligeholdelse',
	'titre_ajouter_un_auteur' => 'Ajouter un auteur', # NEW
	'titre_ajouter_un_mot' => 'Ajouter un mot-clé', # NEW
	'titre_ajouter_une_rubrique' => 'Ajouter une rubrique', # NEW
	'titre_cadre_afficher_article' => 'Vis artikler som er',
	'titre_cadre_afficher_traductions' => 'Vis oversættelsesstatus for følgende sprog:',
	'titre_cadre_ajouter_auteur' => 'TILFØJ FORFATTER:',
	'titre_cadre_interieur_rubrique' => 'I afsnit',
	'titre_cadre_numero_auteur' => 'FORFATTER NUMMER',
	'titre_cadre_numero_objet' => '@objet@ NUMÉRO :', # NEW
	'titre_cadre_signature_obligatoire' => '<b>Underskrift</b> [Obligatorisk]<br />',
	'titre_config_contenu_notifications' => 'Notifications', # NEW
	'titre_config_contenu_prive' => 'Dans l’espace privé', # NEW
	'titre_config_contenu_public' => 'Sur le site public', # NEW
	'titre_config_fonctions' => 'Konfigurering af webstedet',
	'titre_config_langage' => 'Configurer la langue', # NEW
	'titre_configuration' => 'Konfigurering af webstedet',
	'titre_configurer_preferences' => 'Configurer vos préférences', # NEW
	'titre_conflit_edition' => 'Conflit lors de l\'édition', # NEW
	'titre_connexion_ldap' => 'Indstillinger: <b>Din LDAP forbindelse</b>',
	'titre_groupe_mots' => 'NØGLEORDSGRUPPE:',
	'titre_identite_site' => 'Identité du site', # NEW
	'titre_langue_article' => 'ARTIKLENS SPROG', # MODIF
	'titre_langue_rubrique' => 'SPROGAFSNIT', # MODIF
	'titre_langue_trad_article' => 'ARTIKLENS SPROG OG OVERSÆTTELSER',
	'titre_les_articles' => 'ARTIKLER',
	'titre_messagerie_agenda' => 'Messagerie et agenda', # NEW
	'titre_naviguer_dans_le_site' => 'Gennemse webstedet...',
	'titre_nouvelle_rubrique' => 'Nyt afsnit',
	'titre_numero_rubrique' => 'AFSNITSNUMMER:',
	'titre_page_admin_effacer' => 'Teknisk vedligeholdelse: sletning af database',
	'titre_page_articles_edit' => 'Ret: @titre@',
	'titre_page_articles_page' => 'Artikler',
	'titre_page_articles_tous' => 'Hele webstedet',
	'titre_page_auteurs' => 'Besøgende',
	'titre_page_calendrier' => 'Kalender @nom_mois@ @annee@',
	'titre_page_config_contenu' => 'Webstedskonfigurering',
	'titre_page_config_fonctions' => 'Webstedkonfigurering',
	'titre_page_configuration' => 'Konfiguration af websted',
	'titre_page_controle_petition' => 'Opfølgning på appel',
	'titre_page_delete_all' => 'total og uigenkaldelig sletning',
	'titre_page_documents_liste' => 'Dokumenter i afsnit',
	'titre_page_index' => 'Dit private område',
	'titre_page_message_edit' => 'Skriv meddelelse',
	'titre_page_messagerie' => 'Din meddelelsesfunktion',
	'titre_page_recherche' => 'Søgeresultater @recherche@',
	'titre_page_statistiques_referers' => 'Statistik (indkommende links)',
	'titre_page_statistiques_signatures_jour' => 'Nombre de signatures par jour', # NEW
	'titre_page_statistiques_signatures_mois' => 'Nombre de signatures par mois', # NEW
	'titre_page_upgrade' => 'SPIP opgradering',
	'titre_publication_articles_post_dates' => 'Offentliggørelse af fremdaterede artikler',
	'titre_referencer_site' => 'Henvis til webstedet:',
	'titre_rendez_vous' => 'AFTALER:',
	'titre_reparation' => 'Reparer',
	'titre_suivi_petition' => 'Opfølgning på appeller',
	'tls_ldap' => 'Transport Layer Security :', # NEW
	'trad_article_inexistant' => 'Der findes ingen artikel med dette nummer.',
	'trad_article_traduction' => 'Alle udgaver af denne artikel :',
	'trad_deja_traduit' => 'Denne artikel er allerede en oversættelse af den aktuelle artikel.',
	'trad_delier' => 'Afbryd forbindelsen mellem denne artikel og oversættelserne', # MODIF
	'trad_lier' => 'Denne artikel er en oversættelse af artikel nummer :',
	'trad_new' => 'Lav en ny oversættelse af denne artikel', # MODIF

	// U
	'upload_info_mode_document' => 'Déposer cette image dans le portfolio', # NEW
	'upload_info_mode_image' => 'Retirer cette image du portfolio', # NEW
	'utf8_convert_attendez' => 'Attendez quelques instants et rechargez cette page.', # NEW
	'utf8_convert_avertissement' => 'Vous vous apprêtez à convertir le contenu de votre base de données (articles, brèves, etc) du jeu de caractères <b>@orig@</b> vers le jeu de caractères <b>@charset@</b>.', # NEW
	'utf8_convert_backup' => 'N\'oubliez pas de faire auparavant une sauvegarde complète de votre site. Vous devrez aussi vérifier que vos squelettes et fichiers de langue sont compatibles @charset@.', # NEW
	'utf8_convert_erreur_deja' => 'Votre site est déjà en @charset@, inutile de le convertir...', # NEW
	'utf8_convert_erreur_orig' => 'Erreur : le jeu de caractères @charset@ n\'est pas supporté.', # NEW
	'utf8_convert_termine' => 'C\'est terminé !', # NEW
	'utf8_convert_timeout' => '<b>Important :</b> en cas de <i>timeout</i> du serveur, veuillez recharger la page jusqu\'à ce qu\'elle indique « terminé ».', # NEW
	'utf8_convert_verifier' => 'Vous devez maintenant aller vider le cache, et vérifier que tout se passe bien sur les pages publiques du site. En cas de gros problème, une sauvegarde de vos données a été réalisée (au format SQL) dans le répertoire @rep@.', # NEW
	'utf8_convertir_votre_site' => 'Convertir votre site en utf-8', # NEW

	// V
	'version' => 'Version :' # NEW
);

?>
