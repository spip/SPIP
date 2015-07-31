<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://www.spip.net/trad-lang/
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

// A
'activer_plugin' => 'Aktivera insticksmodulen',
'affichage' => 'Visa',
'aide_non_disponible' => 'Den här delen av direkthjälpen finns inte i det aktuella språket',
'auteur' => 'Redaktör',
'avis_acces_interdit' => 'Tilltr&amp;auml;de f&amp;ouml;rbjudet.',
'avis_article_modifie' => 'Varning, @nom_auteur_modif@ har arbetat på den här artikeln för @date_diff@ minuter sen',
'avis_aucun_resultat' => 'Hittade inga resultat',
'avis_chemin_invalide_1' => 'Sökvägen du har angett',
'avis_chemin_invalide_2' => 'är ogiltig. Återgå till den föregående sidan och kontrollera den angivna informationen',
'avis_connexion_echec_1' => 'Anslutningen till SQL-servern misslyckades.',
'avis_connexion_echec_2' => 'Återgå till den föregående sidan och kontrollera informationen du angivit.',
'avis_connexion_echec_3' => '<b>OBS</b> På många servrar måste du  <b>begära</b> aktivering av din databas innan du kan använda den. Om du inte lyckas med uppkopplingen, se till att den är aktiverad.',
'avis_connexion_ldap_echec_1' => 'Anslutning till LDAP-server misslyckades.',
'avis_connexion_ldap_echec_2' => 'Återgå till den föregående sidan och kontrollera informationen du angivit.',
'avis_connexion_ldap_echec_3' => 'Alternativt kan du välja att inte använda LDAP stöd för att importera användare.',
'avis_conseil_selection_mot_cle' => '<b>Viktig grupp:</b> Det är verkligen rekomenderat att välja ett nyckelord ur den här gruppen.',
'avis_deplacement_rubrique' => 'Varning! Den här avdelningen innehåller @contient_breves@ notiser@scb@: om du flyttar den, markera i kryssrutan för att bekräfta.',
'avis_destinataire_obligatoire' => 'Du måste ange en mottagare innan du skickar meddelandet.',
'avis_doublon_mot_cle' => 'Un mot existe deja avec ce titre. Êtes vous sûr de vouloir créer le même ?', # NEW
'avis_erreur_connexion_mysql' => 'Fel på SQL-förbindelsen',
'avis_erreur_version_archive' => '<b>Varning! Filen @archive@ motsvarar en
 annan SPIP-version än den du instellerad</b>
 Du står inför stora svårigheter: risken att
förstöra din databas, tekniska fel för din
 webbplats, etc. Skicka inte den här begäran
om import.<p>För mer information se <a href="@spipnet@">SPIP-dokumentationen</a> (På engelska).', # MODIF
'avis_espace_interdit' => '<b>Förbjudet område</b><p>SPIP är redan installerat.',
'avis_lecture_noms_bases_1' => 'Installerings-scriptet kunde inte läsa namnen på de installerade databaserna.',
'avis_lecture_noms_bases_2' => 'Antingen finns det ingen tillgänglig databas eller så är funktionen som listar
 databaser inaktiverad av säkerhetsskäl (det är fallet hos många webbvärdar).',
'avis_lecture_noms_bases_3' => 'I fråga om det andra alternativet är det möjligt att en databas med ditt användarnamn kan användas:',
'avis_non_acces_message' => 'Du har inte tillgång till det här meddelandet.',
'avis_non_acces_page' => 'Du har inte tillgång till den här sidan.',
'avis_operation_echec' => 'Operationen misslyckades.',
'avis_operation_impossible' => 'Operationen omöjlig att genomföra',
'avis_probleme_archive' => 'Läsfel i filen @archive@',
'avis_site_introuvable' => 'Webbplatsen hittades inte',
'avis_site_syndique_probleme' => 'OBS! Syndikeringen av den här sajten har stött på ett problem ; Därför är funktionen tillfälligt avbruten. Var vänlig och verifiera adressen till sajtens syndikeringsfil (<b>@url_syndic@</b>), och försök att göra en ny hämtning av information.',
'avis_sites_probleme_syndication' => 'Dessa sajter har ett syndikeringsproblem',
'avis_sites_syndiques_probleme' => 'Det har uppstått ett problem med syndikeringen av sajterna',
'avis_suppression_base' => 'OBS! Radering av data är permanent och kan inte göras ogjord.',
'avis_version_mysql' => 'Din version av MySql (@version_mysql@) stöder inte automatisk reparation av databas-tabeller.',

// B
'bouton_acces_ldap' => 'Lägg till en LDAP-katalog >>',
'bouton_ajouter' => 'Lägg till',
'bouton_ajouter_participant' => 'LÄGG TILL EN DELTAGARE',
'bouton_annonce' => 'MEDDELANDE',
'bouton_annuler' => 'Avbryt',
'bouton_checkbox_envoi_message' => 'möjlighet att skicka ett meddelande',
'bouton_checkbox_indiquer_site' => 'Du måste ange ett namn på en webbplats',
'bouton_checkbox_qui_attribue_mot_cle_administrateurs' => 'Webbplats administratörer',
'bouton_checkbox_qui_attribue_mot_cle_redacteurs' => 'redaktörer',
'bouton_checkbox_qui_attribue_mot_cle_visiteurs' => 'besökare av den offentliga webbplatsen när de skriver ett meddelande i ett forum.',
'bouton_checkbox_signature_unique_email' => 'endast en signatur per e-postadress',
'bouton_checkbox_signature_unique_site' => 'endast en signatur per webbplats',
'bouton_demande_publication' => 'Begär att den här artikeln ska publiceras',
'bouton_desactive_tout' => 'Avaktivera alla',
'bouton_desinstaller' => 'Avinstallera',
'bouton_effacer_index' => 'Radera index',
'bouton_effacer_statistiques' => 'Radera statistiken',
'bouton_effacer_tout' => 'Radera ALLA',
'bouton_envoi_message_02' => 'SKICKA ETT MEDDELANDE',
'bouton_envoyer_message' => 'Färdigt meddelande: skicka',
'bouton_fermer' => 'Fermer', # NEW
'bouton_forum_petition' => 'FORUM &amp; NAMNINSAMLINGAR',
'bouton_mettre_a_jour_base' => 'Mettre à jour la base de données', # NEW
'bouton_modifier' => 'Ändra',
'bouton_pense_bete' => 'PERSONLIG MINNESANTECKNING',
'bouton_radio_activer_messagerie' => 'Möjliggör interna meddlanden',
'bouton_radio_activer_messagerie_interne' => 'möjliggör interna meddelanden',
'bouton_radio_activer_petition' => 'Aktivera namninsamlingen',
'bouton_radio_afficher' => 'Visa',
'bouton_radio_apparaitre_liste_redacteurs_connectes' => 'Synas i listan över anslutna redaktörere',
'bouton_radio_articles_futurs' => 'endast för framtida artiklar (ingen inverkan på databasen).',
'bouton_radio_articles_tous' => 'på alla artiklar utan undantag.',
'bouton_radio_articles_tous_sauf_forum_desactive' => 'på alla artiklar utom de utan forum.',
'bouton_radio_desactiver_messagerie' => 'Stäng av meddelandefunktionen',
'bouton_radio_enregistrement_obligatoire' => 'Krav på registrering (användare måste registrera sig genom att tillhandahålla sin e-postadress innan de kan bidra).  being able to post contributions).',
'bouton_radio_envoi_annonces_adresse' => 'Skicka meddelanden till adressen:',
'bouton_radio_envoi_liste_nouveautes' => 'Skicka lista över senaste notiser',
'bouton_radio_moderation_priori' => 'Moderering i förhand (bidrag visas endast efter att de godkänts av en administatör).',
'bouton_radio_modere_abonnement' => 'registrering krävs',
'bouton_radio_modere_posteriori' => 'moderering i efterhand',
'bouton_radio_modere_priori' => 'moderering i förhand',
'bouton_radio_non_apparaitre_liste_redacteurs_connectes' => 'Inte synas i listan över anslutna redaktörere',
'bouton_radio_non_envoi_annonces_editoriales' => 'Skicka inga redaktionella meddelanden',
'bouton_radio_non_syndication' => 'Ingen syndikering',
'bouton_radio_pas_petition' => 'Inga namninsamlingar',
'bouton_radio_petition_activee' => 'Namninsamling aktiverad',
'bouton_radio_publication_immediate' => 'Omedelbar publicering av meddelanden (bidrag visas direkt efter att de skickas, administratörer kan radera dom senare):',
'bouton_radio_sauvegarde_compressee' => 'spara komprimerat i @fichier@',
'bouton_radio_sauvegarde_non_compressee' => 'spara utan att komprimera i @fichier@',
'bouton_radio_supprimer_petition' => 'Radera namninsamlingen',
'bouton_radio_syndication' => 'Syndikering:',
'bouton_redirection' => 'OMPEKA',
'bouton_relancer_installation' => 'Kör installationen igen',
'bouton_restaurer_base' => 'Återställ databasen',
'bouton_suivant' => 'Nästa',
'bouton_tenter_recuperation' => 'Försök till reparation',
'bouton_test_proxy' => 'Testa proxyn',
'bouton_vider_cache' => 'Töm cachen',
'bouton_voir_message' => 'Förhandsgranska meddelandet innan det godkänns',

// C
'cache_mode_compresse' => 'Cachefilerna sparas komprimerade.',
'cache_mode_non_compresse' => 'Cachefilerna sparas utan komprimering.',
'cache_modifiable_webmestre' => 'Den här parametern kan ändras av den webbansvariga.',
'calendrier_synchro' => 'Om du använder ett kalenderprogram som är kompatibelt med <b>iCal</b> kan du synkronisera det med informationen på den här webbplatsen.',
'config_activer_champs' => 'Aktivera följande fält',
'config_choix_base_sup' => 'Ge namnet på en databas på servern',
'config_erreur_base_sup' => 'SPIP har inte tillgång till de existerande databaserna',
'config_info_base_sup' => 'Si vous avez d\'autres bases de données à interroger à travers SPIP, avec son serveur SQL ou avec un autre, le formulaire ci-dessous, vous permet de les déclarer. Si vous laissez certains champs vides, les identifiants de connexion à la base principale seront utilisés.', # NEW
'config_info_base_sup_disponibles' => 'Ytterligare databaser dit databasfrågor kan skickas:',
'config_info_enregistree' => 'La nouvelle configuration a été enregistrée', # NEW
'config_info_logos' => 'Alla objekt på sajten kan kan ha sin egen logotype och dessutom en "mouseover" logotype',
'config_info_logos_utiliser' => 'Använd logotyper',
'config_info_logos_utiliser_non' => 'Använd inte logotyper',
'config_info_logos_utiliser_survol' => 'Använd "mouseover" logotyper',
'config_info_logos_utiliser_survol_non' => 'Använd inte "mouseover" logotyper',
'config_info_redirection' => 'Genom att aktivera det här valet kan du skapa virtuella artiklar, som enbart är länkar till artiklar som publicerats på andra sajter oavsett om det är SPIP-sajter eller ej.',
'config_redirection' => 'Virtuella artiklar',
'config_titre_base_sup' => 'Konfigurera ytterligare en databas',
'config_titre_base_sup_choix' => 'Välj ytterligare en databas',
'connexion_ldap' => 'ldapuppkoppling:',
'copier_en_local' => 'Kopiera till den lokala sajten',

// D
'date_mot_heures' => 'h',
'diff_para_ajoute' => 'Tillaggt stycke',
'diff_para_deplace' => 'Flyttat stycke',
'diff_para_supprime' => 'Raderat stycke',
'diff_texte_ajoute' => 'Tillaggd text',
'diff_texte_deplace' => 'Flyttad text',
'diff_texte_supprime' => 'Raderad text',
'double_clic_inserer_doc' => 'Dubbelklicka för att sätta in den här genvägen i texten',

