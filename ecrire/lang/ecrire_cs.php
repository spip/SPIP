<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de https://trad.spip.net/tradlang_module/ecrire_?lang_cible=cs
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'activer_plugin' => 'Aktivovat plugin',
	'affichage' => 'Zobrazit',
	'aide_non_disponible' => 'Tato část vestavěné nápovědy není ještě v tomto jazykovém znění k dispozici.',
	'auteur' => 'Autor:',
	'avis_acces_interdit' => 'Přístup zakázán.',
	'avis_article_modifie' => 'Varování: Na tomto článku pracoval před @date_diff@ minutou/minutami @nom_auteur_modif@.',
	'avis_aucun_resultat' => 'Nenalezeny žádné výsledky.',
	'avis_chemin_invalide_1' => 'Zvolili jste cestu',
	'avis_chemin_invalide_2' => 'nejspíš neplatnou. Vraťte se na předchozí stránku a zkontrolujte zadané údaje.',
	'avis_connexion_echec_1' => 'Spojení k serveru SQL selhalo.', # MODIF
	'avis_connexion_echec_2' => 'Vraťte se na předchozí stránku a zkontrolujte zadané údaje.',
	'avis_connexion_echec_3' => '<b>Pozn.:</b> V mnoha případech musíte nejprve <b>požádat</b> o aktivaci přístupu k databázi SQL a teprve potém ji můžete používat. Nemůžete-li se připojit, zkontrolujte, zda jste tento požadavek opravdu zadali.', # MODIF
	'avis_connexion_ldap_echec_1' => 'Selhalo připojení k serveru LDAP.',
	'avis_connexion_ldap_echec_2' => 'Vraťte se na předchozí stránku a zkontrolujte zadané údaje.',
	'avis_connexion_ldap_echec_3' => 'Případně při importu uživatelů nepoužívejte podporu LDAP.',
	'avis_deplacement_rubrique' => 'Upozornění! V této sekci je celkem @contient_breves@ vložených novinek: potvrďte jejich přesun zaškrtnutím tohoto políčka.',
	'avis_erreur_connexion_mysql' => 'Chyba připojení SQL', # MODIF
	'avis_espace_interdit' => '<b>Zakázaná oblast</b><p>Systém SPIP je již nainstalován.', # MODIF
	'avis_lecture_noms_bases_1' => 'Instalační program nemůže přečíst názvy nainstalovaných databází.',
	'avis_lecture_noms_bases_2' => 'Buď není dostupná žádná databáze nebo bylo zveřejňování seznamu databází z bepečnostních důvodů
  vypnuto (to je případ mnoha hostitelů).',
	'avis_lecture_noms_bases_3' => 'V takovém případě je možné, že bude možno používat databázi, která má stejný název jako je vaše uživatelské jméno:',
	'avis_non_acces_page' => 'K této stránce nemáte přístup.',
	'avis_operation_echec' => 'Došlo k selhání operace.',
	'avis_suppression_base' => 'VAROVÁNÍ! Odstranění dat je nevratné',

	// B
	'bouton_acces_ldap' => 'Přidat přístup k LDAP >>', # MODIF
	'bouton_ajouter' => 'Přidat',
	'bouton_demande_publication' => 'Požádat o zveřejnění článku',
	'bouton_desactive_tout' => 'Vše vypnout',
	'bouton_effacer_tout' => 'Odstranit vše',
	'bouton_envoyer_message' => 'Konečná zpráva: odeslat',
	'bouton_modifier' => 'Změnit',
	'bouton_radio_afficher' => 'Zobrazit',
	'bouton_radio_apparaitre_liste_redacteurs_connectes' => 'Zobrazit v seznamu připojených redaktorů',
	'bouton_radio_envoi_annonces_adresse' => 'Odesílat oznámení na adresu:',
	'bouton_radio_envoi_liste_nouveautes' => 'Zasílat seznam novinek',
	'bouton_radio_non_apparaitre_liste_redacteurs_connectes' => 'Nezobrazovat v seznamu redaktorů',
	'bouton_radio_non_envoi_annonces_editoriales' => 'Nezasílat redaktorská oznámení',
	'bouton_redirection' => 'PŘESMĚROVÁNÍ',
	'bouton_relancer_installation' => 'Znovu spustit instalaci',
	'bouton_suivant' => 'Další',
	'bouton_tenter_recuperation' => 'Zkusit opravu',
	'bouton_test_proxy' => 'Zkusit proxy',
	'bouton_vider_cache' => 'Vyprázdnit cache',

	// C
	'cache_modifiable_webmestre' => 'Tento parametr může měnit správce webu.',
	'calendrier_synchro' => 'Používáte-li kompatibilní kalendář <b>iCal</b>, můžete jej synchnizovat s údaji na tomto webu.',

	// D
	'date_mot_heures' => 'hodina',

	// E
	'email' => 'e-mail',
	'email_2' => 'email:',
	'entree_adresse_annuaire' => 'Adresa seznamu',
	'entree_adresse_email' => 'Váš email',
	'entree_base_donnee_1' => 'Adresa databáze',
	'entree_base_donnee_2' => '(Tato adresa většinou odpovídá adrese vašeho webu někdy výrazu "localhost", někdy je prázdná.)',
	'entree_biographie' => 'Stručný životopis.',
	'entree_chemin_acces' => '<b>Zadejte</b> cestu:', # MODIF
	'entree_cle_pgp' => 'Váš klíč PGP',
	'entree_contenu_rubrique' => '(Stručný obsah sekce.)',
	'entree_identifiants_connexion' => 'Vaše přihlašovací údaje pro připojení...',
	'entree_informations_connexion_ldap' => 'Zde zadejte údaje pro připojení k adresáří LDAP.
 Tyto údaje by vám měl sdělit správce systému nebo sítě.',
	'entree_infos_perso' => 'Kdo jste?',
	'entree_interieur_rubrique' => 'Uvnitř sekce:',
	'entree_liens_sites' => '<b>Hypertextový odkaz</b> (odkaz, web, který se má navštívit...)', # MODIF
	'entree_login' => 'Vaše přihlašovací údaje',
	'entree_login_connexion_1' => 'Přihlašovací údaje pro připojení',
	'entree_login_connexion_2' => '(Někdy odpovídá vašim přihlašovacím údajům k FTP, někdy zůstávají nevyplněné)',
	'entree_mot_passe' => 'Vaše heslo',
	'entree_mot_passe_1' => 'Heslo pro připojení',
	'entree_mot_passe_2' => '(Někdy odpovídá vašemu heslu k FTP, někdy zůstává prázdné)',
	'entree_nom_fichier' => 'Zadejte název souboru @texte_compresse@:',
	'entree_nom_pseudo' => 'Vaše jeméno nebo přezdívka',
	'entree_nom_pseudo_1' => '(Vaše jméno nebo přezdívka)',
	'entree_nom_site' => 'Název vašeho webu',
	'entree_nouveau_passe' => 'Nové heslo',
	'entree_passe_ldap' => 'Heslo',
	'entree_port_annuaire' => 'Číslo portu adresáře',
	'entree_signature' => 'Podpis',
	'entree_titre_obligatoire' => '<b>Titul</b> [povinný údaj]<br />', # MODIF
	'entree_url' => 'Adresa (URL) vašeho webu',
	'erreur_plugin_fichier_absent' => 'Soubor chybí',
	'erreur_plugin_fichier_def_absent' => 'Chybí definiční soubor',
	'erreur_plugin_nom_fonction_interdit' => 'Zakázané jméno funkce',
	'erreur_plugin_nom_manquant' => 'Chybí jméno pluginu',
	'erreur_plugin_prefix_manquant' => 'Jmenný prostor pluginu není definovaný',
	'erreur_plugin_tag_plugin_absent' => '&lt;plugin&gt; chybí v definičním souboru',
	'erreur_plugin_version_manquant' => 'Chybí informace o verzi pluginu',

	// I
	'ical_info1' => 'Tato stránka umožňuje zůstat v kontaktu s děním na tomto webu několika způsoby.',
	'ical_info2' => 'Bližší informace naleznete v <a href="@spipnet@">dokumentaci k systému SPIP</a>.', # MODIF
	'ical_info_calendrier' => 'Máte k dispozici dva kalendáře. První obsahuje seznam webů s uvedením všech publikovaných článků. Druhý obsahuje redaktorská oznámení a vaše poslední soukromá sdělení: k němu máte přístup pomocí osobního klíče. Tento klíč můžete kdykoli změnit obnovením svého hesla.',
	'ical_methode_http' => 'Odeslání / stažení',
	'ical_methode_webcal' => 'Synchronizace (webcal://)', # MODIF
	'ical_texte_prive' => 'Tento kalendář je určen výhradně k vašemu osobnímu použití a informuje vás o soukromé činnosti redaktorů tohoto webu (osobní úkoly a schůzky, navrhované články a novinky ...).',
	'ical_texte_public' => 'Tento kalendář vám umožní sledovat aktivitu veřejné části webu (publikované články a novinky).',
	'ical_texte_rss' => 'Novinky tohoto webu můžete syndikovat v libovolném programu, který umožňuje číst soubory ve formátu XML/RSS (Rich Site Summary). Tento formát umožňuje systému SPIP číst novinky zveřejněné na jiných webech, které používají kompatibilní formát (syndikovaných webech).',
	'ical_titre_js' => 'Javascript',
	'ical_titre_mailing' => 'Seznam pro rozesílání emailů',
	'ical_titre_rss' => 'Syndikační soubory',
	'icone_activer_cookie' => 'Nastavit cookie',
	'icone_admin_plugin' => 'Spravovat pluginy',
	'icone_afficher_auteurs' => 'Zobrazit autory',
	'icone_afficher_visiteurs' => 'Zobrazit návštěvníky',
	'icone_arret_discussion' => 'Ukončit účast v diskusi',
	'icone_calendrier' => 'Kalendář',
	'icone_creer_auteur' => 'Vytvořit nového autora a spojit ho s tímto článkem',
	'icone_creer_mot_cle' => 'Zadejte nové klíčové slovo a spojte jej s tímto článkem',
	'icone_creer_rubrique_2' => 'Vytvořit novou sekci',
	'icone_modifier_article' => 'Změnit článek',
	'icone_modifier_rubrique' => 'Změnit sekci',
	'icone_retour' => 'Zpět',
	'icone_retour_article' => 'Zpět k článku',
	'icone_supprimer_cookie' => 'Smazat cookie',
	'icone_supprimer_rubrique' => 'Odstranit sekci',
	'icone_supprimer_signature' => 'Odstranit podpis',
	'icone_valider_signature' => 'Potvrdit podpis',
	'image_administrer_rubrique' => 'Máte právo správy této sekce',
	'impossible_modifier_login_auteur' => 'Impossible de modifier le login.', # MODIF
	'impossible_modifier_pass_auteur' => 'Impossible de modifier le mot de passe.', # MODIF
	'info_1_article' => '1 článek',
	'info_activer_cookie' => 'Můžete nastavit <b>administrátorské cookie</b>, které vám dovolí
 snadno přecházet mezi prohlížením webu a jeho editací.',
	'info_administrateur' => 'Správce',
	'info_administrateur_1' => 'Správce',
	'info_administrateur_2' => 'webu (<i>používat opatrně</i>)',
	'info_administrateur_site_01' => 'Jste-li správcem webu, ',
	'info_administrateur_site_02' => 'klepněte na tento odkaz',
	'info_administrateurs' => 'Administrátoři',
	'info_administrer_rubrique' => 'Máte právo správy této sekce',
	'info_adresse' => 'na adrese:',
	'info_adresse_url' => 'Adresa URL veřejného webu',
	'info_aide_en_ligne' => 'Nápověda online systému SPIP',
	'info_ajout_image' => 'Přikládáte-li k článku obrázky jako dokumenty,
  systém SPIP umí automaticky vytvořit jejich náhledy
  (miniatury vložených obrázků). To umožňuje automaticky vytvořit např.
  galerii nebo přehled.',
	'info_ajouter_rubrique' => 'Přidat další sekci do správy:',
	'info_annonce_nouveautes' => 'Oznámení novinek',
	'info_article' => 'článek',
	'info_article_2' => 'články',
	'info_article_a_paraitre' => 'Články čekající na termín publikování',
	'info_articles_02' => 'články',
	'info_articles_2' => 'Články',
	'info_articles_auteur' => 'Články tohoto autora',
	'info_articles_trouves' => 'Nalezené články',
	'info_attente_validation' => 'Vaše články čekající na schválení',
	'info_aujourdhui' => 'dnes:',
	'info_auteurs' => 'Autoři',
	'info_auteurs_par_tri' => 'Autoři@partri@',
	'info_auteurs_trouves' => 'Nalezení autoři',
	'info_authentification_externe' => 'Externí autentifikace',
	'info_avertissement' => 'Upozornění',
	'info_base_installee' => 'Struktura vaší databáze byla nainstalována.',
	'info_chapeau' => 'Stříška',
	'info_chapeau_2' => 'Stříška:',
	'info_chemin_acces_1' => 'Volitelný údaj: <b>Přístup k adresáři</b>', # MODIF
	'info_chemin_acces_2' => 'Musíte nastavit přístup k údajům v adresáři. Tento údaj je nezbytný pro čtení uživatelských profilů, které jsou v něm uloženy.',
	'info_chemin_acces_annuaire' => 'Volitelný údaj: <b>Cesta k adresáři', # MODIF
	'info_choix_base' => 'Třetí krok:',
	'info_classement_1' => 'z @liste@',
	'info_classement_2' => '<sup>e</sup> z @liste@',
	'info_code_acces' => 'Nezapomeňte své přístupové kódy!',
	'info_config_suivi' => 'Jedná-li se o adresu diskusní skupiny, můžete zde uvést adresu, na které se mohou účastníci webu přihlásit. Může se jednat o adresu URL (například webová stránka pro přihlášení do skupiny) nebo o elektronickou adresu s uvedením specifického předmětu (např.: <tt>@adresse_suivi@?subject=subscribe</tt>):',
	'info_config_suivi_explication' => 'Můžete se přihlásit na mailing-list tohoto webu. Potom budete dostávat oznámení o článcích a novinkách připravených k publikování.',
	'info_confirmer_passe' => 'Potvrdit nové heslo:',
	'info_connexion_base' => 'Druhý krok : <b>Pokus o připojení k databázi</b>', # MODIF
	'info_connexion_ldap_ok' => 'Připojení k LDAP bylo úspěšné.</b><p> Můžete přistoupit k dalšímu kroku.</p>', # MODIF
	'info_connexion_mysql' => 'První krok: <b>Připojení k databázi SQL</b>', # MODIF
	'info_connexion_ok' => 'Spojení bylo úspěšné.',
	'info_contact' => 'Kontakt',
	'info_contenu_articles' => 'Obsah článků',
	'info_creation_paragraphe' => '(Odstavce vytvoříte ponecháním volných řádků.)', # MODIF
	'info_creation_rubrique' => 'Články můžete psát teprve,<br /> když vytvoříte alespoň jednu sekci.<br />', # MODIF
	'info_creation_tables' => 'Čtvrtý krok: <b>Vytvoření databázových tabulek</b>', # MODIF
	'info_creer_base' => '<b>Vytvořit</b> novou databázi:', # MODIF
	'info_dans_rubrique' => 'V sekci:',
	'info_date_publication_anterieure' => 'Datum poslední úpravy:',
	'info_date_referencement' => 'Datum vytvoření odkazu na tento web:',
	'info_derniere_etape' => 'Poslední krok: <b>byl dokončen!', # MODIF
	'info_descriptif' => 'Popis:',
	'info_discussion_cours' => 'Probíhající diskuse',
	'info_ecrire_article' => 'Články můžete psát teprve, když vytvoříte alespoň jednu sekci.',
	'info_email_envoi' => 'Email pro zprávy (volitelný údaj)',
	'info_email_envoi_txt' => 'Zde uveďte adresu pro zasílání emailů (standardně se jako tato adresa použije email příjemce):',
	'info_email_webmestre' => 'Email správce webu (volitelný údaj)', # MODIF
	'info_envoi_email_automatique' => 'Automatické zasílání emailů',
	'info_envoyer_maintenant' => 'Odeslat nyní',
	'info_etape_suivante' => 'Přejít k dalšímu kroku',
	'info_etape_suivante_1' => 'Můžete přejít k dalšímu kroku.',
	'info_etape_suivante_2' => 'Můžete přejít k dalšímu kroku.',
	'info_exportation_base' => 'export databáze do @archive@',
	'info_facilite_suivi_activite' => 'Systém SPIP může zasílat elektronické zprávy
  (např. do diskusní skupiny redaktorů) oznamující žádosti o zveřejnění
  a o schválení článků. Tím se ulehčí sledování publikační činnosti
  na webu. ', # MODIF
	'info_fichiers_authent' => 'Autentifikační soubor ".htpasswd"',
	'info_forums_abo_invites' => 'Na vašem webu existují diskusní skupiny vyhrazené přihlášeným účastníkům; návštěvníci se musí tedy zaregistrovat na veřejné části webu.',
	'info_gauche_admin_tech' => '<b>Tato stránka je vyhrazena těm, kdo za web odpovídají.</b><p> Umožňuje přístup k funkcím
