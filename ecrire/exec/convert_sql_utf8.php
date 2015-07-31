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

// En cas d'erreur, une page admin normale avec bouton de retour

// http://doc.spip.org/@convert_utf8_non
function convert_utf8_non($action, $message) {

	echo minipres($action, ('<p>'.$message. "</p>\n<p style='text-align: right'><a href='" . generer_url_ecrire("config_lang"). "'> &gt;&gt; "._T('icone_retour')."</a>"));
	exit;
}

// http://doc.spip.org/@exec_convert_sql_utf8_dist
function exec_convert_sql_utf8_dist() {
	include_spip('inc/minipres');
	include_spip('inc/meta');
	include_spip('inc/charsets');
	lire_metas();

	$charset_spip = $GLOBALS['meta']['charset'];
	// Definir le titre de la page (et le nom du fichier admin)
	//$action = _T('utf8_convertir_votre_site');
	$action = _L("Conversion de la base en $charset_spip");

	// si meta deja la, c'est une reprise apres timeout.
	if ($GLOBALS['meta']['convert_sql_utf8']) {
		$base = charger_fonction('convert_sql_utf8', 'base');
		$base($action, true);
	} else {
		$charset_supporte = false;
		$utf8_supporte = false;	
		// verifier que mysql gere le charset courant pour effectuer les conversions 
		include_spip('base/db_mysql');
		if ($c = spip_mysql_character_set($charset_spip)){
			$sql_charset = $c['charset'];
			$sql_collation = $c['collation'];
			$charset_supporte = true;
		}
		if (!$charset_supporte){
			$res = spip_query("SHOW CHARACTER SET");
			while ($row = spip_fetch_array($res)){
				if ($row['Charset']=='utf8') $utf8_supporte = true;
			}
			echo _L("le Charset SPIP actuel $charset_spip n'est pas supporte par votre serveur mySql<br/>");
			if ($utf8_supporte)
				echo _L("Votre serveur supporte utf-8, vous devriez convertir votre site en utf-8 avant de recommencer cette operation");
				echo install_fin_html();
			exit;
		}

		$commentaire = "";
		//$commentaire = _T('utf8_convert_avertissement',
		//	array('orig' => $charset_orig,'charset' => 'utf-8'));
		$commentaire .=  "<p><small>"
		. http_img_pack('warning.gif', _T('info_avertissement'), "style='width: 48px; height: 48px; float: right;margin: 10px;'");
		$commentaire .= _T('utf8_convert_backup', array('charset' => 'utf-8'))
		."</small>";
		$commentaire .= '<p>'._T('utf8_convert_timeout');
		$commentaire .= "<hr />\n";

		$admin = charger_fonction('admin', 'inc');
		$admin('convert_sql_utf8', $action, $commentaire);
	}
}
?>