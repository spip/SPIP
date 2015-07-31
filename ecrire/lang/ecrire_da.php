<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://www.spip.net/trad-lang/
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

// A
'activer_plugin' => 'Activer le plugin', # NEW
'affichage' => 'Affichage', # NEW
'aide_non_disponible' => 'Denne del af online-hj&aelig;lpen er endnu ikke tilg&aelig;ngelig p&aring; dansk.',
'auteur' => 'Auteur&nbsp;:', # NEW
'avis_acces_interdit' => 'Ingen adgang',
'avis_article_modifie' => 'Advarsel, @nom_auteur_modif@ har arbejdet p&aring; denne artikel for @date_diff@ minutter siden',
'avis_aucun_resultat' => 'Ingen resultater fundet.',
'avis_chemin_invalide_1' => 'Den sti som du har valgt',
'avis_chemin_invalide_2' => 'ser ikke ud til at v&aelig;re gyldig. G&aring; tilbage til sidste side og kontroller de oplysninger, du har indtastet.',
'avis_connexion_echec_1' => 'Ingen forbindelse til SQL-serveren', # MODIF
'avis_connexion_echec_2' => 'G&aring; tilbage til sidste side og kontroller de oplysninger, du har indtastet',
'avis_connexion_echec_3' => '<b>NB:</b> P&aring; mange servere skal du <b>anmode om</b> at f&aring; &aring;bnet adgang til en SQL-database, f&oslash;r du kan bruge den. Hvis du ikke kan etablere en forbindelse, s&aring; kontroller venligst at du har indgivet denne anmodning.', # MODIF
'avis_connexion_ldap_echec_1' => 'Ingen forbindelse til LDAP-serveren',
'avis_connexion_ldap_echec_2' => 'G&aring; tilbage til sidste side og kontroller de oplysninger, du har indtastet.',
'avis_connexion_ldap_echec_3' => 'Alternativt kan du v&aelig;lge ikke at benytte LDAP til at importere brugere.',
'avis_conseil_selection_mot_cle' => '<b>Vigtig gruppe:</b> Det anbefales kraftigt at v&aelig;lge et n&oslash;gleord til denne gruppe.',
'avis_deplacement_rubrique' => 'Advarsel! Dette afsnit indeholder @contient_breves@ nyheder@scb@: Hvis du vil flytte den, s&aring; afkryds venligst her for at bekr&aelig;fte.',
'avis_destinataire_obligatoire' => 'Du skal v&aelig;lge en modtager, f&oslash;r du kan sende meddelelsen.',
'avis_doublon_mot_cle' => 'Un mot existe deja avec ce titre. &Ecirc;tes vous s&ucirc;r de vouloir cr&eacute;er le m&ecirc;me ?', # NEW
'avis_erreur_connexion_mysql' => 'Fejl i forbindelse til SQL',
'avis_erreur_version_archive' => ' <b>Advarsel! Filen @archive@ h&oslash;rer til 
    en anden version af SPIP end den du har
    installeret.</b> Du risikerer store
    vanskeligheder: risiko for at &oslash;del&aelig;gge din database, 
    forskellige funktionsfejl p&aring; webstedet, osv. 
    Forts&aelig;t ikke indl&aelig;sningen.<p>For mere
    information henvises til <a href="@spipnet@">,
                                SPIP-dokumentationen</A>.', # MODIF
'avis_espace_interdit' => '<b>Forbudt omr&aring;de</b><p>SPIP er allerede installeret.',
'avis_lecture_noms_bases_1' => 'Installationsprogrammet kunne ikke l&aelig;se navnene p&aring; de installerede databaser.',
'avis_lecture_noms_bases_2' => 'Enten er databasen ikke tilg&aelig;ngelig, eller ogs&aring; er funktionen, som giver oversigt
		over databaser, sat ud af kraft af sikkerheds&aring;rsager (hvilket er tilf&aelig;ldet p&aring; mange servere).',
'avis_lecture_noms_bases_3' => 'Hvis det sidstn&aelig;vnte er tilf&aelig;ldet, er det muligt at en database, som er navngivet efter dit login, kan anvendes:',
'avis_non_acces_message' => 'Du har ikke adgang til denne meddelelse.',
'avis_non_acces_page' => 'Du har ikke adgang til denne side.',
'avis_operation_echec' => 'Opgaven mislykkedes.',
'avis_operation_impossible' => 'Op&eacute;ration impossible', # NEW
'avis_probleme_archive' => 'L&aelig;sefejl i filen @archive@',
'avis_site_introuvable' => 'Webstedet ikke fundet',
'avis_site_syndique_probleme' => 'Advarsel: syndikering med dette websted er st&oslash;dt p&aring; et problem; derfor afbrydes systemet midlertidigt. Kontroller adressen p&aring; webstedets syndikeringsfil (<b>@url_syndic@</b>), og pr&oslash;v at f&aring; adgang til data igen.', # MODIF
'avis_sites_probleme_syndication' => 'Disse websteder har problemer med syndikering',
'avis_sites_syndiques_probleme' => 'Disse syndikerede sider giver problemer',
'avis_suppression_base' => 'ADVARSEL, sletning kan ikke omg&oslash;res',
'avis_version_mysql' => 'Din version af SQL (@version_mysql@) tillader ikke automatisk reparation af tabeller.',

// B
'bouton_acces_ldap' => 'Tilf&oslash;j adgang til LDAP >>',
'bouton_ajouter' => 'Tilf&oslash;j',
'bouton_ajouter_participant' => 'TILF&Oslash;J DELTAGER:',
'bouton_annonce' => 'ANNONCERING',
'bouton_annuler' => 'Annuler', # NEW
'bouton_checkbox_envoi_message' => 'mulighed for at sende en meddelelse',
'bouton_checkbox_indiquer_site' => 'obligatorisk angivelse af websted ',
'bouton_checkbox_qui_attribue_mot_cle_administrateurs' => 'administratorer af webstedet',
'bouton_checkbox_qui_attribue_mot_cle_redacteurs' => 'redakt&oslash;rer',
'bouton_checkbox_qui_attribue_mot_cle_visiteurs' => 'bes&oslash;gende p&aring; det offentlige websted n&aring;r de sender meddelelse til et forum',
'bouton_checkbox_signature_unique_email' => 'kun en signatur pr. e-mail-adresse',
'bouton_checkbox_signature_unique_site' => 'kun en signatur pr. websted',
'bouton_demande_publication' => 'Anmod om at f&aring; offentliggjort denne artikel',
'bouton_desactive_tout' => 'Tout d&eacute;sactiver', # NEW
'bouton_desinstaller' => 'D&eacute;sinstaller', # NEW
'bouton_effacer_index' => 'Slet indeksering',
'bouton_effacer_statistiques' => 'Effacer les statistiques', # NEW
'bouton_effacer_tout' => 'Slet alt',
'bouton_envoi_message_02' => 'SEND MEDDELELSE',
'bouton_envoyer_message' => 'Send f&aelig;rdig meddelelse',
'bouton_forum_petition' => 'FORUM &amp; APPELLER',
'bouton_modifier' => 'Ret',
'bouton_pense_bete' => 'PERSONLIGT MEMO',
'bouton_radio_activer_messagerie' => 'Tillad interne meddelelser',
'bouton_radio_activer_messagerie_interne' => 'Tillad interne meddelelser',
'bouton_radio_activer_petition' => 'Tillad appeller',
'bouton_radio_afficher' => 'Vis',
'bouton_radio_apparaitre_liste_redacteurs_connectes' => 'Medtag i listen over tilknyttede redakt&oslash;rer',
'bouton_radio_articles_futurs' => 'alene for fremtidige artikler (ingen opdatering af databasen).',
'bouton_radio_articles_tous' => 'for alle artikler uden undtagelse.',
'bouton_radio_articles_tous_sauf_forum_desactive' => 'for alle artikler med aktivt tilknyttet forum.',
'bouton_radio_desactiver_messagerie' => 'Sl&aring; meddelelsesfunktion fra',
'bouton_radio_enregistrement_obligatoire' => 'Tvungen registrering (
		brugere skal give sig til kende ved at oplyse deres e-mail-adresse
		for at kunne komme med indl&aelig;g).',
'bouton_radio_envoi_annonces_adresse' => 'Send nyheder til adressen:',
'bouton_radio_envoi_liste_nouveautes' => 'Send seneste nyhedsliste',
'bouton_radio_moderation_priori' => 'Forh&aring;ndsgodkendelse (bidrag vises f&oslash;rst efter at de er godkendt af administratorer).',
'bouton_radio_modere_abonnement' => 'Kun for abonnenter (bidragydere skal oplyse e-mail-adresse, f&oslash;r de kan indsende bidrag)',
'bouton_radio_modere_posteriori' => 'Efterf&oslash;lgende godkendelse (bidrag er straks synlige men en administrator kan senere slette dem)',
'bouton_radio_modere_priori' => 'Forh&aring;ndsgodkendelse',
'bouton_radio_non_apparaitre_liste_redacteurs_connectes' => 'Medtag ikke i listen over tilknyttede redakt&oslash;rer',
'bouton_radio_non_envoi_annonces_editoriales' => 'Send ingen redaktionelle nyheder',
'bouton_radio_non_syndication' => 'Ingen syndikering',
'bouton_radio_pas_petition' => 'Ingen appeller',
'bouton_radio_petition_activee' => 'Appelfunktion sl&aring;et til',
'bouton_radio_publication_immediate' => 'Indl&aelig;g offentligg&oslash;res straks (bidrag vises straks efter at de er sendt, administratorer kan slette dem senere).',
'bouton_radio_sauvegarde_compressee' => 'gem komprimeret i @fichier@',
'bouton_radio_sauvegarde_non_compressee' => 'gem ukomprimeret i @fichier@',
'bouton_radio_supprimer_petition' => 'Slet appellen',
'bouton_radio_syndication' => 'Syndikering:',
'bouton_redirection' => 'VIDERESTIL',
'bouton_relancer_installation' => 'Gentag installationen',
'bouton_restaurer_base' => 'Genetabler databasen',
'bouton_suivant' => 'N&aelig;ste',
'bouton_tenter_recuperation' => 'Reparationsfors&oslash;g',
'bouton_test_proxy' => 'Test proxy',
'bouton_vider_cache' => 'T&oslash;m cache',
'bouton_voir_message' => 'Vis indl&aelig;g f&oslash;r godkendelse',

// C
'cache_mode_compresse' => 'The cache files are saved in compressed mode.', # NEW
'cache_mode_non_compresse' => 'The cache files are written in uncompressed mode.', # NEW
'cache_modifiable_webmestre' => 'These parameters can be modified by the webmaster.', # NEW
'calendrier_synchro' => 'Hvis du benytter en kalenderapplikation, der er kompatibel med <b>iCal</b>, kan du synkronisere med information p&aring; dette websted.',
'config_activer_champs' => 'Activer les champs suivants', # NEW
'config_choix_base_sup' => 'indiquer une base sur ce serveur', # NEW
'config_erreur_base_sup' => 'SPIP n\'a pas acc&egrave;s &agrave; la liste des bases accessibles', # NEW
'config_info_base_sup' => 'Si vous avez d\'autres bases de donn&eacute;es &agrave; interroger &agrave; travers SPIP, avec son serveur SQL ou avec un autre, le formulaire ci-dessous, vous permet de les d&eacute;clarer. Si vous laissez certains champs vides, les identifiants de connexion &agrave; la base principale seront utilis&eacute;s.', # NEW
'config_info_base_sup_disponibles' => 'Bases suppl&eacute;mentaires d&eacute;j&agrave; interrogeables:', # NEW
'config_info_enregistree' => 'La nouvelle configuration a &eacute;t&eacute; enregistr&eacute;e', # NEW
'config_info_logos' => 'Chaque &eacute;l&eacute;ment du site peut avoir un logo, ainsi qu\'un &laquo;&nbsp;logo de survol&nbsp;&raquo;', # NEW
'config_info_logos_utiliser' => 'Utiliser les logos', # NEW
'config_info_logos_utiliser_non' => 'Ne pas utiliser les logos', # NEW
'config_info_logos_utiliser_survol' => 'Utiliser les logos de survol', # NEW
'config_info_logos_utiliser_survol_non' => 'Ne pas utiliser les logos de survol', # NEW
'config_info_redirection' => 'En activant cette option, vous pourrez cr&eacute;er des articles virtuels, simples r&eacute;f&eacute;rences d\'articles publi&eacute;s sur d\'autres sites ou hors de SPIP.', # NEW
'config_redirection' => 'Articles virtuels', # NEW
'config_titre_base_sup' => 'D&eacute;claration d\'une base suppl&eacute;mentaire', # NEW
'config_titre_base_sup_choix' => 'Choisissez une base suppl&eacute;mentaire', # NEW
'connexion_ldap' => 'Connexion :', # NEW
'copier_en_local' => 'Copier en local', # NEW

// D
'date_mot_heures' => 'timer',
'diff_para_ajoute' => 'Added paragraph', # NEW
'diff_para_deplace' => 'Moved paragraph', # NEW
'diff_para_supprime' => 'Deleted paragraph', # NEW
'diff_texte_ajoute' => 'Added text', # NEW
'diff_texte_deplace' => 'Moved text', # NEW
'diff_texte_supprime' => 'Deleted text', # NEW
'double_clic_inserer_doc' => 'Double-cliquez pour ins&eacute;rer ce raccourci dans le texte', # NEW

