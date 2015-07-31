<?php

# Standard: on ge'ne`re une se'quence PHP qui sera re'interpre'te'e a` chaque fois.
# Avec le compilateur e'tant re'entrant, on peut inclure imme'diatement 
# en cas de script standard (i.e. les 2 affectations de fond et delais).
# Teste' & valide', mais mettre c~a en option car l'incidence sur des squelettes
# avec de'lais configure's pour l'ancienne version peuvent en souffir

function calculer_inclure($fichier, $params, $id_boucle, &$boucles, $pi)
{
	global $dossier_squelettes;
	/*	if (!$pi && (preg_match("/\s*<.php\s*.fond\s*=\s*[\"\']([^;]*)[\"\']\s*;\s*.delais\s*=\s*([^;]*);\s*include\s*..inc-public.php3?..;\s*.>/",
implode('',file((($dossier_squelettes) &&
					    @file_exists("$dossier_squelettes/$fichier")) ?
					   "$dossier_squelettes/$fichier" :
					   $fichier)),
				$m)))
	    {
	      $l  = "";
	      if ($params) {
		reset($params);
		while (list(, $param) = each($params)) {
		  if (ereg("^([_0-9a-zA-Z]+)[[:space:]]*(=[[:space:]]*([^}]+))?$", $param, $args)) {
		    $var = $args[1];
		    $val = $args[3];
		    $l .= "'$var' =>" .
		      ($val ?
		       ("'" . addslashes($val) . "'") :
		       (index_pile($id_boucle, $var, $boucles))) .
		      ",";
		  }
		}
	      }

	      return "inclure_subpage('" . $m[1] . "'," . $m[2] . 
		", array(" . $l . '), $Cache["cache"])';}
		else */
	    { # vieux code du compilateur non re'entrant. 
	      # reste utile pour les squelettes appele's bizarrement
	      $l  = "";
	      if ($params) {
		reset($params);
		while (list(, $param) = each($params)) {
		  if (ereg("^([_0-9a-zA-Z]+)[[:space:]]*(=[[:space:]]*([^}]+))?$", $param, $args)) {
		    $var = $args[1];
		    $val = $args[3];
		    if ($val)
		      $l[] = "'\'$var\' => " . addslashes($val) . "'";
		    else
		      $l[] = "'\'$var\' => \'' . addslashes(" . index_pile($id_boucle, $var, $boucles) . ") .'\''";
		  }
		}
	      }
	      return "\n'<".
		"?php include_ecrire(\'inc_lang.php3\'); lang_select(lire_meta(\'langue_site\')); \$contexte_inclus = array(' ." . 
		($l ? (join(".', '.\n",$l)) : '""') . ".');" .
		(($dossier_squelettes) ?
		 ("
			if (@file_exists(\'$dossier_squelettes/$fichier\')){
				include(\'$dossier_squelettes/$fichier\');
			} else {
				include(\'$fichier\');
			} " ) :
		 ("include(\'$fichier\');")) .
		"lang_dselect(); ?" . ">'";
	    }
}

// Convertit un texte Spip en une EXPRESSION php 
// donc qqch qui peut e^tre l'argument d'un Return 
// ou la partie droite d'une affectation

function calculer_texte($texte, $id_boucle, &$boucles, $id_mere)
{
	$code = ".\n '".ereg_replace("([\\\\'])", "\\\\1", $texte)."'";

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

	return (ereg('^\..', $code) ? substr($code,2) : $code);
}
?>
