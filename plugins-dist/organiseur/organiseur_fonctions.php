<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;


function critere_MESSAGES_destinataire_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$_auteur = calculer_liste($crit->param[0], array(), $boucles, $boucle->id_parent);

	$boucle->join['auteurs_liens']=array("'".$boucle->id_table."'","'id_objet'","'".$boucle->primary."'","'auteurs_liens.objet=\'message\' AND auteurs_liens.id_auteur='.intval($_auteur)");
	$boucle->from['auteurs_liens']='spip_auteurs_liens';
	$boucle->from_type['auteurs_liens'] = 'LEFT';
	$where =
		array("'OR'",
			array("'AND'",
				array("'!='","'".$boucle->id_table.".id_auteur'","intval($_auteur)"),
				array(
					"'AND'",
					array("'='","'auteurs_liens.id_auteur'","intval($_auteur)"),
					array("'!='","'auteurs_liens.vu'","'\'poub\''"),
				),
			),
			array("'OR'",
				array("'='","'".$boucle->id_table.".type'","Sql::quote('affich')"),
				array(
					"'AND'",
					array("'='","'".$boucle->id_table.".type'","Sql::quote('pb')"),
					array("'='","'".$boucle->id_table.".id_auteur'","intval($_auteur)"),
				)
			)
		);
	$not = $crit->not;

	if ($crit->cond)
		$where = array("'?'","strlen($_auteur)",	$where,"'1=1'");

	if ($not)
		$where = array("'NOT'",$where);

	$boucle->where[] = $where;
}
function critere_MESSAGES_non_lu_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$boucle->where[] =
		array("'OR'",
		"'auteurs_liens.vu IS NULL'",
		"Sql::in('auteurs_liens.vu',array('poub','oui'),'NOT',\$connect)"
		);
}

/**
 * Fonction privee pour mutualiser de code des criteres_MESSAGES_rv_*
 * Retourne le code php pour obtenir la date de reference de comparaison
 * des evenements a trouver
 *
 * @param string $idb
 * @param object $boucles
 * @param object $crit
 *
 * @return string code PHP concernant la date.
**/
function organiseur_calculer_date_reference($idb, &$boucles, $crit) {
	if (isset($crit->param[0]))
		return calculer_liste($crit->param[0], array(), $boucles, $boucles[$idb]->id_parent);
	else
		return "date('Y-m-d H:i:00')";
}


/**
 * {rv_a_venir}
 * {rv_a_venir #ENV{date}}
 *
 * @param string $idb
 * @param object $boucles
 * @param object $crit
 */
function critere_MESSAGES_rv_a_venir_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$id_table = $boucle->id_table;

	$_dateref = organiseur_calculer_date_reference($idb, $boucles, $crit);
	$_date = "$id_table.".(isset($boucle->show['date'])?$boucle->show['date']:"date_debut");
	$op = $crit->not ? "<=":">";
	$where = array("'$op'","'$_date'","Sql::quote($_dateref)");

	$boucle->where[] = $where;
}

/**
 * {rv_passe}
 * {rv_passe #ENV{date}}
 *
 * @param string $idb
 * @param object $boucles
 * @param object $crit
 */
function critere_MESSAGES_rv_passe_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$id_table = $boucle->id_table;

	$_dateref = organiseur_calculer_date_reference($idb, $boucles, $crit);
	$_date = "$id_table.date_fin";
	$op = $crit->not ? ">=":"<";

	$where = array("'$op'","'$_date'","Sql::quote($_dateref)");
	$boucle->where[] = $where;
}

/**
 * {rv_en_cours}
 * {rv_en_cours #ENV{date}}
 *
 * @param string $idb
 * @param object $boucles
 * @param object $crit
 */
function critere_MESSAGES_rv_en_cours_dist($idb, &$boucles, $crit) {
	$boucle = &$boucles[$idb];
	$id_table = $boucle->id_table;

	$_dateref = organiseur_calculer_date_reference($idb, $boucles, $crit);
	$_date_debut = "$id_table.".(isset($boucle->show['date'])?$boucle->show['date']:"date_debut");
	$_date_fin = "$id_table.date_fin";

	$where =
				array("'AND'",
					array("'<='", "'$_date_debut'", "Sql::quote($_dateref)"),
					array("'>='", "'$_date_fin'", "Sql::quote($_dateref)")
				);

	if ($crit->not)
		$where = array("'NOT'",$where);
	$boucle->where[] = $where;
}


function organiseur_icone_message($type,$taille=24){
	$icone = array('pb'=>'pensebete','affich'=>'annonce');
	$icone = isset($icone[$type])?$icone[$type]:'message';
	return "$icone-$taille.png";
}
function organiseur_texte_modifier_message($type){
	$texte = array('pb'=>'organiseur:icone_modifier_pensebete','affich'=>'organiseur:icone_modifier_annonce');
	$texte = isset($texte[$type])?$texte[$type]:'icone_modifier_message';
	return _T($texte);
}
function organiseur_texte_nouveau_message($type){
	$texte = array('pb'=>'organiseur:icone_ecrire_nouveau_pensebete','affich'=>'organiseur:icone_ecrire_nouvelle_annonce');
	$texte = isset($texte[$type])?$texte[$type]:'organiseur:icone_ecrire_nouveau_message';
	return _T($texte);
}
?>