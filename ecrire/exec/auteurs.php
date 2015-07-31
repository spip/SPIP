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

include_spip('inc/presentation');

// http://doc.spip.org/@exec_auteurs_dist
function exec_auteurs_dist()
{
	global  $visiteurs;

	$tri = preg_replace('/\W/', '', _request('tri'));
	if (!$tri) $tri='nom'; 

	$result = requete_auteurs($tri, $visiteurs);
	$nombre_auteurs = spip_num_rows($result);
	$max_par_page = 30;
	$debut = intval(_request('debut'));
	if ($debut > $nombre_auteurs - $max_par_page)
		$debut = max(0,$nombre_auteurs - $max_par_page);

	list($auteurs, $lettre)= lettres_d_auteurs($result, $debut, $max_par_page, $tri);

	$res = auteurs_tranches(afficher_n_auteurs($auteurs), $debut, $lettre, $tri, $visiteurs, $max_par_page, $nombre_auteurs);

	if (_request('var_ajaxcharset')) ajax_retour($res);

	pipeline('exec_init',array('args'=>array('exec'=>'auteurs'),'data'=>''));

	bandeau_auteurs($auteurs, $debut, $tri, $visiteurs, $max_par_page, $nombre_auteurs);

	echo "<div id='auteurs'>", $res, "</div>", fin_page();
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
function bandeau_auteurs($auteurs, $debut, $tri, $visiteurs, $max_par_page, $nombre_auteurs)
{
	global $options, $spip_lang_right, $connect_id_auteur,   $connect_statut,   $connect_toutes_rubriques;

	if ($tri=='nom') $s = _T('info_par_nom');
	if ($tri=='statut') $s = _T('info_par_statut');
	if ($tri=='nombre') $s = _T('info_par_nombre_articles');
	$s = ' ('._T('info_par_nombre_article').')';

	if ($visiteurs == "oui") {
		debut_page(_T('titre_page_auteurs'),"auteurs","redacteurs");
		$visiteurs = '&visiteurs=oui';
	} else {
		debut_page(_T('info_auteurs_par_tri', array('partri' => $s)),"auteurs","redacteurs");
	$visiteurs = "";
	}
	debut_gauche();

	debut_boite_info();
	if ($visiteurs)
		echo "\n<p class='arial1'>"._T('info_gauche_visiteurs_enregistres'), '</p>';
	else {
		echo "\n<p class='arial1'>"._T('info_gauche_auteurs'), '</p>';

	if ($connect_statut == '0minirezo')
		echo "\n<br />". _T('info_gauche_auteurs_exterieurs');
	}
	fin_boite_info();


	if ($connect_statut == '0minirezo') {

		if ($connect_toutes_rubriques) 
			$res = icone_horizontale(_T('icone_creer_nouvel_auteur'), generer_url_ecrire("auteur_infos"), "auteur-24.gif", "creer.gif", false);
		else $res = '';

		$res .= icone_horizontale(_T('icone_informations_personnelles'), generer_url_ecrire("auteurs_edit","id_auteur=$connect_id_auteur"), "fiche-perso-24.gif","rien.gif", false);

		$n = spip_num_rows(spip_query("SELECT id_auteur FROM spip_auteurs WHERE statut='6forum' LIMIT 1"));
		if ($n) {
			if ($visiteurs)
				$res .= icone_horizontale (_T('icone_afficher_auteurs'), generer_url_ecrire("auteurs",""), "auteur-24.gif", "", false);
			else
				$res .= icone_horizontale (_T('icone_afficher_visiteurs'), generer_url_ecrire("auteurs","visiteurs=oui"), "auteur-24.gif", "", false);
		}
		echo bloc_des_raccourcis($res);
	}
	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'auteurs'),'data'=>''));
	creer_colonne_droite();
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'auteurs'),'data'=>''));
	debut_droite();

	echo "\n<br />";
	if ($visiteurs)
		gros_titre(_T('info_visiteurs'));
	else
		gros_titre(_T('info_auteurs'));
	echo "\n<br />";
}