// E
'email' => 'e-post',
'email_2' => 'e-post:',
'en_savoir_plus' => 'En savoir plus', # NEW
'entree_adresse_annuaire' => 'Katalogens adress',
'entree_adresse_email' => 'Din e-postadress',
'entree_adresse_fichier_syndication' => 'Adress till syndikeringsfil:',
'entree_adresse_site' => '<b>Webbplats URL</b> [Krävs]',
'entree_base_donnee_1' => 'Adress till databasen',
'entree_base_donnee_2' => '(Ofta är det samma adress som till din webbplats, ibland är det "localhost" och ibland lämnas det helt tomt.)',
'entree_biographie' => 'Kort biografi med några få ord.',
'entree_breve_publiee' => 'Ska den här notisen publiceras?',
'entree_chemin_acces' => '<b>Fyll i</b> sökvägen:',
'entree_cle_pgp' => 'Din PGP-nyckel',
'entree_contenu_rubrique' => '(Några få ord som beskriver innehållet i avdelningen)',
'entree_description_site' => 'Beskrivning av webbplats',
'entree_identifiants_connexion' => 'Dina anslutningsuppgifter',
'entree_informations_connexion_ldap' => 'Fyll i det här formuläret med uppgifter om din anslutning till LDAP. Din system eller nätverks administratör kan ge dig dessa.',
'entree_infos_perso' => 'Vem är du?',
'entree_interieur_rubrique' => 'I avdelning:',
'entree_liens_sites' => '<b>Hyperlänk</b> (referens, sajt att besöka...)',
'entree_login' => 'Dina användaruppgifter',
'entree_login_connexion_1' => 'Användarupgifter för anslutningen',
'entree_login_connexion_2' => '(Är ibland samma som lösenordet för FTP-åtkomst och ibland kan det lämnas tomt)',
'entree_login_ldap' => 'Login LDAP initial', # NEW
'entree_mot_passe' => 'Ditt lösenord',
'entree_mot_passe_1' => 'Lösenord för anslutningen',
'entree_mot_passe_2' => '(Är ibland samma som lösenordet för FTP-åtkomst och ibland kan det lämnas tomt)',
'entree_nom_fichier' => 'Skriv in filnamnet @texte_compresse@',
'entree_nom_pseudo' => 'Ditt namn eller alias',
'entree_nom_pseudo_1' => '(Ditt namn eller alias)',
'entree_nom_site' => 'Din webbplats namn',
'entree_nouveau_passe' => 'Nytt lösenord',
'entree_passe_ldap' => 'Lösenord',
'entree_port_annuaire' => 'Katalogens portnummer',
'entree_signature' => 'Signatur',
'entree_texte_breve' => 'Notisens text',
'entree_titre_obligatoire' => '<b>Title</b> [Krävs]<br />',
'entree_url' => 'Din webbplats URL',
'erreur_plugin_desinstalation_echouee' => 'La désinstallation du plugin a echoué. Vous pouvez néanmoins le desactiver.', # NEW
'erreur_plugin_fichier_absent' => 'Filen saknas',
'erreur_plugin_fichier_def_absent' => 'Definitionsfilen saknas',
'erreur_plugin_nom_fonction_interdit' => 'Förbjudet namn för funktionen',
'erreur_plugin_nom_manquant' => 'plugin-namnet saknas',
'erreur_plugin_prefix_manquant' => 'Pluginens prefix odefinierad',
'erreur_plugin_tag_plugin_absent' => '<plugin> saknas i definitionsfilen',
'erreur_plugin_version_manquant' => 'Denna plugin saknar version.',

// F
'forum_info_original' => 'original',

// H
'htaccess_a_simuler' => 'Avertissement: la configuration de votre serveur HTTP ne tient pas compte des fichiers @htaccess@. Pour pouvoir assurer une bonne sécurité, il faut que vous modifiiez cette configuration sur ce point, ou bien que les constantes @constantes@ (définissables dans le fichier mes_options.php) aient comme valeur des répertoires en dehors de @document_root@.', # NEW
'htaccess_inoperant' => 'htaccess inopérant', # NEW

// I
'ical_info1' => 'Denna sida visar flera metoder att hålla dig informerad om aktiviteter på sajten.',
'ical_info2' => 'För mer information, tveka inte om att besöka <a href="@spipnet@">SPIP\'s dokumentation</a>.', # MODIF
'ical_info_calendrier' => 'Du kan använda två kalendrar. En är en sajtkarta med alla publicerade artiklar. Den andra innehåller redaktionella meddelanden och dina senaste privata meddelanden: Den är personlig, tack vare en genererad nyckel som du kan förändra när som helst genom att byta lösenord.',
'ical_lien_rss_breves' => 'Syndikering av sajtens nyheter',
'ical_methode_http' => 'Nerladdning',
'ical_methode_webcal' => 'Synkronisering (webcal://)',
'ical_texte_js' => 'En rad javascript tillåter dig att, väldigt enkelt och på vilken sajt du vill, visa en lista på de senaste artiklarna publicerade på den här sajten.',
'ical_texte_prive' => 'Den här kalendern som är personlig, innnehåller dina egna redaktionella händelser på den här sajten (uppgifter, egna möten, inlämnade artiklar och nyheter...).',
'ical_texte_public' => 'Den här kalendern låter dig följa den publika aktiviteten på sajten (publicerade artiklar och nyheter).',
'ical_texte_rss' => 'Du kan syndikerar de senaste nyheterna på den är sajten i vilken XML/RSS-läsare som helst. Det är samma format som gör det möjligt att läsa de senaste nyheterna på andra sajter som använder ett kompatibelt format. (syndikerade sajter).',
'ical_titre_js' => 'Javascript',
'ical_titre_mailing' => 'E-postlista',
'ical_titre_rss' => 'Syndikeringsfiler',
'icone_accueil' => 'Accueil', # NEW
'icone_activer_cookie' => 'Sätt en cookie',
'icone_activite' => 'Activité', # NEW
'icone_admin_plugin' => 'Hantera plugin\'s',
'icone_administration' => 'Maintenance', # NEW
'icone_afficher_auteurs' => 'Visa redaktörer',
'icone_afficher_visiteurs' => 'Visa besökare',
'icone_arret_discussion' => 'Avsluta diskussionen',
'icone_calendrier' => 'Kalender',
'icone_configuration' => 'Configuration', # NEW
'icone_creation_groupe_mots' => 'Skapa en ny nyckleordsgrupp',
'icone_creation_mots_cles' => 'Skapa ett nytt nyckelord',
'icone_creer_auteur' => 'Skapa en ny redaktör och koppla honom (henne) till den här artikeln',
'icone_creer_mot_cle' => 'Skapa ett nytt nyckelord och länka det till artikeln',
'icone_creer_mot_cle_breve' => 'Skapa ett nytt nyckelord och koppla det till den här nyheten',
'icone_creer_mot_cle_rubrique' => 'Skapa ett nytt nyckelord och koppla det till den här avdelningen',
'icone_creer_mot_cle_site' => 'Skapa ett nytt nyckelord och koppla det till den här sajten',
'icone_creer_rubrique_2' => 'Skapa en ny avdelning',
'icone_ecrire_nouvel_article' => 'Nyheter i den här sektionen',
'icone_edition' => 'Édition', # NEW
'icone_envoyer_message' => 'Sänd detta meddelande',
'icone_evolution_visites' => 'Besöksantal<br />@visites@ besök',
'icone_ma_langue' => 'Ma langue', # NEW
'icone_mes_infos' => 'Mes informations', # NEW
'icone_mes_preferences' => 'Mes préférences', # NEW
'icone_modif_groupe_mots' => 'Editera nyckelordsgruppen',
'icone_modifier_article' => 'Editera artikeln',
'icone_modifier_breve' => 'Editera nyheten',
'icone_modifier_message' => 'Editera meddelandet',
'icone_modifier_mot' => 'Editera detta nyckelord',
'icone_modifier_rubrique' => 'Editera avdelningen',
'icone_modifier_site' => 'Editera sajten',
'icone_poster_message' => 'Anslå ett meddelande',
'icone_publication' => 'Publication', # NEW
'icone_publier_breve' => 'Publicera nyhet',
'icone_referencer_nouveau_site' => 'Länka en ny sajt',
'icone_refuser_breve' => 'Refusera nyheten',
'icone_relancer_signataire' => 'Kontakta personen igen',
'icone_retour' => 'Tillbaka',
'icone_retour_article' => 'Tillbaka till artikeln',
'icone_squelette' => 'Squelettes', # NEW
'icone_suivi_forum' => 'Uppföljning av publika forum: @nb_forums@ bidrag',
'icone_suivi_publication' => 'Suivi de la publication', # NEW
'icone_supprimer_cookie' => 'Radera cookien',
'icone_supprimer_groupe_mots' => 'Radera den här gruppen',
'icone_supprimer_rubrique' => 'Radera den här avdelningen',
'icone_supprimer_signature' => 'Radera den här signaturen',
'icone_valider_signature' => 'Validera signaturen',
'icone_voir_sites_references' => 'Visa länkade sajter',
'icone_voir_tous_mots_cles' => 'Visa alla nyckelord',
'image_administrer_rubrique' => 'Du kan hantera den här avdelningen',
'info_1_article' => '1 artikel',
'info_1_article_syndique' => '1 article syndiqué', # NEW
'info_1_auteur' => '1 auteur', # NEW
'info_1_breve' => '1 nyhet',
'info_1_message' => '1 message', # NEW
'info_1_mot_cle' => '1 mot-clé', # NEW
'info_1_rubrique' => '1 rubrique', # NEW
'info_1_site' => '1 sajt',
'info_1_visiteur' => '1 visiteur', # NEW
'info_activer_cookie' => 'Du kan aktivera en <b>administrationscookie</b>, som tillåter dig
 att enkelt växla mellan den publika och den privata delen.',
'info_activer_forum_public' => '<i>för att aktivera publika forum, var vänlig och välj moderationssätt:</i>',
'info_admin_etre_webmestre' => 'Me donner les droits de webmestre', # NEW
'info_admin_gere_rubriques' => 'Den här administratören hanterar följande avdelningar:',
'info_admin_gere_toutes_rubriques' => 'Den här administratören hanterar <b>alla avdelningar</b>.',
'info_admin_je_suis_webmestre' => 'Je suis <b>webmestre</b>', # NEW
'info_admin_statuer_webmestre' => 'Donner à cet administrateur les droits de webmestre', # NEW
'info_admin_webmestre' => 'Cet administrateur est <b>webmestre</b>', # NEW
'info_administrateur' => 'Administratör',
'info_administrateur_1' => 'Administratör',
'info_administrateur_2' => 'på sajten (<i>använd med försiktighet</i>)',
'info_administrateur_site_01' => 'Om du är en sajt-administratör, var vänlig',
'info_administrateur_site_02' => 'klicka på den här länken',
'info_administrateurs' => 'Administratörer',
'info_administrer_rubrique' => 'Du kan hantera den här avdelningen',
'info_adresse' => 'till adressen:',
'info_adresse_email' => 'EMAIL-ADRESS:',
'info_adresse_url' => 'Din sajts publika URL',
'info_afficher_visites' => 'Visa besök till:',
'info_affichier_visites_articles_plus_visites' => 'Visa besök till <b>de mest besökta artiklarna sedan starten:</b>',
'info_aide_en_ligne' => 'SPIP Online Hjälp',
'info_ajout_image' => 'När du lägget till filer som bifogade dokument till en artikel,
  kan SPIP automatiskt skapa miniatyrer av de
  inlagda bilderna. Det tillåter till exempel
  att man automatiskt skapar ett galleri eller en portfolio.',
