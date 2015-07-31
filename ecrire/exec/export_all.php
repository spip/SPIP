<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * 
 * On arrive ici depuis exec=admin_tech
 * - le premier coup on initialise et on renvoie sur action=export_all avec un start
 * - action=export_all ouvre le fichier et ecrit son entete, et renvoie ici
 * - les autres coups on lance inc/export, qui remplit le dump et renvoie ici a chaque timeout
 * - a chaque coup on relance inc/export
 * - lorsque inc/export a fini, il renvoie vers action=export_all avec un end
 * - action=export_all clos le fichier et affiche le resume
 * 
 */

include_spip('inc/presentation');
include_spip('base/dump');

// http://doc.spip.org/@exec_export_all_dist
function exec_export_all_dist()
{
	$rub = intval(_request('id_parent'));
	$meta = "status_dump_$rub_"  . $GLOBALS['visiteur_session']['id_auteur'];

	if (!isset($GLOBALS['meta'][$meta]))
		echo exec_export_all_args($rub, _request('gz'));
	else {
		$export = charger_fonction('export', 'inc');
		$export($meta);
	}
}

// L'en tete du fichier doit etre cree a partir de l'espace public
// Ici on construit la liste des tables pour confirmation.
// Envoi automatique en cas d'inaction (sauf si appel incorrect $nom=NULL)

function exec_export_all_args($rub, $gz)
{
	$gz = $gz ? '.gz' : '';
	$nom = $gz 
	?  _request('znom_sauvegarde') 
	:  _request('nom_sauvegarde');
	if (!preg_match(',^[\w_][\w_.]*$,', $nom)) $nom = 'dump';
	$archive = $nom . '.xml' . $gz;
	list($tables,) = base_liste_table_for_dump($GLOBALS['EXPORT_tables_noexport']);
	$clic =  _T('bouton_valider');
	$plie = _T('install_tables_base');
	$res = controle_tables_en_base('export', $tables, $rub);
	$res = "\n<ol style='text-align:left'><li>\n" .
			join("</li>\n<li>", $res) .
			"</li></ol>\n";

	$res = block_parfois_visible('export_tables', $plie, $res, '', false)
	. "<div style='text-align: center;'><input type='submit' value='"
	. $clic
	. "' /></div>";

  	$arg = "start,$gz,$archive,$rub," .  _VERSION_ARCHIVE;
	$id = 'form_export';
	$att = " method='post' id='$id'";
	$timeout = 'if (manuel) document.getElementById(manuel).submit()';
	$corps = (($nom !== NULL)
	? http_script("manuel= '$id'; window.setTimeout('$timeout', 60000);")
	: '')
	. generer_action_auteur('export_all', $arg, '', $res,  $att, true);
	include_spip('inc/presentation');
	$r = envoi_link('spip', true);
	$r =  f_jQuery($r);
	include_spip('inc/minipres');
	$res = minipres(_T('info_sauvegarde'), $corps);
	return str_replace('</head>', $r . '</head>', $res);
}

// Fabrique la liste a cocher des tables presentes
function controle_tables_en_base($name, $check, $rub)
{
	$p = '/^' . $GLOBALS['table_prefix'] . '/';
	$res = $check;
	foreach(sql_alltable() as $t) {
		$t = preg_replace($p, 'spip', $t);
		if (!in_array($t, $check)) $res[]= $t;
	}

	$rub = $rub ? " <= "  : '';
	foreach ($res as $k => $t) {

		$c = "type='checkbox'"
		. (in_array($t, $check) ? " checked='checked'" : '')
		. " onclick='manuel=false'";

		$res[$k] = "<input $c value='$t' id='$name_$t' name='$name"
			. "[]' />\n"
			. $t
			. " ($rub"
			.  sql_countsel($t)
	  		. ")";
	}
	return $res;
}
?>
