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
		if (count($logo) < 6) {
			spip_log("Supprimer ancien logo $logo", 'logo');
			spip_unlink($logo[0]);
		}
		elseif (
			$doc = $logo[5]
			and isset($doc['id_document'])
			and $id_document = $doc['id_document']
		) {
			include_spip('action/editer_liens');
			// supprimer le lien dans la base
			objet_dissocier(['document' => $id_document], [$objet => $id_objet], ['role' => '*']);

			// verifier si il reste des liens avec d'autres objets et sinon supprimer
			$liens = objet_trouver_liens(['document' => $id_document], '*');
			if (!count($liens)) {
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
		$erreur = 'etat invalide';

		return $erreur;
	}
	// chercher dans la base
	$mode_document = 'logo' . $mode;

	include_spip('inc/documents');
	$erreur = '';

	if (!$source) {
		spip_log('spip_image_ajouter : source inconnue', 'logo');
		$erreur = 'source inconnue';

		return $erreur;
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
			$erreur = 'source inconnue';

			return $erreur;
		}
		$source = [
			'tmp_name' => $tmp_name,
			'name' => basename($tmp_name),
		];
	} elseif ($erreur = check_upload_error($source['error'], '', true)) {
		return $erreur;
	}

	// supprimer le logo eventueel existant
	// TODO : si un logo existe, le modifier plutot que supprimer + reinserer (mais il faut gerer le cas ou il est utilise par plusieurs objets, donc pas si simple)
	// mais de toute facon l'interface actuelle oblige a supprimer + reinserer
	logo_supprimer($objet, $id_objet, $etat);


	include_spip('inc/autoriser');
	$source['mode'] = $mode_document;
	$ajouter_documents = charger_fonction('ajouter_documents', 'action');
	autoriser_exception('associerdocuments', $objet, $id_objet);
	$ajoutes = $ajouter_documents('new', [$source], $objet, $id_objet, $mode_document);
	autoriser_exception('associerdocuments', $objet, $id_objet, false);

	$id_document = reset($ajoutes);

	if (!is_numeric($id_document)) {
		$erreur = ($id_document ? $id_document : 'Erreur inconnue');
		spip_log("Erreur ajout logo : $erreur pour source=" . json_encode($source), 'logo');
		return $erreur;
	}

	return ''; // tout est bon, pas d'erreur
}

function logo_migrer_en_base($objet, $time_limit) {

	$dir_logos_erreurs = sous_repertoire(_DIR_IMG, 'logo_erreurs');
	$dir_logos = sous_repertoire(_DIR_IMG, 'logo');
	$formats_logos = ['jpg', 'png', 'svg', 'gif'];
	if (isset($GLOBALS['formats_logos'])) {
		$formats_logos = $GLOBALS['formats_logos'];
	}


	$chercher_logo = charger_fonction('chercher_logo', 'inc');
	include_spip('inc/chercher_logo');
	$_id_objet = id_table_objet($objet);
	$type = type_du_logo($_id_objet);

	foreach (['on', 'off'] as $mode) {
		$nom_base = $type . $mode;
		$dir = (defined('_DIR_LOGOS') ? _DIR_LOGOS : _DIR_IMG);

		$deja = [];
		$files = glob($dir . $nom_base . '*');

		foreach ($files as $file) {
			$logo = substr($file, strlen($dir . $nom_base));
			$logo = explode('.', $logo);
			if (
				is_numeric($logo[0])
				and ($id_objet = intval($logo[0]) or in_array($objet, ['site', 'rubrique']))
			) {
				if (!isset($deja[$id_objet])) {
					$logo = $chercher_logo($id_objet, $_id_objet, $mode);
					// if no logo in base
					if (!$logo or count($logo) < 6) {
						foreach ($formats_logos as $format) {
							if (@file_exists($d = ($dir . ($nom = $nom_base . intval($id_objet) . '.' . $format)))) {
								// logo_modifier commence par supprimer le logo existant, donc on le deplace pour pas le perdre
								@rename($d, $dir_logos . $nom);
								// et on le declare comme nouveau logo
								logo_modifier($objet, $id_objet, $mode, $dir_logos . $nom);
								break;
							}
						}
					}
					$deja[$id_objet] = true;
				}
			}
			// si le fichier est encore la on le move : rien a faire ici
			if (file_exists($file)) {
				@rename($file, $dir_logos_erreurs . basename($file));
			}

			if ($time_limit and time() > $time_limit) {
				effacer_meta('drapeau_edition');
				return;
			}
		}
	}
	effacer_meta('drapeau_edition');
}
