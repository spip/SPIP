<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
\***************************************************************************/

/**
 * Déclaration de filtres de dates pour les squelettes
 *
 * @package SPIP\Core\Filtres
 **/
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Normaliser une date vers le format datetime (Y-m-d H:i:s)
 *
 * @note
 *     Si elle vient du contexte (public/parametrer.php), on force le jour
 *
 * @filtre
 * @link https://www.spip.net/5518
 * @uses vider_date()
 * @param string $date
 *     La date à normaliser
 * @param bool $forcer_jour
 *     true pour forcer à indiquer un jour et mois (01) s'il n'y en a pas.
 * @return string
 *     - une date au format datetime
 *     - une chaîne vide si la date est considérée nulle
 **/
function normaliser_date($date, $forcer_jour = false): string {
	$date = vider_date($date);
	if ($date) {
		if (preg_match('/^\d{8,10}$/', $date)) {
			$date = date('Y-m-d H:i:s', $date);
		}
		if (preg_match('#^([12]\d{3})([-/]00)?( [-0-9:]+)?$#', $date, $regs)) {
			$regs = array_pad($regs, 4, null); // eviter notice php
			$date = $regs[1] . '-00-00' . $regs[3];
		} else {
			if (preg_match('#^([12]\d{3}[-/][01]?\d)([-/]00)?( [-0-9:]+)?$#', $date, $regs)) {
				$regs = array_pad($regs, 4, null); // eviter notice php
				$date = preg_replace('@/@', '-', (string) $regs[1]) . '-00' . $regs[3];
			} else {
				$date = date('Y-m-d H:i:s', strtotime($date));
			}
		}

		if ($forcer_jour) {
			$date = str_replace('-00', '-01', $date);
		}
	}

	return $date;
}

/**
 * Enlève une date considérée comme vide
 *
 * @param string $letexte
 * @param bool $verif_format_date
 * @return string
 *     - La date entrée (si elle n'est pas considérée comme nulle)
 *     - Une chaine vide
 **/
function vider_date($letexte, $verif_format_date = false): string {
	$letexte ??= '';
	if (
		!$verif_format_date
		|| in_array(strlen($letexte), [10,19]) && preg_match('/^\d{4}-\d{2}-\d{2}(\s\d{2}:\d{2}:\d{2})?$/', $letexte)
	) {
		if (strncmp('0000-00-00', $letexte, 10) == 0) {
			return '';
		}
		if (strncmp('0001-01-01', $letexte, 10) == 0) {
			return '';
		}
		if (strncmp('1970-01-01', $letexte, 10) == 0) {
			return '';
		}  // eviter le bug GMT-1
	}
	return $letexte;
}

/**
 * Retrouve à partir d'une chaîne les valeurs heures, minutes, secondes.
 *
 * Les formats `11:29:55` ou `11:29` sont autorisés.
 *
 * @param string $date
 *     Chaîne de date contenant éventuellement un horaire
 * @return array
 *     - [heures, minutes, secondes] si format horaire autorisé
 *     - [0, 0, 0] sinon
 **/
function recup_heure($date): array {

	if (preg_match('#(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?#', $date, $elements)) {
		array_shift($elements);
		if (!isset($elements[2])) {
			$elements[2] = 0;
		}
		$heure = $elements;
	} else {
		$heure = [0, 0, 0];
	}

	return $heure;
}

/**
 * Retourne l'heure d'une date
 *
 * @filtre
 * @link https://www.spip.net/4293
 * @uses recup_heure()
 *
 * @param string $numdate La date à extraire
 * @return string heures, sinon 0
 **/
function heures($numdate): string {
	$heures = null;
	$date_array = recup_heure($numdate);
	if ($date_array) {
		[$heures, $minutes, $secondes] = $date_array;
	}

	return $heures;
}

/**
 * Retourne les minutes d'une date
 *
 * @filtre
 * @link https://www.spip.net/4300
 * @uses recup_heure()
 *
 * @param string $numdate La date à extraire
 * @return string minutes, sinon 0
 **/
function minutes($numdate): string {
	$minutes = null;
	$date_array = recup_heure($numdate);
	if ($date_array) {
		[$heures, $minutes, $secondes] = $date_array;
	}

	return $minutes;
}

/**
 * Retourne les secondes d'une date
 *
 * @filtre
 * @link https://www.spip.net/4312
 * @uses recup_heure()
 *
 * @param string $numdate La date à extraire
 * @return string secondes, sinon 0
 **/
function secondes($numdate): string {
	$secondes = null;
	$date_array = recup_heure($numdate);
	if ($date_array) {
		[$heures, $minutes, $secondes] = $date_array;
	}

	return $secondes;
}

/**
 * Retourne l'horaire (avec minutes) d'une date, tel que `12h36min`
 *
 * @note
 *     Le format de retour varie selon la langue utilisée.
 *
 * @filtre
 * @link https://www.spip.net/5519
 *
 * @param string $numdate La date à extraire
 * @param string $forme.
 *	- si vide,  précise l'unité des minutes : 12h10min
 *	- si 'abbr' ne précise pas l'unité des minutes : 12h10
 * @return string L'heure formatée dans la langue en cours.
 **/
