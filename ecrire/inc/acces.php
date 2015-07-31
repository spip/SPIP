<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@creer_pass_aleatoire
function creer_pass_aleatoire($longueur = 8, $sel = "") {
	$seed = (double) (microtime() + 1) * time();
	mt_srand($seed);
	srand($seed);
	$s = '';
	$pass = '';
	for ($i = 0; $i < $longueur; $i++) {
		if (!$s) {
			$s = mt_rand();
			if (!$s) $s = rand();
			$s = substr(md5(uniqid($s).$sel), 0, 16);
		}
		$r = unpack("Cr", pack("H2", $s.$s));
		$x = $r['r'] & 63;
		if ($x < 10) $x = chr($x + 48);
		else if ($x < 36) $x = chr($x + 55);
		else if ($x < 62) $x = chr($x + 61);
		else if ($x == 63) $x = '/';
		else $x = '.';
		$pass .= $x;
		$s = substr($s, 2);
	}
	$pass = ereg_replace("[./]", "a", $pass);
	$pass = ereg_replace("[I1l]", "L", $pass);
	$pass = ereg_replace("[0O]", "o", $pass);
	return $pass;
}

//
// Creer un identifiant aleatoire (a fusionnner avec le precedent ?)
//

// http://doc.spip.org/@creer_uniqid
function creer_uniqid() {
	static $seeded;

	if (!$seeded) {
		$seed = (double) (microtime() + 1) * time();
		mt_srand($seed);
		srand($seed);
		$seeded = true;
	}

	$s = mt_rand();
	if (!$s) $s = rand();
	return uniqid($s, 1);
}

//
// Renouvellement de l'alea utilise pour sécuriser les scripts dans action/
//

// http://doc.spip.org/@renouvelle_alea
function renouvelle_alea()
{
	$alea = md5(creer_uniqid());
	ecrire_meta('alea_ephemere_ancien',$GLOBALS['meta']['alea_ephemere']);
	ecrire_meta('alea_ephemere', $alea);
	ecrire_meta('alea_ephemere_date', time());
	ecrire_metas();
  	spip_log("renouvellement de l'alea_ephemere: $alea");
}

//
// low-security : un ensemble de fonctions pour gerer de l'identification
// faible via les URLs (suivi RSS, iCal...)
//
// http://doc.spip.org/@low_sec
function low_sec($id_auteur) {
	// Pas d'id_auteur : low_sec
	if (!$id_auteur = intval($id_auteur)) {
		if (!$low_sec = $GLOBALS['meta']['low_sec']) {
			include_spip('inc/meta');
			ecrire_meta('low_sec', $low_sec = creer_pass_aleatoire());
			ecrire_metas();
		}
	}
	else {
		$result = spip_query("SELECT * FROM spip_auteurs WHERE id_auteur = $id_auteur");

		if ($row = spip_fetch_array($result)) {
			$low_sec = $row["low_sec"];
			if (!$low_sec) {
				$low_sec = creer_pass_aleatoire();
				spip_query("UPDATE spip_auteurs SET low_sec = '$low_sec' WHERE id_auteur = $id_auteur");
			}
		}
	}
	return $low_sec;
}

// http://doc.spip.org/@afficher_low_sec
function afficher_low_sec ($id_auteur, $action='') {
	return substr(md5($action.low_sec($id_auteur)),0,8);
}

// http://doc.spip.org/@verifier_low_sec
function verifier_low_sec ($id_auteur, $cle, $action='') {
	return ($cle == afficher_low_sec($id_auteur, $action));
}

// http://doc.spip.org/@effacer_low_sec
function effacer_low_sec($id_auteur) {
	if (!$id_auteur = intval($id_auteur)) return; // jamais trop prudent ;)
	spip_query("UPDATE spip_auteurs SET low_sec = '' WHERE id_auteur = $id_auteur");
}

// http://doc.spip.org/@initialiser_sel
function initialiser_sel() {
	global $htsalt;

	$htsalt = '$1$'.creer_pass_aleatoire();
}


