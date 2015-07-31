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

// http://doc.spip.org/@lire_metas
function lire_metas() {
	if (!_FILE_CONNECT && !@file_exists(_FILE_CONNECT_INS .'.php')) return;
	if ($result = @spip_query("SELECT nom,valeur FROM spip_meta")) {

		$GLOBALS['meta'] = array();
		while ($row = spip_fetch_array($result))
			$GLOBALS['meta'][$row['nom']] = $row['valeur'];

		if (!$GLOBALS['meta']['charset'])
			ecrire_meta('charset', _DEFAULT_CHARSET);
	}
}

// http://doc.spip.org/@ecrire_meta
function ecrire_meta($nom, $valeur, $importable = NULL) {
	if (strlen($nom)){
		$GLOBALS['meta'][$nom] = $valeur; 
		if (!_FILE_CONNECT && !@file_exists(_FILE_CONNECT_INS .'.php')) return;
		// conserver la valeur de impt si existante
		if ($importable === NULL){
			$res = spip_query("SELECT * FROM spip_meta WHERE nom="._q($nom));
			if (@spip_num_rows($res))
				spip_query("UPDATE spip_meta SET valeur=" . _q($valeur) . " WHERE nom="._q($nom));
			else
				spip_query("INSERT spip_meta (nom, valeur) VALUES ("._q($nom).", " . _q($valeur) . ")");
		}
		else
			spip_query("REPLACE spip_meta (nom, valeur, impt) VALUES ("._q($nom).", " . _q($valeur) . ","._q($importable).")");
	}
}

// http://doc.spip.org/@effacer_meta
function effacer_meta($nom) {
	spip_query("DELETE FROM spip_meta WHERE nom='$nom'");
}

//
// Mettre a jour le fichier cache des metas
//
// Ne pas oublier d'appeler cette fonction apres ecrire_meta() et effacer_meta() !
//
// http://doc.spip.org/@ecrire_metas
function ecrire_metas() {
	if (!_FILE_CONNECT && !@file_exists(_FILE_CONNECT_INS .'.php')) return;

	lire_metas();

	if (is_array($GLOBALS['meta'])) {

		if (_DIR_RESTREINT && is_array($GLOBALS['noyau']))
			$GLOBALS['meta']['noyau'] = $GLOBALS['noyau'];

		$ok = ecrire_fichier (_FILE_META, serialize($GLOBALS['meta']));
		if (!$ok && $GLOBALS['connect_statut'] == '0minirezo') {
			include_spip('inc/headers');
			include_spip('inc/minipres');
			echo minipres(_T('texte_inc_meta_2'), "<h4 style='color: red'>"
			. _T('texte_inc_meta_1', array('fichier' => _FILE_META))
			. " <a href='" . generer_test_dirs() . "'>"
			. _T('texte_inc_meta_2')
			. "</a> "
			. _T('texte_inc_meta_3',
				array('repertoire' => joli_repertoire(_DIR_TMP)))
			. "</h4>\n");
			exit;
		}
	}
}
?>
