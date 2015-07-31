<?php

	// wrapper image pour scintillement MSIE, cf.
	// http://www.ultra-fluide.com/ressources/css/css-hacks.htm#scintillement
	if (isset($_GET['file'])){
		$f = $_GET['file']; // pas de urldecode ici car on accepte de toute facon que les caracteres alphanumeriques
		if (preg_match(',^[a-z_0-9\-]+\.(gif|jpg|png)$,i', $f, $r)
				AND @file_exists('./'.$f)){
	
			$mime = array(
				'jpg' => 'image/jpeg',
				'gif' => 'image/gif',
				'png' => 'image/png'
			);
		
			header('Content-Type: '.$mime[strtolower($r[1])]);
			header('Content-Length: '.filesize('./'.$f));
			header('Cache-Control: max-age=36000');
			header('Pragma: public');
			readfile('./'.$f);
			exit;
		}
	}
	
	header("Status : 404 Not Found"); 
	die('404 not found');

?>