pro technickou údržbu. Některé z nich spouští autentifikaci, jež vyžaduje FTP přístup k webu.</p>', # MODIF
	'info_gauche_admin_vider' => '<b>Tato stránka je vyhrazena těm, kdo za web odpovídají.</b><p> Umožňuje přístup k funkcím
pro technickou údržbu. Některé z nich spouští autentifikaci, jež vyžaduje FTP přístup k webu.</p>', # MODIF
	'info_gauche_auteurs' => 'Zde jsou uvedeni všichni autoři webu.
 Jsou rozlišeni barvou ikony (správce = zelená; redaktor = žlutá).',
	'info_gauche_auteurs_exterieurs' => 'Externí autoři bez přístupu k webu jsou označeni modrou ikonou;
  odstranění autoři symbolem odpadkového koše.', # MODIF
	'info_gauche_messagerie' => 'Systém zpráv umožňuje zasílat zprávy mezi redaktory, ukládat interní poznámky a zveřejňovat oznámení na hlavní stánce interní části webu (jste-li správce).',
	'info_gauche_statistiques_referers' => 'Na této stránce je seznam <i>referencí</i>, to je webů, ze kterých vede odkaz na váš vlastní web. Údaje jsou pouze za včerejšek a dnešek. Seznam se každých 24 hodin vynuluje.',
	'info_gauche_visiteurs_enregistres' => 'Zde naleznete návštěvníky zaregistrované
 ve veřejné části webu (diskusní skupiny, do kterých je nutno se přihlásit).',
	'info_generation_miniatures_images' => 'Generování náhledů',
	'info_hebergeur_desactiver_envoi_email' => 'Někteří poskytovatelé prostoru pro web (hostitelé)
  mají vypnuté automatické zasílání emailů ze svých serverů.
  Níže uvedené funkce systému SPIP pak nelze používat.',
	'info_hier' => 'včera:',
	'info_identification_publique' => 'Vaše veřejná identita...',
	'info_image_process' => 'Nejlepší metodu tvorby náhledů zvolíte klepnutím na příslušný obrázek.',
	'info_images_auto' => 'Automaticky vypočtěné obrázky',
	'info_informations_personnelles' => 'Pátý krok: <b>Osobní údaje</b>', # MODIF
	'info_inscription_automatique' => 'Automatický zápis nových redaktorů',
	'info_jeu_caractere' => 'Znaková sada webu',
	'info_jours' => 'dny',
	'info_laisser_champs_vides' => 'tato pole ponechte prázdná)',
	'info_langues' => 'Jazyk webu',
	'info_ldap_ok' => 'Byla nainstalována autentifikace LDAP.',
	'info_lien_hypertexte' => 'Hypertextový odkaz:',
	'info_liste_redacteurs_connectes' => 'Seznam připojených redaktorů',
	'info_login_existant' => 'Toto uživatelské jméno již existuje.',
	'info_login_trop_court' => 'Uživatelské jeméno je příliš krátké.',
	'info_maximum' => 'maximum:',
	'info_meme_rubrique' => 'Ve stejné sekci',
	'info_message_en_redaction' => 'Baše rozpracované zprávy',
	'info_message_technique' => 'Technická zpráva:',
	'info_messagerie_interne' => 'Interní systém zpráv',
	'info_mise_a_niveau_base' => 'auktualizace databáze SQL', # MODIF
	'info_mise_a_niveau_base_2' => '{{Pozor!}} Nainstalovali jste (starší) soubor 
  systému  SPIP než ten, který byl na tomto webu předtím.
  vystavujete se riziku ztráty databáze a nefunkčnosti vašeho webu. 
             <br />{{Nainstalujte znovu
  soubor systému SPIP.}}', # MODIF
	'info_modifier_rubrique' => 'Změnit sekci:',
	'info_modifier_titre' => 'Změnit: @titre@',
	'info_mon_site_spip' => 'Můj web SPIP',
	'info_moyenne' => 'střed / průměr:',
	'info_multi_cet_article' => 'Jazyk článku:',
	'info_multi_langues_choisies' => 'Dále zvolte jazyk, který bude k dispozici redaktorům tohoto webu.
  Jazyky, které se již v rámci webu používají (zobrazené na počátku), nelze vypnout.',
	'info_multi_secteurs' => '... pouze u sekcí v kořenovém adresáři?',
	'info_nom' => 'Jméno',
	'info_nom_destinataire' => 'Jméno příjemce',
	'info_nom_site' => 'Název webu',
	'info_nombre_articles' => '@nb_articles@ články,',
	'info_nombre_rubriques' => '@nb_rubriques@ sekce,',
	'info_nombre_sites' => '@nb_sites@ weby,',
	'info_non_deplacer' => 'Nepřesouvat...',
	'info_non_envoi_annonce_dernieres_nouveautes' => 'SPIP může pravidelně rozesílat oznámení o novinkách na webu (nedávno publikované články a novinky).',
	'info_non_envoi_liste_nouveautes' => 'Nezasílat seznam novinek',
	'info_non_modifiable' => 'nelze změnit',
	'info_non_suppression_mot_cle' => 'nechci odstanit toto klíčové slovo.',
	'info_notes' => 'Poznámky',
	'info_nouvel_article' => 'Nový článek',
	'info_nouvelle_traduction' => 'Nový překlad:',
	'info_numero_article' => 'ČLÁNEK Č.:',
	'info_obligatoire_02' => '[povinný údaj]', # MODIF
	'info_option_accepter_visiteurs' => 'Schválit přihlášení návštěvníků veřejného webu',
	'info_option_ne_pas_accepter_visiteurs' => 'Odmítnout přihlášky návštěvníků',
	'info_options_avancees' => 'ROZŠÍŘENÉ MOŽNOSTI',
	'info_ou' => 'nebo...',
	'info_page_interdite' => 'Zakázaná stránka',
	'info_par_nom' => 'podle jména',
	'info_par_nombre_article' => 'podle počtu článků',
	'info_par_statut' => 'podle stavu',
	'info_par_tri' => '’(podle @tri@)’',
	'info_passe_trop_court' => 'Heslo je příliš krátké.',
	'info_passes_identiques' => 'Zadaná hesla nesouhlasí.',
	'info_plus_cinq_car' => 'vice než 5 znaků',
	'info_plus_cinq_car_2' => '(Více než 5 znaků)',
	'info_plus_trois_car' => '(více než 3 znaky)',
	'info_popularite' => 'oblíbenost: @popularite@; návštěvy: @visites@',
	'info_post_scriptum' => 'P.S.',
	'info_post_scriptum_2' => 'P.S.:',
	'info_pour' => 'pro',
	'info_preview_texte' => 'Náhled umožňuje zobrazit web tak, jakoby všechny články a novinky (minimálně ve stavu "předloženo") byly zveřejněny. Chcete náhled povolit jen správcům, všem redaktorům nebo jej nechcete povolit nikomu?', # MODIF
	'info_procedez_par_etape' => 'postupujte krok za krokem',
	'info_procedure_maj_version' => 'pro úpravu nové verze systému SPIP je třeba spustit aktualizaci
 databáze.',
	'info_ps' => 'P.S.',
	'info_publier' => 'zveřejnit',
	'info_publies' => 'Vaše články publikované online',
	'info_question_accepter_visiteurs' => 'Pokud šablona vašeho webu vyžaduje přihlášení návštěvníků bez přístupu do privátní části, aktivujte tuto možnost zde:',
	'info_question_inscription_nouveaux_redacteurs' => 'Mohou se noví redaktoři přihlašovat z veřejné části webu?
  Pokud ano, návštěvníci se mohou přihlašovat pomocí automatického formuláře
  a získají přístup do privátní části. Budou tak moci navrhovat své vlastní
  články. <blockquote><i>Při přihlášení
  obdrží uživatelé automatický email
  s uvedením přístupových údajů k privátní části.
  Někteří poskytovatelé prostoru pro web odesílání emailů ze svých serverů
  vypínají. Automatické přihlášení je v takovém případě
  nemožné.', # MODIF
	'info_qui_edite' => '@nom_auteur_modif@ a travaillé sur ce contenu il y a @date_diff@ minutes', # MODIF
	'info_racine_site' => 'Kořenový adresář webu',
	'info_recharger_page' => 'Za okamžik zkuste stránku znovu nahrát.',
	'info_recherche_auteur_zero' => 'Pro "@cherche_auteur@" nebyly nalezeny žádné výsledky.',
	'info_recommencer' => 'Začněte znovu.',
	'info_redacteur_1' => 'Redaktor',
	'info_redacteur_2' => 's přístupem do privátní části(<i>doporučeno</i>)',
	'info_redacteurs' => 'Redaktoři',
	'info_redaction_en_cours' => 'ROZPRACOVÁNO',
	'info_redirection' => 'Přesměrování',
	'info_refuses' => 'Vaše odmítnuté články',
	'info_reglage_ldap' => 'Možnost: <b>Nastavení importu LDAP</b>', # MODIF
	'info_renvoi_article' => '<b>Přesměrování.</b> Tento článek odkazuje na stránku:', # MODIF
	'info_reserve_admin' => 'Pouze administrátoři smějí modifikovat tuto adresu.',
	'info_restreindre_rubrique' => 'Omezit správu sekce :',
	'info_resultat_recherche' => 'Výsledky vyhledávání:',
	'info_rubriques' => 'Sekce',
	'info_rubriques_02' => 'sekce',
	'info_rubriques_trouvees' => 'Nalezené sekce',
	'info_sans_titre' => 'Bez názvu',
	'info_selection_chemin_acces' => '<b>Zadejte</b> cestu k adresáři:',
	'info_signatures' => 'podpisy',
	'info_site' => 'Web',
	'info_site_2' => 'web:',
	'info_site_min' => 'web',
	'info_site_reference_2' => 'Web, na nějž vede odkaz',
	'info_site_web' => 'WEB:', # MODIF
	'info_sites' => 'weby',
	'info_sites_lies_mot' => 'Weby, na něž vedou odkazy, a které jsou spojeny s tímto klíčovým slovem',
	'info_sites_proxy' => 'Používat proxy',
	'info_sites_trouves' => 'Nalezené weby',
	'info_sous_titre' => 'Dílčí nadpis:',
	'info_statut_administrateur' => 'Správce',
	'info_statut_auteur' => 'Statut autora:', # MODIF
	'info_statut_auteur_a_confirmer' => 'Registrace k potvrzení',
	'info_statut_auteur_autre' => 'Další status:',
	'info_statut_redacteur' => 'Redaktor',
	'info_statut_utilisateurs_1' => 'Standardní statut importovaných uživatelů',
	'info_statut_utilisateurs_2' => 'Zadejte statut, který mají osoby uvedené v adresáři LDAP, když se poprvé připojí. Tuto hodnotu můžete u každého jednotlivého autora následně změnit.',
	'info_suivi_activite' => 'Sledování redaktorské činnosti',
	'info_surtitre' => 'Nadřízený nadpis:',
	'info_syndication_integrale_1' => 'Váš web nabízí soubory pro syndikaci (viz <a href="@url@">@titre@</a>”).',
	'info_syndication_integrale_2' => 'Chcete poslat celé články, nebo jen prvních pár set znaků jako shrnutí?',
	'info_taille_maximale_vignette' => 'Maximální velikost náhledů generovaných systémem:',
	'info_terminer_installation' => 'Nyní můžete ukončit standardní instalaci.',
	'info_texte' => 'Text',
	'info_texte_explicatif' => 'Vysvětlivka',
	'info_texte_long' => '(text je příliš dlouhý, proto se zobrazuje v několika částech. Po schválení budou spojeny.)',
	'info_texte_message' => 'Text zprávy:', # MODIF
	'info_texte_message_02' => 'Text zprávy',
	'info_titre' => 'Nadpis:',
	'info_total' => 'celkem:',
	'info_tous_articles_en_redaction' => 'Všechny rozpracované články',
	'info_tous_articles_presents' => 'Všechny články publikované v této rubrice.',
	'info_tous_les' => 'všechny:',
	'info_tout_site' => 'Celý web',
	'info_tout_site2' => 'Do tohoto jazyka nebyl článek přeložen.',
	'info_tout_site3' => 'Článek byl do tohoto jazyka přeložen, ale  referenční článek byl následně změněn. Překlad je proto třeba aktualizovat.',
	'info_tout_site4' => 'Do tohoto jazyka byl článek přeložen a překlad je aktuální.',
	'info_tout_site5' => 'Původní článek.',
	'info_tout_site6' => '<b>Pozor:</b> zobrazeny jsou pouze původní články.
