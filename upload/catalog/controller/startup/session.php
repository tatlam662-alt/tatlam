<?php
namespace Opencart\Catalog\Controller\Startup;
/**
 * Class Session
 *
 * @package Opencart\Catalog\Controller\Startup
 */
class Session extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @throws \Exception
	 *
	 * @return void
	 */
	public function index(): void {
		$session = new \Opencart\System\Library\Session($this->config->get('session_engine'), $this->registry);
		$this->registry->set('session', $session);

		// API
		if (isset($this->request->get['route']) && substr(strtolower((string)$this->request->get['route']), 0, 4) == 'api/' && isset($this->request->get['api_token'])) {
			$this->load->model('setting/api');

			$this->model_setting_api->cleanSessions();

			// Make sure the IP is allowed
			$api_info = $this->model_setting_api->getApiByToken($this->request->get['api_token']);

			if ($api_info) {
				$this->session->start($this->request->get['api_token']);

				$this->model_setting_api->updateSession($api_info['api_session_id']);
			}

			return;
		}

		/*
		We are adding the session cookie outside of the session class as I believe
		PHP messed up in a big way handling sessions. Why in the hell is it so hard to
		have more than one concurrent session using cookies!

		Is it not better to have multiple cookies when accessing parts of the system
		that requires different cookie sessions for security reasons.
		*/

		// Update the session lifetime
		if ($this->config->get('config_session_expire')) {
			$this->config->set('session_expire', $this->config->get('config_session_expire'));
		}

		// Update the session SameSite
		$this->config->set('session_samesite', $this->config->get('config_session_samesite'));

		if (isset($this->request->cookie[$this->config->get('session_name')])) {
			$session_id = $this->request->cookie[$this->config->get('session_name')];
		} else {
			$session_id = '';
		}

		$session->start($session_id);

		$route = (string)($this->request->get['route'] ?? '');
		if ($route !== 'account/logout.beaconLogout' && isset($this->session->data['auto_logout_pending_at'])) {
			if (time() - (int)$this->session->data['auto_logout_pending_at'] >= 5) {
				foreach ([
					'customer_id',
					'customer',
					'customer_token',
					'order_id',
					'shipping_address',
					'shipping_method',
					'shipping_methods',
					'payment_address',
					'payment_method',
					'payment_methods',
					'comment',
					'coupon',
					'reward',
					'checkout_recipient',
					'auto_logout_token'
				] as $key) {
					unset($this->session->data[$key]);
				}
			}

			unset($this->session->data['auto_logout_pending_at']);
		}

		$option = [
			// Keep the storefront session cookie browser-session scoped.
			'expires'  => 0,
			'path'     => $this->config->get('session_path'),
			'secure'   => $this->request->server['HTTPS'],
			'httponly' => true,
			'samesite' => $this->config->get('session_samesite')
		];

		setcookie($this->config->get('session_name'), $session->getId(), $option);
	}
}
