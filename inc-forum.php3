<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_FORUM")) return;
define("_INC_FORUM", "1");

include_ecrire('inc_meta.php3');
include_ecrire('inc_admin.php3');
include_ecrire('inc_acces.php3');
include_ecrire('inc_texte.php3');
include_ecrire('inc_filtres.php3');
include_ecrire('inc_lang.php3');
include_ecrire('inc_mail.php3');
include_ecrire('inc_barre.php3');
include_ecrire('inc_forum.php3');
include_ecrire("inc_abstract_sql.php3");

// Gestionnaire d'URLs
if (@file_exists("inc-urls.php3"))
	include_local("inc-urls.php3");
else
	include_local("inc-urls-".$GLOBALS['type_urls'].".php3");


/*******************************/
/* GESTION DU FORMULAIRE FORUM */
/*******************************/

// fabrique un bouton de type $t de Name $n, de Value $v et autres attribut $a
function boutonne($t, $n, $v, $a='') {
  return "\n<input type='$t'" .
    (!$n ? '' : " name='$n'") .
    " value=\"$v\" $a />";
}

//
// Le code dynamique appelee par les squelettes
//
function retour_forum($id_rubrique, $id_forum, $id_article, $id_breve, $id_syndic, $titre, $table, $forums_publics, $url, $hidden) {

	global $REMOTE_ADDR, $id_message, $afficher_texte, $spip_forum_user;

	// Recuperer le message a previsualiser
	if ($id_message = intval($GLOBALS[HTTP_POST_VARS][id_message]))  {
		$titre = $GLOBALS[HTTP_POST_VARS][titre];
		$texte = $GLOBALS[HTTP_POST_VARS][texte];
		$auteur = $GLOBALS[HTTP_POST_VARS][auteur];
		$email_auteur = $GLOBALS[HTTP_POST_VARS][email_auteur];
		$nom_site_forum = $GLOBALS[HTTP_POST_VARS][nom_site_forum];
		$url_site = $GLOBALS[HTTP_POST_VARS][url_site];

		if ($afficher_texte != 'non') {
			$previsu = 
			"<div style='font-size: 120%; font-weigth: bold;'>"
			. typo($titre)
			. "</div><p /><b><a href=\"mailto:"
			. entites_html($email_auteur) . "\">"
			. typo($auteur) . "</a></b><p />"
			. propre($texte) . "<p /><a href=\""
			. entites_html($url_site) . "\">"
			. typo($nom_site_forum) . "</a>";

			// Verifier mots associes au message
			$result_mots = spip_query("SELECT mots.id_mot, mots.titre, mots.type
			FROM spip_mots_forum AS lien, spip_mots AS mots 
			WHERE id_forum='$id_message' AND mots.id_mot = lien.id_mot
			GROUP BY mots.id_mot");
			if (spip_num_rows($result_mots)>0) {
				$previsu .= "<p>"._T('forum_avez_selectionne')."</p><ul>";
				while ($row = spip_fetch_array($result_mots)) {
					$les_mots[$row['id_mot']] = "checked='checked'";
					$presence_mots = true;
					$previsu .= "<li class='font-size=80%'> "
					. $row['type'] . "&nbsp;: <b>" . $row['titre'] ."</b></li>";
				}
				$previsu .= '</ul>';
			}

			if (strlen($texte) < 10 AND !$presence_mots) {
				$previsu .= "<p align='right' style='color: red;'>"._T('forum_attention_dix_caracteres')."</p>\n";
			}
			else if (strlen($titre) < 3 AND $afficher_texte <> "non") {
				$previsu .= "<p align='right' style='color: red;'>"._T('forum_attention_trois_caracteres')."</p>";
			}
			else {
				$previsu .= "<div align='right'><input type='submit' name='confirmer' class='spip_bouton' value='"._T('forum_message_definitif')."' /></div>";
			}
			$previsu = "<div class='spip_encadrer'>$previsu</div>\n<br />";
			// supprimer les <form> de la previsualisation
			// (sinon on ne peut pas faire <cadre>...</cadre> dans les forums)
			$previsu = preg_replace("@<(/?)f(orm[>[:space:]])@ism", "<\\1no-f\\2", $previsu);
		}
	} else {
		// Si premiere edition, initialiser l'auteur
	  	// puis s'accorder une nouvelle entree dans la table
		if ($spip_forum_user && is_array($cookie_user = unserialize($spip_forum_user))) {
			$auteur = $cookie_user['nom'];
			$email_auteur = $cookie_user['email'];
		}
		else {
			$auteur = $GLOBALS['auteur_session']['nom'];
			$email_auteur = $GLOBALS['auteur_session']['email'];
		}
		$id_message = spip_abstract_insert('spip_forum', 
					  "(date_heure, titre, ip, statut)",
					  "(NOW(), '".addslashes($titre)."', '$REMOTE_ADDR', 'redac')");

	}

	// Generation d'une valeur de securite pour validation
	$seed = (double) (microtime() + 1) * time() * 1000000;
	@mt_srand($seed);
	$alea = @mt_rand();
	if (!$alea) {srand($seed);$alea = rand();}
	$forum_id_rubrique = intval($id_rubrique);
	$forum_id_forum = intval($id_forum);
	$forum_id_article = intval($id_article);
	$forum_id_breve = intval($id_breve);
	$forum_id_syndic = intval($id_syndic);
	$hash = calculer_action_auteur("ajout_forum $forum_id_rubrique $forum_id_forum $forum_id_article $forum_id_breve $forum_id_syndic $alea");
	$titre = entites_html($titre);
	if (!$url_site) $url_site = "http://";
	if ($forums_publics == "abo") $disabled = " disabled='disabled'";

	// Faut-il ajouter des propositions de mots-cles
	if ((lire_meta("mots_cles_forums") == "oui") && ($table != 'forum'))
		$table = table_des_mots($table, $les_mots);
	else
		$table = '';

	$url = quote_amp($url);

	return ("<form action='$url' method='post' name='formulaire'>\n$hidden" .
		boutonne('hidden', 'id_message', $id_message) .
		boutonne('hidden', 'alea', $alea) .
		boutonne('hidden', 'hash', $hash) .
"ajout_forum $forum_id_rubrique $forum_id_forum $forum_id_article $forum_id_breve $forum_id_syndic $alea" .
		(($afficher_texte == "non") ?
		 (boutonne('hidden', 'titre', $titre) .
		  $table .
		  "\n<br /><div align='right'>" .
		  boutonne('submit', '', _T('forum_valider'), "class='spip_bouton'") .
		  "</div>") :
		 ($previsu . "<div class='spip_encadrer'><b>"._T('forum_titre')."</b>\n<br />".
		  boutonne('text', 'titre', $titre, "class='forml' size='40'") . "</div>\n<br />"
		  ."<div class='spip_encadrer'><b>" .
		  _T('forum_texte') .
		  "</b>\n<br />" .
		  _T('info_creation_paragraphe') .
		  "\n<br /> " .
		  afficher_barre('formulaire', 'texte', true) .
		  "<textarea name='texte' " .
		  afficher_claret() .
		  " rows='12' class='forml' cols='40'>" .
		  entites_html($texte) .
		  "</textarea></div>" .
		  $table  .
		 "\n<br /><div class='spip_encadrer'>" .
		  _T('forum_lien_hyper') .
		  "\n<br />" .
		  _T('forum_page_url') .
		  "\n<br />" .
		  _T('forum_titre') .
		  "\n<br />" .
		  boutonne('text', 'nom_site_forum', entites_html($nom_site_forum), " class='forml' size='40'") .
		  "\n<br />" .
		  _T('forum_url') .
		  "\n<br />" .
		  boutonne('text', 'url_site', entites_html($url_site),
			   " class='forml'  size='40'") . 
		  "</div>\n<br /><div class='spip_encadrer'>" .
		  _T('forum_qui_etes_vous') .
		  "\n<br />" .
		  _T('forum_votre_nom') .
		  "\n<br />" .
		  boutonne('text', 'auteur', entites_html($auteur),
			   "class='forml' size='40'$disabled") .
		  "\n<br />" .
		  _T('forum_votre_email') .
		  "\n<br />" .
		  boutonne('text', 'email_auteur', entites_html($email_auteur),
			   "class='forml' size='40'$disabled") .
		  "</div>\n<br /><div align='right'>" .
		  boutonne('submit', '',  _T('forum_voir_avant'), "class='spip_bouton'") . 
		  "</div>\n</form>")));
}


// Mots-cles dans les forums :
// Si la variable de personnalisation $afficher_groupe[] est definie
// dans le fichier d'appel, et si la table de reference est OK, proposer
// la liste des mots-cles
function table_des_mots($table, $les_mots) {
	global $afficher_groupe;

	if ($afficher_groupe)
		$in_group = " AND id_groupe IN (" . join($afficher_groupe, ", ") .")";

	$result_groupe = spip_query("SELECT * FROM spip_groupes_mots
	WHERE 6forum = 'oui' AND $table = 'oui'". $in_group);

	$ret = '';
	while ($row_groupe = spip_fetch_array($result_groupe)) {
		$id_groupe = $row_groupe['id_groupe'];
		$titre_groupe = propre($row_groupe['titre']);
		$unseul = ($row_groupe['unseul']== 'oui') ? 'radio' : 'checkbox';
		$result =spip_query("SELECT * FROM spip_mots
		WHERE id_groupe='$id_groupe'");
		$total_rows = spip_num_rows($result);

		if ($total_rows > 0) {
			$ret .= "\n<p />";
			$ret .= "<div class='spip_encadrer' style='font-size: 80%;'>";
			$ret.= "<b>$titre_groupe&nbsp;:</b>";
			$ret .= "<table cellpadding='0' cellspacing='0' border='0' width='100%'>\n";
			$ret .= "<tr><td width='47%' valign='top'>";
			$i = 0;
      
		while ($row = spip_fetch_array($result)) {
			$id_mot = $row['id_mot'];
			$titre_mot = propre($row['titre']);
			$descriptif_mot = propre($row['descriptif']);

			if ($i >= ($total_rows/2) AND $i < $total_rows) {
				$i = $total_rows + 1;
				$ret .= "</td><td width='6%'>&nbsp;</td>
				<td width='47%' valign='top'>";
			}

			$ret .= boutonne($unseul, "ajouter_mot[$id_groupe][]", $id_mot, "id='mot$id_mot' " . $les_mots[$id_mot]) .
			  afficher_petits_logos_mots($id_mot)
			. "<b><label for='mot$id_mot'>$titre_mot</label></b><br />";

			if ($descriptif_mot)
				$ret .= "$descriptif_mot<br />";
			$i++;
		}

		$ret .= "</td></tr></table>";
		$ret .= "</div>";
		}
	}

	return $ret;
}


function afficher_petits_logos_mots($id_mot) {
	$on = cherche_image_nommee("moton$id_mot");
	if ($on) {
	  $image = ("$on[0]$on[1].$on[2]");
		$taille = @getimagesize($image);
		$largeur = $taille[0];
		$hauteur = $taille[1];
		if ($largeur < 100 AND $hauteur < 100)
			return "<img src='$image' align='middle' width='$largeur'
			height='$hauteur' hspace='1' vspace='1' alt=' ' border='0'
			class='spip_image' /> ";
	}
}



/*******************************************/
/* DEFINITION DES BALISES LIEES AUX FORUMS */
/*******************************************/

// Noter l'invalideur de la page contenant ces parametres,
// en cas de premier post sur le forum
function code_invalideur_forums($p, $code) {
	return '
	// invalideur forums
	(!($Cache[\'id_forum\'][calcul_index_forum(' . 
				// Retournera 4 [$SP] mais force la demande du champ a MySQL
				champ_sql('id_article', $p) . ',' .
				champ_sql('id_breve', $p) .  ',' .
				champ_sql('id_rubrique', $p) .',' .
				champ_sql('id_syndic', $p) .  ")]=1)".
				"?'':\n" . $code .")";
}


// Formulaire de reponse a un forum
function balise_FORMULAIRE_FORUM_dist($p) {
	$code = "code_de_forum_spip(" .
	champ_sql('id_rubrique', $p) . ', ' .
	champ_sql('id_forum', $p) . ', ' .
	champ_sql('id_article', $p) . ', ' .
	champ_sql('id_breve', $p) . ', ' .
	champ_sql('id_syndic', $p) . ')';

	$p->code = code_invalideur_forums($p, "(".$code.")");

	$p->statut = 'php';
	return $p;
}


// Parametres de reponse a un forum
function balise_PARAMETRES_FORUM_dist($p) {
	$_accepter_forum = champ_sql('accepter_forum', $p);
	$p->code = '
	// refus des forums ?
	('.$_accepter_forum.'=="non" OR
	(lire_meta("forums_publics") == "non" AND '.$_accepter_forum.'!="oui"))
	? "" : // sinon:
	';

	switch ($p->type_requete) {
		case 'articles':
			$c = '"id_article=".' . champ_sql('id_article', $p);
			break;
		case 'breves':
			$c = '"id_breve=".' . champ_sql('id_breve', $p);
			break;
		case 'rubriques':
			$c = '"id_rubrique=".' . champ_sql('id_rubrique', $p);
			break;
		case 'syndication':
			$c = '"id_syndic=".' . champ_sql('id_syndic', $p);
			break;
		case 'forums':
		default:
			$liste_champs = array ("id_article","id_breve","id_rubrique","id_syndic","id_forum");
			foreach ($liste_champs as $champ) {
				$x = champ_sql( $champ, $p);
				$c .= (($c) ? ".\n" : "") . "((!$x) ? '' : ('&$champ='.$x))";
			}
			$c = "substr($c,1)";
			break;
	}

	$c .= '.
	"&retour=".rawurlencode($lien=$GLOBALS["HTTP_GET_VARS"]["retour"] ? $lien : nettoyer_uri())';

	$p->code .= code_invalideur_forums($p, "(".$c.")");

	$p->statut = 'html';
	return $p;
}


/*******************************************************/
/* FONCTIONS DE CALCUL DES DONNEES DU FORMULAIRE FORUM */
/*******************************************************/
function code_de_forum_spip ($idr, $idf, $ida, $idb, $ids) {

	// recuperer les donnees du forum auquel on repond, false = forum interdit
	if (!$r = sql_recherche_donnees_forum ($idr, $idf, $ida, $idb, $ids))
		return false;

	list($titre, $table, $accepter_forum) = $r;

	// titre propose pour la reponse
	$titre = '> '.supprimer_numero(ereg_replace('^[>[:space:]]*', '',$titre));

	// url de reference
	if (!$url = rawurldecode($GLOBALS['url'])) 
	    $url = $GLOBALS['REQUEST_URI'];
	else {
	// identifiants des parents
	  $args = '';  
	  if ($idr) $args .= "&id_rubrique=$idr";
	  if ($idf) $args .= "&id_forum=$idf";
	  if ($ida) $args .= "&id_article=$ida";
	  if ($idb) $args .= "&id_breve=$idb";
	  if ($ids) $args .= "&id_syndic=$ids";
	  if ($args) $url .= (strpos($url,'?') ? $args : ('?' . substr($args,1)));
	}
	$url = ereg_replace("[?&]var_erreur=[^&]*", '', $url);
	$url = ereg_replace("[?&]var_login[^&]*", '', $url);
	$url = ereg_replace("[?&]var_url[^&]*", '', $url);
	// url de retour du forum
	$retour_forum = rawurldecode($GLOBALS['HTTP_GET_VARS']['retour']);
	if (!$retour_forum)
	  $retour_forum = $url;
	else $retour_forum = ereg_replace('&recalcul=oui','',$retour_forum);

	// debut formulaire forum
	$lacible = "
	include_local('inc-forum.php3');
	lang_select(\$GLOBALS['spip_lang']);
	echo retour_forum('$idr','$idf','$ida','$idb','$ids','".
	  texte_script($titre).
	  "','".$table."', '".$accepter_forum."', '".$url."', \"
	<input type='hidden' name='retour' value='".$retour_forum."' />
	<input type='hidden' name='ajout_forum' value='oui' />
	";
	// message de moderation
	$lacible .= (($accepter_forum != 'pri') ? '' :
	((_T('forum_info_modere'). '<p>'))) . "\"); lang_dselect();";

	// verifier l'identite des posteurs pour les forums sur abo
	if ($accepter_forum == "abo")
		$lacible = "
		if (\$GLOBALS[\"auteur_session\"]) {\n$lacible
		} else {
			include_local('inc-login.php3'); 
			login_pour_tous('$url', false, true, '$url');
		}";

	return "<" . "?php" . $lacible . "?" . ">";
}

//
// Chercher le titre et la configuration du forum de l'element auquel on repond
//
function sql_recherche_donnees_forum ($idr, $idf, $ida, $idb, $ids) {

	// changer la table de reference s'il y a lieu (pour afficher_groupes[] !!)
	if ($idr) {
		$r = "SELECT titre FROM spip_rubriques WHERE id_rubrique = $idr";
		$table = "rubriques";
	} else if ($ida) {
		$r = "SELECT titre FROM spip_articles WHERE id_article = $ida";
		$table = "articles";
	} else if ($idb) {
		$r = "SELECT titre FROM spip_breves WHERE id_breve = $idb";
		$table = "breves";
	} else if ($ids) {
		$r = "SELECT nom_site AS titre FROM spip_syndic WHERE id_syndic = $ids";
		$table = "syndic";
	}

	if ($idf)
		$r = "SELECT titre FROM spip_forum WHERE id_forum = $idf";

	if ($r)
		list($titre) = spip_fetch_array(spip_query($r));
	else {
		$titre = _T('forum_titre_erreur');
		$table = '';
	}

	// quelle est la configuration du forum ?
	if ($ida)
		list($accepter_forum) = spip_fetch_array(spip_query(
		"SELECT accepter_forum FROM spip_articles WHERE id_article=$ida"));
	else
		$accepter_forum = substr(lire_meta("forums_publics"),0,3);
	// valeurs possibles : 'pos'teriori, 'pri'ori, 'abo'nnement
	if ($accepter_forum == "non")
		return false;

	return array ($titre, $table, $accepter_forum);
}

?>