function heures_minutes($numdate, $forme = ''): string {
	if ($forme !== 'abbr') {
		return _T('date_fmt_heures_minutes', ['h' => heures($numdate), 'm' => minutes($numdate)]);
	} else {
		return _T('date_fmt_heures_minutes_court', ['h' => heures($numdate), 'm' => minutes($numdate)]);
	}
}

/**
 * Retrouve à partir d'une date les valeurs année, mois, jour, heures, minutes, secondes
 *
 * Annee, mois, jour sont retrouvés si la date contient par exemple :
 * - '03/11/2015', '3/11/15'
 * - '2015-11-04', '2015-11-4'
 * - '2015-11'
 *
 * Dans ces cas, les heures, minutes, secondes sont retrouvés avec `recup_heure()`
 *
 * Annee, mois, jour, heures, minutes, secondes sont retrouvés si la date contient par exemple :
 * - '20151104111420'
 *
 * @uses recup_heure()
 *
 * @param string $numdate La date à extraire
 * @param bool $forcer_jour
 *     True pour tout le temps renseigner un jour ou un mois (le 1) s'il
 *     ne sont pas indiqués dans la date.
 * @return array [année, mois, jour, heures, minutes, secondes] ou []
 **/
function recup_date($numdate, $forcer_jour = true): array {
	if (!$numdate) {
		return [];
	}
	$heures = $minutes = $secondes = 0;
	if (preg_match('#(\d{1,2})/(\d{1,2})/(\d{4}|\d{1,2})#', $numdate, $regs)) {
		$jour = $regs[1];
		$mois = $regs[2];
		$annee = $regs[3];
		if ($annee < 90) {
			$annee = 2000 + $annee;
		} elseif ($annee < 100) {
			$annee = 1900 + $annee;
		}
		[$heures, $minutes, $secondes] = recup_heure($numdate);
	} elseif (preg_match('#(\d{4})-(\d{1,2})-(\d{1,2})#', $numdate, $regs)) {
		$annee = $regs[1];
		$mois = $regs[2];
		$jour = $regs[3];
		[$heures, $minutes, $secondes] = recup_heure($numdate);
	} elseif (preg_match('#(\d{4})-(\d{2})#', $numdate, $regs)) {
		$annee = $regs[1];
		$mois = $regs[2];
		$jour = '';
		[$heures, $minutes, $secondes] = recup_heure($numdate);
	} elseif (preg_match('#^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$#', $numdate, $regs)) {
		$annee = $regs[1];
		$mois = $regs[2];
		$jour = $regs[3];
		$heures = $regs[4];
		$minutes = $regs[5];
		$secondes = $regs[6];
	} else {
		$annee = $mois = $jour = '';
	}
	if ($annee > 4000) {
		$annee -= 9000;
	}
	if (strlen($jour) && str_starts_with($jour, '0')) {
		$jour = substr($jour, 1);
	}

	if ($forcer_jour && $jour == '0') {
		$jour = '1';
	}
	if ($forcer_jour && $mois == '0') {
		$mois = '1';
	}
	if ($annee || $mois || $jour || $heures || $minutes || $secondes) {
		return [$annee, $mois, $jour, $heures, $minutes, $secondes];
	}
	return [];
}

/**
 * Retourne une date relative si elle est récente, sinon une date complète
 *
 * En fonction de la date transmise, peut retourner par exemple :
 * - «il y a 3 minutes»,
 * - «il y a 11 heures»,
 * - «10 mai 2015 à 10h23min»
 *
 * @example `[(#DATE|date_interface)]`
 *
 * @filtre
 * @link https://www.spip.net/5520
 * @uses date_relative()
 * @uses affdate_heure() utilisé si le décalage est trop grand
 *
 * @param string $date
 *     La date fournie
 * @param int $decalage_maxi
 *     Durée écoulée, en secondes, à partir de laquelle on bascule sur une date complète.
 *     Par défaut +/- 12h.
 * @return string
 *     La date relative ou complète
 **/
function date_interface($date, $decalage_maxi = 43200 /* 12*3600 */): string {
	return sinon(
		date_relative($date, $decalage_maxi),
		affdate_heure($date)
	);
}

/**
 * Retourne une date relative (passée ou à venir)
 *
 * En fonction de la date transmise ainsi que de la date de référence
 * (par défaut la date actuelle), peut retourner par exemple :
 * - «il y a 3 minutes»,
 * - «il y a 2 semmaines»,
 * - «dans 1 semaine»
 *
 * @example
 *     - `[(#DATE|date_relative)]`
 *     - `[(#DATE|date_relative{43200})]`
 *     - `[(#DATE|date_relative{0, #AUTRE_DATE})]` Calcul relatif à une date spécifique
 *
 * @filtre
 * @link https://www.spip.net/4277
 *
 * @param string $date
 *     La date fournie
 * @param int $decalage_maxi
 *     Durée écoulée, en secondes, au delà de laquelle on ne retourne pas de date relative
 *     Indiquer `0` (par défaut) pour ignorer.
 * @param string $ref_date
 *     La date de référence pour le calcul relatif, par défaut la date actuelle
 * @return string
 *     - La date relative
 *     - "" si pas de date ou si elle dépasse le décalage maximum indiqué.
 **/
