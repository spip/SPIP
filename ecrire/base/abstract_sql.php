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

// Cette fonction est systematiquement appelee par les squelettes
// pour constuire une requete SQL de type "lecture" (SELECT) a partir
// de chaque boucle.
// Elle construit et exe'cute une reque^te SQL correspondant a` une balise
// Boucle ; elle notifie une erreur SQL dans le flux de sortie et termine
// le processus.
// Sinon, retourne la ressource interrogeable par spip_abstract_fetch.
// Recoit en argument:
// - le tableau des champs a` ramener
// - le tableau des tables a` consulter
// - le tableau des conditions a` remplir
// - le crite`re de regroupement
// - le crite`re de classement
// - le crite`re de limite
// - une sous-requete e'ventuelle (MySQL > 4.1)
// - un compteur de sous-requete
// - le nom de la table
// - le nom de la boucle (pour le message d'erreur e'ventuel)
// - le serveur sollicite

function spip_abstract_select (
	$select = array(), $from = array(), $where = array(),
	$groupby = '', $orderby = array(), $limit = '',
	$sousrequete = '', $cpt = '',
	$table = '', $id = '', $serveur='') {

	if (!$serveur)
	  // le serveur par defaut est celui defini dans inc_connect.php
	  { spip_connect();
	    $f = 'spip_mysql_select';
	  }
	else {
	  // c'est un autre; est-il deja charge ?
		$f = 'spip_' . $serveur . '_select';
		if (!function_exists($f)) {
		  // non, il est decrit dans le fichier ad hoc
			$d = find_in_path('inc_connect-' . $serveur . '.php');
			if (@file_exists($d)) include($d); else spip_log("pas de fichier $d pour decrire le serveur '$serveur'");
			$f = spip_abstract_serveur($f, $serveur);
		}
	}
	return $f($select, $from, $where,
		  $groupby, array_filter($orderby), $limit,
		  $sousrequete, $cpt,
		  $table, $id, $serveur);
}

function spip_abstract_serveur($f, $serveur) {
	if (function_exists($f))
		return $f;

	erreur_squelette(' '._T('zbug_serveur_indefini'), $serveur);

	// hack pour continuer la chasse aux erreurs
	return 'array';
}

// Les 3 fonctions suivantes exploitent le resultat de la precedente,
// si l'include ne les a pas definies, erreur immediate

function spip_abstract_fetch($res, $serveur='') {
	if (!$serveur) return spip_fetch_array($res);
	$f = spip_abstract_serveur('spip_' . $serveur . '_fetch', $serveur);
	return $f($res);
}

function spip_abstract_count($res, $serveur='')
{
  if (!$serveur) return spip_num_rows($res);
  $f = spip_abstract_serveur('spip_' . $serveur . '_count', $serveur);
  return $f($res);
}

function spip_abstract_free($res, $serveur='')
{
  if (!$serveur) return spip_free_result($res);
  $f = spip_abstract_serveur('spip_' . $serveur . '_free', $serveur);
  return $f($res);
}

function spip_abstract_insert($table, $noms, $valeurs, $serveur='')
{
  $f = (!$serveur ? 'spip_mysql_insert' :
	spip_abstract_serveur('spip_' . $serveur . '_insert', $serveur));
  return $f($table, $noms, $valeurs);
}

function spip_abstract_showtable($table, $serveur='')
{
  $f = (!$serveur ? 'spip_mysql_showtable' :
	spip_abstract_serveur('spip_' . $serveur . '_showtable', $serveur));
  return $f($table);
}

# une composition tellement frequente...
function spip_abstract_fetsel(
	$select = array(), $from = array(), $where = array(),
	$groupby = '', $orderby = array(), $limit = '',
	$sousrequete = '', $cpt = '',
	$table = '', $id = '', $serveur='') {
	return spip_abstract_fetch(spip_abstract_select(
$select, $from, $where,	$groupby, $orderby, $limit,
$sousrequete, $cpt, $table, $id, $serveur),
				   $serveur);
}
?>
