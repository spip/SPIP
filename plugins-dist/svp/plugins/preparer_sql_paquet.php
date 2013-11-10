<?php

/**
 * Fichier permettant de transformer les données d'un arbre de description
 * originaire d'un paquet.xml dans un format compatible avec la base de données
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Plugins
**/
if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Pour une description de plugin donnée (issue de la dtd de paquet.xml),
 * prépare les données à installer en bdd 
 *
 * Les données sont parfois sérialisées, parfois compilées
 * pour tenir compte des spécificités de cette DTD et du stockage en bdd.
 * 
 * @param array $plugin
 *     Description de plugin
 * @return array
 *     Couples clés => valeurs de description du paquet
**/
function plugins_preparer_sql_paquet($plugin)
{
	include_spip('inc/svp_outiller');

	$champs = array();
	if (!$plugin)
		return $champs;

	// On initialise les champs ne necessitant aucune transformation
	foreach (array(
		'categorie'     => 'categorie',
		'etat'          => 'etat',
		'version_base'  => 'schema',
		'logo'          => 'logo',
		'lien_doc'      => 'documentation',
		'lien_demo'     => 'demonstration',
		'lien_dev'      => 'developpement'
		) as $cle_champ => $cle_plugin)
	{
		 $champs[$cle_champ] = (isset($plugin[$cle_plugin]) and $plugin[$cle_plugin])
			? $plugin[$cle_plugin]
			: '';
	}

	// on normalise la version 1.3.12 => 001.003.012
	$champs['version'] = (isset($plugin['version']) AND $plugin['version'])
			? normaliser_version($plugin['version'])
			: '';

	// On passe le prefixe en lettres majuscules comme ce qui est fait dans SPIP
	// Ainsi les valeurs dans la table spip_plugins coincideront avec celles de la meta plugin
	$champs['prefixe'] = strtoupper($plugin['prefix']);

	// Indicateurs d'etat numerique (pour simplifier la recherche des maj de STP)
	static $num = array('stable'=>4, 'test'=>3, 'dev'=>2, 'experimental'=>1);
	$champs['etatnum'] = isset($num[$plugin['etat']]) ? $num[$plugin['etat']] : 0;


	// On passe en utf-8 avec le bon charset les champs pouvant contenir des entites html
	foreach (array(
		'nom'         => 'nom',
		'description' => 'description',
		'slogan'      => 'slogan'
		) as $cle_champ => $cle_plugin)
	{
		 $champs[$cle_champ] = (isset($plugin[$cle_plugin]) and $plugin[$cle_plugin])
			? entite2charset($plugin[$cle_plugin])
			: '';
	}

	// Cles necessitant d'etre serialisees
	// Tags : liste de mots-cles
	// Traitement des auteurs, credits, licences et copyright
	foreach (array(
		'tags'       => 'tags',
		'auteur'     => 'auteur',
		'credit'     => 'credit',
		'licence'    => 'licence',
		'copyright'  => 'copyright',
		) as $cle_champ => $cle_plugin)
	{
		 $champs[$cle_champ] = (isset($plugin[$cle_plugin]) and $plugin[$cle_plugin])
			? serialize($plugin[$cle_plugin])
			: '';
	}

	// Extraction de la compatibilite SPIP et construction de la liste des branches spip supportees
	$champs['compatibilite_spip'] = (isset($plugin['compatibilite']) AND $plugin['compatibilite'])
			? $plugin['compatibilite']
			: '';
	$champs['branches_spip']      = (isset($plugin['compatibilite']) AND $plugin['compatibilite'])
			? compiler_branches_spip($plugin['compatibilite'])
			: '';
	
	// Construction du tableau des dependances necessite, lib et utilise
	$dependances['necessite'] = $plugin['necessite'];
	$dependances['librairie'] = $plugin['lib'];
	$dependances['utilise']   = $plugin['utilise'];
	$champs['dependances']    = serialize($dependances);

	return $champs;
}

?>
