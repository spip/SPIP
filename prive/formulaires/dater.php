<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion du formulaire de date
 *
 * @package SPIP\Core\Formulaires
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Chargement du formulaire d'édition d'une date
 *
 * @param string $objet
 *     Type d'objet
 * @param int $id_objet
 *     Identifiant de l'objet
 * @param string $retour
 *     URL de redirection après le traitement
 * @param array|string $options
 *     Options. Si string, unserialize pour obtenir un tableau.
 *
 *     - date_redac : Permet de modifier en plus la date de rédaction antérieure
 *     - champ_date : permet de preciser le champ date qu'on utilise
 *     - label_date : label optionnel pour la saisie du champ date
 *     - champ_date_redac : permet de preciser le champ date_redac qu'on utilise
 *     - label_date_redac : label optionnel pour la saisie du champ date_redac
 *     - texte_sans_date_redac : texte optionnel affiche pour vider la date_redac
 *     - class : une classe ajoutable au formulaire pour le distinguer si on a plusieurs occurences
 * @return array
 *     Environnement du formulaire
 **/
function formulaires_dater_charger_dist($objet, $id_objet, $retour = '', $options = array()) {

	$objet = objet_type($objet);
	if (!$objet or !intval($id_objet)) {
		return false;
	}

	if (!is_array($options)) {
		$options = unserialize($options);
	}

	$_id_objet = id_table_objet($objet);
	$table = table_objet($objet);
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table($table);

	if (!$desc) {
		return false;
	}

	$champ_date = $desc['date'] ? $desc['date'] : 'date';
	if (isset($options['champ_date']) and $options['champ_date']) {
		$champ_date = $options['champ_date'];
	}
	if (!isset($desc['field'][$champ_date])) {
		return false;
	}

	$valeurs = array(
		'objet' => $objet,
		'id_objet' => $id_objet,
		'id' => $id_objet,
	);


	$select = "$champ_date as date";
	$champ_date_redac = 'date_redac';
	if (isset($options['champ_date_redac']) and $options['champ_date_redac']) {
		$champ_date_redac = $options['champ_date_redac'];
	}
	if (isset($desc['field'][$champ_date_redac])) {
		$select .= ",$champ_date_redac as date_redac";
	}
	if (isset($desc['field']['statut'])) {
		$select .= ',statut';
	}


	$row = sql_fetsel($select, $desc['table'], "$_id_objet=" . intval($id_objet));
	$statut = isset($row['statut']) ? $row['statut'] : 'publie'; // pas de statut => publie

	$valeurs['editable'] = autoriser('dater', $objet, $id_objet, null, array('statut' => $statut));

	$possedeDateRedac = false;

	if (isset($row['date_redac']) and
		$regs = recup_date($row['date_redac'], false)
	) {
		$annee_redac = $regs[0];
		$mois_redac = $regs[1];
		$jour_redac = $regs[2];
		$heure_redac = $regs[3];
		$minute_redac = $regs[4];
		$possedeDateRedac = true;
		// attention : les vrai dates de l'annee 1 sont stockee avec +9000 => 9001
		// mais reviennent ici en annee 1 par recup_date
		// on verifie donc que le intval($row['date_redac']) qui ressort l'annee
		// est bien lui aussi <=1 : dans ce cas c'est une date sql 'nulle' ou presque, selon
		// le gestionnnaire sql utilise (0001-01-01 pour PG par exemple)
		if (intval($row['date_redac']) <= 1 and ($annee_redac <= 1) and ($mois_redac <= 1) and ($jour_redac <= 1)) {
			$possedeDateRedac = false;
		}
	} else {
		$annee_redac = $mois_redac = $jour_redac = $heure_redac = $minute_redac = 0;
	}

	if ($regs = recup_date($row['date'], false)) {
		$annee = $regs[0];
		$mois = $regs[1];
		$jour = $regs[2];
		$heure = $regs[3];
		$minute = $regs[4];
	}

	// attention, si la variable s'appelle date ou date_redac, le compilo va
	// la normaliser, ce qu'on ne veut pas ici.
	$valeurs['afficher_date_redac'] = ($possedeDateRedac ? $row['date_redac'] : '');
	$valeurs['date_redac_jour'] = dater_formater_saisie_jour($jour_redac, $mois_redac, $annee_redac);
	$valeurs['date_redac_heure'] = "$heure_redac:$minute_redac";

	$valeurs['afficher_date'] = $row['date'];
	$valeurs['date_jour'] = dater_formater_saisie_jour($jour, $mois, $annee);
	$valeurs['date_heure'] = "$heure:$minute";

	$valeurs['sans_redac'] = !$possedeDateRedac;

	if (isset($options['date_redac'])) {
		$valeurs['_editer_date_anterieure'] = $options['date_redac'];
	} else {
		$valeurs['_editer_date_anterieure'] = ($objet == 'article' and ($GLOBALS['meta']['articles_redac'] != 'non' or $possedeDateRedac));
	}
	$valeurs['_label_date'] = (($statut == 'publie') ?
		_T('texte_date_publication_objet') : _T('texte_date_creation_objet'));
	if (isset($options['label_date']) and $options['label_date']) {
		$valeurs['_label_date'] = $options['label_date'];
	}
	if (isset($options['label_date_redac']) and $options['label_date_redac']) {
		$valeurs['_label_date_redac'] = $options['label_date_redac'];
	}
	if (isset($options['texte_sans_date_redac']) and $options['texte_sans_date_redac']) {
		$valeurs['_texte_sans_date_redac'] = $options['texte_sans_date_redac'];
	}
	if (isset($options['class']) and $options['class']) {
		$valeurs['_class'] = $options['class'];
	}

	$valeurs['_saisie_en_cours'] = (_request('_saisie_en_cours') !== null or _request('date_jour') !== null);

	// cas ou l'on ne peut pas dater mais on peut modifier la date de redac anterieure
	// https://core.spip.net/issues/3494
	$valeurs['_editer_date'] = $valeurs['editable'];
	if ($valeurs['_editer_date_anterieure'] and !$valeurs['editable']) {
		$valeurs['editable'] = autoriser('modifier', $objet, $id_objet);
	}

	return $valeurs;
}

