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
 * Gestion de l'API de modification/suppression des logos
 *
 * @package SPIP\Core\Logo\Edition
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Supprimer le logo d'un objet
 *
 * @param string $objet
 * @param int $id_objet
 * @param string $etat
 *     `on` ou `off`
 */
function logo_supprimer($objet, $id_objet, $etat) {
	$chercher_logo = charger_fonction('chercher_logo', 'inc');
	$objet = objet_type($objet);
	$primary = id_table_objet($objet);
	include_spip('inc/chercher_logo');

	// existe-t-il deja un logo ?
	$logo = $chercher_logo($id_objet, $primary, $etat);
	if ($logo) {
		# TODO : deprecated, a supprimer -> anciens logos IMG/artonxx.png pas en base
		if ((is_countable($logo) ? count($logo) : 0) < 6) {
			spip_log('Supprimer ancien logo ' . json_encode($logo, JSON_THROW_ON_ERROR), 'logo');
			spip_unlink($logo[0]);
		}
		elseif (
			($doc = $logo[5])
			&& isset($doc['id_document'])
			&& ($id_document = $doc['id_document'])
		) {
			include_spip('action/editer_liens');
			// supprimer le lien dans la base
			objet_dissocier(['document' => $id_document], [$objet => $id_objet], ['role' => '*']);

			// verifier si il reste des liens avec d'autres objets et sinon supprimer
			$liens = objet_trouver_liens(['document' => $id_document], '*');
			if ($liens === []) {
				$supprimer_document = charger_fonction('supprimer_document', 'action');
				$supprimer_document($doc['id_document']);
			}
		}
	}
}

/**
 * Modifier le logo d'un objet
 *
 * @param string $objet
 * @param int $id_objet
 * @param string $etat
 *     `on` ou `off`
 * @param string|array $source
 *     - array : sous tableau de `$_FILE` issu de l'upload
 *     - string : fichier source (chemin complet ou chemin relatif a `tmp/upload`)
 * @return string
 *     Erreur, sinon ''
 */
function logo_modifier($objet, $id_objet, $etat, $source) {
	$chercher_logo = charger_fonction('chercher_logo', 'inc');
	$objet = objet_type($objet);
	$primary = id_table_objet($objet);
	include_spip('inc/chercher_logo');

	$mode = preg_replace(',\W,', '', $etat);
	if (!$mode) {
		spip_log("logo_modifier : etat $etat invalide", 'logo');

		return 'etat invalide';
	}
	// chercher dans la base
	$mode_document = 'logo' . $mode;

	include_spip('inc/documents');
	$erreur = '';

	if (!$source) {
		spip_log('spip_image_ajouter : source inconnue', 'logo');

		return 'source inconnue';
	}

	// fichier dans upload/
	if (is_string($source)) {
		$tmp_name = false;
		if (file_exists($source)) {
			$tmp_name = $source;
		} elseif (file_exists($f = determine_upload() . $source)) {
			$tmp_name = $f;
		}
		if (!$tmp_name) {
			spip_log('spip_image_ajouter : source inconnue', 'logo');

			return 'source inconnue';
		}
		$source = [
			'tmp_name' => $tmp_name,
			'name' => basename($tmp_name),
		];
	} elseif ($erreur = check_upload_error($source['error'], '', true)) {
		return $erreur;
	}

	// supprimer le logo éventuel existant
	// TODO : si un logo existe, le modifier plutot que supprimer + reinserer
	// (mais il faut gerer le cas ou il est utilise par plusieurs objets, donc pas si simple)
	// mais de toute facon l'interface actuelle oblige a supprimer + reinserer
	// @see medias_upgrade_logo_objet()
	if (empty($GLOBALS['logo_migrer_en_base'])) {
		logo_supprimer($objet, $id_objet, $etat);
	}


	include_spip('inc/autoriser');
	$source['mode'] = $mode_document;
	$ajouter_documents = charger_fonction('ajouter_documents', 'action');
	autoriser_exception('associerdocuments', $objet, $id_objet);
	$ajoutes = $ajouter_documents('new', [$source], $objet, $id_objet, $mode_document);
	autoriser_exception('associerdocuments', $objet, $id_objet, false);

	$id_document = reset($ajoutes);

	if (!is_numeric($id_document)) {
		$erreur = ($id_document ?: 'Erreur inconnue');
		spip_log("Erreur ajout logo : $erreur pour source=" . json_encode($source, JSON_THROW_ON_ERROR), 'logo');
		return $erreur;
	}

	return ''; // tout est bon, pas d'erreur
}

/**
 * Migration des logos en documents.
 *
 * - avant dans IMG/artonXX.png
 * - après dans IMG/logo/... + enregistrés en document dans spip_documents
 *
 * Cette migration est effectuée à partir de SPIP 4.0
 * et la fonction doit être appelée pour chaque plugin qui aurait utilisé des logos
 * sur des objets éditoriaux.
 *
 * @since 4.0
 * @deprecated 5.0 Migrer le site & les logos / tables dans un SPIP 4.x ou 5.x
 * @param string $objet Type d’objet spip, tel que 'article'
 * @param int $time_limit
 */
