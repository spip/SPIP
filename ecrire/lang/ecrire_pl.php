<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://www.spip.net/trad-lang/
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

// A
'activer_plugin' => 'Włącz rozszerzenie',
'affichage' => 'Wyświetlanie',
'aide_non_disponible' => 'Ta część pomocy on-line nie jest jeszcze dostępna w tym języku.',
'auteur' => 'Autor :',
'avis_acces_interdit' => 'Dostęp zabroniony.',
'avis_article_modifie' => 'Uwaga, @nom_auteur_modif@ pracował nad tym artykułem @date_diff@ minut temu.',
'avis_aucun_resultat' => 'Brak wyników szukania.',
'avis_base_inaccessible' => 'Impossible de se connecter à la base de données @base@.', # NEW
'avis_chemin_invalide_1' => 'Ścieżka, którą wybrałeś',
'avis_chemin_invalide_2' => 'wydaje się nieprawidłowa. Proszę powrócić na poprzednią stronę w celu weryfikacji informacji.',
'avis_connexion_echec_1' => 'Połączenie z serwerem SQL nie powiodło się.', # MODIF
'avis_connexion_echec_2' => 'Powróć do poprzedniej strony i zweryfikuj podane informacje.',
'avis_connexion_echec_3' => '<b>Uwaga</b> W przypadku wielu serwerów musisz <b>poprosić</b> o uaktywnienie dostępu do bazy danych, aby móc z niej korzystać. Jeśli nie możesz się połączyć, upewnij się, czy poprosiłeś o dostęp.', # MODIF
'avis_connexion_ldap_echec_1' => 'Połączenie z LDAP nie powiodło się.',
'avis_connexion_ldap_echec_2' => 'Powróć do poprzedniej strony i zweryfikuj podane informacje.',
'avis_connexion_ldap_echec_3' => 'Nie używaj wsparcia LDAP do importu użytkowników.',
'avis_conseil_selection_mot_cle' => '<b>Ważna grupa:</b> Zaleca się wybór słowa kluczowego dla tej grupy.',
'avis_deplacement_rubrique' => 'Uwaga! Ten dział zawiera @contient_breves@ news@scb@: jeśli chcesz go przenieść, proszę zaznacz to okienko.',
'avis_destinataire_obligatoire' => 'Aby wysłać tę wiadomość, musisz wybrać odbiorcę.',
'avis_doublon_mot_cle' => 'Un mot existe deja avec ce titre. Êtes vous sûr de vouloir créer le même ?', # NEW
'avis_erreur_connexion_mysql' => 'Błąd połączenia z SQL', # MODIF
'avis_erreur_version_archive' => '<b>Ostrzeżenie! Plik @archive@ odpowiada
    wersji SPIP innej, niż
    zainstalowana przez Ciebie.</b> Wiąże się to z poważnymi
    trudnościami: ryzykiem zniszczenia Twojej bazy danych,
    zakłóceniami funkcjonowania Twojej strony, etc. Nie
    spełniaj żądania importu.<p>Więcej
    informacji na <a href="@spipnet@">
                                 w dokumentacji SPIP</a>.', # MODIF
'avis_espace_interdit' => '<b>Dostęp zabroniony</b><div>SPIP jest już zainstalowany.</div>',
'avis_lecture_noms_bases_1' => 'Program instalacyjny nie może odczytać nazw instalowanych baz danych.',
'avis_lecture_noms_bases_2' => 'Żadna baza danych nie jest dostępna, lub funkcja listingu baz danych nie działa
   ze względów bezpieczeństwa(co jest częstym przypadkiem wielu hostów).',
'avis_lecture_noms_bases_3' => 'W drugim przypadku, do użytku może nadawać się baza danych nazwana Twoim loginem :',
'avis_non_acces_message' => 'Nie masz dostępu do tej wiadomości.',
'avis_non_acces_page' => 'Nie masz dostępu do tej strony.',
'avis_operation_echec' => 'Operacja nie powiodła się.',
'avis_operation_impossible' => 'Operacja niemożliwa',
'avis_probleme_archive' => 'Istnieje błąd w pliku @archive@',
'avis_site_introuvable' => 'Strony nie znaleziono',
'avis_site_syndique_probleme' => 'Uwaga : syndykacja strony napotkała na problem ; system został na chwilę wstrzymany. Sprawdź URL syndykowanej strony (<b>@url_syndic@</b>), i spróbuj powtórnie pozyskać informacje.', # MODIF
'avis_sites_probleme_syndication' => 'Te strony mają problem z syndykacją',
'avis_sites_syndiques_probleme' => 'Następujące strony syndykowane sprawiają problem',
'avis_suppression_base' => 'OSTRZEŻENIE, usunięcie danych jest nieodwracalne',
'avis_version_mysql' => 'Twoja wersja SQL (@version_mysql@) nie umożliwia auto-naprawy tablic baz danych.', # MODIF

// B
'bouton_acces_ldap' => 'Dodaj dostęp do LDAP',
'bouton_ajouter' => 'Dodaj',
'bouton_ajouter_participant' => 'DODAJ UCZESTNIKA:',
'bouton_annonce' => 'ZAWIADOMIENIE',
'bouton_annuler' => 'Anuluj',
'bouton_checkbox_envoi_message' => 'możliwość wysłania wiadomości',
'bouton_checkbox_indiquer_site' => 'Musisz wpisać nazwę strony internetowej',
'bouton_checkbox_qui_attribue_mot_cle_administrateurs' => 'administratorzy strony',
'bouton_checkbox_qui_attribue_mot_cle_redacteurs' => 'redaktorzy',
'bouton_checkbox_qui_attribue_mot_cle_visiteurs' => 'odwiedzający publiczną stronę, kiedy umieszczają wiadomość na forum.',
'bouton_checkbox_signature_unique_email' => 'tylko jeden podpis na adres e-mail',
'bouton_checkbox_signature_unique_site' => 'tylko jeden podpis na stronę internetową',
'bouton_demande_publication' => 'Prośba o publikację artykułu',
'bouton_desactive_tout' => 'Wyłącz wszystko',
'bouton_desinstaller' => 'Odinstaluj',
'bouton_effacer_index' => 'Usuń indeksowanie',
'bouton_effacer_statistiques' => 'Effacer les statistiques', # NEW
'bouton_effacer_tout' => 'Usuń WSZYSTKO',
'bouton_envoi_message_02' => 'WYŚLIJ WIADOMOŚĆ',
'bouton_envoyer_message' => 'Wiadomość końcowa: wysłano',
'bouton_fermer' => 'Fermer', # NEW
'bouton_forum_petition' => 'FORUM &amp; OGŁOSZENIA', # MODIF
'bouton_mettre_a_jour_base' => 'Mettre à jour la base de données', # NEW
'bouton_modifier' => 'Modyfikuj',
'bouton_pense_bete' => 'NOTATKA OSOBISTA',
'bouton_radio_activer_messagerie' => 'Aktywuj pocztę wewnętrzną',
'bouton_radio_activer_messagerie_interne' => 'Aktywuj pocztę wewnętrzną',
'bouton_radio_activer_petition' => 'Aktywacja ogłoszeń',
'bouton_radio_afficher' => 'Pokaż',
'bouton_radio_apparaitre_liste_redacteurs_connectes' => 'Pojawić się na liście zalogowanych redaktorów',
'bouton_radio_articles_futurs' => 'wyłącznie do przyszłych artykułów (brak działania na bazie danych).',
'bouton_radio_articles_tous' => 'do wszystkich artykułów bez wyjątków.',
'bouton_radio_articles_tous_sauf_forum_desactive' => 'do wszystkich artykułów z wyjątkiem tych z nieczynnym forum.',
'bouton_radio_desactiver_messagerie' => 'Dezaktywuj pocztę',
'bouton_radio_enregistrement_obligatoire' => 'Obowiązkowa rejestracja (
  użytkownicy muszą się zapisać przez podanie adresu e-mail
  aby mieć możliwość zamieszczania postów).',
'bouton_radio_envoi_annonces_adresse' => 'Wysyłaj ogłoszenia na adres :',
'bouton_radio_envoi_liste_nouveautes' => 'Wysyłaj najnowszą listę newsów',
'bouton_radio_moderation_priori' => 'Uprzednia moderacja (
 posty będą się ukazywać dopiero po ich zatwierdzeniu przez
 administratorów).',
'bouton_radio_modere_abonnement' => 'na abonament',
'bouton_radio_modere_posteriori' => 'moderacja a posteriori',
'bouton_radio_modere_priori' => 'moderacja a priori',
'bouton_radio_non_apparaitre_liste_redacteurs_connectes' => 'Nie pokazuj w liście redaktorów',
'bouton_radio_non_envoi_annonces_editoriales' => 'Nie wysyłaj żadnych zawiadomień redakcyjnych',
'bouton_radio_non_syndication' => 'Bez syndykacji',
'bouton_radio_pas_petition' => 'Wyłącz ogłoszenia',
'bouton_radio_petition_activee' => 'Ogłoszenia włączone',
'bouton_radio_publication_immediate' => 'Natychmiastowa publikacja wiadomości
 (posty będą się ukazywać w momencie ich wysłania, administratorzy mogą
 je później usunąć).',
'bouton_radio_sauvegarde_compressee' => 'zapisz w postaci skompresowanej w @fichier@',
'bouton_radio_sauvegarde_non_compressee' => 'zapisz w postaci nieskompresowanej w @fichier@',
'bouton_radio_supprimer_petition' => 'Usuń ogłoszenia',
'bouton_radio_syndication' => 'Syndykacja:',
'bouton_redirection' => 'PRZEKIERUJ',
'bouton_relancer_installation' => 'Uruchom ponownie instalację',
'bouton_restaurer_base' => 'Przywróć bazę danych',
'bouton_suivant' => 'Następny',
'bouton_tenter_recuperation' => 'Próba naprawy',
'bouton_test_proxy' => 'Test proxy',
'bouton_vider_cache' => 'Opróżnij cache',
'bouton_voir_message' => 'Podgląd wiadomości przed zatwierdzeniem',

// C
'cache_mode_compresse' => 'Pliki cache zostały zapisane w postaci skompresowanej.',
'cache_mode_non_compresse' => 'Pliki cache zostały zapisane w trybie nieskompresowanym',
'cache_modifiable_webmestre' => 'Te parametry może zmieniać webmaster serwisu.',
'calendrier_synchro' => 'Jeśli używasz programów - terminarzy kompatybinych z <b>iCal</b>, możesz go zsynchronizować z informacjami tego serwisu.',
'config_activer_champs' => 'Włącz następujące pola',
'config_choix_base_sup' => 'indiquer une base sur ce serveur', # NEW
'config_erreur_base_sup' => 'SPIP n\'a pas accès à la liste des bases accessibles', # NEW
'config_info_base_sup' => 'Si vous avez d\'autres bases de données à interroger à travers SPIP, avec son serveur SQL ou avec un autre, le formulaire ci-dessous, vous permet de les déclarer. Si vous laissez certains champs vides, les identifiants de connexion à la base principale seront utilisés.', # NEW
'config_info_base_sup_disponibles' => 'Bases supplémentaires déjà interrogeables:', # NEW
'config_info_enregistree' => 'La nouvelle configuration a été enregistrée', # NEW
'config_info_logos' => 'Każdy element strony może mieć logo, a także logo roll-over.',
'config_info_logos_utiliser' => 'Użyj logo',
'config_info_logos_utiliser_non' => 'Nie używaj logo',
'config_info_logos_utiliser_survol' => 'Używaj logo roll-over',
'config_info_logos_utiliser_survol_non' => 'Nie używaj logo roll-over',
'config_info_redirection' => 'Włączając tą opcję, możesz tworzyć artykuły wirtualne, odnoszące się do artykułów opublikowanych na innych stronach niż Twoja.',
'config_redirection' => 'Wirtualne artykuły',
'config_titre_base_sup' => 'Déclaration d\'une base supplémentaire', # NEW
'config_titre_base_sup_choix' => 'Choisissez une base supplémentaire', # NEW
'connexion_ldap' => 'Połączenie:',
'copier_en_local' => 'Skopiuj lokalnie',

// D
'date_mot_heures' => 'godz.',
'diff_para_ajoute' => 'Dodany akapit',
'diff_para_deplace' => 'Akapit przeniesiony',
'diff_para_supprime' => 'Akapit usunięty',
'diff_texte_ajoute' => 'Dodany tekst',
'diff_texte_deplace' => 'Przeniesiony tekst',
'diff_texte_supprime' => 'Usunięty tekst',
'double_clic_inserer_doc' => 'Kliknij dwa razy aby umieścić skrót w tekście',

