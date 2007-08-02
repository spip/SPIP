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

// http://doc.spip.org/@convert_utf8_init
function convert_utf8_init($tables_a_convertir)
{
	// noter dans les meta qu'on veut convertir, et quoi
	$charset_source = $GLOBALS['meta']['charset'];
	ecrire_meta('charset', 'utf-8');
	ecrire_metas();
	foreach ($tables_a_convertir as $table => $champ) {
		spip_log("demande update charset table $table ($champ)");
		spip_query("UPDATE $table SET $champ = CONCAT('<CONVERT ".$charset_source.">', $champ)	WHERE $champ NOT LIKE '<CONVERT %'");
	}

	@unlink(_DIR_TMP.'convert_utf8_backup.sql');

	// convertir spip_meta

	foreach ($GLOBALS['meta'] as $c => $v) {
		$v2 = unicode_to_utf_8(charset2unicode($v, $charset_source));
		if ($v2 != $v) ecrire_meta($c, $v2);
	}
	ecrire_metas();
}

// http://doc.spip.org/@base_convert_utf8_dist
function base_convert_utf8_dist($titre, $reprise=false)
{
	// poser un verrou (et abandonner si l'action est en cours)

	if (!spip_get_lock('conversion_charset')) {
		echo minipres(_T('utf8_convert_attendez'));
		exit;
	}
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

	include_spip('inc/meta');
	if (!$reprise) convert_utf8_init($tables_a_convertir);

	echo install_debut_html($titre);
	
	echo "<p>", _T('utf8_convert_timeout'), "</p><hr />\n";

	// preparer un fichier de sauvegarde au cas ou
	// on met 'a' car ca peut demander plusieurs rechargements
	$f = @fopen(_DIR_TMP.'convert_utf8_backup.sql', 'a');

	foreach ($tables_a_convertir as $table => $champ) {
		convert_table_utf8($f, $table, $champ);
	}

	if ($f) fclose($f);

	echo "<p><b>"._T('utf8_convert_termine')."</b></p>";
	echo "<p>,"._T('utf8_convert_verifier', array('rep' => joli_repertoire(_DIR_TMP))), '</p>';

	// bouton "retour au site" + redirige_par_entete
	echo "<p style='text-align: right'>",
	  "<a href='", generer_url_ecrire("config_lang"), "'> &gt;&gt; ",
	  _T('icone_retour'),"</a></p>",
	  install_fin_html();
}


// http://doc.spip.org/@convert_table_utf8
function convert_table_utf8($f, $table, $champ)
{
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
	spip_abstract_free($s);
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
?>
