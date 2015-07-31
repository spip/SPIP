<?php

include ("inc_version.php3");
include_ecrire ("inc_presentation.php3");
include_ecrire ("inc_texte.php3");

if ($f == 'gpl')
	install_debut_html("GNU General Public License");
else if ($f == 'gpl_fr')
	install_debut_html("Licence publique g&eacute;n&eacute;rale GNU (traduction non officielle)");
else {
	install_debut_html("Licence et conditions d'utilisation");
	$f = 'licence';
}

$texte = join('', file("$f.txt"));

echo propre("<tt>$texte</tt>");


echo "<pre><hr class='spip'>
<b>[<a href='licence.php3'>Conditions d'utilisation</a>]
[<a href='licence.php3?f=gpl'>GNU General Public License</a>]
[<a href='licence.php3?f=gpl_fr'>Traduction fran&ccedil;aise</a>]
[<a href='./'>Retour au site</a>]</b></pre>";


install_fin_html();
?>