'info_ajout_participant' => 'Följande deltagare har lagts till:',
'info_ajouter_rubrique' => 'lägg till en annan avdelning:',
'info_annonce_nouveautes' => 'Senaste nyheterna',
'info_anterieur' => 'föregående',
'info_appliquer_choix_moderation' => 'Aktivera ditt val av moderering:',
'info_article' => 'artikel',
'info_article_2' => 'artiklar',
'info_article_a_paraitre' => 'Fördaterade artiklar som kommer att publiceras',
'info_articles_02' => 'artiklar',
'info_articles_2' => 'Artiklar',
'info_articles_auteur' => 'Den här redaktörens artiklar',
'info_articles_lies_mot' => 'Artiklar kopplade till det här nyckelordet',
'info_articles_trouves' => 'Artiklar hittade',
'info_articles_trouves_dans_texte' => 'Artiklar hittade (i texten)',
'info_attente_validation' => 'Dina artiklar som väntar på validering',
'info_aucun_article' => 'Aucun article', # NEW
'info_aucun_article_syndique' => 'Aucun article syndiqué', # NEW
'info_aucun_auteur' => 'Aucun auteur', # NEW
'info_aucun_breve' => 'Aucune brève', # NEW
'info_aucun_message' => 'Aucun message', # NEW
'info_aucun_mot_cle' => 'Aucun mot-clé', # NEW
'info_aucun_rubrique' => 'Aucune rubrique', # NEW
'info_aucun_site' => 'Aucun site', # NEW
'info_aucun_visiteur' => 'Aucun visiteur', # NEW
'info_aujourdhui' => 'idag:',
'info_auteur_message' => 'AVSÄNDARE:',
'info_auteurs' => 'Redaktörer',
'info_auteurs_par_tri' => 'Redaktörer@partri@',
'info_auteurs_trouves' => 'Redaktörer funna',
'info_authentification_externe' => 'Extern autentifiering',
'info_avertissement' => 'Varning',
'info_barre_outils' => 'med dess verktygsfält?',
'info_base_installee' => 'Databasen är skapad',
'info_base_restauration' => 'Återskapande av databasen pågår.',
'info_bloquer' => 'Block',
'info_breves' => 'Använder din sajt nyhetssystemet?',
'info_breves_03' => 'Nyheter',
'info_breves_liees_mot' => 'Nyheter knutna till det här nyckelordet',
'info_breves_touvees' => 'Nyheter funna',
'info_breves_touvees_dans_texte' => 'Nyheter funna (i texten)',
'info_changer_nom_groupe' => 'Byt namn på den här gruppen:',
'info_chapeau' => 'Ingress',
'info_chapeau_2' => 'Introduktion:',
'info_chemin_acces_1' => 'inställningar: <b>sökväg i katalogen</b>',
'info_chemin_acces_2' => 'Från och med nu måste du konfigurera sökvägen till katalogen information. Det är nödvändigt för att kunna läsa användarprofilerna som är sparade i katalogen.',
'info_chemin_acces_annuaire' => 'Inställningar: <b>Sökväg i katalogen</b>',
'info_choix_base' => 'Tredje steget:',
'info_classement_1' => '<sup>er</sup> sur @liste@', # NEW
'info_classement_2' => '<sup>e</sup> sur @liste@', # NEW
'info_code_acces' => 'Glöm inte ditt eget lösenord!',
'info_comment_lire_tableau' => 'Hur tolkar man den här bilden',
'info_compatibilite_html' => 'Norme HTML à suivre', # NEW
'info_compresseur_gzip' => '<b>N. B. :</b> Il est recommandé de vérifier au préalable si l\'hébergeur compresse déjà systématiquement les scripts php ; pour cela, vous pouvez par exemple utiliser le service suivant : @testgzip@', # NEW
'info_compresseur_texte' => 'Si votre serveur ne comprime pas automatiquement les pages html pour les envoyer aux internautes, vous pouvez essayer de forcer cette compression pour diminuer le poids des pages téléchargées. <b>Attention</b> : cela peut ralentir considerablement certains serveurs.', # NEW
'info_compresseur_titre' => 'Optimering och komprimering',
'info_config_forums_prive' => 'Dans l’espace privé du site, vous pouvez activer plusieurs types de forums :', # NEW
'info_config_forums_prive_admin' => 'Un forum réservé aux administrateurs du site :', # NEW
'info_config_forums_prive_global' => 'Un forum global, ouvert à tous les rédacteurs :', # NEW
'info_config_forums_prive_objets' => 'Un forum sous chaque article, brève, site référencé, etc. :', # NEW
'info_config_suivi' => 'Om den här adressen är en mailing lista, kan du under adressen visa var man kan registrera sig. Det kan vara en URL (till exempel en webbsida där man kan registrera sig), eller en e-postadress med en speciell ärenderad (till exempel: <tt>@adresse_suivi@?subject=subscribe</tt>):',
'info_config_suivi_explication' => 'Du kan anmäla dig till sajtens nyhetsbrev. Du kommer då att automatiskt få meddelanden om artiklar och nyheter som laddats upp för publicering.',
'info_confirmer_passe' => 'Bekräfta ditt nya lösenord:',
'info_conflit_edition_avis_non_sauvegarde' => 'Attention, les champs suivants ont été modifiés par ailleurs. Vos modifications sur ces champs n\'ont donc pas été enregistrées.', # NEW
'info_conflit_edition_differences' => 'Différences :', # NEW
'info_conflit_edition_version_enregistree' => 'La version enregistrée :', # NEW
'info_conflit_edition_votre_version' => 'Votre version :', # NEW
'info_connexion_base' => 'Försöker att ansluta till databasen',
'info_connexion_base_donnee' => 'Connexion à votre base de données', # NEW
'info_connexion_ldap_ok' => 'Din förbindelse till LDAP-servern lyckades.</b><p> Du kan gå vidare till nästa steg.</p>', # MODIF
'info_connexion_mysql' => 'Din databasförbindelse',
'info_connexion_ok' => 'Förbindelsen lyckades.',
'info_contact' => 'Kontakt',
'info_contenu_articles' => 'Innehåll i artiklarna',
'info_contributions' => 'Contributions', # NEW
'info_creation_mots_cles' => 'Skapa och hantera sajtens nyckelord här.',
'info_creation_paragraphe' => '(För att skapa stycken, räcker det att lämna tomma rader.)',
'info_creation_rubrique' => 'Innan du kan skriva artiklar,<br /> måste du skapa åtminstone en avdelning.<br />',
'info_creation_tables' => 'Skapar databastabeller',
'info_creer_base' => '<b>Skapa</b> en ny databas:',
'info_dans_groupe' => 'I gruppen:',
'info_dans_rubrique' => 'I avdelningen:',
'info_date_publication_anterieure' => 'Datum för tidigare publicering:',
'info_date_referencement' => 'DATUM DÅ SAJTEN LÄNKADES:',
'info_delet_mots_cles' => 'Du har försökt radera nyckelordet
<b>@titre_mot@</b> (@type_mot@). Nyckleordet är länkat till
<b>@texte_lie@</b> du måste bekräfta handlingen:',
'info_derniere_etape' => 'Klart!',
'info_derniere_syndication' => 'Den sista syndikeringen av den här sajten skedde den',
'info_derniers_articles_publies' => 'Dina senaste publicerade artiklar',
'info_desactiver_forum_public' => 'Avaktivera de publika forumen.
 Publika forum kan tillåtas  för
 enskilda artiklar; de kommer inte att vara aktiva för avdelningae, nyheter, osv.',
'info_desactiver_messagerie_personnelle' => 'Du kan aktivera eller avaktivera dina personliga meddelanden på den här sajten.',
'info_descriptif' => 'Beskrivning:',
'info_desinstaller_plugin' => 'raderar data och avaktiverar tillägget',
'info_discussion_cours' => 'Pågående diskussion',
'info_ecrire_article' => 'Innan du kan skriva artiklar, ymåste du skapa minst en avdelning.',
'info_email_envoi' => 'Avsändaren mailadress (frivilligt)',
'info_email_envoi_txt' => 'Skriv in avsändarens e-postadress som används när man sänder mejlen (som default används mottagarens adress som avsändaradress) :',
'info_email_webmestre' => 'Webmasterns mejladress (frivillig)',
'info_entrer_code_alphabet' => 'Skriv in koden för teckenuppsättningen som skall användas:',
'info_envoi_email_automatique' => 'Automatisk e-post',
'info_envoi_forum' => 'Skicka foruminlägg till artikelredaktörerna',
'info_envoyer_maintenant' => 'Skicka nu',
'info_erreur_restauration' => 'Fel vid återskapande: filen finns inte.',
'info_etape_suivante' => 'Gå till nästa steg',
'info_etape_suivante_1' => 'Du kan fortsätta till nästa steg.',
'info_etape_suivante_2' => 'Du kan fortsätta till nästa steg.',
'info_exceptions_proxy' => 'Exceptions pour le proxy', # NEW
'info_exportation_base' => 'Exportera databasen till @archive@',
'info_facilite_suivi_activite' => 'För att underlätta att följa de redaktionella;
  aktiviteterna, kan SPIP skicka medddelanden via e-post, exempelvis till en maillista för redaktörer,
  angående publiceringar och godkännanden.',
'info_fichiers_authent' => 'Lösenordsfil ".htpasswd"',
'info_fonctionnement_forum' => 'Fonctionnement du forum :', # NEW
'info_forum_administrateur' => 'Administratörernas forum',
'info_forum_interne' => 'Internt forum',
'info_forum_ouvert' => 'I sajtens privata del är ett forum öppet för alla
  redaktörer. Nedan kan du aktivera ett
  extra forum reserverat för administratörerna.',
'info_forum_statistiques' => 'Besöksstatistik',
'info_forums_abo_invites' => 'Votre site comporte des forums sur abonnement ; les visiteurs sont donc invités à s\'enregistrer sur le site public.', # NEW
'info_gauche_admin_effacer' => '<b>Endast administratörer har tillgång till denna sida.</b><p> Den ger tillgång till tekniska underhållsrutiner av olika slag. En del av dem kommer, om de används, att kräva FTP-tillgång till Webservern för autentifiering.</p>', # MODIF
'info_gauche_admin_tech' => '<b>Cette page est uniquement accessible aux responsables du site.</b><p> Elle donne accès aux différentes
fonctions de maintenance technique. Certaines d\'entre elles donnent lieu à un processus d\'authentification spécifique, qui
exige d\'avoir un accès FTP au site Web.</p>', # MODIF
'info_gauche_admin_vider' => '<b>Cette page est uniquement accessible aux responsables du site.</b><p> Elle donne accès aux différentes
fonctions de maintenance technique. Certaines d\'entre elles donnent lieu à un processus d\'authentification spécifique, qui
exige d\'avoir un accès FTP au site Web.</p>', # MODIF
'info_gauche_auteurs' => 'Här hittar du alla redaktörer på sajten.
 Status på var och en av dem visas av färgen på ikonen (administratör = grön; redaktör = gul).',
'info_gauche_auteurs_exterieurs' => 'Externa redaktörer utan behörighet på sajten, visas med en blå ikon; raderade redaktörer med en soptunna.',
'info_gauche_messagerie' => 'La messagerie vous permet d\'échanger des messages entre rédacteurs, de conserver des pense-bêtes (pour votre usage personnel) ou d\'afficher des annonces sur la page d\'accueil de l\'espace privé (si vous êtes administrateur).', # NEW
'info_gauche_numero_auteur' => 'REDAKTÖR NUMMER:',
'info_gauche_numero_breve' => 'NYHET NUMMER',
'info_gauche_statistiques_referers' => 'Cette page présente la liste des <i>referers</i>, c\'est-à-dire des sites contenant des liens menant vers votre propre site, uniquement pour hier et aujourd\'hui ; cette liste est remise à zéro toutes les 24 heures.', # NEW
'info_gauche_suivi_forum' => 'Sidna för<i>forumuppföljning</i> är ett administrationsverktyg för din sajt (inte en diskussions- eller redigeringsida). Den visar alla bidrag till det publika forum som hör till artikeln och där kan du hantera bidragen contributions.',
'info_gauche_suivi_forum_2' => 'La page de <i>suivi des forums</i> est un outil de gestion de votre site (et non un espace de discussion ou de rédaction). Elle affiche toutes les contributions des forums du site, aussi bien celles du site public que de l\'espace privé et vous permet de gérer ces contributions.', # NEW
'info_gauche_visiteurs_enregistres' => 'Vous trouverez ici les visiteurs enregistrés
	dans l\'espace public du site (forums sur abonnement).', # NEW
'info_generation_miniatures_images' => 'Skapande av tumnagelbilder.',
'info_gerer_trad' => 'Aktivera översättningslänkar?',
'info_gerer_trad_objets' => '@objets@ : gérer les liens de traduction', # NEW
'info_groupe_important' => 'Viktig grupp',
'info_hebergeur_desactiver_envoi_email' => 'Vissa webhotell avaktiverar automatisk mejl
på deras servrar. Om så är fallet kan följande
funktioner hos SPIP inte användas.',
'info_hier' => 'I går:',
'info_historique' => 'Versioner:',
'info_historique_activer' => 'Aktivera versionshantering',
'info_historique_affiche' => 'Visa denna version',
'info_historique_comparaison' => 'jämför',
'info_historique_desactiver' => 'Avaktivera versionshantering',
'info_historique_lien' => 'Visa versioner',
'info_historique_texte' => 'Versionshantering tillåter dig att se förändringar i och tillägg till en artikel och visar skillnader mellan versioner.',
'info_historique_titre' => 'Versionshantering',
'info_identification_publique' => 'Din publika identitet...',
'info_image_process' => 'Välj den bästa metoden för att skapa miniatyrer genom att klicka på motsvarande bild.',
'info_image_process2' => '<b>OBS!.</b> <i>Om du inte kan se någon bild, så är din server inte konfigurerad för att använda sådana verktyg. Om du vill använda dessa finesser så kontakta din leverantörs tekniska support och be dem installera utökningarna för «GD» eller «Imagick».</i>',
'info_images_auto' => 'Images calculées automatiquement', # NEW
'info_informations_personnelles' => 'Personlig information',
'info_inscription_automatique' => 'Automatisk registreing av nya redaktörer',
'info_jeu_caractere' => 'Sajtens teckenuppsättning',
'info_jours' => 'dagar',
'info_laisser_champs_vides' => 'Lämna dessa fält tomma)',
'info_langues' => 'Sajtens språk',
'info_ldap_ok' => 'L\'authentification LDAP est installée.', # NEW
'info_lien_hypertexte' => 'Hyperlänk:',
'info_liens_syndiques_1' => 'syndikerade länkar',
'info_liens_syndiques_2' => 'i väntan på validering.',
'info_liens_syndiques_3' => 'forum',
'info_liens_syndiques_4' => 'är',
'info_liens_syndiques_5' => 'forum',
'info_liens_syndiques_6' => 'är',
'info_liens_syndiques_7' => 'i väntan på validering.',
'info_liste_redacteurs_connectes' => 'Inlogggade redaktörer',
'info_login_existant' => 'Användarnamnet finns redan.',
'info_login_trop_court' => 'Användarnamnet är för kort.',
'info_logos' => 'Logotyperna',
'info_maximum' => 'maximum:',
'info_meme_rubrique' => 'I samma avdelning',
'info_message' => 'Meddelande från',
'info_message_efface' => 'MEDDELANDET RADERAT',
'info_message_en_redaction' => 'utkorg',
'info_message_technique' => 'Tekniskt meddelande:',
'info_messagerie_interne' => 'Internmeddelande',
'info_mise_a_niveau_base' => 'SQL database upgradering',
'info_mise_a_niveau_base_2' => '{{Warning!}} Du har installerat {äldre} 
  SPIP filer än de som redan
  varit installerade på sajten: Du riskerar att förlora databasen
  och ha en sajt som inte längre fungerar.<br />{{återinstallera
  SPIP filerna.}}',