Překlady jsou spojeny s originálem a barva
ukazuje jejich stav:',
	'info_travail_colaboratif' => 'Spolupráce na článku',
	'info_un_article' => 'článek, ',
	'info_un_site' => 'jeden web, ',
	'info_une_rubrique' => 'jedna sekce, ',
	'info_une_rubrique_02' => '1 sekce',
	'info_url' => 'URL:',
	'info_urlref' => 'Hypertextový odkaz:',
	'info_utilisation_spip' => 'Nyní můžete začít používat redakční systém...',
	'info_visites_par_mois' => 'Zobrazení po měsících:',
	'info_visiteur_1' => 'Návštěvník',
	'info_visiteur_2' => 'z veřejného webu',
	'info_visiteurs' => 'Návštěvníci',
	'info_visiteurs_02' => 'Návštěvníci z veřejného webu',
	'install_echec_annonce' => 'Instalace se nejspíš nezdaří, resp. jejím výsledkem bude nefunkční web...',
	'install_extension_mbstring' => 'Systém SPIP nefunguje s:',
	'install_extension_php_obligatoire' => 'Systém SPIP vyžaduje rozšíření php:',
	'install_select_langue' => 'Zvolte jazyk a spusťte instalaci klepnutím na tlačítko "Další".',
	'intem_redacteur' => 'redaktor',
	'item_accepter_inscriptions' => 'Schválit přihlášky',
	'item_activer_messages_avertissement' => 'Zapnout upozornění',
	'item_administrateur_2' => 'správce',
	'item_afficher_calendrier' => 'Zobrazit v kalendáři',
	'item_autoriser_syndication_integrale' => 'Vložit kompletní články do syndikačních souborů',
	'item_choix_administrateurs' => 'administrátoři',
	'item_choix_generation_miniature' => 'Automaticky generovat náhledy obrázků.',
	'item_choix_non_generation_miniature' => 'Negenerovat náhledy obrázků.',
	'item_choix_redacteurs' => 'redaktoři',
	'item_choix_visiteurs' => 'návštěvníci veřejného webu',
	'item_creer_fichiers_authent' => 'Vytvořit soubory .htpasswd',
	'item_login' => 'Uživatelské jméno',
	'item_mots_cles_association_articles' => 'k článkům,',
	'item_mots_cles_association_rubriques' => 'k sekcím',
	'item_mots_cles_association_sites' => 'k webům, na něž existují odkazy nabo k syndikovaným webům.',
	'item_non' => 'Ne',
	'item_non_accepter_inscriptions' => 'Odmítnout přihlášky',
	'item_non_activer_messages_avertissement' => 'Bez upozornění',
	'item_non_afficher_calendrier' => 'Nezobrazovat v kalendáři',
	'item_non_autoriser_syndication_integrale' => 'Poslat pouze shrnutí',
	'item_non_creer_fichiers_authent' => 'Nevytvářet tyto soubory',
	'item_non_publier_articles' => 'Nezveřejňovat články před stanoveným datem.',
	'item_nouvel_auteur' => 'Nový autor',
	'item_nouvelle_rubrique' => 'Nová sekce',
	'item_oui' => 'Ano',
	'item_publier_articles' => 'Zveřejnit články bez ohledu na datum.',
	'item_reponse_article' => 'Odpověď na článek',
	'item_visiteur' => 'návštěvník',

	// J
	'jour_non_connu_nc' => 'neuvedeno',

	// L
	'lien_ajouter_auteur' => 'Přidat autora',
	'lien_email' => 'email',
	'lien_nom_site' => 'NÁZEV WEBU:',
	'lien_retirer_auteur' => 'Odstanit autora',
	'lien_site' => 'web',
	'lien_tout_deplier' => 'Rozbalit vše',
	'lien_tout_replier' => 'Sbalit vše',
	'lien_trier_nom' => 'Třídit podle jména',
	'lien_trier_nombre_articles' => 'Třídit podle čísel článků',
	'lien_trier_statut' => 'Třídit podle stavu',
	'lien_voir_en_ligne' => 'ZOBRAZIT ONLINE:',
	'logo_article' => 'LOGO ČLÁNKU', # MODIF
	'logo_auteur' => 'LOGO AUTORA', # MODIF
	'logo_rubrique' => 'LOGO SEKCE', # MODIF
	'logo_site' => 'LOGO WEBU', # MODIF
	'logo_standard_rubrique' => 'STANDARDNÍ LOGO SEKCE', # MODIF
	'logo_survol' => 'LOGO PŘI PŘECHODU', # MODIF

	// M
	'menu_aide_installation_choix_base' => 'Volba databáze',
	'module_fichier_langue' => 'Jazykový soubor',
	'module_raccourci' => 'Zkratka',
	'module_texte_affiche' => 'Zobrazený text',
	'module_texte_explicatif' => 'Do šablony webu můžete vložit následující zkratky. Budou automaticky přeloženy do jazyků, pro něž existují jazykové soubory.',
	'module_texte_traduction' => 'Jazykový soubor " @module@ " existuje v těchto verzích:',
	'mois_non_connu' => 'není známo',

	// O
	'onglet_repartition_actuelle' => 'nyní',

	// P
	'plugin_etat_developpement' => 'upravuje se',
	'plugin_etat_experimental' => 'experimentalní',
	'plugin_etat_stable' => 'stabilní',
	'plugin_etat_test' => 'testovací verze',
	'plugins_liste' => 'Seznam pluginů',

	// R
	'repertoire_plugins' => 'Adresář:',
	'required' => '[povinný údaj]', # MODIF

	// S
	'statut_admin_restreint' => '(admin - vyhrazeno)', # MODIF

	// T
	'taille_cache_image' => 'Obrázky, automaticky vypočtené systémem SPIP (náhledy dokumentů, názvy zobrazené graficky, matematické funkce ve formátu TeX...) zabírají v adresáři @dir@ celkem @taille@.',
	'taille_cache_octets' => 'Velikost cache je v současnosti @octets@.', # MODIF
	'taille_cache_vide' => 'Cache je prázdná.',
	'taille_repertoire_cache' => 'Velikost adresáře cache',
	'text_article_propose_publication' => 'Články připravené k publikování. Neváhejte přidat svůj názor prostřednictvím diskusního fóra, jež je připojeno ke každému článku (na spodním okraji stránky).', # MODIF
	'texte_acces_ldap_anonyme_1' => 'Některé servery LDAP odmítají anonymní přístup. V takovém případě je třeba definovat identifikační údaje pro přístup, aby bylo možno hledat v adresáři. Přesto je však většinou možné nechat následující pole nevyplněná.',
	'texte_admin_effacer_01' => 'Tímto příkazem odstraníte <i>veškerý</i> obsah databáze,
