<?php
/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// librairie de base du core
include_spip('inc/filtres_images_mini');


// Image typographique
// Fonctions pour l'arabe

// http://doc.spip.org/@rtl_mb_ord
function rtl_mb_ord($char){

	if (($c = ord($char)) < 216) return $c;
	else return 256 * rtl_mb_ord(substr($char, 0, -1)) + ord(substr($char, -1));

	/*	return (strlen($char) < 2) ?
			ord($char) : 256 * mb_ord(substr($char, 0, -1))
				+ ord(substr($char, -1));
				
	*/			
}


// http://doc.spip.org/@rtl_reverse
function rtl_reverse($mot, $rtl_global) {
	$rtl_prec = $rtl_global;

	$ponctuations = array("/", "-", "«","»", "“", "”", ",", ".", " ", ":", ";", "(", ")", "،", "؟", "?", "!", " ");
	foreach($ponctuations as $ponct) {
		$ponctuation[$ponct] = true;
	}

		

	for ($i = 0; $i < spip_strlen($mot); $i++) {
		$lettre = spip_substr($mot, $i, 1);
		
		$code = rtl_mb_ord($lettre);
		# echo "<li>$lettre - $code";

		if (($code >= 54928 && $code <= 56767) ||  ($code >= 15707294 && $code <= 15711164)) {
			$rtl = true;
		}
		else $rtl = false;
		
		if ($lettre == "٠" || $lettre == "١" || $lettre == "٢" || $lettre == "٣" || $lettre == "٤" || $lettre == "٥"
				 || $lettre == "٦" || $lettre == "٧" || $lettre == "٨" || $lettre == "٩") $rtl = false;
		
		if ($ponctuation[$lettre]) {
			# le truc mega casse-gueule de l'inversion unicode:
			# traiter le sens de placement en fonction de la lettre precedente
			# (et non automatiquement le rtl_global)
			$rtl = $rtl_prec;
			
			if ($rtl) {
				switch ($lettre) {
					case "(": $lettre = ")"; break;
					case ")": $lettre = "("; break;
					case "«": $lettre = "»"; break;
					case "»": $lettre = "«"; break;
					case "“": $lettre = "”"; break;
					case "”": $lettre = "“"; break;
				}
			}
		}
		
		
		if ($rtl) $res = $lettre.$res;
		else $res = $res.$lettre;
		
		$rtl_prec = $rtl;
		
	}
	return $res;
}



