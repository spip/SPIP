<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined('_ECRIRE_INC_VERSION')) return;

// http://doc.spip.org/@base_serial
function base_serial(&$tables_principales){

	$spip_jobs = array(
		"id_job" 	=> "bigint(21) NOT NULL",
		"descriptif"	=> "text DEFAULT '' NOT NULL",
		"fonction" 	=> "varchar(255) NOT NULL", //nom de la fonction
		"args"=> "longblob DEFAULT '' NOT NULL", // arguments
		"md5args"=> "char(32) NOT NULL default ''", // signature des arguments
		"inclure" => "varchar(255) NOT NULL", // fichier a inclure ou path/ pour charger_fonction
		"priorite" 	=> "smallint(6) NOT NULL default 0",
		"date" => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL", // date au plus tot
		"status" => "tinyint NOT NULL default 1",
		);

	$spip_jobs_key = array(
		"PRIMARY KEY" 	=> "id_job",
		"KEY date" => "date",
		"KEY status" => "status",
	);

	/// Attention: mes_fonctions peut avoir deja defini cette variable
	/// il faut donc rajouter, mais pas reinitialiser
	$tables_principales['spip_jobs'] = array('field' => &$spip_jobs, 'key' => &$spip_jobs_key);
	$tables_principales = pipeline('declarer_tables_principales',$tables_principales);
}

include_spip('base/objets');
$GLOBALS['tables_principales'] = lister_tables_principales();

?>
