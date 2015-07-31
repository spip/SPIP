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

function formulaires_configurer_multilinguisme_charger_dist(){
	foreach(array('multi_articles','multi_rubriques','multi_secteurs','gerer_trad') as $m)
		$valeurs[$m] = $GLOBALS['meta'][$m];

	if ($GLOBALS['meta']['multi_articles'] == "oui"
	  OR $GLOBALS['meta']['multi_rubriques'] == "oui"
	  OR count(explode(',',$GLOBALS['meta']['langues_utilisees'])) > 1) {

		$selection = (is_null(_request('multi_articles'))?explode(',', $GLOBALS['meta']['langues_multilingue']):_request('langues_auth'));
		$valeurs['_langues'] = saisie_langues_utiles('langues_auth',$selection?$selection:array());
	}

	return $valeurs;
}


function formulaires_configurer_multilinguisme_traiter_dist(){
	$res = array('editable'=>true);
	foreach(array('multi_articles','multi_rubriques','multi_secteurs','gerer_trad') as $m)
		if (!is_null($v=_request($m)))
			ecrire_meta($m, $v=='oui'?'oui':'non');

	if ($i = _request('langues_auth') AND is_array($i)) {
		$i = array_unique(array_merge($i,explode(',',$GLOBALS['meta']['langues_utilisees'])));
		ecrire_meta('langues_multilingue', implode(",",$i));
	}
	$res['message_ok'] = _T('config_info_enregistree');
	return $res;
}

function saisie_langues_utiles($name, $selection) {
	include_spip('inc/lang_liste');
	$langues = $GLOBALS['codes_langues'];
	
	$langues_installees = explode(',', $GLOBALS['meta']['langues_proposees']);
	$langues_trad = array_flip($langues_installees);

	$langues_bloquees = explode(',',$GLOBALS['meta']['langues_utilisees']);

	$res = "";

	$i = 0;
	foreach($langues_bloquees as $code_langue) {
		$nom_langue = $langues[$code_langue];
		$res .= "<li class='choix "
		  . alterner($i++,'odd','even')
		  . (isset($langues_trad[$code_langue])?" traduite":"")
		  . "'>"
			. "<input type='hidden' name='{$name}[]' value='$code_langue'>" // necessaire ...
		  . "<input type='checkbox' name='{$name}[]' id='{$name}_$code_langue' value='$code_langue' checked='checked' disabled='disabled' />"
		  . "<label for='{$name}_$code_langue'>" . $nom_langue ."&nbsp;&nbsp; <span class='code_langue'>[$code_langue]</span></label>"
		  . "</li>";
	}

	if ($res)
		$res = "<ul id='langues_bloquees'>".$res."</ul><div class='nettoyeur'></div>";

	$res .= "<ul id='langues_proposees'>";

	$i = 0;
	$langues_bloquees = array_flip($langues_bloquees);
	foreach($langues as $code_langue=>$nom_langue) {
		if (!isset($langues_bloquees[$code_langue])) {
			$checked = (in_array($code_langue,$selection)?' checked="checked"':'');
			$res .= "<li class='choix "
			  . alterner($i++,'odd','even')
				. (isset($langues_trad[$code_langue])?" traduite":"")
				. "'>"
				. "<input type='checkbox' name='{$name}[]' id='{$name}_$code_langue' value='$code_langue'"
			  . $checked
			  . "/>"
				. "<label for='{$name}_$code_langue'"
			  . ($checked?" class='on'":"")
			  . ">"
			  . $nom_langue ."&nbsp;&nbsp; <span class='code_langue'>[$code_langue]</span></label>"
				. "</li>";
		}
	}

	$res .= "</ul><div class='nettoyeur'></div>";
	return $res;
}
?>
