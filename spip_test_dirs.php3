<?

include ("ecrire/inc_presentation.php3");

function bad_dirs($bad_dirs) {
		echo "
<BR><FONT FACE=\"Verdana,Arial,Helvetica,sans-serif\" SIZE=3>Pr&eacute;liminaire : <B>R&eacute;gler les droits d'acc&egrave;s</B></FONT>

<P><B>Les r&eacute;pertoires suivants ne sont pas accessibles en &eacute;criture&nbsp;: <UL>$bad_dirs.</UL> </B>

<P>Pour y rem&eacute;dier, utilisez votre client FTP afin de r&eacute;gler les droits d'acc&egrave;s de chacun
de ces r&eacute;pertoires. La proc&eacute;dure est expliqu&eacute;e en d&eacute;tail dans le guide d'installation.

<P>Une fois cette manipulation effectu&eacute;e, vous pourrez <B><A HREF='spip_test_dirs.php3'>recharger
cette page</A> afin de commencer r&eacute;ellement l'installation.";

}

//
// teste les droits sur les repertoires
//
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

		$bad_dirs = join(" ", $bad_dirs);

		install_debut_html();
		bad_dirs($bad_dirs);
		
		echo aide ("install0");
		
		install_fin_html();
		exit;
	}
	header("Location: ./ecrire/install.php3?etape1=oui");
}

?>
