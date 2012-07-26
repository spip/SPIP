<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/ecrire_?lang_cible=hu
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'activer_plugin' => 'Plugin aktiválása',
	'affichage' => 'Affichage', # NEW
	'aide_non_disponible' => 'Ez a része a súgónak még nincs lefordítva arra a nyelvre.',
	'annuler_recherche' => 'Annuler la recherche', # NEW
	'auteur' => 'Szerzőr :',
	'avis_acces_interdit' => 'Hozzáférés nincs engedélyezve',
	'avis_article_modifie' => 'Vigyázat, @nom_auteur_modif@ dolgozott ezen a cikken @date_diff@ perccel ezelőtt',
	'avis_aucun_resultat' => 'Nincs eredmény.',
	'avis_base_inaccessible' => 'Impossible de se connecter à la base de données @base@.', # NEW
	'avis_chemin_invalide_1' => 'Az Ön által választott elérési út',
	'avis_chemin_invalide_2' => 'nem tűnik érvényesnek. Menjen az elöző oldalra és ellenőrizze a beírt adatokat.',
	'avis_connexion_echec_1' => 'A SQL szerverhez való csatlakozás sikertelen.', # MODIF
	'avis_connexion_echec_2' => 'Menjen az elöző oldalra, és ellenőrizze a beírt adatokat.',
	'avis_connexion_echec_3' => '<b>Megjegyzés:</b> Sok szerver esetén, <b>kérni kell</b> a SQL adatbázishoz való hozzáférés aktválását, mielőbb használhassa. Amennyiben nem tud csatlakozni, ellenőrizze, ha ez az eljárás megtörtént-e.', # MODIF
	'avis_connexion_erreur_nom_base' => 'Le nom de la base ne peut contenir que des lettres, des chiffres et des tirets', # NEW
	'avis_connexion_ldap_echec_1' => 'Az LDAP szerverhez való csatlakozás sikertelen.',
	'avis_connexion_ldap_echec_2' => 'Menjen az elöző oldalra, és ellenőrizze a beírt adatokat.',
	'avis_connexion_ldap_echec_3' => 'Alternatív módon, ne használja az LDAP támogatást felhasználók importálására.',
	'avis_deplacement_rubrique' => 'Vigyázat ! Ez a rovat @contient_breves@ hírt tartalmaz : ha át akarja helyezni, ezt a megerősítési jelölőkockát kell jelölni.',
	'avis_destinataire_obligatoire' => 'Egy címzettet kell jelölni mielőbb elküldi ezt az üzenetet.',
	'avis_erreur_connexion_mysql' => 'SQL-es csatlakozási hiba', # MODIF
	'avis_erreur_version_archive' => '<b>Vigyázat! A @archive@ fájl az itt telepített SPIP
    egy másik verziónak felel meg
    </b> Óriási nehézségek előtt áll:
az Ön adatbázis megsemmisítése, honlap rossz működése, stb. Ne
    érvényesítse ezt az impotálási kérést.<p>több
    információról lásd <a href="@spipnet@">
