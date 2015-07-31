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

// http://doc.spip.org/@maj_version
function maj_version ($version, $test = true) {
	if ($test) {
		include_spip('inc/meta');
		ecrire_meta('version_installee', $version);
		ecrire_metas();
		spip_log("mise a jour de la base vers $version");
	} else {
		echo _T('alerte_maj_impossible', array('version' => $version));
		exit;
	}
}

// http://doc.spip.org/@upgrade_vers
function upgrade_vers($version, $version_installee, $version_cible = 0){
	return ($version_installee<$version
		AND (($version_cible>=$version) OR ($version_cible==0))
	);
}
// http://doc.spip.org/@maj_base
function maj_base($version_cible = 0) {
	global $spip_version;

	//
	// Lecture de la version installee
	//
	// spip_query_db car on est peut-etre en cours d'installation
	$version_installee = 0.0;
	$result = spip_query_db ("SELECT valeur FROM spip_meta WHERE nom='version_installee'");
	if ($result) if ($row = spip_fetch_array($result)) $version_installee = (double) $row['valeur'];

	//
	// Si pas de version mentionnee dans spip_meta, c'est qu'il s'agit
	// d'une nouvelle installation
	//   => ne pas passer par le processus de mise a jour
	//
	// $version_installee = 1.702; quand on a besoin de forcer une MAJ

	if (!$version_installee) {
		spip_query_db("REPLACE spip_meta (nom, valeur)
			VALUES ('version_installee', '$spip_version')");
		return true;
	}


	//
	// Verification des droits de modification sur la base
	//

	spip_query("DROP TABLE IF EXISTS spip_test");
	spip_query("CREATE TABLE spip_test (a INT)");
	spip_query("ALTER TABLE spip_test ADD b INT");
	spip_query("INSERT INTO spip_test (b) VALUES (1)");
	$result = spip_query("SELECT b FROM spip_test");
	spip_query("ALTER TABLE spip_test DROP b");
	if (!$result) return false;

	//
	// Selection en fonction de la version
	//
	if (upgrade_vers(0.98, $version_installee, $version_cible)) {

		spip_query("ALTER TABLE spip_articles ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_articles ADD export VARCHAR(10) DEFAULT 'oui'");
		spip_query("ALTER TABLE spip_articles ADD images TEXT DEFAULT ''");
		spip_query("ALTER TABLE spip_articles ADD date_redac datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		spip_query("ALTER TABLE spip_articles DROP INDEX id_article");
		spip_query("ALTER TABLE spip_articles ADD INDEX id_rubrique (id_rubrique)");
		spip_query("ALTER TABLE spip_articles ADD visites INTEGER DEFAULT '0' NOT NULL");
		spip_query("ALTER TABLE spip_articles ADD referers BLOB NOT NULL");

		spip_query("ALTER TABLE spip_auteurs ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_auteurs ADD pgp BLOB NOT NULL");

		spip_query("ALTER TABLE spip_auteurs_articles ADD INDEX id_auteur (id_auteur), ADD INDEX id_article (id_article)");
	
		spip_query("ALTER TABLE spip_rubriques ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_rubriques ADD export VARCHAR(10) DEFAULT 'oui', ADD id_import BIGINT DEFAULT '0'");
	
		spip_query("ALTER TABLE spip_breves ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_breves DROP INDEX id_breve");
		spip_query("ALTER TABLE spip_breves DROP INDEX id_breve_2");
		spip_query("ALTER TABLE spip_breves ADD INDEX id_rubrique (id_rubrique)");
	
		spip_query("ALTER TABLE spip_forum ADD ip VARCHAR(16)");
		spip_query("ALTER TABLE spip_forum ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_forum DROP INDEX id_forum");
		spip_query("ALTER TABLE spip_forum ADD INDEX id_parent (id_parent), ADD INDEX id_rubrique (id_rubrique), ADD INDEX id_article(id_article), ADD INDEX id_breve(id_breve)");
		maj_version (0.98);
	}

	if (upgrade_vers(0.99, $version_installee, $version_cible)) {
	
		$result = spip_query("SELECT DISTINCT id_article FROM spip_forum WHERE id_article!=0 AND id_parent=0");

		while ($row = spip_fetch_array($result)) {
			unset($forums_article);
			$id_article = $row['id_article'];
			$result2 = spip_query("SELECT id_forum FROM spip_forum WHERE id_article=$id_article");
			for (;;) {
				unset($forums);
				while ($row2 = spip_fetch_array($result2)) $forums[] = $row2['id_forum'];
				if (!$forums) break;
				$forums = join(',', $forums);
				$forums_article[] = $forums;
				$result2 = spip_query("SELECT id_forum FROM spip_forum WHERE id_parent IN ($forums)");
			}
			$forums_article = join(',', $forums_article);
			spip_query("UPDATE spip_forum SET id_article=$id_article WHERE id_forum IN ($forums_article)");
		}
	
		$result = spip_query("SELECT DISTINCT id_breve FROM spip_forum WHERE id_breve!=0 AND id_parent=0");

		while ($row = spip_fetch_array($result)) {
			unset($forums_breve);
			$id_breve = $row['id_breve'];
			$result2 = spip_query("SELECT id_forum FROM spip_forum WHERE id_breve=$id_breve");
			for (;;) {
				unset($forums);
				while ($row2 = spip_fetch_array($result2)) $forums[] = $row2['id_forum'];
				if (!$forums) break;
				$forums = join(',', $forums);
				$forums_breve[] = $forums;
				$result2 = spip_query("SELECT id_forum FROM spip_forum WHERE id_parent IN ($forums)");
			}
			$forums_breve = join(',', $forums_breve);
			spip_query("UPDATE spip_forum SET id_breve=$id_breve WHERE id_forum IN ($forums_breve)");
		}
	
		$result = spip_query("SELECT DISTINCT id_rubrique FROM spip_forum WHERE id_rubrique!=0 AND id_parent=0");

		while ($row = spip_fetch_array($result)) {
			unset($forums_rubrique);
			$id_rubrique = $row['id_rubrique'];
			$result2 = spip_query("SELECT id_forum FROM spip_forum WHERE id_rubrique=$id_rubrique");
			for (;;) {

				unset($forums);
				while ($row2 = spip_fetch_array($result2)) $forums[] = $row2['id_forum'];
				if (!$forums) break;
				$forums = join(',', $forums);
				$forums_rubrique[] = $forums;
				$result2 = spip_query("SELECT id_forum FROM spip_forum WHERE id_parent IN ($forums)");
			}
			$forums_rubrique = join(',', $forums_rubrique);
			spip_query("UPDATE spip_forum SET id_rubrique=$id_rubrique WHERE id_forum IN ($forums_rubrique)");

		}
		maj_version (0.99);
	}

	if (upgrade_vers(0.997, $version_installee, $version_cible)) {
		spip_query("DROP TABLE spip_index");
		maj_version (0.997);
	}

	if (upgrade_vers(0.999, $version_installee, $version_cible)) {
		global $htsalt;
		spip_query("ALTER TABLE spip_auteurs CHANGE pass pass tinyblob NOT NULL");
		spip_query("ALTER TABLE spip_auteurs ADD htpass tinyblob NOT NULL");
		$result = spip_query("SELECT id_auteur, pass FROM spip_auteurs WHERE pass!=''");

		while (list($id_auteur, $pass) = spip_fetch_array($result, SPIP_NUM)) {
			$htpass = generer_htpass($pass);
			$pass = md5($pass);
			spip_query("UPDATE spip_auteurs SET pass='$pass', htpass='$htpass' WHERE id_auteur=$id_auteur");
		}
		maj_version (0.999);
	}
	
	if (upgrade_vers(1.01, $version_installee, $version_cible)) {
		spip_query("UPDATE spip_forum SET statut='publie' WHERE statut=''");
		maj_version (1.01);
	}
	
	if (upgrade_vers(1.02, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_forum ADD id_auteur BIGINT DEFAULT '0' NOT NULL");
		maj_version (1.02);
	}

	if (upgrade_vers(1.03, $version_installee, $version_cible)) {
		spip_query("DROP TABLE spip_maj");
		maj_version (1.03);
	}

	if (upgrade_vers(1.04, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD accepter_forum VARCHAR(3)");
		maj_version (1.04);
	}

	if (upgrade_vers(1.05, $version_installee, $version_cible)) {
		spip_query("DROP TABLE spip_petition");
		spip_query("DROP TABLE spip_signatures_petition");
		maj_version (1.05);
	}

	if (upgrade_vers(1.1, $version_installee, $version_cible)) {
		spip_query("DROP TABLE spip_petition");
		spip_query("DROP TABLE spip_signatures_petition");
		maj_version (1.1);
	}

	// Correction de l'oubli des modifs creations depuis 1.04
	if (upgrade_vers(1.204, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD accepter_forum VARCHAR(3) NOT NULL");
		spip_query("ALTER TABLE spip_forum ADD id_message bigint(21) NOT NULL");
		spip_query("ALTER TABLE spip_forum ADD INDEX id_message (id_message)");
		spip_query("ALTER TABLE spip_auteurs ADD en_ligne datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		spip_query("ALTER TABLE spip_auteurs ADD imessage VARCHAR(3) not null");
		spip_query("ALTER TABLE spip_auteurs ADD messagerie VARCHAR(3) not null");
		maj_version (1.204);
	}

	if (upgrade_vers(1.207, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_rubriques DROP INDEX id_rubrique");
		spip_query("ALTER TABLE spip_rubriques ADD INDEX id_parent (id_parent)");
		spip_query("ALTER TABLE spip_rubriques ADD statut VARCHAR(10) NOT NULL");
		// Declencher le calcul des rubriques publiques
		include_spip('inc/rubriques');
		calculer_rubriques();
		maj_version (1.207);
	}

	if (upgrade_vers(1.208, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_auteurs_messages CHANGE forum vu CHAR(3) NOT NULL");
		spip_query("UPDATE spip_auteurs_messages SET vu='oui'");
		spip_query("UPDATE spip_auteurs_messages SET vu='non' WHERE statut='a'");

		spip_query("ALTER TABLE spip_messages ADD id_auteur bigint(21) NOT NULL");
		spip_query("ALTER TABLE spip_messages ADD INDEX id_auteur (id_auteur)");
		$result = spip_query("SELECT id_auteur, id_message FROM spip_auteurs_messages WHERE statut='de'");
		while ($row = spip_fetch_array($result)) {
			$id_auteur = $row['id_auteur'];
			$id_message = $row['id_message'];
			spip_query("UPDATE spip_messages SET id_auteur=$id_auteur WHERE id_message=$id_message");
		}

		spip_query("ALTER TABLE spip_auteurs_messages DROP statut");
		maj_version (1.208);
	}

	if (upgrade_vers(1.209, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_syndic ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_syndic_articles ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_messages ADD maj TIMESTAMP");
		maj_version (1.209);
	}

	if (upgrade_vers(1.210, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_messages DROP page");

		stripslashes_base('spip_articles', array('surtitre', 'titre', 'soustitre', 'descriptif', 'chapo', 'texte', 'ps'));
		stripslashes_base('spip_auteurs', array('nom', 'bio', 'nom_site'));
		stripslashes_base('spip_breves', array('titre', 'texte', 'lien_titre'));
		stripslashes_base('spip_forum', array('titre', 'texte', 'auteur', 'nom_site'));
		stripslashes_base('spip_messages', array('titre', 'texte'));
		stripslashes_base('spip_mots', array('type', 'titre', 'descriptif', 'texte'));
		stripslashes_base('spip_petitions', array('texte'));
		stripslashes_base('spip_rubriques', array('titre', 'descriptif', 'texte'));
		stripslashes_base('spip_signatures', array('nom_email', 'nom_site', 'message'));
		stripslashes_base('spip_syndic', array('nom_site', 'descriptif'));
		stripslashes_base('spip_syndic_articles', array('titre', 'lesauteurs'));
		maj_version (1.210);
	}

	if (upgrade_vers(1.3, $version_installee, $version_cible)) {
		// Modifier la syndication (pour liste de sites)
		spip_query("ALTER TABLE spip_syndic ADD syndication VARCHAR(3) NOT NULL");
		spip_query("ALTER TABLE spip_syndic ADD statut VARCHAR(10) NOT NULL");
		spip_query("ALTER TABLE spip_syndic ADD date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		spip_query("UPDATE spip_syndic SET syndication='oui', statut='publie', date=NOW()");

		// Statut pour articles syndication, pour pouvoir desactiver un article
		spip_query("ALTER TABLE spip_syndic_articles ADD statut VARCHAR(10) NOT NULL");
		spip_query("UPDATE spip_syndic_articles SET statut='publie'");
		maj_version (1.3);
	}

	if (upgrade_vers(1.301, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_forum ADD id_syndic bigint(21) DEFAULT '0' NOT NULL");
		maj_version (1.301);
	}

	if (upgrade_vers(1.302, $version_installee, $version_cible)) {
		# spip_query("ALTER TABLE spip_forum_cache DROP PRIMARY KEY");
		# spip_query("ALTER TABLE spip_forum_cache DROP INDEX fichier");
		# spip_query("ALTER TABLE spip_forum_cache ADD PRIMARY KEY (fichier, id_forum, id_article, id_rubrique, id_breve, id_syndic)");
		spip_query("ALTER TABLE spip_forum ADD INDEX id_syndic (id_syndic)");
		maj_version (1.302);
	}

	if (upgrade_vers(1.303, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_rubriques ADD date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		spip_query("ALTER TABLE spip_syndic ADD date_syndic datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		spip_query("UPDATE spip_syndic SET date_syndic=date");
		maj_version (1.303);
	}

	if (upgrade_vers(1.306, $version_installee, $version_cible)) {
		spip_query("DROP TABLE spip_index_syndic_articles");
		spip_query("ALTER TABLE spip_syndic ADD date_index datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		spip_query("ALTER TABLE spip_syndic ADD INDEX date_index (date_index)");
		maj_version (1.306);
	}

	if (upgrade_vers(1.307, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_syndic_articles ADD descriptif blob NOT NULL");
		maj_version (1.307);
	}

	if (upgrade_vers(1.404, $version_installee, $version_cible)) {
		spip_query("UPDATE spip_mots SET type='Mots sans groupe...' WHERE type=''");

		$result = spip_query("SELECT * FROM spip_mots GROUP BY type");
		while($row = spip_fetch_array($result)) {
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
		while($row = spip_fetch_array($result)) {
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

		while ($row = @spip_fetch_array($result)) {
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
		include_spip('inc/meta');
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

		if ($webmaster = spip_fetch_array($result)) {
			include_spip('inc/meta');
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
		while ($row = spip_fetch_array($result)) {
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
		while ($row = spip_fetch_array($result)) {
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
		while ($row = spip_fetch_array($res)) {
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

	if (upgrade_vers(1.600, $version_installee, $version_cible)) {
		include_spip('inc/indexation');
		purger_index();
		creer_liste_indexation();
		maj_version (1.600);
	}

	if (upgrade_vers(1.601, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_forum ADD INDEX id_syndic (id_syndic)");
		maj_version (1.601);
	}

	if (upgrade_vers(1.603, $version_installee, $version_cible)) {
		// supprimer les fichiers deplaces
		@unlink('inc_meta_cache.php');
		@unlink('inc_meta_cache.php3');
		@unlink('data/engines-list.ini');
		maj_version (1.603);
	}

	if (upgrade_vers(1.604, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_auteurs ADD lang VARCHAR(10) DEFAULT '' NOT NULL");
		$u = spip_query("SELECT * FROM spip_auteurs WHERE prefs LIKE '%spip_lang%'");
		while ($row = spip_fetch_array($u)) {
			$prefs = unserialize($row['prefs']);
			$l = $prefs['spip_lang'];
			unset ($prefs['spip_lang']);
			spip_query("UPDATE spip_auteurs SET lang=" . _q($l) . ", prefs='".addslashes(serialize($prefs))."' WHERE id_auteur=".$row['id_auteur']);
		}
		$u = spip_query("SELECT lang FROM spip_auteurs");
		maj_version (1.604, $u);
	}

	if (upgrade_vers(1.702, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD extra longblob NULL");
		spip_query("ALTER TABLE spip_auteurs ADD extra longblob NULL");
		spip_query("ALTER TABLE spip_breves ADD extra longblob NULL");
		spip_query("ALTER TABLE spip_rubriques ADD extra longblob NULL");
		spip_query("ALTER TABLE spip_mots ADD extra longblob NULL");

		// recuperer les eventuels 'supplement' installes en 1.701
		if ($version_installee == 1.701) {
			spip_query("UPDATE spip_articles SET extra = supplement");
			spip_query("ALTER TABLE spip_articles DROP supplement");
			spip_query("UPDATE spip_auteurs SET extra = supplement");
			spip_query("ALTER TABLE spip_auteurs DROP supplement");
			spip_query("UPDATE spip_breves SET extra = supplement");
			spip_query("ALTER TABLE spip_breves DROP supplement");
			spip_query("UPDATE spip_rubriques SET extra = supplement");
			spip_query("ALTER TABLE spip_rubriques DROP supplement");
			spip_query("UPDATE spip_mots SET extra = supplement");
			spip_query("ALTER TABLE spip_mots DROP supplement");
		}
		
		$u = spip_query("SELECT extra FROM spip_articles");
		$u&= spip_query("SELECT extra FROM spip_auteurs");
		$u&= spip_query("SELECT extra FROM spip_breves");
		$u&= spip_query("SELECT extra FROM spip_rubriques");
		$u&= spip_query("SELECT extra FROM spip_mots");
		maj_version (1.702,$u);
	}

	if (upgrade_vers(1.703, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD lang VARCHAR(10) DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_rubriques ADD lang VARCHAR(10) DEFAULT '' NOT NULL");
		maj_version (1.703);
	}

	if (upgrade_vers(1.704, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD INDEX lang (lang)");
		spip_query("ALTER TABLE spip_auteurs ADD INDEX lang (lang)");
		spip_query("ALTER TABLE spip_rubriques ADD INDEX lang (lang)");
		maj_version (1.704);
	}

	if (upgrade_vers(1.705, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD langue_choisie VARCHAR(3) DEFAULT 'non'");
		spip_query("ALTER TABLE spip_rubriques ADD langue_choisie VARCHAR(3) DEFAULT 'non'");
		maj_version (1.705);
	}

	if (upgrade_vers(1.707, $version_installee, $version_cible)) {
		spip_query("UPDATE spip_articles SET langue_choisie='oui' WHERE MID(lang,1,1) != '.' AND lang != ''");
		spip_query("UPDATE spip_articles SET lang=MID(lang,2,8) WHERE langue_choisie = 'non'");
		spip_query("UPDATE spip_rubriques SET langue_choisie='oui' WHERE MID(lang,1,1) != '.' AND lang != ''");
		spip_query("UPDATE spip_rubriques SET lang=MID(lang,2,8) WHERE langue_choisie = 'non'");
		maj_version (1.707);
	}

	if (upgrade_vers(1.708, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_breves ADD lang VARCHAR(10) DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_breves ADD langue_choisie VARCHAR(3) DEFAULT 'non'");
		maj_version (1.708);
	}

	if (upgrade_vers(1.709, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD id_trad bigint(21) DEFAULT '0' NOT NULL");
		spip_query("ALTER TABLE spip_articles ADD INDEX id_trad (id_trad)");
		maj_version (1.709);
	}

	if (upgrade_vers(1.717, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD INDEX date_modif (date_modif)");
		maj_version (1.717);
	}

	if (upgrade_vers(1.718, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_referers DROP domaine");
		spip_query("ALTER TABLE spip_referers_articles DROP domaine");
		spip_query("ALTER TABLE spip_referers_temp DROP domaine");
		maj_version (1.718);
	}

	if (upgrade_vers(1.722, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD nom_site tinytext NOT NULL");
		spip_query("ALTER TABLE spip_articles ADD url_site VARCHAR(255) NOT NULL");
		spip_query("ALTER TABLE spip_articles ADD INDEX url_site (url_site)");
		if ($version_installee >= 1.720) {
			spip_query("UPDATE spip_articles SET url_site=url_ref");
			spip_query("ALTER TABLE spip_articles DROP INDEX url_ref");
			spip_query("ALTER TABLE spip_articles DROP url_ref");
		}
		maj_version (1.722);
	}

	if (upgrade_vers(1.723, $version_installee, $version_cible)) {
		if ($version_installee == 1.722) {
			spip_query("ALTER TABLE spip_articles MODIFY url_site VARCHAR(255) NOT NULL");
			spip_query("ALTER TABLE spip_articles DROP INDEX url_site;");
			spip_query("ALTER TABLE spip_articles ADD INDEX url_site (url_site);");
		}
		maj_version (1.723);
	}

	if (upgrade_vers(1.724, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_messages ADD date_fin datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		maj_version (1.724);
	}

	if (upgrade_vers(1.726, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_auteurs ADD low_sec tinytext NOT NULL");
		maj_version (1.726);
	}

	if (upgrade_vers(1.727, $version_installee, $version_cible)) {
		// occitans : oci_xx -> oc_xx
		spip_query("UPDATE spip_auteurs SET lang=REPLACE(lang,'oci_', 'oc_') WHERE lang LIKE 'oci_%'");
		spip_query("UPDATE spip_rubriques SET lang=REPLACE(lang,'oci_', 'oc_') WHERE lang LIKE 'oci_%'");
		spip_query("UPDATE spip_articles SET lang=REPLACE(lang,'oci_', 'oc_') WHERE lang LIKE 'oci_%'");
		spip_query("UPDATE spip_breves SET lang=REPLACE(lang,'oci_', 'oc_') WHERE lang LIKE 'oci_%'");
		maj_version (1.727);
	}

	// Ici version 1.7 officielle

	if (upgrade_vers(1.728, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD id_version int unsigned DEFAULT '0' NOT NULL");
		maj_version (1.728);
	}

	if (upgrade_vers(1.730, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_articles ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_auteurs ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_auteurs ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_breves ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_breves ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_mots ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_mots ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_rubriques ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_rubriques ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_syndic ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_syndic ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_forum ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_forum ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_signatures ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_signatures ADD INDEX idx (idx)");
		maj_version (1.730);
	}

	if (upgrade_vers(1.731, $version_installee, $version_cible)) {
		spip_query("UPDATE spip_articles SET idx='1' where lang IN ('de','vi')");
		spip_query("UPDATE spip_rubriques SET idx='1' where lang IN ('de','vi')");
		spip_query("UPDATE spip_breves SET idx='1' where lang IN ('de','vi')");
		spip_query("UPDATE spip_auteurs SET idx='1' where lang IN ('de','vi')");
		maj_version (1.731);
	}

	if (upgrade_vers(1.732, $version_installee, $version_cible)) { // en correction d'un vieux truc qui avait fait sauter le champ inclus sur les bases version 1.415
		spip_query("ALTER TABLE spip_documents ADD inclus  VARCHAR(3) DEFAULT 'non'");
		maj_version (1.732);
	}

	if (upgrade_vers(1.733, $version_installee, $version_cible)) {
		// spip_query("ALTER TABLE spip_articles ADD id_version int unsigned DEFAULT '0' NOT NULL");
		spip_query("DROP TABLE spip_versions");
		spip_query("DROP TABLE spip_versions_fragments");
		creer_base();
		maj_version(1.733);
	}

	#if ($version_installee < 1.734) {
	#	// integrer nouvelles tables auxiliaires du compilateur ESJ
	#	creer_base();
	#	maj_version(1.734);
	#}

	if (upgrade_vers(1.801, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_rubriques	ADD statut_tmp VARCHAR(10) NOT NULL,	ADD date_tmp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		include_spip('inc/rubriques');
		calculer_rubriques();
		maj_version(1.801);
	}

	// Nouvelles tables d'invalidation
	if (upgrade_vers(1.802, $version_installee, $version_cible)) {
		spip_query("DROP TABLE spip_id_article_caches");
		spip_query("DROP TABLE spip_id_auteur_caches");
		spip_query("DROP TABLE spip_id_breve_caches");
		spip_query("DROP TABLE spip_id_document_caches");
		spip_query("DROP TABLE spip_id_forum_caches");
		spip_query("DROP TABLE spip_id_groupe_caches");
		spip_query("DROP TABLE spip_id_message_caches");
		spip_query("DROP TABLE spip_id_mot_caches");
		spip_query("DROP TABLE spip_id_rubrique_caches");
		spip_query("DROP TABLE spip_id_signature_caches");
		spip_query("DROP TABLE spip_id_syndic_article_caches");
		spip_query("DROP TABLE spip_id_syndic_caches");
		spip_query("DROP TABLE spip_id_type_caches");
		spip_query("DROP TABLE spip_inclure_caches");
		maj_version(1.802);
	}
	if (upgrade_vers(1.803, $version_installee, $version_cible)) {

	#	27 AOUT 2004 : conservons cette table pour autoriser les retours
	#	de SPIP 1.8a6 CVS vers 1.7.2
	#	spip_query("DROP TABLE spip_forum_cache");

		spip_query("DROP TABLE spip_inclure_caches");
		maj_version(1.803);
	}
	if (upgrade_vers(1.804, $version_installee, $version_cible)) {
		// recreer la table spip_caches
		spip_query("DROP TABLE spip_caches");
		creer_base();
		maj_version(1.804);
	}

	if (upgrade_vers(1.805, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_forum ADD id_thread bigint(21) DEFAULT '0' NOT NULL");
		include_spip('inc/forum');
		calculer_threads();
		maj_version(1.805);
	}

	// tables d'orthographe
	#if ($version_installee < 1.806)
	#	maj_version(1.806);

	// URLs propres (inc_version = 0.12)
	if (upgrade_vers(1.807, $version_installee, $version_cible)) {
		foreach (array('articles', 'breves', 'rubriques', 'mots') as $objets) {
			spip_query("ALTER TABLE spip_$objets ADD url_propre VARCHAR(255) NOT NULL");
			spip_query("ALTER TABLE spip_$objets ADD INDEX url_propre (url_propre)");
		}
		maj_version(1.807);
	}

	// referers de la veille
	if (upgrade_vers(1.808, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_referers ADD visites_veille INT UNSIGNED NOT NULL");
		maj_version(1.808);
	}


	// corrections diverses
	if (upgrade_vers(1.809, $version_installee, $version_cible)) {
		// plus de retour possible vers 1.7.2
		spip_query("DROP TABLE spip_forum_cache");

		// les requetes ci-dessous ne s'appliqueront que si on est passe
		// par une certaine version de developpement - oublie de le faire
		// plus tot, car le code d'alors recreait purement et simplement
		// cette table
		spip_query("ALTER TABLE spip_versions DROP chapo");
		spip_query("ALTER TABLE spip_versions DROP texte");
		spip_query("ALTER TABLE spip_versions DROP ps");
		spip_query("ALTER TABLE spip_versions DROP extra");
		spip_query("ALTER TABLE spip_versions ADD champs text NOT NULL");

		maj_version(1.809);
	}

	// Annuler les brouillons de forum jamais valides
	if (upgrade_vers(1.810, $version_installee, $version_cible)) {
		spip_query("DELETE FROM spip_forum WHERE statut='redac'");
		maj_version(1.810);
	}

	if (upgrade_vers(1.811, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_syndic ADD extra longblob NULL");
		maj_version(1.811);
	}
	
	if (upgrade_vers(1.812, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_documents ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		maj_version(1.812);
	}

	// Mise a jour des types MIME
	if (upgrade_vers(1.813, $version_installee, $version_cible)) {
		# rien a faire car c'est creer_base() qui s'en charge
		maj_version(1.813);
	}

	// URLs propres auteurs
	if (upgrade_vers(1.814, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_auteurs ADD url_propre VARCHAR(255) NOT NULL");
		spip_query("ALTER TABLE spip_auteurs ADD INDEX url_propre (url_propre)");
		maj_version(1.814);
	}

	// Mots-cles sur les documents
	// + liens documents <-> sites et articles syndiques (podcasting)
	if (upgrade_vers(1.815, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_documents	ADD distant VARCHAR(3) DEFAULT 'non'");
		maj_version(1.815);
	}

	// Indexation des documents (rien a faire sauf reinstaller inc_auxbase)
	if (upgrade_vers(1.816, $version_installee, $version_cible)) {
		maj_version(1.816);
	}

	// Texte et descriptif des groupes de mots-cles
	if (upgrade_vers(1.817, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_groupes_mots ADD descriptif text NOT NULL AFTER titre");
		spip_query("ALTER TABLE spip_groupes_mots ADD COLUMN texte longblob NOT NULL AFTER descriptif");
		maj_version(1.817);
	}

	// Conformite des noms de certains champs (0minirezo => minirezo)
	if (upgrade_vers(1.818, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_groupes_mots CHANGE COLUMN 0minirezo minirezo char(3) NOT NULL");
		spip_query("ALTER TABLE spip_groupes_mots CHANGE COLUMN 1comite comite char(3) NOT NULL");
		spip_query("ALTER TABLE spip_groupes_mots CHANGE COLUMN 6forum forum char(3) NOT NULL");
		maj_version(1.818);
	}

	// Options de syndication : miroir + oubli
	if (upgrade_vers(1.819, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_syndic ADD miroir VARCHAR(3) DEFAULT 'non'");
		spip_query("ALTER TABLE spip_syndic ADD oubli VARCHAR(3) DEFAULT 'non'");
		maj_version(1.819);
	}

	// Un bug dans les 1.730 (il manquait le "ADD")
	if (upgrade_vers(1.820, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_auteurs ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_breves ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_mots ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_rubriques ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_syndic ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_forum ADD INDEX idx (idx)");
		spip_query("ALTER TABLE spip_signatures ADD INDEX idx (idx)");
		maj_version(1.820);
	}

	// reindexer les articles (on avait oublie les auteurs)
	if (upgrade_vers(1.821, $version_installee, $version_cible)) {
		spip_query("UPDATE spip_articles SET idx='1' WHERE idx='oui'");
		maj_version(1.821);
	}
	// le 'type' des mots doit etre du texte, sinon on depasse en champ multi
	if (upgrade_vers(1.822, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_mots DROP INDEX type");
		spip_query("ALTER TABLE spip_mots CHANGE type type TEXT NOT NULL");
		maj_version(1.822);
	}
	// ajouter une table de fonctions pour ajax
	if (upgrade_vers(1.825, $version_installee, $version_cible)) {
		maj_version(1.825);
	}
	if (upgrade_vers(1.826, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_ajax_fonc DROP fonction");
		maj_version(1.826);
	}

	// Syndication : ajout de l'option resume=oui/non et de la langue
	if (upgrade_vers(1.901, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_syndic ADD resume VARCHAR(3) DEFAULT 'oui'");
		spip_query("ALTER TABLE spip_syndic_articles ADD lang VARCHAR(10) DEFAULT '' NOT NULL");
		maj_version(1.901);
	}

	// Syndication : ajout de source, url_source, tags
	if (upgrade_vers(1.902, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_syndic_articles ADD url_source TINYTEXT DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_syndic_articles ADD source TINYTEXT DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_syndic_articles ADD tags TEXT DEFAULT '' NOT NULL");
		maj_version(1.902);
	}

	// URLs propres des sites (sait-on jamais)
	// + oubli des KEY url_propre sur les auteurs si installation neuve
	if (upgrade_vers(1.903, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_syndic ADD url_propre VARCHAR(255) NOT NULL");
		spip_query("ALTER TABLE spip_syndic ADD INDEX url_propre (url_propre)");
		spip_query("ALTER TABLE spip_auteurs ADD INDEX url_propre (url_propre)");
		maj_version(1.903);
	}

	// suppression des anciennes tables temporaires des visites
	// (maintenant stockees sous forme de fichiers)
	if (upgrade_vers(1.904, $version_installee, $version_cible)) {
		spip_query("DROP TABLE IF EXISTS spip_visites_temp");
		spip_query("DROP TABLE IF EXISTS spip_referers_temp");
		maj_version(1.904);
	}

	// fusion des 10 tables index en une seule
	// pour fonctions futures evoluees du moteur de recherche
	if (upgrade_vers(1.905, $version_installee, $version_cible)) {
		// agrandir le champ "valeur" de spip_meta pour pouvoir y stocker
		// des choses plus sympa
		spip_query("ALTER TABLE spip_meta CHANGE `valeur` `valeur` TEXT");
		// table des correspondances table->id_table
		$liste_tables = array();
		$liste_tables[1]='spip_articles';
		$liste_tables[2]='spip_auteurs';
		$liste_tables[3]='spip_breves';
		$liste_tables[4]='spip_documents';
		$liste_tables[5]='spip_forum';
		$liste_tables[6]='spip_mots';
		$liste_tables[7]='spip_rubriques';
		$liste_tables[8]='spip_signatures';
		$liste_tables[9]='spip_syndic';
		$s=addslashes(serialize($liste_tables));
		spip_query("INSERT INTO `spip_meta` ( `nom` , `valeur` , `maj` ) VALUES ('index_table', '$s', NOW( ));");

		spip_query("INSERT INTO spip_index (hash,points,id_objet,id_table) SELECT hash,points,id_article as id_objet,'1' as id_table FROM spip_index_articles");
		spip_query("DROP TABLE IF EXISTS spip_index_articles");

		spip_query("INSERT INTO spip_index (hash,points,id_objet,id_table) SELECT hash,points,id_auteur as id_objet,'2' as id_table FROM spip_index_auteurs");
		spip_query("DROP TABLE IF EXISTS spip_index_auteurs");

		spip_query("INSERT INTO spip_index (hash,points,id_objet,id_table) SELECT hash,points,id_breve as id_objet,'3' as id_table FROM spip_index_breves");
		spip_query("DROP TABLE IF EXISTS spip_index_breves");

		spip_query("INSERT INTO spip_index (hash,points,id_objet,id_table) SELECT hash,points,id_document as id_objet,'4' as id_table FROM spip_index_documents");
		spip_query("DROP TABLE IF EXISTS spip_index_documents");

		spip_query("INSERT INTO spip_index (hash,points,id_objet,id_table) SELECT hash,points,id_forum as id_objet,'5' as id_table FROM spip_index_forum");
		spip_query("DROP TABLE IF EXISTS spip_index_forum");

		spip_query("INSERT INTO spip_index (hash,points,id_objet,id_table) SELECT hash,points,id_mot as id_objet,'6' as id_table FROM spip_index_mots");
		spip_query("DROP TABLE IF EXISTS spip_index_mots");

		spip_query("INSERT INTO spip_index (hash,points,id_objet,id_table) SELECT hash,points,id_rubrique as id_objet,'7' as id_table FROM spip_index_rubriques");
		spip_query("DROP TABLE IF EXISTS spip_index_rubriques");

		spip_query("INSERT INTO spip_index (hash,points,id_objet,id_table) SELECT hash,points,id_signature as id_objet,'8' as id_table FROM spip_index_signatures");
		spip_query("DROP TABLE IF EXISTS spip_index_signatures");

		spip_query("INSERT INTO spip_index (hash,points,id_objet,id_table) SELECT hash,points,id_syndic as id_objet,'9' as id_table FROM spip_index_syndic");
		spip_query("DROP TABLE IF EXISTS spip_index_syndic");
		include_spip('inc/meta');
		lire_metas();
		ecrire_metas();

		maj_version(1.905);
	}


	// cette table est desormais geree par le plugin "podcast_client", on la
	// supprime si le plugin n'est pas active ; risque inherent a l'utilisation
	// de versions alpha :-)
	if (upgrade_vers(1.906, $version_installee, $version_cible)) {
		if (!in_array('podcast_client', $GLOBALS['plugins'])) {
			spip_query("DROP TABLE spip_documents_syndic");
		}
		maj_version(1.906);
	}
	if (upgrade_vers(1.907, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_forum ADD INDEX idx (idx)");
		maj_version(1.907);
	}
	// Oups ! on stockait les tags de syndication sous la forme rel="category"
	// au lieu de rel="directory" - http://microformats.org/wiki/rel-directory
	if (upgrade_vers(1.908, $version_installee, $version_cible)) {
		spip_query("UPDATE spip_syndic_articles SET tags = REPLACE(tags, 'rel=\"category\">', 'rel=\"directory\">') WHERE tags like '%category%'");
		maj_version(1.908);
	}
	if (upgrade_vers(1.909, $version_installee, $version_cible)) {
		spip_query("ALTER IGNORE TABLE spip_mots_articles ADD PRIMARY KEY (id_article, id_mot)");
		spip_query("ALTER IGNORE TABLE spip_mots_breves ADD PRIMARY KEY (id_breve, id_mot)");
		spip_query("ALTER IGNORE TABLE spip_mots_rubriques ADD PRIMARY KEY (id_rubrique, id_mot)");
		spip_query("ALTER IGNORE TABLE spip_mots_syndic ADD PRIMARY KEY (id_syndic, id_mot)");
		spip_query("ALTER IGNORE TABLE spip_mots_documents ADD PRIMARY KEY (id_document, id_mot)");
		spip_query("ALTER IGNORE TABLE spip_mots_forum ADD PRIMARY KEY (id_forum, id_mot)");
		maj_version(1.909);
	}

	if (upgrade_vers(1.910, $version_installee, $version_cible)) {
		spip_query("ALTER IGNORE TABLE spip_auteurs_articles ADD PRIMARY KEY (id_auteur, id_article)");
		spip_query("ALTER IGNORE TABLE spip_auteurs_rubriques ADD PRIMARY KEY (id_auteur, id_rubrique)");
		spip_query("ALTER IGNORE TABLE spip_auteurs_messages ADD PRIMARY KEY (id_auteur, id_message)");
		maj_version(1.910);
	}

	if (upgrade_vers(1.911, $version_installee, $version_cible)) {

		spip_query("ALTER IGNORE TABLE spip_auteurs_articles DROP INDEX id_auteur");
		spip_query("ALTER IGNORE TABLE spip_auteurs_rubriques DROP INDEX id_auteur");
		spip_query("ALTER IGNORE TABLE spip_auteurs_messages DROP INDEX id_auteur");
		spip_query("ALTER IGNORE TABLE spip_mots_articles DROP INDEX id_article");
		spip_query("ALTER IGNORE TABLE spip_mots_breves DROP INDEX id_breve");
		spip_query("ALTER IGNORE TABLE spip_mots_rubriques DROP INDEX id_rubrique");
		spip_query("ALTER IGNORE TABLE spip_mots_syndic DROP INDEX id_syndic");
		spip_query("ALTER IGNORE TABLE spip_mots_forum DROP INDEX id_forum");
		spip_query("ALTER IGNORE TABLE spip_mots_documents DROP INDEX id_document");
		spip_query("ALTER IGNORE TABLE spip_caches DROP	INDEX fichier");
		maj_version(1.911);
	}

	// Le logo du site n'est plus le logo par defaut des rubriques
	// mais pour assurer la compatibilite ascendante, on le duplique
	if (upgrade_vers(1.912, $version_installee, $version_cible)) {
		@copy(_DIR_IMG.'rubon0.gif', _DIR_IMG.'siteon0.gif');
		@copy(_DIR_IMG.'ruboff0.gif', _DIR_IMG.'siteoff0.gif');
		@copy(_DIR_IMG.'rubon0.jpg', _DIR_IMG.'siteon0.jpg');
		@copy(_DIR_IMG.'ruboff0.jpg', _DIR_IMG.'siteoff0.jpg');
		@copy(_DIR_IMG.'rubon0.png', _DIR_IMG.'siteon0.png');
		@copy(_DIR_IMG.'ruboff0.png', _DIR_IMG.'siteoff0.png');
		maj_version(1.912);
	}

	// suppression de auteur_modif qui n'est plus utilise nulle part
	if (upgrade_vers(1.913, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_articles DROP auteur_modif");
		maj_version(1.913);
	}

	// Ajout de SVG
	if (upgrade_vers(1.914, $version_installee, $version_cible)) {
		spip_query("INSERT IGNORE INTO spip_types_documents (extension, titre, inclus) VALUES ('svg', 'Scalable Vector Graphics', 'embed')");
		spip_query("UPDATE spip_types_documents	SET mime_type='image/svg+xml' WHERE extension='svg'");
		maj_version(1.914);
	}

	// Ajout de plein de type mime
	if (upgrade_vers(1.915, $version_installee, $version_cible)) {
		maj_version(1.915);
	}
	// refaire l'upgrade 1.905 qui a pu foirer en partie a cause de la requete ALTER sur `spip_meta`
	if (upgrade_vers(1.916, $version_installee, $version_cible)) {
		// agrandir le champ "valeur" de spip_meta pour pouvoir y stocker
		// des choses plus sympa
		spip_query("ALTER TABLE spip_meta CHANGE `valeur` `valeur` TEXT");
		include_spip('inc/indexation');
		update_index_tables();
		maj_version(1.916);
	}
	if (upgrade_vers(1.917, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_documents DROP inclus");
		maj_version(1.917);
	}

	// Permettre d'enregistrer un numero IP dans les revisions d'articles
	// a la place de l'id_auteur
	if (upgrade_vers(1.918, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_versions CHANGE `id_auteur` `id_auteur` VARCHAR(23)");
		maj_version(1.918);
	}

	if (upgrade_vers(1.919, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_ajax_fonc DROP id_auteur");
		maj_version('1.919');
	}

	if (upgrade_vers(1.920, $version_installee, $version_cible)) {
		spip_query("ALTER IGNORE TABLE spip_documents_articles ADD PRIMARY KEY (id_article, id_document)");
		spip_query("ALTER IGNORE TABLE spip_documents_breves ADD PRIMARY KEY (id_breve, id_document)");
		spip_query("ALTER IGNORE TABLE spip_documents_rubriques ADD PRIMARY KEY (id_rubrique, id_document)");
		spip_query("ALTER IGNORE TABLE spip_documents_articles DROP INDEX id_article");
		spip_query("ALTER IGNORE TABLE spip_documents_breves DROP INDEX id_breve");
		spip_query("ALTER IGNORE TABLE spip_documents_rubriques DROP INDEX id_rubrique");
		maj_version('1.920');
	}
	if (upgrade_vers(1.922, $version_installee, $version_cible)) {
		spip_query("ALTER TABLE spip_meta ADD impt ENUM('non', 'oui') DEFAULT 'oui' NOT NULL AFTER valeur");
		$meta_serveur = array('version_installee','adresse_site','alea_ephemere_ancien','alea_ephemere','alea_ephemere_date','langue_site','langues_proposees','date_calcul_rubriques','derniere_modif','optimiser_table','drapeau_edition','creer_preview','taille_preview','creer_htpasswd','creer_htaccess','gd_formats_read','gd_formats',
		'netpbm_formats','formats_graphiques','image_process','plugin_header','plugin');
		foreach($meta_serveur as $nom)
			spip_query("UPDATE spip_meta SET impt='non' WHERE nom="._q($nom));
		maj_version('1.922');
	}
	if (upgrade_vers(1.923, $version_installee, $version_cible)) {
		if (isset($GLOBALS['meta']['IMPORT_tables_noimport'])){
			$IMPORT_tables_noimport = unserialize($GLOBALS['meta']['IMPORT_tables_noimport']);
			foreach ($IMPORT_tables_noimport as $key=>$table)
				if ($table=='spip_meta') unset($IMPORT_tables_noimport[$key]);
			include_spip('inc/meta');
			ecrire_meta('IMPORT_tables_noimport',serialize($IMPORT_tables_noimport),'non');
		}
		maj_version('1.923');
	}

	if (upgrade_vers(1.924, $version_installee, $version_cible)) {
		spip_query('DROP TABLE spip_ajax_fonc');
		maj_version('1.924');
	}

	if (upgrade_vers(1.925, $version_installee, $version_cible)) {
		include_spip('inc/flock');
		/* deplacement des sessions */
		$f_session = preg_files('data', 'session_');
		$repertoire = _DIR_SESSIONS;
		if(!@file_exists($repertoire)) {
			$repertoire = preg_replace(','._DIR_TMP.',', '', $repertoire);
			$repertoire = sous_repertoire(_DIR_TMP, $repertoire);
		}
		foreach($f_session as $f) {
			$d = basename($f);
			@copy($f, $repertoire.$d);
		}
		/* deplacement des visites */
		$f_visites = preg_files('data/visites');
		$repertoire = sous_repertoire(_DIR_TMP, 'visites');
		foreach($f_visites as $f) {
			$d = basename($f);
			@copy($f, $repertoire.$d);
		}
		/* deplacement des upload */
		$auteurs = array();
		$req = spip_query("SELECT login FROM spip_auteurs WHERE statut = '0minirezo'");
		while($row = spip_fetch_array($req))
			$auteurs[] = $row['login']; 
		$f_upload = preg_files('upload', -1, 10000, $auteurs);
		$repertoire = _DIR_TRANSFERT;
		if(!@file_exists($repertoire)) {
			$repertoire = preg_replace(','._DIR_TMP.',', '', $repertoire);
			$repertoire = sous_repertoire(_DIR_TMP, $repertoire);
		}
		foreach($auteurs as $login) {
			if(is_dir('upload/'.$login))
				$sous_repertoire = sous_repertoire(_DIR_TRANSFERT, $login);
		}
		foreach($f_upload as $f) {
			@copy($f, _DIR_TMP.$f);
		}
		/* deplacement des dumps */
		$f_session = preg_files('data', 'dump');
		$repertoire = _DIR_DUMP;
		if(!@file_exists($repertoire)) {
			$repertoire = preg_replace(','._DIR_TMP.',', '', $repertoire);
			$repertoire = sous_repertoire(_DIR_TMP, $repertoire);
		}
		foreach($f_session as $f) {
			$d = basename($f);
			@copy($f, $repertoire.$d);
		}
		maj_version('1.925');
	}
	// Ajout de MP4
	if (upgrade_vers(1.926, $version_installee, $version_cible)) {
		spip_query("INSERT IGNORE INTO spip_types_documents (extension, titre, inclus) VALUES ('mp4', 'MPEG4', 'embed')");
		spip_query("UPDATE spip_types_documents	SET mime_type='application/mp4' WHERE extension='mp4'");
		maj_version('1.926');
	}
	// Ajout de CSV
	if (upgrade_vers(1.927, $version_installee, $version_cible)) {
		//pas de psd en <img> 
		spip_query("UPDATE spip_types_documents	SET inclus='non' WHERE extension='psd'");
		//ajout csv
		spip_query("INSERT IGNORE INTO spip_types_documents (extension, titre) VALUES ('csv', 'CSV')");
		spip_query("UPDATE spip_types_documents	SET mime_type='text/csv' WHERE extension='csv'");
		//ajout mkv
		spip_query("INSERT IGNORE INTO spip_types_documents (extension, titre, inclus) VALUES ('mkv', 'Matroska Video', 'embed')");
		spip_query("UPDATE spip_types_documents	SET mime_type='video/x-mkv' WHERE extension='mkv'");
		//ajout mka
		spip_query("INSERT IGNORE INTO spip_types_documents (extension, titre, inclus) VALUES ('mka', 'Matroska Audio', 'embed')");
		spip_query("UPDATE spip_types_documents	SET mime_type='audio/x-mka' WHERE extension='mka'");
		//ajout kml
		spip_query("INSERT IGNORE INTO spip_types_documents (extension, titre) VALUES ('kml', 'Keyhole Markup Language')");
		spip_query("UPDATE spip_types_documents	SET mime_type='application/vnd.google-earth.kml+xml' WHERE extension='kml'");
		//ajout kmz
		spip_query("INSERT IGNORE INTO spip_types_documents (extension, titre) VALUES ('kmz', 'Google Earth Placemark File')");
		spip_query("UPDATE spip_types_documents	SET mime_type='application/vnd.google-earth.kmz' WHERE extension='kmz'");
		maj_version('1.927');
	}

}

?>
