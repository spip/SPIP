<?php

function formulaires_configurer_image_fond_login_data() : array {
	return [
		'couleur_defaut' => "#db1762",
	];
}

function formulaires_configurer_image_fond_login_charger_dist() {
	include_spip('inc/config');
	include_spip('inc/autoriser');

	$data = formulaires_configurer_image_fond_login_data();

	$valeurs = array(
		"couleur_login" => lire_config("couleur_login", $data['couleur_defaut']),
		"couleur_defaut_login" => $data['couleur_defaut'],
		"upload_image_fond_login" => "",
	);

	$img = _DIR_IMG . "spip_fond_login.jpg";
	if (file_exists($img)) {
		$valeurs["src_img"] = $img;
	}

	return $valeurs;
}


function formulaires_configurer_image_fond_login_verifier_dist() {
	$erreurs = array();

	if (_request("supprimer_image_fond_login")) {
		// rien à tester
	}

	elseif (_request("supprimer_couleur_login")) {
		// rien à tester
	}

	elseif (!empty($_FILES['upload_image_fond_login'])) {
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
	$retours = [
		'message_ok' => _T('config_info_enregistree'),
		'editable' => true,
	];

	include_spip('inc/config');
	$data = formulaires_configurer_image_fond_login_data();

	if (_request('couleur_login')) {
		$color = _request('couleur_login');
		if ($color === $data['couleur_defaut']) {
			effacer_config("couleur_login");
		} else {
			ecrire_config("couleur_login", $color);
		}
	}

	if (_request("supprimer_image_fond_login")) {
		@unlink($dest);
		$retours = [
			'message_ok' => _L('L’image est enlevée.'),
			'editable' => true,
		];
	}

	elseif (_request("supprimer_couleur_login")) {
		effacer_config("couleur_login");
		set_request("couleur_login", null);
		$retours = [
			'message_ok' => _L('La couleur est remise à sa valeur par défaut.'),
			'editable' => true,
		];
	}

	elseif (!empty($_FILES['upload_image_fond_login'])) {
		$file = $_FILES['upload_image_fond_login'];
		include_spip('inc/documents');
		deplacer_fichier_upload($file['tmp_name'], $dest);
		$retours = [
			'message_ok' => _L('L’image est installée'),
			'editable' => true,
		];
	}

	include_spip('inc/invalideur');
	suivre_invalideur('1'); # tout effacer

	return $retours;
}
