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
function formulaire_discuter($query, $total, $debut, $total_afficher, $script, $args, $mute=false)
{
	$nav ='';
	if ($total > $total_afficher) {
		$evt = (_SPIP_AJAX === 1);
		$nav = "<div class='serif2 centered'>";
		for ($i = 0; $i < $total; $i = $i + $total_afficher){
			$y = $i + $total_afficher - 1;
			if ($i == $debut)
				$nav .= "<span class='spip_medium'><b>[$i-$y]</b></span> ";
			else {
				$a = "$args&debut=$i";
				if (!$evt) {
					$h = generer_url_ecrire($script, $a);
				} else {
					$h = generer_url_ecrire('discuter', $a);
					$evt = "\nonclick=" . ajax_action_declencheur($h,'forum');
				}
				$nav .= "[<a href='$h#forum'$evt>$i-$y</a>] ";
			}
		}
		$nav .= "</div>";
	}

	return $nav 
	. afficher_forum($query, $script, $args, $mute)
	. "<br />"
	. $nav;
}

// http://doc.spip.org/@inc_discuter_dist
function inc_discuter_dist($id_article, $debut=1, $statut='prive')
{
	$debut = intval($debut);
	$id_article = intval($id_article);
	$where = "id_article=" . sql_quote($id_article) . " AND id_parent=0 AND statut=" . sql_quote($statut);

	if (! ($n = sql_countsel('spip_forum', $where))) return '';

	$total_afficher = 8;
	$forum = sql_select('*', 'spip_forum', $where, '',  "date_heure DESC", "$debut,$total_afficher");

	$res = formulaire_discuter($forum, $n, $debut, $total_afficher, 'articles', "id_article=$id_article&statut=$statut");

	return ajax_action_greffe("forum", '', $res);
}
?>
