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
include_spip('inc/forum');

// http://doc.spip.org/@forum_parent
function forum_parent($id_forum) {
	$row=sql_fetsel("*", "spip_forum", "id_forum=$id_forum AND statut != 'redac'");
	if (!$row) return '';
	$id_forum=$row['id_forum'];
	$forum_id_parent=$row['id_parent'];
	$forum_id_rubrique=$row['id_rubrique'];
	$forum_id_article=$row['id_article'];
	$forum_id_breve=$row['id_breve'];
	$forum_id_syndic=$row['id_syndic'];
	$forum_stat=$row['statut'];

	if ($forum_id_article > 0) {
	  $row=sql_fetsel("id_article, titre, statut", "spip_articles", "id_article=$forum_id_article");
	  $id_article = $row['id_article'];
	  $titre = $row['titre'];
	  $statut = $row['statut'];
	  if ($forum_stat == "prive" OR $forum_stat == "privoff") {
	    return array('pref' => _T('item_reponse_article'),
			 'url' => generer_url_ecrire("articles","id_article=$id_article"),
			 'type' => 'id_article',
			 'valeur' => $id_article,
			 'titre' => $titre);
	  } else {
	    $ancre = "forum$id_forum" ;
	    return array('pref' =>  _T('lien_reponse_article'),
			 'url' => generer_url_entite($id_article,'article', '',$ancre, true),
			 'type' => 'id_article',
			 'valeur' => $id_article,
			 'titre' => $titre,
			 'avant' => "<a href='" . generer_url_ecrire("articles_forum","id_article=$id_article") . "'><span style='color: red'>"._T('lien_forum_public'). "</span></a><br />");
	  }
	}
	else if ($forum_id_rubrique > 0) {
	  $row = sql_fetsel("*", "spip_rubriques", "id_rubrique=$forum_id_rubrique");
	  $id_rubrique = $row['id_rubrique'];
	  $titre = $row['titre'];
	  return array('pref' => _T('lien_reponse_rubrique'),
		       'url' =>  generer_url_entite($id_rubrique,'rubrique','','',true),
		       'type' => 'id_rubrique',
		       'valeur' => $id_rubrique,
		       'titre' => $titre);
	}
	else if ($forum_id_syndic > 0) {
	  $row = sql_fetsel("*", "spip_syndic", "id_syndic=$forum_id_syndic");
	  $id_syndic = $row['id_syndic'];
	  $titre = $row['nom_site'];
	  $statut = $row['statut'];
	  return array('pref' => _T('lien_reponse_site_reference'),
		       'url' =>  generer_url_ecrire("sites","id_syndic=$id_syndic"),
		       'type' => 'id_syndic',
		       'valeur' => $id_syndic,
		       'titre' => $titre);
	}
	else if ($forum_id_breve > 0) {
	  $row = sql_fetsel("*", "spip_breves", "id_breve=$forum_id_breve");
	  $id_breve = $row['id_breve'];
	  $date_heure = $row['date_heure'];
	  $titre = $row['titre'];
	  if ($forum_stat == "prive") {
	    return array('pref' => _T('lien_reponse_breve'),
			 'url' => generer_url_ecrire("breves_voir","id_breve=$id_breve"),
			 'type' => 'id_breve',
			 'valeur' => $id_breve,
			 'titre' => $titre);
	  } else {
	    return array('pref' => _T('lien_reponse_breve_2'),
			 'url' =>  generer_url_entite($id_breve, 'breve','','',true),
			 'type' => 'id_breve',
			 'valeur' => $id_breve,
			 'titre' => $titre);
	  }
	}
	else if ($forum_stat == "privadm") {
	  $retour = forum_parent($forum_id_parent);
	  if ($retour) return $retour;
	  else return array('pref' => _T('info_message'),
			    'url' => generer_url_ecrire('forum_admin'),
			    'titre' => _T('info_forum_administrateur'));
	}
	else {
	  $retour = forum_parent($forum_id_parent);
	  if ($retour) return $retour;
	  else return array('pref' => _T('info_message'),
			    'url' => generer_url_ecrire('forum'),
			    'titre' => _T('info_forum_interne'));
	}
}

