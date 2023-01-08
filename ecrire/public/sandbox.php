<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Gestion d'une sécurisation des squelettes
 *
 * Une surcharge de ce fichier pourrait permettre :
 *
 * - de limiter l'utilisation des filtres à l'aide d'une liste d’inclusion ou d’exclusion,
 * - de rendre inactif le PHP écrit dans les squelettes
 * - de refuser l'inclusion de fichier PHP dans les squelettes
 *
 * @package SPIP\Core\Compilateur\Sandbox
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Composer le code d'exécution d'un texte
 *
 * En principe juste un echappement de guillemets
 * sauf si on veut aussi echapper et interdire les scripts serveurs
 * dans les squelettes
 *
 * @param string $texte
 *     texte à composer
 * @param Champ $p
 *     Balise qui appelle ce texte
 * @return string
 *     texte
 */
function sandbox_composer_texte($texte, &$p) {
	$code = "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $texte) . "'";

	return $code;
}


/**
 * Composer le code d'exécution d'un filtre
 *
 * @param string $fonc
 * @param string $code
 * @param string $arglist
 * @param Champ $p
 * @param int $nb_arg_droite        nb d'arguments à droite du filtre dans le source spip |fff{a,b,c}
 *     Balise qui appelle ce filtre
 * @return string
 */
function sandbox_composer_filtre($fonc, $code, $arglist, &$p, $nb_arg_droite = 1000): string {
	if (isset($GLOBALS['spip_matrice'][$fonc])) {
		$code = "filtrer('$fonc',$code$arglist)";
	}

	// le filtre est defini sous forme de fonction ou de methode
	// par ex. dans inc_texte, inc_filtres ou mes_fonctions
	elseif ($f = chercher_filtre($fonc)) {
		// cas particulier : le filtre |set doit acceder a la $Pile
		// proto: filtre_set(&$Pile, $val, $args...)
		if (strpbrk($f, ':')) { // Class::method
			$refl = new ReflectionMethod($f);
		} else {
			$refl = new ReflectionFunction($f);
		}
		$refs = $refl->getParameters();
		if (isset($refs[0]) and $refs[0]->name == 'Pile') {
			$code = "$f(\$Pile,$code$arglist)";
			$nb_arg_gauche = 2; // la balise à laquelle s'applique le filtre + $Pile
		} else {
			$code = "$f($code$arglist)";
			$nb_arg_gauche = 1; // la balise à laquelle s'applique le filtre
		}
		$nb_args_f = $nb_arg_gauche + $nb_arg_droite;
		$min_f = $refl->getNumberOfRequiredParameters();
		if (($nb_args_f < $min_f)) {
			$msg_args = ['filtre' => texte_script($fonc), 'nb' => $min_f - $nb_args_f];
			erreur_squelette([ 'zbug_erreur_filtre_nbarg_min', $msg_args], $p);
		}
	}
	// le filtre n'existe pas,
	// on le notifie
	else {
		erreur_squelette(['zbug_erreur_filtre', ['filtre' => texte_script($fonc)]], $p);
	}

	return $code;
}

// Calculer un <INCLURE(xx.php)>
// La constante ci-dessous donne le code general quand il s'agit d'un script.
define('CODE_INCLURE_SCRIPT', 'if (!($path = %s) OR !is_readable($path))
	erreur_squelette(array("fichier_introuvable", array("fichier" => "%s")), array(%s));
else {
	$contexte_inclus = %s;
	include $path;
}
');

/**
 * Composer le code d'inclusion PHP
 *
 * @param string $fichier
 * @param Champ $p
 *     Balise créant l'inclusion
 * @param string $_contexte Chaine PHP qui écrira le contexte 'array(...)'
 * @return string
 */
function sandbox_composer_inclure_php($fichier, &$p, $_contexte) {
	$compil = texte_script(memoriser_contexte_compil($p));
	// si inexistant, on essaiera a l'execution
	if ($path = find_in_path($fichier)) {
		$path = "\"$path\"";
	} else {
		$path = "find_in_path(\"$fichier\")";
	}

	return sprintf(CODE_INCLURE_SCRIPT, $path, $fichier, $compil, $_contexte);
}

/**
 * Composer le code de sécurisation anti script
 *
 * @param string $code
 * @param Champ $p
 *     Balise sur laquelle s'applique le filtre
 * @return string
 */
function sandbox_composer_interdire_scripts($code, &$p) {
	// Securite
	if (
		$p->interdire_scripts
		and $p->etoile != '**'
	) {
		if (!preg_match("/^sinon[(](.*),'([^']*)'[)]$/", $code, $r)) {
			$code = "interdire_scripts($code)";
		} else {
			$code = interdire_scripts($r[2]);
			$code = "sinon(interdire_scripts($r[1]),'$code')";
		}
	}

	return $code;
}


/**
 * Appliquer des filtres sur un squelette complet
 *
 * La fonction accèpte plusieurs tableaux de filtres à partir du 3ème argument
 * qui seront appliqués dans l'ordre
 *
 * @uses echapper_php_callback()
 *
 * @param array $skel
 * @param string $corps
 * @param array $filtres
 *     Tableau de filtres à appliquer.
 * @return mixed|string
 */
function sandbox_filtrer_squelette($skel, $corps, $filtres) {
	$series_filtres = func_get_args();
	array_shift($series_filtres);// skel
	array_shift($series_filtres);// corps

	// proteger les <INCLUDE> et tous les morceaux de php licites
	if ($skel['process_ins'] == 'php') {
		$corps = preg_replace_callback(',<[?](\s|php|=).*[?]>,UimsS', 'echapper_php_callback', $corps);
	}

	// recuperer les couples de remplacement
	$replace = echapper_php_callback();

	foreach ($series_filtres as $filtres) {
		if (is_countable($filtres) ? count($filtres) : 0) {
			foreach ($filtres as $filtre) {
				if ($filtre and $f = chercher_filtre($filtre)) {
					$corps = $f($corps);
				}
			}
		}
	}

	// restaurer les echappements
	return str_replace($replace[0], $replace[1], $corps);
}


/**
 * Callback pour échapper du code PHP (les séquences `<?php ... ?>`)
 *
 * Rappeler la fonction sans paramètre pour obtenir les substitutions réalisées.
 *
 * @see sandbox_filtrer_squelette()
 *
 * @param array|null $r
 *     - array : ce sont les captures de la regex à échapper
 *     - NULL : demande à dépiler tous les échappements réalisés
 * @return string|array
 *     - string : hash de substitution du code php lorsque `$r` est un array
 *     - array : Liste( liste des codes PHP, liste des substitutions )
 **/
function echapper_php_callback($r = null) {
	static $src = [];
	static $dst = [];

	// si on recoit un tableau, on est en mode echappement
	// on enregistre le code a echapper dans dst, et le code echappe dans src
	if (is_array($r)) {
		$dst[] = $r[0];

		return $src[] = '___' . md5($r[0]) . '___';
	}

	// si on recoit pas un tableau, on renvoit les couples de substitution
	// et on RAZ les remplacements
	$r = [$src, $dst];
	$src = $dst = [];

	return $r;
}