včetně <i>veškerých</i> přístupů redaktorů a správců. Pokud jej spustíte, musíte následně znovu nainstalovat
systém SPIP, abyste vytvořili novou databázi a první přístup pro správce.',
	'texte_adresse_annuaire_1' => '(Máte-li adresář uložen na stejném počítači jako tento web, jedná se nejspíše o "localhost".)',
	'texte_ajout_auteur' => 'K článku byl doplněn tento autor:',
	'texte_annuaire_ldap_1' => 'Máte-li přístup k adresáři (LDAP), můžete jej použít k automatickému importu uživatelů do systému SPIP.',
	'texte_article_statut' => 'Tento článek je:',
	'texte_article_virtuel' => 'Virtuální článek',
	'texte_article_virtuel_reference' => '<b>Virtuální článek:</b> znamená článek, na nějž je na vašem webu SPIP odkaz. Ten je však přesměrován na jinou adresu URL. Chcete-li přesměrování zrušit, odstraňte níže uvedenou adresu URL.',
	'texte_aucun_resultat_auteur' => 'Vyhledávání"@cherche_auteur@" nepřineslo žádné výsledky',
	'texte_auteur_messagerie' => 'Na tomto webu lze mít neustále zobrazený seznam připojených redaktorů. To umožňuje přímou výměnu zpráv. Na uvedeném seznamu nemusíte figurovat (pro ostatní uživatele jste "neviditelní").',
	'texte_auteurs' => 'AUTOŘI',
	'texte_choix_base_1' => 'Zvolte databázi:',
	'texte_choix_base_2' => 'Na serveru SQL je několik databází.', # MODIF
	'texte_choix_base_3' => '<b>Zvolte</b> databázi, která vám byla přidělena poskytovatelem webového prostoru:', # MODIF
	'texte_compte_element' => '@count@ prvek',
	'texte_compte_elements' => '@count@ prvky',
	'texte_connexion_mysql' => 'Zkontrolujte informace od vašeho poskytovatele webového prostoru: mělo by tam být uvedeno, zda podporuje databázi SQL a přístupové kódy pro připojení k serveru SQL.', # MODIF
	'texte_contenu_article' => '(Obsah článku v několika slovech.)',
	'texte_contenu_articles' => 'Na základě šablony svého webu se můžete rozhodnout, že některé prvky
  článků nebudete používat.
  Pro označení funkcí, které jsou k dispozici, použijte níže uvedený seznam.',
	'texte_crash_base' => 'Došlo-li ke zhroucení databáze,
   můžete zkusit její automatickou obnovu.',
	'texte_creer_rubrique' => 'Než můžete začít psát články,<br /> musíte vytvořit sekci.', # MODIF
	'texte_date_creation_article' => 'DATUM NAPSÁNÍ ČLÁNKU:',
	'texte_date_publication_anterieure' => 'Datum poslední úpravy:',
	'texte_date_publication_anterieure_nonaffichee' => 'Nezobrazovat datum poslední úpravy.',
	'texte_date_publication_article' => 'DATUM ZVEŘEJNĚNÍ ONLINE:',
	'texte_descriptif_rapide' => 'Stručný popis',
	'texte_effacer_base' => 'Odstranit databázi SPIP',
	'texte_en_cours_validation' => 'Následující články a novinky jsou připraveny k publikaci. Neváhejte připojit svůj názor prostřednictvím diskusního fóra, jež je k nim připojeno.', # MODIF
	'texte_enrichir_mise_a_jour' => 'Zalamování textu můžete rozšířit pomocí "typografických zkratek".',
	'texte_fichier_authent' => '<b>Má systém SPIP vytvořit speciální soubory <tt>.htpasswd</tt>
  a <tt>.htpasswd-admin</tt> v adresáři  @dossier@?</b><p>
  Tyto soubory umožňují omezit přístup autorů a správců k dalším částem
  vašeho webu
  (např. k externímu programu statistik).</p><p>
  Nechcete-li tyto soubory používat, můžete ponechat výchozí hodnotu
  (nevytvářet soubory).</p>', # MODIF
	'texte_informations_personnelles_1' => 'Systém pro vás nyní vytvoří vlastní přístup k webu.',
	'texte_informations_personnelles_2' => '(Poznámka: pokud se jedná o opakovanou instalaci a váš přístup je stále funkční, můžete', # MODIF
	'texte_introductif_article' => '(Úvodní text článku.)',
	'texte_jeu_caractere' => 'Doporučujeme použít univerzální abecedu Unicode (<tt>utf-8</tt>) pro váš web, která umožňuje zobrazit texty v jakémkoli jazyce. Žádný moderní prohlížeč s Unicode nemá potíže.',
	'texte_jeu_caractere_3' => 'Váš web nyní používá tuto znakovou sadu:',
	'texte_jeu_caractere_4' => 'Pokud toto neodpovídá vaší situaci (například po obnovení dat ze zálohy), nebo <em>pokud konfigurujete tento web</em> a chcete použít jinou znakovou sadu, prosím označte znakovou sadu:',
	'texte_login_ldap_1' => '(V případě anonymního přístupu ponechte prázdné, případně zadejte kompletní cestu "<tt>uid=dupont, ou=users, dc=mon-domaine, dc=com</tt>".)',
	'texte_login_precaution' => 'Pozor! Pod tímto uživatelským jménem jste právě přihlášeni!
 Tento formulář používejte opatrně...',
	'texte_mise_a_niveau_base_1' => 'Aktualizovali jste soubory systému SPIP.
 Nyní musíte aktualizovat databázi webu.',
	'texte_modifier_article' => 'Změnit článek:',
	'texte_multilinguisme' => 'Chcete-li správu článků v několika jazycích se složitou navigací, můžete k článkům, resp. sekcím (záleží na organizační struktuře vašeho webu) přidat nabídku pro výběr jazyka.', # MODIF
	'texte_multilinguisme_trad' => 'Rovněž můžete zapnout systém správy odkazů mezi překlady jednotlivých článků.', # MODIF
	'texte_non_compresse' => '<i>nekomprimováno</i> (váš server tuto funkci nepodporuje)',
	'texte_nouvelle_version_spip_1' => 'Nainstalovali jste novou verzi systému SPIP.',
	'texte_nouvelle_version_spip_2' => 'Tato verze vyžaduje rozsáhlejší aktualizaci než je obvyklé. Jste-li správcem tohoto webu, odstraňte soubor @connect@ a spusťte instalaci. Tím dojde k aktualizaci parametrů pro připojení k databázi.<p> (Pozn.: Pokud jste parametry pro připojení zapomněli, podívejte se nejprve do souboru @connect@, a teprve poté jej odstraňte...)</p>', # MODIF
	'texte_operation_echec' => 'Vraťte se na předchozí stránku a zvolte jinou databázi nebo vytvořte novou. Zkontrolujte informace od svého poskytovatele prostoru pro web.',
	'texte_plus_trois_car' => 'více než 3 znaky',
	'texte_plusieurs_articles' => 'Pro "@cherche_auteur@" bylo nalezeno několik autorů:',
	'texte_port_annuaire' => '(Většinou vyhovuje přednastavená hodnota.)',
	'texte_presente_plugin' => 'Tato stránka zobrazuje všechny dostupné pluginy. Aktivujte potřebné pluginy zaškrtnutím příslušného políčka.',
	'texte_proposer_publication' => 'Po dopsání článku,<br /> můžete navrhnout jeho zveřejnění.', # MODIF
	'texte_proxy' => 'V některých případech (intranet, chráněné sítě...),
  je třeba používat <i>HTTP proxy</i>.  Jinak se k syndikovaným webům nedostanete.
  V takovém případě uveďte adresu proxy níže ve formátu
  <tt><html>http://proxy:8080</html></tt>. Obvykle
  je toto pole prázdné.', # MODIF
	'texte_publication_articles_post_dates' => 'Jak má systém SPIP zacházet s články, u nichž bylo datum zveřejnění
  stanoveno do budoucnosti?',
	'texte_rappel_selection_champs' => '[Nezapomeňte správně zvolit hodnotu v tomto poli.]',
	'texte_recalcul_page' => 'Chcete-li tuto stránku znovu vygenerovat,
