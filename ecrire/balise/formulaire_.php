<?php
/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

include_spip('inc/filtres');

function protege_champ($texte){
	$texte = entites_html($texte);
	$texte = str_replace("'","&#39;",$texte);
	return $texte;
}

/* prendre en charge par defaut les balises formulaires simples */
// http://doc.spip.org/@balise_FORMULAIRE__dist
function balise_FORMULAIRE__dist($p) {
	return calculer_balise_dynamique($p, $p->nom_champ, array());
}

/* prendre en charge par defaut les balises dynamiques formulaires simples */
// http://doc.spip.org/@balise_FORMULAIRE__dyn
function balise_FORMULAIRE__dyn($form)
{
	// recuperer les arguments passes a la balise
	$args = func_get_args();


	// deux moyen d'arriver ici : soit #FORMULAIRE_XX reroute avec 'FORMULAIRE_XX' ajoute en premier arg
	// soit #FORMULAIRE_{xx}
	if (substr($form,0,11)=="FORMULAIRE_")
		$form = strtolower(substr($form,11));
	else 
		$form = strtolower($form);	
		
	// on enleve le premier qui est le nom de la balise et deja recupere ci-dessus
	array_shift($args);

	if (!find_in_path("formulaires/$form.html"))
		return '';

	// tester si ce formulaire vient d'etre poste (memes arguments)
	// pour ne pas confondre 2 #FORMULAIRES_XX identiques sur une meme page
	$je_suis_poste = false;
	if ($post_form = _request('formulaire_action')
	AND $post_args = _request('formulaire_action_args')) {
		$post_args = decoder_contexte_ajax($post_args,$post_form);
		if ($args === $post_args){
			$je_suis_poste = true;
		}
	}

	// init
	$erreurs = $valeurs = array();
	$message_ok = $message_erreur = "";
	$editable = true;

	// si le formulaire vient d'etre poste, on recupere les erreurs
	if ($je_suis_poste){
		$post = traiter_formulaires_dynamiques(true);
		$erreurs = isset($post["erreurs_$form"])?$post["erreurs_$form"]:array();
		$message_ok = "";
		if (isset($post["message_ok_$form"]))
			$message_ok = $post["message_ok_$form"];
		elseif(isset($erreurs['message_ok']))
			$message_ok = $erreurs["message_ok"];
		$message_erreur = isset($erreurs['message_erreur'])?$erreurs['message_erreur']:"";
		$editable = (!isset($post["erreurs_$form"])) || count($erreurs) || 
			(isset($post["editable_$form"]) && $post["editable_$form"]);
	}

	if ($charger_valeurs = charger_fonction("charger","formulaires/$form/",true))
		$valeurs = call_user_func_array($charger_valeurs,$args);
	$valeurs = pipeline(
		'formulaire_charger',
		array(
			'args'=>array('form'=>$form,'args'=>$args),
			'data'=>$valeurs)
	);
	
	// si $valeurs===false, alors le formulaire n'est pas applicable
	// on n'affiche rien. C'est plus fort qu'editable qui est geree
	// par le squelette du formulaire lui meme
	if ($valeurs===false) return '';
	
	
	// reperer les valeurs particulieres editable, message_ok et message_erreur
	if (isset($valeurs['editable'])){
		$editable = $valeurs['editable'];
		unset($valeurs['editable']);
	}
	foreach(array('message_ok','message_erreur') as $k){
		if (isset($valeurs[$k])){
			if (!$je_suis_poste) $$k = $valeurs[$k];
			unset($valeurs[$k]);
		}
	}
	
	// charger peut passer une action si le formulaire ne tourne pas sur self()
	// ou une action vide si elle ne sert pas
	$action = isset($valeurs['action'])?$valeurs['action']:self();
	
	// recuperer la saisie en cours si erreurs
	// seulement si c'est ce formulaire qui est poste
	// ou si on le demande explicitement par le parametre _forcer_request = true
	foreach(array_keys($valeurs) as $champ){
		if (substr($champ,0,1)!=='_'){
			if ($je_suis_poste || (isset($valeurs['_forcer_request']) && $valeurs['_forcer_request'])) {
						if (($v = _request($champ))!==NULL)
							$valeurs[$champ] = $v;
			}
			if ($action)
				$action = parametre_url($action,$champ,''); // nettoyer l'url des champs qui vont etre saisis
			// proteger les ' et les " dans les champs que l'on va injecter
			if (is_string($valeurs[$champ]))
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

	return array("formulaires/$form",
		3600,
		array_merge(
			$valeurs,
			array(
			'form' => $form,
			'action' => $action,
			'formulaire_args' => encoder_contexte_ajax($args,$form),
			'id' => isset($valeurs['id'])?$valeurs['id']:'new',
			'erreurs' => $erreurs,
			'message_ok' => $message_ok,
			'message_erreur' => $message_erreur,
			'editable' => $editable?' ':'',
			)
		)
	);
}

?>
