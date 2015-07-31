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

// http://doc.spip.org/@exec_auteurs_dist
function exec_auteurs_dist()
{
	$tri = preg_replace('/\W/', '', _request('tri'));
	if (!$tri) $tri='nom'; 
	$statut = preg_replace('/\W/', '', _request('statut'));

	$result = requete_auteurs($tri, $statut);
	$nombre_auteurs = spip_num_rows($result);
	$max_par_page = 30;
	$debut = intval(_request('debut'));
	if ($debut > $nombre_auteurs - $max_par_page)
		$debut = max(0,$nombre_auteurs - $max_par_page);

	list($auteurs, $lettre)= lettres_d_auteurs($result, $debut, $max_par_page, $tri);

	$res = auteurs_tranches(afficher_n_auteurs($auteurs), $debut, $lettre, $tri, $statut, $max_par_page, $nombre_auteurs);

	if (_request('var_ajaxcharset'))
	  ajax_retour($res);
	else {

		pipeline('exec_init',array('args'=>array('exec'=>'auteurs'),'data'=>''));
		// Chaine indiquant le mode de tri est obsolète depuis Ajax
		$commencer_page = charger_fonction('commencer_page', 'inc');
		echo $commencer_page(_T('info_auteurs_par_tri',
					array('partri' => '')),
				     "auteurs","redacteurs");
		bandeau_auteurs($tri, $statut=='6forum');

		echo "<div id='auteurs'>", $res, "</div>";
		echo pipeline('affiche_milieu',array('args'=>array('exec'=>'auteurs'),'data'=>''));
		echo fin_gauche(), fin_page();
	}
}

// http://doc.spip.org/@lettres_d_auteurs
function lettres_d_auteurs($query, $debut, $max_par_page, $tri)
{
	$auteurs = $lettre = array();
	$lettres_nombre_auteurs =0;
	$lettre_prec ="";
	$i = 0;
	while ($auteur = spip_fetch_array($query)) {
		if ($i>=$debut AND $i<$debut+$max_par_page) {
			if ($auteur['statut'] == '0minirezo')
				$auteur['restreint'] = spip_num_rows(spip_query("SELECT id_auteur FROM spip_auteurs_rubriques WHERE id_auteur=".$auteur['id_auteur']));
			$auteurs[] = $auteur;
		}
		$i++;

		if ($tri == 'nom') {
			$premiere_lettre = strtoupper(spip_substr(extraire_multi($auteur['nom']),0,1));
			if ($premiere_lettre != $lettre_prec) {
				$lettre[$premiere_lettre] = $lettres_nombre_auteurs;
			}
			$lettres_nombre_auteurs ++;
			$lettre_prec = $premiere_lettre;
		}
	}

	return array($auteurs, $lettre);
}

// http://doc.spip.org/@bandeau_auteurs
function bandeau_auteurs($tri, $visiteurs)
{
	global $connect_id_auteur,   $connect_statut,   $connect_toutes_rubriques;

	debut_gauche();

	debut_boite_info();
	if ($visiteurs) 
		echo "\n<p class='arial1'>"._T('info_gauche_visiteurs_enregistres'), '</p>';
	else 
		echo "\n<p class='arial1'>"._T('info_gauche_auteurs'), '</p>';

	if ($connect_statut == '0minirezo')
		echo "\n<br />". _T('info_gauche_auteurs_exterieurs');

	fin_boite_info();

	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'auteurs'),'data'=>''));

	if ($connect_statut == '0minirezo') {

		if ($connect_toutes_rubriques) 
			$res = icone_horizontale(_T('icone_creer_nouvel_auteur'), generer_url_ecrire("auteur_infos", 'new=oui'), "auteur-24.gif", "creer.gif", false);
		else $res = '';

		$res .= icone_horizontale(_T('icone_informations_personnelles'), generer_url_ecrire("auteur_infos","id_auteur=$connect_id_auteur"), "fiche-perso-24.gif","rien.gif", false);

		$n = spip_num_rows(spip_query("SELECT id_auteur FROM spip_auteurs WHERE statut='6forum' LIMIT 1"));
		if ($n) {
			if ($visiteurs)
				$res .= icone_horizontale (_T('icone_afficher_auteurs'), generer_url_ecrire("auteurs"), "auteur-24.gif", "", false);
			else
				$res .= icone_horizontale (_T('icone_afficher_visiteurs'), generer_url_ecrire("auteurs","statut=6forum"), "auteur-24.gif", "", false);
		}
		echo bloc_des_raccourcis($res);
	}
	creer_colonne_droite();
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'auteurs'),'data'=>''));
	debut_droite();

	echo "\n<br />";
	gros_titre($visiteurs ? _T('info_visiteurs') :  _T('info_auteurs'));
	echo "\n<br />";
}

