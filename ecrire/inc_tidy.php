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


function echappe_xhtml ($letexte) { // oui, c'est dingue... on echappe le mathml
	$regexp_echap_math = "/<math((.*?))<\/math>/si";
	$source = "mathml";

	while (preg_match($regexp_echap_math, $letexte, $regs)) {
		$num_echap++;
		
		$les_echap[$num_echap] = $regs[0];

		$pos = strpos($letexte, $regs[0]);
		$letexte = substr($letexte,0,$pos)."@@SPIP_$source$num_echap@@"
			.substr($letexte,$pos+strlen($regs[0]));
	}
	
	return array($letexte, $les_echap);
}


function xhtml ($buffer) {
	if (version_tidy() == "1") {
		include_ecrire("inc_texte.php3");

		$retour_echap = echappe_xhtml ($buffer);
		$buffer = $retour_echap[0];
		$les_echap = $retour_echap[1];

		// Options selon: http://tidy.sourceforge.net/docs/quickref.html
		tidy_set_encoding ("utf8");
		tidy_setopt('wrap', 0);
		tidy_setopt('indent-spaces', 4);
		tidy_setopt('output-xhtml', true);
		tidy_setopt('indent', 5);
		tidy_setopt('show-body-only', false);
		tidy_setopt('quote-nbsp', false);
	
		$html = tidy_parse_string($buffer);
	    tidy_clean_repair();
	    $tidy = tidy_get_output();
	    $tidy = echappe_retour($tidy, $les_echap, "mathml");
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



function xhtml_nettoyer_chaine ($buffer) {
	if (version_tidy() == "1") {
		include_ecrire("inc_texte.php3");

		$retour_echap = echappe_xhtml ($buffer);
		$buffer = $retour_echap[0];
		$les_echap = $retour_echap[1];

		// Options selon: http://tidy.sourceforge.net/docs/quickref.html
		tidy_set_encoding ("utf8");
		tidy_setopt('wrap', 0);
		tidy_setopt('indent-spaces', 4);
		tidy_setopt('output-xhtml', true);
		tidy_setopt('indent', 5);
		tidy_setopt('show-body-only', true);
		tidy_setopt('quote-nbsp', false);
	
		$html = tidy_parse_string($buffer);
	    tidy_clean_repair();
	    $tidy = tidy_get_output();
	    $tidy = echappe_retour($tidy, $les_echap, "mathml");
		return $tidy;
	}
	else if (version_tidy() == "2") {
		$config = array('indent' => TRUE,
			'output-xhtml' => TRUE,
			'wrap' => 200,
			'show-body-only' => TRUE);
		$tidy = tidy_parse_string($buffer, $config, 'UTF8');
		$tidy->cleanRepair();
	return $tidy;	
	}	
	else return $buffer;
}	





?>