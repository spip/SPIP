<?php

function formulaires_configurer_ecran_connexion_data(): array {
	return [
		'couleur_defaut' => '#db1762',
		'img_fond' => _DIR_IMG . 'spip_fond_login.jpg',
	];
}

function formulaires_configurer_ecran_connexion_charger_dist() {
	include_spip('inc/config');
	include_spip('inc/autoriser');

	$data = formulaires_configurer_ecran_connexion_data();

	$valeurs = [
		'couleur_login' => lire_config('couleur_login', $data['couleur_defaut']),
		'couleur_defaut_login' => $data['couleur_defaut'],
		'upload_image_fond_login' => '',
	];

	if (file_exists($data['img_fond'])) {
		$valeurs['src_img'] = $data['img_fond'];
	}

	return $valeurs;
}


function formulaires_configurer_ecran_connexion_verifier_dist() {
	$erreurs = [];

	if (_request('supprimer_image_fond_login')) {
		// rien à tester
	}

	elseif (_request('supprimer_couleur_login')) {
		// rien à tester
	}

	elseif (!empty($_FILES['upload_image_fond_login'])) {
		$file = $_FILES['upload_image_fond_login'];
		include_spip('inc/documents');
		$extension = pathinfo((string) $file['name'], PATHINFO_EXTENSION);
		$extension = corriger_extension(strtolower($extension));
		if ($extension != 'jpg') {
			$erreurs['upload_image_fond_login'] = _T('erreur_type_fichier');
		}
	}

	return $erreurs;
}


function formulaires_configurer_ecran_connexion_traiter_dist() {

	$retours = [
		'message_ok' => _T('config_info_enregistree'),
		'editable' => true,
	];

	include_spip('inc/config');
	$data = formulaires_configurer_ecran_connexion_data();
	$dest = $data['img_fond'];

	if (_request('couleur_login')) {
		$color = _request('couleur_login');
		if ($color === $data['couleur_defaut']) {
			effacer_config('couleur_login');
		} else {
			ecrire_config('couleur_login', $color);
		}
	}

	if (_request('supprimer_image_fond_login')) {
		@unlink($dest);
	}

	elseif (_request('supprimer_couleur_login')) {
		effacer_config('couleur_login');
		set_request('couleur_login', null);
	}

	elseif (!empty($_FILES['upload_image_fond_login'])) {
		$file = $_FILES['upload_image_fond_login'];
		include_spip('inc/documents');
		deplacer_fichier_upload($file['tmp_name'], $dest);
	}

	include_spip('inc/invalideur');
	suivre_invalideur('1'); # tout effacer

	return $retours;
}
