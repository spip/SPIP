<?php

include ("inc.php3");

include_ecrire ("inc_admin.php3");

debut_page(_T('onglet_vider_cache'), "administration", "cache");


echo "<br><br><br>";
gros_titre(_T('titre_admin_vider'));
// barre_onglets("administration", "vider");


debut_gauche();

debut_boite_info();

echo _T('info_gauche_admin_vider');

fin_boite_info();

debut_droite();

if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
	echo _T('avis_non_acces_page');
	fin_page();
	exit;
}

include_ecrire('inc_index.php3');
$resultat = effectuer_une_indexation (50);

var_dump($resultat);

echo "<BR>";

fin_page();


?>