// E
'email' => 'e-mail',
'email_2' => 'e-mail:',
'en_savoir_plus' => 'En savoir plus', # NEW
'entree_adresse_annuaire' => 'Adresse p&aring; kataloget',
'entree_adresse_email' => 'Din e-mail-adresse',
'entree_adresse_fichier_syndication' => 'Adresse p&aring; syndikeringsfil:',
'entree_adresse_site' => '<b>URL p&aring; websted</b> [Skal oplyses]',
'entree_base_donnee_1' => 'Adresse p&aring; database',
'entree_base_donnee_2' => '(Ofte svarer denne adresse til adressen p&aring; webstedet, undertiden er den navngivet &laquo;localhost&raquo;, og undertiden skal den v&aelig;re blank.)',
'entree_biographie' => 'Kort pr&aelig;sentation.',
'entree_breve_publiee' => 'Skal denne nyhed offentligg&oslash;res?',
'entree_chemin_acces' => '<b>Angiv</b> stien:',
'entree_cle_pgp' => 'Din PGP n&oslash;gle',
'entree_contenu_rubrique' => '(Kort beskrivelse af afsnittets indhold.)',
'entree_description_site' => 'Beskrivelse af websted',
'entree_identifiants_connexion' => 'Dine opkoblingsinformationer...',
'entree_informations_connexion_ldap' => 'Udfyld denne side med LDAP opkoblingsinformation. Du kan indhente oplysningerne hos din system- eller netv&aelig;rskadministrator.',
'entree_infos_perso' => 'Hvem er du?',
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
'entree_nom_site' => 'Dit websteds navn',
'entree_nouveau_passe' => 'Ny adgangskode',
'entree_passe_ldap' => 'Adgangskode',
'entree_port_annuaire' => 'Portnummer p&aring; kataloget',
'entree_signature' => 'Signatur',
'entree_texte_breve' => 'Nyhedstekst',
'entree_titre_obligatoire' => '<b>Titel</b> [Skal oplyses]<br />',
'entree_url' => 'Dit websteds URL',
'erreur_plugin_desinstalation_echouee' => 'La d&eacute;sinstallation du plugin a echou&eacute;. Vous pouvez n&eacute;anmoins le desactiver.', # NEW
'erreur_plugin_fichier_absent' => 'Fichier absent', # NEW
'erreur_plugin_fichier_def_absent' => 'Fichier de d&eacute;finition absent', # NEW
'erreur_plugin_nom_fonction_interdit' => 'Nom de fonction interdit', # NEW
'erreur_plugin_nom_manquant' => 'Nom du plugin manquant', # NEW
'erreur_plugin_prefix_manquant' => 'Espace de nommage du plugin non d&eacute;fini', # NEW
'erreur_plugin_tag_plugin_absent' => '&lt;plugin&gt; manquant dans le fichier de d&eacute;finition', # NEW
'erreur_plugin_version_manquant' => 'Version du plugin manquante', # NEW

// F
'forum_info_original' => 'original', # NEW

// H
'htaccess_a_simuler' => 'Avertissement: la configuration de votre serveur HTTP ne tient pas compte des fichiers @htaccess@. Pour pouvoir assurer une bonne s&eacute;curit&eacute;, il faut que vous modifiez cette configuration sur ce point, ou bien que les constantes @constantes@ (d&eacute;finissables dans le fichier mes_options.php) aient comme valeur des r&eacute;pertoires en dehors de @document_root@.', # NEW
'htaccess_inoperant' => 'htaccess inop&eacute;rant', # NEW

// I
'ical_info1' => 'Denne side viser flere m&aring;der til at f&oslash;lge med i aktiviteter p&aring; dette websted.',
'ical_info2' => 'For mere information, bes&oslash;g <a href="@spipnet@">SPIP dokumentation</a>.', # MODIF
'ical_info_calendrier' => 'To kalendere st&aring;r til r&aring;dighed. Den f&oslash;rste er en oversigt over webstedet, der viser alle offentliggjorte artikler.Den anden indeholder b&aring;de redaktionelle annonceringer og dine seneste private meddelelser. Den er forbeholdt dig i kraft af en personlig n&oslash;gle, som du kan &aelig;ndre n&aring;r som helst ved at forny din adgangskode.',
'ical_lien_rss_breves' => 'Syndication des br&egrave;ves du site', # NEW
'ical_methode_http' => 'Filhentning',
'ical_methode_webcal' => 'Synkronisering (webcal://)',
'ical_texte_js' => 'Med en linies javascript kan du nemt vise de senest offentliggjorte artikler p&aring; et websted, der tilh&oslash;rer dig.',
'ical_texte_prive' => 'Denne strengt personlige kalender holder dig underrettet om private redaktionelle aktiviteter p&aring; webstedet (opgaver, personlige aftaler, indsendte artikler, nyheder ...).',
'ical_texte_public' => 'Med denne kalender kan du f&oslash;lge de offentlige aktiviteter p&aring; webstedet (offentliggjorte artikler og nyheder).',
'ical_texte_rss' => 'Du kan syndikere de seneste nyheder p&aring; dette websted i en hvilken som helst XML/RSS (Rich Site Summary) fill&aelig;ser. Dette format tillader ogs&aring; SPIP at l&aelig;se de seneste nyheder offenliggjort af andre websteder i et kompatibelt udvekslingsformat.',
'ical_titre_js' => 'Javascript',
'ical_titre_mailing' => 'Postliste',
'ical_titre_rss' => '&laquo;Backend&raquo; filer',
'icone_activer_cookie' => 'Opret administrationscookie',
'icone_admin_plugin' => 'Gestion des plugins', # NEW
'icone_afficher_auteurs' => 'Vis forfattere',
'icone_afficher_visiteurs' => 'Vis bes&oslash;gende',
'icone_arret_discussion' => 'Stop deltagelse i denne diskussion',
'icone_calendrier' => 'Kalender',
'icone_creation_groupe_mots' => 'Opret ny n&oslash;gleordsgruppe',
'icone_creation_mots_cles' => 'Opret nyt n&oslash;gleord',
'icone_creer_auteur' => 'Opret ny forfatter og tilknyt til denne artikel',
'icone_creer_mot_cle' => 'Opret nyt n&oslash;gleord og tilknyt til denne artikel',
'icone_creer_mot_cle_breve' => 'Cr&eacute;er un nouveau mot-cl&eacute; et le lier &agrave; cette br&egrave;ve', # NEW
'icone_creer_mot_cle_rubrique' => 'Cr&eacute;er un nouveau mot-cl&eacute; et le lier &agrave; cette rubrique', # NEW
'icone_creer_mot_cle_site' => 'Cr&eacute;er un nouveau mot-cl&eacute; et le lier &agrave; ce site', # NEW
'icone_creer_rubrique_2' => 'Opret nyt afsnit',
'icone_ecrire_nouvel_article' => 'Nye indl&aelig;g i dette afsnit',
'icone_envoyer_message' => 'Send denne meddelelse',
'icone_evolution_visites' => 'Udvikling i bes&oslash;g <br />@visites@ bes&oslash;g',
'icone_modif_groupe_mots' => 'Ret denne n&oslash;gleordsgruppe',
'icone_modifier_article' => 'Ret denne artikel',
'icone_modifier_breve' => 'Ret denne nyhed',
'icone_modifier_message' => 'Ret denne meddelelse',
'icone_modifier_mot' => 'Modifier ce mot-cl&eacute;', # NEW
'icone_modifier_rubrique' => 'Ret dette afsnit',
'icone_modifier_site' => 'Ret dette websted',
'icone_poster_message' => 'Opret meddelelse',
'icone_publier_breve' => 'Offentligg&oslash;r nyhed',
'icone_referencer_nouveau_site' => 'Ny webstedshenvisning',
'icone_refuser_breve' => 'Afvis nyhed',
'icone_relancer_signataire' => 'Relancer le signataire', # NEW
'icone_retour' => 'Tilbage',
'icone_retour_article' => 'Tilbage til artikel',
'icone_suivi_forum' => 'Opf&oslash;lgning i offentligt forum: @nb_forums@ bidrag',
'icone_supprimer_cookie' => 'Slet cookier',
'icone_supprimer_groupe_mots' => 'Slet denne gruppe',
'icone_supprimer_rubrique' => 'Slet dette afsnit',
'icone_supprimer_signature' => 'Slet denne signatur',
'icone_valider_signature' => 'Godkend signatur',
'icone_voir_sites_references' => 'Vis links',
'icone_voir_tous_mots_cles' => 'Vis alle n&oslash;gleord',
'image_administrer_rubrique' => 'Du kan administrere dette afsnit',
'info_1_article' => '1 artikel',
'info_1_breve' => '1 nyhed',
'info_1_site' => '1 websted',
'info_activer_cookie' => 'Du kan installere en <b>administrationscookie</b>, som tillader dig at skifte nemt mellem det offentlige websted og dit private afsnit.',
'info_activer_forum_public' => '<i>For at &aring;bne op for offentlige forummer, skal du v&aelig;lge, hvordan deres godkendelsesprocedure skal v&aelig;re som standard:</i>',
'info_admin_gere_rubriques' => 'Denne administrator administrerer f&oslash;lgende afsnit:',
'info_admin_gere_toutes_rubriques' => 'Denne administrator administrerer <b>alle afsnit</b>.',
'info_admin_statuer_webmestre' => 'Donner &agrave; cet administrateur les droits de webmestre', # NEW
'info_admin_webmestre' => 'Cet administrateur est <b>webmestre</b>', # NEW
'info_administrateur' => 'Administrator',
'info_administrateur_1' => 'Administrator',
'info_administrateur_2' => 'af webstedet (<i>anvend med forsigtighed</i>)',
'info_administrateur_site_01' => 'Hvis du er webstedsadministrator, s&aring;',
'info_administrateur_site_02' => 'klik p&aring; dette link',
'info_administrateurs' => 'Administratorer',
'info_administrer_rubrique' => 'Du kan administrere dette afsnit',
'info_adresse' => 'til adressen:',
'info_adresse_email' => 'EMAIL ADRESSE:',
'info_adresse_url' => 'Dit offentlige websteds URL',
'info_afficher_visites' => 'Vis bes&oslash;g for:',
'info_affichier_visites_articles_plus_visites' => 'Vis bes&oslash;g til <b>de mest bes&oslash;gte artikler siden starten:</b>',
'info_aide_en_ligne' => 'SPIP online hj&aelig;lp',
'info_ajout_image' => 'N&aring;r du vedh&aelig;fter billeder til en artikel, kan
		SPIP automatisk lave miniatureudgaver af billederne.
		Dette muligg&oslash;r f.eks. automatisk oprettelse af et
		galleri eller et album.',
'info_ajout_participant' => 'F&oslash;lgende deltager er tilf&oslash;jet:',
'info_ajouter_rubrique' => 'Tilf&oslash;j endnu et afsnit at administrere:',
'info_annonce_nouveautes' => 'Seneste annonceringer',
'info_anterieur' => 'forrige',
'info_appliquer_choix_moderation' => 'Anvend denne godkendelsesprocedure:',
'info_article' => 'artikel',
'info_article_2' => 'artikler',
'info_article_a_paraitre' => 'Fremdaterede artikler der skal offentligg&oslash;res',
'info_articles_02' => 'artikler',
'info_articles_2' => 'Artikler',
'info_articles_auteur' => 'Denne forfatters artikler',
'info_articles_lies_mot' => 'Artikler knyttet til dette n&oslash;gleord',
'info_articles_trouves' => 'Fundne artikler',
'info_articles_trouves_dans_texte' => 'Artikler fundet (i teksten)',
'info_attente_validation' => 'Dine artikler som afventer godkendelse',
'info_aujourdhui' => 'i dag:',
'info_auteur_message' => 'MEDDELELSESAFSENDER:',
'info_auteurs' => 'Forfattere',
'info_auteurs_par_tri' => 'Forfattere@partri@',
'info_auteurs_trouves' => 'Forfattere fundet',
'info_authentification_externe' => 'Ekstern adgangskontrol',
'info_avertissement' => 'Advarsel',
'info_barre_outils' => 'avec sa barre d\'outils ?', # NEW
'info_base_installee' => 'Din databasestruktur er installeret.',
'info_base_restauration' => 'Genoprettelse af databasen er i gang.',
'info_bloquer' => 'bloquer', # NEW
'info_breves' => 'Bruger dit websted nyhedssystemet?',
'info_breves_03' => 'nyheder',
'info_breves_liees_mot' => 'Nyheder knyttet til dette n&oslash;gleord',
'info_breves_touvees' => 'Fundne nyheder',
'info_breves_touvees_dans_texte' => 'Nyheder fundet (i teksten)',
'info_changer_nom_groupe' => 'Ret navnet p&aring; denne gruppe:',
'info_chapeau' => 'Hoved',
'info_chapeau_2' => 'Indledning:',
'info_chemin_acces_1' => 'Valgmuligheder: <b>Adgangsvej til katalog</b>',
'info_chemin_acces_2' => 'Du skal nu konfigurere adgangsvejen til kataloginformationen. Dette er vigtigt for at kunne l&aelig;se de brugerprofiler, som ligger i kataloget.',
'info_chemin_acces_annuaire' => 'Valgmuligheder: <b>Adgangsvej til katalog</b>',
'info_choix_base' => 'Tredje skrift:',
'info_classement_1' => '<sup>.</sup> af @liste@',
'info_classement_2' => '<sup>.</sup> af @liste@',
'info_code_acces' => 'Glem ikke dine egne adgangsoplysninger!',
'info_comment_lire_tableau' => 'S&aring;dan l&aelig;ses grafen',
'info_compresseur_gzip' => '<b>N.&nbsp;B.&nbsp;:</b> Il est recommand&#233; de v&#233;rifier au pr&#233;alable si l\'h&#233;bergeur compresse d&#233;j&#224; syst&#233;matiquement les scripts php&nbsp;; pour cela, vous pouvez par exemple utiliser le service suivant&nbsp;: @testgzip@', # NEW
'info_compresseur_texte' => 'Si votre serveur ne comprime pas automatiquement les pages html pour les envoyer aux internautes, vous pouvez essayer de forcer cette compression pour diminuer le poids des pages t&eacute;l&eacute;charg&eacute;es. <b>Attention</b> : cela peut ralentir considerablement certains serveurs.', # NEW
'info_compresseur_titre' => 'Optimisations et compression', # NEW
'info_config_forums_prive' => 'Dans l&#8217;espace priv&#233; du site, vous pouvez activer plusieurs types de forums&nbsp;:', # NEW
'info_config_forums_prive_admin' => 'Un forum r&#233;serv&#233; aux administrateurs du site&nbsp;:', # NEW
'info_config_forums_prive_global' => 'Un forum global, ouvert &#224; tous les r&#233;dacteurs&nbsp;:', # NEW
'info_config_forums_prive_objets' => 'Un forum sous chaque article, br&#232;ve, site r&#233;f&#233;renc&#233;, etc.&nbsp;:', # NEW
'info_config_suivi' => 'Hvis denne adresse svarer til en postliste, kan du nedefor angive, hvor webstedets bes&oslash;gende kan lade sig registrere. Denne adresse kan v&aelig;re en  URL (f.eks. siden med tilmelding til listen via web), eller en e-mail adresse med et s&aelig;rligt emne tilknyttet (f.eks.: <tt>@adresse_suivi@?subject=abonner</tt>):',
'info_config_suivi_explication' => 'Du kan abonnere p&aring; dette websteds postliste. Du vil s&aring; via e-mail modtage annonceringer vedr&oslash;rende artikler og nyheder, der er indsendt til offentligg&oslash;relse.',
'info_confirmer_passe' => 'Bekr&aelig;ft ny adgangskode:',
'info_conflit_edition_avis_non_sauvegarde' => 'Attention, les champs suivants ont &#233;t&#233; modifi&#233;s par ailleurs. Vos modifications sur ces champs n\'ont donc pas &#233;t&#233; enregistr&#233;es.', # NEW
'info_conflit_edition_differences' => 'Diff&#233;rences&nbsp;:', # NEW
'info_conflit_edition_version_enregistree' => 'La version enregistr&#233;e&nbsp;:', # NEW
'info_conflit_edition_votre_version' => 'Votre version&nbsp;:', # NEW
'info_connexion_base' => 'Andet skrift: <b>Fors&oslash;g p&aring; opkobling til databasen</b>',
'info_connexion_base_donnee' => 'Connexion &agrave; votre base de donn&eacute;es', # NEW
'info_connexion_ldap_ok' => '<b>Din LDAP-opkobling lykkedes.</b><p> Du kan g&aring; til n&aelig;ste skridt.', # MODIF
'info_connexion_mysql' => 'F&oslash;rste skridt: <b>Din SQL opkobling</b>',
'info_connexion_ok' => 'Opkoblingen lykkedes.',
'info_contact' => 'Kontakt',
'info_contenu_articles' => 'Artiklens bestanddele',
'info_creation_mots_cles' => 'Opret og konfigurer webstedets n&oslash;gleord her',
'info_creation_paragraphe' => '(For at lave afsnit skal du inds&aelig;tte blanke linier.)',
'info_creation_rubrique' => 'F&oslash;r du kan skrive artikler<br /> skal du lave mindst et afsnit.<br />',
'info_creation_tables' => 'Fjerde skridt: <b>Oprettelse af databasetabeller</b>',
'info_creer_base' => '<b>Opret</b> en ny database:',
'info_dans_groupe' => 'I gruppe:',
'info_dans_rubrique' => 'I afsnit:',
'info_date_publication_anterieure' => 'Dato for tidligere offentligg&oslash;relse:',
'info_date_referencement' => 'DATO FOR HENVISNING TIL DETTE WEBSTED:',
'info_delet_mots_cles' => 'Du har &oslash;nsket at slette n&oslash;gleordet
	<b>@titre_mot@</b> (@type_mot@). Da n&oslash;gleordet er knyttet til 
	<b>@texte_lie@</b>skal du bekr&aelig;fte sletningen:',
