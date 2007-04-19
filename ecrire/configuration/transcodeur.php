<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/lang');
include_spip('inc/presentation');

function configuration_transcodeur_dist()
{
	$charset = $GLOBALS['meta']["charset"];

	$res =  _T('texte_jeu_caractere') .
	  "<blockquote class='spip'>\n<p>"
	  . _T('texte_jeu_caractere_3') .
	  "</p>\n<div style='text-align: center'><b><tt>"
	  .entites_html($charset)
	  ."</tt></b></div><p>" .
	  _T('texte_jeu_caractere_4') .
	  " &nbsp; <input type='text' name='charset'
			value=\"".entites_html($charset)."\" />" .
	  "<br />\n(".
	  _T('texte_jeu_caractere_2').")" .
		"</p></blockquote>\n";

	// faudrait dire si le charset est inconnu
	// ca eviterait l'erreur sur array_flip dans inc/charsets
	if ($charset != 'utf-8' AND load_charset($charset))
		$res .= _T('texte_jeu_caractere_conversion',
			array('url' => generer_url_ecrire('convert_utf8'))
		);

	$res = ajax_action_post('configurer',
				'transcodeur',
				'config_lang',
				'',
				$res);

	$res = debut_cadre_relief("breve-24.gif", true, "", _T('info_jeu_caractere')) .
	  $res .
	  fin_cadre_relief(true);

	return ajax_action_greffe("configurer-transcodeur", $res);
}
?>
