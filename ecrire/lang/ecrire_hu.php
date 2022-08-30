<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de https://trad.spip.net/tradlang_module/ecrire_?lang_cible=hu
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'activer_plugin' => 'Plugin aktiválása',
	'aide_non_disponible' => 'Ez a része a súgónak még nincs lefordítva arra a nyelvre.',
	'auteur' => 'Szerzőr :',
	'avis_acces_interdit' => 'Hozzáférés nincs engedélyezve',
	'avis_article_modifie' => 'Vigyázat, @nom_auteur_modif@ dolgozott ezen a cikken @date_diff@ perccel ezelőtt',
	'avis_aucun_resultat' => 'Nincs eredmény.',
	'avis_chemin_invalide_1' => 'Az Ön által választott elérési út',
	'avis_chemin_invalide_2' => 'nem tűnik érvényesnek. Menjen az elöző oldalra és ellenőrizze a beírt adatokat.',
	'avis_connexion_echec_1' => 'A SQL szerverhez való csatlakozás sikertelen.', # MODIF
	'avis_connexion_echec_2' => 'Menjen az elöző oldalra, és ellenőrizze a beírt adatokat.',
	'avis_connexion_echec_3' => '<b>Megjegyzés:</b> Sok szerver esetén, <b>kérni kell</b> a SQL adatbázishoz való hozzáférés aktválását, mielőbb használhassa. Amennyiben nem tud csatlakozni, ellenőrizze, ha ez az eljárás megtörtént-e.', # MODIF
	'avis_connexion_ldap_echec_1' => 'Az LDAP szerverhez való csatlakozás sikertelen.',
	'avis_connexion_ldap_echec_2' => 'Menjen az elöző oldalra, és ellenőrizze a beírt adatokat.',
	'avis_connexion_ldap_echec_3' => 'Alternatív módon, ne használja az LDAP támogatást felhasználók importálására.',
	'avis_deplacement_rubrique' => 'Vigyázat ! Ez a rovat @contient_breves@ hírt tartalmaz : ha át akarja helyezni, ezt a megerősítési jelölőkockát kell jelölni.',
	'avis_erreur_connexion_mysql' => 'SQL-es csatlakozási hiba', # MODIF
	'avis_espace_interdit' => '<b>Tiltott zóna</b><p>SPIP már telepítve van.', # MODIF
	'avis_lecture_noms_bases_1' => 'A telepítő program nem tudta olvasni a már telepített adatbázisok nevét.',
	'avis_lecture_noms_bases_2' => 'Vagy egyetlen adatbázis sem szabad, vagy az adatbázisokat listázó függvény lett inaktiválva
  biztonsági okokból (ami előfordul számos szolgáltatónál).',
	'avis_lecture_noms_bases_3' => 'A második alternativában elképzelhető, hogy az Ön login nevét viselő adatbázis használható :',
	'avis_non_acces_page' => 'Nincs jogosultsága erre az oldalra.',
	'avis_operation_echec' => 'A művelet sikertelen.',
	'avis_suppression_base' => 'VIGYÁZAT, az adatok törlése visszavonhatatlan',

	// B
	'bouton_acces_ldap' => 'Hozzátenni az LDAP hozzáférést >>', # MODIF
	'bouton_ajouter' => 'Új',
	'bouton_demande_publication' => 'Kérni e cikk publikálását',
	'bouton_desactive_tout' => 'Minden tiltása',
	'bouton_effacer_tout' => 'MINDENT törölni',
	'bouton_envoyer_message' => 'Végleges üzenet: küldés',
	'bouton_modifier' => 'Módosítás',
	'bouton_radio_afficher' => 'Megjelenítés',
	'bouton_radio_apparaitre_liste_redacteurs_connectes' => 'Szerepelni a csatlakozott szerzők listában',
	'bouton_radio_envoi_annonces_adresse' => 'Küldeni a hírdetéseket a következő címre :',
	'bouton_radio_envoi_liste_nouveautes' => 'Küldeni az újdongágok listáját',
	'bouton_radio_non_apparaitre_liste_redacteurs_connectes' => 'Nem szerepelni a szerzők listában',
	'bouton_radio_non_envoi_annonces_editoriales' => 'Ne legyen szerkesztői hírküldés',
	'bouton_redirection' => 'ÁTIRÁNYÍTÁS',
	'bouton_relancer_installation' => 'Telepítés újrakezdése',
	'bouton_suivant' => 'Következő',
	'bouton_tenter_recuperation' => 'Kisérletezni egy javítást',
	'bouton_test_proxy' => 'Probálni a proxyt',
	'bouton_vider_cache' => 'A "cache" ürítése',

	// C
	'cache_modifiable_webmestre' => 'Ezt a paramétert a honlap gazdája módosíthatja.',
	'calendrier_synchro' => 'Amennyiben egy <b>iCal</b>-val kompatibilis szoftvert használ, lehet szinkronizálni e honlap információival.',

	// D
	'date_mot_heures' => 'órák',

	// E
	'email' => 'email',
	'email_2' => 'email :',
	'entree_adresse_annuaire' => 'A címjegyzék címe',
	'entree_adresse_email' => 'Az Ön email címe',
	'entree_base_donnee_1' => 'Adatbázis címe',
	'entree_base_donnee_2' => '(Gyakran ez a cím a honlapé, néha «localhost», néha teljesen üres marad.)',
	'entree_biographie' => 'Rövid önéletrajz pár szóban.',
	'entree_chemin_acces' => '<b>Beírni</b> az elerési utat :', # MODIF
	'entree_cle_pgp' => 'Az Ön PGP kulcsa',
	'entree_contenu_rubrique' => '(Rovat tartalma pár szóban.)',
	'entree_identifiants_connexion' => 'A csatlakozási azonosítói...',
	'entree_informations_connexion_ldap' => 'Ezen az űrlapon írja be az Ön LDAP szerver csatlakozási információkat.
 Ezek az információ szerezhetők a rendszer, vagy a hálozat adminisztrátorától.',
	'entree_infos_perso' => 'Kicsoda Ön ?',
	'entree_interieur_rubrique' => 'Melyik rovatba kerüljön :',
	'entree_liens_sites' => '<b>Hiperhívatkozás</b> (referencia, látógatható honlap...)', # MODIF
	'entree_login' => 'Az Ön felhasználói neve (login)',
	'entree_login_connexion_1' => 'Csatlakozási login',
	'entree_login_connexion_2' => '(Néha megfelel az FTP loginjának; néha üres marad)',
	'entree_mot_passe' => 'Az Ön jelszava',
	'entree_mot_passe_1' => 'Csatlakozási jelszó',
	'entree_mot_passe_2' => '(Néha megfelel az FTP jelszavának; néha üres marad)',
	'entree_nom_fichier' => 'Írja be a fájl nevét @texte_compresse@:',
	'entree_nom_pseudo' => 'Az Ön neve, vagy felhasználói neve',
	'entree_nom_pseudo_1' => '(Az Ön neve vagy felhsználói neve)',
	'entree_nom_site' => 'A honlapja neve',
	'entree_nouveau_passe' => 'Új jelszó',
	'entree_passe_ldap' => 'Jelszó',
	'entree_port_annuaire' => 'A címtár port száma',
	'entree_signature' => 'Aláírás',
	'entree_titre_obligatoire' => '<b>Cím</b> [Kötelező]<br />', # MODIF
	'entree_url' => 'A honlapja címe (URL)',
	'erreur_plugin_fichier_absent' => 'Nem létező fájl',
	'erreur_plugin_fichier_def_absent' => 'Nem létező definiáló fájl',
	'erreur_plugin_nom_fonction_interdit' => 'Tilos függvénynév',
	'erreur_plugin_nom_manquant' => 'Hiányzó plugin név',
	'erreur_plugin_prefix_manquant' => 'Nem definiált plugin név terület',
	'erreur_plugin_tag_plugin_absent' => 'hiányzó &lt;plugin&gt; a definiáló fájlban',
	'erreur_plugin_version_manquant' => 'Hiányzó plugin verzió',

	// I
	'ical_info1' => 'Ez az oldal több módszert mutat ahhoz, hogy maradjon kapcsolatban e honlap életével.',
	'ical_info2' => 'Azokról a technikákról tövábbi információk olvashatók ide <a href="@spipnet@">az SPIP dokumentációja (franciául)</a>.', # MODIF
	'ical_info_calendrier' => 'Két naptár áll rendelkezésére. Az első egy olyan térpkép a honlapról, melyben szerepel az összes publikált cikk. A második pedig a tartalmi hírdetéseket, illetve az Ön utolsó privát üzenetei : egy személyes kulcsnak köszönhetően van fenntartva Ön részére, ami bármikor módosítható a jelszava változtatásával.',
	'ical_methode_http' => 'Letöltés',
	'ical_methode_webcal' => 'Szinkronizálás (webcal://)', # MODIF
	'ical_texte_prive' => 'Ez a naptár, ami szigorúan személyes használatra, informálja Önt a honlap privát tartalmi tevékenységről (feladatok és személyes talákozások, javasolt cikkek és hírek...).',
	'ical_texte_public' => 'Ez a naptár a honlap nyilvános tevékenységének a figyelését teszi lehetővé (publikált cikkek és hírek).',
	'ical_texte_rss' => 'Ön a honlap ujdonságait szindikálhatja bármilyen XML/RSS (Rich Site Summary)tipusú fájlolvasóval. Valamint ez a formátum SPIP részére teszi lehetővé más honlapok publikált újdonságok olvasását (szindikált honlapok).',
	'ical_titre_js' => 'Javascript',
	'ical_titre_mailing' => 'Levelező lista',
	'ical_titre_rss' => '« backend » fájlok (rss)',
	'icone_activer_cookie' => 'A hivatkozási süti (cookie) aktiválása',
	'icone_admin_plugin' => 'Plugin-ek beállítása',
	'icone_afficher_auteurs' => 'Megjeleníteni a szerzőket',
	'icone_afficher_visiteurs' => 'Megjeleníteni a látogatókat',
	'icone_arret_discussion' => 'Megszüntetni a vitahoz való részvételt ',
	'icone_calendrier' => 'Naptár',
	'icone_creer_auteur' => 'Új szerző létrehozása, és hozzárendelése ehhez a cikkekhez',
	'icone_creer_mot_cle' => 'Új kulcsszó létrehozása és hozzárendelése ehhez a cikkhez',
	'icone_creer_rubrique_2' => 'Új rovat létrehozása',
	'icone_modifier_article' => 'A cikk módosítása',
	'icone_modifier_rubrique' => 'A rovat módosítása',
	'icone_retour' => 'Vissza',
	'icone_retour_article' => 'Vissza a cikkhez',
	'icone_supprimer_cookie' => 'A hivatkozási süti (cookie) törlése',
	'icone_supprimer_rubrique' => 'A rovat törlése',
	'icone_supprimer_signature' => 'Az aláírás törlése',
	'icone_valider_signature' => 'Az aláírás érvényesítése',
	'image_administrer_rubrique' => 'Ezt a rubrikát adminisztrálhatja',
	'impossible_modifier_login_auteur' => 'Impossible de modifier le login.', # MODIF
	'impossible_modifier_pass_auteur' => 'Impossible de modifier le mot de passe.', # MODIF
	'info_1_article' => '1 cikk',
	'info_activer_cookie' => 'Egy <b>hivatkozási sütit</b> (cookie) lehet aktiválni, melynek segítségével könnyen át tud menni a nyilvános részről a privát részre.',
	'info_administrateur' => 'Adminisztrátor',
	'info_administrateur_1' => 'Adminisztrátor',
	'info_administrateur_2' => 'honlap (<i>óvatosan használja</i>)',
	'info_administrateur_site_01' => 'Amennyiben Ön a honlap adminisztrátora, legyen szíves',
	'info_administrateur_site_02' => 'kattintani erre a linkre',
	'info_administrateurs' => 'Adminisztrátorok',
	'info_administrer_rubrique' => 'Ezt a rubrikát Ön adminisztrálhatja',
	'info_adresse' => 'ezen a címen :',
	'info_adresse_url' => 'A nyilvános honlap címe (URL)',
	'info_aide_en_ligne' => 'On-line SPIP súgó',
	'info_ajout_image' => 'Ha képeket tesz hozzá, mint cikkhez csatolt dokumentum,
  akkor SPIP automatikusan létre hozhat Önnek kisebb képeket (miniatürök)a beszúrt képekről
  Ez példáúl teszi lehetővé egy képgalléria, vagy egy portfolio automatikus létrehozása.',
	'info_ajouter_rubrique' => 'Újabb adminisztrálandó rovat létrehozása :',
	'info_annonce_nouveautes' => 'Az újdonságok közlése',
	'info_article' => 'cikk',
	'info_article_2' => 'cikk',
	'info_article_a_paraitre' => 'utólagosan dátumozott publikálandó cikkek',
	'info_articles_02' => 'cikk',
	'info_articles_2' => 'Cikkek',
	'info_articles_auteur' => 'A szerző cikkei',
	'info_articles_trouves' => 'Talált cikkek',
	'info_attente_validation' => 'Jóváhagyás alatti cikkei',
	'info_aujourdhui' => 'A mai napon :',
	'info_auteurs' => 'A szerzők',
	'info_auteurs_par_tri' => 'Szerzők@partri@',
	'info_auteurs_trouves' => 'Talált szerzők',
	'info_authentification_externe' => 'Külső autentifikálás',
	'info_avertissement' => 'Figyelmeztetés',
	'info_base_installee' => 'Az Ön adatbázisának struktúrája telepítve van.',
	'info_chapeau' => 'Bevezető',
	'info_chapeau_2' => 'Bevezető :',
	'info_chemin_acces_1' => 'Opciók : <b>Elérési út a címtárban</b>', # MODIF
	'info_chemin_acces_2' => 'Mostántól a címtárban a információk elérési utját kell konfigurálni. Ez az adat nélkülözhetetlen ahhoz, hogy olvashatóak legyenek a felhaszálói profilok a címtárban.',
	'info_chemin_acces_annuaire' => 'Opciók : <b>Elérési út a címtárban', # MODIF
	'info_choix_base' => 'Harmadik lépés :',
	'info_classement_1' => '<sup>.</sup> összesen @liste@',
	'info_classement_2' => '<sup>.-dik</sup> összesen @liste@',
	'info_code_acces' => 'Ne felejtse el a saját hozzáférési kódjait !',
	'info_config_suivi' => 'Ha ez a cím egy levelező listahoz tartozik, lejjebb azt a címet jelezheti, ahova a résztvevők beíratkozhatnak. Ez a cím akár URL lehet (pl. a beíratkozási oldal a Weben), vagy egy specifikus tárgyat tartalmazó email cím (pl.<tt>@adresse_suivi@?subject=subscribe</tt>):',
	'info_config_suivi_explication' => 'Beíratkozhat a honlap levelező listájához. Ilyenkor emailben fogja kapni ezeket a cikkeket, híreket, melyeket javasoltak publikálásra.',
	'info_confirmer_passe' => 'Az új jelszó erősítse meg :',
	'info_connexion_base' => 'Második lépés : <b>Adatbázishoz való csatlakozási próba</b>', # MODIF
	'info_connexion_ldap_ok' => 'Az LDAP csatlakozás sikeres lett.</b><p> Léphet tovább a következőre.</p>', # MODIF
	'info_connexion_mysql' => 'Első lépés : <b>Az Ön SQL csatlakozása</b>', # MODIF
	'info_connexion_ok' => 'A csatlakozás sikeres.',
	'info_contact' => 'Kontakt',
	'info_contenu_articles' => 'Cikkek tartalma',
	'info_creation_paragraphe' => '(Paragrafúsok létrehozására, egyszerűen csak üres sorokat kell hagyni.)', # MODIF
	'info_creation_rubrique' => 'Mielőbb cikkeket írhasson,<br /> legalább egy rubrikát kell létrehozni.<br />', # MODIF
	'info_creation_tables' => 'Negyedik lépés : <b>Az adatbázis táblai létrehozása</b>', # MODIF
	'info_creer_base' => '<b>Létrehozni</b> egy újabb adatbázist :', # MODIF
	'info_dans_rubrique' => 'A rovatban :',
	'info_date_publication_anterieure' => 'Elöző szerkesztés dátuma :',
	'info_date_referencement' => 'A HONLAP ELTÁVOLÍTÁSA DÁTUMA :',
	'info_derniere_etape' => 'Utolsó lépés : <b>Vége van !', # MODIF
	'info_descriptif' => 'Rövid ismertető :',
	'info_discussion_cours' => 'Folyamatban lévő viták',
	'info_ecrire_article' => 'Mielőbb írjon cikkeket, legalább egy rubrikát kell létrehozni.',
	'info_email_envoi' => 'Email cím küldésre (opció)',
	'info_email_envoi_txt' => 'Itt jelezze a használandó feladó címet az email küldésére (ennek híján, a címzett címét használjuk, mint feladói) :',
	'info_email_webmestre' => 'A Webmester email címe (opció)', # MODIF
	'info_envoi_email_automatique' => 'Automatikus email küldés',
	'info_envoyer_maintenant' => 'Azonnali küldés',
	'info_etape_suivante' => 'Következő lépés',
	'info_etape_suivante_1' => 'Léphet a következőre.',
	'info_etape_suivante_2' => 'Léphet a következőre.',
	'info_exportation_base' => 'Adatbázis exportálása @archive@ felé',
	'info_facilite_suivi_activite' => 'Ahhoz, hogy könnyebben lehessen figyelemmel követni a honlap szerkesztői tevékenységét, SPIP emailen küldheti például a publikálási, ill. cikkjóváhagyási kéréseket egy szerzői levelezőlistára.', # MODIF
	'info_fichiers_authent' => 'Azonosítási fájlok « .htpasswd »',
	'info_forums_abo_invites' => 'A honlapja beiratkozásos fórumokat tartalmaz ; tehát a látogatók beíratkozhatnak a nyilvános részen.',
	'info_gauche_admin_tech' => '<b>Ez az oldal csak a honlap gazdai részére elérhető.</b><p> A különböző műszaki karbantartási feladatokra ad lehetőséget. Ezek közül néhany igényel olyan specifikus azonosítási eljárást, ami a honlaphoz FTP elérést követel.</p>', # MODIF
	'info_gauche_admin_vider' => '<b>Ez az oldal csak a honlap gazdai részére elérhető.</b><p> A különböző műszaki karbantartási feladatokra ad lehetőséget. Ezek közül néhany igényel olyan specifikus azonosítási eljárást, ami a honlaphoz FTP elérést követel.</p>', # MODIF
	'info_gauche_auteurs' => 'Itt található a honlap összes szerzője.
 Saját státuszuk az ikon színe szerint van jelölve ( adminisztrátor = zöld; szerző = sárga).',
	'info_gauche_auteurs_exterieurs' => 'A külső szerzők, melyek nem férhetnek a honlaphoz, kék ikonnal vannak jelölve ;
