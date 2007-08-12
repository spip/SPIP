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

	$id_rubrique = intval(_request('id_rubrique'));

	if ($spip_ecran == "large") {
		$largeur_table = 974;
		$hauteur_table = 400;
		$nb_col = 4;
	} else {
		$largeur_table = 750;
		$hauteur_table = 300;
		$nb_col = 3;
	}
	$largeur_col = floor($largeur_table/$nb_col);
	$profile = ($GLOBALS['var_profile']) ? "&var_profile=1" : '';

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_articles_tous'), "accueil", "tout-site", " hauteurFrame($nb_col);");

	echo "\n<div>&nbsp;</div>";

	echo debut_grand_cadre(true);

	if ($id_rubrique) {
		$j = $nb_col;
		while ($id_rubrique > 0) {
			$result=spip_query("SELECT id_parent FROM spip_rubriques WHERE id_rubrique=$id_rubrique");
			if ($row=sql_fetch($result)){
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
			if ($row = sql_fetch($result)) {
				$la_rubrique =$row['id_parent'];
			}
			
			$compteur = 0;
			$ret = '';
			while ($la_rubrique > 0) {
				$result = spip_query("SELECT titre, id_parent FROM spip_rubriques WHERE id_rubrique ='$la_rubrique'");
				if ($row = sql_fetch($result)) {
					$compteur++;
					$titre = typo($row['titre']);
					$lien = $dest[$nb_col-$compteur-1];
					if (!$la_rubrique=$row['id_parent'])
					  $class = "brouteur_icone_secteur";
					else $class = "brouteur_icone_rubrique";
					$ret = "\n<div class='$class'><a href='" . generer_url_ecrire("brouteur","id_rubrique=$lien$profile") . "'>$titre</a></div>\n<div style='margin-$spip_lang_left: 28px;'>$ret</div>";
				}
			}
			$lien = $dest[$nb_col-$compteur-2];

			// Afficher la hierarchie pour "remonter"
			echo "<div style='text-align: $spip_lang_left;'>";
			
			echo "<div id='brouteur_hierarchie'>"; // pour calculer hauteur de iframe
			echo "<div class='brouteur_icone_racine'><a href='",
				generer_url_ecrire("brouteur","id_rubrique=$lien$profile"),
				"'>",
				_T('info_racine_site'),
				"</a></div>",
				"\n<div style='margin-$spip_lang_left: 28px;'>$ret</div>",
				"</div>";
			echo "</div>";
		}
	} else {
		$dest[0] = '0';
	}

	for ($i=0; $i < $nb_col; $i++) {		
		echo "<iframe width='{$largeur_col}px' style='float:$spip_lang_left' id='iframe$i' name='iframe$i'",
		  (" src='" . generer_url_ecrire('brouteur_frame',"frame=$i$profile&rubrique=".$dest[$i])),
		  "' class='iframe-brouteur' height='",
		  $hauteur_table,
		  "'></iframe>";
	}
	echo fin_grand_cadre(true);

	// fixer la hauteur du brouteur de maniere a remplir l'ecran
	// nota: code tire du plugin dimensions.js
	echo "<script type='text/javascript'><!--
		jQuery('iframe.iframe-brouteur').height(
			Math.max((window.innerHeight || jQuery.boxModel && document.documentElement.clientHeight || document.body.clientHeight || 0)-195,300)
		);
	//--></script>\n";
	echo fin_page();
}
?>
