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

	$chmod = _request('chmod');

	$fconnect = charger_fonction('db_' . $server_db, 'base', true);
	$link = $fconnect($adresse_db, 0, $login_db, $pass_db);

	echo install_debut_html();

// prenons toutes les dispositions possibles pour que rien ne s'affiche !

	echo "<!-- $fconnect $login_db $pass_db";
	$db_connect = 0; // revoirfunction_exists($ferrno) ? $ferrno() : 0;
	echo " '$link'";
	echo "-->";

	if (($db_connect=="0") && $link) {
		echo "<p class='resultat'><b>"._T('info_connexion_ok')."</b></p>";
		echo info_etape(_T('menu_aide_installation_choix_base').aide ("install2"));

		// pourquoi se connecter une deuxieme fois ?
		$link = $fconnect($adresse_db,0,$login_db,$pass_db);
		list($checked, $res) = install_etape_2_bases($login_db, $server_db);

		$hidden = (defined('_SPIP_CHMOD')
		? ''
		: "\n<input type='hidden' name='chmod' value='".htmlspecialchars($chmod)."' />"
			   );

		echo install_etape_2_form($adresse_db,$login_db,$pass_db, $server_db, $hidden, $checked, $res);
	} else  {
		echo info_etape(_T('info_connexion_base'));
		echo "<p class='resultat'><b>",
#		  _T('avis_connexion_echec_1'),
		  _L('La connexion &agrave; la base de donn&eacute;es a &eacute;chou&eacute;.'),
		  "</b></p>";
		echo "<p>"._T('avis_connexion_echec_2')."</p>";
		echo "<p style='font-size: small;'>",
#		  _T('avis_connexion_echec_3'),
		  _L('<b>N.B.</b> Sur de nombreux serveurs, vous devez <b>demander</b> l\'activation de votre acc&egrave;s &agrave; la base  de donn&eacute;es avant de pouvoir l\'utiliser. Si vous ne pouvez vous connecter, v&eacute;rifiez que vous avez effectu&eacute; cette d&eacute;marche.'),
		  "</p>";
	}
	
	echo info_progression_etape(2,'etape_','install/');
	echo install_fin_html();
}

// Liste les bases accessibles, 
// avec une heuristique pour preselectionner la plus probable

function install_etape_2_bases($login_db, $server_db)
{
	$flistdbs = 'spip_' . $server_db . '_listdbs';
	$fselectdb = 'spip_' . $server_db . '_selectdb';
	$ffetch = 'spip_' . $server_db . '_fetch';

	$result = $flistdbs();
	$bases = $checked = '';
	if ($result) {
		while ($row = $ffetch($result, SPIP_NUM)) {

			$table_nom = $row[0];
			$base = "<li>\n<input name=\"choix_db\" value=\"".$table_nom."\" type='radio' id='tab$i'";
			$base_fin = " /><label for='tab$i'>".$table_nom."</label>\n</li>";
			if (!$checked AND
			    (($table_nom == $login_db) OR
			     ($GLOBALS['table_prefix'] == $table_nom))) {
				$checked = "$base checked='checked'$base_fin";
			} else {
				$bases .= "$base$base_fin\n";
			}
		}
	}
	if ($bases) 
		return array($checked, 
		       "<label for='choix_db'><b>"._T('texte_choix_base_2')."</b><br />"._T('texte_choix_base_3')."</label>"
		       .  "<ul>$checked$bases</ul><p>"._T('info_ou')." ");

	$res = "<b>"._T('avis_lecture_noms_bases_1')."</b>
		"._T('avis_lecture_noms_bases_2')."<p>";
	if ($login_db) {
			// Si un login comporte un point, le nom de la base est plus
			// probablement le login sans le point -- testons pour savoir
			$test_base = $login_db;
			$ok = $fselectdb($test_base);
			$test_base2 = str_replace('.', '_', $test_base);
			if ($fselectdb($test_base2)) {
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

function install_etape_2_form($adresse_db,$login_db,$pass_db, $server_db, $hidden, $checked, $res)
 {
	return generer_form_ecrire('install', (
	  "\n<input type='hidden' name='etape' value='3' />"
	 . $hidden
	. (defined('_INSTALL_HOST_DB')
		? ''
		: "\n<input type='hidden' name='adresse_db'  value=\"".htmlspecialchars($adresse_db)."\" />"
	)
	. (defined('_INSTALL_USER_DB')
		? ''
		: "\n<input type='hidden' name='login_db' value=\"".htmlspecialchars($login_db)."\" />"
	)
	. (defined('_INSTALL_PASS_DB')
		? ''
		: "\n<input type='hidden' name='pass_db' value=\"".htmlspecialchars($pass_db)."\" />"
	)

	. (defined('_INSTALL_SERVER_DB')
		? ''
		: "\n<input type='hidden' name='server_db' value=\"".htmlspecialchars($server_db)."\" />"
	)

	. (defined('_INSTALL_NAME_DB')
		? '<h3>'._T('install_nom_base_hebergeur'). ' <tt>'._INSTALL_NAME_DB.'</tt>'.'</h3>'
		: "\n<fieldset><legend>"._T('texte_choix_base_1')."</legend>\n"
		. $res
		. "\n<input name=\"choix_db\" value=\"new_spip\" type='radio' id='nou'"
		. ($checked  ? '' : " checked='checked'")
		. " />\n<label for='nou'>"._T('info_creer_base')."</label></p>\n<p>"
		. "\n<input type='text' name='table_new' class='fondl' value=\"spip\" size='20' /></p></fieldset>\n"
	)

	. ((defined('_INSTALL_TABLE_PREFIX')
	OR $GLOBALS['table_prefix'] != 'spip')
		? '<h3>'._T('install_table_prefix_hebergeur').'  <tt>'.$GLOBALS['table_prefix'].'</tt>'.'</h3>'
		: "<fieldset><legend>"._T('texte_choix_table_prefix')."</legend>\n"
	. "<p><label for='table_prefix'>"._T('info_table_prefix')."</label></p><p>"
	. "\n<input type='text' id='tprefix' name='tprefix' class='fondl' value='"
		. 'spip' # valeur par defaut
		. "' size='20' /></p></fieldset>"
	)

	. bouton_suivant()));
}

?>
