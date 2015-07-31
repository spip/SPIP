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

# Affiche les infos d'une rubrique selectionnee dans le mini navigateur

function exec_informer_dist()
{
	global $couleur_foncee,$spip_display,$spip_lang_right ;
	global $id, $exclus, $col, $type, $rac;
	$id = intval($id);
	$exclus = intval($exclus);
	$col = intval($col);
	$corps ='';

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
		
	$rac = htmlentities($rac);
	$corps .= "<div style='display: none;'>";
	$corps .= "<input type='text' id='".$rac."_sel' value='$id' />";
	$corps .= "<input type='text' id='".$rac."_sel2' value=\"".entites_html($titre)."\" />";
	$corps .= "</div>";

	$corps .= "<div class='arial2' style='padding: 5px; background-color: white; border: 1px solid $couleur_foncee; border-top: 0px;'>";
	if ($type == "rubrique" AND $spip_display != 1 AND $spip_display!=4 AND $GLOBALS['meta']['image_process'] != "non") {
		$logo_f = charger_fonction('chercher_logo', 'inc');
		if ($res = $logo_f($id, 'id_rubrique', 'on'))
			if ($res = decrire_logo("id_rubrique", 'on', $id, 100, 48, $res))
				$corps .=  "<div style='float: $spip_lang_right; margin-$spip_lang_right: -5px; margin-top: -5px;'>$res</div>";
	}

	$corps .= "<div><p><b>$titre</b></p></div>";
	if (strlen($descriptif) > 0) $corps .= "<div>$descriptif</div>";

	$corps .= "<div style='text-align: $spip_lang_right;'>";
		
# ce lien provoque la selection (directe) de la rubrique cliquee
	$onClick = "findObj('id_parent').value=$id;";
# et l'affichage de son titre dans le bandeau
	$onClick .= "findObj('titreparent').value='"
					. strtr(
						str_replace("'", "&#8217;",
						str_replace('"', "&#34;",
							textebrut($titre))),
						"\n\r", "  ")."';";
	$onClick .= "findObj('selection_rubrique').style.display='none';";
	$onClick .= "return false;";
		
		
	$corps .= "<input type='submit' value='"._T('bouton_choisir')."' onClick=\"$onClick\" class=\"fondo\" />";
	$corps .= "</div>";
	$corps .= "</div>";

	return $corps;
}

?>
