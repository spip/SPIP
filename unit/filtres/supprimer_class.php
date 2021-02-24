<?php
/**
 * Test unitaire de la fonction supprimer_class
 * du fichier ./inc/filtres.php
 *
 * genere automatiquement par TestBuilder
 * le 2021-02-19 14:53
 */

	$test = 'supprimer_class';
	$remonte = "../";
	while (!is_dir($remonte."ecrire"))
		$remonte = "../$remonte";
	require $remonte.'tests/test.inc';
	find_in_path("./inc/filtres.php",'',true);

	// chercher la fonction si elle n'existe pas
	if (!function_exists($f='supprimer_class')){
		find_in_path("inc/filtres.php",'',true);
		$f = chercher_filtre($f);
	}

	//
	// hop ! on y va
	//
	$err = tester_fun($f, essais_supprimer_class());
	
	// si le tableau $err est pas vide ca va pas
	if ($err) {
		die ('<dl>' . join('', $err) . '</dl>');
	}

	echo "OK";
	

	function essais_supprimer_class(){
		$essais = array (
  0 => 
  array (
    0 => '<span class=\'maclasse-prefixe suffixe-maclasse maclasse--bem\'>toto</span>',
    1 => '<span class="maclasse maclasse-prefixe suffixe-maclasse maclasse--bem">toto</span>',
    2 => 'maclasse',
  ),
  1 => 
  array (
    0 => '<span class="maclasse maclasse-prefixe suffixe-maclasse maclasse--bem">toto</span>',
    1 => '<span class="maclasse maclasse-prefixe suffixe-maclasse maclasse--bem">toto</span>',
    2 => 'autreclass',
  ),
  2 => 
  array (
    0 => '<span class=\'maclasse-prefixe suffixe-maclasse maclasse--bem\'>toto</span>',
    1 => '<span class="maclasse maclasse-prefixe suffixe-maclasse maclasse--bem">toto</span>',
    2 => 'maclasse1 maclasse maclasse2',
  ),
  3 => 
  array (
    0 => '<span class=\'maclasse suffixe-maclasse\'>toto</span>',
    1 => '<span class="maclasse maclasse-prefixe suffixe-maclasse maclasse--bem">toto</span>',
    2 => 'maclasse-prefixe maclasse--bem',
  ),
  4 => 
  array (
    0 => '<span class=\'maclasse-prefixe\'>toto</span>',
    1 => '<span class="maclasse maclasse-prefixe">toto</span>',
    2 => 'maclasse',
  ),
  5 => 
  array (
    0 => '<span class=\'maclasse\'>toto</span>',
    1 => '<span class="maclasse maclasse-prefixe">toto</span>',
    2 => 'maclasse-prefixe',
  ),
  6 => 
  array (
    0 => '<span>toto</span>',
    1 => '<span class="maclasse maclasse-prefixe">toto</span>',
    2 => 'maclasse maclasse-prefixe',
  ),
  7 => 
  array (
    0 => '<span>toto</span>',
    1 => '<span class="maclasse maclasse-prefixe">toto</span>',
    2 => 'maclasse-prefixe maclasse',
  ),
);
		return $essais;
	}









?>