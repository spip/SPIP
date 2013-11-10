<?php

/*
 * transforme un raccourci de ressource en un lien minimaliste
 * 
 *
 */

define('_EXTRAIRE_RESSOURCES', ',' . '<"?(https?://|[\w -]+\.[\w -]+)[^<]*>'.',UimsS');


function traiter_ressources($r) {
	if ($ressource = charger_fonction('ressource', 'inc', true)) {
		$html = $ressource($r[0]);
	} else {
		include_spip('inc/lien');
		$url = explode(' ', trim($r[0], '<>'));
		$url = $url[0];
		# <http://url/absolue>
		if (preg_match(',^https?://,i', $url))
			$html = propre("<span class='ressource spip_out'>&lt;[->".$url."]&gt;</span>");
		# <url/relative>
		else if (false !== strpos($url, '/'))
			$html = propre("<span class='ressource spip_in'>&lt;[->".$url."]&gt;</span>");
		# <fichier.rtf>
		else {
			preg_match(',\.([^.]+)$,', $url, $regs);
			if (file_exists($f = _DIR_IMG.$regs[1].'/'.$url)) {
				$html = propre("<span class='ressource spip_in'>&lt;[".$url."->".$f."]&gt;</span>");
			} else {
				$html = propre("<span class='ressource'>&lt;".$url."&gt;</span>");
			}
		}
	}

	return '<html>'.$html.'</html>';
}

?>
