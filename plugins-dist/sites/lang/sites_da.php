<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/sites?lang_cible=da
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
	'avis_echec_syndication_01' => 'Syndikering mislykket: enten er den valgte datakilde utilgængelig eller også indeholder den ingen artikler.',
	'avis_echec_syndication_02' => 'Syndication mislykket: kunne ikke få forbindelse til den valgte datakilde.',
	'avis_site_introuvable' => 'Webstedet ikke fundet',
	'avis_site_syndique_probleme' => 'Advarsel: syndikering med dette websted er stødt på et problem; derfor afbrydes systemet midlertidigt. Kontroller adressen på webstedets syndikeringsfil (<b>@url_syndic@</b>), og prøv at få adgang til data igen.', # MODIF
	'avis_sites_probleme_syndication' => 'Disse websteder har problemer med syndikering',
	'avis_sites_syndiques_probleme' => 'Disse syndikerede sider giver problemer',

	// B
	'bouton_exporter' => 'Exporter', # NEW
	'bouton_importer' => 'Importer', # NEW
	'bouton_radio_modere_posteriori' => 'Efterfølgende godkendelse (bidrag er straks synlige men en administrator kan senere slette dem)', # MODIF
	'bouton_radio_modere_priori' => 'Forhåndsgodkendelse', # MODIF
	'bouton_radio_non_syndication' => 'Ingen syndikering',
	'bouton_radio_syndication' => 'Syndikering:',

	// C
	'confirmer_purger_syndication' => 'Êtes-vous certain de vouloir supprimer tous les articles syndiqués de ce site ?', # NEW

	// E
	'entree_adresse_fichier_syndication' => 'Adresse på syndikeringsfil:',
	'entree_adresse_site' => '<b>URL på websted</b> [Skal oplyses]',
	'entree_description_site' => 'Beskrivelse af websted',
	'erreur_fichier_format_inconnu' => 'Le format du fichier @fichier@ n\'est pas pris en charge.', # NEW
	'erreur_fichier_incorrect' => 'Impossible de lire le fichier.', # NEW

	// F
	'form_prop_nom_site' => 'Webstedets navn',

	// I
	'icone_article_syndic' => 'Article syndiqué', # NEW
	'icone_articles_syndic' => 'Articles syndiqués', # NEW
	'icone_controler_syndication' => 'Publication des articles syndiqués', # NEW
	'icone_modifier_site' => 'Ret dette websted',
	'icone_referencer_nouveau_site' => 'Ny webstedshenvisning',
	'icone_site_reference' => 'Sites référencés', # NEW
	'icone_supprimer_article' => 'Supprimer cet article', # NEW
	'icone_supprimer_articles' => 'Supprimer ces articles', # NEW
	'icone_valider_article' => 'Valider cet article', # NEW
	'icone_valider_articles' => 'Valider ces articles', # NEW
	'icone_voir_sites_references' => 'Vis links',
	'info_1_site_importe' => '1 site a été importé', # NEW
	'info_a_valider' => '[at kontrollere]',
	'info_aucun_site_importe' => 'Aucun site n\'a pu être importé', # NEW
	'info_bloquer' => 'bloquer', # NEW
	'info_bloquer_lien' => 'bloker dette link',
	'info_derniere_syndication' => 'Sidste syndikering af dette websted blev udført den',
	'info_liens_syndiques_1' => 'syndikerede links',
	'info_liens_syndiques_2' => 'afventer godkendelse.',
	'info_nb_sites_importes' => '@nb@ sites ont été importés', # NEW
	'info_nom_site_2' => '<b>Webstedets navn</b> [Skal udfyldes]',
	'info_panne_site_syndique' => 'Syndikeret side er ude af trit',
	'info_probleme_grave' => 'problem med',
	'info_question_proposer_site' => 'Hvem kan foreslå henvisninger til websteder?',
	'info_retablir_lien' => 'Genopret dette link',
	'info_site_attente' => 'Websted afventer godkendelse',
	'info_site_propose' => 'Websted sendt til godkendelse den:',
	'info_site_reference' => 'Online henvisning',
	'info_site_refuse' => 'Websted afvist',
	'info_site_syndique' => 'Dette websted er syndikeret...', # MODIF
	'info_site_valider' => 'Websteder der afventer godkendelse',
	'info_sites_referencer' => 'Link til websted',
	'info_sites_refuses' => 'Afviste websteder',
	'info_statut_site_1' => 'Dette websted er:',
	'info_statut_site_2' => 'Offentliggjort',
	'info_statut_site_3' => 'Indsendt',
	'info_statut_site_4' => 'I papirkurven', # MODIF
	'info_syndication' => 'syndikering:',
	'info_syndication_articles' => 'bidrag',
	'item_bloquer_liens_syndiques' => 'Afspær syndikerede links indtil de er godkendt',
	'item_gerer_annuaire_site_web' => 'Vedligehold katalog over websteder',
	'item_non_bloquer_liens_syndiques' => 'Undlad at spærre links til syndikerede websteder',
	'item_non_gerer_annuaire_site_web' => 'Vedligehold ikke katalog over websteder',
	'item_non_utiliser_syndication' => 'Benyt ikke automatisk syndikering',
	'item_utiliser_syndication' => 'Benyt automatisk syndikering',

	// L
	'label_exporter_avec_mots_cles_1' => 'Exporter les mots-clés sous forme de tags', # NEW
	'label_exporter_id_parent' => 'Exporter les sites de la rubrique', # NEW
	'label_exporter_publie_seulement_1' => 'Exporter uniquement les sites publiés', # NEW
	'label_fichier_import' => 'Fichier HTML', # NEW
	'label_importer_les_tags_1' => 'Importer les tags sous forme de mot-clé', # NEW
	'label_importer_statut_publie_1' => 'Publier automatiquement les sites', # NEW
	'lien_mise_a_jour_syndication' => 'Opdater nu',
	'lien_nouvelle_recuperation' => 'Forsøg at hente data igen',
	'lien_purger_syndication' => 'Effacer tous les articles syndiqués', # NEW

	// N
	'nombre_articles_syndic' => '@nb@ articles syndiqués', # NEW

	// S
	'statut_off' => 'Supprimé', # NEW
	'statut_prop' => 'En attente', # NEW
	'statut_publie' => 'Publié', # NEW
	'syndic_choix_moderation' => 'Que faire des prochains liens en provenance de ce site ?', # NEW
	'syndic_choix_oublier' => 'Que faire des liens qui ne figurent plus dans le fichier de syndication ?', # NEW
	'syndic_choix_resume' => 'Certains sites diffusent le texte complet des articles. Lorsque celui-ci est disponible souhaitez-vous syndiquer :', # NEW
	'syndic_lien_obsolete' => 'lien obsolète', # NEW
	'syndic_option_miroir' => 'les bloquer automatiquement', # NEW
	'syndic_option_oubli' => 'les effacer (après @mois@ mois)', # NEW
	'syndic_option_resume_non' => 'le contenu complet des articles (au format HTML)', # NEW
	'syndic_option_resume_oui' => 'un simple résumé (au format texte)', # NEW
	'syndic_options' => 'Options de syndication :', # NEW

	// T
	'texte_expliquer_export_bookmarks' => 'Vous pouvez exporter une liste de sites au format Marque-page HTML,
	pour vous permettre ensuite de l\'importer dans votre navigateur ou dans un service en ligne', # NEW
	'texte_expliquer_import_bookmarks' => 'Vous pouvez importer une liste de sites au format Marque-page HTML,
	en provenance de votre navigateur ou d\'un service en ligne de gestion des Marques-pages.', # NEW
	'texte_liens_sites_syndiques' => 'Links til syndikerede sider kan spærres på forhånd; følgende indstilling er standardindstillingen for syndikerede websteder, når de er oprettet.
			Det er således på trods heraf muligt at spærre hvert link individuelt eller at vælge for hver websted at spærre de links der kommer fra en givet websted.', # MODIF
	'texte_messages_publics' => 'Offentlige bidrag til artiklen:',
	'texte_non_fonction_referencement' => 'Du kan vælge ikke at bruge denne automatiske funktion, og selv angive de elementer, der er vigtige for webstedet...', # MODIF
	'texte_referencement_automatique' => '<b>Automatiserede webstedshenvisninger</b><br />
		Du kan hurtigt henvise til et websted ved nedenfor at angive dens URL eller adressen på dens datakilde. 
		SPIP vil automatisk indhente oplysninger om webstedet (titel, beskrivelse...).', # MODIF
	'texte_referencement_automatique_verifier' => 'Veuillez vérifier les informations fournies par <tt>@url@</tt> avant d\'enregistrer.', # NEW
	'texte_syndication' => 'Hvis webstedet tillader det, er det muligt automatisk at hente en oversigt over det
		seneste materiale. For at gøre dette, skal du igangsætte syndikering.
		<blockquote><i>Nogle webhoteller tillader ikke denne funktion.
		I så fald kan du ikke foretage indholdssyndikering fra dit websted.</i></blockquote>', # MODIF
	'titre_articles_syndiques' => 'Syndikerede artikler hentet på dette websted',
	'titre_dernier_article_syndique' => 'Senest syndikerede artikler',
	'titre_exporter_bookmarks' => 'Exporter des Marques-pages', # NEW
	'titre_importer_bookmarks' => 'Importer des Marques-pages', # NEW
	'titre_importer_exporter_bookmarks' => 'Importer et Exporter des Marques-pages', # NEW
	'titre_page_sites_tous' => 'Links til websteder',
	'titre_referencement_sites' => 'Henvisning og syndikering til websteder',
	'titre_site_numero' => 'WEBSTEDSNUMMER:',
	'titre_sites_proposes' => 'Indsendte websteder',
	'titre_sites_references_rubrique' => 'Websteder der henvises til i dette afsnit',
	'titre_sites_syndiques' => 'Syndikerede websteder',
	'titre_sites_tous' => 'Websteder der henvises til',
	'titre_syndication' => 'Webstedssyndikering',
	'tout_voir' => 'Voir tous les articles syndiqués', # NEW

	// U
	'un_article_syndic' => '1 article syndiqué' # NEW
);

?>
