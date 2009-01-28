<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// Les parametres generaux du site sont dans une table SQL;
// Recopie dans le tableau PHP global meta, car on en a souvent besoin

// duree maximale du cache. Le double pour l'antidater
define('_META_CACHE_TIME', 1<<24);

// http://doc.spip.org/@inc_meta_dist
function inc_meta_dist()
{
	// Lire les meta, en cache si present, valide et lisible
	// en cas d'install ne pas faire confiance au meta_cache eventuel
	if ((_request('exec')!=='install' OR !test_espace_prive())
	AND $new = jeune_fichier(_FILE_META, _META_CACHE_TIME)
#   AND (@filemtime(_FILE_META) > @filemtime(_DIR_RESTREINT . '.svn/entries'))
	AND $meta = spip_file_get_contents(_FILE_META)
	AND $meta = @unserialize($meta))
		$GLOBALS['meta'] = $meta;
	if (isset($GLOBALS['meta']['touch']) && ($GLOBALS['meta']['touch']<time()-_META_CACHE_TIME))
		unset($GLOBALS['meta']);
	// sinon lire en base
	if (!$GLOBALS['meta']) $new = !lire_metas();
	// renouveller l'alea au besoin
	if ((test_espace_prive() || isset($_GET['renouvelle_alea']))
	AND $GLOBALS['meta']
#	AND ($GLOBALS['exec'] === 'upgrade')
	AND (time() > _RENOUVELLE_ALEA + @$GLOBALS['meta']['alea_ephemere_date'])) {
		// si on n'a pas l'acces en ecriture sur le cache,
		// ne pas renouveller l'alea sinon le cache devient faux
		if (supprimer_fichier(_FILE_META)) {
			include_spip('inc/acces');
			renouvelle_alea();
			$new = false; 
		} else spip_log("impossible d'ecrire dans " . _FILE_META);
	}
	// et refaire le cache si on a du lire en base
	if (!$new) ecrire_fichier(_FILE_META, serialize($GLOBALS['meta']));
}

// fonctions aussi appelees a l'install ==> spip_query en premiere requete 
// pour eviter l'erreur fatale (serveur non encore configure)

// http://doc.spip.org/@lire_metas
function lire_metas() {

	if ($result = spip_query("SELECT nom,valeur FROM spip_meta")) {
		include_spip('base/abstract_sql');
		$GLOBALS['meta'] = array();
		while ($row = sql_fetch($result))
			$GLOBALS['meta'][$row['nom']] = $row['valeur'];

		if (!$GLOBALS['meta']['charset']
		  OR $GLOBALS['meta']['charset']=='_DEFAULT_CHARSET' // hum, correction d'un bug ayant abime quelques install
		)
			ecrire_meta('charset', _DEFAULT_CHARSET);
	}
	return $GLOBALS['meta'];
}
// http://doc.spip.org/@touch_meta
function touch_meta($antidate){
	if (!@touch(_FILE_META, $antidate))
		ecrire_fichier(_FILE_META, serialize(array_merge(array('touch'=>$antidate),$GLOBALS['meta'])));
}

// http://doc.spip.org/@effacer_meta
function effacer_meta($nom) {
	// section critique sur le cache:
	// l'invalider avant et apres la MAJ de la BD
	// c'est un peu moints bien qu'un vrai verrou mais ca suffira
	// et utiliser une statique pour eviter des acces disques a repetition
	static $touch = true;
	$antidate = time() - (_META_CACHE_TIME<<4);
	if ($touch) {touch_meta($antidate);}
	sql_delete("spip_meta", "nom='$nom'");
	unset($GLOBALS['meta'][$nom]);
	if ($touch) {touch_meta($antidate); $touch = false;}
}

// http://doc.spip.org/@ecrire_meta
function ecrire_meta($nom, $valeur, $importable = NULL) {

	static $touch = true;
	if (!$nom) return;
	$GLOBALS['meta'][$nom] = $valeur;
	include_spip('base/abstract_sql');
	$res = sql_select("*","spip_meta","nom=" . sql_quote($nom),'','','','','','continue');
	if (!$res) return; 
	$res = sql_fetch($res);
	// conserver la valeur de impt si existante
	// et ne pas invalider le cache si affectation a l'identique
	if ($res AND $valeur == $res['valeur']) return;
	// cf effacer pour le double touch
	$antidate = time() - (_META_CACHE_TIME<<1);
	if ($touch) {touch_meta($antidate);}
  $r = array('nom' => $nom, 'valeur' => $valeur);
  if ($importable) $r['impt'] = $importable;
	if ($res) {
		sql_updateq('spip_meta', $r,"nom=" . sql_quote($nom));
	} else {
		sql_insertq('spip_meta', $r);
	}
	if ($touch) {touch_meta($antidate); $touch = false;}
}
?>
