<?php

namespace Spip\Auth;

final class SessionCookie {

	private const TEST_CONTENT = 'test_echec_cookie';
	private array $cookies;

	public function __construct(
		private string $cookieSessionName = 'spip_session',
	) {
		// FIXME: use injection (Symfony\Component\HttpFoundation\Request or like)
		$this->cookies = &$_COOKIE;
		// FIXME: use injection for \spip_setcookie()
	}

	public function exists(): bool {
		return array_key_exists($this->cookieSessionName, $this->cookies);
	}

	public function isValid(): bool {
		return $this->get() !== null;
	}

	/** Get id_auteur from valid cookie content */
	public function getUserId(): ?int {
		return $this->getUserIdFromContent($this->get());
	}

	/** Get id_auteur from specified cookie content */
	public function getUserIdFromContent(?string $content): ?int {
		return $content === null ? null : intval($content);
	}

	/**
	 * Read with content validation
	 *
	 * Removes existing cookie with bad content
	 */
	public function get(): ?string {
		if (!$this->exists()) {
			return null;
		}
		$content = $this->getUnsafe();
		if (!$this->validate($content)) {
			$this->remove();
			return null;
		}
		return $content;
	}

	/**
	 * Write with content validation
	 *
	 * Removes existing cookie if bad content
	 */
	public function set(string $content, int $expires = 0): bool {
		if (!$this->validate($content)) {
			$this->remove();
			return false;
		}
		return $this->setUnsafe($content, $expires);
	}

	/** Define new expires of an existing cookie */
	public function expires(int $expires = 0): bool {
		$content = $this->get();
		if ($content === null) {
			return false;
		}
		return $this->set($content, $expires);
	}

	public function validate(string $content): bool {
		return preg_match(",^\d+_[0-9a-f]{32}$,", $content);
	}

	/** Supprimer le cookie de session */
	public function remove(): void {
		if ($this->exists()) {
			$this->setUnsafe('', time() - 24 * 3600);
			unset($this->cookies[$this->cookieSessionName]);
		}
	}

	public function generateValue(int $id_auteur): string {
		$value = $id_auteur . '_' . md5(uniqid(random_int(0, mt_getrandmax()), true));
		$this->cookies[$this->cookieSessionName] = $value;
		return $value;
	}

	public function isTestContent(): bool {
		return $this->getUnsafe() === self::TEST_CONTENT;
	}

	public function setTestContent(): bool {
		return $this->setUnsafe(self::TEST_CONTENT);
	}

	/** read without content validation */
	private function getUnsafe(): ?string {
		return $this->cookies[$this->cookieSessionName] ?? null;
	}

	/** Write without content validation */
	private function setUnsafe(string $content, int $expires = 0): bool {
		$this->cookies[$this->cookieSessionName] = $content;
		return spip_setcookie($this->cookieSessionName, $content, $expires, httponly: true);
	}
}
