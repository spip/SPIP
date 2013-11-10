<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/sites?lang_cible=bs
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
	'avis_echec_syndication_01' => 'Sindikacija nije uspjela: odabrani backend nije citljiv ili ne nudi nijedan clanak.',
	'avis_echec_syndication_02' => 'Sindikacija nije uspjela: nije moguce dostici backend ove stranice',
	'avis_site_introuvable' => 'Stranica nije pronadjena',
	'avis_site_syndique_probleme' => 'Paznja : Problem prilikom sindikacije ove stranice; Doslo je do privremenog prekida sistema. Provjerite adresu dokumenta sindikacije ove stranice \\f1 (<b>@url_syndic@</b>)\\f0  i pokusajte povratiti informacije.', # MODIF
	'avis_sites_probleme_syndication' => 'Doslo je do problema  prilikom sindikacije ovih stranica',
	'avis_sites_syndiques_probleme' => 'Sindikovane stranice su postavljale problem',

	// B
	'bouton_exporter' => 'Exporter', # NEW
	'bouton_importer' => 'Importer', # NEW
	'bouton_radio_modere_posteriori' => '\\f1 post-moderation\\f0 ', # MODIF
	'bouton_radio_modere_priori' => '\\f1 pre-moderation\\f0 ', # MODIF
	'bouton_radio_non_syndication' => 'Bez sindikacije',
	'bouton_radio_syndication' => 'Sindikacija:',

	// C
	'confirmer_purger_syndication' => 'Êtes-vous certain de vouloir supprimer tous les articles syndiqués de ce site ?', # NEW

	// E
	'entree_adresse_fichier_syndication' => 'Adresa dokumenta « backend » za sindikaciju:',
	'entree_adresse_site' => '<b>Adresa stranice</b> [Obavezno]',
	'entree_description_site' => 'OПИС СТРАНИЦЕ',
	'erreur_fichier_format_inconnu' => 'Le format du fichier @fichier@ n\'est pas pris en charge.', # NEW
	'erreur_fichier_incorrect' => 'Impossible de lire le fichier.', # NEW

	// F
	'form_prop_nom_site' => 'Naziv stranice',

	// I
	'icone_article_syndic' => 'Article syndiqué', # NEW
	'icone_articles_syndic' => 'Articles syndiqués', # NEW
	'icone_controler_syndication' => 'Publication des articles syndiqués', # NEW
	'icone_modifier_site' => 'Izmijeni ovu stranicu',
	'icone_referencer_nouveau_site' => 'Preporuciti novu stranicu',
	'icone_site_reference' => 'Sites référencés', # NEW
	'icone_supprimer_article' => 'Supprimer cet article', # NEW
	'icone_supprimer_articles' => 'Supprimer ces articles', # NEW
	'icone_valider_article' => 'Valider cet article', # NEW
	'icone_valider_articles' => 'Valider ces articles', # NEW
	'icone_voir_sites_references' => 'Pogledaj preporucene stranice',
	'info_1_site_importe' => '1 site a été importé', # NEW
	'info_a_valider' => '[za ovjeriti]',
	'info_aucun_site_importe' => 'Aucun site n\'a pu être importé', # NEW
	'info_bloquer' => 'blokirati',
	'info_bloquer_lien' => 'blokiraj ovaj link',
	'info_derniere_syndication' => 'Posljednja sindikacija ove stranice je izvrsena',
	'info_liens_syndiques_1' => 'sindikovani linkovi',
	'info_liens_syndiques_2' => 'na cekanju za ovjeru.',
	'info_nb_sites_importes' => '@nb@ sites ont été importés', # NEW
	'info_nom_site_2' => '<b>Ime stranice</b> [Obavezno]',
	'info_panne_site_syndique' => 'Sindikovana stranica nije u funkciji',
	'info_probleme_grave' => 'problem sa',
	'info_question_proposer_site' => 'Ko moze predloziti preporucene stranice?',
	'info_retablir_lien' => 'obnovi ovaj link',
	'info_site_attente' => 'Web stranica ceka na ovjeru',
	'info_site_propose' => 'Stranica preporucena:',
	'info_site_reference' => 'Preporucene stranice online',
	'info_site_refuse' => 'Web stranica odbijena',
	'info_site_syndique' => 'Ova stranica je sindikovana...', # MODIF
	'info_site_valider' => 'Stranice za ovjeriti',
	'info_sites_referencer' => 'Preporuci stranicu',
	'info_sites_refuses' => 'Odbijene stranice',
	'info_statut_site_1' => 'Ova stranica je:',
	'info_statut_site_2' => 'Objavljena',
	'info_statut_site_3' => 'Predlozena',
	'info_statut_site_4' => 'U korpi za smece', # MODIF
	'info_syndication' => 'sindikacija:',
	'info_syndication_articles' => 'clanak/ci',
	'item_bloquer_liens_syndiques' => 'Blokiraj sindikovane linkove za validaciju',
	'item_gerer_annuaire_site_web' => 'Uredi direktorij za web stranice',
	'item_non_bloquer_liens_syndiques' => 'Ne blokiraj linkove koji su rezultat sindikacije',
	'item_non_gerer_annuaire_site_web' => 'Dezaktiviraj direktorij web stranica',
	'item_non_utiliser_syndication' => 'Ne koristi automatsku sindikaciju',
	'item_utiliser_syndication' => 'Koristi automatsku sindikaciju',

	// L
	'label_exporter_avec_mots_cles_1' => 'Exporter les mots-clés sous forme de tags', # NEW
	'label_exporter_id_parent' => 'Exporter les sites de la rubrique', # NEW
	'label_exporter_publie_seulement_1' => 'Exporter uniquement les sites publiés', # NEW
	'label_fichier_import' => 'Fichier HTML', # NEW
	'label_importer_les_tags_1' => 'Importer les tags sous forme de mot-clé', # NEW
	'label_importer_statut_publie_1' => 'Publier automatiquement les sites', # NEW
	'lien_mise_a_jour_syndication' => 'Osvjezi sada',
	'lien_nouvelle_recuperation' => 'Pokusaj ponovno dobavljanje podataka',
	'lien_purger_syndication' => 'Effacer tous les articles syndiqués', # NEW

	// N
	'nombre_articles_syndic' => '@nb@ articles syndiqués', # NEW

	// S
	'statut_off' => 'Supprimé', # NEW
	'statut_prop' => 'En attente', # NEW
	'statut_publie' => 'Publié', # NEW
	'syndic_choix_moderation' => 'Sta treba uraditi sa sljedecim linkovima sa ove stranice?',
	'syndic_choix_oublier' => 'Sta treba uraditi sa linkovima koji vise nisu prisutni u dokumentu sindikacije?',
	'syndic_choix_resume' => 'Neke stranice nude na raspolaganje cjelokupni tekst clanaka. Ako je taj dostupan, zelite li pristupiti sindikaciji:',
	'syndic_lien_obsolete' => 'zastarijeli link',
	'syndic_option_miroir' => 'atomatski blokiraj',
	'syndic_option_oubli' => 'izbrisi (poslije  @mois@ mmjesec/a/i)',
	'syndic_option_resume_non' => 'kompletni sadrzaj clanaka (u  HTML formatu)',
	'syndic_option_resume_oui' => 'jednostavni rezime (u formi teksta)',
	'syndic_options' => 'Opcije sindikacije:',

	// T
	'texte_expliquer_export_bookmarks' => 'Vous pouvez exporter une liste de sites au format Marque-page HTML,
	pour vous permettre ensuite de l\'importer dans votre navigateur ou dans un service en ligne', # NEW
	'texte_expliquer_import_bookmarks' => 'Vous pouvez importer une liste de sites au format Marque-page HTML,
	en provenance de votre navigateur ou d\'un service en ligne de gestion des Marques-pages.', # NEW
	'texte_liens_sites_syndiques' => 'Linkovi izvedeni iz sindikovanih stranica mogu a priori biti blokirani; dole prikazana postavka  je standardna postavka sindikovanih stranica prije njihove kreacije. U svakom slucaju je moguce pojedinacno deblokirati  svaki link ili, stranicu po stranicu, blokirati linkove koji  dolaze sa odredjene lokacije.', # MODIF
	'texte_messages_publics' => 'Javne poruke clanka:',
	'texte_non_fonction_referencement' => 'Mozete izabrati da ne koristite ovu automatsku funkciju i sami naznaciti elemente vezane za ovu stranicu...', # MODIF
	'texte_referencement_automatique' => '<b>Automatska preporuka stranice</b><br />Mozete brzo preporuciti web stranicu, tako sto cete naznaciti zeljenu URL adresu ili adresu njenog backend dokumenta. SPIP ce automatski sakupiti informacije vezane za tu stranicu (naslov, opis...).', # MODIF
	'texte_referencement_automatique_verifier' => 'Veuillez vérifier les informations fournies par <tt>@url@</tt> avant d\'enregistrer.', # NEW
	'texte_syndication' => 'Moguce je automatsko otkrivanje spiska novosti, ako web stranica to dozvoljava. Zato trebate aktivirati sindikaciju\\tab <blockquote><i>Odredjeni hosting servisi dezaktiviraju tu funkciju; u tom slucaju ne mozete koristiti sindikaciju sadrzaja na vasoj stranici.</i></blockquote>', # MODIF
	'titre_articles_syndiques' => 'Sindikovani clanci, izvuceni iz ove stranice',
	'titre_dernier_article_syndique' => 'Posljednji sindikovani clanci',
	'titre_exporter_bookmarks' => 'Exporter des Marques-pages', # NEW
	'titre_importer_bookmarks' => 'Importer des Marques-pages', # NEW
	'titre_importer_exporter_bookmarks' => 'Importer et Exporter des Marques-pages', # NEW
	'titre_page_sites_tous' => 'Preporucene stranice',
	'titre_referencement_sites' => 'Sindikacija i preporucivanje stranica',
	'titre_site_numero' => 'STRANICA BROJ:',
	'titre_sites_proposes' => 'Predlozene stranice',
	'titre_sites_references_rubrique' => 'Preporucene stranice u ovoj rubrici',
	'titre_sites_syndiques' => 'Sindikovane stranice',
	'titre_sites_tous' => 'Preporucene stranice',
	'titre_syndication' => 'Sindikacija stranica',
	'tout_voir' => 'Voir tous les articles syndiqués', # NEW

	// U
	'un_article_syndic' => '1 article syndiqué' # NEW
);

?>