/**
 * Formate la date
 *
 * @param string|int $jour
 *     Numéro du jour
 * @param string|int $mois
 *     Numéro du mois
 * @param string|int $annee
 *     Année
 * @param string $sep
 *     Séparateur
 * @return string
 *     Date formatée tel que `02/10/2012`
 **/
function dater_formater_saisie_jour($jour, $mois, $annee, $sep = '/') {
	$annee = str_pad($annee, 4, '0', STR_PAD_LEFT);
	if (intval($jour)) {
		$jour = str_pad($jour, 2, '0', STR_PAD_LEFT);
		$mois = str_pad($mois, 2, '0', STR_PAD_LEFT);

		return "$jour$sep$mois$sep$annee";
	}
	if (intval($mois)) {
		$mois = str_pad($mois, 2, '0', STR_PAD_LEFT);

		return "$mois$sep$annee";
	}

	return $annee;
}

/**
 * Identifier le formulaire en faisant abstraction des paramètres qui
 * ne représentent pas l'objet edité
 *
 * @param string $objet
 *     Type d'objet
 * @param int $id_objet
 *     Identifiant de l'objet
 * @param string $retour
 *     URL de redirection après le traitement
 * @param array|string $options
 *     Options.
 * @return string
 *     Hash du formulaire
 **/
function formulaires_dater_identifier_dist($objet, $id_objet, $retour = '', $options = array()) {
	return serialize(array($objet, $id_objet));
}

/**
 * Vérifications avant traitements du formulaire d'édition d'une date
 *
 * @param string $objet
 *     Type d'objet
 * @param int $id_objet
 *     Identifiant de l'objet
 * @param string $retour
 *     URL de redirection après le traitement
 * @param array|string $options
 *     Options.
 * @return Array
 *     Tableau des erreurs
 */
function formulaires_dater_verifier_dist($objet, $id_objet, $retour = '', $options = array()) {
	$erreurs = array();

	// ouvrir le formulaire en edition ?
	if (_request('_saisie_en_cours')) {
		$erreurs['message_erreur'] = '';

		return $erreurs;
	}

	if (_request('changer')) {
		foreach (array('date', 'date_redac') as $k) {
			if ($v = _request($k . '_jour') and !dater_recuperer_date_saisie($v, $k)) {
				$erreurs[$k] = _T('format_date_incorrecte');
			} elseif ($v = _request($k . '_heure') and !dater_recuperer_heure_saisie($v)) {
				$erreurs[$k] = _T('format_heure_incorrecte');
			}
		}

		if (!_request('date_jour')) {
			$erreurs['date'] = _T('info_obligatoire');
		}
	}

	return $erreurs;
}