SPIP dokumentációja (franciául)</a>.', # MODIF
	'avis_espace_interdit' => '<b>Tiltott zóna</b><p>SPIP már telepítve van.', # MODIF
	'avis_lecture_noms_bases_1' => 'A telepítő program nem tudta olvasni a már telepített adatbázisok nevét.',
	'avis_lecture_noms_bases_2' => 'Vagy egyetlen adatbázis sem szabad, vagy az adatbázisokat listázó függvény lett inaktiválva
  biztonsági okokból (ami előfordul számos szolgáltatónál).',
	'avis_lecture_noms_bases_3' => 'A második alternativában elképzelhető, hogy az Ön login nevét viselő adatbázis használható :',
	'avis_non_acces_message' => 'Nincs jogosultsága erre az üzenetre.',
	'avis_non_acces_page' => 'Nincs jogosultsága erre az oldalra.',
	'avis_operation_echec' => 'A művelet sikertelen.',
	'avis_operation_impossible' => 'Opération impossible', # NEW
	'avis_probleme_archive' => 'Olvasási hiba a @archive@ nevű fájlon',
	'avis_suppression_base' => 'VIGYÁZAT, az adatok törlése visszavonhatatlan',
	'avis_version_mysql' => 'Ez a SQL verzió (@version_mysql@) nem teszi lehetővé a adatbázis táblai önjavítását.', # MODIF

	// B
	'bouton_acces_ldap' => 'Hozzátenni az LDAP hozzáférést >>', # MODIF
	'bouton_ajouter' => 'Új',
	'bouton_ajouter_participant' => 'ÚJ RÉSZTVEVŐ :',
	'bouton_annonce' => 'HÍRDETÉS',
	'bouton_annuler' => 'Annuler', # NEW
	'bouton_checkbox_envoi_message' => 'Lehetőség üzenetküldéshez',
	'bouton_checkbox_indiquer_site' => 'Honlapot kötelezően kell kijelölni',
	'bouton_checkbox_signature_unique_email' => 'csak egy aláírás emailcímenként',
	'bouton_checkbox_signature_unique_site' => 'csak egy aláírás honlaponként',
	'bouton_demande_publication' => 'Kérni e cikk publikálását',
	'bouton_desactive_tout' => 'Minden tiltása',
	'bouton_desinstaller' => 'Désinstaller', # NEW
	'bouton_effacer_index' => 'Törölni az indexeket',
	'bouton_effacer_tout' => 'MINDENT törölni',
	'bouton_envoi_message_02' => 'ÜZENET KÜLDÉS',
	'bouton_envoyer_message' => 'Végleges üzenet: küldés',
	'bouton_fermer' => 'Fermer', # NEW
	'bouton_mettre_a_jour_base' => 'Mettre à jour la base de données', # NEW
	'bouton_modifier' => 'Módosítás',
	'bouton_pense_bete' => 'EMLÉKEZTETŐ SZEMÉLYES HASZNÁLATHOZ',
	'bouton_radio_activer_messagerie' => 'A belső üzenetek aktiválása',
	'bouton_radio_activer_messagerie_interne' => 'A belső üzenetek aktiválása',
	'bouton_radio_activer_petition' => 'Az aláírásgyűjtés aktiválása',
	'bouton_radio_afficher' => 'Megjelenítés',
	'bouton_radio_apparaitre_liste_redacteurs_connectes' => 'Szerepelni a csatlakozott szerzők listában',
	'bouton_radio_desactiver_messagerie' => 'Inaktiválni az üzeneteket',
	'bouton_radio_envoi_annonces_adresse' => 'Küldeni a hírdetéseket a következő címre :',
	'bouton_radio_envoi_liste_nouveautes' => 'Küldeni az újdongágok listáját',
	'bouton_radio_non_apparaitre_liste_redacteurs_connectes' => 'Nem szerepelni a szerzők listában',
	'bouton_radio_non_envoi_annonces_editoriales' => 'Ne legyen szerkesztői hírküldés',
	'bouton_radio_pas_petition' => 'Nincs aláírásgyűjtés',
	'bouton_radio_petition_activee' => 'Aláírásgyűjtés aktiválása',
	'bouton_radio_supprimer_petition' => 'Törölni az aláírásgyűjtést',
	'bouton_redirection' => 'ÁTIRÁNYÍTÁS',
	'bouton_relancer_installation' => 'Telepítés újrakezdése',
	'bouton_suivant' => 'Következő',
	'bouton_tenter_recuperation' => 'Kisérletezni egy javítást',
	'bouton_test_proxy' => 'Probálni a proxyt',
	'bouton_vider_cache' => 'A "cache" ürítése',
	'bouton_voir_message' => 'Nézni az üzenetet jóváhagyás előtt',

	// C
	'cache_mode_compresse' => 'A "cache" fájlai tömörített formában vannak felvéve.',
	'cache_mode_non_compresse' => 'A "cache" fájlai nem tömörített formában vannak felvéve.',
	'cache_modifiable_webmestre' => 'Ezt a paramétert a honlap gazdája módosíthatja.',
	'calendrier_synchro' => 'Amennyiben egy <b>iCal</b>-val kompatibilis szoftvert használ, lehet szinkronizálni e honlap információival.',
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
	'date_mot_heures' => 'órák',

	// E
	'ecran_securite' => ' + écran de sécurité @version@', # NEW
	'email' => 'email',
	'email_2' => 'email :',
	'en_savoir_plus' => 'En savoir plus', # NEW
	'entree_adresse_annuaire' => 'A címjegyzék címe',
	'entree_adresse_email' => 'Az Ön email címe',
	'entree_adresse_email_2' => 'Adresse email', # NEW
	'entree_base_donnee_1' => 'Adatbázis címe',
	'entree_base_donnee_2' => '(Gyakran ez a cím a honlapé, néha «localhost», néha teljesen üres marad.)',
	'entree_biographie' => 'Rövid önéletrajz pár szóban.',
	'entree_chemin_acces' => '<b>Beírni</b> az elerési utat :', # MODIF
	'entree_cle_pgp' => 'Az Ön PGP kulcsa',
	'entree_cle_pgp_2' => 'Clé PGP', # NEW
	'entree_contenu_rubrique' => '(Rovat tartalma pár szóban.)',
	'entree_identifiants_connexion' => 'A csatlakozási azonosítói...',
	'entree_identifiants_connexion_2' => 'Identifiants de connexion', # NEW
	'entree_informations_connexion_ldap' => 'Ezen az űrlapon írja be az Ön LDAP szerver csatlakozási információkat.
 Ezek az információ szerezhetők a rendszer, vagy a hálozat adminisztrátorától.',
	'entree_infos_perso' => 'Kicsoda Ön ?',
	'entree_infos_perso_2' => 'Qui est l\'auteur ?', # NEW
	'entree_interieur_rubrique' => 'Melyik rovatba kerüljön :',
	'entree_liens_sites' => '<b>Hiperhívatkozás</b> (referencia, látógatható honlap...)', # MODIF
	'entree_login' => 'Az Ön felhasználói neve (login)',
	'entree_login_connexion_1' => 'Csatlakozási login',
	'entree_login_connexion_2' => '(Néha megfelel az FTP loginjának; néha üres marad)',
	'entree_login_ldap' => 'Eredeti LDAP login',
	'entree_mot_passe' => 'Az Ön jelszava',
	'entree_mot_passe_1' => 'Csatlakozási jelszó',
	'entree_mot_passe_2' => '(Néha megfelel az FTP jelszavának; néha üres marad)',
	'entree_nom_fichier' => 'Írja be a fájl nevét @texte_compresse@:',
	'entree_nom_pseudo' => 'Az Ön neve, vagy felhasználói neve',
	'entree_nom_pseudo_1' => '(Az Ön neve vagy felhsználói neve)',
	'entree_nom_pseudo_2' => 'Nom ou pseudo', # NEW
	'entree_nom_site' => 'A honlapja neve',
	'entree_nom_site_2' => 'Nom du site de l\'auteur', # NEW
	'entree_nouveau_passe' => 'Új jelszó',
	'entree_passe_ldap' => 'Jelszó',
	'entree_port_annuaire' => 'A címtár port száma',
	'entree_signature' => 'Aláírás',
	'entree_titre_obligatoire' => '<b>Cím</b> [Kötelező]<br />', # MODIF
	'entree_url' => 'A honlapja címe (URL)',
	'entree_url_2' => 'Adresse (URL) du site', # NEW
	'erreur_connect_deja_existant' => 'Un serveur existe déjà avec ce nom', # NEW
	'erreur_nom_connect_incorrect' => 'Ce nom de serveur n\'est pas autorisé', # NEW
	'erreur_plugin_desinstalation_echouee' => 'La désinstallation du plugin a echoué. Vous pouvez néanmoins le desactiver.', # NEW
	'erreur_plugin_fichier_absent' => 'Nem létező fájl',
	'erreur_plugin_fichier_def_absent' => 'Nem létező definiáló fájl',
	'erreur_plugin_nom_fonction_interdit' => 'Tilos függvénynév',
	'erreur_plugin_nom_manquant' => 'Hiányzó plugin név',
	'erreur_plugin_prefix_manquant' => 'Nem definiált plugin név terület',
	'erreur_plugin_tag_plugin_absent' => 'hiányzó &lt;plugin&gt; a definiáló fájlban',
	'erreur_plugin_version_manquant' => 'Hiányzó plugin verzió',

	// H
	'htaccess_a_simuler' => 'Avertissement: la configuration de votre serveur HTTP ne tient pas compte des fichiers @htaccess@. Pour pouvoir assurer une bonne sécurité, il faut que vous modifiiez cette configuration sur ce point, ou bien que les constantes @constantes@ (définissables dans le fichier mes_options.php) aient comme valeur des répertoires en dehors de @document_root@.', # NEW
	'htaccess_inoperant' => 'htaccess inopérant', # NEW

	// I
	'ical_info1' => 'Ez az oldal több módszert mutat ahhoz, hogy maradjon kapcsolatban e honlap életével.',
	'ical_info2' => 'Azokról a technikákról tövábbi információk olvashatók ide <a href="@spipnet@">az SPIP dokumentációja (franciául)</a>.', # MODIF
	'ical_info_calendrier' => 'Két naptár áll rendelkezésére. Az első egy olyan térpkép a honlapról, melyben szerepel az összes publikált cikk. A második pedig a tartalmi hírdetéseket, illetve az Ön utolsó privát üzenetei : egy személyes kulcsnak köszönhetően van fenntartva Ön részére, ami bármikor módosítható a jelszava változtatásával.',
	'ical_methode_http' => 'Letöltés',
	'ical_methode_webcal' => 'Szinkronizálás (webcal://)',
	'ical_texte_js' => 'Egyetlenegy javascript sor nagyon egyszerűen teszi lehetővé az itteni honlap legutóbbi cikkei publikálását bármilyen honlapon, ami az Öné.',
	'ical_texte_prive' => 'Ez a naptár, ami szigorúan személyes használatra, informálja Önt a honlap privát tartalmi tevékenységről (feladatok és személyes talákozások, javasolt cikkek és hírek...).',
	'ical_texte_public' => 'Ez a naptár a honlap nyilvános tevékenységének a figyelését teszi lehetővé (publikált cikkek és hírek).',
	'ical_texte_rss' => 'Ön a honlap ujdonságait szindikálhatja bármilyen XML/RSS (Rich Site Summary)tipusú fájlolvasóval. Valamint ez a formátum SPIP részére teszi lehetővé más honlapok publikált újdonságok olvasását (szindikált honlapok).',
	'ical_titre_js' => 'Javascript',
	'ical_titre_mailing' => 'Levelező lista',
	'ical_titre_rss' => '« backend » fájlok (rss)',
	'icone_accueil' => 'Accueil', # NEW
	'icone_activer_cookie' => 'A hivatkozási süti (cookie) aktiválása',
	'icone_activite' => 'Activité', # NEW
	'icone_admin_plugin' => 'Plugin-ek beállítása',
	'icone_administration' => 'Maintenance', # NEW
	'icone_afficher_auteurs' => 'Megjeleníteni a szerzőket',
	'icone_afficher_visiteurs' => 'Megjeleníteni a látogatókat',
	'icone_arret_discussion' => 'Megszüntetni a vitahoz való részvételt ',
	'icone_calendrier' => 'Naptár',
	'icone_configuration' => 'Configuration', # NEW
	'icone_creer_auteur' => 'Új szerző létrehozása, és hozzárendelése ehhez a cikkekhez',
	'icone_creer_mot_cle' => 'Új kulcsszó létrehozása és hozzárendelése ehhez a cikkhez',
	'icone_creer_mot_cle_rubrique' => 'Créer un nouveau mot-clé et le lier à cette rubrique', # NEW
	'icone_creer_mot_cle_site' => 'Créer un nouveau mot-clé et le lier à ce site', # NEW
	'icone_creer_rubrique_2' => 'Új rovat létrehozása',
	'icone_edition' => 'Édition', # NEW
	'icone_envoyer_message' => 'Üzenet küldése',
	'icone_ma_langue' => 'Ma langue', # NEW
	'icone_mes_infos' => 'Mes informations', # NEW
	'icone_mes_preferences' => 'Mes préférences', # NEW
	'icone_modifier_article' => 'A cikk módosítása',
	'icone_modifier_message' => 'Az üzenet módosítása',
	'icone_modifier_rubrique' => 'A rovat módosítása',
	'icone_publication' => 'Publication', # NEW
	'icone_relancer_signataire' => 'Relancer le signataire', # NEW
	'icone_retour' => 'Vissza',
	'icone_retour_article' => 'Vissza a cikkhez',
	'icone_squelette' => 'Squelettes', # NEW
	'icone_suivi_publication' => 'Suivi de la publication', # NEW
	'icone_supprimer_cookie' => 'A hivatkozási süti (cookie) törlése',
	'icone_supprimer_rubrique' => 'A rovat törlése',
	'icone_supprimer_signature' => 'Az aláírás törlése',
	'icone_valider_signature' => 'Az aláírás érvényesítése',
	'image_administrer_rubrique' => 'Ezt a rubrikát adminisztrálhatja',
	'impossible_modifier_login_auteur' => 'Impossible de modifier le login.', # NEW
	'impossible_modifier_pass_auteur' => 'Impossible de modifier le mot de passe.', # NEW
	'info_1_article' => '1 cikk',
	'info_1_article_syndique' => '1 article syndiqué', # NEW
	'info_1_auteur' => '1 auteur', # NEW
	'info_1_message' => '1 message', # NEW
	'info_1_mot_cle' => '1 mot-clé', # NEW
	'info_1_rubrique' => '1 rubrique', # NEW
	'info_1_site' => '1 honlap',
	'info_1_visiteur' => '1 visiteur', # NEW
	'info_activer_cookie' => 'Egy <b>hivatkozási sütit</b> (cookie) lehet aktiválni, melynek segítségével könnyen át tud menni a nyilvános részről a privát részre.',
	'info_admin_etre_webmestre' => 'Me donner les droits de webmestre', # NEW
	'info_admin_gere_rubriques' => 'Ez az adminisztrátor a következő rovatokat kezelheti :',
	'info_admin_gere_toutes_rubriques' => 'Ez az adminisztrátor az <b>összes rubrikát</b> kezeli.', # MODIF
	'info_admin_gere_toutes_rubriques_2' => 'Je gère <b>toutes les rubriques</b>', # NEW
	'info_admin_je_suis_webmestre' => 'Je suis <b>webmestre</b>', # NEW
	'info_admin_statuer_webmestre' => 'Donner à cet administrateur les droits de webmestre', # NEW
	'info_admin_webmestre' => 'Cet administrateur est <b>webmestre</b>', # NEW
	'info_administrateur' => 'Adminisztrátor',
	'info_administrateur_1' => 'Adminisztrátor',
	'info_administrateur_2' => 'honlap (<i>óvatosan használja</i>)',
	'info_administrateur_site_01' => 'Amennyiben Ön a honlap adminisztrátora, legyen szíves',
	'info_administrateur_site_02' => 'kattintani erre a linkre',
	'info_administrateurs' => 'Adminisztrátorok',
	'info_administrer_rubrique' => 'Ezt a rubrikát Ön adminisztrálhatja',
	'info_adresse' => 'ezen a címen :',
	'info_adresse_url' => 'A nyilvános honlap címe (URL)',
	'info_afficher_par_nb' => 'Afficher par', # NEW
	'info_afficher_visites' => 'A látógatások megjelenítése erre :',
	'info_aide_en_ligne' => 'On-line SPIP súgó',
	'info_ajout_image' => 'Ha képeket tesz hozzá, mint cikkhez csatolt dokumentum,
  akkor SPIP automatikusan létre hozhat Önnek kisebb képeket (miniatürök)a beszúrt képekről
  Ez példáúl teszi lehetővé egy képgalléria, vagy egy portfolio automatikus létrehozása.',
	'info_ajout_participant' => 'A következő résztvevő hozzá van téve :',
	'info_ajouter_rubrique' => 'Újabb adminisztrálandó rovat létrehozása :',
	'info_annonce_nouveautes' => 'Az újdonságok közlése',
	'info_anterieur' => 'elöző',
	'info_article' => 'cikk',
	'info_article_2' => 'cikk',
	'info_article_a_paraitre' => 'utólagosan dátumozott publikálandó cikkek',
	'info_articles_02' => 'cikk',
	'info_articles_2' => 'Cikkek',
	'info_articles_auteur' => 'A szerző cikkei',
	'info_articles_miens' => 'Mes articles', # NEW
	'info_articles_tous' => 'Tous les articles', # NEW
	'info_articles_trouves' => 'Talált cikkek',
	'info_articles_trouves_dans_texte' => 'Talált cikkek (a szövegben)',
	'info_attente_validation' => 'Jóváhagyás alatti cikkei',
	'info_aucun_article' => 'Aucun article', # NEW
	'info_aucun_article_syndique' => 'Aucun article syndiqué', # NEW
	'info_aucun_auteur' => 'Aucun auteur', # NEW
	'info_aucun_message' => 'Aucun message', # NEW
	'info_aucun_rubrique' => 'Aucune rubrique', # NEW
	'info_aucun_site' => 'Aucun site', # NEW
	'info_aucun_visiteur' => 'Aucun visiteur', # NEW
	'info_aujourdhui' => 'A mai napon :',
	'info_auteur_message' => 'AZ ÜZENET SZERZŐJE :',
	'info_auteurs' => 'A szerzők',
	'info_auteurs_par_tri' => 'Szerzők@partri@',
	'info_auteurs_trouves' => 'Talált szerzők',
	'info_authentification_externe' => 'Külső autentifikálás',
	'info_avertissement' => 'Figyelmeztetés',
	'info_barre_outils' => 'avec sa barre d\'outils ?', # NEW
	'info_base_installee' => 'Az Ön adatbázisának struktúrája telepítve van.',
	'info_bio' => 'Biographie', # NEW
	'info_chapeau' => 'Bevezető',
	'info_chapeau_2' => 'Bevezető :',
	'info_chemin_acces_1' => 'Opciók : <b>Elérési út a címtárban</b>', # MODIF
	'info_chemin_acces_2' => 'Mostántól a címtárban a információk elérési utját kell konfigurálni. Ez az adat nélkülözhetetlen ahhoz, hogy olvashatóak legyenek a felhaszálói profilok a címtárban.',
	'info_chemin_acces_annuaire' => 'Opciók : <b>Elérési út a címtárban', # MODIF
	'info_choix_base' => 'Harmadik lépés :',
	'info_classement_1' => '<sup>.</sup> összesen @liste@',
	'info_classement_2' => '<sup>.-dik</sup> összesen @liste@',
	'info_code_acces' => 'Ne felejtse el a saját hozzáférési kódjait !',
	'info_compatibilite_html' => 'Norme HTML à suivre', # NEW
	'info_compresseur_gzip' => '<b>N. B. :</b> Il est recommandé de vérifier au préalable si l\'hébergeur compresse déjà systématiquement les scripts php ; pour cela, vous pouvez par exemple utiliser le service suivant : @testgzip@', # MODIF
	'info_compresseur_texte' => 'Si votre serveur ne comprime pas automatiquement les pages html pour les envoyer aux internautes, vous pouvez essayer de forcer cette compression pour diminuer le poids des pages téléchargées. <b>Attention</b> : cela peut ralentir considerablement certains serveurs.', # NEW
	'info_config_suivi' => 'Ha ez a cím egy levelező listahoz tartozik, lejjebb azt a címet jelezheti, ahova a résztvevők beíratkozhatnak. Ez a cím akár URL lehet (pl. a beíratkozási oldal a Weben), vagy egy specifikus tárgyat tartalmazó email cím (pl.<tt>@adresse_suivi@?subject=subscribe</tt>):',
	'info_config_suivi_explication' => 'Beíratkozhat a honlap levelező listájához. Ilyenkor emailben fogja kapni ezeket a cikkeket, híreket, melyeket javasoltak publikálásra.',
	'info_confirmer_passe' => 'Az új jelszó erősítse meg :',
	'info_conflit_edition_avis_non_sauvegarde' => 'Attention, les champs suivants ont été modifiés par ailleurs. Vos modifications sur ces champs n\'ont donc pas été enregistrées.', # NEW
	'info_conflit_edition_differences' => 'Différences :', # NEW
	'info_conflit_edition_version_enregistree' => 'La version enregistrée :', # NEW
	'info_conflit_edition_votre_version' => 'Votre version :', # NEW
	'info_connexion_base' => 'Második lépés : <b>Adatbázishoz való csatlakozási próba</b>', # MODIF
	'info_connexion_base_donnee' => 'Connexion à votre base de données', # NEW
	'info_connexion_ldap_ok' => 'Az LDAP csatlakozás sikeres lett.</b><p> Léphet tovább a következőre.</p>', # MODIF
	'info_connexion_mysql' => 'Első lépés : <b>Az Ön SQL csatlakozása</b>', # MODIF
	'info_connexion_ok' => 'A csatlakozás sikeres.',
	'info_contact' => 'Kontakt',
	'info_contenu_articles' => 'Cikkek tartalma',
	'info_contributions' => 'Contributions', # NEW
	'info_creation_paragraphe' => '(Paragrafúsok létrehozására, egyszerűen csak üres sorokat kell hagyni.)', # MODIF
	'info_creation_rubrique' => 'Mielőbb cikkeket írhasson,<br /> legalább egy rubrikát kell létrehozni.<br />', # MODIF
	'info_creation_tables' => 'Negyedik lépés : <b>Az adatbázis táblai létrehozása</b>', # MODIF
	'info_creer_base' => '<b>Létrehozni</b> egy újabb adatbázist :', # MODIF
	'info_dans_rubrique' => 'A rovatban :',
	'info_date_publication_anterieure' => 'Elöző szerkesztés dátuma :',
	'info_date_referencement' => 'A HONLAP ELTÁVOLÍTÁSA DÁTUMA :',
	'info_derniere_etape' => 'Utolsó lépés : <b>Vége van !', # MODIF
	'info_derniers_articles_publies' => 'Az Ön legutolsó publikált cikkei',
	'info_desactiver_messagerie_personnelle' => 'Lehet aktiválni, vagy inaktiválni a személyes levelezést a honlapon.',
	'info_descriptif' => 'Rövid ismertető :',
	'info_desinstaller_plugin' => 'supprime les données et désactive le plugin', # NEW
	'info_discussion_cours' => 'Folyamatban lévő viták',
	'info_ecrire_article' => 'Mielőbb írjon cikkeket, legalább egy rubrikát kell létrehozni.',
	'info_email_envoi' => 'Email cím küldésre (opció)',
	'info_email_envoi_txt' => 'Itt jelezze a használandó feladó címet az email küldésére (ennek híján, a címzett címét használjuk, mint feladói) :',
	'info_email_webmestre' => 'A Webmester email címe (opció)', # MODIF
	'info_entrer_code_alphabet' => 'Írja be a használandó abécé kódját :',
	'info_envoi_email_automatique' => 'Automatikus email küldés',
	'info_envoyer_maintenant' => 'Azonnali küldés',
	'info_etape_suivante' => 'Következő lépés',
	'info_etape_suivante_1' => 'Léphet a következőre.',
	'info_etape_suivante_2' => 'Léphet a következőre.',
	'info_exceptions_proxy' => 'Exceptions pour le proxy', # NEW
	'info_exportation_base' => 'Adatbázis exportálása @archive@ felé',
	'info_facilite_suivi_activite' => 'Ahhoz, hogy könnyebben lehessen figyelemmel követni a honlap szerkesztői tevékenységét, SPIP emailen küldheti például a publikálási, ill. cikkjóváhagyási kéréseket egy szerzői levelezőlistára.',
	'info_fichiers_authent' => 'Azonosítási fájlok « .htpasswd »',
	'info_forums_abo_invites' => 'A honlapja beiratkozásos fórumokat tartalmaz ; tehát a látogatók beíratkozhatnak a nyilvános részen.',
	'info_gauche_admin_effacer' => '<b>Ez az oldal csak a honlap gazdai részére elérhető.</b><p> A különböző műszaki karbantartási feladatokra ad lehetőséget. Ezek közül néhany igényel olyan specifikus azonosítási eljárást, ami a honlaphoz FTP elérést követel.</p>', # MODIF
	'info_gauche_admin_tech' => '<b>Ez az oldal csak a honlap gazdai részére elérhető.</b><p> A különböző műszaki karbantartási feladatokra ad lehetőséget. Ezek közül néhany igényel olyan specifikus azonosítási eljárást, ami a honlaphoz FTP elérést követel.</p>', # MODIF
	'info_gauche_admin_vider' => '<b>Ez az oldal csak a honlap gazdai részére elérhető.</b><p> A különböző műszaki karbantartási feladatokra ad lehetőséget. Ezek közül néhany igényel olyan specifikus azonosítási eljárást, ami a honlaphoz FTP elérést követel.</p>', # MODIF
	'info_gauche_auteurs' => 'Itt található a honlap összes szerzője.
 Saját státuszuk az ikon színe szerint van jelölve ( adminisztrátor = zöld; szerző = sárga).',
	'info_gauche_auteurs_exterieurs' => 'A külső szerzők, melyek nem férhetnek a honlaphoz, kék ikonnal vannak jelölve ;
