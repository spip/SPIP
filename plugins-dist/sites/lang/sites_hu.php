<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/sites?lang_cible=hu
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
	'avis_echec_syndication_01' => 'A szindikálás sikertelen: a kijelölt backend nem dolgozható fel, vagy egyetlen cikket sem ajánl fel.',
	'avis_echec_syndication_02' => 'A szindikálás sikertelen: nem lehet elérni a honlap backend-jét.',
	'avis_site_introuvable' => 'A honlap nem található',
	'avis_site_syndique_probleme' => 'Vigyázat : a honlap szindikálása egy problémával ütközött ; tehát a rendszer egyelőre ideiglenesen fel van függesztve. Ellenőrizze e honlap szindikációs fájl címét (<b>@url_syndic@</b>), és újból próbálja szerezni az információkat.', # MODIF
	'avis_sites_probleme_syndication' => 'Azok a honlap szindikálási problémával ütköztek',
	'avis_sites_syndiques_probleme' => 'Azok a szindikált honlapok valami problémát okoztak',

	// B
	'bouton_exporter' => 'Exporter', # NEW
	'bouton_importer' => 'Importer', # NEW
	'bouton_radio_modere_posteriori' => 'utólag moderált', # MODIF
	'bouton_radio_modere_priori' => 'elözőleg moderálva', # MODIF
	'bouton_radio_non_syndication' => 'Nincs szindikálás',
	'bouton_radio_syndication' => 'Szindikálás :',

	// C
	'confirmer_purger_syndication' => 'Êtes-vous certain de vouloir supprimer tous les articles syndiqués de ce site ?', # NEW

	// E
	'entree_adresse_fichier_syndication' => 'A szindikálásra használt « backend » fájl címe :',
	'entree_adresse_site' => '<b>Honlap címe</b> [Kötelező]',
	'entree_description_site' => 'A honlap leírása',
	'erreur_fichier_format_inconnu' => 'Le format du fichier @fichier@ n\'est pas pris en charge.', # NEW
	'erreur_fichier_incorrect' => 'Impossible de lire le fichier.', # NEW

	// F
	'form_prop_nom_site' => 'Honlap neve',

	// I
	'icone_article_syndic' => 'Article syndiqué', # NEW
	'icone_articles_syndic' => 'Articles syndiqués', # NEW
	'icone_controler_syndication' => 'Publication des articles syndiqués', # NEW
	'icone_modifier_site' => 'A honlap módosítása',
	'icone_referencer_nouveau_site' => 'Új honlap felvétele',
	'icone_site_reference' => 'Sites référencés', # NEW
	'icone_supprimer_article' => 'Supprimer cet article', # NEW
	'icone_supprimer_articles' => 'Supprimer ces articles', # NEW
	'icone_valider_article' => 'Valider cet article', # NEW
	'icone_valider_articles' => 'Valider ces articles', # NEW
	'icone_voir_sites_references' => 'Felvett honlapok nézete',
	'info_1_site_importe' => '1 site a été importé', # NEW
	'info_a_valider' => '[érvényesítendő]',
	'info_aucun_site_importe' => 'Aucun site n\'a pu être importé', # NEW
	'info_bloquer' => 'blokkol',
	'info_bloquer_lien' => 'blokkolni a linket',
	'info_derniere_syndication' => 'A honlap legutolsó szindikálása került sor',
	'info_liens_syndiques_1' => 'Szindikált linkek',
	'info_liens_syndiques_2' => 'jóváhagyás alatt vannak.',
	'info_nb_sites_importes' => '@nb@ sites ont été importés', # NEW
	'info_nom_site_2' => '<b>Honlap neve</b> [Kötelező]',
	'info_panne_site_syndique' => 'Hibás szindikált honlap',
	'info_probleme_grave' => 'probléma',
	'info_question_proposer_site' => 'Ki ajánlhat fel felvetendő honlapokat ?',
	'info_retablir_lien' => 'visszaállítani ezt a linket',
	'info_site_attente' => 'Jóvahagyás alatti honlap',
	'info_site_propose' => 'Honlap ajánlasának időpontja :',
	'info_site_reference' => 'Felvett honlap',
	'info_site_refuse' => 'Elutasított honlap',
	'info_site_syndique' => 'Ez a honlap szindikálva van...', # MODIF
	'info_site_valider' => 'Jóváhagyandó honlapok',
	'info_sites_referencer' => 'Honlap felvétele',
	'info_sites_refuses' => 'Elutasított honlapok',
	'info_statut_site_1' => 'Ez a honlap :',
	'info_statut_site_2' => 'Publikált',
	'info_statut_site_3' => 'Javasolt',
	'info_statut_site_4' => 'A szemetesben van', # MODIF
	'info_syndication' => 'szindikálás :',
	'info_syndication_articles' => 'cikk(ek)',
	'item_bloquer_liens_syndiques' => 'Szindikált linkek tiltása jóváhagyás érdekében',
	'item_gerer_annuaire_site_web' => 'Egy honlap címtár kezelése',
	'item_non_bloquer_liens_syndiques' => 'Nem blokkolni a szindikálásból eredő linkeket',
	'item_non_gerer_annuaire_site_web' => 'A honlap címtár inaktiválása',
	'item_non_utiliser_syndication' => 'Nem kell használni az automatikus szindikálást',
	'item_utiliser_syndication' => 'Automatikus szindikálás használata',

	// L
	'label_exporter_avec_mots_cles_1' => 'Exporter les mots-clés sous forme de tags', # NEW
	'label_exporter_id_parent' => 'Exporter les sites de la rubrique', # NEW
	'label_exporter_publie_seulement_1' => 'Exporter uniquement les sites publiés', # NEW
	'label_fichier_import' => 'Fichier HTML', # NEW
	'label_importer_les_tags_1' => 'Importer les tags sous forme de mot-clé', # NEW
	'label_importer_statut_publie_1' => 'Publier automatiquement les sites', # NEW
	'lien_mise_a_jour_syndication' => 'Frissítés most',
	'lien_nouvelle_recuperation' => 'Újabb kisérlet az adatok megszerzésére',
	'lien_purger_syndication' => 'Effacer tous les articles syndiqués', # NEW

	// N
	'nombre_articles_syndic' => '@nb@ articles syndiqués', # NEW

	// S
	'statut_off' => 'Supprimé', # NEW
	'statut_prop' => 'En attente', # NEW
	'statut_publie' => 'Publié', # NEW
	'syndic_choix_moderation' => 'Mi legyen azokkal a linkekkel, melyek jönnek erről a honlapról ?',
	'syndic_choix_oublier' => 'Mi legyen azekkel a linkekkel, melyek nem szerepelnek a szindikálási (RSS) fájlban ?',
	'syndic_choix_resume' => 'Bizonyos honlapok a cikkek teljes tartalmát továbbítják. Ha rendelkezésre áll, kivánja-e szindikálni :',
	'syndic_lien_obsolete' => 'Elavult hivatkozás',
	'syndic_option_miroir' => 'automatikusan blokkolni',
	'syndic_option_oubli' => 'törölni (@mois@ hónap után)',
	'syndic_option_resume_non' => 'a cikkek teljes tartalmát (HTML formátumban)',
	'syndic_option_resume_oui' => 'egy egyszerű összefoglalás (szöveges formátumban)',
	'syndic_options' => 'Szindikálási opciók :',

	// T
	'texte_expliquer_export_bookmarks' => 'Vous pouvez exporter une liste de sites au format Marque-page HTML,
	pour vous permettre ensuite de l\'importer dans votre navigateur ou dans un service en ligne', # NEW
	'texte_expliquer_import_bookmarks' => 'Vous pouvez importer une liste de sites au format Marque-page HTML,
	en provenance de votre navigateur ou d\'un service en ligne de gestion des Marques-pages.', # NEW
	'texte_liens_sites_syndiques' => 'A szindikált honlapokról származó linkeket lehetnek eleve tiltva ; a lenti beállítás jelzi a szindikált honlapok alapértelmezett beállítását létrehozásuk után.
