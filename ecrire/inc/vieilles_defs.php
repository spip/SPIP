<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

/* Ce fichier contient des fonctions, globales ou constantes	*/
/* qui ont fait partie des fichiers de configurations de Spip	*/
/* mais en ont ete retires ensuite.				*/
/* Ce fichier n'est donc jamais charge par la presente version	*/
/* mais est present pour que les contributions à Spip puissent	*/
/* fonctionner en chargeant ce fichier, en attendant d'etre	*/
/* reecrites conformement a la nouvelle interface.		*/


// http://doc.spip.org/@debut_raccourcis
function debut_raccourcis() {
        global $spip_display;
        echo "<div>&nbsp;</div>";
        creer_colonne_droite();

        debut_cadre_enfonce();
        if ($spip_display != 4) {
                echo "<font face='Verdana,Arial,Sans,sans-serif' size=1>";
                echo "<b>"._T('titre_cadre_raccourcis')."</b><p />";
        } else {
                echo "<h3>"._T('titre_cadre_raccourcis')."</h3>";
                echo "<ul>";
        }
}

function fin_raccourcis() {
        global $spip_display;
        
        if ($spip_display != 4) echo "</font>";
        else echo "</ul>";
        
        fin_cadre_enfonce();
}

?>
