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
include_spip('inc/actions');

// http://doc.spip.org/@affiche_navigation_forum
function affiche_navigation_forum(&$query, $script, $args, $debut, $pas=NULL, $enplus=NULL, $date=NULL)
{
	if (!$pas) $pas = 10;
	if (!$enplus) $enplus = 100;

	$total = sql_countsel($query['FROM'], $query['WHERE'], $query['GROUP BY']);
	// pas de navigation si tout tient
	if ($total > $pas) {
		if ($date) {
			preg_match('/^\w+/', $query['ORDER BY'], $m);
			$debut = navigation_trouve_date($date, $m[0], $pas, $query);
		}
		if ($total <= $debut) $debut = $total-$pas;
		$max = min($total, $debut+$enplus);
		$tranche = $debut;
		while (($tranche + $enplus) >= $max) 
		  $tranche -= $pas;
		if ($tranche <0) $tranche = 0;

		$h = generer_url_ecrire($script, $args);
		$nav = (!$tranche ? '' : "<a href='$h'>0</a>| ... |\n");

		$e = (_SPIP_AJAX === 1 );

		for (;$tranche<$max;$tranche+=$pas){
			if ($tranche == $debut)
				$nav .= "<span class='spip_medium'><b>$tranche</b></span> |\n";
			else {
				$h = "$args&debut=$tranche";
				$h = generer_url_ecrire($script, $h);
				if ($e)	$e = "\nonclick=" . ajax_action_declencheur($h,$script);
				$nav .= "<a href='$h'$e>$tranche</a> |\n";
			}
		}

		if ($tranche  < $total) {
		  $h = generer_url_ecrire($script, $args . "&debut=" . $total);
		  if ($e) {
		    $e = "\nonclick=" . ajax_action_declencheur($h,$script);
		  }
		  $nav .= "... | <a href='$h'$e>$total</a>";
		}
	}

	$query['LIMIT'] = "$debut, $pas";
	return $nav;
}

// http://doc.spip.org/@navigation_trouve_date
function navigation_trouve_date($date, $nom_date, $pas, $query)
{
	$debut = 0;
	if (!is_numeric($date)) {
		include_spip('inc/filtres');
		list($a,$m,$j,$h,$n,$s) = recup_date($date);
		$date = mktime($h,$n,$s,$m ? $m : 1,$j ? $j : 1,$a);
	}
	$q = sql_select($query['SELECT'], $query['FROM'], $query['WHERE'], $query['GROUP BY'], $query['ORDER BY']);
	while ($r = sql_fetch($q)) {
		if ($r[$nom_date] <= $date) break;
		$debut++;
	}
	$debut -= ($debut%$pas);
	return $debut;
}

// tous les boutons de controle d'un forum
// nb : les forums prives (privrac ou prive), une fois effaces
// (privoff), ne sont pas revalidables ; le forum d'admin (privadm)
// n'est pas effacable

