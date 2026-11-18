<?php
namespace Opencart\Catalog\Controller\Startup;
/**
 * Class Currency
 *
 * @package Opencart\Catalog\Controller\Startup
 */
class Currency extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		// Currency
		$this->load->model('localisation/currency');

		$currencies = $this->model_localisation_currency->getCurrencies();
		$code = isset($this->session->data['currency']) && is_scalar($this->session->data['currency']) ? (string)$this->session->data['currency'] : '';
		$cookie_code = isset($this->request->cookie['currency']) && is_scalar($this->request->cookie['currency']) ? (string)$this->request->cookie['currency'] : '';

		if ($cookie_code !== '' && !array_key_exists($code, $currencies)) {
			$code = $cookie_code;
		}

		if (!array_key_exists($code, $currencies)) {
			$default_code = (string)$this->config->get('config_currency');
			$code = isset($currencies[$default_code]) ? $default_code : (string)(array_key_first($currencies) ?? $default_code);
		}

		if (!isset($this->session->data['currency']) || $this->session->data['currency'] != $code) {
			$this->session->data['currency'] = $code;
		}

		// Set a new currency cookie if the code does not match the current one
		if (!isset($this->request->cookie['currency']) || $this->request->cookie['currency'] != $code) {
			$option = [
				'expires'  => time() + 60 * 60 * 24 * 30,
				'path'     => $this->config->get('session_path'),
				'secure'   => !empty($this->request->server['HTTPS']),
				'httponly' => true,
				'samesite' => 'Lax'
			];

			setcookie('currency', $code, $option);
		}

		$this->registry->set('currency', new \Opencart\System\Library\Cart\Currency($this->registry));
	}
}
