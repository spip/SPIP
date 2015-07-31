<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_TIDY")) return;
define("_ECRIRE_INC_TIDY", "1");


function version_tidy() {
	$version = 0;
	
	if (function_exists('tidy_parse_string')) {
		$version = 1;
		if (function_exists('tidy_get_body')) {
			$version = 2;
		}
	}
	return $version;
}

function xhtml ($buffer) {
	$config = array('indent' => TRUE,
               'output-xhtml' => TRUE,
               'wrap', 200);

	if (version_tidy() == "1") {
		tidy_set_encoding ("utf8");
		tidy_setopt('wrap', 0);
		tidy_setopt('indent-spaces', 4);
		tidy_setopt('output-xhtml', true);
		tidy_setopt('add-xml-decl', true);
		tidy_setopt('indent', 5);
	
		$html = tidy_parse_string($buffer);
	    tidy_clean_repair();
	    $tidy = tidy_get_output();
		return $tidy;
	}
	else if (version_tidy() == "2") {
		$config = array('indent' => TRUE,
			'output-xhtml' => TRUE,
			'wrap' => 200);
		$tidy = tidy_parse_string($buffer, $config, 'UTF8');
		$tidy->cleanRepair();
	return $tidy;	
	}	
	else return $buffer;
}	



?>