a törölt szerzők pedig kukával vannak jelölve.', # MODIF
	'info_gauche_messagerie' => 'A levelezés lehetővé tesz szerzők közti üzenetcserét, emlékeztetők (saját használatra) megtartását, vagy hírdetések megjelenítését a privát rész főoldalán (amennyiben Ön adminisztrátor).',
	'info_gauche_numero_auteur' => 'SZERZŐ SZÁMA',
	'info_gauche_statistiques_referers' => 'Ez az oldal a <i>referers</i> listáját mutat, vagyis olyan honlapokat, melyeken az Ön honlapjához hivatkozó linkek találhatók, de csak a tegnapi és a mai napra : ez a lista nullázva van 24 óra után.',
	'info_gauche_visiteurs_enregistres' => 'Itt találhatók a honlap nyilvános részén regisztrált látogatók (beíratkozásos fórumok).',
	'info_generation_miniatures_images' => 'Bélyegképek generálása a képekről',
	'info_gerer_trad' => 'Fordítasi linkek kezelése ?',
	'info_gerer_trad_objets' => '@objets@ : gérer les liens de traduction', # NEW
	'info_hebergeur_desactiver_envoi_email' => 'Bizonyos szolgáltatók nem aktiválják az automatikus email küldést a szerverükről. Ilyen esetben, a következő SPIP funkciók nem fognak működni.',
	'info_hier' => 'Tegnap :',
	'info_historique_activer' => 'A felülvizsgálatok megfigyelésének aktiválása',
	'info_historique_affiche' => 'A verzió megjelenítése',
	'info_historique_comparaison' => 'Összehasonlítás',
	'info_historique_desactiver' => 'A felülvizsgalatok megfigyelésének inaktiválása',
	'info_historique_texte' => 'A felülvizsgalatok megfigyelése egy cikk tartalmához nyújtott összes módosításokről tárolja az előzményeket, és megjeleníti az egymást követő változatok eltéréseket.',
	'info_identification_publique' => 'Az Ön nyilvános azonosítása...',
	'info_image_process' => 'Válasszon a bélyegképek legjobb készítesi modszerét azzal, hogy kattintson a megfelelő képre.',
	'info_image_process2' => '<b>Megjegyzés</b> <i>Ha egyetlen kép sem jelenik meg, akkor ez azt jelenti, hogy a honlapját tároló szervert nem konfigurálták olyan eszkőzök használására. Ha mégis akarja használni ezeket a funkciókat, keresse a rendszergazdát, és a «GD» vagy «Imagick» kiegészítéseket kérje.</i>', # MODIF
	'info_images_auto' => 'Automatikusan kalkulált képek',
	'info_informations_personnelles' => 'Ötödik lépés : <b>Személyes adatok</b>', # MODIF
	'info_inscription_automatique' => 'Új szerzők automatikus beiratkozása',
	'info_jeu_caractere' => 'A honlap karakter táblája',
	'info_jours' => 'nap',
	'info_laisser_champs_vides' => 'hagyja üresen ezeket a mezőket)',
	'info_langues' => 'A honlap nyelvei',
	'info_ldap_ok' => 'Az LDAP azonosítás telepítve van.',
	'info_lien_hypertexte' => 'Hiperhivatkozás :',
	'info_liste_nouveautes_envoyee' => 'La liste des nouveautés a été envoyée', # NEW
	'info_liste_redacteurs_connectes' => 'Jelenleg csatlakozott szerzők listája',
	'info_login_existant' => 'Ez a login már létezik.',
	'info_login_trop_court' => 'A login túl rövid.',
	'info_login_trop_court_car_pluriel' => 'Le login doit contenir au moins @nb@ caractères.', # NEW
	'info_logos' => 'Les logos', # NEW
	'info_maximum' => 'A legtöbb :',
	'info_meme_rubrique' => 'Abban a rovatban',
	'info_message' => 'Üzenet kelte',
	'info_message_efface' => 'ÜZENET TÖRÖLVE',
	'info_message_en_redaction' => 'Az Ön szerkesztés alatti üzenetei',
	'info_message_technique' => 'Műszaki üzenet :',
	'info_messagerie_interne' => 'Belső levelezés',
	'info_mise_a_niveau_base' => 'A SQL adatbázisa naprakész tétele', # MODIF
	'info_mise_a_niveau_base_2' => '{{Vigyázat!}} Az SPIP fájlait egyik {elöző} változatot telepített fel, mint ami ezelőtt volt ezen a tárhelyen: az adatbázis veszhet, és a honlap többet nem fog működni.<br />{{Telepítse újra az SPIP fájlait.}}', # MODIF
	'info_modification_enregistree' => 'Votre modification a été enregistrée', # NEW
	'info_modifier_auteur' => 'Modifier l\'auteur :', # NEW
	'info_modifier_rubrique' => 'A rovat módosítása :',
	'info_modifier_titre' => 'Módosítás : @titre@',
	'info_mon_site_spip' => 'Az én SPIP honlapom',
	'info_mot_sans_groupe' => '(Csoport nélküli szavak...)',
	'info_moteur_recherche' => 'Integrált kereső motor',
	'info_moyenne' => 'Átlagosan :',
	'info_multi_articles' => 'A nyelvi menü aktiválása a cikkeknél ?',
	'info_multi_cet_article' => 'A cikk nyelve :',
	'info_multi_langues_choisies' => 'Lejjebb jelölje ki a szerzők által használható nyelveket.
  A honlapján már használt nyelveket (elsőknek jelennek meg) nem lehet inaktiválni.',
	'info_multi_objets' => '@objets@ : activer le menu de langue', # NEW
	'info_multi_rubriques' => 'A nyelvi menü aktiválása a rovatoknál ?',
	'info_multi_secteurs' => '... csak a gyökérben található rovatok esetén ?',
	'info_nb_articles' => '@nb@ articles', # NEW
	'info_nb_articles_syndiques' => '@nb@ articles syndiqués', # NEW
	'info_nb_auteurs' => '@nb@ auteurs', # NEW
	'info_nb_messages' => '@nb@ messages', # NEW
	'info_nb_mots_cles' => '@nb@ mots-clés', # NEW
	'info_nb_rubriques' => '@nb@ rubriques', # NEW
	'info_nb_sites' => '@nb@ sites', # NEW
	'info_nb_visiteurs' => '@nb@ visiteurs', # NEW
	'info_nom' => 'Név',
	'info_nom_destinataire' => 'Címzett neve',
	'info_nom_site' => 'Az Ön honlapja neve',
	'info_nombre_articles' => '@nb_articles@ cikk,',
	'info_nombre_partcipants' => 'A VITA RÉSZTVEVŐI :',
	'info_nombre_rubriques' => '@nb_rubriques@ rovat,',
	'info_nombre_sites' => '@nb_sites@ honlap,',
	'info_non_deplacer' => 'Nem kell áthelyezni...',
	'info_non_envoi_annonce_dernieres_nouveautes' => 'SPIP rendszeresen küldhet a honlap legújabb ujdonságait
  (nemrég publikált cikkek és hírek).',
	'info_non_envoi_liste_nouveautes' => 'Nem kell küldeni az ujdonságok listáját',
	'info_non_modifiable' => 'nem módosítható',
	'info_non_suppression_mot_cle' => 'ne akarom törölni ezt a kulcsszót.',
	'info_note_numero' => 'Note @numero@', # NEW
	'info_notes' => 'Megjegyzések',
	'info_nouveaux_message' => 'Új üzenetek',
	'info_nouvel_article' => 'Új cikk',
	'info_nouvelle_traduction' => 'Új fordítás :',
	'info_numero_article' => 'CIKK SZÁMA :',
	'info_obligatoire_02' => '[Kötelező]', # MODIF
	'info_option_accepter_visiteurs' => 'A látogatói beíratkozás engedélyezése ',
	'info_option_faire_suivre' => 'A fórumok üzenetei továbbítása a cikkek szerzői felé',
	'info_option_ne_pas_accepter_visiteurs' => 'Látogatói beíratkozás tiltása',
	'info_options_avancees' => 'B?VÍTETT OPCIÓK',
	'info_ortho_activer' => 'A helyesírás ellenőrző aktiválása',
	'info_ortho_desactiver' => 'A helyesírás ellenőrző leállítása',
	'info_ou' => 'vagy...',
	'info_page_interdite' => 'Tiltott oldal',
	'info_par_nom' => 'par nom', # NEW
	'info_par_nombre_article' => '(cikk darabszám szerint)', # MODIF
	'info_par_statut' => 'par statut', # NEW
	'info_par_tri' => '\'(par @tri@)\'', # NEW
	'info_passe_trop_court' => 'A jelszó túl rövid.',
	'info_passe_trop_court_car_pluriel' => 'Le mot de passe doit contenir au moins @nb@ caractères.', # NEW
	'info_passes_identiques' => 'A két jelszó nem egyforma.',
	'info_pense_bete_ancien' => 'Az Ön régebbi emlékeztet?i', # MODIF
	'info_plus_cinq_car' => 'több, mint 5 karakter',
	'info_plus_cinq_car_2' => '(több, mint 5 karakter)',
	'info_plus_trois_car' => '(több, mint 3 karakter)',
	'info_popularite' => 'Népszer?ség : @popularite@ ; látógatások : @visites@',
	'info_popularite_4' => 'népszerűség : @popularite@ ; látógatások : @visites@',
	'info_post_scriptum' => 'Útóírat',
	'info_post_scriptum_2' => 'Útóírat:',
	'info_pour' => 'erre',
	'info_preview_admin' => 'Csak az adminisztrátorok előnézhetik a honlapot',
	'info_preview_comite' => 'Minden szerző előnézheti a honlapot',
	'info_preview_desactive' => 'Az előnézet teljesen van leállítva',
	'info_preview_texte' => 'Lehetséges előnézni a honlapot, mintha az összes cikk és hír (legalább "javasolt" státusszal) publikálva lenne. Ezt a lehetőséget csak az adminisztrátoroknak, az összes szerzőnek, vagy senkinek kell adni ?',
	'info_principaux_correspondants' => 'A főbb levelezőtársai',
	'info_procedez_par_etape' => 'lépésről lépésre járjon el',
	'info_procedure_maj_version' => 'A naprakésztételes eljárást kell indítani ahhoz, hogy
 adaptáljuk az adatbázist az SPIP új változatához.',
	'info_proxy_ok' => 'Test du proxy réussi.', # NEW
	'info_ps' => 'U.Í',
	'info_publier' => 'publikál',
	'info_publies' => 'Az Ön publikált cikkei',
	'info_question_accepter_visiteurs' => 'Amennyiben a honlapja vázaiban a látógatók beíratkozhatnak privát részre való hozzáférés nélkül, akkor a lenti opciót kell kijelölni :',
	'info_question_inscription_nouveaux_redacteurs' => 'Elfogadja-e az új szerzők beíratkozását a nyilvanos honlapról ? Amennyiben elfogadja, akkor a látogatók beíratkozhatnak
  egy automatizált űrlapon és majd hozzáférnek a privát részre, saját cikkei javaslattételére. <blockquote><i>A beíratkozási fázis során,
