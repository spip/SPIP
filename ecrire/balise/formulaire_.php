<?php


/* prendre en charge par defaut les balises formulaires simples */
// http://doc.spip.org/@balise_FORMULAIRE__dist
function balise_FORMULAIRE__dist($p) {
	preg_match(",^FORMULAIRE_(.*)?$,", $p->nom_champ, $regs);
	$form = $regs[1];
	
	return calculer_balise_dynamique($p,"FORMULAIRE_$form",array());
}


/* prendre en charge par defaut les balises dynamiques formulaires simples */
// http://doc.spip.org/@balise_FORMULAIRE__dyn
function balise_FORMULAIRE__dyn($form)
{
	$form = strtolower(substr($form,11));
	
	// recuperer les arguments passes a la balise
	$args = func_get_args();
	// en enlevant le premier qui est le nom de la balise et deja recuperer ci-dessus
	array_shift($args);
	
	$erreurs = isset($_POST["erreurs_$form"])?$_POST["erreurs_$form"]:array();
	$message_ok = isset($_POST["message_ok_$form"])?$_POST["message_ok_$form"]:"";
	$message_erreur = isset($erreurs['message_erreur'])?$erreurs['message_erreur']:"";
	$valeurs = array();
	$editable = (!isset($_POST["erreurs_$form"])) || count($erreurs);

	if ($charger_valeurs = charger_fonction("charger","formulaires/$form/",true))
		$valeurs = call_user_func_array($charger_valeurs,$args);
	if ($valeurs===false) {
		// pas de saisie
		$editable = false;
		$valeurs = array();
	}

	$action = self();
	// recuperer la saisie en cours si erreurs
	foreach(array_keys($valeurs) as $champ){
		if ($v = _request($champ))
			$valeurs[$champ] = $v;
		$action = parametre_url($action,$champ,''); // nettoyer l'url des champs qui vont etre saisis
	}
	$action = parametre_url($action,'formulaire_action',''); // nettoyer l'url des champs qui vont etre saisis
	$action = parametre_url($action,'formulaire_action_cle',''); // nettoyer l'url des champs qui vont etre saisis
	$action = parametre_url($action,'formulaire_action_args',''); // nettoyer l'url des champs qui vont etre saisis

	return array("formulaires/$form", 0, 
		array_merge($valeurs,
		array(
			'action' => $action,
			'formulaire_args' => base64_encode(serialize($args)),
			'redirect' => '',
			'id' => isset($valeurs['id'])?$valeurs['id']:'new',
			'erreurs' => $erreurs,
			'message_ok' => $message_ok,
			'message_erreur' => $message_erreur,
			'form' => $form,
			'editable' => $editable,
		))
	);
}

?>