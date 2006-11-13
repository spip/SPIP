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
function inc_informer_dist($id, $col, $exclus, $rac, $type)
{
	global $couleur_foncee,$spip_display,$spip_lang_right ;

	include_spip('inc/texte');
	if ($type == "rubrique") {
		$res = spip_query("SELECT titre, descriptif FROM spip_rubriques WHERE id_rubrique = $id");
		if ($row = spip_fetch_array($res)) {
			$titre = typo($row["titre"]);
			$descriptif = propre($row["descriptif"]);
		} else {
			$titre = _T('info_racine_site');
		}
	} else
		$titre = '';

	$res = '';
	if ($type == "rubrique" AND $spip_display != 1 AND $spip_display!=4 AND $GLOBALS['meta']['image_process'] != "non") {
		$chercher_logo = charger_fonction('chercher_logo', 'inc');
		if ($res = $chercher_logo($id, 'id_rubrique', 'on'))  {
			list($fid, $dir, $nom, $format) = $res;
			$res = ratio_image($fid, $nom, $format, 100, 48, "alt=''");
			if ($res)
				$res =  "<div style='float: $spip_lang_right; margin-$spip_lang_right: -5px; margin-top: -5px;'>$res</div>";
		}
	}

	$rac = htmlentities($rac);

# ce lien provoque la selection (directe) de la rubrique cliquee
# et l'affichage de son titre dans le bandeau
	$titre = strtr(str_replace("'", "&#8217;",
			str_replace('"', "&#34;", textebrut($titre))),
		       "\n\r", "  ");

	return "<div style='display: none;'>"
	. "<input type='text' id='".$rac."_sel' value='$id' />"
	. "<input type='text' id='".$rac."_sel2' value=\""
	. entites_html($titre)
	. "\" />"
	. "</div>"
	. "<div class='arial2' style='padding: 5px; background-color: white; border: 1px solid $couleur_foncee; border-top: 0px;'>"
	. (!$res ? '' : $res)
	. "<div><p><b>$titre</b></p></div>"
	. (!$descriptif ? '' : "<div>$descriptif</div>")
	. "<div style='text-align: $spip_lang_right;'>"
	. "<input type='submit' class='fondo' value='"
	. _T('bouton_choisir')
	. "'\nonclick=\"aff_selection_titre('$titre',$id,'selection_rubrique','id_parent'); return false;\" />"
	.  "</div>"
	.  "</div>";
}
?>
