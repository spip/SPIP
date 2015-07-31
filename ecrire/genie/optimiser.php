<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('base/abstract_sql');

// http://doc.spip.org/@genie_optimiser_dist
function genie_optimiser_dist($t) {

	optimiser_base_une_table();
	optimiser_base();

	// la date souhaitee pour le tour suivant = apres-demain a 4h du mat ;
	// sachant qu'on a un delai de 48h, on renvoie aujourd'hui a 4h du mat
	// avec une periode de flou entre 2h et 6h pour ne pas saturer un hebergeur
	// qui aurait beaucoup de sites SPIP
	return -(mktime(2,0,0) + rand(0, 3600*4));
}

// heure de reference pour le garbage collector = 24h auparavant
// http://doc.spip.org/@optimiser_base
function optimiser_base($attente = 86400) {
	optimiser_base_disparus($attente);
}


// http://doc.spip.org/@optimiser_base_une_table
function optimiser_base_une_table() {

	$tables = array();
	$result = sql_showbase();

	// on n'optimise qu'une seule table a chaque fois,
	// pour ne pas vautrer le systeme
	// lire http://dev.mysql.com/doc/refman/5.0/fr/optimize-table.html
	while ($row = sql_fetch($result))
		$tables[] = array_shift($row);

	if ($tables) {
		$table_op = intval($GLOBALS['meta']['optimiser_table']+1) % sizeof($tables);
		ecrire_meta('optimiser_table', $table_op);
		$q = $tables[$table_op];
		spip_log("debut d'optimisation de la table $q");
		if (sql_optimize($q))
			spip_log("fin d'optimisation de la table $q");
		else spip_log("Pas d'optimiseur necessaire");
	}
}

// mysql < 4.0 refuse les requetes DELETE multi table 
// et elles ont une syntaxe differente entre 4.0 et 4.1
// On passe donc par un SELECT puis DELETE avec IN 

// Utilitaire exploitant le SELECT et appliquant DELETE
// L'index du SELECT doit s'appeler "id"

// http://doc.spip.org/@optimiser_sansref
function optimiser_sansref($table, $id, $sel, $and="")
{
	$in = array();
	while ($row = sql_fetch($sel)) $in[$row['id']]=true;
	sql_free($sel);

	if ($in) {
		sql_delete($table,  sql_in($id,array_keys($in)) . ($and?" AND $and":""));
		spip_log("Numeros des entrees $id supprimees dans la table $table: $in");
	}
	return count($in);
}

// Nomenclature des liens morts entre les tables,
// suite a la suppresion d'articles, d'auteurs etc
// Maintenant que MySQL 5 a des Cascades on pourrait faire autrement
// mais on garde la compatibilite avec les versions precedentes.

// http://doc.spip.org/@optimiser_base_disparus
function optimiser_base_disparus($attente = 86400) {

	# format = 20060610110141, si on veut forcer une optimisation tout de suite
	$mydate = sql_quote(date("Y-m-d H:i:s", time() - $attente));

	$n = 0;

	//
	// Rubriques 
	//

	# les articles qui sont dans une id_rubrique inexistante
	# attention on controle id_rubrique>0 pour ne pas tuer les articles
	# specialement affectes a une rubrique non-existante (plugin,
	# cf. http://trac.rezo.net/trac/spip/ticket/1549 )
	$res = sql_select("A.id_article AS id",
		        "spip_articles AS A
		        LEFT JOIN spip_rubriques AS R
		          ON A.id_rubrique=R.id_rubrique",
			 "A.id_rubrique > 0
			 AND R.id_rubrique IS NULL
		         AND A.maj < $mydate");

	$n+= optimiser_sansref('spip_articles', 'id_article', $res);

	// les articles a la poubelle
	sql_delete("spip_articles", "statut='poubelle' AND maj < $mydate");

	//
	// Auteurs
	//

	include_spip('action/editer_liens');
	// optimiser les liens de tous les auteurs vers des objets effaces
	// et depuis des auteurs effaces
	$n+= objet_optimiser_liens(array('auteur'=>'*'),'*');

	# effacer les auteurs poubelle qui ne sont lies a rien
	$res = sql_select("A.id_auteur AS id",
		      	"spip_auteurs AS A
		      	LEFT JOIN spip_auteurs_liens AS L
		          ON L.id_auteur=A.id_auteur",
			"L.id_auteur IS NULL
		       	AND A.statut='5poubelle' AND A.maj < $mydate");

	$n+= optimiser_sansref('spip_auteurs', 'id_auteur', $res);

	# supprimer les auteurs 'nouveau' qui n'ont jamais donne suite
	# au mail de confirmation (45 jours pour repondre, ca devrait suffire)
	sql_delete("spip_auteurs", "statut='nouveau' AND maj < ". sql_quote(date('Y-m-d', time()-45*24*3600)));


	$n = pipeline('optimiser_base_disparus', array(
			'args'=>array(
				'attente' => $attente,
				'date' => $mydate),
			'data'=>$n
	));
	
	if (!$n) spip_log("Optimisation des tables: aucun lien mort");
}
?>
