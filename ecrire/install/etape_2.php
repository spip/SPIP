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

include_spip('base/abstract_sql');

// http://doc.spip.org/@install_etape_2_dist
function install_etape_2_dist()
{
	$adresse_db = defined('_INSTALL_HOST_DB')
		? _INSTALL_HOST_DB
		: _request('adresse_db');

	$login_db = defined('_INSTALL_USER_DB')
		? _INSTALL_USER_DB
		: _request('login_db');

	$pass_db = defined('_INSTALL_PASS_DB')
		? _INSTALL_PASS_DB
		: _request('pass_db');

	$server_db = defined('_INSTALL_SERVER_DB')
		? _INSTALL_SERVER_DB
		: _request('server_db');

        $name_db =  defined('_INSTALL_NAME_DB')
		? _INSTALL_NAME_DB
		: '';

	$chmod = _request('chmod');

	$link = spip_connect_db($adresse_db, 0, $login_db, $pass_db, $name_db, $server_db);
	$GLOBALS['connexions'][$server_db] = $link;

	$GLOBALS['connexions'][$server_db][$GLOBALS['spip_sql_version']]
	= $GLOBALS['spip_' . $server_db .'_functions_' . $GLOBALS['spip_sql_version']];

	echo install_debut_html();

// prenons toutes les dispositions possibles pour que rien ne s'affiche !

	/*
	 * /!\ sqlite3/PDO : erreur sur join(', ', $link)
	 * L'objet PDO ne peut pas etre transformee en chaine
	 * Un echo $link ne fonctionne pas non plus
	 * Il faut utiliser par exemple print_r($link)
	 */
	//echo "\n<!--\n", join(', ', $link), " $login_db ";
	$db_connect = 0; // revoirfunction_exists($ferrno) ? $ferrno() : 0;
	//echo join(', ', $GLOBALS['connexions'][$server_db]);
	//echo "\n-->\n";

	if (($db_connect=="0") && $link) {
		echo "<p class='resultat ok'><b>"._T('info_connexion_ok')."</b></p>\n";
		echo info_progression_etape(2,'etape_','install/');

		echo info_etape(_T('menu_aide_installation_choix_base').aide ("install2"));



		spip_connect_db($adresse_db, 0, $login_db, $pass_db, '',$server_db);

		echo "\n", '<!-- ',  sql_version($server_db), ' -->' ;
		list($checked, $res) = install_etape_2_bases($login_db, $server_db);

		$hidden = (defined('_SPIP_CHMOD')
		? ''
		: ("\n<input type='hidden' name='chmod' value='".htmlspecialchars($chmod)."' />"))
		. predef_ou_cache($adresse_db,$login_db,$pass_db, $server_db);

		echo install_etape_2_form($hidden, $checked, $res, 3);
	} else  {
		echo info_etape(_T('info_connexion_base'));

		echo info_progression_etape(1,'etape_','install/',true);

		echo "<p class='resultat echec'><b>",
		  _T('avis_connexion_echec_1'),
		  "</b></p>";
		echo "<p>"._T('avis_connexion_echec_2')."</p>";
		echo "<p style='font-size: small;'>",
		  _T('avis_connexion_echec_3'),
		  "</p>";
	}
	
	echo install_fin_html();
}

// Liste les bases accessibles, 
// avec une heuristique pour preselectionner la plus probable

// http://doc.spip.org/@install_etape_2_bases
function install_etape_2_bases($login_db, $server_db)
{
	$res =  install_etape_liste_bases($server_db, $login_db);
	if ($res) {
		list($checked, $bases) = $res;
		return array($checked, 
			     "<label for='choix_db'><b>"
			     ._T('texte_choix_base_2')
			     ."</b><br />"
			     ._T('texte_choix_base_3')
			     ."</label>"
			     . "<ul>\n<li>"
			     . join("</li>\n<li>",$bases)
			     . "</li>\n</ul><p>"
			     . _T('info_ou')
			     . " "
			     );
	}
	$res = "<b>"._T('avis_lecture_noms_bases_1')."</b>
		"._T('avis_lecture_noms_bases_2')."<p>";

	if ($login_db) {
		// Si un login comporte un point, le nom de la base est plus
		// probablement le login sans le point -- testons pour savoir
			$test_base = $login_db;
			$ok = sql_selectdb($test_base, $server_db);
			$test_base2 = str_replace('.', '_', $test_base);
			if (sql_selectdb($test_base2, $server_db)) {
				$test_base = $test_base2;
				$ok = true;
			}

			if ($ok) {
				$res .= _T('avis_lecture_noms_bases_3')
				. "<ul>"
				. "<li><input name=\"choix_db\" value=\"".$test_base."\" type='radio' id='stand' checked='checked' />"
				. "<label for='stand'>".$test_base."</label></li>\n"
				. "</ul>"
				. "<p>"._T('info_ou')." ";
				$checked = true;
			}
	}

	return array($checked, $res);
}

// http://doc.spip.org/@install_etape_2_form
function install_etape_2_form($hidden, $checked, $res, $etape)
 {
	return generer_form_ecrire('install', (
	  "\n<input type='hidden' name='etape' value='$etape' />"
	 . $hidden
	. (defined('_INSTALL_NAME_DB')
		? '<h3>'._T('install_nom_base_hebergeur'). ' <tt>'._INSTALL_NAME_DB.'</tt>'.'</h3>'
		: "\n<fieldset><legend>"._T('texte_choix_base_1')."</legend>\n"
		. $res
		. "\n<input name=\"choix_db\" value=\"new_spip\" type='radio' id='nou'"
		. ($checked  ? '' : " checked='checked'")
		. " />\n<label for='nou'>"._T('info_creer_base')."</label></p>\n<p>"
		. "\n<input type='text' name='table_new' class='text' value=\"spip\" size='20' /></p></fieldset>\n"
	)

	. ((defined('_INSTALL_TABLE_PREFIX')
	OR $GLOBALS['table_prefix'] != 'spip')
		? '<h3>'._T('install_table_prefix_hebergeur').'  <tt>'.$GLOBALS['table_prefix'].'</tt>'.'</h3>'
		: "<fieldset><legend>"._T('texte_choix_table_prefix')."</legend>\n"
	. "<p><label for='table_prefix'>"._T('info_table_prefix')."</label></p><p>"
	. "\n<input type='text' id='tprefix' name='tprefix' class='text' value='"
		. 'spip' # valeur par defaut
		. "' size='20' /></p></fieldset>"
	)

	. bouton_suivant()));
}
?>