'info_derniere_etape' => 'Sidste skridt: <b>Det er overst&aring;et!',
'info_derniere_syndication' => 'Sidste syndikering af dette websted blev udf&oslash;rt den',
'info_derniers_articles_publies' => 'Dine senest offentliggjorte artikler',
'info_desactiver_forum_public' => 'Frav&aelig;lg brugen af offentlige forummer. Der kan &aring;bnes op for offentlige forummer fra gang til gang i forhold til artikler.
De vil v&aelig;re lukkede i forhold til afsnit, meddelelser osv..',
'info_desactiver_messagerie_personnelle' => 'Du kan &aring;bne eller lukke for personlige meddelelser p&aring; dette websted.',
'info_descriptif' => 'Beskrivelse:',
'info_desinstaller_plugin' => 'supprime les donn&eacute;es et d&eacute;sactive le plugin', # NEW
'info_discussion_cours' => 'Igangv&aelig;rende diskussioner',
'info_ecrire_article' => 'F&oslash;r du kan lave artikler, skal du oprette mindst et afsnit.',
'info_email_envoi' => 'Afsenderens e-mail adresse (valgfri)',
'info_email_envoi_txt' => 'Indtast afsenderens e-mail adresse ved afsendelse af e-mails (som standard bruges modtagerens adresse som afsenderadresse)&nbsp;:',
'info_email_webmestre' => 'E-mail-adresse p&aring; webmaster (valgfrit)',
'info_entrer_code_alphabet' => 'Indtast koden for det tegns&aelig;t, der skal benyttes:',
'info_envoi_email_automatique' => 'Automatisk e-mail-forsendelse',
'info_envoi_forum' => 'Send indl&aelig;g i forummer til artiklernes forfattere',
'info_envoyer_maintenant' => 'Send nu',
'info_erreur_restauration' => 'Fejl under genopretning: fil findes ikke.',
'info_etape_suivante' => 'G&aring; til n&aelig;ste trin',
'info_etape_suivante_1' => 'Du kan g&aring; til n&aelig;ste trin.',
'info_etape_suivante_2' => 'Du kan g&aring; til n&aelig;ste trin.',
'info_exportation_base' => 'eksporter database til @archive@',
'info_facilite_suivi_activite' => 'For at lette opf&oslash;lgning p&aring; webstedets redaktionelle aktiviteter sender SPIP e-mails med anmodning om offentligg&oslash;relse og godkendelse til f.eks. redakt&oslash;rens adresseliste.',
'info_fichiers_authent' => 'Adgangskontrolfil ".htpasswd"',
'info_fonctionnement_forum' => 'Forummets funktionsm&aring;de:',
'info_forum_administrateur' => 'administratorforum',
'info_forum_interne' => 'internt forum',
'info_forum_ouvert' => 'I det private afsnit af webstedet er der et forum &aring;bent for alle registrerede redakt&oslash;rer. Nedenfor kan du &aring;bne et ekstra forum alene for administratorer.',
'info_forum_statistiques' => 'Bes&oslash;gsstatistik',
'info_forums_abo_invites' => 'Your site contains forums by subscription; visitors may register for them on the public site.', # NEW
'info_gauche_admin_effacer' => '<b>Kun administratorer har adgang til denne side.</b><p> Den giver adgang til forskellige tekniske vedligeholdelsesopgaver. Nogle af dem giver anledning til en s&aelig;rlig adgangskontrol, der kr&aelig;ver FTP-adgang til siden.', # MODIF
'info_gauche_admin_tech' => '<b>Kun administratorer har adgang til denne side.</b><p> Den giver adgang til forskellige tekniske vedligeholdelsesopgaver. Nogle af dem giver anledning til en s&aelig;rlig adgangskontrol, der kr&aelig;ver FTP-adgang til siden.', # MODIF
'info_gauche_admin_vider' => '<b>Kun administratorer har adgang til denne side.</b><p> Den giver adgang til forskellige tekniske vedligeholdelsesopgaver. Nogle af dem giver anledning til en s&aelig;rlig adgangskontrol, der kr&aelig;ver FTP-adgang til siden.', # MODIF
'info_gauche_auteurs' => 'Her finder du alle webstedets forfattere. Status p&aring; hver enkelt fremg&aring;r af farven p&aring; ikonet (redakt&oslash;r = gr&oslash;n, administrator = gul).',
'info_gauche_auteurs_exterieurs' => 'Udenforst&aring;ende forfattere uden adgang til webstedet vises med et bl&aring;t symbol; slettede forfattere repr&aelig;senteres af en papirkurv.',
'info_gauche_messagerie' => 'Meddelelsessystemet giver mulighed for at udveksle meddelelser mellem redakt&oslash;rer, for at gemme huskesedler (til personlig brug) 
	eller for at vise annonceringer i det private omr&aring;de (hvis du er administrator).',
'info_gauche_numero_auteur' => 'FORFATTER NUMMER:',
'info_gauche_numero_breve' => 'NYHED NUMMER:',
'info_gauche_statistiques_referers' => 'Denne side viser en oversigt over <i>henvisende sider</i>, dvs. websteder der har linket til dit websted alene i dag. Faktisk nulstilles oversigten med 24 timers mellemrum.',
'info_gauche_suivi_forum' => '<i>Forumopf&oslash;lgning</i> er et administrationsv&aelig;rkt&oslash;j (ikke et diskussions- eller redigeringsomr&aring;de). Det viser alle indl&aelig;g i det offentlige forum knyttet til en bestemt artikel og giver dig mulighed for at administrere indl&aelig;ggene.',
'info_gauche_suivi_forum_2' => '<i>Forumopf&oslash;lgning</i> er et administrationsv&aelig;rkt&oslash;j (ikke et diskussions- eller redigeringsomr&aring;de). Siden viser alle indl&aelig;g i webstedets forummer, b&aring;de p&aring; det offentlige og det private omr&aring;de, og giver dig mulighed for at administrere indl&aelig;ggene.',
'info_gauche_visiteurs_enregistres' => 'Her finder du de bes&oslash;gende, der er tilmeldt til webstedets offentlige afsnit (fora med tilmelding).',
'info_generation_miniatures_images' => 'Dannelse af piktogrammer',
'info_gerer_trad' => 'Danne link til overs&aelig;ttelse?',
'info_groupe_important' => 'vigtig gruppe',
'info_hebergeur_desactiver_envoi_email' => 'Nogle webhoteller tillader ikke automatisk udsendelse af e-mails. I s&aring; fald kan f&oslash;lgende funktioner i SPIP ikke benyttes.',
'info_hier' => 'i g&aring;r:',
'info_historique' => 'Revisions:', # NEW
'info_historique_activer' => 'Enable revisions follow-up', # NEW
'info_historique_affiche' => 'Display this version', # NEW
'info_historique_comparaison' => 'compare', # NEW
'info_historique_desactiver' => 'Disable revisions follow-up', # NEW
'info_historique_lien' => 'Display list of versions', # NEW
'info_historique_texte' => 'Revisions follow-up allows you to keep track of every modifications added to an article and displays the differences between successive versions.', # NEW
'info_historique_titre' => 'Revisions follow-up', # NEW
'info_identification_publique' => 'Din offentlige identitet...',
'info_image_process' => 'V&aelig;lg den bedste metode til at skabe miniaturebilleder ved at klikke p&aring; det korresponderende billede.',
'info_image_process2' => '<b>N.B.</b> <i>If you can\'t see any image, then your server is not configured to use such tools. If you want to use these features, contact your provider\'s technical support and ask for the &laquo;GD&raquo; or &laquo;Imagick&raquo; extensions to be installed.</i>', # NEW
'info_images_auto' => 'Images calcul&eacute;es automatiquement', # NEW
'info_informations_personnelles' => 'Femte trin: <b>Personlig information</b>',
'info_inscription_automatique' => 'Automatisk registrering af nye redakt&oslash;rer',
'info_jeu_caractere' => 'Webstedets tegns&aelig;t',
'info_jours' => 'dage',
'info_laisser_champs_vides' => 'efterlad disse felter tomme)',
'info_langues' => 'Webstedets sprog',
'info_ldap_ok' => 'LDAP adgangskontrol er installeret.',
'info_lien_hypertexte' => 'Hypertekst link:',
'info_liens_syndiques_1' => 'syndikerede links',
'info_liens_syndiques_2' => 'afventer godkendelse.',
'info_liens_syndiques_3' => 'forummer',
'info_liens_syndiques_4' => 'er',
'info_liens_syndiques_5' => 'forum',
'info_liens_syndiques_6' => 'er',
'info_liens_syndiques_7' => 'afventer godkendelse.',
'info_liste_redacteurs_connectes' => 'Oversigt over tilknyttede reakt&oslash;rer',
'info_login_existant' => 'Dette login findes allerede.',
'info_login_trop_court' => 'Login for kort.',
'info_logos' => 'Les logos', # NEW
'info_maximum' => 'maksimum:',
'info_meme_rubrique' => 'In the same section', # NEW
'info_message' => 'Meddelelse fra',
'info_message_efface' => 'MEDDELELSE SLETTET',
'info_message_en_redaction' => 'Dine meddelelser under redaktion',
'info_message_technique' => 'Teknisk meddelelse:',
'info_messagerie_interne' => 'Interne meddelelser',
'info_mise_a_niveau_base' => 'SQL databaseopgradering',
'info_mise_a_niveau_base_2' => '{{Advarsel!}} Du har installeret en version af SPIP-filer, der er &aelig;ldre end dem, der var p&aring; webstedet i forvejen. Du risikerer at miste databasen og webstedet vil ikke fungere ordentligt mere.<br />{{Geninstraller SPIP-filerne.}}',
'info_mode_fonctionnement_defaut_forum_public' => 'Standard funktionsm&aring;de for offentlige forummer',
'info_modifier_auteur' => 'Modifier l\'auteur :', # NEW
'info_modifier_breve' => 'Ret nyhed:',
'info_modifier_mot' => 'Modifier le mot-cl&eacute; :', # NEW
'info_modifier_rubrique' => 'Ret afsnit:',
'info_modifier_titre' => 'Ret: @titre@',
'info_mon_site_spip' => 'Mit SPIP-websted',
'info_mot_sans_groupe' => '(N&oslash;gleord uden en gruppe...)',
'info_moteur_recherche' => 'Indbygget s&oslash;gemaskine',
'info_mots_cles' => 'N&oslash;gleord',
'info_mots_cles_association' => 'N&oslash;gleord i denne gruppe kan forbindes med:',
'info_moyenne' => 'gennemsnit:',
'info_multi_articles' => 'Muligg&oslash;re valg af sprog til artiklerne?',
'info_multi_cet_article' => 'Denne artikel er p&aring;:',
'info_multi_langues_choisies' => 'V&aelig;lg de sprog der skal v&aelig;re til r&aring;dighed for redakt&oslash;rer p&aring; webstedet.
  Sprog der allerede er i brug p&aring; webstedet (de &oslash;verste p&aring; listen) kan ikke frav&aelig;lges.
 ',
'info_multi_rubriques' => 'Muligg&oslash;re sprogvalg til afsnit?',
'info_multi_secteurs' => 'Kun for afsnit placeret i roden&nbsp;?',
'info_nom' => 'Navn',
'info_nom_destinataire' => 'Navn p&aring; modtager',
'info_nom_site' => 'Dit websteds navn',
'info_nom_site_2' => '<b>Webstedets navn</b> [Skal udfyldes]',
'info_nombre_articles' => '@nb_articles@ artikler,',
'info_nombre_breves' => '@nb_breves@ nyheder,',
'info_nombre_partcipants' => 'DELTAGERE I DISKUSSIONEN:',
'info_nombre_rubriques' => '@nb_rubriques@ afsnit',
'info_nombre_sites' => '@nb_sites@ websteder,',
'info_non_deplacer' => 'Flyt ikke...',
'info_non_envoi_annonce_dernieres_nouveautes' => 'SPIP kan udsende webstedets seneste indl&aelig;g regelm&aelig;ssigt.
		(nyligt offentliggjorte artikler og nyheder).',
