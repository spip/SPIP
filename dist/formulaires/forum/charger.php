<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

function formulaires_forum_charger_dist(
$titre, $table, $type, $script,
$id_rubrique, $id_forum, $id_article, $id_breve, $id_syndic,
$ajouter_mot, $ajouter_groupe, $afficher_texte, $url_param_retour) {

	// exiger l'authentification des posteurs pour les forums sur abo
	if ($type == "abo") {
		if (!$GLOBALS["visiteur_session"]['statut']) {
			return array(false,array(
				'login_forum_abo'=>' ',
				'inscription' => generer_url_public('identifiants', 'lang='.$GLOBALS['spip_lang']),
				'oubli' => generer_url_public('spip_pass','lang='.$GLOBALS['spip_lang'],true),
				));
		}
	}

	// Tableau des valeurs servant au calcul d'une signature de securite.
	// Elles seront placees en Input Hidden pour que inc/forum_insert
	// recalcule la meme chose et verifie l'identite des resultats.
	// Donc ne pas changer la valeur de ce tableau entre le calcul de
	// la signature et la fabrication des Hidden
	// Faire attention aussi a 0 != ''

	// id_rubrique est parfois passee pour les articles, on n'en veut pas
	$ids = array();
	if ($id_rubrique > 0 AND ($id_article OR $id_breve OR $id_syndic))
		$id_rubrique = 0;
	foreach (array('id_article', 'id_breve', 'id_forum', 'id_rubrique', 'id_syndic') as $o) {
		$ids[$o] = ($x = intval($$o)) ? $x : '';
	}


	// ne pas mettre '', sinon le squelette n'affichera rien.
	$previsu = ' ';

	// au premier appel (pas de Post-var nommee "retour_forum")
	// memoriser eventuellement l'URL de retour pour y revenir apres
	// envoi du message ; aux appels suivants, reconduire la valeur.
	// Initialiser aussi l'auteur
	if ($retour_forum = rawurldecode(_request('retour')))
		$retour_forum = str_replace('&var_mode=recalcul','',$retour_forum);
	else {
		// par defaut, on veut prendre url_forum(), mais elle ne sera connue
		// qu'en sortie, on inscrit donc une valeur absurde ("!")
		$retour_forum = "!";
		// sauf si on a passe un parametre en argument (exemple : {#SELF})
		if ($url_param_retour)
			$retour_forum = str_replace('&amp;', '&', $url_param_retour);
		$retour_forum = rawurlencode($retour_forum);
	}
	if (_request('retour_forum')){
		$arg = forum_fichier_tmp(join('', $ids));
		
		$securiser_action = charger_fonction('securiser_action', 'inc');
		// on sait que cette fonction est dans le fichier associe
		$hash = calculer_action_auteur("ajout_forum-$arg");
	}

	// pour la chaine de hidden
	$script_hidden = $script = str_replace('&amp;', '&', $script);
	foreach ($ids as $id => $v)
		$script_hidden = parametre_url($script_hidden, $id, $v, '&');

	return array(
		'modere' => (($type != 'pri') ? '' : ' '),
		'nom_site' => '',
		'retour_forum' => $retour_forum,
		'afficher_texte' => $afficher_texte,
		'table' => $table,
		'texte' => '',
		'titre' => extraire_multi($titre),
		'url' => $script, # ce sur quoi on fait le action='...'
		'url_post' => $script_hidden, # pour les variables hidden
		'url_site' => "http://",
		'arg' => $arg,
		'hash' => $hash,
		'nobot' => _request('nobot'),
		'ajouter_groupe' => $ajouter_groupe,
		'ajouter_mot' => (is_array($ajouter_mot) ? $ajouter_mot : array($ajouter_mot)),
		'id_forum' => $id_forum, // passer id_forum au formulaire pour lui permettre d'afficher a quoi l'internaute repond
	);
}


// Une securite qui nous protege contre :
// - les doubles validations de forums (derapages humains ou des brouteurs)
// - les abus visant a mettre des forums malgre nous sur un article (??)
// On installe un fichier temporaire dans _DIR_TMP (et pas _DIR_CACHE
// afin de ne pas bugguer quand on vide le cache)
// Le lock est leve au moment de l'insertion en base (inc-messforum)
// Ce systeme n'est pas fonctionnel pour les forums sans previsu (notamment
// si $afficher_texte = 'non')

// http://doc.spip.org/@forum_fichier_tmp
function forum_fichier_tmp($arg)
{
# astuce : mt_rand pour autoriser les hits simultanes
	while (($alea = time() + @mt_rand()) + intval($arg)
	       AND @file_exists($f = _DIR_TMP."forum_$alea.lck"))
	  {};
	spip_touch ($f);

# et maintenant on purge les locks de forums ouverts depuis > 4 h

	if ($dh = @opendir(_DIR_TMP))
		while (($file = @readdir($dh)) !== false)
			if (preg_match('/^forum_([0-9]+)\.lck$/', $file)
			AND (time()-@filemtime(_DIR_TMP.$file) > 4*3600))
				spip_unlink(_DIR_TMP.$file);
	return $alea;
}
?>