<?php

include ("ecrire/inc_version.php3");
include_ecrire("inc_charsets.php3");

 
 function printWordWrapped($image, $top, $left, $maxWidth, $font, $color, $text, $textSize, $align="left") {
               $words = explode(' ', strip_tags($text)); // split the text into an array of single words
               $line = '';
               while (count($words) > 0) {
                       $dimensions = imagettfbbox($textSize, 0, $font, $line.' '.$words[0]);
                       $lineWidth = $dimensions[2] - $dimensions[0]; // get the length of this line, if the word is to be included
                       if ($lineWidth > $maxWidth) { // if this makes the text wider that anticipated
                               $lines[] = $line; // add the line to the others
                               $line = ''; // empty it (the word will be added outside the loop)
                               }
                       $line .= ' '.$words[0]; // add the word to the current sentence
                       $words = array_slice($words, 1); // remove the word from the array
                       }
               if ($line != '') { $lines[] = $line; } // add the last line to the others, if it isn't empty
               $lineHeight = floor($textSize * 1.3);
               $height = count($lines) * $lineHeight; // the height of all the lines total
               // do the actual printing
               $i = 0;
               foreach ($lines as $line) {
               		$line = ereg_replace("~", " ", $line);
                       $dimensions = imagettfbbox($textSize, 0, $font, $line);
                       $largeur_ligne = $dimensions[2] - $dimensions[0];
                       if ($largeur_ligne > $largeur_max) $largeur_max = $largeur_ligne;
                       if ($align == "right") $left_pos = $maxWidth - $largeur_ligne;
                       else if ($align == "center") $left_pos = floor(($maxWidth - $largeur_ligne)/2);
                       else $left_pos = 0;
                       imagettftext($image, $textSize, 0, $left + $left_pos, $top + $lineHeight * $i, $color, $font, trim($line));
                       $i++;
                       }
               $retour["height"] = $height;
               $retour["width"] = $largeur_max;
                 
               $dimensions_espace = imagettfbbox($textSize, 0, $font, ' ');
               $largeur_espace = $dimensions_espace[2] - $dimensions_espace[0];
               $retour["espace"] = $largeur_espace;
             return $retour;
        }

// Définition du content-type
header("Content-type: image/png");

$query = md5($QUERY_STRING);

$dossier = _DIR_IMG. creer_repertoire(_DIR_IMG, 'cache-texte');

$fichier = "$dossier/$query.png";


if (!file_exists($fichier)) {
	// Création de l'image
	$text= $_GET["texte"];
	
	$text = ereg_replace("\&nbsp;", "~", $text);	

	$taille = $_GET["taille"];
	if ($taille < 1) $taille = 16;
	
	$couleur = $_GET["couleur"];
	if (strlen($couleur) < 6) $couleur = "000000";
	
	$fond = $_GET["fond"];
	if (strlen($fond) < 6) $fond = "ffffff";
	
	$ombre = $_GET["ombre"];
	$ombrex = $_GET["ombrex"];
	$ombrey = $_GET["ombrey"];
	if (!$_GET["ombrex"]) $ombrex = 1;
	if (!$_GET["ombrey"]) $ombrey = $ombrex;
	
	$align = $_GET["align"];
	if (!$_GET["align"]) $align="left";
	
	
	$police = $_GET["police"];
	if (strlen($police) < 2) $police = "dustismo.ttf";
	
	// Il faut completer avec un vrai _SPIP_PATH, de facon a pouvoir livrer des /polices dans les dossiers de squelettes
	$font = find_in_path("polices/$police", "ecrire");

	$largeur = $_GET["largeur"];
	if ($largeur < 5) $largeur = 600;
	
	$dir = $_GET["dir"];
	
	$imgbidon = imageCreateTrueColor($largeur, 45);
	$retour = printWordWrapped($imgbidon, $taille+5, 0, $largeur, $font, $black, $text, $taille);
	$hauteur = $retour["height"];
	$largeur = $retour["width"];
	$espace = $retour["espace"];
	imagedestroy($imgbidon);
	
	$im = imageCreateTrueColor($largeur+$ombrex-$espace, $hauteur+5+$ombrey);
	imagealphablending ($im, FALSE );
	imagesavealpha ( $im, TRUE );
	
	// Création de quelques couleurs
	if (strlen($ombre) == 6) $grey = imagecolorallocatealpha($im, hexdec("0x{".substr($ombre, 0,2)."}"), hexdec("0x{".substr($ombre, 2,2)."}"), hexdec("0x{".substr($ombre, 4,2)."}"), 50);
	$black = imagecolorallocatealpha($im, hexdec("0x{".substr($couleur, 0,2)."}"), hexdec("0x{".substr($couleur, 2,2)."}"), hexdec("0x{".substr($couleur, 4,2)."}"), 0);
	$grey2 = imagecolorallocatealpha($im, hexdec("0x{".substr($fond, 0,2)."}"), hexdec("0x{".substr($fond, 2,2)."}"), hexdec("0x{".substr($fond, 4,2)."}"), 127);
	
	ImageFilledRectangle ($im,0,0,$largeur+$ombrex,$hauteur+5+$ombrey,$grey2);
	
	// Le texte à dessiner
	// Remplacez le chemin par votre propre chemin de police
	//global $text;
	
	
	if (strlen($ombre) == 6) printWordWrapped($im, $taille+$ombrey+5, $ombrex, $largeur, $font, $grey, $text, $taille, $align);
	printWordWrapped($im, $taille+5, 0, $largeur, $font, $black, $text, $taille, $align);
	
	
	// Utiliser imagepng() donnera un texte plus claire,
	// comparé à l'utilisation de la fonction imagejpeg()
	imagepng($im, $fichier);
	imagedestroy($im);
}

echo join(file($fichier),'');


?> 