přejděte raději do veřejné části a použijte tlačítko "znovu vypočítat".',
	'texte_recuperer_base' => 'Opravit databázi',
	'texte_reference_mais_redirige' => 'Na článek je na vašem webu SPIP odkaz, ale je přesměrován na jinou adresu URL.',
	'texte_requetes_echouent' => '<b>Pokud některé dotazy SQL neustále
  selhávají bez zjevné příčiny, může to být kvůli samotné databázi.</b><p>
  Databáze SQL umožňuje opravu náhodně poškozených databázových tabulek.
  O opravu se můžete pokusit.
  Pokud se obnova nezdaří, uložte si zobrazené hlášení.
  Může v něm být uvedena příčina selhání...</p><p>
  Jestliže problém nejde vyřešit, kontaktujte
  svého poskytovatele webového prostoru.</p>', # MODIF
	'texte_selection_langue_principale' => 'Níže můžete vybrat "hlavní jazyk" webu. Tato volba vás neomezuje na psaní článků pouze v tomto jazyce, ale umožňuje určit:
 <ul><li> standardní formát dat veřejného webu;</li>
 <li> podstatu textového procesoru, který systém SPIP použije při zadávání textů;</li>
 <li> jazyk formulářů veřejného webu;</li>
 <li> výchozí jazyk privátní části.</li></ul>',
	'texte_sous_titre' => 'Dílčí název',
	'texte_statistiques_visites' => '(tmavě:  neděle / tmavá křivka: průměr)',
	'texte_statut_attente_validation' => 'čekající na schválení',
	'texte_statut_publies' => 'publikováno online',
	'texte_statut_refuses' => 'odmítnuto',
	'texte_suppression_fichiers' => 'Tento příkaz služí k odstranění všech souborů v paměti cache systému SPIP.