// http://doc.spip.org/@auteurs_tranches
function auteurs_tranches($auteurs, $debut, $lettre, $tri, $statut, $max_par_page, $nombre_auteurs)
{
	global $spip_lang_right;

	$arg = $statut ? "&statut=$statut" : '';
	$res ="\n<tr class='toile_gris_moyen'>"
	. "\n<td style='width: 20px'>";

	if ($tri=='statut' OR $statut !='')
  		$res .= http_img_pack('admin-12.gif','', " class='lang'");
	else {
	  $t =  _T('lien_trier_statut');
	  $res .= auteurs_href(http_img_pack('admin-12.gif', $t, "class='lang'"),"tri=statut$arg", " title=\"$t\"");
	}

	$res .= "</td><td style='width: 20px'></td><td colspan='2'>";

	if ($tri=='nom')
		$res .= '<b>'._T('info_nom').'</b>';
	else
		$res .= auteurs_href(_T('info_nom'), "tri=nom$arg", " title='"._T('lien_trier_nom'). "'");


	$res .= "</td><td>";

	$col = ($statut == '6forum') ? _T('message') : _T('info_articles');
	if ($tri=='nombre')
		$res .= '<b>' . $col .'</b>';
	else
		$res .= auteurs_href($col, "tri=nombre$arg", " title=\""._T('lien_trier_nombre_articles'). '"');

	$res .= "</td></tr>\n";

	if ($nombre_auteurs > $max_par_page) {
		$res .= "\n<tr class='toile_blanche'><td class='arial1' colspan='5'>";

		for ($j=0; $j < $nombre_auteurs; $j+=$max_par_page) {
			if ($j > 0) 	$res .= " | ";

			if ($j == $debut)
				$res .= "<b>$j</b>";
			else if ($j > 0)
				$res .= auteurs_href($j, "tri=$tri&statut=$statut&debut=$j");
			else
				$res .= auteurs_href('0', "tri=$tri&statut=$statut");
			if ($debut > $j  AND $debut < $j+$max_par_page){
				$res .= " | <b>$debut</b>";
			}
		}
		$res .= "</td></tr>\n";

		if ($tri == 'nom') {
			$res .= "\n<tr class='toile_blanche'><td class='arial11' colspan='5'>";
			foreach ($lettre as $key => $val) {
				if ($val == $debut)
					$res .= "<b>$key</b>\n";
				else
					$res .= auteurs_href($key, "tri=$tri&statut=$statut&debut=$val") . "\n";
			}
			$res .= "</td></tr>\n";
		}
	}

	$nav = '';
	$debut_suivant = $debut + $max_par_page;
	if ($debut_suivant < $nombre_auteurs OR $debut > 0) {
		$nav = "\n<table id='bas' style='width: 100%' border='0'>"
		. "\n<tr class='toile_blanche'><td align='left'>";

		if ($debut > 0) {
			$debut_prec = max($debut - $max_par_page, 0);
			$nav .= auteurs_href('&lt;&lt;&lt;',"tri=$tri&debut=$debut_prec&statut=$statut");
		}
		$nav .= "</td><td style='text-align: $spip_lang_right'>";
		if ($debut_suivant < $nombre_auteurs) {
			$nav .= auteurs_href('&gt;&gt;&gt;',"tri=$tri&debut=$debut_suivant&statut=$statut");
		}
		$nav .= "</td></tr></table>\n";
	}

	return 	debut_cadre_relief('auteur-24.gif',true)
	. "\n<table  class='arial2' border='0' cellpadding='2' cellspacing='0' style='width: 100%; border: 1px solid #aaaaaa;'>\n"
	. $res
	. $auteurs
	. "</table>\n<br />"
	.  $nav
	. fin_cadre_relief(true);
}

