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
include_spip('inc/presentation');

// http://doc.spip.org/@exec_valider_xml_dist
function exec_valider_xml_dist()
{
	if (!autoriser('ecrire')) {
		include_spip('inc/minipres');
		echo minipres();
	} else valider_xml_ok(_request('var_url'));
}

function valider_xml_ok($url)
{
	$url = urldecode($url);
	$titre = _T('analyse_xml');
	if (!$url) {
		$url_aff = 'http://';
		$onfocus = "this.value='';";
		$texte = $bandeau = $err = '';
	} else {
		include_spip('public/debug');
		include_spip('inc/distant');
		$transformer_xml = charger_fonction('valider_xml', 'inc');

		if (is_dir($url)) {
			foreach(preg_files($url, '.php$') as $f) {
				$res[]= controle_une_url($transformer_xml, basename($f, '.php'), $url);
			}
			$res = valider_resultats($res);
			$bandeau = $url;
		} else {
			@list($server, $script) = preg_split('/[?]/', $url);
			if ((!$server) OR ($server == './') 
			    OR strpos($server, url_de_base()) === 0) {
			  include_spip('inc/headers');
			  redirige_par_entete(parametre_url($url,'transformer_xml','valider_xml', '&'));
			}

			$onfocus = "this.value='" . addslashes($url) . "';";
			unset($GLOBALS['xhtml_error']);
			if (preg_match(',^[a-z][0-9a-z_]*$,i', $url)) {
				$texte = $transformer_xml(charger_fonction($url, 'exec'), true);
				$url_aff = generer_url_ecrire($url);
			} else {
				$texte = $transformer_xml(recuperer_page($url));
				$url_aff = entites_html($url);
			}
			if (isset($GLOBALS['xhtml_error'])) 
				list($texte, $err) = emboite_texte($texte);
			else {
				$err = '<h3>' . _T('spip_conforme_dtd') . '</h3>';
				list($texte, ) = emboite_texte($texte);
			}

			$res =
			"<div style='text-align: center'>" . $err . "</div>" .
			"<div style='margin: 10px; text-align: left'>" . $texte . '</div>';
			$bandeau = "<a href='$url_aff'>$url</a>";
		}
	}

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page($titre);
	$onfocus = '<input type="text" size="70" value="' .$url_aff .'" name="var_url" id="var_url" onfocus="'.$onfocus . '" />';
	$onfocus = generer_form_ecrire('valider_xml', $onfocus, " method='get'");

	echo "<h1>", $titre, '<br>', $bandeau, '</h1>',
	  "<div style='text-align: center'>", $onfocus, "</div>",
	  $res,
	  fin_page();
}

function valider_resultats($res)
{
	$i = 0;
	$table = '';
	foreach($res as $l) {
		$i++;
		$class = 'row_'.alterner($i, 'even', 'odd');
		list($script, $texte, $erreurs) = $l;
		if ($texte < 0) {
			$texte = (0- $texte);
			$color = ";color: red";
		} else  $color = '';
		$h = generer_url_ecrire('valider_xml', "var_url=$script");
		$table .= "<tr class='$class'>"
		. "<td><a href='$h'>$script</a></td>"
		. "<td style='text-align: right$color'>$texte</td>"
		. "<td style='text-align: right'>$erreurs</td>";
	}
	return "<table class='spip'>"
	  . "<tr><th>script</th><th>"
	  . _T('taille_octets', array('taille' => ' '))
	  . "</th><th>" 
	  . _T('erreur_texte')
	  . "</th></tr>"
	  . $table
	  . "</table>";
}

function controle_une_url($transformer_xml, $script, $dir)
{
// ne pas se controler soi-meme
// et ne pas valider les exec qui sont en fait des actions.

	if ($script == $GLOBALS['exec']
	    OR $script=='index' 
	    OR $script == 'export_all'
	    OR $script == 'import_all')
		return array($script, '/', '/'); 

	unset($GLOBALS['xhtml_error']);
	$f = charger_fonction($script, $dir, true);
	spip_log("$script $f");
	if(!$f) return false;
	$f = $transformer_xml($f, true);
	$res = strlen($f);
	// On colore en rouge s'il y a l'attribut minipres:
	// test non significatif car le script necessite des arguments
	// (ou une authentification pour action d'administration)
	if (strpos($f, "id='minipres'")) $res = 0 - $res;
	if (isset($GLOBALS['xhtml_error'])) {
		preg_match_all(",(.*?)(\d+)(\D+(\d+)<br />),",
		       $GLOBALS['xhtml_error'],
		       $regs,
		       PREG_SET_ORDER);
		$n = count($regs);
	} else $n = 0;
	return array($script, $res, $n);
}
?>
