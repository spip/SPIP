<?php

include ("inc.php3");

debut_page(_T('titre_page_etat_traductions'), "asuivre", "plan-trad");

if ($connect_statut == '0minirezo') {
	echo "<br>";
	barre_onglets("traductions", "detail");
}

debut_gauche();

if (!$trad_lang) $trad_lang = $spip_lang;


//debut_cadre_relief('langues-24.gif');
echo "<p>";
debut_cadre_formulaire();

$link = new Link();

echo "<font face='Arial,Sans,sans-serif'>";
echo _T('titre_cadre_afficher_traductions')."&nbsp;: <p>";
echo menu_langues('trad_lang', $trad_lang);
echo "</font>";

//fin_cadre_relief();
fin_cadre_formulaire();


debut_boite_info();

echo _T('texte_plan_trad');

echo "<p>"."<IMG SRC='img_pack/langues-off-12.gif' WIDTH='12' HEIGHT='12' BORDER='0'> "._T('texte_plan_trad_en_cours');

echo "<p>"."<IMG SRC='img_pack/langues-modif-12.gif' WIDTH='12' HEIGHT='12' BORDER='0'> "._T('texte_plan_trad_modif');

echo "<p><i>"._T('texte_plan_trad_note')."</i>";

fin_boite_info();


debut_droite();