// E
'email' => 'e-mail',
'email_2' => 'e-mail:',
'en_savoir_plus' => 'En savoir plus', # NEW
'entree_adresse_annuaire' => 'Adres katalogu',
'entree_adresse_email' => 'Twój adres e-mail',
'entree_adresse_fichier_syndication' => 'Adres pliku syndykacji:',
'entree_adresse_site' => '<b>URL strony</b> [obowiązkowo]',
'entree_base_donnee_1' => 'Adres bazy danych',
'entree_base_donnee_2' => '(Często adres ten jest taki sam, jak adres strony, czasem odpowiada nazwie «serwera lokalnego», a czasem jest pozostawiony pusty.)',
'entree_biographie' => 'Krótka biografia w kilku słowach.',
'entree_breve_publiee' => 'Czy ten news ma zostać opublikowany?',
'entree_chemin_acces' => '<b>Podaj</b> ścieżkę dostępu:',
'entree_cle_pgp' => 'Twój klucz PGP',
'entree_contenu_rubrique' => '(Tematyka działu w kilku słowach.)',
'entree_description_site' => 'Opis strony',
'entree_identifiants_connexion' => 'Identyfikatory połączenia...',
'entree_informations_connexion_ldap' => 'Proszę w ten formularz wpisać informacje o połączeniu LDAP. Uzyskać je można od administratora systemu lub sieci.',
'entree_infos_perso' => 'Kim jesteś?',
'entree_interieur_rubrique' => 'W dziale:',
'entree_liens_sites' => '<b>Łącza hipertekstowe</b> (referencje, strony do odwiedzenia...)',
'entree_login' => 'Twój login',
'entree_login_connexion_1' => 'Login połączenia',
'entree_login_connexion_2' => '(Czasem jest taki, jak Twój login FTP, a czasem jest pozostawiony pusty)',
'entree_login_ldap' => 'Zaloguj do LDAP',
'entree_mot_passe' => 'Twoje hasło',
'entree_mot_passe_1' => 'Hasło połączenia',
'entree_mot_passe_2' => '(Czasem jest takie, jak Twoje hasło dostępu FTP, a czasem jest pozostawione puste)',
'entree_nom_fichier' => 'Proszę wpisać nazwę pliku @texte_compresse@:',
'entree_nom_pseudo' => 'Twoje imię lub alias',
'entree_nom_pseudo_1' => '(Twoje imię lub alias)',
'entree_nom_site' => 'Nazwa Twojej strony',
'entree_nouveau_passe' => 'Nowe hasło',
'entree_passe_ldap' => 'Hasło',
'entree_port_annuaire' => 'Numer portu katalogu',
'entree_signature' => 'Podpis',
'entree_texte_breve' => 'Tekst newsa',
'entree_titre_obligatoire' => '<b>Tytuł</b> [Obowiązkowo]<br />',
'entree_url' => 'URL Twojej strony',
'erreur_connect_deja_existant' => 'Un serveur existe déjà avec ce nom', # NEW
'erreur_nom_connect_incorrect' => 'Ce nom de serveur n\'est pas autorisé', # NEW
'erreur_plugin_desinstalation_echouee' => 'La désinstallation du plugin a echoué. Vous pouvez néanmoins le desactiver.', # NEW
'erreur_plugin_fichier_absent' => 'Brak pliku',
'erreur_plugin_fichier_def_absent' => 'Brak pliku definicji',
'erreur_plugin_nom_fonction_interdit' => 'Nieprawidłowa nazwa funkcji',
'erreur_plugin_nom_manquant' => 'Brak nazwy rozszerzenia',
'erreur_plugin_prefix_manquant' => 'Nie zdefiniowana nazwa rozszerzenia',
'erreur_plugin_tag_plugin_absent' => '&lt;plugin&gt; nie ma w pliku definicji',
'erreur_plugin_version_manquant' => 'Brakuje wersji rozszerzenia',

// F
'forum_info_original' => 'oryginalny',

// H
'htaccess_a_simuler' => 'Avertissement: la configuration de votre serveur HTTP ne tient pas compte des fichiers @htaccess@. Pour pouvoir assurer une bonne sécurité, il faut que vous modifiez cette configuration sur ce point, ou bien que les constantes @constantes@ (définissables dans le fichier mes_options.php) aient comme valeur des répertoires en dehors de @document_root@.', # MODIF
'htaccess_inoperant' => 'htaccess inopérant', # NEW

// I
'ical_info1' => 'Na tej stronie prezentujemy różne metody pozostanie w kontakcie z działalnością serwisu.',
'ical_info2' => 'Aby przeczytać więcej na ten temat, idź na stronę<a href="@spipnet@">la documentation de SPIP</a>.', # MODIF
'ical_info_calendrier' => 'Do Twojej dyspozycji są dwa kalendarze. Jeden pokazuje mapę strony wraz ze wszystkimi opublikowanymi artykułami. Drugi zawiera ogłoszenia redakcyjne oraz Twoje ostatni wiadomości prywatne : tylko Ty masz do niego dostęp za hasłem.',
'ical_lien_rss_breves' => 'Zapisz się do RSS newsów',
'ical_methode_http' => 'Ściągnij',
'ical_methode_webcal' => 'Synchronizacja (webcal://)',
'ical_texte_js' => 'Jedna linia javascript pozwala w bardzo prosty sposób, w każdym należącym do Ciebie serwisie pokazać ostatnio opublikowane w tym serwisie artykuły.',
'ical_texte_prive' => 'Ten kalendarz, wyłącznie do użytku osobistego, informuje Cię o działalności redakcyjnej w strefie prywatnej (zadania, spotkania, proponowane artykuły i newsy).',
'ical_texte_public' => 'Ten kalendarz pozawala śledzić aktywność redakcyjną serwisu (opublikowane artykuły i newsy).',
'ical_texte_rss' => 'Możesz syndykować nowości z tego serwisu w jakimkolwiek czytniku plików formatu XML/RSS (Rich Site Summary). Ten format pozwala SPIP odczytywać nowości opublikowane w innych serwisach (serwisach syndykowanych).',
'ical_titre_js' => 'Javascript',
'ical_titre_mailing' => 'Lista Mailowa',
'ical_titre_rss' => 'Pliki syndykacji',
'icone_accueil' => 'Accueil', # NEW
'icone_activer_cookie' => 'Aktywuj cookies korespondencji',
'icone_activite' => 'Activité', # NEW
'icone_admin_plugin' => 'Zarządzanie rozszerzeniami',
'icone_administration' => 'Maintenance', # NEW
'icone_afficher_auteurs' => 'Pokaż autorów',
'icone_afficher_visiteurs' => 'Pokaż odwiedzających',
'icone_arret_discussion' => 'Zaprzestań udziału w tej dyskusji',
'icone_calendrier' => 'Kalendarz',
'icone_configuration' => 'Configuration', # NEW
'icone_creation_groupe_mots' => 'Utwórz nową grupę słów kluczowych',
'icone_creation_mots_cles' => 'Utwórz nowe słowo kluczowe',
'icone_creer_auteur' => 'Stwórz nowego autora i przypisz go do tego artykułu',
'icone_creer_mot_cle' => 'Stwórz nowe słowo kluczowe i skojarz je z tym artykułem',
'icone_creer_mot_cle_breve' => 'Utwórz nowe słowo kluczowe i połącz je z tym newsem',
'icone_creer_mot_cle_rubrique' => 'Utwórz nowe słowo kluczowe i połącz je z tym działem',
'icone_creer_mot_cle_site' => 'Utwórz nowe słowo kluczowe i połącz je z tym linkiem ',
'icone_creer_rubrique_2' => 'Utwórz nowy dział',
'icone_ecrire_nouvel_article' => 'Newsy w tym dziale',
'icone_edition' => 'Édition', # NEW
'icone_envoyer_message' => 'Wyślij tę wiadomość',
'icone_evolution_visites' => 'Rozwój wizyt<br />@visites@ wizyt',
'icone_ma_langue' => 'Ma langue', # NEW
'icone_mes_infos' => 'Mes informations', # NEW
'icone_mes_preferences' => 'Mes préférences', # NEW
'icone_modif_groupe_mots' => 'Zmień tę grupę słów kluczowych',
'icone_modifier_article' => 'Zmień ten artykuł',
'icone_modifier_breve' => 'Zmień ten news',
'icone_modifier_message' => 'Zmień tę wiadomość',
'icone_modifier_mot' => 'Modifier ce mot-clé', # NEW
'icone_modifier_rubrique' => 'Zmień ten dział',
'icone_modifier_site' => 'Zmień tę stronę',
'icone_poster_message' => 'Napisz wiadomość',
'icone_publication' => 'Publication', # NEW
'icone_publier_breve' => 'Publikuj ten news',
'icone_referencer_nouveau_site' => 'Nowy link do strony',
'icone_refuser_breve' => 'Odrzuć ten news',
'icone_relancer_signataire' => 'Relancer le signataire', # NEW
'icone_retour' => 'Powrót',
'icone_retour_article' => 'Powrót do artykułu',
'icone_squelette' => 'Squelettes', # NEW
'icone_suivi_forum' => 'Śledź wątek forum publicznego: @nb_forums@',
'icone_suivi_publication' => 'Suivi de la publication', # NEW
'icone_supprimer_cookie' => 'Usuń cookies korespondencji',
'icone_supprimer_groupe_mots' => 'Usuń tę grupę',
'icone_supprimer_rubrique' => 'Usuń ten dział',
'icone_supprimer_signature' => 'Usuń ten podpis',
'icone_valider_signature' => 'Zatwierdź ten podpis',
'icone_voir_sites_references' => 'Pokaż zlinkowane strony',
'icone_voir_tous_mots_cles' => 'Pokaż wszystkie słowa kluczowe',
'image_administrer_rubrique' => 'Możesz zarządzać tym działem',
'info_1_article' => '1 artykuł',
'info_1_article_syndique' => '1 article syndiqué', # NEW
'info_1_auteur' => '1 auteur', # NEW
'info_1_breve' => '1 news',
'info_1_message' => '1 message', # NEW
'info_1_mot_cle' => '1 mot-clé', # NEW
'info_1_rubrique' => '1 rubrique', # NEW
'info_1_site' => '1 strona',
'info_1_visiteur' => '1 visiteur', # NEW
'info_activer_cookie' => 'Możesz uaktywnić <b>cookies korespondecji</b>, co ci
 pozwoli przechodzić łatwo pomiędzy stroną publiczną a prywatną.',
'info_activer_forum_public' => '<i>Aby aktywować forum publiczne, wybierz domyślny sposób moderacji</i>',
'info_admin_etre_webmestre' => 'Me donner les droits de webmestre', # NEW
'info_admin_gere_rubriques' => 'Ten administrator zarządza następującymi działami:',
'info_admin_gere_toutes_rubriques' => 'Ten administrator zarządza <b>wszystkimi działami</b>.',
'info_admin_je_suis_webmestre' => 'Je suis <b>webmestre</b>', # NEW
'info_admin_statuer_webmestre' => 'Donner à cet administrateur les droits de webmestre', # NEW
'info_admin_webmestre' => 'Cet administrateur est <b>webmestre</b>', # NEW
'info_administrateur' => 'Administrator',
'info_administrateur_1' => 'Administrator',
'info_administrateur_2' => 'strony (<i>korzystaj uważnie</i>)',
'info_administrateur_site_01' => 'Jeśli jesteś administratorem strony, proszę',
'info_administrateur_site_02' => 'kliknij na ten link',
'info_administrateurs' => 'Administratorzy',
'info_administrer_rubrique' => 'Możesz zarządzać tym działem',
'info_adresse' => 'na adres:',
'info_adresse_email' => 'ADRES E-MAIL:',
'info_adresse_url' => 'URL Twojej publicznej strony',
'info_afficher_visites' => 'Pokaż odwiedziny dla :',
'info_affichier_visites_articles_plus_visites' => 'Pokaż odwiedziny dla <b>najczęściej odwiedzanych artykułów od początku:</b>',
'info_aide_en_ligne' => 'SPIP Pomoc Online',
'info_ajout_image' => 'Kiedy dodajesz do artykułu obrazki jako załączniki,
  SPIP może automatycznie utworzyć winiety (thumbnails) ze
  wstawionych obrazków. Pozwoli to na, na przykład, automatyczne
  utworzenie galerii lub portfolio.',
