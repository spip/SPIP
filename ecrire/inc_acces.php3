<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_ACCES")) return;
define("_ECRIRE_INC_ACCES", "1");


$GLOBALS['htaccess'] = $GLOBALS['dir_ecrire'].'.htaccess';
$GLOBALS['htpasswd'] = $GLOBALS['dir_ecrire'].'data/.htpasswd';


function creer_pass_aleatoire($longueur = 8, $sel = "") {
	global $flag_mt_rand;
	$seed = (double) (microtime() + 1) * time();
	if ($flag_mt_rand) mt_srand($seed);
	srand($seed);

	for ($i = 0; $i < $longueur; $i++) {
		if (!$s) {
			if ($flag_mt_rand) $s = mt_rand();
			if (!$s) $s = rand();
			$s = substr(md5(uniqid($s).$sel), 0, 15);
		}
		$r = unpack("Cr", pack("H2", $s));
		$x = $r['r'] & 63;
		if ($x < 10) $x = chr($x + 48);
		else if ($x < 36) $x = chr($x + 55);
		else if ($x < 62) $x = chr($x + 61);
		else if ($x == 63) $x = '/';
		else $x = '.';
		$pass .= $x;
		$s = substr($s, 1);
	}
	return $pass;
}

function initialiser_sel() {
	global $htsalt;

	$htsalt = '$1$'.creer_pass_aleatoire();
}


function ecrire_logins($fichier, $tableau_logins) {
	reset($tableau_logins);

	while(list($login, $htpass) = each($tableau_logins)) {
		if ($login && $htpass) {
			fputs($fichier, "$login:$htpass\n");
		}
	}
}


function ecrire_acces() {
	global $htaccess, $htpasswd;

	$query = "SELECT login, htpass FROM spip_auteurs WHERE statut != '5poubelle' AND statut!='6forum'";
	$result = mysql_query($query);

	unset($logins);
	while($row = mysql_fetch_array($result)) $logins[$row[0]] = $row[1];

	$fichier = fopen($htpasswd, "w");
	ecrire_logins($fichier, $logins);
	fclose($fichier);

	$query = "SELECT login, htpass FROM spip_auteurs WHERE statut = '0minirezo'";
	$result = mysql_query($query);

	unset($logins);
	while($row = mysql_fetch_array($result)) $logins[$row[0]] = $row[1];

	$fichier = fopen("$htpasswd-admin", "w");
	ecrire_logins($fichier, $logins);
	fclose($fichier);
}


function generer_htpass($pass) {
	global $htsalt, $flag_crypt;
	if ($flag_crypt) return crypt($pass, $htsalt);
	else return '';
}


initialiser_sel();


?>