a felhasználók automatikus emailt kapnak,
  mely a privát reszhez szükséges hozzáférési kódokat tartalmazza.
 Bizonyos szolgáltatók inaktiválják az emailküldést a szerverükről : ilyen esetben lehetetlen az automatikus beíratkozás.', # MODIF
	'info_question_utilisation_moteur_recherche' => 'Kivánja-e használni az SPIP integrált kereső motorját ?
 (annak inaktiválása gyorsítja a rendszer működését.)',
	'info_question_vignettes_referer_non' => 'Ne pas afficher les captures des sites d\'origine des visites', # NEW
	'info_qui_edite' => '@nom_auteur_modif@ a travaillé sur ce contenu il y a @date_diff@ minutes', # MODIF
	'info_racine_site' => 'Honlap teteje',
	'info_recharger_page' => 'Legyen szíves újratölteni ezt az oldalt egy kis idő múlva.',
	'info_recherche_auteur_a_affiner' => 'Túl sok eredmény erre "@cherche_auteur@" ; legyen szíves szükíteni a keresést.',
	'info_recherche_auteur_ok' => 'Több szerző talált erre "@cherche_auteur@":',
	'info_recherche_auteur_zero' => '"@cherche_auteur@" nincs találat.',
	'info_recommencer' => 'Még egyszer, legyen szíves.',
	'info_redacteur_1' => 'Szerző',
	'info_redacteur_2' => 'van hozzáférése az (<i>ajánlott</i>) privát részre',
	'info_redacteurs' => 'Szerzők',
	'info_redaction_en_cours' => 'SZERKESZTÉS ALATT VAN',
	'info_redirection' => 'Átirányítás',
	'info_redirection_activee' => 'La redirection est activée.', # NEW
	'info_redirection_desactivee' => 'La redirection a été supprimée.', # NEW
	'info_refuses' => 'Az Ön elutasított cikkei',
	'info_reglage_ldap' => 'Opciók: <b>LDAP importálás beállítása</b>', # MODIF
	'info_renvoi_article' => '<b>Átirányítás.</b> Ez a cikk erre az oldalra hivatkozik:', # MODIF
	'info_reserve_admin' => 'Csak az adminisztrátork módosíthatják ezt a címet.',
	'info_restreindre_rubrique' => 'Korlátozni a kezelést a következő rubrikára :',
	'info_resultat_recherche' => 'Keresés eredményei :',
	'info_rubriques' => 'Rovatok',
	'info_rubriques_02' => 'rovatok',
	'info_rubriques_trouvees' => 'Talált rovatok',
	'info_rubriques_trouvees_dans_texte' => 'Talált rovatok (a szövegben)',
	'info_sans_titre' => 'Cím nélkül',
	'info_selection_chemin_acces' => '<b>Válassza</b> lejjebb az elérési utat a címtárban :',
	'info_signatures' => 'aláírások',
	'info_site' => 'Honlap',
	'info_site_2' => 'honlap :',
	'info_site_min' => 'honlap',
	'info_site_reference_2' => 'Felvett honlap',
	'info_site_web' => 'HONLAP  :', # MODIF
	'info_sites' => 'honlapok',
	'info_sites_lies_mot' => 'A kulcsszóhoz kötött felvett honlapok',
	'info_sites_proxy' => 'Proxy használata',
	'info_sites_trouves' => 'Talált honlapok',
	'info_sites_trouves_dans_texte' => 'Talált honlapok (a szövegben)',
	'info_sous_titre' => 'Alcím :',
	'info_statut_administrateur' => 'Adminisztrátor',
	'info_statut_auteur' => 'A szerző státusza :', # MODIF
	'info_statut_auteur_2' => 'Je suis', # NEW
	'info_statut_auteur_a_confirmer' => 'Megerősítendő beíratkozás',
	'info_statut_auteur_autre' => 'Egyéb státusz :',
	'info_statut_efface' => 'Törölt',
	'info_statut_redacteur' => 'Szerző',
	'info_statut_utilisateurs_1' => 'Az importált felhasználók alapértelmezett státusza',
	'info_statut_utilisateurs_2' => 'Válassza azt a státuszt, ami lesz hozzárendelve az LDAP címtárban lévő személyekhez, ha csatlakoznak legelőször. Később ez az érték egyénileg lesz módosítható.',
	'info_suivi_activite' => 'A szerkesztői tevékenység követése',
	'info_surtitre' => 'Előcím :',
	'info_syndication_integrale_1' => 'Az Őn honlapja szidikálási (RSS) fájlokat javasol (lásd « <a href="@url@">@titre@</a> »).',
	'info_syndication_integrale_2' => 'A cikkek teljes tartalmát kiván-e átadni, vagy csak egy néhányszáz karakteres összefoglalást?',
	'info_table_prefix' => 'Vous pouvez modifier le préfixe du nom des tables de données (ceci est indispensable lorsque l\'on souhaite installer plusieurs sites dans la même base de données). Ce préfixe s\'écrit en lettres minuscules, non accentuées, et sans espace.', # NEW
	'info_taille_maximale_images' => 'SPIP va tester la taille maximale des images qu\'il peut traiter (en millions de pixels).<br /> Les images plus grandes ne seront pas réduites.', # NEW
	'info_taille_maximale_vignette' => 'A rendszer által generált bélyegképek legnagyobb mérete :',
	'info_terminer_installation' => 'Most bejefezheti a szabványos telepítési eljárást.',
	'info_texte' => 'Szöveg',
	'info_texte_explicatif' => 'Magyarázat',
	'info_texte_long' => '(hosszú a szöveg : tehát több részben bontva jelenik meg, melyek össze lesznek hozva jóváhagyás után.)',
	'info_texte_message' => 'Üzenete szövege :', # MODIF
	'info_texte_message_02' => 'Üzenet szövege',
	'info_titre' => 'Cím :',
	'info_total' => 'Összesen :',
	'info_tous_articles_en_redaction' => 'Az összes szerkesztés alatti cikk',
	'info_tous_articles_presents' => 'Az összes publikált cikk abban a rovatban',
	'info_tous_articles_refuses' => 'Tous les articles refusés', # NEW
	'info_tous_les' => 'minden :',
	'info_tous_redacteurs' => 'Hírdetések minden szerző részére',
	'info_tout_site' => 'A egész honlap',
	'info_tout_site2' => 'A cikk nem lett lefordítva erre a nyelvre.',
	'info_tout_site3' => 'A cikk le lett fordítva arra a nyelvre, de később módosült az eredeti. A fordítást frissíteni kell.',
	'info_tout_site4' => 'A cikk le lett fordítva erre a nyelvre, és naprakész a fordítás.',
	'info_tout_site5' => 'Eredeti cikk.',
	'info_tout_site6' => '<b>Vigyázat :</b> csak az eredeti cikkek jelennek meg.
