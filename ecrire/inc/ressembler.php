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


if (!defined('_ECRIRE_INC_VERSION')) return;



/**
 * Calcule une distance levenshtein
 * (similarité entre 2 chaines)
 * en ne prenant en compte que les 254 premiers
 * caractères des chaines transmises.
 *
 * [ne pas faire d'erreur si les chaines sont > 254 caracteres]
 * 
 * @param string $a	premier mot
 * @param string $b	second mot
 * @return distance de levenshtein
**/
// http://doc.spip.org/@levenshtein255
function levenshtein255 ($a, $b) {
	$a = substr($a, 0, 254);
	$b = substr($b, 0, 254);
	return @levenshtein($a,$b);
}


/**
  * Reduit un mot a sa valeur translitteree et en minuscules
  *
  * @param string $mot Mot a transliterer
  * @return Mot translittere en minuscule.
 **/
// http://doc.spip.org/@reduire_mot
function reduire_mot($mot) {
	return strtr(
		translitteration(trim($mot)),
		'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
		'abcdefghijklmnopqrstuvwxyz'
		);
}


/**
 * Retrouve dans une liste donnee les mots qui ont des orthographes
 * proches d'un certain mot.
 * L'algorythme utilise la distance de levenshtein, pour trouver ces mots
 * et retourne les identifiants de table correspondants dans second tableau transmis.
 * 
 * @param string $mot	Le mot a chercher : 'juniur'
 * @param array $table_mots		Le dictionnaire : array('junior', 'vivien')
 * @param array $table_ids		Optionnel, table d'identifiants correspondants : array(3, 5)
 * @return array	Liste des noms (ou identifiants si transmis) approchants : array('junior') ou array(3)
**/
// http://doc.spip.org/@mots_ressemblants
function mots_ressemblants($mot, $table_mots, $table_ids='') {

	$result = array();

	if (!$table_mots) return $result;

	$lim = 2;
	$nb = 0;
	$opt = 1000000;
	$mot_opt = '';
	$mot = reduire_mot($mot);
	$len = strlen($mot);

	while (!$nb AND $lim < 10) {
		reset($table_mots);
		if ($table_ids) reset($table_ids);
		while (list(, $val) = each($table_mots)) {
			if ($table_ids) list(, $id) = each($table_ids);
			else $id = $val;
			$val2 = trim($val);
			if ($val2) {
				if (!isset($distance[$id])) {
					$val2 = reduire_mot($val2);
					$len2 = strlen($val2);
					if ($val2 == $mot)
						$m = -2; # resultat exact
					else if (substr($val2, 0, $len) == $mot)
						$m = -1; # sous-chaine
					else {
						# distance
						$m = levenshtein255($val2, $mot);
						# ne pas compter la distance due a la longueur
						$m -= max(0, $len2 - $len); 
					}
					$distance[$id] = $m;
				} else $m = 0;
				if ($m <= $lim) {
					$selection[$id] = $m;
					if ($m < $opt) {
						$opt = $m;
						$mot_opt = $val;
					}
					$nb++;
				}
			}
		}
		$lim += 2;
	}

	if (!$nb) return $result;
	reset($selection);
	if ($opt > -1) {
		$moy = 1;
		while(list(, $val) = each($selection)) $moy *= $val;
		if($moy) $moy = pow($moy, 1.0/$nb);
		$lim = ($opt + $moy) / 2;
	}
	else $lim = -1;

	reset($selection);
	while (list($key, $val) = each($selection)) {
		if ($val <= $lim) {
			$result[] = $key;
		}
	}
	return $result;
}



?>