function logo_migrer_en_base($objet, $time_limit) {

	$dir_logos_erreurs = sous_repertoire(_DIR_IMG, 'logo_erreurs');
	$dir_logos = sous_repertoire(_DIR_IMG, 'logo');
	$formats_logos = ['jpg', 'png', 'svg', 'gif'];
	if (isset($GLOBALS['formats_logos'])) {
		$formats_logos = $GLOBALS['formats_logos'];
	}


	$trouver_table = charger_fonction('trouver_table', 'base');
	$chercher_logo = charger_fonction('chercher_logo', 'inc');
	include_spip('inc/chercher_logo');
	$_id_objet = id_table_objet($objet);
	$table = table_objet_sql($objet);
	$type = type_du_logo($_id_objet);
	$desc = $trouver_table($table);

	// on desactive les revisions
	$liste_objets_versionnes = $GLOBALS['meta']['objets_versions'] ?? '';
	unset($GLOBALS['meta']['objets_versions']);
	// et le signalement des editions
	$articles_modif = $GLOBALS['meta']['articles_modif'] ?? '';
	$GLOBALS['meta']['articles_modif'] = 'non';

	foreach (['on', 'off'] as $mode) {
		$nom_base = $type . $mode;
		$dir = (defined('_DIR_LOGOS') ? _DIR_LOGOS : _DIR_IMG);

		$files = glob($dir . $nom_base . '*');
		// est-ce que c'est une nouvelle tentative de migration ?
		// dans ce cas les logos sont deja dans IMG/logo/
		if (!(is_countable($files) ? count($files) : 0)) {
			$files = glob($dir_logos . $nom_base . '*');
			if (is_countable($files) ? count($files) : 0) {
				// mais il faut verifier si ils ont pas deja ete migres pour tout ou partie
				$filescheck = [];
				foreach ($files as $file) {
					$short = basename(dirname((string) $file)) . DIRECTORY_SEPARATOR . basename((string) $file);
					$filescheck[$short] = $file;
				}
				// trouver ceux deja migres
				$deja = sql_allfetsel('fichier', 'spip_documents', sql_in('fichier', array_keys($filescheck)) . " AND mode LIKE 'logo%'");
				if (is_countable($deja) ? count($deja) : 0) {
					$deja = array_column($deja, 'fichier');
					$restant = array_diff(array_keys($filescheck), $deja);
					$files = [];
					if ($restant !== []) {
						foreach ($restant as $r) {
							$files[] = $filescheck[$r];
						}
					}
				}
				// et si il en reste on peut y aller...
				// mais il faut modifier $dir qui sert de base dans la suite
				if (is_countable($files) ? count($files) : 0) {
					$dir = $dir_logos;
				}
			}
		}

		$count = (is_countable($files) ? count($files) : 0);
		spip_log("logo_migrer_en_base $objet $mode : " . $count . ' logos restant', 'maj' . _LOG_INFO_IMPORTANTE);

		$deja = [];
		foreach ($files as $file) {
			$logo = substr((string) $file, strlen($dir . $nom_base));
			$logo = explode('.', $logo);
			if (
				is_numeric($logo[0])
				&& (($id_objet = (int) $logo[0]) || in_array($objet, ['site', 'rubrique']))
				&& !isset($deja[$id_objet])
			) {
				$logo = $chercher_logo($id_objet, $_id_objet, $mode);
				// if no logo in base
				if (!$logo || (is_countable($logo) ? count($logo) : 0) < 6) {
					foreach ($formats_logos as $format) {
						if (@file_exists($d = ($dir . ($nom = $nom_base . (int) $id_objet . '.' . $format)))) {
							if (isset($desc['field']['date_modif'])) {
								$date_modif = sql_getfetsel('date_modif', $table, "$_id_objet=$id_objet");
							} else {
								$date_modif = null;
							}
							// s'assurer que le logo a les bon droits au passage (evite un echec en cas de sanitization d'un svg)
							@chmod($d, _SPIP_CHMOD & 0666);
							// logo_modifier commence par supprimer le logo existant, donc on le deplace pour pas le perdre
							@rename($d, $dir_logos . $nom);
							// et on le declare comme nouveau logo
							logo_modifier($objet, $id_objet, $mode, $dir_logos . $nom);
							if ($date_modif) {
								sql_updateq($table, ['date_modif' => $date_modif], "$_id_objet=$id_objet");
							}
							break;
						}
					}
				}
				$deja[$id_objet] = true;
			}
			// si le fichier est encore la on le move : rien a faire ici
			// (sauf si c'est une re-migration : il est deja dans logo/ donc il bouge pas)
			if ($dir !== $dir_logos && file_exists($file)) {
				@rename($file, $dir_logos_erreurs . basename((string) $file));
			}

			$count--;
			if ($count % 250 === 0) {
				spip_log("logo_migrer_en_base $objet $mode : " . $count . ' logos restant', 'maj' . _LOG_INFO_IMPORTANTE);
			}

			if ($time_limit && time() > $time_limit) {
				effacer_meta('drapeau_edition');
				return;
			}
		}
	}

	if ($liste_objets_versionnes) {
		$GLOBALS['meta']['objets_versions'] = $liste_objets_versionnes;
	}
	$GLOBALS['meta']['articles_modif'] = $articles_modif;

	effacer_meta('drapeau_edition');
}


/**
 * Retourne le type de logo tel que `art` depuis le nom de clé primaire
 * de l'objet
 *
 * C'est par défaut le type d'objet, mais il existe des exceptions historiques
 * déclarées par la globale `$table_logos`
 *
 * @see logo_migrer_en_base()
 * @see medias_upgrade_logo_objet()
 *
 * @param string $_id_objet
 *     Nom de la clé primaire de l'objet
 * @return string
 *     Type du logo
 * @deprecated 4.0 MAIS NE PAS SUPPRIMER CAR SERT POUR L'UPGRADE des logos et leur mise en base
 **/
function type_du_logo($_id_objet) {
	$legacy_tables_logos = [
		'id_article' => 'art',
		'id_auteur' => 'aut',
		'id_rubrique' => 'rub',
		'id_groupe' => 'groupe',
	];
	return $legacy_tables_logos[$_id_objet] ?? objet_type(preg_replace(',^id_,', '', $_id_objet));
}
