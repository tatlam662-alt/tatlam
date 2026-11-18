<?php
namespace Opencart\Catalog\Controller\Startup;
/**
 * Class Language
 *
 * @package Opencart\Catalog\Controller\Startup
 */
class Language extends \Opencart\System\Engine\Controller {
	/**
	 * @var array<string, array<string, mixed>>
	 */
	private static array $languages = [];

	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		// Language
		$this->load->model('localisation/language');

		self::$languages = $this->model_localisation_language->getLanguages();

		$language_info = [];

		// Choose only an enabled language and tolerate malformed URL parameters.
		$default_code = (string)$this->config->get('config_language_catalog');
		$request_code = isset($this->request->get['language']) && is_scalar($this->request->get['language']) ? (string)$this->request->get['language'] : '';
		$code = isset(self::$languages[$default_code]) ? $default_code : (string)(array_key_first(self::$languages) ?? '');

		if ($request_code !== '' && isset(self::$languages[$request_code])) {
			$code = $request_code;
		}

		if ($code !== '') {
			$language_info = self::$languages[$code];
		}

		if ($language_info) {
			// If extension switch add language directory
			if ($language_info['extension']) {
				$this->language->addPath('extension/' . $language_info['extension'], DIR_EXTENSION . $language_info['extension'] . '/catalog/language/');
			}

			// Set the config language_id key
			$this->config->set('config_language_id', $language_info['language_id']);
			$this->config->set('config_language', $language_info['code']);

			$this->load->language('default');
		}
	}

	/**
	 * After
	 *
	 * Override the language default values
	 *
	 * @param string       $route
	 * @param string       $prefix
	 * @param string       $code
	 * @param array<mixed> $output
	 *
	 * @return void
	 */
	public function after(&$route, &$prefix, &$code, &$output): void {
		$code = is_scalar($code) ? (string)$code : '';

		if ($code === '' || !isset(self::$languages[$code])) {
			$requested_code = isset($this->request->get['language']) && is_scalar($this->request->get['language']) ? (string)$this->request->get['language'] : '';
			$default_code = (string)$this->config->get('config_language');
			$code = isset(self::$languages[$requested_code]) ? $requested_code : (isset(self::$languages[$default_code]) ? $default_code : (string)(array_key_first(self::$languages) ?? ''));
		}

		// Use $this->language->load so it's not triggering infinite loops
		$this->language->load($route, $prefix, $code);

		if (isset(self::$languages[$code])) {
			$language_info = self::$languages[$code];

			$path = '';

			if ($language_info['extension']) {
				$extension = 'extension/' . $language_info['extension'];

				if (oc_substr($route, 0, strlen($extension)) != $extension) {
					$path = $extension . '/';
				}
			}

			// Use $this->language->load so it's not triggering infinite loops
			$this->language->load($path . $route, $prefix, $code);
		}
	}
}