Tím například vynutíte obnovu všech stránek poté, co provedete významné
změny grafického uspořádání nebo struktury vašeho webu.',
	'texte_sur_titre' => 'Podtitul',
	'texte_table_ok' => ': tato tabulka je v pořádku.',
	'texte_tentative_recuperation' => 'Pokus o opravu',
	'texte_tenter_reparation' => 'Zkusit opravu databáze',
	'texte_test_proxy' => 'Proxy otestujete zadáním adresy webu,
    který chcete odzkoušet.',
	'texte_titre_02' => 'Název:',
	'texte_titre_obligatoire' => '<b>Název</b> [povinný údaj]', # MODIF
	'texte_travail_article' => '@nom_auteur_modif@ upravoval tento článek před @date_diff@ minutou/minutami',
	'texte_travail_collaboratif' => 'Pokud na jednom článku často pracuje několik
  redaktorů, může systém zobrazit články "otevřené"
  v poslední době. Tím se předejde souběžným úpravám.
  Tato možnost je standardně vypnuta, aby se předešlo
  zobrazování zbytečných varovných hlášení.',
	'texte_vide' => 'vyprázdnit',
	'texte_vider_cache' => 'Vyprázdnit cache',
	'titre_admin_tech' => 'Technická údržba',
	'titre_admin_vider' => 'Technická údržba',
	'titre_cadre_afficher_article' => 'Zobrazit články',
	'titre_cadre_afficher_traductions' => 'Zobrazit stav překladu v následujících jazycích:',
	'titre_cadre_ajouter_auteur' => 'PŘIDAT AUTORA:',
	'titre_cadre_interieur_rubrique' => 'V rámci sekce',
	'titre_cadre_numero_auteur' => 'ČÍSLO AUTORA',
	'titre_cadre_signature_obligatoire' => '<b>Podpis</b> [povinný údaj]<br />', # MODIF
	'titre_config_fonctions' => 'Nastavení webu',
	'titre_configuration' => 'Nastavení webu',
	'titre_connexion_ldap' => 'Možnost: <b>Vaše připojení LDAP</b>',
	'titre_groupe_mots' => 'SKUPINA SLOV:',
	'titre_langue_article' => 'JAZYK ČLÁNKU', # MODIF
	'titre_langue_rubrique' => 'JAZYK SEKCE', # MODIF
	'titre_langue_trad_article' => 'JAZYK ČLÁNKU A JEHO PŘEKLADŮ',
	'titre_les_articles' => 'ČLÁNKY',
	'titre_naviguer_dans_le_site' => 'Procházet webem...',
	'titre_nouvelle_rubrique' => 'Nová sekce',
	'titre_numero_rubrique' => 'SEKCE ČÍSLO:',
	'titre_page_articles_edit' => 'Změnit: @titre@',
	'titre_page_articles_page' => 'Články',
	'titre_page_articles_tous' => 'Celý web',
	'titre_page_calendrier' => 'Kalendář @nom_mois@ @annee@',
	'titre_page_config_contenu' => 'Nastavení webu',
	'titre_page_delete_all' => 'kompletní a nevratné odstranění',
	'titre_page_recherche' => 'Výsledky vyhledávání @recherche@',
	'titre_page_statistiques_referers' => 'Statistiky (příchozí odkazy)',
	'titre_page_upgrade' => 'Aktualizace systému SPIP',
	'titre_publication_articles_post_dates' => 'Zveřejnění post-datovaných článků',
	'titre_reparation' => 'Oprava',
	'titre_suivi_petition' => 'Sledování peticí',
	'tls_ldap' => 'Transport Layer Security :',
	'trad_article_traduction' => 'Dostupné překlady tohoto článku:',
	'trad_delier' => 'Tento článek nadále s překlady nespojovat', # MODIF
	'trad_lier' => 'Toto je překlad článku číslo:',
	'trad_new' => 'Nově přeložit článek', # MODIF

	// U
	'utf8_convert_erreur_orig' => 'Chyba: jazyková sada @charset@ není podporována.',

	// V
	'version' => 'Verze:'
);
