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

include_spip('inc/filtres');

function protege_champ($texte){
	if (is_array($texte))
		$texte = array_map('protege_champ',$texte);
	else {
		// ne pas corrompre une valeur serialize
		if ((preg_match(",^[abis]:\d+[:;],", $texte) AND unserialize($texte)!=false) OR is_null($texte))
			return $texte;
		$texte = entites_html($texte);
		$texte = str_replace("'","&#39;",$texte);
	}
	return $texte;
}

function existe_formulaire($form)
{
	if (substr($form,0,11)=="FORMULAIRE_")
		$form = strtolower(substr($form,11));
	else 
		$form = strtolower($form);

	if (!$form) return ''; // on ne sait pas, le nom du formulaire n'est pas fourni ici

	return find_in_path($form.'.' . _EXTENSION_SQUELETTES, 'formulaires/') ? $form : false;
}


/* prendre en charge par defaut les balises formulaires simples */
// http://doc.spip.org/@balise_FORMULAIRE__dist
function balise_FORMULAIRE__dist($p) {

	// Cas d'un #FORMULAIRE_TOTO inexistant : renvoyer la chaine vide.
	// mais si #FORMULAIRE_{toto} on ne peut pas savoir a la compilation, continuer
	if (existe_formulaire($p->nom_champ)===FALSE) {
		    $p->code = "''";
		    $p->interdire_scripts = false;
		    return $p;
	}

	// sinon renvoyer un code php dnamique
	return calculer_balise_dynamique($p, $p->nom_champ, array());
}

/* prendre en charge par defaut les balises dynamiques formulaires simples */
// http://doc.spip.org/@balise_FORMULAIRE__dyn
function balise_FORMULAIRE__dyn($form)
{
	$form = existe_formulaire($form);
	if (!$form) return '';

	// deux moyen d'arriver ici : 
	// soit #FORMULAIRE_XX reroute avec 'FORMULAIRE_XX' ajoute en premier arg
	// soit #FORMULAIRE_{xx}

	// recuperer les arguments passes a la balise
	// on enleve le premier qui est le nom de la balise 
	// deja recupere ci-dessus

	$args = func_get_args();
	array_shift($args);
	$contexte = balise_FORMULAIRE__contexte($form, $args);
	if (!is_array($contexte)) return $contexte;
	return array("formulaires/$form", 3600, $contexte);
}

