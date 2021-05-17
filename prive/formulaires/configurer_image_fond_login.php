<?php

function formulaires_configurer_image_fond_login_charger_dist() {

	$valeurs = array(
		"upload_image_fond_login" => ""
	);

	$img = _DIR_IMG . "spip_fond_login.jpg";
	if (file_exists($img)) {
		$valeurs["src_img"] = $img;
	}

	$valeurs['_bigup_rechercher_fichiers'] = true;
	return $valeurs;
}


function formulaires_configurer_image_fond_login_verifier_dist() {
	$erreurs = array();

	if (!empty($_FILES['upload_image_fond_login'])) {
		$file = $_FILES['upload_image_fond_login'];
		include_spip('action/ajouter_documents');
		$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
		$extension = corriger_extension(strtolower($extension));
		if (!in_array($extension, ['jpg'])) {
			$erreurs['upload_image_fond_login'] = _L('Mauvaise extension de l’image');
		}
	}

	return $erreurs;
}


function formulaires_configurer_image_fond_login_traiter_dist() {
	
	$dest = _DIR_IMG . "spip_fond_login.jpg";
	$retours = [];

	if (_request("supprimer_image_fond_login")) {
		@unlink($dest);
		$retours = [
			'message_ok' => _L('L’image est enlevée.'),
			'editable' => true,
		];
	}

	if (!empty($_FILES['upload_image_fond_login'])) {
		$file = $_FILES['upload_image_fond_login'];
		include_spip('inc/documents');
		deplacer_fichier_upload($file['tmp_name'], $dest);
		$retours = [
			'message_ok' => _L('L’image est installée'),
			'editable' => true,
		];
	}

	return $retours;
}
