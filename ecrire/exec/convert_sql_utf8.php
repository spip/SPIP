<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// En cas d'erreur, une page admin normale avec bouton de retour


// http://doc.spip.org/@exec_convert_sql_utf8_dist
function exec_convert_sql_utf8_dist() {
	include_spip('inc/minipres');
	include_spip('inc/charsets');
	$charset_spip = $GLOBALS['meta']['charset'];

	// Definir le titre de la page (et le nom du fichier admin)
	//$action = _T('utf8_convertir_votre_site');
	$action = _L("Conversion de la base en $charset_spip"); #volontairement non traduit (obsolete)

	// si meta deja la, c'est une reprise apres timeout.
	if ($GLOBALS['meta']['convert_sql_utf8']) {
		$base = charger_fonction('convert_sql_utf8', 'base');
		$base($action, true);
	} else {
		$charset_supporte = false;
		$utf8_supporte = false;	
		// verifier que mysql gere le charset courant pour effectuer les conversions 
		include_spip('base/abstract_sql');
		if ($c = sql_get_charset($charset_spip)){
			$sql_charset = $c['charset'];
			$sql_collation = $c['collation'];
			$charset_supporte = true;
		}
		if (!$charset_supporte) {
		  	$utf8_supporte = sql_get_charset('utf8');
			$res = _L("Le charset SPIP actuel $charset_spip n'est pas supporte par votre serveur MySQL<br/>");
			if ($utf8_supporte)
				$res .= _L("Votre serveur supporte utf-8, vous devriez convertir votre site en utf-8 avant de recommencer cette operation");
			echo minipres($action, $res);
		} else {

		$commentaire = "";
		//$commentaire = _T('utf8_convert_avertissement',
		//	array('orig' => $charset_orig,'charset' => 'utf-8'));
		$commentaire .=  "<small>"
		. http_img_pack('warning.gif', _T('info_avertissement'), "style='width: 48px; height: 48px; float: right;margin: 10px;'");
		$commentaire .= _T('utf8_convert_backup', array('charset' => 'utf-8'))
		."</small>";
		$commentaire .= '<br />'._T('utf8_convert_timeout');
		$commentaire .= "<hr />\n";

		$admin = charger_fonction('admin', 'inc');
		echo $admin('convert_sql_utf8', $action, $commentaire);
		}
	}
}
?>
