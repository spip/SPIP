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

function action_charger_plugin_dist() {
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

	if (!preg_match(',^(https?|ftp)://.*\.zip,',
		$zip = _request('url_zip_plugin2'))
	AND !preg_match(',^(https?|ftp)://.*\.zip,',
		$zip = _request('url_zip_plugin')))
	{
		include_spip('inc/headers');
		redirige_par_entete(generer_url_ecrire('admin_plugin'));
	}

	# destination des fichiers
	$dest = _DIR_PLUGINS_AUTO;

	# eliminer plugins/ du chemin indique
	$remove = 'plugins';

	# dispose-t-on du fichier ?
	$status = null;
	$fichier = $dest.basename($zip);
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
				'remove' => $remove,
				'dest' => $dest,
				'fichier' => $fichier,
				'extract' => _request('extract')
			)
		);
		if (_request('extract')) @unlink($fichier);
	}


	if (is_array($status)) {
		if (_request('extract')) {
			$retour = _L('Plugin install&#233;');
			$texte = '<p>'._L('Le fichier '.$zip.' a &#233;t&#233; d&#233;compact&#233; et install&#233;').'</p>';
			$texte .= _L("<h2 style='text-align:center;'>Vous pouvez maintenant l'activer.</h2>");
		} else {
			$retour = _L('Plugin charg&#233;');
			$texte = '<p>'._L('Le fichier '.$zip.' a &#233;t&#233; t&#233;l&#233;charg&#233;').'</p>';
			$texte .= liste_fichiers_pclzip($status);
			$texte .= _L("<h2 style='text-align:center;'>Vous pouvez maintenant l'installer.</h2>");
		}

	} else if ($status < 0) {
		$retour = _T('erreur');
		$texte = _L("echec pclzip : erreur ").$status;
	} else if ($status == 0) {
		$retour = _T('erreur');
		$texte = _L('erreur : impossible de charger '.$zip);
	}

	include_spip('exec/install'); // pour bouton_suivant()
	echo minipres($retour,
		_request('extract')
			? generer_form_ecrire('admin_plugin&plug='.$status['dirname'],
				$texte . bouton_suivant())
			: "<form action='".self()."' method='post'>"
				.form_hidden(
					'?action='._request('action')
					.'&url_zip_plugin='.$zip.'&extract=oui'
				)
				.$texte.bouton_suivant()."</form>\n"
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
