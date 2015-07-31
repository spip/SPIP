<?php

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

include_ecrire('inc_meta.php3');
include_ecrire('inc_admin.php3');
include_ecrire('inc_acces.php3');
include_ecrire('inc_texte.php3');
include_ecrire('inc_filtres.php3');
include_ecrire('inc_lang.php3');
include_ecrire('inc_mail.php3');
include_ecrire('inc_forum.php3');
include_ecrire("inc_abstract_sql.php3");
include_local(_FILE_CONNECT);

// Gestionnaire d'URLs
if (@file_exists("inc-urls.php3"))
	include_local("inc-urls.php3");
else
	include_local("inc-urls-".$GLOBALS['type_urls'].".php3");

/*******************************/
/* GESTION DU FORMULAIRE FORUM */
/*******************************/

// Contexte du formulaire
global $balise_FORMULAIRE_FORUM_collecte;
$balise_FORMULAIRE_FORUM_collecte = array('id_rubrique', 'id_forum', 'id_article', 'id_breve', 'id_syndic');


// verification des droits a faire du forum
function balise_FORMULAIRE_FORUM_stat($args, $filtres) {

	// Note : ceci n'est pas documente !!
	// $filtres[0] peut contenir l'url sur lequel faire tourner le formulaire
	// exemple dans un squelette article.html : [(#FORMULAIRE_FORUM|forum.php3)]

	// $args[5] peut contenir l'url sur lequel faire le retour
	// exemple dans un squelette article.html : [(#FORMULAIRE_FORUM{#SELF})]

	// recuperer les donnees du forum auquel on repond, false = forum interdit
	list ($idr, $idf, $ida, $idb, $ids) = $args;
	$idr = intval($idr);
	$idf = intval($idf);
	$ida = intval($ida);
	$idb = intval($idb);
	$ids = intval($ids);
	if (!$r = sql_recherche_donnees_forum ($idr, $idf, $ida, $idb, $ids))
		return '';

	list ($titre, $table, $forums_publics) = $r;

	// Attention id_rubrique est passe pour les articles => on n'en veut pas
	if ($idr > 0 AND ($ida OR $idb OR $ids))
		$idr = 0;

	return
		array($titre, $table, $forums_publics, $idr, $idf, $ida, $idb, $ids,
			$filtres[0], $args[5]);
}

