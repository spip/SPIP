<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_32KO_BROWSERS")) return;
define("_ECRIRE_INC_32KO_BROWSERS", "1");


function set_32ko_browsers() {
	global $connu_ok, $connu_coupe;

	// navigateurs OK

	// Netscape
	$connu_ok['Mozilla/4.5 [fr] (Macintosh; U; PPC)'] = true ; // fil
	$connu_ok['Mozilla/4.75 [en] (Win95; U)'] = true; // antoine
	$connu_ok['Mozilla/5.0 (Macintosh; N; PPC; en-US; 0.7) Gecko/20010108'] = true; // pedro
	$connu_ok['Mozilla/4.74 (Macintosh; U; PPC)'] = true; // pedro
	$connu_ok['Mozilla/4.0 (compatible; MSIE 5.5; Windows 98; Win 9x 4.90)'] = true; // antoine
	$connu_ok['Mozilla/5.0 (X11; U; Linux 2.2.17 i586; en-US; Galeon) Gecko/20010421'] = true; // michael parienti
	$connu_ok['Mozilla/4.77 [en] (X11; U; Linux 2.2.17 i586)'] = true; // michael parienti
	$connu_ok['Mozilla/5.0 (X11; U; Linux 2.2.17 i586; en-US; rv:0.9) Gecko/20010505'] = true; // michael parienti
	$connu_ok['Mozilla/4.0 (compatible; MSIE 5.0; Windows 98; DigExt)'] = true;	// fil

	// Konqueror
	$connu_ok['Mozilla/5.0 (compatible; Konqueror/2.1.2; X11)'] = true; // michael parienti

	// Lynx
	$connu_ok['Lynx/2.8.3rel.1 libwww-FM/2.14'] = true; // fil
	$connu_ok['Lynx/2.7.1 (MacOS b1) libwww-FM/unknown'] = true; // pedro

	// Omniweb
	$connu_ok['Mozilla/4.0 (compatible; MSIE 5.5; Mac_PowerPC; OmniWeb/4.0-code-freeze-3)'] = true; // pedro

	// Links
	$connu_ok['Links (0.95; Darwin 1.3.7 Power Macintosh)'] = true; // aris

	// navigateurs qui coupent

	// Internet Explorer
	$connu_coupe['Mozilla/4.0 (compatible; MSIE 5.0; Mac_PowerPC)'] = true; // fil
	$connu_coupe['Mozilla/4.0 (compatible; MSIE 5.1b1; Mac_PowerPC)'] = true; // pedro
	$connu_coupe['Mozilla/4.0 (compatible; MSIE 5.0b1; Mac_PowerPC)'] = true; // pedro
	$connu_coupe['Mozilla/4.0 (compatible; MSIE 5.13; Mac_PowerPC)'] = true;  // fil

	// iCab
	$connu_coupe['iCab/2.5.1 (Macintosh; I; PPC)'] = true; // fil
	$connu_coupe['Mozilla/4.5 (compatible; iCab 2.5.1; Macintosh; I; PPC)'] = true; // pedro

	// Opera
	$connu_coupe['Mozilla/4.0 (compatible; MSIE 5.0; Mac_PowerPC) Opera 5.0  [en]'] = true; // fil
	$connu_coupe['Opera/5.0 (Macintosh;US;PPC)  [en]'] = true; // fil
}



function browser_32ko($user_agent) {
	global $connu_ok;
	if ($connu_ok[$user_agent]) return true;
	else return false;
}

set_32ko_browsers();

?>
