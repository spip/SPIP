<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/sites?lang_cible=cs
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
	'avis_echec_syndication_01' => 'Vytvoření dat selhalo: Buď nelze číst z vybraného základního systému (backend) nebo na něm není žádný článek.',
	'avis_echec_syndication_02' => 'Selhalo zpracování dat: Nelze komunikovat se základním systémem (backend) těchto stránek.',
	'avis_site_introuvable' => 'Web nenalezen',
	'avis_site_syndique_probleme' => 'Varování: při syndikalizaci tohoto webu došlo k potížím. Systém je proto nefunkční. Zkontrolujte adresu syndikalizačního souborutohoto webu(<b>@url_syndic@</b>) a zkuste znovu provést obnovu informací.', # MODIF
	'avis_sites_probleme_syndication' => 'Na těchto webech došlo k problémům se syndikalizací',
	'avis_sites_syndiques_probleme' => 'Problém pochází z těchto syndikovaných webů',

	// B
	'bouton_exporter' => 'Exporter', # NEW
	'bouton_importer' => 'Importer', # NEW
	'bouton_radio_modere_posteriori' => 'moderování ex post', # MODIF
	'bouton_radio_modere_priori' => 'moderování předem', # MODIF
	'bouton_radio_non_syndication' => 'Bez syndikace',
	'bouton_radio_syndication' => 'Syndikace:',

	// C
	'confirmer_purger_syndication' => 'Êtes-vous certain de vouloir supprimer tous les articles syndiqués de ce site ?', # NEW

	// E
	'entree_adresse_fichier_syndication' => 'Adresa souboru pro syndikaci:',
	'entree_adresse_site' => '<b>Adresa webu</b> [povinný údaj]',
	'entree_description_site' => 'Popis webu',
	'erreur_fichier_format_inconnu' => 'Le format du fichier @fichier@ n\'est pas pris en charge.', # NEW
	'erreur_fichier_incorrect' => 'Impossible de lire le fichier.', # NEW

	// F
	'form_prop_nom_site' => 'Název webu',

	// I
	'icone_article_syndic' => 'Article syndiqué', # NEW
	'icone_articles_syndic' => 'Articles syndiqués', # NEW
	'icone_controler_syndication' => 'Publication des articles syndiqués', # NEW
	'icone_modifier_site' => 'Změnit web',
	'icone_referencer_nouveau_site' => 'Zveřejnit odkaz na nový web',
	'icone_site_reference' => 'Sites référencés', # NEW
	'icone_supprimer_article' => 'Supprimer cet article', # NEW
	'icone_supprimer_articles' => 'Supprimer ces articles', # NEW
	'icone_valider_article' => 'Valider cet article', # NEW
	'icone_valider_articles' => 'Valider ces articles', # NEW
	'icone_voir_sites_references' => 'Zobrazit odkazovaný web',
	'info_1_site_importe' => '1 site a été importé', # NEW
	'info_a_valider' => '[ke schválení]',
	'info_aucun_site_importe' => 'Aucun site n\'a pu être importé', # NEW
	'info_bloquer' => 'zablokovat',
	'info_bloquer_lien' => 'zablokovat tento odkaz',
	'info_derniere_syndication' => 'Poslední syndikace tohoto webu byla pro vedena ',
	'info_liens_syndiques_1' => 'syndikovaný odkaz',
	'info_liens_syndiques_2' => 'čekající na schválení.',
	'info_nb_sites_importes' => '@nb@ sites ont été importés', # NEW
	'info_nom_site_2' => '<b>Název webu</b> [povinný údaj]',
	'info_panne_site_syndique' => 'Syndikovaný web nefunguje',
	'info_probleme_grave' => 'chyba',
	'info_question_proposer_site' => 'Kdo může navrhovat odkazy na weby?',
	'info_retablir_lien' => 'obnovit tento odkaz',
	'info_site_attente' => 'Web čeká na schválení',
	'info_site_propose' => 'Web navržen dne:',
	'info_site_reference' => 'Web odkazovaný online',
	'info_site_refuse' => 'Web byl odmítnut',
	'info_site_syndique' => 'Toto je syndikovaný web...', # MODIF
	'info_site_valider' => 'Weby ke schválení',
	'info_sites_referencer' => 'Zadat odkaz na web',
	'info_sites_refuses' => 'Odmítnuté weby',
	'info_statut_site_1' => 'Tento web je:',
	'info_statut_site_2' => 'Publikováno',
	'info_statut_site_3' => 'Připraveno',
	'info_statut_site_4' => 'Do koše', # MODIF
	'info_syndication' => 'syndikace:',
	'info_syndication_articles' => 'článek/článků',
	'item_bloquer_liens_syndiques' => 'Zablokovat syndikované odkazy pro schválení',
	'item_gerer_annuaire_site_web' => 'Správa adresáře webů',
	'item_non_bloquer_liens_syndiques' => 'Neblokovat odkazy, které jsou výsledkem syndikace',
	'item_non_gerer_annuaire_site_web' => 'Vypnout adresář webu',
	'item_non_utiliser_syndication' => 'Nepoužívat automatickou syndikaci',
	'item_utiliser_syndication' => 'Používat automatickou syndikaci',

	// L
	'label_exporter_avec_mots_cles_1' => 'Exporter les mots-clés sous forme de tags', # NEW
	'label_exporter_id_parent' => 'Exporter les sites de la rubrique', # NEW
	'label_exporter_publie_seulement_1' => 'Exporter uniquement les sites publiés', # NEW
	'label_fichier_import' => 'Fichier HTML', # NEW
	'label_importer_les_tags_1' => 'Importer les tags sous forme de mot-clé', # NEW
	'label_importer_statut_publie_1' => 'Publier automatiquement les sites', # NEW
	'lien_mise_a_jour_syndication' => 'Aktualizovat',
	'lien_nouvelle_recuperation' => 'Pokusit se znovu získat data',
	'lien_purger_syndication' => 'Effacer tous les articles syndiqués', # NEW

	// N
	'nombre_articles_syndic' => '@nb@ articles syndiqués', # NEW

	// S
	'statut_off' => 'Supprimé', # NEW
	'statut_prop' => 'En attente', # NEW
	'statut_publie' => 'Publié', # NEW
	'syndic_choix_moderation' => 'Co se má udělat s budoucími odkazy z tohoto webu?',
	'syndic_choix_oublier' => 'Co s odkazy, které už nejsou v syndikačním souboru?',
	'syndic_choix_resume' => 'Některé weby publikují celé texty článků. Je-li tato funkce k dispozici, chcete syndikovat:',
	'syndic_lien_obsolete' => 'zastaralý odkaz',
	'syndic_option_miroir' => 'automaticky blokovat',
	'syndic_option_oubli' => 'odstranit (po @mois@ měsíci/měsících)',
	'syndic_option_resume_non' => 'celý obsah článků (ve formátu HTML)',
	'syndic_option_resume_oui' => 'stručný obsah (v textovém formátu)',
	'syndic_options' => 'Možnosti syndikace:',

	// T
	'texte_expliquer_export_bookmarks' => 'Vous pouvez exporter une liste de sites au format Marque-page HTML,
	pour vous permettre ensuite de l\'importer dans votre navigateur ou dans un service en ligne', # NEW
	'texte_expliquer_import_bookmarks' => 'Vous pouvez importer une liste de sites au format Marque-page HTML,
	en provenance de votre navigateur ou d\'un service en ligne de gestion des Marques-pages.', # NEW
	'texte_liens_sites_syndiques' => 'Odkazy ze syndikovaných webů lze předem zablokovat.
   Níže uvedené nastavení je standardním
   nastavením syndikovaných webů po jejich vytvoření.
   Jednotlivé odkazy můžete vždy následně odblokovat,
   případně se rozhodnout zablokovat odkazy pocházející z konkrétních webů.', # MODIF
	'texte_messages_publics' => 'Veřejné zprávy k článku:',
	'texte_non_fonction_referencement' => 'Tuto automatickou funkci nemusíte použít a parametry webu můžete zadat sami...', # MODIF
	'texte_referencement_automatique' => '<b>Automatický odkaz na web</b><br />Odkaz na web snadno vytvoříte zadáním požadované adresy URL nebo adresy jeho syndikačního souboru. Systém SPIP automaticky převezme údaje o takovém webu (název, popis...).', # MODIF
	'texte_referencement_automatique_verifier' => 'Please, verify the information provided by <tt>@url@</tt> before saving.', # NEW
	'texte_syndication' => 'Pokud to web umožňuje, můžete automaticky získat seznam na něm zveřejněných
  novinek. K tomu je nutno zapnout syndikaci.
  <blockquote><i>Někteří poskytovatelé webového prostoru tuto funkcni vypínají.
  V takovém případě nemůžete syndikaci ze svého webu použít.</i></blockquote>', # MODIF
	'titre_articles_syndiques' => 'Syndikované články, přenesené z tohoto webu',
	'titre_dernier_article_syndique' => 'Poslední syndikované články',
	'titre_exporter_bookmarks' => 'Exporter des Marques-pages', # NEW
	'titre_importer_bookmarks' => 'Importer des Marques-pages', # NEW
	'titre_importer_exporter_bookmarks' => 'Importer et Exporter des Marques-pages', # NEW
	'titre_page_sites_tous' => 'Odkazované weby',
	'titre_referencement_sites' => 'Odkazy na weby a syndikace',
	'titre_site_numero' => 'ČÍSLO WEBU:',
	'titre_sites_proposes' => 'Navržené weby',
	'titre_sites_references_rubrique' => 'Weby, na něž jsou v této sekci odkazy',
	'titre_sites_syndiques' => 'Syndikované weby',
	'titre_sites_tous' => 'Odkazované weby',
	'titre_syndication' => 'Syndikace webů',
	'tout_voir' => 'Voir tous les articles syndiqués', # NEW

	// U
	'un_article_syndic' => '1 article syndiqué' # NEW
);

?>
