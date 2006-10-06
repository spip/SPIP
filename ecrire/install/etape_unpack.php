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
include_spip('inc/actions');

// http://doc.spip.org/@inc_install_unpack
function install_etape_unpack_dist()
{
  global  $connect_id_auteur;

  include_spip('inc/admin');

  $action = _T('texte_unpack');
 
  debut_admin(generer_url_post_ecrire("install"),$action);

  fin_admin($action);

	## ??????? a verifier
  if (@file_exists(_DIR_RACINE . "spip_loader" . '.php'))
	redirige_par_entete(generer_action_auteur('loader','','',true));
  else if (@file_exists(_DIR_RESTREINT . 'inc/install_unpack.php'))
	redirige_par_entete(generer_action_auteur('unpack','','',true));
  else // c'est qui lui ???? 
    redirige_par_entete(generer_action_auteur('loader','','',true));
}

?>