'info_ajout_participant' => 'Następujący uczestnik został dodany:',
'info_ajouter_rubrique' => 'Dodaj dział do zarządzania :',
'info_annonce_nouveautes' => 'Zapowiedzi najnowszych newsów',
'info_anterieur' => 'poprzedni',
'info_appliquer_choix_moderation' => 'Wybierze ten sposób moderowania:',
'info_article' => 'artykuł',
'info_article_2' => 'artykuły',
'info_article_a_paraitre' => 'Artykuły przeterminowane do opublikowania',
'info_articles_02' => 'artykuły',
'info_articles_2' => 'Artykuły',
'info_articles_auteur' => 'Artykuły tego autora',
'info_articles_lies_mot' => 'Artykuły powiązane z tym słowem kluczowym',
'info_articles_miens' => 'Mes articles', # NEW
'info_articles_tous' => 'Tous les articles', # NEW
'info_articles_trouves' => 'Artykuły znalezione',
'info_articles_trouves_dans_texte' => 'Artykuły znalezione (w tekście)',
'info_attente_validation' => 'Twoje artykuły oczekujące zatwierdzenia',
'info_aucun_article' => 'Aucun article', # NEW
'info_aucun_article_syndique' => 'Aucun article syndiqué', # NEW
'info_aucun_auteur' => 'Aucun auteur', # NEW
'info_aucun_breve' => 'Aucune brève', # NEW
'info_aucun_message' => 'Aucun message', # NEW
'info_aucun_mot_cle' => 'Aucun mot-clé', # NEW
'info_aucun_rubrique' => 'Aucune rubrique', # NEW
'info_aucun_site' => 'Aucun site', # NEW
'info_aucun_visiteur' => 'Aucun visiteur', # NEW
'info_aujourdhui' => 'dziś:',
'info_auteur_message' => 'NADAWCA WIADOMOŚCI:',
'info_auteurs' => 'Autorzy',
'info_auteurs_par_tri' => 'Autorzy@partri@',
'info_auteurs_trouves' => 'Autorzy znalezieni',
'info_authentification_externe' => 'Zewnętrzne uwierzytelnienie',
'info_avertissement' => 'Ostrzeżenie',
'info_barre_outils' => 'avec sa barre d\'outils ?', # NEW
'info_base_installee' => 'Struktura Twojej bazy danych została zainstalowana.',
'info_base_restauration' => 'Trwa odtwarzanie bazy danych.',
'info_bloquer' => 'zablokuj',
'info_breves' => 'Czy na Twojej stronie działa system newsów?',
'info_breves_03' => 'newsy',
'info_breves_liees_mot' => 'Newsy powiązane z tym słowem kluczowym',
'info_breves_touvees' => 'Newsy znalezione',
'info_breves_touvees_dans_texte' => 'Newsy znalezione (w tekście)',
'info_changer_nom_groupe' => 'Zmień nazwę grupy:',
'info_chapeau' => 'Wstęp',
'info_chapeau_2' => 'Wstęp:',
'info_chemin_acces_1' => 'Opcje: <b>Ścieżka dostępu do katalogu</b>',
'info_chemin_acces_2' => 'Powinniście w tym momencie skonfigurować ścieżkę dostępu do spisu. Ta informacja jest niezbędna do tego by odczytywać profile użytkowników zawarte w spisie.',
'info_chemin_acces_annuaire' => 'Opcje: <b>Ścieżka dostępu do katalogu</b>',
'info_choix_base' => 'Trzeci krok:',
'info_classement_1' => '<sup>er</sup> na @liste@',
'info_classement_2' => '<sup>e</sup> na @liste@',
'info_code_acces' => 'Zapamiętaj swoje kody dostępu!',
'info_comment_lire_tableau' => 'Jak odczytywac tą tabelę',
'info_compatibilite_html' => 'Norme HTML à suivre', # NEW
'info_compresseur_gzip' => '<b>N. B. :</b> Il est recommandé de vérifier au préalable si l\'hébergeur compresse déjà systématiquement les scripts php ; pour cela, vous pouvez par exemple utiliser le service suivant : @testgzip@', # NEW
'info_compresseur_texte' => 'Si votre serveur ne comprime pas automatiquement les pages html pour les envoyer aux internautes, vous pouvez essayer de forcer cette compression pour diminuer le poids des pages téléchargées. <b>Attention</b> : cela peut ralentir considerablement certains serveurs.', # NEW
'info_compresseur_titre' => 'Optimisations et compression', # NEW
'info_config_forums_prive' => 'Dans l’espace privé du site, vous pouvez activer plusieurs types de forums :', # NEW
'info_config_forums_prive_admin' => 'Un forum réservé aux administrateurs du site :', # NEW
'info_config_forums_prive_global' => 'Un forum global, ouvert à tous les rédacteurs :', # NEW
'info_config_forums_prive_objets' => 'Un forum sous chaque article, brève, site référencé, etc. :', # NEW
'info_config_suivi' => 'Jeśli ten adres odpowiada liście subskrypcyjnej, możecie wpisać poniżej adres, za któego pomocą uczestnicy serwisu mogą się zapisać. Adres ten może być URL-em (np. stroną służącą do zapisywania się na listę przez internet), albo adresem e-mail opatrzonym stosownym tematem (np.: <tt>@dany_adres@?subject=subscribe</tt>):',
'info_config_suivi_explication' => 'Możesz automatycznie, za pomocą poczty elektronicznej otrzymywać ogłoszenia dotyczące aktywności redakcyjnej tego serwisu. W tym celu powinnieneś się zapisać na listę mailową.',
'info_confirmer_passe' => 'Potwierdź nowe hasło:',
'info_conflit_edition_avis_non_sauvegarde' => 'Attention, les champs suivants ont été modifiés par ailleurs. Vos modifications sur ces champs n\'ont donc pas été enregistrées.', # NEW
'info_conflit_edition_differences' => 'Différences :', # NEW
'info_conflit_edition_version_enregistree' => 'La version enregistrée :', # NEW
'info_conflit_edition_votre_version' => 'Votre version :', # NEW
'info_connexion_base' => 'Próba połączenia z bazą danych',
'info_connexion_base_donnee' => 'Connexion à votre base de données', # NEW
'info_connexion_ldap_ok' => '<b>Połączenie LDAP powiodło się.<b><p> Idź do następnego etapu.</p>', # MODIF
'info_connexion_mysql' => 'Połączenie z bazą SQL', # MODIF
'info_connexion_ok' => 'Połączenie powiodło się.',
'info_contact' => 'Kontakt',
'info_contenu_articles' => 'Tematyka artykułów',
'info_contributions' => 'Contributions', # NEW
'info_creation_mots_cles' => 'Utwórz i konfiguruj tu słowa kluczowe strony',
'info_creation_paragraphe' => '(By utworzyć akapity, po prostu zostaw puste linijki.)',
'info_creation_rubrique' => 'Aby móc pisać artykuły,<br /> musisz utworzyć przynajmniej jeden dział.<br />',
'info_creation_tables' => 'Tworzenie tablic bazy danych',
'info_creer_base' => '<b>Utwórz</b> nową bazę danych :',
'info_dans_groupe' => 'W grupie:',
'info_dans_rubrique' => 'W dziale:',
'info_date_publication_anterieure' => 'Data poprzedniej publikacji:',
'info_date_referencement' => 'DATA ZLINKOWANIA TEJ STRONY:',
'info_delet_mots_cles' => 'Chcesz usunąć słowo kluczowege
<b>@titre_mot@</b> (@type_mot@). To słowo jest powiązane z
<b>@texte_lie@</b>musisz potwierdzić swoją decyzję:',
'info_derniere_etape' => 'Zakończone z sukcesem!',
'info_derniere_syndication' => 'Ostatnia syndykacja tego serwisu została dokonana',
'info_derniers_articles_publies' => 'Twoje ostatnie opublikowane artykuły',
'info_desactiver_forum_public' => 'Wyłącz możliwość korzystania z forum
 publicznego. Forum publiczne będzie można w pewnych przypadkach aktywować
 dołączając je do określonych artykułów ; wyłączenie będzie dotyczyło przede wszystkim działów i skrótów itd.',
'info_desactiver_messagerie_personnelle' => 'Możesz włączyć lub wyłączyć wewnętrzną pocztę w tym serwisie.',
'info_descriptif' => 'Opis:',
'info_desinstaller_plugin' => 'usuń dane i wyłącz plugina',
'info_discussion_cours' => 'Dyskusja w toku',
'info_ecrire_article' => 'Aby móc pisać artykuły, musisz utworzyć przynajmniej jeden dział.',
'info_email_envoi' => 'Adres e-mail nadawcy (nieobowiązkowo)',
'info_email_envoi_txt' => 'Wpisz adres e-maila, który będzie używany do wywyłania wiadomości (domyślnie adres odbiorcy będzie jednocześnie adresem wysyłkowym) :',
'info_email_webmestre' => 'Adres e-mail webmastera (nieobowiązkowo)',
'info_entrer_code_alphabet' => 'Wpisz kodowanie alfabetu :',
'info_envoi_email_automatique' => 'Automatyczna wysyłka maili',
'info_envoi_forum' => 'Wysyłka forum do autorów artykułów',
'info_envoyer_maintenant' => 'Wyślij teraz',
'info_erreur_restauration' => 'Błąd odtwarzania : plik nie istnieje.',
'info_etape_suivante' => 'Przejdź do następnego kroku',
'info_etape_suivante_1' => 'Możesz przejść do następnego kroku.',
'info_etape_suivante_2' => 'Możesz przejść do następnego kroku.',
'info_exceptions_proxy' => 'Exceptions pour le proxy', # NEW
'info_exportation_base' => 'eksportuj bazę danych do @archive@',
'info_facilite_suivi_activite' => 'W celu uproszczenia działań
  redakcyjnych strony, SPIP może wysłać mailem, na przykład
   na listę mailingową redaktorów, informację-zapytanie
  o sprawdzenie i publikację artykułów.',
'info_fichiers_authent' => 'Plik uwierzytelniający « .htpasswd »',
'info_fonctionnement_forum' => 'Funkcjonowanie forum :',
'info_forum_administrateur' => 'forum administratorów',
'info_forum_interne' => 'forum wewnętrzne',
'info_forum_ouvert' => 'W strefie prywatnej, forum jest otwarte dla wszystkich
  zarejestrownych redaktorów. Możecie także aktywować
  dodatkowe forum, zarezerwowane jedynie dla adminów.',
'info_forum_statistiques' => 'Odwiedź statystyki',
'info_forums_abo_invites' => 'Twoja strona zawiera formu dostępne po zalogowaniu, zatem odwiedzający powinni się zarejestrować na stronie publicznej.',
'info_gauche_admin_effacer' => '<b>Ta strona jest dostępna jedynie dla administratorów.<b><p> Daje ona dostęp do funkcji technicznych serwisu. Niektóre spośród nich wymagają wymagają połączenia   z serwerem przez FTP.', # MODIF
'info_gauche_admin_tech' => '<b>Ta strona jest dostępna jedynie dla jej właścicieli.</b><p> Daje dostęp do fukcji typowo technicznych. Niektóre spośród nich wymagają specjalnego uwierzytelnienia
, które można uzyskać jedynie poprzez FTP.', # MODIF
'info_gauche_admin_vider' => '<b>Ta strona jest dostępna jedynie dla głównych administratorów.</b><p> Daje dostęp do funkcji typowo technicznych. Niektóre spośród nich wymagają specjalnego uwierzytelnienia
, które można uzyskać jedynie poprzez FTP.', # MODIF
'info_gauche_auteurs' => 'Znajdziesz tutaj informacje o wszystkich autorach serwisu.
 Ich kompetencje są zaznaczone kolorem ikony (redaktor = zielonym; administrator = żółtym).',
'info_gauche_auteurs_exterieurs' => 'Autorzy zewnętrzni, bez dostępu do strefy publicznej są zaznaczeni ikoną niebieską ;
  autorzy skasowani.',
'info_gauche_messagerie' => 'Poczta wewnętrzna pozwala Ci wymieniać wiadomości z innymi redaktorami, i zapisywać notki (prywatne) lub publikować ogłoszenia na stronie głównej strefy prywatnej (jeśli jesteś administratorem).',
'info_gauche_numero_auteur' => 'AUTOR NUMER',
'info_gauche_numero_breve' => 'NEWS NUMER',
'info_gauche_statistiques_referers' => 'Ta strona wyświetla listę <i>odnośników</i>, to znaczy stron które zawierają łącza prowadzące do Twojej strony, które ktoś użył wczoraj lub dzisiaj : lista jest zerowana co 24 godziny.',
'info_gauche_suivi_forum' => 'Strona <i>obserwacji forum</i> jest narzędziem zarządzania stroną (nie, miejscem dyskusji czy redakcji). Wyświetla ona wszystkie komentarze do danego artykułu na forum publicznym i pozwala edytować owe komentarze.',
'info_gauche_suivi_forum_2' => 'Strona  <i>archiwum forum</i> jest narzędziem administracji stroną (a nie miejscem dyskusji czy redagowania). Wyświetla ona wszystkie komentarze forum z całego serwisu, zarówno te ze stron publicznych i strefy prywatnej i pozwala na zarządzanie tymi komentarzami.',
'info_gauche_visiteurs_enregistres' => 'Znajdziesz tu gości zarejestrowanych
 w strefie publicznej strony (abonament forum).',
'info_generation_miniatures_images' => 'Generowanie miniaturek obrazków',
'info_gerer_trad' => 'Zarządzać linkami do przekładu?',
'info_gerer_trad_objets' => '@objets@ : gérer les liens de traduction', # NEW
'info_groupe_important' => 'Ważna grupa',
'info_hebergeur_desactiver_envoi_email' => 'Niektóre serwisy hostingowe wyłączają możliwość automatycznego wywyłania
  emaili za pośrednictwem ich serwerów. W tym przypadku te funkcje
  SPIP nie będą działały.',
'info_hier' => 'wczoraj:',
'info_historique' => 'Poprawki :',
'info_historique_activer' => 'Włączyć zarządzanie poprawkami',
'info_historique_affiche' => 'Wyświetl tę wersję',
'info_historique_comparaison' => 'porównanie',
'info_historique_desactiver' => 'Wyłączyć zarządzanie poprawkami',
'info_historique_lien' => 'Wyświetl historię zmian',
'info_historique_texte' => 'Zarządzanie poprawkami artykułu pozwala zachować historię wszystkich zmian dokonanych w treści artykułu i wyświetlić różnice pomiędzy kolejnymi wersjami.',
'info_historique_titre' => 'Zarządzanie poprawkami',
'info_identification_publique' => 'Twoja nazwa publiczna',
'info_image_process' => 'Wybierz najlepsza metode przygotowania miniaturek kilkając na odpowiednim obrazku.',
'info_image_process2' => '<b>N.B.</b> <i>Jeśli nie wyświetlił się żaden obrazek, Twój serwer nie został skonfugurowany tak, aby skorzystać z tego narzędzia. Jeśli jednak chcesz skorzystać z tych funkcji, skontaktuj się z osobą odpowiedzialną za sprawy techniczne Twojego serwisu i poproś o włączenie rozszerzeń "GD" lub "Imagick".</i>',
'info_images_auto' => 'Automatycznie obliczane rozmiary obrazka',
'info_informations_personnelles' => 'Informacje o użytkowniku',
'info_inscription_automatique' => 'Automatyczna rejestracja nowych redaktorów',
'info_jeu_caractere' => 'Kodowanie strony',
'info_jours' => 'dni',
'info_laisser_champs_vides' => 'pozostaw te pola puste)',
'info_langues' => 'Języki stron',
'info_ldap_ok' => 'Uwierzytelnianie LDAP jest włączone.',
'info_lien_hypertexte' => 'Hiperłącze:',
'info_liens_syndiques_1' => 'linki syndykowane',
'info_liens_syndiques_2' => 'oczekujące zatwierdzenia.',
'info_liens_syndiques_3' => 'forum',
'info_liens_syndiques_4' => 'są',
'info_liens_syndiques_5' => 'forum',
'info_liens_syndiques_6' => 'jest',
'info_liens_syndiques_7' => 'w trakcie zatwierdzania.',
'info_liste_redacteurs_connectes' => 'Lista zalogowanych redaktorów',
'info_login_existant' => 'Podany login już istnieje.',
'info_login_trop_court' => 'Za krótki login.',
'info_logos' => 'Logo',
'info_maximum' => 'maksimum:',
'info_meme_rubrique' => 'W tym samym dziale',
'info_message' => 'Wiadomość od',
'info_message_efface' => 'WIADOMOŚĆ USUNIĘTA',
'info_message_en_redaction' => 'Twoje wiadomości w trakcie tworzenia',
'info_message_technique' => 'Wiadomość techniczna:',
'info_messagerie_interne' => 'Poczta wewnętrzna',
'info_mise_a_niveau_base' => 'uaktualnianie bazy SQL', # MODIF
'info_mise_a_niveau_base_2' => '{{Uwaga !}} Zainstalowałeś taką wersję
  plików SPIP, które należą do wcześniejszej niż posiadana przez ciebie wersja:
  twoja baza danych może zostać zniszczona
  lub popsuta i strona przestanie działać.<br />{{Zainstaluj ponownie
  pliki SPIP.}}',
