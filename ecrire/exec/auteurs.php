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

//
// Lire les auteurs qui nous interessent
// et memoriser la liste des lettres initiales
//

function exec_auteurs_dist()
{
  global  $debut, $tri, $visiteurs;

if (!$tri) $tri='nom'; else $tri = preg_replace('/["\'?=&<>]/', '', $tri);
$debut = intval($debut);
$result = requete_auteurs($tri, $visiteurs);
$nombre_auteurs = spip_num_rows($result);
$max_par_page = 30;
$debut = intval($debut);
if ($debut > $nombre_auteurs - $max_par_page)
	$debut = max(0,$nombre_auteurs - $max_par_page);

$i = 0;
$auteurs=$lettre=array();
$lettres_nombre_auteurs =0;
$lettre_prec ="";

while ($auteur = spip_fetch_array($result)) {
	if ($i>=$debut AND $i<$debut+$max_par_page) {
		if ($auteur['statut'] == '0minirezo')
			$auteur['restreint'] = spip_num_rows(spip_query("SELECT id_auteur FROM spip_auteurs_rubriques WHERE id_auteur=".$auteur['id_auteur']));
			$auteurs[] = $auteur;
	}
	$i++;

	if ($tri == 'nom') {
		$premiere_lettre = strtoupper(spip_substr(extraire_multi($auteur['nom']),0,1));
		if ($premiere_lettre != $lettre_prec) {
#			echo " - $auteur[nom] -";
			$lettre[$premiere_lettre] = $lettres_nombre_auteurs;
		}
		$lettres_nombre_auteurs ++;
		$lettre_prec = $premiere_lettre;
	}
 }
 pipeline('exec_init',array('args'=>array('exec'=>'auteurs'),'data'=>''));

affiche_auteurs($auteurs, $lettre, $max_par_page, $nombre_auteurs);
}

function affiche_auteurs($auteurs, $lettre, $max_par_page, $nombre_auteurs)
{
  global $debut, $options, $spip_lang_right, $tri, $visiteurs, $connect_id_auteur,   $connect_statut,   $connect_toutes_rubriques;


if ($tri=='nom' OR $tri=='statut')
	$partri = " "._T('info_par_tri', array('tri' => $tri));
else if ($tri=='nombre')
	$partri = " "._T('info_par_nombre_article');

if ($visiteurs == "oui") {
	debut_page(_T('titre_page_auteurs'),"auteurs","redacteurs");
	$visiteurs = '&visiteurs=oui';
 } else {
	debut_page(_T('info_auteurs_par_tri', array('partri' => $partri)),"auteurs","redacteurs");
	$visiteurs = "";
 }
debut_gauche();

debut_boite_info();
if ($visiteurs)
	echo "<p class='arial1'>"._T('info_gauche_visiteurs_enregistres');
else {
	echo "<p class='arial1'>"._T('info_gauche_auteurs');

	if ($connect_statut == '0minirezo')
		echo '<br>'. _T('info_gauche_auteurs_exterieurs');
}
fin_boite_info();


if ($connect_statut == '0minirezo') {

	debut_raccourcis();
	if ($connect_toutes_rubriques) icone_horizontale(_T('icone_creer_nouvel_auteur'), generer_url_ecrire("auteur_infos"), "auteur-24.gif", "creer.gif");
	icone_horizontale(_T('icone_informations_personnelles'), generer_url_ecrire("auteurs_edit","id_auteur=$connect_id_auteur"), "fiche-perso-24.gif","rien.gif");

	$n = spip_num_rows(spip_query("SELECT id_auteur FROM spip_auteurs WHERE statut='6forum' LIMIT 1"));
	if ($n) {
		if ($visiteurs)
			icone_horizontale (_T('icone_afficher_auteurs'), generer_url_ecrire("auteurs",""), "auteur-24.gif", "");
		else
			icone_horizontale (_T('icone_afficher_visiteurs'), generer_url_ecrire("auteurs","visiteurs=oui"), "auteur-24.gif", "");
	}
	fin_raccourcis();
}
debut_droite();

echo "<br>";
if ($visiteurs)
	gros_titre(_T('info_visiteurs'));
else
	gros_titre(_T('info_auteurs'));
echo "<p>";


debut_cadre_relief('auteur-24.gif');
echo "<TABLE BORDER=0 CELLPADDING=2 CELLSPACING=0 WIDTH='100%' class='arial2' style='border: 1px solid #aaaaaa;'>\n";
echo "<tr bgcolor='#DBE1C5'>";
echo "<td width='20'>";
if ($tri=='statut')
  echo http_img_pack('admin-12.gif','', "border='0'");
 else
   echo http_href_img(generer_url_ecrire('auteurs','tri=statut'),'admin-12.gif', "border='0'", _T('lien_trier_statut'));

echo "</td><td>";
	if ($tri == '' OR $tri=='nom')
		echo '<b>'._T('info_nom').'</b>';
	else
		echo "<a href='" . generer_url_ecrire("auteurs","tri=nom") . "' title='"._T('lien_trier_nom')."'>"._T('info_nom')."</a>";

if ($options == 'avancees') echo "</td><td colspan='2'>"._T('info_contact');
echo "</td><td>";
	if (!$visiteurs) {
		if ($tri=='nombre')
			echo '<b>'._T('info_articles').'</b>';
		else
			echo "<a href='" . generer_url_ecrire("auteurs","tri=nombre") . "' title=\""._T('lien_trier_nombre_articles')."\">"._T('info_articles_2')."</a>"; //'
	}
echo "</td></tr>\n";

if ($nombre_auteurs > $max_par_page) {
	echo "<tr bgcolor='white'><td class='arial1' colspan='".($options == 'avancees' ? 5 : 3)."'>";
	//echo "<font face='Verdana,Arial,Sans,sans-serif' size='2'>";
	for ($j=0; $j < $nombre_auteurs; $j+=$max_par_page) {
		if ($j > 0) echo " | ";

		if ($j == $debut)
			echo "<b>$j</b>";
		else if ($j > 0)
		  echo "<a href='", generer_url_ecrire('auteurs',"tri=$tri$visiteurs&debut=$j"), "'>$j</a>";
		else
		  echo " <a href='",  generer_url_ecrire('auteurs',"tri=$tri$visiteurs"), "'>0</a>";

		if ($debut > $j  AND $debut < $j+$max_par_page){
			echo " | <b>$debut</b>";
		}

	}
	//echo "</font>";
	echo "</td></tr>\n";

	if ($tri == 'nom' AND $options == 'avancees') {
		// affichage des lettres
		echo "<tr bgcolor='white'><td class='arial11' colspan='5'>";
		foreach ($lettre as $key => $val) {
			if ($val == $debut)
				echo "<b>$key</b> ";
			else
			  echo "<a href='", generer_url_ecrire('auteurs',"tri=$tri$visiteurs&debut=$val"),"'>$key</a> ";
		}
		echo "</td></tr>\n";
	}
	echo "<tr height='5'></tr>";
}

afficher_n_auteurs($auteurs);

echo "</table>\n";

echo "<a name='bas'>";
echo "<table width='100%' border='0'>";

$debut_suivant = $debut + $max_par_page;
 if ($visiteurs) $visiteurs = "\n<input type='hidden' name='visiteurs' value='oui' />";
if ($debut_suivant < $nombre_auteurs OR $debut > 0) {
	echo "<tr height='10'></tr>";
	echo "<tr bgcolor='white'><td align='left'>";
	if ($debut > 0) {
		$debut_prec = max($debut - $max_par_page, 0);
		echo generer_url_post_ecrire("auteurs","tri=$tri&debut=$debut_prec"),
		  "\n<input type='submit' value='&lt;&lt;&lt;' class='fondo' />",
		  $visiteurs,
		  "\n</form>";
	}
	echo "</td><td style='text-align: $spip_lang_right'>";
	if ($debut_suivant < $nombre_auteurs) {
		echo generer_url_post_ecrire("auteurs","tri=$tri&debut=$debut_suivant"),
		  "\n<input type='submit' value='&gt;&gt;&gt;' class='fondo' />",
		  $visiteurs,
		  "\n</form>";
	}
	echo "</td></tr>\n";
}

echo "</table>\n";



fin_cadre_relief();


fin_page();
}

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

