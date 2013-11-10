<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/sites?lang_cible=ro
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
	'avis_echec_syndication_01' => 'Sindicalizarea a eşuat: backend-ul indicat este indescifrabil sau nu propune nici un articol.',
	'avis_echec_syndication_02' => 'Sindicalizarea a eşuat: imposibil de accesat backend-ul acestui site.',
	'avis_site_introuvable' => 'Site negăsit',
	'avis_site_syndique_probleme' => 'Atenţie: sindicalizarea acestui site nu a fost posibilă ; sistemul este pentru moment întrerupt. Verificaţi adresa fişierului de sindicalizare a acestui site (<b>@url_syndic@</b>), şi reîncercaţi operaţia.',
	'avis_sites_probleme_syndication' => 'Aceste site-uri au avut probleme de sindicalizare a conţinutului',
	'avis_sites_syndiques_probleme' => 'Aceste site-uri sindicalizate au avut o problemă',

	// B
	'bouton_exporter' => 'Exporter', # NEW
	'bouton_importer' => 'Importer', # NEW
	'bouton_radio_modere_posteriori' => 'moderare ulterioară', # MODIF
	'bouton_radio_modere_priori' => 'moderare à priori', # MODIF
	'bouton_radio_non_syndication' => 'Fără sindicalizare',
	'bouton_radio_syndication' => 'Sindicalizare :',

	// C
	'confirmer_purger_syndication' => 'Êtes-vous certain de vouloir supprimer tous les articles syndiqués de ce site ?', # NEW

	// E
	'entree_adresse_fichier_syndication' => 'Adresa fişierului pentru sindicalizare :',
	'entree_adresse_site' => '<b>Adresa site-ului</b> [Obligatorie]',
	'entree_description_site' => 'Descrierea site-ului',
	'erreur_fichier_format_inconnu' => 'Le format du fichier @fichier@ n\'est pas pris en charge.', # NEW
	'erreur_fichier_incorrect' => 'Impossible de lire le fichier.', # NEW

	// F
	'form_prop_nom_site' => 'Numele site-ului',

	// I
	'icone_article_syndic' => 'Article syndiqué', # NEW
	'icone_articles_syndic' => 'Articles syndiqués', # NEW
	'icone_controler_syndication' => 'Publication des articles syndiqués', # NEW
	'icone_modifier_site' => 'Modificaţi acest site',
	'icone_referencer_nouveau_site' => 'Referenţiaţi un nou site',
	'icone_site_reference' => 'Sites référencés', # NEW
	'icone_supprimer_article' => 'Supprimer cet article', # NEW
	'icone_supprimer_articles' => 'Supprimer ces articles', # NEW
	'icone_valider_article' => 'Valider cet article', # NEW
	'icone_valider_articles' => 'Valider ces articles', # NEW
	'icone_voir_sites_references' => 'Vedeţi site-urile referenţiate',
	'info_1_site_importe' => '1 site a été importé', # NEW
	'info_a_valider' => '[de validat]',
	'info_aucun_site_importe' => 'Aucun site n\'a pu être importé', # NEW
	'info_bloquer' => 'blocaţi',
	'info_bloquer_lien' => 'blocaţi această legătură',
	'info_derniere_syndication' => 'Ultima sindicalizare a acestui site a fost făcuta la data de',
	'info_liens_syndiques_1' => 'legăturile sindicalizate',
	'info_liens_syndiques_2' => ' sunt în aşteptarea validării.',
	'info_nb_sites_importes' => '@nb@ sites ont été importés', # NEW
	'info_nom_site_2' => '<b>Nume site</b> [Obligatoriu]',
	'info_panne_site_syndique' => 'Site sidicalizat în pană',
	'info_probleme_grave' => 'problemă de',
	'info_question_proposer_site' => 'Cine poate propune site-uri referenţiate ?',
	'info_retablir_lien' => 'restabiliţi această legatură',
	'info_site_attente' => 'Site Web în aşteptarea validării',
	'info_site_propose' => 'Site propus la data de :',
	'info_site_reference' => 'Site referenţiat în direct',
	'info_site_refuse' => 'Site Web refuzat',
	'info_site_syndique' => 'Acest site este sindicalizat...', # MODIF
	'info_site_valider' => 'Site-uri de validat',
	'info_sites_referencer' => 'Referenţiere site',
	'info_sites_refuses' => 'Site-uri refuzate',
	'info_statut_site_1' => 'Acest site este :',
	'info_statut_site_2' => 'Publicat',
	'info_statut_site_3' => 'Propus',
	'info_statut_site_4' => 'La coşul de gunoi', # MODIF
	'info_syndication' => 'sindicare :',
	'info_syndication_articles' => 'articol(e)',
	'item_bloquer_liens_syndiques' => 'Blocaţi legăturile sindicalizate pentru validare',
	'item_gerer_annuaire_site_web' => 'Gestionaţi un anuar de site-uri Web',
	'item_non_bloquer_liens_syndiques' => 'Nu blocaţi legăturile provenite din sindicalizare',
	'item_non_gerer_annuaire_site_web' => 'Dezactivaţi anuarul site-urilor Web',
	'item_non_utiliser_syndication' => 'Nu folosiţi sindicalizarea automatică',
	'item_utiliser_syndication' => 'Utilizaţi sindicalizarea automatică',

	// L
	'label_exporter_avec_mots_cles_1' => 'Exporter les mots-clés sous forme de tags', # NEW
	'label_exporter_id_parent' => 'Exporter les sites de la rubrique', # NEW
	'label_exporter_publie_seulement_1' => 'Exporter uniquement les sites publiés', # NEW
	'label_fichier_import' => 'Fichier HTML', # NEW
	'label_importer_les_tags_1' => 'Importer les tags sous forme de mot-clé', # NEW
	'label_importer_statut_publie_1' => 'Publier automatiquement les sites', # NEW
	'lien_mise_a_jour_syndication' => 'Aduceţi la zi acum',
	'lien_nouvelle_recuperation' => 'Încercaţi încă o dată recuperarea datelor',
	'lien_purger_syndication' => 'Effacer tous les articles syndiqués', # NEW

	// N
	'nombre_articles_syndic' => '@nb@ articles syndiqués', # NEW

	// S
	'statut_off' => 'Supprimé', # NEW
	'statut_prop' => 'En attente', # NEW
	'statut_publie' => 'Publié', # NEW
	'syndic_choix_moderation' => 'Ce să fac cu următoarele legături provenind de la acest site ?',
	'syndic_choix_oublier' => 'Ce să fac cu legăturile care nu mai figurează în fişierul de sindicalizare ?',
	'syndic_choix_resume' => 'Anumite site-uri difuzează textul complet al articolelor. Când acesta este disponibil, doriţi să sindicalizaţi: :',
	'syndic_lien_obsolete' => 'legătură învechită',
	'syndic_option_miroir' => 'blocare automată',
	'syndic_option_oubli' => 'ştergere (după @mois@ luni)',
	'syndic_option_resume_non' => 'conţinutul complet al articolelor (în format HTML)',
	'syndic_option_resume_oui' => 'un rezumat simplu (în format text)',
	'syndic_options' => 'Opţiuni de syndicalizare :',

	// T
	'texte_expliquer_export_bookmarks' => 'Vous pouvez exporter une liste de sites au format Marque-page HTML,
	pour vous permettre ensuite de l\'importer dans votre navigateur ou dans un service en ligne', # NEW
	'texte_expliquer_import_bookmarks' => 'Vous pouvez importer une liste de sites au format Marque-page HTML,
	en provenance de votre navigateur ou d\'un service en ligne de gestion des Marques-pages.', # NEW
	'texte_liens_sites_syndiques' => 'Legăturile provenind de la site-urile sindicalizate pot
   fi blocate à priori ; reglajul
   de mai jos indică setarea implicită aplicată
   site-urilor sindicalizate după crearea lor. Este
   posibil după aceea să deblocaţi fiecare legătură în mod individual, sau să
   alegeţi, site cu site, să blocaţi legăturile.', # MODIF
	'texte_messages_publics' => 'Mesajele publice ale articolului :',
	'texte_non_fonction_referencement' => 'Puteţi să alegeţi să nu folosiţi această funcţie automată, ci să indicaţi chiar dumneavoastră elementele legate de acest site...', # MODIF
	'texte_referencement_automatique' => '<b>Referenţierea automatică a unui site</b><br />Puteţi să referenţiaţi rapid un site Web indicând mai jos adresa sa URL, sau adresa fişierului său de sindicalizare. SPIP va recupera în mod automat informaţiile referitoare la acest site (titlu, descriere, ş.a.m.d.).', # MODIF
	'texte_referencement_automatique_verifier' => 'Vă rugăm să verificaţi informaţiile furnizate de <tt>@url@</tt> înainte de a înregistra.',
	'texte_syndication' => 'Este posibilă recuperarea îm mod automat, pentru site-urile de Web care o permit, 
  a listei noutăţilor lor. Pentru aceasta trebuie să activaţi sindicalizarea. 
  <blockquote><i>Anumiţi furnizori dezactivează această funcţionalitate ;
  în acest caz, nu veţi putea folosi sindicalizarea conţinutului în site-ul dumneavoastră.</i></blockquote>', # MODIF
	'titre_articles_syndiques' => 'Articole sindicalizate luate de pe acest site',
	'titre_dernier_article_syndique' => 'Ultimele articole sindicalizate',
	'titre_exporter_bookmarks' => 'Exporter des Marques-pages', # NEW
	'titre_importer_bookmarks' => 'Importer des Marques-pages', # NEW
	'titre_importer_exporter_bookmarks' => 'Importer et Exporter des Marques-pages', # NEW
	'titre_page_sites_tous' => 'Site-urile referenţiate',
	'titre_referencement_sites' => 'Referenţierea de site-uri şi sindicalizarea',
	'titre_site_numero' => 'SITE NUMĂRUL :',
	'titre_sites_proposes' => 'Site-urile propuse',
	'titre_sites_references_rubrique' => 'Site-urile referenţiate în această rubrică',
	'titre_sites_syndiques' => 'Site-urile sindicalizate',
	'titre_sites_tous' => 'Site-urile referenţiate',
	'titre_syndication' => 'Sindicalizarea site-urilor',
	'tout_voir' => 'Voir tous les articles syndiqués', # NEW

	// U
	'un_article_syndic' => '1 article syndiqué' # NEW
);

?>