'info_non_envoi_liste_nouveautes' => 'Send ikke oversigt over seneste nyheder',
'info_non_modifiable' => 'kan ikke &aelig;ndres',
'info_non_suppression_mot_cle' => 'Jeg &oslash;nsker ikke at slette dette n&oslash;gleord.',
'info_notes' => 'Fodnoter',
'info_nouveaux_message' => 'Nye meddelelser',
'info_nouvel_article' => 'Ny artikel',
'info_nouvelle_traduction' => 'Ny overs&aelig;ttelse:',
'info_numero_article' => 'ARTIKEL NUMMER:',
'info_obligatoire_02' => '[Skal udfyldes]',
'info_option_accepter_visiteurs' => 'Allow visitors registration from the public site', # NEW
'info_option_email' => 'N&aring;r en bes&oslash;gende p&aring; webstedet sender en meddelelse til forummet, som drejer sig om denne artikel, kan artiklens forfatter f&aring; underretning om meddelelsen via e-mail. &Oslash;nsker du at bruge denne mulighed?', # MODIF
'info_option_faire_suivre' => 'Videresend meddelelser i forummer til artiklernes forfattere',
'info_option_ne_pas_accepter_visiteurs' => 'Refuse visitor registration', # NEW
'info_option_ne_pas_faire_suivre' => 'Videresend ikke meddelelser i forummer',
'info_options_avancees' => 'AVANCEREDE INDSTILLINGER',
'info_ortho_activer' => 'Enable the spell checker.', # NEW
'info_ortho_desactiver' => 'Disable the spell checker.', # NEW
'info_ou' => 'eller...',
'info_oui_suppression_mot_cle' => 'Jeg &oslash;nsker at slette dette n&oslash;gleord permanent.',
'info_page_interdite' => 'Forbudt side',
'info_par_nom' => 'par nom', # NEW
'info_par_nombre_article' => '(efter antal artiker)',
'info_par_statut' => 'par statut', # NEW
'info_par_tri' => '\'(par @tri@)\'', # NEW
'info_pas_de_forum' => 'intet forum',
'info_passe_trop_court' => 'Adgangskode for kort.',
'info_passes_identiques' => 'De to adgangskoder er ikke ens.',
'info_pense_bete_ancien' => 'Dine gamle huskesedler', # MODIF
'info_plus_cinq_car' => 'mere end 5 tegn',
'info_plus_cinq_car_2' => '(Mere end 5 tegn)',
'info_plus_trois_car' => '(Mere end 3 tegn)',
'info_popularite' => 'popularitet: @popularite@; bes&oslash;g: @visites@',
'info_popularite_2' => 'webstedets polularitet:',
'info_popularite_3' => 'polularitet:&nbsp;@popularite@; bes&oslash;g:&nbsp;@visites@',
'info_popularite_4' => 'polularitet:&nbsp;@popularite@; bes&oslash;g:&nbsp;@visites@',
'info_post_scriptum' => 'Efterskrift',
'info_post_scriptum_2' => 'Efterskrift:',
'info_pour' => 'til',
'info_preview_admin' => 'Only administrators have access to the preview mode', # NEW
'info_preview_comite' => 'All authors have access to the preview mode', # NEW
'info_preview_desactive' => 'Preview mode is disabled', # NEW
'info_preview_texte' => 'It is possible to preview the site as if all articles and news items (which have at least the status "submitted") were already published. Should this preview mode be restricted to administrators, open to all authors, or disabled completely?', # NEW
'info_principaux_correspondants' => 'Dine hovedbidragydere',
'info_procedez_par_etape' => 'g&aring; frem skridt for skridt',
'info_procedure_maj_version' => 'opgraderingsprocdeduren b&oslash;r f&oslash;lges for at tilpasse databasen til den nye version af SPIP.',
'info_proxy_ok' => 'Test du proxy r&eacute;ussi.', # NEW
'info_ps' => 'P.S.',
'info_publier' => 'publier', # NEW
'info_publies' => 'Dine offentliggjorte artikler',
'info_question_accepter_visiteurs' => 'If your site\'s templates allow visitors to register without entering the private area, please activate the following option:', # NEW
'info_question_activer_compactage_css' => 'Souhaitez-vous activer le compactage des feuilles de style (CSS) ?', # NEW
'info_question_activer_compactage_js' => 'Souhaitez-vous activer le compactage des scripts (javascript) ?', # NEW
'info_question_activer_compresseur' => 'Voulez-vous activer la compression du flux HTTP ?', # NEW
'info_question_gerer_statistiques' => 'Skal dit websted danne bes&oslash;gsstatistik?',
'info_question_inscription_nouveaux_redacteurs' => 'Vil du tillade, at nye redakt&oslash;rer tilmelder sig
		p&aring; det offentligt tilg&aelig;ngelige websted? Ja betyder, at bes&oslash;gende kan tilmelde sig p&aring; en automatisk dannet formular,
		og derefter f&aring; adgang til det private omr&aring;de, hvor de kan vedligeholde deres egne artikler.
		<blockquote><i>Under tilmeldingen modtager brugerne en automatisk dannet e-mail med deres adgangskode til det
		private websted. Nogle webhoteller tillader ikke at der sendes e-mails fra deres servere. I s&aring; fald kan automatisk
		tilmelding ikke finde sted.', # MODIF
'info_question_mots_cles' => '&Oslash;nsker du at bruge n&oslash;gleord p&aring; webstedet?',
'info_question_proposer_site' => 'Hvem kan foresl&aring; henvisninger til websteder?',
'info_question_utilisation_moteur_recherche' => '&Oslash;nsker du at anvende den s&oslash;gefunktion, der findes i SPIP?
	(At frav&aelig;lge s&oslash;gefunktionen g&oslash;r webstedet hurtigere.)',
