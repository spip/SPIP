<?php

function calculer_inclure($fichier, $params, $id_boucle, &$boucles, $pi) {
	global $dossier_squelettes;

	$l = '';
	if ($params) {
		reset($params);
		foreach($params as $param) {
			$var = $args[1];
			$val = $args[3];

				// Cas de la langue : passer $spip_lang
				// et non table.lang (car depend de {lang_select})
			if ($var =='lang') {
				$critere_langue = "";
				if ($val)
					$l[] = "'\'lang\' => " . addslashes($val) . "'";
				else
					$l[] = "'\'lang\' => \''.\$GLOBALS[spip_lang].'\''";
			}

			else
				if ($val)
					$l[] = "'\'$var\' => " . addslashes($val) . "'";
				else {
					$l[] = "'\'$var\' => \'' . addslashes(" . index_pile($id_boucle, $var, $boucles) . ") .'\''";
				}
			}
		$criteres = ("' ." . join(".', '.\n",$l) . ". '");
	}
	return "\n'<".
		"?php
		\$contexte_inclus = array('.$criteres.');" .
		(($dossier_squelettes) ?
		("
			if (@file_exists(\'$dossier_squelettes/$fichier\')){
				include(\'$dossier_squelettes/$fichier\');
			} else {
				include(\'$fichier\');
			} " ) :
		("include(\'$fichier\');")) .
		"?" . ">'";
}

// Convertit un texte Spip en une EXPRESSION php 
// donc qqch qui peut e^tre l'argument d'un Return 
// ou la partie droite d'une affectation

function calculer_texte($texte, $id_boucle, &$boucles, $id_mere) {
	$code = "'".ereg_replace("([\\\\'])", "\\\\1", $texte)."'";

	// bloc multi
	if (eregi('<multi>', $texte)) {
		$ouvre_multi = 'extraire_multi(';
		$ferme_multi = ')';
	} else {
		$ouvre_multi = $ferme_multi = '';
	}

	// Reperer les balises de traduction <:toto:>
	while (eregi("<:(([a-z0-9_]+):)?([a-z0-9_]+)(\|[^>]*)?:>", $code, $match)) {
		//
		// Traiter la balise de traduction multilingue
		//
		$chaine = strtolower($match[3]);
		if (!($module = $match[2]))
		  // ordre des modules a explorer
		  $module = 'local/public/spip';
		// il faudrait traiter un $m non vide
		list ($c,$m) = applique_filtres(explode('|',
							substr($match[4],1)),
						"_T('$module:$chaine')",
						$id_boucle, 
						$boucles,
						$id_mere);
		$code = str_replace($match[0], 
				    "'$ferme_multi.$c.$ouvre_multi'",
				    $code);
	}

	$code = "$ouvre_multi$code$ferme_multi";
	return ($code);
}

?>