// http://doc.spip.org/@rtl_visuel
function rtl_visuel($texte, $rtl_global) {
	// hebreu + arabe: 54928 => 56767
	// hebreu + presentation A: 15707294 => 15710140
	// arabe presentation: 15708336 => 15711164
	
	#	echo hexdec("efb7bc");

	// premiere passe pour determiner s'il y a du rtl
	// de facon a placer ponctuation et mettre les mots dans l'ordre


	$arabic_letters = array(
		array("ي", // lettre 0
			"ﻱ",  // isolee 1
			"ﻳ", // debut 2
			"ﻴ", // milieu 3
			"ﻲ"),
		array("ب", // lettre 0
			"ﺏ",  // isolee 1
			"ﺑ", // debut 2
			"ﺒ", // milieu 3
			"ﺐ"),
		array("ا", // lettre 0
			"ا",  // isolee 1
			"ﺍ", // debut 2
			"ﺍ", // milieu 3
			"ﺎ"),
		array("إ", // lettre 0
			"إ",  // isolee 1
			"إ", // debut 2
			"ﺈ", // milieu 3
			"ﺈ"),
		array("ل", // lettre 0
			"ﻝ",  // isolee 1
			"ﻟ", // debut 2
			"ﻠ", // milieu 3
			"ﻞ"),
		array("خ", // lettre 0
			"ﺥ",  // isolee 1
			"ﺧ", // debut 2
			"ﺨ", // milieu 3
			"ﺦ"),
		array("ج", // lettre 0
			"ﺝ",  // isolee 1
			"ﺟ", // debut 2
			"ﺠ", // milieu 3
			"ﺞ"),
		array("س", // lettre 0
			"ﺱ",  // isolee 1
			"ﺳ", // debut 2
			"ﺴ", // milieu 3
			"ﺲ"),
		array("ن", // lettre 0
			"ﻥ",  // isolee 1
			"ﻧ", // debut 2
			"ﻨ", // milieu 3
			"ﻦ"),
		array("ش", // lettre 0
			"ﺵ",  // isolee 1
			"ﺷ", // debut 2
			"ﺸ", // milieu 3
			"ﺶ"),
		array("ق", // lettre 0
			"ﻕ",  // isolee 1
			"ﻗ", // debut 2
			"ﻘ", // milieu 3
			"ﻖ"),
		array("ح", // lettre 0
			"ﺡ",  // isolee 1
			"ﺣ", // debut 2
			"ﺤ", // milieu 3
			"ﺢ"),
		array("م", // lettre 0
			"ﻡ",  // isolee 1
			"ﻣ", // debut 2
			"ﻤ", // milieu 3
			"ﻢ"),
		array("ر", // lettre 0
			"ر",  // isolee 1
			"ﺭ", // debut 2
			"ﺮ", // milieu 3
			"ﺮ"),
		array("ع", // lettre 0
			"ع",  // isolee 1
			"ﻋ", // debut 2
			"ﻌ", // milieu 3
			"ﻊ"),
		array("و", // lettre 0
			"و",  // isolee 1
			"ﻭ", // debut 2
			"ﻮ", // milieu 3
			"ﻮ"),
		array("ة", // lettre 0
			"ة",  // isolee 1
			"ة", // debut 2
			"ﺔ", // milieu 3
			"ﺔ"),
		array("ف", // lettre 0
			"ﻑ",  // isolee 1
			"ﻓ", // debut 2
			"ﻔ", // milieu 3
			"ﻒ"),
		array("ﻻ", // lettre 0
			"ﻻ",  // isolee 1
			"ﻻ", // debut 2
			"ﻼ", // milieu 3
			"ﻼ"),
		array("ح", // lettre 0
			"ﺡ",  // isolee 1
			"ﺣ", // debut 2
			"ﺤ", // milieu 3
			"ﺢ"),
		array("ت", // lettre 0
			"ﺕ",  // isolee 1
			"ﺗ", // debut 2
			"ﺘ", // milieu 3
			"ﺖ"),
		array("ض", // lettre 0
			"ﺽ",  // isolee 1
			"ﺿ", // debut 2
			"ﻀ", // milieu 3
			"ﺾ"),
		array("ك", // lettre 0
			"ك",  // isolee 1
			"ﻛ", // debut 2
			"ﻜ", // milieu 3
			"ﻚ"),
		array("ه", // lettre 0
			"ﻩ",  // isolee 1
			"ﻫ", // debut 2
			"ﻬ", // milieu 3
			"ﻪ"),
		array("ي", // lettre 0
			"ي",  // isolee 1
			"ﻳ", // debut 2
			"ﻴ", // milieu 3
			"ﻲ"),
		array("ئ", // lettre 0
			"ﺉ",  // isolee 1
			"ﺋ", // debut 2
			"ﺌ", // milieu 3
			"ﺊ"),
		array("ص", // lettre 0
			"ﺹ",  // isolee 1
			"ﺻ", // debut 2
			"ﺼ", // milieu 3
			"ﺺ"),
		array("ث", // lettre 0
			"ﺙ",  // isolee 1
			"ﺛ", // debut 2
			"ﺜ", // milieu 3
			"ﺚ"),
		array("ﻷ", // lettre 0
			"ﻷ",  // isolee 1
			"ﻷ", // debut 2
			"ﻸ", // milieu 3
			"ﻸ"),
		array("د", // lettre 0
			"ﺩ",  // isolee 1
			"ﺩ", // debut 2
			"ﺪ", // milieu 3
			"ﺪ"),
		array("ذ", // lettre 0
			"ﺫ",  // isolee 1
			"ﺫ", // debut 2
			"ﺬ", // milieu 3
			"ﺬ"),
		array("ط", // lettre 0
			"ﻁ",  // isolee 1
			"ﻃ", // debut 2
			"ﻄ", // milieu 3
			"ﻂ"),
		array("آ", // lettre 0
			"آ",  // isolee 1
			"آ", // debut 2
			"ﺂ", // milieu 3
			"ﺂ"),
		array("أ", // lettre 0
			"أ",  // isolee 1
			"أ", // debut 2
			"ﺄ", // milieu 3
			"ﺄ"),
		array("ؤ", // lettre 0
			"ؤ",  // isolee 1
			"ؤ", // debut 2
			"ﺆ", // milieu 3
			"ﺆ"),
		array("ز", // lettre 0
			"ز",  // isolee 1
			"ز", // debut 2
			"ﺰ", // milieu 3
			"ﺰ"),
		array("ظ", // lettre 0
			"ظ",  // isolee 1
			"ﻇ", // debut 2
			"ﻈ", // milieu 3
			"ﻆ"),
		array("غ", // lettre 0
			"غ",  // isolee 1
			"ﻏ", // debut 2
			"ﻐ", // milieu 3
			"ﻎ"),
		array("ى", // lettre 0
			"ى",  // isolee 1
			"ﯨ", // debut 2
			"ﯩ", // milieu 3
			"ﻰ"),
		array("پ", // lettre 0
			"پ",  // isolee 1
			"ﭘ", // debut 2
			"ﭙ", // milieu 3
			"ﭗ"),
		array("چ", // lettre 0
			"چ",  // isolee 1
			"ﭼ", // debut 2
			"ﭽ", // milieu 3
			"ﭻ")
	);
	
	if(init_mb_string() AND mb_regex_encoding() !== "UTF-8") echo "Attention: dans php.ini, il faut indiquer:<br /><strong>mbstring.internal_encoding = UTF-8</strong>";
	
	
	$texte = explode(" ", $texte);
	
	foreach ($texte as $mot) {
		$res = "";

		// Inserer des indicateurs de debut/fin
		$mot = "^".$mot."^";

		$mot = preg_replace(",&nbsp;,u", " ", $mot);
		$mot = preg_replace(",&#171;,u", "«", $mot);
		$mot = preg_replace(",&#187;,u", "»", $mot);

		// ponctuations
		$ponctuations = array("/", "-", "«","»", "“", "”", ",", ".", " ", ":", ";", "(", ")", "،", "؟", "?", "!"," ");
		foreach($ponctuations as $ponct) {
			$mot = str_replace("$ponct", "^$ponct^", $mot);
		}

		// lettres forcant coupure
		$mot = preg_replace(",ا,u", "ا^", $mot);
		$mot = preg_replace(",د,u", "د^", $mot);
		$mot = preg_replace(",أ,u", "أ^", $mot);
		$mot = preg_replace(",إ,u", "إ^", $mot);
		$mot = preg_replace(",أ,u", "أ^", $mot);
		$mot = preg_replace(",ر,u", "ر^", $mot);
		$mot = preg_replace(",ذ,u", "ذ^", $mot);
		$mot = preg_replace(",ز,u", "ز^", $mot);
		$mot = preg_replace(",و,u", "و^", $mot);
		$mot = preg_replace(",و,u", "و^", $mot);
		$mot = preg_replace(",ؤ,u", "ؤ^", $mot);
		$mot = preg_replace(",ة,u", "ة^", $mot);
		//		$mot = preg_replace(",ل,u", "^ل", $mot);
		//		$mot = preg_replace(",,", "^", $mot);


		$mot = preg_replace(",٠,u", "^٠^", $mot);
		$mot = preg_replace(",١,u", "^١^", $mot);
		$mot = preg_replace(",٢,u", "^٢^", $mot);
		$mot = preg_replace(",٣,u", "^٣^", $mot);
		$mot = preg_replace(",٤,u", "^٤^", $mot);
		$mot = preg_replace(",٥,u", "^٥^", $mot);
		$mot = preg_replace(",٦,u", "^٦^", $mot);
		$mot = preg_replace(",٧,u", "^٧^", $mot);
		$mot = preg_replace(",٨,u", "^٨^", $mot);
		$mot = preg_replace(",٩,u", "^٩^", $mot);


		// Ligatures
		$mot = preg_replace(",لا,u", "ﻻ", $mot);
		$mot = preg_replace(",لأ,u", "ﻷ", $mot);
		
		
		foreach ($arabic_letters as $a_l) {
			$mot = preg_replace(",([^\^])".$a_l[0]."([^\^]),u", "\\1".$a_l[3]."\\2", $mot);
			$mot = preg_replace(",\^".$a_l[0]."([^\^]),u", "^".$a_l[2]."\\1", $mot);
			$mot = preg_replace(",([^\^])".$a_l[0]."\^,u", "\\1".$a_l[4]."^", $mot);
			// il semble qu'il ne soit pas necessaire de remplacer
			// la lettre isolee
			//			$mot = preg_replace(",\^".$a_l[0]."\^,u", "^".$a_l[1]."^", $mot);
		}
		
				$mot = preg_replace(",\^,u", "", $mot);
		
		$res = $mot;
		$res = rtl_reverse($mot, $rtl_global);

		/*
		$rtl = false;		
		for ($i = 0; $i < spip_strlen($mot); $i++) {
			$lettre = spip_substr($mot, $i, 1);
			$code = rtl_mb_ord($lettre);
			if (($code >= 54928 && $code <= 56767) ||  ($code >= 15708336 && $code <= 15711164)) $rtl = true;
		}
		*/
		
		
		if ($rtl_global) $retour = $res . " " . $retour;
		else $retour = $retour. " ".$res;
	}
	
	
	return $retour;
}


