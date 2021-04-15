<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

# Les information d'une rubrique selectionnee dans le mini navigateur

// https://code.spip.net/@inc_informer_dist
function inc_informer_dist($id, $col, $exclus, $rac, $type, $do = 'aff') {
	include_spip('inc/texte');
	$titre = $descriptif = '';
	if ($type == "rubrique") {
		$row = sql_fetsel("titre, descriptif", "spip_rubriques", "id_rubrique = " . intval($id));
		if ($row) {
			$titre = typo($row["titre"]);
			$descriptif = propre($row["descriptif"]);
		} else {
			$titre = _T('info_racine_site');
		}
	}

	$res = '';
	if ($type == "rubrique" and $GLOBALS['spip_display'] != 1 and isset($GLOBALS['meta']['image_process'])) {
		if ($GLOBALS['meta']['image_process'] != "non") {
			$chercher_logo = charger_fonction('chercher_logo', 'inc');
			if ($res = $chercher_logo($id, 'id_rubrique', 'on')) {
				list($fid, $dir, $nom, $format) = $res;
				include_spip('inc/filtres_images_mini');
				$res = image_reduire("<img src='$fid' alt='' />", 100, 48);
				if ($res) {
					$res = "<div class='informer__media' style='float: " . $GLOBALS['spip_lang_right'] . "; margin-" . $GLOBALS['spip_lang_right'] . ": -5px; margin-top: -5px;'>$res</div>";
				}
			}
		}
	}

	$rac = spip_htmlentities($rac, ENT_QUOTES);
	$do = spip_htmlentities($do, ENT_QUOTES);
	$id = intval($id);

# ce lien provoque la selection (directe) de la rubrique cliquee
# et l'affichage de son titre dans le bandeau
	$titre = strtr(str_replace("'", "&#8217;",
		str_replace('"', "&#34;", textebrut($titre))),
		"\n\r", "  ");

	$js_func = $do . '_selection_titre';

	return "<div style='display: none;'>"
	. "<input type='text' id='" . $rac . "_sel' value='$id' />"
	. "<input type='text' id='" . $rac . "_sel2' value=\""
	. entites_html($titre)
	. "\" />"
	. "</div>"
	. "<div class='informer' style='padding: 5px; border-top: 0px;'>"
	. '<div class="informer__item">'
	. (!$res ? '' : $res)
	. "<p class='informer__titre'><b>" . safehtml($titre) . "</b></p>"
	. (!$descriptif ? '' : "<div class='informer__descriptif'>" . safehtml($descriptif) . "</div>")
	. '</div>'
	. "<div class='informer__action' style='clear:both; text-align: " . $GLOBALS['spip_lang_right'] . ";'>"
	. "<input type='submit' class='fondo btn submit' value='"
	. _T('bouton_choisir')
	. "'\nonclick=\"$js_func('$titre',$id,'selection_rubrique','id_parent'); return false;\" />"
	. "</div>"
	. "</div>";
}
