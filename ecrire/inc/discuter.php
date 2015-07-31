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

include_spip('inc/forum');
include_spip('inc/presentation');

// http://doc.spip.org/@formulaire_discuter
function formulaire_discuter($script, $args, $debut, $pas, $ancre, $total, $objet)
{
	$nav = '';
	$e = (_SPIP_AJAX === 1);
	for ($tranche = 0; $tranche < $total; $tranche += $pas){
		$y = $tranche + $pas - 1;
		if ($tranche == $debut)
			$nav .= "<span class='spip_medium'><b>[$tranche-$y]</b></span> ";
		else {
			$h = "$args&debut=$tranche";
			if (!$e) {
				$h = generer_url_ecrire($script, $h);
			} else {
				$h .= "&script=$script";
				if ($objet) $h .= "&objet=$objet";
				$h = generer_url_ecrire('discuter', $h);
				$e = "\nonclick=" . ajax_action_declencheur($h,$ancre);
			}
			$nav .= "[<a href='$h#$ancre'$e>$tranche-$y</a>] ";
		}
	}
	return "<div class='serif2 centered'>$nav</div>";
}

// http://doc.spip.org/@inc_discuter_dist
function inc_discuter_dist($id, $script, $objet, $statut='prive', $debut=NULL, $pas=NULL, $id_parent = 0)
{
	if ($GLOBALS['meta']['forum_prive_objets'] == 'non')
		return '';

	$debut = intval($debut);
	if (!$pas = intval($pas)) $pas = 10;
	$id = intval($id);
	$args = ($objet ? "$objet=$id&" : '') . "statut=$statut";
	$ancre = "poster_forum_prive" . ($objet ? '' : "-$id");

	if ($id_parent) {
	  $id_t = sql_getfetsel('id_thread', 'spip_forum', "id_forum=$id_parent");
	  $query = array('SELECT' => "*", 'FROM' => "spip_forum", 'WHERE' => "id_forum=$id_t");

	  $res = afficher_forum($query, $script, $args);

	} else {
		$clic = _T('icone_poster_message');
		$logo = ($script == 'forum_admin') ?
		  "forum-admin-24.gif" : "forum-interne-24.gif";
		$lien = generer_url_ecrire("poster_forum_prive", "statut=$statut&id=$id&script=$script");
		$res = icone_inline($clic, $lien, $logo, "creer.gif",'center', $ancre);

		$where = ((!$objet OR !$id) ? '' : ($objet . "=" . sql_quote($id) . " AND "))
		  . "id_parent=0 AND statut=" . sql_quote($statut);

		$n = sql_countsel('spip_forum', $where);
		if ($n) {

			$nav = ($n <= $pas) ? '' :
			  formulaire_discuter($script, "id=$id&$objet=$id&statut=$statut", $debut, $pas, $ancre, $n, $objet);

			$query = array('SELECT' => "*", 'FROM' => "spip_forum", 'WHERE' =>  $where, 'ORDER BY' => "date_heure DESC", 'LIMIT' => "$debut,$pas");
			$q = afficher_forum($query, $script,  $args, false);
			$res .= $nav . $q	. "<br />" . $nav;
		}
	}
	return ajax_action_greffe($ancre, '', $res);
}
?>
