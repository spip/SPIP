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


$reinstall = 'non';
include ("inc.php3");
include_ecrire('inc_admin.php3');
$action = _T('texte_unpack');

debut_admin($action);

$hash = calculer_action_auteur("unpack");

fin_admin($action);

if (@file_exists("../spip_loader.php3"))
	redirige_par_entete("../spip_loader.php3?hash=$hash&id_auteur=$connect_id_auteur");
else if (@file_exists("../spip_unpack.php3"))
	redirige_par_entete("../spip_unpack.php3?hash=$hash&id_auteur=$connect_id_auteur");
else
	redirige_par_entete("../spip_loader.php3?hash=$hash&id_auteur=$connect_id_auteur");

?>