a törölt szerzők pedig kukával vannak jelölve.', # MODIF
	'info_gauche_messagerie' => 'A levelezés lehetővé tesz szerzők közti üzenetcserét, emlékeztetők (saját használatra) megtartását, vagy hírdetések megjelenítését a privát rész főoldalán (amennyiben Ön adminisztrátor).',
	'info_gauche_statistiques_referers' => 'Ez az oldal a <i>referers</i> listáját mutat, vagyis olyan honlapokat, melyeken az Ön honlapjához hivatkozó linkek találhatók, de csak a tegnapi és a mai napra : ez a lista nullázva van 24 óra után.',
	'info_gauche_visiteurs_enregistres' => 'Itt találhatók a honlap nyilvános részén regisztrált látogatók (beíratkozásos fórumok).',
	'info_generation_miniatures_images' => 'Bélyegképek generálása a képekről',
	'info_hebergeur_desactiver_envoi_email' => 'Bizonyos szolgáltatók nem aktiválják az automatikus email küldést a szerverükről. Ilyen esetben, a következő SPIP funkciók nem fognak működni.',
	'info_hier' => 'Tegnap :',
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
	'info_liste_redacteurs_connectes' => 'Jelenleg csatlakozott szerzők listája',
	'info_login_existant' => 'Ez a login már létezik.',
	'info_login_trop_court' => 'A login túl rövid.',
	'info_maximum' => 'A legtöbb :',
	'info_meme_rubrique' => 'Abban a rovatban',
	'info_message_en_redaction' => 'Az Ön szerkesztés alatti üzenetei',
	'info_message_technique' => 'Műszaki üzenet :',
	'info_messagerie_interne' => 'Belső levelezés',
	'info_mise_a_niveau_base' => 'A SQL adatbázisa naprakész tétele', # MODIF
	'info_mise_a_niveau_base_2' => '{{Vigyázat!}} Az SPIP fájlait egyik {elöző} változatot telepített fel, mint ami ezelőtt volt ezen a tárhelyen: az adatbázis veszhet, és a honlap többet nem fog működni.<br />{{Telepítse újra az SPIP fájlait.}}', # MODIF
	'info_modifier_rubrique' => 'A rovat módosítása :',
	'info_modifier_titre' => 'Módosítás : @titre@',
	'info_mon_site_spip' => 'Az én SPIP honlapom',
	'info_moyenne' => 'Átlagosan :',
	'info_multi_cet_article' => 'A cikk nyelve :',
	'info_multi_langues_choisies' => 'Lejjebb jelölje ki a szerzők által használható nyelveket.
  A honlapján már használt nyelveket (elsőknek jelennek meg) nem lehet inaktiválni.',
	'info_multi_secteurs' => '... csak a gyökérben található rovatok esetén ?',
	'info_nom' => 'Név',
	'info_nom_destinataire' => 'Címzett neve',
	'info_nom_site' => 'Az Ön honlapja neve',
	'info_nombre_articles' => '@nb_articles@ cikk,',
	'info_nombre_rubriques' => '@nb_rubriques@ rovat,',
	'info_nombre_sites' => '@nb_sites@ honlap,',
	'info_non_deplacer' => 'Nem kell áthelyezni...',
	'info_non_envoi_annonce_dernieres_nouveautes' => 'SPIP rendszeresen küldhet a honlap legújabb ujdonságait
  (nemrég publikált cikkek és hírek).',
	'info_non_envoi_liste_nouveautes' => 'Nem kell küldeni az ujdonságok listáját',
	'info_non_modifiable' => 'nem módosítható',
	'info_non_suppression_mot_cle' => 'ne akarom törölni ezt a kulcsszót.',
	'info_notes' => 'Megjegyzések',
	'info_nouvel_article' => 'Új cikk',
	'info_nouvelle_traduction' => 'Új fordítás :',
	'info_numero_article' => 'CIKK SZÁMA :',
	'info_obligatoire_02' => '[Kötelező]', # MODIF
	'info_option_accepter_visiteurs' => 'A látogatói beíratkozás engedélyezése ',
	'info_option_ne_pas_accepter_visiteurs' => 'Látogatói beíratkozás tiltása',
	'info_options_avancees' => 'B?VÍTETT OPCIÓK',
	'info_ou' => 'vagy...',
	'info_page_interdite' => 'Tiltott oldal',
	'info_par_nombre_article' => '(cikk darabszám szerint)', # MODIF
	'info_passe_trop_court' => 'A jelszó túl rövid.',
	'info_passes_identiques' => 'A két jelszó nem egyforma.',
	'info_plus_cinq_car' => 'több, mint 5 karakter',
	'info_plus_cinq_car_2' => '(több, mint 5 karakter)',
	'info_plus_trois_car' => '(több, mint 3 karakter)',
	'info_popularite' => 'Népszer?ség : @popularite@ ; látógatások : @visites@',
	'info_post_scriptum' => 'Útóírat',
	'info_post_scriptum_2' => 'Útóírat:',
	'info_pour' => 'erre',
	'info_preview_texte' => 'Lehetséges előnézni a honlapot, mintha az összes cikk és hír (legalább "javasolt" státusszal) publikálva lenne. Ezt a lehetőséget csak az adminisztrátoroknak, az összes szerzőnek, vagy senkinek kell adni ?', # MODIF
	'info_procedez_par_etape' => 'lépésről lépésre járjon el',
	'info_procedure_maj_version' => 'A naprakésztételes eljárást kell indítani ahhoz, hogy
 adaptáljuk az adatbázist az SPIP új változatához.',
	'info_ps' => 'U.Í',
	'info_publier' => 'publikál',
	'info_publies' => 'Az Ön publikált cikkei',
	'info_question_accepter_visiteurs' => 'Amennyiben a honlapja vázaiban a látógatók beíratkozhatnak privát részre való hozzáférés nélkül, akkor a lenti opciót kell kijelölni :',
	'info_question_inscription_nouveaux_redacteurs' => 'Elfogadja-e az új szerzők beíratkozását a nyilvanos honlapról ? Amennyiben elfogadja, akkor a látogatók beíratkozhatnak
  egy automatizált űrlapon és majd hozzáférnek a privát részre, saját cikkei javaslattételére. <blockquote><i>A beíratkozási fázis során,
