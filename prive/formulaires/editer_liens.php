<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;


/**
 * @param string $a
 * @param string|int $b
 * @param int|string $c
 * @return array
 *   ($table_source,$objet,$id_objet,$objet_lien)
 */
function determine_source_lien_objet($a,$b,$c){
	$table_source = $objet_lien = $objet = $id_objet = null;
	if (is_numeric($c) AND !is_numeric($b)){
		$table_source = table_objet($a);
		$objet_lien = objet_type($a);
		$objet = objet_type($b);
		$id_objet = $c;
	}
	if (is_numeric($b) AND !is_numeric($c)){
		$table_source = table_objet($c);
		$objet_lien = objet_type($a);
		$objet = objet_type($a);
		$id_objet = $b;
	}

	return array($table_source,$objet,$id_objet,$objet_lien);
}

/**
 * #FORMULAIRE_EDITER_LIENS{auteurs,article,23}
 *   pour associer des auteurs � l'article 23, sur la table pivot spip_auteurs_liens
 * #FORMULAIRE_EDITER_LIENS{article,23,auteurs}
 *   pour associer des auteurs � l'article 23, sur la table pivot spip_articles_liens
 * #FORMULAIRE_EDITER_LIENS{articles,auteur,12}
 *   pour associer des articles � l'auteur 12, sur la table pivot spip_articles_liens
 * #FORMULAIRE_EDITER_LIENS{auteur,12,articles}
 *   pour associer des articles � l'auteur 12, sur la table pivot spip_auteurs_liens
 *
 * @param string $a
 * @param string|int $b
 * @param int|string $c
 * @param bool $editable
 * @return array
 */
function formulaires_editer_liens_charger_dist($a,$b,$c,$editable=true){

	list($table_source,$objet,$id_objet,$objet_lien) = determine_source_lien_objet($a,$b,$c);
	if (!$table_source OR !$objet OR !$objet_lien OR !$id_objet)
		return false;

	$objet_source = objet_type($table_source);
	$table_sql_source = table_objet_sql($objet_source);

	// verifier existence de la table xxx_liens
	include_spip('action/editer_liens');
	if (!objet_associable($objet_lien))
		return false;

	if (!$editable AND !count(objet_trouver_liens(array($objet_lien=>'*'),array(($objet_lien==$objet_source?$objet:$objet_source)=>'*'))))
		return false;
	
	$valeurs = array(
		'id'=>"$table_source-$objet-$id_objet-$objet_lien", // identifiant unique pour les id du form
		'_vue_liee' => $table_source."_lies",
		'_vue_ajout' => $table_source."_associer",
		'_objet_lien' => $objet_lien,
		'id_lien_ajoute'=>_request('id_lien_ajoute'),
		'objet'=>$objet,
		'id_objet'=>$id_objet,
		'objet_source'=>$objet_source,
		'recherche'=>'',
		'visible'=>0,
		'ajouter_lien'=>'',
		'supprimer_lien'=>'',
		'_oups' => _request('_oups'),
		'editable' => $editable?true:false,
	);

	return $valeurs;
}

/**
 * Traiter le post des informations d'edition de liens
 * Les formulaires postent dans trois variables ajouter_lien et supprimer_lien
 * et remplacer_lien
 *
 * Les deux premieres peuvent etre de trois formes differentes :
 * ajouter_lien[]="objet1-id1-objet2-id2"
 * ajouter_lien[objet1-id1-objet2-id2]="nimportequoi"
 * ajouter_lien['clenonnumerique']="objet1-id1-objet2-id2"
 * Dans ce dernier cas, la valeur ne sera prise en compte
 * que si _request('clenonnumerique') est vrai (submit associe a l'input)
 *
 * remplacer_lien doit etre de la forme
 * remplacer_lien[objet1-id1-objet2-id2]="objet3-id3-objet2-id2"
 * ou objet1-id1 est celui qu'on enleve et objet3-id3 celui qu'on ajoute
 *
 * @param string $a
 * @param string|int $b
 * @param int|string $c
 * @param bool $editable
 * @return array
 */
