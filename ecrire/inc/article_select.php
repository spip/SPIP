<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// Recupere les donnees d'un article pour composer un formulaire d'edition
// (utilise par exec/article_edit)
// id_article = numero d'article existant
// id_rubrique = ou veut-on l'installer (pas obligatoire)
// lier_trad = l'associer a l'article numero $lier_trad
// new=oui = article a creer si on valide le formulaire
// http://doc.spip.org/@article_select
function article_select($id_article, $id_rubrique=0, $lier_trad=0, $id_version=0) {
	global $connect_id_auteur, $connect_id_rubrique, $spip_lang; 

	include_spip('inc/auth'); // pour auteurs_article si espace public
	include_spip('inc/autoriser');

	if (is_numeric($id_article)) {

		if (!autoriser('modifier','article',$id_article))
			return array();

// marquer le fait que l'article est ouvert en edition par toto a telle date
// une alerte sera donnee aux autres redacteurs sur exec=articles
		if ($GLOBALS['meta']['articles_modif'] != 'non') {
			include_spip('inc/drapeau_edition');
			signale_edition ($id_article,  $GLOBALS['auteur_session'], 'article');
		}
		$row = spip_fetch_array(spip_query("SELECT * FROM spip_articles WHERE id_article=$id_article"));
	// si une ancienne revision est demandee, la charger
	// en lieu et place de l'actuelle ; attention les champs
	// qui etaient vides ne sont pas vide's. Ca permet de conserver
	// des complements ajoutes "orthogonalement", et ca fait un code
	// plus generique.
		if ($id_version) {
			include_spip('inc/revisions');
			if ($textes = recuperer_version($id_article, $id_version)) {
				foreach ($textes as $champ => $contenu)
					$row[$champ] = $contenu;
			}
		}
		return $row;
	}
	// id_article non numerique, c'est une demande de creation.
	// Si c'est une demande de nouvelle traduction, init specifique
	if ($lier_trad)
		$row = article_select_trad($lier_trad);
	else {
		$row['titre'] = filtrer_entites(_T('info_nouvel_article'));
		$row['onfocus'] = " onfocus=\"if(!antifocus){this.value='';antifocus=true;}\"";
		$row['id_rubrique'] = $id_rubrique;
	}

	// appel du script a la racine, faut choisir 
	// admin restreint ==> sa premiere rubrique
	// autre ==> la derniere rubrique cree
	if (!$row['id_rubrique']) {
		if ($connect_id_rubrique)
			$row['id_rubrique'] = $id_rubrique = $connect_id_rubrique[0]; 
		else {
			$row_rub = spip_fetch_array(spip_query("SELECT id_rubrique FROM spip_rubriques ORDER BY id_rubrique DESC LIMIT 1"));
			$row['id_rubrique'] = $id_rubrique = $row_rub['id_rubrique'];
		}
	}

	// recuperer le secteur, pour affecter les bons champs extras
	if (!$row['id_secteur']) {
		$row_rub = spip_fetch_array(spip_query("SELECT id_secteur FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));
		$row['id_secteur'] = $row_rub['id_secteur'];
	}

	return $row;
}

//
// Si un article est demande en creation (new=oui) avec un lien de trad,
// on initialise les donnees de maniere specifique
//
// http://doc.spip.org/@article_select_trad
function article_select_trad($lier_trad) {
	// Recuperer les donnees de l'article original
	$result = spip_query("SELECT * FROM spip_articles WHERE id_article=$lier_trad");
	if ($row = spip_fetch_array($result)) {
		$row['titre'] = filtrer_entites(_T('info_nouvelle_traduction')).' '.$row["titre"];
		$id_rubrique = $row['id_rubrique'];
	}

	// Regler la langue, si possible, sur celle du redacteur
	// Cela implique souvent de choisir une rubrique ou un secteur
	if (in_array($GLOBALS['spip_lang'],
	explode(',', $GLOBALS['meta']['langues_multilingue']))) {
		// Si le menu de langues est autorise sur les articles,
		// on peut changer la langue quelle que soit la rubrique
		// donc on reste dans la meme rubrique
		if ($GLOBALS['meta']['multi_articles'] == 'oui') {
			$row['id_rubrique'] = $row['id_rubrique']; # explicite :-)
		}
		else if ($GLOBALS['meta']['multi_rubriques'] == 'oui') {
			// Sinon, chercher la rubrique la plus adaptee pour
			// accueillir l'article dans la langue du traducteur
			if ($GLOBALS['meta']['multi_secteurs'] == 'oui') {
				$id_parent = 0;
			} else {
				// on cherche une rubrique soeur dans la bonne langue
				$row_rub = spip_fetch_array(spip_query("SELECT id_parent FROM spip_rubriques WHERE id_rubrique=$id_rubrique"));

				$id_parent = $row_rub['id_parent'];
			}
			$row_rub = spip_fetch_array(spip_query("SELECT id_rubrique FROM spip_rubriques WHERE lang='".$GLOBALS['spip_lang']."' AND id_parent=$id_parent"));
			if ($row_rub)
				$row['id_rubrique'] = $row_rub['id_rubrique'];
		}
	}

	return $row;
}

?>