function date_relative($date, $decalage_maxi = 0, $ref_date = null): string {

	if (!$date) {
		return '';
	}

	$ref_time = is_null($ref_date) ? time() : strtotime($ref_date);

	$decal = date('U', $ref_time) - date('U', strtotime($date));

	if ($decalage_maxi && ($decal > $decalage_maxi || $decal < 0)) {
		return '';
	}

	if ($decal < 0) {
		$il_y_a = 'date_dans';
		$decal = -1 * $decal;
	} else {
		$il_y_a = 'date_il_y_a';
	}

	if ($decal > 3600 * 24 * 30 * 6) {
		return affdate_court($date);
	}

	if ($decal > 3600 * 24 * 30) {
		$mois = floor($decal / (3600 * 24 * 30));
		$delai = $mois < 2
			? "$mois " . _T('date_un_mois')
			: "$mois " . _T('date_mois');
	} else {
		if ($decal > 3600 * 24 * 7) {
			$semaines = floor($decal / (3600 * 24 * 7));
			$delai = $semaines < 2
				? "$semaines " . _T('date_une_semaine')
				: "$semaines " . _T('date_semaines');
		} else {
			if ($decal > 3600 * 24) {
				$jours = floor($decal / (3600 * 24));
				if ($jours < 2) {
					return $il_y_a == 'date_dans' ? _T('date_demain') : _T('date_hier');
				} else {
					$delai = "$jours " . _T('date_jours');
				}
			} else {
				if ($decal >= 3600) {
					$heures = floor($decal / 3600);
					$delai = $heures < 2
						? "$heures " . _T('date_une_heure')
						: "$heures " . _T('date_heures');
				} else {
					if ($decal >= 60) {
						$minutes = floor($decal / 60);
						$delai = $minutes < 2
							? "$minutes " . _T('date_une_minute')
							: "$minutes " . _T('date_minutes');
					} else {
						$secondes = ceil($decal);
						$delai = $secondes < 2
							? "$secondes " . _T('date_une_seconde')
							: "$secondes " . _T('date_secondes');
					}
				}
			}
		}
	}

	return _T($il_y_a, ['delai' => $delai]);
}


/**
 * Retourne une date relative courte (passée ou à venir)
 *
 * Retourne «hier», «aujourd'hui» ou «demain» si la date correspond, sinon
 * utilise `date_relative()`
 *
 * @example `[(#DATE|date_relativecourt)]`
 *
 * @filtre
 * @uses date_relative()
 *
 * @param string $date
 *     La date fournie
 * @param int $decalage_maxi
 *     Durée écoulée, en secondes, au delà de laquelle on ne retourne pas de date relative
 *     Indiquer `0` (par défaut) pour ignorer.
 * @return string
 *     - La date relative
 *     - "" si pas de date ou si elle dépasse le décalage maximum indiqué.
 **/
function date_relativecourt($date, $decalage_maxi = 0): string {

	if (!$date) {
		return '';
	}
	$decal = date('U', strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($date))));

	if ($decalage_maxi && ($decal > $decalage_maxi || $decal < 0)) {
		return '';
	}

	if ($decal < -24 * 3600) {
		$retour = date_relative($date, $decalage_maxi);
	} elseif ($decal < 0) {
		$retour = _T('date_demain');
	} else {
		if ($decal < (3600 * 24)) {
			$retour = _T('date_aujourdhui');
		} else {
			$retour = $decal < (3600 * 24 * 2)
				? _T('date_hier')
				: date_relative($date, $decalage_maxi);
		}
	}

	return $retour;
}

/**
 * Formatage humain de la date `$numdate` selon le format `$vue`
 *
 * @param string $numdate
 *     Une écriture de date
 * @param string $vue
 *     Type de format souhaité ou expression pour `strtotime()` tel que `Y-m-d h:i:s`
 * @param array $options {param: string, annee_courante: int}
 *  - param: 'abbr' ou 'initiale' permet d'afficher les jours au format court ou initiale
 *  - annee_courante: Permet de definir l'annee de reference pour l'affichage des dates courtes
 *
 * @return string
 */