// http://doc.spip.org/@controle_forum_boucle
function controle_forum_boucle($row, $args) {

	$id_forum = $row['id_forum'];
	$forum_id_parent = $row['id_parent'];
	$forum_id_rubrique = $row['id_rubrique'];
	$forum_id_article = $row['id_article'];
	$forum_id_breve = $row['id_breve'];
	$forum_date_heure = $row['date_heure'];
	$forum_titre = echapper_tags($row['titre']);
	$forum_texte = $row['texte'];
	$forum_auteur = echapper_tags(extraire_multi($row['auteur']));
	$forum_email_auteur = echapper_tags($row['email_auteur']);
	$forum_nom_site = echapper_tags($row['nom_site']);
	$forum_url_site = echapper_tags($row['url_site']);
	$forum_stat = $row['statut'];
	$forum_ip = $row['ip'];
	$forum_id_auteur = $row["id_auteur"];

	$r = forum_parent($id_forum);
	$avant = $r['avant'];
	$url = $r['url'];
	$titre = $r['titre'];
	$type = $r['type'];
	$valeur = $r['valeur'];
	$pref = $r['pref'];

	if ($documents = sql_allfetsel('doc.id_document, doc.fichier AS fichier', 'spip_documents AS doc LEFT JOIN spip_documents_liens AS lien ON doc.id_document=lien.id_document', 'lien.id_objet='.intval($id_forum)." AND objet='forum'")) {
		include_spip('inc/documents');
		foreach ($documents as $k => $t) {
			$h = generer_url_entite($t['id_document'], 'document');
			$documents[$k] = "<a href='".$h."'>".basename($t['fichier'])."</a>";
		}
	}

	switch($forum_stat) {
		case 'off':
		case 'privoff':
			$style = " style='border: 2px #ff0000 dashed;'";
			break;
		case 'prop':
			$style = " style='border: 2px yellow solid; background-color: white;'";
			break;
		case 'spam':
			$style = " style='border: 2px black dotted;'";
			break;
		default:
			$style = "";
			break;
	}

	if ($forum_email_auteur) {
		if (email_valide($forum_email_auteur))
			$forum_email_auteur = "<a href='mailto:"
			.htmlspecialchars($forum_email_auteur)
			."?subject=".rawurlencode($forum_titre)."'>".$forum_email_auteur
			."</a>";
		$forum_auteur .= " &mdash; $forum_email_auteur";
	}

	$suite = "\n<br />$avant<b>$pref\n<a href='$url' class='controle'>$titre</a></b>"  
	. "<div class='controle'>".justifier(propre($forum_texte))."</div>";
	include_spip('public/composer');
	if ($forum_notes = safehtml(calculer_notes()))
		$suite .= "<div class='notes controle'>".justifier(safehtml($forum_notes))."</div>";

	if (strlen($forum_url_site) > 10 AND strlen($forum_nom_site) > 3)
		$suite .= "\n<div style='text-align: left' class='serif'><b><a href='$forum_url_site'>$forum_nom_site</a></b></div>";

	return 	"\n<div><br /><a id='forum$id_forum'></a></div>" .
	  debut_cadre_forum("", true, "", typo($forum_titre)) .
	  "<div$style>" .
	  date_interface($forum_date_heure) .
	  safehtml("<span class='arial2'> / <b>$forum_auteur</b></span>") .
	  boutons_controle_forum($id_forum, $forum_stat, $forum_id_auteur, "$type=$valeur", $forum_ip, 'controle_forum', $args) .
	  safehtml(lignes_longues($suite)) .
	  afficher_forum_mots($id_forum) .
	  join(', ', $documents) .
	  "<div class='nettoyeur'></div></div>".
	  fin_cadre_forum(true);
}

//
// Debut de la page de controle
//

// http://doc.spip.org/@exec_controle_forum_dist
function exec_controle_forum_dist()
{
  exec_controle_forum_args(intval(_request('id_rubrique')),
			   _request('type'),
			   intval(_request('debut')),
			   intval(_request('pas')),
			   intval(_request('enplus')),
			   _request('recherche'));
}