'info_mode_fonctionnement_defaut_forum_public' => 'Domyślny tryb funkcjonowania forum publicznego',
'info_modification_enregistree' => 'Votre modification a été enregistrée', # NEW
'info_modifier_auteur' => 'Modifier l\'auteur :', # NEW
'info_modifier_breve' => 'Zmiana newsa:',
'info_modifier_mot' => 'Modifier le mot-clé :', # NEW
'info_modifier_rubrique' => 'Zmiana działu:',
'info_modifier_titre' => 'Zmiana: @titre@',
'info_mon_site_spip' => 'Moja strona SPIP',
'info_mot_sans_groupe' => '(Słowa kluczowe bez grupy...)',
'info_moteur_recherche' => 'Zintegrowana wyszukiwarka',
'info_mots_cles' => 'Słowa kluczowe',
'info_mots_cles_association' => 'Słowa kluczowe w tej grupie mogą być powiązane z:',
'info_moyenne' => 'średnia:',
'info_multi_articles' => 'Aktywuj menu językowe w artykułach ?',
'info_multi_cet_article' => 'Język tego artykułu:',
'info_multi_langues_choisies' => 'Wybierz języki do dyspozycji redaktorów twojej strony.
  Języki już używane na twojej stronie(wyświetlane na początku) nie mogą być wyłączone.',
'info_multi_objets' => '@objets@ : activer le menu de langue', # NEW
'info_multi_rubriques' => 'Włącz menu językowe w działach ?',
'info_multi_secteurs' => ' ... tylko dla działów podstawowych ?',
'info_nb_articles' => '@nb@ articles', # NEW
'info_nb_articles_syndiques' => '@nb@ articles syndiqués', # NEW
'info_nb_auteurs' => '@nb@ auteurs', # NEW
'info_nb_breves' => '@nb@ brèves', # NEW
'info_nb_messages' => '@nb@ messages', # NEW
'info_nb_mots_cles' => '@nb@ mots-clés', # NEW
'info_nb_rubriques' => '@nb@ rubriques', # NEW
'info_nb_sites' => '@nb@ sites', # NEW
'info_nb_visiteurs' => '@nb@ visiteurs', # NEW
'info_nom' => 'Nazwisko',
'info_nom_destinataire' => 'Nazwisko odbiorcy',
'info_nom_site' => 'Nazwa Twojej strony',
'info_nom_site_2' => '<b>Nazwa strony</b> [obowiązkowo]',
'info_nombre_articles' => '@nb_articles@ artykułów,',
'info_nombre_breves' => '@nb_breves@ newsów,',
'info_nombre_partcipants' => 'UCZESTNICY DYSKUSJI:',
'info_nombre_rubriques' => '@nb_rubriques@ działu,',
'info_nombre_sites' => '@nb_sites@ stron,',
'info_non_deplacer' => 'Nie zmieniać miejsca ...',
'info_non_envoi_annonce_dernieres_nouveautes' => 'SPIP może regularnie wysyłać ogłoszenia o najnowszych newsach.
  (ostatnio opublikowane artykuły i newsy).',
'info_non_envoi_liste_nouveautes' => 'Nie wysyłaj listy najnowszych newsów',
'info_non_modifiable' => 'nie może być zmienione',
'info_non_suppression_mot_cle' => 'Nie chcę usunąć tego słowa kluczowego.',
'info_note_numero' => 'Note @numero@', # NEW
'info_notes' => 'Notatki',
'info_nouveaux_message' => 'Nowe wiadomości',
'info_nouvel_article' => 'Nowy artykuł',
'info_nouvelle_traduction' => 'Nowy przekład :',
'info_numero_article' => 'ARTYKUŁ NUMER :',
'info_obligatoire_02' => '[Obowiązkowo]',
'info_option_accepter_visiteurs' => 'Zaakceptuj zapisy czytelników Twojej strony',
'info_option_email' => 'Kiedy odwiedzający stronę zostawią na forum wiadomość
  związaną z artykułem, autorzy artykułu mogą zostać
  poinformowani o tym przez e-mail. Czy chcesz zastosować tę opcję?', # MODIF
'info_option_faire_suivre' => 'Przesłać wiadomości tego forum do autorów artykułów',
'info_option_ne_pas_accepter_visiteurs' => 'Wyłącz zapisy czytelników serwisu',
'info_option_ne_pas_faire_suivre' => 'Nie przesyłać wiadomości tego forum',
'info_options_avancees' => 'OPCJE ZAAWANSOWANE',
'info_ortho_activer' => 'Włącz korektor ortografii',
'info_ortho_desactiver' => 'Wyłącz korektor ortografii',
'info_ou' => 'lub...',
'info_oui_suppression_mot_cle' => 'chcę definitywni usunąć to słowo kluczowe.',
'info_page_interdite' => 'Strona zabroniona',
'info_par_nom' => 'wg nazw',
'info_par_nombre_article' => '(wg liczby artykułów)',
'info_par_statut' => 'wg statusu',
'info_par_tri' => '\'(par @tri@)\'',
'info_pas_de_forum' => 'brak forum',
'info_passe_trop_court' => 'Za krótkie hasło.',
'info_passes_identiques' => 'Hasła nie są identyczne.',
'info_pense_bete_ancien' => 'Twoje stare notatki', # MODIF
'info_plus_cinq_car' => 'więcej niż 5 znaków',
'info_plus_cinq_car_2' => '(Więcej niż 5 znaków)',
'info_plus_trois_car' => '(Więcej niż 3 znaki)',
'info_popularite' => 'popularność: @popularite@; odwiedziny: @visites@',
'info_popularite_2' => 'popularność strony:',
'info_popularite_3' => 'popularność: @popularite@; odwiedziny: @visites@',
'info_popularite_4' => 'popularność: @popularite@; odwiedziny: @visites@',
'info_post_scriptum' => 'Postscriptum',
'info_post_scriptum_2' => 'Postscriptum:',
'info_pour' => 'dla',
'info_preview_admin' => 'Jedynie administratorzy mogą włączyć podgląd artykułu',
'info_preview_comite' => 'Wszyscy redaktorzy mogą włączyć podgląd artykułu',
'info_preview_desactive' => 'Podgląd artykułów jest całkiem wyłączony',
'info_preview_texte' => 'Istnieje możliwość włączania podglądu nieopublikowanych artykułów i newsów (muszą być "zaproponowane do oceny"). Czy chcesz, żeby ta funkcja była dostępna dla administratorów, redaktorów czy dla nikogo ? ',
'info_previsions' => 'prévisions :', # NEW
'info_principaux_correspondants' => 'Wasi główni korespondenci',
'info_procedez_par_etape' => 'etap za etapem',
'info_procedure_maj_version' => 'powinna zostać procedura uaktualniania w celu dostosowania
 bazy danych do nowej wersji SPIP.',
'info_proxy_ok' => 'Test proxy udany.',
'info_ps' => 'P.S.',
'info_publier' => 'opublikuj',
'info_publies' => 'Twoje artykuły opublikowane online',
'info_question_accepter_visiteurs' => 'Jeśli szkielety Twojego serwisu przewidują dla odwiedzających zapisy bez dostępu do panelu administracyjnego, włącz poniższą opcję:',
'info_question_activer_compactage_css' => 'Souhaitez-vous activer le compactage des feuilles de style (CSS) ?', # NEW
'info_question_activer_compactage_js' => 'Souhaitez-vous activer le compactage des scripts (javascript) ?', # NEW
'info_question_activer_compresseur' => 'Voulez-vous activer la compression du flux HTTP ?', # NEW
'info_question_gerer_statistiques' => 'Czy Twoja strona ma prowadzić statystykę odwiedzin?',
'info_question_inscription_nouveaux_redacteurs' => 'Czy akceptujesz możliwość dodawania nowych redaktorów
  za pośrednictwem stron publicznych ? Jeśli tak, odwiedzący będę mogli się zapisać
  za pomocą zautomatyzowanego formularza i będą mieli dostęp do strefy prywatnej, gdzie
  będą proponować własne artykuły. <blockquote><i>W trakcie procesu zapisywania,
  użytkownik otrzyma automatycznie mailem
  hasła dostępu do strefy prywatnej. Niektóre serwisy hostingowe
  wyłączają możliwość wysyłania maili z ich
  serwerów : w tym przypadku automatyczne zapisanie się jest
  niemożliwe.', # MODIF
'info_question_mots_cles' => 'Czy na Twojej stronie mają być stosowane słowa kluczowe?',
'info_question_proposer_site' => 'Kto może proponować zlinkowane strony ?',
'info_question_utilisation_moteur_recherche' => 'Czy życzysz sobie skorzystać z wyszukiwarki wewnętrznej SPIP ?
 (jej wyłączenie przyspiesza funkcjonowanie systemu.)',
