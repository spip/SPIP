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


// http://doc.spip.org/@generer_action_auteur
function generer_action_auteur($action, $arg, $redirect="", $mode=false, $att='', $public=false)
{
	$securiser_action = charger_fonction('securiser_action', 'inc');
	return $securiser_action($action, $arg, $redirect, $mode, $att, $public);
}

// http://doc.spip.org/@redirige_action_auteur
function redirige_action_auteur($action, $arg, $ret, $gra='', $mode=false, $atts='') {

	$r = _DIR_RESTREINT . generer_url_ecrire($ret, $gra, true, true);

	return generer_action_auteur($action, $arg, $r, $mode, $atts);
}

// http://doc.spip.org/@redirige_action_post
function redirige_action_post($action, $arg, $ret, $gra, $corps, $att='') {
	$r = _DIR_RESTREINT . generer_url_ecrire($ret, $gra, false, true);
	return generer_action_auteur($action, $arg, $r, $corps, $att . " method='post'");
}

// Retourne un formulaire d'execution de $action sur $id,
// revenant a l'envoyeur $script d'arguments $args.
// Utilise Ajax si dispo, en ecrivant le resultat dans le innerHTML du noeud
// d'attribut  id = $action-$id (cf. AjaxSqueeze dans layer.js)

// http://doc.spip.org/@ajax_action_auteur
function ajax_action_auteur($action, $id, $script, $args='', $corps=false, $args_ajax='', $fct_ajax='')
{
	if (strpos($args,"#")===FALSE)
		$ancre = "$action-" . intval($id);
	else {
		$ancre = explode("#",$args);
		$args = $ancre[0];
		$ancre = $ancre[1];
	}

	// Formulaire (POST)
	// methodes traditionnelle et ajax a unifier...
	if (is_string($corps)) {

		// Methode traditionnelle
		if (_SPIP_AJAX !== 1) {
			return redirige_action_post($action,
				$id,
				$script,
				"$args#$ancre",
				$corps);
		}

		// Methode Ajax
		else {
			if ($args AND !$args_ajax) $args_ajax = "&$args";
			if (isset($_GET['var_profile']))
				$args_ajax .= '&var_profile=1';
			return redirige_action_post($action,
				$id,
				$action,
				"script=$script$args_ajax",
				$corps,
				(" onsubmit="
				 . ajax_action_declencheur('this', $ancre, $fct_ajax)));
				 
		}
	}

	// Lien (GET)
	else {
		$href = redirige_action_auteur($action,
			$id,
			$script,
			"$args#$ancre",
			false);

		if ($args AND !$args_ajax) $args_ajax = "&$args";
		if (isset($_GET['var_profile']))
			$args_ajax .= '&var_profile=1';

		$ajax = redirige_action_auteur($action,
			$id,
			$action,
			"script=$script$args_ajax");

		$cli = array_shift($corps);
		return "<a href='$href'\nonclick="
		.  ajax_action_declencheur($ajax, $ancre, $fct_ajax)
		. ">"
		. (!$corps ?  $cli : ("\n<span" . $corps[0] . ">$cli</span>"))
		. "</a>";
	}
}

