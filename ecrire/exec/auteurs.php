<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/presentation');

// Constante pour le nombre d'auteurs par page.
if (!defined('MAX_AUTEURS_PAR_PAGE')) define('MAX_AUTEURS_PAR_PAGE', 30);
if (!defined('AUTEURS_MIN_REDAC')) define('AUTEURS_MIN_REDAC', "0minirezo,1comite,5poubelle");
if (!defined('AUTEURS_DEFAUT')) define('AUTEURS_DEFAUT', '');
// decommenter cette ligne et commenter la precedente 
// pour que l'affichage par defaut soit les visiteurs
#if (!defined('AUTEURS_DEFAUT')) define('AUTEURS_DEFAUT', '!');

// http://doc.spip.org/@exec_auteurs_dist
function exec_auteurs_dist()
{
	$tri = preg_replace('/\W/', '', _request('tri'));
	if (!$tri) $tri='nom'; 
	$statut =  _request('statut');
	if (!$statut)  $statut = AUTEURS_DEFAUT . AUTEURS_MIN_REDAC;
	$debut = intval(_request('debut'));

	$recherche = NULL;
	if ($cherche = _request('recherche')) {
		include_spip('inc/rechercher');
		$tables = liste_des_champs();
		$tables = array('auteur'=>$tables['auteur']);
		$recherche = recherche_en_base($cherche, $tables,array('toutvoir'=>true));
		if ($recherche['auteur'])
			$recherche = sql_in('aut.id_auteur', array_keys($recherche['auteur']));
		else {
			$recherche = "aut.id_auteur=0"; // rien trouve !
		}
	}
	$form = formulaire_recherche("auteurs",(($s=_request('statut'))?"<input type='hidden' name='statut' value='$s' />":""));
	exec_auteurs_args($statut, $tri, $debut, $recherche,$form, $cherche);
}


// http://doc.spip.org/@exec_auteurs_args
function exec_auteurs_args($statut, $tri, $debut, $recherche=NULL, $trouve='', $cherche='')
{
	if ($recherche !=='') {
		list($auteurs, $lettre, $nombre_auteurs, $debut) =
		  lettres_d_auteurs(requete_auteurs($tri, $statut, $recherche), $debut, MAX_AUTEURS_PAR_PAGE, $tri);


		$recherche = auteurs_tranches(afficher_n_auteurs($auteurs), $debut, $lettre, $tri, $statut, MAX_AUTEURS_PAR_PAGE, $nombre_auteurs,$cherche);

		if ($cherche){
			if (count($auteurs))
				$recherche = "<h3>". _T('info_resultat_recherche')." &laquo;$cherche&raquo;</h3>" . $recherche;
			else
				$recherche = "<h3>". _T('info_recherche_auteur_zero',array('cherche_auteur'=>$cherche))."</h3>" . $recherche;
		}

	}

	if (_AJAX) {
		ajax_retour($recherche); //ecrire en id='auteurs' ci-dessous
	} else {

		pipeline('exec_init',array('args'=>array('exec'=>'auteurs'),'data'=>''));

		$visiteurs = !statut_min_redac($statut);
		$commencer_page = charger_fonction('commencer_page', 'inc');
		echo $commencer_page(
			$visiteurs ? _T('info_visiteurs') :  _T('info_auteurs'),
				     "auteurs","redacteurs");

		echo bandeau_auteurs($tri, $visiteurs);
		
		echo  $trouve, "<div class='nettoyeur'></div>";

		echo "<div id='auteurs'>", $recherche, "</div>";
		echo pipeline('affiche_milieu',array('args'=>array('exec'=>'auteurs'),'data'=>''));
		echo fin_gauche(), fin_page();
	}
}

