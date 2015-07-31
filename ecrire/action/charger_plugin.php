<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


/*
 * Ce fichier est extrait du plugin charge : action charger decompresser
 *
 * Auteur : bertrand@toggg.com
 * © 2007 - Distribue sous licence LGPL
 *
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@action_charger_plugin_dist
function action_charger_plugin_dist() {
	global $spip_lang_left;

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	include_spip('inc/minipres');
	include_spip('inc/charger_plugin');

	// droits : il faut avoir le droit de choisir les plugins,
	// mais aussi d'en ajouter -- a voir
	include_spip('inc/autoriser');
	if (!autoriser('configurer', 'plugins')
	OR !autoriser('webmestre')) {
		echo minipres();
		exit;
	}

	if ($arg == 'update_flux') {
		if (is_array($syndic_plug = @unserialize($GLOBALS['meta']['syndic_plug'])))
			foreach ($syndic_plug as $url => $c)
				essaie_ajouter_liste_plugins($url);
	} else if ($arg == 'supprimer_flux'
	AND $url = _request('supprimer_flux')) {
		$syndic_plug = @unserialize($GLOBALS['meta']['syndic_plug']);
		unset($syndic_plug[$url]);
		ecrire_meta('syndic_plug', serialize($syndic_plug));
		ecrire_metas();
	}

	if (!preg_match(',^(https?|ftp)://.*\.zip,',
		$zip = _request('url_zip_plugin'))
	AND !preg_match(',^(https?|ftp)://.*\.zip,',
		$zip = _request('url_zip_plugin2')))
	{
		essaie_ajouter_liste_plugins($zip);
		include_spip('inc/headers');
		redirige_par_entete(generer_url_ecrire('admin_plugin'));
	}

	## si ici on n'est pas en POST c'est qu'il y a un loup
	if (!$_POST) die('pas normal');

	# destination finale des fichiers
	switch($arg) {
		case 'lib':
			$dest = sous_repertoire(_DIR_PLUGINS_AUTO, 'lib');
			break;
		case 'auto':
		default:
			$dest = _DIR_PLUGINS_AUTO;
			break;
	}

	# si premiere lecture, destination temporaire des fichiers
	$tmp = _request('extract')
		? $dest
		: sous_repertoire(_DIR_CACHE,'chargeur');
	

	# dispose-t-on du fichier ?
	$status = null;
	$fichier = $tmp.basename($zip);
	if (!@file_exists($fichier)) {
		include_spip('inc/distant');
		$contenu = recuperer_page($zip, false, false,
			8000000 /* taille max */);
		if (!$contenu
		OR !ecrire_fichier($fichier, $contenu)) {
			spip_log('charger_decompresser impossible de charger '.$zip);
			$status = 0;
		}
	}

	if ($status === null) {
		$status = chargeur_charger_zip(
			array(
				'zip' => $zip,
				'dest' => $dest,
				'fichier' => $fichier,
				'tmp' => $tmp,
				'extract' => _request('extract')
			)
		);
		if (_request('extract')) {
			@unlink($fichier);
		}
	}

	// Vers quoi pointe le bouton "suite"
	$suite = '';

	// le fichier .zip est la et bien forme
	if (is_array($status)) {

		// C'est un plugin ?
		if (lire_fichier($xml=$status['tmpname'].'/plugin.xml', $pluginxml)) {

			include_spip('inc/xml');
			$arbre = spip_xml_load($xml);
			$retour = typo(spip_xml_aplatit($arbre['plugin'][0]['nom']));

			// l'icone ne peut pas etre dans tmp/ (lecture http oblige)
			// on la copie donc dans local/chargeur/
			if ($image = trim($arbre['plugin'][0]['icon'][0])) {
				$dir = sous_repertoire(_DIR_VAR,'chargeur');
				@copy($status['tmpname'].'/'.$image, $image2 = $dir.basename($image));
				$retour = "<img src='".$image2."' style='float:right;' />"
					. $retour;
			} else 
				$retour = "<img src='".find_in_path('images/plugin-24.gif')."' style='float:right;' />"
					. $retour;

			if (_request('extract')) {
				$texte = plugin_propre(
					spip_xml_aplatit($arbre['plugin'][0]['description']));
				$texte .= '<p>'._L('Le fichier '.$zip.' a &#233;t&#233; d&#233;compact&#233; et install&#233;').'</p>';
				$texte .= _L("<h2 style='text-align:center;'>Continuez pour l'activer.</h2>");
			} else {
				$texte = '<p>'._L('Le fichier '.$zip.' a &#233;t&#233; t&#233;l&#233;charg&#233;').'</p>';
				$texte .= liste_fichiers_pclzip($status);
				$texte .= _L("<h2 style='text-align:center;'>Vous pouvez maintenant l'installer.</h2>");
				$suite = 'auto';
			}
		}

		// C'est un paquet quelconque
		else {
			$retour = _L('Chargement du paquet') . ' '.basename($status['tmpname']);
			if (_request('extract')) {
				$texte = '<p>'._L('Le fichier '.$zip.' a &#233;t&#233; d&#233;compact&#233; et install&#233; dans le répertoire '.$dest).'</p>';
			} else {
				$texte = "<p>"._L("Le fichier ".$zip.' a &#233;t&#233; t&#233;l&#233;charg&#233;.')."</p>\n";
				$texte .= liste_fichiers_pclzip($status);
				$suite = 'lib';
			}
		}
	}

	// fichier la mais pas bien dezippe
	else if ($status < 0) {
		$retour = _T('erreur');
		$texte = _L("echec pclzip : erreur ").$status;
	}

	// fichier absent
	else if ($status == 0) {
		$retour = _T('erreur');
		$texte = _L('erreur : impossible de charger '.$zip);
	}

	include_spip('exec/install'); // pour bouton_suivant()

	$texte = "<div style='text-align:$spip_lang_left;'>$texte</div>\n";

	echo minipres($retour." ",
		$suite
			? redirige_action_auteur(_request('action'),
				$suite,
				'',
				'',
					form_hidden('?url_zip_plugin='.$zip.'&extract=oui')
					.$texte
					."<a class='suivant' href='"
						.generer_url_ecrire('admin_plugin')
					."'>Annuler</a>"
				.bouton_suivant(),
				"\nmethod='post'")
			: generer_form_ecrire('admin_plugin&plug='.
				preg_replace(',^[^/]+/|/$,', '', $status['dirname']),
				$texte . bouton_suivant())
	);
	exit;


	// 0 = rien, pas charge
	// liste de fichiers = retour gagnant
	// < 0 = erreur pclzip 
	// ----- Error codes
	//   -1 : Unable to open file in binary write mode
	//   -2 : Unable to open file in binary read mode
	//   -3 : Invalid parameters
	//   -4 : File does not exist
	//   -5 : Filename is too long (max. 255)
	//   -6 : Not a valid zip file
	//   -7 : Invalid extracted file size
	//   -8 : Unable to create directory
	//   -9 : Invalid archive extension
	//  -10 : Invalid archive format
	//  -11 : Unable to delete file (unlink)
	//  -12 : Unable to rename file (rename)
	//  -13 : Invalid header checksum
	//  -14 : Invalid archive size

#	redirige_par_entete($url_retour);
}

?>