function affdate_base($numdate, $vue, $options = []): string {
	if (is_string($options)) {
		$options = ['param' => $options];
	}
	$date_array = recup_date($numdate, false);
	if (!$date_array) {
		return '';
	}
	[$annee, $mois, $jour, $heures, $minutes, $secondes] = $date_array;

	// 1er, 21st, etc.
	$journum = $jour;

	if ($jour == 0) {
		$jour = '';
		$njour = 0;
	} else {
		$njour = (int) $jour;
		if ($jourth = _T('date_jnum' . $jour)) {
			$jour = $jourth;
		}
	}

	$mois = (int) $mois;
	if ($mois > 0 && $mois < 13) {
		/* Traiter le cas "abbr" pour les noms de mois */
		$param = ((isset($options['param']) && $options['param'] === 'abbr') ? '_' . $options['param'] : '');
		$nommois = _T('date_mois_' . $mois . $param);
		$jourmois = $jour
			? _T('date_de_mois_' . $mois, ['j' => $jour, 'nommois' => $nommois])
			: $nommois;
	} else {
		$nommois = '';
		$jourmois = '';
	}

	if ($annee < 0) {
		$annee = -$annee . ' ' . _T('date_avant_jc');
		$avjc = true;
	} else {
		$avjc = false;
	}

	switch ($vue) {
		case 'saison':
		case 'saison_annee':
			$saison = '';
			if ($mois > 0) {
				$saison = ($options['param'] == 'sud') ? 3 : 1;
				if ($mois == 3 && $jour >= 21 || $mois > 3) {
					$saison = ($options['param'] == 'sud') ? 4 : 2;
				}
				if ($mois == 6 && $jour >= 21 || $mois > 6) {
					$saison = ($options['param'] == 'sud') ? 1 : 3;
				}
				if ($mois == 9 && $jour >= 21 || $mois > 9) {
					$saison = ($options['param'] == 'sud') ? 2 : 4;
				}
				if ($mois == 12 && $jour >= 21 || $mois > 12) {
					$saison = ($options['param'] == 'sud') ? 3 : 1;
				}
			}
			if ($vue == 'saison') {
				return $saison ? _T('date_saison_' . $saison) : '';
			} else {
				return $saison ? trim((string) _T(
					'date_fmt_saison_annee',
					['saison' => _T('date_saison_' . $saison), 'annee' => $annee]
				)) : '';
			}

		case 'court':
			if ($avjc) {
				return $annee;
			}
			$a = ((isset($options['annee_courante']) && $options['annee_courante']) ? $options['annee_courante'] : date('Y'));
			if ($annee < ($a - 100) || $annee > ($a + 100)) {
				return $annee;
			}
			if ($annee != $a) {
				return _T(
					'date_fmt_mois_annee',
					['mois' => $mois, 'nommois' => spip_ucfirst($nommois), 'annee' => $annee]
				);
			}

			return _T(
				'date_fmt_jour_mois',
				['jourmois' => $jourmois, 'jour' => $jour, 'mois' => $mois, 'nommois' => $nommois, 'annee' => $annee]
			);

		case 'jourcourt':
			if ($avjc) {
				return $annee;
			}
			$a = ((isset($options['annee_courante']) && $options['annee_courante']) ? $options['annee_courante'] : date('Y'));
			if ($annee < ($a - 100) || $annee > ($a + 100)) {
				return $annee;
			}
			if ($annee != $a) {
				return _T(
					'date_fmt_jour_mois_annee',
					['jourmois' => $jourmois, 'jour' => $jour, 'mois' => $mois, 'nommois' => $nommois, 'annee' => $annee]
				);
			}

			return _T(
				'date_fmt_jour_mois',
				['jourmois' => $jourmois, 'jour' => $jour, 'mois' => $mois, 'nommois' => $nommois, 'annee' => $annee]
			);

		case 'entier':
			if ($avjc) {
				return $annee;
			}
			if ($jour) {
				return _T(
					'date_fmt_jour_mois_annee',
					['jourmois' => $jourmois, 'jour' => $jour, 'mois' => $mois, 'nommois' => $nommois, 'annee' => $annee]
				);
			} elseif ($mois) {
				return trim((string) _T('date_fmt_mois_annee', ['mois' => $mois, 'nommois' => $nommois, 'annee' => $annee]));
			} else {
				return $annee;
			}

		case 'nom_mois':
			return $nommois;

		case 'mois':
			return sprintf('%02s', $mois);

		case 'jour':
			return $jour;

		case 'journum':
			return $journum;

		case 'nom_jour':
			if (!$mois || !$njour) {
				return '';
			}
			$nom = mktime(1, 1, 1, $mois, $njour, $annee);
			$nom = 1 + (int) date('w', $nom);
			$param = ((isset($options['param']) && $options['param']) ? '_' . $options['param'] : '');

			return _T('date_jour_' . $nom . $param);

		case 'mois_annee':
			if ($avjc) {
				return $annee;
			}

			return trim((string) _T('date_fmt_mois_annee', ['mois' => $mois, 'nommois' => $nommois, 'annee' => $annee]));

		case 'annee':
			return $annee;

		// Cas d'une vue non definie : retomber sur le format
		// de date propose par http://www.php.net/date
		default:
			[$annee, $mois, $jour, $heures, $minutes, $secondes] = $date_array;
			// il faut envoyer jour = 1 si jour pas défini, c'est le comportement qu'on avait historiquement en envoyant ''
			if (!$time = mktime($heures, $minutes, $secondes, $mois, is_numeric($jour) ? $jour : 1, $annee)) {
				$time = strtotime($numdate);
			}
			return date($vue, $time);
	}
}


/**
 * Affiche le nom du jour pour une date donnée
 *
 * @example
 *     - `[(#DATE|nom_jour)]` lundi
 *     - `[(#DATE|nom_jour{abbr})]` lun.
 *     - `[(#DATE|nom_jour{initiale})]` l.
 *
 * @filtre
 * @link https://www.spip.net/4305
 * @uses affdate_base()
 *
 * @param string $numdate
 *     Une écriture de date
 * @param string $forme
 *     Forme spécifique de retour :
 *     - initiale : l'initiale du jour
 *     - abbr : abbréviation du jour
 *     - '' : le nom complet (par défaut)
 * @return string
 *     Nom du jour
 **/