// http://doc.spip.org/@auteurs_tranches
function auteurs_tranches($auteurs, $debut, $lettre, $tri, $visiteurs, $max_par_page, $nombre_auteurs)
{
	global $options, $spip_lang_right;

	$res ="\n<tr bgcolor='#DBE1C5'>"
	. "\n<td width='20'>";

	if ($tri=='statut')
  		$res .= http_img_pack('admin-12.gif','', "border='0'");
	else {
	  $t =  _T('lien_trier_statut');
	  $res .= auteurs_href(http_img_pack('admin-12.gif', $t, "border='0'"),'tri=statut', " title=\"$t\"");
	}

	$res .= "</td><td>";

	if ($tri == '' OR $tri=='nom')
		$res .= '<b>'._T('info_nom').'</b>';
	else
		$res .= auteurs_href(_T('info_nom'), "tri=nom", " title='"._T('lien_trier_nom'). "'");

	if ($options == 'avancees')
	 	$res .= "</td><td colspan='2'>"._T('info_contact');

	$res .= "</td><td>";

	if (!$visiteurs) {
		if ($tri=='nombre')
			$res .= '<b>'._T('info_articles').'</b>';
		else
			$res .= auteurs_href(_T('info_articles_2'), "tri=nombre", " title=\""._T('lien_trier_nombre_articles'). '"');
	} else $visiteurs = '&visiteurs=oui';

	$res .= "</td></tr>\n";

	if ($nombre_auteurs > $max_par_page) {
		$res .= "\n<tr bgcolor='white'><td class='arial1' colspan='".($options == 'avancees' ? 5 : 3)."'>";

		for ($j=0; $j < $nombre_auteurs; $j+=$max_par_page) {
			if ($j > 0) 	$res .= " | ";

			if ($j == $debut)
				$res .= "<b>$j</b>";
			else if ($j > 0)
				$res .= auteurs_href($j, "tri=$tri$visiteurs&debut=$j");
			else
				$res .= auteurs_href('0', "tri=$tri$visiteurs");
			if ($debut > $j  AND $debut < $j+$max_par_page){
				$res .= " | <b>$debut</b>";
			}
		}
		$res .= "</td></tr>\n";

		if ($tri == 'nom' AND $options == 'avancees') {
			$res .= "\n<tr bgcolor='white'><td class='arial11' colspan='5'>";
			foreach ($lettre as $key => $val) {
				if ($val == $debut)
					$res .= "<b>$key</b>\n";
				else
					$res .= auteurs_href($key, "tri=$tri$visiteurs&debut=$val") . "\n";
			}
			$res .= "</td></tr>\n";
		}
	}

	$nav = '';
	$debut_suivant = $debut + $max_par_page;
	if ($debut_suivant < $nombre_auteurs OR $debut > 0) {
		$nav = "\n<table id='bas' width='100%' border='0'>"
		. "\n<tr bgcolor='white'><td align='left'>";

		if ($debut > 0) {
			$debut_prec = max($debut - $max_par_page, 0);
			$nav .= auteurs_href('&lt;&lt;&lt;',"tri=$tri&debut=$debut_prec$visiteurs");
		}
		$nav .= "</td><td style='text-align: $spip_lang_right'>";
		if ($debut_suivant < $nombre_auteurs) {
			$nav .= auteurs_href('&gt;&gt;&gt;',"tri=$tri&debut=$debut_suivant$visiteurs");
		}
		$nav .= "</td></tr></table>\n";
	}

	return 	debut_cadre_relief('auteur-24.gif',true)
	. "\n<table border='0' cellpadding='2' cellspacing='0' width='100%' class='arial2' style='border: 1px solid #aaaaaa;'>\n"
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
	if ($_COOKIE['spip_accepte_ajax'] == 1 )
		$att .= ("\nonclick=" . ajax_action_declencheur("\"$h\"",$a));

	return "<a href='$h#$a'$att>$clic</a>";
}