/**
 * Traitement du formulaire d'édition d'une date
 *
 * @param string $objet
 *     Type d'objet
 * @param int $id_objet
 *     Identifiant de l'objet
 * @param string $retour
 *     URL de redirection après le traitement
 * @param array|string $options
 *     Options.
 * @return Array
 *     Retours des traitements
 */
function formulaires_dater_traiter_dist($objet, $id_objet, $retour = '', $options = array()) {
	$res = array('editable' => ' ');

	if (_request('changer')) {
		$table = table_objet($objet);
		$trouver_table = charger_fonction('trouver_table', 'base');
		$desc = $trouver_table($table);

		if (!$desc) {
			return array('message_erreur' => _L('erreur'));
		} #impossible en principe

		$champ_date = $desc['date'] ? $desc['date'] : 'date';
		if (isset($options['champ_date']) and $options['champ_date']) {
			$champ_date = $options['champ_date'];
		}

		$set = array();

		$charger = charger_fonction('charger', 'formulaires/dater/');
		$v = $charger($objet, $id_objet, $retour, $options);

		if ($v['_editer_date']) {
			if (!$d = dater_recuperer_date_saisie(_request('date_jour'))) {
				$d = array(date('Y'), date('m'), date('d'));
			}
			if (!$h = dater_recuperer_heure_saisie(_request('date_heure'))) {
				$h = array(0, 0);
			}

			$set[$champ_date] = sql_format_date($d[0], $d[1], $d[2], $h[0], $h[1]);
		}

		$champ_date_redac = 'date_redac';
		if (isset($options['champ_date_redac']) and $options['champ_date_redac']) {
			$champ_date_redac = $options['champ_date_redac'];
		}
		if (isset($desc['field'][$champ_date_redac]) and $v['_editer_date_anterieure']) {
			if (!_request('date_redac_jour') or _request('sans_redac')) {
				$set[$champ_date_redac] = sql_format_date(0, 0, 0, 0, 0, 0);
			} else {
				if (!$d = dater_recuperer_date_saisie(_request('date_redac_jour'), 'date_redac')) {
					$d = array(date('Y'), date('m'), date('d'));
				}
				if (!$h = dater_recuperer_heure_saisie(_request('date_redac_heure'))) {
					$h = array(0, 0);
				}
				$set[$champ_date_redac] = sql_format_date($d[0], $d[1], $d[2], $h[0], $h[1]);
			}
		}

		if (count($set)) {
			$publie_avant = objet_test_si_publie($objet, $id_objet);
			include_spip('action/editer_objet');
			objet_modifier($objet, $id_objet, $set);
			$publie_apres = objet_test_si_publie($objet, $id_objet);
			if ($publie_avant !== $publie_apres) {
				// on refuse ajax pour forcer le rechargement de la page ici
				// on refera traiter une 2eme fois, mais c'est sans consequence
				refuser_traiter_formulaire_ajax();
			}
		}
	}

	if ($retour) {
		$res['redirect'] = $retour;
	}

	set_request('date_jour');
	set_request('date_redac_jour');
	set_request('date_heure');
	set_request('date_redac_heure');

	return $res;
}

/**
 * Récupérer annee, mois, jour sur la date saisie
 *
 * @param string $post
 * @param string $quoi
 * @return array|string Chaîne vide si date invalide, tableau (année, mois, jour) sinon.
 */
function dater_recuperer_date_saisie($post, $quoi = 'date') {
	if (!preg_match('#^(?:(?:([0-9]{1,2})[/-])?([0-9]{1,2})[/-])?([0-9]{4}|[0-9]{1,2})#', $post, $regs)) {
		return '';
	}
	if ($quoi == 'date_redac') {
		if ($regs[3] <> '' and $regs[3] < 1001) {
			$regs[3] += 9000;
		}

		return array($regs[3], $regs[2], $regs[1]);
	} else {
		if (checkdate(intval($regs[2]), intval($regs[1]), intval($regs[3]))
			and $t = mktime(0, 0, 0, $regs[2], $regs[1], $regs[3])
		) {
			return array(date('Y', $t), date('m', $t), date('d', $t));
		}
		return '';
	}
}

/**
 * Récupérer heures,minutes sur l'heure saisie
 *
 * @param string $post
 * @return array
 */
function dater_recuperer_heure_saisie($post) {
	if (!preg_match('#([0-9]{1,2})(?:[h:](?:([0-9]{1,2}))?)?#', $post, $regs)) {
		return '';
	}
	if ($regs[1] > 23 or $regs[2] > 59) {
		return '';
	}

	return array($regs[1], $regs[2]);
}
