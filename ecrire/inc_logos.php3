<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_LOGOS")) return;
define("_ECRIRE_INC_LOGOS", "1");


function get_image($racine) {
	if (file_exists("../IMG/$racine.gif")) {
		$fichier = "$racine.gif";
	}
	else if (file_exists("../IMG/$racine.jpg")) {
		$fichier = "$racine.jpg";
	}
	else if (file_exists("../IMG/$racine.png")) {
		$fichier = "$racine.png";
	}

	if ($fichier) {
		$taille = resize_logo($fichier);
		return array($fichier, $taille);
	}
	else return;
}


function resize_logo($image) {
	$limage = @getimagesize("../IMG/$image");
	if (!$limage) return;
	$limagelarge = $limage[0];
	$limagehaut = $limage[1];

	if ($limagelarge > 200){
		$limagehaut = $limagehaut * 200 / $limagelarge;
		$limagelarge = 200;
	}

	if ($limagehaut > 200){
		$limagelarge = $limagelarge * 200 / $limagehaut;
		$limagehaut = 200;
	}

	// arrondir a l'entier superieur
	$limagehaut = ceil($limagehaut);
	$limagelarge = ceil($limagelarge);

	return (array($limage[0],$limage[1],$limagelarge,$limagehaut));
}


function afficher_boite_logo($racine, $titre) {
	global $id_article, $coll, $id_breve, $id_auteur, $id_mot, $id_syndic, $connect_id_auteur, $PHP_SELF;

	$redirect = substr($PHP_SELF, strrpos($PHP_SELF, '/') + 1);
	$logo = get_image($racine);
	if ($logo) {
		$fichier = $logo[0];
		$taille = $logo[1];
		if ($taille) {
			$taille_html = " WIDTH=$taille[2] HEIGHT=$taille[3] ";
			$taille_txt = "$taille[0] x $taille[1] pixels";
		}
	}

	echo "<CENTER><TABLE WIDTH=100% CELLPADDING=2 BORDER=1 CLASS='hauteur'><TR><TD WIDTH=100% ALIGN='center' BGCOLOR='#FFCC66'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#333333'><B>";
	echo bouton_block_invisible("$racine");
	echo $titre;
	echo "</B></FONT></TD></TR></TABLE></CENTER>";
	echo "<BR><font size=2 FACE='Verdana,Arial,Helvetica,sans-serif'>";
	if ($fichier) {
		$hash = calculer_action_auteur("supp_image $fichier");

		echo "<P><CENTER><IMG SRC='../IMG/$fichier' $taille_html>";
		echo debut_block_invisible("$racine");

		echo "<BR>$taille_txt\n";
		echo "<BR>[<A HREF='../spip_image.php3?";
		$elements = array('id_article', 'id_breve', 'id_syndic', 'coll', 'id_mot');
		while (list(,$element) = each ($elements)) {
			if ($$element) {
				echo $element.'='.$$element.'&';
			}
		}
		echo "image_supp=$fichier&hash_id_auteur=$connect_id_auteur&id_auteur=$id_auteur&hash=$hash&redirect=$redirect'>Supprimer le logo</A>]";
		echo fin_block();
		echo "</CENTER>";
	}
	else {
		$hash = calculer_action_auteur("ajout_image $racine");
		echo debut_block_invisible("$racine");

		echo "<FONT SIZE=1>";
		echo "\n\n<FORM ACTION='../spip_image.php3' METHOD='POST' ENCTYPE='multipart/form-data'>";
		echo "\n<INPUT NAME='redirect' TYPE=Hidden VALUE='$redirect'>";
		if ($id_auteur > 0) echo "\n<INPUT NAME='id_auteur' TYPE=Hidden VALUE='$id_auteur'>";
		if ($id_article > 0) echo "\n<INPUT NAME='id_article' TYPE=Hidden VALUE='$id_article'>";
		if ($id_breve > 0) echo "\n<INPUT NAME='id_breve' TYPE=Hidden VALUE='$id_breve'>";
		if ($id_mot > 0) echo "\n<INPUT NAME='id_mot' TYPE=Hidden VALUE='$id_mot'>";
		if ($id_syndic > 0) echo "\n<INPUT NAME='id_syndic' TYPE=Hidden VALUE='$id_syndic'>";
		if ($coll > 0) echo "\n<INPUT NAME='coll' TYPE=Hidden VALUE='$coll'>";
		echo "\n<INPUT NAME='hash_id_auteur' TYPE=Hidden VALUE='$connect_id_auteur'>";
		echo "\n<INPUT NAME='hash' TYPE=Hidden VALUE='$hash'>";
		echo "\n<INPUT NAME='ajout_logo' TYPE=Hidden VALUE='oui'>";
		echo "\n<INPUT NAME='logo' TYPE=Hidden VALUE='$racine'>";
		if (tester_upload()){
			echo "\nT&eacute;l&eacute;charger un nouveau logo&nbsp;:<BR>";
			echo "\n<INPUT NAME='image' TYPE=File CLASS='forml' SIZE=15>";
			echo "\n   <INPUT NAME='ok' TYPE=Submit VALUE='T&eacute;l&eacute;charger' CLASS='fondo'>";
		} else {
		
			$myDir = opendir("upload");
			while($entryName = readdir($myDir)){
				if (!ereg("^\.",$entryName) AND eregi("(gif|jpg|png)$",$entryName)){
					$entryName = addslashes($entryName);
					$afficher .= "\n<OPTION VALUE='ecrire/upload/$entryName'>$entryName";
				}
			}
			closedir($myDir);
			
			if (strlen($afficher) > 10){
				echo "\nS&eacute;lectionner un fichier&nbsp;:";
				echo "\n<SELECT NAME='image' CLASS='forml' SIZE=1>";
				echo $afficher;
				echo "\n</SELECT>";
				echo "\n  <INPUT NAME='ok' TYPE=Submit VALUE='Choisir' CLASS='fondo'>";
			} else {
				echo "Installer des images dans le dossier /ecrire/upload pour pouvoir les s&eacute;lectionner ici.";
			}
		
		}

		echo "\n</FORM>\n\n";
		echo "</FONT>";
		echo fin_block();
	}
	echo "</font>";
}


?>