<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://www.spip.net/trad-lang/
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
	'avis_echec_syndication_01' => 'Syndication failed: either the selected backend is unreadable or it does not offer any article.', # NEW
	'avis_echec_syndication_02' => 'Syndication failed: could not reach the backend of this site.', # NEW
	'avis_site_introuvable' => 'Sivusto ei löytynyt',
	'avis_site_syndique_probleme' => 'Attention : la syndication de ce site a rencontré un problème ; le système est donc temporairement interrompu pour l\'instant. Vérifiez l\'adresse du fichier de syndication de ce site (<b>@url_syndic@</b>), et tentez une nouvelle récupération des informations.', # MODIF
	'avis_sites_probleme_syndication' => 'Näillä sivustoilla oli syndikoinnnissa ongelmia',
	'avis_sites_syndiques_probleme' => 'Nämä syndikoidut sivustot tuottivat ongelman',

	// B
	'bouton_exporter' => 'Exporter', # NEW
	'bouton_importer' => 'Importer', # NEW
	'bouton_radio_modere_posteriori' => 'moderointi jälkikäteen', # MODIF
	'bouton_radio_modere_priori' => 'moderointi etukäteen', # MODIF
	'bouton_radio_non_syndication' => 'Ei syndikointia',
	'bouton_radio_syndication' => 'Syndikointi:',

	// C
	'confirmer_purger_syndication' => 'Êtes-vous certain de vouloir supprimer tous les articles syndiqués de ce site ?', # NEW

	// E
	'entree_adresse_fichier_syndication' => '«backend» -tiedoston osoite syndikointia varten:', # MODIF
	'entree_adresse_site' => '<b>Sivusto URL-osoite</b> [Pakollinen]',
	'entree_description_site' => 'Sivuston kuvaus',
	'erreur_fichier_format_inconnu' => 'Le format du fichier @fichier@ n\'est pas pris en charge.', # NEW
	'erreur_fichier_incorrect' => 'Impossible de lire le fichier.', # NEW

	// F
	'form_prop_nom_site' => 'Sivuston nimi',

	// I
	'icone_article_syndic' => 'Article syndiqué', # NEW
	'icone_articles_syndic' => 'Articles syndiqués', # NEW
	'icone_controler_syndication' => 'Publication des articles syndiqués', # NEW
	'icone_modifier_site' => 'Modify this site', # NEW
	'icone_referencer_nouveau_site' => 'Reference a new site', # NEW
	'icone_site_reference' => 'Sites référencés', # NEW
	'icone_supprimer_article' => 'Supprimer cet article', # NEW
	'icone_supprimer_articles' => 'Supprimer ces articles', # NEW
	'icone_valider_article' => 'Valider cet article', # NEW
	'icone_valider_articles' => 'Valider ces articles', # NEW
	'icone_voir_sites_references' => 'Show referenced sites', # NEW
	'info_1_site_importe' => '1 site a été importé', # NEW
	'info_a_valider' => '[varmennettava]',
	'info_aucun_site_importe' => 'Aucun site n\'a pu être importé', # NEW
	'info_bloquer' => 'block', # NEW
	'info_bloquer_lien' => 'estä tämä linkki',
	'info_derniere_syndication' => 'The last syndication of this site was carried out on', # NEW
	'info_liens_syndiques_1' => 'syndicated links', # NEW
	'info_liens_syndiques_2' => 'pending validation.', # NEW
	'info_nb_sites_importes' => '@nb@ sites ont été importés', # NEW
	'info_nom_site_2' => '<b>Sivuston nimi</b> [Pakollinen]',
	'info_panne_site_syndique' => 'Syndikoitu sivusto ei ole toiminnassa',
	'info_probleme_grave' => 'error of', # NEW
	'info_question_proposer_site' => 'Who can propose referenced sites?', # NEW
	'info_retablir_lien' => 'palauta tämä linkki',
	'info_site_attente' => 'Sivusto odottaa varmennusta',
	'info_site_propose' => 'Sivusto lähetetty:',
	'info_site_reference' => 'Viittaavaa sivustoa linjoilla',
	'info_site_refuse' => 'Sivusto hylätty',
	'info_site_syndique' => 'Tämä sivusto on syndikoitu...',
	'info_site_valider' => 'Hyväksyttävät sivustot',
	'info_sites_referencer' => 'Lainata sivustoa',
	'info_sites_refuses' => 'Hylätyt sivustot',
	'info_statut_site_1' => 'Tämä sivusto on:',
	'info_statut_site_2' => 'Julkaistu',
	'info_statut_site_3' => 'Lähetetty',
	'info_statut_site_4' => 'Roskakorissa',
	'info_syndication' => 'syndikointi:', # MODIF
	'info_syndication_articles' => 'artkkeli(t)',
	'item_bloquer_liens_syndiques' => 'Block syndicated links for validation', # NEW
	'item_gerer_annuaire_site_web' => 'Manage Web sites directory', # NEW
	'item_non_bloquer_liens_syndiques' => 'Do not block the links emanating from syndication', # NEW
	'item_non_gerer_annuaire_site_web' => 'Disable Web sites directory', # NEW
	'item_non_utiliser_syndication' => 'Do not use automated syndication', # NEW
	'item_utiliser_syndication' => 'Use automated syndication', # NEW

	// L
	'label_exporter_avec_mots_cles_1' => 'Exporter les mots-clés sous forme de tags', # NEW
	'label_exporter_id_parent' => 'Exporter les sites de la rubrique', # NEW
	'label_exporter_publie_seulement_1' => 'Exporter uniquement les sites publiés', # NEW
	'label_fichier_import' => 'Fichier HTML', # NEW
	'label_importer_les_tags_1' => 'Importer les tags sous forme de mot-clé', # NEW
	'label_importer_statut_publie_1' => 'Publier automatiquement les sites', # NEW
	'lien_mise_a_jour_syndication' => 'Update now', # NEW
	'lien_nouvelle_recuperation' => 'Try to perform a new retrieval of data', # NEW
	'lien_purger_syndication' => 'Effacer tous les articles syndiqués', # NEW

	// N
	'nombre_articles_syndic' => '@nb@ articles syndiqués', # NEW

	// S
	'statut_off' => 'Supprimé', # NEW
	'statut_prop' => 'En attente', # NEW
	'statut_publie' => 'Publié', # NEW
	'syndic_choix_moderation' => 'What should be done with the next links from this site?', # NEW
	'syndic_choix_oublier' => 'What should be done with links which are no longer present in the syndication file?', # NEW
	'syndic_choix_resume' => 'Some sites offer the full text of their articles. When the full text is available, do you wish to syndicate:', # NEW
	'syndic_lien_obsolete' => 'obsolete link', # NEW
	'syndic_option_miroir' => 'block them automatically', # NEW
	'syndic_option_oubli' => 'delete them (after @mois@ months)', # NEW
	'syndic_option_resume_non' => 'the full content of the articles (HTML format)', # NEW
	'syndic_option_resume_oui' => 'just a summary (text format)', # NEW
	'syndic_options' => 'Syndication options:', # NEW

	// T
	'texte_expliquer_export_bookmarks' => 'Vous pouvez exporter une liste de sites au format Marque-page HTML,
	pour vous permettre ensuite de l\'importer dans votre navigateur ou dans un service en ligne', # NEW
	'texte_expliquer_import_bookmarks' => 'Vous pouvez importer une liste de sites au format Marque-page HTML,
	en provenance de votre navigateur ou d\'un service en ligne de gestion des Marques-pages.', # NEW
	'texte_liens_sites_syndiques' => 'Links emanating from syndicated sites could
			be blocked beforehand; the following
			setting show the default setting of
			syndicated sites after their creation. It
			is, then, possible anyway to
			block each link individually, or to
			choose, for each site, to block the links coming
			from any particular site.', # NEW
	'texte_messages_publics' => 'Public Messages of the article:', # NEW
	'texte_non_fonction_referencement' => 'You can choose not to use this automated feature, and enter the elements concerning that site manually...', # NEW
	'texte_referencement_automatique' => '<b>Automated site referencing</b><br />You can reference a Web site quickly by indicating below the desired URL, or the address of its syndication file. SPIP will automatically retrieve the site\'s information (title, description...).', # NEW
	'texte_referencement_automatique_verifier' => 'Please, verify the information provided by <tt>@url@</tt> before saving.', # NEW
	'texte_syndication' => 'Il est possible de récupérer automatiquement, lorsqu\'un site Web le permet, 
		la liste de ses nouveautés. Pour cela, vous devez activer la syndication. 
		<blockquote><i>Certains hébergeurs désactivent cette fonctionnalité ; 
		dans ce cas, vous ne pourrez pas utiliser la syndication de contenu
		depuis votre site.</i></blockquote>', # MODIF
	'titre_articles_syndiques' => 'Syndicated articles pulled out from this site', # NEW
	'titre_dernier_article_syndique' => 'Viimeisimmät syndikoidut artikkelit',
	'titre_exporter_bookmarks' => 'Exporter des Marques-pages', # NEW
	'titre_importer_bookmarks' => 'Importer des Marques-pages', # NEW
	'titre_importer_exporter_bookmarks' => 'Importer et Exporter des Marques-pages', # NEW
	'titre_page_sites_tous' => 'Lähdesivustot',
	'titre_referencement_sites' => 'Sivustojen lainaukset ja syndikoinnit',
	'titre_site_numero' => 'SIVUSTO NUMERO:',
	'titre_sites_proposes' => 'Lähetetyt sivustot',
	'titre_sites_references_rubrique' => 'Viitatut sivustot tässä lohkossa',
	'titre_sites_syndiques' => 'Syndikoidut sivustot',
	'titre_sites_tous' => 'Viitatut sivustot',
	'titre_syndication' => 'Sites syndication', # NEW
	'tout_voir' => 'Voir tous les articles syndiqués', # NEW

	// U
	'un_article_syndic' => '1 article syndiqué' # NEW
);

?>
