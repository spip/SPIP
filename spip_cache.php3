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

include_ecrire("inc_session");
include_ecrire("inc_invalideur");

if (verifier_action_auteur($action, $hash, $id_auteur)) {
	$action();
	spip_log($action);
 }
if ($redirect) redirige_par_entete($redirect);

?>
