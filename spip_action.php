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

if (isset($hash)) {
	include_ecrire("inc_session");
	if (!verifier_action_auteur("$action $arg", $hash, $id_auteur)) {
		include_ecrire('inc_minipres');
		minipres(_T('info_acces_interdit'));
		exit;
	}
 }

$var_f = include_fonction('spip_action_' . $action);
$var_f();
if ($redirect) redirige_par_entete(urldecode($redirect));
?>