function balise_FORMULAIRE__contexte($form, $args)
{
	// tester si ce formulaire vient d'etre poste (memes arguments)
	// pour ne pas confondre 2 #FORMULAIRES_XX identiques sur une meme page
	// si poste, on recupere les erreurs

	$je_suis_poste = false;
	if ($post_form = _request('formulaire_action')
	 AND $post_form == $form
	 AND $p = _request('formulaire_action_args')
	 AND is_array($p = decoder_contexte_ajax($p, $post_form))) {
		// enlever le faux attribut de langue masque
		array_shift($p);
		if (formulaire__identifier($form, $args, $p))
			$je_suis_poste = true;
	}

	$editable = true;
	$erreurs = $post = array();
	if ($je_suis_poste) {
		$post = traiter_formulaires_dynamiques(true);
		$e = "erreurs_$form";
		$erreurs = isset($post[$e]) ? $post[$e] : array();
		$editable = "editable_$form";
		$editable = (!isset($post[$e]))
			  || count($erreurs)
			  || (isset($post[$editable]) && $post[$editable]);
	}

	$valeurs = formulaire__charger($form, $args, $je_suis_poste);

	// si $valeurs n'est pas un tableau, le formulaire n'est pas applicable
	// C'est plus fort qu'editable qui est gere par le squelette 
	// Idealement $valeur doit etre alors un message explicatif.
	if (!is_array($valeurs)) return is_string($valeurs) ? $valeurs : '';
	
	// charger peut passer une action si le formulaire ne tourne pas sur self()
	// ou une action vide si elle ne sert pas
	$action = (isset($valeurs['action'])) ? $valeurs['action'] : self('&amp;', true);
	// bug IEx : si action finit par / 
	// IE croit que le <form ... action=../ > est autoferme
	if (substr($action,-1)=='/') {
		// on ajoute une ancre pour feinter IE, au pire ca tue l'ancre qui finit par un /
		$action .= '#';
	}

	// recuperer la saisie en cours si erreurs
	// seulement si c'est ce formulaire qui est poste
	// ou si on le demande explicitement par le parametre _forcer_request = true
	$dispo = ($je_suis_poste || (isset($valeurs['_forcer_request']) && $valeurs['_forcer_request']));
	foreach(array_keys($valeurs) as $champ){
		if ($champ[0]!=='_' AND !in_array($champ, array('message_ok','message_erreur','editable'))) {
			if ($dispo AND (($v = _request($champ))!==NULL))
				$valeurs[$champ] = $v;
			if ($action)
				$action = parametre_url($action,$champ,'');
			// nettoyer l'url des champs qui vont etre saisis
			// proteger les ' et les " dans les champs que l'on va injecter
			$valeurs[$champ] = protege_champ($valeurs[$champ]);
		}
	}

	if ($action) {
		// nettoyer l'url
		$action = parametre_url($action,'formulaire_action','');
		$action = parametre_url($action,'formulaire_action_args','');
	}

	if (isset($valeurs['_action'])){
		$securiser_action = charger_fonction('securiser_action','inc');
		$secu = $securiser_action(reset($valeurs['_action']),end($valeurs['_action']),'',-1);
		$valeurs['_hidden'] = (isset($valeurs['_hidden'])?$valeurs['_hidden']:'') .
		"<input type='hidden' name='arg' value='".$secu['arg']."' />"
		. "<input type='hidden' name='hash' value='".$secu['hash']."' />";
	}

	// empiler la lang en tant que premier argument implicite du CVT
	// pour permettre de la restaurer au moment du Verifier et du Traiter
	array_unshift($args, $GLOBALS['spip_lang']);

	$valeurs['formulaire_args'] = encoder_contexte_ajax($args, $form);
	$valeurs['erreurs'] = $erreurs;
	$valeurs['action'] = $action;
	$valeurs['form'] = $form;

	if (!isset($valeurs['id'])) $valeurs['id'] = 'new';
	// editable peut venir de charger() ou de traiter() sinon
	if (!isset($valeurs['editable'])) $valeurs['editable'] = $editable;
	// dans tous les cas, renvoyer un espace ou vide (et pas un booleen)
	$valeurs['editable'] = ($valeurs['editable']?' ':'');

	if ($je_suis_poste) {
		$valeurs['message_erreur'] = "";
		if (isset($erreurs['message_erreur']))
			$valeurs['message_erreur'] = $erreurs['message_erreur'];

		$valeurs['message_ok'] = "";
		if (isset($post["message_ok_$form"]))
			$valeurs['message_ok'] = $post["message_ok_$form"];
		elseif (isset($erreurs['message_ok']))
			$valeurs['message_ok'] = $erreurs["message_ok"];
	}
	
	return $valeurs;
}

/**
 * Charger les valeurs de saisie du formulaire
 *
 * @param string $form
 * @param array $args
 * @param bool $poste
 * @return array
 */
function formulaire__charger($form, $args, $poste)
{
	if ($charger_valeurs = charger_fonction("charger","formulaires/$form",true))
		$valeurs = call_user_func_array($charger_valeurs,$args);
	else $valeurs = array();

	$valeurs = pipeline(
		'formulaire_charger',
		array(
			'args'=>array('form'=>$form,'args'=>$args,'je_suis_poste'=>$poste),
			'data'=>$valeurs)
	);
	return $valeurs;
}

/**
 * Verifier que le formulaire en cours est celui qui est poste
 * on se base sur la fonction identifier (si elle existe) qui fournit
 * une signature identifiant le formulaire a partir de ses arguments
 * significatifs
 *
 * En l'absence de fonction identifier, on se base sur l'egalite des
 * arguments, ce qui fonctionne dans les cas simples
 *
 * @param string $form
 * @param array $args
 * @param array $p
 * @return bool
 */
function formulaire__identifier($form, $args, $p) {
	if ($identifier_args = charger_fonction("identifier","formulaires/$form",true)) {
		return call_user_func_array($identifier_args,$args)===call_user_func_array($identifier_args,$p);
	}
	return $args===$p;
}
?>
