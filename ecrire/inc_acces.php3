<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_ACCES")) return;
define("_ECRIRE_INC_ACCES", "1");


function creer_pass_aleatoire($longueur = 8, $sel = "") {
	$seed = (double) (microtime() + 1) * time();
	mt_srand($seed);
	srand($seed);
	$s = '';
	$pass = '';
	for ($i = 0; $i < $longueur; $i++) {
		if (!$s) {
			$s = mt_rand();
			if (!$s) $s = rand();
			$s = substr(md5(uniqid($s).$sel), 0, 16);
		}
		$r = unpack("Cr", pack("H2", $s.$s));
		$x = $r['r'] & 63;
		if ($x < 10) $x = chr($x + 48);
		else if ($x < 36) $x = chr($x + 55);
		else if ($x < 62) $x = chr($x + 61);
		else if ($x == 63) $x = '/';
		else $x = '.';
		$pass .= $x;
		$s = substr($s, 2);
	}
	$pass = preg_replace(",[./],", "a", $pass);
	$pass = preg_replace(",[I1l],", "L", $pass);
	$pass = preg_replace(",[0O],", "o", $pass);
	return $pass;
}


//
// low-security : un ensemble de fonctions pour gerer de l'identification
// faible via les URLs (suivi RSS, iCal...)
//
function low_sec($id_auteur) {
	// Pas d'id_auteur : low_sec
	if (!$id_auteur = intval($id_auteur)) {
		if (!$low_sec = lire_meta('low_sec')) {
			include_ecrire('inc_meta.php3');
			ecrire_meta('low_sec', $low_sec = creer_pass_aleatoire());
			ecrire_metas();
		}
	}
	else {
		$query = "SELECT * FROM spip_auteurs WHERE id_auteur = $id_auteur";
		$result = spip_query($query);

		if ($row = spip_fetch_array($result)) {
			$low_sec = $row["low_sec"];
			if (!$low_sec) {
				$low_sec = creer_pass_aleatoire();
				spip_query("UPDATE spip_auteurs SET low_sec = '$low_sec' WHERE id_auteur = $id_auteur");
			}
		}
	}
	return $low_sec;
}

function afficher_low_sec ($id_auteur, $action='') {
	return substr(md5($action.low_sec($id_auteur)),0,8);
}

function verifier_low_sec ($id_auteur, $cle, $action='') {
	return ($cle == afficher_low_sec($id_auteur, $action));
}

function effacer_low_sec($id_auteur) {
	if (!$id_auteur = intval($id_auteur)) return; // jamais trop prudent ;)
	spip_query("UPDATE spip_auteurs SET low_sec = '' WHERE id_auteur = $id_auteur");
}