Egyébkent minden linket lehet utólag engedélyezni egyenként, vagy honlap szerint tiltani a leendő linkeket.
', # MODIF
	'texte_messages_publics' => 'A cikk nyilvános üzenetei :',
	'texte_non_fonction_referencement' => 'Ezt az automatikus funkciót kihagyhatja, és Önmaga jelezheti a honlapra vonatkozó elemeket...', # MODIF
	'texte_referencement_automatique' => '<b>egy honlap automatikus felvétele</b><br />Gyorsan felvehet egy honlapot, ha lejjebb jelzi a kivánt URL-t, vagy a backend fájl címét. SPIP automatikusan fogja megszerezni az erre vonatkozó információkat (neve, leírása...).', # MODIF
	'texte_referencement_automatique_verifier' => 'Veuillez vérifier les informations fournies par <tt>@url@</tt> avant d\'enregistrer.', # NEW
	'texte_syndication' => 'Ha a honlap megengedi,akkor automatikusan lehet szerezni újdonságait listáját.
  Ezért kell aktiválni a szindikálást.
  <blockquote><i>Egyes szolgáltatók kikapcsolják ezt a lehetőséget ; 
  ilyen esetben, nem használhatja a tartalomszindikálást
  az Ön honlapjáról.</i></blockquote>', # MODIF
	'titre_articles_syndiques' => 'Szindikált cikkek erről a honlapról',
	'titre_dernier_article_syndique' => 'Utolsó szindikált cikkek',
	'titre_exporter_bookmarks' => 'Exporter des Marques-pages', # NEW
	'titre_importer_bookmarks' => 'Importer des Marques-pages', # NEW
	'titre_importer_exporter_bookmarks' => 'Importer et Exporter des Marques-pages', # NEW
	'titre_page_sites_tous' => 'A felvett honlapok',
	'titre_referencement_sites' => 'Honlapok felvéltele és szindikálás',
	'titre_site_numero' => 'HONLAP SZÁMA :',
	'titre_sites_proposes' => 'Javasolt honlapok',
	'titre_sites_references_rubrique' => 'Felvett honlapok ebben a rovatban',
	'titre_sites_syndiques' => 'A szindikált honlapok',
	'titre_sites_tous' => 'A felvett honlapok',
	'titre_syndication' => 'Honlapok szindikálása',
	'tout_voir' => 'Voir tous les articles syndiqués', # NEW

	// U
	'un_article_syndic' => '1 article syndiqué' # NEW
);

?>
