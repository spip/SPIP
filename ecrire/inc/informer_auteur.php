<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

# Les information d'une rubrique selectionnee dans le mini navigateur

// http://doc.spip.org/@inc_informer_dist
function inc_informer_auteur_dist($id)
{
	global $couleur_foncee,$spip_display,$spip_lang_right ;

	include_spip('inc/presentation');

	$res = spip_query("SELECT * FROM spip_auteurs WHERE id_auteur = $id");
	if ($row = spip_fetch_array($res)) {
			$nom = typo(extraire_multi($row["nom"]));
			$bio = propre($row["bio"]);
			$mail = $row['email'];
			if (!email_valide($mail))
				$nom .= "(<span style='color:red'>"
				. _T('info_email_invalide')
				. '</span>)';
			else $nom = "<a href='mailto:$mail' title=\""
			  . _T('info_ecire_message_prive')
			  . '">'
			  . $nom
			  . "</a>";

			$nb = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_auteurs_articles WHERE id_auteur=$id"));
			if ($nb['n'] > 1)
			$nb = $nb['n']."&nbsp;"._T('info_article_2');
			else if($nb['n'] == 1)
			$nb = "1&nbsp;"._T('info_article');
			else $nb = "&nbsp;";
	} else {
			$nom = "<span style='color:red'>"
			. _T('texte_vide')
			. '</span>';
			$bio = $mail = $nb = '';
	}
			spip_log("nb $nb $bio");
	$res = '';
	if ($spip_display != 1 AND $spip_display!=4 AND $GLOBALS['meta']['image_process'] != "non") {
		$logo_f = charger_fonction('chercher_logo', 'inc');
		if ($res = $logo_f($id, 'id_auteur', 'on'))  {
			list($fid, $dir, $n, $format) = $res;
			$res = ratio_image($fid, $n, $format, 100, 48, "alt=''");
			if ($res)
				$res =  "<div style='float: $spip_lang_right; margin-$spip_lang_right: -5px; margin-top: -5px;'>$res</div>";
		}
	}

	return 	"<div class='arial2' style='padding: 5px; background-color: white; border: 1px solid $couleur_foncee; border-top: 0px;'>"
	. (!$res ? '' : $res)
	. "<div><a href='"
	. generer_url_ecrire('auteur_infos', "id_auteur=$id&initial=-1")
	. "'>"
	. bonhomme_statut($row)
	. "</a> <b>"
	. $nom
	. "</b><br />"
	. $nb
	. "</div><br />"
	. "<div>$bio</div>"
	.  "</div>";
}
?>