// http://doc.spip.org/@auteurs_href
function auteurs_href($clic, $args='', $att='')
{
	$h = generer_url_ecrire('auteurs', $args);
	$a = 'auteurs';

	if (_SPIP_AJAX === 1 )
		$att .= ("\nonclick=" . ajax_action_declencheur($h,$a));

	return "<a href='$h#$a'$att>$clic</a>";
}

// http://doc.spip.org/@requete_auteurs
function requete_auteurs($tri, $statut)
{
  global $connect_statut, $spip_lang, $connect_id_auteur;

//
// Construire la requete
//

// si on n'est pas minirezo, ignorer les auteurs sans article publie
// sauf les admins, toujours visibles.
// limiter les statuts affiches
if ($connect_statut == '0minirezo') {
	if ($statut) {
		$sql_visible = "aut.statut IN ('$statut')";
	} else {
		$sql_visible = "aut.statut IN ('0minirezo','1comite','5poubelle')";
	}
} else {
	$sql_visible = "(
		aut.statut = '0minirezo'
		OR art.statut IN ('prop', 'publie')
		OR aut.id_auteur=$connect_id_auteur
	)";
}

$sql_sel = '';

// tri
switch ($tri) {
case 'nombre':
	$sql_order = ' compteur DESC, unom';
	break;

case 'statut':
	$sql_order = ' statut, unom';
	break;

case 'nom':
default:
	$sql_sel = ", ".creer_objet_multi ("nom", $spip_lang);
	$sql_order = " multi";
}


//
// La requete de base est tres sympa
// (pour les visiteurs, ca postule que les messages concernent des articles)

 $row = spip_query("SELECT							aut.id_auteur AS id_auteur,							aut.statut AS statut,								aut.nom AS nom,								UPPER(aut.nom) AS unom,							count(lien.id_article) as compteur							$sql_sel									FROM spip_auteurs as aut " . (($statut == '6forum') ? 			"LEFT JOIN spip_forum AS lien ON aut.id_auteur=lien.id_auteur " :		("LEFT JOIN spip_auteurs_articles AS lien ON aut.id_auteur=lien.id_auteur	 LEFT JOIN spip_articles AS art ON (lien.id_article = art.id_article)")) .	" WHERE $sql_visible GROUP BY aut.id_auteur ORDER BY $sql_order");
 return $row;
}

// http://doc.spip.org/@afficher_n_auteurs
function afficher_n_auteurs($auteurs) {

	$res = '';
	$formater_auteur = charger_fonction('formater_auteur', 'inc');
	foreach ($auteurs as $row) {

		list($s, $mail, $nom, $w, $p) = $formater_auteur($row['id_auteur']);
		$res .= "\n<tr class='toile_gris_leger'>"
		. "\n<td style='border-top: 1px solid #cccccc;'>"
		. $s
		. "</td><td class='arial1' style='border-top: 1px solid #cccccc;'>"
		. $mail
		. "</td><td class='verdana1' style='border-top: 1px solid #cccccc;'>"
		. $nom
		. ((isset($row['restreint']) AND $row['restreint'])
		   ? (" &nbsp;<small>"._T('statut_admin_restreint')."</small>")
		   : '')
		 ."</td><td class='arial1' style='border-top: 1px solid #cccccc;'>"
		 . $w
		 . "</td><td class='arial1' style='border-top: 1px solid #cccccc;'>"
		 . $p
		.  "</td></tr>\n";
	}
	return $res;
}
?>
