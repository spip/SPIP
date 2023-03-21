<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Gestion du formulaire d'édition de logo
 *
 * Ce formulaire ajoute, modifie ou supprime des logos sur les objets de SPIP.
 *
 * - En dehors d'une boucle, ce formulaire modifie le logo du site.
 * - Dans une boucle, il modifie le logo de la table selectionnée.
 *
 * Pensez juste que l'appel de `#LOGO_{TYPE}` s'appuie sur le nom de la clé primaire et non sur le
 * nom de l'objet réel. Par exemple on ecrira `#LOGO_GROUPE` (et non `#LOGO_GROUPEMOTS`) pour afficher
 * un logo issu du formulaire mis dans une boucle `GROUPES_MOTS`
 *
 * - il est possible de lui passer les paramètres objet et id : `#FORMULAIRE_EDITER_LOGO{article,1}`
 * - il est possible de spécifier une URL de redirection apres traitement :
 *   `#FORMULAIRE_EDITER_LOGO{article,1,#URL_ARTICLE}`
 *
 * @package SPIP\Core\Formulaires
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// utilise pour le logo du site, donc doit rester ici
$GLOBALS['logo_libelles']['site'] = _T('logo_site');
$GLOBALS['logo_libelles']['racine'] = _T('logo_standard_rubrique');


/**
 * Chargement du formulaire d'édition de logo
 *
 * @param string $objet Objet SPIP auquel sera lie le document (ex. article)
 * @param int $id_objet Identifiant de l'objet
 * @param string $retour Url de redirection apres traitement
 * @param array $options Tableau d'option (exemple : image_reduire => 50)
 * @return array|false Variables d'environnement pour le fond
 */
function formulaires_editer_logo_charger_dist($objet, $id_objet, $retour = '', $options = []) {
	// pas dans une boucle ? formulaire pour le logo du site
	// dans ce cas, il faut chercher un 'siteon0.ext'
	if (!$objet) {
		$objet = 'site';
	}

	$objet = objet_type($objet);
	$_id_objet = id_table_objet($objet);

	if (!is_array($options)) {
		$options = unserialize($options);
	}
	$options = spip_sanitize_from_request($options, '*');

	if (!isset($options['titre'])) {
		$balise_img = chercher_filtre('balise_img');
		$img = $balise_img(chemin_image('image-24.png'), '', 'cadre-icone');
		$libelles = pipeline('libeller_logo', $GLOBALS['logo_libelles']);
		$libelle = (($id_objet || $objet != 'rubrique') ? $objet : 'racine');
		if (isset($libelles[$libelle])) {
			$libelle = $libelles[$libelle];
		} elseif ($libelle = objet_info($objet, 'texte_logo_objet')) {
			$libelle = _T($libelle);
		} else {
			$libelle = _L('Logo');
		}
		switch ($objet) {
			case 'article':
				$libelle .= ' ' . aider('logoart');
				break;
			case 'breve':
				$libelle .= ' ' . aider('breveslogo');
				break;
			case 'rubrique':
				$libelle .= ' ' . aider('rublogo');
				break;
			default:
				break;
		}

		$options['titre'] = $img . $libelle;
	}
	if (!isset($options['editable'])) {
		include_spip('inc/autoriser');
		$options['editable'] = autoriser('iconifier', $objet, $id_objet);
	}

	$res = [
		'editable' => ($GLOBALS['meta']['activer_logos'] == 'oui' ? ' ' : '') && (!isset($options['editable']) || $options['editable']),
		'logo_survol' => ($GLOBALS['meta']['activer_logos_survol'] == 'oui' ? ' ' : ''),
		'objet' => $objet,
		'id_objet' => $id_objet,
		'_options' => $options,
		'_show_upload_off' => '',
	];

	// rechercher le logo de l'objet
	// la fonction prend un parametre '_id_objet' etrange :
	// le nom de la cle primaire (et non le nom de la table)
	// ou directement le nom du raccourcis a chercher
	$chercher_logo = charger_fonction('chercher_logo', 'inc');
	$etats = $res['logo_survol'] ? ['on', 'off'] : ['on'];
	foreach ($etats as $etat) {
		$logo = $chercher_logo($id_objet, $_id_objet, $etat);
		if ($logo) {
			$res['logo_' . $etat] = $logo[0];
			$res['logo_id_' . $etat] = $logo[5]['id_document'] ?? '';
		}
	}
	// pas de logo_on -> pas de formulaire pour le survol
	if (!isset($res['logo_on'])) {
		$res['logo_survol'] = '';
	} elseif (!isset($res['logo_off']) && _request('logo_up')) {
		$res['_show_upload_off'] = ' ';
	}

	// si le logo n'est pas editable et qu'il n'y en a pas, on affiche pas du tout le formulaire
	if (!$res['editable'] && !isset($res['logo_off']) && !isset($res['logo_on'])) {
		return false;
	}

	return $res;
}