// http://doc.spip.org/@printWordWrapped
function printWordWrapped($image, $top, $left, $maxWidth, $font, $couleur, $text, $textSize, $align="left", $hauteur_ligne = 0) {
	static $memps = array();

	// imageftbbox exige un float, et settype aime le double pour php < 4.2.0
	settype($textSize, 'double');

	// calculer les couleurs ici, car fonctionnement different selon TTF ou PS
	$black = imagecolorallocatealpha($image, hexdec("0x{".substr($couleur, 0,2)."}"), hexdec("0x{".substr($couleur, 2,2)."}"), hexdec("0x{".substr($couleur, 4,2)."}"), 0);
	$grey2 = imagecolorallocatealpha($image, hexdec("0x{".substr($couleur, 0,2)."}"), hexdec("0x{".substr($couleur, 2,2)."}"), hexdec("0x{".substr($couleur, 4,2)."}"), 127);

	// Gaffe, T1Lib ne fonctionne carrement pas bien des qu'on sort de ASCII
	// C'est dommage, parce que la rasterisation des caracteres est autrement plus jolie qu'avec TTF.
	// A garder sous le coude en attendant que ca ne soit plus une grosse bouse.
	// Si police Postscript et que fonction existe...
	if (
	false AND
	strtolower(substr($font,-4)) == ".pfb"
	AND function_exists("imagepstext")) {
		// Traitement specifique pour polices PostScript (experimental)
		$textSizePs = round(1.32 * $textSize);
		if (!$fontps = $memps["$font"]) {
			$fontps = imagepsloadfont($font);
			// Est-ce qu'il faut reencoder? Pas testable proprement, alors... 
			// imagepsencodefont($fontps,find_in_path('polices/standard.enc'));
			$memps["$font"] = $fontps;
		}
	}

	$rtl_global = false;
	for ($i = 0; $i < spip_strlen($text); $i++) {
		$lettre = spip_substr($text, $i, 1);
		$code = rtl_mb_ord($lettre);
		if (($code >= 54928 && $code <= 56767) ||  ($code >= 15707294 && $code <= 15711164)) {
			$rtl_global = true;
		}
	}


	// split the text into an array of single words
	$words = explode(' ', $text);

	// les espaces
	foreach($words as $k=>$v)
		$words[$k] = str_replace(array('~'), array(' '), $v);


	if ($hauteur_ligne == 0) $lineHeight = floor($textSize * 1.3);
	else $lineHeight = $hauteur_ligne;

	$dimensions_espace = imageftbbox($textSize, 0, $font, ' ', array());
	if ($dimensions_espace[2] < 0)
		$dimensions_espace = imageftbbox($textSize, 0, $font, $line, array());
	$largeur_espace = $dimensions_espace[2] - $dimensions_espace[0];
	$retour["espace"] = $largeur_espace;


	$line = '';
	while (count($words) > 0) {
		
		$mot = $words[0];
		
		if ($rtl_global) $mot = rtl_visuel($mot,$rtl_global);
	
		$dimensions = imageftbbox($textSize, 0, $font, $line.' '.$mot, array());
		$lineWidth = $dimensions[2] - $dimensions[0]; // get the length of this line, if the word is to be included
		if ($lineWidth > $maxWidth) { // if this makes the text wider that anticipated
			$lines[] = $line; // add the line to the others
			$line = ''; // empty it (the word will be added outside the loop)
		}
		$line .= ' '.$words[0]; // add the word to the current sentence
		$words = array_slice($words, 1); // remove the word from the array
	}
	if ($line != '') { $lines[] = $line; } // add the last line to the others, if it isn't empty
	$height = count($lines) * $lineHeight; // the height of all the lines total
	// do the actual printing
	$i = 0;

	// Deux passes pour recuperer, d'abord, largeur_ligne
	// necessaire pour alignement right et center
	foreach ($lines as $line) {
		if ($rtl_global) $line = rtl_visuel($line, $rtl_global);
		
		$dimensions = imageftbbox($textSize, 0, $font, $line, array());
		$largeur_ligne = $dimensions[2] - $dimensions[0];
		if ($largeur_ligne > $largeur_max) $largeur_max = $largeur_ligne;
	}

	foreach ($lines as $i => $line) {
		if ($rtl_global) $line = rtl_visuel($line, $rtl_global);

		$dimensions = imageftbbox($textSize, 0, $font, $line, array());
		$largeur_ligne = $dimensions[2] - $dimensions[0];
		if ($align == "right") $left_pos = $largeur_max - $largeur_ligne;
		else if ($align == "center") $left_pos = floor(($largeur_max - $largeur_ligne)/2);
		else $left_pos = 0;
		
		
		if ($fontps) {
			$line = trim($line);
			imagepstext ($image, "$line", $fontps, $textSizePs, $black, $grey2, $left + $left_pos, $top + $lineHeight * $i, 0, 0, 0, 16);
		}
		else imagefttext($image, $textSize, 0, $left + $left_pos, $top + $lineHeight * $i, $black, $font, trim($line), array());
	}
	$retour["height"] = $height;# + round(0.3 * $hauteur_ligne);
	$retour["width"] = $largeur_max;
                 
	return $retour;
}