// http://doc.spip.org/@boutons_controle_forum
function boutons_controle_forum($id_forum, $forum_stat, $forum_id_auteur=0, $ref, $forum_ip, $script, $args) {
	$controle = $original = $spam = '';


	// selection du logo et des boutons correspondant a l'etat du forum
	switch ($forum_stat) {
		# forum sous un article dans l'espace prive
		case "prive":
			$logo = "forum-interne-24.gif";
			$valider = false;
			$valider_repondre = false;
			$suppression = 'privoff';
			break;
		# forum des administrateurs
		case "privadmin":
			$logo = "forum-admin-24.gif";
			$valider = false;
			$valider_repondre = false;
			$suppression = false;
			break;
		# forum de l'espace prive, supprime (non revalidable,
		# d'ailleurs on ne sait plus a quel type de forum il appartenait)
		case "privoff":
			$logo = "forum-interne-24.gif";
			$valider = false;
			$valider_repondre = false;
			$suppression = false;
			break;
		# forum general de l'espace prive
		case "privrac":
			$logo = "forum-interne-24.gif";
			$valider = false;
			$valider_repondre = false;
			$suppression = 'privoff';
			break;

		# forum publie sur le site public
		case "publie":
			$logo = "forum-public-24.gif";
			$valider = false;
			$valider_repondre = false;
			$suppression = 'off';
			break;
		# forum supprime sur le site public
		case "off":
			$logo = "forum-public-24.gif";
			$valider = 'publie';
			$valider_repondre = false;
			$suppression = false;
			$controle = "<br /><span style='color: red; font-weight: bold;'>"._T('info_message_supprime')." $forum_ip</span>";
			if($forum_id_auteur)
				$controle .= " - <a href='" . generer_url_ecrire('auteur_infos', "id_auteur=$forum_id_auteur") .
				  "'>" ._T('lien_voir_auteur'). "</a>";
			break;
		# forum propose (a moderer) sur le site public
		case "prop":
			$logo = "forum-public-24.gif";
			$valider = 'publie';
			$valider_repondre = true;
			$suppression = 'off';
			break;
		# forum signale comme spam sur le site public
		case "spam":
			$logo = "forum-public-24.gif";
			$valider = 'publie';
			$valider_repondre = false;
			$suppression = false;
			$spam = true;
			break;
		# forum original (reponse a un forum modifie) sur le site public
		case "original":
			$logo = "forum-public-24.gif";
			$original = true;
			break;
		default:
			return;
	}

	$lien =  generer_url_ecrire($script, $args, true, true) . "#forum$id_forum";
	$boutons ='';
	if ($suppression)
	  $boutons .= icone_inline(_T('icone_supprimer_message'), generer_action_auteur('instituer_forum',"$id_forum-$suppression", $lien),
			$logo,
			"supprimer.gif", 'right', 'non');

	if ($valider)
	  $boutons .= icone_inline(_T('icone_valider_message'), generer_action_auteur('instituer_forum',"$id_forum-$valider", $lien),
			$logo,
			"creer.gif", 'right', 'non');

	if ($valider_repondre) {
	  $dblret = rawurlencode(_DIR_RESTREINT_ABS . $lien);
	  $boutons .= icone_inline(_T('icone_valider_message') . " &amp; " .   _T('lien_repondre_message'), generer_action_auteur('instituer_forum',"$id_forum-$valider", generer_url_public('forum', "$ref&id_forum=$id_forum&retour=$dblret", true, true)),
			     $logo,
			     "creer.gif", 'right', 'non');
	}

	if ($boutons) $controle .= "<div style='float:".$GLOBALS['spip_lang_right'] ."; width: 80px; padding-bottom:20px;'>". $boutons . "</div>";

	// TODO: un bouton retablir l'original ?
	if ($original) {
		$controle .= "<div style='float:".$GLOBALS['spip_lang_right'].";color:green'>"
		."("
		._T('forum_info_original')
		.")</div>";
	}

	if ($spam) {
		$controle .= "<div style='float:".$GLOBALS['spip_lang_right'].";color:red'>"
		."("
		._T('spam') // Marque' comme spam ?
		.")</div>";
	}


	return $controle;
}

