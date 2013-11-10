<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net
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
	'avis_echec_syndication_01' => '<NEW> La syndication a échoué : le backend indiqué est indéchiffrable ou ne propose aucun article.',
	'avis_echec_syndication_02' => '<NEW> La syndication a échoué : impossible d\'accéder au backend de ce site.',
	'avis_site_introuvable' => '<NEW> Site introuvable',
	'avis_site_syndique_probleme' => '<NEW> Attention : la syndication de ce site a rencontré un problème ; le système est donc temporairement interrompu pour l\'instant. Vérifiez l\'adresse du fichier de syndication de ce site (<b>@url_syndic@</b>), et tentez une nouvelle récupération des informations.', # MODIF
	'avis_sites_probleme_syndication' => '<NEW> Ces sites ont rencontré un problème de syndication',
	'avis_sites_syndiques_probleme' => '<NEW> Ces sites syndiqués ont posé un problème',

	// B
	'bouton_exporter' => 'Exporter', # NEW
	'bouton_importer' => 'Importer', # NEW
	'bouton_radio_modere_posteriori' => '<NEW> modéré à posteriori', # MODIF
	'bouton_radio_modere_priori' => '<NEW> modéré à priori', # MODIF
	'bouton_radio_non_syndication' => '<NEW> Pas de syndication',
	'bouton_radio_syndication' => '<NEW> Syndication :',

	// C
	'confirmer_purger_syndication' => 'Êtes-vous certain de vouloir supprimer tous les articles syndiqués de ce site ?', # NEW

	// E
	'entree_adresse_fichier_syndication' => '<NEW> Adresse du fichier « backend » pour la syndication :',
	'entree_adresse_site' => '<NEW> <b>Adresse du site</b> [Obligatoire]',
	'entree_description_site' => 'Lýsing á síðunni',
	'erreur_fichier_format_inconnu' => 'Le format du fichier @fichier@ n\'est pas pris en charge.', # NEW
	'erreur_fichier_incorrect' => 'Impossible de lire le fichier.', # NEW

	// F
	'form_prop_nom_site' => '<NEW> Nom du site',

	// I
	'icone_article_syndic' => 'Article syndiqué', # NEW
	'icone_articles_syndic' => 'Articles syndiqués', # NEW
	'icone_controler_syndication' => 'Publication des articles syndiqués', # NEW
	'icone_modifier_site' => 'Breyta þessari heimasíðu',
	'icone_referencer_nouveau_site' => '<NEW> Référencer un nouveau site',
	'icone_site_reference' => 'Sites référencés', # NEW
	'icone_supprimer_article' => 'Supprimer cet article', # NEW
	'icone_supprimer_articles' => 'Supprimer ces articles', # NEW
	'icone_valider_article' => 'Valider cet article', # NEW
	'icone_valider_articles' => 'Valider ces articles', # NEW
	'icone_voir_sites_references' => '<NEW> Voir les sites référencés',
	'info_1_site_importe' => '1 site a été importé', # NEW
	'info_a_valider' => '[staðfesta]',
	'info_aucun_site_importe' => 'Aucun site n\'a pu être importé', # NEW
	'info_bloquer' => 'bloquer', # NEW
	'info_bloquer_lien' => '<NEW> bloquer ce lien',
	'info_derniere_syndication' => '<NEW> La dernière syndication de ce site a été effectuée le',
	'info_liens_syndiques_1' => '<NEW> liens syndiqués',
	'info_liens_syndiques_2' => '<NEW> sont en attente de validation.',
	'info_nb_sites_importes' => '@nb@ sites ont été importés', # NEW
	'info_nom_site_2' => '<NEW> <b>Nom du site</b> [Obligatoire]',
	'info_panne_site_syndique' => '<NEW> Site syndiqué en panne',
	'info_probleme_grave' => '<NEW> problème de',
	'info_question_proposer_site' => '<NEW> Qui peut proposer des sites référencés ?',
	'info_retablir_lien' => '<NEW> rétablir ce lien',
	'info_site_attente' => '<NEW> Site Web en attente de validation',
	'info_site_propose' => '<NEW> Site proposé le :',
	'info_site_reference' => '<NEW> Site référencé en ligne',
	'info_site_refuse' => '<NEW> Site Web refusé',
	'info_site_syndique' => '<NEW> Ce site est syndiqué...', # MODIF
	'info_site_valider' => '<NEW> Sites à valider',
	'info_sites_referencer' => '<NEW> Référencer un site',
	'info_sites_refuses' => '<NEW> Les sites refusés',
	'info_statut_site_1' => '<NEW> Ce site est :',
	'info_statut_site_2' => '<NEW> Publié',
	'info_statut_site_3' => '<NEW> Proposé',
	'info_statut_site_4' => '<NEW> A la poubelle', # MODIF
	'info_syndication' => '<NEW> syndication :',
	'info_syndication_articles' => '<NEW> article(s)',
	'item_bloquer_liens_syndiques' => '<NEW> Bloquer les liens syndiqués pour validation',
	'item_gerer_annuaire_site_web' => '<NEW> Gérer un annuaire de sites Web',
	'item_non_bloquer_liens_syndiques' => '<NEW> Ne pas bloquer les liens issus de la syndication',
	'item_non_gerer_annuaire_site_web' => '<NEW> Désactiver l\'annuaire de sites Web',
	'item_non_utiliser_syndication' => '<NEW> Ne pas utiliser la syndication automatique',
	'item_utiliser_syndication' => '<NEW> Utiliser la syndication automatique',

	// L
	'label_exporter_avec_mots_cles_1' => 'Exporter les mots-clés sous forme de tags', # NEW
	'label_exporter_id_parent' => 'Exporter les sites de la rubrique', # NEW
	'label_exporter_publie_seulement_1' => 'Exporter uniquement les sites publiés', # NEW
	'label_fichier_import' => 'Fichier HTML', # NEW
	'label_importer_les_tags_1' => 'Importer les tags sous forme de mot-clé', # NEW
	'label_importer_statut_publie_1' => 'Publier automatiquement les sites', # NEW
	'lien_mise_a_jour_syndication' => '<NEW> Mettre à jour maintenant',
	'lien_nouvelle_recuperation' => '<NEW> Tenter une nouvelle récupération des données',
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
	'texte_liens_sites_syndiques' => '<NEW> Les liens issus des sites syndiqués peuvent
			être bloqués a priori ; le réglage
			ci-dessous indique le réglage par défaut des
			sites syndiqués après leur création. Il
			est ensuite possible, de toutes façons, de
			débloquer chaque lien individuellement, ou de
			choisir, site par site, de bloquer les liens à venir
			de tel ou tel site.', # MODIF
	'texte_messages_publics' => '<NEW> Messages publics de l\'article :',
	'texte_non_fonction_referencement' => '<NEW> Vous pouvez préférer ne pas utiliser cette fonction automatique, et indiquer vous-même les éléments concernant ce site...', # MODIF
	'texte_referencement_automatique' => '<NEW> <b>Référencement automatisé d\'un site</b><br />Vous pouvez référencer rapidement un site Web en indiquant ci-dessous l\'adresse URL désirée, ou l\'adresse de son fichier backend. SPIP va récupérer automatiquement les informations concernant ce site (titre, description...).', # MODIF
	'texte_referencement_automatique_verifier' => 'Veuillez vérifier les informations fournies par <tt>@url@</tt> avant d\'enregistrer.', # NEW
	'texte_syndication' => '<NEW> Il est possible de récupérer automatiquement, lorsqu\'un site Web le permet, 
		la liste de ses nouveautés. Pour cela, vous devez activer la syndication. 
		<blockquote><i>Certains hébergeurs désactivent cette fonctionnalité ; 
		dans ce cas, vous ne pourrez pas utiliser la syndication de contenu
		depuis votre site.</i></blockquote>', # MODIF
	'titre_articles_syndiques' => '<NEW> Articles syndiqués tirés de ce site',
	'titre_dernier_article_syndique' => '<NEW> Derniers articles syndiqués',
	'titre_exporter_bookmarks' => 'Exporter des Marques-pages', # NEW
	'titre_importer_bookmarks' => 'Importer des Marques-pages', # NEW
	'titre_importer_exporter_bookmarks' => 'Importer et Exporter des Marques-pages', # NEW
	'titre_page_sites_tous' => '<NEW> Les sites référencés',
	'titre_referencement_sites' => '<NEW> Référencement de sites et syndication',
	'titre_site_numero' => '<NEW> SITE NUMÉRO :',
	'titre_sites_proposes' => '<NEW> Les sites proposés',
	'titre_sites_references_rubrique' => '<NEW> Les sites référencés dans cette rubrique',
	'titre_sites_syndiques' => '<NEW> Les sites syndiqués',
	'titre_sites_tous' => '<NEW> Les sites référencés',
	'titre_syndication' => '<NEW> Syndication de sites',
	'tout_voir' => 'Voir tous les articles syndiqués', # NEW

	// U
	'un_article_syndic' => '1 article syndiqué' # NEW
);

?>