'info_question_vignettes_referer' => 'Lorsque vous consultez les statistiques, vous pouvez visualiser des aper&ccedil;us des sites d\'origine des visites', # NEW
'info_question_vignettes_referer_non' => 'Ne pas afficher les captures des sites d\'origine des visites', # NEW
'info_question_vignettes_referer_oui' => 'Afficher les captures des sites d\'origine des visites', # NEW
'info_question_visiteur_ajout_document_forum' => 'Si vous souhaitez autoriser les visiteurs &#224; joindre des documents (images, sons...) &#224; leurs messages de forum, indiquer ci-dessous la liste des extensions de documents autoris&#233;s pour les forums (ex: gif, jpg, png, mp3).', # NEW
'info_question_visiteur_ajout_document_forum_format' => 'Si vous souhaitez autoriser tous les types de documents consid&eacute;r&eacute;s comme fiables par SPIP, mettre une &eacute;toile. Pour ne rien autoriser, ne rien indiquer.', # NEW
'info_qui_attribue_mot_cle' => 'N&oslash;gleord i denne grupe kan tildeles efter:',
'info_racine_site' => 'Top',
'info_recharger_page' => 'V&aelig;r venlig at genindl&aelig;se denne side om et &oslash;jeblik.',
'info_recherche_auteur_a_affiner' => 'For mange resultater fundet til "@cherche_auteur@"; v&aelig;r venlig at afgr&aelig;nse s&oslash;gningen mere.',
'info_recherche_auteur_ok' => 'Der er fundet flere redakt&oslash;rer til "@cherche_auteur@":',
'info_recherche_auteur_zero' => '<b>Ingen resultater fundet til "@cherche_auteur@".',
'info_recommencer' => 'V&aelig;r venlig at fors&oslash;ge igen.',
'info_redacteur_1' => 'Redakt&oslash;r',
'info_redacteur_2' => 'med adgang til det private omr&aring;de (<i>anbefalet</i>)',
'info_redacteurs' => 'Redakt&oslash;rer',
'info_redaction_en_cours' => 'REDIGERING ER IGANG',
'info_redirection' => 'Viderestilling',
'info_referencer_doc_distant' => 'R&eacute;f&eacute;rencer un document sur l\'internet&nbsp;:', # NEW
'info_refuses' => 'Dine artikler er afvist',
'info_reglage_ldap' => 'Muligheder: <b>Konfigurere LDAP underst&oslash;ttelse</b>',
'info_renvoi_article' => '<b>Viderestilling.</b> Denne artikel henviser til siden:',
'info_reserve_admin' => 'Kun administratorer kan &aelig;ndre denne adresse.',
'info_restauration_sauvegarde' => 'Genindl&aelig;sning af sikkerhedskopi @archive@', # MODIF
'info_restauration_sauvegarde_insert' => 'Insertion de @archive@ dans la base', # NEW
'info_restreindre_rubrique' => 'Begr&aelig;ns administrationsrettigheder til dette afsnit:',
'info_resultat_recherche' => 'S&oslash;geresultater:',
'info_rubriques' => 'Afsnit',
'info_rubriques_02' => 'afsnit',
'info_rubriques_liees_mot' => 'Afsnit knyttet til dette n&oslash;gleord',
'info_rubriques_trouvees' => 'Afsnit fundet',
'info_rubriques_trouvees_dans_texte' => 'Afsnit fundet (i teksten)',
'info_sans_titre' => 'Uden overskrift',
'info_sauvegarde' => 'Sikkerhedskopi',
'info_sauvegarde_articles' => 'Sikkerhedskopi af artikler',
'info_sauvegarde_articles_sites_ref' => 'Sikkerhedskopi af henvisninger til websteder',
'info_sauvegarde_auteurs' => 'Sikkerhedskopi af forfattere',
'info_sauvegarde_breves' => 'Sikkerhedskopi af nyheder',
'info_sauvegarde_documents' => 'Sikkerhedskopi af dokumenter',
'info_sauvegarde_echouee' => 'Hvis sikkerhedskopiering mislykkes (&laquo;Max. eksekveringstid overskredet&raquo;),',
'info_sauvegarde_forums' => 'Sikkerhedskopi af forummer',
'info_sauvegarde_groupe_mots' => 'Sikkerhedskopi af n&oslash;gleordsgrupper',
'info_sauvegarde_messages' => 'Sikkerhedskopi af meddelelser',
'info_sauvegarde_mots_cles' => 'Sikkerhedskopi af n&oslash;gleord',
'info_sauvegarde_petitions' => 'Sikkerhedskopi af appeller',
'info_sauvegarde_refers' => 'Sikkerhedskopi af henvisende sider',
'info_sauvegarde_reussi_01' => 'Sikkerhedskopiering gennemf&oslash;rt.',
'info_sauvegarde_reussi_02' => 'Databasen er gemt i @archive@. Du kan',
'info_sauvegarde_reussi_03' => 'returnere til administration',
'info_sauvegarde_reussi_04' => 'af webstedet.',
'info_sauvegarde_rubrique_reussi' => 'Les tables de la rubrique @titre@ ont &eacute;t&eacute; sauvegard&eacute;e dans @archive@. Vous pouvez', # NEW
'info_sauvegarde_rubriques' => 'Sikkerhedskopi af afsnit',
'info_sauvegarde_signatures' => 'Sikkerhedskopi af underskrifter',
'info_sauvegarde_sites_references' => 'Sikkerhedskopi af links til websteder',
'info_sauvegarde_type_documents' => 'Sikkerhedskopi af dokumenttyper',
'info_sauvegarde_visites' => 'Sikkerhedskopi af bes&oslash;g',
'info_selection_chemin_acces' => '<b>V&aelig;lg</b> nedenfor stien til kataloget:',
'info_selection_un_seul_mot_cle' => 'Du kan <b>kun v&aelig;lge et n&oslash;gleord</b> ad gangen i denne gruppe.',
'info_signatures' => 'underskrifter',
'info_site' => 'Websted',
'info_site_2' => 'websted:',
'info_site_min' => 'websted',
'info_site_propose' => 'Websted sendt til godkendelse den:',
'info_site_reference_2' => 'Henvisning',
'info_site_syndique' => 'Dette websted er syndikeret...',
'info_site_valider' => 'Websteder der afventer godkendelse',
'info_site_web' => 'WEBSTED:',
'info_sites' => 'websteder',
'info_sites_lies_mot' => 'Links til websteder knyttet til dette n&oslash;gleord',
'info_sites_proxy' => 'Brug proxy',
'info_sites_refuses' => 'Afviste websteder',
'info_sites_trouves' => 'Websteder fundet',
'info_sites_trouves_dans_texte' => 'Websteder fundet (i teksten)',
'info_sous_titre' => 'Underrubrik:',
'info_statut_administrateur' => 'Administrator',
'info_statut_auteur' => 'Denne forfatters status:', # MODIF
'info_statut_auteur_a_confirmer' => 'Inscription &agrave; confirmer', # NEW
'info_statut_auteur_autre' => 'Autre statut&nbsp;:', # NEW
'info_statut_efface' => 'Slettet',
'info_statut_redacteur' => 'Redakt&oslash;r',
'info_statut_site_1' => 'Dette websted er:',
'info_statut_site_2' => 'Offentliggjort',
'info_statut_site_3' => 'Indsendt',
'info_statut_site_4' => 'I papirkurven',
'info_statut_utilisateurs_1' => 'Importerede brugeres standardstatus',
'info_statut_utilisateurs_2' => 'V&aelig;lg den status som skal tildeles personerne i LDAP kataloget, n&aring;r de logger ind f&oslash;rste gang. Senere kan du &aelig;ndre v&aelig;rdien for hver forfatter fra sag til sag.',
'info_suivi_activite' => 'Opf&oslash;lgning p&aring; redaktionelle aktiviteter',
'info_supprimer_mot' => 'slet dette n&oslash;gleord',
'info_surtitre' => 'Hovedoverskrift:',
'info_syndication_integrale_1' => 'Votre site propose des fichiers de syndication (voir &laquo;&nbsp;<a href="@url@">@titre@</a>&nbsp;&raquo;).', # NEW
'info_syndication_integrale_2' => 'Souhaitez-vous transmettre les articles dans leur int&eacute;gralit&eacute;, ou ne diffuser qu\'un r&eacute;sum&eacute; de quelques centaines de caract&egrave;res&nbsp;?', # NEW
'info_table_prefix' => 'Vous pouvez modifier le pr&eacute;fixe du nom des tables de donn&eacute;es (ceci est indispensable lorsque l\'on souhaite installer plusieurs sites dans la m&ecirc;me base de donn&eacute;es). Ce pr&eacute;fixe s\'&eacute;crit en lettres minuscules, non accentu&eacute;es, et sans espace.', # NEW
'info_taille_maximale_images' => 'SPIP va tester la taille maximale des images qu\'il peut traiter (en millions de pixels).<br /> Les images plus grandes ne seront pas r&eacute;duites.', # NEW
'info_taille_maximale_vignette' => 'Max. st&oslash;rrelse p&aring; piktogram dannet af systemet:',
'info_terminer_installation' => 'Du kan nu afslutte standardinstallationen.',
'info_texte' => 'Tekst',
'info_texte_explicatif' => 'Forklarende tekst',
'info_texte_long' => '(teksten er for lang: den vil blive opdelt i flere dele, som vil blive sat sammen efter godkendelse.)',
'info_texte_message' => 'Meddelelsens tekst:',
'info_texte_message_02' => 'Meddelelsens tekst',
'info_titre' => 'Overskrift:',
'info_titre_mot_cle' => 'Navn eller titel p&aring; dette n&oslash;gleord',
'info_total' => 'ialt:',
'info_tous_articles_en_redaction' => 'Alle artikler undervejs',
'info_tous_articles_presents' => 'Alle artikler offentliggjort i dette afsnit',
'info_tous_articles_refuses' => 'Tous les articles refus&eacute;s', # NEW
'info_tous_les' => 'for hver:',
'info_tous_redacteurs' => 'Annonceringer til alle redakt&oslash;rer',
'info_tout_site' => 'Hele webstedet',
'info_tout_site2' => 'Artiklen er ikke blevet oversat til dette sprog.',
'info_tout_site3' => 'Artiklen er blevet oversat til dette sprig, men nogle &aelig;ndringer er senere blevet tilf&oslash;jet til referenceartiklen. Overs&aelig;ttelsen skal opdateres.   ',
'info_tout_site4' => 'Artiklen er blevet oversat til dette sprog og overs&aelig;ttelsen er opdateret.',
'info_tout_site5' => 'Den oprindelige artikel.',
'info_tout_site6' => '<b>Advarsel:</b> kun de oprindelige artikler vises.
Overs&aelig;ttelserne er tilknyttet den oprindelige artikel 
i en farve, der angiver deres status:',
'info_travail_colaboratif' => 'Samarbejde om artikler',
'info_un_article' => 'en artikel,',
'info_un_mot' => 'Et n&oslash;gleord ad gangen',
'info_un_site' => 'et websted,',
'info_une_breve' => 'en nyhed,',
'info_une_rubrique' => 'et afsnit,',
'info_une_rubrique_02' => '1 afsnit',
'info_url' => 'URL:',
'info_url_site' => 'WEBSTEDETS URL:',
'info_urlref' => 'Hyperlink:',
'info_utilisation_spip' => 'SPIP er nu klar til brug...',
'info_visites_par_mois' => 'Bes&oslash;g pr. m&aring;ned:',
'info_visites_plus_populaires' => 'Vis bes&oslash;g til <b>de mest popul&aelig;re artikler</b> og til <b>de senest offentliggjorte artikler:</b>',
'info_visiteur_1' => 'Bes&oslash;gende',
'info_visiteur_2' => 'p&aring; den offentligt tilg&aelig;ngelige websted',
'info_visiteurs' => 'Bes&oslash;gende',
'info_visiteurs_02' => 'Bes&oslash;gende p&aring; offentligt websted',
'install_adresse_base_hebergeur' => 'Adresse de la base de donn&eacute;es attribu&eacute;e par l\'h&eacute;bergeur', # NEW
'install_base_ok' => 'La base @base@ a &eacute;t&eacute; reconnue', # NEW
'install_echec_annonce' => 'L\'installation va probablement &eacute;chouer, ou aboutir &agrave; un site non fonctionnel...', # NEW
'install_extension_mbstring' => 'SPIP ne fonctionne pas avec&nbsp;:', # NEW
'install_extension_php_obligatoire' => 'SPIP exige l\'extension php&nbsp;:', # NEW
'install_login_base_hebergeur' => 'Login de connexion attribu&eacute; par l\'h&eacute;bergeur', # NEW
'install_nom_base_hebergeur' => 'Nom de la base attribu&eacute; par l\'h&eacute;bergeur&nbsp;:', # NEW
'install_pas_table' => 'Base actuellement sans tables', # NEW
'install_pass_base_hebergeur' => 'Mot de passe de connexion attribu&eacute; par l\'h&eacute;bergeur', # NEW
'install_php_version' => 'PHP version @version@ insuffisant (minimum = @minimum@)', # NEW
'install_select_langue' => 'V&aelig;lg et sprog og klik derefter p&aring; knappen &laquo;n&aelig;ste&raquo; for at igangs&aelig;tte installationen.',
'install_select_type_db' => 'Indiquer le type de base de donn&eacute;es&nbsp;:', # NEW
'install_select_type_mysql' => 'MySQL', # NEW
'install_select_type_pg' => 'PostgreSQL', # NEW
'install_select_type_sqlite2' => 'SQLite 2', # NEW
'install_select_type_sqlite3' => 'SQLite 3', # NEW
'install_serveur_hebergeur' => 'Serveur de base de donn&eacute;es attribu&eacute; par l\'h&eacute;bergeur', # NEW
'install_table_prefix_hebergeur' => 'Pr&eacute;fixe de table attribu&eacute; par l\'h&eacute;bergeur&nbsp;:', # NEW
'install_tables_base' => 'Tables de la base', # NEW
'install_types_db_connus' => 'SPIP sait utiliser <b>MySQL</b> (le plus r&eacute;pandu), <b>PostgreSQL</b> et <b>SQLite</b>.', # NEW
'install_types_db_connus_avertissement' => 'Attention&nbsp;: plusieurs plugins ne fonctionnent qu\'avec MySQL', # NEW
'intem_redacteur' => 'redakt&oslash;r',
'intitule_licence' => 'Licence', # NEW
'item_accepter_inscriptions' => 'Tillad tilmeldinger',
'item_activer_forum_administrateur' => 'Sl&aring; funktionen administratorforum til',
'item_activer_messages_avertissement' => 'Tillad advarselsmeddelelser',
'item_administrateur_2' => 'administrator',
'item_afficher_calendrier' => 'Vis i kalenderen',
'item_ajout_mots_cles' => 'Tillad at knytte n&oslash;gleord til forummer',
'item_autoriser_documents_joints' => 'Tillad at vedh&aelig;fte dokumenter til artikler',
'item_autoriser_documents_joints_rubriques' => 'Tillad dokumenter i afsnit',
'item_autoriser_selectionner_date_en_ligne' => 'Permettre de modifier la date de chaque document', # NEW
'item_autoriser_syndication_integrale' => 'Diffuser l\'int&eacute;gralit&eacute; des articles dans les fichiers de syndication', # NEW
'item_bloquer_liens_syndiques' => 'Afsp&aelig;r syndikerede links indtil de er godkendt',
'item_breve_refusee' => 'NEJ - Nyhed afvist',
'item_breve_validee' => 'JA - Nyhed godkendt',
'item_choix_administrateurs' => 'administratorer',
'item_choix_generation_miniature' => 'Dan miniaturepiktogrammer automatisk.',
'item_choix_non_generation_miniature' => 'Dan ikke miniaturebilleder.',
'item_choix_redacteurs' => 'redakt&oslash;rer',
'item_choix_visiteurs' => 'bes&oslash;gende p&aring; den offentlige websted',
'item_compresseur' => 'Activer la compression', # NEW
'item_config_forums_prive_global' => 'Activer le forum des r&#233;dacteurs', # NEW
'item_config_forums_prive_objets' => 'Activer ces forums', # NEW
'item_creer_fichiers_authent' => 'Dan .htpasswd filer',
'item_desactiver_forum_administrateur' => 'Sl&aring; funktionen administratorforum fra',
'item_gerer_annuaire_site_web' => 'Vedligehold katalog over websteder',
'item_gerer_statistiques' => 'Dan statistik',
'item_limiter_recherche' => 'Begr&aelig;ns s&oslash;gning til information p&aring; din eget websted',
'item_login' => 'Login',
'item_messagerie_agenda' => 'Activer la messagerie et l&#8217;agenda', # NEW
'item_mots_cles_association_articles' => 'artiklerne',
'item_mots_cles_association_breves' => 'nyhederne',
'item_mots_cles_association_rubriques' => 'afsnittene',
'item_mots_cles_association_sites' => 'de linkede eller syndikerede websteder.',
'item_non' => 'Nej',
'item_non_accepter_inscriptions' => 'Tillad ikke tilmelding',
'item_non_activer_messages_avertissement' => 'Ingen advarselsmeddelelser',
'item_non_afficher_calendrier' => 'Vis ikke i kalender',
'item_non_ajout_mots_cles' => 'Tillad ikke at knytte n&oslash;gleord til forummer',
'item_non_autoriser_documents_joints' => 'Tillad ikke vedh&aelig;ftning af dokumenter til artikler',
'item_non_autoriser_documents_joints_rubriques' => 'Tillad ikke dokumenter i afsnit',
'item_non_autoriser_selectionner_date_en_ligne' => 'La date des documents est celle de leur ajout sur le site', # NEW
'item_non_autoriser_syndication_integrale' => 'Ne diffuser qu\'un r&eacute;sum&eacute;', # NEW
'item_non_bloquer_liens_syndiques' => 'Undlad at sp&aelig;rre links til syndikerede websteder',
'item_non_compresseur' => 'D&#233;sactiver la compression', # NEW
'item_non_config_forums_prive_global' => 'D&#233;sactiver le forum des r&#233;dacteurs', # NEW
'item_non_config_forums_prive_objets' => 'D&#233;sactiver ces forums', # NEW
'item_non_creer_fichiers_authent' => 'Dan ikke disse filer',
'item_non_gerer_annuaire_site_web' => 'Vedligehold ikke katalog over websteder',
'item_non_gerer_statistiques' => 'Dan ikke statistik',
'item_non_limiter_recherche' => 'Udvid s&oslash;gning til indholdet i linkede websteder',
'item_non_messagerie_agenda' => 'D&#233;sactiver la messagerie et l&#8217;agenda', # NEW
'item_non_publier_articles' => 'Vent med at offentligg&oslash;re artikler til deres publiceringsdato.',
'item_non_utiliser_breves' => 'Luk ikke op for nyheder',
'item_non_utiliser_config_groupe_mots_cles' => 'Undlad at anvende avanceret konfigurering af n&oslash;gleordsgrupper',
'item_non_utiliser_moteur_recherche' => 'Benyt ikke s&oslash;gefunktion',
'item_non_utiliser_mots_cles' => 'Benyt ikke n&oslash;gleord',
'item_non_utiliser_syndication' => 'Benyt ikke automatisk syndikering',
'item_nouvel_auteur' => 'Ny forfatter',
'item_nouvelle_breve' => 'Ny nyhed',
'item_nouvelle_rubrique' => 'Nyt afsnit',
'item_oui' => 'Ja',
'item_publier_articles' => 'Offentligg&oslash;r artikler uden hensyn til deres publiceringsdato.',
'item_reponse_article' => 'Kommenter artiklen',
'item_utiliser_breves' => 'Anvend nyheder',
'item_utiliser_config_groupe_mots_cles' => 'Benyt avanceret konfigurering af n&oslash;gleordsgrupper',
'item_utiliser_moteur_recherche' => 'Benyt s&oslash;gefunktion',
'item_utiliser_mots_cles' => 'Benyt n&oslash;gleord',
'item_utiliser_syndication' => 'Benyt automatisk syndikering',
'item_visiteur' => 'bes&oslash;gende',

// J
'jour_non_connu_nc' => '&nbsp;',

// L
'ldap_correspondance' => 'h&eacute;ritage du champ @champ@', # NEW
'ldap_correspondance_1' => 'H&eacute;ritage des champs LDAP', # NEW
'ldap_correspondance_2' => 'Pour chacun des champs SPIP suivants, indiquer le nom du champ LDAP correspondant. Laisser vide pour ne pas le remplir, s&eacute;parer par des espaces ou des virgules pour essayer plusieurs champs LDAP.', # NEW
'lien_ajout_destinataire' => 'Tilf&oslash;j denne modtager',
'lien_ajouter_auteur' => 'Tilf&oslash;j denne forfatter',
'lien_ajouter_participant' => 'Tilf&oslash;j modtager',
'lien_email' => 'e-mail',
'lien_forum_public' => 'Vedligehold denne artikels offentlige forum',
'lien_mise_a_jour_syndication' => 'Opdater nu',
'lien_nom_site' => 'WEBSTEDETS NAVN:',
'lien_nouvelle_recuperation' => 'Fors&oslash;g at hente data igen',
'lien_reponse_article' => 'Kommenter denne artikel',
'lien_reponse_breve' => 'Kommenter denne nyhed',
'lien_reponse_breve_2' => 'Kommenter denne nyhed',
'lien_reponse_rubrique' => 'Kommenter dette afsnit',
'lien_reponse_site_reference' => 'Kommenter dette link:',
'lien_retirer_auteur' => 'Fjern forfatter',
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
'logo_article' => 'LOGO TIL ARTIKLEN',
'logo_auteur' => 'LOGO TIL FORFATTEREN',
'logo_breve' => 'LOGO TIL NYHEDEN',
'logo_mot_cle' => 'LOGO TIL N&Oslash;GLEORDET',
'logo_rubrique' => 'LOGO TIL AFSNITTETS',
'logo_site' => 'LOGO TIL WEBSTEDETS',
'logo_standard_rubrique' => 'STANDARDLOGO TIL AFSNIT',
'logo_survol' => 'PEGEF&Oslash;LSOMT LOGO',

// M
'menu_aide_installation_choix_base' => 'Valg af database',
'module_fichier_langue' => 'Sprogfil',
'module_raccourci' => 'Genvej',
'module_texte_affiche' => 'Vist tekst',
'module_texte_explicatif' => 'Du kan inds&aelig;tte f&oslash;lgende genveje i dit websteds skabeloner. De vil automatisk blive oversat til de forskellige sprog, som der findes sprogfiler til.',
'module_texte_traduction' => 'Sprogfilen &laquo;&nbsp;@module@&nbsp;&raquo; findes p&aring;:',
'mois_non_connu' => 'ukendt',

// N
'nouvelle_version_spip' => 'La version @version@ de SPIP est disponible', # NEW

// O
'onglet_contenu' => 'Contenu', # NEW
'onglet_declarer_une_autre_base' => 'D&eacute;clarer une autre base', # NEW
'onglet_discuter' => 'Discuter', # NEW
'onglet_documents' => 'Documents', # NEW
'onglet_interactivite' => 'Interactivit&eacute;', # NEW
'onglet_proprietes' => 'Propri&eacute;t&eacute;s', # NEW
'onglet_repartition_actuelle' => 'nu',
'onglet_sous_rubriques' => 'Sous-rubriques', # NEW