// http://doc.spip.org/@ecrire_logins
function ecrire_logins($fichier, $tableau_logins) {
	reset($tableau_logins);

	while(list($login, $htpass) = each($tableau_logins)) {
		if ($login && $htpass) {
			fputs($fichier, "$login:$htpass\n");
		}
	}
}


// http://doc.spip.org/@ecrire_acces
function ecrire_acces() {
	$htaccess = _DIR_RESTREINT . _ACCESS_FILE_NAME;
	$htpasswd = _DIR_TMP . _AUTH_USER_FILE;

	// si .htaccess existe, outrepasser spip_meta
	if (($GLOBALS['meta']['creer_htpasswd'] == 'non') AND !@file_exists($htaccess)) {
		@unlink($htpasswd);
		@unlink($htpasswd."-admin");
		return;
	}

	# remarque : ici on laisse passer les "nouveau" de maniere a leur permettre
	# de devenir "1comite" le cas echeant (auth http)... a nettoyer
	// attention, il faut au prealable se connecter a la base (necessaire car utilise par install)
	$result = spip_query_db("SELECT login, htpass FROM spip_auteurs WHERE statut != '5poubelle' AND statut!='6forum'");

	$logins = array();
	while($row = spip_fetch_array($result)) $logins[$row['login']] = $row['htpass'];

	$fichier = @fopen($htpasswd, "w");
	if ($fichier) {
		ecrire_logins($fichier, $logins);
		fclose($fichier);
	} else {
	  redirige_par_entete(generer_url_action('test_dirs', '', true));
	}

	$result = spip_query_db("SELECT login, htpass FROM spip_auteurs WHERE statut = '0minirezo'");


	$logins = array();
	while($row = spip_fetch_array($result)) $logins[$row['login']] = $row['htpass'];

	$fichier = fopen("$htpasswd-admin", "w");
	ecrire_logins($fichier, $logins);
	fclose($fichier);
}


// http://doc.spip.org/@generer_htpass
function generer_htpass($pass) {
	global $htsalt;
	if (function_exists('crypt'))
		return crypt($pass, $htsalt);
}

//
// Verifier la presence des .htaccess
//
// http://doc.spip.org/@verifier_htaccess
function verifier_htaccess($rep) {
	$htaccess = "$rep/" . _ACCESS_FILE_NAME;
	if ((!@file_exists($htaccess)) AND 
	    !defined('_ECRIRE_INSTALL') AND !defined('_TEST_DIRS')) {
		spip_log("demande de creation de $htaccess");
		if ($_SERVER['SERVER_ADMIN'] != 'www@nexenservices.com'){
			if (!$f = @fopen($htaccess, "w")) {
				spip_log("ECHEC DE LA CREATION DE $htaccess"); # ne pas traduire
			} else {
				fputs($f, "deny from all\n");
				fclose($f);
			}
		} else {
			echo "<font color=\"#FF0000\">IMPORTANT : </font>";
			echo "Votre h&eacute;bergeur est Nexen Services.<br />";
			echo "La protection du r&eacute;pertoire <i>$rep/</i> doit se faire
			par l'interm&eacute;diaire de ";
			echo "<a href=\"http://www.nexenservices.com/webmestres/htlocal.php\"
			target=\"_blank\">l'espace webmestres</a>.";
			echo "Veuillez cr&eacute;er manuellement la protection pour
			ce r&eacute;pertoire (un couple login/mot de passe est
			n&eacute;cessaire).<br />";
		}
	}
}

// http://doc.spip.org/@gerer_htaccess
function gerer_htaccess() {
	$mode = $GLOBALS['meta']['creer_htaccess'];
	$r = spip_query("SELECT extension FROM spip_types_documents");
	while ($e = spip_fetch_array($r)) {
		if (is_dir($dir = _DIR_DOC . $e['extension'])) {
			if ($mode == 'oui')
				verifier_htaccess($dir);
			else @unlink("$dir/" . _ACCESS_FILE_NAME);
		}
	}
	return $mode;
}

initialiser_sel();

?>
