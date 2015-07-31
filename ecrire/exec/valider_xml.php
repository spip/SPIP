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
	if ($GLOBALS['connect_statut'] != '0minirezo') {
		echo minipres();
		exit;
	}

	$url = urldecode(_request('var_url'));

	if (!$url) {
	  $url_aff = 'http://';
	  $onfocus = "this.value='';";
	  $texte = $err = '';

	} else {

	  list($server, $script) = preg_split('/[?]/', $url);
	  if ((!$server) OR ($server == './') 
	      OR strpos($server, url_de_base()) === 0) {
	    	    include_spip('inc/headers');
	    	    redirige_par_entete(parametre_url($url,'transformer_xml','valider_xml', '&'));
	  }

	  include_spip('public/debug');
	  include_spip('inc/distant');
	  $url_aff = entites_html($url);
	  $onfocus = "this.value='" . addslashes($url) . "';";

	  $transformer_xml = charger_fonction('valider_xml', 'inc');

	  if (preg_match(',^[a-z][0-9a-z_]*$,i', $url))
		$texte = $transformer_xml(charger_fonction($url, 'exec'), true);
	  else 	$texte = $transformer_xml(recuperer_page($url));

	  if (isset($GLOBALS['xhtml_error'])) 
	  	list($texte, $err) = emboite_texte($texte);
	  else {
	    $err = '<h3>' . _T('spip_conforme_dtd') . '</h3>';
	    list($texte, ) = emboite_texte($texte);
	  }
	}
	$titre = _T('analyse_xml');
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page($titre);

	echo "<div style='margin: 10px; text-align: center'>", "<h1>", $titre, '</h1>';
	echo "<form style='margin: 0px;' action='", generer_url_ecrire('valider_xml') . "'>";
	echo "<div><input type='hidden' name='exec' value='valider_xml' />";
	echo '<input type="text" size="70" value="',$url_aff,'" name="var_url" onfocus="'.$onfocus . '" />';
	echo "</div></form>";

	echo  $err, "</div>";
	echo "<div style='margin: 10px; text-align: left'>",$texte, '</div>', fin_page();
}
?>
