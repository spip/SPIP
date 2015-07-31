<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


	//
	// Ce fichier assure la compatibilite avec spip-lab
	//
	if (defined('_COMPATIBLE_SPIP_LAB')) {
		return;
	} else {
		define('_COMPATIBLE_SPIP_LAB', 1);

		// Appel depuis un fichier d'affichage (articles_version.php)
		if (!defined('_ECRIRE_INC_VERSION')) {
			include("inc.php3");
include_ecrire("inc_presentation");
include_ecrire("inc_texte");
include_ecrire("inc_urls");
include_ecrire("inc_rubriques");
include_ecrire("inc_index");
include_ecrire("inc_logos");
include_ecrire('inc_forum');
		}

		// Appel depuis un fichier librairie
		function include_spip($fichier) {

			switch ($fichier) {
				case 'ecrire.php':
					break;
				default:
					// charger la version spip-lab si presente
					if (@file_exists(_DIR_RESTREINT.'lab_'.$fichier))
						include('lab_'.$fichier);
					// sinon prendre la version spip-stable
					else
						if (@file_exists(_DIR_RESTREINT.'inc_'.$fichier.'3'))
							include_ecrire('inc_'.$fichier.'3');
					// mais peut-etre avons nous une version '.php' de la version stable
					else
						if (@file_exists(_DIR_RESTREINT.'inc_'.$fichier))
							include_ecrire('inc_'.$fichier);
					else
						die ("Fichier SPIP-Lab \"$fichier\" manquant.");
					break;
			}
		}


		// pas encore backportee
		function html_background() {
		}

	}

?>