/**
 * Identifier le formulaire en faisant abstraction des parametres qui
 * ne representent pas l'objet edite
 *
 * @param string $objet Objet SPIP auquel sera lie le document (ex. article)
 * @param int $id_objet Identifiant de l'objet
 * @param string $retour Url de redirection apres traitement
 * @param array $options Tableau d'option (exemple : image_reduire => 50)
 * @return string              Hash du formulaire
 */
function formulaires_editer_logo_identifier_dist($objet, $id_objet, $retour = '', $options = []) {
	return serialize([$objet, $id_objet]);
}

/**
 * Verification avant traitement du formulaire d'édition de logo
 *
 * On verifie que l'upload s'est bien passe et
 * que le document recu est une image (d'apres son extension)
 *
 * @param string $objet Objet SPIP auquel sera lie le document (ex. article)
 * @param int $id_objet Identifiant de l'objet
 * @param string $retour Url de redirection apres traitement
 * @param array $options Tableau d'option (exemple : image_reduire => 50)
 * @return array               Erreurs du formulaire
 */
function formulaires_editer_logo_verifier_dist($objet, $id_objet, $retour = '', $options = []) {
	$erreurs = [];
	// verifier les extensions
	$sources = formulaire_editer_logo_get_sources();
	include_spip('action/editer_logo');
	$extensions_possibles = $GLOBALS['formats_logos'];
	if (in_array('jpg', $extensions_possibles)) {
		$extensions_possibles[] = 'jpeg';
	}
	foreach ($sources as $etat => $file) {
		// seulement si une reception correcte a eu lieu
		if ($file && $file['error'] == 0) {
			if (
				!in_array(
					strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION)),
					$extensions_possibles
				)
			) {
				$erreurs['logo_' . $etat] = _L('Extension non reconnue');
			}
		} elseif ($file && $file['error'] != 0 && isset($file['msg'])) {
			$erreurs['message_erreur'] = $file['msg'];
		}
	}

	return $erreurs;
}

/**
 * Traitement de l'upload d'un logo
 *
 * Il est affecte au site si la balise n'est pas dans une boucle,
 * sinon a l'objet concerne par la boucle ou indiquee par les parametres d'appel
 *
 * @param string $objet Objet SPIP auquel sera lie le document (ex. article)
 * @param int $id_objet Identifiant de l'objet
 * @param string $retour Url de redirection apres traitement
 * @param array $options Tableau d'option (exemple : image_reduire => 50)
 * @return array               Retour des traitements
 */
function formulaires_editer_logo_traiter_dist($objet, $id_objet, $retour = '', $options = []) {
	$res = ['editable' => ' '];

	// pas dans une boucle ? formulaire pour le logo du site
	// dans ce cas, il faut chercher un 'siteon0.ext'
	if (!$objet) {
		$objet = 'site';
	}

	include_spip('action/editer_logo');

	// effectuer la suppression si demandee d'un logo
	$on = _request('supprimer_logo_on');
	if ($on || _request('supprimer_logo_off')) {
		logo_supprimer($objet, $id_objet, $on ? 'on' : 'off');
		$res['message_ok'] = ''; // pas besoin de message : la validation est visuelle
		set_request('logo_up', ' ');
	} // sinon supprimer ancien logo puis copier le nouveau
	else {
		$sources = formulaire_editer_logo_get_sources();
		foreach ($sources as $etat => $file) {
			if ($file && $file['error'] == 0) {
				if ($err = logo_modifier($objet, $id_objet, $etat, $file)) {
					$res['message_erreur'] = $err;
				} else {
					$res['message_ok'] = '';
				} // pas besoin de message : la validation est visuelle
				set_request('logo_up', ' ');
			}
		}
	}

	// Invalider les caches de l'objet
	include_spip('inc/invalideur');
	suivre_invalideur("id='$objet/$id_objet'");


	if ($retour) {
		$res['redirect'] = $retour;
	}

	return $res;
}


/**
 * Extraction des sources des fichiers uploadés correspondant aux 2 logos (normal + survol)
 * si leur upload s'est bien passé
 *
 * @return array
 *     Sources des fichiers dans les clés `on` ou `off`
 */
function formulaire_editer_logo_get_sources() {
	if (!$_FILES) {
		$_FILES = $GLOBALS['HTTP_POST_FILES'] ?? [];
	}
	if (!is_array($_FILES)) {
		return [];
	}

	include_spip('inc/documents');
	$sources = [];
	foreach (['on', 'off'] as $etat) {
		$logo = 'logo_' . $etat;
		if (isset($_FILES[$logo])) {
			if ($_FILES[$logo]['error'] == 0) {
				$sources[$etat] = $_FILES[$logo];
			} elseif ($_FILES[$logo]['error'] != 0) {
				$msg = check_upload_error($_FILES[$logo]['error'], false, true);
				if ($msg && is_string($msg)) {
					$sources[$etat] = $_FILES[$logo];
					$sources[$etat]['msg'] = $msg;
				}
			}
		}
	}

	return $sources;
}
