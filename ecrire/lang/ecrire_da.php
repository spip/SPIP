<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de https://trad.spip.net/tradlang_module/ecrire_?lang_cible=da
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'aide_non_disponible' => 'Denne del af online-hjælpen er endnu ikke tilgængelig på dansk.',
	'avis_acces_interdit' => 'Ingen adgang',
	'avis_article_modifie' => 'Advarsel, @nom_auteur_modif@ har arbejdet på denne artikel for @date_diff@ minutter siden',
	'avis_aucun_resultat' => 'Ingen resultater fundet.',
	'avis_chemin_invalide_1' => 'Den sti som du har valgt',
	'avis_chemin_invalide_2' => 'ser ikke ud til at være gyldig. Gå tilbage til sidste side og kontroller de oplysninger, du har indtastet.',
	'avis_connexion_echec_1' => 'Ingen forbindelse til SQL-serveren', # MODIF
	'avis_connexion_echec_2' => 'Gå tilbage til sidste side og kontroller de oplysninger, du har indtastet',
	'avis_connexion_echec_3' => '<b>NB:</b> På mange servere skal du <b>anmode om</b> at få åbnet adgang til en SQL-database, før du kan bruge den. Hvis du ikke kan etablere en forbindelse, så kontroller venligst at du har indgivet denne anmodning.', # MODIF
	'avis_connexion_ldap_echec_1' => 'Ingen forbindelse til LDAP-serveren',
	'avis_connexion_ldap_echec_2' => 'Gå tilbage til sidste side og kontroller de oplysninger, du har indtastet.',
	'avis_connexion_ldap_echec_3' => 'Alternativt kan du vælge ikke at benytte LDAP til at importere brugere.',
	'avis_deplacement_rubrique' => 'Advarsel! Dette afsnit indeholder @contient_breves@ nyheder@scb@: Hvis du vil flytte den, så afkryds venligst her for at bekræfte.',
	'avis_erreur_connexion_mysql' => 'Fejl i forbindelse til SQL',
	'avis_espace_interdit' => '<b>Forbudt område</b><p>SPIP er allerede installeret.', # MODIF
	'avis_lecture_noms_bases_1' => 'Installationsprogrammet kunne ikke læse navnene på de installerede databaser.',
	'avis_lecture_noms_bases_2' => 'Enten er databasen ikke tilgængelig, eller også er funktionen, som giver oversigt
		over databaser, sat ud af kraft af sikkerhedsårsager (hvilket er tilfældet på mange servere).',
	'avis_lecture_noms_bases_3' => 'Hvis det sidstnævnte er tilfældet, er det muligt at en database, som er navngivet efter dit login, kan anvendes:',
	'avis_non_acces_page' => 'Du har ikke adgang til denne side.',
	'avis_operation_echec' => 'Opgaven mislykkedes.',
	'avis_suppression_base' => 'ADVARSEL, sletning kan ikke omgøres',

	// B
	'bouton_acces_ldap' => 'Tilføj adgang til LDAP >>',
	'bouton_ajouter' => 'Tilføj',
	'bouton_demande_publication' => 'Anmod om at få offentliggjort denne artikel',
	'bouton_effacer_tout' => 'Slet alt',
	'bouton_envoyer_message' => 'Send færdig meddelelse',
	'bouton_modifier' => 'Ret',
	'bouton_radio_afficher' => 'Vis',
	'bouton_radio_apparaitre_liste_redacteurs_connectes' => 'Medtag i listen over tilknyttede redaktører',
	'bouton_radio_envoi_annonces_adresse' => 'Send nyheder til adressen:',
	'bouton_radio_envoi_liste_nouveautes' => 'Send seneste nyhedsliste',
	'bouton_radio_non_apparaitre_liste_redacteurs_connectes' => 'Medtag ikke i listen over tilknyttede redaktører',
	'bouton_radio_non_envoi_annonces_editoriales' => 'Send ingen redaktionelle nyheder',
	'bouton_redirection' => 'VIDERESTIL',
	'bouton_relancer_installation' => 'Gentag installationen',
	'bouton_suivant' => 'Næste',
	'bouton_tenter_recuperation' => 'Reparationsforsøg',
	'bouton_test_proxy' => 'Test proxy',
	'bouton_vider_cache' => 'Tøm cache',

	// C
	'calendrier_synchro' => 'Hvis du benytter en kalenderapplikation, der er kompatibel med <b>iCal</b>, kan du synkronisere med information på dette websted.',

	// D
	'date_mot_heures' => 'timer',

	// E
	'email' => 'e-mail',
	'email_2' => 'e-mail:',
	'entree_adresse_annuaire' => 'Adresse på kataloget',
	'entree_adresse_email' => 'Din e-mail-adresse',
	'entree_base_donnee_1' => 'Adresse på database',
	'entree_base_donnee_2' => '(Ofte svarer denne adresse til adressen på webstedet, undertiden er den navngivet «localhost», og undertiden skal den være blank.)',
	'entree_biographie' => 'Kort præsentation.',
	'entree_chemin_acces' => '<b>Angiv</b> stien:',
	'entree_cle_pgp' => 'Din PGP nøgle',
	'entree_contenu_rubrique' => '(Kort beskrivelse af afsnittets indhold.)',
	'entree_identifiants_connexion' => 'Dine opkoblingsinformationer...',
	'entree_informations_connexion_ldap' => 'Udfyld denne side med LDAP opkoblingsinformation. Du kan indhente oplysningerne hos din system- eller netværskadministrator.',
	'entree_infos_perso' => 'Hvem er du?',
	'entree_interieur_rubrique' => 'I afsnit:',
	'entree_liens_sites' => '<b>Hypertekst link</b> (henvisning, websted...)',
	'entree_login' => 'Dit login',
	'entree_login_connexion_1' => 'Tilkoblingslogin',
	'entree_login_connexion_2' => '(Undertiden identisk med dit FTP-login, andre gange blank)',
	'entree_mot_passe' => 'Din adgangskode',
	'entree_mot_passe_1' => 'Tilkoblingsadgangskode',
	'entree_mot_passe_2' => '(Undertiden identisk med dit FTP-login, andre gange blank)',
	'entree_nom_fichier' => 'Indtast filnavn @texte_compresse@:',
	'entree_nom_pseudo' => 'Dit navn eller alias',
	'entree_nom_pseudo_1' => '(navn eller kaldenavn)',
	'entree_nom_site' => 'Dit websteds navn',
	'entree_nouveau_passe' => 'Ny adgangskode',
	'entree_passe_ldap' => 'Adgangskode',
	'entree_port_annuaire' => 'Portnummer på kataloget',
	'entree_signature' => 'Signatur',
	'entree_titre_obligatoire' => '<b>Titel</b> [Skal oplyses]<br />',
	'entree_url' => 'Dit websteds URL',

	// I
	'ical_info1' => 'Denne side viser flere måder til at følge med i aktiviteter på dette websted.',
	'ical_info2' => 'For mere information, besøg <a href="@spipnet@">SPIP dokumentation</a>.', # MODIF
	'ical_info_calendrier' => 'To kalendere står til rådighed. Den første er en oversigt over webstedet, der viser alle offentliggjorte artikler.Den anden indeholder både redaktionelle annonceringer og dine seneste private meddelelser. Den er forbeholdt dig i kraft af en personlig nøgle, som du kan ændre når som helst ved at forny din adgangskode.',
	'ical_methode_http' => 'Filhentning',
	'ical_methode_webcal' => 'Synkronisering (webcal://)', # MODIF
	'ical_texte_prive' => 'Denne strengt personlige kalender holder dig underrettet om private redaktionelle aktiviteter på webstedet (opgaver, personlige aftaler, indsendte artikler, nyheder ...).',
	'ical_texte_public' => 'Med denne kalender kan du følge de offentlige aktiviteter på webstedet (offentliggjorte artikler og nyheder).',
	'ical_texte_rss' => 'Du kan syndikere de seneste nyheder på dette websted i en hvilken som helst XML/RSS (Rich Site Summary) fillæser. Dette format tillader også SPIP at læse de seneste nyheder offenliggjort af andre websteder i et kompatibelt udvekslingsformat.',
	'ical_titre_js' => 'Javascript',
	'ical_titre_mailing' => 'Postliste',
	'ical_titre_rss' => '«Backend» filer',
	'icone_activer_cookie' => 'Opret administrationscookie',
	'icone_afficher_auteurs' => 'Vis forfattere',
	'icone_afficher_visiteurs' => 'Vis besøgende',
	'icone_arret_discussion' => 'Stop deltagelse i denne diskussion',
	'icone_calendrier' => 'Kalender',
	'icone_creer_auteur' => 'Opret ny forfatter og tilknyt til denne artikel',
	'icone_creer_mot_cle' => 'Opret nyt nøgleord og tilknyt til denne artikel',
	'icone_creer_rubrique_2' => 'Opret nyt afsnit',
	'icone_modifier_article' => 'Ret denne artikel',
	'icone_modifier_rubrique' => 'Ret dette afsnit',
	'icone_retour' => 'Tilbage',
	'icone_retour_article' => 'Tilbage til artikel',
	'icone_supprimer_cookie' => 'Slet cookier',
	'icone_supprimer_rubrique' => 'Slet dette afsnit',
	'icone_supprimer_signature' => 'Slet denne signatur',
	'icone_valider_signature' => 'Godkend signatur',
	'image_administrer_rubrique' => 'Du kan administrere dette afsnit',
	'impossible_modifier_login_auteur' => 'Impossible de modifier le login.', # MODIF
	'impossible_modifier_pass_auteur' => 'Impossible de modifier le mot de passe.', # MODIF
	'info_1_article' => '1 artikel',
	'info_activer_cookie' => 'Du kan installere en <b>administrationscookie</b>, som tillader dig at skifte nemt mellem det offentlige websted og dit private afsnit.',
	'info_administrateur' => 'Administrator',
	'info_administrateur_1' => 'Administrator',
	'info_administrateur_2' => 'af webstedet (<i>anvend med forsigtighed</i>)',
	'info_administrateur_site_01' => 'Hvis du er webstedsadministrator, så',
	'info_administrateur_site_02' => 'klik på dette link',
	'info_administrateurs' => 'Administratorer',
	'info_administrer_rubrique' => 'Du kan administrere dette afsnit',
	'info_adresse' => 'til adressen:',
	'info_adresse_url' => 'Dit offentlige websteds URL',
	'info_aide_en_ligne' => 'SPIP online hjælp',
	'info_ajout_image' => 'Når du vedhæfter billeder til en artikel, kan
		SPIP automatisk lave miniatureudgaver af billederne.
		Dette muliggør f.eks. automatisk oprettelse af et
		galleri eller et album.',
	'info_ajouter_rubrique' => 'Tilføj endnu et afsnit at administrere:',
	'info_annonce_nouveautes' => 'Seneste annonceringer',
	'info_article' => 'artikel',
	'info_article_2' => 'artikler',
	'info_article_a_paraitre' => 'Fremdaterede artikler der skal offentliggøres',
	'info_articles_02' => 'artikler',
	'info_articles_2' => 'Artikler',
	'info_articles_auteur' => 'Denne forfatters artikler',
	'info_articles_trouves' => 'Fundne artikler',
	'info_attente_validation' => 'Dine artikler som afventer godkendelse',
	'info_aujourdhui' => 'i dag:',
	'info_auteurs' => 'Forfattere',
	'info_auteurs_par_tri' => 'Forfattere@partri@',
	'info_auteurs_trouves' => 'Forfattere fundet',
	'info_authentification_externe' => 'Ekstern adgangskontrol',
	'info_avertissement' => 'Advarsel',
	'info_base_installee' => 'Din databasestruktur er installeret.',
	'info_chapeau' => 'Hoved',
	'info_chapeau_2' => 'Indledning:',
	'info_chemin_acces_1' => 'Valgmuligheder: <b>Adgangsvej til katalog</b>',
	'info_chemin_acces_2' => 'Du skal nu konfigurere adgangsvejen til kataloginformationen. Dette er vigtigt for at kunne læse de brugerprofiler, som ligger i kataloget.',
	'info_chemin_acces_annuaire' => 'Valgmuligheder: <b>Adgangsvej til katalog</b>',
	'info_choix_base' => 'Tredje skrift:',
	'info_classement_1' => '<sup>.</sup> af @liste@',
	'info_classement_2' => '<sup>.</sup> af @liste@',
	'info_code_acces' => 'Glem ikke dine egne adgangsoplysninger!',
	'info_config_suivi' => 'Hvis denne adresse svarer til en postliste, kan du nedefor angive, hvor webstedets besøgende kan lade sig registrere. Denne adresse kan være en  URL (f.eks. siden med tilmelding til listen via web), eller en e-mail adresse med et særligt emne tilknyttet (f.eks.: <tt>@adresse_suivi@?subject=abonner</tt>):',
	'info_config_suivi_explication' => 'Du kan abonnere på dette websteds postliste. Du vil så via e-mail modtage annonceringer vedrørende artikler og nyheder, der er indsendt til offentliggørelse.',
	'info_confirmer_passe' => 'Bekræft ny adgangskode:',
	'info_connexion_base' => 'Andet skrift: <b>Forsøg på opkobling til databasen</b>',
	'info_connexion_ldap_ok' => '<b>Din LDAP-opkobling lykkedes.</b><p> Du kan gå til næste skridt.', # MODIF
	'info_connexion_mysql' => 'Første skridt: <b>Din SQL opkobling</b>',
	'info_connexion_ok' => 'Opkoblingen lykkedes.',
	'info_contact' => 'Kontakt',
	'info_contenu_articles' => 'Artiklens bestanddele',
	'info_creation_paragraphe' => '(For at lave afsnit skal du indsætte blanke linier.)', # MODIF
	'info_creation_rubrique' => 'Før du kan skrive artikler<br /> skal du lave mindst et afsnit.<br />',
	'info_creation_tables' => 'Fjerde skridt: <b>Oprettelse af databasetabeller</b>',
	'info_creer_base' => '<b>Opret</b> en ny database:',
	'info_dans_rubrique' => 'I afsnit:',
	'info_date_publication_anterieure' => 'Dato for tidligere offentliggørelse:',
	'info_date_referencement' => 'DATO FOR HENVISNING TIL DETTE WEBSTED:',
	'info_derniere_etape' => 'Sidste skridt: <b>Det er overstået!',
	'info_descriptif' => 'Beskrivelse:',
	'info_discussion_cours' => 'Igangværende diskussioner',
	'info_ecrire_article' => 'Før du kan lave artikler, skal du oprette mindst et afsnit.',
	'info_email_envoi' => 'Afsenderens e-mail adresse (valgfri)',
	'info_email_envoi_txt' => 'Indtast afsenderens e-mail adresse ved afsendelse af e-mails (som standard bruges modtagerens adresse som afsenderadresse) :',
	'info_email_webmestre' => 'E-mail-adresse på webmaster (valgfrit)', # MODIF
	'info_envoi_email_automatique' => 'Automatisk e-mail-forsendelse',
	'info_envoyer_maintenant' => 'Send nu',
	'info_etape_suivante' => 'Gå til næste trin',
	'info_etape_suivante_1' => 'Du kan gå til næste trin.',
	'info_etape_suivante_2' => 'Du kan gå til næste trin.',
	'info_exportation_base' => 'eksporter database til @archive@',
	'info_facilite_suivi_activite' => 'For at lette opfølgning på webstedets redaktionelle aktiviteter sender SPIP e-mails med anmodning om offentliggørelse og godkendelse til f.eks. redaktørens adresseliste.', # MODIF
	'info_fichiers_authent' => 'Adgangskontrolfil ".htpasswd"',
	'info_gauche_admin_tech' => '<b>Kun administratorer har adgang til denne side.</b><p> Den giver adgang til forskellige tekniske vedligeholdelsesopgaver. Nogle af dem giver anledning til en særlig adgangskontrol, der kræver FTP-adgang til siden.', # MODIF
	'info_gauche_admin_vider' => '<b>Kun administratorer har adgang til denne side.</b><p> Den giver adgang til forskellige tekniske vedligeholdelsesopgaver. Nogle af dem giver anledning til en særlig adgangskontrol, der kræver FTP-adgang til siden.', # MODIF
	'info_gauche_auteurs' => 'Her finder du alle webstedets forfattere. Status på hver enkelt fremgår af farven på ikonet (redaktør = grøn, administrator = gul).',
	'info_gauche_auteurs_exterieurs' => 'Udenforstående forfattere uden adgang til webstedet vises med et blåt symbol; slettede forfattere repræsenteres af en papirkurv.', # MODIF
	'info_gauche_messagerie' => 'Meddelelsessystemet giver mulighed for at udveksle meddelelser mellem redaktører, for at gemme huskesedler (til personlig brug) 
	eller for at vise annonceringer i det private område (hvis du er administrator).',
	'info_gauche_statistiques_referers' => 'Denne side viser en oversigt over <i>henvisende sider</i>, dvs. websteder der har linket til dit websted alene i dag. Faktisk nulstilles oversigten med 24 timers mellemrum.',
	'info_gauche_visiteurs_enregistres' => 'Her finder du de besøgende, der er tilmeldt til webstedets offentlige afsnit (fora med tilmelding).',
	'info_generation_miniatures_images' => 'Dannelse af piktogrammer',
	'info_hebergeur_desactiver_envoi_email' => 'Nogle webhoteller tillader ikke automatisk udsendelse af e-mails. I så fald kan følgende funktioner i SPIP ikke benyttes.',
	'info_hier' => 'i går:',
	'info_identification_publique' => 'Din offentlige identitet...',
	'info_image_process' => 'Vælg den bedste metode til at skabe miniaturebilleder ved at klikke på det korresponderende billede.',
	'info_informations_personnelles' => 'Femte trin: <b>Personlig information</b>',
	'info_inscription_automatique' => 'Automatisk registrering af nye redaktører',
	'info_jeu_caractere' => 'Webstedets tegnsæt',
	'info_jours' => 'dage',
	'info_laisser_champs_vides' => 'efterlad disse felter tomme)',
	'info_langues' => 'Webstedets sprog',
	'info_ldap_ok' => 'LDAP adgangskontrol er installeret.',
	'info_lien_hypertexte' => 'Hypertekst link:',
	'info_liste_redacteurs_connectes' => 'Oversigt over tilknyttede reaktører',
	'info_login_existant' => 'Dette login findes allerede.',
	'info_login_trop_court' => 'Login for kort.',
	'info_maximum' => 'maksimum:',
	'info_message_en_redaction' => 'Dine meddelelser under redaktion',
	'info_message_technique' => 'Teknisk meddelelse:',
	'info_messagerie_interne' => 'Interne meddelelser',
	'info_mise_a_niveau_base' => 'SQL databaseopgradering',
	'info_mise_a_niveau_base_2' => '{{Advarsel!}} Du har installeret en version af SPIP-filer, der er ældre end dem, der var på webstedet i forvejen. Du risikerer at miste databasen og webstedet vil ikke fungere ordentligt mere.<br />{{Geninstraller SPIP-filerne.}}',
	'info_modifier_rubrique' => 'Ret afsnit:',
	'info_modifier_titre' => 'Ret: @titre@',
	'info_mon_site_spip' => 'Mit SPIP-websted',
	'info_moyenne' => 'gennemsnit:',
	'info_multi_cet_article' => 'Denne artikel er på:',
	'info_multi_langues_choisies' => 'Vælg de sprog der skal være til rådighed for redaktører på webstedet.
  Sprog der allerede er i brug på webstedet (de øverste på listen) kan ikke fravælges.
 ',
	'info_multi_secteurs' => 'Kun for afsnit placeret i roden ?',
	'info_nom' => 'Navn',
	'info_nom_destinataire' => 'Navn på modtager',
	'info_nom_site' => 'Dit websteds navn',
	'info_nombre_articles' => '@nb_articles@ artikler,',
	'info_nombre_rubriques' => '@nb_rubriques@ afsnit',
	'info_nombre_sites' => '@nb_sites@ websteder,',
	'info_non_deplacer' => 'Flyt ikke...',
	'info_non_envoi_annonce_dernieres_nouveautes' => 'SPIP kan udsende webstedets seneste indlæg regelmæssigt.
		(nyligt offentliggjorte artikler og nyheder).',
	'info_non_envoi_liste_nouveautes' => 'Send ikke oversigt over seneste nyheder',
	'info_non_modifiable' => 'kan ikke ændres',
	'info_non_suppression_mot_cle' => 'Jeg ønsker ikke at slette dette nøgleord.',
	'info_notes' => 'Fodnoter',
	'info_nouvel_article' => 'Ny artikel',
	'info_nouvelle_traduction' => 'Ny oversættelse:',
	'info_numero_article' => 'ARTIKEL NUMMER:',
	'info_obligatoire_02' => '[Skal udfyldes]', # MODIF
	'info_options_avancees' => 'AVANCEREDE INDSTILLINGER',
	'info_ou' => 'eller...',
	'info_page_interdite' => 'Forbudt side',
	'info_par_nombre_article' => '(efter antal artiker)',
	'info_passe_trop_court' => 'Adgangskode for kort.',
	'info_passes_identiques' => 'De to adgangskoder er ikke ens.',
	'info_plus_cinq_car' => 'mere end 5 tegn',
	'info_plus_cinq_car_2' => '(Mere end 5 tegn)',
	'info_plus_trois_car' => '(Mere end 3 tegn)',
	'info_popularite' => 'popularitet: @popularite@; besøg: @visites@',
	'info_post_scriptum' => 'Efterskrift',
	'info_post_scriptum_2' => 'Efterskrift:',
	'info_pour' => 'til',
	'info_procedez_par_etape' => 'gå frem skridt for skridt',
	'info_procedure_maj_version' => 'opgraderingsprocdeduren bør følges for at tilpasse databasen til den nye version af SPIP.',
	'info_ps' => 'P.S.',
	'info_publies' => 'Dine offentliggjorte artikler',
	'info_question_inscription_nouveaux_redacteurs' => 'Vil du tillade, at nye redaktører tilmelder sig
		på det offentligt tilgængelige websted? Ja betyder, at besøgende kan tilmelde sig på en automatisk dannet formular,
		og derefter få adgang til det private område, hvor de kan vedligeholde deres egne artikler.
		<blockquote><i>Under tilmeldingen modtager brugerne en automatisk dannet e-mail med deres adgangskode til det
		private websted. Nogle webhoteller tillader ikke at der sendes e-mails fra deres servere. I så fald kan automatisk
		tilmelding ikke finde sted.', # MODIF
	'info_qui_edite' => '@nom_auteur_modif@ a travaillé sur ce contenu il y a @date_diff@ minutes', # MODIF
	'info_racine_site' => 'Top',
	'info_recharger_page' => 'Vær venlig at genindlæse denne side om et øjeblik.',
	'info_recherche_auteur_zero' => '<b>Ingen resultater fundet til "@cherche_auteur@".',
	'info_recommencer' => 'Vær venlig at forsøge igen.',
	'info_redacteur_1' => 'Redaktør',
	'info_redacteur_2' => 'med adgang til det private område (<i>anbefalet</i>)',
	'info_redacteurs' => 'Redaktører',
	'info_redaction_en_cours' => 'REDIGERING ER IGANG',
	'info_redirection' => 'Viderestilling',
	'info_refuses' => 'Dine artikler er afvist',
	'info_reglage_ldap' => 'Muligheder: <b>Konfigurere LDAP understøttelse</b>',
	'info_renvoi_article' => '<b>Viderestilling.</b> Denne artikel henviser til siden:',
	'info_reserve_admin' => 'Kun administratorer kan ændre denne adresse.',
	'info_restreindre_rubrique' => 'Begræns administrationsrettigheder til dette afsnit:',
	'info_resultat_recherche' => 'Søgeresultater:',
	'info_rubriques' => 'Afsnit',
	'info_rubriques_02' => 'afsnit',
	'info_rubriques_trouvees' => 'Afsnit fundet',
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
	'info_sous_titre' => 'Underrubrik:',
	'info_statut_administrateur' => 'Administrator',
	'info_statut_auteur' => 'Denne forfatters status:', # MODIF
	'info_statut_redacteur' => 'Redaktør',
	'info_statut_utilisateurs_1' => 'Importerede brugeres standardstatus',
	'info_statut_utilisateurs_2' => 'Vælg den status som skal tildeles personerne i LDAP kataloget, når de logger ind første gang. Senere kan du ændre værdien for hver forfatter fra sag til sag.',
	'info_suivi_activite' => 'Opfølgning på redaktionelle aktiviteter',
	'info_surtitre' => 'Hovedoverskrift:',
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
	'info_tous_les' => 'for hver:',
	'info_tout_site' => 'Hele webstedet',
	'info_tout_site2' => 'Artiklen er ikke blevet oversat til dette sprog.',
	'info_tout_site3' => 'Artiklen er blevet oversat til dette sprig, men nogle ændringer er senere blevet tilføjet til referenceartiklen. Oversættelsen skal opdateres.   ',
	'info_tout_site4' => 'Artiklen er blevet oversat til dette sprog og oversættelsen er opdateret.',
	'info_tout_site5' => 'Den oprindelige artikel.',
	'info_tout_site6' => '<b>Advarsel:</b> kun de oprindelige artikler vises.
