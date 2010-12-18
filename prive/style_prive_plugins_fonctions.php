<?php

function tous_les_fonds($dir,$pattern){
	$liste = find_all_in_path($dir,$pattern);
	foreach($liste as $k=>$v)
		$liste[$k] = $dir . basename($v,'.html');
	return $liste;
}

?>
