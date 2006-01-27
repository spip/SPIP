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

include_ecrire('inc_admin');

function verifier_version_sauvegarde ($archive) {
	global $spip_version;
	global $flag_gz;

	$ok = @file_exists(_DIR_SESSIONS . $archive);
	$gz = $flag_gz;
	$_fopen = ($gz) ? gzopen : fopen;
	$_fread = ($gz) ? gzread : fread;
	$buf_len = 1024; // la version doit etre dans le premier ko

	if ($ok) {
		$f = $_fopen(_DIR_SESSIONS . $archive, "rb");
		$buf = $_fread($f, $buf_len);

		if (ereg("<SPIP [^>]* version_base=\"([0-9\.]+)\" ", $buf, $regs)
			AND $regs[1] == $spip_version)
			return false; // c'est bon
		else
			return _T('avis_erreur_version_archive', array('archive' => $archive));
	} else
		return _T('avis_probleme_archive', array('archive' => $archive));
}

function import_all_dist()
{
	global $archive;
	if ($archive) {
	$action = _T('info_restauration_sauvegarde', array('archive' => $archive));
	$commentaire = verifier_version_sauvegarde ($archive);
	}

  debut_admin(generer_url_post_ecrire("import_all","archive=$archive"), $action, $commentaire);

  $archive = _DIR_SESSIONS . $archive;

  ecrire_meta("debut_restauration", "debut");
  ecrire_meta("fichier_restauration", $archive);
  ecrire_meta("status_restauration", "0");
  ecrire_metas();
  
  fin_admin($action);
  header("Location: ./");
}
?>
