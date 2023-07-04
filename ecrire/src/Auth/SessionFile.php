<?php

namespace Spip\Auth;

final class SessionFile {

	public function __construct(
		public readonly SessionCookie $cookie = new SessionCookie(),
		private readonly Aleas $aleas = new Aleas(),
		private readonly string $directory = \_DIR_SESSIONS,
	) {
		// FIXME: injection Logger
		// FIXME: injection File / Directory Writer
		// FIXME: throw Exception
	}

	public function getPath(Alea $alea): string {
		return $this->getPathForName($alea, $this->cookie->get(), true);
	}

	public function getPathOrFail(Alea $alea): string {
		return $this->getPathForName($alea, $this->cookie->get(), false);
	}

	public function remove(): void {
		if ($file = $this->getPath(Alea::CURRENT)) {
			spip_unlink($file);
		}
	}

	/**
	 * Écrit des données dans le fichier de session prévu
	 * @return string Le chemin du fichier écrit
	 */
	public function write(array $sessionData): string {
		return $this->writeForName($this->cookie->get(), $sessionData);
	}

	/**
	 * Écrit des données dans le fichier de session de nom indiqué
	 * @return string Le chemin du fichier écrit
	 */
	public function writeForName(string $cookieSessionName, array $sessionData): string {
		$file = $this->getPathForName(Alea::CURRENT, $cookieSessionName);
		if (!ecrire_fichier_session($file, $sessionData)) {
			$msg = sprintf('Echec ecriture fichier session %s', $file);
			$this->log($msg, _LOG_HS);
			$this->throwError($msg);
			exit;
		}
		return $file;
	}


	/** Return path to session file for this Alea and Session cookie name  */
	public function getPathForName(Alea $alea, string $cookieSessionName, bool $continueOnFailure = false): string {
		$repertoire = sous_repertoire($this->directory, '', false, $continueOnFailure);
		if (!$repertoire) {
			return '';
		}
		$filename = $this->getFilenameForName($alea, $cookieSessionName, $continueOnFailure);
		if (!$filename) {
			return '';
		}

		return $repertoire . $filename;
	}

	private function getFilenameForName(Alea $alea, string $cookieSessionName, bool $continueOnFailure = false): string {
		$aleaValue = $this->aleas->getAlea($alea);
		if (!$aleaValue) {
			if (!$continueOnFailure) {
				$msg = sprintf("fichier session: %s indisponible", $alea->value);
				$this->log($msg);
				$this->throwError($msg);
			}
			return '';
		}
		$id_auteur = $this->cookie->getUserIdFromContent($cookieSessionName);
		return $id_auteur . '_' . md5($cookieSessionName . ' ' . $aleaValue) . '.php';
	}

	private function getAlea(Alea $alea, bool $continueOnFailure = false): string {
		$content = $this->aleas->getAlea($alea);
		if ($content !== null) {
			return $content;
		}
		if (!$continueOnFailure) {
			$msg = sprintf("fichier session: %s indisponible", $alea->value);
			$this->log($msg);
			$this->throwError($msg);
		}
		return '';
	}

	// FIXME: use LoggerInterface
	private function log(string $msg, int $level = _LOG_INFO) {
		spip_log($msg, 'session.' . $level);
	}

	private function throwError(string $msg) {
		// FIXME: really throw something…
		include_spip('inc/minipres');
		echo minipres();
	}
}
