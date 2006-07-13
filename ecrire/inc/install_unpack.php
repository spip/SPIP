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

function inc_install_unpack()
{
  global  $connect_id_auteur;

  include_spip('inc/admin');

  $action = _T('texte_unpack');
 
  debut_admin(generer_url_post_ecrire("install"),$action);

  $hash = calculer_action_auteur("unpack");

  fin_admin($action);

	## ??????? a verifier
  if (@file_exists(_DIR_RACINE . "spip_loader" . '.php'))
    redirige_par_entete(generer_url_public("spip_loader"), "?hash=$hash&id_auteur=$connect_id_auteur");
  else if (@file_exists(_DIR_RACINE . "spip_unpack" . '.php'))
    redirige_par_entete(generer_url_public("spip_unpack"), "?hash=$hash&id_auteur=$connect_id_auteur");
  else
    redirige_par_entete(generer_url_public("spip_loader"), "?hash=$hash&id_auteur=$connect_id_auteur");
}

?>