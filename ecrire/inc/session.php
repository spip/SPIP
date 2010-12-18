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

/*
 * Gestion de l'authentification par sessions
 * a utiliser pour valider l'acces (bloquant)
 * ou pour reconnaitre un utilisateur (non bloquant)
 *
 */

$GLOBALS['visiteur_session'] = ''; # globale decrivant l'auteur
$GLOBALS['rejoue_session'] = ''; # globale pour insertion de JS en fin de page

//
// 3 actions sur les sessions, selon le type de l'argument:
//
// - numerique: efface toutes les sessions de l'auteur (retour quelconque)
// - tableau: cree une session pour l'auteur decrit et retourne l'identifiant
// - autre: predicat de validite de la session indiquee par le cookie

// http://doc.spip.org/@inc_session_dist
function inc_session_dist($auteur=false)
{
	if (is_numeric($auteur))
		return supprimer_sessions($auteur, $auteur > 0);
	else if (is_array($auteur))
		return ajouter_session($auteur);
	else
		return verifier_session($auteur);
}

//
// Ajoute une session pour l'auteur decrit par un tableau issu d'un SELECT-SQL
//

// http://doc.spip.org/@ajouter_session
function ajouter_session($auteur) {
	// Si le client a deja une session valide pour son id_auteur
	// on conserve le meme fichier

	// Attention un visiteur peut avoir une session et un id=0,
	// => ne pas melanger les sessions des differents visiteurs
	$auteur['id_auteur'] = intval($auteur['id_auteur']);
	if (!isset($_COOKIE['spip_session'])
	OR !preg_match(',^'.$auteur['id_auteur'].'_,', $_COOKIE['spip_session']))
		$_COOKIE['spip_session'] = $auteur['id_auteur'].'_'.md5(uniqid(rand(),true));

	$fichier_session = fichier_session('alea_ephemere');

	if (!isset($auteur['hash_env'])) $auteur['hash_env'] = hash_env();
	if (!isset($auteur['ip_change'])) $auteur['ip_change'] = false;

	if (!ecrire_fichier_session($fichier_session, $auteur)) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	} else {
		include_spip('inc/cookie');
		$duree = _RENOUVELLE_ALEA *
		  (!isset($auteur['cookie'])
		  	? 2 : (is_numeric($auteur['cookie'])
				? $auteur['cookie'] : 20));
		spip_setcookie(
			'spip_session',
			$_COOKIE['spip_session'],
			time() + $duree
			);
		spip_log("ajoute session $fichier_session cookie $duree");
		return $_COOKIE['spip_session'];
	}
}

// Ajouter une donnee dans la session SPIP
// http://doc.spip.org/@session_set
function session_set($nom, $val=null) {
	$GLOBALS['visiteur_session'][$nom] = $val;
	ajouter_session($GLOBALS['visiteur_session']);
	actualiser_sessions($GLOBALS['visiteur_session']);
}

// Lire une valeur dans la session SPIP
// http://doc.spip.org/@session_get
function session_get($nom) {
	return $GLOBALS['visiteur_session'][$nom];
}

// Quand on modifie une fiche auteur on appelle cette fonction qui va
// mettre a jour les fichiers de session de l'auteur en question.
// (auteurs identifies seulement)
// http://doc.spip.org/@actualiser_sessions
function actualiser_sessions($auteur) {
	if (!intval($auteur['id_auteur']))
		return;

	// memoriser l'auteur courant (celui qui modifie la fiche)
	$sauve = $GLOBALS['visiteur_session'];

	// .. mettre a jour les sessions de l'auteur cible
	foreach(preg_files(_DIR_SESSIONS, '/'.$id_auteur.'_.*\.php') as $session) {
		$GLOBALS['visiteur_session'] = array();
		include $session; # $GLOBALS['visiteur_session'] est alors l'auteur cible

		$auteur = array_merge($GLOBALS['visiteur_session'], $auteur);
		ecrire_fichier_session($session, $auteur);
	}

	// restaurer l'auteur courant
	$GLOBALS['visiteur_session'] = $sauve;

	// si c'est le meme, rafraichir les valeurs
	if ($auteur['id_auteur'] == $sauve['id_auteur'])
		verifier_session();
}

// http://doc.spip.org/@ecrire_fichier_session
function ecrire_fichier_session($fichier, $auteur) {

	// ne pas enregistrer ces elements de securite
	// dans le fichier de session
	unset($auteur['pass']);
	unset($auteur['htpass']);
	unset($auteur['low_sec']);
	unset($auteur['alea_actuel']);
	unset($auteur['alea_futur']);

	// enregistrer les autres donnees du visiteur
	$texte = "<"."?php\n";
	foreach ($auteur as $var => $val)
		$texte .= '$GLOBALS[\'visiteur_session\'][\''.$var.'\'] = '
			. var_export($val,true).";\n";
	$texte .= "?".">\n";

	return ecrire_fichier($fichier, $texte);
}

//
// Cette fonction efface toutes les sessions appartenant a l'auteur
// On en profite pour effacer toutes les sessions
// creees il y a plus de 4*_RENOUVELLE_ALEA
// Tenir compte de l'ancien format ou les noms commencaient par "session_"
// et du meme coup des repertoires plats