function nom_jour($numdate, $forme = ''): string {
	if ($forme !== 'abbr' && $forme !== 'initiale') {
		$forme = '';
	}

	return affdate_base($numdate, 'nom_jour', ['param' => $forme]);
}

/**
 * Affiche le numéro du jour (1er à 31) pour une date donnée
 *
 * Utilise une abbréviation (exemple "1er") pour certains jours,
 * en fonction de la langue utilisée.
 *
 * @example `[(#DATE|jour)]`
 *
 * @filtre
 * @link https://www.spip.net/4295
 * @uses affdate_base()
 * @see  journum()
 *
 * @param string $numdate
 *     Une écriture de date
 * @return string
 *     Numéro du jour
 **/
function jour($numdate): string {
	return affdate_base($numdate, 'jour');
}

/**
 * Affiche le numéro du jour (1 à 31) pour une date donnée
 *
 * @example `[(#DATE|journum)]`
 *
 * @filtre
 * @uses affdate_base()
 * @see  jour()
 *
 * @param string $numdate
 *     Une écriture de date
 * @return string
 *     Numéro du jour
 **/
function journum($numdate): string {
	return affdate_base($numdate, 'journum');
}

/**
 * Affiche le numéro du mois (01 à 12) pour une date donnée
 *
 * @example `[(#DATE|mois)]`
 *
 * @filtre
 * @link https://www.spip.net/4303
 * @uses affdate_base()
 *
 * @param string $numdate
 *     Une écriture de date
 * @return string
 *     Numéro du mois (sur 2 chiffres)
 **/
function mois($numdate): string {
	return  affdate_base($numdate, 'mois');
}

/**
 * Affiche le nom du mois pour une date donnée
 *
 * @example
 *     - `[(#DATE|nom_mois)]` novembre
 *     - `[(#DATE|nom_mois{abbr})]` nov.
 *
 * @filtre
 * @link https://www.spip.net/4306
 * @uses affdate_base()
 *
 * @param string $numdate
 *     Une écriture de date
 * @param string $forme
 *     Forme spécifique de retour :
 *     - abbr : abbréviation du mois
 *     - '' : le nom complet (par défaut)
 * @return string
 *     Nom du mois
 **/
function nom_mois($numdate, $forme = ''): string {
	if ($forme !== 'abbr') {
		$forme = '';
	}

	return affdate_base($numdate, 'nom_mois', ['param' => $forme]);
}

/**
 * Affiche l'année sur 4 chiffres d'une date donnée
 *
 * @example `[(#DATE|annee)]`
 *
 * @filtre
 * @link https://www.spip.net/4146
 * @uses affdate_base()
 *
 * @param string $numdate
 *     Une écriture de date
 * @return string
 *     Année (sur 4 chiffres)
 **/
function annee($numdate): string {
	return affdate_base($numdate, 'annee');
}


/**
 * Affiche le nom boréal ou austral de la saison
 *
 * @filtre
 * @link https://www.spip.net/4311
 * @uses affdate_base()
 * @example
 *     En PHP
 *     ```
 *     saison("2008-10-11 14:08:45") affiche "automne"
 *     saison("2008-10-11 14:08:45", "sud") affiche "printemps"
 *     ```
 *     En squelettes
 *     ```
 *     [(#DATE|saison)]
 *     [(#DATE|saison{sud})]
 *     ```
 *
 * @param string $numdate
 *     Une écriture de date
 * @param string $hemisphere
 *     Nom optionnel de l'hémisphère (sud ou nord) ; par défaut nord
 * @return string
 *     La date formatée
 **/
function saison($numdate, $hemisphere = 'nord'): string {
	if ($hemisphere !== 'sud') {
		$hemisphere = 'nord';
	}

	return affdate_base($numdate, 'saison', ['param' => $hemisphere]);
}


/**
 * Affiche le nom boréal ou austral de la saison suivi de l'année en cours
 *
 * @filtre
 * @uses affdate_base()
 * @example
 *     En PHP
 *     ```
 *     saison_annee("2008-10-11 14:08:45") affiche "automne 2008"
 *     saison_annee("2008-10-11 14:08:45", "sud") affiche "printemps 2008"
 *     ```
 *     En squelettes
 *     ```
 *     [(#DATE|saison_annee)]
 *     [(#DATE|saison_annee{sud})]
 *     ```
 *
 * @param string $numdate
 *     Une écriture de date
 * @param string $hemisphere
 *     Nom optionnel de l'hémisphère (sud ou nord) ; par défaut nord
 * @return string
 *     La date formatée
 **/
function saison_annee($numdate, $hemisphere = 'nord'): string {
	if ($hemisphere !== 'sud') {
		$hemisphere = 'nord';
	}

	return affdate_base($numdate, 'saison_annee', ['param' => $hemisphere]);
}