function balise_FORMULAIRE_FORUM_dyn($titre, $table, $forums_publics, $id_rubrique, $id_forum, $id_article, $id_breve, $id_syndic, $url, $url_param_retour) {
	global $REMOTE_ADDR, $afficher_texte, $_COOKIE, $_POST;

	// Recuperer les donnees postees du formulaire ou, a defaut, du contexte
	foreach (array('id_article', 'id_breve', 'id_syndic',
	'id_rubrique', 'id_forum') as $id)
		if (isset($_POST['forum_'.$id]))
			$$id = intval($_POST['forum_'.$id]);
		else
			$$id = intval($$id);

	// url de reference
	if (!$url) {
		$url = new Link();
		$url = $url->getUrl();
	} else {
		// identifiants des parents
		$args = array();
		if ($id_rubrique) $args[] = "id_rubrique=$id_rubrique";
		if ($id_forum) $args[] = "id_forum=$id_forum";
		if ($id_article) $args[] = "id_article=$id_article";
		if ($id_breve) $args[] = "id_breve=$id_breve";
		if ($id_syndic) $args[] = "id_syndic=$id_syndic";
		if (count($args))
			$url .= (strpos($url,'?') ? '&' : '?'). join('&',$args);
	}

	$url = ereg_replace("[?&]var_erreur=[^&]*", '', $url);
	$url = ereg_replace("[?&]var_login=[^&]*", '', $url);
	$url = ereg_replace("[?&]url=[^&]*", '', $url);

	// verifier l'identite des posteurs pour les forums sur abo
	if (($forums_publics == "abo") && (!$GLOBALS["auteur_session"])) {
		return array('formulaire_login_forum', 0, array());
	}

	// ne pas mettre '', sinon le squelette n'affichera rien.
	$previsu = ' ';

	// au premier appel (pas de Post-var nommee "retour_forum")
	// memoriser evntuellement l'URL de retour pour y revenir apres
	// envoi du message ; aux appels suivants, reconduire la valeur.
	// Initialiser aussi l'auteur
	if (!$retour_forum = rawurldecode(_request('retour_forum'))) {
		if ($retour_forum = rawurldecode(_request('retour')))
			$retour_forum = str_replace('&var_mode=recalcul','',$retour_forum);
		else {
			// par defaut, on veut prendre url_forum(), mais elle ne sera connue
			// qu'en sortie, on inscrit donc une valeur absurde ("!")
			$retour_forum = "!";
			
			// sauf si on a passe un parametre en argument (exemple : {#SELF})
			if($url_param_retour)
				$retour_forum = str_replace("&amp;", "&", $url_param_retour);
		}

		if (isset($_COOKIE['spip_forum_user'])
		AND is_array($cookie_user = unserialize($_COOKIE['spip_forum_user']))) {
			$auteur = $cookie_user['nom'];
			$email_auteur = $cookie_user['email'];
		} else {
			$auteur = $GLOBALS['auteur_session']['nom'];
			$email_auteur = $GLOBALS['auteur_session']['email'];
		}

	} else {

		// Recuperer le message a previsualiser
		$titre = _request('titre');
		$texte = _request('texte');
		$auteur = _request('auteur');
		$email_auteur = _request('email_auteur');
		$nom_site_forum = _request('nom_site_forum');
		$url_site = _request('url_site');
		$ajouter_mot = _request('ajouter_mot'); // array

		// Verifier mots associes au message
		if (is_array($ajouter_mot))
		$mots = preg_replace('/[^0-9,]/', '', join(',',$ajouter_mot));
		else $mots = '0';

		// affichage {par num type, type, num titre,titre}
		$result_mots = spip_query("SELECT id_mot, titre, type
			FROM spip_mots
			WHERE id_mot IN ($mots)
			ORDER BY 0+type,type,0+titre,titre");
		if (spip_num_rows($result_mots)>0) {
			$mots_forums = "<p>"._T('forum_avez_selectionne')."</p><ul>";
			while ($row = spip_fetch_array($result_mots)) {
				$les_mots[$row['id_mot']] = "checked='checked'";
				$presence_mots = true;
				$mots_forums .= "<li style='font-size: 80%;'> "
				. typo($row['type']) . "&nbsp;: <b>"
				. typo($row['titre']) ."</b></li>";
			}
			$mots_forums .= '</ul>';
		}

		//
		// Preparer la previsualisation
		//
		if ($afficher_texte != 'non') {
			if (strlen($texte) < 10 AND !$presence_mots)
				$erreur = _T('forum_attention_dix_caracteres');
			else if (strlen($titre) < 3 AND $afficher_texte <> "non")
				$erreur = _T('forum_attention_trois_caracteres');
			else
				$bouton = _T('forum_message_definitif');

			$previsu = inclure_balise_dynamique(
				array(
					'formulaire_forum_previsu',
					0,
					array(
						'titre' => interdire_scripts(typo($titre)),
						'email_auteur' => $email_auteur,
						'auteur' => interdire_scripts(typo($auteur)),
						'texte' => propre($texte),
						'url_site' => $url_site,
						'nom_site_forum' =>
							interdire_scripts(typo($nom_site_forum)),
						'mots_forum' => $mots_forum,
						'erreur' => $erreur,
						'bouton' => $bouton
					)
				), false);

			// supprimer les <form> de la previsualisation
			// (sinon on ne peut pas faire <cadre>...</cadre> dans les forums)
			$previsu = preg_replace("@<(/?)f(orm[>[:space:]])@ism",
				"<\\1no-f\\2", $previsu);
		}

	// Une securite qui nous protege contre :
	// - les doubles validations de forums (derapages humains ou des brouteurs)
	// - les abus visant a mettre des forums malgre nous sur un article (??)
	// On installe un fichier temporaire dans _DIR_SESSIONS (et pas _DIR_CACHE
	// afin de ne pas bugguer quand on vide le cache)
	// Le lock est leve au moment de l'insertion en base (inc-messforum.php3)
	// Ce systeme n'est pas fonctionnel pour les forums sans previsu (notamment
	// si $afficher_texte = 'non')

		$alea = preg_replace('/[^0-9]/', '', $alea);
		if(!$alea OR !@file_exists(_DIR_SESSIONS."forum_$alea.lck")) {
			while (
				# astuce : mt_rand pour autoriser les hits simultanes
				$alea = time() + @mt_rand()
				AND @file_exists($f = _DIR_SESSIONS."forum_$alea.lck")) {};
			spip_touch ($f);
		}

		# et maintenant on purge les locks de forums ouverts depuis > 4 h
		if ($dh = @opendir(_DIR_SESSIONS))
			while (($file = @readdir($dh)) !== false)
				if (preg_match('/^forum_([0-9]+)\.lck$/', $file)
				AND (time()-@filemtime(_DIR_SESSIONS.$file) > 4*3600))
					@unlink(_DIR_SESSIONS.$file);

		$hash = calculer_action_auteur("ajout_forum $id_rubrique $id_forum $id_article $id_breve $id_syndic $alea");
	}

	// Faut-il ajouter des propositions de mots-cles
	if ($table AND (lire_meta("mots_cles_forums") == "oui"))
		$table = table_des_mots($table, $les_mots);
	else
		$table = '';

	// preparer les parametres du forum en input hidden
	$parametres_forum = '';
	foreach (array('alea', 'hash', 'retour_forum') as $var)
		if ($val = entites_html($$var))
			$parametres_forum .=
			"<input type='hidden' name='$var' value=\"$val\" />\n";
	foreach (array(
	'id_article', 'id_breve', 'id_syndic', 'id_rubrique', 'id_forum')
	as $var)
		if ($val = $$var)
			$parametres_forum .=
			"<input type='hidden' name='forum_$var' value='$val' />\n";

	return array('formulaire_forum', 0,
	array(
		'auteur' => $auteur,
		'disabled' => ($forums_publics == "abo")? " disabled='disabled'" : '',
		'email_auteur' => $email_auteur,
		'modere' => (($forums_publics != 'pri') ? '' : _T('forum_info_modere')),
		'nom_site_forum' => $nom_site_forum,
		'previsu' => $previsu,
		'table' => $table,
		'texte' => $texte,
		'titre' => extraire_multi($titre),
		'url' =>  $url,
		'url_site' => ($url_site ? $url_site : "http://"),
		'parametres_forum' => $parametres_forum,

		## gestion des la variable de personnalisation $afficher_texte
		# mode normal : afficher le texte en < input text >, cf. squelette
		'afficher_texte_input' => (($afficher_texte <> 'non') ? '&nbsp;' : ''),
		# mode 'non' : afficher les elements en < input hidden >
		'afficher_texte_hidden' => (($afficher_texte <> 'non') ? '' :
			(boutonne('hidden', 'titre', htmlspecialchars($titre)) .
				$table .
				"\n<br /><div align='right'>" .
				boutonne('submit', '', _T('forum_valider'),
				"class='spip_bouton'") .
				"</div>"))

		));
}


function barre_forum($texte)
{
	include_ecrire('inc_layer.php3');
	$texte = entites_html($texte);

	if (!$GLOBALS['browser_barre'])
		return "<textarea name='texte' rows='12' class='forml' cols='40'>$texte</textarea>";
	static $num_formulaire = 0;
	$num_formulaire++;
	include_ecrire('inc_barre.php3');
	return afficher_barre("document.getElementById('formulaire_$num_formulaire')", true) .
	  "
<textarea name='texte' rows='12' class='forml' cols='40'
id='formulaire_$num_formulaire'
onselect='storeCaret(this);'
onclick='storeCaret(this);'
onkeyup='storeCaret(this);'
ondbclick='storeCaret(this);'>$texte</textarea>";
}

// Mots-cles dans les forums :
// Si la variable de personnalisation $afficher_groupe[] est definie
// dans le fichier d'appel, et si la table de reference est OK, proposer
// la liste des mots-cles
function table_des_mots($table, $les_mots) {
	global $afficher_groupe;

	if (is_array($afficher_groupe))
		$in_group = " AND id_groupe IN (" . join($afficher_groupe, ", ") .")";
	else
		$in_group = '';

	$result_groupe = spip_query("SELECT * FROM spip_groupes_mots
	WHERE forum = 'oui' AND $table = 'oui'". $in_group);

	$ret = '';
	while ($row_groupe = spip_fetch_array($result_groupe)) {
		$id_groupe = $row_groupe['id_groupe'];
		$titre_groupe = propre($row_groupe['titre']);
		$unseul = ($row_groupe['unseul']== 'oui') ? 'radio' : 'checkbox';
		$result =spip_query("SELECT * FROM spip_mots
		WHERE id_groupe='$id_groupe'");
		$total_rows = spip_num_rows($result);

		if ($total_rows > 0) {
			$ret .= "\n<p />"
			  . "<div class='spip_encadrer' style='font-size: 80%;'>"
			  . "<b>$titre_groupe&nbsp;:</b>"
			  . "<table cellpadding='0' cellspacing='0' border='0' width='100%'>\n"
			  ."<tr><td width='47%' valign='top'>";
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

			$ret .= boutonne($unseul, "ajouter_mot[]", $id_mot, "id='mot$id_mot' " . $les_mots[$id_mot]) .
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
	include_ecrire('inc_logos.php3');
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



/*******************************************************/
/* FONCTIONS DE CALCUL DES DONNEES DU FORMULAIRE FORUM */
/*******************************************************/

//
// Chercher le titre et la configuration du forum de l'element auquel on repond
//

function sql_recherche_donnees_forum ($idr, $idf, $ida, $idb, $ids) {

	// changer la table de reference s'il y a lieu (pour afficher_groupes[] !!)
	if ($ida) {
		$r = "SELECT titre FROM spip_articles WHERE id_article = $ida";
		$table = "articles";
	} else if ($idb) {
		$r = "SELECT titre FROM spip_breves WHERE id_breve = $idb";
		$table = "breves";
	} else if ($ids) {
		$r = "SELECT nom_site AS titre FROM spip_syndic WHERE id_syndic = $ids";
		$table = "syndic";
	} else if ($idr) {
		$r = "SELECT titre FROM spip_rubriques WHERE id_rubrique = $idr";
		$table = "rubriques";
	}

	if ($idf)
		$r = "SELECT titre FROM spip_forum WHERE id_forum = $idf";

	if ($r) {
		list($titre) = spip_fetch_array(spip_query($r));
		$titre = supprimer_numero($titre);
	} else 
		return;

	// quelle est la configuration du forum ?
	if ($ida)
		list($accepter_forum) = spip_fetch_array(spip_query(
		"SELECT accepter_forum FROM spip_articles WHERE id_article=$ida"));
	if (!$accepter_forum)
		$accepter_forum = substr(lire_meta("forums_publics"),0,3);
	// valeurs possibles : 'pos'teriori, 'pri'ori, 'abo'nnement
	if ($accepter_forum == "non")
		return false;

	return array ($titre, $table, $accepter_forum);
}

?>