'info_question_vignettes_referer' => 'Lorsque vous consultez les statistiques, vous pouvez visualiser des aperçus des sites d\'origine des visites', # NEW
'info_question_vignettes_referer_non' => 'Ne pas afficher les captures des sites d\'origine des visites', # NEW
'info_question_vignettes_referer_oui' => 'Afficher les captures des sites d\'origine des visites', # NEW
'info_question_visiteur_ajout_document_forum' => 'Si vous souhaitez autoriser les visiteurs à joindre des documents (images, sons...) à leurs messages de forum, indiquer ci-dessous la liste des extensions de documents autorisés pour les forums (ex: gif, jpg, png, mp3).', # NEW
'info_question_visiteur_ajout_document_forum_format' => 'Si vous souhaitez autoriser tous les types de documents considérés comme fiables par SPIP, mettre une étoile. Pour ne rien autoriser, ne rien indiquer.', # NEW
'info_qui_attribue_mot_cle' => 'Słowa kluczowe w tej grupie mogą być dopisywane przez:',
'info_racine_site' => 'Rdzeń strony',
'info_recharger_page' => 'Proszę za chwilę ponownie załadować tę stronę.',
'info_recherche_auteur_a_affiner' => 'Zbyt dużo rezultatów w "@cherche_auteur@" ; spróbuj sprecyzować poszukiwania.',
'info_recherche_auteur_ok' => 'Kilkunastu redaktorów zostało znalezionych dla "@cherche_auteur@":',
'info_recherche_auteur_zero' => '<b>Żadnych wyników dla " @cherche_auteur@ ".',
'info_recommencer' => 'Proszę spróbować ponownie.',
'info_redacteur_1' => 'Redaktor',
'info_redacteur_2' => 'posiadając dostęp do strefy prywatnej (<i>zaleca się</i>)',
'info_redacteurs' => 'Redaktorzy',
'info_redaction_en_cours' => 'REDAKCJA W TOKU',
'info_redirection' => 'Przekierowanie',
'info_referencer_doc_distant' => 'Dodaj odnośnik do dokumentu w internecie:',
'info_refuses' => 'Twoje odrzucone artykuły',
'info_reglage_ldap' => 'Opcje: <b>Regulacja importu LDAP</b>',
'info_remplacer_mot' => 'Remplacer "@titre@"', # NEW
'info_renvoi_article' => '<b>Przekierowanie.</b> Ten artykuł odsyła do strony:',
'info_reserve_admin' => 'Tylko administratorzy mogą zmienić ten adres.',
'info_restauration_sauvegarde' => 'odtworzenie zapisanego pliku @archive@', # MODIF
'info_restauration_sauvegarde_insert' => 'Insertion de @archive@ dans la base', # NEW
'info_restreindre_rubrique' => 'Ograniczenie zarządzaniem rubryką :',
'info_resultat_recherche' => 'Wyniki wyszukiwania:',
'info_rubriques' => 'Działy',
'info_rubriques_02' => 'działy',
'info_rubriques_liees_mot' => 'Działy powiązane z tym słowem kluczowym',
'info_rubriques_trouvees' => 'Odnalezione działy',
'info_rubriques_trouvees_dans_texte' => 'Odnalezione działy (w tekście)',
'info_sans_titre' => 'Bez tytułu',
'info_sauvegarde' => 'Backup',
'info_sauvegarde_articles' => 'Backup artykułów',
'info_sauvegarde_articles_sites_ref' => 'Zapisz artykuły ze zlinkowanych stron',
'info_sauvegarde_auteurs' => 'Backup autorów',
'info_sauvegarde_breves' => 'Backup newsów',
'info_sauvegarde_documents' => 'Backup dokumentów',
'info_sauvegarde_echouee' => 'Jeśli zapis się nie powiódł («Maximum execution time exceeded»),',
'info_sauvegarde_forums' => 'Backup forum',
'info_sauvegarde_groupe_mots' => 'Backup grup słów kluczowych',
'info_sauvegarde_messages' => 'Backup wiadomości',
'info_sauvegarde_mots_cles' => 'Backup słów kluczowych',
'info_sauvegarde_petitions' => 'Zapisz ogłoszenia',
'info_sauvegarde_refers' => 'Zapisz odnośniki',
'info_sauvegarde_reussi_01' => 'Backup zakończył się pomyślnie.',
'info_sauvegarde_reussi_02' => '<Baza danych została zapisana w @archive@. Możesz',
'info_sauvegarde_reussi_03' => 'powrót do zarządzania',
'info_sauvegarde_reussi_04' => 'Twojej strony.',
'info_sauvegarde_rubrique_reussi' => 'Les tables de la rubrique @titre@ ont été sauvegardée dans @archive@. Vous pouvez', # NEW
'info_sauvegarde_rubriques' => 'Kopia bezpieczeństwa działów',
'info_sauvegarde_signatures' => 'Zapisz podpisy petycji',
'info_sauvegarde_sites_references' => 'Zapisz zlinkowane strony',
'info_sauvegarde_type_documents' => 'Backup typów dokumentów',
'info_sauvegarde_visites' => 'Backup odwiedzin',
'info_selection_chemin_acces' => '<b>Wybierz</b> poniżej ścieżkę dostępu w katalogu:',
'info_selection_un_seul_mot_cle' => 'Możesz wybrać <b>tylko jedno słowo kluczowe</b> naraz w tej grupie.',
'info_signatures' => 'podpisy',
'info_site' => 'Strona',
'info_site_2' => 'strona:',
'info_site_min' => 'strona',
'info_site_propose' => 'Strona zaproponowana :',
'info_site_reference_2' => 'Strona zlinkowana',
'info_site_syndique' => 'Ta strona jest syndykowana...',
'info_site_valider' => 'Strony do zatwierdzenia',
'info_site_web' => 'STRONA INTERNETOWA:',
'info_sites' => 'strony',
'info_sites_lies_mot' => 'Zlinkowane strony związane z tymi słowami kluczowymi',
'info_sites_proxy' => 'Użyj proxy',
'info_sites_refuses' => 'Odrzucone strony',
'info_sites_trouves' => 'Znalezione strony',
'info_sites_trouves_dans_texte' => 'Strony znalezione (w tekście)',
'info_sous_titre' => 'Podtytuł:',
'info_statut_administrateur' => 'Administrator',
'info_statut_auteur' => 'Status tego autora:', # MODIF
'info_statut_auteur_a_confirmer' => 'Potwierdzenie subskrypcji',
'info_statut_auteur_autre' => 'Inny status :',
'info_statut_efface' => 'Usunięto',
'info_statut_redacteur' => 'Redaktor',
'info_statut_site_1' => 'Ta strona jest:',
'info_statut_site_2' => 'Opublikowana',
'info_statut_site_3' => 'Zatwierdzona',
'info_statut_site_4' => 'Do kosza',
'info_statut_utilisateurs_1' => 'Domyślny status zaiportowanych użytkowników',
'info_statut_utilisateurs_2' => 'Wybierz status, który zostanie przyznany osobom występującym w katalogu LDAP, kiedy połączą się po raz pierwszy. Możesz zmieniać tę wartość w zależności od autora. ',
'info_suivi_activite' => 'Archiwum aktywności edytorskiej',
'info_supprimer_mot' => 'usuń słowo kluczowe',
'info_surtitre' => 'Nadtytuł :',
'info_syndication_integrale_1' => 'Twoja strona oferuje plik syndykacji « <a href="@url@">@titre@</a> »).',
'info_syndication_integrale_2' => 'Czy chcesz przesyłać całe artykuły czy wolisz dystrybuować podsumowanie ograniczone do kilkuset znaków ?',
'info_table_prefix' => 'Możesz użyć własnego prefixa nazw tablic w bazie danych (jest to niezbędne jeśli chcesz zainstalować więcej stron przy użyciu tej samej bazy danych). Prefix powinien być pisany bez akcentów, małymi literami i bez spacji.',
'info_taille_maximale_images' => 'SPIP va tester la taille maximale des images qu\'il peut traiter (en millions de pixels).<br /> Les images plus grandes ne seront pas réduites.', # NEW
'info_taille_maximale_vignette' => 'Maksymalny rozmiar minitaurek, generowanych przez system :',
'info_terminer_installation' => 'Możesz teraz zakończyć proces standardowej instalacji.',
'info_texte' => 'Tekst',
'info_texte_explicatif' => 'Tekst wyjaśniający',
'info_texte_long' => '(tekst jest za długi: pojawi się w kilku częściach, które zostaną złożone po zatwierdzeniu.)',
'info_texte_message' => 'Tekst Twojej wiadomości:',
'info_texte_message_02' => 'Tekst wiadomości',
'info_titre' => 'Tytuł:',
'info_titre_mot_cle' => 'Nazwa lub tytuł tego słowa kluczowego',
'info_total' => 'ogółem:',
'info_tous_articles_en_redaction' => 'Wszystkie artykuły w toku',
'info_tous_articles_presents' => 'Wszystkie artykuły opublikowane w tym dziale',
'info_tous_articles_refuses' => 'Tous les articles refusés', # NEW
'info_tous_les' => 'każdy:',
'info_tous_redacteurs' => 'Ogłoszenia dla wszystkich redaktorów',
'info_tout_site' => 'Cała strona',
'info_tout_site2' => 'Artykuł nie został jeszcze przetłumaczony na ten język.',
'info_tout_site3' => 'Artykuł został przetłumaczony na ten język, ale po dokonaniu przekładu zostały wprowadzone zmiany do artykułu źródłowego. Przekład powinien zostać uaktualniony.',
'info_tout_site4' => 'Artykuł został przetłumaczony na ten język i nie wymaga uaktualniania.',
'info_tout_site5' => 'Artykuł oryginalny.',
'info_tout_site6' => '<b>Uwaga :</b> wyświetlone zostały jedynie artykuły oryginalne.
Przekłady są połączone z oryginałem, za pomocą koloru wskazującego na ich status :',
'info_traductions' => 'Traductions', # NEW
'info_travail_colaboratif' => 'Praca zespołowa nad artykułem',
'info_un_article' => 'artykuł,',
'info_un_mot' => 'Jedno słowo kluczowe naraz',
'info_un_site' => 'strona,',
'info_une_breve' => 'news,',
'info_une_rubrique' => 'dział,',
'info_une_rubrique_02' => '1 dział',
'info_url' => 'URL:',
'info_url_proxy' => 'URL du proxy', # NEW
'info_url_site' => 'URL STRONY:',
'info_url_test_proxy' => 'URL de test', # NEW
'info_urlref' => 'Łącze hipertekstowe :',
'info_utilisation_spip' => 'SPIP jest już gotowy do użytku...',
'info_visites_par_mois' => 'Wizyt miesięcznie:',
'info_visites_plus_populaires' => 'Pokaż odwiedziny dla <b>najpopularniejszych artykułów</b> i dla <b>artykułów ostatnio opublikowanych:</b>',
'info_visiteur_1' => 'Odwiedzający',
'info_visiteur_2' => 'strony publicznej',
'info_visiteurs' => 'Odwiedzający',
'info_visiteurs_02' => 'Odwiedzający stronę publiczną',
'info_webmestre_forces' => 'Les webmestres sont actuellement définis dans <tt>@file_options@</tt>.', # NEW
'install_adresse_base_hebergeur' => 'Adres bazy danych przyznany przez usługodawcę hostingowego',
'install_base_ok' => 'La base @base@ a été reconnue', # NEW
'install_connect_ok' => 'La nouvelle base a bien été déclarée sous le nom de serveur @connect@.', # NEW
'install_echec_annonce' => 'Instalacja może się nie powieść lub może się zdażyć, że strona przestanie działać ...',
'install_extension_mbstring' => 'SPIP nie działa z:',
'install_extension_php_obligatoire' => 'SPIP wymaga rozszerzenia php:',
'install_login_base_hebergeur' => 'Login połączenia z bazą danych przyznany przez usługodawcę hostingowego',
'install_nom_base_hebergeur' => 'Nazwa bazy danych przyznana przez usługodawcę hostingowego:',
'install_pas_table' => 'Base actuellement sans tables', # NEW
'install_pass_base_hebergeur' => 'Hasło do bazy danych przyznane przez uługodawcę hostingowego',
'install_php_version' => 'wersja PHP @version@ jest za niska (minimum = @minimum@)',
'install_select_langue' => 'Wybierz język i kliknij na przycisk "next" aby rozpocząć procedurę instalacji.',
'install_select_type_db' => 'Indiquer le type de base de données :', # NEW
'install_select_type_mysql' => 'MySQL', # NEW
'install_select_type_pg' => 'PostgreSQL', # NEW
'install_select_type_sqlite2' => 'SQLite 2', # NEW
'install_select_type_sqlite3' => 'SQLite 3', # NEW
'install_serveur_hebergeur' => 'Serveur de base de données attribué par l\'hébergeur', # NEW
'install_table_prefix_hebergeur' => 'Prefix przyznany przez usługodawcę hostingowego',
'install_tables_base' => 'Tables de la base', # NEW
'install_types_db_connus' => 'SPIP sait utiliser <b>MySQL</b> (le plus répandu), <b>PostgreSQL</b> et <b>SQLite</b>.', # NEW
'install_types_db_connus_avertissement' => 'Attention : plusieurs plugins ne fonctionnent qu\'avec MySQL', # NEW
'intem_redacteur' => 'redaktor',
'intitule_licence' => 'Licence', # NEW
'item_accepter_inscriptions' => 'Zaakceptuj zapisy',
'item_activer_forum_administrateur' => 'Aktywuj forum administratorów',
'item_activer_messages_avertissement' => 'Aktywuj komunikaty ostrzegawcze',
'item_administrateur_2' => 'administrator',
'item_afficher_calendrier' => 'Wyświetl kalendarz',
'item_ajout_mots_cles' => 'Autoryzuj słowa kluczowe dodane do forum',
'item_autoriser_documents_joints' => 'Autoryzuj załączniki do artykułów',
'item_autoriser_documents_joints_rubriques' => 'Autoryzacja dokumentów w działach',
'item_autoriser_selectionner_date_en_ligne' => 'Permettre de modifier la date de chaque document', # NEW
'item_autoriser_syndication_integrale' => 'Wysyłanie pełnej treści artykułów w pliku syndykacji',
'item_bloquer_liens_syndiques' => 'Zablokuj akceptację syndykowanych linków',
'item_breve_refusee' => 'NIE - news odrzucony',
'item_breve_validee' => 'TAK - news zatwierdzony',
'item_choix_administrateurs' => 'administratorzy',
'item_choix_generation_miniature' => 'Generuj automatycznie miniaturki obrazków.',
'item_choix_non_generation_miniature' => 'Nie generuj miniaturek obrazków.',
'item_choix_redacteurs' => 'redaktorzy',
'item_choix_visiteurs' => 'odwiedzający stronę publiczną',
'item_compresseur' => 'Activer la compression', # NEW
'item_config_forums_prive_global' => 'Activer le forum des rédacteurs', # NEW
'item_config_forums_prive_objets' => 'Activer ces forums', # NEW
'item_creer_fichiers_authent' => 'Utwórz pliki .htpasswd',
'item_desactiver_forum_administrateur' => 'Wyłącz forum administratorów',
'item_gerer_annuaire_site_web' => 'Zarządzaj katalogiem stron www',
'item_gerer_statistiques' => 'Zarządzaj statystykami',
'item_limiter_recherche' => 'Ogranicz szukanie do informacji zawartych na Twojej stronie',
'item_login' => 'Login',
'item_messagerie_agenda' => 'Activer la messagerie et l’agenda', # NEW
'item_mots_cles_association_articles' => 'artykuły',
'item_mots_cles_association_breves' => 'newsy',
'item_mots_cles_association_rubriques' => 'do działów',
'item_mots_cles_association_sites' => 'do stron zlinkowanych lub zrzeszonych.',
'item_non' => 'Nie',
'item_non_accepter_inscriptions' => 'Nie akceptuj zapisów',
'item_non_activer_messages_avertissement' => 'Wyłącz komunikaty ostrzeżeń',
'item_non_afficher_calendrier' => 'Nie wyświetlaj kalendarza',
'item_non_ajout_mots_cles' => 'Nie autoryzuj dodawania słów kluczowych do forum',
'item_non_autoriser_documents_joints' => 'Nie autoryzuj dokumentów w artykułach',
'item_non_autoriser_documents_joints_rubriques' => 'Nie autoryzuj dokumentów w działach',
'item_non_autoriser_selectionner_date_en_ligne' => 'La date des documents est celle de leur ajout sur le site', # NEW
'item_non_autoriser_syndication_integrale' => 'Wysyłanie podsumowania',
'item_non_bloquer_liens_syndiques' => 'Nie blokuj łączy pochodzących z syndykacji',
'item_non_compresseur' => 'Désactiver la compression', # NEW
'item_non_config_forums_prive_global' => 'Désactiver le forum des rédacteurs', # NEW
'item_non_config_forums_prive_objets' => 'Désactiver ces forums', # NEW
'item_non_creer_fichiers_authent' => 'Nie twórz tych plików',
'item_non_gerer_annuaire_site_web' => 'Wyłącz katalog stron www',
'item_non_gerer_statistiques' => 'Nie zarządzaj statystykami',
'item_non_limiter_recherche' => 'Szukaj także w treści stron, które są zlinkowane w systemie',
'item_non_messagerie_agenda' => 'Désactiver la messagerie et l’agenda', # NEW
'item_non_publier_articles' => 'Nie publikuj artykułów przed datą ich publikacji.',
'item_non_utiliser_breves' => 'Nie używaj newsów',
'item_non_utiliser_config_groupe_mots_cles' => 'Nie używaj zaawansowanej konfiguracji grup słów kluczowych',
'item_non_utiliser_moteur_recherche' => 'Nie używaj wyszukiwarki',
'item_non_utiliser_mots_cles' => 'Nie używaj słów kluczowych',
'item_non_utiliser_syndication' => 'Wyłącz automatyczną syndykację',
'item_nouvel_auteur' => 'Nowy autor',
'item_nouvelle_breve' => 'Nowy news',
'item_nouvelle_rubrique' => 'Nowy dział',
'item_oui' => 'Tak',
'item_publier_articles' => 'Publikuj artykuły bez względu na datę ich publikacji.',
'item_reponse_article' => 'Odpowiedz na artykuł',
'item_utiliser_breves' => 'Używaj newsów',
'item_utiliser_config_groupe_mots_cles' => 'Uzywaj zaawansowanej konfiguracji grup słów kluczowych',
'item_utiliser_moteur_recherche' => 'Używaj wyszukiwarki',
'item_utiliser_mots_cles' => 'Używaj słów kluczowych',
'item_utiliser_syndication' => 'Używaj automatycznej syndykacji',
'item_version_html_max_html4' => 'Se limiter au HTML4 sur le site public', # NEW
'item_version_html_max_html5' => 'Permettre le HTML5', # NEW
'item_visiteur' => 'odwiedzający',