// P
'page_pas_proxy' => 'Cette page ne doit pas passer par le proxy', # NEW
'pas_de_proxy_pour' => 'Au besoin, indiquez les machines ou domaines pour lesquels ce proxy ne doit pas s\'appliquer (par exemple&nbsp;: @exemple@)', # NEW
'plugin_charge_paquet' => 'Chargement du paquet @name@', # NEW
'plugin_charger' => 'T&#233;l&#233;charger', # NEW
'plugin_erreur_charger' => 'erreur&nbsp;: impossible de charger @zip@', # NEW
'plugin_erreur_droit1' => 'Le r&#233;pertoire <code>@dest@</code> n\'est pas accessible en &#233;criture.', # NEW
'plugin_erreur_droit2' => 'Veuillez v&#233;rifier les droits sur ce r&#233;pertoire (et le cr&#233;er le cas &#233;ch&#233;ant), ou installer les fichiers par FTP.', # NEW
'plugin_erreur_zip' => 'echec pclzip&nbsp;: erreur @status@', # NEW
'plugin_etat_developpement' => 'en d&eacute;veloppement', # NEW
'plugin_etat_experimental' => 'exp&eacute;rimental', # NEW
'plugin_etat_stable' => 'stable', # NEW
'plugin_etat_test' => 'en test', # NEW
'plugin_impossible_activer' => 'Impossible d\'activer le plugin @plugin@', # NEW
'plugin_info_automatique1' => 'Si vous souhaitez autoriser l\'installation automatique des plugins, veuillez&nbsp;:', # NEW
'plugin_info_automatique1_lib' => 'Si vous souhaitez autoriser l\'installation automatique de cette librairie, veuillez&nbsp;:', # NEW
'plugin_info_automatique2' => 'cr&#233;er un r&#233;pertoire <code>@rep@</code>&nbsp;;', # NEW
'plugin_info_automatique3' => 'v&#233;rifier que le serveur est autoris&#233; &#224; &#233;crire dans ce r&#233;pertoire.', # NEW
'plugin_info_automatique_creer' => '&#224; cr&#233;er &#224; la racine du site.', # NEW
'plugin_info_automatique_exemples' => 'exemples&nbsp;:', # NEW
'plugin_info_automatique_ftp' => 'Vous pouvez installer des plugins, par FTP, dans le r&#233;pertoire <tt>@rep@</tt>', # NEW
'plugin_info_automatique_lib' => 'Certains plugins demandent aussi &#224; pouvoir t&#233;l&#233;charger des fichiers dans le r&#233;pertoire <code>lib/</code>, &#224; cr&#233;er le cas &#233;ch&#233;ant &#224; la racine du site.', # NEW
'plugin_info_automatique_liste' => 'Vos listes de plugins&nbsp;:', # NEW
'plugin_info_automatique_liste_officielle' => 'les plugins officiels', # NEW
'plugin_info_automatique_liste_update' => 'Mettre &#224; jour les listes', # NEW
'plugin_info_automatique_ou' => 'ou...', # NEW
'plugin_info_automatique_select' => 'S&#233;lectionnez ci-dessous un plugin&nbsp;: SPIP le t&#233;l&#233;chargera et l\'installera dans le r&#233;pertoire <code>@rep@</code>&nbsp;; si ce plugin existe d&#233;j&#224;, il sera mis &#224; jour.', # NEW
'plugin_info_extension_1' => 'Les extensions ci-dessous sont charg&#233;es et activ&#233;es dans le r&#233;pertoire @extensions@.', # NEW
'plugin_info_extension_2' => 'Elles ne sont pas d&#233;sactivables.', # NEW
'plugin_info_telecharger' => '&#224; t&#233;l&#233;charger depuis @url@ et &#224; installer dans @rep@', # NEW
'plugin_librairies_installees' => 'Librairies install&#233;es', # NEW
'plugin_necessite_lib' => 'Ce plugin n&#233;cessite la librairie @lib@', # NEW
'plugin_necessite_plugin' => 'N&eacute;cessite le plugin @plugin@ en version @version@ minimum.', # NEW
'plugin_necessite_spip' => 'N&eacute;cessite SPIP en version @version@ minimum.', # NEW
'plugin_source' => 'source:&nbsp;', # NEW
'plugin_titre_automatique' => 'Installation automatique', # NEW
'plugin_titre_automatique_ajouter' => 'Ajouter des plugins', # NEW
'plugin_titre_installation' => 'Installation du plugin @plugin@', # NEW
'plugin_zip_active' => 'Continuez pour l\'activer', # NEW
'plugin_zip_adresse' => 'indiquez ci-dessous l\'adresse d\'un fichier zip de plugin &#224; t&#233;l&#233;charger, ou encore l\'adresse d\'une liste de plugins.', # NEW
'plugin_zip_adresse_champ' => 'Adresse du plugin ou de la liste&nbsp;', # NEW
'plugin_zip_content' => 'Il contient les fichiers suivants (@taille@),<br />pr&#234;ts &#224; installer dans le r&#233;pertoire <code>@rep@</code>', # NEW
'plugin_zip_installe_finie' => 'Le fichier @zip@ a &#233;t&#233; d&#233;compact&#233; et install&#233;.', # NEW
'plugin_zip_installe_rep_finie' => 'Le fichier @zip@ a &#233;t&#233; d&#233;compact&#233; et install&#233; dans le r&#233;pertoire @rep@', # NEW
'plugin_zip_installer' => 'Vous pouvez maintenant l\'installer.', # NEW
'plugin_zip_telecharge' => 'Le fichier @zip@ a &#233;t&#233; t&#233;l&#233;charg&#233;', # NEW
'plugins_actif_aucun' => 'Aucun plugin activ&#233;.', # NEW
'plugins_actif_un' => 'Un plugin activ&#233;.', # NEW
'plugins_actifs' => '@count@ plugins activ&#233;s.', # NEW
'plugins_actifs_liste' => 'Plugins actifs', # NEW
'plugins_compte' => '@count@ plugins', # NEW
'plugins_disponible_un' => 'Un plugin disponible.', # NEW
'plugins_disponibles' => '@count@ plugins disponibles.', # NEW
'plugins_erreur' => 'Erreur dans les plugins : @plugins@', # NEW
'plugins_liste' => 'Liste des plugins', # NEW
'plugins_liste_extensions' => 'Extensions', # NEW
'plugins_recents' => 'Plugins r&eacute;cents.', # NEW
'plugins_vue_hierarchie' => 'Hi&eacute;rarchie', # NEW
'plugins_vue_liste' => 'Liste', # NEW
'protocole_ldap' => 'Version du protocole :', # NEW

// R
'repertoire_plugins' => 'R&eacute;pertoire&nbsp;:', # NEW

// S
'sans_heure' => 'sans heure', # NEW
'sauvegarde_fusionner' => 'Fusionner la base actuelle et la sauvegarde', # NEW
'sauvegarde_fusionner_depublier' => 'D&eacute;publier les objets fusionn&eacute;s', # NEW
'sauvegarde_url_origine' => 'Eventuellement, URL du site d\'origine :', # NEW
'statut_admin_restreint' => '(begr&aelig;nset admin)',
'syndic_choix_moderation' => 'Que faire des prochains liens en provenance de ce site&nbsp;?', # NEW
'syndic_choix_oublier' => 'Que faire des liens qui ne figurent plus dans le fichier de syndication&nbsp;?', # NEW
'syndic_choix_resume' => 'Certains sites diffusent le texte complet des articles. Lorsque celui-ci est disponible souhaitez-vous syndiquer&nbsp;:', # NEW
'syndic_lien_obsolete' => 'lien obsol&egrave;te', # NEW
'syndic_option_miroir' => 'les bloquer automatiquement', # NEW
'syndic_option_oubli' => 'les effacer (apr&egrave;s @mois@&nbsp;mois)', # NEW
'syndic_option_resume_non' => 'le contenu complet des articles (au format HTML)', # NEW
'syndic_option_resume_oui' => 'un simple r&eacute;sum&eacute; (au format texte)', # NEW
'syndic_options' => 'Options de syndication&nbsp;:', # NEW

// T
'taille_cache_image' => 'Les images calcul&eacute;es automatiquement par SPIP (vignettes des documents, titres pr&eacute;sent&eacute;s sous forme graphique, fonctions math&eacute;matiques au format TeX...) occupent dans le r&eacute;pertoire @dir@ un total de @taille@.', # NEW
'taille_cache_infinie' => 'This site does not have any fixed limit for the size of the <code>CACHE/</code> directory.', # NEW
'taille_cache_maxi' => 'SPIP will try to limit the size of the <code>CACHE/</code> directory to approximately <b>@octets@</b> of data.', # NEW
'taille_cache_octets' => 'The size of the cache is currently @octets@.', # NEW
'taille_cache_vide' => 'The cache is empty.', # NEW
'taille_repertoire_cache' => 'Current size of the cache', # NEW
'text_article_propose_publication' => 'Artiklen er sendt til offentligg&oslash;relse. Hold dig ikke tilbage fra at give din mening til kende gennem det forum, der er tilknyttet artiklen (nederst p&aring; siden).', # MODIF
'text_article_propose_publication_forum' => 'N\'h&eacute;sitez pas &agrave; donner votre avis gr&acirc;ce au forum attach&eacute; &agrave; cet article (en bas de page).', # NEW
'texte_acces_ldap_anonyme_1' => 'Nogle LDAP-servere tillader ikke anonym adgang. I s&aring; fald m&aring; du angive en brugeridentifikation for senere at kunne s&oslash;ge efter information i kataloget. Men i de fleste tilf&aelig;lde kan du lade de f&oslash;lgende felter st&aring; tomme.',
'texte_admin_effacer_01' => 'Denne kommando sletter <i>hele</i> indholdet i databasen,
	herunder <i>hele</i> ops&aelig;tningen for redakt&oslash;rer og administratorer. N&aring;r du har udf&oslash;rt den, b&oslash;r du 
	geninstallere SPIP for at danne en ny database og &aring;bne op for den f&oslash;rste administratoradgang.',
'texte_admin_effacer_stats' => 'Cette commande efface toutes les donn&eacute;es li&eacute;es aux statistiques de visite du site, y compris la popularit&eacute; des articles.', # NEW
'texte_admin_tech_01' => 'Dette valg giver dig mulighed for at gemme databasens indhold i en fil lagret i kataloget 
 @dossier@.
 Husk ogs&aring; at medtage hele kataloget <i>IMG/</i>, som rummer de billeder og dokumenter, der bruges i artikler og afsnit.',
'texte_admin_tech_02' => 'Advarsel: denne sikkerhedskopi kan KUN genindl&aelig;ses p&aring; et websted, der har installeret samme version af SPIP.
 Det er en almindelig misforst&aring;else at tage sikkerhedskopi af et websted forud for opgradering af SPIP...
 For mere information henvises til <a href="@spipnet@">SPIP dokumentation</a>.', # MODIF
'texte_admin_tech_03' => 'Du kan v&aelig;lge at gemme filen i komprimeret form for hurtigere at kunne overf&oslash;re den til din maskine eller tage en sikkerhedskopi af serveren og spare diskplads.',
'texte_admin_tech_04' => 'Dans un but de fusion avec une autre base, vous pouvez limiter la sauvegarde &agrave; la rubrique: ', # NEW
'texte_adresse_annuaire_1' => '(Hvis dit katalog findes p&aring; samme server som webstedet, er det formentlig &laquo;localhost&raquo;.)',
'texte_ajout_auteur' => 'F&oslash;lgende forfatter har bidraget til artiklen:',
'texte_annuaire_ldap_1' => 'Hvis du har adgang til et LDAP-katalog, kan du anvende det til automatisk at importere brugere i SPIP.',
'texte_article_statut' => 'Denne artikel er:',
'texte_article_virtuel' => 'Virtuel artikel',
'texte_article_virtuel_reference' => '<b>Virtuel artikel:</b> fremst&aring;r som en artikel p&aring; dit websted, men viderestiller til en anden URL. Slet URL\'en for at fjerne viderestillingen.',
'texte_aucun_resultat_auteur' => 'Ingen resultater til "@cherche_auteur@".',
'texte_auteur_messagerie' => 'Dette websted kan l&oslash;bende holde &oslash;je med, hvilke redakt&oslash;rer der er logget ind. Dette muligg&oslash;r realtidsudveksling af meddelelser (hvis udveksling af meddelser ovenfor er fravalgt, vedligeholdes oversigten over redakt&oslash;rer, der er online, heller ikke). Du kan v&aelig;lge ikke at v&aelig;re synlig i oversigten (du er s&aring; &laquo;usynlig&raquo; for andre brugere).',
'texte_auteur_messagerie_1' => 'Dette websted tillader udveksling af meddelelser og oprettelse af private diskussionsforummer mellem deltagere p&aring; webstedet. Du kan v&aelig;lge ikke at deltage i udvekslingen.',
'texte_auteurs' => 'FORFATTERNE',
'texte_breves' => 'Nyheder er korte og enkle tekster der muligg&oslash;r online offentligg&oslash;relse af pr&aelig;cis information, administration af presseanmeldelser, arrangementskalender...',
'texte_choix_base_1' => 'V&aelig;lg database:',
'texte_choix_base_2' => 'SQL server indeholder et antal databaser.',
'texte_choix_base_3' => '<b>V&aelig;lg</b> v&aelig;lg nedenfor den database, som webhotellet har tildelt dig:',
'texte_choix_table_prefix' => 'Pr&eacute;fixe des tables&nbsp;:', # NEW
'texte_commande_vider_tables_indexation' => 'Brug denne kommando til at t&oslash;mme de indekstabeller, som benyttes af SPIP\'s indbyggede s&oslash;gefunktion.
			Derved kan du spare diskplads.',
'texte_comment_lire_tableau' => 'Artiklens rangering i popularitetslisten vises i marginen. Artiklens popularitet 
		(et overslag over hvor mange daglige bes&oslash;g den vil opn&aring;, hvis det aktuelle trafikomfang opretholdes) 
		og antallet af bes&oslash;g, der er registreret siden begyndelsen, vises i den ballon, der kommer til syne n&aring;r musen 
		holdes over titlen.',
'texte_compacter_avertissement' => 'Attention &#224; ne pas activer ces options durant le d&#233;veloppement de votre site : les &#233;l&#233;ments compact&#233;s perdent toute lisibilit&#233;.', # NEW
'texte_compacter_script_css' => 'SPIP peut compacter les scripts javascript et les feuilles de style CSS, pour les enregistrer dans des fichiers statiques ; cela acc&#233;l&#232;re l\'affichage du site.', # NEW
'texte_compresse_ou_non' => '(denne kan v&aelig;re komprimeret eller ikke)',
'texte_compresseur_page' => 'SPIP peut compresser automatiquement chaque page qu\'il envoie aux
visiteurs du site. Ce r&#233;glage permet d\'optimiser la bande passante (le
site est plus rapide derri&#232;re une liaison &#224; faible d&#233;bit), mais
demande plus de puissance au serveur.', # NEW
'texte_compte_element' => '@count@ element',
'texte_compte_elements' => '@count@ elementer',
'texte_config_groupe_mots_cles' => '&Oslash;nsker du at aktivere den avancerede konfiguration af n&oslash;gleordsgrupper, ved f.eks. at specificere
			 at et entydigt ord pr. gruppe kan v&aelig;lges, at en gruppe er vigtig...?',