// recuperer le critere SQL qui selectionne nos forums
// http://doc.spip.org/@critere_statut_controle_forum
function critere_statut_controle_forum($type, $id_rubrique=0, $recherche='') {

	if (is_array($id_rubrique))   $id_rubrique = join(',',$id_rubrique);
	if (!$id_rubrique) {
		$from = 'spip_forum AS F';
		$where = "";
		$and = "";
	} else {
		if (strpos($id_rubrique,','))
		  $eq = " IN ($id_rubrique)";
		else $eq = "=$id_rubrique";
	      
		$from = 'spip_forum AS F, spip_articles AS A';
		$where = "A.id_secteur$eq AND F.id_article=A.id_article";
		$and = ' AND ';
	}
   
	switch ($type) {
	case 'public':
		$and .= "F.statut IN ('publie', 'off', 'prop', 'spam') AND F.texte!=''";
		break;
	case 'prop':
		$and .= "F.statut='prop'";
		break;
	case 'spam':
		$and .= "F.statut='spam'";
		break;
	case 'interne':
		$and .= "F.statut IN ('prive', 'privrac', 'privoff', 'privadm') AND F.texte!=''";
		break;
	case 'vide':
		$and .= "F.statut IN ('publie', 'off', 'prive', 'privrac', 'privoff', 'privadm') AND F.texte=''";
		break;
	default:
		$where = '0=1';
		$and ='';
		break;
	}

	if ($recherche) {
		# recherche par IP
		if (preg_match(',^\d+\.\d+\.(\*|\d+\.(\*|\d+))$,', $recherche)) {
			$and .= " AND ip LIKE ".sql_quote(str_replace('*', '%', $recherche));
		} else {
			include_spip('inc/rechercher');
			if ($a = recherche_en_base($recherche, 'forum'))
				$and .= " AND ".sql_in('id_forum',
					array_keys(array_pop($a)));
			else
				$and .= " AND 0=1";
		}
	}

	return array($from, "$where$and");
}

// Index d'invalidation des forums
// http://doc.spip.org/@calcul_index_forum
function calcul_index_forum($id_article, $id_breve, $id_rubrique, $id_syndic) {
	if ($id_article) return 'a'.$id_article; 
	if ($id_breve) return 'b'.$id_breve;
	if ($id_rubrique) return 'r'.$id_rubrique;
	if ($id_syndic) return 's'.$id_syndic;
}

//
// Recalculer tous les threads
//
// http://doc.spip.org/@calculer_threads
function calculer_threads() {
	// fixer les id_thread des debuts de discussion
	sql_update('spip_forum', array('id_thread'=>'id_forum'), "id_parent=0");
	// reparer les messages qui n'ont pas l'id_secteur de leur parent
	do {
		$discussion = "0";
		$precedent = 0;
		$r = sql_select("fille.id_forum AS id,	maman.id_thread AS thread", 'spip_forum AS fille, spip_forum AS maman', "fille.id_parent = maman.id_forum AND fille.id_thread <> maman.id_thread",'', "thread");
		while ($row = sql_fetch($r)) {
			if ($row['thread'] == $precedent)
				$discussion .= "," . $row['id'];
			else {
				if ($precedent)
					sql_updateq("spip_forum", array("id_thread" => $precedent), "id_forum IN ($discussion)");
				$precedent = $row['thread'];
				$discussion = $row['id'];
			}
		}
		sql_updateq("spip_forum", array("id_thread" => $precedent), "id_forum IN ($discussion)");
	} while ($discussion != "0");
}

// Calculs des URLs des forums (pour l'espace public)
// http://doc.spip.org/@racine_forum
function racine_forum($id_forum){
	if (!$id_forum = intval($id_forum)) return false;

	$row = sql_fetsel("id_parent, id_rubrique, id_article, id_breve, id_syndic, id_message, id_thread", "spip_forum", "id_forum=".$id_forum);

	if (!$row) return false;

	if ($row['id_parent']
	AND $row['id_thread'] != $id_forum) // eviter boucle infinie
		return racine_forum($row['id_thread']);

	if ($row['id_message'])
		return array('message', $row['id_message'], $id_forum);
	if ($row['id_rubrique'])
		return array('rubrique', $row['id_rubrique'], $id_forum);
	if ($row['id_article'])
		return array('article', $row['id_article'], $id_forum);
	if ($row['id_breve'])
		return array('breve', $row['id_breve'], $id_forum);
	if ($row['id_syndic'])
		return array('site', $row['id_syndic'], $id_forum);

	// On ne devrait jamais arriver ici, mais prevoir des cas de forums
	// poses sur autre chose que les objets prevus...
	spip_log("erreur racine_forum $id_forum");
	return array();
} 