/**
 * Formate une date
 *
 * @example
 *     En PHP`affdate("2008-10-11 14:08:45")` affiche "11 octobre 2008"
 *
 * @example
 *     En squelettes
 *     - `[(#DATE|affdate)]`
 *     - `[(#DATE|affdate{Y-m-d})]`
 *
 * @filtre
 * @link https://www.spip.net/4129
 * @uses affdate_base()
 * @see  affdate_court()
 * @see  affdate_jourcourt()
 *
 * @param string $numdate
 *     Une écriture de date
 * @param string $format
 *     Type de format souhaité ou expression pour `strtotime()` tel que `Y-m-d h:i:s`
 * @return string
 *     La date formatée
 **/
function affdate($numdate, $format = 'entier'): string {
	return affdate_base($numdate, $format);
}


/**
 * Formate une date, omet l'année si année courante, sinon omet le jour
 *
 * Si l'année actuelle (ou indiquée dans `$annee_courante`) est 2015,
 * retournera "21 juin" si la date en entrée est le 21 juin 2015,
 * mais retournera "juin 2013" si la date en entrée est le 21 juin 2013.
 *
 * @example `[(#DATE|affdate_court)]`
 *
 * @filtre
 * @link https://www.spip.net/4130
 * @uses affdate_base()
 * @see  affdate()
 * @see  affdate_jourcourt()
 *
 * @param string $numdate
 *     Une écriture de date
 * @param int|null $annee_courante
 *     L'année de comparaison, utilisera l'année en cours si omis.
 * @return string
 *     La date formatée
 **/
function affdate_court($numdate, $annee_courante = null): string {
	return affdate_base($numdate, 'court', ['annee_courante' => $annee_courante]);
}


/**
 * Formate une date, omet l'année si année courante
 *
 * Si l'année actuelle (ou indiquée dans `$annee_courante`) est 2015,
 * retournera "21 juin" si la date en entrée est le 21 juin 2015,
 * mais retournera "21 juin 2013" si la date en entrée est le 21 juin 2013.
 *
 * @example `[(#DATE|affdate_jourcourt)]`
 *
 * @filtre
 * @link https://www.spip.net/4131
 * @uses affdate_base()
 * @see  affdate()
 * @see  affdate_court()
 *
 * @param string $numdate
 *     Une écriture de date
 * @param int|null $annee_courante
 *     L'année de comparaison, utilisera l'année en cours si omis.
 * @return string
 *     La date formatée
 **/
function affdate_jourcourt($numdate, $annee_courante = null): string {
	return affdate_base($numdate, 'jourcourt', ['annee_courante' => $annee_courante]);
}

/**
 * Retourne le mois en toute lettre et l’année d'une date
 *
 * Ne retourne pas le jour donc.
 *
 * @filtre
 * @link https://www.spip.net/4132
 * @uses affdate_base()
 *
 * @param string $numdate
 *     Une écriture de date
 * @return string
 *     La date formatée
 **/
function affdate_mois_annee($numdate): string {
	return affdate_base($numdate, 'mois_annee');
}

/**
 * Retourne la date suivie de l'heure
 *
 * @example `[(#DATE|affdate_heure)]` peut donner "11 novembre 2015 à 11h10min"
 *
 * @filtre
 * @uses recup_date()
 * @uses affdate()
 *
 * @param string $numdate
 *     Une écriture de date
 * @return string
 *     La date formatée, sinon ''
 **/
function affdate_heure($numdate): string {
	$date_array = recup_date($numdate);
	if (!$date_array) {
		return '';
	}
	[$annee, $mois, $jour, $heures, $minutes, $sec] = $date_array;

	return _T('date_fmt_jour_heure', [
		'jour' => affdate($numdate),
		'heure' => _T('date_fmt_heures_minutes', ['h' => $heures, 'm' => $minutes])
	]);
}

/**
 * Afficher de facon textuelle les dates de début et fin en fonction des cas
 *
 * - Lundi 20 fevrier a 18h
 * - Le 20 fevrier de 18h a 20h
 * - Du 20 au 23 fevrier
 * - Du 20 fevrier au 30 mars
 * - Du 20 fevrier 2007 au 30 mars 2008
 *
 * `$horaire='oui'` ou `true` permet d'afficher l'horaire,
 * toute autre valeur n'indique que le jour
 * `$forme` peut contenir une ou plusieurs valeurs parmi
 *  - `abbr` (afficher le nom des jours en abrege)
 *  - `hcal` (generer une date au format hcal)
 *  - `jour` (forcer l'affichage des jours)
 *  - `annee` (forcer l'affichage de l'annee)
 *
 * @param string $date_debut
 * @param string $date_fin
 * @param string $horaire
 * @param string $forme
 *   - `abbr` pour afficher le nom du jour en abrege (Dim. au lieu de Dimanche)
 *   - `annee` pour forcer l'affichage de l'annee courante
 *   - `jour` pour forcer l'affichage du nom du jour
 *   - `hcal` pour avoir un markup microformat abbr
 * @return string
 *     texte de la date
 */