// http://doc.spip.org/@bandeau_auteurs
function bandeau_auteurs($tri, $visiteurs)
{
	global $connect_id_auteur,   $connect_statut,   $connect_toutes_rubriques;

	$ret = debut_gauche("auteurs",true) . debut_boite_info(true);

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

		if (avoir_visiteurs(true)) {
                        if ($visiteurs)
				$res .= icone_horizontale (_T('icone_afficher_auteurs'), generer_url_ecrire("auteurs", "statut=" . AUTEURS_MIN_REDAC), "auteur-24.gif", "", false);
			else
				$res .= icone_horizontale (_T('icone_afficher_visiteurs'), generer_url_ecrire("auteurs","statut=!" . AUTEURS_MIN_REDAC), "auteur-24.gif", "", false);
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
	$lettres_nombre_auteurs = 0;
	$lettre_prec ="";
	$nombre_auteurs = 0;
	$query = sql_select($query['SELECT'], $query['FROM'], $query['WHERE'], $query['GROUP BY'], $query['ORDER BY']);

	while ($auteur = sql_fetch($query)) {
		if ($nombre_auteurs>=$debut AND $nombre_auteurs<$debut+$max_par_page) {
			$auteur['restreint'] = sql_countsel("spip_auteurs_rubriques", "id_auteur=".$auteur['id_auteur']);
			
			$auteurs[] = $auteur;
		}

		if ($tri == 'nom') {
			$premiere_lettre = strtoupper(spip_substr(corriger_typo($auteur['nom']),0,1));
			if ($premiere_lettre != $lettre_prec) { 
				$lettre[$premiere_lettre] = $nombre_auteurs;
			}
			$lettre_prec = $premiere_lettre;
		}
		$nombre_auteurs++;
	}
	return array($auteurs, $lettre, $nombre_auteurs, $debut);
}

// http://doc.spip.org/@auteurs_tranches
function auteurs_tranches($auteurs, $debut, $lettre, $tri, $statut, $max_par_page, $nombre_auteurs, $cherche='')
{
	global $spip_lang_right;

	$arg = ($statut ? ("&statut=" .urlencode($statut)) : '')
	   .  ($cherche ? ("&recherche=" . urlencode($cherche)) : '');

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
			$val_prev = 0;
			foreach ($lettre as $key => $val) {
				if ($val == $debut)
					$res .= "<b>$key</b>\n";
				else {
					if ($debut>$val_prev && $debut<$val)
						$res .= "<b>..</b> ";
					$res .= auteurs_href($key, "tri=$tri$arg&debut=$val") . "\n";
				}
				$val_prev = $val;
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

	//
	// Construire la requete
	//
	
	// si on n'est pas minirezo, ignorer les auteurs sans article
	// sauf les admins, toujours visibles.

	// limiter les statuts affiches
	if ($connect_statut == '0minirezo') {
		if ($statut[0]=='!') {
			  $statut = substr($statut,1); $not = "NOT";
		} else $not = '';
		$visit = !statut_min_redac($statut);
		$statut = preg_split('/\W+/', $statut); 
		$sql_visible = sql_in("aut.statut", $statut, $not);
	} else {
		$sql_visible = "(
			aut.statut = '0minirezo'
			OR aut.id_auteur=$connect_id_auteur
			OR " . sql_in('art.statut', array('prop', 'publie'))
		. ')';
		$visit = false;
	}

	$sql_sel = '';
	$join = $visit ?
	 ""
	 : 
	 (strpos($sql_visible,'art.statut')?("LEFT JOIN spip_auteurs_articles AS lien ON aut.id_auteur=lien.id_auteur" . " LEFT JOIN spip_articles AS art ON (lien.id_article = art.id_article)"):"");
	
	// tri
	switch ($tri) {
	case 'nombre':
		$sql_sel = "COUNT(lien.id_article) AS compteur";
		$sql_order = 'compteur DESC, unom';
		$join = $visit ?
		 "LEFT JOIN spip_forum AS lien ON aut.id_auteur=lien.id_auteur"
		 : ("LEFT JOIN spip_auteurs_articles AS lien ON aut.id_auteur=lien.id_auteur" 
		. (strpos($sql_visible,'art.statut')?" LEFT JOIN spip_articles AS art ON (lien.id_article = art.id_article)":""));
		break;
	
	case 'site':
		$sql_order = 'site, unom';
		break;
	
	case 'statut':
		$sql_order = 'statut, unom';
		break;
	
	case 'nom':
	default:
		$sql_sel = sql_multi ("nom", $spip_lang);
		$sql_order = "multi";
	}
	//
	// La requete de base est tres sympa
	// (pour les visiteurs, ca postule que les messages concernent des articles)
	return array('SELECT' =>
			array_diff(
			array(
				"aut.id_auteur AS id_auteur",
				"aut.statut AS statut", 
				"aut.nom_site AS site", 
				"aut.nom AS nom", 
				"UPPER(aut.nom) AS unom", 
				$sql_sel),array('',null)),
		     'FROM' => "spip_auteurs AS aut $join",
		     'WHERE' => $sql_visible . ($recherche 
				? " AND $recherche" 
				: ''),
		     'GROUP BY' => "aut.statut, aut.nom_site, aut.nom, aut.id_auteur", 
		     'ORDER BY' => $sql_order);
}

// http://doc.spip.org/@afficher_n_auteurs
function afficher_n_auteurs($auteurs) {

	$res = '';
	$formater_auteur = charger_fonction('formater_auteur', 'inc');
	foreach ($auteurs as $row) {

		list($s, $mail, $nom, $w, $p) = $formater_auteur($row['id_auteur']);

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