// J
'jour_non_connu_nc' => 'nieznany',

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
'lien_ajout_destinataire' => 'Dodaj odbiorcę',
'lien_ajouter_auteur' => 'Dodaj autora',
'lien_ajouter_mot' => 'Ajouter ce mot-clé', # NEW
'lien_ajouter_participant' => 'Dodaj uczestnika',
'lien_email' => 'e-mail',
'lien_forum_public' => 'Zarządzaj forum publicznym tego artykułu',
'lien_mise_a_jour_syndication' => 'Uaktualnij teraz',
'lien_nom_site' => 'NAZWA STRONY:',
'lien_nouvelle_recuperation' => 'Spróbuj ponowić odtwarzanie danych',
'lien_reponse_article' => 'Odpowiedz na ten artykuł',
'lien_reponse_breve' => 'Odpowiedz na ten news',
'lien_reponse_breve_2' => 'Odpowiedz na ten news',
'lien_reponse_rubrique' => 'Odpowiedz w tym dziale',
'lien_reponse_site_reference' => 'Odwołanie się do zlinkowanej strony :',
'lien_retirer_auteur' => 'Usuń autora',
'lien_retirer_tous_auteurs' => 'Retirer tous les auteurs', # NEW
'lien_retrait_particpant' => 'usuń uczestnika',
'lien_site' => 'strona',
'lien_supprimer_rubrique' => 'usuń ten dział',
'lien_tout_deplier' => 'Zwiń wszystko',
'lien_tout_replier' => 'Rozwiń wszystko',
'lien_tout_supprimer' => 'Usuń wszystko',
'lien_trier_nom' => 'Sortuj według nazw',
'lien_trier_nombre_articles' => 'Sortuj według liczby artykułów',
'lien_trier_statut' => 'Sortuj według status',
'lien_voir_en_ligne' => 'ZOBACZ ON-LINE :',
'logo_article' => 'LOGO ARTYKUŁU',
'logo_auteur' => 'LOGO AUTORA',
'logo_breve' => 'LOGO NEWSA',
'logo_groupe' => 'LOGO DE CE GROUPE', # NEW
'logo_mot_cle' => 'LOGO SŁOWA KLUCZOWEGO',
'logo_rubrique' => 'LOGO DZIAŁU',
'logo_site' => 'LOGO STRONY',
'logo_standard_rubrique' => 'STANDARDOWE LOGO DLA DZIAŁÓW',
'logo_survol' => 'LOGO ROLL-OVER',

// M
'menu_aide_installation_choix_base' => 'Wybieranie bazy danych',
'module_fichier_langue' => 'Plik językowy',
'module_raccourci' => 'Skrót',
'module_texte_affiche' => 'Wyświetlany tekst',
'module_texte_explicatif' => 'Możesz umieścić następujące skróty w szkielecie Twojej strony publicznej. Zostaną automatycznie przetłumaczone na rózne języki, których pliki językowe istnieją.',
'module_texte_traduction' => 'Plik językowy « @module@ » jest dostępny w :',
'mois_non_connu' => 'nieznany',

// N
'nouvelle_version_spip' => 'La version @version@ de SPIP est disponible', # NEW

// O
'onglet_contenu' => 'Contenu', # NEW
'onglet_declarer_une_autre_base' => 'Déclarer une autre base', # NEW
'onglet_discuter' => 'Discuter', # NEW
'onglet_documents' => 'Documents', # NEW
'onglet_interactivite' => 'Interactivité', # NEW
'onglet_proprietes' => 'Propriétés', # NEW
'onglet_repartition_actuelle' => 'teraz',
'onglet_sous_rubriques' => 'Sous-rubriques', # NEW

// P
'page_pas_proxy' => 'Cette page ne doit pas passer par le proxy', # NEW
'pas_de_proxy_pour' => 'Jeśli potrzeba podaj serwery lub domeny, do których to proxy ma nie być używane: @exemple@)',
'plugin_charge_paquet' => 'Chargement du paquet @name@', # NEW
'plugin_charger' => 'Télécharger', # NEW
'plugin_erreur_charger' => 'erreur : impossible de charger @zip@', # NEW
'plugin_erreur_droit1' => 'Le répertoire <code>@dest@</code> n\'est pas accessible en écriture.', # NEW
'plugin_erreur_droit2' => 'Veuillez vérifier les droits sur ce répertoire (et le créer le cas échéant), ou installer les fichiers par FTP.', # NEW
'plugin_erreur_zip' => 'echec pclzip : erreur @status@', # NEW
'plugin_etat_developpement' => 'wersja rozwojowa',
'plugin_etat_experimental' => 'wersja eksperymentalna',
'plugin_etat_stable' => 'wersja stabilna',
'plugin_etat_test' => 'wersja testowa',
'plugin_impossible_activer' => 'Nie można włączyć plugina @plugin@',
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
'plugin_necessite_plugin' => 'Potrzeba pluginu @plugin@ co najmniej w wersji @version@.',
'plugin_necessite_spip' => 'Potrzeba co minimum wersji SPIP @version@ .',
'plugin_source' => 'source: ', # NEW
'plugin_titre_automatique' => 'Installation automatique', # NEW
'plugin_titre_automatique_ajouter' => 'Ajouter des plugins', # NEW
'plugin_titre_installation' => 'Installation du plugin @plugin@', # NEW
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
'plugins_actifs_liste' => 'Plugins actifs', # NEW
'plugins_compte' => '@count@ plugins', # NEW
'plugins_disponible_un' => 'Un plugin disponible.', # NEW
'plugins_disponibles' => '@count@ plugins disponibles.', # NEW
'plugins_erreur' => 'Erreur dans les plugins : @plugins@', # NEW
'plugins_liste' => 'Lista rozszerzeń',
'plugins_liste_extensions' => 'Extensions', # NEW
'plugins_recents' => 'Plugins récents.', # NEW
'plugins_vue_hierarchie' => 'Hiérarchie', # NEW
'plugins_vue_liste' => 'Liste', # NEW
'protocole_ldap' => 'Wersja protokołu:',

// Q
'queue_executer_maintenant' => 'Exécuter maintenant', # NEW
'queue_nb_jobs_in_queue' => '@nb@ travaux en attente', # NEW
'queue_next_job_in_nb_sec' => 'Prochain travail dans @nb@ s', # NEW
'queue_one_job_in_queue' => '1 travail en attente', # NEW
'queue_purger_queue' => 'Purger la liste des travaux', # NEW
'queue_titre' => 'Liste de travaux', # NEW

// R
'repertoire_plugins' => 'Katalog :',

// S
'sans_heure' => 'sans heure', # NEW
'sauvegarde_fusionner' => 'Dokonać połączenia istniejącej bazy danych z backupem',
'sauvegarde_fusionner_depublier' => 'Dépublier les objets fusionnés', # NEW
'sauvegarde_url_origine' => 'Ewentualnie, URL strony oryginalnej :',
'statut_admin_restreint' => '(admin z ograniczeniami)',
'syndic_choix_moderation' => 'Co zrobić z linkami, które pochodzą z tego serwisu ?',
'syndic_choix_oublier' => 'Co zrobić z linkami, których nie ma już w pliku syndykacji?',
'syndic_choix_resume' => 'Niektóre strony publikują pełny tekst artykułów. Jeśli dostępna jest taka wersja czy chcesz z niej skorzystać :',
'syndic_lien_obsolete' => 'nieaktualny link',
'syndic_option_miroir' => 'blokować automatycznie',
'syndic_option_oubli' => 'usunąć (po @mois@  miesiącach)',
'syndic_option_resume_non' => 'pełna treść artykułów (w formacie HTML)',
'syndic_option_resume_oui' => 'posumowanie (w postaci tekstowej)',
'syndic_options' => 'Opcje syndykacji :',

// T
'taille_cache_image' => 'Obrazki mają automatycznie zmieniany rozmiar przez SPIP (miniaturki dokumentów, tytuły przedstawiane w postaci graficznej, funkcje matematyczne w formacie TeX...) zajmują w katalogu @dir@ obszar @taille@.',
'taille_cache_infinie' => 'Serwis nie ma włączonego ograniczenia dla wielkości katalogu cache.',
'taille_cache_maxi' => 'SPIP próbuje ograniczyć wielkość katalogu   <code>CACHE/</code> tej strony do około <b>@octets@</b> danych.',
'taille_cache_octets' => 'W tym momencie wielkość cache to: @octets@.',
'taille_cache_vide' => 'Cache jest pusty.',
'taille_repertoire_cache' => 'Wielkość katalogu cache',
'text_article_propose_publication' => 'Artykuł zatwierdzony do publikacji. Nie wahaj się wyrazić swoją opinię przez forum dotyczące tego artykułu (na dole strony).', # MODIF
'text_article_propose_publication_forum' => 'N\'hésitez pas à donner votre avis grâce au forum attaché à cet article (en bas de page).', # NEW
'texte_acces_ldap_anonyme_1' => 'Niektóre serwery LDAP nie akceptują dostępu anonimowego. W tym przypadku należy podać indentyfikator dostępu, aby móc przeszukiwać katalog. Niemniej, w większości przypadków poniższe pola powinny pozostać puste.',
'texte_admin_effacer_01' => 'Ta komenda usuwa <i>całą</i> zawartość bazy danych,
włącznie z <i>wszystkimi</i> parametrami dostępu dla redaktorów i administartorów. Po jej zastosowaniu powinieneś
zreinstalować SPIP w celu utworzenia nowej bazy danych i dostępu pierwszego administratora.',
'texte_admin_effacer_stats' => 'Cette commande efface toutes les données liées aux statistiques de visite du site, y compris la popularité des articles.', # NEW
'texte_admin_tech_01' => 'Ta opcja pozwala Ci zapisać zawartość bazy danych w pliku, który zostanie zachowany w katalogu @dossier@. Pamiętaj także o skopiowaniu całego katalogu @img@, który zawiera obrazki i dokumenty używane w artykułach i działach.',
'texte_admin_tech_02' => 'Uwaga: tą kopię bezpieczeństwa będzie można odtworzyć
 TYLKO I WYŁĄCZNIE w serwisie opartym na tej samej wersji SPIP. Nie wolno  "oprózniać bazy danych" sądząc, że po zaktualizowaniu SPIP będzie można odtworzyć bazę z backupu. Więcej informacji w <a href="@spipnet@">dokumentacji SPIP</a>.', # MODIF
'texte_admin_tech_03' => 'Możesz wybrać wykonanie kopii bezpieczeńśtwa pod postacią skompresowaną, w celu
 przyspieszenia ściągania pliku lub zapisywania na serwerze, i zarazem oszczędności przestrzeni dyskowej.',
'texte_admin_tech_04' => 'Dans un but de fusion avec une autre base, vous pouvez limiter la sauvegarde à la rubrique: ', # NEW
'texte_adresse_annuaire_1' => '(Jeśli Twój katalog jest zainstalowany na tym samym komputerze co strona internetowa, chodzi zapewne o «localhost».)',
'texte_ajout_auteur' => 'Następujący autor został dodany do artykułu:',
'texte_annuaire_ldap_1' => 'Jeśli masz dostęp do katalogu (LDAP), możesz zniego skorzystać do automatycznego importu użytkowników SPIP.  ',
'texte_article_statut' => 'Ten artykuł jest:',
'texte_article_virtuel' => 'Wirtualny artykuł',
'texte_article_virtuel_reference' => '<b>Artykuł wirtualny :</b> artykuł zlinkowany w Twoim serwisie SPIP ale przekierowujący do innego URL-a. Aby usunąć to przekierowanie, wymaż URL powyżej.',
'texte_aucun_resultat_auteur' => 'Żadnych wyników dla "@cherche_auteur@".',
'texte_auteur_messagerie' => 'Na tej stronie może wyświetlać się bez przerwy lista zalogowanych redaktorów, co pozwoli Ci bezpośrednio wymieniać z nimi wiadomości. Możesz także zdecydować o nie pojawianiu się na tej liście (jesteś wówczas "niewidzialny" dla innych użytkowników)',
'texte_auteur_messagerie_1' => 'Na tej stronie możesz wymieniać wiadomości i tworzyć prywatne fora dyskusyjne pomiędzy użytkownikami strony. Możesz nie brać udziału w tych wymianach.',
'texte_auteurs' => 'AUTORZY',
'texte_breves' => 'Newsy są krótkimi tekstami, które pozwalają
 szybko umieścić na stronie zwięzłe informacje i zarządzać
 przeglądem prasy, albo kalendarzem wydarzeń...',
'texte_choix_base_1' => 'Wybierz bazę danych:',
'texte_choix_base_2' => 'Serwer SQL zawiera kilka baz danych.', # MODIF
'texte_choix_base_3' => '<b>Wybierz</b>, jaka została Ci przyznana przez Twój serwis hostingowy:',
'texte_choix_table_prefix' => 'Prefix tablic:',
'texte_commande_vider_tables_indexation' => 'Skorzystaj z tego polecenia w celu opróżnienia tabeli indeksujących, które używane są
 przez zintegrowaną wyszukiwarkę SPIP.
   Pozwoli to oszczędzić przestrzeń dysku twardego.',
'texte_comment_lire_tableau' => 'Szereg artykułów występujących,
  w klasyfikacji popularności jest na marginesie marge ; popularność artykułu(szacunek
  dziennej liczby wizyt zostanie obiczony jeśli częstotliwość wizyt zostanie utrzymana)
 a liczba wizyt,
