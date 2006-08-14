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

define("_ECRIRE_INSTALL", "1");
define('_FILE_TMP', '_install');

// http://doc.spip.org/@exec_install_dist
function exec_install_dist()
{
	global $etape;
	if (_FILE_CONNECT && $etape != 'unpack') 
		minipres(_T('avis_espace_interdit'));
	else {

	// On va supprimer les eventuelles vieilles valeurs de meta,
	// mais il faut relancer init_langues pour savoir quelles
	// sont les langues disponibles pour l'installation
	@unlink(_FILE_META);
	unset($GLOBALS['meta']);
	include_spip('inc/lang');
	init_langues();

	include_spip('base/create');
	include_spip('base/db_mysql');

	$fonc = charger_fonction("install_$etape", 'inc');
	$fonc();
	}
}

//
// Verifier que l'hebergement est compatible SPIP ... ou l'inverse :-)
// (sert a l'etape 1 de l'installation)
// http://doc.spip.org/@tester_compatibilite_hebergement
function tester_compatibilite_hebergement() {
	$err = array();

	$p = phpversion();
	if (ereg('^([0-9]+)\.([0-9]+)\.([0-9]+)', $p, $regs)) {
		$php = array($regs[1], $regs[2], $regs[3]);
		$m = '4.0.8';
		$min = explode('.', $m);
		if ($php[0]<$min[0]
		OR ($php[0]==$min[0] AND $php[1]<$min[1])
		OR ($php[0]==$min[0] AND $php[1]==$min[1] AND $php[2]<$min[2]))
			$err[] = _L("PHP version $p insuffisant (minimum = $m)");
	}

	if (!function_exists('mysql_query'))
		$err[] = _T('install_extension_php_obligatoire')
		. " <a href='http://se.php.net/mysql'>MYSQL</a>";

	if (!function_exists('preg_match_all'))
		$err[] = _T('install_extension_php_obligatoire')
		. " <a href='http://se.php.net/pcre'>PCRE</a>";

	if ($a = @ini_get('mbstring.func_overload'))
		$err[] = _T('install_extension_mbstring')
		. "mbstring.func_overload=$a - <a href='http://se.php.net/mb_string'>mb_string</a>.<br /><small>";

	if ($err) {
			echo "<P><FONT FACE='Verdana,Arial,Sans,sans-serif' SIZE=4><b>"._T('avis_attention').'</b> <p>'._T('install_echec_annonce')."</p></FONT>";
		while (list(,$e) = each ($err))
			echo "<li>$e</li>\n";

		# a priori ici on pourrait die(), mais il faut laisser la possibilite
		# de forcer malgre tout (pour tester, ou si bug de detection)
		echo "<p /><hr />\n";
	}
}


// Une fonction pour faciliter la recherche du login (superflu ?)
// http://doc.spip.org/@login_hebergeur
function login_hebergeur() {
	global $HTTP_X_HOST, $REQUEST_URI, $SERVER_NAME, $HTTP_HOST;

	$base_hebergeur = 'localhost'; # par defaut

	// Lycos (ex-Multimachin)
	if ($HTTP_X_HOST == 'membres.lycos.fr') {
		ereg('^/([^/]*)', $REQUEST_URI, $regs);
		$login_hebergeur = $regs[1];
	}
	// Altern
	else if (ereg('altern\.com$', $SERVER_NAME)) {
		ereg('([^.]*\.[^.]*)$', $HTTP_HOST, $regs);
		$login_hebergeur = ereg_replace('[^a-zA-Z0-9]', '_', $regs[1]);
	}
	// Free
	else if (ereg('(.*)\.free\.fr$', $SERVER_NAME, $regs)) {
		$base_hebergeur = 'sql.free.fr';
		$login_hebergeur = $regs[1];
	}

	return array($base_hebergeur, $login_hebergeur);
}

?>