'info_mode_fonctionnement_defaut_forum_public' => 'Valt funktionssätt för de publika forumen',
'info_modifier_auteur' => 'Editera detaljer för redaktören:',
'info_modifier_breve' => 'Editera nyheten:',
'info_modifier_mot' => 'Editera nyckelordet:',
'info_modifier_rubrique' => 'Editera avdelningen:',
'info_modifier_titre' => 'Editera: @titre@',
'info_mon_site_spip' => 'Min SPIP-sajt',
'info_mot_sans_groupe' => '(Nyckelord utan en grupp...)',
'info_moteur_recherche' => 'Integrerad sökmotor',
'info_mots_cles' => 'Nyckelord',
'info_mots_cles_association' => 'Nyckelord i den här gruppen kan associeras med:',
'info_moyenne' => 'genomsnitt:',
'info_multi_articles' => 'Aktivera språkmenyen för artiklar?',
'info_multi_cet_article' => 'Den här artikelns språk:',
'info_multi_langues_choisies' => 'Var vänlig, välj tillgängliga språk för redaktörerna bland språken nedan.
  Språk som redan används på din sajt (högst upp på listan) kan inte avaktiveras.',
'info_multi_objets' => '@objets@ : activer le menu de langue', # NEW
'info_multi_rubriques' => 'Aktivera språkmenyn för avdelningar?',
'info_multi_secteurs' => '... bara för avdelningarna i roten?',
'info_nb_articles' => '@nb@ articles', # NEW
'info_nb_articles_syndiques' => '@nb@ articles syndiqués', # NEW
'info_nb_auteurs' => '@nb@ auteurs', # NEW
'info_nb_breves' => '@nb@ brèves', # NEW
'info_nb_messages' => '@nb@ messages', # NEW
'info_nb_mots_cles' => '@nb@ mots-clés', # NEW
'info_nb_rubriques' => '@nb@ rubriques', # NEW
'info_nb_sites' => '@nb@ sites', # NEW
'info_nb_visiteurs' => '@nb@ visiteurs', # NEW
'info_nom' => 'Namn',
'info_nom_destinataire' => 'Mottagarens namn',
'info_nom_site' => 'Din sajts namn',
'info_nom_site_2' => '<b>Sajtens namn</b> [krävs]',
'info_nombre_articles' => '@nb_articles@ artiklar,',
'info_nombre_breves' => '@nb_breves@ nyheter,',
'info_nombre_partcipants' => 'DELTAGARE I DISKUSSIONEN:',
'info_nombre_rubriques' => '@nb_rubriques@ avdelningar,',
'info_nombre_sites' => '@nb_sites@ sajter,',
'info_non_deplacer' => 'Flytta inte...',
'info_non_envoi_annonce_dernieres_nouveautes' => 'SPIP peut envoyer, régulièrement, l\'annonce des dernières nouveautés du site
		(articles et brèves récemment publiés).', # NEW
'info_non_envoi_liste_nouveautes' => 'Skicka inte listan med senaste nyheterna',
'info_non_modifiable' => 'kan inte förändras',
'info_non_suppression_mot_cle' => 'Jag vill inte radera nyckelordet.',
'info_note_numero' => 'Note @numero@', # NEW
'info_notes' => 'Fotnoter',
'info_nouveaux_message' => 'Nya meddelanden',
'info_nouvel_article' => 'Ny artikel',
'info_nouvelle_traduction' => 'Ny översättning:',
'info_numero_article' => 'ARTIKEL NUMMER:',
'info_obligatoire_02' => '[Krävs]',
'info_option_accepter_visiteurs' => 'Tillåt besökare registrera sig på den publika delen',
'info_option_email' => 'When a site visitor posts a message to the forum
		associated with an article, the article\'s authors can be
		informed of this message by e-mail. Do you wish to use this option?', # MODIF
'info_option_faire_suivre' => 'Vidarebefordra forummeddelanden till artikelredaktören',
'info_option_ne_pas_accepter_visiteurs' => 'Avvisa besökarregistrering',
'info_option_ne_pas_faire_suivre' => 'Vidarebefordra inte forummeddelanden',
'info_options_avancees' => 'AVANCERADE INSTÄLLNINGAR',
'info_ortho_activer' => 'Aktivera stavningskontrollen.',
'info_ortho_desactiver' => 'Avaktivera stavningskontrollen.',
'info_ou' => 'eller...',
'info_oui_suppression_mot_cle' => 'Jag vill radera det här nyckelordet permanent.',
'info_page_interdite' => 'Förbjuden sida',
'info_par_nom' => 'efter namn',
'info_par_nombre_article' => 'efter antal artiklar',
'info_par_statut' => 'efter status',
'info_par_tri' => '\'(efter @tri@)\'',
'info_pas_de_forum' => 'inget forum',
'info_passe_trop_court' => 'lösenordet är för kort.',
'info_passes_identiques' => 'De två lösenorden är inte identiska.',
'info_pense_bete_ancien' => 'Dina gamla meddelanden', # MODIF
'info_plus_cinq_car' => 'mer än 5 tecken',
'info_plus_cinq_car_2' => '(Mer än 5 tecken)',
'info_plus_trois_car' => '(Mer än 3 tecken)',
'info_popularite' => 'popularitet: @popularite@; besök: @visites@',
'info_popularite_2' => 'sajtens popularitet:',
'info_popularite_3' => 'populäritet: @popularite@; besök: @visites@',
'info_popularite_4' => 'popularitet: @popularite@; besök: @visites@',
'info_post_scriptum' => 'Postscript',
'info_post_scriptum_2' => 'PS:',
'info_pour' => 'för',
'info_preview_admin' => 'Endast administratörer har tillgång till förhandsvisning',
'info_preview_comite' => 'Alla redaktörer har tillgång till förhandsvisning',
'info_preview_desactive' => 'Förhandsvisning är avaktiverad',
'info_preview_texte' => 'Il est possible de prévisualiser le site comme si tous les articles et les brèves (ayant au moins le statut « proposé ») étaient publiés. Cette possibilité doit-elle être ouverte aux administrateurs seulement, à tous les rédacteurs, ou à personne ?', # NEW
'info_principaux_correspondants' => 'Vos principaux correspondants', # NEW
'info_procedez_par_etape' => 'Var vänlig, fortsätt steg för steg',
'info_procedure_maj_version' => 'Uppdateringsproceduren skall köras för att
 anpassa databasen till den nya versionen av SPIP.',
'info_proxy_ok' => 'Testen av proxy lyckades.',
'info_ps' => 'P.S.',
'info_publier' => 'publicera',
'info_publies' => 'Dina onlinepublicerade artiklar',
'info_question_accepter_visiteurs' => 'Om sajtens template tillåter besökare att registrera sig utan att gå in i den privata delen, var vänlig och aktivera följande option:',
'info_question_activer_compactage_css' => 'Vill du aktivera komprimering av CSS stylesheets?',
'info_question_activer_compactage_js' => 'Vill du aktivera komprimering av  Javascript filer?',
'info_question_activer_compresseur' => 'Vill du aktivera komprimering av HTTP trafiken?',
'info_question_gerer_statistiques' => 'Skall din sajt hantera besöksstatistik?',
'info_question_inscription_nouveaux_redacteurs' => 'Acceptez-vous les inscriptions de nouveaux rédacteurs à
  partir du site public ? Si vous acceptez, les visiteurs pourront s\'inscrire
  depuis un formulaire automatisé et accéderont alors à l\'espace privé pour
  proposer leurs propres articles. <blockquote><i>Lors de la phase d\'inscription,
  les utilisateurs reçoivent un courrier électronique automatique
  leur fournissant leurs codes d\'accès au site privé. Certains
  hébergeurs désactivent l\'envoi de mails depuis leurs
  serveurs : dans ce cas, l\'inscription automatique est
  impossible.', # MODIF
'info_question_mots_cles' => 'Vill du använsda nyckelord i din sajt?',
'info_question_proposer_site' => 'Vem kan föreslå länkar till sajter?',
'info_question_utilisation_moteur_recherche' => 'Vill du använda SPIPŽs integrerade sökfunktion?
 (Att stänga av den ökar systemets prestanda.)',
