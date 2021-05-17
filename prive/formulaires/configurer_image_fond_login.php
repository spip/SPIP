<?php


function formulaires_configurer_image_fond_login_charger_dist() {
	include_spip("inc/utils");
	$repertoire = sous_repertoire(_DIR_IMG, "logo");
	$img = $repertoire."hotspot$id.jpg";
	$img = _DIR_IMG."spip_fond_login.jpg";
	$valeurs = array(
		"upload_image_fond_login" => ""
	);
	if (file_exists($img)) $valeurs["src_img"] = $img;
	$valeurs['_bigup_rechercher_fichiers'] = true;
	return $valeurs;
}


function formulaires_configurer_image_fond_login_verifier_dist() {
	$erreurs = array();
	return $erreurs;
}



function formulaires_configurer_image_fond_login_traiter_dist() {
	$img = _DIR_IMG."spip_fond_login.jpg";
	if ($_POST["supprimer_image_fond_login"]){
		@unlink($dest);
	}

	die ($img);
	if ($_FILES['upload_image_fond_login']['name']) {
		$fichier = $_FILES['upload_image_fond_login']['name'];
		if (preg_match(",\.jpg$,", $fichier)) {
			$fichier = $_FILES['upload_image_fond_login']['tmp_name'];
			rename($fichier, $dest);
		}
	}
	$retours = [
		'message_ok' => json_encode($_FILES),
		'editable' => true,
	];

	//return $retours;
}
