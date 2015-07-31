<?php

	// wrapper image pour scintillement MSIE, cf.
	// http://www.ultra-fluide.com/ressources/css/css-hacks.htm#scintillement
	if (!isset($_GET['file'])
	OR !preg_match(',^[^/\0]+\.(gif|jpg|png)$,i', $_GET['file'], $r)
	OR !@file_exists('./'.$_GET['file']))
		die('404 not found');

	$mime = array(
		'jpg' => 'image/jpeg',
		'gif' => 'image/gif',
		'png' => 'image/png'
	);

	header('Content-Type: '.$mime[strtolower($r[1])]);
	header('Content-Length: '.filesize('./'.$_GET['file']));
	header('Cache-Control: max-age=36000');
	header('Pragma: public');
	readfile('./'.$_GET['file']);
	exit;

?>