'info_question_vignettes_referer' => 'Lorsque vous consultez les statistiques, vous pouvez visualiser des aperçus des sites d\'origine des visites', # NEW
'info_question_vignettes_referer_non' => 'Ne pas afficher les captures des sites d\'origine des visites', # NEW
'info_question_vignettes_referer_oui' => 'Visa skärmbilder av länkande sajter',
'info_question_visiteur_ajout_document_forum' => 'Do you wish to authorise visitors to attach documenst (images, sound files, ...) to their forum messages?', # MODIF
'info_question_visiteur_ajout_document_forum_format' => 'If so, give below the list of extensions for the file types which are to be authorised (e.g. gif, jpg, png, mp3).', # MODIF
'info_qui_attribue_mot_cle' => 'Nyckelord i den här gruppen kan tilldelas av:',
'info_racine_site' => 'Sajtens bas',
'info_recharger_page' => 'Var vänlig och uppdatera sidan om en liten stund.',
'info_recherche_auteur_a_affiner' => 'För många resultat för "@cherche_auteur@"; Var vänlig och avgränsa sökningen mer.',
'info_recherche_auteur_ok' => 'Flera redaktörer hittades för "@cherche_auteur@":',
'info_recherche_auteur_zero' => 'Inga resultat hittades för "@cherche_auteur@".',
'info_recommencer' => 'Var vänlig och försök igen.',
'info_redacteur_1' => 'Redaktör',
'info_redacteur_2' => 'för tillgång till den privata delen (<i>rekommenderas</i>)',
'info_redacteurs' => 'Redaktörer',
'info_redaction_en_cours' => 'UNDER ARBETE',
'info_redirection' => 'Ompekning',
'info_referencer_doc_distant' => 'Länka till ett dokument på internet:',
'info_refuses' => 'Refuserade artiklar',
'info_reglage_ldap' => 'inställningar: <b>anpassar LDAP import</b>',
'info_remplacer_mot' => 'Remplacer "@titre@"', # NEW
'info_renvoi_article' => '<b>Ompekning.</b> Den här artikeln pekar om till:',
'info_reserve_admin' => 'Enbart administratörer kan förändra adressen.',
'info_restauration_sauvegarde' => 'Lägger tillbaka backupen @archive@',
'info_restauration_sauvegarde_insert' => 'Läser in @archive@ i databasen',
'info_restreindre_rubrique' => 'Begränsa administrationen till avdelningen:',
'info_resultat_recherche' => 'Sökresultat:',
'info_rubriques' => 'Avdelningar',
'info_rubriques_02' => 'avdelningar',
'info_rubriques_liees_mot' => 'Avdelningar förknippade med detta nyckelord',
'info_rubriques_trouvees' => 'Hittade avdelningar',
'info_rubriques_trouvees_dans_texte' => 'Avdelningar funna (i texten)',
'info_sans_titre' => 'Utan titel',
'info_sauvegarde' => 'Backup',
'info_sauvegarde_articles' => 'Backa upp artiklarna',
'info_sauvegarde_articles_sites_ref' => 'Säkerhetskopiera artiklar från länkade sajter',
'info_sauvegarde_auteurs' => 'Säkerhetskopiera redaktörerna',
'info_sauvegarde_breves' => 'Ta en säkerhetskopia av nyheterna',
'info_sauvegarde_documents' => 'Säkerhetskopiera dokumenten',
'info_sauvegarde_echouee' => 'Om säkerhetskopieringen misslyckas («Maximal utförandetid överskreds»),',
'info_sauvegarde_forums' => 'Säkerhetskopiera forumen',
'info_sauvegarde_groupe_mots' => 'Säkerhetskopiera nyckelordsgrupperna',
'info_sauvegarde_messages' => 'Säkerhetskopiera meddelanden',
'info_sauvegarde_mots_cles' => 'Säkerhetskopiera nyckelorden',
'info_sauvegarde_petitions' => 'Säkerhetskopiera namninsamlingarna',
'info_sauvegarde_refers' => 'Säkerhetskopiera länkarna',
'info_sauvegarde_reussi_01' => 'Säkerhetskopieringen lyckades.',
'info_sauvegarde_reussi_02' => 'Databasen har sparats i @archive@. Du kan',
'info_sauvegarde_reussi_03' => 'Återvänd till administrationen',
'info_sauvegarde_reussi_04' => 'på din sajt.',
'info_sauvegarde_rubrique_reussi' => 'Les tables de la rubrique @titre@ ont été sauvegardée dans @archive@. Vous pouvez', # NEW
'info_sauvegarde_rubriques' => 'Säkerhetskopiera avdelningarna',
'info_sauvegarde_signatures' => 'Säkerhetskopiera namininsamlingarnas underskrifter',
'info_sauvegarde_sites_references' => 'säkerhetskopiera länkade sajter',
'info_sauvegarde_type_documents' => 'Säkerhetskopiera dokumenttyper',
'info_sauvegarde_visites' => 'Säkerhetskopiera besök',
'info_selection_chemin_acces' => '<b>Välj</b> åtkomstväg i katalogen:',
'info_selection_un_seul_mot_cle' => 'Du kan välja <b>endast ett nyckelord</b> samtidigt i den här gruppen.',
'info_signatures' => 'underskrifter',
'info_site' => 'Sajt',
'info_site_2' => 'sajt:',
'info_site_min' => 'sajt',
'info_site_propose' => 'Sajt föreslagen den:',
'info_site_reference_2' => 'Länkad sajt',
'info_site_syndique' => 'Denna sajt är syndikerad...',
'info_site_valider' => 'Sajter som väntar på godkännande',
'info_site_web' => 'WEBBSAJT:',
'info_sites' => 'sajter',
'info_sites_lies_mot' => 'Länkade sajter knutna till detta nyckelord',
'info_sites_proxy' => 'Använder en proxy',
'info_sites_refuses' => 'Refuserade sajter',
'info_sites_trouves' => 'Hittade sajter',
'info_sites_trouves_dans_texte' => 'Hittade sajter (i texten)',
'info_sous_titre' => 'Undertitel:',
'info_statut_administrateur' => 'Administratör',
'info_statut_auteur' => 'Redaktörens status:', # MODIF
'info_statut_auteur_a_confirmer' => 'Registreringar som skall godkännas',
'info_statut_auteur_autre' => 'Annan status:',
'info_statut_efface' => 'Raderad',
'info_statut_redacteur' => 'Redaktör',
'info_statut_site_1' => 'Denna sajt är:',
'info_statut_site_2' => 'Publicerad',
'info_statut_site_3' => 'Inskickad',
'info_statut_site_4' => 'I papperskorgen',
'info_statut_utilisateurs_1' => 'Startinställningar för importerade användare',
'info_statut_utilisateurs_2' => 'Choisissez le statut qui est attribué aux personnes présentes dans l\'annuaire LDAP lorsqu\'elles se connectent pour la première fois. Vous pourrez par la suite modifier cette valeur pour chaque auteur au cas par cas.', # NEW
'info_suivi_activite' => 'Följ upp aktiviteten på sajten',
'info_supprimer_mot' => 'Radera nyckelordet',
'info_surtitre' => 'Övertitel:',
'info_syndication_integrale_1' => 'Din sajt publiceras RSS-filer för syndikering (Se <a href="@url@">@titre@</a>).',
'info_syndication_integrale_2' => 'Vill du skicka hela artiklar eller bara en sammanfattning på ett par hundra tecken?',
'info_table_prefix' => 'Det är möjligt att byta prefix i databastabellernas namn. (Du behöver göra det om di installerar flera sajter i samma databas). Prefixet måste skrivas utan accenter, med versaler utan mellanslag.',
'info_taille_maximale_images' => 'SPIP va tester la taille maximale des images qu\'il peut traiter (en millions de pixels).<br /> Les images plus grandes ne seront pas réduites.', # NEW
'info_taille_maximale_vignette' => 'Maximal storlek på miniatyrer som skapats av systemet:',
'info_terminer_installation' => 'Du kan nu avsluta installationsprocessen.',
'info_texte' => 'Text',
'info_texte_explicatif' => 'Förklarande text',
'info_texte_long' => '(Texten är lång: Den kommer att synas i flera delar som sedan sammanfogas efter validering.)',
'info_texte_message' => 'Texten i ditt meddelande:',
'info_texte_message_02' => 'Texten i ditt meddelande',
'info_titre' => 'Titel:',
'info_titre_mot_cle' => 'Namen eller titel för det här nyckelordet',
'info_total' => 'total:',
'info_tous_articles_en_redaction' => 'Alla artiklar under arbete',
'info_tous_articles_presents' => 'Alla artiklar som publicerats i den här avdelningen',
'info_tous_articles_refuses' => 'Tous les articles refusés', # NEW
'info_tous_les' => 'alla:',
'info_tous_redacteurs' => 'meddelande till alla redaktörer',
'info_tout_site' => 'Hela sajten',
'info_tout_site2' => 'Artikeln har inte blivit översatt till det här språket.',
'info_tout_site3' => 'Artikeln har blivit översatt till det hör språket men vissa förändringar har gjorts senare i orginalartikeln. Översättningen behöver uppdateras.',
'info_tout_site4' => 'Artikeln har blivit översatt till det här språket och översättningen är aktuell.',
'info_tout_site5' => 'Orginalartikeln.',
'info_tout_site6' => '<b>OBS:</b> bara orginalartiklarna visas.
Översättningarna är länkade till orginalet
med en färg som visar deras status:',
'info_traductions' => 'Traductions', # NEW
'info_travail_colaboratif' => 'Gemensamt arbete på artiklar',
'info_un_article' => 'en artikel,',
'info_un_mot' => 'Ett nyckelord åt gången',
'info_un_site' => 'En sajt,',
'info_une_breve' => 'en nyhet,',
'info_une_rubrique' => 'en avdelning,',
'info_une_rubrique_02' => '1 avdelning',
'info_url' => 'URL:',
'info_url_proxy' => 'URL du proxy', # NEW
'info_url_site' => 'SAJTEN\'S URL:',
'info_url_test_proxy' => 'URL de test', # NEW
'info_urlref' => 'Hyperlänk:',
'info_utilisation_spip' => 'SPIP är nu färdig att användas..',
'info_visites_par_mois' => 'Besökare per månad:',
'info_visites_plus_populaires' => 'Visa besök till <b>de populäraste artiklarna</b> och till <b>de senast publicerade artiklarna:</b>',
'info_visiteur_1' => 'Besökare',
'info_visiteur_2' => 'på den publika delen',
'info_visiteurs' => 'Besökare',
'info_visiteurs_02' => 'Beökare på den publika delen',
'info_webmestre_forces' => 'Les webmestres sont actuellement définis dans <tt>@file_options@</tt>.', # NEW
'install_adresse_base_hebergeur' => 'Databasadress tillhandahållen av webbhotellet',
'install_base_ok' => 'La base @base@ a été reconnue', # NEW
'install_echec_annonce' => 'Den här installationen kommer förmodligen inte att fungera, alternativt resulterar den i en sajt med reducerad funktionalitet ...',
'install_extension_mbstring' => 'SPIP fungerar inte med:',
'install_extension_php_obligatoire' => 'SPIP kräver ett tillägg till php:',
'install_login_base_hebergeur' => 'Login tilldelat av leverantören',
'install_nom_base_hebergeur' => 'Databasnamn tilldelat av leverantören:',
'install_pas_table' => 'Databasen har inga tabeller',
'install_pass_base_hebergeur' => 'Lösenord till databasen tilldelat av leverantören',
'install_php_version' => 'Versionen av PHP, @version@ är för gammal (minimum = @minimum@)',
'install_select_langue' => 'Välj ett språk, klicka sedan på knappen "fortsätt" för att starta installationen.',
'install_select_type_db' => 'Välj typ av databas :',
'install_select_type_mysql' => 'MySQL',
'install_select_type_pg' => 'PostgreSQL', # NEW
'install_select_type_sqlite2' => 'SQLite 2',
'install_select_type_sqlite3' => 'SQLite 3',
'install_serveur_hebergeur' => 'Serveur de base de données attribué par l\'hébergeur', # NEW
'install_table_prefix_hebergeur' => 'Préfixe de table attribué par l\'hébergeur :', # NEW
'install_tables_base' => 'Databasens tabeller',
'install_types_db_connus' => 'SPIP sait utiliser <b>MySQL</b> (le plus répandu), <b>PostgreSQL</b> et <b>SQLite</b>.', # NEW
'install_types_db_connus_avertissement' => 'Attention : plusieurs plugins ne fonctionnent qu\'avec MySQL', # NEW
'intem_redacteur' => 'redaktör',
'intitule_licence' => 'Licens',
'item_accepter_inscriptions' => 'Tillåt registreringar',
'item_activer_forum_administrateur' => 'Aktivera administratörernas forum',
'item_activer_messages_avertissement' => 'Aktivera varningsmeddelanden',
'item_administrateur_2' => 'administratör',
'item_afficher_calendrier' => 'Visa i kalendern',
'item_ajout_mots_cles' => 'Tillåt nya nyckelord till forumen',
'item_autoriser_documents_joints' => 'Tillåt dokument bifogade till artiklarna',
'item_autoriser_documents_joints_rubriques' => 'Tillåt dokument i avdelningarna',
'item_autoriser_selectionner_date_en_ligne' => 'Tillåt förändringar av publiseringsdatum',
'item_autoriser_syndication_integrale' => 'Inkludera hela artiklar i syndikeringsfilerna',
'item_bloquer_liens_syndiques' => 'Stoppa syndikerade länkar från godkännande',
'item_breve_refusee' => 'Nej - Nyheten refuserades',
'item_breve_validee' => 'Ja - Nyheten godkändes',
'item_choix_administrateurs' => 'administratörer',
'item_choix_generation_miniature' => 'Generera miniatyrer automatiskt.',
'item_choix_non_generation_miniature' => 'Generera inte miniatyrer.',
'item_choix_redacteurs' => 'redaktörer',
'item_choix_visiteurs' => 'besökare till den publika delen',
'item_compresseur' => 'Aktivera komprimering',
'item_config_forums_prive_global' => 'Aktivera redaktörernas forum',
'item_config_forums_prive_objets' => 'Aktivera dessa forum',
'item_creer_fichiers_authent' => 'Skapa .htpasswd filer',
'item_desactiver_forum_administrateur' => 'Avaktivera administratörernas forum',
'item_gerer_annuaire_site_web' => 'Administrera webbsajt-katalogen',
'item_gerer_statistiques' => 'Hantera statistik',
'item_limiter_recherche' => 'Begränsa sökningen till information i din sajt',
'item_login' => 'Login',
'item_messagerie_agenda' => 'Aktivera meddelandesystemet och kalendern',
'item_mots_cles_association_articles' => 'artiklar',
'item_mots_cles_association_breves' => 'nyheter',
'item_mots_cles_association_rubriques' => 'avdelningar',
'item_mots_cles_association_sites' => 'länkade eller syndikerade sajter.',
'item_non' => 'Nej',
'item_non_accepter_inscriptions' => 'Tillåt inte registreringar',
'item_non_activer_messages_avertissement' => 'Inga varningsmeddelanden',
'item_non_afficher_calendrier' => 'Visa inte i kalendern',
'item_non_ajout_mots_cles' => 'Tillåt inte tillägg av nyckelord till forumen',
'item_non_autoriser_documents_joints' => 'Tillåt inte dokument i artiklarna',
'item_non_autoriser_documents_joints_rubriques' => 'Tillåt inte dokument i avdelningarna',
'item_non_autoriser_selectionner_date_en_ligne' => 'Publiceringsdatum är det datum då dokumentet publicerades.',
'item_non_autoriser_syndication_integrale' => 'Skicka bara en sammanfattning',
'item_non_bloquer_liens_syndiques' => 'Blockera inte länkar som kommer ifrån syndikering',
'item_non_compresseur' => 'Avaktivera komprimering',
'item_non_config_forums_prive_global' => 'Avaktivera redaktörernas forum',
'item_non_config_forums_prive_objets' => 'Avaktivera dessa forum',
'item_non_creer_fichiers_authent' => 'Skapa inte dessa filer',
'item_non_gerer_annuaire_site_web' => 'Avaktivera webbsajt-katalogen',
'item_non_gerer_statistiques' => 'Hantera inte statistik',
'item_non_limiter_recherche' => 'Utöka indexeringen till att omfatta innehåll på länkade sajter',
'item_non_messagerie_agenda' => 'Avaktivera meddelandesystemet och kalendern',
'item_non_publier_articles' => 'Publicera inte artiklar innan deras publiceringsdatum.',
'item_non_utiliser_breves' => 'Använd inte nyheter',
'item_non_utiliser_config_groupe_mots_cles' => 'Använd inte avancerad konfiguration av nyckelord.',
'item_non_utiliser_moteur_recherche' => 'Använd inte sökmotorn',
'item_non_utiliser_mots_cles' => 'Använd inte nyckelord',
'item_non_utiliser_syndication' => 'Använd inte automatisk syndikering',
'item_nouvel_auteur' => 'Ny redaktör',
'item_nouvelle_breve' => 'Ny nyhet',
'item_nouvelle_rubrique' => 'Ny avdelning',
'item_oui' => 'Ja',
'item_publier_articles' => 'Publicera artiklarna utan att ta hänsyn till publiceringsdatum.',
'item_reponse_article' => 'Svara på artikeln',
'item_utiliser_breves' => 'Använd nyheter',
'item_utiliser_config_groupe_mots_cles' => 'Använd avancerad konfiguration för nyckelordsgrupper',
'item_utiliser_moteur_recherche' => 'Använd sökmotorn',
'item_utiliser_mots_cles' => 'Använd nyckelord',
'item_utiliser_syndication' => 'Använd automatisk syndikering',
'item_version_html_max_html4' => 'Se limiter au HTML4 sur le site public', # NEW
'item_version_html_max_html5' => 'Permettre le HTML5', # NEW
'item_visiteur' => 'besökare',

// J
'jour_non_connu_nc' => 'okänd',

