<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/*
 * Determiner l'utilisation du vertebreur
 * lorsque l'on passe un appel spip.php?page=table:articles
 */
function vertebres_styliser($flux) {

	// si pas de squelette trouve,
	// on verifie si on demande une vue de table
	if (!$squelette = $flux['data']
	  AND $fond = $flux['args']['fond']
	  AND strncmp($fond,'prive/vertebres:',16)==0
	  AND $table = substr($fond,16)
	  AND include_spip('inc/autoriser')
		AND autoriser('webmestre')) {

		$ext = $flux['args']['ext'];
		$connect = $flux['args']['connect'];
		
		// Si pas de squelette regarder si c'est une table
		// et si l'on a la permission de l'afficher
		$trouver_table = charger_fonction('trouver_table', 'base');
		if ($desc= $trouver_table($table, $connect)) {
			$fond = $table;
			$base = _DIR_TMP . 'table_' . $fond . ".$ext";
			if (!file_exists($base)
			OR  (defined('_VAR_MODE') AND _VAR_MODE)) {
				$vertebrer = charger_fonction('vertebrer', 'public');
				ecrire_fichier($base, $vertebrer($desc));
			}
			
			// sauver les changements
			$flux['data'] = _DIR_TMP . 'table_' . $fond;
		}
	}
	
	return $flux;
}

?>