// http://doc.spip.org/@parent_forum
function parent_forum($id_forum) {
	if (!$id_forum = intval($id_forum)) return;
	$row = sql_fetsel("id_parent, id_rubrique, id_article, id_breve, id_syndic", "spip_forum", "id_forum=".$id_forum);
	if(!$row) return array();
	if($row['id_parent']) return array('forum', $row['id_parent']);
	if($row['id_article']) return array('article', $row['id_article']);
	if($row['id_breve']) return array('breve', $row['id_breve']);
	if($row['id_rubrique']) return array('rubrique', $row['id_rubrique']);
	if($row['id_syndic']) return array('site', $row['id_syndic']);
} 


// obsolete, maintenu poru compat
// http://doc.spip.org/@generer_url_forum_dist
function generer_url_forum_dist($id_forum, $args='', $ancre='') {
	$generer_url_externe = charger_fonction("generer_url_forum",'urls');
	return $generer_url_externe($id_forum, $args, $ancre);
}


// http://doc.spip.org/@generer_url_forum_parent
function generer_url_forum_parent($id_forum) {
	if ($id_forum = intval($id_forum)) {
		list($type, $id) = parent_forum($id_forum);
		if ($type)
			return generer_url_entite($id, $type);
	}
	return '';
} 


// Quand on edite un forum, on tient a conserver l'original
// sous forme d'un forum en reponse, de statut 'original'
// http://doc.spip.org/@conserver_original
function conserver_original($id_forum) {
	$s = sql_fetsel("id_forum", "spip_forum", "id_parent=".sql_quote($id_forum)." AND statut='original'");

	if ($s)	return ''; // pas d'erreur

	// recopier le forum
	$t = sql_fetsel("*", "spip_forum", "id_forum=".sql_quote($id_forum));

	if ($t) {
		unset($t['id_forum']);
		$id_copie = sql_insertq('spip_forum', $t);
		if ($id_copie) {
			sql_updateq('spip_forum', array('id_parent'=> $id_forum, 'statut'=>'original'), "id_forum=$id_copie");
			return ''; // pas d'erreur
		}
	}

	return '&erreur';
}

// appelle conserver_original(), puis modifie le contenu via l'API inc/modifier
// http://doc.spip.org/@enregistre_et_modifie_forum
function enregistre_et_modifie_forum($id_forum, $c=false) {
	if ($err = conserver_original($id_forum)) {
		spip_log("erreur de sauvegarde de l'original, $err");
		return;
	}

	include_spip('inc/modifier');
	return revision_forum($id_forum, $c);
}

//
// Afficher les forums
//

// http://doc.spip.org/@afficher_forum
function afficher_forum($query, $retour, $arg, $controle_id_article = false, $script='', $argscript='') {
	global $spip_display;
	static $compteur_forum = 0;
	static $nb_forum = array();
	static $thread = array();

	$request = sql_allfetsel($query['SELECT'], $query['FROM'], $query['WHERE'], $query['GROUP BY'], $query['ORDER BY'], $query['LIMIT']);
	$compteur_forum++;
	$nb_forum[$compteur_forum] = count($request);
	$thread[$compteur_forum] = 1;
	
	$res = '';

	foreach($request as  $row) {
		$statut=$row['statut'];
		$id_parent=$row['id_parent'];
		if (($controle_id_article) ? ($statut!="perso") :
			(($statut=="prive" OR $statut=="privrac" OR $statut=="privadm" OR $statut=="perso")
			 OR ($statut=="publie" AND $id_parent > 0))) {

			$query = array('SELECT' => "*", 
				'FROM' => "spip_forum",
				'WHERE' => "id_parent='" . $row['id_forum'] . "'" . ($controle_id_article ? '':" AND statut<>'off'"),
				'ORDER BY' => "date_heure");

			$bloc = afficher_forum_thread($row, $controle_id_article, $compteur_forum, $nb_forum, $thread, $retour, $arg, $script, $argscript)
			  . afficher_forum($query, $retour, $arg, $controle_id_article, $script, $argscript);

			$res .= ajax_action_greffe('poster_forum_prive', $row['id_forum'], $bloc); 
		}
		$thread[$compteur_forum]++;
	}
	$compteur_forum--;
	if ($spip_display == 4 AND $res) $res = "<ul>$res</ul>";	
	return $res;
}

