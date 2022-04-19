<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion du formulaire de d'édition d'article
 *
 * @package SPIP\Core\Articles\Formulaires
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/actions');
include_spip('inc/editer');

/**
 * Chargement du formulaire d'édition d'article
 *
 * @uses formulaires_editer_objet_charger()
 *
 * @param int|string $id_article
 *     Identifiant de l'article. 'new' pour une nouvel article.
 * @param int $id_rubrique
 *     Identifiant de la rubrique parente
 * @param string $retour
 *     URL de redirection après le traitement
 * @param int $lier_trad
 *     Identifiant éventuel d'un article source de traduction
 * @param string $config_fonc
 *     Nom de la fonction ajoutant des configurations particulières au formulaire
 * @param array $row
 *     Valeurs de la ligne SQL de l'article, si connu
 * @param string $hidden
 *     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
 * @return array
 *     Environnement du formulaire
 **/
function formulaires_editer_article_charger_dist(
	$id_article = 'new',
	$id_rubrique = 0,
	$retour = '',
	$lier_trad = 0,
	$config_fonc = 'articles_edit_config',
	$row = [],
	$hidden = ''
) {
	$valeurs = formulaires_editer_objet_charger(
		'article',
		$id_article,
		$id_rubrique,
		$lier_trad,
		$retour,
		$config_fonc,
		$row,
		$hidden
	);

	if (intval($id_article) and !autoriser('modifier', 'article', intval($id_article))) {
		$valeurs['editable'] = '';
	}

	// il faut enlever l'id_rubrique car la saisie se fait sur id_parent
	// et id_rubrique peut etre passe dans l'url comme rubrique parent initiale
	// et sera perdue si elle est supposee saisie
	return $valeurs;
}

/**
 * Identifier le formulaire en faisant abstraction des paramètres qui
 * ne représentent pas l'objet édité
 *
 * @param int|string $id_article
 *     Identifiant de l'article. 'new' pour une nouvel article.
 * @param int $id_rubrique
 *     Identifiant de la rubrique parente
 * @param string $retour
 *     URL de redirection après le traitement
 * @param int $lier_trad
 *     Identifiant éventuel d'un article source de traduction
 * @param string $config_fonc
 *     Nom de la fonction ajoutant des configurations particulières au formulaire
 * @param array $row
 *     Valeurs de la ligne SQL de l'article, si connu
 * @param string $hidden
 *     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
 * @return string
 *     Hash du formulaire
 */
function formulaires_editer_article_identifier_dist(
	$id_article = 'new',
	$id_rubrique = 0,
	$retour = '',
	$lier_trad = 0,
	$config_fonc = 'articles_edit_config',
	$row = [],
	$hidden = ''
) {
	return serialize([intval($id_article), $lier_trad]);
}

/**
 * Choix par défaut des options de présentation
 *
 * @param array $row
 *     Valeurs de la ligne SQL d'un article, si connu
 * return array
 *     Configuration pour le formulaire
 */
function articles_edit_config(array $row): array {

	$config = [];
	$config['lignes'] = 8;
	$config['langue'] = $GLOBALS['spip_lang'];
	$config['restreint'] = ($row['statut'] === 'publie');

	return $config;
}

/**
 * Vérifications du formulaire d'édition d'article
 *
 * @uses formulaires_editer_objet_verifier()
 *
 * @param int|string $id_article
 *     Identifiant de l'article. 'new' pour une nouvel article.
 * @param int $id_rubrique
 *     Identifiant de la rubrique parente
 * @param string $retour
 *     URL de redirection après le traitement
 * @param int $lier_trad
 *     Identifiant éventuel d'un article source de traduction
 * @param string $config_fonc
 *     Nom de la fonction ajoutant des configurations particulières au formulaire
 * @param array $row
 *     Valeurs de la ligne SQL de l'article, si connu
 * @param string $hidden
 *     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
 * @return array
 *     Erreurs du formulaire
 **/
function formulaires_editer_article_verifier_dist(
	$id_article = 'new',
	$id_rubrique = 0,
	$retour = '',
	$lier_trad = 0,
	$config_fonc = 'articles_edit_config',
	$row = [],
	$hidden = ''
) {
	// auto-renseigner le titre si il n'existe pas
	titre_automatique('titre', ['descriptif', 'chapo', 'texte']);
	if (!_request('id_parent') and !intval($id_article)) {
		$valeurs = formulaires_editer_objet_charger(
			'article',
			$id_article,
			$id_rubrique,
			$lier_trad,
			$retour,
			$config_fonc,
			$row,
			$hidden
		);
		set_request('id_parent', $valeurs['id_parent']);
	}
	// on ne demande pas le titre obligatoire : il sera rempli a la volee dans editer_article si vide
	$erreurs = formulaires_editer_objet_verifier('article', $id_article, ['id_parent']);
	// si on utilise le formulaire dans le public
	if (!function_exists('autoriser')) {
		include_spip('inc/autoriser');
	}
	if (
		!isset($erreurs['id_parent'])
		and !autoriser('creerarticledans', 'rubrique', _request('id_parent'))
	) {
		$erreurs['id_parent'] = _T('info_creerdansrubrique_non_autorise');
	}

	return $erreurs;
}

/**
 * Traitements du formulaire d'édition d'article
 *
 * @uses formulaires_editer_objet_traiter()
 *
 * @param int|string $id_article
 *     Identifiant de l'article. 'new' pour une nouvel article.
 * @param int $id_rubrique
 *     Identifiant de la rubrique parente
 * @param string $retour
 *     URL de redirection après le traitement
 * @param int $lier_trad
 *     Identifiant éventuel d'un article source de traduction
 * @param string $config_fonc
 *     Nom de la fonction ajoutant des configurations particulières au formulaire
 * @param array $row
 *     Valeurs de la ligne SQL de l'article, si connu
 * @param string $hidden
 *     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
 * @return array
 *     Retours des traitements
 **/
function formulaires_editer_article_traiter_dist(
	$id_article = 'new',
	$id_rubrique = 0,
	$retour = '',
	$lier_trad = 0,
	$config_fonc = 'articles_edit_config',
	$row = [],
	$hidden = ''
) {
	// ici on ignore changer_lang qui est poste en cas de trad,
	// car l'heuristique du choix de la langue est pris en charge par article_inserer
	// en fonction de la config du site et de la rubrique choisie
	if (!$lier_trad) {
		set_request('changer_lang');
	}

	return formulaires_editer_objet_traiter(
		'article',
		$id_article,
		$id_rubrique,
		$lier_trad,
		$retour,
		$config_fonc,
		$row,
		$hidden
	);
}
