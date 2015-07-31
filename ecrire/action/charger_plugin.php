<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
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

if (!defined('_ECRIRE_INC_VERSION')) return;

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
	if (!autoriser('configurer', 'plugins')) {
		echo minipres();
		exit;
	}

	if ($arg == 'update_flux') {
		if (is_array($syndic_plug = @unserialize($GLOBALS['meta']['syndic_plug'])))
			foreach ($syndic_plug as $url => $c)
				essaie_ajouter_liste_plugins($url);
	} 
	elseif ($arg == 'supprimer_flux' AND $url = _request('supprimer_flux')) {
		$syndic_plug = @unserialize($GLOBALS['meta']['syndic_plug']);
		unset($syndic_plug[$url]);
		ecrire_meta('syndic_plug', serialize($syndic_plug));
	}
	elseif (in_array($arg,array('charger_zip','lib','plugins'))) {
		// la verification que c'est bien un zip sera faite apres
		$zip = _request('url_zip_plugin');
	}
	elseif (strlen($arg)) {
		// la verification que c'est bien un zip sera faite apres
		$zip = $arg;
	}
	else {
		// indetermine : c'est un zip ou une liste
		$arg = 'charger_liste_ou_zip';
		$zip = _request('url_zip_plugin2');
	}

	# si premiere lecture, destination temporaire des fichiers
	$tmp = sous_repertoire(_DIR_CACHE, 'chargeur');
	# on ne se contenten pas du basename qui peut etre un simple v1
	# exemple de l'url http://nodeload.github.com/kbjr/Git.php/zipball/v0.1.1-rc

	$fichier = (_request('fichier')?
		_request('fichier')
		:"h".substr(md5($zip),0,8)."-".basename($zip)
		);
	# basename par securite notamment dans le cas ou $fichier viens de l'exterieur
	$fichier = $tmp.basename($fichier);
	$extension = ""; // a verifier

	# au second tour, le zip designe directement le fichier au lieu de l'url
	# initiale
	if (!file_exists($fichier)) {
		# si on ne dispose pas encore du fichier
		# verifier que le zip en est bien un (sans se fier a son extension)
		#	en chargeant son entete car l'url initiale peut etre une simple
		# redirection et ne pas comporter d'extension .zip
		include_spip('inc/distant');
		$head = recuperer_page($zip, false, true, 0);

		if (preg_match(",^Content-Type:\s*application/zip$,Uims",$head))
			$extension = "zip";
		// au cas ou, si le content-type n'est pas la
		// mais que l'extension est explicite
		elseif(pathinfo($zip, PATHINFO_EXTENSION)=="zip")
			$extension = "zip";

		# si ce n'est pas un zip dans un format connu,
		# c'est sans doute une liste de plugins
		# si on est dans le bon scenario
		if (!$extension) {
			if ($arg == 'charger_liste_ou_zip') {
				essaie_ajouter_liste_plugins($zip);
			}
		}
	}
	else {
		$extension = pathinfo($zip, PATHINFO_EXTENSION);
	}

	# format de fichier inconnu
	if (!$extension) {
		include_spip('inc/headers');
		redirige_url_ecrire('charger_plugin');
	}

	# Si definie a '', le chargeur est interdit ; mais on n'aurait de toutes
	# facons jamais pu venir ici avec toutes les securisations faites :^)
	if (!_DIR_PLUGINS_AUTO) die('jamais');

	# dispose-t-on du fichier ?
	$status = null;
	# forcer l'extension du fichier par securite
	$fichier = $tmp.basename($fichier,".$extension").".$extension";
	if (!@file_exists($fichier)) {
		include_spip('inc/distant');
		$contenu = recuperer_page($zip, $fichier, false,_COPIE_LOCALE_MAX_SIZE);
		if (!$contenu) {
			spip_log('charger_decompresser impossible de charger '.$zip);
			$status = -1;
		}
	}

	if ($status === null) {
		$status = chargeur_charger_zip(
			array(
				'zip' => $zip,
				'arg' => $arg,
				'fichier' => $fichier,
				'tmp' => $tmp,
				'extract' => _request('extract')
			)
		);
		if (_request('extract')) {
			spip_unlink($fichier);
		}
	}

	// Vers quoi pointe le bouton "suite"
	$suite = '';

	// le fichier .zip est la et bien forme
	if (is_array($status)) {

		// Reconnaitre un plugin par son fichier xml
		$get_infos = charger_fonction('get_infos','plugins');
		$infos = $get_infos($status['tmpname'], true, '');
		if ($infos) {
			$nom = $infos['nom'];
			$image = $infos['icon'];
			$description = $infos['description'];
			$type = 'plugin';
			$dest = _DIR_PLUGINS_AUTO;
		} else {
			$type = 'lib';
			$dest = _DIR_RACINE.'lib/';
		}

		// Fixer son emplacement final
		$status['dirname'] = $dest
			. basename($status['tmpname']) . '/';

		// repertoire parent accessible en ecriture ?
		if (!@is_dir($dest)
		OR !@is_writeable($dest)) {
			$retour = _T("erreur");
			$texte = "<p>"._T('plugin_erreur_droit1',array('dest'=>$dest))."</p>"
			  . "<p>"._T('plugin_erreur_droit2').aide('install0')."</p>";
		}
		else

		// C'est un plugin ?
		if ($type == 'plugin') {

			$retour = typo($nom);

			// l'icone ne peut pas etre dans tmp/ (lecture http oblige)
			// on la copie donc dans local/chargeur/
			if ($image) {
				$dir = sous_repertoire(_DIR_VAR,'chargeur');
				@copy($status['tmpname'].'/'.$image, $image2 = $dir.basename($image));
				$retour = "<img src='".$image2."' style='float:right;' />"
					. $retour;
			} else 
				$retour = "<img src='".find_in_path('images/plugin-24.gif')."' style='float:right;' />"
					. $retour;

			if (_request('extract')) {
				$afficher = charger_fonction('afficher_plugin','plugins'); // pour plugin_propre
				$texte = plugin_propre($description)
				. '<p>'._T('plugin_zip_installe_finie',array('zip'=>$zip)).'</p>'
				. "<h2 style='text-align:center;'>"._T('plugin_zip_active')."</h2>";
			} else {
                $texte = '<p>'._T('plugin_zip_telecharge',array('zip'=>$zip)).'</p>';
				$texte .= liste_fichiers_pclzip($status);
				$texte .= "<h2 style='text-align:center;'>"._T('plugin_zip_installer')."</h2>";
				$suite = 'plugins';
			}
		}

		// C'est un paquet quelconque
		else {
		  $retour = _T('plugin_charge_paquet',array('name' => basename($status['tmpname'])));

			if (_request('extract')) {
			  $texte = '<p>'._T('plugin_zip_installe_rep_finie', array('zip'=>$zip, 'rep'=>$status['dirname'])).'</p>';
			} else {
                $texte = "<p>"._T('plugin_zip_telecharge',array('zip'=>$zip))."</p>\n";
				$texte .= liste_fichiers_pclzip($status);
				$suite = 'lib';
			}
		}
	}

	// fichier absent
	else if ($status == -1) {
		$retour = _T('erreur');
		$texte = _T('plugin_erreur_charger', array('zip'=>$zip));
	}

	// fichier la mais pas bien dezippe
	else {
		$retour = _T('erreur');
		$texte = _T('plugin_erreur_zip',array('status'=>$status));
	}


	include_spip('exec/install'); // pour bouton_suivant()

	$texte = "<div style='text-align:$spip_lang_left;'>$texte</div>\n";

	$redirect = rawurldecode(_request('redirect'));
	// par defaut on revient sur la page admin_plugin
	if($redirect == _DIR_RESTREINT OR $redirect == "./"){
		$redirect_annul = generer_url_ecrire('admin_plugin');
		$redirect_form = 'admin_plugin&voir=recents&'.$type.'='.preg_replace(',^[^/]+/|/$,', '', $status['dirname']);
		$redirect_action = '';
	}
	else{
		$redirect_annul = $redirect;
		$redirect_form = preg_replace(',^.*exec\=,', '', $redirect);
		if (!$suite)
			$texte .= form_hidden(parametre_url(generer_url_ecrire($redirect_form), $type,preg_replace(',^[^/]+/|/$,', '', $status['dirname'])));
		$redirect_action = $redirect_form;
	}
	echo minipres($retour." ",
		$suite
			? redirige_action_post(_request('action'),
				$suite,
				$redirect_action,
				'',
					form_hidden('?url_zip_plugin='.urlencode($zip).'&extract=oui&fichier='.urlencode($fichier))
					.$texte
					."<a class='suivant' href='"
						.$redirect_annul
					."'>"._T('bouton_annuler')."</a>"
				.bouton_suivant())
			: generer_form_ecrire($redirect_form, $texte . bouton_suivant())
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
