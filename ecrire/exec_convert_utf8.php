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


include_ecrire('inc_admin');


function demander_conversion($tables_a_convertir, $action) {
	global $spip_lang_right;

	$charset_orig = $GLOBALS['meta']['charset'];

	// tester si le charset d'origine est connu de spip
	if (!load_charset($charset_orig))
		$message = _L("Erreur : le jeu de caract&egrave;res ".("<b>".$charset_orig."</b>")." n'est pas support&eacute;.");

	// ne pas convertir si deja utf8
	else if ($charset_orig == 'utf-8')
		$message = 'Votre site est d&eacute;j&agrave; en utf-8, inutile de le convertir...';

	else {
		$commentaire = _L("Vous vous appr&ecirc;tez &agrave; convertir le contenu de votre base de donn&eacute;es (articles, br&egrave;ves, etc) du jeu de caract&egrave;res ".("<b>".$GLOBALS['meta']['charset']."</b>")." vers le jeu de caract&egrave;res universel <b>utf-8</b>.");
		$commentaire .=  "<p><small>"
		. http_img_pack('warning.gif', _T('info_avertissement'), "width='48' height='48' align='right'");
		$commentaire .= _L("N'oubliez pas de faire auparavant une sauvegarde compl&egrave;te de votre site. Vous devrez aussi v&eacute;rifier que vos squelettes et fichiers de langue sont compatibles utf-8. D'autre part le suivi des r&eacute;visions, s'il est activ&eacute;, sera endommag&eacute;.</small>");
		$commentaire .= _L("<p><b>Important&nbsp;:</b> en cas de timeout, veuillez recharger la page jusqu'&agrave; ce qu'elle indique 'termin&eacute;'.");
		$commentaire .= "<hr />\n";

		debut_admin(generer_url_post_ecrire("convert_utf8"),
			$action, $commentaire);

		// noter dans les meta qu'on veut convertir
		ecrire_meta('conversion_charset', $charset_orig);
		ecrire_meta('charset', 'utf-8');
		ecrire_metas();
		foreach ($tables_a_convertir as $table => $champ) {
			spip_log("demande update charset table $table ($champ)");
			#echo _L("demande update charset table $table ($champ)<br>\n");
			spip_query("UPDATE $table
			SET $champ = CONCAT('<CONVERT ".$charset_orig.">', $champ)
			WHERE $champ NOT LIKE '<CONVERT %'");
		}
		return;
	}

	// Ici en cas d'erreur, une page admin normale avec bouton de retour
	install_debut_html($action);
	echo '<p>'.$message;

	echo "<p align='right'> <a href='" . generer_url_ecrire("config_lang")
	. "'> &gt;&gt; "._T('icone_retour')."</a>";

	install_fin_html();
	exit;
}

function convert_utf8_dist() {
	include_ecrire('inc_meta');
	include_ecrire('inc_charsets');
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
		'spip_messages' => 'titre'
	);
#	$tables_a_convertir = array();
	// Definir le titre de la page (et le nom du fichier admin)
	$action = _L('Conversion utf-8');

	// si l'appel est explicite, passer par l'authentification ftp
	if (!$GLOBALS['meta']['conversion_charset']) {
		demander_conversion($tables_a_convertir, $action);

		// si on est la c'est que l'autorisation ftp vient d'etre donnee

		// convertir spip_meta
		$charset_source = lire_meta('conversion_charset');
		foreach ($GLOBALS['meta'] as $c => $v) {
			$v2 = unicode_to_utf_8(charset2unicode($v, $charset_source));
			if ($v2 != $v)
				ecrire_meta($c, $v);
		}
		ecrire_metas();
	}

	// commencer (ou continuer apres un timeout et reload)

	install_debut_html($action);

	// preparer un fichier de sauvegarde au cas ou
	// on met 'a' car ca peut demander plusieurs rechargements
	$f = @fopen(_DIR_SESSIONS.'convert_utf8_backup.sql', 'a');


	foreach ($tables_a_convertir as $table => $champ) {
		echo "<br /><b>$table</b> &nbsp; ";
		$s = spip_query("SELECT * FROM $table
		WHERE $champ LIKE '<CONVERT %'");

		// recuperer 'id_article' (encore un truc a faire dans table_objet)
		preg_match(',^spip_(.*?)s?$,', $table, $r);
		$id_champ = 'id_'.$r[1];
		if ($table == 'spip_petitions') $id_champ = 'id_article';
		if ($table == 'spip_groupes_mots') $id_champ = 'id_groupe';

		// lire les donnees dans un array
		while ($t = spip_fetch_array($s, SPIP_ASSOC)) {
			$query = array();
			foreach ($t as $c => $v) {
				if ($c == $champ) {
					preg_match(',^<CONVERT (.*?)>,', $v, $reg);
					$v = substr($v, strlen($reg[0]));
					$charset_source = $reg[1];
				}
				$query[] = "$c = '".addslashes($v)."'";
			}

			// Cette query ne fait que retablir les donnees existantes
			$query = "UPDATE $table SET ".join(',', $query)."
			WHERE $id_champ = ".$t[$id_champ];

			// On l'enregistre telle quelle sur le fichier de sauvegarde
			if ($f) fwrite($f, $query.";\n");

			// Mais on la transcode
			if ($charset_source != 'utf-8') {
				$query = unicode_to_utf_8(
					charset2unicode($query, $charset_source));
				spip_query($query
				." AND $champ LIKE '<CONVERT %'" # eviter une double conversion
				);
				echo '.           '; flush();
			}
		}
		spip_free_result($s);
	}

	if ($f) fclose($f);

	echo _L("<p><b>C'est termin&eacute;&nbsp;!</b>");
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