function formulaires_editer_liens_traiter_dist($a,$b,$c,$editable=true){
	$res = array('editable'=>$editable?true:false);
	list($table_source,$objet,$id_objet,$objet_lien) = determine_source_lien_objet($a,$b,$c);
	if (!$table_source OR !$objet OR !$objet_lien)
		return $res;


	if (_request('tout_voir'))
		set_request('recherche','');


	if (autoriser('modifier',$objet,$id_objet)) {
		// annuler les suppressions du coup d'avant !
		if (_request('annuler_oups')
			AND $oups = _request('_oups')
			AND $oups = unserialize($oups)){
			$objet_source = objet_type($table_source);
			include_spip('action/editer_liens');
			foreach($oups as $oup) {
				if ($objet_lien==$objet_source)
					objet_associer(array($objet_source=>$oup[$objet_source]), array($objet=>$oup[$objet]),$oup);
				else
					objet_associer(array($objet=>$oup[$objet]), array($objet_source=>$oup[$objet_source]),$oup);
			}
			# oups ne persiste que pour la derniere action, si suppression
			set_request('_oups');
		}

		$supprimer = _request('supprimer_lien');
		$ajouter = _request('ajouter_lien');

		// il est possible de preciser dans une seule variable un remplacement :
		// remplacer_lien[old][new]
		if ($remplacer = _request('remplacer_lien')){
			foreach($remplacer as $k=>$v){
				if ($old = lien_verifier_action($k,'')){
					foreach(is_array($v)?$v:array($v) as $kn=>$vn)
						if ($new = lien_verifier_action($kn,$vn)){
							$supprimer[$old] = 'x';
							$ajouter[$new] = '+';
						}
				}
			}
		}

		if ($supprimer){
			include_spip('action/editer_liens');
			$oups = array();

			foreach($supprimer as $k=>$v) {
				if ($lien = lien_verifier_action($k,$v)){
					$lien = explode("-",$lien);
					list($objet_source,$ids,$objet_lie,$idl) = $lien;
					if ($objet_lien==$objet_source){
						$oups = array_merge($oups,  objet_trouver_liens(array($objet_source=>$ids), array($objet_lie=>$idl)));
						objet_dissocier(array($objet_source=>$ids), array($objet_lie=>$idl));
					}
					else{
						$oups = array_merge($oups,  objet_trouver_liens(array($objet_lie=>$idl), array($objet_source=>$ids)));
						objet_dissocier(array($objet_lie=>$idl), array($objet_source=>$ids));
					}
				}
			}
			set_request('_oups',$oups?serialize($oups):null);
		}
		
		if ($ajouter){
			$ajout_ok = false;
			include_spip('action/editer_liens');
			foreach($ajouter as $k=>$v){
				if ($lien = lien_verifier_action($k,$v)){
					$ajout_ok = true;
					list($objet1,$ids,$objet2,$idl) = explode("-",$lien);
					if ($objet_lien==$objet1)
						objet_associer(array($objet1=>$ids), array($objet2=>$idl));
					else
						objet_associer(array($objet2=>$idl), array($objet1=>$ids));
					set_request('id_lien_ajoute',$ids);
				}
			}
			# oups ne persiste que pour la derniere action, si suppression
			# une suppression suivie d'un ajout dans le meme hit est un remplacement
			# non annulable !
			if ($ajout_ok)
				set_request('_oups');
		}
	}

	
	return $res;
}


/**
 * Les formulaires envoient une action dans un tableau ajouter_lien
 * ou supprimer_lien
 * L'action est de la forme
 * objet1-id1-objet2-id2
 *
 * L'action peut etre indiquee dans la cle, ou dans la valeur
 * Si elle est indiquee dans la valeur, et que la cle est non numerique,
 * on ne la prend en compte que si un submit avec la cle a ete envoye
 *
 * @param string $k
 * @param string $v
 * @return string
 */
function lien_verifier_action($k,$v){
	if (preg_match(",^\w+-[\w*]+-[\w*]+-[\w*]+,",$k))
		return $k;
	if (preg_match(",^\w+-[\w*]+-[\w*]+-[\w*]+,",$v)){
		if (is_numeric($k))
			return $v;
		if (_request($k))
			return $v;
	}
	return '';
}
?>
