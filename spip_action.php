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

include ("ecrire/inc_version.php3");
spip_log(join(',', $_REQUEST));
spip_log("$action $arg $id_auteur $redirect");
$var_f = include_fonction('spip_action_' . $action);
$var_f();
spip_log("$action $arg $id_auteur $redirect");
if ($redirect) redirige_par_entete($redirect);
?>
