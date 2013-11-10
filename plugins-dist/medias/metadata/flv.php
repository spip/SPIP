<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

function metadata_flv_dist($file, $bigindian = true){
	$meta = array();

	if ($fd = fopen($file, 'r')
	  AND $raw = fread($fd, 512)){

		$keys = array('largeur'=>'width', 'hauteur'=>'height','duree'=>'duration','framerate'=>'framerate');
		foreach ($keys as $m=>$k) {
			if (($i = strpos($raw, $k))>-1){
				$bytes = substr($raw, $i+strlen($k)+1, 8);
				if ($bigindian)
					$bytes = strrev($bytes);
				$zz = unpack("dflt", $bytes); // unpack the bytes
				$meta[$m] = $zz['flt']; // return the number from the associative array
			}
		}
	}

	return $meta;
}

?>