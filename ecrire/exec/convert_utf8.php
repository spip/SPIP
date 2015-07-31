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

if (!defined("_ECRIRE_INC_VERSION")) return;


include_spip('inc/admin');


// http://doc.spip.org/@demander_conversion
function demander_conversion($tables_a_convertir, $action) {
	global $spip_lang_right;

	$charset_orig = $GLOBALS['meta']['charset'];

	// tester si le charset d'origine est connu de spip
	if (!load_charset($charset_orig))
		$message = _T('utf8_convert_erreur_orig', array('charset' => "<b>".$charset_orig."</b>"));

	// ne pas convertir si deja utf8
	else if ($charset_orig == 'utf-8')
		$message = _T('utf8_convert_erreur_deja',
			array('charset' => $charset_orig)
		);

	else {
		$commentaire = _T('utf8_convert_avertissement',
			array('orig' => $charset_orig,
				'charset' => 'utf-8')
		);
		$commentaire .=  "<p><small>"
		. http_img_pack('warning.gif', _T('info_avertissement'), "width='48' height='48' align='right'");
		$commentaire .= _T('utf8_convert_backup', array('charset' => 'utf-8'))
		."</small>";
		$commentaire .= '<p>'._T('utf8_convert_timeout');
		$commentaire .= "<hr />\n";

		debut_admin(generer_url_post_ecrire("convert_utf8"),
			$action, $commentaire);

		// noter dans les meta qu'on veut convertir
		ecrire_meta('conversion_charset', $charset_orig);
		ecrire_meta('charset', 'utf-8');
		ecrire_metas();
		foreach ($tables_a_convertir as $table => $champ) {
			spip_log("demande update charset table $table ($champ)");
			spip_query("UPDATE $table SET $champ = CONCAT('<CONVERT ".$charset_orig.">', $champ)	WHERE $champ NOT LIKE '<CONVERT %'");
		}
		return;
	}

	// Ici en cas d'erreur, une page admin normale avec bouton de retour
	minipres($action, ('<p>'.$message. "</p><p align='right'> <a href='" . generer_url_ecrire("config_lang"). "'> &gt;&gt; "._T('icone_retour')."</a>"));
}

// stocker le nouvel extra
// http://doc.spip.org/@convert_extra
function convert_extra($v) {
	if ($extra = @unserialize($v)) {
		foreach ($extra as $key=>$val)
			$extra[$key] = unicode_to_utf_8(
			charset2unicode($val, $charset_source));
			return ", extra="._q(serialize($extra));
	}
}


// http://doc.spip.org/@exec_convert_utf8_dist
function exec_convert_utf8_dist() {
	include_spip('inc/meta');
	include_spip('inc/charsets');
	lire_metas();

	// une liste des tables a convertir, avec le champ dans lequel on
	// indique '<CONVERT charset>' ; on commence par les rubriques sinon
	// ca fait desordre dans l'interface privee
	$tables_a_convertir = array(
		'spip_rubriques' => 'titre',
		'spip_auteurs' => 'nom',
		'spip_articles' => 'titre',
		'spip_breves' => 'titre',
		'spip_documents' => 'titre',
		'spip_forum' => 'titre',
		'spip_mots' => 'titre',
		'spip_groupes_mots' => 'titre',
		'spip_petitions' => 'texte',
		'spip_signatures' => 'nom_email',
		'spip_syndic' => 'nom_site',
		'spip_syndic_articles' => 'titre',
		'spip_messages' => 'titre'
	);

	// Definir le titre de la page (et le nom du fichier admin)
	$action = _T('utf8_convertir_votre_site');

	// si l'appel est explicite, passer par l'authentification ftp
	if (!$GLOBALS['meta']['conversion_charset']) {
		demander_conversion($tables_a_convertir, $action);

		// si on est la c'est que l'autorisation ftp vient d'etre donnee
		@unlink(_DIR_TMP.'convert_utf8_backup.sql');

		// convertir spip_meta
		$charset_source = $GLOBALS['meta']['conversion_charset'];
		foreach ($GLOBALS['meta'] as $c => $v) {
			$v2 = unicode_to_utf_8(charset2unicode($v, $charset_source));
			if ($v2 != $v)
				ecrire_meta($c, $v2);
		}
		ecrire_metas();
	}

	// commencer (ou continuer apres un timeout et reload)

	install_debut_html($action);
	
	echo "<p>" . _T('utf8_convert_timeout') . "<hr />\n";

	if (!spip_get_lock('conversion_charset'))
		die(_T('utf8_convert_attendez'));

	// preparer un fichier de sauvegarde au cas ou
	// on met 'a' car ca peut demander plusieurs rechargements
	$f = @fopen(_DIR_TMP.'convert_utf8_backup.sql', 'a');


	foreach ($tables_a_convertir as $table => $champ) {
		echo "<br /><b>$table</b> &nbsp; ";
		$s = spip_query("SELECT * FROM $table WHERE $champ LIKE '<CONVERT %'");

		// recuperer 'id_article' (encore un truc a faire dans table_objet)
		preg_match(',^spip_(.*?)s?$,', $table, $r);
		$id_champ = 'id_'.$r[1];
		if ($table == 'spip_petitions') $id_champ = 'id_article';
		if ($table == 'spip_groupes_mots') $id_champ = 'id_groupe';

		// lire les donnees dans un array
		while ($t = spip_fetch_array($s, SPIP_ASSOC)) {
			$query = array();
			$query_no_convert = '';
			$query_extra = '';
			foreach ($t as $c => $v) {
				if ($c == $champ) {
					preg_match(',^<CONVERT (.*?)>,', $v, $reg);
					$v = substr($v, strlen($reg[0]));
					$charset_source = $reg[1];
					$query[] = "$c=" . _q($v);
				} else {
					if (!is_numeric($v)
					AND !is_ascii($v)) {
						// traitement special car donnees serializees
						if ($c == 'extra') {
							$query_no_convert .= ", $c="._q($v);
							$query_extra = convert_extra($v);
						} else
							$query[] = "$c=" . _q($v);
					} else
						# pour le backup
						$query_no_convert .= ", $c="._q($v);
				}
			}

			$set = join(', ', $query);
			$where = "$id_champ = ".$t[$id_champ];

			// On l'enregistre telle quelle sur le fichier de sauvegarde
			if ($f) fwrite($f,
				"UPDATE $table SET $set$query_no_convert"
				." WHERE $where;\n"
			);

			// Mais on la transcode
			// en evitant une double conversion
			if ($charset_source != 'utf-8') {
				$query = "UPDATE $table SET "
				. unicode_to_utf_8(charset2unicode($set, $charset_source))
				. $query_extra
				. " WHERE $where AND $champ LIKE '<CONVERT %'";
				#echo $query;
				spip_query($query);
				echo '.           '; flush();

			}
		}
		spip_free_result($s);
	}

	if ($f) fclose($f);

	echo "<p><b>"._T('utf8_convert_termine')."</b>";
	echo "<p> "._T('utf8_convert_verifier', array('rep' => joli_repertoire(_DIR_TMP)));
	effacer_meta('conversion_charset');
	ecrire_metas();

	// C'est fini, supprimer le fichier autorisant les modifs
	fin_admin($action);
	
	// bouton "retour au site" + redirige_par_entete
	echo "<p align='right'> <a href='" . generer_url_ecrire("config_lang")
	. "'> &gt;&gt; "._T('icone_retour')."</a>";

	install_fin_html();
}


?>
