<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

# Les information d'une rubrique selectionnee dans le mini navigateur

// http://doc.spip.org/@inc_informer_dist
function inc_informer_dist($id, $col, $exclus, $rac, $type, $do='aff')
{
	include_spip('inc/texte');
	if ($type == "rubrique") {
		$row = sql_fetsel("titre, descriptif", "spip_rubriques", "id_rubrique = $id");
		if ($row) {
			$titre = typo($row["titre"]);
			$descriptif = propre($row["descriptif"]);
		} else {
			$titre = _T('info_racine_site');
		}
	} else
		$titre = '';

	$res = '';
	if ($type == "rubrique" AND $GLOBALS['spip_display'] != 1 AND isset($GLOBALS['meta']['image_process']))
	  if ($GLOBALS['meta']['image_process'] != "non") {
		$chercher_logo = charger_fonction('chercher_logo', 'inc');
		if ($res = $chercher_logo($id, 'id_rubrique', 'on'))  {
			list($fid, $dir, $nom, $format) = $res;
			include_spip('inc/filtres_images_mini');
			$res = image_reduire("<img src='$fid' alt='' />", 100, 48);
			if ($res)
				$res =  "<div style='float: ".$GLOBALS['spip_lang_right']."; margin-".$GLOBALS['spip_lang_right'].": -5px; margin-top: -5px;'>$res</div>";
		}
	}

	$rac = spip_htmlentities($rac);

# ce lien provoque la selection (directe) de la rubrique cliquee
# et l'affichage de son titre dans le bandeau
	$titre = strtr(str_replace("'", "&#8217;",
			str_replace('"', "&#34;", textebrut($titre))),
		       "\n\r", "  ");

	$js_func = $do . '_selection_titre';
	return "<div style='display: none;'>"
	. "<input type='text' id='".$rac."_sel' value='$id' />"
	. "<input type='text' id='".$rac."_sel2' value=\""
	. entites_html($titre)
	. "\" />"
	. "</div>"
	. "<div class='informer' style='padding: 5px; border-top: 0px;'>"
	. (!$res ? '' : $res)
	. "<p><b>".safehtml($titre)."</b></p>"
	. (!$descriptif ? '' : "<div>".safehtml($descriptif)."</div>")
	. "<div style='text-align: ".$GLOBALS['spip_lang_right'].";'>"
	. "<input type='submit' class='fondo' value='"
	. _T('bouton_choisir')
	. "'\nonclick=\"$js_func('$titre',$id,'selection_rubrique','id_parent'); return false;\" />"
	.  "</div>"
	.  "</div>";
}
?>