// http://doc.spip.org/@exec_controle_forum_args
function exec_controle_forum_args($id_rubrique, $type, $debut, $pas, $enplus, $recherche)
{
	if (!autoriser('publierdans','rubrique',$id_rubrique)) {
		include_spip('inc/minipres');
		echo minipres();
	} else {

	if (!preg_match('/^\w+$/', $type)) $type = 'public';
	$formulaire_recherche = formulaire_recherche("controle_forum","<input type='hidden' name='type' value='$type' />");

	list($from,$where) = critere_statut_controle_forum($type, $id_rubrique, $recherche);

	// Si un id_controle_forum est demande, on adapte le debut
	if ($debut_id_forum = intval(_request('debut_id_forum'))
	AND (NULL !== ($d = sql_getfetsel('date_heure', 'spip_forum', "id_forum=$debut_id_forum")))) {
	  $debut = sql_countsel($from, $where . (" AND F.date_heure > '$d'"));
	}

	if ($recherche)
	  $args = 'recherche='.rawurlencode($recherche).'&';
	else $args = '';

	$args .= (!$id_rubrique ? "" : "id_rubrique=$id_rubrique&") . 'type=';

	$query = array('SELECT' => "F.id_forum, F.id_parent, F.id_rubrique, F.id_article, F.id_breve, F.date_heure, F.titre, F.texte, F.auteur, F.email_auteur, F.nom_site, F.url_site, F.statut, F.ip, F.id_auteur",
		       'FROM' => $from,
		       'WHERE' => $where,
		       'GROUP BY' => "",
		       'ORDER BY' => "F.date_heure DESC");
  
	$nav = affiche_navigation_forum($query, 'controle_forum', $args . $type, $debut, $pas, $enplus);

	$select = sql_select($query['SELECT'], $query['FROM'], $query['WHERE'], $query['GROUP BY'], $query['ORDER BY'], $query['LIMIT']);
	
	$res = '';
	while ($row = sql_fetch($select)) 
		$res .= controle_forum_boucle($row, "$args$type&debut=$debut");
	$res =  "<br />$nav<br />$res<br />$nav";	

	if (_AJAX) {
		ajax_retour($res);
	} else {
		$ancre = 'controle_forum';
		$res = "<div id='$ancre' class='serif2'>$res</div>";

		pipeline('exec_init',array('args'=>array('exec'=>'controle_forum', 'type'=>$type),'data'=>''));


		$commencer_page = charger_fonction('commencer_page', 'inc');
		echo $commencer_page(_T('titre_page_forum_suivi'), "forum", "forum-controle");

		echo "<br /><br /><br />";
		echo gros_titre(_T('titre_forum_suivi'),'',false);

		echo debut_onglet();
		echo onglet(_T('onglet_messages_publics'), generer_url_ecrire('controle_forum', $args . "public"), "public", $type=='public', "forum-public-24.gif");
		echo onglet(_T('onglet_messages_internes'), generer_url_ecrire('controle_forum', $args . "interne"), "interne", $type=='interne', "forum-interne-24.gif");

		list($from,$where) = critere_statut_controle_forum('vide', $id_rubrique);
		$n = sql_fetsel('1', $from, $where);
		if ($n) echo onglet(_T('onglet_messages_vide'), generer_url_ecrire('controle_forum', $args . "vide"), "vide", $type=='vide');

		list($from,$where) = critere_statut_controle_forum('prop', $id_rubrique);
		$f = sql_fetsel('1', $from, $where);
		if ($f)
			echo onglet(_T('texte_statut_attente_validation'), generer_url_ecrire('controle_forum', $args . "prop"), "prop", $type=='prop');

		echo fin_onglet();

		echo debut_gauche('', true);
		echo debut_boite_info(true);
		echo "<span class='verdana1 spip_small'>", _T('info_gauche_suivi_forum_2'), aide("suiviforum"), "</span>";

		// Afficher le lien RSS

		echo bouton_spip_rss("forums_$type");

		echo fin_boite_info(true);
			
		echo pipeline('affiche_gauche',array('args'=>array('exec'=>'controle_forum', 'type'=>$type),'data'=>''));
		echo creer_colonne_droite('', true);
		echo pipeline('affiche_droite',array('args'=>array('exec'=>'controle_forum', 'type'=>$type),'data'=>''));
			
			
		echo debut_droite('', true);
		echo pipeline('affiche_milieu',array('args'=>array('exec'=>'controle_forum', 'type'=>$type),'data'=>''));

		echo $formulaire_recherche . "<div class='nettoyeur'></div>";
		echo $res; 
		echo fin_gauche(), fin_page();
	}
	}
}
?>