function afficher_rubrique($id_parent, $marge = 0, $cond = '', $afficher = true) {
	global $deplier;
	global $liste_rubs;
	global $trad_lang;
	global $couleur_foncee;
	global $dir_lang;
	global $spip_lang_left;
	static $total_articles = 0;
	static $rubriques_actives;

	//
	// Calculer les rubriques actives
	// (contenant des articles a afficher)
	//

	if (!$rubriques_actives) {
		$rubriques_actives[0] = true;
	
		$query = "SELECT DISTINCT a.id_rubrique ".
			"FROM spip_articles AS a LEFT JOIN spip_articles AS t ".
			"ON (a.id_article = t.id_trad AND t.lang = '$trad_lang') ".
			"WHERE a.statut='publie' AND a.lang!='$trad_lang' AND (a.id_trad=0 OR a.id_trad=a.id_article) ".
			"AND (t.id_article IS NULL OR t.statut!='publie' OR t.date_modif < a.date_modif)";
		$result = spip_query($query);
		while ($row = spip_fetch_array($result)) {
			$rubriques_actives[$row['id_rubrique']] = true;
		}
	}
	
	if (!$cond) $cond = array();
	$vide = !$rubriques_actives[$id_parent];
	if (!$vide) {
		echo $cond['avant'];
	}

	//
	// Afficher les articles a traduire
	//

	if ($id_parent AND !$vide AND $afficher) {
		$query = "SELECT a.id_article, a.titre, a.date, a.descriptif, a.lang, t.id_article AS trad_id_article, t.statut AS trad_statut, (t.date_modif >= a.date_modif) AS trad_a_jour ".
			"FROM spip_articles AS a LEFT JOIN spip_articles AS t ".
			"ON (a.id_article = t.id_trad AND t.lang = '$trad_lang') ".
			"WHERE a.id_rubrique=$id_parent AND a.statut='publie' AND a.lang!='$trad_lang' AND (a.id_trad=0 OR a.id_trad=a.id_article) ".
			"AND (t.id_article IS NULL OR t.statut!='publie' OR t.date_modif < a.date_modif) ".
			"ORDER BY t.statut='publie' DESC, trad_a_jour DESC, a.titre";
		$result = spip_query($query);

		$table = '';
	
		while ($row = spip_fetch_array($result)) {
			$total_articles ++;
			if ($lang = $row['lang']) changer_typo($lang);
			$id_article = $row['id_article'];
			$titre = typo($row['titre']);
			$date = $row['date'];
			$id_article_traduit = $row['trad_id_article'];
			$statut_traduit = $row['trad_statut'];
			$a_jour = $row['trad_a_jour'];
			$descriptif = $row['descriptif'];
			if ($descriptif) $descriptif = ' title="'.attribut_html(typo($descriptif)).'"';
			
			$vals = '';

			$popularite = ceil(min(100,100 * $row['popularite'] / max(1, 0 + lire_meta('popularite_max'))));
			$petition = $row['petition'];

			$s = "";
			if ($id_article_traduit) {
				$s .= "<a href='articles.php3?id_article=$id_article_traduit'>";
				if ($a_jour AND $statut_traduit == 'publie') {
					$puce = 'langues-12.gif';
					$puce_title = "Traduction &agrave; jour";
				}
				else if ($statut_traduit == 'publie') {
					$puce = 'langues-modif-12.gif';
					$puce_title = "L'article original a &eacute;t&eacute; modifi&eacute, la traduction n'est plus &agrave; jour";
				}
				else {
					$puce = 'langues-off-12.gif';
					$puce_title = "Traduction en cours";
				}
				$s .= "<img src='img_pack/$puce' width='12' height='12' border='0' title=\"".attribut_html($puce_title)."\">";
				$s .= "</a>&nbsp;&nbsp;";
			}
			
			$s .= "<a href=\"articles.php3?id_article=$id_article\"$descriptif$dir_lang>".typo($titre)."</a>";
			$s .= " &nbsp; <font size='1' color='#666666'$dir_lang>(".traduire_nom_langue($lang).")</font>";

			$vals[] = $s;

			$s = affdate($date);
			$vals[] = $s;

			$table[] = $vals;
		}
		spip_free_result($result);

		if ($table) {
			echo "<table width=100% cellpadding=0 cellspacing=0 border=0><tr><td width=100% background=''>";
			echo "<table width=100% cellpadding=3 cellspacing=0 border=0>";
			$largeurs = array('', 90);
			$styles = array('arial2', 'arial1');
			afficher_liste($largeurs, $table, $styles);
			echo "</table></td></tr></table>";
		}
	}
	
	if (!$vide) {
		echo $cond['apres'];
	}

	//
	// Parcourir les sous-rubriques
	//

	if ($afficher OR $vide) {
		$query_parent = "SELECT id_rubrique, titre FROM spip_rubriques WHERE id_parent=$id_parent ORDER BY titre";
		$result_parent = spip_query($query_parent);
	
		while ($row_parent = spip_fetch_array($result_parent)) {
			$id_rubrique = $row_parent['id_rubrique'];
			$titre_rubrique = typo($row_parent['titre']);
	
			if ($deplier == 'oui')
				$afficher_fils = !$liste_rubs[$id_rubrique];
			else if ($deplier == 'non')
				$afficher_fils = $liste_rubs[$id_rubrique];
			else $afficher_fils = ($total_articles < 30) || $liste_rubs[$id_rubrique];
			$afficher_fils &= $afficher;

			$rubs = $liste_rubs;
			if ($rubs[$id_rubrique])
				unset($rubs[$id_rubrique]);
			else
				$rubs[$id_rubrique] = $id_rubrique;
			$lien = new Link();
			$lien->addVar('rubs', join(',', $rubs));

			$bandeau = "<div style='width: 100%; margin: 0px; padding: 3px; border: none; background: $couleur_foncee;'>\n" .
				"<b><a href='".$lien->getUrl()."'><img src='img_pack/".($afficher_fils ? 'triangle-bleu-bas.gif' : 'triangle-bleu.gif')."' alt='' width='14' height='14' border='0'></a> ".
				"<a href='naviguer.php3?coll=$id_rubrique'><font color='white' face='Verdana,Arial,Sans,sans-serif'>$titre_rubrique</font></a></b>\n".
				"</div>\n";

			if ($afficher) {
				$cond_fils['avant'] = "<div style='margin: 0px; margin-$spip_lang_left: ".$marge."px; padding: 0px; background: none;'>\n".$bandeau;
				$cond_fils['apres'] = "</div>\n";
				if (!$id_parent) $cond_fils['avant'] = "<p>".$cond_fils['avant'];
			}
			if ($vide) {
				$cond_fils['avant'] = $cond['avant'].$cond['apres'].$cond_fils['avant'];
			}
			
			if (afficher_rubrique($id_rubrique, $marge + 20, $cond_fils, $afficher_fils)) $vide = false;
			if (!$afficher AND !$vide) break;
		}
	}
	return !$vide;
}


echo "<p>";
$lien = new Link();
$lien->delVar('rubs');
$lien->addVar('deplier', 'oui');
echo "<a href='".$lien->getUrl()."'>"._T('lien_tout_deplier')."</a>";
$lien->addVar('deplier', 'non');
echo " | <a href='".$lien->getUrl()."'>"._T('lien_tout_replier')."</a>";
echo "<p>";

$liste_rubs = array();
if ($rubs) {
	$t = explode(',', $rubs);
	while (list(, $rub) = each($t)) {
		$liste_rubs[$rub] = $rub;
	}
}

afficher_rubrique(0);


fin_page();

?>