//array imagefttext ( resource image, float size, float angle, int x, int y, int col, string font_file, string text [, array extrainfo] )
//array imagettftext ( resource image, float size, float angle, int x, int y, int color, string fontfile, string text )

// http://doc.spip.org/@produire_image_typo
function produire_image_typo() {
	/*
	arguments autorises:
	
	$texte : le texte a transformer; attention: c'est toujours le premier argument, et c'est automatique dans les filtres
	$couleur : la couleur du texte dans l'image - pas de dieze
	$police: nom du fichier de la police (inclure terminaison)
	$largeur: la largeur maximale de l'image ; attention, l'image retournee a une largeur inferieure, selon les limites reelles du texte
	$hauteur_ligne: la hauteur de chaque ligne de texte si texte sur plusieurs lignes
	(equivalent a "line-height")
	$padding: forcer de l'espace autour du placement du texte; necessaire pour polices a la con qui "depassent" beaucoup de leur boite 
	$align: alignement left, right, center
	*/



	// Recuperer les differents arguments
	$numargs = func_num_args();
	$arg_list = func_get_args();
	$texte = $arg_list[0];
	for ($i = 1; $i < $numargs; $i++) {
		if (($p = strpos($arg_list[$i], "="))!==FALSE) {
			$nom_variable = substr($arg_list[$i], 0, $p);
			$val_variable = substr($arg_list[$i], $p+1);
		
			$variable["$nom_variable"] = $val_variable;
		}
		
	}

	// Construire requete et nom fichier
	$text = str_replace("&nbsp;", "~", $texte);	
	$text = preg_replace(",(\r|\n)+,ms", " ", $text);
	include_spip('inc/charsets');
	$text = html2unicode(strip_tags($text));
	if (strlen($text) == 0) return "";

	$taille = $variable["taille"];
	if ($taille < 1) $taille = 16;

	$couleur = couleur_html_to_hex($variable["couleur"]);
	if (strlen($couleur) < 6) $couleur = "000000";

	$alt = $texte;
		
	$align = $variable["align"];
	if (!$variable["align"]) $align="left";
	 
	$police = $variable["police"];
	if (strlen($police) < 2) $police = "dustismo.ttf";

	$largeur = $variable["largeur"];
	if ($largeur < 5) $largeur = 600;

	if ($variable["hauteur_ligne"] > 0) $hauteur_ligne = $variable["hauteur_ligne"];
	else $hauteur_ligne = 0;
	if ($variable["padding"] > 0) $padding = $variable["padding"];
	else $padding = 0;
	


	$string = "$text-$taille-$couleur-$align-$police-$largeur-$hauteur_ligne-$padding";
	$query = md5($string);
	$dossier = sous_repertoire(_DIR_VAR, 'cache-texte');
	$fichier = "$dossier$query.png";

	$flag_gd_typo = function_exists("imageftbbox")
		&& function_exists('imageCreateTrueColor');

	
	if (@file_exists($fichier))
		$image = $fichier;
	else if (!$flag_gd_typo)
		return $texte;
	else {
		$font = find_in_path('polices/'.$police);
		if (!$font) {
			spip_log(_T('fichier_introuvable', array('fichier' => $police)));
			$font = find_in_path('polices/'."dustismo.ttf");
		}

		$imgbidon = imageCreateTrueColor($largeur, 45);
		$retour = printWordWrapped($imgbidon, $taille+5, 0, $largeur, $font, $couleur, $text, $taille, 'left', $hauteur_ligne);
		$hauteur = $retour["height"];
		$largeur_reelle = $retour["width"];
		$espace = $retour["espace"];
		imagedestroy($imgbidon);

		$im = imageCreateTrueColor($largeur_reelle-$espace+(2*$padding), $hauteur+5+(2*$padding));
		imagealphablending ($im, FALSE );
		imagesavealpha ( $im, TRUE );
		
		// Creation de quelques couleurs
		
		$grey2 = imagecolorallocatealpha($im, hexdec("0x{".substr($couleur, 0,2)."}"), hexdec("0x{".substr($couleur, 2,2)."}"), hexdec("0x{".substr($couleur, 4,2)."}"), 127);
		ImageFilledRectangle ($im,0,0,$largeur_reelle+(2*$padding),$hauteur+5+(2*$padding),$grey2);
		
		// Le texte a dessiner
		printWordWrapped($im, $taille+5+$padding, $padding, $largeur, $font, $couleur, $text, $taille, $align, $hauteur_ligne);
		
		
		// Utiliser imagepng() donnera un texte plus claire,
		// compare a l'utilisation de la fonction imagejpeg()
		_image_gd_output($im,array('fichier_dest'=>$fichier,'format_dest'=>'png'));
		imagedestroy($im);
		
		$image = $fichier;
	}


	$dimensions = getimagesize($image);
	$largeur = $dimensions[0];
	$hauteur = $dimensions[1];
	
	return inserer_attribut("<img src='$image' width='$largeur' height='$hauteur' style='width:".$largeur."px;height:".$hauteur."px;' />", 'alt', $alt);
}


?>