Oversættelserne er tilknyttet den oprindelige artikel 
i en farve, der angiver deres status:',
	'info_travail_colaboratif' => 'Samarbejde om artikler',
	'info_un_article' => 'en artikel,',
	'info_un_site' => 'et websted,',
	'info_une_rubrique' => 'et afsnit,',
	'info_une_rubrique_02' => '1 afsnit',
	'info_url' => 'URL:',
	'info_urlref' => 'Hyperlink:',
	'info_utilisation_spip' => 'SPIP er nu klar til brug...',
	'info_visites_par_mois' => 'Besøg pr. måned:',
	'info_visiteur_1' => 'Besøgende',
	'info_visiteur_2' => 'på den offentligt tilgængelige websted',
	'info_visiteurs' => 'Besøgende',
	'info_visiteurs_02' => 'Besøgende på offentligt websted',
	'install_select_langue' => 'Vælg et sprog og klik derefter på knappen «næste» for at igangsætte installationen.',
	'intem_redacteur' => 'redaktør',
	'item_accepter_inscriptions' => 'Tillad tilmeldinger',
	'item_activer_messages_avertissement' => 'Tillad advarselsmeddelelser',
	'item_administrateur_2' => 'administrator',
	'item_afficher_calendrier' => 'Vis i kalenderen',
	'item_choix_administrateurs' => 'administratorer',
	'item_choix_generation_miniature' => 'Dan miniaturepiktogrammer automatisk.',
	'item_choix_non_generation_miniature' => 'Dan ikke miniaturebilleder.',
	'item_choix_redacteurs' => 'redaktører',
	'item_choix_visiteurs' => 'besøgende på den offentlige websted',
	'item_creer_fichiers_authent' => 'Dan .htpasswd filer',
	'item_login' => 'Login',
	'item_mots_cles_association_articles' => 'artiklerne',
	'item_mots_cles_association_rubriques' => 'afsnittene',
	'item_mots_cles_association_sites' => 'de linkede eller syndikerede websteder.',
	'item_non' => 'Nej',
	'item_non_accepter_inscriptions' => 'Tillad ikke tilmelding',
	'item_non_activer_messages_avertissement' => 'Ingen advarselsmeddelelser',
	'item_non_afficher_calendrier' => 'Vis ikke i kalender',
	'item_non_creer_fichiers_authent' => 'Dan ikke disse filer',
	'item_non_publier_articles' => 'Vent med at offentliggøre artikler til deres publiceringsdato.',
	'item_nouvel_auteur' => 'Ny forfatter',
	'item_nouvelle_rubrique' => 'Nyt afsnit',
	'item_oui' => 'Ja',
	'item_publier_articles' => 'Offentliggør artikler uden hensyn til deres publiceringsdato.',
	'item_reponse_article' => 'Kommenter artiklen',
	'item_visiteur' => 'besøgende',

	// J
	'jour_non_connu_nc' => ' ',

	// L
	'lien_ajouter_auteur' => 'Tilføj denne forfatter',
	'lien_email' => 'e-mail',
	'lien_nom_site' => 'WEBSTEDETS NAVN:',
	'lien_retirer_auteur' => 'Fjern forfatter',
	'lien_site' => 'websted',
	'lien_tout_deplier' => 'Udfold alle',
	'lien_tout_replier' => 'Sammenfold alle',
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

	// O
	'onglet_repartition_actuelle' => 'nu',

	// R
	'required' => '[Skal udfyldes]', # MODIF

	// S
	'statut_admin_restreint' => '(begrænset admin)', # MODIF

	// T
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
	'texte_article_virtuel_reference' => '<b>Virtuel artikel:</b> fremstår som en artikel på dit websted, men viderestiller til en anden URL. Slet URL’en for at fjerne viderestillingen.',
	'texte_aucun_resultat_auteur' => 'Ingen resultater til "@cherche_auteur@".',
	'texte_auteur_messagerie' => 'Dette websted kan løbende holde øje med, hvilke redaktører der er logget ind. Dette muliggør realtidsudveksling af meddelelser (hvis udveksling af meddelser ovenfor er fravalgt, vedligeholdes oversigten over redaktører, der er online, heller ikke). Du kan vælge ikke at være synlig i oversigten (du er så «usynlig» for andre brugere).',
	'texte_auteurs' => 'FORFATTERNE',
	'texte_choix_base_1' => 'Vælg database:',
	'texte_choix_base_2' => 'SQL server indeholder et antal databaser.',
	'texte_choix_base_3' => '<b>Vælg</b> vælg nedenfor den database, som webhotellet har tildelt dig:',
	'texte_compte_element' => '@count@ element',
	'texte_compte_elements' => '@count@ elementer',
	'texte_connexion_mysql' => 'Slå op i de oplysninger, som dit webhotel har stillet til rådighed: Hvis webhotellet understøtter SQL, bør det indeholde oplysninger om opkobling.', # MODIF
	'texte_contenu_article' => '(Artiklens indhold med få ord.)',
	'texte_contenu_articles' => 'Med udgangspunkt i det layout du har valgt til dit websted, kan du vælge at nogle artikelelementer ikke skal benyttes.
		Benyt følgende liste til at bestemme, hvilke elementer der skal være til rådighed.',
	'texte_crash_base' => 'Hvis din database er brudt ned, kan du her forsøge en automatisk genopbygning.',
	'texte_creer_rubrique' => 'Før du kan skrive artikler,<br /> skal du oprette et afsnit.',
	'texte_date_creation_article' => 'DATO FOR OPRETTELSE AF ARTIKLEN:',
	'texte_date_publication_anterieure' => 'DATO FOR TIDLIGERE OFFENTLIGGØRELSE',
	'texte_date_publication_anterieure_nonaffichee' => 'Skjul dato for tidligere offentliggørelse.',
	'texte_date_publication_article' => 'DATO FOR ONLINE OFFENTLIGGØRELSE:',
	'texte_descriptif_rapide' => 'Kort beskrivelse',
	'texte_effacer_base' => 'Slet SPIP databasen',
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
	'texte_login_ldap_1' => '(Efterlad tom for anonym adgang eller indtast en fuldstændig sti, f.eks. «<tt>uid=hansen, ou=brugere, dc=mit-domæne, dc=dk</tt>».)',
	'texte_login_precaution' => 'Advarsel! Dette er den login, du er koblet på med nu.
	Brug denne formular med forsigtighed ...',
	'texte_mise_a_niveau_base_1' => 'Du har netop opdateret SPIP’s filer.
	Du skal nu opdatere webstedets database.',
	'texte_modifier_article' => 'Ret artiklen:',
	'texte_multilinguisme' => 'Hvis du ønsker at administrere artikler på flere sprog med den deraf følgende større kompleksitet, kan du forsyne afsnit og/eller artikler med en sprogvalgsmenu. Denne funktion er afhængig af strukturen på websiden.', # MODIF
	'texte_multilinguisme_trad' => 'Du kan også vælge at have link mellem de forskellige sprogversioner af en artikel.', # MODIF
	'texte_non_compresse' => '<i>ukomprimeret</i> (din server understøtter ikke denne funktion)',
	'texte_nouvelle_version_spip_1' => 'Du har netop installeret en ny version af SPIP.',
	'texte_nouvelle_version_spip_2' => 'Denne nye version kræver en mere omfattende opdatering end sædvanligt. Hvis du er webmaster på webstedet, så slet venligst filen <tt>inc_connect.php3</tt> i kataloget <tt>ecrire</tt> og genstart installationen for at opdatere dine opkoblingsparametre til databasen. <p>(NB.: hvis du har glemt dine opkoblingsparametre, så kast et blik på indholdet af filen <tt>inc_connect.php3</tt> før du sletter den...)', # MODIF
	'texte_operation_echec' => 'Gå tilbage til forrige side og vælg en anden database eller opret en ny. Kontroller de oplysninger, dit webhotel har stillet til rådighed.',
	'texte_plus_trois_car' => 'mere end 3 tegn',
	'texte_plusieurs_articles' => 'Der er fundet flere forfattere til "@cherche_auteur@":',
	'texte_port_annuaire' => '(Standardværdien passer for det meste.)',
	'texte_proposer_publication' => 'Når din artikel er færdig,<br /> kan du indsende den til offentliggørelse.',
	'texte_proxy' => 'I nogle tilfælde (intranet, beskyttede netværk...),
		er det nødvendigt at benytte en <i>proxy HTTP</i> for at komme i kontakt med syndikerede websteder.
		Hvis der skal benyttes proxy, så indtast dens adresse her: 
		<tt><html>http://proxy:8080</html></tt>. Almindeligvis skal feltet stå tomt.',
	'texte_publication_articles_post_dates' => 'Hvad skal SPIP gøre med hensyn til artikler med en offentliggørelsesdato, der ligger ude i 
		fremtiden?',
	'texte_rappel_selection_champs' => '[Husk at vælge dette felt korrekt.]',
	'texte_recalcul_page' => 'Hvis du kun ønsker at opdatere en side, bør du gøre det ved fra det offentlige område at benytte knappen « Opdater ».',
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
	'texte_suppression_fichiers' => 'Brug denne kommando til at slette alle filer i SPIP’s cache.
		Dette giver dig bl.a. mulighed for at gennemtvinge opdatering af alle sider i tilfælde af 
		at du har lavet væsentlige grafiske eller strukturelle ændringer på webstedet.',
	'texte_sur_titre' => 'Hovedoverskrift',
	'texte_table_ok' => ': denne tabel er OK.',
	'texte_tentative_recuperation' => 'Reparationsforsøg',
	'texte_tenter_reparation' => 'Forsøg på at reparere databasen',
	'texte_test_proxy' => 'For at afprøve proxy’en, kan du indtaste adressen på et websted som du ønsker at teste.',
	'texte_titre_02' => 'Emne:',
	'texte_titre_obligatoire' => '<b>Overskrift</b> [Obligatorisk]',
	'texte_travail_article' => '@nom_auteur_modif@ har arbejdet på denne artikel for @date_diff@ minutter siden',
	'texte_travail_collaboratif' => 'Hvis det sker hyppigt at flere redaktører arbejder på samme artikel, kan systemet
		vise «åbne» artikler for at undgå samtidige ændringer. Denne indstilling er som standard
		slået fra for at undgå utidige advarselsmeddelelser.',
	'texte_vide' => 'tom',
	'texte_vider_cache' => 'Tøm cachen',
	'titre_admin_tech' => 'Teknisk vedligeholdelse',
	'titre_admin_vider' => 'Teknisk vedligeholdelse',
	'titre_cadre_afficher_article' => 'Vis artikler som er',
	'titre_cadre_afficher_traductions' => 'Vis oversættelsesstatus for følgende sprog:',
	'titre_cadre_ajouter_auteur' => 'TILFØJ FORFATTER:',
	'titre_cadre_interieur_rubrique' => 'I afsnit',
	'titre_cadre_numero_auteur' => 'FORFATTER NUMMER',
	'titre_cadre_signature_obligatoire' => '<b>Underskrift</b> [Obligatorisk]<br />',
	'titre_config_fonctions' => 'Konfigurering af webstedet',
	'titre_configuration' => 'Konfigurering af webstedet',
	'titre_connexion_ldap' => 'Indstillinger: <b>Din LDAP forbindelse</b>',
	'titre_groupe_mots' => 'NØGLEORDSGRUPPE:',
	'titre_langue_article' => 'ARTIKLENS SPROG', # MODIF
	'titre_langue_rubrique' => 'SPROGAFSNIT', # MODIF
	'titre_langue_trad_article' => 'ARTIKLENS SPROG OG OVERSÆTTELSER',
	'titre_les_articles' => 'ARTIKLER',
	'titre_naviguer_dans_le_site' => 'Gennemse webstedet...',
	'titre_nouvelle_rubrique' => 'Nyt afsnit',
	'titre_numero_rubrique' => 'AFSNITSNUMMER:',
	'titre_page_articles_edit' => 'Ret: @titre@',
	'titre_page_articles_page' => 'Artikler',
	'titre_page_articles_tous' => 'Hele webstedet',
	'titre_page_calendrier' => 'Kalender @nom_mois@ @annee@',
	'titre_page_config_contenu' => 'Webstedskonfigurering',
	'titre_page_delete_all' => 'total og uigenkaldelig sletning',
	'titre_page_recherche' => 'Søgeresultater @recherche@',
	'titre_page_statistiques_referers' => 'Statistik (indkommende links)',
	'titre_page_upgrade' => 'SPIP opgradering',
	'titre_publication_articles_post_dates' => 'Offentliggørelse af fremdaterede artikler',
	'titre_reparation' => 'Reparer',
	'titre_suivi_petition' => 'Opfølgning på appeller',
	'trad_article_traduction' => 'Alle udgaver af denne artikel :',
	'trad_delier' => 'Afbryd forbindelsen mellem denne artikel og oversættelserne', # MODIF
	'trad_lier' => 'Denne artikel er en oversættelse af artikel nummer :',
	'trad_new' => 'Lav en ny oversættelse af denne artikel' # MODIF
);
