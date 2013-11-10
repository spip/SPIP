<?php

if (!defined('_ECRIRE_INC_VERSION')) return;


function compagnon_compagnon_messages($flux) {

	$exec     = $flux['args']['exec'];
	$pipeline = $flux['args']['pipeline'];
	$vus      = $flux['args']['deja_vus'];
	$aides    = &$flux['data'];

	switch ($pipeline) {
		
		case 'affiche_milieu':
			switch ($exec) {

				
				case 'accueil':
					$aides[] = array(
						'id' => 'accueil',
						'inclure' => 'compagnon/accueil',
						'statuts'=> array('1comite', '0minirezo', 'webmestre')
					);
					$aides[] = array(
						'id' => 'accueil_configurer',
						'titre' => _T('compagnon:c_accueil_configurer_site'),
						'texte' => _T('compagnon:c_accueil_configurer_site_texte', array('nom'=>$GLOBALS['meta']['nom_site'])),
						'statuts'=> array('webmestre'),
						'target' => '#bando_identite .nom_site_spip .nom',
					);
					$aides[] = array(
						'id' => 'accueil_publication',
						'titre' => _T('compagnon:c_accueil_publication'),
						'texte' => _T('compagnon:c_accueil_publication_texte'),
						'statuts'=> array('webmestre'),
						'target'=> '#bando1_menu_edition',
					);
					break;


				case 'rubriques':
					// eviter si possible une requete sql.
					if (!isset($vus['rubriques']) and !Sql::countsel('spip_rubriques')) {
						$aides[] = array(
							'id' => 'rubriques',
							'titre' => _T('compagnon:c_rubriques_creer'),
							'texte' => _T('compagnon:c_rubriques_creer_texte'),
							'statuts'=> array('webmestre'),
							'target'=> '#contenu .icone:first-of-type',
						);
					}
					break;


				case 'rubrique':
					// eviter si possible une requete sql.
					if (!isset($vus['rubrique'])) {
						$statut = Sql::getfetsel('statut', 'spip_rubriques', 'id_rubrique='.$flux['args']['id_rubrique']);
						if ($statut != 'publie') {
							$aides[] = array(
								'id' => 'rubrique',
								'titre' => _T('compagnon:c_rubrique_publier'),
								'texte' => _T('compagnon:c_rubrique_publier_texte'),
								'statuts'=> array('webmestre'),
								'target'=> '#contenu .icone.article-new-24'
							);
						}
					}
					break;

				case 'articles':
					// eviter si possible une requete sql.
					if (!isset($vus['articles']) and !Sql::countsel('spip_rubriques')) {
						$aides[] = array(
							'id' => 'articles',
							'titre' => _T('compagnon:c_articles_creer'),
							'texte' => _T('compagnon:c_articles_creer_texte'),
							'statuts'=> array('webmestre')
						);
					}
					break;

				case 'article':
					$aides[] = array(
						'id' => 'article_redaction',
						'inclure' => 'compagnon/article_redaction',
						'statuts'=> array('0minirezo', 'webmestre')
					);	
					$aides[] = array(
						'id' => 'article_redaction_redacteur',
						'inclure' => 'compagnon/article_redaction_redacteur',
						'statuts'=> array('1comite')
					);
					break;
			}
			break;

		case 'affiche_gauche':
			switch ($exec) {
				case 'job_queue':
					$aides[] = array(
						'id' => 'job_queue',
						'titre' => _T('compagnon:c_job'),
						'texte' => _T('compagnon:c_job_texte'),
						'statuts'=> array('webmestre')
					);
					break;
			}
			break;
	}

	
	return $flux;
}

?>