// Construit une Div comportant un unique message, 
// plus les lignes verticales de conduite

// http://doc.spip.org/@afficher_forum_thread
function afficher_forum_thread($row, $controle_id_article, $compteur_forum, $nb_forum, $i, $retour, $arg,  $script, $argscript) {
	global $spip_lang_right, $spip_display;
	static $voir_logo = array(); // pour ne calculer qu'une fois

	if (is_array($voir_logo)) {
		$voir_logo = (($spip_display != 1 AND $spip_display != 4 AND $GLOBALS['meta']['image_process'] != "non") ? 
		      "position: absolute; $spip_lang_right: 0px; margin: 0px; margin-top: -3px; margin-$spip_lang_right: 0px;" 
		      : '');
	}

	$id_forum=$row['id_forum'];
	$id_parent=$row['id_parent'];
	$id_rubrique=$row['id_rubrique'];
	$id_article=$row['id_article'];
	$id_breve=$row['id_breve'];
	$id_message=$row['id_message'];
	$id_syndic=$row['id_syndic'];
	$id_auteur=$row["id_auteur"];
	$titre=$row['titre'];
	$texte=$row['texte'];
	$nom_site=$row['nom_site'];
	$url_site=$row['url_site'];
	$statut=$row['statut'];
	$ip=$row["ip"];

	$h = (!$id_article ? '' : generer_url_entite($id_article, 'article'))
	  . "#forum$id_forum";

	$titre_boite = "<a href='$h' id='forum$id_forum'>"
	  . typo($titre)
	  . '</a>';

	if ($spip_display == 4) {
		$res = $titre_boite ."<br />";
	} else {
		if ($id_auteur AND $voir_logo) {
			$chercher_logo = charger_fonction('chercher_logo', 'inc');
			if ($logo = $chercher_logo($id_auteur, 'id_auteur', 'on')) {
				list($fid, $dir, $nom, $format) = $logo;
				include_spip('inc/filtres_images_mini');
				$logo = image_reduire("<img src='$fid' alt='' />", 48, 48);
				if ($logo)
					$titre_boite = "\n<div style='$voir_logo'>$logo</div>$titre_boite" ;
			}
		}


		$res = "<tr>"
		. afficher_forum_4($compteur_forum, $nb_forum, $i)
		. "\n<td style='width: 100%' valign='top'>"
		. (($compteur_forum == 1)
		   ? debut_cadre_forum(forum_logo($statut), true, "", $titre_boite)
		   : debut_cadre_thread_forum("", true, "", $titre_boite));
	}

	// Si refuse, cadre rouge
	if ($statut=="off") {
		$style =" style='border: 2px dashed red; padding: 5px;'";
	}
	// Si propose, cadre jaune
	else if ($statut=="prop") {
		$style = " style='border: 1px solid yellow; padding: 5px;'";
	}
	// Si original, cadre vert
	else if ($statut=="original") {
		$style = " style='border: 1px solid green; padding: 5px;'";
	} else $style = '';

	$mots = afficher_forum_mots($id_forum);

	$res .= "<table$style width='100%' cellpadding='5' cellspacing='0'>\n<tr><td>"
	.  afficher_forum_auteur($row)
	. (!$controle_id_article ? '' :
	   boutons_controle_forum($id_forum, $statut, $id_auteur, "id_article=$id_article", $ip,  $script, $argscript))
	. "<div style='font-weight: normal;'>"
	. safehtml(justifier(propre($texte)))
	. "</div>\n"
	. (!$nom_site ? '' :
	      ((strlen($url_site) > 10) ? "\n<div style='text-align: left' class='verdana2'><b><a href='$url_site'>$nom_site</a></b></div>"
	       : "<b>$nom_site</b>"))
	. ($controle_id_article ? '' :
	      repondre_forum($row, $titre, $statut, "$retour?$arg", _T('lien_repondre_message')))
	  . $mots
	  . "</td></tr></table>";

	if ($spip_display == 4) return "\n<li>$res</li>\n";

	if ($compteur_forum == 1) $res .= fin_cadre_forum(true);
	else $res .= fin_cadre_thread_forum(true);
	$res .= "</td></tr>";

	return "<table width='100%' cellpadding='0' cellspacing='0' border='0'>$res</table>\n";
}

