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

// Constante pour le nombre d'auteurs par page.
@define('MAX_AUTEURS_PAR_PAGE', 30);

// http://doc.spip.org/@exec_auteurs_dist
function exec_auteurs_dist()
{
	global $spip_lang_right;
	$tri = preg_replace('/\W/', '', _request('tri'));
	if (!$tri) $tri='nom'; 
	$statut =  _request('statut');
	$recherche = _request('recherche');

	$result = requete_auteurs($tri, $statut, $recherche);
	$nombre_auteurs = spip_num_rows($result);

	$debut = intval(_request('debut'));
	if ($debut > $nombre_auteurs - MAX_AUTEURS_PAR_PAGE)
		$debut = max(0,$nombre_auteurs - MAX_AUTEURS_PAR_PAGE);

	list($auteurs, $lettre)= lettres_d_auteurs($result, $debut, MAX_AUTEURS_PAR_PAGE, $tri);
	$res = auteurs_tranches(afficher_n_auteurs($auteurs), $debut, $lettre, $tri, $statut, MAX_AUTEURS_PAR_PAGE, $nombre_auteurs);

	if (_request('var_ajaxcharset'))
	  ajax_retour($res);
	else {

		pipeline('exec_init',array('args'=>array('exec'=>'auteurs'),'data'=>''));
		// Chaine indiquant le mode de tri est obsolète depuis Ajax
		$commencer_page = charger_fonction('commencer_page', 'inc');
		echo $commencer_page(_T('info_auteurs_par_tri',
					array('partri' => '')) .
				     (!$statut ? '' : " ($statut)"),
				     "auteurs","redacteurs");

		echo bandeau_auteurs($tri, !statut_min_redac($statut));

		$recherche_aff = entites_html($recherche);
		if (!strlen($recherche)) {
			$recherche_aff = _T('info_rechercher');
			$onfocus = " onfocus=\"this.value='';\"";
		} else $onfocus = '';
		$onfocus = '<input type="text" size="10" value="'.$recherche_aff.'" name="recherche" class="spip_recherche" accesskey="r"' . $onfocus . ' />';
		echo "<div style='width:200px;float:$spip_lang_right;'>".generer_form_ecrire("auteurs", $onfocus, " method='get'")."</div>"
		 . "<br class='nettoyeur' />";

		echo "<div id='auteurs'>", $res, "</div>";
		echo pipeline('affiche_milieu',array('args'=>array('exec'=>'auteurs'),'data'=>''));
		echo fin_gauche(), fin_page();
	}
}

// http://doc.spip.org/@bandeau_auteurs
function bandeau_auteurs($tri, $visiteurs)
{
	global $connect_id_auteur,   $connect_statut,   $connect_toutes_rubriques;
	$ret = "";

	$ret .= debut_gauche("auteurs",true);
	$ret .= debut_boite_info(true);
	if ($visiteurs) 
		$ret .= "\n<p class='arial1'>"._T('info_gauche_visiteurs_enregistres'). '</p>';
	else 
		$ret .= "\n<p class='arial1'>"._T('info_gauche_auteurs'). '</p>';

	if ($connect_statut == '0minirezo')
		$ret .= "\n<p class='arial1'>". _T('info_gauche_auteurs_exterieurs') . '</p>';

	$ret .= fin_boite_info(true);

	$ret .= pipeline('affiche_gauche',array('args'=>array('exec'=>'auteurs'),'data'=>''));

	if ($connect_statut == '0minirezo') {

		if ($connect_toutes_rubriques) 
			$res = icone_horizontale(_T('icone_creer_nouvel_auteur'), generer_url_ecrire("auteur_infos", 'new=oui'), "auteur-24.gif", "creer.gif", false);
		else $res = '';

		$res .= icone_horizontale(_T('icone_informations_personnelles'), generer_url_ecrire("auteur_infos","id_auteur=$connect_id_auteur"), "fiche-perso-24.gif","rien.gif", false);

		if (avoir_visiteurs()) {
                        if ($visiteurs)
				$res .= icone_horizontale (_T('icone_afficher_auteurs'), generer_url_ecrire("auteurs"), "auteur-24.gif", "", false);
			else
				$res .= icone_horizontale (_T('icone_afficher_visiteurs'), generer_url_ecrire("auteurs","statut=!1comite,0minirezo,nouveau"), "auteur-24.gif", "", false);
		}
		$ret .= bloc_des_raccourcis($res);
	}
	$ret .= creer_colonne_droite('auteurs',true);
	$ret .= pipeline('affiche_droite',array('args'=>array('exec'=>'auteurs'),'data'=>''));
	$ret .= debut_droite('',true);

	$ret .= "\n<br />";
	$ret .= gros_titre($visiteurs ? _T('info_visiteurs') :  _T('info_auteurs'),'',false);
	$ret .= "\n<br />";
	return $ret;
}