od początku opublikowania artykułu pojawi się kiedy najedziesz myszką na tytuł artykułu.',
'texte_compacter_avertissement' => 'Attention à ne pas activer ces options durant le développement de votre site : les éléments compactés perdent toute lisibilité.', # NEW
'texte_compacter_script_css' => 'SPIP peut compacter les scripts javascript et les feuilles de style CSS, pour les enregistrer dans des fichiers statiques ; cela accélère l\'affichage du site.', # NEW
'texte_compatibilite_html' => 'Vous pouvez demander à SPIP de produire, sur le site public, du code compatible avec la norme <i>HTML4</i>, ou lui permettre d\'utiliser les possibilités plus modernes du <i>HTML5</i>.', # NEW
'texte_compatibilite_html_attention' => 'Il n\'y a aucun risque à activer l\'option <i>HTML5</i>, mais si vous le faites, les pages de votre site devront commencer par la mention suivante pour rester valides : <code>&lt;!DOCTYPE html&gt;</code>.', # NEW
'texte_compresse_ou_non' => '(może być skompresowany lub nie)',
'texte_compresseur_page' => 'SPIP peut compresser automatiquement chaque page qu\'il envoie aux
visiteurs du site. Ce réglage permet d\'optimiser la bande passante (le
site est plus rapide derrière une liaison à faible débit), mais
demande plus de puissance au serveur.', # NEW
'texte_compte_element' => '@count@ element',
'texte_compte_elements' => '@count@ elementy',
'texte_config_groupe_mots_cles' => 'Czy życzysz sobie, aby włączyć zaawansowaną konfigurację słów kluczowych,
   gdzie możesz zaznaczyć np. jedno słowo
   słowa wg grup, wg ważności grup... ?',
'texte_conflit_edition_correction' => 'Veuillez contrôler ci-dessous les différences entre les deux versions du texte ; vous pouvez aussi copier vos modifications, puis recommencer.', # NEW
'texte_connexion_mysql' => 'Sprawdź informacje dostarczone przez Twój serwis hostingowy : znajdzies tam, jeśli serwer korzysta z SQL, kody służące do połączenia z serwerem SQL.', # MODIF
'texte_contenu_article' => '(Treść artykułu w kilku słowach.)',
'texte_contenu_articles' => 'Zależnie od struktury jaką przyjąłeś dla swojego serwisu, możesz zdecydować,
 których elementów artykułów nie używać.Korzystając z poniższej listy, wybierz używane elementy.',
'texte_crash_base' => 'Jeśli Twoja baza danych
   zepsuła, możesz spróbować naprawić ją
   automatycznie.',
'texte_creer_rubrique' => 'Aby móc pisać artykuły,<br /> musisz utworzyć dział.',
'texte_date_creation_article' => 'DATA UTWORZENIA ARTYKUŁU:',
'texte_date_publication_anterieure' => 'Data poprzedniej publikacji :',
'texte_date_publication_anterieure_nonaffichee' => 'Ukryj datę poprzedniej publikacji.',
'texte_date_publication_article' => 'DATA PUBLIKACJI ONLINE:',
'texte_descriptif_petition' => 'Opis ogłoszenia',
'texte_descriptif_rapide' => 'Krótki opis',
'texte_documents_joints' => 'Możesz autoryzować dodawanie dokumentów tekstowych, obrazków,
multimediów, itd.) do artykułów bądź działów. Te pliki
 mogą zostać dołączone do artykułu
 lub mogą być użyte niezależnie.<p>', # MODIF
'texte_documents_joints_2' => 'Te ustawienia nie przeszkadzają we wstawianiu obrazków bezpośrednio do artykułów.',
'texte_effacer_base' => 'Usuń bazę danych SPIP',
'texte_effacer_donnees_indexation' => 'Wymaż dane pochodzące z indeksowania',
'texte_effacer_statistiques' => 'Effacer les statistiques', # NEW
'texte_en_cours_validation' => 'Następujące artykuły i newsy zostały zatwierdzone do publikacji. Nie wahaj się wyrazić swoją opinię na dołączonych do nich forach.', # MODIF
'texte_en_cours_validation_forum' => 'N\'hésitez pas à donner votre avis grâce aux forums qui leur sont attachés.', # NEW
'texte_enrichir_mise_a_jour' => 'Możesz wzbogacić układ Twojego tekstu « skrótami typograficznymi ».',
'texte_fichier_authent' => '<b>Czy SPIP ma stworzyć specjalne pliki <tt>.htpasswd</tt>
  i <tt>.htpasswd-admin</tt> w katalogu @dossier@ ?</b><p>
  Te pliki mogą służyć jako ograniczenie dostępu dla autorów
  i administratorów do innych części Twojego serwisu
  (np. zewnętrznych statystyk).</p><p>
  Jeśli nie chcesz z nich korzystać, możesz pozostawić tą opcję
  w nienaruszonej postaci (te pliki nie zostaną stworzone).</p>', # MODIF
'texte_informations_personnelles_1' => 'System stworzy teraz spersonalizowany dostep do serwisu.',
'texte_informations_personnelles_2' => '(Informacja: jeśli jest to reinstalacja, a Twój dostęp wciąż działa, możesz',
'texte_introductif_article' => '(Tekst wprowadzający do artykułu.)',
'texte_jeu_caractere' => 'Zaleca się używania uniwersalnego kodowania (<tt>utf-8</tt>), co pozwala na wyświetlanie tekstów we wszystkich językach i jest kompatybilne ze wszystkimi współczesnymi przeglądarkami.',
'texte_jeu_caractere_2' => 'Uwaga: te ustawienia nie powodują konwersji tekstów już zapisanych w bazie danych.',
'texte_jeu_caractere_3' => 'Twoja strona pracuje obecnie w kodowaniu :',
'texte_jeu_caractere_4' => 'Jeśli to nie odpowiada aktualnemu kodowaniu Twoich danych (np. po odtworzeniu bazy danych), lub jeśli <em>uruchamiasz stronę</em> i chcesz ustamić inne kodowanie, wpisz je tutaj:',
'texte_jeu_caractere_conversion' => 'Wskazówka : możesz chcieć przekonwertować  swoje dane z całego serwisu (artykuły, aktualności, fora, itd) na kodowanie <tt>utf-8</tt>, udając się na <a href="@url@"> stronę konwertera utf-8</a>.',
'texte_lien_hypertexte' => '(Jeśli Twoja wiadomość odnosi się do jakiegoś artykułu opublikowanego w internecie, albo do strony, na której można znaleźć dodatkowe informacje, wpisz tytuł strony i jej adres URL.)',
'texte_liens_sites_syndiques' => 'Łącza pochodzące z syndykacji mogą
   być domyślnie zablokowane ; regulacja tego
   wskazuje regulacje domyślne
   stron syndykowanych po ich stworzeniu. Jest
   możliwe późniejsze odblokowanie, łączy indywidualnie, lub
   wybór, strona po stronie, blokady linków pochodzących z danych stron.',
'texte_login_ldap_1' => '(Pozostaw puste przy dostępie anonimowym, lub wpisz pełną ścieżkę, np. « <tt>uid=dupont, ou=users, dc=mon-domaine, dc=com</tt> ».)',
'texte_login_precaution' => 'Uwaga ! To jest login, z którego pomocą jesteś teraz połączony.
 Używaj tego formularza ostrożnie...',
'texte_message_edit' => 'Ostrzeżenie: ta wiadomość może być zmieniana przez wszystkich administratorów strony i jest widoczna dla wszystkich redaktorów. Używaj zawiadomień jedynie, aby podkreślić ważne wydarzenia w życiu strony.',
'texte_messagerie_agenda' => 'Une messagerie permet aux rédacteurs du site de communiquer entre eux directement dans l’espace privé du site. Elle est associée à un agenda.', # NEW
'texte_messages_publics' => 'Publiczne komentarze do artykułu :',
'texte_mise_a_niveau_base_1' => 'Właśnie zaktualizowałeś pliki SPIP.
 Teraz należy uaktualnić bazę danych
 Twojego serwisu.',
'texte_modifier_article' => 'Edytuj artykuł',
'texte_moteur_recherche_active' => '<b>Wyszukiwarka jest włączona</b> Użyj tego polecenia
  jeśli życzysz sobie szybkiej reindeksacji (np. po odtworzeniu
  kopii bezpieczeństwa). Pamiętaj, że dokumenty zmodyfikowane
  w normalny sposób (za pomocą interfejsu SPIP) są automatycznie reindeksowane : dlatego to polecenie jest rzadko stosowane.',
'texte_moteur_recherche_non_active' => 'Wyszukiwarka nie jest włączona.',
'texte_mots_cles' => 'Słowa kluczowe pozwalają na stworzenie linków tematycznych pomiędzy artykułami
  niezależnie od ich umieszczenia w konkretnych działach. Możesz w ten sposób
  wzbogacić nawigację Twojej strony, a nawet skorzystać z tej opcji
  aby spersonalizować artykuły w Twoim szkielecie strony.',
'texte_mots_cles_dans_forum' => 'Czy chcesz pozwolić użytkownikom na korzystanie ze słów kluczy, i umożliwić wybór przez odwiedzających, w forum publicznym strony ? (Uwaga : ta funkcja jest trudna do poprawnego użytkowania.)',
'texte_multilinguisme' => 'Jeśli planujesz edytować artykuły w wielu językach, ze złożoną nawigacją, możesz dołożyć do artykułu lub działu, menu wyboru języka, jako element organizacyjny Twojej strony.',
'texte_multilinguisme_trad' => 'Możesz także włączyć system zarządzania linkami pomiędzy przekładami artykułów.',
'texte_non_compresse' => '<i>nie skompresowany</i> (twój serwer nie posiada tej funkcji)',
'texte_non_fonction_referencement' => 'Być może wolisz nie używać funkcji automatycznej, i samemu zaznaczyć elementy związane z tą stroną...',
'texte_nouveau_message' => 'Nowa wiadomość',
'texte_nouveau_mot' => 'Nowe słowo kluczowe',
'texte_nouvelle_version_spip_1' => 'Nowa wersja SPIP została zainstalowana.',
'texte_nouvelle_version_spip_2' => 'Nowa wersja wymaga bardziej kompletnego dostosowania niż zwykle. Jeśli jesteś administratorem strony wykasuj plik @connect@ z katalogu <tt>ecrire</tt> i powtórz instalację w celu uaktualnienia Twoich parametrów połączenia z bazą danych .<p> (NB. : jeśli zapomniałeś parametry połączenia, rzuć okiem do pliku @connect@ zanim go skasujesz...)', # MODIF
'texte_operation_echec' => 'Powróć do poprzedniej strony, wybierz inną bazę danych lub stwórz nową. Sprawdź informacje podane przez twój serwis hostingowy.',
'texte_plus_trois_car' => 'więcej niż 3 znaki',
'texte_plusieurs_articles' => 'Kilku autorów zostało znalezionych dla "@cherche_auteur@":',
'texte_port_annuaire' => '(Wartość podana domyślnie zwykle pasuje .)',
'texte_presente_plugin' => 'Oto jest lista dostępnych rozszerzeń (pluginów). Możesz aktywować te rozszerzenia, których potrzebujesz, zaznaczając odpowiednie pole.',
'texte_proposer_publication' => 'Gdy Twój artykuł jest ukończony,<br /> możesz zatwierdzić go do publikacji.',
'texte_proxy' => 'W niektórych przypadkach (intranet, sieci chronione...),
  może zajść konieczność wykorzystania <i>proxy HTTP</i> aby dostać się do stron zrzeszonych.
  W innym przypadku, wpisz poniżej adres takiej strony, w postaci
  <tt><html>http://proxy:8080</html></tt>. Zwykle,
  to pole pozostaje wolne.',
'texte_publication_articles_post_dates' => 'Jak powinien zachowywać się SPIP odnośnie artykułów,
  których data jest zaplanowana na
  przyszłość ?',
'texte_rappel_selection_champs' => '[Nie zapomnij wybrać poprawnych pól.]',
'texte_recalcul_page' => 'Jeśli chcesz 
odświeżyć tylko jedną stronę, zrób to z obszaru publicznego, używając przycisku « odśwież ».',
'texte_recapitiule_liste_documents' => 'Ta strona wyświetla w postaci listy dokumenty które umieściłeś w działach. Aby zmienić informacje o danym dokumencie, kliknij na łącze prowadzące do strony danego działu.',
'texte_recuperer_base' => 'Napraw bazę danych',
'texte_reference_mais_redirige' => 'linki z artykułów w twoim serwisie SPIP, przekierowujące do innych URL-i.',
'texte_referencement_automatique' => '<b>Zautomatyzowane dodawanie linków</b><br />Możesz szybko dodać link do jakiejś strony internetowej, wpisując poniżej jej adres, oraz adres jej pliku syndykacji. SPIP automatycznie dopisze informacje, dotyczące tej strony (tytuł, opis...).',
'texte_referencement_automatique_verifier' => 'Veuillez vérifier les informations fournies par <tt>@url@</tt> avant d\'enregistrer.', # NEW
'texte_requetes_echouent' => '<b>Jeśli pewne zapytania SQL nie udają się
  regularnie i bez widocznego powodu, możliwe jest
  że powodem tego jest baza danych</b><p>
  SQL ma możliwość naprawy poszczególnych tabel
  jeśli przez przypadek zostały uszkodzone.
 Możesz spróbować naprawić je tutaj ; jeśli jednak nie powiedzie się taka operacja,
  zachcowaj kopię wyświetlanego komunikatu, ponieważ może on zawierać wskazówki w czym tkwi poroblem.
<p>  Jeśli problem będzie się pojawiał często skontaktuj się z administratorem Twojego serwera.', # MODIF
'texte_restaurer_base' => 'Odtwórz zawartość kopii bezpieczeństwa bazy',
'texte_restaurer_sauvegarde' => 'Ta opcja pozwala Ci odtworzyć poprzednią kopię bezpieczeństwa
  bazy danych. Aby móc to uczynić plik - kopia bezpieczeństwa powienien być
  umieszczony w katalogu @dossier@.
  Bądź ostrożny korzystając z tej funkcji : <b> modyfikacje i ewentualne straty, są
  nieodwracalne.</b>',
'texte_sauvegarde' => 'Backup zawartości bazy danych',
'texte_sauvegarde_base' => 'Backup bazy danych',
'texte_sauvegarde_compressee' => 'Backup zostanie zrobiony w nieskompresowanym pliku @fichier@.',
'texte_selection_langue_principale' => 'Możesz poniżej wybrać « główny język » serwisu. Ten wybór nie zmusza Cię - na szczęście ! - do pisania artykułów w wybranym języku, ale pozwala określić :
 <ul><li> domyślny format dat na stronach publicznych ;</li>
 <li> rodzaj kodowania tekstu, który ma używać SPIP ;</li>
 <li> język używany wa formularzach stron publicznych ;</li>
 <li> oraz język używany domyślnie w strefie prywatnej.</li></ul>',
