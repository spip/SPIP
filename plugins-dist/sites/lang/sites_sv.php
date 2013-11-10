<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/sites?lang_cible=sv
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
	'avis_echec_syndication_01' => 'Uppkopplingen misslyckades: Källfilen är antingen otydbar eller så innehåller den ingen artikel. ',
	'avis_echec_syndication_02' => 'Uppkopplingen misslyckades: Det var inte möjligt att komma åt syndikeringsfilen.',
	'avis_site_introuvable' => 'Webbplatsen hittades inte',
	'avis_site_syndique_probleme' => 'OBS! Syndikeringen av den här sajten har stött på ett problem ; Därför är funktionen tillfälligt avbruten. Var vänlig och verifiera adressen till sajtens syndikeringsfil (<b>@url_syndic@</b>), och försök att göra en ny hämtning av information.',
	'avis_sites_probleme_syndication' => 'Dessa sajter har ett syndikeringsproblem',
	'avis_sites_syndiques_probleme' => 'Det har uppstått ett problem med syndikeringen av sajterna',

	// B
	'bouton_exporter' => 'Exporter', # NEW
	'bouton_importer' => 'Importer', # NEW
	'bouton_radio_modere_posteriori' => 'moderering i efterhand', # MODIF
	'bouton_radio_modere_priori' => 'moderering i förhand', # MODIF
	'bouton_radio_non_syndication' => 'Ingen syndikering',
	'bouton_radio_syndication' => 'Syndikering:',

	// C
	'confirmer_purger_syndication' => 'Êtes-vous certain de vouloir supprimer tous les articles syndiqués de ce site ?', # NEW

	// E
	'entree_adresse_fichier_syndication' => 'Adress till syndikeringsfil:',
	'entree_adresse_site' => '<b>Webbplats URL</b> [Krävs]',
	'entree_description_site' => 'Beskrivning av webbplats',
	'erreur_fichier_format_inconnu' => 'Le format du fichier @fichier@ n\'est pas pris en charge.', # NEW
	'erreur_fichier_incorrect' => 'Impossible de lire le fichier.', # NEW

	// F
	'form_prop_nom_site' => 'Sajtens namn',

	// I
	'icone_article_syndic' => 'Article syndiqué', # NEW
	'icone_articles_syndic' => 'Articles syndiqués', # NEW
	'icone_controler_syndication' => 'Publication des articles syndiqués', # NEW
	'icone_modifier_site' => 'Editera sajten',
	'icone_referencer_nouveau_site' => 'Länka en ny sajt',
	'icone_site_reference' => 'Sites référencés', # NEW
	'icone_supprimer_article' => 'Supprimer cet article', # NEW
	'icone_supprimer_articles' => 'Supprimer ces articles', # NEW
	'icone_valider_article' => 'Valider cet article', # NEW
	'icone_valider_articles' => 'Valider ces articles', # NEW
	'icone_voir_sites_references' => 'Visa länkade sajter',
	'info_1_site_importe' => '1 site a été importé', # NEW
	'info_a_valider' => '[att godkännas]',
	'info_aucun_site_importe' => 'Aucun site n\'a pu être importé', # NEW
	'info_bloquer' => 'Block',
	'info_bloquer_lien' => 'blockera länken',
	'info_derniere_syndication' => 'Den sista syndikeringen av den här sajten skedde den',
	'info_liens_syndiques_1' => 'syndikerade länkar',
	'info_liens_syndiques_2' => 'i väntan på validering.',
	'info_nb_sites_importes' => '@nb@ sites ont été importés', # NEW
	'info_nom_site_2' => '<b>Sajtens namn</b> [krävs]',
	'info_panne_site_syndique' => 'Syndikerad sajt fungerar ej',
	'info_probleme_grave' => 'fel av',
	'info_question_proposer_site' => 'Vem kan föreslå länkar till sajter?',
	'info_retablir_lien' => 'återskapa den här länken',
	'info_site_attente' => 'Webbsajten väntar på godkännande',
	'info_site_propose' => 'Sajt föreslagen den:',
	'info_site_reference' => 'Länkade sajter ',
	'info_site_refuse' => 'Webbsajten refuserad',
	'info_site_syndique' => 'Denna sajt är syndikerad...', # MODIF
	'info_site_valider' => 'Sajter som väntar på godkännande',
	'info_sites_referencer' => 'Länka till en sajt',
	'info_sites_refuses' => 'Refuserade sajter',
	'info_statut_site_1' => 'Denna sajt är:',
	'info_statut_site_2' => 'Publicerad',
	'info_statut_site_3' => 'Inskickad',
	'info_statut_site_4' => 'I papperskorgen', # MODIF
	'info_syndication' => 'syndikering:',
	'info_syndication_articles' => 'artikel(ar)',
	'item_bloquer_liens_syndiques' => 'Stoppa syndikerade länkar från godkännande',
	'item_gerer_annuaire_site_web' => 'Administrera webbsajt-katalogen',
	'item_non_bloquer_liens_syndiques' => 'Blockera inte länkar som kommer ifrån syndikering',
	'item_non_gerer_annuaire_site_web' => 'Avaktivera webbsajt-katalogen',
	'item_non_utiliser_syndication' => 'Använd inte automatisk syndikering',
	'item_utiliser_syndication' => 'Använd automatisk syndikering',

	// L
	'label_exporter_avec_mots_cles_1' => 'Exporter les mots-clés sous forme de tags', # NEW
	'label_exporter_id_parent' => 'Exporter les sites de la rubrique', # NEW
	'label_exporter_publie_seulement_1' => 'Exporter uniquement les sites publiés', # NEW
	'label_fichier_import' => 'Fichier HTML', # NEW
	'label_importer_les_tags_1' => 'Importer les tags sous forme de mot-clé', # NEW
	'label_importer_statut_publie_1' => 'Publier automatiquement les sites', # NEW
	'lien_mise_a_jour_syndication' => 'Uppdatera nu',
	'lien_nouvelle_recuperation' => 'Försök att hämta datum igen',
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
	'syndic_lien_obsolete' => 'Trasig länk',
	'syndic_option_miroir' => 'les bloquer automatiquement', # NEW
	'syndic_option_oubli' => 'les effacer (après @mois@ mois)', # NEW
	'syndic_option_resume_non' => 'det fullständiga innehållet i artiklarna (i HTML-format)',
	'syndic_option_resume_oui' => 'en enkel sammanfattning (i text-format)',
	'syndic_options' => 'Alternativ för syndikering :',

	// T
	'texte_expliquer_export_bookmarks' => 'Vous pouvez exporter une liste de sites au format Marque-page HTML,
	pour vous permettre ensuite de l\'importer dans votre navigateur ou dans un service en ligne', # NEW
	'texte_expliquer_import_bookmarks' => 'Vous pouvez importer une liste de sites au format Marque-page HTML,
	en provenance de votre navigateur ou d\'un service en ligne de gestion des Marques-pages.', # NEW
	'texte_liens_sites_syndiques' => 'Les liens issus des sites syndiqués peuvent
			être bloqués a priori ; le réglage
			ci-dessous indique le réglage par défaut des
			sites syndiqués après leur création. Il
			est ensuite possible, de toutes façons, de
			débloquer chaque lien individuellement, ou de
			choisir, site par site, de bloquer les liens à venir
			de tel ou tel site.', # NEW
	'texte_messages_publics' => 'Messages publics de l\'article :', # NEW
	'texte_non_fonction_referencement' => 'Du kan välja att inte använda den automatiska funktionen och i stället mata in information om sajetn manuellt...', # MODIF
	'texte_referencement_automatique' => '<b>Référencement automatisé d\'un site</b><br />Vous pouvez référencer rapidement un site Web en indiquant ci-dessous l\'adresse URL désirée, ou l\'adresse de son fichier de syndication. SPIP va récupérer automatiquement les informations concernant ce site (titre, description...).', # NEW
	'texte_referencement_automatique_verifier' => 'Veuillez vérifier les informations fournies par <tt>@url@</tt> avant d\'enregistrer.', # NEW
	'texte_syndication' => 'If a site allows it, it is possible to retrieve automatically
		the list of its latest material. To achieve this, you must activate the syndication. 
		<blockquote><i>Some hosts disable this function; 
		in this case, you cannot use the content syndication
		from your site.</i></blockquote>', # MODIF
	'titre_articles_syndiques' => 'Syndikerade artiklar från den här sajten',
	'titre_dernier_article_syndique' => 'Senaste syndikerade artiklar',
	'titre_exporter_bookmarks' => 'Exporter des Marques-pages', # NEW
	'titre_importer_bookmarks' => 'Importer des Marques-pages', # NEW
	'titre_importer_exporter_bookmarks' => 'Importer et Exporter des Marques-pages', # NEW
	'titre_page_sites_tous' => 'Refererade webbplatser',
	'titre_referencement_sites' => 'Référencement de sites et syndication', # NEW
	'titre_site_numero' => 'WEBBPLATS NUMMER',
	'titre_sites_proposes' => 'Les sites proposés', # NEW
	'titre_sites_references_rubrique' => 'Refererade webbplatser i den här avdelningen',
	'titre_sites_syndiques' => 'Les sites syndiqués', # NEW
	'titre_sites_tous' => 'Refererade webbplatser',
	'titre_syndication' => 'Syndication de sites', # NEW
	'tout_voir' => 'Voir tous les articles syndiqués', # NEW

	// U
	'un_article_syndic' => '1 article syndiqué' # NEW
);

?>
