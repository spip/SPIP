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


if (!defined("_ECRIRE_INC_VERSION")) return;

function traiter_appels_actions(){
	// cas de l'appel qui renvoie une redirection (302) ou rien (204)
	if ($action = _request('action')) {
		include_spip('inc/autoriser');
		include_spip('inc/headers');
		$url = _request('redirect');
		$var_f = charger_fonction($action, 'action');
		$var_f();
		if (isset($GLOBALS['redirect'])
		OR $GLOBALS['redirect'] = _request('redirect')) {
			redirige_par_entete($GLOBALS['redirect']);
		}
		if (!headers_sent()
			AND !ob_get_length())
				http_status(204); // No Content
		return true;
	}
	return false;
}


// http://doc.spip.org/@refuser_traiter_formulaire_ajax
function refuser_traiter_formulaire_ajax(){
	if ($v=_request('var_ajax')
	  AND $v=='form'
		AND $form = _request('formulaire_action')
		AND $args = _request('formulaire_action_args')
		AND decoder_contexte_ajax($args,$form)!==false) {
		// on est bien dans le contexte de traitement d'un formulaire en ajax
		// mais traiter ne veut pas
		// on le dit a la page qui va resumbit
		// sans ajax
		include_spip('inc/actions');
		ajax_retour('noajax',false);
		exit;
	}
}

function traiter_appels_inclusions_ajax(){
	// traiter les appels de bloc ajax (ex: pagination)
	if ($v = _request('var_ajax')
	AND $v !== 'form'
	AND $args = _request('var_ajax_env')) {
		include_spip('inc/filtres');
		include_spip('inc/actions');
		if ($args = decoder_contexte_ajax($args)
		AND $fond = $args['fond']) {
			include_spip('public/parametrer');
			$contexte = calculer_contexte();
			$contexte = array_merge($args, $contexte);
			$page = evaluer_fond($fond,$contexte);
			$texte = $page['texte'];
		} 
		else 
			$texte = _L('signature ajax bloc incorrecte');
		ajax_retour($page['texte']);
		return true; // on a fini le hit
	}
	return false;	
}

// au 1er appel, traite les formulaires dynamiques charger/verifier/traiter
// au 2e se sachant 2e, retourne les messages et erreurs stockes au 1er
// Le 1er renvoie True si il faut faire exit a la sortie

// http://doc.spip.org/@traiter_formulaires_dynamiques
function traiter_formulaires_dynamiques($get=false){
	static $post = array();
	static $done = false;

	if ($get) return $post; 
	if ($done) return false;
	$done = true;

	if (!($form = _request('formulaire_action')
	AND $args = _request('formulaire_action_args')))
		return false; // le hit peut continuer normalement

	include_spip('inc/filtres');
	if (($args = decoder_contexte_ajax($args,$form))===false) {
		include_spip('inc/actions');
		spip_log("signature ajax form incorrecte : $form");
		return false; // continuons le hit comme si de rien etait
	} else {
		$verifier = charger_fonction("verifier","formulaires/$form/",true);
		$post["erreurs_$form"] = pipeline(
				  'formulaire_verifier',
					array(
						'args'=>array('form'=>$form,'args'=>$args),
						'data'=>$verifier?call_user_func_array($verifier,$args):array())
					);
		if ((count($post["erreurs_$form"])==0)){
			$rev = "";
			if ($traiter = charger_fonction("traiter","formulaires/$form/",true))
				$rev = call_user_func_array($traiter,$args);

			$rev = pipeline(
				  'formulaire_traiter',
					array(
						'args'=>array('form'=>$form,'args'=>$args),
						'data'=>$rev)
					);
					// traiter peut retourner soit un message, soit un array(editable,message)
					if (is_array($rev)) {
						$post["editable_$form"] = reset($rev);
						$post["message_ok_$form"] = end($rev);
					} else
						$post["message_ok_$form"] = $rev;
		}
		// si le formulaire a ete soumis en ajax, on le renvoie direct !
		if (_request('var_ajax')){
			if (find_in_path('formulaire_.php','balise/',true)) {
				include_spip('inc/actions');
				array_unshift($args,$form);
				ajax_retour(inclure_balise_dynamique(call_user_func_array('balise_formulaire__dyn',$args),false),false);
				return true; // on a fini le hit
			}
		}
	}
	return false; // le hit peut continuer normalement
}

?>