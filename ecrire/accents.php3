<?php

include ("inc_connect.php3");
include_ecrire ("inc_auth.php3");
include_ecrire ("inc_admin.php3");


debut_admin("accents");

function changer_accents($dir) {

	$handle = opendir($dir);
	while ($nomfich = readdir($handle)) {
		if (ereg('\.', $nomfich) && !ereg('(\.php3|\.html)$', $nomfich)) continue;
		if ($nomfich == 'accents.php3') continue;
		if ($nomfich == 'inc_texte.php3') continue;
		if ($nomfich == 'inc_filtres.php3') continue;
		if ($nomfich == 'inc_index.php3') continue;
		if ($nomfich == 'inc_mail.php3') continue;
		$nomfich = "$dir/$nomfich";
		$fichier = @file("$nomfich");

		if ($fichier) {
			echo "$nomfich<br>";
			$fichier = join('', $fichier);

			$fichier = ereg_replace(chr(233), '&eacute;', $fichier);
			$fichier = ereg_replace(chr(201), '&Eacute;', $fichier);

			$fichier = ereg_replace(chr(224), '&agrave;', $fichier);
			$fichier = ereg_replace(chr(232), '&egrave;', $fichier);
			$fichier = ereg_replace(chr(249), '&ugrave;', $fichier);
			$fichier = ereg_replace(chr(192), '&Agrave;', $fichier);
			$fichier = ereg_replace(chr(200), '&Egrave;', $fichier);
			$fichier = ereg_replace(chr(217), '&Ugrave;', $fichier);

			$fichier = ereg_replace(chr(226), '&acirc;', $fichier);
			$fichier = ereg_replace(chr(234), '&ecirc;', $fichier);
			$fichier = ereg_replace(chr(238), '&icirc;', $fichier);
			$fichier = ereg_replace(chr(244), '&ocirc;', $fichier);
			$fichier = ereg_replace(chr(251), '&ucirc;', $fichier);
			$fichier = ereg_replace(chr(194), '&Acirc;', $fichier);
			$fichier = ereg_replace(chr(202), '&Ecirc;', $fichier);
			$fichier = ereg_replace(chr(206), '&Icirc;', $fichier);
			$fichier = ereg_replace(chr(212), '&Ocirc;', $fichier);
			$fichier = ereg_replace(chr(219), '&Ucirc;', $fichier);

			$fichier = ereg_replace(chr(231), '&ccedil;', $fichier);
			$fichier = ereg_replace(chr(199), '&Ccedil;', $fichier);

			$fichier = ereg_replace(chr(171), '&laquo;', $fichier);
			$fichier = ereg_replace(chr(187), '&raquo;', $fichier);

			$fichier = eregi_replace("(face *= *['\"\\]+)[^'\"\\]*georgia[^'\"\\]*(['\"\\]+)", "\\1Georgia,Garamond,Times,serif\\2", $fichier);
			$fichier = eregi_replace("(face *= *['\"\\]+)[^'\"\\]*verdana[^'\"\\]*(['\"\\]+)", "\\1Verdana,Arial,Helvetica,sans-serif\\2", $fichier);

			$f = fopen($nomfich, 'wb');
			fputs($f, $fichier);
			fclose($f);
		}
	}

	closedir($handle); 
}


changer_accents(".");
changer_accents("AIDE");


fin_admin("accents");

?>