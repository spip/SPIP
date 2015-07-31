<?

include ("inc_connect.php3");
include_local ("inc_auth.php3");
include_local ("inc_admin.php3");


debut_admin("accents");

function changer_accents($dir) {

	$handle = opendir($dir);
	while ($nomfich = readdir($handle)) {
		if (ereg('\.', $nomfich) && !ereg('(\.php3|\.html)$', $nomfich)) continue;
		if ($nomfich == 'accents.php3') continue;
		if ($nomfich == 'inc_texte.php3') continue;
		if ($nomfich == 'inc_index.php3') continue;
		if ($nomfich == 'inc_mail.php3') continue;
		$nomfich = "$dir/$nomfich";
		$fichier = @file("$nomfich");

		if ($fichier) {
			echo "$nomfich<br>";
			$fichier = join('', $fichier);
			$fichier = ereg_replace('é', '&eacute;', $fichier);
			$fichier = ereg_replace('É', '&Eacute;', $fichier);

			$fichier = ereg_replace('à', '&agrave;', $fichier);
			$fichier = ereg_replace('è', '&egrave;', $fichier);
			$fichier = ereg_replace('ù', '&ugrave;', $fichier);
			$fichier = ereg_replace('À', '&Agrave;', $fichier);
			$fichier = ereg_replace('È', '&Egrave;', $fichier);
			$fichier = ereg_replace('Ù', '&Ugrave;', $fichier);

			$fichier = ereg_replace('â', '&acirc;', $fichier);
			$fichier = ereg_replace('ê', '&ecirc;', $fichier);
			$fichier = ereg_replace('î', '&icirc;', $fichier);
			$fichier = ereg_replace('ô', '&ocirc;', $fichier);
			$fichier = ereg_replace('û', '&ucirc;', $fichier);
			$fichier = ereg_replace('Â', '&Acirc;', $fichier);
			$fichier = ereg_replace('Ê', '&Ecirc;', $fichier);
			$fichier = ereg_replace('Î', '&Icirc;', $fichier);
			$fichier = ereg_replace('Ô', '&Ocirc;', $fichier);
			$fichier = ereg_replace('Û', '&Ucirc;', $fichier);

			$fichier = ereg_replace('ç', '&ccedil;', $fichier);
			$fichier = ereg_replace('Ç', '&Ccedil;', $fichier);

			$fichier = ereg_replace('«', '&laquo;', $fichier);
			$fichier = ereg_replace('»', '&raquo;', $fichier);

	
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