a felhasználók automatikus emailt kapnak,
  mely a privát reszhez szükséges hozzáférési kódokat tartalmazza.
 Bizonyos szolgáltatók inaktiválják az emailküldést a szerverükről : ilyen esetben lehetetlen az automatikus beíratkozás.', # MODIF
	'info_racine_site' => 'Honlap teteje',
	'info_recharger_page' => 'Legyen szíves újratölteni ezt az oldalt egy kis idő múlva.',
	'info_recherche_auteur_zero' => '"@cherche_auteur@" nincs találat.',
	'info_recommencer' => 'Még egyszer, legyen szíves.',
	'info_redacteur_1' => 'Szerző',
	'info_redacteur_2' => 'van hozzáférése az (<i>ajánlott</i>) privát részre',
	'info_redacteurs' => 'Szerzők',
	'info_redaction_en_cours' => 'SZERKESZTÉS ALATT VAN',
	'info_redirection' => 'Átirányítás',
	'info_refuses' => 'Az Ön elutasított cikkei',
	'info_reglage_ldap' => 'Opciók: <b>LDAP importálás beállítása</b>', # MODIF
	'info_renvoi_article' => '<b>Átirányítás.</b> Ez a cikk erre az oldalra hivatkozik:', # MODIF
	'info_reserve_admin' => 'Csak az adminisztrátork módosíthatják ezt a címet.',
	'info_restreindre_rubrique' => 'Korlátozni a kezelést a következő rubrikára :',
	'info_resultat_recherche' => 'Keresés eredményei :',
	'info_rubriques' => 'Rovatok',
	'info_rubriques_02' => 'rovatok',
	'info_rubriques_trouvees' => 'Talált rovatok',
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
	'info_sous_titre' => 'Alcím :',
	'info_statut_administrateur' => 'Adminisztrátor',
	'info_statut_auteur' => 'A szerző státusza :', # MODIF
	'info_statut_auteur_a_confirmer' => 'Megerősítendő beíratkozás',
	'info_statut_auteur_autre' => 'Egyéb státusz :',
	'info_statut_redacteur' => 'Szerző',
	'info_statut_utilisateurs_1' => 'Az importált felhasználók alapértelmezett státusza',
	'info_statut_utilisateurs_2' => 'Válassza azt a státuszt, ami lesz hozzárendelve az LDAP címtárban lévő személyekhez, ha csatlakoznak legelőször. Később ez az érték egyénileg lesz módosítható.',
	'info_suivi_activite' => 'A szerkesztői tevékenység követése',
	'info_surtitre' => 'Előcím :',
	'info_syndication_integrale_1' => 'Az Őn honlapja szidikálási (RSS) fájlokat javasol (lásd « <a href="@url@">@titre@</a> »).',
	'info_syndication_integrale_2' => 'A cikkek teljes tartalmát kiván-e átadni, vagy csak egy néhányszáz karakteres összefoglalást?',
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
	'info_tous_les' => 'minden :',
	'info_tout_site' => 'A egész honlap',
	'info_tout_site2' => 'A cikk nem lett lefordítva erre a nyelvre.',
	'info_tout_site3' => 'A cikk le lett fordítva arra a nyelvre, de később módosült az eredeti. A fordítást frissíteni kell.',
	'info_tout_site4' => 'A cikk le lett fordítva erre a nyelvre, és naprakész a fordítás.',
	'info_tout_site5' => 'Eredeti cikk.',
	'info_tout_site6' => '<b>Vigyázat :</b> csak az eredeti cikkek jelennek meg.
