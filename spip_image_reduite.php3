<?php

$img = $_GET['img'];
$logo = $img;
include ("ecrire/inc_version.php3");
include_ecrire("inc_admin.php3");

if (!$taille_y)
	$taille_y = $taille_x;
	
if (eregi("(.*)\.(jpg|gif|png)$", $logo, $regs)) {
	if ($i = cherche_image_nommee($regs[1], array($regs[2]))
	    AND verifier_action_auteur("reduire $taille_x $taille_y", $hash, $hash_id_auteur))
	{
			list($dir,$nom,$format) = $i;
		$logo = $dir . $nom . '.' . $format;
		include_ecrire("inc_logos.php3");
		$suffixe = '-'.$taille_x.'x'.$taille_y;
		$preview = creer_vignette($logo, $taille_x, $taille_y, $format,('cache'.$suffixe), $nom.$suffixe);
		if ($preview) {
			$vignette = $preview['fichier'];
			$width = $preview['width'];
			$height = $preview['height'];
			//echo "<img src='$vignette' name='$name' border='0' align='$align' alt='' hspace='$espace' vspace='$espace' width='$width' height='$height' class='spip_logos' />";
			$retour = $vignette;
		}
		else if ($taille_origine = getimagesize($logo)) {
			//list ($destWidth,$destHeight) = image_ratio($taille_origine[0], $taille_origine[1], $taille_x, $taille_y);
			$retour = $logo;
		}	

	// Afficher l'image resultante, meme grande...
//	header("Content-type: image/$format");
//	echo implode ('', file ($retour));

	header("Location: $retour");
	}
 }

?>