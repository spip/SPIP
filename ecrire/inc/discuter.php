<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

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
function inc_discuter_dist($id, $script, $objet, $statut='prive', $debut=1, $pas=10)
{
	$debut = intval($debut);
	$id = intval($id);
	if (!$pas) $pas = 10;
	$ancre = "poster_forum_prive-$id";
	$clic = _T('icone_poster_message');
	$logo = ($script == 'forum_admin') ?
	  "forum-admin-24.gif" : "forum-interne-24.gif";
	$lien = generer_url_ecrire("poster_forum_prive", "statut=$statut&id=$id&script=$script") ."#formulaire";
	$lien = icone_inline($clic, $lien, $logo, "creer.gif",'center', $ancre);

	$where = (!$objet ? '' : ($objet . "=" . sql_quote($id) . " AND "))
	  . "id_parent=0 AND statut=" . sql_quote($statut);

	$n = sql_countsel('spip_forum', $where);
	if (!$n) return $lien;

	$nav = ($n <= $pas) ? '' :
	  formulaire_discuter($script, "id=$id&$objet=$id&statut=$statut", $debut, $pas, $ancre, $n, $objet);

	$q = sql_select('*', 'spip_forum', $where, '',  "date_heure DESC", "$debut,$pas");
	$args = ($objet ? "$objet=$id&" : '') . "statut=$statut";
	$q = afficher_forum($q, $script,  $args, false);
	$res = $lien. $nav . $q	. "<br />" . $nav;
	return ajax_action_greffe($ancre, '', $res);
}
?>
