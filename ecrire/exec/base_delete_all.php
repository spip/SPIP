<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion d'affichage de la page de destruction des tables de SPIP
 *
 * @package SPIP\Core\Exec
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Exec de la page de destruction des tables de SPIP
 **/
function exec_base_delete_all_dist() {
	include_spip('inc/autoriser');
	if (!autoriser('detruire')) {
		include_spip('inc/minipres');
		echo minipres();
	} else {
		include_spip('base/dump');
		$res = base_lister_toutes_tables('', [], [], true);
		if (!$res) {
			include_spip('inc/minipres');
			spip_log('Erreur base de donnees');
			echo minipres(
				_T('info_travaux_titre'),
				_T('titre_probleme_technique') . '<p><tt>' . sql_errno() . ' ' . sql_error() . '</tt></p>'
			);
		} else {
			$res = base_saisie_tables('delete', $res);
			include_spip('inc/headers');
			$res = "\n<ol style='text-align:left'><li>\n" .
				join("</li>\n<li>", $res) .
				'</li></ol>';
			$admin = charger_fonction('admin', 'inc');
			$res = $admin('delete_all', _T('titre_page_delete_all'), $res);
			if (!$res) {
				redirige_url_ecrire('install', '');
			} else {
				echo $res;
			}
		}
	}
}