// http://doc.spip.org/@repondre_forum
function repondre_forum($row, $titre, $statut, $retour, $clic)
{
	$id_forum = $row['id_forum'];
	$id_thread = $row['id_thread'];
	$ancre = "poster_forum_prive-$id_thread";

	$lien = generer_url_ecrire("poster_forum_prive", "statut=$statut&id_parent=$id_forum&titre_message=" . rawurlencode($titre) . "&script=" . urlencode($retour)) . '#formulaire';

	return "<div style='text-align: right' class='verdana1'><b><a onclick="
	. ajax_action_declencheur($lien, $ancre)
	. "\nhref='"
	. $lien
	. "'>"
	. $clic
	. "</a></b></div>\n";
}

// http://doc.spip.org/@afficher_forum_auteur
function afficher_forum_auteur($row)
{
	$titre=$row['titre'];
	$id_auteur=$row["id_auteur"];
	$date_heure=$row['date_heure'];
	$email_auteur=$row['email_auteur'];
	$auteur= extraire_multi($row['auteur']);

	if ($id_auteur) {
		$formater_auteur = charger_fonction('formater_auteur', 'inc');
		$res = join(' ',$formater_auteur($id_auteur));
	} else {
		if ($email_auteur) {
			if (email_valide($email_auteur))
				$email_auteur = "<a href='mailto:"
				.htmlspecialchars($email_auteur)
				."?subject=".rawurlencode($titre)."'>".$email_auteur
				."</a>";
			$auteur .= " &mdash; $email_auteur";
		}
		$res = safehtml("<span class='arial2'> / <b>$auteur</b></span>");
	}
	return "<div style='font-weight: normal;'>"
	  . date_interface($date_heure)
	  . "&nbsp;&nbsp;$res</div>";
}

// http://doc.spip.org/@afficher_forum_mots
function afficher_forum_mots($id_forum)
{
	if ($GLOBALS['meta']["mots_cles_forums"] <> "oui") return '';

	$mots = sql_allfetsel("titre, type", "spip_mots AS M LEFT JOIN spip_mots_forum AS L ON L.id_mot=M.id_mot",  "L.id_forum=" . intval($id_forum));

	foreach ($mots as $k => $r) {
		$mots[$k] = propre('<b>' . $r['type'] . ' :</b>') . ' '
		  . propre($r['titre']);
	}

	if (!$mots) return '';
	return ("\n<ul><li>" . join("</li>\n<li>", $mots) . "</li></ul>\n");
}

// affiche les traits de liaisons entre les reponses

// http://doc.spip.org/@afficher_forum_4
function afficher_forum_4($compteur_forum, $nb_forum, $thread)
{
	global $spip_lang_rtl;
	$fleche2="forum-droite$spip_lang_rtl.gif";
	$fleche='rien.gif';
	$vertical = chemin_image('forum-vert.gif');
	$rien = chemin_image('rien.gif');
	$res = '';
	for ($j=2;$j<=$compteur_forum AND $j<20;$j++){
		$res .= "<td style='width: 10px; vertical-align: top; background-image: url("
		. (($thread[$j]!=$nb_forum[$j]) ? $vertical : $rien)
		.  ");'>"
		. http_img_pack(($j==$compteur_forum) ? $fleche2 : $fleche, "", "width='10' height='13'")
		. "</td>\n";
	}
	return $res;
}
?>
