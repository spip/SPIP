<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/headers');
include_spip('inc/acces');

// Mise en place des fichiers de configuration si ce n'est fait

function install_etape_fin_dist() {
	ecrire_acces();

	$f = str_replace(_FILE_TMP_SUFFIX, '.php', (string) _FILE_CHMOD_TMP);
	if (file_exists(_FILE_CHMOD_TMP) && !@rename(_FILE_CHMOD_TMP, $f) && @copy(_FILE_CHMOD_TMP, $f)) {
		spip_unlink(_FILE_CHMOD_TMP);
	}

	$f = str_replace(_FILE_TMP_SUFFIX, '.php', (string) _FILE_CONNECT_TMP);
	if (file_exists(_FILE_CONNECT_TMP)) {
		spip_log("renomme $f");
		if (!@rename(_FILE_CONNECT_TMP, $f) && @copy(_FILE_CONNECT_TMP, $f)) {
			@spip_unlink(_FILE_CONNECT_TMP);
		}
	}

	// creer le repertoire cache, qui sert partout !
	// deja fait en etape 4 en principe, on garde au cas ou
	if (!@file_exists(_DIR_CACHE)) {
		$rep = preg_replace(',' . _DIR_TMP . ',', '', (string) _DIR_CACHE);
		$rep = sous_repertoire(_DIR_TMP, $rep, true, true);
	}

	// Verifier la securite des htaccess
	// Si elle ne fonctionne pas, prevenir
	$msg = install_verifier_htaccess();
	if ($msg) {
		$cible = _T('public:accueil_site');
		$cible = generer_form_ecrire('accueil', '', '', $cible);
		$minipage = new Spip\Afficher\Minipage\Installation();
		echo $minipage->page($msg . $cible);
		// ok, deboucher dans l'espace prive
	} else {
		redirige_url_ecrire('accueil');
	}
}

function install_verifier_htaccess() {
	if (
		verifier_htaccess(_DIR_TMP, true)
		&& verifier_htaccess(_DIR_CONNECT, true)
		&& verifier_htaccess(_DIR_VENDOR, true)
	) {
		return '';
	}

	$titre = _T('htaccess_inoperant');

	$averti = _T(
		'htaccess_a_simuler',
		[
			'htaccess' => '<tt>' . _ACCESS_FILE_NAME . '</tt>',
			'constantes' => '<tt>_DIR_TMP &amp; _DIR_CONNECT</tt>',
			'document_root' => '<tt>' . $_SERVER['DOCUMENT_ROOT'] . '</tt>'
		]
	);

	return "<div class='error'><h3>$titre</h3><p>$averti</p></div>";
}
