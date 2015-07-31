<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

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
	$pass = preg_replace("@[./]@", "a", $pass);
	$pass = preg_replace("@[I1l]@", "L", $pass);
	$pass = preg_replace("@[0O]@", "o", $pass);
	return $pass;
}

/**
 * Creer un identifiant aleatoire
 *
 * http://doc.spip.org/@creer_uniqid
 *
 * @return string
 */
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
// Renouvellement de l'alea utilise pour securiser les scripts dans action/
//

// http://doc.spip.org/@renouvelle_alea
function renouvelle_alea() {
	if (!isset($GLOBALS['meta']['alea_ephemere'])){
		include_spip('base/abstract_sql');
		$GLOBALS['meta']['alea_ephemere'] = sql_getfetsel('valeur', 'spip_meta', "nom='alea_ephemere'");
	}
	ecrire_meta('alea_ephemere_ancien', @$GLOBALS['meta']['alea_ephemere'], 'non');
	$GLOBALS['meta']['alea_ephemere'] = md5(creer_uniqid());
	ecrire_meta('alea_ephemere', $GLOBALS['meta']['alea_ephemere'], 'non');
	ecrire_meta('alea_ephemere_date', time(), 'non');
	spip_log("renouvellement de l'alea_ephemere");
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
			ecrire_meta('low_sec', $low_sec = creer_pass_aleatoire());
		}
	}
	else {
		$low_sec = sql_getfetsel("low_sec", "spip_auteurs", "id_auteur = $id_auteur");
		if (!$low_sec) {
			$low_sec = creer_pass_aleatoire();
			sql_updateq("spip_auteurs", array("low_sec" => $low_sec), "id_auteur = $id_auteur");
		}
	}
	return $low_sec;
}

// Inclure les arguments significatifs pour le hachage
// cas particulier du statut pour compatibilite ancien rss/suivi_revisions

function param_low_sec($op, $args=array(), $lang='', $mime='rss')
{
	$a = $b = '';
	foreach ($args as $val => $var)
		if ($var) {
			if ($val<>'statut') $a .= ':' . $val.'-'.$var;
			$b .= $val.'='.$var . '&';
		}
	$a = substr($a,1);
	$id = intval(@$GLOBALS['connect_id_auteur']);
	return $b
	  . "op="
	  . $op
	  . "&id="
	  . $id
	  . "&cle="
	  . afficher_low_sec($id, "$mime $op $a")
	  . (!$a ? '' : "&args=$a")
	  . (!$lang ? '' : "&lang=$lang");
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
	sql_updateq("spip_auteurs", array("low_sec" => ''), "id_auteur = $id_auteur");
}

// http://doc.spip.org/@initialiser_sel
function initialiser_sel() {
	global $htsalt;
	if (CRYPT_MD5) $htsalt = '$1$'.creer_pass_aleatoire();
	else return "";
}

// Cette fonction ne sert qu'a la connexion en mode http_auth.non LDAP
// Son role est de creer le fichier htpasswd
// Voir le plugin "acces restreint"
// http://doc.spip.org/@ecrire_acces
function ecrire_acces() {
	$htaccess = _DIR_RESTREINT . _ACCESS_FILE_NAME;
	$htpasswd = _DIR_TMP . _AUTH_USER_FILE;

	// Cette variable de configuration peut etre posee par un plugin
	// par exemple acces_restreint ;
	// si .htaccess existe, outrepasser spip_meta
	if (($GLOBALS['meta']['creer_htpasswd'] != 'oui')
	AND !@file_exists($htaccess)) {
		spip_unlink($htpasswd);
		spip_unlink($htpasswd."-admin");
		return;
	}

	# remarque : ici on laisse passer les "nouveau" de maniere a leur permettre
	# de devenir redacteur le cas echeant (auth http)... a nettoyer
	// attention, il faut au prealable se connecter a la base (necessaire car utilise par install)
	// TODO: factoriser avec auth/spip qui fait deja ce job et generaliser le test spip_connect_ldap()

	if (spip_connect_ldap()) return;
	$p1 = ''; // login:htpass pour tous
	$p2 = ''; // login:htpass pour les admins
	$s = sql_select("login, htpass, statut", "spip_auteurs", sql_in("statut",  array('1comite','0minirezo','nouveau')));
	while ($t = sql_fetch($s)) {
		if (strlen($t['login']) AND strlen($t['htpass'])) {
			$p1 .= $t['login'].':'.$t['htpass']."\n";
			if ($t['statut'] == '0minirezo')
				$p2 .= $t['login'].':'.$t['htpass']."\n";
		}
	}
	if ($p1) {
	  ecrire_fichier($htpasswd, $p1);
	  ecrire_fichier($htpasswd.'-admin', $p2);
	  spip_log("Ecriture de $htpasswd et $htpasswd-admin");
	}
}


// http://doc.spip.org/@generer_htpass
function generer_htpass($pass) {
	global $htsalt;
	if (function_exists('crypt'))
		return crypt($pass, $htsalt);
}

//
// Installe ou verifie un .htaccess, y compris sa prise en compte par Apache
//
// http://doc.spip.org/@verifier_htaccess
function verifier_htaccess($rep, $force=false) {
	$htaccess = rtrim($rep,"/") . "/" . _ACCESS_FILE_NAME;
	if (((@file_exists($htaccess)) OR defined('_TEST_DIRS')) AND !$force)
		return true;
	if ($ht = @fopen($htaccess, "w")) {
		fputs($ht, "deny from all\n");
		fclose($ht);
		@chmod($htaccess, _SPIP_CHMOD & 0666);
		$t = rtrim($rep,"/") . "/.ok";
		if ($ht = @fopen($t, "w")) {
			@fclose($ht);
			include_spip('inc/distant');
			$t = substr($t,strlen(_DIR_RACINE));
			$t = url_de_base() . $t;
			$ht = recuperer_lapage($t, false, 'HEAD', 0);
			// htaccess inoperant si on a recupere des entetes HTTP
			// (ignorer la reussite si connexion par fopen)
			$ht = !(isset($ht[0]) AND $ht[0]);
		}
	}
	spip_log("Creation de $htaccess " . ($ht ? " reussie" : " manquee"));
	return $ht;
}	



// http://doc.spip.org/@gerer_htaccess
function gerer_htaccess() {
	// Cette variable de configuration peut etre posee par un plugin
	// par exemple acces_restreint
	$f = ($GLOBALS['meta']['creer_htaccess'] === 'oui');
	$dirs = sql_allfetsel('extension', 'spip_types_documents');
	$dirs[] = array('extension' => 'distant');
	foreach($dirs as $e) {
		if (is_dir($dir = _DIR_IMG . $e['extension'])) {
			if ($f)
				verifier_htaccess($dir);
			else spip_unlink($dir . '/' . _ACCESS_FILE_NAME);
		}
	}
	return $GLOBALS['meta']['creer_htaccess'];
}

initialiser_sel();

?>
