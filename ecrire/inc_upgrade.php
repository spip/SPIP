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

function upgrade_dist()
{

  global $connect_id_auteur, $spip_version, $reinstall;

  if (!_FILE_CONNECT) {
	Header("Location: install.php3");
	exit;
  }

// Si reinstallation necessaire, message ad hoc
  if ($reinstall == 'oui') {

	@copy(_FILE_CONNECT, _FILE_CONNECT_INS);

	install_debut_html(_T('titre_page_upgrade')); 
	echo "<p><b>",_T('texte_nouvelle_version_spip_1'),"</b><p> ";
	echo _T('texte_nouvelle_version_spip_2');
	echo "<p><div align='right'>";
	echo '<form action="upgrade.php3">';
	echo "<input type='submit' value=\"",
	  _T('bouton_relancer_installation'),
	  "\" class='fondl'>";
	echo "</form>\n";
	install_fin_html();
	exit;
  }

// eviter les actions vides pour cause de fichier de langue inaccessible.
  $upgrade_titre = _T('info_mise_a_niveau_base') ;
  if (!$upgrade_titre) $upgrade_titre = 'info_mise_a_niveau_base';

// Commentaire standard upgrade
  $commentaire = _T('texte_mise_a_niveau_base_1');

// Verifier la version
  $version_installee = (double) str_replace(',','.',$GLOBALS['meta']['version_installee']);
# NB: str_replace car, sur club-internet, il semble que version_installe soit
# enregistree au format '1,812' et non '1.812'

// Erreur downgrade (cas de double installation de fichiers SPIP sur une meme base)
  if ($spip_version < $version_installee)
	$commentaire = _T('info_mise_a_niveau_base_2');

  // Qu'est-ce que tu fais ici?
  if ($spip_version == $version_installee) {
	@header("Location: ./");
	exit;
  }

  include_ecrire('inc_admin.php3');

  debut_admin($upgrade_titre, $commentaire);

  include_ecrire ("inc_base.php3");

  creer_base();
  $ok = maj_base();

  if ($ok) {
	include_ecrire ("inc_acces.php3");
	include_ecrire ("inc_config.php3");
	ecrire_acces();
	init_config();
  }

  fin_admin($upgrade_titre);

  if ($ok) {
	$hash = calculer_action_auteur("purger_cache");
	redirige_par_entete("../spip_cache.php3?purger_cache=oui"
		."&id_auteur=$connect_id_auteur&hash=$hash"
		."&redirect=" .  _DIR_RESTREINT_ABS);
  }
  else {
	echo _T('alerte_maj_impossible', array('version' => $spip_version));
  }
}

function demande_maj_version()
{
	include_ecrire("inc_presentation.php3");
	debut_page();
	echo "<blockquote><blockquote><h4><font color='red'>",
	_T('info_message_technique'),
	"</font><br> ",
	_T('info_procedure_maj_version'),
	"</h4>",
	_T('info_administrateur_site_01'),
	" <a href='upgrade.php3'>",
	_T('info_administrateur_site_02'),
	"</a></blockquote></blockquote><p>";
	fin_page();
	exit;
}

// appele dans inc_version pour gestion de l'installation
function info_install()
 {
	// Soit on est dans ecrire/ et on envoie sur l'installation
	if (@file_exists("inc_version.php3")) {
		header("Location: " . _DIR_RESTREINT . "install.php3");
		exit;
	}
	// Soit on est dans le site public
	else if (defined("_INC_PUBLIC")) {
		# on ne peut pas deviner ces repertoires avant l'installation !
		$db_ok = false;
		include_ecrire ("inc_minipres.php");
		install_debut_html(_T('info_travaux_titre')); echo "<p>"._T('info_travaux_texte')."</p>";
		install_fin_html();
		exit;
	}
	// Soit on est appele de l'exterieur (spikini, etc)
}
?>