A fordítások az eredetihez vannak csatolva olyan színben,
ami állapotát jelzi :',
	'info_traductions' => 'Traductions', # NEW
	'info_travail_colaboratif' => 'Együttműködési munka a cikkeken',
	'info_un_article' => 'egy cikk,',
	'info_un_site' => 'egy honlap,',
	'info_une_rubrique' => 'egy rovat,',
	'info_une_rubrique_02' => '1 rovat',
	'info_url' => 'URL :',
	'info_url_proxy' => 'URL du proxy', # NEW
	'info_url_site' => 'HONLAP URL-JE :',
	'info_url_test_proxy' => 'URL de test', # NEW
	'info_urlref' => 'Hiperhivatkozás :',
	'info_utilisation_spip' => 'Mostantól kezdheti használni a publikálási rendszert...',
	'info_visites_par_mois' => 'Megjelenítés havonta :',
	'info_visiteur_1' => 'Vendége',
	'info_visiteur_2' => 'a publikus honlapnak',
	'info_visiteurs' => 'Látógatók',
	'info_visiteurs_02' => 'A nyilvános honlap vendégei',
	'info_webmestre_forces' => 'Les webmestres sont actuellement définis dans <tt>@file_options@</tt>.', # NEW
	'install_adresse_base_hebergeur' => 'Adresse de la base de données attribuée par l\'hébergeur', # NEW
	'install_base_ok' => 'La base @base@ a été reconnue', # NEW
	'install_connect_ok' => 'La nouvelle base a bien été déclarée sous le nom de serveur @connect@.', # NEW
	'install_echec_annonce' => 'A telepítés valószinűleg nem fog sikerülni, vagy a honlap nem fog megfelelően működni...',
	'install_extension_mbstring' => 'Azzal nem működik az SPIP :',
	'install_extension_php_obligatoire' => 'SPIP a PHP-t igényli :',
	'install_login_base_hebergeur' => 'Login de connexion attribué par l\'hébergeur', # NEW
	'install_nom_base_hebergeur' => 'Nom de la base attribué par l\'hébergeur :', # NEW
	'install_pas_table' => 'Base actuellement sans tables', # NEW
	'install_pass_base_hebergeur' => 'Mot de passe de connexion attribué par l\'hébergeur', # NEW
	'install_php_version' => 'PHP version @version@ insuffisant (minimum = @minimum@)', # NEW
	'install_select_langue' => 'Válasszon egy nyelvet és kattintson a « következő » gombra a telepítési folyamat indítására.',
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
	'intem_redacteur' => 'szerző',
	'intitule_licence' => 'Licence', # NEW
	'item_accepter_inscriptions' => 'Elfogadni a beíratkozásokat',
	'item_activer_messages_avertissement' => 'A figyelmeztető üzenetek aktiválása',
	'item_administrateur_2' => 'adminisztrátor',
	'item_afficher_calendrier' => 'Megjelenítés a naptárban',
	'item_autoriser_documents_joints' => 'Cikkekhez csatolt dokumentumok engedélyezése',
	'item_autoriser_documents_joints_rubriques' => 'Rovatokban lévő dokumentumok engedélyezése',
	'item_autoriser_syndication_integrale' => 'A cikkek teljes tartalma a szindikálási fájlokban',
	'item_choix_administrateurs' => 'az adminisztrátorok',
	'item_choix_generation_miniature' => 'Bélyegképek automatikus létrehozása.',
	'item_choix_non_generation_miniature' => 'A bélyegképeket nem kell létrehozni.',
	'item_choix_redacteurs' => 'a szerzők',
	'item_choix_visiteurs' => 'a nyilvános honlap látógatói',
	'item_creer_fichiers_authent' => 'A .htpasswd tipusú fájlok létrehozása',
	'item_limiter_recherche' => 'Keresés a honlapon',
	'item_login' => 'Login',
	'item_messagerie_agenda' => 'Activer la messagerie et l’agenda', # NEW
	'item_mots_cles_association_articles' => 'cikkekre',
	'item_mots_cles_association_rubriques' => 'rovatokra',
	'item_mots_cles_association_sites' => 'felvett, vagy szindikált honlaopkra',
	'item_non' => 'Nem',
	'item_non_accepter_inscriptions' => 'Beíratkozások elutasítása',
	'item_non_activer_messages_avertissement' => 'Nincs figyelmeztető üzenet',
	'item_non_afficher_calendrier' => 'Nincs megjelenítés a naptárban',
	'item_non_autoriser_documents_joints' => 'Dokumentumok tiltása a cikkekben',
	'item_non_autoriser_documents_joints_rubriques' => 'Dokumentumok tiltása a rovatokban',
	'item_non_autoriser_syndication_integrale' => 'Csak egy összefoglalást átadni',
	'item_non_compresseur' => 'Désactiver la compression', # NEW
	'item_non_creer_fichiers_authent' => 'Nem kell létrehozni ezeket a fájlokat',
	'item_non_gerer_statistiques' => 'Nem kell kezelni a statisztikákat',
	'item_non_limiter_recherche' => 'Keresés bővítése a felvett honlapok tartalmáig',
	'item_non_messagerie_agenda' => 'Désactiver la messagerie et l’agenda', # NEW
	'item_non_publier_articles' => 'Nem kell publikálni a cikkeket az adott publikálási dátum előtt.',
	'item_non_utiliser_moteur_recherche' => 'Nem kell motort használni',
	'item_nouvel_auteur' => 'Új szerző',
	'item_nouvelle_rubrique' => 'Új rovat',
	'item_oui' => 'Igen',
	'item_publier_articles' => 'A cikkek publikálása, publikálási dátumtól függetlenül.',
	'item_reponse_article' => 'Hozzászólás a cikkhez',
	'item_utiliser_moteur_recherche' => 'Kereső motor használata',
	'item_version_html_max_html4' => 'Se limiter au HTML4 sur le site public', # NEW
	'item_version_html_max_html5' => 'Permettre le HTML5', # NEW
	'item_visiteur' => 'vendég',

	// J
	'jour_non_connu_nc' => 'névtelen',

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
	'lien_ajout_destinataire' => 'A címzett hozzáadása',
	'lien_ajouter_auteur' => 'A szerző hozzáadása',
	'lien_ajouter_participant' => 'Egy résztvevő hozzáadása',
	'lien_ajouter_une_rubrique' => 'Ajouter cette rubrique', # NEW
	'lien_email' => 'email',
	'lien_nom_site' => 'HONLAP NEVE :',
	'lien_retirer_auteur' => 'A szerző eltávolítása',
	'lien_retirer_rubrique' => 'Retirer la rubrique', # NEW
	'lien_retirer_tous_auteurs' => 'Retirer tous les auteurs', # NEW
	'lien_retirer_toutes_rubriques' => 'Retirer toutes les rubriques', # NEW
	'lien_retrait_particpant' => 'a résztvevő eltávolítása',
	'lien_site' => 'honlap',
	'lien_supprimer_rubrique' => 'a rovat törlése',
	'lien_tout_deplier' => 'Minden kibontása',
	'lien_tout_replier' => 'Minden összecsukása',
	'lien_tout_supprimer' => 'Tout supprimer', # NEW
	'lien_trier_nom' => 'Név szerinti sorbarendezés',
	'lien_trier_nombre_articles' => 'Cikk darabszám szerinti sorbarendezés',
	'lien_trier_statut' => 'Státusz szerinti sorbarendezés',
	'lien_voir_en_ligne' => 'JELENLEG :',
	'logo_article' => 'A CIKK LOGOJA', # MODIF
	'logo_auteur' => 'A SZERZŐ LOGOJA', # MODIF
	'logo_rubrique' => 'ROVAT LOGOJA', # MODIF
	'logo_site' => 'A HONLAP LOGOJA', # MODIF
	'logo_standard_rubrique' => 'A ROVATOK SZABVÁNYOS LOGOJA', # MODIF
	'logo_survol' => 'LEBEGŐ LOGO', # MODIF

	// M
	'menu_aide_installation_choix_base' => 'Adatbázis kiválasztása',
	'module_fichier_langue' => 'Nyelvi fájl',
	'module_raccourci' => 'Röviditések',
	'module_texte_affiche' => 'Megjelenített szöveg',
	'module_texte_explicatif' => 'A következő rövidítések beszúrhatók a nyilvános honlap csontvázaiba. Automatikusan lesznek lefordítva, amennyiben létezik egy nyelvi fájl.',
	'module_texte_traduction' => 'A « @module@ » nyelvi fájl létezik :',
	'mois_non_connu' => 'ismeretlen',

	// N
	'nouvelle_version_spip' => 'La version @version@ de SPIP est disponible', # NEW

	// O
	'onglet_contenu' => 'Contenu', # NEW
	'onglet_declarer_une_autre_base' => 'Déclarer une autre base', # NEW
	'onglet_discuter' => 'Discuter', # NEW
	'onglet_documents' => 'Documents', # NEW
	'onglet_interactivite' => 'Interactivité', # NEW
	'onglet_proprietes' => 'Propriétés', # NEW
	'onglet_repartition_actuelle' => 'jelenleg',
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
	'plugin_etat_developpement' => 'Fejlesztés alatt',
	'plugin_etat_experimental' => 'kisérlet jellegű',
	'plugin_etat_stable' => 'stabil',
	'plugin_etat_test' => 'tesztelés alatt',
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
	'plugins_liste' => 'plugin lista',
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
	'repertoire_plugins' => 'Mappa :',

	// S
	'sans_heure' => 'sans heure', # NEW
	'statut_admin_restreint' => '(korlátolt admin)',

	// T
	'tache_cron_asap' => 'Tache CRON @function@ (ASAP)', # NEW
	'tache_cron_secondes' => 'Tache CRON @function@ (toutes les @nb@ s)', # NEW
	'taille_cache_image' => 'Az SPIP által kalkulált képek (dok. bélyegképei, grafikusan megjelenő címek, TeX formatumú matek függvények...) @taille@ méretű helyet foglalnak a @dir@ nevű mappában.',
	'taille_cache_infinie' => 'Ennél a honlapnál nincs méretkorlátozás a <code>CACHE/</code> mappában.',
	'taille_cache_maxi' => 'SPIP próbálja korlátozni a <code>CACHE/</code> mappa méretét kb. <b>@octets@</b> méretre.',
	'taille_cache_moins_de' => 'La taille du cache est de moins de @octets@.', # NEW
	'taille_cache_octets' => 'A cache mérete jelenleg @octets@.', # MODIF
	'taille_cache_vide' => 'A cache üres.',
	'taille_repertoire_cache' => 'Cache mappa mérete',
	'text_article_propose_publication' => 'Publikálásra javasolt cikk. Ne habozzon hozzászólni a cikkhez kötött fórum segítségével (az oldal végén).', # MODIF
	'texte_acces_ldap_anonyme_1' => 'Bizonyos LDAP szerverek nem fogadják el a névtelen hozzáférést. Ilyen esetben egy azonosítót kell jelezni ahhoz, hogy lehessen keresni adatokat a címtárban. Legtöbb esetben azonban, a következő mezők üresen maradhatnak.',
	'texte_admin_effacer_01' => 'Ez a parancs az adatbázis <i>egész</i> tartalmát törli,