'texte_signification' => 'Ciemne paski oznaczają podsumowanie wszystkich odwiedzin w poddziałach, paski jasne liczbę wizyt dla poszczególnych działów.',
'texte_sous_titre' => 'Podtytuł',
'texte_statistiques_visites' => '(ciemny pasek : niedziela / ciemna krzywa : rozwój średniej)',
'texte_statut_attente_validation' => 'w trakcie zatwierdzania',
'texte_statut_publies' => 'opublikowany online',
'texte_statut_refuses' => 'odrzucony',
'texte_suppression_fichiers' => 'Używaj tego polecenia gdy chcesz usunąć wszystkie pliki zapisane
 w cache SPIP. Pozwoli to na odświeżenie wszystkich stron, jeśli dokonaliści poważniejszych modyfikacji w układzie graficznym lub strukturze strony.',
'texte_sur_titre' => 'Nadtytuł',
'texte_syndication' => 'Jeśli dany serwis na to pozwala, jest możliwość wyciągnięcia z niego 
  listy newsów. Aby skorzystać z tej funkcji musisz włączyć <i>syndykację ?</i>. 
  <blockquote><i>Niektóre serwery mają taką możliwość wyłączoną ; 
  wówczas nie możesz używać syndykacji przy użyciu swojej strony.</i></blockquote>', # MODIF
'texte_table_ok' => ': ta tabela działa poprawnie.',
'texte_tables_indexation_vides' => 'Tabele indeksowania wyszukiwarki są puste.',
'texte_tentative_recuperation' => 'Próba naprawy',
'texte_tenter_reparation' => 'Spróbuj naprawić bazę danych',
'texte_test_proxy' => 'Aby wypróbować proxy, wpisz tutaj adres strony internetowej
    którą chcesz przetestować.',
'texte_titre_02' => 'Temat:',
'texte_titre_obligatoire' => '<b>Tytuł</b> [Obowiązkowo]',
'texte_travail_article' => '@nom_auteur_modif@ pracował nad tym artykułem @date_diff@ minut temu',
'texte_travail_collaboratif' => 'Jeśli często zdarza się, że kilku redaktorów
   pracuje nad tym samym artykułem, system
  może wyświetlić artykuły ostatnio « otwarte »
  aby uniknąć jednoczesnego edytowania.
  Domyślnie ta opcja jest wyłączona
  w celu wyeliminowania niepotrzebnych komunikatów.',
'texte_trop_resultats_auteurs' => 'Zbyt dużo rezultatów dla "@cherche_auteur@" ; spróbuj sprecyzować kryteria wyszukiwania.',
'texte_type_urls' => 'Vous pouvez choisir ci-dessous le mode de calcul de l\'adresse des pages.', # NEW
'texte_type_urls_attention' => 'Attention ce réglage ne fonctionnera que si le fichier @htaccess@ est correctement installé à la racine du site.', # NEW
'texte_unpack' => 'ściąganie najnowszej wersji',
'texte_utilisation_moteur_syndiques' => 'Jeśli korzystasz z wyszukiwarki zintegrowanej ze    SPIP, możesz dokonywać przeszukiwania
    dołączonych artykułów na dwa sposoby.
    <br />- Najprostszy
    polega na wyszukiwaniu jedynie
    w tytułach i skrótach artykułów. <br />-
    Druga, o wiele potężniejsza metoda, pozwala
    SPIP w tekstach stron, których linki są dołączone . Jeśli
    zatem dołączacie link do jakiejś strony, SPIP dokona automatycznie
wyszukiwania w dołączonej stronie.', # MODIF
'texte_utilisation_moteur_syndiques_2' => 'Ta metoda sprawia, że SPIP musi
    regularnie odwiedzać strony, których linki są dołączone,
    co może spowodować lekkie spowolnienie Twojej strony.',
'texte_vide' => 'pusty',
'texte_vider_cache' => 'Opróżnij cache',
'titre_admin_effacer' => 'Konserwacja techniczna',
'titre_admin_tech' => 'Konserwacja techniczna',
'titre_admin_vider' => 'Konserwacja techniczna',
'titre_ajouter_un_auteur' => 'Ajouter un auteur', # NEW
'titre_ajouter_un_mot' => 'Ajouter un mot-clé', # NEW
'titre_articles_syndiques' => 'Artykułu syndykowane, wyciągnięte z tej strony',
'titre_breves' => 'Newsy',
'titre_cadre_afficher_article' => 'Pokaż artykuły:',
'titre_cadre_afficher_traductions' => 'Wyświetl stan przekładów dla następujących języków:',
'titre_cadre_ajouter_auteur' => 'DODAJ ARTYKUŁY:',
'titre_cadre_forum_administrateur' => 'Prywatne forum administratorów',
'titre_cadre_forum_interne' => 'Forum wewnętrzne',
'titre_cadre_interieur_rubrique' => 'Artykuł znajduje się w dziale',
'titre_cadre_numero_auteur' => 'AUTOR NUMER',
'titre_cadre_numero_objet' => '@objet@ NUMÉRO :', # NEW
'titre_cadre_signature_obligatoire' => '<b>Podpis</b> [Obowiązkowo]<br />',
'titre_compacter_script_css' => 'Compactage des scripts et CSS', # NEW
'titre_compresser_flux_http' => 'Compression du flux HTTP', # NEW
'titre_config_contenu_notifications' => 'Notifications', # NEW
'titre_config_contenu_prive' => 'Dans l’espace privé', # NEW
'titre_config_contenu_public' => 'Sur le site public', # NEW
'titre_config_fonctions' => 'Konfiguracja strony',
'titre_config_forums_prive' => 'Forums de l’espace privé', # NEW
'titre_config_groupe_mots_cles' => 'Konfiguracja grup słów kluczowych',
'titre_config_langage' => 'Configurer la langue', # NEW
'titre_configuration' => 'Konfiguracja strony',
'titre_configurer_preferences' => 'Configurer vos préférences', # NEW
'titre_conflit_edition' => 'Conflit lors de l\'édition', # NEW
'titre_connexion_ldap' => 'Opcje: <b>Twoje połączenie LDAP</b>',
'titre_dernier_article_syndique' => 'Ostatnio syndykowane artykuły',
'titre_documents_joints' => 'Załączniki',
'titre_evolution_visite' => 'Ewolucja odwiedzin',
'titre_forum_suivi' => 'Archiwum forum',
'titre_gauche_mots_edit' => 'SŁOWO NUMER :',
'titre_groupe_mots' => 'GRUPY SŁÓW KLUCZOWYCH:',
'titre_identite_site' => 'Identité du site', # NEW
'titre_langue_article' => 'JĘZYK ARTYKUŁU',
'titre_langue_breve' => 'JĘZYK NEWSA',
'titre_langue_rubrique' => 'JĘZYK DZIAŁU',
'titre_langue_trad_article' => 'JĘZYK I TŁUMACZENIA ARTYKUŁU',
'titre_les_articles' => 'ARTYKUŁY',
'titre_messagerie_agenda' => 'Messagerie et agenda', # NEW
'titre_mots_cles_dans_forum' => 'Słowa kluczowe na forum na stronie publicznej',
'titre_mots_tous' => 'Słowa kluczowe',
'titre_naviguer_dans_le_site' => 'Przeglądaj stronę...',
'titre_nouveau_groupe' => 'Nowa grupa',
'titre_nouvelle_breve' => 'Nowy news',
'titre_nouvelle_rubrique' => 'Nowy dział',
'titre_numero_rubrique' => 'DZIAŁ NUMER :',
'titre_page_admin_effacer' => 'Konserwacja techniczna : wyczyść bazę',
'titre_page_articles_edit' => 'Edytuj: @titre@',
'titre_page_articles_page' => 'Artykuły',
'titre_page_articles_tous' => 'Cała strona',
'titre_page_auteurs' => 'Odwiedzający',
'titre_page_breves' => 'Newsy',
'titre_page_breves_edit' => 'Edytuj newsa: «@titre@»',
'titre_page_calendrier' => 'Kalendarz @nom_mois@ @annee@',
'titre_page_config_contenu' => 'Konfiguracja strony',
'titre_page_config_fonctions' => 'Konfiguracja strony',
'titre_page_configuration' => 'Konfiguracja strony',
'titre_page_controle_petition' => 'Archiwum ogłoszeń',
'titre_page_delete_all' => 'całkowite i nieodwracalne usunięcie',
'titre_page_documents_liste' => 'Załączniki',
'titre_page_forum' => 'Forum administratorów',
'titre_page_forum_envoi' => 'Wyślij wiadomość',
'titre_page_forum_suivi' => 'Archiwum forum',
'titre_page_index' => 'Twój obszar prywatny',
'titre_page_message_edit' => 'Napisz wiadomość',
'titre_page_messagerie' => 'Twoje wiadomości',
'titre_page_mots_tous' => 'Słowa kluczowe',
'titre_page_recherche' => 'Wyniki wyszukiwania @recherche@',
'titre_page_sites_tous' => 'Zlinkowane strony',
'titre_page_statistiques' => 'Statystyki działu',
'titre_page_statistiques_messages_forum' => 'Messages de forum', # NEW
'titre_page_statistiques_referers' => 'Statystyki (linki wchodzące)',
'titre_page_statistiques_signatures_jour' => 'Nombre de signatures par jour', # NEW
'titre_page_statistiques_signatures_mois' => 'Nombre de signatures par mois', # NEW
'titre_page_statistiques_visites' => 'Statystyka odwiedzin',
'titre_page_upgrade' => 'Dostosowanie SPIP',
'titre_publication_articles_post_dates' => 'Publikacja post-datowanych artykułów',
'titre_referencement_sites' => 'Linkowanie i zrzeszanie stron',
'titre_referencer_site' => 'Dodaj link do strony :',
'titre_rendez_vous' => 'SPOTKANIA:',
'titre_reparation' => 'Napraw',
'titre_site_numero' => 'STRONA NUMER :',
'titre_sites_proposes' => 'Strony zatwierdzone',
'titre_sites_references_rubrique' => 'Linki do stron z tego działu',
'titre_sites_syndiques' => 'Syndykowane serwisy',
'titre_sites_tous' => 'Linki do stron',
'titre_suivi_petition' => 'Archiwum ogłoszeń',
'titre_syndication' => 'Syndykacja stron',
'titre_type_urls' => 'Type d\'adresses URL', # NEW
'tls_ldap' => 'Transport Layer Security :',
'tout_dossier_upload' => 'Cały katalog @upload@',
'trad_article_inexistant' => 'Nie ma artykułu o tym numerze.',
'trad_article_traduction' => 'Wszystkie wersje tego artykułu:',
'trad_deja_traduit' => 'Błąd: nie można połączyć tego artykułu z wybranym numerem.',
'trad_delier' => 'Nie łączyć artykułu z tym przekładem', # MODIF
'trad_lier' => 'Ten artykuł jest przekładem artykułu numer',
'trad_new' => 'Napisz nowe tłumaczenie artykułu', # MODIF

// U
'upload_fichier_zip' => 'Plik ZIP',
'upload_fichier_zip_texte' => 'Plik, który chcesz zainstalować jest plikiem ZIP.',
'upload_fichier_zip_texte2' => 'Ten plik może być :',
'upload_info_mode_document' => 'Déposer cette image dans le portfolio', # NEW
'upload_info_mode_image' => 'Retirer cette image du portfolio', # NEW
'upload_limit' => 'Ten plik jest zbyt duży dla serwera ;maksymalny rozmiar pliku zapisywanego na serwerze to @max@.',
'upload_zip_conserver' => 'Conserver l’archive après extraction', # NEW
'upload_zip_decompacter' => 'zdekompresowany i każdy jego element zostanie zainstalowany w serwisie. Pliki, które zostaną zainstalowane w serwisie to:',
'upload_zip_telquel' => 'zostaną zainstalowane jako archiwum Zip;',
'upload_zip_titrer' => 'Titrer selon le nom des fichiers', # NEW
'utf8_convert_attendez' => 'Poczekaj chwilę i odśwież stronę.',
'utf8_convert_avertissement' => 'Zamierzasz przekonwertować zawartość Twojej bazy danych (artykuły, aktualności, etc.) z kodowania <b>@orig@</b> na kodowanie <b>@charset@</b>.',
'utf8_convert_backup' => 'Nie zapomnij wykonać wcześniej kopii bezpieczeństwa Twojej strony. Powinieneś także zweryfikować czy Twoje szkielety i pliki językowe są zgodne z @charset@. W innym wypadku przegląd zmian - jeśli jest aktywny - zostanie zniszczony.',
'utf8_convert_erreur_deja' => 'Twoja strona już jest w kodowaniu @charset@, nie ma potrzeby konwertowania...',
'utf8_convert_erreur_orig' => 'Błąd : kodowanie @charset@ nie jest wspierane.',
'utf8_convert_termine' => 'Zakończone !',
'utf8_convert_timeout' => '<b>Ważne :</b> w przypadu <i>timeout</i> serwera, odśwież stronę aż wyświetlenia się "Zakończone".',
'utf8_convert_verifier' => 'Teraz powineneś opróżnić CACHE i sprawdzić, czy wszystko przebiegło dobrze. W przypadku jakichkolwiek problemów pamiętaj, że zawsze masz w odwodzie kopię bazy dancyh (w formacie SQL) w katalogu @rep@.',
'utf8_convertir_votre_site' => 'Konwertuj stronę na utf-8.',

// V
'version' => 'Wersja :',
'version_deplace_rubrique' => 'Déplacé de <b>« @from@ »</b> vers <b>« @to@ »</b>.', # NEW
'version_initiale' => 'Wersja początkowa'
);

?>