// http://doc.spip.org/@supprimer_sessions
function supprimer_sessions($id_auteur, $toutes=true) {

	if ($toutes) {
		$dir = opendir(_DIR_SESSIONS);
		$t = time()  - (4*_RENOUVELLE_ALEA);
		while(($f = readdir($dir)) !== false) {
			if (preg_match(",^\D*(\d+)_\w{32}\.php[3]?$,", $f, $regs)){
				$f = _DIR_SESSIONS . $f;
				if (($regs[1] == $id_auteur) OR ($t > filemtime($f)))
					spip_unlink($f);
			}
		}
	}
	else {
		verifier_session();
		spip_unlink(fichier_session('alea_ephemere', true));
	}

	// forcer le recalcul de la session courante
	spip_session(true);
}

//
// Verifie si le cookie spip_session indique une session valide.
// Si oui, la decrit dans le tableau $visiteur_session et retourne id_auteur
// La rejoue si IP change puis accepte le changement si $change=true
//

// http://doc.spip.org/@verifier_session
function verifier_session($change=false) {
	// si pas de cookie, c'est fichu

	if (!isset($_COOKIE['spip_session']))
		return false;

	// Tester avec alea courant
	$fichier_session = fichier_session('alea_ephemere', true);

	if ($fichier_session AND @file_exists($fichier_session)) {
		include($fichier_session);
	} else {
		// Sinon, tester avec alea precedent
		$fichier_session = fichier_session('alea_ephemere_ancien', true);
		if (!$fichier_session OR !@file_exists($fichier_session)) return false;

		// Renouveler la session avec l'alea courant
		include($fichier_session);
		spip_unlink($fichier_session);
		ajouter_session($GLOBALS['visiteur_session']);
	}

	// Compatibilite ascendante : auteur_session est visiteur_session si
	// c'est un auteur SPIP authentifie (tandis qu'un visiteur_session peut
	// n'etre qu'identifie, sans aucune authentification).

	if ($GLOBALS['visiteur_session']['id_auteur'])
		$GLOBALS['auteur_session'] = &$GLOBALS['visiteur_session'];

	// Si l'adresse IP change, inc/presentation mettra une balise image
	// avec un URL de rappel demandant a changer le nom de la session.
	// Seul celui qui a l'IP d'origine est rejoue
	// ainsi un eventuel voleur de cookie ne pourrait pas deconnecter
	// sa victime, mais se ferait deconnecter par elle.
	if (hash_env() != $GLOBALS['visiteur_session']['hash_env']) {
		if (!$GLOBALS['visiteur_session']['ip_change']) {
			$GLOBALS['rejoue_session'] = rejouer_session();
			$GLOBALS['visiteur_session']['ip_change'] = true;
			ajouter_session($GLOBALS['visiteur_session']);
		} else if ($change) {
			spip_log("session non rejouee, vol de cookie ?");
		}
	} else if ($change) {
		spip_log("rejoue session $fichier_session ".$_COOKIE['spip_session']);
		spip_unlink($fichier_session);
		$GLOBALS['visiteur_session']['ip_change'] = false;
		unset($_COOKIE['spip_session']);
		ajouter_session($GLOBALS['visiteur_session']);
	}

	return is_numeric($GLOBALS['visiteur_session']['id_auteur'])?$GLOBALS['visiteur_session']['id_auteur']:null;
}

// Code a inserer par inc/presentation pour rejouer la session
// Voir action/cookie qui sera appele.

// http://doc.spip.org/@rejouer_session
function rejouer_session()
{
	include_spip('inc/filtres');
	return	  http_img_pack('rien.gif', " ", "id='img_session' width='0' height='0'") .
		  http_script("\ndocument.img_session.src='" . generer_url_action('cookie','change_session=oui', true) .  "'");
}

//
// Calcule le nom du fichier session
//
// http://doc.spip.org/@fichier_session
function fichier_session($alea, $tantpis=false) {

	if (!isset($GLOBALS['meta'][$alea])) {
		include_spip('base/abstract_sql');
		$GLOBALS['meta'][$alea]  = sql_getfetsel('valeur', 'spip_meta', "nom=" . sql_quote($alea), '','', '', '', '', 'continue');
	}

	if (!$GLOBALS['meta'][$alea] AND !$tantpis) {
		include_spip('inc/minipres');
		echo minipres();
	} else {

		$repertoire = _DIR_SESSIONS;
		if(!@file_exists($repertoire)) {
			if ($tantpis) return '';
			$repertoire = preg_replace(','._DIR_TMP.',', '', $repertoire);
			include_spip('inc/flock');
			$repertoire = sous_repertoire(_DIR_TMP, $repertoire);
		}
		$c = $_COOKIE['spip_session'];
		return $repertoire . intval($c) .'_' . md5($c.' '.$GLOBALS['meta'][$alea]). '.php';
	}
}

//
// On verifie l'IP et le nom du navigateur
//

// http://doc.spip.org/@hash_env
function hash_env() {
  static $res ='';
  if ($res) return $res;
  return $res = md5($GLOBALS['ip'] . $_SERVER['HTTP_USER_AGENT']);
}

?>
