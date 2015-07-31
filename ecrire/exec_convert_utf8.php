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


function demander_conversion($tables_a_convertir) {

	$action = _L('Conversion utf-8');

	$charset_orig = $GLOBALS['meta']['charset'];

	if ($charset_orig == 'utf-8')
		$commentaire = 'Votre site est d&eacute;j&agrave; en utf-8, inutile de le convertir...';
	else {
		$commentaire = _L("Vous vous appr&ecirc;tez &agrave; convertir le contenu de votre base de donn&eacute;es (articles, br&egrave;ves, etc) du jeu de caract&egrave;res ".("<b>".$GLOBALS['meta']['charset']."</b>")." vers le jeu de caract&egrave;res universel <b>utf-8</b>.");
		$commentaire .= _L("<p>Note&nbsp;: en cas de timeout, veuillez simplement recharger la page jusqu'à ce qu'elle indique 'terminé'.");
	}

	// tester si le charset d'origine est connu de spip
	if (!load_charset($charset_orig))
		$commentaire = _L("Erreur : le jeu de caract&egrave;res ".("<b>".$charset_orig."</b>")." n'est pas support&eacute;.");


	debut_admin(generer_url_post_ecrire("convert_utf8"), $action, $commentaire);

	// noter dans les meta qu'on veut convertir
	ecrire_meta('conversion_charset', time());
	ecrire_meta('charset', 'utf-8');
	ecrire_metas();
	foreach ($tables_a_convertir as $table => $champ) {
		spip_log("demande update charset table $table ($champ)");
		echo("demande update charset table $table ($champ)<br>\n");
		spip_query("UPDATE $table
		SET $champ = CONCAT('<CONVERT ".$charset_orig.">', $champ)
		WHERE $champ NOT LIKE '<CONVERT %'");
	}

}

function convert_utf8_dist() {
	include_ecrire('inc_meta');
	include_ecrire('inc_charsets');
	lire_metas();

	// une liste des tables a convertir, avec le champ dans lequel on
	// indique '<CONVERT charset>'
	$tables_a_convertir = array(
		'spip_auteurs' => 'nom',
		'spip_articles' => 'titre',
		'spip_breves' => 'titre',
		'spip_documents' => 'titre',
		'spip_forum' => 'titre',
		'spip_mots' => 'titre',
		'spip_groupes_mots' => 'titre',
		'spip_petitions' => 'texte',
		'spip_rubriques' => 'titre',
		'spip_signatures' => 'nom_email',
		'spip_syndic' => 'nom_site',
		'spip_messages' => 'titre'
	);
	## quid de spip_meta ?


	// si l'appel est explicite, passer par l'authentification ftp
	if (!$GLOBALS['meta']['conversion_charset']) {
		demander_conversion($tables_a_convertir);
	}

	// sinon commencer (ou continuer apres un timeout et reload)
	foreach ($tables_a_convertir as $table => $champ) {
		echo "<br>$table ($champ) :<br>";
		$s = spip_query("SELECT * FROM $table
		WHERE $champ LIKE '<CONVERT %'");

		// recuperer 'id_article' (encore un truc a faire dans table_objet)
		preg_match(',^spip_(.*?)s?$,', $table, $r);
		$id_champ = 'id_'.$r[1];
		if ($table == 'spip_petitions') $id_champ = 'id_article';

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
			WHERE $id_champ = ".$t[$id_champ]
			. " AND $champ LIKE '<CONVERT %'"; # eviter une double conversion

			// Mais on la transcode
			$query = unicode_to_utf_8(charset2unicode($query, $charset_source));

			spip_query($query);
			echo '.                                                 '; flush();
		}
		spip_free_result($s);
	}

	echo "<br />Termin&eacute; !";
	effacer_meta('conversion_charset');
	ecrire_metas();

	// C'est fini, supprimer le fichier autorisant les modifs
	fin_admin($action);

}


?>
