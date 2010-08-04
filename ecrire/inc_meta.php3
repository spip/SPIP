<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_META")) return;
define("_ECRIRE_INC_META", "1");

function lire_metas() {
	global $meta, $meta_maj;

	$meta = '';
	$meta_maj = '';
	$query = 'SELECT nom,valeur,UNIX_TIMESTAMP(maj) AS d FROM spip_meta';
	$result = spip_query($query);
	while ($row = spip_fetch_array($result)) {
		$nom = $row['nom'];
		$meta[$nom] = $row['valeur'];
		$meta_maj[$nom] = $row['d'];
	}
}

function ecrire_meta($nom, $valeur) {
	$valeur = addslashes($valeur);
	spip_query("REPLACE spip_meta (nom, valeur) VALUES ('$nom', '$valeur')");
}

function effacer_meta($nom) {
	spip_query("DELETE FROM spip_meta WHERE nom='$nom'");
}

//
// Mettre a jour le fichier cache des metas
//
// Ne pas oublier d'appeler cette fonction apres ecrire_meta() et effacer_meta() !
//
function ecrire_metas() {
	global $meta, $meta_maj;

	lire_metas();

	$s = '';

	if ($meta) {
		reset($meta);
		while (list($key, $val) = each($meta)) {
			$key = addslashes($key);
			$val = preg_replace(",([\\\\']),", "\\\\1", $val);
			$s .= "\$GLOBALS['meta']['$key'] = '$val';\n";
		}
		$s .= "\n";
	}
	if ($meta_maj) {
		reset($meta_maj);
		while (list($key, $val) = each($meta_maj)) {
			$key = addslashes($key);
			$s .= "\$GLOBALS['meta_maj']['$key'] = '$val';\n";
		}
		$s .= "\n";
	}

	if ($s) {
		$ok = ecrire_fichier (_DIR_SESSIONS . 'meta_cache.php3',
				      '<'.'?php

if (defined("_DATA_META_CACHE")) return;
define("_DATA_META_CACHE", "1");
' 
				      . $s . '?'.'>');
		if (!$ok && $GLOBALS['connect_statut'] == '0minirezo')
		  echo "<h4 font color=red>"._T('texte_inc_meta_1')." <a href='../spip_test_dirs.php3'>"._T('texte_inc_meta_2')."</a> "._T('texte_inc_meta_3')."&nbsp;</h4>\n";
	}
}

// On force lire_metas() si le cache n'a pas ete utilise
if (!isset($GLOBALS['meta']))
	lire_metas();

?>
