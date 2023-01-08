<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Gestion des mises à jour de bdd de SPIP
 *
 * Mises à jour en 2.1 (et 2.0.0+)
 *
 * @package SPIP\Core\SQL\Upgrade
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('base/medias');

// 2.0.0+

// http://archives.rezo.net/archives/spip-zone.mbox/C6RZKNBUNJYN42IOEOC4QKVCA233AMLI/
$GLOBALS['maj'][13833] = [
	['sql_alter', 'TABLE spip_documents_liens ADD INDEX objet(id_objet,objet)']
];

// 2.1

$GLOBALS['maj'][13904] = [
	['sql_alter', "TABLE spip_auteurs ADD webmestre varchar(3)  DEFAULT 'non' NOT NULL"],
	[
		'sql_update',
		'spip_auteurs',
		['webmestre' => "'oui'"],
		sql_in('id_auteur', defined('_ID_WEBMESTRES') ? explode(
			':',
			_ID_WEBMESTRES
		) : (autoriser('configurer') ? [$GLOBALS['visiteur_session']['id_auteur']] : [0]))
	] // le webmestre est celui qui fait l'upgrade si rien de defini
];

// sites plantes en mode "'su" au lieu de "sus"
$GLOBALS['maj'][13929] = [
	['sql_update', 'spip_syndic', ['syndication' => "'sus'"], "syndication LIKE '\\'%'"]
];

// Types de fichiers m4a/m4b/m4p/m4u/m4v/dv
// Types de fichiers Open XML (cro$oft)
$GLOBALS['maj'][14558] = [['creer_base_types_doc']];

// refaire les upgrade dont les numeros sont inferieurs a ceux de la branche 2.0
// etre sur qu'ils sont bien unipotents(?)...
$GLOBALS['maj'][14559] = $GLOBALS['maj'][13904] + $GLOBALS['maj'][13929] + $GLOBALS['maj'][14558];

// La version 14588 etait une mauvaise piste:
// Retour en arriere pour ceux qui l'ont subi, ne rien faire sinon
if (@$GLOBALS['meta']['version_installee'] >= 14588) {
	// "mode" est un mot-cle d'Oracle
	$GLOBALS['maj'][14588] = [
		['sql_alter', 'TABLE spip_documents  DROP INDEX mode'],
		[
			'sql_alter',
			"TABLE spip_documents  CHANGE mode genre ENUM('vignette', 'image', 'document') DEFAULT 'document' NOT NULL"
		],
		['sql_alter', 'TABLE spip_documents  ADD INDEX genre(genre)']
	];
	// solution moins intrusive au pb de mot-cle d'Oracle, retour avant 14588
	$GLOBALS['maj'][14598] = [
		['sql_alter', 'TABLE spip_documents  DROP INDEX genre'],
		[
			'sql_alter',
			"TABLE spip_documents  CHANGE genre mode ENUM('vignette', 'image', 'document') DEFAULT 'document' NOT NULL"
		],
		['sql_alter', 'TABLE spip_documents  ADD INDEX mode(mode)']
	];
}

// Restauration correcte des types mime des fichiers Ogg
// https://core.spip.net/issues/1941
// + Types de fichiers : f4a/f4b/f4p/f4v/mpc http://en.wikipedia.org/wiki/Flv#File_formats
// + Report du commit oublié : https://git.spip.net/spip/spip/commit/a6468fa5e3e34483b98b24b0102c4356f2f369a3
$GLOBALS['maj'][15676] = [['creer_base_types_doc']];

// Type de fichiers : webm http://en.wikipedia.org/wiki/Flv#File_formats
$GLOBALS['maj'][15827] = [['creer_base_types_doc']];
