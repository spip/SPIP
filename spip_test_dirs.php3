<?

function aide ($aide) {
	return " <P ALIGN='right'><FONT SIZE=2>[<B><A HREF='#' onMouseDown=\"window.open('ecrire/aide_index.php3?aide=$aide','myWindow','scrollbars=yes,resizable=yes,width=550')\">AIDE</A></B>]</FONT>";
}

if (!file_exists("inc_connect.php3")){
	$test_dirs = array("CACHE", "IMG", "ecrire", "ecrire/data", "ecrire/upload");
	unset($bad_dirs);
	while (list(, $my_dir) = each($test_dirs)) {
		$ok = true;
		$nom_fich = "$my_dir/test.txt";
		$f = @fopen($nom_fich, "w");
		if (!$f) $ok = false;
		else if (!@fclose($f)) $ok = false;
		else if (!@unlink($nom_fich)) $ok = false;
		
		if (!$ok) $bad_dirs[] = "<LI>".$my_dir;
	}
	if ($bad_dirs) {
		include ("ecrire/inc_install.php3");

		$bad_dirs = join(" ", $bad_dirs);

		debut_html();
		bad_dirs($bad_dirs);
		
		echo aide ("install0");
		
		fin_html();
		exit;
	}
	header("Location: ./ecrire/install.php3?etape1=oui");
}

?>