// L
'label_bando_outils' => 'Barre d\'outils', # NEW
'label_bando_outils_afficher' => 'Afficher les outils', # NEW
'label_bando_outils_masquer' => 'Masquer les outils', # NEW
'label_choix_langue' => 'Selectionnez votre langue', # NEW
'label_slogan_site' => 'Slogan du site', # NEW
'label_taille_ecran' => 'Largeur de l\'ecran', # NEW
'label_texte_et_icones_navigation' => 'Menu de navigation', # NEW
'label_texte_et_icones_page' => 'Affichage dans la page', # NEW
'ldap_correspondance' => 'héritage du champ @champ@', # NEW
'ldap_correspondance_1' => 'Héritage des champs LDAP', # NEW
'ldap_correspondance_2' => 'Pour chacun des champs SPIP suivants, indiquer le nom du champ LDAP correspondant. Laisser vide pour ne pas le remplir, séparer par des espaces ou des virgules pour essayer plusieurs champs LDAP.', # NEW
'lien_ajout_destinataire' => 'Lägg till som mottagare',
'lien_ajouter_auteur' => 'Lätt till redaktören',
'lien_ajouter_mot' => 'Ajouter ce mot-clé', # NEW
'lien_ajouter_participant' => 'Lägg till en deltagare',
'lien_email' => 'e-post',
'lien_forum_public' => 'Hantera den här artikelns publika forum',
'lien_mise_a_jour_syndication' => 'Uppdatera nu',
'lien_nom_site' => 'SAJTENS NAMN:',
'lien_nouvelle_recuperation' => 'Försök att hämta datum igen',
'lien_reponse_article' => 'Svara på artikeln',
'lien_reponse_breve' => 'Skriv ett svar på nyheten',
'lien_reponse_breve_2' => 'Skriv ett svar på nyheten',
'lien_reponse_rubrique' => 'Skriv ett svar till avdelningen',
'lien_reponse_site_reference' => 'Réponse au site référencé :', # NEW
'lien_retirer_auteur' => 'Ta bort redaktören',
'lien_retirer_tous_auteurs' => 'Retirer tous les auteurs', # NEW
'lien_retrait_particpant' => 'ta bort deltagaren',
'lien_site' => 'sajt',
'lien_supprimer_rubrique' => 'ta bort den här avdelningen',
'lien_tout_deplier' => 'Expandera alla',
'lien_tout_replier' => 'Kollapsa alla',
'lien_tout_supprimer' => 'Radera alla',
'lien_trier_nom' => 'Sortera efter namn',
'lien_trier_nombre_articles' => 'Sortera efter artikelnummer',
'lien_trier_statut' => 'Sortera efter status',
'lien_voir_en_ligne' => 'SE ONLINE:',
'logo_article' => 'ARTIKELNS LOGOTYPE',
'logo_auteur' => 'REDAKTÖRENS LOGOTYPE',
'logo_breve' => 'NYHETENS LOGOTYPE',
'logo_groupe' => 'LOGO DE CE GROUPE', # NEW
'logo_mot_cle' => 'NYCKELORDETS LOGOTYPE',
'logo_rubrique' => 'Avdelningens logotype',
'logo_site' => 'SAJTENS LOGOTYPE',
'logo_standard_rubrique' => 'STANDARDLOGOTYPE FÖR AVDELNINGAR',
'logo_survol' => 'LOGOTYPE FÖR MUS-ÖVER',

// M
'menu_aide_installation_choix_base' => 'Välj din databas',
'module_fichier_langue' => 'Språkfiler',
'module_raccourci' => 'Genväg',
'module_texte_affiche' => 'Visad text',
'module_texte_explicatif' => 'Vous pouvez insérer les raccourcis suivants dans les squelettes de votre site public. Ils seront automatiquement traduits dans les différentes langues pour lesquelles il existe un fichier de langue.', # NEW
'module_texte_traduction' => 'Språkfilen « @module@ » finns i:',
'mois_non_connu' => 'non connu', # NEW

// N
'nouvelle_version_spip' => 'La version @version@ de SPIP est disponible', # NEW

// O
'onglet_contenu' => 'Innehåll',
'onglet_declarer_une_autre_base' => 'Ange en annan databas',
'onglet_discuter' => 'Diskutera',
'onglet_documents' => 'Dokument',
'onglet_interactivite' => 'Interaktivitet',
'onglet_proprietes' => 'Egenskaper',
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
'plugin_etat_developpement' => 'Under utveckling',
'plugin_etat_experimental' => 'exprimentell',
'plugin_etat_stable' => 'Stabil',
'plugin_etat_test' => 'under test',
'plugin_impossible_activer' => 'Omöjligt att aktivera pluginen @plugin@',
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
'plugin_info_extension_1' => 'Les extensions ci-dessous sont chargées et activées dans le répertoire @extensions@.', # NEW
'plugin_info_extension_2' => 'Elles ne sont pas désactivables.', # NEW
'plugin_info_telecharger' => 'à télécharger depuis @url@ et à installer dans @rep@', # NEW
'plugin_librairies_installees' => 'Librairies installées', # NEW
'plugin_necessite_lib' => 'Ce plugin nécessite la librairie @lib@', # NEW
'plugin_necessite_plugin' => 'Version @version@ eller nyare av pluginen @plugin@ krävs.',
'plugin_necessite_spip' => 'Nécessite SPIP en version @version@ minimum.', # NEW
'plugin_source' => 'source: ', # NEW
'plugin_titre_automatique' => 'Installation automatique', # NEW
'plugin_titre_automatique_ajouter' => 'Ajouter des plugins', # NEW
'plugin_titre_installation' => 'Installation du plugin @plugin@', # NEW
'plugin_zip_active' => 'Fortsätt för att aktivera',
'plugin_zip_adresse' => 'indiquez ci-dessous l\'adresse d\'un fichier zip de plugin à télécharger, ou encore l\'adresse d\'une liste de plugins.', # NEW
'plugin_zip_adresse_champ' => 'Adresse du plugin ou de la liste ', # NEW
'plugin_zip_content' => 'Il contient les fichiers suivants (@taille@),<br />prêts à installer dans le répertoire <code>@rep@</code>', # NEW
'plugin_zip_installe_finie' => 'Filen @zip@ har packats upp och installerats.',
'plugin_zip_installe_rep_finie' => 'Filen @zip@ har packats upp och installerats i katalogen @rep@',
'plugin_zip_installer' => 'Du kan installera nu.',
'plugin_zip_telecharge' => 'Le fichier @zip@ a été téléchargé', # NEW
'plugins_actif_aucun' => 'Aucun plugin activé.', # NEW
'plugins_actif_un' => 'Un plugin activé.', # NEW
'plugins_actifs' => '@count@ aktiva plugins.',
'plugins_actifs_liste' => 'Plugins actifs', # NEW
'plugins_compte' => '@count@ plugins',
'plugins_disponible_un' => 'Un plugin disponible.', # NEW
'plugins_disponibles' => '@count@ tillgängliga plugins.',
'plugins_erreur' => 'Fel i följande plugin: @plugins@',
'plugins_liste' => 'Lista över plugins',
'plugins_liste_extensions' => 'Extensions', # NEW
'plugins_recents' => 'Plugins récents.', # NEW
'plugins_vue_hierarchie' => 'Hiérarchie', # NEW
'plugins_vue_liste' => 'Liste', # NEW
'protocole_ldap' => 'Version du protocole :', # NEW

// R
'repertoire_plugins' => 'Répertoire :', # NEW

// S
'sans_heure' => 'sans heure', # NEW
'sauvegarde_fusionner' => 'Fusionner la base actuelle et la sauvegarde', # NEW
'sauvegarde_fusionner_depublier' => 'Dépublier les objets fusionnés', # NEW
'sauvegarde_url_origine' => 'Eventuellement, URL du site d\'origine :', # NEW
'statut_admin_restreint' => '(admin restreint)', # NEW
'syndic_choix_moderation' => 'Que faire des prochains liens en provenance de ce site ?', # NEW
'syndic_choix_oublier' => 'Que faire des liens qui ne figurent plus dans le fichier de syndication ?', # NEW
'syndic_choix_resume' => 'Certains sites diffusent le texte complet des articles. Lorsque celui-ci est disponible souhaitez-vous syndiquer :', # NEW
'syndic_lien_obsolete' => 'Trasig länk',
'syndic_option_miroir' => 'les bloquer automatiquement', # NEW
'syndic_option_oubli' => 'les effacer (après @mois@ mois)', # NEW
'syndic_option_resume_non' => 'det fullständiga innehållet i artiklarna (i HTML-format)',
'syndic_option_resume_oui' => 'en enkel sammanfattning (i text-format)',
'syndic_options' => 'Alternativ för syndikering :',

// T
'taille_cache_image' => 'Les images calculées automatiquement par SPIP (vignettes des documents, titres présentés sous forme graphique, fonctions mathématiques au format TeX...) occupent dans le répertoire @dir@ un total de @taille@.', # NEW
'taille_cache_infinie' => 'Ce site ne prévoit pas de limitation de taille du répertoire du cache.', # NEW
'taille_cache_maxi' => 'SPIP essaie de limiter la taille du répertoire du cache de ce site à environ <b>@octets@</b> de données.', # NEW
'taille_cache_octets' => 'La taille du cache est actuellement de @octets@.', # NEW
'taille_cache_vide' => 'Cachen är tom.',
'taille_repertoire_cache' => 'Taille du répertoire cache', # NEW
'text_article_propose_publication' => 'Artikel inlämnad för publicering. tveka inte att säga din mening i forumet längst ned på sidan.', # MODIF
'text_article_propose_publication_forum' => 'N\'hésitez pas à donner votre avis grâce au forum attaché à cet article (en bas de page).', # NEW
'texte_acces_ldap_anonyme_1' => 'Certains serveurs LDAP n\'acceptent aucun accès anonyme. Dans ce cas il faut spécifier un identifiant d\'accès initial afin de pouvoir ensuite rechercher des informations dans l\'annuaire. Dans la plupart des cas néanmoins, les champs suivants pourront être laissés vides.', # NEW
'texte_admin_effacer_01' => 'Detta kommando raderar <i>all</i> information i databasen,
inklusive <i>alla</i> uppgifter om användare. Efter att du använt det, måste du 
installera om SPIP för att skapa en ny databas och den första administratören.',
'texte_admin_effacer_stats' => 'Cette commande efface toutes les données liées aux statistiques de visite du site, y compris la popularité des articles.', # NEW
'texte_admin_tech_01' => 'Cette option vous permet de sauvegarder le contenu de la base dans un fichier qui sera stocké dans le répertoire @dossier@. N\'oubliez pas également de récupérer l\'intégralité du répertoire @img@, qui contient les images et les documents utilisés dans les articles et les rubriques.', # NEW
'texte_admin_tech_02' => 'Attention: cette sauvegarde ne pourra être restaurée QUE dans un site installé sous la même version de SPIP. Il ne faut donc surtout pas « vider la base » en espérant réinstaller la sauvegarde après une mise à jour... Consultez <a href="@spipnet@">la documentation de SPIP</a>.', # MODIF
'texte_admin_tech_03' => 'Du kan välja att spara filen i komprimerad form för att 
 snabba upp överföringen till din dator eller till en backupserver och spara lite diskutrymme.',
'texte_admin_tech_04' => 'Dans un but de fusion avec une autre base, vous pouvez limiter la sauvegarde à la rubrique: ', # NEW
'texte_adresse_annuaire_1' => '( Om din katalog är installerad på samma dator som din websajt, är det troligen «localhost».)',
'texte_ajout_auteur' => 'Följande redaktör lades till artikeln:',
'texte_annuaire_ldap_1' => 'Om du har tillgång till en LDAP-katalog kan du använda den för att importera användare till SPIP.',
'texte_article_statut' => 'Artikelns status:',
'texte_article_virtuel' => 'Virtuell artikel',
'texte_article_virtuel_reference' => '<b>Virtuell Artikel:</b> länkad artikel i din SPIP site, men ompekad till en annan URL. För att ta bort ompekningen, radera denna URL.',
'texte_aucun_resultat_auteur' => 'Inga resultat för "@cherche_auteur@".',
'texte_auteur_messagerie' => 'Ce site peut vous indiquer en permanence la liste des rédacteurs connectés, ce qui vous permet d\'échanger des messages en direct. Vous pouvez décider de ne pas apparaître dans cette liste (vous êtes « invisible » pour les autres utilisateurs).', # NEW
'texte_auteur_messagerie_1' => 'Ce site permet l\'échange de messages et la constitution de forums de discussion privés entre les participants du site. Vous pouvez décider de ne pas participer à ces échanges.', # NEW
'texte_auteurs' => 'REDAKTÖRERNA',
'texte_breves' => 'Les brèves sont des textes courts et simples permettant de
	mettre en ligne rapidement des informations concises, de gérer
	une revue de presse, un calendrier d\'événements...', # NEW
'texte_choix_base_1' => 'Välj din databas:',
'texte_choix_base_2' => 'Databasservern innehåller flera databaser.',
'texte_choix_base_3' => '<b>Välj</b> den som din leverantör har gett dig:',
'texte_choix_table_prefix' => 'Prefix för tabeller:',
'texte_commande_vider_tables_indexation' => 'Utilisez cette commande afin de vider les tables d\'indexation utilisées
			par le moteur de recherche intégré à SPIP. Cela vous permettra
			de gagner de l\'espace disque.', # NEW
'texte_comment_lire_tableau' => 'Artikeln rank i
  popularitetsklassificeringen visas i 
  marginalen; artikelns popularitet (en uppskattning av
  antalet dagliga besök den skulle få om den aktuella
  trafikmängden upprätthålls) och antal besök
  sedan starten visas i bubblan som 
  dyker upp när muspekaren hålls över titeln.',
