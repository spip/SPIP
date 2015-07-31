<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_LOGOS")) return;
define("_ECRIRE_INC_LOGOS", "1");


include_ecrire ("inc_admin.php3");


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

		// contrer le cache du navigateur
		if ($fid = @filesize("../IMG/$fichier") . @filemtime("../IMG/$fichier")) {
			$fid = "?".md5($fid);
		}
		return array($fichier, $taille, $fid);
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


function afficher_boite_logo($logo, $survol, $texteon, $texteoff) {
	global $options;

	$logo_ok = get_image($logo);
	if ($logo_ok) $survol_ok = get_image($survol);

	echo "<p>";
	debut_cadre_relief("image-24.gif");
	echo "<center><font size='2' FACE='Verdana,Arial,Helvetica,sans-serif'>";
	echo "<b>";
	echo bouton_block_invisible(md5($texteon));
	echo $texteon;
	echo "</b>";

	afficher_logo($logo, $texteon);

	if ($logo_ok OR $survol_ok) {
		echo "<br><br><b>";
		echo bouton_block_invisible(md5($texteoff));
		echo $texteoff;
		echo "</b>";
		afficher_logo($survol, $texteoff);
	}

	echo "</font></center>";
	fin_cadre_relief();
}

function afficher_logo($racine, $titre) {
	global $id_article, $coll, $id_breve, $id_auteur, $id_mot, $id_syndic, $connect_id_auteur;
	global $couleur_foncee, $couleur_claire;
	global $clean_link;

	$redirect = $clean_link->getUrl();
	$logo = get_image($racine);
	if ($logo) {
		$fichier = $logo[0];
		$taille = $logo[1];
		$fid = $logo[2];
		if ($taille) {
			$taille_html = " WIDTH=$taille[2] HEIGHT=$taille[3] ";
			$taille_txt = "$taille[0] x $taille[1] pixels";
		}
	}

	echo "<font size=1>";

	if ($fichier) {
		$hash = calculer_action_auteur("supp_image $fichier");

		echo "<P><CENTER><IMG SRC='../IMG/$fichier$fid' $taille_html>";

		echo debut_block_invisible(md5($titre));
		echo "$taille_txt\n";
		echo "<BR>[<A HREF='../spip_image.php3?";
		$elements = array('id_article', 'id_breve', 'id_syndic', 'coll', 'id_mot', 'id_auteur');
		while (list(,$element) = each ($elements)) {
			if ($$element) {
				echo $element.'='.$$element.'&';
			}
		}
		echo "image_supp=$fichier&hash_id_auteur=$connect_id_auteur&id_auteur=$id_auteur&hash=$hash&redirect=$redirect'>"._T('lien_supprimer')."</A>]";
		echo fin_block();
		echo "</CENTER>";
	}
	else {
		$hash = calculer_action_auteur("ajout_image $racine");
		echo debut_block_invisible(md5($titre));

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
			echo "\n"._T('info_telecharger_nouveau_logo')."<BR>";
			echo "\n<INPUT NAME='image' TYPE=File CLASS='forml' style='font-size:9px;' SIZE=15>";
			echo "\n <div align='right'><INPUT NAME='ok' TYPE=Submit VALUE='"._T('bouton_telecharger')."' CLASS='fondo' style='font-size:9px;'></div>";
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
				echo "\n"._T('info_selectionner_fichier_2');
				echo "\n<SELECT NAME='image' CLASS='forml' SIZE=1>";
				echo $afficher;
				echo "\n</SELECT>";
				echo "\n  <INPUT NAME='ok' TYPE=Submit VALUE='"._T('bouton_choisir')."' CLASS='fondo'>";
			} else {
				echo _T('info_installer_images_dossier');
			}

		}
		echo fin_block();
		echo "</FORM>\n";
	}

	echo "</font>";
}


?>