// http://doc.spip.org/@requete_auteurs
function requete_auteurs($tri, $visiteurs)
{
  global $connect_statut, $spip_lang, $connect_id_auteur;

//
// Construire la requete
//

// si on n'est pas minirezo, ignorer les auteurs sans article publie
// sauf les admins, toujours visibles.
// limiter les statuts affiches
if ($connect_statut == '0minirezo') {
	if ($visiteurs == "oui") {
		$sql_visible = "aut.statut IN ('6forum','5poubelle')";
		$tri = 'nom';
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
	$sql_order = ' statut, login = "", unom';
	break;

case 'nom':
default:
	$sql_sel = ", ".creer_objet_multi ("nom", $spip_lang);
	$sql_order = " multi";
}



//
// La requete de base est tres sympa
//

 $row = spip_query("SELECT							aut.id_auteur AS id_auteur,							aut.statut AS statut,								aut.login AS login,								aut.nom AS nom,								aut.email AS email,								aut.source AS source,								aut.pass AS pass,								aut.url_site AS url_site,							aut.messagerie AS messagerie,							UPPER(aut.nom) AS unom,							count(lien.id_article) as compteur						$sql_sel									FROM spip_auteurs as aut							LEFT JOIN spip_auteurs_articles AS lien ON aut.id_auteur=lien.id_auteur	LEFT JOIN spip_articles AS art ON (lien.id_article = art.id_article)		WHERE	$sql_visible								GROUP BY aut.id_auteur	 ORDER BY		$sql_order");
 return $row;
}

// http://doc.spip.org/@afficher_n_auteurs
function afficher_n_auteurs($auteurs) {
	global $connect_statut, $options, $messagerie;

	$res = '';
	foreach ($auteurs as $row) {

		$res .= "\n<tr style='background-color: #eeeeee;'>";

	// statut auteur
		$res .= "\n<td style='border-top: 1px solid #cccccc;'>";
		$res .= bonhomme_statut($row);

	// nom
		$res .= "</td><td class='verdana11' style='border-top: 1px solid #cccccc;'>"
		. "<a href='"
		. generer_url_ecrire('auteurs_edit',"id_auteur=".$row['id_auteur'])
		."'>"
		. typo($row['nom'])
		. '</a>';

		if (isset($row['restreint']) AND $row['restreint'])
			$res .= " &nbsp;<small>"._T('statut_admin_restreint')."</small>";


	// contact
		if ($options == 'avancees') {
			$res .= "</td><td class='arial1' style='border-top: 1px solid #cccccc;'>";
			if ($row['messagerie'] != 'non' AND $row['login']
			    AND $messagerie != "non")
				$res .= bouton_imessage($row['id_auteur'],"force")."&nbsp;";
			if ($connect_statut=="0minirezo")
				if (strlen($row['email'])>3)
					$res .= "<a href='mailto:".$row['email']."'>"._T('lien_email')."</a>";
				else
					$res .= "&nbsp;";

			if (strlen($row['url_site'])>3)
				$res .= "</td><td class='arial1' style='border-top: 1px solid #cccccc;'><a href='".$row['url_site']."'>"._T('lien_site')."</a>";
			else
				$res .= "</td><td style='border-top: 1px solid #cccccc;'>&nbsp;";
		}

	// nombre d'articles
		$res .= "</td><td class='arial1' style='border-top: 1px solid #cccccc;'>";
		if ($row['compteur'] > 1)
			$res .= $row['compteur']."&nbsp;"._T('info_article_2');
		else if($row['compteur'] == 1)
			$res .= "1&nbsp;"._T('info_article');
		else
			$res .= "&nbsp;";

		$res .= "</td></tr>\n";
	}
	return $res;
}
?>