'texte_compacter_avertissement' => 'Attention à ne pas activer ces options durant le développement de votre site : les éléments compactés perdent toute lisibilité.', # NEW
'texte_compacter_script_css' => 'SPIP peut compacter les scripts javascript et les feuilles de style CSS, pour les enregistrer dans des fichiers statiques ; cela accélère l\'affichage du site.', # NEW
'texte_compatibilite_html' => 'Vous pouvez demander à SPIP de produire, sur le site public, du code compatible avec la norme <i>HTML4</i>, ou lui permettre d\'utiliser les possibilités plus modernes du <i>HTML5</i>.', # NEW
'texte_compatibilite_html_attention' => 'Il n\'y a aucun risque à activer l\'option <i>HTML5</i>, mais si vous le faites, les pages de votre site devront commencer par la mention suivante pour rester valides : <code>&lt;!DOCTYPE html&gt;</code>.', # NEW
'texte_compresse_ou_non' => '(Den kan vara komprimerad eller inte.)',
'texte_compresseur_page' => 'SPIP peut compresser automatiquement chaque page qu\'il envoie aux
visiteurs du site. Ce réglage permet d\'optimiser la bande passante (le
site est plus rapide derrière une liaison à faible débit), mais
demande plus de puissance au serveur.', # NEW
'texte_compte_element' => '@count@ objekt',
'texte_compte_elements' => '@count@ element',
'texte_config_groupe_mots_cles' => 'Souhaitez-vous activer la configuration avancée des mots-clés,
   en indiquant par exemple qu\'on peut sélectionner un mot unique
   par groupe, qu\'un groupe est important... ?', # NEW
'texte_conflit_edition_correction' => 'Veuillez contrôler ci-dessous les différences entre les deux versions du texte ; vous pouvez aussi copier vos modifications, puis recommencer.', # NEW
'texte_connexion_mysql' => 'Consult the information provided by your service provider. It should contain the connection codes for the SQL server.', # MODIF
'texte_contenu_article' => '(Contenu de l\'article en quelques mots.)', # NEW
'texte_contenu_articles' => 'Selon la maquette adoptée pour votre site, vous pouvez décider
		que certains éléments des articles ne sont pas utilisés.
		Utilisez la liste ci-dessous pour indiquer quels éléments sont disponibles.', # NEW
'texte_crash_base' => 'Si votre base de données a
			crashé, vous pouvez tenter une réparation
			automatique.', # NEW
'texte_creer_rubrique' => 'Avant de pouvoir écrire des articles,<br /> vous devez créer une rubrique.', # NEW
'texte_date_creation_article' => 'DATE DE CRÉATION DE L\'ARTICLE :', # NEW
'texte_date_publication_anterieure' => 'Date de rédaction antérieure :', # NEW
'texte_date_publication_anterieure_nonaffichee' => 'Ne pas afficher de date de rédaction antérieure.', # NEW
'texte_date_publication_article' => 'DATE DE PUBLICATION EN LIGNE :', # NEW
'texte_descriptif_petition' => 'Descriptif de la pétition', # NEW
'texte_descriptif_rapide' => 'Descriptif rapide', # NEW
'texte_documents_joints' => 'Vous pouvez autoriser l\'ajout de documents (fichiers bureautiques, images,
 multimédia, etc.) aux articles et/ou aux rubriques. Ces fichiers
 peuvent ensuite être référencés dans
 l\'article, ou affichés séparément.', # MODIF
'texte_documents_joints_2' => 'Ce réglage n\'empêche pas l\'insertion d\'images directement dans les articles.', # NEW
'texte_effacer_base' => 'Effacer la base de données SPIP', # NEW
'texte_effacer_donnees_indexation' => 'Effacer les données d\'indexation', # NEW
'texte_effacer_statistiques' => 'Effacer les statistiques', # NEW
'texte_en_cours_validation' => 'The following articles and news are submitted for publication. Do not hesitate to give your opinion through the forums attached to them.', # MODIF
'texte_en_cours_validation_forum' => 'N\'hésitez pas à donner votre avis grâce aux forums qui leur sont attachés.', # NEW
'texte_enrichir_mise_a_jour' => 'Vous pouvez enrichir la mise en page de votre texte en utilisant des « raccourcis typographiques ».', # NEW
'texte_fichier_authent' => '<b>Should SPIP create the <tt>.htpasswd</tt>
  and <tt>.htpasswd-admin</tt> files in the directory @dossier@?</b><p>
  These files can be used to restrict access to authors
  and administrators in other parts of your site
  (for instance, external statistical programme).</p><p>
  If you have no need of such files, you can leave this option
  with its default value (no files 
  creation).</p>', # MODIF
'texte_informations_personnelles_1' => 'Le système va maintenant vous créer un accès personnalisé au site.', # NEW
'texte_informations_personnelles_2' => '(Note : s\'il s\'agit d\'une réinstallation, et que votre accès marche toujours, vous pouvez', # NEW
'texte_introductif_article' => '(Texte introductif de l\'article.)', # NEW
'texte_jeu_caractere' => 'Il est conseillé d\'employer, sur votre site, l\'alphabet universel (<tt>utf-8</tt>) : celui-ci permet l\'affichage de textes dans toutes les langues, et ne pose plus de problèmes de compatibilité avec les navigateurs modernes.', # NEW
'texte_jeu_caractere_2' => 'Attention : ce réglage ne provoque pas la conversion des textes déjà enregistrés dans la base de données.', # NEW
'texte_jeu_caractere_3' => 'Din sajt använder följande teckenuppsättning:',
'texte_jeu_caractere_4' => 'Si cela ne correspond pas à la réalité de vos données (suite, par exemple, à une restauration de base de données), ou si <em>vous démarrez ce site</em> et souhaitez partir sur un autre jeu de caractères, veuillez indiquer ce dernier ici :', # NEW
'texte_jeu_caractere_conversion' => 'Note : vous pouvez décider de convertir une fois pour toutes l\'ensemble des textes de votre site (articles, brèves, forums, etc.) vers l\'alphabet <tt>utf-8</tt>, en vous rendant sur <a href="@url@">la page de conversion vers l\'utf-8</a>.', # NEW
'texte_lien_hypertexte' => '(Si votre message se réfère à un article publié sur le Web, ou à une page fournissant plus d\'informations, veuillez indiquer ci-après le titre de la page et son adresse URL.)', # NEW
'texte_liens_sites_syndiques' => 'Les liens issus des sites syndiqués peuvent
			être bloqués a priori ; le réglage
			ci-dessous indique le réglage par défaut des
			sites syndiqués après leur création. Il
			est ensuite possible, de toutes façons, de
			débloquer chaque lien individuellement, ou de
			choisir, site par site, de bloquer les liens à venir
			de tel ou tel site.', # NEW
'texte_login_ldap_1' => '(Laisser vide pour un accès anonyme, ou entrer le chemin complet, par exemple « <tt>uid=dupont, ou=users, dc=mon-domaine, dc=com</tt> ».)', # NEW
'texte_login_precaution' => 'Attention ! Ceci est le login sous lequel vous êtes connecté actuellement.
	Utilisez ce formulaire avec précaution...', # NEW
'texte_message_edit' => 'Attention : ce message peut être modifié par tous les administrateurs du site, et est visible par tous les rédacteurs. N\'utilisez les annonces que pour exposer des événements importants de la vie du site.', # NEW
'texte_messagerie_agenda' => 'Une messagerie permet aux rédacteurs du site de communiquer entre eux directement dans l’espace privé du site. Elle est associée à un agenda.', # NEW
'texte_messages_publics' => 'Messages publics de l\'article :', # NEW
'texte_mise_a_niveau_base_1' => 'Du har just uppdaterat filerna i SPIP.
 Nu måste du uppgradera sajtens databas.',
'texte_modifier_article' => 'Redigera artikeln:',
'texte_moteur_recherche_active' => '<b>Sökmotorn är aktiverad.</b> använd det här kommandot
  om du vill utföra en snabb omindexering (t.ex efter
  att ha tagit tillbaka data från en backup). Kom ihåg att dokument som skapats på
  det normala sättet (från gränssnittet i SPIP ) automatiskt
  indexeras igen: därför är kommandot bara användbart i speciella omständigheter.',
'texte_moteur_recherche_non_active' => 'Sökfunktionen är inte aktiverad.',
'texte_mots_cles' => 'Les mots-clés permettent de créer des liens thématiques entre vos articles
		indépendamment de leur placement dans des rubriques. Vous pouvez ainsi
		enrichir la navigation de votre site, voire utiliser ces propriétés
		pour personnaliser la présentation des articles dans vos squelettes.', # NEW
'texte_mots_cles_dans_forum' => 'Vill du tillåta besökare att välja nyckelord i de publika forumen? (Varning: Detta kan vara svårt att använda på rätt sätt.)',
'texte_multilinguisme' => 'Om du vill hantera artiklar på flera språk, med en avancerad navigering, kan du lägga till en option för språkval i artiklar och avdelningar, i enlighet med sajtens struktur.',
'texte_multilinguisme_trad' => 'Dessutom kan du aktivera ett system för länkhantering av de olika översättningarna av en artikel.',
'texte_non_compresse' => '<i>okomprimerad</i> (din server stöder inte denna funktion)',
'texte_non_fonction_referencement' => 'Du kan välja att inte använda den automatiska funktionen och i stället mata in information om sajetn manuellt...',
'texte_nouveau_message' => 'Nytt meddelande',
'texte_nouveau_mot' => 'Nytt nyckelord',
'texte_nouvelle_version_spip_1' => 'Du har installerat en ny version av SPIP.',
'texte_nouvelle_version_spip_2' => 'Cette nouvelle version nécessite une mise à jour plus complète qu\'à l\'accoutumée. Si vous êtes webmestre du site, veuillez effacer le fichier @connect@ et reprendre l\'installation afin de mettre à jour vos paramètres de connexion à la base de données.<p> (NB. : si vous avez oublié vos paramètres de connexion, jetez un œil au fichier @connect@ avant de le supprimer...)</p>', # MODIF
'texte_operation_echec' => 'Retournez à la page précédente, sélectionnez une autre base ou créez-en une nouvelle. Vérifiez les informations fournies par votre hébergeur.', # NEW
'texte_plus_trois_car' => 'mer än tre tecken',
'texte_plusieurs_articles' => 'Flera redaktörer hittades för  "@cherche_auteur@":',
'texte_port_annuaire' => '(La valeur indiquée par défaut convient généralement.)', # NEW
'texte_presente_plugin' => 'Cette page liste les plugins disponibles sur le site. Vous pouvez activer les plugins nécessaires en cochant la case correspondante.', # NEW
'texte_proposer_publication' => 'Lorsque votre article est terminé,<br /> vous pouvez proposer sa publication.', # NEW
'texte_proxy' => 'Dans certains cas (intranet, réseaux protégés), les sites distants (documentation de SPIP, sites syndiqués, etc.) ne sont accessibles qu\'à travers un <i>proxy HTTP</i>. Le cas échéant, indiquez ci-dessous son adresse, sous la forme @proxy_en_cours@. En général, vous laisserez cette case vide.', # NEW
'texte_publication_articles_post_dates' => 'Quel comportement SPIP doit-il adopter face aux articles dont la
		date de publication a été fixée à une
		échéance future ?', # NEW
'texte_rappel_selection_champs' => '[N\'oubliez pas de sélectionner correctement ce champ.]', # NEW
'texte_recalcul_page' => 'Om du vill uppdatera
enbart en sida, är det bäst att göra det från den publika delen genom att klicka på « Ladda om sidan ».',
'texte_recapitiule_liste_documents' => 'Cette page récapitule la liste des documents que vous avez placés dans les rubriques. Pour modifier les informations de chaque document, suivez le lien vers la page de sa rubrique.', # NEW
'texte_recuperer_base' => 'Reparera databasen',
'texte_reference_mais_redirige' => 'article référencé dans votre site SPIP, mais redirigé vers une autre URL.', # NEW
'texte_referencement_automatique' => '<b>Référencement automatisé d\'un site</b><br />Vous pouvez référencer rapidement un site Web en indiquant ci-dessous l\'adresse URL désirée, ou l\'adresse de son fichier de syndication. SPIP va récupérer automatiquement les informations concernant ce site (titre, description...).', # NEW
'texte_referencement_automatique_verifier' => 'Veuillez vérifier les informations fournies par <tt>@url@</tt> avant d\'enregistrer.', # NEW
'texte_requetes_echouent' => '<b>Lorsque certaines requêtes SQL échouent
  systématiquement et sans raison apparente, il est possible
  que ce soit à cause de la base de données
  elle-même.</b><p>
  Votre serveur SQL dispose d\'une faculté de réparation de ses
  tables lorsqu\'elles ont été endommagées par
  accident. Vous pouvez ici tenter cette réparation ; en
  cas d\'échec, conservez une copie de l\'affichage, qui contient
  peut-être des indices de ce qui ne va pas...</p><p>
  Si le problème persiste, prenez contact avec votre
  hébergeur.</p>', # MODIF
'texte_restaurer_base' => 'Återställ innehållet i databasens backup',
'texte_restaurer_sauvegarde' => 'Cette option vous permet de restaurer une sauvegarde précédemment
		effectuée de la base. A cet effet, le fichier contenant la sauvegarde doit avoir été
		placé dans le répertoire @dossier@.
		Soyez prudent avec cette fonctionnalité : <b>les modifications, pertes éventuelles, sont
		irréversibles.</b>', # NEW