function affdate_debut_fin($date_debut, $date_fin, $horaire = 'oui', $forme = ''): string {
	$abbr = $jour = '';
	$affdate = 'affdate_jourcourt';
	if (str_contains($forme, 'abbr')) {
		$abbr = 'abbr';
	}
	if (str_contains($forme, 'annee')) {
		$affdate = 'affdate';
	}
	if (str_contains($forme, 'jour')) {
		$jour = 'jour';
	}

	$dtstart = $dtend = $dtabbr = '';
	if (str_contains($forme, 'hcal')) {
		$dtstart = "<abbr class='dtstart' title='" . date_iso($date_debut) . "'>";
		$dtend = "<abbr class='dtend' title='" . date_iso($date_fin) . "'>";
		$dtabbr = '</abbr>';
	}

	$date_debut = strtotime($date_debut);
	$date_fin = strtotime($date_fin);
	$d = date('Y-m-d', $date_debut);
	$f = date('Y-m-d', $date_fin);
	$h = ($horaire === 'oui' || $horaire === true);
	$hd = _T('date_fmt_heures_minutes_court', ['h' => date('H', $date_debut), 'm' => date('i', $date_debut)]);
	$hf = _T('date_fmt_heures_minutes_court', ['h' => date('H', $date_fin), 'm' => date('i', $date_fin)]);

	if ($d === $f) { // meme jour
		$nomjour = nom_jour($d, $abbr);
		$s = $affdate($d);
		$s = _T('date_fmt_jour', ['nomjour' => $nomjour, 'jour' => $s]);
		if ($h) {
			if ($hd === $hf) {
				// Lundi 20 fevrier a 18h25
				$s = spip_ucfirst(_T('date_fmt_jour_heure', ['jour' => $s, 'heure' => $hd]));
				$s = "$dtstart$s$dtabbr";
			} else {
				// Le <abbr...>lundi 20 fevrier de 18h00</abbr> a <abbr...>20h00</abbr>
				if ($dtabbr && $dtstart && $dtend) {
					$s = _T(
						'date_fmt_jour_heure_debut_fin_abbr',
						[
						'jour' => spip_ucfirst($s),
						'heure_debut' => $hd,
						'heure_fin' => $hf,
						'dtstart' => $dtstart,
						'dtend' => $dtend,
						'dtabbr' => $dtabbr
						],
						[
							'sanitize' => false
						]
					);
				} // Le lundi 20 fevrier de 18h00 a 20h00
				else {
					$s = spip_ucfirst(_T(
						'date_fmt_jour_heure_debut_fin',
						['jour' => $s, 'heure_debut' => $hd, 'heure_fin' => $hf]
					));
				}
			}
		} else {
			$s = $dtabbr && $dtstart
				? $dtstart . spip_ucfirst($s) . $dtabbr
				: spip_ucfirst($s);
		}
	} else {
		if (date('Y-m', $date_debut) === date('Y-m', $date_fin)) { // meme annee et mois, jours differents
			$date_debut = $h
				? affdate_jourcourt($d, date('Y', $date_fin))
				: jour($d);
			$date_fin = $affdate($f);
			if ($jour) {
				$nomjour_debut = nom_jour($d, $abbr);
				$date_debut = _T('date_fmt_jour', ['nomjour' => $nomjour_debut, 'jour' => $date_debut]);
				$nomjour_fin = nom_jour($f, $abbr);
				$date_fin = _T('date_fmt_jour', ['nomjour' => $nomjour_fin, 'jour' => $date_fin]);
			}
			if ($h) {
				$date_debut = _T('date_fmt_jour_heure', ['jour' => $date_debut, 'heure' => $hd]);
				$date_fin = _T('date_fmt_jour_heure', ['jour' => $date_fin, 'heure' => $hf]);
			}
			$date_debut = $dtstart . $date_debut . $dtabbr;
			$date_fin = $dtend . $date_fin . $dtabbr;

			$s = _T('date_fmt_periode', ['date_debut' => $date_debut, 'date_fin' => $date_fin]);
		} else {
			$date_debut = affdate_jourcourt($d, date('Y', $date_fin));
			$date_fin = $affdate($f);
			if ($jour) {
				$nomjour_debut = nom_jour($d, $abbr);
				$date_debut = _T('date_fmt_jour', ['nomjour' => $nomjour_debut, 'jour' => $date_debut]);
				$nomjour_fin = nom_jour($f, $abbr);
				$date_fin = _T('date_fmt_jour', ['nomjour' => $nomjour_fin, 'jour' => $date_fin]);
			}
			if ($h) {
				$date_debut = _T('date_fmt_jour_heure', ['jour' => $date_debut, 'heure' => $hd]);
				$date_fin = _T('date_fmt_jour_heure', ['jour' => $date_fin, 'heure' => $hf]);
			}

			$date_debut = $dtstart . $date_debut . $dtabbr;
			$date_fin = $dtend . $date_fin . $dtabbr;
			$s = _T('date_fmt_periode', ['date_debut' => $date_debut, 'date_fin' => $date_fin]);
		}
	}

	return $s;
}

/**
 * Adapte une date pour être insérée dans une valeur de date d'un export ICAL
 *
 * Retourne une date au format `Ymd\THis\Z`, tel que '20150428T163254Z'
 *
 * @example `DTSTAMP:[(#DATE|date_ical)]`
 * @filtre
 * @uses recup_heure()
 * @uses recup_date()
 *
 * @param string $date
 *     La date
 * @param int $addminutes
 *     Ajouter autant de minutes à la date
 * @return string
 *     Date au format ical
 **/
