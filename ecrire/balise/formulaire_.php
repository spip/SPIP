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

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

include_spip('inc/filtres');

/* prendre en charge par defaut les balises formulaires simples */
// http://doc.spip.org/@balise_FORMULAIRE__dist
function balise_FORMULAIRE__dist($p) {
	preg_match(",^FORMULAIRE_(.*)?$,", $p->nom_champ, $regs);
	if (!strlen($form = $regs[1])){
		//$form = interprete_argument_balise(1,$p);
	}
	
	return calculer_balise_dynamique($p,"FORMULAIRE_$form",array());
}

// http://doc.spip.org/@protege_valeurs
function protege_valeurs($valeur){
	return is_string($valeur)?entites_html($valeur):$valeur;
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

	$erreurs = isset($_POST["erreurs_$form"])?$_POST["erreurs_$form"]:array();
	$message_ok = isset($_POST["message_ok_$form"])?$_POST["message_ok_$form"]:"";
	$message_erreur = isset($erreurs['message_erreur'])?$erreurs['message_erreur']:"";
	$valeurs = array();
	$editable = (!isset($_POST["erreurs_$form"])) || count($erreurs) || 
		(isset($_POST["editable_$form"]) && $_POST["editable_$form"]);

	$valeurs = array();
	if ($charger_valeurs = charger_fonction("charger","formulaires/$form/",true))
		$valeurs = call_user_func_array($charger_valeurs,$args);
	if ($valeurs===false) {
		// pas de saisie
		$editable = false;
		$valeurs = array();
	}
	elseif(
		is_array($valeurs)
	 && ($keys = array_keys($valeurs))
	 && (is_numeric(reset($keys)) && is_numeric(end($keys)))
	 && ((reset($valeurs)===true) OR (reset($valeurs)===false))
	 && (count($valeurs)==2)) {
	 $editable = reset($valeurs);
	 $valeurs = end($valeurs);
	}

	$action = self();
	// recuperer la saisie en cours si erreurs
	foreach(array_keys($valeurs) as $champ){
		if (substr($champ,0,1)!=='_'){
			if (($v = _request($champ))!==NULL)
				$valeurs[$champ] = $v;
			$action = parametre_url($action,$champ,''); // nettoyer l'url des champs qui vont etre saisis
			$valeurs[$champ] = protege_valeurs($valeurs[$champ]); // proteger les ' et les " dans les champs que l'on va injecter dans les input
		}
	}
	$action = parametre_url($action,'formulaire_action',''); // nettoyer l'url des champs qui vont etre saisis
	$action = parametre_url($action,'formulaire_action_cle',''); // nettoyer l'url des champs qui vont etre saisis
	$action = parametre_url($action,'formulaire_action_args',''); // nettoyer l'url des champs qui vont etre saisis

	if (isset($valeurs['_action'])){
		$securiser_action = charger_fonction('securiser_action','inc');
		$secu = inc_securiser_action_dist(reset($valeurs['_action']),end($valeurs['_action']),'',-1);
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
			'formulaire_args' => base64_encode(serialize($args)),
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