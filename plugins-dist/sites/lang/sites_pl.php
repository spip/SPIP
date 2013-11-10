<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/sites?lang_cible=pl
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'articles_dispo' => 'En attente', # NEW
	'articles_meme_auteur' => 'Tous les articles de cet auteur', # NEW
	'articles_off' => 'Bloqués', # NEW
	'articles_publie' => 'Publiés', # NEW
	'articles_refuse' => 'Supprimés', # NEW
	'articles_tous' => 'Tous', # NEW
	'aucun_article_syndic' => 'Aucun article syndiqué', # NEW
	'avis_echec_syndication_01' => 'Syndykacja nie powiodła się : plik backend jest nieodczytywalny lub nie ma w nim żadnego artykułu.',
	'avis_echec_syndication_02' => 'Syndykacja nie powiodła się : nie ma dostępu do pliku backend tego serwisu.',
	'avis_site_introuvable' => 'Strony nie znaleziono',
	'avis_site_syndique_probleme' => 'Uwaga : syndykacja strony napotkała na problem ; system został na chwilę wstrzymany. Sprawdź URL syndykowanej strony (<b>@url_syndic@</b>), i spróbuj powtórnie pozyskać informacje.', # MODIF
	'avis_sites_probleme_syndication' => 'Te strony mają problem z syndykacją',
	'avis_sites_syndiques_probleme' => 'Następujące strony syndykowane sprawiają problem',

	// B
	'bouton_exporter' => 'Exporter', # NEW
	'bouton_importer' => 'Importer', # NEW
	'bouton_radio_modere_posteriori' => 'moderacja a posteriori', # MODIF
	'bouton_radio_modere_priori' => 'moderacja a priori', # MODIF
	'bouton_radio_non_syndication' => 'Bez syndykacji',
	'bouton_radio_syndication' => 'Syndykacja:',

	// C
	'confirmer_purger_syndication' => 'Êtes-vous certain de vouloir supprimer tous les articles syndiqués de ce site ?', # NEW

	// E
	'entree_adresse_fichier_syndication' => 'Adres pliku syndykacji:',
	'entree_adresse_site' => '<b>URL strony</b> [obowiązkowo]',
	'entree_description_site' => 'Opis strony',
	'erreur_fichier_format_inconnu' => 'Le format du fichier @fichier@ n\'est pas pris en charge.', # NEW
	'erreur_fichier_incorrect' => 'Impossible de lire le fichier.', # NEW

	// F
	'form_prop_nom_site' => 'Nazwa stron\\y',

	// I
	'icone_article_syndic' => 'Article syndiqué', # NEW
	'icone_articles_syndic' => 'Articles syndiqués', # NEW
	'icone_controler_syndication' => 'Publication des articles syndiqués', # NEW
	'icone_modifier_site' => 'Zmień tę stronę',
	'icone_referencer_nouveau_site' => 'Nowy link do strony',
	'icone_site_reference' => 'Sites référencés', # NEW
	'icone_supprimer_article' => 'Supprimer cet article', # NEW
	'icone_supprimer_articles' => 'Supprimer ces articles', # NEW
	'icone_valider_article' => 'Valider cet article', # NEW
	'icone_valider_articles' => 'Valider ces articles', # NEW
	'icone_voir_sites_references' => 'Pokaż zlinkowane strony',
	'info_1_site_importe' => '1 site a été importé', # NEW
	'info_a_valider' => '[do zatwierdzenia]',
	'info_aucun_site_importe' => 'Aucun site n\'a pu être importé', # NEW
	'info_bloquer' => 'zablokuj',
	'info_bloquer_lien' => 'zablokuj ten link',
	'info_derniere_syndication' => 'Ostatnia syndykacja tego serwisu została dokonana',
	'info_liens_syndiques_1' => 'linki syndykowane',
	'info_liens_syndiques_2' => 'oczekujące zatwierdzenia.',
	'info_nb_sites_importes' => '@nb@ sites ont été importés', # NEW
	'info_nom_site_2' => '<b>Nazwa strony</b> [obowiązkowo]',
	'info_panne_site_syndique' => 'Strona syndykowana nie działa',
	'info_probleme_grave' => 'błąd',
	'info_question_proposer_site' => 'Kto może proponować zlinkowane strony ?',
	'info_retablir_lien' => 'przywróc link',
	'info_site_attente' => 'Strona internetowa oczekująca na zatwierdzenie',
	'info_site_propose' => 'Strona zaproponowana :',
	'info_site_reference' => 'Strona zlinkowana on-line',
	'info_site_refuse' => 'Strona internetowa odrzucona',
	'info_site_syndique' => 'Ta strona jest syndykowana...', # MODIF
	'info_site_valider' => 'Strony do zatwierdzenia',
	'info_sites_referencer' => 'Dodaj link',
	'info_sites_refuses' => 'Odrzucone strony',
	'info_statut_site_1' => 'Ta strona jest:',
	'info_statut_site_2' => 'Opublikowana',
	'info_statut_site_3' => 'Zatwierdzona',
	'info_statut_site_4' => 'Do kosza', # MODIF
	'info_syndication' => 'syndykacja :',
	'info_syndication_articles' => 'artykuł(y)',
	'item_bloquer_liens_syndiques' => 'Zablokuj akceptację syndykowanych linków',
	'item_gerer_annuaire_site_web' => 'Zarządzaj katalogiem stron www',
	'item_non_bloquer_liens_syndiques' => 'Nie blokuj łączy pochodzących z syndykacji',
	'item_non_gerer_annuaire_site_web' => 'Wyłącz katalog stron www',
	'item_non_utiliser_syndication' => 'Wyłącz automatyczną syndykację',
	'item_utiliser_syndication' => 'Używaj automatycznej syndykacji',

	// L
	'label_exporter_avec_mots_cles_1' => 'Exporter les mots-clés sous forme de tags', # NEW
	'label_exporter_id_parent' => 'Exporter les sites de la rubrique', # NEW
	'label_exporter_publie_seulement_1' => 'Exporter uniquement les sites publiés', # NEW
	'label_fichier_import' => 'Fichier HTML', # NEW
	'label_importer_les_tags_1' => 'Importer les tags sous forme de mot-clé', # NEW
	'label_importer_statut_publie_1' => 'Publier automatiquement les sites', # NEW
	'lien_mise_a_jour_syndication' => 'Uaktualnij teraz',
	'lien_nouvelle_recuperation' => 'Spróbuj ponowić odtwarzanie danych',
	'lien_purger_syndication' => 'Effacer tous les articles syndiqués', # NEW

	// N
	'nombre_articles_syndic' => '@nb@ articles syndiqués', # NEW

	// S
	'statut_off' => 'Supprimé', # NEW
	'statut_prop' => 'En attente', # NEW
	'statut_publie' => 'Publié', # NEW
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
	'texte_expliquer_export_bookmarks' => 'Vous pouvez exporter une liste de sites au format Marque-page HTML,
	pour vous permettre ensuite de l\'importer dans votre navigateur ou dans un service en ligne', # NEW
	'texte_expliquer_import_bookmarks' => 'Vous pouvez importer une liste de sites au format Marque-page HTML,
	en provenance de votre navigateur ou d\'un service en ligne de gestion des Marques-pages.', # NEW
	'texte_liens_sites_syndiques' => 'Łącza pochodzące z syndykacji mogą
   być domyślnie zablokowane ; regulacja tego
   wskazuje regulacje domyślne
   stron syndykowanych po ich stworzeniu. Jest
   możliwe późniejsze odblokowanie, łączy indywidualnie, lub
   wybór, strona po stronie, blokady linków pochodzących z danych stron.', # MODIF
	'texte_messages_publics' => 'Publiczne komentarze do artykułu :',
	'texte_non_fonction_referencement' => 'Być może wolisz nie używać funkcji automatycznej, i samemu zaznaczyć elementy związane z tą stroną...', # MODIF
	'texte_referencement_automatique' => '<b>Zautomatyzowane dodawanie linków</b><br />Możesz szybko dodać link do jakiejś strony internetowej, wpisując poniżej jej adres, oraz adres jej pliku syndykacji. SPIP automatycznie dopisze informacje, dotyczące tej strony (tytuł, opis...).', # MODIF
	'texte_referencement_automatique_verifier' => 'Veuillez vérifier les informations fournies par <tt>@url@</tt> avant d\'enregistrer.', # NEW
	'texte_syndication' => 'Jeśli dany serwis na to pozwala, jest możliwość wyciągnięcia z niego 
  listy newsów. Aby skorzystać z tej funkcji musisz włączyć <i>syndykację ?</i>. 
  <blockquote><i>Niektóre serwery mają taką możliwość wyłączoną ; 
  wówczas nie możesz używać syndykacji przy użyciu swojej strony.</i></blockquote>', # MODIF
	'titre_articles_syndiques' => 'Artykułu syndykowane, wyciągnięte z tej strony',
	'titre_dernier_article_syndique' => 'Ostatnio syndykowane artykuły',
	'titre_exporter_bookmarks' => 'Exporter des Marques-pages', # NEW
	'titre_importer_bookmarks' => 'Importer des Marques-pages', # NEW
	'titre_importer_exporter_bookmarks' => 'Importer et Exporter des Marques-pages', # NEW
	'titre_page_sites_tous' => 'Zlinkowane strony',
	'titre_referencement_sites' => 'Linkowanie i zrzeszanie stron',
	'titre_site_numero' => 'STRONA NUMER :',
	'titre_sites_proposes' => 'Strony zatwierdzone',
	'titre_sites_references_rubrique' => 'Linki do stron z tego działu',
	'titre_sites_syndiques' => 'Syndykowane serwisy',
	'titre_sites_tous' => 'Linki do stron',
	'titre_syndication' => 'Syndykacja stron',
	'tout_voir' => 'Voir tous les articles syndiqués', # NEW

	// U
	'un_article_syndic' => '1 article syndiqué' # NEW
);

?>