'texte_conflit_edition_correction' => 'Veuillez contr&#244;ler ci-dessous les diff&#233;rences entre les deux versions du texte&nbsp;; vous pouvez aussi copier vos modifications, puis recommencer.', # NEW
'texte_connexion_mysql' => 'Sl&aring; op i de oplysninger, som dit webhotel har stillet til r&aring;dighed: Hvis webhotellet underst&oslash;tter SQL, b&oslash;r det indeholde oplysninger om opkobling.', # MODIF
'texte_contenu_article' => '(Artiklens indhold med f&aring; ord.)',
'texte_contenu_articles' => 'Med udgangspunkt i det layout du har valgt til dit websted, kan du v&aelig;lge at nogle artikelelementer ikke skal benyttes.
		Benyt f&oslash;lgende liste til at bestemme, hvilke elementer der skal v&aelig;re til r&aring;dighed.',
'texte_crash_base' => 'Hvis din database er brudt ned, kan du her fors&oslash;ge en automatisk genopbygning.',
'texte_creer_rubrique' => 'F&oslash;r du kan skrive artikler,<br /> skal du oprette et afsnit.',
'texte_date_creation_article' => 'DATO FOR OPRETTELSE AF ARTIKLEN:',
'texte_date_publication_anterieure' => 'DATO FOR TIDLIGERE OFFENTLIGG&Oslash;RELSE',
'texte_date_publication_anterieure_nonaffichee' => 'Skjul dato for tidligere offentligg&oslash;relse.',
'texte_date_publication_article' => 'DATO FOR ONLINE OFFENTLIGG&Oslash;RELSE:',
'texte_descriptif_petition' => 'Beskrivelse af appellen',
'texte_descriptif_rapide' => 'Kort beskrivelse',
'texte_documents_joints' => 'Du kan tillade at dokumenter (kontorfiler, billeder,
	multimedia, osv.) f&oslash;jes til artikler og/eller afsnit. Der kan s&aring; henvises til filerne i dokumentet, eller de kan vises separat.<p>', # MODIF
'texte_documents_joints_2' => 'Denne konfiguration hindrer ikke inds&aelig;ttelse af billeder direkte i dokumenter.',
'texte_effacer_base' => 'Slet SPIP databasen',
'texte_effacer_donnees_indexation' => 'Slet indekseringsdata',
'texte_effacer_statistiques' => 'Effacer les statistiques', # NEW
'texte_en_cours_validation' => 'F&oslash;lgende artikler og nyheder er foresl&aring;et offentliggjort. T&oslash;v ikke med at give din mening til kende via de fora, som er knyttet til artiklerne.', # MODIF
'texte_en_cours_validation_forum' => 'N\'h&eacute;sitez pas &agrave; donner votre avis gr&acirc;ce aux forums qui leur sont attach&eacute;s.', # NEW
'texte_enrichir_mise_a_jour' => 'Du kan forbedre layoutet af teksten ved at benytte &laquo;typografiske koder&raquo;.',
'texte_fichier_authent' => '<b>Skal SPIP oprette specielle <tt>.htpasswd</tt>
		og <tt>.htpasswd-admin</tt> filer i kataloget @dossier@?</b><p>
		Disse filer kan benyttes til at begr&aelig;nse adgangen for forfattere og administratorer til andre dele af dit websted
		(f.eks. et eksternt statistikprogram).<p>
		Hvis du ikke har benyttet s&aring;danne filer f&oslash;r, kan du v&aelig;lge standardv&aelig;rdien (ingen filoprettelse).', # MODIF
