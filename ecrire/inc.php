<?php

	//
	// Ce fichier assure la compatibilite avec spip-lab
	//
	if (defined('_COMPATIBLE_SPIP_LAB')) {
		return;
	} else {
		define('_COMPATIBLE_SPIP_LAB', 1);

		// Appel depuis un fichier d'affichage (articles_version.php)
		if (!defined('_ECRIRE_INC_VERSION')) {
			include('inc.php3');
		}

		// Appel depuis un fichier librairie
		function include_spip($fichier) {
			global $dir_ecrire;

			switch ($fichier) {
				case 'ecrire.php':
					break;
				default:
					// charger la version spip-lab si presente
					if (@file_exists($dir_ecrire.'lab_'.$fichier))
						include_ecrire('lab_'.$fichier);
					// sinon prendre la version spip-stable
					else
						if (@file_exists($dir_ecrire.'inc_'.$fichier.'3'))
							include_ecrire('inc_'.$fichier.'3');
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