bele értve az <i>összes</i> szerzői, illetve adminisztrátori hozzáférést. Miután futtata, akkor indítani kell az
SPIP újratélépítését egy újabb adatbázis létrehozására, valamint egy első adminisztrátori hozzáférést.',
	'texte_adresse_annuaire_1' => '(Ha az Ön címtára ugyanazon a gépen van telepítve, mint ez a honlap, akkor valószínűleg «localhost»-ról van szó.)',
	'texte_ajout_auteur' => 'A következő szerző lett hozzátéve a cikkhez :',
	'texte_annuaire_ldap_1' => 'A címtárhoz van hozzáférése (LDAP), akkor ezt az SPIP-be való a felhasználók automatikus importálására használhatja.',
	'texte_article_statut' => 'Ez a cikk :',
	'texte_article_virtuel' => 'Virtuális cikk',
	'texte_article_virtuel_reference' => '<b>Virtuális cikk :</b> SPIP honlapján felvett cikk, de másik URL felé átirányítva. Az átirányítás megszüntetésére törölje a fenti URL-t.',
	'texte_aucun_resultat_auteur' => 'Nincs találat erre "@cherche_auteur@"',
	'texte_auteur_messagerie' => 'A honlap állandóan jelezheti a csatlakozott szerzők listáját, ami közvetlen üzenetcserét tesz lehetővé.  Úgy is döntheti, hogy nem szerepel a listában (Ön "láthatatlan" a többi felhasználók számára).',
	'texte_auteur_messagerie_1' => 'Ez a honlap engedi az üzenetcserét és a magán fórumok létrehozását a résztvevők között. Úgy dönhet, hogy nem vesz részt ezekben.',
	'texte_auteurs' => 'A SZERZŐ',
	'texte_choix_base_1' => 'Válassza az adatbázist :',
	'texte_choix_base_2' => 'A SQL szerver több adatbázist tartalmaz.', # MODIF
	'texte_choix_base_3' => '<b>Jelölje</b> azt, amit az Ön Web szolgaltatója adta:', # MODIF
	'texte_choix_table_prefix' => 'Préfixe des tables :', # NEW
	'texte_commande_vider_tables_indexation' => 'Ezt a parancsot használja a használt indexálási táblák törlésére, melyeket használ az SPIP belső kereső motorja. Ettól tárhelyet lehet visszanyerni.',
	'texte_compatibilite_html' => 'Vous pouvez demander à SPIP de produire, sur le site public, du code compatible avec la norme <i>HTML4</i>, ou lui permettre d\'utiliser les possibilités plus modernes du <i>HTML5</i>.', # NEW
	'texte_compatibilite_html_attention' => 'Il n\'y a aucun risque à activer l\'option <i>HTML5</i>, mais si vous le faites, les pages de votre site devront commencer par la mention suivante pour rester valides : <code>&lt;!DOCTYPE html&gt;</code>.', # NEW
	'texte_compresse_ou_non' => '(ez tömörítve lehet, vagy nem)',
	'texte_compte_element' => '@count@ darab',
	'texte_compte_elements' => '@count@ darab',
	'texte_conflit_edition_correction' => 'Veuillez contrôler ci-dessous les différences entre les deux versions du texte ; vous pouvez aussi copier vos modifications, puis recommencer.', # NEW
	'texte_connexion_mysql' => 'Ellenőrizze a Web szolgáltatója által adott információkat : található az, ha fut SQL, illetve annak csatlakozási paraméterei.', # MODIF
	'texte_contenu_article' => '(Cikk tartalma néhány szóban.)',
	'texte_contenu_articles' => 'A honlap felépítése alapján, úgy döntheti,
  hogy a cikkek bizonyos elemei nincsenek kihasználva.
  Használja a lenti listát ahhoz, hogy jelezze milyen elemek állnak rendelkezésre.',
	'texte_crash_base' => 'Ha széttört az adatbázis
  egy automatikus javítást kisérletezhet.',
	'texte_creer_rubrique' => 'Mielőbb írhat cikkeket,<br /> egy rovatot kell létrehozni.', # MODIF
	'texte_date_creation_article' => 'CIKK LÉTREHOZÁSÁNAK IDŐPONTJA :',
	'texte_date_creation_objet' => 'Date de création :', # on ajoute le ":" NEW
	'texte_date_publication_anterieure' => 'Elöző szerkesztés dátuma :',
	'texte_date_publication_anterieure_nonaffichee' => 'Nem kell megjeleníteni az elöző szerkesztés(ek) időpontját.',
	'texte_date_publication_article' => 'NYILVÁNOS PUBLIKÁLÁS IDŐPONTJA :',
	'texte_date_publication_objet' => 'Date de publication en ligne :', # NEW
	'texte_descriptif_petition' => 'Az aláírásgyűjtés leírása',
	'texte_descriptif_rapide' => 'Rövid leírás',
	'texte_effacer_base' => 'Az SPIP adatbázisa törlése',
	'texte_effacer_donnees_indexation' => 'Az indexálási adatok törlése',
	'texte_effacer_statistiques' => 'Effacer les statistiques', # NEW
	'texte_en_cours_validation' => 'Az alábbi híreket és cikkeket javasolták publikálásra. Szóljon hozzá a hozzájuk csatolt fórumokban.', # MODIF
	'texte_enrichir_mise_a_jour' => 'A szerkesztést lehet szépíteni a « nyomdai jelek » segítségével.',
	'texte_fichier_authent' => '<b>SPIP-nek kell-e létrehoznia spéciális <tt>.htpasswd</tt>
  és <tt>.htpasswd-admin</tt> fájlokat a @dossier@ mappában?</b><p>
  Azok a fájlok használhatók a szerzői és adminisztrátori hozzáférés korlátozására bizonyos helyeken
  (például külső statistikai program).</p><p>
  Ha nem kell, ezt az opciót ki lehet hagyni
  az alapértelmezett értékkel (nincs fájllétrehozás).</p>', # MODIF
	'texte_informations_personnelles_1' => 'Most a rendszer fog létrehozni egy személyes hozzáférést Önnek.',
	'texte_informations_personnelles_2' => '(Megjegyzés : ha újratelepítésról van szó, és még mindig megy a hozzáférése, akkor', # MODIF
	'texte_introductif_article' => '(A cikk bevezető szövege.)',
	'texte_jeu_caractere' => 'Az Őn honlapján ajánlott az univerzális abécé (<tt>utf-8</tt>) használata :az összes nyelv megjelenítését teszi lehetővé, és már nem okoz kompatibilitási problemát a korszerű böngészőkkel.',
	'texte_jeu_caractere_2' => 'Vigyázat: E beállítás nem végzi az adatbázisban már meglévő szövegek konvertálását.',
	'texte_jeu_caractere_3' => 'Az Őn honlapja jelenleg a kovetkező karaktertáblát használja :',
	'texte_jeu_caractere_4' => 'Ha nem felel meg adatai állapotának (pl. adatbázisresztaurálás után), vagy ha <em>inditja ezt a honlapot</em>, és szeretne egy másik karaktertáblát használni, ezt az utóbbit jelölje ide :',
	'texte_jeu_caractere_conversion' => 'Megjegyzés : úgy döntheti, hogy véglegesen konvertálja honlapja összes szövegeit (cikkek, hírek, fórumok, stb.) az <tt>utf-8</tt> karakter táblára az <a href="@url@">utf-8-ra konvertálási oldal</a> látógatásával.',
	'texte_lien_hypertexte' => '(Ha az Ön üzenete egy publikált cikkre, vagy egy több információt tartalmazó oldara hivatkozik, lejjeb jelezze az oldal nevét, illetve címét.)',
	'texte_login_ldap_1' => '(Névtelen hozzáféréshez üresen kell hagyni, vagy beírni a teljes utat például « <tt>uid=azennevem, ou=users, dc=azen-domainem, dc=com</tt> ».)',
	'texte_login_precaution' => 'Vigyázat ! Ez az a login, amivel jelenleg csatlakozva van.
 Ezt az űrlapot óvatosan használja...',
	'texte_message_edit' => 'Vigyázat : ezt az üzenetet a honlap összes adminisztrátora módosíthatja, és az összes szerző láthatja. A hirdetéseket csak a honlap legfontosabb eseményeinek közlésére használja.',
	'texte_messagerie_agenda' => 'Une messagerie permet aux rédacteurs du site de communiquer entre eux directement dans l’espace privé du site. Elle est associée à un agenda.', # NEW
	'texte_mise_a_niveau_base_1' => 'Éppen SPIP verziófrissítést végzett.
 Most pedig a honlap adatbázisát kell naprakésszé tenni.',
	'texte_modifier_article' => 'Cikk módosítása :',
	'texte_moteur_recherche_active' => '<b>A kereső motor aktiválva van.</b> Ezt a parancsot használja,
 ha gyors újraindexálást szeretne (például egy mentés resztaurálása után).
 Jegyezze meg, hogy a rendesen (az SPIP    felületen) módosított dokumentumok automatikusan vannak újra indexelve : ez a parancs tehát csak rendkivül esetben hasznos.',
	'texte_moteur_recherche_non_active' => 'A kereső motor nincs aktiválva.',
	'texte_multilinguisme' => 'Amennyiben több nyelvű cikkeket szeretne kezelni, komplex böngészés mellett, egy nyelvi menüt lehet tenni a cikkekhez és/vagy a rovatokhoz, a honlapja felépítésétől függően.', # MODIF
	'texte_multilinguisme_trad' => 'Egy linkeket kezelő rendszert is lehet aktiválni egy cikk különböző fordításai között.', # MODIF
	'texte_non_compresse' => '<i>nincs tömörítve</i> (az Ön szervere nem él azzal a lehetőséggel)',
	'texte_nouveau_message' => 'Új üzenet',
	'texte_nouvelle_version_spip_1' => 'Az SPIP egyik újabb verzióját telepítette.',
	'texte_nouvelle_version_spip_2' => 'Ez az új verzió a szokásosnál teljesebb frissítést igényel. Ha Ön a honlap gazdája, akkor törölje a @connect@ nevű fájlt, folytassa a telepítést ahhoz, hogy az adatbázis csatlakozási paramétereit módosíthassa.<p> (Megjegyzés. : amennyiben elfelejtette a csatlakozási paramétereit, tekintse át a @connect@ nevű fájlt, mielőbb kitörölne...)</p>', # MODIF
	'texte_operation_echec' => 'Menjen az elöző oldalra, jelöljön ki egy másik adatbázist, vagy hozzon létre egy ujat. Ellenőrizze az Ön szolgáltatója által adott információkat.',
	'texte_plus_trois_car' => 'több, mint 3 karakter',
	'texte_plusieurs_articles' => 'Több szerző talált "@cherche_auteur@" szerint:',
	'texte_port_annuaire' => '(Az alapértelmezett érték általában megfelel.)',
	'texte_presente_plugin' => 'Ez az oldal sorolja a rendelkezésre álló plugineket a honlapon. Ezek közül a szükségeseket aktiválhatja a megfelelő négyzet kijelölésével. ',
	'texte_proposer_publication' => 'Ha a cikk be van fejezve,<br /> akkor a publikálását javasolhatja.', # MODIF
	'texte_proxy' => 'Bizonyos esetekben (intranet, biztonságos hálózatok...),
  szükséges használni egy <i>HTTP proxy</i>-t a szindikált honlapok elérésére.
  Ha kell, lejjebb jelezze a címét, ilyen formában