// Une fonction service qui affiche le statut de l'auteur dans l'espace prive
function afficher_formulaire_statut_auteur ($id_auteur, $statut, $post='') {
	global $connect_statut, $connect_toutes_rubriques, $connect_id_auteur;
	global $spip_lang_right;

	if ($post) {
		$url_self = $post;
		echo "<p />";
		echo "<form action='$post' method='post'>\n";
	} else
		$url_self = "auteur_infos.php3?id_auteur=$id_auteur";


	// S'agit-il d'un admin restreint ?
	if ($statut == '0minirezo') {
		$query_admin = "SELECT lien.id_rubrique, titre FROM spip_auteurs_rubriques AS lien, spip_rubriques AS rubriques WHERE lien.id_auteur=$id_auteur AND lien.id_rubrique=rubriques.id_rubrique GROUP BY lien.id_rubrique";
		$result_admin = spip_query($query_admin);
		$admin_restreint = (spip_num_rows($result_admin) > 0);
	}

	// Seuls les admins voient le menu 'statut' ; les admins restreints
	// ne peuvent l'utiliser que pour mettre un auteur a la poubelle
	if ($connect_statut == "0minirezo"
	AND ($connect_toutes_rubriques OR $statut != "0minirezo")
	AND $connect_id_auteur != $id_auteur) {
		debut_cadre_relief();

		if ($statut == '0minirezo') {
			if ($admin_restreint)
				echo bouton_block_visible("statut$id_auteur");
			else
				echo bouton_block_invisible("statut$id_auteur");
		}

		echo "<b>"._T('info_statut_auteur')." </b> ";
		echo "<select name='statut' size=1 class='fondl'>";

		if ($connect_statut == "0minirezo" AND $connect_toutes_rubriques)
			echo "<OPTION".mySel("0minirezo",$statut).">"._T('item_administrateur_2');

		echo "<OPTION".mySel("1comite",$statut).">"._T('intem_redacteur');

		if (($statut == '6forum')
		OR (lire_meta('accepter_visiteurs') == 'oui')
		OR (lire_meta('forums_publics') == 'abo')
		OR spip_num_rows(spip_query("SELECT statut
		FROM spip_auteurs WHERE statut='6forum'")))
			echo "<OPTION".mySel("6forum",$statut).">"._T('item_visiteur');
		echo "<OPTION".mySel("5poubelle",$statut).
		  " style='background:url(" . _DIR_IMG_PACK . "rayures-sup.gif)'>&gt; "._T('texte_statut_poubelle');

		echo "</select>\n";

		//
		// Gestion restreinte des rubriques
		//
		if ($statut == '0minirezo') {
			if (!$admin_restreint) {
				echo debut_block_invisible("statut$id_auteur");
				echo "<p /><div style='arial2'>\n";
				echo _T('info_admin_gere_toutes_rubriques');
			} else {
				echo debut_block_visible("statut$id_auteur");
				echo "<p /><div style='arial2'>\n";
				echo _T('info_admin_gere_rubriques')."\n";
				echo "<ul style='list-style-image: url(" . _DIR_IMG_PACK . "rubrique-12.gif)'>";
				while ($row_admin = spip_fetch_array($result_admin)) {
					$id_rubrique = $row_admin["id_rubrique"];
					$titre = typo($row_admin["titre"]);
					echo "<li>$titre";
					if ($connect_toutes_rubriques
					AND $connect_id_auteur != $id_auteur) {
						echo " <font size=1>"
						. "[<a href='$url_self&supp_rub=$id_rubrique'>"
						._T('lien_supprimer_rubrique')
						."</a>]</font>";
					}
					$toutes_rubriques .= "$id_rubrique,";
				}
				echo "</ul>";
				$toutes_rubriques = ",$toutes_rubriques";
			}
	
			if ($connect_toutes_rubriques
			AND $connect_id_auteur != $id_auteur) {
				echo "</div><br /><div class='arial1'>";
				if (spip_num_rows($result_admin) == 0) {
					echo "<b>"._T('info_restreindre_rubrique')."</b><br />";
				} else {
					echo "<b>"._T('info_ajouter_rubrique')."</b><br />";
				}
				echo "<INPUT NAME='id_auteur' VALUE='$id_auteur' TYPE='hidden'>";
				echo "<SELECT NAME='add_rub' SIZE=1 CLASS='formo'>";
				echo "<OPTION VALUE='0'>\n";
				afficher_auteur_rubriques("0");
				echo "</SELECT>";
			}

			echo "</div>\n";
			echo fin_block();
		}


		if ($post) {
			echo "<div align='$spip_lang_right'><input type='submit'
			class='fondo' name='Valider'
			value=\""._T('bouton_valider')."\" /></div>"; 
			echo "</form>\n";
		}

		fin_cadre_relief();
	}
}

function modifier_statut_auteur (&$auteur, $statut, $add_rub='', $supp_rub='') {
	global $connect_statut, $connect_toutes_rubriques;
	// changer le statut ?
	if ($connect_statut == '0minirezo' AND $statut) {
		if (ereg("^(0minirezo|1comite|5poubelle|6forum)$",$statut)) {
			$auteur['statut'] = $statut;
			spip_query("UPDATE spip_auteurs SET statut='".$statut."'
			WHERE id_auteur=".$auteur['id_auteur']);
		}
	}
	// modif auteur restreint, seulement pour les admins
	if ($connect_toutes_rubriques) {
		if ($add_rub=intval($add_rub))
			spip_query("INSERT INTO spip_auteurs_rubriques
			(id_auteur,id_rubrique)
			VALUES(".$auteur['id_auteur'].", $add_rub)");

		if ($supp_rub=intval($supp_rub))
			spip_query("DELETE FROM spip_auteurs_rubriques
			WHERE id_auteur=".$auteur['id_auteur']."
			AND id_rubrique=$supp_rub");
	}
}


function initialiser_sel() {
	global $htsalt;

	$htsalt = '$1$'.creer_pass_aleatoire();
}


function ecrire_logins($fichier, $tableau_logins) {
	reset($tableau_logins);

	while(list($login, $htpass) = each($tableau_logins)) {
		if ($login && $htpass) {
			fputs($fichier, "$login:$htpass\n");
		}
	}
}


function ecrire_acces() {
	$htaccess = _DIR_RESTREINT . _ACCESS_FILE_NAME;
	$htpasswd = _DIR_SESSIONS . _AUTH_USER_FILE;

	// si .htaccess existe, outrepasser spip_meta
	if ((lire_meta('creer_htpasswd') == 'non') AND !@file_exists($htaccess)) {
		@unlink($htpasswd);
		@unlink($htpasswd."-admin");
		return;
	}

	# remarque : ici on laisse passer les "nouveau" de maniere a leur permettre
	# de devenir "1comite" le cas echeant (auth http)... a nettoyer
	$query = "SELECT login, htpass FROM spip_auteurs WHERE statut != '5poubelle' AND statut!='6forum'";
	$result = spip_query_db($query);	// attention, il faut au prealable se connecter a la base (necessaire car utilise par install.php3)
	$logins = array();
	while($row = spip_fetch_array($result)) $logins[$row['login']] = $row['htpass'];

	$fichier = @fopen($htpasswd, "w");
	if ($fichier) {
		ecrire_logins($fichier, $logins);
		fclose($fichier);
	} else {
		redirige_par_entete("../spip_test_dirs.php3");
	}

	$query = "SELECT login, htpass FROM spip_auteurs WHERE statut = '0minirezo'";
	$result = spip_query_db($query);

	$logins = array();
	while($row = spip_fetch_array($result)) $logins[$row['login']] = $row['htpass'];

	$fichier = fopen("$htpasswd-admin", "w");
	ecrire_logins($fichier, $logins);
	fclose($fichier);
}


function generer_htpass($pass) {
	global $htsalt, $flag_crypt;
	if ($flag_crypt) return crypt($pass, $htsalt);
	else return '';
}

//
// Verifier la presence des .htaccess
//
function verifier_htaccess($rep) {
	$htaccess = "$rep/" . _ACCESS_FILE_NAME;
	if ((!@file_exists($htaccess)) AND 
	    !defined('_ECRIRE_INSTALL') AND !defined('_TEST_DIRS')) {
		spip_log("demande de creation de $htaccess");
		if ($_SERVER['SERVER_ADMIN'] != 'www@nexenservices.com'){
			if (!$f = fopen($htaccess, "w"))
				echo "<b>" .
				  "ECHEC DE LA CREATION DE $htaccess" . # ne pas traduire
				  "</b>";
			else
			  {
				fputs($f, "deny from all\n");
				fclose($f);
			  }
		} else {
			echo "<font color=\"#FF0000\">IMPORTANT : </font>";
			echo "Votre h&eacute;bergeur est Nexen Services.<br />";
			echo "La protection du r&eacute;pertoire <i>$rep/</i> doit se faire
			par l'interm&eacute;diaire de ";
			echo "<a href=\"http://www.nexenservices.com/webmestres/htlocal.php\"
			target=\"_blank\">l'espace webmestres</a>.";
			echo "Veuillez cr&eacute;er manuellement la protection pour
			ce r&eacute;pertoire (un couple login/mot de passe est
			n&eacute;cessaire).<br />";
		}
	}
}

function gerer_htaccess() {
	$mode = lire_meta('creer_htaccess');
	$r = spip_query("SELECT extension FROM spip_types_documents");
	while ($e = spip_fetch_array($r)) {
		if (is_dir($dir = _DIR_DOC . $e['extension'])) {
			if ($mode == 'oui')
				verifier_htaccess($dir);
			else @unlink("$dir/" . _ACCESS_FILE_NAME);
		}
	}
	return $mode;
}

// En profiter pour verifier la securite de ecrire/data/
verifier_htaccess(_DIR_SESSIONS);

initialiser_sel();

?>
