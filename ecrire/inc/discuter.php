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

include_spip('inc/forum');
include_spip('inc/presentation');

// http://doc.spip.org/@formulaire_discuter
function formulaire_discuter($query, $total, $debut, $total_afficher, $script, $args, $mute=false)
{
	$nav ='';
	if ($total > $total_afficher) {
		$evt = (_SPIP_AJAX === 1);
		$nav = "<div class='serif2' align='center'>";
		for ($i = 0; $i < $total; $i = $i + $total_afficher){
			$y = $i + $total_afficher - 1;
			if ($i == $debut)
				$nav .= "<font size='3'><b>[$i-$y]</b></font> ";
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
function inc_discuter_dist($id_article, $flag, $debut=1)
{
	$debut = intval($debut);
	$id_article = intval($id_article);

	$res = spip_fetch_array(spip_query("SELECT COUNT(*) AS cnt FROM spip_forum WHERE statut='prive' AND id_article='$id_article' AND id_parent=0"));
	$res = $res["cnt"];

	if ($res) {

		$total_afficher = 8;
		$forum = spip_query("SELECT * FROM spip_forum WHERE statut='prive' AND id_article='$id_article' AND id_parent=0 ORDER BY date_heure DESC" .   " LIMIT $debut,$total_afficher"   );
#				   " LIMIT $total_afficher OFFSET $debut" # PG

		$res = formulaire_discuter($forum, $res, $debut, $total_afficher, 'articles', "id_article=$id_article");
	} else $res ='';

	return ajax_action_greffe("forum", $res);
}
?>