<tt><html>http://proxy:8080</html></tt>. Általában,
  ezt a cellát üresen kell hagyni.', # MODIF
	'texte_publication_articles_post_dates' => 'Hogyan viselkedjen az SPIP azokkal a cikkekel, melynek a
  publikálási dátuma már jövőbeli ?',
	'texte_rappel_selection_champs' => '[Ne felejtse el helyesen kijelölni ezt a mezőt.]',
	'texte_recalcul_page' => 'Ha csak egy oldalt
szeretne frissíteni, akkor menjen inkább a nyilvános részre, és kattintson az «oldal frissítés» gombra.',
	'texte_recapitiule_liste_documents' => 'Ez az oldal felsorolja ezeket a dokumentumokat, melyeket helyezte a rovatokba. Minden egyes dokumentum módosítására, kövesse a rovata oldalához vezető linket.',
	'texte_recuperer_base' => 'Adatbázis javítása',
	'texte_reference_mais_redirige' => 'a cikke fel van véve az Ön SPIP honlapján, de át lett irányítva egy másik URL felé.',
	'texte_requetes_echouent' => '<b>Ha bizonyos SQL lekérdezések rendszeresen és oktalanul hibásak,
 lehetséges, hogy maga az adatbázis az  oka.</b><p>
  SQL ad lehetőséget a táblák javítására, ha véletlenül lett sérülved.
  Itt lehet javítást kezdeményezni ;
  Kudarc esetén, tartson másolatot a képernyőről,
  ami talán nyomokat tartalmaz...</p><p>
 Ha a probléma fennáll, keresse a szolgáltatóját.</p>', # MODIF
	'texte_selection_langue_principale' => 'Lejjebb kijelölhető a honlap « fő nyelve ». Ez a választás - szerencsére ! - nem kötelez írni cikkeket a választott nyelven, de meghatározhatja :
 <ul><li> a nyilvános részen az alapértelmezett dátumformátumot ;</li>
 <li> milyen nyomdai motort használhasson az SPIP a szövegekre ;</li>
 <li> a nyilvános részen használt nyelv a menükben ;</li>
 <li> az alapértelmezett nyelv a privát részben.</li></ul>',
	'texte_sous_titre' => 'Alcím',
	'texte_statistiques_visites' => '(sötét sávok : vasárnap / sötét görbe : átlag kialakulása)',
	'texte_statut_attente_validation' => 'jóváhagyás folyamatban',
	'texte_statut_publies' => 'publikált',
	'texte_statut_refuses' => 'elutasított',
	'texte_suppression_fichiers' => 'EZt a parancsot használja az SPIP cache-ban lévő összes fájlok törlésére