'texte_informations_personnelles_1' => 'Systemet vil give dig en tilpasset adgang til webstedet.',
'texte_informations_personnelles_2' => '(Bem&aelig;rk: hvis det er en geninstallation og din adgang stadig fungerer, kan du',
'texte_introductif_article' => '(Introduktion til artiklen)',
'texte_jeu_caractere' => 'Denne indstilling er nyttig, hvis dit websted viser andre alfabeter end det latinske alfabet (dvs. det &laquo;vestlige&raquo;) og dets afledninger. 
 I s&aring; fald skal standardindstillingen &aelig;ndres til et passende tegns&aelig;t. Vi anbefaler dig at pr&oslash;ve med forskellige indstillinger for at finde den bedste l&oslash;sning. Husk ogs&aring; at tilpasse webstedet tilsvarende (<tt>#CHARSET</tt> parameteren).',
'texte_jeu_caractere_2' => 'Denne indstilling har ikke tilbagevirkende kraft.
	Tekst, der allerede er lagt ind, kan derfor blive vist forkert efter &aelig;ndring af denne indstilling.
	Du kan dog altid vende tilbage til den oprindelige indstilling.',
'texte_jeu_caractere_3' => 'Votre site est actuellement install&eacute; dans le jeu de caract&egrave;res&nbsp;:', # NEW
'texte_jeu_caractere_4' => 'Si cela ne correspond pas &agrave; la r&eacute;alit&eacute; de vos donn&eacute;es (suite, par exemple, &agrave; une restauration de base de donn&eacute;es), ou si <em>vous d&eacute;marrez ce site</em> et souhaitez partir sur un autre jeu de caract&egrave;res, veuillez indiquer ce dernier ici&nbsp;:', # NEW
'texte_jeu_caractere_conversion' => 'Note&nbsp;: vous pouvez d&eacute;cider de convertir une fois pour toutes l\'ensemble des textes de votre site (articles, br&egrave;ves, forums, etc.) vers l\'alphabet <tt>utf-8</tt>, en vous rendant sur <a href="@url@">la page de conversion vers l\'utf-8</a>.', # NEW
'texte_lien_hypertexte' => '(Hvis din meddelelse henviser til en artikel der er offentliggjort p&aring; webben, eller til en side der giver flere oplysninger, s&aring; indtast her artiklens overskrift og dens URL.)',
'texte_liens_sites_syndiques' => 'Links til syndikerede sider kan sp&aelig;rres p&aring; forh&aring;nd; f&oslash;lgende indstilling er standardindstillingen for syndikerede websteder, n&aring;r de er oprettet.
			Det er s&aring;ledes p&aring; trods heraf muligt at sp&aelig;rre hvert link individuelt eller at v&aelig;lge for hver websted at sp&aelig;rre de links der kommer fra en givet websted.',
'texte_login_ldap_1' => '(Efterlad tom for anonym adgang eller indtast en fuldst&aelig;ndig sti, f.eks. &laquo;<tt>uid=hansen, ou=brugere, dc=mit-dom&aelig;ne, dc=dk</tt>&raquo;.)',
'texte_login_precaution' => 'Advarsel! Dette er den login, du er koblet p&aring; med nu.
	Brug denne formular med forsigtighed ...',
'texte_message_edit' => 'Advarsel: Denne meddelelse kan &aelig;ndres af alle webstedets administratorer, og den er synlig for alle redakt&oslash;rer. Benyt kun annonceringer til at g&oslash;re opm&aelig;rksom p&aring; vigtige begivenheder p&aring; webstedet.',
'texte_messagerie_agenda' => 'Une messagerie permet aux r&#233;dacteurs du site de communiquer entre eux directement dans l&#8217;espace priv&#233; du site. Elle est associ&#233;e &#224; un agenda.', # NEW
'texte_messages_publics' => 'Offentlige bidrag til artiklen:',
'texte_mise_a_niveau_base_1' => 'Du har netop opdateret SPIP\'s filer.
	Du skal nu opdatere webstedets database.',
'texte_modifier_article' => 'Ret artiklen:',
'texte_moteur_recherche_active' => '<b>S&oslash;gefunktionen er valgt til.</b> Brug denne kommando, hvis du &oslash;nsker at udf&oslash;re en hurtig reindeksering
		(f.eks. efter at have indl&aelig;st en sikkerhedskopi). Bem&aelig;rk, at dokumenter der &aelig;ndres normalt (f.eks. fra SPIP\'s brugergr&aelig;nseflade)
		automatisk indekseres igen: derfor er denne kommando kun nyttig under ekstraordin&aelig;re omst&aelig;ndigheder.',
'texte_moteur_recherche_non_active' => 'S&oslash;gefunktionen er valgt fra.',
'texte_mots_cles' => 'N&oslash;gleord g&oslash;r det muligt for dig at oprette emnem&aelig;ssige forbindelser mellem artikler uafh&aelig;ngigt af, hvilket afsnit de tilh&oslash;rer.
		P&aring; denne m&aring;de kan du forbedre navigationen p&aring; dit websted 
		eller benytte disse egenskaber til at tilpasse artiklerne i dine skabeloner.',
'texte_mots_cles_dans_forum' => 'Vil du tillade brug af n&oslash;gleord, som brugerne kan v&aelig;lge, i de offentlige forummer p&aring; webstedet? (Advarsel: denne facilitet er temmelig vanskelig at bruge rigtigt.)',
'texte_multilinguisme' => 'Hvis du &oslash;nsker at administrere artikler p&aring; flere sprog med den deraf f&oslash;lgende st&oslash;rre kompleksitet, kan du forsyne afsnit og/eller artikler med en sprogvalgsmenu. Denne funktion er afh&aelig;ngig af strukturen p&aring; websiden.',
'texte_multilinguisme_trad' => 'Du kan ogs&aring; v&aelig;lge at have link mellem de forskellige sprogversioner af en artikel.',
'texte_non_compresse' => '<i>ukomprimeret</i> (din server underst&oslash;tter ikke denne funktion)',
'texte_non_fonction_referencement' => 'Du kan v&aelig;lge ikke at bruge denne automatiske funktion, og selv angive de elementer, der er vigtige for webstedet...',
'texte_nouveau_message' => 'Ny meddelelse',
'texte_nouveau_mot' => 'Nyt n&oslash;gleord',
'texte_nouvelle_version_spip_1' => 'Du har netop installeret en ny version af SPIP.',
'texte_nouvelle_version_spip_2' => 'Denne nye version kr&aelig;ver en mere omfattende opdatering end s&aelig;dvanligt. Hvis du er webmaster p&aring; webstedet, s&aring; slet venligst filen <tt>inc_connect.php3</tt> i kataloget <tt>ecrire</tt> og genstart installationen for at opdatere dine opkoblingsparametre til databasen. <p>(NB.: hvis du har glemt dine opkoblingsparametre, s&aring; kast et blik p&aring; indholdet af filen <tt>inc_connect.php3</tt> f&oslash;r du sletter den...)', # MODIF
'texte_operation_echec' => 'G&aring; tilbage til forrige side og v&aelig;lg en anden database eller opret en ny. Kontroller de oplysninger, dit webhotel har stillet til r&aring;dighed.',
'texte_plus_trois_car' => 'mere end 3 tegn',
'texte_plusieurs_articles' => 'Der er fundet flere forfattere til "@cherche_auteur@":',
'texte_port_annuaire' => '(Standardv&aelig;rdien passer for det meste.)',
'texte_presente_plugin' => 'Cette page liste les plugins disponibles sur le site. Vous pouvez activer les plugins n&eacute;cessaires en cochant la case correspondante.', # NEW
'texte_proposer_publication' => 'N&aring;r din artikel er f&aelig;rdig,<br /> kan du indsende den til offentligg&oslash;relse.',
'texte_proxy' => 'I nogle tilf&aelig;lde (intranet, beskyttede netv&aelig;rk...),
		er det n&oslash;dvendigt at benytte en <i>proxy HTTP</i> for at komme i kontakt med syndikerede websteder.
		Hvis der skal benyttes proxy, s&aring; indtast dens adresse her: 
		<tt><html>http://proxy:8080</html></tt>. Almindeligvis skal feltet st&aring; tomt.',
'texte_publication_articles_post_dates' => 'Hvad skal SPIP g&oslash;re med hensyn til artikler med en offentligg&oslash;relsesdato, der ligger ude i 
		fremtiden?',
'texte_rappel_selection_champs' => '[Husk at v&aelig;lge dette felt korrekt.]',
'texte_recalcul_page' => 'Hvis du kun &oslash;nsker at opdatere en side, b&oslash;r du g&oslash;re det ved fra det offentlige omr&aring;de at benytte knappen &laquo; Opdater &raquo;.',
'texte_recapitiule_liste_documents' => 'Denne side er en oversigt over de dokumenter, du har anbragt i afsnittene. For at &aelig;ndre oplysningerne om et dokument, skal du f&oslash;lge linket til dets afsnitsside.',
'texte_recuperer_base' => 'Reparer databasen',
'texte_reference_mais_redirige' => 'artikler der refereres til p&aring; dit SPIP websted, men som viderestiller til en anden URL.',
'texte_referencement_automatique' => '<b>Automatiserede webstedshenvisninger</b><br />
		Du kan hurtigt henvise til et websted ved nedenfor at angive dens URL eller adressen p&aring; dens datakilde. 
		SPIP vil automatisk indhente oplysninger om webstedet (titel, beskrivelse...).',
'texte_referencement_automatique_verifier' => 'Veuillez v&eacute;rifier les informations fournies par <tt>@url@</tt> avant d\'enregistrer.', # NEW
'texte_requetes_echouent' => '<b>N&aring;r nogle SQL foresp&oslash;rgsler systematisk og uden tilsyneladende grund g&aring;r galt, er det muligt at fejlen ligger i selve databasen.</b>
		<p>SQL har en funktion, der reparerer dens tabeller, hvis de er blevet &oslash;delagt ved et uheld. 
		Her kan du fors&oslash;ge at igangs&aelig;tte denne reparationsfunktion; 
		hvis den g&aring;r galt, b&oslash;r du beholde en kopi af sk&aelig;rmbilledet, 
		som m&aring;ske kan indeholde antydninger af, hvad der er galt....
		<p>Hvis problemet fortsat best&aring;r, s&aring; kontakt dit webhotel.', # MODIF
'texte_restaurer_base' => 'Genindl&aelig;s indholdet af sikkerhedskopien af databasen',
'texte_restaurer_sauvegarde' => 'Denne valgmulighed giver dig adgang til at genindl&aelig;se en tidligere 
		sikkerhedskopi af databasen. For at g&oslash;re det, skal filen, der indeholder sikkerhedskopien af databasen, 
		p&aring; forh&aring;nd kopieres til kataloget @dossier@.
		V&aelig;r forsigtig med denne funktion: <b>Alle eventuelle &aelig;ndringer og tab er uoprettelige.</b>',
'texte_sauvegarde' => 'Sikkerhedskopier indholdet af databasen',
'texte_sauvegarde_base' => 'Sikkerhedskopier databasen',
'texte_sauvegarde_compressee' => 'Sikkerhedskopien vil blive lagt i den ukomprimerede fil @fichier@.',
'texte_selection_langue_principale' => 'Du kan nedenfor v&aelig;lge webstedets &laquo;hovedsprog&raquo;. 
		Heldigvis begr&aelig;nser dette valg ikke dine artikler til at skulle skrives p&aring; det valgte sprog 
		men g&oslash;r det muligt at fasts&aelig;tte, 
		<ul><li> standardformatet for datoer i det offentlige omr&aring;de</li>

		<li> hvilken typografisk funktion SPIP skal benytte til tekstformatering;</li>

		<li> det sprog der anvendes i formularer p&aring; det offentlige websted</li>

		<li> standardsproget i det private omr&aring;de.</li></ul>',
'texte_signification' => 'R&oslash;de bj&aelig;lker viser summer (totaler for underafsnit), 
		gr&oslash;nne bj&aelig;lker viser antal bes&oslash;g i hvert afsnit.',
'texte_sous_titre' => 'Underrubrik',
'texte_statistiques_visites' => '(m&oslash;rke bj&aelig;lker:  S&oslash;ndag / m&oslash;rk kurve: gennemsnitsudvikling)',
'texte_statut_attente_validation' => 'afventer godkendelse',
'texte_statut_publies' => 'offentliggjort online',
'texte_statut_refuses' => 'afvist',
'texte_suppression_fichiers' => 'Brug denne kommando til at slette alle filer i SPIP\'s cache.
		Dette giver dig bl.a. mulighed for at gennemtvinge opdatering af alle sider i tilf&aelig;lde af 
		at du har lavet v&aelig;sentlige grafiske eller strukturelle &aelig;ndringer p&aring; webstedet.',
'texte_sur_titre' => 'Hovedoverskrift',
'texte_syndication' => 'Hvis webstedet tillader det, er det muligt automatisk at hente en oversigt over det
		seneste materiale. For at g&oslash;re dette, skal du igangs&aelig;tte syndikering.
		<blockquote><i>Nogle webhoteller tillader ikke denne funktion.
		I s&aring; fald kan du ikke foretage indholdssyndikering fra dit websted.</i></blockquote>', # MODIF
'texte_table_ok' => ': denne tabel er OK.',
'texte_tables_indexation_vides' => 'S&oslash;gefunktionens indekstabeller er tomme.',
'texte_tentative_recuperation' => 'Reparationsfors&oslash;g',
'texte_tenter_reparation' => 'Fors&oslash;g p&aring; at reparere databasen',
'texte_test_proxy' => 'For at afpr&oslash;ve proxy\'en, kan du indtaste adressen p&aring; et websted som du &oslash;nsker at teste.',
'texte_titre_02' => 'Emne:',
'texte_titre_obligatoire' => '<b>Overskrift</b> [Obligatorisk]',
'texte_travail_article' => '@nom_auteur_modif@ har arbejdet p&aring; denne artikel for @date_diff@ minutter siden',
'texte_travail_collaboratif' => 'Hvis det sker hyppigt at flere redakt&oslash;rer arbejder p&aring; samme artikel, kan systemet
		vise &laquo;&aring;bne&raquo; artikler for at undg&aring; samtidige &aelig;ndringer. Denne indstilling er som standard
		sl&aring;et fra for at undg&aring; utidige advarselsmeddelelser.',
'texte_trop_resultats_auteurs' => 'For mange resultater til "@cherche_auteur@"; v&aelig;r venlig at afgr&aelig;nse s&oslash;gningen yderligere.',
'texte_type_urls' => 'Vous pouvez choisir ci-dessous le mode de calcul de l\'adresse des pages.', # NEW
'texte_type_urls_attention' => 'Attention ce r&eacute;glage ne fonctionnera que si le fichier @htaccess@ est correctement install&eacute; &agrave; la racine du site.', # NEW
'texte_unpack' => 'download af seneste version',
'texte_utilisation_moteur_syndiques' => 'Hvis du benytter SPIP\'s indbyggede s&oslash;gefunktion, kan du p&aring; forskellig vis foretage
		s&oslash;gninger p&aring; websteder og i syndikerede artikler. <br /><img src=\'puce.gif\'> Den enkleste best&aring;r i kun at 
		s&oslash;ge i artiklernes overskrifter og beskrivelser. <br /><img src=\'puce.gif\'> 
		En anden metode, som er st&aelig;rkere, lader SPIP s&oslash;ge i teksten p&aring; de websteder, der henvises til. 
		Hvis du henviser til et websted, vil SPIP selv s&oslash;ge i dens tekst.', # MODIF
'texte_utilisation_moteur_syndiques_2' => 'Denne metode tvinger SPIP til j&aelig;vnligt at bes&oslash;ge webstedet, hvilket kan koste 
		en forringelse af svartiderne p&aring; din eget websted.',
'texte_vide' => 'tom',
'texte_vider_cache' => 'T&oslash;m cachen',
'titre_admin_effacer' => 'Teknisk vedligeholdelse',
'titre_admin_tech' => 'Teknisk vedligeholdelse',
'titre_admin_vider' => 'Teknisk vedligeholdelse',
'titre_articles_syndiques' => 'Syndikerede artikler hentet p&aring; dette websted',
'titre_breves' => 'Nyheder',
'titre_cadre_afficher_article' => 'Vis artikler som er',
'titre_cadre_afficher_traductions' => 'Vis overs&aelig;ttelsesstatus for f&oslash;lgende sprog:',
'titre_cadre_ajouter_auteur' => 'TILF&Oslash;J FORFATTER:',
'titre_cadre_forum_administrateur' => 'Administratorers private forum',
'titre_cadre_forum_interne' => 'Internt forum',
'titre_cadre_interieur_rubrique' => 'I afsnit',
'titre_cadre_numero_auteur' => 'FORFATTER NUMMER',
'titre_cadre_signature_obligatoire' => '<b>Underskrift</b> [Obligatorisk]<br />',
'titre_compacter_script_css' => 'Compactage des scripts et CSS', # NEW
'titre_compresser_flux_http' => 'Compression du flux HTTP', # NEW
'titre_config_contenu_notifications' => 'Notifications', # NEW
'titre_config_contenu_prive' => 'Dans l&#8217;espace priv&#233;', # NEW
'titre_config_contenu_public' => 'Sur le site public', # NEW
'titre_config_fonctions' => 'Konfigurering af webstedet',
'titre_config_forums_prive' => 'Forums de l&#8217;espace priv&#233;', # NEW
'titre_config_groupe_mots_cles' => 'Konfiguration af n&oslash;gleordsgrupper',
'titre_configuration' => 'Konfigurering af webstedet',
'titre_conflit_edition' => 'Conflit lors de l\'&#233;dition', # NEW
'titre_connexion_ldap' => 'Indstillinger: <b>Din LDAP forbindelse</b>',
'titre_dernier_article_syndique' => 'Senest syndikerede artikler',
'titre_documents_joints' => 'Vedh&aelig;ftede dokumenter',
'titre_evolution_visite' => 'Udvikling i bes&oslash;gstal',
'titre_forum_suivi' => 'Opf&oslash;lgning p&aring; forummer',
'titre_gauche_mots_edit' => 'N&Oslash;GLEORDSNUMMER:',
'titre_groupe_mots' => 'N&Oslash;GLEORDSGRUPPE:',
'titre_langue_article' => 'ARTIKLENS SPROG',
'titre_langue_breve' => 'NYHEDENS SPROG',
'titre_langue_rubrique' => 'SPROGAFSNIT',
'titre_langue_trad_article' => 'ARTIKLENS SPROG OG OVERS&AElig;TTELSER',
'titre_les_articles' => 'ARTIKLER',
'titre_messagerie_agenda' => 'Messagerie et agenda', # NEW
'titre_mots_cles_dans_forum' => 'N&oslash;gleord i forummer p&aring; det offentlige websted',
'titre_mots_tous' => 'N&oslash;gleord',
'titre_naviguer_dans_le_site' => 'Gennemse webstedet...',
'titre_nouveau_groupe' => 'Nyhedsgruppe',
'titre_nouvelle_breve' => 'Ny nyhed',
'titre_nouvelle_rubrique' => 'Nyt afsnit',
'titre_numero_rubrique' => 'AFSNITSNUMMER:',
'titre_page_admin_effacer' => 'Teknisk vedligeholdelse: sletning af database',
'titre_page_articles_edit' => 'Ret: @titre@',
'titre_page_articles_page' => 'Artikler',
'titre_page_articles_tous' => 'Hele webstedet',
'titre_page_auteurs' => 'Bes&oslash;gende',
'titre_page_breves' => 'Nyheder',
'titre_page_breves_edit' => 'Ret nyhed: &laquo;@titre@&raquo;',
'titre_page_calendrier' => 'Kalender @nom_mois@ @annee@',
'titre_page_config_contenu' => 'Webstedskonfigurering',
'titre_page_config_fonctions' => 'Webstedkonfigurering',
'titre_page_configuration' => 'Konfiguration af websted',
'titre_page_controle_petition' => 'Opf&oslash;lgning p&aring; appel',
'titre_page_delete_all' => 'total og uigenkaldelig sletning',
'titre_page_documents_liste' => 'Dokumenter i afsnit',
'titre_page_forum' => 'Administratorforum',
'titre_page_forum_envoi' => 'Send meddelelse',
'titre_page_forum_suivi' => 'Opf&oslash;lgning p&aring; forummer',
'titre_page_index' => 'Dit private omr&aring;de',
'titre_page_message_edit' => 'Skriv meddelelse',
'titre_page_messagerie' => 'Din meddelelsesfunktion',
'titre_page_mots_tous' => 'N&oslash;gleord',
'titre_page_recherche' => 'S&oslash;geresultater @recherche@',
'titre_page_sites_tous' => 'Links til websteder',
'titre_page_statistiques' => 'Statistik pr. afsnit',
'titre_page_statistiques_messages_forum' => 'Messages de forum', # NEW
'titre_page_statistiques_referers' => 'Statistik (indkommende links)',
'titre_page_statistiques_signatures_jour' => 'Nombre de signatures par jour', # NEW
'titre_page_statistiques_signatures_mois' => 'Nombre de signatures par mois', # NEW
'titre_page_statistiques_visites' => 'Bes&oslash;gsstatistik',
'titre_page_upgrade' => 'SPIP opgradering',
'titre_publication_articles_post_dates' => 'Offentligg&oslash;relse af fremdaterede artikler',
'titre_referencement_sites' => 'Henvisning og syndikering til websteder',
'titre_referencer_site' => 'Henvis til webstedet:',
'titre_rendez_vous' => 'AFTALER:',
'titre_reparation' => 'Reparer',
'titre_site_numero' => 'WEBSTEDSNUMMER:',
'titre_sites_proposes' => 'Indsendte websteder',
'titre_sites_references_rubrique' => 'Websteder der henvises til i dette afsnit',
'titre_sites_syndiques' => 'Syndikerede websteder',
'titre_sites_tous' => 'Websteder der henvises til',
'titre_suivi_petition' => 'Opf&oslash;lgning p&aring; appeller',
'titre_syndication' => 'Webstedssyndikering',
'titre_type_urls' => 'Type d\'adresses URL', # NEW
'tls_ldap' => 'Transport Layer Security :', # NEW
'tout_dossier_upload' => 'Tout le dossier @upload@', # NEW
'trad_article_inexistant' => 'Der findes ingen artikel med dette nummer.',
'trad_article_traduction' => 'Alle udgaver af denne artikel&nbsp;:',
'trad_deja_traduit' => 'Denne artikel er allerede en overs&aelig;ttelse af den aktuelle artikel.',
'trad_delier' => 'Afbryd forbindelsen mellem denne artikel og overs&aelig;ttelserne',
'trad_lier' => 'Denne artikel er en overs&aelig;ttelse af artikel nummer&nbsp;:',
'trad_new' => 'Lav en ny overs&aelig;ttelse af denne artikel',

// U
'upload_fichier_zip' => 'Fichier ZIP', # NEW
'upload_fichier_zip_texte' => 'Le fichier que vous proposez d\'installer est un fichier Zip.', # NEW
'upload_fichier_zip_texte2' => 'Ce fichier peut &ecirc;tre&nbsp;:', # NEW
'upload_info_mode_document' => 'D&#233;poser cette image dans le portfolio', # NEW
'upload_info_mode_image' => 'Retirer cette image du portfolio', # NEW
'upload_limit' => 'Ce fichier est trop gros pour le serveur&nbsp;; la taille maximum autoris&eacute;e en <i>upload</i> est de @max@.', # NEW
'upload_zip_conserver' => 'Conserver l&#8217;archive apr&#232;s extraction', # NEW
'upload_zip_decompacter' => 'd&eacute;compress&eacute; et chaque &eacute;l&eacute;ment qu\'il contient install&eacute; sur le site. Les fichiers qui seront alors install&eacute;s sur le site sont&nbsp;:', # NEW
'upload_zip_telquel' => 'install&eacute; tel quel, en tant qu\'archive compress&eacute;e Zip&nbsp;;', # NEW
'upload_zip_titrer' => 'Titrer selon le nom des fichiers', # NEW
'utf8_convert_attendez' => 'Attendez quelques instants et rechargez cette page.', # NEW
'utf8_convert_avertissement' => 'Vous vous appr&ecirc;tez &agrave; convertir le contenu de votre base de donn&eacute;es (articles, br&egrave;ves, etc) du jeu de caract&egrave;res <b>@orig@</b> vers le jeu de caract&egrave;res <b>@charset@</b>.', # NEW
'utf8_convert_backup' => 'N\'oubliez pas de faire auparavant une sauvegarde compl&egrave;te de votre site. Vous devrez aussi v&eacute;rifier que vos squelettes et fichiers de langue sont compatibles @charset@.', # NEW
'utf8_convert_erreur_deja' => 'Votre site est d&eacute;j&agrave; en @charset@, inutile de le convertir...', # NEW
'utf8_convert_erreur_orig' => 'Erreur&nbsp;: le jeu de caract&egrave;res @charset@ n\'est pas support&eacute;.', # NEW
'utf8_convert_termine' => 'C\'est termin&eacute;&nbsp;!', # NEW
'utf8_convert_timeout' => '<b>Important&nbsp;:</b> en cas de <i>timeout</i> du serveur, veuillez recharger la page jusqu\'&agrave; ce qu\'elle indique &laquo;&nbsp;termin&eacute;&nbsp;&raquo;.', # NEW
'utf8_convert_verifier' => 'Vous devez maintenant aller vider le cache, et v&eacute;rifier que tout se passe bien sur les pages publiques du site. En cas de gros probl&egrave;me, une sauvegarde de vos donn&eacute;es a &eacute;t&eacute; r&eacute;alis&eacute;e (au format SQL) dans le r&eacute;pertoire @rep@.', # NEW
'utf8_convertir_votre_site' => 'Convertir votre site en utf-8', # NEW

// V
'version' => 'Version&nbsp;:', # NEW
'version_deplace_rubrique' => 'D&#233;plac&#233; de <b>&#171;&nbsp;@from@&nbsp;&#187;</b> vers <b>&#171;&nbsp;@to@&nbsp;&#187;</b>.', # NEW
'version_initiale' => 'Initial version', # NE
);

?>