// http://doc.spip.org/@statut_min_redac
function statut_min_redac($statut)
{
  $x = (!$statut)
    || (strpos($statut, "0minirezo")!==false)
    || (strpos($statut, "1comite")!==false);

  return $statut[0] =='!' ? !$x : $x;
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
			$auteur['restreint'] = spip_abstract_countsel("spip_auteurs_rubriques", "id_auteur=".$auteur['id_auteur'],'', "1");
			
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

// http://doc.spip.org/@auteurs_tranches
function auteurs_tranches($auteurs, $debut, $lettre, $tri, $statut, $max_par_page, $nombre_auteurs)
{
	global $spip_lang_right;

	$arg = $statut ? ("&statut=" .urlencode($statut)) : '';
	$res ="\n<tr class='titrem'>"
	. "\n<th style='width: 20px'>";

	if ($tri=='statut')
  		$res .= http_img_pack('admin-12.gif','', " class='lang'");
	else {
	  $t =  _T('lien_trier_statut');
	  $res .= auteurs_href(http_img_pack('admin-12.gif', $t, "class='lang'"),"tri=statut$arg", " title=\"$t\"");
	}

	$res .= "</th><th style='width: 20px'></th><th>";

	if ($tri=='nom')
		$res .= '<b>'._T('info_nom').'</b>';
	else
		$res .= auteurs_href(_T('info_nom'), "tri=nom$arg", " title='"._T('lien_trier_nom'). "'");

	$res .= "</th><th>";

	if ($tri=='site')
		$res .= '<b>'._T('info_site').'</b>';
	else
		$res .= auteurs_href(_T('info_site'), "tri=site$arg", " title='"._T('info_site'). "'");

	$res .= '</th><th>';

	$col = statut_min_redac($statut) ? _T('info_articles') : _T('message') ;

	if ($tri=='nombre')
		$res .= '<b>' . $col .'</b>';
	else
		$res .= auteurs_href($col, "tri=nombre$arg", " title=\""._T('lien_trier_nombre_articles'). '"');

	$res .= "</th></tr>\n";

	if ($nombre_auteurs > $max_par_page) {
		$res .= "\n<tr class='' ><td colspan='5'><div class='arial1 tranches'>";

		for ($j=0; $j < $nombre_auteurs; $j+=$max_par_page) {
			if ($j > 0) 	$res .= " | ";

			if ($j == $debut)
				$res .= "<b>$j</b>";
			else if ($j > 0)
				$res .= auteurs_href($j, "tri=$tri$arg&debut=$j");
			else
				$res .= auteurs_href('0', "tri=$tri$arg");
			if ($debut > $j  AND $debut < $j+$max_par_page){
				$res .= " | <b>$debut</b>";
			}
		}

		if ($tri == 'nom') {
			$res .= "</div><div>\n";
			foreach ($lettre as $key => $val) {
				if ($val == $debut)
					$res .= "<b>$key</b>\n";
				else
					$res .= auteurs_href($key, "tri=$tri$arg&debut=$val") . "\n";
			}
			$res .= "</div></td></tr>\n";
		}
	}

	$nav = '';
	$debut_suivant = $debut + $max_par_page;
	if ($debut_suivant < $nombre_auteurs OR $debut > 0) {
		$nav = "\n<table id='bas' style='width: 100%' border='0'>"
		. "\n<tr class=''><td align='left'>";

		if ($debut > 0) {
			$debut_prec = max($debut - $max_par_page, 0);
			$nav .= auteurs_href('&lt;&lt;&lt;',"tri=$tri&debut=$debut_prec$arg");
		}
		$nav .= "</td><td style='text-align: $spip_lang_right'>";
		if ($debut_suivant < $nombre_auteurs) {
			$nav .= auteurs_href('&gt;&gt;&gt;',"tri=$tri&debut=$debut_suivant&$arg");
		}
		$nav .= "</td></tr></table>\n";
	}

	return 	debut_cadre('liste','auteur-24.gif','','','lesauteurs')
	. "\n<br /><table  class='arial2' cellpadding='2' cellspacing='0' style='width: 100%; border: 0px;'>\n"
	. $res
	. $auteurs
	. "</table>\n<br />"
	.  $nav
	. fin_cadre();
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
function requete_auteurs($tri, $statut, $recherche=NULL)
{
  global $connect_statut, $spip_lang, $connect_id_auteur;

  $in_auteurs = "";
  if ($recherche){
		include_spip('inc/rechercher');
		include_spip('base/abstract_sql');
		$tables = liste_des_champs();
		$tables = array('auteur'=>$tables['auteur']);
		$results = recherche_en_base($recherche, $tables);
		$in_auteurs = calcul_mysql_in('aut.id_auteur',implode(',',array_keys($results['auteur'])));
  }
  
	//
	// Construire la requete
	//
	
	// si on n'est pas minirezo, ignorer les auteurs sans article
	// sauf les admins, toujours visibles.

	// limiter les statuts affiches
	if ($connect_statut == '0minirezo') {
		if (!$statut) {
			$sql_visible = "aut.statut IN ('0minirezo','1comite','5poubelle')";
			$visit = false;
		} else {
			if ($statut[0]=='!') {
			  $statut = substr($statut,1); $not = " NOT";
			} else $not = '';
			$statut = preg_replace('/\W+/',"','",$statut); 
			$sql_visible = "aut.statut$not IN ('$statut')";
			$visit = statut_min_redac($statut);
		}
	} else {
		$sql_visible = "(
			aut.statut = '0minirezo'
			OR art.statut IN ('prop', 'publie')
			OR aut.id_auteur=$connect_id_auteur
		)";
		$visit = false;
	}
	if ($in_auteurs)
		$sql_visible = "(($sql_visible) AND $in_auteurs)";
	
	$sql_sel = '';
	
	// tri
	switch ($tri) {
	case 'nombre':
		$sql_order = ' compteur DESC, unom';
		break;
	
	case 'site':
		$sql_order = ' site, unom';
		break;
	
	case 'statut':
		$sql_order = ' statut, unom';
		break;
	
	case 'nom':
	default:
		$sql_sel = ", ".spip_abstract_multi ("nom", $spip_lang);
		$sql_order = " multi";
	}
	
	//
	// La requete de base est tres sympa
	// (pour les visiteurs, ca postule que les messages concernent des articles)
	
	 $row = spip_query("SELECT							aut.id_auteur AS id_auteur,							aut.statut AS statut,								aut.nom_site AS site, aut.nom AS nom,								UPPER(aut.nom) AS unom,							count(lien.id_article) as compteur							$sql_sel									FROM spip_auteurs as aut " . ($visit ?		 			"LEFT JOIN spip_forum AS lien ON aut.id_auteur=lien.id_auteur " :		("LEFT JOIN spip_auteurs_articles AS lien ON aut.id_auteur=lien.id_auteur	 LEFT JOIN spip_articles AS art ON (lien.id_article = art.id_article)")) .	" WHERE $sql_visible GROUP BY aut.statut, aut.nom_site, aut.nom, aut.id_auteur ORDER BY $sql_order");
	 return $row;
}

// http://doc.spip.org/@afficher_n_auteurs
function afficher_n_auteurs($auteurs) {

	$res = '';
	$formater_auteur = charger_fonction('formater_auteur', 'inc');
	foreach ($auteurs as $row) {

		list($s, $mail, $nom, $w, $p) = $formater_auteur($row['id_auteur']);
		if ($w) {
		  if (preg_match(',^([^>]*>)[^<]*(.*)$,', $w,$r)) {
		    $w = $r[1] . substr($row['site'],0,20) . $r[2];
		  }
		}
		$res .= "\n<tr class='tr_liste'>"
		. "\n<td>"
		. $s
		. "</td><td class='arial1'>"
		. $mail
		. "</td><td class='verdana1'>"
		. $nom
		. ((isset($row['restreint']) AND $row['restreint'])
		   ? (" &nbsp;<small>"._T('statut_admin_restreint')."</small>")
		   : '')
		 ."</td><td class='arial1'>"
		 . $w
		 . "</td><td class='arial1'>"
		 . $p
		.  "</td></tr>\n";
	}
	return $res;
}
?>
