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

// http://doc.spip.org/@mini_afficher_rubrique
function inc_plonger_dist($id_rubrique, $idom="", $list=array(), $col = 1, $exclu=0) {
	global  $spip_lang_left;
	
	if ($list) $id_rubrique = $list[$col-1];
	
	$ret = '';

	# recherche les filles et petites-filles de la rubrique donnee
	$ordre = array();
	$rub = array();

	$res = spip_query("SELECT rub1.id_rubrique, rub1.titre, rub1.id_parent, rub1.lang, rub1.langue_choisie FROM spip_rubriques AS rub1, spip_rubriques AS rub2 WHERE ((rub1.id_parent = $id_rubrique) OR (rub2.id_parent = $id_rubrique AND rub1.id_parent=rub2.id_rubrique)) AND rub1.id_rubrique!=$exclu GROUP BY rub1.id_rubrique");

	while ($row = spip_fetch_array($res)) {
		$rub[$row['id_parent']]['enfants'] = true;
		if ($row['id_parent'] == $id_rubrique)
			$ordre[$row['id_rubrique']]= trim(typo($row['titre']))
			. (($row['langue_choisie'] != 'oui')
			   ? '' : (' [' . $row['lang'] . ']'));
	}

	$next = $list[$col];
	if ($ordre) {
		asort($ordre);
		$rec = generer_url_ecrire('plonger',"rac=$idom&exclus=$exclu&col=".($col+1));
		$args = "'$idom',this,$col,'$spip_lang_left'";
		while (list($id, $titrebrut) = each($ordre)) {

			$titre = "<div class='"
			. ($id_rubrique ? 'petite-rubrique' : "petit-secteur")
			. "'>"
			. supprimer_numero($titrebrut)
			. "</div>";

			if (isset($rub[$id]["enfants"])) {
				$titre = "<div class='rub-ouverte'>$titre</div>";

				$acces = "firstChild.";
				$url = "\nhref='$rec&amp;id=$id'" ;
			} else {  $url = $acces = ''; }

			$ret .= "<a class='"
			. (($id == $next) ? "highlight" : "pashighlight")
			. "'"
			. $url
			.  "\nonClick=\"changerhighlight(this);return "
			. (!is_array($list) ? ' false' 
			   : "aff_selection_provisoire($id,$args)")
# ce lien provoque la selection (directe) de la rubrique cliquee
# et l'affichage de son titre dans le bandeau
			. "\"\nondblclick=\""
			. "return aff_selection_titre(this."
			. $acces
			. "firstChild.firstChild.nodeValue,$id,$args);"
			. "\">$titre</a>";
		}
	}

	$idom2 = $idom . "_col_".($col+1);
	$left = ($col*150);

	return http_img_pack("searching.gif", "*", "style='visibility: hidden; position: absolute; $spip_lang_left: "
	. ($left-30)
	. "px; top: 2px; z-index: 2;' id='img_$idom2'")
	. "<div style='width: 150px; height: 100%; overflow: auto; position: absolute; top: 0px; $spip_lang_left: "
	.($left-150)
	."px;'>"
	. $ret
	. "\n</div>\n<div id='$idom2'>"
	. ($next
	   ? inc_plonger_dist($id_rubrique, $idom, $list, $col+1, $exclu)
	   : "")
	. "\n</div>";
}

?>
