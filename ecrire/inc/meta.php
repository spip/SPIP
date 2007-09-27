<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// Les parametres generaux du site sont dans une table SQL;
// Recopie dans le tableau PHP global meta, car on en a souvent besoin

function init_metas()
{
	// Lire les meta, en cache si present, valide et lisible
	if (@filemtime(_FILE_META) AND lire_fichier(_FILE_META, $meta))
		$GLOBALS['meta'] = @unserialize($meta);
	// sinon le refaire.
	if (!$GLOBALS['meta']) {
		if (lire_metas())
			ecrire_fichier(_FILE_META,
				       serialize($GLOBALS['meta']));
	}
}

// http://doc.spip.org/@lire_metas
function lire_metas() {
	if (!_FILE_CONNECT && !@file_exists(_FILE_CONNECT_INS .'.php'))
		return false;
	if ($result = @spip_query("SELECT nom,valeur FROM spip_meta")) {

		$GLOBALS['meta'] = array();
		while ($row = sql_fetch($result))
			$GLOBALS['meta'][$row['nom']] = $row['valeur'];

		if (!$GLOBALS['meta']['charset'])
			ecrire_meta('charset', _DEFAULT_CHARSET);
	}
	return $GLOBALS['meta'];
}

// http://doc.spip.org/@ecrire_meta
function ecrire_meta($nom, $valeur, $importable = NULL) {

	if (!$nom) return;
	$GLOBALS['meta'][$nom] = $valeur;
	if (!_FILE_CONNECT && !@file_exists(_FILE_CONNECT_INS .'.php')) return;
	include_spip('base/abstract_sql');
	$res = sql_fetsel("impt,valeur", 'spip_meta', "nom=" . _q($nom));
	// conserver la valeur de impt si existante
	// et ne pas detruire le cache si affectation a l'identique
	if ($res) {
		if ($valeur == $res['valeur']) return;
		$r = ($importable === NULL) ? ''
		: (", impt=" .  _q($importable));
		spip_query("UPDATE spip_meta SET valeur=" . _q($valeur) ."$r WHERE nom=" . _q($nom) );
	} else
		spip_query("INSERT INTO spip_meta (nom,valeur,impt) VALUES (" .  _q($nom) . "," . _q($valeur) ."," .  _q($importable) . ')');
	@touch(_FILE_META, 0);
}

// http://doc.spip.org/@effacer_meta
function effacer_meta($nom) {
	spip_query("DELETE FROM spip_meta WHERE nom='$nom'");
	@touch(_FILE_META,0);
}
?>