A fordítások az eredetihez vannak csatolva olyan színben,
ami állapotát jelzi :',
	'info_travail_colaboratif' => 'Együttműködési munka a cikkeken',
	'info_un_article' => 'egy cikk,',
	'info_un_site' => 'egy honlap,',
	'info_une_rubrique' => 'egy rovat,',
	'info_une_rubrique_02' => '1 rovat',
	'info_url' => 'URL :',
	'info_urlref' => 'Hiperhivatkozás :',
	'info_utilisation_spip' => 'Mostantól kezdheti használni a publikálási rendszert...',
	'info_visites_par_mois' => 'Megjelenítés havonta :',
	'info_visiteur_1' => 'Vendége',
	'info_visiteur_2' => 'a publikus honlapnak',
	'info_visiteurs' => 'Látógatók',
	'info_visiteurs_02' => 'A nyilvános honlap vendégei',
	'install_echec_annonce' => 'A telepítés valószinűleg nem fog sikerülni, vagy a honlap nem fog megfelelően működni...',
	'install_extension_mbstring' => 'Azzal nem működik az SPIP :',
	'install_extension_php_obligatoire' => 'SPIP a PHP-t igényli :',
	'install_select_langue' => 'Válasszon egy nyelvet és kattintson a « következő » gombra a telepítési folyamat indítására.',
	'intem_redacteur' => 'szerző',
	'item_accepter_inscriptions' => 'Elfogadni a beíratkozásokat',
	'item_activer_messages_avertissement' => 'A figyelmeztető üzenetek aktiválása',
	'item_administrateur_2' => 'adminisztrátor',
	'item_afficher_calendrier' => 'Megjelenítés a naptárban',
	'item_autoriser_syndication_integrale' => 'A cikkek teljes tartalma a szindikálási fájlokban',
	'item_choix_administrateurs' => 'az adminisztrátorok',
	'item_choix_generation_miniature' => 'Bélyegképek automatikus létrehozása.',
	'item_choix_non_generation_miniature' => 'A bélyegképeket nem kell létrehozni.',
	'item_choix_redacteurs' => 'a szerzők',
	'item_choix_visiteurs' => 'a nyilvános honlap látógatói',
	'item_creer_fichiers_authent' => 'A .htpasswd tipusú fájlok létrehozása',
	'item_login' => 'Login',
	'item_mots_cles_association_articles' => 'cikkekre',
	'item_mots_cles_association_rubriques' => 'rovatokra',
	'item_mots_cles_association_sites' => 'felvett, vagy szindikált honlaopkra',
	'item_non' => 'Nem',
	'item_non_accepter_inscriptions' => 'Beíratkozások elutasítása',
	'item_non_activer_messages_avertissement' => 'Nincs figyelmeztető üzenet',
	'item_non_afficher_calendrier' => 'Nincs megjelenítés a naptárban',
	'item_non_autoriser_syndication_integrale' => 'Csak egy összefoglalást átadni',
	'item_non_creer_fichiers_authent' => 'Nem kell létrehozni ezeket a fájlokat',
	'item_non_publier_articles' => 'Nem kell publikálni a cikkeket az adott publikálási dátum előtt.',
	'item_nouvel_auteur' => 'Új szerző',
	'item_nouvelle_rubrique' => 'Új rovat',
	'item_oui' => 'Igen',
	'item_publier_articles' => 'A cikkek publikálása, publikálási dátumtól függetlenül.',
	'item_reponse_article' => 'Hozzászólás a cikkhez',
	'item_visiteur' => 'vendég',

	// J
	'jour_non_connu_nc' => 'névtelen',

	// L
	'lien_ajouter_auteur' => 'A szerző hozzáadása',
	'lien_email' => 'email',
	'lien_nom_site' => 'HONLAP NEVE :',
	'lien_retirer_auteur' => 'A szerző eltávolítása',
	'lien_site' => 'honlap',
	'lien_tout_deplier' => 'Minden kibontása',
	'lien_tout_replier' => 'Minden összecsukása',
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

	// O
	'onglet_repartition_actuelle' => 'jelenleg',

	// P
	'plugin_etat_developpement' => 'Fejlesztés alatt',
	'plugin_etat_experimental' => 'kisérlet jellegű',
	'plugin_etat_stable' => 'stabil',
	'plugin_etat_test' => 'tesztelés alatt',
	'plugins_liste' => 'plugin lista',

	// R
	'repertoire_plugins' => 'Mappa :',
	'required' => '[Kötelező]', # MODIF

	// S
	'statut_admin_restreint' => '(korlátolt admin)', # MODIF

	// T
	'taille_cache_image' => 'Az SPIP által kalkulált képek (dok. bélyegképei, grafikusan megjelenő címek, TeX formatumú matek függvények...) @taille@ méretű helyet foglalnak a @dir@ nevű mappában.',
	'taille_cache_infinie' => 'Ennél a honlapnál nincs méretkorlátozás a <code>CACHE/</code> mappában.',
	'taille_cache_maxi' => 'SPIP próbálja korlátozni a <code>CACHE/</code> mappa méretét kb. <b>@octets@</b> méretre.',
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
	'texte_auteurs' => 'A SZERZŐ',
	'texte_choix_base_1' => 'Válassza az adatbázist :',
	'texte_choix_base_2' => 'A SQL szerver több adatbázist tartalmaz.', # MODIF
	'texte_choix_base_3' => '<b>Jelölje</b> azt, amit az Ön Web szolgaltatója adta:', # MODIF
	'texte_compte_element' => '@count@ darab',
	'texte_compte_elements' => '@count@ darab',
	'texte_connexion_mysql' => 'Ellenőrizze a Web szolgáltatója által adott információkat : található az, ha fut SQL, illetve annak csatlakozási paraméterei.', # MODIF
	'texte_contenu_article' => '(Cikk tartalma néhány szóban.)',
	'texte_contenu_articles' => 'A honlap felépítése alapján, úgy döntheti,
  hogy a cikkek bizonyos elemei nincsenek kihasználva.
  Használja a lenti listát ahhoz, hogy jelezze milyen elemek állnak rendelkezésre.',
	'texte_crash_base' => 'Ha széttört az adatbázis
  egy automatikus javítást kisérletezhet.',
	'texte_creer_rubrique' => 'Mielőbb írhat cikkeket,<br /> egy rovatot kell létrehozni.', # MODIF
	'texte_date_creation_article' => 'CIKK LÉTREHOZÁSÁNAK IDŐPONTJA :',
	'texte_date_publication_anterieure' => 'Elöző szerkesztés dátuma :',
	'texte_date_publication_anterieure_nonaffichee' => 'Nem kell megjeleníteni az elöző szerkesztés(ek) időpontját.',
	'texte_date_publication_article' => 'NYILVÁNOS PUBLIKÁLÁS IDŐPONTJA :',
	'texte_descriptif_rapide' => 'Rövid leírás',
	'texte_effacer_base' => 'Az SPIP adatbázisa törlése',
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
	'texte_jeu_caractere_3' => 'Az Őn honlapja jelenleg a kovetkező karaktertáblát használja :',
	'texte_jeu_caractere_4' => 'Ha nem felel meg adatai állapotának (pl. adatbázisresztaurálás után), vagy ha <em>inditja ezt a honlapot</em>, és szeretne egy másik karaktertáblát használni, ezt az utóbbit jelölje ide :',
	'texte_login_ldap_1' => '(Névtelen hozzáféréshez üresen kell hagyni, vagy beírni a teljes utat például « <tt>uid=azennevem, ou=users, dc=azen-domainem, dc=com</tt> ».)',
	'texte_login_precaution' => 'Vigyázat ! Ez az a login, amivel jelenleg csatlakozva van.
 Ezt az űrlapot óvatosan használja...',
	'texte_mise_a_niveau_base_1' => 'Éppen SPIP verziófrissítést végzett.
 Most pedig a honlap adatbázisát kell naprakésszé tenni.',
	'texte_modifier_article' => 'Cikk módosítása :',
	'texte_multilinguisme' => 'Amennyiben több nyelvű cikkeket szeretne kezelni, komplex böngészés mellett, egy nyelvi menüt lehet tenni a cikkekhez és/vagy a rovatokhoz, a honlapja felépítésétől függően.', # MODIF
	'texte_multilinguisme_trad' => 'Egy linkeket kezelő rendszert is lehet aktiválni egy cikk különböző fordításai között.', # MODIF
	'texte_non_compresse' => '<i>nincs tömörítve</i> (az Ön szervere nem él azzal a lehetőséggel)',
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
	'texte_vide' => 'üres',
	'texte_vider_cache' => 'A cache ürítése',
	'titre_admin_tech' => 'Műszaki karbantartás',
	'titre_admin_vider' => 'Műszaki karbantartás',
	'titre_cadre_afficher_article' => 'Cikkek megjelenítése',
	'titre_cadre_afficher_traductions' => 'A fordítások állápotának megjelenítése a következő nyelvekről :',
	'titre_cadre_ajouter_auteur' => 'ÚJ SZERZŐ :',
	'titre_cadre_interieur_rubrique' => 'A rovaton belül',
	'titre_cadre_numero_auteur' => 'SZERZŐ SZÁMA',
	'titre_cadre_signature_obligatoire' => '<b>Aláírás</b> [Kötelező]<br />', # MODIF
	'titre_config_fonctions' => 'A honlap konfigurálása',
	'titre_configuration' => 'A honlap konfigurálása',
	'titre_connexion_ldap' => 'Opciók : <b>Az Ön LDAP csatlakozás</b>',
	'titre_groupe_mots' => 'SZÓCSOPORT :',
	'titre_langue_article' => 'A CIKK NYELVE', # MODIF
	'titre_langue_rubrique' => 'A ROVAT NYELVE', # MODIF
	'titre_langue_trad_article' => 'A CIKK NYELVE ÉS FORDÍTÁSAI',
	'titre_les_articles' => 'CIKKEK',
	'titre_naviguer_dans_le_site' => 'Böngészni a honlapon...',
	'titre_nouvelle_rubrique' => 'Új rovat',
	'titre_numero_rubrique' => 'ROVAT SZÁMA :',
	'titre_page_articles_edit' => 'Módosítás : @titre@',
	'titre_page_articles_page' => 'A cikkek',
	'titre_page_articles_tous' => 'Az egész honlap',
	'titre_page_calendrier' => 'Naptár @annee@ @nom_mois@',
	'titre_page_config_contenu' => 'A honlap konfigurálása',
	'titre_page_delete_all' => 'Teljes és visszavonhatatlan törlés',
	'titre_page_recherche' => 'A @recherche@ alapú keresés eredménye',
	'titre_page_statistiques_referers' => 'Statisztikák (bejövő linkek)',
	'titre_page_upgrade' => 'SPIP frissítése',
	'titre_publication_articles_post_dates' => 'Utólagosan dátumozott cikkek publikálása',
	'titre_reparation' => 'Javítás',
	'titre_suivi_petition' => 'Aláírásgyűjtések megfigyelése',
	'trad_article_traduction' => 'A cikk összes változatai :',
	'trad_delier' => 'Visszavenni a cikk csatolását ezekre a fordításokra', # MODIF
	'trad_lier' => 'Ez a cikk egy fordítás erről a cikkről :',
	'trad_new' => 'Írni egy újabb fordítást erről a cikkről', # MODIF

	// U
	'utf8_convert_erreur_orig' => 'Hiba : a @charset@ karaktertábla nincs támogatva.',

	// V
	'version' => 'Verzió :'
);
