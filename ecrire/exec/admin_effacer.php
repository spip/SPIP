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

// http://doc.spip.org/@exec_admin_effacer_dist
function exec_admin_effacer_dist()
{
  global $connect_statut, $connect_toutes_rubriques, $couleur_foncee;

$commencer_page = charger_fonction('commencer_page', 'inc');
echo $commencer_page(_T('titre_page_admin_effacer'), "configuration", "base");


echo "\n<br /><br /><br />";
gros_titre(_T('titre_admin_effacer'));
echo barre_onglets("administration", "effacer");


debut_gauche();

debut_boite_info();

echo _T('info_gauche_admin_effacer');

fin_boite_info();

debut_droite();

if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
	echo _T('avis_non_acces_page');
	echo fin_gauche(), fin_page();
	exit;
}



//
// Effacement total
//

debut_cadre_relief();

echo "\n<table border='0' cellspacing='1' cellpadding='8' width=\"100%\">";
echo "<tr><td bgcolor='$couleur_foncee' background=''><b>";
echo "<span style='font-family: Verdana,Arial,Sans,sans-serif; font-size: 16px; color: #FFFFFF;'>". _T('texte_effacer_base')."</span></b></td></tr>";

echo "<tr><td class='serif'>";

echo "\n<p align='justify'>\n";
echo '<img src="' . _DIR_IMG_PACK . 'warning.gif" alt="'._T('info_avertissement').'" width="48" height="48" align="right" />';
echo _T('texte_admin_effacer_01');

echo "\n<center>";

echo debut_boite_alerte();

echo "\n<div class='serif'>";
echo "\n<b>"._T('avis_suppression_base')."&nbsp;!</b>";

 echo  generer_url_post_ecrire("delete_all", "reinstall=non"),
   "\n<div align='right'>",
   "<input class='fondo' type='submit' value='",
   _T('bouton_effacer_tout'),
   "' /></div></form>",
   "\n</div>";

echo fin_boite_alerte();

echo "</center></p>";

echo "</td></tr>";
echo "</table>";

fin_cadre_relief();

echo "<br />";




echo fin_gauche(), fin_page();

}
?>
