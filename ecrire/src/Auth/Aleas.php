<?php

namespace Spip\Auth;

/**
 * Les aléas sont utilisés pour sécuriser les scripts du répertoire `action/`
 */
final class Aleas {

	private const META_DATE = 'alea_ephemere_date';

	public function __construct() {
		// FIXME: injection of config (meta) values + search / write
		// FIXME: injection of Logger
		if (!$this->load()) {
			spip_log('aleas indisponibles', 'session');
		}
	}

	public function current(): ?string {
		return $this->getAlea(Alea::CURRENT);
	}

	public function previous(): ?string {
		return $this->getAlea(Alea::PREVIOUS);
	}

	public function getAlea(Alea $alea): ?string {
		return $GLOBALS['meta'][$alea->value] ?? null;
	}

	/** Renouveller l'alea */
	public function rotate(): void {
		$this->saveAlea(Alea::PREVIOUS, $this->current() ?? '');
		$this->saveAlea(Alea::CURRENT, $this->generateValue());
		ecrire_meta(self::META_DATE, time(), 'non');
		spip_log("renouvellement de l'alea_ephemere");
	}

	/**
	 * Charge les aléas ehpémères s'il ne sont pas encore dans la globale 'meta'
	 *
	 * Si les métas 'alea_ephemere' et 'alea_ephemere_ancien' se sont pas encore chargées
	 * en méta (car elles ne sont pas stockées, pour sécurité, dans le fichier cache des métas),
	 * alors on les récupère en base. Et on les ajoute à nos métas globales.
	 *
	 * @see touch_meta()
	 */
	private function load(): bool {
		if ($this->getAlea(Alea::CURRENT)) {
			return true;
		}

		$aleas = $this->getAleasInDb();
		foreach ($aleas as $name => $value) {
			$this->setAlea(Alea::from($name), $value);
		}
		return (bool) $aleas;
	}

	/** return array<string, string> */
	private function getAleasInDb(): array {
		include_spip('base/abstract_sql');
		$metas = sql_allfetsel(
			['nom', 'valeur'],
			'spip_meta',
			sql_in('nom', [Alea::CURRENT->value, Alea::PREVIOUS->value]),
			option: 'continue'
		);
		return array_column($metas ?: [], 'valeur', 'nom');
	}

	private function setAlea(Alea $alea, string $value): void {
		$GLOBALS['meta'][$alea->value] = $value;
	}

	private function saveAlea(Alea $alea, string $value): void {
		$this->setAlea($alea, $value);
		ecrire_meta($alea->value, $value, 'non');
	}

	private function generateValue(): string {
		return md5(creer_uniqid());
	}
}
