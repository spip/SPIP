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

function fragments_instituer_auteur_dist()
{
  $script = _request('script');
  $id_auteur = intval(_request('id_auteur'));
  if (!preg_match('/^\w+$/', $script)) die("$script !!");

  $r = spip_fetch_array(spip_query("SELECT statut FROM spip_auteurs WHERE id_auteur=$id_auteur"));

  $f = charger_fonction('instituer_auteur', 'inc');
  return $f(_request('id_auteur'), $r['statut'] , _request('script'));
}
?>
