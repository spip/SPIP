<?php
/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

include ("inc.php3");

$var_nom = 'ajax_page';
$var_f = find_in_path('inc_' . $var_nom . '.php');

if ($var_f) 
  include($var_f);
else
  include_ecrire($var_f = 'inc_' . $var_nom . '.php');

# gerer un charset minimaliste en convertissant tout en unicode &#xxx;


$var_nom = 'ajax_page_' . $fonction;
if (!function_exists($var_nom))
	spip_log("fonction $var_nom indisponible dans $var_f");
 else {
	if ($flag_ob) {
		ob_start();
		$charset = lire_meta("charset");
	}
	@header('Content-type: text/html; charset=$charset');
	echo "<"."?xml version='1.0' encoding='$charset'?".">\n";
	$var_nom(intval($id), intval($exclus), intval($col), $id_ajax_fonc, $type, $rac);

	if ($flag_ob) {
	# fin gestion charset
		$a = ob_get_contents();
		ob_end_clean();
		include_ecrire('inc_charsets.php3');
		echo charset2unicode($a, 'AUTO', true);
	}
}
?>
