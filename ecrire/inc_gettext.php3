<?php

// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_GETTEXT")) return;
define("_ECRIRE_INC_GETTEXT", "1");


//
// i18n : merge ("My name is @name@", array('name'=>'Bob'))
//        into "My name is Bob"
//
function text_merge($text, $args) {
	if (is_array($args)) {
		while (list($name,$value) = each($args))
			$text = ereg_replace ("@$name@", "$value", $text);
	}
	return $text;
}

//
// i18n : our own small gettext
//
function spip_gettext($text, $args, $lang) {
	global $i18n;
	global $dir_ecrire;

	// load the language file
	if (!$i18n[$lang]) {
		if (file_exists($dir_ecrire."i18n/spip_$lang.php3"))
			include_ecrire ("i18n/spip_$lang.php3");
		else {
			$lang = 'fr';
			include_ecrire ("i18n/spip_fr.php3");
		}
	}

	// get the french text if the translation file is not complete
	if (!$i18n[$lang][$text]) {
		$lang = 'fr';
		include_ecrire ("i18n/spip_fr.php3");
	}

	// use the translated text if found
	if ($i18n[$lang][$text])
		$text = $i18n[$lang][$text];

	// merge it with the variables
	return text_merge($text, $args);
}

?>