function date_ical($date, $addminutes = 0): string {
	[$heures, $minutes, $secondes] = recup_heure($date);
	[$annee, $mois, $jour] = recup_date($date);

	return gmdate('Ymd\THis\Z', mktime($heures, $minutes + $addminutes, $secondes, $mois, $jour, $annee));
}


/**
 * Retourne une date formattée au format "RFC 3339" ou "ISO 8601"
 *
 * @example `[(#DATE|date_iso)]` peut donner "2015-11-11T10:13:45Z"
 *
 * @filtre
 * @link https://www.spip.net/5641
 * @link https://fr.wikipedia.org/wiki/ISO_8601
 * @link http://www.ietf.org/rfc/rfc3339.txt
 * @link http://php.net/manual/fr/class.datetime.php
 *
 * @uses recup_date()
 * @uses recup_heure()
 *
 * @param string $date_heure
 *     Une écriture de date
 * @return string
 *     La date formatée
 **/
function date_iso($date_heure): string {
	$date = recup_date($date_heure);
	$annee = $date[0] ?? null;
	$mois = $date[1] ?? null;
	$jour = $date[2] ?? null;
	[$heures, $minutes, $secondes] = recup_heure($date_heure);
	$time = @mktime($heures, $minutes, $secondes, $mois, $jour, $annee);

	return gmdate('Y-m-d\TH:i:s\Z', $time);
}

/**
 * Retourne une date formattée au format "RFC 822"
 *
 * Utilisé pour `<pubdate>` dans certains flux RSS
 *
 * @example `[(#DATE|date_822)]` peut donner "Wed, 11 Nov 2015 11:13:45 +0100"
 *
 * @filtre
 * @link https://www.spip.net/4276
 * @link http://php.net/manual/fr/class.datetime.php
 *
 * @uses recup_date()
 * @uses recup_heure()
 *
 * @param string $date_heure
 *     Une écriture de date
 * @return string
 *     La date formatée
 **/
function date_822($date_heure): string {
	[$annee, $mois, $jour] = recup_date($date_heure);
	[$heures, $minutes, $secondes] = recup_heure($date_heure);
	$time = mktime($heures, $minutes, $secondes, $mois, $jour, $annee);

	return date('r', $time);
}

/**
 * Pour une date commençant par `Y-m-d`, retourne `Ymd`
 *
 * @example `date_anneemoisjour('2015-10-11 11:27:03')` retourne `20151011`
 * @see date_anneemois()
 *
 * @param string $d
 *     Une écriture de date commençant par un format `Y-m-d` (comme date ou datetime SQL).
 *     Si vide, utilise la date actuelle.
 * @return string
 *     Date au format `Ymd`
 **/
function date_anneemoisjour($d): string {
	if (!$d) {
		$d = date('Y-m-d');
	}

	return substr($d, 0, 4) . substr($d, 5, 2) . substr($d, 8, 2);
}

/**
 * Pour une date commençant par `Y-m`, retourne `Ym`
 *
 * @example `date_anneemoisjour('2015-10-11 11:27:03')` retourne `201510`
 * @see date_anneemoisjour()
 *
 * @param string $d
 *     Une écriture de date commençant par un format `Y-m` (comme date ou datetime SQL).
 *     Si vide, utilise la date actuelle.
 * @return string
 *     Date au format `Ym`
 **/
function date_anneemois($d): string {
	if (!$d) {
		$d = date('Y-m-d');
	}

	return substr($d, 0, 4) . substr($d, 5, 2);
}

/**
 * Retourne le premier jour (lundi) de la même semaine au format `Ymd`
 *
 * @example `date_debut_semaine(2015, 11, 11)` retourne `20151109`
 * @see date_fin_semaine()
 *
 * @param int $annee
 * @param int $mois
 * @param int $jour
 * @return string
 *     Date au lundi de la même semaine au format `Ymd`
 **/
function date_debut_semaine($annee, $mois, $jour): string {
	$w_day = date('w', mktime(0, 0, 0, $mois, $jour, $annee));
	if ($w_day == 0) {
		$w_day = 7;
	} // Gaffe: le dimanche est zero
	$debut = $jour - $w_day + 1;

	return date('Ymd', mktime(0, 0, 0, $mois, $debut, $annee));
}

/**
 * Retourne le dernier jour (dimanche) de la même semaine au format `Ymd`
 *
 * @example `date_debut_semaine(2015, 11, 11)` retourne `20151115`
 * @see date_fin_semaine()
 *
 * @param int $annee
 * @param int $mois
 * @param int $jour
 * @return string
 *     Date au dimanche de la même semaine au format `Ymd`
 **/
function date_fin_semaine($annee, $mois, $jour): string {
	$w_day = date('w', mktime(0, 0, 0, $mois, $jour, $annee));
	if ($w_day == 0) {
		$w_day = 7;
	} // Gaffe: le dimanche est zero
	$debut = $jour - $w_day + 1;

	return date('Ymd', mktime(0, 0, 0, $mois, $debut + 6, $annee));
}
