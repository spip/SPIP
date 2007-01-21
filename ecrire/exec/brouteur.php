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

include_spip('inc/presentation');

// http://doc.spip.org/@exec_brouteur_dist
function exec_brouteur_dist()
{
	global $spip_ecran, $spip_lang_left;

	$id_rubrique = intval(_request($id_rubrique));

	if ($spip_ecran == "large") {
		$largeur_table = 974;
		$hauteur_table = 400;
		$nb_col = 4;
	} else {
		$largeur_table = 750;
		$hauteur_table = 300;
		$nb_col = 3;
	}
	$largeur_col = round($largeur_table/$nb_col);

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_articles_tous'), "accueil", "tout-site", " hauteurFrame($nb_col);");

	echo "\n<div>&nbsp;</div>";

	echo "\n<table border='0' cellpadding='0' cellspacing='2' width='$largeur_table'>";

	if ($id_rubrique) {
		$j = $nb_col;
		while ($id_rubrique > 0) {
			$result=spip_query("SELECT id_parent FROM spip_rubriques WHERE id_rubrique='$id_rubrique'");
			if ($row=spip_fetch_array($result)){
				$j--;
				$dest[$j] = $id_rubrique;
				$id_rubrique =$row['id_parent'];
			}
		}
		$dest[$j-1] = 0;
		
		while (!$dest[1]) {
			for ($i = 0; $i < $nb_col; $i++) {
				$dest[$i] = $dest[$i+1];
			}
		}

		if ($dest[0] > 0 AND $dest[$nb_col-2]) {
			
			$la_rubrique = $dest[0];
			
			$result = spip_query("SELECT id_parent FROM spip_rubriques WHERE id_rubrique ='$la_rubrique'");
			if ($row = spip_fetch_array($result)) {
				$la_rubrique =$row['id_parent'];
			}
			
			$compteur = 0;
			$ret = '';
			while ($la_rubrique > 0) {
				$result = spip_query("SELECT * FROM spip_rubriques WHERE id_rubrique ='$la_rubrique'");
				if ($row = spip_fetch_array($result)) {
					$compteur++;
					$titre = typo($row['titre']);
					$la_rubrique =$row['id_parent'];
					$lien = $dest[$nb_col-$compteur-1];
					if ($la_rubrique == 0) $icone = "secteur-24.gif";
					else $icone = "rubrique-24.gif";
					$ret = "\n<div " .
					  http_style_background($icone,
								"$spip_lang_left no-repeat; padding-top: 5px; padding-bottom: 5px; padding-$spip_lang_left: 28px") . "><a href='" . generer_url_ecrire("brouteur","id_rubrique=$lien") . "'>$titre</a></div>\n<div style='margin-$spip_lang_left: 28px;'>$ret</div>";
				}
			}
			$lien = $dest[$nb_col-$compteur-2];

			// Afficher la hierarchie pour "remonter"
			echo "<tr><td colspan='$nb_col' style='text-align: $spip_lang_left;'>";
			
			echo "<div id='brouteur_hierarchie'>"; // pour calculer hauteur de iframe
			echo "<div ",
				http_style_background("racine-site-24.gif",
						"$spip_lang_left no-repeat; padding-top: 5px; padding-bottom: 5px; padding-$spip_lang_left: 28px"),
				"><a href='",
				generer_url_ecrire("brouteur","id_rubrique=$lien"),
				"'>",
				_T('info_racine_site'),
				"</a></div>",
				"\n<div style='margin-$spip_lang_left: 28px;'>$ret</div>",
				"</div>";
			echo "</div></td></tr>";
		}
	} else {
		$dest[0] = '0';
	}

	echo "\n<tr>";

	for ($i=0; $i < $nb_col; $i++) {
		echo "\n<td valign='top' width='$largeur_col'>";
		
		echo "<iframe width='100%' id='iframe$i' name='iframe$i'",
			(" src='" . generer_url_ecrire('brouteur_frame',"rubrique=".$dest[$i]."&frame=$i'")),
		  " class='iframe-brouteur' height='",
		  $hauteur_table,
		  "'></iframe>";

		echo "</td>";
	}
	echo "\n</tr></table>";

	// fixer la hauteur du brouteur de maniere a remplir l'ecran
	// nota: code tire du plugin dimensions.js
	echo "<script type='text/javascript'><!--
		jQuery('iframe.iframe-brouteur').height(
			Math.max(jQuery(window.innerHeight || jQuery.boxModel && document.documentElement.clientHeight || document.body.clientHeight || 0)-190,300)
		);
	//--></script>\n";
	echo fin_page();
}
?>
