<?php

//$img = "IMG/rubon0.png"; 
$img = $_GET['img'];
$logo = $img;

if (!$taille_y)
	$taille_y = $taille_x;
	
	
include ("ecrire/inc_version.php3");
include_local("inc-public-global.php3");
include_ecrire("inc_admin.php3");

if (ereg("^../",$logo))
	$logo = substr($logo,3);
	
if (ereg("^" . _DIR_IMG, $logo)) {
	$img = substr($logo,strlen(_DIR_IMG));
}
else { $img = $logo; $logo = _DIR_IMG . $logo;}

if (@file_exists($logo) 
	AND eregi("^(.*)\.(jpg|gif|png)$", $img, $regs) 
	AND verifier_action_auteur("reduire $taille_x $taille_y", $hash, $hash_id_auteur)
) {

	include_ecrire("inc_logos.php3");
		$nom = $regs[1];
		$format = $regs[2];
		$suffixe = '-'.$taille_x.'x'.$taille_y;
		$cache_folder=  _DIR_IMG . creer_repertoire(_DIR_IMG, 'cache'.$suffixe);
		$preview = creer_vignette($logo, $taille_x, $taille_y, $format, $cache_folder.$nom.$suffixe);

		if ($preview) {
			$vignette = $preview['fichier'];
			$width = $preview['width'];
			$height = $preview['height'];
			//echo "<img src='$vignette' name='$name' border='0' align='$align' alt='' hspace='$espace' vspace='$espace' width='$width' height='$height' class='spip_logos' />";
			$retour = $vignette;
		}
		else if ($taille_origine = getimagesize($logo)) {
			list ($destWidth,$destHeight) = image_ratio($taille_origine[0], $taille_origine[1], $taille_x, $taille_y);
			//echo "<img src='$logo' name='$name' width='$destWidth' height='$destHeight' border='0' align='$align' alt='' hspace='$espace' vspace='$espace' class='spip_logos' />";
			$retour = $logo;
		}

	// Afficher l'image resultante, meme grande...
//	header("Content-type: image/$format");
//	echo implode ('', file ($retour));

	header("Location: $retour");


}

?>