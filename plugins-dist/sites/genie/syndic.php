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

if (!defined("_ECRIRE_INC_VERSION")) return;
include_spip('inc/syndic');

## valeurs modifiables dans mes_options
## attention il est tres mal vu de prendre une periode < 20 minutes
if (!defined('_PERIODE_SYNDICATION'))
	define('_PERIODE_SYNDICATION', 2*60);
if (!defined('_PERIODE_SYNDICATION_SUSPENDUE'))
	define('_PERIODE_SYNDICATION_SUSPENDUE', 24*60);


// http://doc.spip.org/@genie_syndic_dist
function genie_syndic_dist($t) {
	return executer_une_syndication();
}

//
// Effectuer la syndication d'un unique site,
// retourne 0 si aucun a faire ou echec lors de la tentative
//

// http://doc.spip.org/@executer_une_syndication
function executer_une_syndication() {

	// On va tenter un site 'sus' ou 'off' de plus de 24h, et le passer en 'off'
	// s'il echoue
	$where = Sql::in("syndication", array('sus','off')) . "
	AND NOT(" . Sql::date_proche('date_syndic', (0 - _PERIODE_SYNDICATION_SUSPENDUE) , "MINUTE") . ')';
	$id_syndic = Sql::getfetsel("id_syndic", "spip_syndic", $where, '', "date_syndic", "1");
	if ($id_syndic) {
		// inserer la tache dans la file, avec controle d'unicite
		job_queue_add('syndic_a_jour','syndic_a_jour',array($id_syndic),'genie/syndic',true);
	}

	// Et un site 'oui' de plus de 2 heures, qui passe en 'sus' s'il echoue
	$where = "syndication='oui'
	AND NOT(" . Sql::date_proche('date_syndic', (0 - _PERIODE_SYNDICATION) , "MINUTE") . ')';
	$id_syndic = Sql::getfetsel("id_syndic", "spip_syndic", $where, '', "date_syndic", "1");

	if ($id_syndic) {
		// inserer la tache dans la file, avec controle d'unicite
		job_queue_add('syndic_a_jour','syndic_a_jour',array($id_syndic),'genie/syndic',true);
	}

	return 0;
}


/**
 * Mettre a jour le site
 * Attention, cette fonction ne doit pas etre appellee simultanement
 * sur un meme site: un verrouillage a du etre pose en amont.
 * => elle doit toujours etre appelee par job_queue_add
 *
 * http://doc.spip.org/@syndic_a_jour
 *
 * @param int $now_id_syndic
 * @return bool|string
 */
function syndic_a_jour($now_id_syndic) {
	include_spip('inc/texte');
	$call = debug_backtrace();
	if ($call[1]['function']!=='queue_start_job')
		spip_log("syndic_a_jour doit etre appelee par JobQueue Cf. http://trac.rezo.net/trac/spip/changeset/10294",_LOG_ERREUR);

	$row = Sql::fetsel("*", "spip_syndic", "id_syndic=".intval($now_id_syndic));

	if (!$row) return;

	$url_syndic = $row['url_syndic'];
	$url_site = $row['url_site'];

	if ($row['moderation'] == 'oui')
		$moderation = 'dispo';	// a valider
	else
		$moderation = 'publie';	// en ligne sans validation

	// determiner le statut a poser en cas d'echec : sus par defaut
	// off si le site est deja off, ou sus depuis trop longtemps
	$statut = 'sus';
	if (
		$row['statut']=='off'
	  OR ($row['statut']=='sus' AND time()-strtotime($row['date_syndic'])>_PERIODE_SYNDICATION_SUSPENDUE*60)
	  )
		$statut = 'off';

	Sql::updateq('spip_syndic', array('syndication'=>$statut, 'date_syndic'=>date('Y-m-d H:i:s')), "id_syndic=".intval($now_id_syndic));

	// Aller chercher les donnees du RSS et les analyser
	include_spip('inc/distant');
	$rss = recuperer_page($url_syndic, true);
	if (!$rss)
		$articles = _T('sites:avis_echec_syndication_02');
	else
		$articles = analyser_backend($rss, $url_syndic);

	// Renvoyer l'erreur le cas echeant
	if (!is_array($articles)) return $articles;

	// Les enregistrer dans la base

	$faits = array();
	foreach ($articles as $data) {
		inserer_article_syndique ($data, $now_id_syndic, $moderation, $url_site, $url_syndic, $row['resume'], $row['documents'], $faits);
	}

	// moderation automatique des liens qui sont sortis du feed
	if (count($faits) > 0) {
		$faits = Sql::in("id_syndic_article", $faits, 'NOT');
		if ($row['miroir'] == 'oui') {
			Sql::update('spip_syndic_articles', array('statut'=>"'off'", 'maj'=>'maj'), "id_syndic=$now_id_syndic AND $faits");
		}
	// suppression apres 2 mois des liens qui sont sortis du feed
		if ($row['oubli'] == 'oui') {

		  Sql::delete('spip_syndic_articles', "id_syndic=$now_id_syndic AND NOT(" . Sql::date_proche('maj', -2, 'MONTH') . ') AND NOT(' . Sql::date_proche('date', -2, 'MONTH') . ") AND $faits");
		}
	}

	// Noter que la syndication est OK
	Sql::updateq("spip_syndic", array("syndication" => 'oui'), "id_syndic=".intval($now_id_syndic));

	return false; # c'est bon
}


//
// Insere un article syndique (renvoie true si l'article est nouveau)
// en  verifiant qu'on ne vient pas de l'ecrire avec
// un autre item du meme feed qui aurait le meme link
//
// http://doc.spip.org/@inserer_article_syndique
function inserer_article_syndique ($data, $now_id_syndic, $statut, $url_site, $url_syndic, $resume, $documents, &$faits) {
	// Creer le lien s'il est nouveau - cle=(id_syndic,url)
	// On coupe a 255 caracteres pour eviter tout doublon
	// sur une URL de plus de 255 qui exloserait la base de donnees
	$le_lien = substr($data['url'], 0,255);

	// si true, un lien deja syndique arrivant par une autre source est ignore
	// par defaut [false], chaque source a sa liste de liens, eventuellement
	// les memes
	define('_SYNDICATION_URL_UNIQUE', false);

	// Si false, on ne met pas a jour un lien deja syndique avec ses nouvelles
	// donnees ; par defaut [true] : on met a jour si le contenu a change
	// Attention si on modifie a la main un article syndique, les modifs sont
	// ecrasees lors de la syndication suivante
	define('_SYNDICATION_CORRECTION', true);

	// Chercher les liens de meme cle
	// S'il y a plusieurs liens qui repondent, il faut choisir le plus proche
	// (ie meme titre et pas deja fait), le mettre a jour et ignorer les autres
	$n = 0;
	$s = Sql::select("id_syndic_article,titre,id_syndic,statut", "spip_syndic_articles",
		"url=" . Sql::quote($le_lien)
		. (_SYNDICATION_URL_UNIQUE
			? ''
			: " AND id_syndic=$now_id_syndic")
		." AND " . Sql::in('id_syndic_article', $faits, 'NOT'), "", "maj DESC");
	while ($a = Sql::fetch($s)) {
		$id =  $a['id_syndic_article'];
		$id_syndic = $a['id_syndic'];
		if ($a['titre'] == $data['titre']) {
			$id_syndic_article = $id;
			break;
		}
		$n++;
	}
	// S'il y en avait qu'un, le prendre quel que soit le titre
	if ($n == 1)
		$id_syndic_article = $id;
	// Si l'article n'existe pas, on le cree
	elseif (!isset($id_syndic_article)) {
		$champs = array(
			'id_syndic' => $now_id_syndic,
			'url' => $le_lien,
			'date' => date("Y-m-d H:i:s", $data['date'] ? $data['date'] : $data['lastbuilddate']),
			'statut'  => $statut
		);
		// Envoyer aux plugins
		$champs = pipeline('pre_insertion',
			array(
				'args' => array(
					'table' => 'spip_syndic_articles',
				),
				'data' => $champs
			)
		);
		$ajout = $id_syndic_article = Sql::insertq('spip_syndic_articles', $champs);
		if (!$ajout) return;
		pipeline('post_insertion',
			array(
				'args' => array(
					'table' => 'spip_syndic_articles',
					'id_objet' => $id_syndic_article
				),
				'data' => $champs
			)
		);
	}
	$faits[] = $id_syndic_article;


	// Si le lien n'est pas nouveau, plusieurs options :
	if (!$ajout) {
		// 1. Lien existant : on corrige ou pas ?
		if (!_SYNDICATION_CORRECTION) {
			return;
		}
		// 2. Le lien existait deja, lie a un autre spip_syndic
		if (_SYNDICATION_URL_UNIQUE AND $id_syndic != $now_id_syndic)
			return;
	}

	// Descriptif, en mode resume ou mode 'full text'
	// on prend en priorite data['descriptif'] si on est en mode resume,
	// et data['content'] si on est en mode "full syndication"
	if ($resume != 'non') {
		// mode "resume"
		$desc = strlen($data['descriptif']) ?
			$data['descriptif'] : $data['content'];
		$desc = couper(trim_more(textebrut($desc)), 300);
	} else {
		// mode "full syndication"
		// choisir le contenu pertinent
		// & refaire les liens relatifs
		$desc = strlen($data['content']) ?
			$data['content'] : $data['descriptif'];
		$desc = liens_absolus($desc, $url_syndic);
	}

	// tags & enclosures (preparer spip_syndic_articles.tags)
	$tags = ($data['enclosures']?$data['enclosures']:'');
	# eviter les doublons (cle = url+titre) et passer d'un tableau a une chaine
	if ($data['tags']) {
		$vus = array();
		foreach ($data['tags'] as $tag) {
			$cle = supprimer_tags($tag).extraire_attribut($tag,'href');
			$vus[$cle] = $tag;
		}
		$tags .= ($tags ? ', ' : '') . join(', ', $vus);
	}

	// Mise a jour du contenu (titre,auteurs,description,date?,source...)
	$vals = array(
			'titre' => $data['titre'],
			'lesauteurs' => $data['lesauteurs'],
			'descriptif' => $desc,
			'lang'=> substr($data['lang'],0,10),
			'source' => substr($data['source'],0,255),
			'url_source' => substr($data['url_source'],0,255),
			'tags' => $tags);

	// Mettre a jour la date si lastbuilddate
	if ($data['lastbuilddate'])
		$vals['date']= date("Y-m-d H:i:s", $data['lastbuilddate']);
				    
	Sql::updateq('spip_syndic_articles', $vals, "id_syndic_article=$id_syndic_article");

	// Point d'entree post_syndication
	pipeline('post_syndication',
		array(
			'args' => array(
				'table' => 'spip_syndic_articles',
				'id_objet' => $id_syndic_article,
				'url' => $le_lien,
				'id_syndic' => $now_id_syndic,
				'ajout' => $ajout,
			),
			'data' => $data
		)
	);

	return $ajout;
}

/**
 * Nettoyer les contenus de flux qui utilisent des espaces insecables en debut
 * pour faire un retrait.
 * Peut etre sous la forme de l'entite &nbsp; ou en utf8 \xc2\xa0
 *
 * @param  string $texte
 * @return string
 */
function trim_more($texte){
	$texte = trim($texte);
	// chr(194)chr(160)
	$texte = preg_replace(",^(\s|(&nbsp;)|(\xc2\xa0))+,ums","",$texte);
	return  $texte;
}
?>
