<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;



function base_maj014_dist($version_installee, $version_cible)
{
	if (upgrade_vers(1.404, $version_installee, $version_cible)) {
		spip_query("UPDATE spip_mots SET type='Mots sans groupe...' WHERE type=''");

		$result = spip_query("SELECT * FROM spip_mots GROUP BY type");
		while($row = sql_fetch($result)) {
				$type = addslashes($row['type']);
				// Old style, doit echouer
				spip_log('ne pas tenir compte de l erreur spip_groupes_mots ci-dessous:', 'mysql');
				spip_query("INSERT INTO spip_groupes_mots 					(titre, unseul, obligatoire, articles, breves, rubriques, syndic, 0minirezo, 1comite, 6forum)					VALUES (\"$type\", 'non', 'non', 'oui', 'oui', 'non', 'oui', 'oui', 'oui', 'non')");
				// New style, devrait marcher
				spip_query("INSERT INTO spip_groupes_mots 					(titre, unseul, obligatoire, articles, breves, rubriques, syndic, minirezo, comite, forum)					VALUES (\"$type\", 'non', 'non', 'oui', 'oui', 'non', 'oui', 'oui', 'oui', 'non')");
		}
		spip_query("DELETE FROM spip_mots WHERE titre='kawax'");
		maj_version (1.404);
	}

	if (upgrade_vers(1.405, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_mots ADD id_groupe bigint(21) NOT NULL");
	
		$result = spip_query("SELECT * FROM spip_groupes_mots");
		while($row = sql_fetch($result)) {
				$id_groupe = addslashes($row['id_groupe']);
				$type = addslashes($row['titre']);
				spip_query("UPDATE spip_mots SET id_groupe = '$id_groupe' WHERE type='$type'");
		}
		maj_version (1.405);
	}

	if (upgrade_vers(1.408, $version_installee, $version_cible)) {
		// Images articles passent dans spip_documents
		$result = spip_query("SELECT id_article, images FROM spip_articles WHERE LENGTH(images) > 0");


		$types = array('jpg' => 1, 'png' => 2, 'gif' => 3);

		while ($row = @sql_fetch($result)) {
			$id_article = $row['id_article'];
			$images = $row['images'];
			$images = explode(",", $images);
			reset($images);
			$replace = '_orig_';
			foreach ($images as $val) {
				$image = explode("|", $val);
				$fichier = $image[0];
				$largeur = $image[1];
				$hauteur = $image[2];
				ereg("-([0-9]+)\.(gif|jpg|png)$", $fichier, $match);
				$id_type = intval($types[$match[2]]);
				$num_img = $match[1];
				$fichier = _DIR_IMG . $fichier;
				$taille = @filesize($fichier);
				// ici on n'a pas les fonctions absctract !
				$s = spip_query("INSERT INTO spip_documents (titre, id_type, fichier, mode, largeur, hauteur, taille) VALUES ('image $largeur x $hauteur', $id_type, '$fichier', 'vignette', '$largeur', '$hauteur', '$taille')");
				$id_document = mysql_insert_id($s);
				if ($id_document > 0) {
					spip_query("INSERT INTO spip_documents_articles (id_document, id_article) VALUES ($id_document, $id_article)");
					$replace = "REPLACE($replace, '<IMG$num_img|', '<IM_$id_document|')";
				} else {
					echo _T('texte_erreur_mise_niveau_base', array('fichier' => $fichier, 'id_article' => $id_article));
					exit;
				}
			}
			$replace = "REPLACE($replace, '<IM_', '<IMG')";
			$replace_chapo = str_replace('_orig_', 'chapo', $replace);
			$replace_descriptif = str_replace('_orig_', 'descriptif', $replace);
			$replace_texte = str_replace('_orig_', 'texte', $replace);
			$replace_ps = str_replace('_orig_', 'ps', $replace);
			spip_query("UPDATE spip_articles SET chapo=$replace_chapo, descriptif=$replace_descriptif, texte=$replace_texte, ps=$replace_ps WHERE id_article=$id_article");

		}
		spip_query("ALTER TABLE spip_articles DROP images");
		maj_version (1.408);
	}

	if (upgrade_vers(1.414, $version_installee, $version_cible)) {
		// Forum par defaut "en dur" dans les spip_articles
		// -> non, prio (priori), pos (posteriori), abo (abonnement)
		$accepter_forum = substr($GLOBALS['meta']["forums_publics"],0,3) ;
		$result = spip_query("ALTER TABLE spip_articles CHANGE accepter_forum accepter_forum CHAR(3) NOT NULL");

		$result = spip_query("UPDATE spip_articles SET accepter_forum='$accepter_forum' WHERE accepter_forum != 'non'");

		maj_version (1.414);
	}

	/*
	if ($version_installee == 1.415) {
		spip_query("ALTER TABLE spip_documents DROP inclus");
		maj_version (1.415);
	}
	*/

	if (upgrade_vers(1.417, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_syndic_articles DROP date_index");
		maj_version (1.417);
	}

	if (upgrade_vers(1.418, $version_installee, $version_cible)) {
		$result = spip_query("SELECT * FROM spip_auteurs WHERE statut = '0minirezo' AND email != '' ORDER BY id_auteur LIMIT 1");

		if ($webmaster = sql_fetch($result)) {
			ecrire_meta('email_webmaster', $webmaster['email']);
			ecrire_metas();
		}
		maj_version (1.418);
	}

	if (upgrade_vers(1.419, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_auteurs ADD alea_actuel TINYTEXT DEFAULT ''");
		spip_query("ALTER TABLE spip_auteurs ADD alea_futur TINYTEXT DEFAULT ''");
		spip_query("UPDATE spip_auteurs SET alea_futur = FLOOR(32000*RAND())");
		maj_version (1.419);
	}

	if (upgrade_vers(1.420, $version_installee, $version_cible)) {
		spip_query("UPDATE spip_auteurs SET alea_actuel='' WHERE statut='nouveau'");
		maj_version (1.420);
	}
	
	if (upgrade_vers(1.421, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD auteur_modif bigint(21) DEFAULT '0' NOT NULL");
		spip_query("ALTER TABLE spip_articles ADD date_modif datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		maj_version (1.421);
	}

	if (upgrade_vers(1.432, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles DROP referers");
		spip_query("ALTER TABLE spip_articles ADD referers INTEGER DEFAULT '0' NOT NULL");
		spip_query("ALTER TABLE spip_articles ADD popularite INTEGER DEFAULT '0' NOT NULL");
		maj_version (1.432);
	}

	if (upgrade_vers(1.436, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_documents ADD date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		maj_version (1.436);
	}

	if (upgrade_vers(1.437, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_visites ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_visites_referers ADD maj TIMESTAMP");
		maj_version (1.437);
	}

	if (upgrade_vers(1.438, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD INDEX id_secteur (id_secteur)");
		spip_query("ALTER TABLE spip_articles ADD INDEX statut (statut, date)");
		maj_version (1.438);
	}

	if (upgrade_vers(1.439, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_syndic ADD INDEX statut (statut, date_syndic)");
		spip_query("ALTER TABLE spip_syndic_articles ADD INDEX statut (statut)");
		spip_query("ALTER TABLE spip_syndic_articles CHANGE url url VARCHAR(255) NOT NULL");
		spip_query("ALTER TABLE spip_syndic_articles ADD INDEX url (url)");
		maj_version (1.439);
	}

	if (upgrade_vers(1.440, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_visites_temp CHANGE ip ip INTEGER UNSIGNED NOT NULL");
		maj_version (1.440);
	}

	if (upgrade_vers(1.441, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_visites_temp CHANGE date date DATE NOT NULL");
		spip_query("ALTER TABLE spip_visites CHANGE date date DATE NOT NULL");
		spip_query("ALTER TABLE spip_visites_referers CHANGE date date DATE NOT NULL");
		maj_version (1.441);
	}

	if (upgrade_vers(1.442, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_auteurs ADD prefs TINYTEXT NOT NULL");
		maj_version (1.442);
	}

	if (upgrade_vers(1.443, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_auteurs CHANGE login login VARCHAR(255) BINARY NOT NULL");
		spip_query("ALTER TABLE spip_auteurs CHANGE statut statut VARCHAR(255) NOT NULL");
		spip_query("ALTER TABLE spip_auteurs ADD INDEX login (login)");
		spip_query("ALTER TABLE spip_auteurs ADD INDEX statut (statut)");
		maj_version (1.443);
	}

	if (upgrade_vers(1.444, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_syndic ADD moderation VARCHAR(3) NOT NULL");
		maj_version (1.444);
	}

	if (upgrade_vers(1.457, $version_installee, $version_cible)) {
		spip_query("DROP TABLE spip_visites");
		spip_query("DROP TABLE spip_visites_temp");
		spip_query("DROP TABLE spip_visites_referers");
		creer_base(); // crade, a ameliorer :-((
		maj_version (1.457);
	}

	if (upgrade_vers(1.458, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_auteurs ADD cookie_oubli TINYTEXT NOT NULL");
		maj_version (1.458);
	}

	if (upgrade_vers(1.459, $version_installee, $version_cible)) {
		$result = spip_query("SELECT type FROM spip_mots GROUP BY type");
		while ($row = sql_fetch($result)) {
			$type = addslashes($row['type']);
			$res = spip_query("SELECT * FROM spip_groupes_mots WHERE titre='$type'");
			if (spip_num_rows($res) == 0) {
				$s = spip_query("INSERT INTO spip_groupes_mots (titre, unseul, obligatoire, articles, breves, rubriques, syndic, minirezo, comite, forum) VALUES ('$type', 'non', 'non', 'oui', 'oui', 'non', 'oui', 'oui', 'oui', 'non')");
			  if ($id_groupe = mysql_insert_id($s))
					spip_query("UPDATE spip_mots SET id_groupe = '$id_groupe' WHERE type='$type'");
			}
		}
		spip_query("UPDATE spip_articles SET popularite=0");
		maj_version (1.459);
	}

	if (upgrade_vers(1.460, $version_installee, $version_cible)) {
		// remettre les mots dans les groupes dupliques par erreur
		// dans la precedente version du paragraphe de maj 1.459
		// et supprimer ceux-ci
		$result = spip_query("SELECT * FROM spip_groupes_mots ORDER BY id_groupe");
		while ($row = sql_fetch($result)) {
			$titre = addslashes($row['titre']);
			if (! $vu[$titre] ) {
				$vu[$titre] = true;
				$id_groupe = $row['id_groupe'];
				spip_query("UPDATE spip_mots SET id_groupe=$id_groupe WHERE type='$titre'");
				spip_query("DELETE FROM spip_groupes_mots WHERE titre='$titre' AND id_groupe<>$id_groupe");
			}
		}
		maj_version (1.460);
	}

	if (upgrade_vers(1.462, $version_installee, $version_cible)) {
		spip_query("UPDATE spip_types_documents SET inclus='embed' WHERE inclus!='non' AND extension IN ('aiff', 'asf', 'avi', 'mid', 'mov', 'mp3', 'mpg', 'ogg', 'qt', 'ra', 'ram', 'rm', 'swf', 'wav', 'wmv')");
		maj_version (1.462);
	}

	if (upgrade_vers(1.463, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles CHANGE popularite popularite DOUBLE");
		spip_query("ALTER TABLE spip_visites_temp ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_referers_temp ADD maj TIMESTAMP");
		maj_version (1.463);
	}

	// l'upgrade < 1.462 ci-dessus etait fausse, d'ou correctif
	if (upgrade_vers(1.464, $version_installee, $version_cible) AND ($version_installee >= 1.462)) {
		$res = spip_query("SELECT id_type, extension FROM spip_types_documents WHERE id_type NOT IN (1,2,3)");
		while ($row = sql_fetch($res)) {
			$extension = $row['extension'];
			$id_type = $row['id_type'];
			spip_query("UPDATE spip_documents SET id_type=$id_type	WHERE fichier like '%.$extension'");
		}
		maj_version (1.464);
	}

	if (upgrade_vers(1.465, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles CHANGE popularite popularite DOUBLE NOT NULL");
		maj_version (1.465);
	}

	if (upgrade_vers(1.466, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_auteurs ADD source VARCHAR(10) DEFAULT 'spip' NOT NULL");
		maj_version (1.466);
	}

	if (upgrade_vers(1.468, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_auteurs ADD INDEX en_ligne (en_ligne)");
		spip_query("ALTER TABLE spip_forum ADD INDEX statut (statut, date_heure)");
		maj_version (1.468);
	}

	if (upgrade_vers(1.470, $version_installee, $version_cible)) {
		if ($version_installee >= 1.467) {	// annule les "listes de diff"
			spip_query("DROP TABLE spip_listes");
			spip_query("ALTER TABLE spip_auteurs DROP abonne");
			spip_query("ALTER TABLE spip_auteurs DROP abonne_pass");
		}
		maj_version (1.470);
	}

	if (upgrade_vers(1.471, $version_installee, $version_cible)) {
		if ($version_installee >= 1.470) {	// annule les "maj"
			spip_query("ALTER TABLE spip_auteurs_articles DROP maj TIMESTAMP");
			spip_query("ALTER TABLE spip_auteurs_rubriques DROP maj TIMESTAMP");
			spip_query("ALTER TABLE spip_auteurs_messages DROP maj TIMESTAMP");
			spip_query("ALTER TABLE spip_documents_articles DROP maj TIMESTAMP");
			spip_query("ALTER TABLE spip_documents_rubriques DROP maj TIMESTAMP");
			spip_query("ALTER TABLE spip_documents_breves DROP maj TIMESTAMP");
			spip_query("ALTER TABLE spip_mots_articles DROP maj TIMESTAMP");
			spip_query("ALTER TABLE spip_mots_breves DROP maj TIMESTAMP");
			spip_query("ALTER TABLE spip_mots_rubriques DROP maj TIMESTAMP");
			spip_query("ALTER TABLE spip_mots_syndic DROP maj TIMESTAMP");
			spip_query("ALTER TABLE spip_mots_forum DROP maj TIMESTAMP");
		}
		maj_version (1.471);
	}

	if (upgrade_vers(1.472, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_referers ADD visites_jour INTEGER UNSIGNED NOT NULL");
		maj_version (1.472);
	}

	if (upgrade_vers(1.473, $version_installee, $version_cible)) {
		spip_query("UPDATE spip_syndic_articles SET url = REPLACE(url, '&amp;', '&')");
		spip_query("UPDATE spip_syndic SET url_site = REPLACE(url_site, '&amp;', '&')");
		maj_version (1.473);
	}
}


?>