'texte_sauvegarde' => 'Säkerhetskopiera innehållet i databasen',
'texte_sauvegarde_base' => 'Säkerhetskopiera databasen',
'texte_sauvegarde_compressee' => 'Säkerhetskopian kommer att sparas okomprimerad i filen @fichier@.',
'texte_selection_langue_principale' => 'Vous pouvez sélectionner ci-dessous la « langue principale » du site. Ce choix ne vous oblige - heureusement ! - pas à écrire vos articles dans la langue sélectionnée, mais permet de déterminer :
	<ul><li> le format par défaut des dates sur le site public ;</li>
	<li> la nature du moteur typographique que SPIP doit utiliser pour le rendu des textes ;</li>
	<li> la langue utilisée dans les formulaires du site public ;</li>
	<li> la langue présentée par défaut dans l\'espace privé.</li></ul>', # NEW
'texte_signification' => 'Les barres foncées représentent les entrées cumulées (total des sous-rubriques), les barres claires le nombre de visites pour chaque rubrique.', # NEW
'texte_sous_titre' => 'Undertitel',
'texte_statistiques_visites' => '(mörka staplar:  Söndag / mörk linje: genomsnittlig nivå)',
'texte_statut_attente_validation' => 'väntar på godkännande',
'texte_statut_publies' => 'publicerad online',
'texte_statut_refuses' => 'avvisad',
'texte_suppression_fichiers' => 'Använd detta kommando för att radera alla filer
i SPIP\'s cache. Det gör det möjligt att tvinga fram en uppdatering av alla sidor om du
gjort viktiga förändringar i sajtens utseende eller struktur.',
'texte_sur_titre' => 'Övertitel',
'texte_syndication' => 'If a site allows it, it is possible to retrieve automatically
		the list of its latest material. To achieve this, you must activate the syndication. 
		<blockquote><i>Some hosts disable this function; 
		in this case, you cannot use the content syndication
		from your site.</i></blockquote>', # MODIF
'texte_table_ok' => ': den här tabellen är OK.',
'texte_tables_indexation_vides' => 'Indextabellerna är tomma.',
'texte_tentative_recuperation' => 'Reparationsförsök',
'texte_tenter_reparation' => 'Försök att reparera databasen',
'texte_test_proxy' => 'Pour faire un essai de ce proxy, indiquez ici l\'adresse d\'un site Web
				que vous souhaitez tester.', # NEW
'texte_titre_02' => 'Ärenderad:',
'texte_titre_obligatoire' => '<b>Titel</b> [Krävs]',
'texte_travail_article' => '@nom_auteur_modif@ arbetade med den här artikeln för @date_diff@ minuter sedan',
'texte_travail_collaboratif' => 'S\'il est fréquent que plusieurs rédacteurs
		travaillent sur le même article, le système
		peut afficher les articles récemment « ouverts »
		afin d\'éviter les modifications simultanées.
		Cette option est désactivée par défaut
		afin d\'éviter d\'afficher des messages d\'avertissement
		intempestifs.', # NEW
'texte_trop_resultats_auteurs' => 'För många resultat för "@cherche_auteur@"; Var vänlig att smalna av frågan.',
'texte_type_urls' => 'Vous pouvez choisir ci-dessous le mode de calcul de l\'adresse des pages.', # NEW
'texte_type_urls_attention' => 'Attention ce réglage ne fonctionnera que si le fichier @htaccess@ est correctement installé à la racine du site.', # NEW
'texte_unpack' => 'téléchargement de la dernière version', # NEW
'texte_utilisation_moteur_syndiques' => 'When you use SPIP\'s integrated search engine, 
you can perform searches on sites and
 articles syndicated in two different ways. <br />- The simplest
 way is to search only in the
 titles and descriptions of the articles. <br />-
 A second, much more powerful, method allows
 SPIP to search also in the text
 of the referenced sites. If you
 reference a site, SPIP will perform
 the search in the site\'s text itself.', # MODIF
'texte_utilisation_moteur_syndiques_2' => 'Cette méthode oblige SPIP à visiter
				régulièrement les sites référencés,
				ce qui peut provoquer un léger ralentissement de votre propre
				site.', # NEW
'texte_vide' => 'tom',
'texte_vider_cache' => 'töm cachen',
'titre_admin_effacer' => 'Tekniskt underhåll',
'titre_admin_tech' => 'Tekniskt underhåll',
'titre_admin_vider' => 'Tekniskt underhåll',
'titre_ajouter_un_auteur' => 'Ajouter un auteur', # NEW
'titre_ajouter_un_mot' => 'Ajouter un mot-clé', # NEW
'titre_articles_syndiques' => 'Syndikerade artiklar från den här sajten',
'titre_breves' => 'Nyheter',
'titre_cadre_afficher_article' => 'Visa artiklarna:',
'titre_cadre_afficher_traductions' => 'Visa status för översättningen för följande språk:',
'titre_cadre_ajouter_auteur' => 'LÄGG TILL EN REDAKTÖR:',
'titre_cadre_forum_administrateur' => 'Administratörernas privata forum ',
'titre_cadre_forum_interne' => 'Internt forum',
'titre_cadre_interieur_rubrique' => 'I Avdelningen',
'titre_cadre_numero_auteur' => 'Redaktör nummer',
'titre_cadre_signature_obligatoire' => '<b>Signatur</b> [krävs]<br />',
'titre_compacter_script_css' => 'Compactage des scripts et CSS', # NEW
'titre_compresser_flux_http' => 'Compression du flux HTTP', # NEW
'titre_config_contenu_notifications' => 'Notifications', # NEW
'titre_config_contenu_prive' => 'Dans l’espace privé', # NEW
'titre_config_contenu_public' => 'Sur le site public', # NEW
'titre_config_fonctions' => 'Sajtens konfiguration',
'titre_config_forums_prive' => 'Forums de l’espace privé', # NEW
'titre_config_groupe_mots_cles' => 'Konfigurera nyckelordsgrupper',
'titre_config_langage' => 'Configurer la langue', # NEW
'titre_configuration' => 'Sajtens konfiguration',
'titre_configurer_preferences' => 'Configurer vos préférences', # NEW
'titre_conflit_edition' => 'Conflit lors de l\'édition', # NEW
'titre_connexion_ldap' => 'Optioner: <b>Din LDAP koppling</b>',
'titre_dernier_article_syndique' => 'Senaste syndikerade artiklar',
'titre_documents_joints' => 'Bifogade dokument',
'titre_evolution_visite' => 'Besöksnivå',
'titre_forum_suivi' => 'Suivi des forums', # NEW
'titre_gauche_mots_edit' => 'NYCKELORDSNUMMER:',
'titre_groupe_mots' => 'NYCKELORDSGRUPP:',
'titre_identite_site' => 'Identité du site', # NEW
'titre_langue_article' => 'ARTIKELNS SPRÅK',
'titre_langue_breve' => 'NYHETENS SPRÅK',
'titre_langue_rubrique' => 'AVDELNINGENS SPRÅK',
'titre_langue_trad_article' => 'ARTIKESPRÅK OCH ÖVERSÄTTNINGAR',
'titre_les_articles' => 'ARTIKLAR',
'titre_messagerie_agenda' => 'Messagerie et agenda', # NEW
'titre_mots_cles_dans_forum' => 'Nyckelord i de publika forumen',
'titre_mots_tous' => 'Nyckelord',
'titre_naviguer_dans_le_site' => 'Navigera i sajten...',
'titre_nouveau_groupe' => 'Ny grupp',
'titre_nouvelle_breve' => 'Ny nyhet',
'titre_nouvelle_rubrique' => 'Ny avdelning',
'titre_numero_rubrique' => 'AVDELNING NUMMER:',
'titre_page_admin_effacer' => 'Tekniskt underhåll: raderar databasen',
'titre_page_articles_edit' => 'Editera: @titre@',
'titre_page_articles_page' => 'Artiklar',
'titre_page_articles_tous' => 'Hela sajten',
'titre_page_auteurs' => 'Besökare',
'titre_page_breves' => 'Nyheter',
'titre_page_breves_edit' => 'Editera nyheten: «@titre@»',
'titre_page_calendrier' => 'Kalender @nom_mois@ @annee@',
'titre_page_config_contenu' => 'Sajtens inställningar',
'titre_page_config_fonctions' => 'Sajtens inställningare',
'titre_page_configuration' => 'Sajtens inställningar',
'titre_page_controle_petition' => 'Suivi des pétitions', # NEW
'titre_page_delete_all' => 'total och oåterkallelig radering',
'titre_page_documents_liste' => 'Bifogade dokument',
'titre_page_forum' => 'Administratörernas forum',
'titre_page_forum_envoi' => 'Sänd ett meddelande',
'titre_page_forum_suivi' => 'Suivi des forums', # NEW
'titre_page_index' => 'Din privata del',
'titre_page_message_edit' => 'Rédiger un message', # NEW
'titre_page_messagerie' => 'Votre messagerie', # NEW
'titre_page_mots_tous' => 'Mots-clés', # NEW
'titre_page_recherche' => 'Sökresultat @recherche@',
'titre_page_sites_tous' => 'Refererade webbplatser',
'titre_page_statistiques' => 'Statistik uppdelat på avdelning',
'titre_page_statistiques_messages_forum' => 'Messages de forum', # NEW
'titre_page_statistiques_referers' => 'Statistik (inkommande länkar)',
'titre_page_statistiques_signatures_jour' => 'Nombre de signatures par jour', # NEW
'titre_page_statistiques_signatures_mois' => 'Nombre de signatures par mois', # NEW
'titre_page_statistiques_visites' => 'Besöksstatistik',
'titre_page_upgrade' => 'Uppgradera SPIP',
'titre_publication_articles_post_dates' => 'Tidsstyrd publicering av artiklar',
'titre_referencement_sites' => 'Référencement de sites et syndication', # NEW
'titre_referencer_site' => 'Referera webbplatsen:',
'titre_rendez_vous' => 'MÖTEN:',
'titre_reparation' => 'Reparera',
'titre_site_numero' => 'WEBBPLATS NUMMER',
'titre_sites_proposes' => 'Les sites proposés', # NEW
'titre_sites_references_rubrique' => 'Refererade webbplatser i den här avdelningen',
'titre_sites_syndiques' => 'Les sites syndiqués', # NEW
'titre_sites_tous' => 'Refererade webbplatser',
'titre_suivi_petition' => 'Uppföljning av namninasamlingar',
'titre_syndication' => 'Syndication de sites', # NEW
'titre_type_urls' => 'Type d\'adresses URL', # NEW
'tls_ldap' => 'Transport Layer Security :', # NEW
'tout_dossier_upload' => 'Hela @upload@-katalogen',
'trad_article_inexistant' => 'Det finns ingen artikel med det här numret',
'trad_article_traduction' => 'Alla versioner av den här artikeln:',
'trad_deja_traduit' => 'Den här artikeln är i sig en översättning av den nuvarande artikeln.', # MODIF
'trad_delier' => 'Sluta länka den här artikeln till dess översättningar', # MODIF
'trad_lier' => 'Den här artikeln är en översättning av artikel nummer:',
'trad_new' => 'Skriv en ny översättning av den här artikeln', # MODIF

// U
'upload_fichier_zip' => 'ZIP fil',
'upload_fichier_zip_texte' => 'Filen du försöker installera är en ZIP-fil.',
'upload_fichier_zip_texte2' => 'Den här filen kan:',
'upload_info_mode_document' => 'Déposer cette image dans le portfolio', # NEW
'upload_info_mode_image' => 'Retirer cette image du portfolio', # NEW
'upload_limit' => 'Den här filen är för stor för servern; Den maimala storleken som kan <i>laddas upp</i> är @max@.',
'upload_zip_conserver' => 'Conserver l’archive après extraction', # NEW
'upload_zip_decompacter' => 'uppackad och alla filerna kommer ahh bli installerade på sajten. De filer som kommer att installeras är:',
'upload_zip_telquel' => 'installerad som den är, som en ZIP-fil;',
'upload_zip_titrer' => 'Lägg till titlar efter vad filerna kallas',
'utf8_convert_attendez' => 'Vänta några sekunder och ladda sedan om den här sidan.',
'utf8_convert_avertissement' => 'Du håller på att konvertera innehållet i din databas (artiklar, notiser, etc) från teckenkodningen <b>@orig@</b> till teckenkodningen <b>@charset@</b>',
'utf8_convert_backup' => 'Glöm inte bort att göra en fullständig säkerhetskopia av din webbplats. Du behöver också kontrollera att dina dokumentmallar och språkfiler är kompatibla med @charset@. Om spårning av revideringar är påslaget kommer den hur som helst att bli skadad.', # MODIF
'utf8_convert_erreur_deja' => 'Din webbplats är redan i @charset@, det är ingen mening med att konvertera.',
'utf8_convert_erreur_orig' => 'Fel: teckenkodningen @charset@ stöds inte.',
'utf8_convert_termine' => 'Klart!',
'utf8_convert_timeout' => '<b>Viktigt:</b> Om servern svarar med <i>timeout</i>, ladda om sidan tills du får meddelandet "Klart!".',
'utf8_convert_verifier' => 'Nu behöver du tömma webbplatsens cache och sedan kontrollera att allt är bra med de offentliga delarna av webbplatsen. Om du upplever allvarliga problem har en säkerhetskopia av ditt ursprungliga data (i SQL-format) placerats i @rep@-katalogen.',
'utf8_convertir_votre_site' => 'Konvertera din webbplats till utf-8',

// V
'version' => 'Version:',
'version_deplace_rubrique' => 'Déplacé de <b>« @from@ »</b> vers <b>« @to@ »</b>.', # NEW
'version_initiale' => 'Utgångsversion'
);

?>