dans le cache SPIP. Ez például eröltethet az összes oldal frissítését, ha jelentős módosításokat végzett a honlap grafikáján, vagy szerkezetén.',
	'texte_sur_titre' => 'Felső cím',
	'texte_table_ok' => ': ez a tábla rendben van.',
	'texte_tables_indexation_vides' => 'A motor indexálási táblai üresek.',
	'texte_tentative_recuperation' => 'Javítási kisérlet',
	'texte_tenter_reparation' => 'Adatbázis javítási kisérlet',
	'texte_test_proxy' => 'Ha ezt a proxyt akarja tesztelni, ide jelezze a tesztelni kívánt honlap címét.',
	'texte_titre_02' => 'Cím :',
	'texte_titre_obligatoire' => '<b>Cím</b> [Kötelező]', # MODIF
	'texte_travail_article' => '@nom_auteur_modif@ dolgozott ezen a cikken @date_diff@ perccel ezelőtt',
	'texte_travail_collaboratif' => 'Ha gyakori az, hogy több szerző ugyanazon a cikken dolgozik,
  akkor a rendszer megjelenítheti a nemrég « megnyilt » cikkeket
  az egyidejű módosítások elkerülésére.
  Ez az opció nincs aktiválva eleve
 a váratlan figyelmeztető üzenetek elkerülésére.
',
	'texte_trop_resultats_auteurs' => 'Túl sok találat erre "@cherche_auteur@" ; szükítse a kérésést.',
	'texte_unpack' => 'Legújabb verzió letöltése',
	'texte_utilisation_moteur_syndiques' => 'Ha az SPIP belső  kereső motorját használja 
    , kétféle módon lehet keresni a szindikált cikkekben, illetve honlapokon.
    <br />Az egyszerűbb korlátózodik a cikkek címeire és rövid ismertetőire.
    <br />
    Egy erőteljesebb módszer lehetővé teszi,
    hogy az SPIP a felvett honlapok szövegein belül is keres .     Ha valamilyen honlapot vesz fel,
    akkor SPIP fogja végezni a keresést a honlap saját szövegében.', # MODIF
	'texte_utilisation_moteur_syndiques_2' => 'Ez a módszer kényszeríti az SPIP rendszeres látógatásokra a felvett honlapokon,
    ami enyhe lassítást okozhat az Ön honlapján.',
	'texte_vide' => 'üres',
	'texte_vider_cache' => 'A cache ürítése',
	'titre_admin_effacer' => 'Műszaki karbantartás',
	'titre_admin_tech' => 'Műszaki karbantartás',
	'titre_admin_vider' => 'Műszaki karbantartás',
	'titre_ajouter_un_auteur' => 'Ajouter un auteur', # NEW
	'titre_ajouter_un_mot' => 'Ajouter un mot-clé', # NEW
	'titre_ajouter_une_rubrique' => 'Ajouter une rubrique', # NEW
	'titre_cadre_afficher_article' => 'Cikkek megjelenítése',
	'titre_cadre_afficher_traductions' => 'A fordítások állápotának megjelenítése a következő nyelvekről :',
	'titre_cadre_ajouter_auteur' => 'ÚJ SZERZŐ :',
	'titre_cadre_interieur_rubrique' => 'A rovaton belül',
	'titre_cadre_numero_auteur' => 'SZERZŐ SZÁMA',
	'titre_cadre_numero_objet' => '@objet@ NUMÉRO :', # NEW
	'titre_cadre_signature_obligatoire' => '<b>Aláírás</b> [Kötelező]<br />', # MODIF
	'titre_config_contenu_notifications' => 'Notifications', # NEW
	'titre_config_contenu_prive' => 'Dans l’espace privé', # NEW
	'titre_config_contenu_public' => 'Sur le site public', # NEW
	'titre_config_fonctions' => 'A honlap konfigurálása',
	'titre_config_langage' => 'Configurer la langue', # NEW
	'titre_configuration' => 'A honlap konfigurálása',
	'titre_configurer_preferences' => 'Configurer vos préférences', # NEW
	'titre_conflit_edition' => 'Conflit lors de l\'édition', # NEW
	'titre_connexion_ldap' => 'Opciók : <b>Az Ön LDAP csatlakozás</b>',
	'titre_groupe_mots' => 'SZÓCSOPORT :',
	'titre_identite_site' => 'Identité du site', # NEW
	'titre_langue_article' => 'A CIKK NYELVE', # MODIF
	'titre_langue_rubrique' => 'A ROVAT NYELVE', # MODIF
	'titre_langue_trad_article' => 'A CIKK NYELVE ÉS FORDÍTÁSAI',
	'titre_les_articles' => 'CIKKEK',
	'titre_messagerie_agenda' => 'Messagerie et agenda', # NEW
	'titre_naviguer_dans_le_site' => 'Böngészni a honlapon...',
	'titre_nouvelle_rubrique' => 'Új rovat',
	'titre_numero_rubrique' => 'ROVAT SZÁMA :',
	'titre_page_admin_effacer' => 'Műszaki karbantartás : adatbázis törlése',
	'titre_page_articles_edit' => 'Módosítás : @titre@',
	'titre_page_articles_page' => 'A cikkek',
	'titre_page_articles_tous' => 'Az egész honlap',
	'titre_page_auteurs' => 'Vendégek',
	'titre_page_calendrier' => 'Naptár @annee@ @nom_mois@',
	'titre_page_config_contenu' => 'A honlap konfigurálása',
	'titre_page_config_fonctions' => 'A honlap konfigurálása',
	'titre_page_configuration' => 'A honlap konfigurálása',
	'titre_page_controle_petition' => 'Aláírásgyűjtések megfigyelése',
	'titre_page_delete_all' => 'Teljes és visszavonhatatlan törlés',
	'titre_page_documents_liste' => 'A rovatok dokumentumai',
	'titre_page_index' => 'Az Ön privát része',
	'titre_page_message_edit' => 'Üzenet szerkesztése',
	'titre_page_messagerie' => 'Az Ön levelezése',
	'titre_page_recherche' => 'A @recherche@ alapú keresés eredménye',
	'titre_page_statistiques_referers' => 'Statisztikák (bejövő linkek)',
	'titre_page_statistiques_signatures_jour' => 'Nombre de signatures par jour', # NEW
	'titre_page_statistiques_signatures_mois' => 'Nombre de signatures par mois', # NEW
	'titre_page_upgrade' => 'SPIP frissítése',
	'titre_publication_articles_post_dates' => 'Utólagosan dátumozott cikkek publikálása',
	'titre_referencer_site' => 'Honlap felvétele :',
	'titre_rendez_vous' => 'TALÁLKOZÓ :',
	'titre_reparation' => 'Javítás',
	'titre_suivi_petition' => 'Aláírásgyűjtések megfigyelése',
	'tls_ldap' => 'Transport Layer Security :', # NEW
	'trad_article_inexistant' => 'Nincs ilyen sorszámú cikk.',
	'trad_article_traduction' => 'A cikk összes változatai :',
	'trad_deja_traduit' => 'Ez a cikk már egy fordítás a jelen cikkről.', # MODIF
	'trad_delier' => 'Visszavenni a cikk csatolását ezekre a fordításokra', # MODIF
	'trad_lier' => 'Ez a cikk egy fordítás erről a cikkről :',
	'trad_new' => 'Írni egy újabb fordítást erről a cikkről', # MODIF

	// U
	'upload_info_mode_document' => 'Déposer cette image dans le portfolio', # NEW
	'upload_info_mode_image' => 'Retirer cette image du portfolio', # NEW
	'utf8_convert_attendez' => 'Várjon egy kicsit, majd töltse újra az oldalt.',
	'utf8_convert_avertissement' => 'Most az adatbázis tartalmát (cikkek, hírek, stb.) készül konvertálni az eredeti <b>@orig@</b> karaktertáblából a <b>@charset@</b> karaktertábla felé.',
	'utf8_convert_backup' => 'Ne felejtse először teljesen megmenteni a honlapját. Azt is ellenőrizze, hogy a szkiptjei és a nyelvi fájlok is @charset@ kompatibilis. Egyébként a módosítások követése, ha aktivált, károsodni fog.', # MODIF
	'utf8_convert_erreur_deja' => 'A honlapja használja már a @charset@ karaktertáblát, szóval felesleges konvertálni...',
	'utf8_convert_erreur_orig' => 'Hiba : a @charset@ karaktertábla nincs támogatva.',
	'utf8_convert_termine' => 'Befejeződött !',
	'utf8_convert_timeout' => 'Fontos :</b> szerver <i>időtúllépése</i> esetén töltse újra az oldalt addig, amíg nem írja ki, hogy "befejeződött".',
	'utf8_convert_verifier' => 'Most ürítse a cache-t, és ellenőrizze, hogy minden rendben van a publikus lapokon. Nagyobb problema esetére egy adatmentés (SQL formátumban) megtörtént a @rep@ n. mappában.',
	'utf8_convertir_votre_site' => 'utf-8 karaktertáblába konvertálodjon a honlapja',

	// V
	'version' => 'Verzió :'
);

?>
