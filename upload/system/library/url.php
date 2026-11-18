<?php
/**
 * @package   OpenCart
 *
 * @author    Daniel Kerr
 * @copyright Copyright (c) 2005 - 2022, OpenCart, Ltd. (https://www.opencart.com/)
 * @license   https://opensource.org/licenses/GPL-3.0
 * @author    Daniel Kerr
 *
 * @see       https://www.opencart.com
 */
namespace Opencart\System\Library;
/**
 * Class URL
 */
class Url {
	/**
	 * @var string
	 */
	private string $url;
	/**
	 * @var array<int, object>
	 */
	private array $rewrite = [];

	/**
	 * Constructor
	 *
	 * @param string $url
	 */
	public function __construct(string $url) {
		$this->url = $this->normalizeLocalhostUrl($url);
	}

	/**
	 * Replace a localhost origin with the public proxy origin, when available.
	 * Keep the original path so both catalog and renamed admin URLs continue to work.
	 */
	private function normalizeLocalhostUrl(string $url): string {
		$parts = parse_url($url);

		if (!is_array($parts) || empty($parts['host'])) {
			return $url;
		}

		$baseHost = strtolower(trim((string)$parts['host'], '[]'));

		if (!in_array($baseHost, ['localhost', '127.0.0.1', '::1'], true)) {
			return $url;
		}

		$hostPattern = '/^(?:\[[0-9a-fA-F:]+\]|[a-zA-Z0-9.-]+)(?::[0-9]{1,5})?$/';
		$host = trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ''))[0]);

		if ($host === '') {
			$forwarded = trim(explode(',', (string)($_SERVER['HTTP_FORWARDED'] ?? ''))[0]);
			if (preg_match('/(?:^|;)\s*host=(?:"([^";]+)"|([^;]+))/i', $forwarded, $matches)) {
				$host = trim($matches[1] !== '' ? $matches[1] : $matches[2]);
			}
		}

		if ($host === '') {
			$host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
		}

		if ($host === '' || !preg_match($hostPattern, $host)) {
			return $url;
		}

		$forwardedProto = strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
		if ($forwardedProto === '' && preg_match('/(?:^|;)\s*proto=("?)(https?)(?:"?)(?:;|$)/i', (string)($_SERVER['HTTP_FORWARDED'] ?? ''), $matches)) {
			$forwardedProto = strtolower($matches[2]);
		}

		if (in_array($forwardedProto, ['http', 'https'], true)) {
			$scheme = $forwardedProto;
		} elseif (strtolower((string)($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')) === 'on' || strtolower((string)($_SERVER['HTTPS'] ?? '')) === 'on' || (string)($_SERVER['SERVER_PORT'] ?? '') === '443') {
			$scheme = 'https';
		} elseif (preg_match('/^(?:localhost|127\.0\.0\.1|\[::1\])(?::[0-9]{1,5})?$/i', $host)) {
			$scheme = strtolower((string)($parts['scheme'] ?? 'http'));
		} else {
			$scheme = 'https';
		}

		$path = $parts['path'] ?? '/';
		$query = isset($parts['query']) ? '?' . $parts['query'] : '';
		$fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

		return $scheme . '://' . $host . $path . $query . $fragment;
	}

	/**
	 * Add Rewrite
	 *
	 * Add a rewrite method to the URL system
	 *
	 * @param \Opencart\System\Engine\Controller $rewrite
	 *
	 * @return void
	 */
	public function addRewrite(object $rewrite): void {
		if (is_callable([$rewrite, 'rewrite'])) {
			$this->rewrite[] = $rewrite;
		}
	}

	/**
	 * Link
	 *
	 * Generates a URL
	 *
	 * @param string                      $route
	 * @param array<string, mixed>|string $args
	 * @param bool                        $js
	 *
	 * @return string
	 */
	public function link(string $route, $args = '', bool $js = false): string {
		$url = $this->url . 'index.php?route=' . $route;

		if ($args) {
			if (is_array($args)) {
				$url .= '&' . http_build_query($args);
			} else {
				$url .= '&' . trim($args, '&');
			}
		}

		foreach ($this->rewrite as $rewrite) {
			$url = $rewrite->rewrite($url);
		}

		// Normalize localhost origins introduced by legacy store settings or rewrites.
		$url = $this->normalizeLocalhostUrl($url);

		// See https://stackoverflow.com/questions/78729429/403-forbidden-when-url-contains-get-with-encoded-question-mark-unsafeallow3f
		// https://github.com/opencart/opencart/issues/14202
		$url = str_replace('%3F', '?', $url);

		if (!$js) {
			return str_replace('&', '&amp;', $url);
		} else {
			return $url;
		}
	}
}
