<?php
/**
 * Test unitaire de la fonction multiple_de_trois
 * du fichier filtres/images_lib.php
 *
 * genere automatiquement par TestBuilder
 * le 
 */


	$test = 'multiple_de_trois';
	$remonte = "../";
	while (!is_dir($remonte."ecrire"))
		$remonte = "../$remonte";
	require $remonte.'tests/test.inc';
	find_in_path("filtres/images_lib.php",'',true);

	//
	// hop ! on y va
	//
	$err = tester_fun('multiple_de_trois', essais_multiple_de_trois());
	
	// si le tableau $err est pas vide ca va pas
	if ($err) {
		die ('<dl>' . join('', $err) . '</dl>');
	}

	echo "OK";
	

	function essais_multiple_de_trois(){
		$essais = array (
  0 => 
  array (
    0 => 0,
    1 => 0,
  ),
  1 => 
  array (
    0 => -0,
    1 => -1,
  ),
  2 => 
  array (
    0 => 0,
    1 => 1,
  ),
  3 => 
  array (
    0 => 3,
    1 => 2,
  ),
  4 => 
  array (
    0 => 3,
    1 => 3,
  ),
  5 => 
  array (
    0 => 3,
    1 => 4,
  ),
  6 => 
  array (
    0 => 6,
    1 => 5,
  ),
  7 => 
  array (
    0 => 6,
    1 => 6,
  ),
  8 => 
  array (
    0 => 6,
    1 => 7,
  ),
  9 => 
  array (
    0 => 9,
    1 => 10,
  ),
  10 => 
  array (
    0 => 21,
    1 => 20,
  ),
  11 => 
  array (
    0 => 30,
    1 => 30,
  ),
  12 => 
  array (
    0 => 51,
    1 => 50,
  ),
  13 => 
  array (
    0 => 99,
    1 => 100,
  ),
  14 => 
  array (
    0 => 999,
    1 => 1000,
  ),
  15 => 
  array (
    0 => 9999,
    1 => 10000,
  ),
);
		return $essais;
	}



?>