function afficher_n_auteurs($auteurs) {
	global $connect_statut, $options, $messagerie;

 foreach ($auteurs as $row) {

	echo "<tr style='background-color: #eeeeee;'>";

	// statut auteur
	echo "<td style='border-top: 1px solid #cccccc;'>";
	echo bonhomme_statut($row);

	// nom
	echo "</td><td class='verdana11' style='border-top: 1px solid #cccccc;'>";
	echo "<a href='", generer_url_ecrire('auteurs_edit',"id_auteur=".$row['id_auteur']), "'>",typo($row['nom']),'</a>';

	if (isset($row['restreint']) AND $row['restreint'])
		echo " &nbsp;<small>"._T('statut_admin_restreint')."</small>";


	// contact
	if ($options == 'avancees') {
		echo "</td><td class='arial1' style='border-top: 1px solid #cccccc;'>";
		if ($row['messagerie'] != 'non' AND $row['login']
		AND $messagerie != "non")
			echo bouton_imessage($row['id_auteur'],"force")."&nbsp;";
		if ($connect_statut=="0minirezo")
			if (strlen($row['email'])>3)
				echo "<A HREF='mailto:".$row['email']."'>"._T('lien_email')."</A>";
			else
				echo "&nbsp;";

		if (strlen($row['url_site'])>3)
			echo "</td><td class='arial1' style='border-top: 1px solid #cccccc;'><A HREF='".$row['url_site']."'>"._T('lien_site')."</A>";
		else
			echo "</td><td style='border-top: 1px solid #cccccc;'>&nbsp;";
	}

	// nombre d'articles
	echo "</td><td class='arial1' style='border-top: 1px solid #cccccc;'>";
	if ($row['compteur'] > 1)
		echo $row['compteur']."&nbsp;"._T('info_article_2');
	else if($row['compteur'] == 1)
		echo "1&nbsp;"._T('info_article');
	else
		echo "&nbsp;";

	echo "</td></tr>\n";
 }
}
?>
