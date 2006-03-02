<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


//
if (!defined("_ECRIRE_INC_VERSION")) return;

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

function maj_base() {
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
	if ($version_installee < 0.98) {

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

	if ($version_installee < 0.99) {
	
		$query = "SELECT DISTINCT id_article FROM spip_forum WHERE id_article!=0 AND id_parent=0";
		$result = spip_query($query);
		while ($row = spip_fetch_array($result)) {
			unset($forums_article);
			$id_article = $row['id_article'];
			$query2 = "SELECT id_forum FROM spip_forum WHERE id_article=$id_article";
			for (;;) {
				$result2 = spip_query($query2);
				unset($forums);
				while ($row2 = spip_fetch_array($result2)) $forums[] = $row2['id_forum'];
				if (!$forums) break;
				$forums = join(',', $forums);
				$forums_article[] = $forums;
				$query2 = "SELECT id_forum FROM spip_forum WHERE id_parent IN ($forums)";
			}
			$forums_article = join(',', $forums_article);
			$query3 = "UPDATE spip_forum SET id_article=$id_article WHERE id_forum IN ($forums_article)";
			spip_query($query3);
		}
	
		$query = "SELECT DISTINCT id_breve FROM spip_forum WHERE id_breve!=0 AND id_parent=0";
		$result = spip_query($query);
		while ($row = spip_fetch_array($result)) {
			unset($forums_breve);
			$id_breve = $row['id_breve'];
			$query2 = "SELECT id_forum FROM spip_forum WHERE id_breve=$id_breve";
			for (;;) {
				$result2 = spip_query($query2);
				unset($forums);
				while ($row2 = spip_fetch_array($result2)) $forums[] = $row2['id_forum'];
				if (!$forums) break;
				$forums = join(',', $forums);
				$forums_breve[] = $forums;
				$query2 = "SELECT id_forum FROM spip_forum WHERE id_parent IN ($forums)";
			}
			$forums_breve = join(',', $forums_breve);
			$query3 = "UPDATE spip_forum SET id_breve=$id_breve WHERE id_forum IN ($forums_breve)";
			spip_query($query3);
		}
	
		$query = "SELECT DISTINCT id_rubrique FROM spip_forum WHERE id_rubrique!=0 AND id_parent=0";
		$result = spip_query($query);
		while ($row = spip_fetch_array($result)) {
			unset($forums_rubrique);
			$id_rubrique = $row['id_rubrique'];
			$query2 = "SELECT id_forum FROM spip_forum WHERE id_rubrique=$id_rubrique";
			for (;;) {
				$result2 = spip_query($query2);
				unset($forums);
				while ($row2 = spip_fetch_array($result2)) $forums[] = $row2['id_forum'];
				if (!$forums) break;
				$forums = join(',', $forums);
				$forums_rubrique[] = $forums;
				$query2 = "SELECT id_forum FROM spip_forum WHERE id_parent IN ($forums)";
			}
			$forums_rubrique = join(',', $forums_rubrique);
			$query3 = "UPDATE spip_forum SET id_rubrique=$id_rubrique WHERE id_forum IN ($forums_rubrique)";
			spip_query($query3);
		}
		maj_version (0.99);
	}

	if ($version_installee < 0.997) {
		spip_query("DROP TABLE spip_index");
		maj_version (0.997);
	}

	if ($version_installee < 0.999) {
		global $htsalt;
		spip_query("ALTER TABLE spip_auteurs CHANGE pass pass tinyblob NOT NULL");
		spip_query("ALTER TABLE spip_auteurs ADD htpass tinyblob NOT NULL");
		$query = "SELECT id_auteur, pass FROM spip_auteurs WHERE pass!=''";
		$result = spip_query($query);
		while (list($id_auteur, $pass) = spip_fetch_array($result)) {
			$htpass = generer_htpass($pass);
			$pass = md5($pass);
			spip_query("UPDATE spip_auteurs SET pass='$pass', htpass='$htpass' WHERE id_auteur=$id_auteur");
		}
		maj_version (0.999);
	}
	
	if ($version_installee < 1.01) {
		spip_query("UPDATE spip_forum SET statut='publie' WHERE statut=''");
		maj_version (1.01);
	}
	
	if ($version_installee < 1.02) {
		spip_query("ALTER TABLE spip_forum ADD id_auteur BIGINT DEFAULT '0' NOT NULL");
		maj_version (1.02);
	}

	if ($version_installee < 1.03) {
		spip_query("DROP TABLE spip_maj");
		maj_version (1.03);
	}

	if ($version_installee < 1.04) {
		spip_query("ALTER TABLE spip_articles ADD accepter_forum VARCHAR(3)");
		maj_version (1.04);
	}

	if ($version_installee < 1.05) {
		spip_query("DROP TABLE spip_petition");
		spip_query("DROP TABLE spip_signatures_petition");
		maj_version (1.05);
	}

	if ($version_installee < 1.1) {
		spip_query("DROP TABLE spip_petition");
		spip_query("DROP TABLE spip_signatures_petition");
		maj_version (1.1);
	}

	// Correction de l'oubli des modifs creations depuis 1.04
	if ($version_installee < 1.204) {
		spip_query("ALTER TABLE spip_articles ADD accepter_forum VARCHAR(3) NOT NULL");
		spip_query("ALTER TABLE spip_forum ADD id_message bigint(21) NOT NULL");
		spip_query("ALTER TABLE spip_forum ADD INDEX id_message (id_message)");
		spip_query("ALTER TABLE spip_auteurs ADD en_ligne datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		spip_query("ALTER TABLE spip_auteurs ADD imessage VARCHAR(3) not null");
		spip_query("ALTER TABLE spip_auteurs ADD messagerie VARCHAR(3) not null");
		maj_version (1.204);
	}

	if ($version_installee < 1.207) {
		spip_query("ALTER TABLE spip_rubriques DROP INDEX id_rubrique");
		spip_query("ALTER TABLE spip_rubriques ADD INDEX id_parent (id_parent)");
		spip_query("ALTER TABLE spip_rubriques ADD statut VARCHAR(10) NOT NULL");
		// Declencher le calcul des rubriques publiques
		spip_query("REPLACE spip_meta (nom, valeur) VALUES ('calculer_rubriques', 'oui')");
		maj_version (1.207);
	}

	if ($version_installee < 1.208) {
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

	if ($version_installee < 1.209) {
		spip_query("ALTER TABLE spip_syndic ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_syndic_articles ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_messages ADD maj TIMESTAMP");
		maj_version (1.209);
	}

	if ($version_installee < 1.210) {
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

	if ($version_installee < 1.3) {
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

	if ($version_installee < 1.301) {
		spip_query("ALTER TABLE spip_forum ADD id_syndic bigint(21) DEFAULT '0' NOT NULL");
		maj_version (1.301);
	}

	if ($version_installee < 1.302) {
		# spip_query("ALTER TABLE spip_forum_cache DROP PRIMARY KEY");
		# spip_query("ALTER TABLE spip_forum_cache DROP INDEX fichier");
		# spip_query("ALTER TABLE spip_forum_cache ADD PRIMARY KEY (fichier, id_forum, id_article, id_rubrique, id_breve, id_syndic)");
		spip_query("ALTER TABLE spip_forum ADD INDEX id_syndic (id_syndic)");
		maj_version (1.302);
	}

	if ($version_installee < 1.303) {
		spip_query("ALTER TABLE spip_rubriques ADD date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		spip_query("ALTER TABLE spip_syndic ADD date_syndic datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		spip_query("UPDATE spip_syndic SET date_syndic=date");
		maj_version (1.303);
	}

	if ($version_installee < 1.306) {
		spip_query("DROP TABLE spip_index_syndic_articles");
		spip_query("ALTER TABLE spip_syndic ADD date_index datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		spip_query("ALTER TABLE spip_syndic ADD INDEX date_index (date_index)");
		maj_version (1.306);
	}

	if ($version_installee < 1.307) {
		spip_query("ALTER TABLE spip_syndic_articles ADD descriptif blob NOT NULL");
		maj_version (1.307);
	}

	if ($version_installee < 1.404) {
		spip_query("UPDATE spip_mots SET type='Mots sans groupe...' WHERE type=''");

		$result = spip_query("SELECT * FROM spip_mots GROUP BY type");
		while($row = spip_fetch_array($result)) {
				$type = addslashes($row['type']);
				spip_query("INSERT INTO spip_groupes_mots 
					(titre, unseul, obligatoire, articles, breves, rubriques, syndic, 0minirezo, 1comite, 6forum)
					VALUES (\"$type\", 'non', 'non', 'oui', 'oui', 'non', 'oui', 'oui', 'oui', 'non')");
		}
		spip_query("DELETE FROM spip_mots WHERE titre='kawax'");
		maj_version (1.404);
	}

	if ($version_installee < 1.405) {
		spip_query("ALTER TABLE spip_mots ADD id_groupe bigint(21) NOT NULL");
	
		$result = spip_query("SELECT * FROM spip_groupes_mots");
		while($row = spip_fetch_array($result)) {
				$id_groupe = addslashes($row['id_groupe']);
				$type = addslashes($row['titre']);
				spip_query("UPDATE spip_mots SET id_groupe = '$id_groupe' WHERE type='$type'");
		}
		maj_version (1.405);
	}

	if ($version_installee < 1.408) {
		// Images articles passent dans spip_documents
		$query = "SELECT id_article, images FROM spip_articles WHERE LENGTH(images) > 0";
		$result = spip_query($query);

		$types = array('jpg' => 1, 'png' => 2, 'gif' => 3);

		while ($row = @spip_fetch_array($result)) {
			$id_article = $row['id_article'];
			$images = $row['images'];
			$images = explode(",", $images);
			reset($images);
			$replace = '_orig_';
			while (list (, $val) = each($images)) {
				$image = explode("|", $val);
				$fichier = $image[0];
				$largeur = $image[1];
				$hauteur = $image[2];
				ereg("-([0-9]+)\.(gif|jpg|png)$", $fichier, $match);
				$id_type = intval($types[$match[2]]);
				$num_img = $match[1];
				$fichier = _DIR_IMG . $fichier;
				$taille = @filesize($fichier);
				$id_document = spip_abstract_insert("spip_documents", 
							   "(titre, id_type, fichier, mode, largeur, hauteur, taille)",
							   "('image $largeur x $hauteur', $id_type, '$fichier', 'vignette', '$largeur', '$hauteur', '$taille')");

				if ($id_document > 0) {
					spip_query("INSERT INTO spip_documents_articles (id_document, id_article) VALUES ($id_document, $id_article)");
					$replace = "REPLACE($replace, '<IMG$num_img|', '<IM_$id_document|')";
				} else {
					echo _T('texte_erreur_mise_niveau_base', array('fichier' => $fichier, 'id_article' => $id_article));
					exit;
				}
			}
			$replace = "REPLACE($replace, '<IM_', '<IMG')";
			$replace_chapo = ereg_replace('_orig_', 'chapo', $replace);
			$replace_descriptif = ereg_replace('_orig_', 'descriptif', $replace);
			$replace_texte = ereg_replace('_orig_', 'texte', $replace);
			$replace_ps = ereg_replace('_orig_', 'ps', $replace);
			$query = "UPDATE spip_articles ".
				"SET chapo=$replace_chapo, descriptif=$replace_descriptif, texte=$replace_texte, ps=$replace_ps ".
				"WHERE id_article=$id_article";
			spip_query($query);
		}
		spip_query("ALTER TABLE spip_articles DROP images");
		maj_version (1.408);
	}

	if ($version_installee < 1.414) {
		// Forum par defaut "en dur" dans les spip_articles
		// -> non, prio (priori), pos (posteriori), abo (abonnement)
		include_spip('inc/meta');
		$accepter_forum = substr($GLOBALS['meta']["forums_publics"],0,3) ;
		$query = "ALTER TABLE spip_articles CHANGE accepter_forum accepter_forum CHAR(3) NOT NULL";
		$result = spip_query($query);
		$query = "UPDATE spip_articles SET accepter_forum='$accepter_forum' WHERE accepter_forum != 'non'";
		$result = spip_query($query);
		maj_version (1.414);
	}

	/*
	if ($version_installee == 1.415) {
		spip_query("ALTER TABLE spip_documents DROP inclus");
		maj_version (1.415);
	}
	*/

	if ($version_installee < 1.417) {
		spip_query("ALTER TABLE spip_syndic_articles DROP date_index");
		maj_version (1.417);
	}

	if ($version_installee < 1.418) {
		$query = "SELECT * FROM spip_auteurs WHERE statut = '0minirezo' AND email != '' ORDER BY id_auteur LIMIT 1";
		$result = spip_query($query);
		if ($webmaster = spip_fetch_array($result)) {
			include_spip('inc/meta');
			ecrire_meta('email_webmaster', $webmaster['email']);
			ecrire_metas();
		}
		maj_version (1.418);
	}

	if ($version_installee < 1.419) {
		$query = "ALTER TABLE spip_auteurs ADD alea_actuel TINYTEXT DEFAULT ''";
		spip_query($query);
		$query = "ALTER TABLE spip_auteurs ADD alea_futur TINYTEXT DEFAULT ''";
		spip_query($query);
		$query = "UPDATE spip_auteurs SET alea_futur = FLOOR(32000*RAND())";
		spip_query($query);
		maj_version (1.419);
	}

	if ($version_installee < 1.420) {
		$query = "UPDATE spip_auteurs SET alea_actuel='' WHERE statut='nouveau'";
		spip_query($query);
		maj_version (1.420);
	}
	
	if ($version_installee < 1.421) {
		$query = "ALTER TABLE spip_articles ADD auteur_modif bigint(21) DEFAULT '0' NOT NULL";
		spip_query($query);
		$query = "ALTER TABLE spip_articles ADD date_modif datetime DEFAULT '0000-00-00 00:00:00' NOT NULL";
		spip_query($query);
		maj_version (1.421);
	}

	if ($version_installee < 1.432) {
		spip_query("ALTER TABLE spip_articles DROP referers");
		$query = "ALTER TABLE spip_articles ADD referers INTEGER DEFAULT '0' NOT NULL";
		spip_query($query);
		$query = "ALTER TABLE spip_articles ADD popularite INTEGER DEFAULT '0' NOT NULL";
		spip_query($query);
		maj_version (1.432);
	}

	if ($version_installee < 1.436) {
		$query = "ALTER TABLE spip_documents ADD date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL";
		spip_query($query);
		maj_version (1.436);
	}

	if ($version_installee < 1.437) {
		spip_query("ALTER TABLE spip_visites ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_visites_referers ADD maj TIMESTAMP");
		maj_version (1.437);
	}

	if ($version_installee < 1.438) {
		spip_query("ALTER TABLE spip_articles ADD INDEX id_secteur (id_secteur)");
		spip_query("ALTER TABLE spip_articles ADD INDEX statut (statut, date)");
		maj_version (1.438);
	}

	if ($version_installee < 1.439) {
		spip_query("ALTER TABLE spip_syndic ADD INDEX statut (statut, date_syndic)");
		spip_query("ALTER TABLE spip_syndic_articles ADD INDEX statut (statut)");
		spip_query("ALTER TABLE spip_syndic_articles CHANGE url url VARCHAR(255) NOT NULL");
		spip_query("ALTER TABLE spip_syndic_articles ADD INDEX url (url)");
		maj_version (1.439);
	}

	if ($version_installee < 1.440) {
		spip_query("ALTER TABLE spip_visites_temp CHANGE ip ip INTEGER UNSIGNED NOT NULL");
		maj_version (1.440);
	}

	if ($version_installee < 1.441) {
		spip_query("ALTER TABLE spip_visites_temp CHANGE date date DATE NOT NULL");
		spip_query("ALTER TABLE spip_visites CHANGE date date DATE NOT NULL");
		spip_query("ALTER TABLE spip_visites_referers CHANGE date date DATE NOT NULL");
		maj_version (1.441);
	}

	if ($version_installee < 1.442) {
		$query = "ALTER TABLE spip_auteurs ADD prefs TINYTEXT NOT NULL";
		spip_query($query);
		maj_version (1.442);
	}

	if ($version_installee < 1.443) {
		spip_query("ALTER TABLE spip_auteurs CHANGE login login VARCHAR(255) BINARY NOT NULL");
		spip_query("ALTER TABLE spip_auteurs CHANGE statut statut VARCHAR(255) NOT NULL");
		spip_query("ALTER TABLE spip_auteurs ADD INDEX login (login)");
		spip_query("ALTER TABLE spip_auteurs ADD INDEX statut (statut)");
		maj_version (1.443);
	}

	if ($version_installee < 1.444) {
		spip_query("ALTER TABLE spip_syndic ADD moderation VARCHAR(3) NOT NULL");
		maj_version (1.444);
	}

	if ($version_installee < 1.457) {
		spip_query("DROP TABLE spip_visites");
		spip_query("DROP TABLE spip_visites_temp");
		spip_query("DROP TABLE spip_visites_referers");
		creer_base(); // crade, a ameliorer :-((
		maj_version (1.457);
	}

	if ($version_installee < 1.458) {
		spip_query("ALTER TABLE spip_auteurs ADD cookie_oubli TINYTEXT NOT NULL");
		maj_version (1.458);
	}

	if ($version_installee < 1.459) {
		$result = spip_query("SELECT type FROM spip_mots GROUP BY type");
		while ($row = spip_fetch_array($result)) {
			$type = addslashes($row['type']);
			$res = spip_query("SELECT * FROM spip_groupes_mots
				WHERE titre='$type'");
			if (spip_num_rows($res) == 0) {
			  if ($id_groupe = spip_abstract_insert("spip_groupes_mots", 
						       "(titre, unseul, obligatoire, articles, breves, rubriques, syndic, 0minirezo, 1comite, 6forum)",
						       "('$type', 'non', 'non', 'oui', 'oui', 'non', 'oui', 'oui', 'oui', 'non')"))

					spip_query("UPDATE spip_mots SET id_groupe = '$id_groupe' WHERE type='$type'");
			}
		}
		spip_query("UPDATE spip_articles SET popularite=0");
		maj_version (1.459);
	}

	if ($version_installee < 1.460) {
		// remettre les mots dans les groupes dupliques par erreur
		// dans la precedente version du paragraphe de maj 1.459
		// et supprimer ceux-ci
		$result = spip_query("SELECT * FROM spip_groupes_mots ORDER BY id_groupe");
		while ($row = spip_fetch_array($result)) {
			$titre = addslashes($row['titre']);
			if (! $vu[$titre] ) {
				$vu[$titre] = true;
				$id_groupe = $row['id_groupe'];
				spip_query ("UPDATE spip_mots SET id_groupe=$id_groupe WHERE type='$titre'");
				spip_query ("DELETE FROM spip_groupes_mots WHERE titre='$titre' AND id_groupe<>$id_groupe");
			}
		}
		maj_version (1.460);
	}

	if ($version_installee < 1.462) {
		spip_query("UPDATE spip_types_documents SET inclus='embed' WHERE inclus!='non' AND extension IN ".
			"('aiff', 'asf', 'avi', 'mid', 'mov', 'mp3', 'mpg', 'ogg', 'qt', 'ra', 'ram', 'rm', 'swf', 'wav', 'wmv')");
		maj_version (1.462);
	}

	if ($version_installee < 1.463) {
		spip_query("ALTER TABLE spip_articles CHANGE popularite popularite DOUBLE");
		spip_query("ALTER TABLE spip_visites_temp ADD maj TIMESTAMP");
		spip_query("ALTER TABLE spip_referers_temp ADD maj TIMESTAMP");
		maj_version (1.463);
	}

	// l'upgrade < 1.462 ci-dessus etait fausse, d'ou correctif
	if (($version_installee < 1.464) AND ($version_installee >= 1.462)) {
		$res = spip_query("SELECT id_type, extension FROM spip_types_documents WHERE id_type NOT IN (1,2,3)");
		while ($row = spip_fetch_array($res)) {
			$extension = $row['extension'];
			$id_type = $row['id_type'];
			spip_query("UPDATE spip_documents SET id_type=$id_type
				WHERE fichier like '%.$extension'");
		}
		maj_version (1.464);
	}

	if ($version_installee < 1.465) {
		spip_query("ALTER TABLE spip_articles CHANGE popularite popularite DOUBLE NOT NULL");
		maj_version (1.465);
	}

	if ($version_installee < 1.466) {
		spip_query("ALTER TABLE spip_auteurs ADD source VARCHAR(10) DEFAULT 'spip' NOT NULL");
		maj_version (1.466);
	}

	if ($version_installee < 1.468) {
		spip_query("ALTER TABLE spip_auteurs ADD INDEX en_ligne (en_ligne)");
		spip_query("ALTER TABLE spip_forum ADD INDEX statut (statut, date_heure)");
		maj_version (1.468);
	}

	if ($version_installee < 1.470) {
		if ($version_installee >= 1.467) {	// annule les "listes de diff"
			spip_query("DROP TABLE spip_listes");
			spip_query("ALTER TABLE spip_auteurs DROP abonne");
			spip_query("ALTER TABLE spip_auteurs DROP abonne_pass");
		}
		maj_version (1.470);
	}

	if ($version_installee < 1.471) {
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

	if ($version_installee < 1.472) {
		spip_query("ALTER TABLE spip_referers ADD visites_jour INTEGER UNSIGNED NOT NULL");
		maj_version (1.472);
	}

	if ($version_installee < 1.473) {
		spip_query("UPDATE spip_syndic_articles SET url = REPLACE(url, '&amp;', '&')");
		spip_query("UPDATE spip_syndic SET url_site = REPLACE(url_site, '&amp;', '&')");
		maj_version (1.473);
	}

	if ($version_installee < 1.600) {
		include_spip('inc/indexation');
		purger_index();
		creer_liste_indexation();
		maj_version (1.600);
	}

	if ($version_installee < 1.601) {
		spip_query("ALTER TABLE spip_forum ADD INDEX id_syndic (id_syndic)");
		maj_version (1.601);
	}

	if ($version_installee < 1.603) {
		// supprimer les fichiers deplaces
		@unlink('inc_meta_cache.php');
		@unlink('inc_meta_cache.php3');
		@unlink('data/engines-list.ini');
		maj_version (1.603);
	}

	if ($version_installee < 1.604) {
		spip_query("ALTER TABLE spip_auteurs ADD lang VARCHAR(10) DEFAULT '' NOT NULL");
		$u = spip_query("SELECT * FROM spip_auteurs WHERE prefs LIKE '%spip_lang%'");
		while ($row = spip_fetch_array($u)) {
			$prefs = unserialize($row['prefs']);
			$l = $prefs['spip_lang'];
			unset ($prefs['spip_lang']);
			spip_query ("UPDATE spip_auteurs SET lang='".addslashes($l)."',
				prefs='".addslashes(serialize($prefs))."'
				WHERE id_auteur=".$row['id_auteur']);
		}
		maj_version (1.604, spip_query("SELECT lang FROM spip_auteurs"));
	}

	if ($version_installee < 1.702) {
		spip_query("ALTER TABLE spip_articles ADD extra longblob NULL");
		spip_query("ALTER TABLE spip_auteurs ADD extra longblob NULL");
		spip_query("ALTER TABLE spip_breves ADD extra longblob NULL");
		spip_query("ALTER TABLE spip_rubriques ADD extra longblob NULL");
		spip_query("ALTER TABLE spip_mots ADD extra longblob NULL");

		// recuperer les eventuels 'supplement' installes en 1.701
		if ($version_installee == 1.701) {
			spip_query ("UPDATE spip_articles SET extra = supplement");
			spip_query ("ALTER TABLE spip_articles DROP supplement");
			spip_query ("UPDATE spip_auteurs SET extra = supplement");
			spip_query ("ALTER TABLE spip_auteurs DROP supplement");
			spip_query ("UPDATE spip_breves SET extra = supplement");
			spip_query ("ALTER TABLE spip_breves DROP supplement");
			spip_query ("UPDATE spip_rubriques SET extra = supplement");
			spip_query ("ALTER TABLE spip_rubriques DROP supplement");
			spip_query ("UPDATE spip_mots SET extra = supplement");
			spip_query ("ALTER TABLE spip_mots DROP supplement");
		}
		maj_version (1.702,
			spip_query("SELECT extra FROM spip_articles")
			&& spip_query("SELECT extra FROM spip_auteurs")
			&& spip_query("SELECT extra FROM spip_breves")
			&& spip_query("SELECT extra FROM spip_rubriques")
			&& spip_query("SELECT extra FROM spip_mots")
			);
	}

	if ($version_installee < 1.703) {
		spip_query("ALTER TABLE spip_articles ADD lang VARCHAR(10) DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_rubriques ADD lang VARCHAR(10) DEFAULT '' NOT NULL");
		maj_version (1.703);
	}

	if ($version_installee < 1.704) {
		spip_query("ALTER TABLE spip_articles ADD INDEX lang (lang)");
		spip_query("ALTER TABLE spip_auteurs ADD INDEX lang (lang)");
		spip_query("ALTER TABLE spip_rubriques ADD INDEX lang (lang)");
		maj_version (1.704);
	}

	if ($version_installee < 1.705) {
		spip_query("ALTER TABLE spip_articles ADD langue_choisie VARCHAR(3) DEFAULT 'non'");
		spip_query("ALTER TABLE spip_rubriques ADD langue_choisie VARCHAR(3) DEFAULT 'non'");
		maj_version (1.705);
	}

	if ($version_installee < 1.707) {
		spip_query("UPDATE spip_articles SET langue_choisie='oui' WHERE MID(lang,1,1) != '.' AND lang != ''");
		spip_query("UPDATE spip_articles SET lang=MID(lang,2,8) WHERE langue_choisie = 'non'");
		spip_query("UPDATE spip_rubriques SET langue_choisie='oui' WHERE MID(lang,1,1) != '.' AND lang != ''");
		spip_query("UPDATE spip_rubriques SET lang=MID(lang,2,8) WHERE langue_choisie = 'non'");
		maj_version (1.707);
	}

	if ($version_installee < 1.708) {
		spip_query("ALTER TABLE spip_breves ADD lang VARCHAR(10) DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_breves ADD langue_choisie VARCHAR(3) DEFAULT 'non'");
		maj_version (1.708);
	}

	if ($version_installee < 1.709) {
		spip_query("ALTER TABLE spip_articles ADD id_trad bigint(21) DEFAULT '0' NOT NULL");
		spip_query("ALTER TABLE spip_articles ADD INDEX id_trad (id_trad)");
		maj_version (1.709);
	}

	if ($version_installee < 1.717) {
		spip_query("ALTER TABLE spip_articles ADD INDEX date_modif (date_modif)");
		maj_version (1.717);
	}

	if ($version_installee < 1.718) {
		spip_query("ALTER TABLE spip_referers DROP domaine");
		spip_query("ALTER TABLE spip_referers_articles DROP domaine");
		spip_query("ALTER TABLE spip_referers_temp DROP domaine");
		maj_version (1.718);
	}

	if ($version_installee < 1.722) {
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

	if ($version_installee < 1.723) {
		if ($version_installee == 1.722) {
			spip_query("ALTER TABLE spip_articles MODIFY url_site VARCHAR(255) NOT NULL");
			spip_query("ALTER TABLE spip_articles DROP INDEX url_site;");
			spip_query("ALTER TABLE spip_articles ADD INDEX url_site (url_site);");
		}
		maj_version (1.723);
	}

	if ($version_installee < 1.724) {
		spip_query("ALTER TABLE spip_messages ADD date_fin datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		maj_version (1.724);
	}

	if ($version_installee < 1.726) {
		spip_query("ALTER TABLE spip_auteurs ADD low_sec tinytext NOT NULL");
		maj_version (1.726);
	}

	if ($version_installee < 1.727) {
		// occitans : oci_xx -> oc_xx
		spip_query("UPDATE spip_auteurs SET lang=REPLACE(lang,'oci_', 'oc_') WHERE lang LIKE 'oci_%'");
		spip_query("UPDATE spip_rubriques SET lang=REPLACE(lang,'oci_', 'oc_') WHERE lang LIKE 'oci_%'");
		spip_query("UPDATE spip_articles SET lang=REPLACE(lang,'oci_', 'oc_') WHERE lang LIKE 'oci_%'");
		spip_query("UPDATE spip_breves SET lang=REPLACE(lang,'oci_', 'oc_') WHERE lang LIKE 'oci_%'");
		maj_version (1.727);
	}

	// Ici version 1.7 officielle

	if ($version_installee < 1.728) {
		spip_query("ALTER TABLE spip_articles ADD id_version int unsigned DEFAULT '0' NOT NULL");
		maj_version (1.728);
	}

	if ($version_installee < 1.730) {
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

	if ($version_installee < 1.731) {	// reindexer les docs allemands et vietnamiens
		spip_query("UPDATE spip_articles SET idx='1' where lang IN ('de','vi')");
		spip_query("UPDATE spip_rubriques SET idx='1' where lang IN ('de','vi')");
		spip_query("UPDATE spip_breves SET idx='1' where lang IN ('de','vi')");
		spip_query("UPDATE spip_auteurs SET idx='1' where lang IN ('de','vi')");
		maj_version (1.731);
	}

	if ($version_installee < 1.732) {	// en correction d'un vieux truc qui avait fait sauter le champ inclus sur les bases version 1.415
		spip_query ("ALTER TABLE spip_documents ADD inclus  VARCHAR(3) DEFAULT 'non'");
		maj_version (1.732);
	}

	if ($version_installee < 1.733) {
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

	if ($version_installee < 1.801) {
		spip_query("ALTER TABLE spip_rubriques
			ADD statut_tmp VARCHAR(10) NOT NULL,
			ADD date_tmp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
		include_spip('inc/rubriques');
		calculer_rubriques();
		maj_version(1.801);
	}

	// Nouvelles tables d'invalidation
	if ($version_installee < 1.802) {
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
	if ($version_installee < 1.803) {

	#	27 AOUT 2004 : conservons cette table pour autoriser les retours
	#	de SPIP 1.8a6 CVS vers 1.7.2
	#	spip_query("DROP TABLE spip_forum_cache");

		spip_query("DROP TABLE spip_inclure_caches");
		maj_version(1.803);
	}
	if ($version_installee < 1.804) {
		// recreer la table spip_caches
		spip_query("DROP TABLE spip_caches");
		creer_base();
		maj_version(1.804);
	}

	if ($version_installee < 1.805) {
		spip_query("ALTER TABLE spip_forum
		ADD id_thread bigint(21) DEFAULT '0' NOT NULL");
		include_spip('inc/forum');
		calculer_threads();
		maj_version(1.805);
	}

	// tables d'orthographe
	#if ($version_installee < 1.806)
	#	maj_version(1.806);

	// URLs propres (inc_version = 0.12)
	if ($version_installee < 1.807) {
		foreach (array('articles', 'breves', 'rubriques', 'mots') as $objets) {
			spip_query("ALTER TABLE spip_$objets
				ADD url_propre VARCHAR(255) NOT NULL");
			spip_query("ALTER TABLE spip_$objets
				ADD INDEX url_propre (url_propre)");
		}
		maj_version(1.807);
	}

	// referers de la veille
	if ($version_installee < 1.808) {
		spip_query("ALTER TABLE spip_referers
		ADD visites_veille INT UNSIGNED NOT NULL");
		maj_version(1.808);
	}


	// corrections diverses
	if ($version_installee < 1.809) {
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
	if ($version_installee < 1.810) {
		spip_query("DELETE FROM spip_forum WHERE statut='redac'");
		maj_version(1.810);
	}

	if ($version_installee < 1.811) {
		spip_query("ALTER TABLE spip_syndic ADD extra longblob NULL");
		maj_version(1.811);
	}
	
	if ($version_installee < 1.812) {
		spip_query("ALTER TABLE spip_documents
		ADD idx ENUM('', '1', 'non', 'oui', 'idx') DEFAULT '' NOT NULL");
		maj_version(1.812);
	}

	// Mise a jour des types MIME
	if ($version_installee < 1.813) {
		# rien a faire car c'est creer_base() qui s'en charge
		maj_version(1.813);
	}

	// URLs propres auteurs
	if ($version_installee < 1.814) {
		spip_query("ALTER TABLE spip_auteurs
			ADD url_propre VARCHAR(255) NOT NULL");
		spip_query("ALTER TABLE spip_auteurs
			ADD INDEX url_propre (url_propre)");
		maj_version(1.814);
	}

	// Mots-cles sur les documents
	// + liens documents <-> sites et articles syndiques (podcasting)
	if ($version_installee < 1.815) {
		spip_query("ALTER TABLE spip_documents
		ADD distant VARCHAR(3) DEFAULT 'non'");
		maj_version(1.815);
	}

	// Indexation des documents (rien a faire sauf reinstaller inc_auxbase)
	if ($version_installee < 1.816) {
		maj_version(1.816);
	}

	// Texte et descriptif des groupes de mots-cles
	if ($version_installee < 1.817) {
		spip_query("ALTER TABLE spip_groupes_mots
		ADD descriptif text NOT NULL AFTER titre");
		spip_query("ALTER TABLE spip_groupes_mots
		ADD COLUMN texte longblob NOT NULL AFTER descriptif");
		maj_version(1.817);
	}

	// Conformite des noms de certains champs (0minirezo => minirezo)
	if ($version_installee < 1.818) {
		spip_query("ALTER TABLE spip_groupes_mots CHANGE COLUMN 0minirezo minirezo char(3) NOT NULL");
		spip_query("ALTER TABLE spip_groupes_mots CHANGE COLUMN 1comite comite char(3) NOT NULL");
		spip_query("ALTER TABLE spip_groupes_mots CHANGE COLUMN 6forum forum char(3) NOT NULL");
		maj_version(1.818);
	}

	// Options de syndication : miroir + oubli
	if ($version_installee < 1.819) {
		spip_query("ALTER TABLE spip_syndic
			ADD miroir VARCHAR(3) DEFAULT 'non'");
		spip_query("ALTER TABLE spip_syndic
			ADD oubli VARCHAR(3) DEFAULT 'non'");
		maj_version(1.819);
	}

	// Un bug dans les 1.730 (il manquait le "ADD")
	if ($version_installee < 1.820) {
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
	if ($version_installee < 1.821) {
		spip_query("UPDATE spip_articles SET idx='1' WHERE idx='oui'");
		maj_version(1.821);
	}
	// le ÇtypeÈ des mots doit etre du texte, sinon on depasse en champ multi
	if ($version_installee < 1.822) {
		spip_query("ALTER TABLE spip_mots DROP INDEX type");
		spip_query("ALTER TABLE spip_mots CHANGE type type TEXT NOT NULL");
		maj_version(1.822);
	}
	// ajouter une table de fonctions pour ajax
	if ($version_installee < 1.825) {
		maj_version(1.825);
	}
	if ($version_installee < 1.826) {
		spip_query("ALTER TABLE spip_ajax_fonc DROP fonction");
		maj_version(1.826);
	}

	// Syndication : ajout de l'option resume=oui/non et de la langue
	if ($version_installee < 1.901) {
		spip_query("ALTER TABLE spip_syndic
			ADD resume VARCHAR(3) DEFAULT 'oui'");
		spip_query("ALTER TABLE spip_syndic_articles
			ADD lang VARCHAR(10) DEFAULT '' NOT NULL");
		maj_version(1.901);
	}

	// Syndication : ajout de source, url_source, tags
	if ($version_installee < 1.902) {
		spip_query("ALTER TABLE spip_syndic_articles
			ADD url_source TINYTEXT DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_syndic_articles
			ADD source TINYTEXT DEFAULT '' NOT NULL");
		spip_query("ALTER TABLE spip_syndic_articles
			ADD tags TEXT DEFAULT '' NOT NULL");
		maj_version(1.902);
	}

	// URLs propres des sites (sait-on jamais)
	// + oubli des KEY url_propre sur les auteurs si installation neuve
	if ($version_installee < 1.903) {
		spip_query("ALTER TABLE spip_syndic
			ADD url_propre VARCHAR(255) NOT NULL");
		spip_query("ALTER TABLE spip_syndic
			ADD INDEX url_propre (url_propre)");
		spip_query("ALTER TABLE spip_auteurs
			ADD INDEX url_propre (url_propre)");
		maj_version(1.903);
	}

	// suppression des anciennes tables temporaires des visites
	// (maintenant stockees sous forme de fichiers)
	if ($version_installee < 1.904) {
		spip_query("DROP TABLE IF EXISTS spip_visites_temp");
		spip_query("DROP TABLE IF EXISTS spip_referers_temp");
		maj_version(1.904);
	}

	// fusion des 10 tables index en une seule
	// pour fonctions futures evoluees du moteur de recherche
	if ($version_installee < 1.905) {
		// agrandir le champ "valeur" de spip_meta pour pouvoir y stocker
		// des choses plus sympa
		spip_query("ALTER TABLE `spip_meta` CHANGE `valeur` `valeur` TEXT");
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
	if ($version_installee < 1.906) {
		if (!in_array('podcast_client', $GLOBALS['plugins'])) {
			spip_query("DROP TABLE spip_documents_syndic");
		}
		maj_version(1.906);
	}

	if ($version_installee < 1.907) {
		spip_query("ALTER TABLE spip_forum ADD INDEX idx (idx)");
		maj_version(1.907);
	}


	return true;
}

?>