// Comme ci-dessus, mais reduit au cas POST et on fournit le bouton Submit.
// 
// http://doc.spip.org/@ajax_action_post
function ajax_action_post($action, $arg, $retour, $gra, $corps, $clic='', $atts_i='', $atts_span = "", $args_ajax='')
{
	global $spip_lang_right;

	if (strpos($gra,"#")===FALSE) {
	  // A etudier: prendre systematiquement arg en trancodant les \W
		$n = intval($arg);
		$ancre = "$action-" . ($n ? $n : $arg);
	} else {
		$ancre = explode("#",$gra);
		$args = $ancre[0];
		$ancre = $ancre[1];
	}

	if (!$atts_i) 
		$atts_i = " style='float: $spip_lang_right'";

	if (is_array($clic)) {
		$submit = "";
		$atts_i .= "\nonclick='AjaxNamedSubmit(this)'";
		foreach($clic as $n => $c)
		  $submit .= "\n<input type='submit' name='$n' value='$c' $atts_i />";
	} else {
		if (!$clic)  $clic =  _T('bouton_valider');
		$submit = "<input type='submit' value='$clic' $atts_i />";
	}
	$corps = "<div>"
	  . $corps 
	  . "<span"
	  . $atts_span
	  . ">"
	  . $submit
	  . "</span></div>";

	if (_SPIP_AJAX !== 1) {
		return redirige_action_post($action, $arg, $retour,
					($gra . '#' . $ancre),
				        $corps);
	} else { 

		if ($gra AND !$args_ajax) $args_ajax = "&$gra";
		if (isset($GLOBALS['var_profile']))
			$args_ajax .= '&var_profile=1';

		return redirige_action_post($action,
			$arg,
			$action,
			"script=$retour$args_ajax",
			$corps,
			" onsubmit=" . ajax_action_declencheur('this', $ancre));
	}
}

//
// Attention pour que Safari puisse manipuler cet evenement
// il faut onsubmit="return AjaxSqueeze(x,'truc',...)"
// et non pas onsubmit='return AjaxSqueeze(x,"truc",...)'
//
// http://doc.spip.org/@ajax_action_declencheur
function ajax_action_declencheur($request, $noeud, $fct_ajax='') {
	if (strpos($request, 'this') !== 0) 
		$request = "'".$request."'";

	return '"return AjaxSqueeze('
	. $request
	. ",'"
	. $noeud
	. "',"
	  . ($fct_ajax ? $fct_ajax : "''")
	. ',event)"';
}

// Place un element HTML dans une div nommee,
// sauf si c'est un appel Ajax car alors la div y est deja 
// $fonction : denomination semantique du bloc, que l'on retouve en attribut class
// $id : id de l'objet concerne si il y a lieu ou "", sert a construire un identifiant unique au bloc ("fonction-id")
// http://doc.spip.org/@ajax_action_greffe
function ajax_action_greffe($fonction, $id, $corps)
{
	$idom = $fonction.(strlen($id)?"-$id":"");
	return _AJAX
		? "$corps"
		: "\n<div id='$idom' class='ajax-action $fonction'>$corps\n</div>\n";
}

// http://doc.spip.org/@ajax_retour
function ajax_retour($corps, $xml = true)
{
	if (isset($_COOKIE['spip_admin']) AND $GLOBALS['tableau_des_temps'])
		erreur_squelette();
	else { 
		if ($GLOBALS['exec'] == 'valider_xml') {
		 	$debut = _DOCTYPE_ECRIRE
			. "<html><head><title>Debug Spip Ajax</title></head>"
			.  "<body><div>\n\n"
			. "<!-- %%%%%%%%%%%%%%%%%%% Ajax %%%%%%%%%%%%%%%%%%% -->\n";

			$fin = '</div></body></html>';

		} else {
			$c = $GLOBALS['meta']["charset"];
			header('Content-Type: text/html; charset='. $c);
			$debut = (($xml AND strlen(trim($corps)))?'<' . "?xml version='1.0' encoding='" . $c . "'?" . ">\n":'');
			$fin = '';
		}
		echo $debut, $corps, $fin;
	}
}

// http://doc.spip.org/@determine_upload
function determine_upload($type='') {
	include_spip('inc/autoriser');
	
	if (!autoriser('chargerftp')
	OR $type == 'logos') # on ne le permet pas pour les logos
		return false;

	$repertoire = _DIR_TRANSFERT;
	if (!@is_dir($repertoire)) {
		$repertoire = str_replace(_DIR_TMP, '', $repertoire);
		$repertoire = sous_repertoire(_DIR_TMP, $repertoire);
	}

	if (!$GLOBALS['visiteur_session']['restreint'])
		return $repertoire;
	else
		return sous_repertoire($repertoire, $GLOBALS['visiteur_session']['login']);
}
?>
